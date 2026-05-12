<?php
/* ATUALIZADO - conexão DB + dropdown sistemas + sistema de convite UUID + dddice */
session_start();

// ============================================================
// DDDICE — Credenciais (usadas pelo escudo do mestre)
// ============================================================
define('DDDICE_API_KEY',   'Insira sua API Key do DDDice aqui');
define('DDDICE_ROOM_SLUG', 'Insira seu room slug do DDDice aqui');

// ---- Endpoint AJAX: ?action=roll_escudo (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'roll_escudo') {
    header('Content-Type: application/json');
    $body = json_decode(file_get_contents('php://input'), true);
    $dice = $body['dice'] ?? [];

    if (empty($dice)) { echo json_encode(['error' => 'Nenhum dado enviado.']); exit; }

    $ch = curl_init('https://dddice.com/api/1.0/roll');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['dice' => $dice, 'room' => DDDICE_ROOM_SLUG]),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . DDDICE_API_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) { echo json_encode(['error' => 'cURL: ' . $curlErr]); exit; }

    $data = json_decode($response, true);
    if ($httpCode !== 200 && $httpCode !== 201) {
        $msg = $data['data']['message'] ?? $data['message'] ?? $response;
        echo json_encode(['error' => "HTTP $httpCode: $msg"]);
        exit;
    }

    $values = $data['data']['values'] ?? [];
    $total  = array_sum(array_column($values, 'value'));
    echo json_encode([
        'ok'     => true,
        'total'  => $total,
        'values' => array_map(fn($v) => ['value' => $v['value'], 'type' => $v['type'] ?? ''], $values),
    ]);
    exit;
}

// ---- Endpoint AJAX: ?action=themes_escudo (GET) ----
if (($_GET['action'] ?? '') === 'themes_escudo') {
    header('Content-Type: application/json');
    $ch = curl_init('https://dddice.com/api/1.0/dice-box');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . DDDICE_API_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($response, true);
    if ($httpCode !== 200) { echo json_encode(['error' => "HTTP $httpCode"]); exit; }
    echo json_encode(['themes' => $data['data'] ?? []]);
    exit;
}

if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../app/config/database.php';
$pdo = Database::getConexao();

// ============================================================
// FUNÇÕES
// ============================================================

/** Gera UUID v4 (36 chars) conforme RFC 4122. */
function guidv4(): string {
    $data    = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// ============================================================
// ENDPOINTS AJAX (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $usuario_id = (int) $_SESSION['usuario']['id'];

    if ($_POST['action'] === 'gerar_convite') {
        $campaign_id = (int) ($_POST['campaign_id'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT id_campanha FROM tb_campanha
            WHERE id_campanha = ? AND id_usuario_mestre = ?
        ");
        $stmt->execute([$campaign_id, $usuario_id]);

        if (!$stmt->fetch()) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
            exit;
        }

        $pdo->prepare("
            UPDATE tb_convite_campanha
               SET tp_status = 'expirado'
             WHERE id_campanha = ? AND tp_status = 'pendente'
        ")->execute([$campaign_id]);

        $token = guidv4();
        $pdo->prepare("
            INSERT INTO tb_convite_campanha
                (id_campanha, ds_token, tp_status, dt_criacao, dt_expiracao)
            VALUES (?, ?, 'pendente', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY))
        ")->execute([$campaign_id, $token]);

        $link = 'https://' . $_SERVER['HTTP_HOST'] . '/TABLE-main/pages/invite.php?token=' . $token;
        echo json_encode(['sucesso' => true, 'link' => $link, 'token' => $token]);
        exit;
    }

    echo json_encode(['sucesso' => false, 'mensagem' => 'Ação desconhecida.']);
    exit;
}

// ============================================================
// DADOS DA PÁGINA
// ============================================================
$stmt     = $pdo->query("SELECT id_sistema, nm_sistema FROM tb_sistema ORDER BY nm_sistema ASC");
$sistemas = $stmt->fetchAll();

$campanhaDados       = null;
$PersonagemsCampanha = [];
$combatesCampanha    = [];
$atributosSistema    = [];

$id_campanha = $_GET['id'] ?? null;
if ($id_campanha) {
    $stmt = $pdo->prepare("
        SELECT c.*, s.nm_sistema
          FROM tb_campanha c
          LEFT JOIN tb_sistema s ON c.id_sistema = s.id_sistema
         WHERE c.id_campanha = ?
    ");
    $stmt->execute([$id_campanha]);
    $campanhaDados = $stmt->fetch();

    if ($campanhaDados) {
        $stmt = $pdo->prepare("
            SELECT p.*, s.nm_sistema, cl.nm_classe, o.nm_origem
              FROM tb_campanha_personagem cp
              JOIN tb_personagem p  ON cp.id_personagem = p.id_personagem
              LEFT JOIN tb_sistema s ON p.id_sistema = s.id_sistema
              LEFT JOIN tb_personagem_classe pc ON p.id_personagem = pc.id_personagem
              LEFT JOIN tb_classe cl ON pc.id_classe = cl.id_classe
              LEFT JOIN tb_personagem_origem po ON p.id_personagem = po.id_personagem
              LEFT JOIN tb_origem o ON po.id_origem = o.id_origem
             WHERE cp.id_campanha = ?
        ");
        $stmt->execute([$id_campanha]);
        $PersonagemsCampanha = $stmt->fetchAll();

        foreach ($PersonagemsCampanha as &$Personagem) {
            $stmtAttr = $pdo->prepare("
                SELECT a.nm_atributo, a.ds_abreviacao, pa.qt_valor
                  FROM tb_personagem_atributo pa
                  JOIN tb_atributo a ON pa.id_atributo = a.id_atributo
                 WHERE pa.id_personagem = ?
            ");
            $stmtAttr->execute([$Personagem['id_personagem']]);
            $Personagem['atributos'] = $stmtAttr->fetchAll();
        }

        $stmtSisAttr = $pdo->prepare("SELECT * FROM tb_atributo WHERE id_sistema = ? ORDER BY id_atributo ASC");
        $stmtSisAttr->execute([$campanhaDados['id_sistema']]);
        $atributosSistema = $stmtSisAttr->fetchAll();

        $stmt = $pdo->prepare("
            SELECT c.*,
                   (SELECT SUM(m.qt_vida)
                      FROM tb_monstro m
                      JOIN tb_combate_monstro cm ON m.id_monstro = cm.id_monstro
                     WHERE cm.id_combate = c.id_combate) AS vd_total
              FROM tb_combate c WHERE c.id_campanha = ?
        ");
        $stmt->execute([$id_campanha]);
        $combatesCampanha = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Criar Campanha</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/criar-campanha.css?v=2.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- SDK dddice para animação 3D no Escudo do Mestre -->
    <script src="https://cdn.dddice.com/js/dddice-latest.js"></script>
    <style>
        /* ── CANVAS dddice — fullscreen overlay durante a animação ── */
        #dddice-canvas-escudo {
            position: fixed;
            inset: 0;
            width: 100vw;
            height: 100vh;
            display: block;
            z-index: 8000;
            pointer-events: none;
        }

        /* ── POP-UP DE RESULTADO (escudo) ── */
        #escudo-result-popup {
            position: fixed;
            inset: 0;
            z-index: 8500;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        #escudo-result-popup.show { opacity: 1; pointer-events: auto; }

        #escudo-result-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(6px);
            opacity: 0;
            transition: opacity 0.3s;
        }
        #escudo-result-popup.show #escudo-result-backdrop { opacity: 1; }

        #escudo-result-card {
            position: relative;
            background: linear-gradient(155deg, #0a0610 0%, #11091c 100%);
            border: 2px solid var(--premium-accent);
            border-radius: 24px;
            padding: 50px 64px 42px;
            text-align: center;
            box-shadow: 0 0 60px rgba(139,92,246,0.22), 0 32px 80px rgba(0,0,0,0.85);
            transform: scale(0.82) translateY(18px);
            transition: transform 0.38s cubic-bezier(0.34,1.56,0.64,1);
            min-width: 300px;
            cursor: pointer;
        }
        #escudo-result-popup.show #escudo-result-card { transform: scale(1) translateY(0); }

        #escudo-result-label {
            font-size: 0.72rem;
            letter-spacing: 4px;
            color: var(--premium-accent);
            text-transform: uppercase;
            margin-bottom: 12px;
            font-weight: 700;
        }

        #escudo-result-total {
            font-size: 6.5rem;
            font-weight: 900;
            line-height: 1;
            color: #fff;
            text-shadow: 0 0 35px rgba(139,92,246,0.7), 0 0 70px rgba(139,92,246,0.3);
            margin-bottom: 14px;
        }

        @keyframes escudo-pop-in {
            0%   { transform: scale(0.4); opacity: 0; }
            65%  { transform: scale(1.12); }
            100% { transform: scale(1); opacity: 1; }
        }
        #escudo-result-total.pop { animation: escudo-pop-in 0.45s cubic-bezier(0.34,1.56,0.64,1) forwards; }

        #escudo-result-breakdown {
            font-size: 0.85rem;
            color: #555;
            letter-spacing: 1px;
            min-height: 20px;
            margin-bottom: 6px;
        }
        #escudo-result-breakdown .val { color: #c4b5fd; font-weight: 700; }
        #escudo-result-breakdown .op  { color: #2e2b1f; margin: 0 3px; }
        #escudo-result-dismiss {
            margin-top: 22px;
            font-size: 0.6rem;
            color: #2e2b1f;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* ── BOLINHA CONTADORA na sessao-escudo ── */
        .escudo-bolinha {
            position: absolute;
            top: -14px;
            right: -14px;
            width: 38px;
            height: 38px;
            background-color: var(--premium-accent);
            color: #fff;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 900;
            font-size: 0.9rem;
            box-shadow: 0 2px 10px rgba(139,92,246,0.6);
            opacity: 0;
            transform: scale(0);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 5;
            pointer-events: none;
        }
        .escudo-bolinha.show { opacity: 1; transform: scale(1); }

        #escudo-tab-dados .item-dado {
            position: relative;
            cursor: pointer;
        }
        #escudo-tab-dados .item-dado.selecionado .dado-icon-container {
            filter: drop-shadow(0 0 10px var(--premium-accent));
        }

        /* Seletor de tema */
        .escudo-tema-row {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 10px 14px;
            margin-bottom: 18px;
        }
        .escudo-tema-row label {
            font-size: 0.7rem;
            font-weight: 700;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            white-space: nowrap;
        }
        #escudo-theme-select {
            flex: 1;
            background: transparent;
            border: none;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.82rem;
            outline: none;
            cursor: pointer;
        }
        #escudo-theme-select option { background: #0d091a; }

        #escudo-status-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #e74c3c; flex-shrink: 0;
            transition: background 0.3s, box-shadow 0.3s;
        }
        #escudo-status-dot.ok      { background: #2ecc71; box-shadow: 0 0 7px #2ecc71; }
        #escudo-status-dot.loading { background: var(--premium-accent); animation: blink 0.9s infinite; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.15} }

        /* Resumo da seleção */
        .escudo-sel-resumo {
            background: rgba(139,92,246,0.07);
            border: 1px solid rgba(139,92,246,0.15);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.78rem;
            color: #666;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            min-height: 38px;
        }
        .escudo-sel-resumo .chip {
            background: rgba(139,92,246,0.2);
            border: 1px solid rgba(139,92,246,0.35);
            border-radius: 20px;
            padding: 2px 10px;
            font-weight: 700;
            color: #c4b5fd;
            font-size: 0.76rem;
        }
        .btn-escudo-limpar-sel {
            margin-left: auto;
            background: none;
            border: 1px solid rgba(255,255,255,0.08);
            color: #555;
            border-radius: 6px;
            padding: 3px 8px;
            font-size: 0.68rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-escudo-limpar-sel:hover { border-color: #ff4d4d; color: #ff4d4d; }

        /* Botão rolar */
        .btn-escudo-rolar {
            width: 100%;
            background: linear-gradient(135deg, #5b21b6, #8b5cf6);
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 8px 20px rgba(91,33,182,0.4);
            margin-top: 8px;
        }
        .btn-escudo-rolar:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(91,33,182,0.6);
            filter: brightness(1.1);
        }
        .btn-escudo-rolar:disabled { opacity: 0.35; cursor: not-allowed; transform: none; }

        /* Toast */
        #escudo-toast {
            position: fixed;
            bottom: 28px;
            left: 50%;
            transform: translateX(-50%) translateY(16px);
            background: #1e0808;
            border: 1px solid #c0392b;
            border-radius: 10px;
            padding: 11px 22px;
            font-size: 0.82rem;
            color: #f1948a;
            z-index: 9999;
            opacity: 0;
            transition: all 0.28s;
            pointer-events: none;
        }
        #escudo-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
    </style>
    <style>
        .sistema-showcase { margin-bottom:30px; background:rgba(255,255,255,.05); border-radius:15px; padding:25px; border:1px solid rgba(255,255,255,.1); animation:slideDown .4s ease-out; }
        @keyframes slideDown { from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:translateY(0)} }
        .system-hero { display:flex; gap:20px; margin-bottom:25px; align-items:center; }
        .system-img  { width:120px; height:120px; background:#222; border-radius:12px; object-fit:cover; border:2px solid var(--cor-destaque-claro); }
        .system-text h2 { font-size:1.8rem; color:var(--cor-destaque-claro); margin-bottom:8px; }
        .system-text p  { font-size:.95rem; color:#ccc; line-height:1.4; }
        .btn-criar-campanha:disabled { opacity:.5; cursor:not-allowed; }

        .item-dado { cursor:pointer; transition:all .3s ease; }
        .dado-icon-container { width:50px; height:50px; display:flex; align-items:center; justify-content:center; margin:0 auto 10px; transition:transform .3s cubic-bezier(.175,.885,.32,1.275); }
        .img-dado { width:100%; height:100%; object-fit:contain; filter:drop-shadow(0 4px 8px rgba(0,0,0,.5)); }
        .item-dado:hover .dado-icon-container { transform:scale(1.15) rotate(5deg); }
        .dado-girando { animation:girarDado .6s ease-in-out; }
        @keyframes girarDado {
            0%{transform:rotate(0deg) scale(1)} 25%{transform:rotate(90deg) scale(1.3) translateY(-5px)}
            50%{transform:rotate(180deg) scale(1)} 75%{transform:rotate(270deg) scale(1.3) translateY(-5px)}
            100%{transform:rotate(360deg) scale(1)}
        }
        .hexa-dado { color:#000 !important; font-weight:800; }

        .popup-dados { position:fixed !important; top:50% !important; left:50% !important; transform:translate(-50%,-50%) !important; width:90% !important; max-width:380px !important; background:#110e1a !important; border:2px solid var(--premium-accent) !important; border-radius:24px !important; box-shadow:0 20px 60px rgba(0,0,0,.9) !important; z-index:10001 !important; padding:35px !important; animation:modalPop .3s cubic-bezier(.175,.885,.32,1.275); }
        @keyframes modalPop { from{opacity:0;transform:translate(-50%,-40%) scale(.9)} to{opacity:1;transform:translate(-50%,-50%) scale(1)} }
        .popup-overlay { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.8); backdrop-filter:blur(8px); z-index:10000; display:none; }
        .btn-confirmar-rolagem { width:100%; background:var(--premium-accent); color:#fff; border:none; padding:12px; border-radius:12px; font-weight:700; margin-top:20px; cursor:pointer; transition:all .3s; text-transform:uppercase; letter-spacing:1px; }
        .btn-confirmar-rolagem:hover { filter:brightness(1.2); transform:translateY(-2px); }

        .card-ameaca-premium { background:linear-gradient(90deg,rgba(30,10,10,.9) 0%,rgba(60,20,20,.4) 100%); border:1px solid rgba(255,50,50,.2); border-radius:12px; display:flex; align-items:center; padding:10px; gap:15px; margin-bottom:12px; position:relative; overflow:hidden; text-align:left; }
        .card-ameaca-premium::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background:#ff3232; box-shadow:0 0 10px #ff3232; }
        .card-ameaca-img { width:70px; height:70px; border-radius:8px; object-fit:cover; border:1px solid rgba(255,255,255,.1); background:#111; }
        .card-ameaca-body { flex:1; }
        .card-ameaca-body h4 { color:#fff; font-weight:800; font-size:1rem; margin-bottom:2px; }
        .card-ameaca-details { display:flex; flex-direction:column; }
        .card-ameaca-details span { font-size:.75rem; color:#ccc; font-weight:600; }
        .card-ameaca-details b { color:#ff4d4d; }
        .card-ameaca-actions { display:flex; gap:8px; }
        .btn-card-ficha { background:none; border:1px solid #fff; color:#fff; padding:5px 12px; border-radius:4px; font-weight:700; font-size:.7rem; text-transform:uppercase; cursor:pointer; }
        .btn-card-add   { background:#cd1d1d; border:none; color:#fff; padding:5px 12px; border-radius:4px; font-weight:700; font-size:.7rem; text-transform:uppercase; cursor:pointer; }
        .lista-ameacas-cards { display:flex; flex-direction:column; gap:5px; }
    </style>
</head>
<body class="body-criar-campanha">

    <!-- Canvas dddice — fullscreen, sobrepõe a tela durante a animação do Escudo -->
    <canvas id="dddice-canvas-escudo"></canvas>

    <!-- Pop-up de resultado do Escudo do Mestre -->
    <div id="escudo-result-popup" onclick="fecharResultadoEscudo()">
        <div id="escudo-result-backdrop"></div>
        <div id="escudo-result-card" onclick="event.stopPropagation()">
            <div id="escudo-result-label">Resultado</div>
            <div id="escudo-result-total">—</div>
            <div id="escudo-result-breakdown"></div>
            <div id="escudo-result-dismiss">Clique para fechar · ESC</div>
        </div>
    </div>

    <!-- Toast do Escudo -->
    <div id="escudo-toast"></div>

    <header>
        <div class="logotipo">
            <a href="index.php"><img src="../img/logo_horizontal.png" alt="Logo TABLE"></a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="cm-jogar.php">Como Jogar</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a></li>
                <li><a href="criar-mapa.php">Mundos</a></li>
                <li><a href="rolador-de-dados.php">Dados</a></li>
                <li><a href="sobre-nos.php">Sobre Nós</a></li>
            </ul>
        </nav>
        <?php if (isset($_SESSION['usuario'])): ?>
            <div class="usuario-logado-nav" id="nav-logado" onclick="window.location.href='perfil.php'" title="Ir para o Perfil">
                <img src="<?= !empty($_SESSION['usuario']['foto']) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png' ?>" alt="Avatar Navbar" class="avatar-nav">
                <span class="nome-nav"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
            </div>
        <?php else: ?>
            <div class="botoes-navegacao" id="nav-deslogado">
                <a href="login.php"    class="botao-entrar"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="cadastro.php" class="botao-cadastrar"><i class="fas fa-user-plus"></i> Cadastre-se</a>
            </div>
        <?php endif; ?>
    </header>

    <main class="main-criar-campanha">
        <div class="conteudo-campanha">

            <!-- TELA 01: CRIAR -->
            <div id="sessao-criar">
                <h1 class="titulo-pagina">Criar Campanha</h1>
                <section class="card-formulario-campanha">
                    <form id="form-criar-campanha">
                        <div class="grupo-form">
                            <label for="selecao-sistema">Sistema de RPG:</label>
                            <select id="selecao-sistema" class="input-campanha" onchange="carregarDetalhesSistema(this.value)">
                                <option value="" disabled selected>Selecione um sistema...</option>
                                <?php foreach ($sistemas as $sis): ?>
                                    <option value="<?= $sis['id_sistema'] ?>"><?= htmlspecialchars($sis['nm_sistema']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="sistema-showcase" class="sistema-showcase escondido"></div>

                        <div class="grupo-form">
                            <label for="nome-campanha">Nome:</label>
                            <input type="text" id="nome-campanha" class="input-campanha" placeholder="Nome da nova Campanha..." required>
                        </div>

                        <div class="grupo-form">
                            <label>Descrição:</label>
                            <div class="editor-container">
                                <div class="editor-toolbar">
                                    <button type="button" class="toolbar-btn bold"      id="btn-bold"      title="Negrito"><i class="fas fa-bold"></i></button>
                                    <button type="button" class="toolbar-btn italic"    id="btn-italic"    title="Itálico"><i class="fas fa-italic"></i></button>
                                    <button type="button" class="toolbar-btn underline" id="btn-underline" title="Sublinhado"><i class="fas fa-underline"></i></button>
                                </div>
                                <div id="descricao-campanha" class="textarea-campanha" contenteditable="true" placeholder="Descreva sua campanha aqui..."></div>
                            </div>
                        </div>

                        <div class="form-acoes">
                            <a href="#" class="btn-cancelar"><i class="fas fa-times"></i> Cancelar</a>
                            <button type="submit" class="btn-criar-campanha"><i class="fas fa-plus-circle"></i> Criar</button>
                        </div>
                    </form>
                </section>
            </div>

            <!-- TELA 02: DETALHES -->
            <div id="sessao-detalhes" class="sessao-detalhes">
                <h1 id="display-nome-campanha" class="titulo-campanha-criada">Nome da campanha</h1>
                <div id="banner-campanha-display" class="banner-campanha escondido"></div>
                <div class="descricao-campanha-display" id="display-descricao-campanha"><p>Sua campanha aparecerá aqui...</p></div>

                <div class="barra-acoes">
                    <button class="btn-acao" onclick="abrirModal('modal-foto-capa')"><i class="fas fa-image"></i> Foto de Capa</button>
                    <button class="btn-acao" onclick="abrirModalPersonagens()"><i class="fas fa-user-plus"></i> Adicionar Personagem</button>
                    <button class="btn-acao" onclick="abrirModalConvite()"><i class="fas fa-link"></i> Convidar Jogadores</button>
                    <button class="btn-acao" onclick="irParaEditar()"><i class="fas fa-edit"></i> Editar Campanha</button>
                    <button class="btn-acao" onclick="irParaCombate()"><i class="fas fa-skull-crossbones"></i> Criar Combate</button>
                    <button class="btn-acao especial" onclick="irParaEscudo()"><i class="fas fa-shield-halved"></i> Escudo do Mestre</button>
                </div>

                <div class="sub-nav-campanha">
                    <a href="javascript:void(0)" class="link-sub-nav ativa" id="aba-personagens" onclick="switchDashboardTab('personagens')">Personagens</a>
                    <a href="javascript:void(0)" class="link-sub-nav" id="aba-combates" onclick="switchDashboardTab('combates')">Combates</a>
                </div>

                <div class="lista-Personagems" id="lista-Personagems">
                    <?php if (empty($PersonagemsCampanha)): ?>
                        <p style="text-align:center;opacity:.5;margin-top:20px;">Nenhum personagem na campanha ainda.</p>
                    <?php endif; ?>
                    <?php foreach ($PersonagemsCampanha as $Personagem): ?>
                        <div class="card-Personagem">
                            <div class="avatar-Personagem">
                                <img src="<?= !empty($Personagem['ds_foto']) ? $Personagem['ds_foto'] : '../img/uploads/perfil/avatar1.png' ?>" alt="Avatar">
                            </div>
                            <div class="info-Personagem">
                                <h3><?= htmlspecialchars($Personagem['nm_personagem']) ?></h3>
                                <p><?= htmlspecialchars($Personagem['nm_sistema'] . ' - ' . $Personagem['nm_classe']) ?></p>
                                <span>Nexus: <?= $Personagem['qt_nivel'] ?>%</span>
                            </div>
                            <button class="btn-ver-ficha" onclick="window.location.href='exibir-ficha.php?id=<?= $Personagem['id_personagem'] ?>'">
                                <i class="fas fa-eye"></i> Ver Ficha
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="lista-combates escondido" id="lista-combates">
                    <?php if (empty($combatesCampanha)): ?>
                        <p style="text-align:center;opacity:.5;margin-top:20px;">Nenhum combate adicionado ainda.</p>
                    <?php endif; ?>
                    <?php foreach ($combatesCampanha as $combate): ?>
                        <div class="card-combate">
                            <h3><?= htmlspecialchars($combate['nm_combate']) ?></h3>
                            <p>VD: <?= $combate['vd_total'] ?: 0 ?> (vida total)</p>
                            <div class="card-combate-footer">
                                <button class="btn-remover-combate" onclick="removerCombate(<?= $combate['id_combate'] ?>, this)"><i class="fas fa-trash"></i> Remover</button>
                                <button class="btn-editar-combate"><i class="fas fa-edit"></i> Editar</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- TELA 03: EDITAR -->
            <div id="sessao-editar" class="sessao-criar escondido sessao-editar-container">
                <h1 class="titulo-pagina">Editar Campanha</h1>
                <section class="card-formulario-campanha">
                    <form id="form-editar-campanha">
                        <div class="grupo-form">
                            <label for="selecao-sistema-edit">Sistema de RPG:</label>
                            <select id="selecao-sistema-edit" class="input-campanha" onchange="carregarDetalhesSistema(this.value,'sistema-showcase-edit')">
                                <option value="" disabled>Selecione um sistema...</option>
                                <?php foreach ($sistemas as $sis): ?>
                                    <option value="<?= $sis['id_sistema'] ?>"><?= htmlspecialchars($sis['nm_sistema']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="sistema-showcase-edit" class="sistema-showcase escondido"></div>

                        <div class="grupo-form">
                            <label for="nome-campanha-edit">Nome:</label>
                            <input type="text" id="nome-campanha-edit" class="input-campanha" placeholder="Nome da Campanha..." required>
                        </div>

                        <div class="grupo-form">
                            <label>Descrição:</label>
                            <div class="editor-container">
                                <div class="editor-toolbar">
                                    <button type="button" class="toolbar-btn bold"      onclick="document.execCommand('bold',false,null)"      title="Negrito"><i class="fas fa-bold"></i></button>
                                    <button type="button" class="toolbar-btn italic"    onclick="document.execCommand('italic',false,null)"    title="Itálico"><i class="fas fa-italic"></i></button>
                                    <button type="button" class="toolbar-btn underline" onclick="document.execCommand('underline',false,null)" title="Sublinhado"><i class="fas fa-underline"></i></button>
                                </div>
                                <div id="descricao-campanha-edit" class="textarea-campanha" contenteditable="true" placeholder="Descreva sua campanha aqui..."></div>
                            </div>
                        </div>

                        <div class="form-acoes">
                            <button type="button" class="btn-cancelar" onclick="showSection('sessao-detalhes')"><i class="fas fa-times"></i> Cancelar</button>
                            <button type="button" class="btn-criar-campanha" onclick="salvarEdicao()"><i class="fas fa-save"></i> Salvar Alterações</button>
                        </div>
                    </form>
                </section>
            </div>

            <!-- TELA 04: COMBATE -->
            <div id="sessao-combate" class="sessao-combate">
                <div class="combate-header">
                    <div class="combate-titulo-area">
                        <div>
                            <label>Nome do Combate:</label><br>
                            <input type="text" class="input-nome-combate" id="nome-combate-input" placeholder="Nome do novo Combate...">
                        </div>
                        <div class="vd-total-display">VD Total: <span id="vd-total-valor">0</span></div>
                    </div>
                    <div class="combate-botoes-topo">
                        <button class="btn-combate-sair"   onclick="showSection('sessao-detalhes')"><i class="fas fa-times-circle"></i> Sair sem Salvar</button>
                        <button class="btn-combate-salvar" onclick="salvarCombate()"><i class="fas fa-save"></i> Salvar</button>
                    </div>
                </div>

                <div class="combate-grid">
                    <div class="catalogo-ameacas">
                        <div class="area-banners-combate">
                            <div class="banners-flex">
                                <div class="banner-card banner-ordem"><img src="../img/ordem-paranormal-icon.png" alt="Ordem Logo"></div>
                                <div class="banner-card banner-table"><img src="../img/logo_branco.png" alt="TABLE Logo"><span>TABLE</span></div>
                                <div class="banner-card banner-novas"><span>CRIAR NOVAS CRIATURAS!</span></div>
                            </div>
                            <p class="banner-subtexto">Conteúdo oficial da TABLE. Veja mais <a href="#">aqui</a> em breve!</p>
                        </div>
                        <div class="lista-ameacas-header">
                            <label>Lista de Ameaças</label>
                            <div class="search-container">
                                <i class="fas fa-search"></i>
                                <input type="text" id="busca-ameaca" placeholder="Buscar..." oninput="renderCatalogo()">
                            </div>
                            <div class="filtros-elemento" id="filtros-ameacas">
                                <button class="btn-filtro ativo"  onclick="filtrarPorElemento('Todos',this)">Todos</button>
                                <button class="btn-filtro"        onclick="filtrarPorElemento('Conhecimento',this)">Conhecimento</button>
                                <button class="btn-filtro"        onclick="filtrarPorElemento('Morte',this)">Morte</button>
                                <button class="btn-filtro"        onclick="filtrarPorElemento('Sangue',this)">Sangue</button>
                                <button class="btn-filtro"        onclick="filtrarPorElemento('Medo',this)">Medo</button>
                                <button class="btn-filtro"        onclick="filtrarPorElemento('Realidade',this)">Realidade</button>
                            </div>
                        </div>
                        <div class="lista-ameacas-cards" id="catalogo-cards"></div>
                    </div>
                    <div class="ameacas-selecionadas">
                        <h2 class="titulo-ameacas-selecionadas">Ameaças Adicionadas</h2>
                        <div class="lista-ameacas-cards" id="selecionadas-cards"></div>
                    </div>
                </div>
            </div>

            <!-- TELA 05: ESCUDO -->
            <div id="sessao-escudo" class="sessao-escudo">
                <div class="escudo-wrapper">
                    <aside class="escudo-sidebar">
                        <h3>Histórico de Dados</h3>
                        <div class="sidebar-dados-lista" id="sidebar-dados-lista">
                            <div class="item-dado">
                                <div class="hexa-dado">11</div>
                                <div class="info-rolagem"><p>Resultado [11]</p><h4>1d20 = 11</h4></div>
                            </div>
                        </div>
                    </aside>

                    <section class="escudo-principal">
                        <div class="escudo-topo">
                            <h1 id="escudo-titulo-campanha">Nome da Campanha</h1>
                            <div class="escudo-acoes-topo">
                                <button class="btn-escudo-sair"   onclick="fecharEscudo()">Sair sem Salvar</button>
                                <button class="btn-escudo-salvar" onclick="fecharEscudo()">Salvar</button>
                            </div>
                        </div>

                        <div class="escudo-nav">
                            <a class="escudo-link-nav ativo" onclick="switchEscudoTab('personagens',this)">Personagens</a>
                            <a class="escudo-link-nav"       onclick="switchEscudoTab('combates',this)">Combates</a>
                            <a class="escudo-link-nav"       onclick="switchEscudoTab('investigacoes',this)">Investigações</a>
                            <a class="escudo-link-nav"       onclick="switchEscudoTab('relatorios',this)">Relatórios</a>
                            <a class="escudo-link-nav"       onclick="switchEscudoTab('dados',this)">Dados</a>
                            <a class="escudo-link-nav"       onclick="switchEscudoTab('anotacoes',this)">Anotações</a>
                            <a href="criar-mapa.php?id=<?= $id_campanha ?>" target="_blank" class="escudo-link-nav link-mapa-especial">Mapas <i class="fas fa-external-link-alt"></i></a>
                        </div>

                        <!-- Personagens -->
                        <div id="escudo-tab-personagens" class="escudo-Personagems-grid">
                            <?php foreach ($PersonagemsCampanha as $Personagem): ?>
                                <div class="card-Personagem-compacto">
                                    <div class="card-compacto-header">
                                        <h3><?= htmlspecialchars($Personagem['nm_personagem']) ?></h3>
                                        <p><?= htmlspecialchars($Personagem['nm_classe'] ?: 'Mundano') ?> • <?= htmlspecialchars($Personagem['nm_origem'] ?? 'Acadêmico') ?></p>
                                        <span>NEX: <?= $Personagem['qt_nivel'] ?>%</span>
                                    </div>
                                    <div class="atributos-Personagem-p1">
                                        <?php foreach (array_slice($Personagem['atributos'], 0, 5) as $attr): ?>
                                            <div class="attr-p1-box">
                                                <span><?= htmlspecialchars($attr['ds_abreviacao']) ?></span>
                                                <strong><?= $attr['qt_valor'] ?></strong>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="status-bars-p1">
                                        <?php foreach ([
                                            ['VIDA','qt_vida','qt_vida_maxima','fill-vida-p1'],
                                            ['SANIDADE','qt_sanidade','qt_sanidade_maxima','fill-sanidade-p1'],
                                            ['ESFORÇO','qt_esforco','qt_esforco_maximo','fill-esforco-p1'],
                                        ] as [$lbl,$cur,$max,$cls]): ?>
                                            <div class="barra-p1-container">
                                                <div class="barra-p1-label"><?= $lbl ?></div>
                                                <div class="barra-p1-bg">
                                                    <div class="barra-p1-fill <?= $cls ?>"
                                                         style="width:<?= ($Personagem[$max]>0?round($Personagem[$cur]/$Personagem[$max]*100):0) ?>%"></div>
                                                    <div class="barra-p1-text"><?= $Personagem[$cur] ?>/<?= $Personagem[$max] ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="compacto-footer">
                                        <div class="footer-stats-grid">
                                            <div class="footer-stat-item"><span>PE/T</span><strong>1</strong></div>
                                            <div class="footer-stat-item"><span>DESL</span><strong>9m</strong></div>
                                            <div class="footer-stat-item"><span>DEF</span><strong><?= $Personagem['qt_defesa'] ?></strong></div>
                                            <div class="footer-stat-item"><span>BLQ</span><strong>0</strong></div>
                                            <div class="footer-stat-item"><span>ESQ</span><strong><?= $Personagem['qt_esquiva'] ?? $Personagem['qt_defesa'] ?></strong></div>
                                        </div>
                                        <a href="exibir-ficha.php?id=<?= $Personagem['id_personagem'] ?>" class="btn-ficha-compacto">Ver Ficha Completa</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Combates -->
                        <div id="escudo-tab-combates" class="escondido">
                            <div id="escudo-combates-lista" class="lista-combates">
                                <?php foreach ($combatesCampanha as $combate): ?>
                                    <div class="card-combate-escudo" style="background:var(--fundo-card-escudo);padding:30px;border-radius:15px;display:flex;justify-content:space-between;align-items:center;border:1px solid var(--cor-borda-escudo);">
                                        <div>
                                            <h3 style="font-size:1.5rem;margin-bottom:5px;"><?= htmlspecialchars($combate['nm_combate']) ?></h3>
                                            <p style="color:#888;">VD: <?= $combate['vd_total'] ?: 0 ?></p>
                                        </div>
                                        <button class="btn-iniciar-combate"
                                                onclick="iniciarCombateEscudo(<?= $combate['id_combate'] ?>,'<?= htmlspecialchars($combate['nm_combate']) ?>')"
                                                style="background:#fff;color:#000;padding:10px 30px;border-radius:20px;font-weight:800;cursor:pointer;">Iniciar</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="escudo-combate-ativo" class="escudo-combate-ativo escondido">
                                <div class="coluna-iniciativa">
                                    <div class="header-iniciativa">
                                        <h2>Iniciativa</h2>
                                        <div class="controles-turno">
                                            <button class="btn-turno">Voltar Turno</button>
                                            <button class="btn-turno">Próximo Turno</button>
                                        </div>
                                    </div>
                                    <div id="lista-iniciativa-escudo"></div>
                                </div>
                                <div class="ficha-detalhes-escudo" id="detalhe-participante-escudo">
                                    <p style="text-align:center;color:#888;padding-top:50px;">Selecione um participante para ver detalhes.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Investigações -->
                        <div id="escudo-tab-investigacoes" class="escondido">
                            <div id="inv-modo-lista">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                                    <h2 style="font-size:1.2rem;font-weight:700;">Fichas de Investigação</h2>
                                    <button class="btn-adicionar-investigacao" onclick="novaFichaInvestigacao()">Adicionar</button>
                                </div>
                                <div class="investigacao-lista">
                                    <div class="item-investigacao">
                                        <h3>Nova Ficha de Investigação</h3>
                                        <div class="acoes-investigacao">
                                            <button class="btn-inv-del">Deletar</button>
                                            <button class="btn-inv-abrir" onclick="abrirFichaInvestigacao()">Abrir</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="inv-modo-detalhe" class="escondido">
                                <div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
                                    <button class="btn-voltar-investigacao" onclick="voltarListaInvestigacao()">Voltar</button>
                                </div>
                                <div class="form-investigacao" style="background:rgba(0,0,0,.3);padding:30px;border-radius:20px;">
                                    <div class="campo-investigacao"><label>Nome do caso</label><input type="text" placeholder="Nome do caso"></div>
                                    <div class="campo-investigacao"><label>Resumo:</label><div class="textarea-p1" contenteditable="true" placeholder="..."></div></div>
                                    <div class="campo-investigacao"><label>Objetivo:</label><div class="textarea-p1" contenteditable="true" placeholder="..."></div></div>
                                    <div class="campo-investigacao"><label>Perguntas:</label><div class="textarea-p1" contenteditable="true" placeholder="..."></div></div>
                                    <div class="campo-investigacao"><label>Pistas:</label><div class="textarea-p1" contenteditable="true" placeholder="..."></div></div>
                                </div>
                            </div>
                        </div>

                        <!-- Relatórios -->
                        <div id="escudo-tab-relatorios" class="escondido">
                            <div id="rel-modo-lista">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                                    <h2 style="font-size:1.2rem;font-weight:700;">Relatórios de Missão</h2>
                                    <button class="btn-adicionar-investigacao" onclick="novoRelatorioMissao()">Adicionar</button>
                                </div>
                                <div class="investigacao-lista">
                                    <div class="item-investigacao">
                                        <h3>Nova Ficha de Investigação</h3>
                                        <div class="acoes-investigacao">
                                            <button class="btn-inv-del">Deletar</button>
                                            <button class="btn-inv-abrir" onclick="abrirRelatorioMissao()">Abrir</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="rel-modo-detalhe" class="escondido">
                                <div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
                                    <button class="btn-voltar-investigacao" onclick="voltarListaRelatorio()">Voltar</button>
                                </div>
                                <div class="form-investigacao" style="background:rgba(0,0,0,.3);padding:30px;border-radius:20px;">
                                    <div class="form-relatorio-row">
                                        <div class="campo-investigacao"><label>Missão:</label><input type="text" placeholder="Nome do relatório..."></div>
                                        <div class="campo-investigacao"><label>Equipe:</label><input type="text" placeholder="Nome da equipe..."></div>
                                    </div>
                                    <div class="campo-investigacao"><label>Personagens Envolvidos:</label><input type="text" placeholder="..."></div>
                                    <div class="campo-investigacao"><label>Pistas Encontradas</label><div class="textarea-p1" contenteditable="true" placeholder="Todas as pistas..."></div></div>
                                    <div class="campo-investigacao"><label>Causalidades</label><div class="textarea-p1" contenteditable="true" placeholder="Mortes, perda de itens..."></div></div>
                                    <div class="campo-investigacao"><label>Resumo da Missão:</label><div class="textarea-p1" contenteditable="true" placeholder="Resumo e conclusão..."></div></div>
                                    <div class="campo-investigacao">
                                        <label>Resultado:</label>
                                        <div class="status-toggle-group">
                                            <button class="btn-status-rel ativo" data-status="aberto"   onclick="setRelStatus(this)">Em aberto</button>
                                            <button class="btn-status-rel"       data-status="sucesso"  onclick="setRelStatus(this)">Sucesso</button>
                                            <button class="btn-status-rel"       data-status="fracasso" onclick="setRelStatus(this)">Fracasso</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dados -->
                        <div id="escudo-tab-dados" class="escondido" style="position:relative;">
                            <div class="dados-header">
                                <div class="titulo-dados">Rolar Dados <i class="fas fa-dice-d20"></i></div>
                            </div>

                            <!-- Seletor de tema dddice -->
                            <div class="escudo-tema-row">
                                <span id="escudo-status-dot" class="loading" title="Conectando..."></span>
                                <label>Tema dddice:</label>
                                <select id="escudo-theme-select" disabled>
                                    <option value="">Conectando...</option>
                                </select>
                            </div>

                            <!-- Grade de dados com bolinhas contadoras -->
                            <div class="grid-dados">
                                <?php foreach ([2,4,6,8,10,12,20,100] as $l): ?>
                                    <div class="item-dado" id="escudo-dado-d<?= $l ?>" data-lados="<?= $l ?>">
                                        <div class="dado-icon-container">
                                            <img src="../img/dados/D<?= $l ?>.png" alt="D<?= $l ?>" class="img-dado">
                                        </div>
                                        <span class="label-dado">D<?= $l ?></span>
                                        <div class="escudo-bolinha" id="escudo-bolinha-d<?= $l ?>">0</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Resumo dos dados selecionados -->
                            <div class="escudo-sel-resumo" id="escudo-sel-resumo">
                                <span style="color:#555;">Clique nos dados para selecionar...</span>
                            </div>

                            <!-- Botão rolar com dddice -->
                            <button class="btn-escudo-rolar" id="escudo-btn-rolar" disabled onclick="escudoExecutarRolagem()">
                                <i class="fas fa-dice"></i> Rolar com dddice
                            </button>
                        </div>

                        <!-- Anotações -->
                        <div id="escudo-tab-anotacoes" class="escondido">
                            <div class="form-investigacao" style="background:rgba(0,0,0,.3);padding:30px;border-radius:20px;">
                                <div class="secao-anotacao"><h3>GERAL:</h3><div class="textarea-p1" contenteditable="true" placeholder="Informações gerais ao longo da sessão..."></div></div>
                                <div class="secao-anotacao"><h3>Sessões Futuras:</h3><div class="textarea-p1" contenteditable="true" placeholder="Notas de possíveis eventos futuros..."></div></div>
                                <div class="secao-anotacao"><h3>Sessões Anteriores:</h3><div class="textarea-p1" contenteditable="true" placeholder="Eventos importantes que ocorreram..."></div></div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

        </div>
    </main>

    <footer class="rodape-principal">
        <div class="rodape-conteudo">
            <div class="rodape-logo-area">
                <div class="rodape-marca"><img src="../img/logo_branco.png" alt="Logo TABLE"><span>TABLE</span></div>
                <p>Acompanhe uma experiência imersiva nos mundos de RPG. Aprenda e jogue com seus amigos!</p>
            </div>
            <div class="rodape-links">
                <h4>Navegação</h4>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="cm-jogar.php">Como Jogar</a></li>
                    <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a></li>
                    <li><a href="criar-mapa.php">Mundos</a></li>
                    <li><a href="rolador-de-dados.php">Dados</a></li>
                    <li><a href="sobre-nos.php">Sobre Nós</a></li>
                </ul>
            </div>
            <div class="rodape-links">
                <h4>Jogar</h4>
                <ul>
                    <li><a href="cm-jogador.php">Como Player</a></li>
                    <li><a href="cm-mestre.php">Como Mestre</a></li>
                    <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Campanhas</a></li>
                    <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Meu Perfil</a></li>
                </ul>
            </div>
        </div>
        <div class="rodape-inferior">
            <p>© 2026 TABLE. Todos os direitos reservados.</p>
            <div class="redes-sociais">
                <a href="#"><i class="fa-brands fa-discord"></i></a>
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <!-- MODAIS -->

    <!-- 1. FOTO DE CAPA -->
    <div id="modal-foto-capa" class="modal-overlay">
        <div class="modal-box" style="max-width:450px;">
            <i class="fas fa-times modal-close" onclick="fecharModal('modal-foto-capa')"></i>
            <h2 style="color:#fff;text-transform:uppercase;letter-spacing:1px;">Foto de Capa</h2>
            <div class="modal-body-central">
                <p style="color:#888;font-size:.85rem;margin-bottom:25px;text-align:center;">Recomendado: 1200x300px.</p>
                <input type="file" id="input-foto-capa" style="display:none;" accept="image/*" onchange="previewCapa(this)">
                <button class="btn-confirmar-rolagem" onclick="document.getElementById('input-foto-capa').click()">
                    <i class="fas fa-cloud-upload-alt"></i> Selecionar Imagem
                </button>
            </div>
        </div>
    </div>

    <!-- 2. ADICIONAR PERSONAGENS -->
    <div id="modal-adc-Personagems" class="modal-overlay">
        <div class="modal-box" style="max-width:700px;">
            <i class="fas fa-times modal-close" onclick="fecharModal('modal-adc-Personagems')"></i>
            <h2 style="color:#fff;text-transform:uppercase;letter-spacing:1px;">Adicionar Personagens</h2>
            <p style="color:#888;font-size:.85rem;margin-bottom:20px;">Escolha um personagem para integrar à sua campanha:</p>
            <div class="modal-lista-Personagems" id="modal-meus-personagens" style="max-height:400px;overflow-y:auto;">
                <p style="text-align:center;padding:20px;color:#888;">Carregando grimório de personagens...</p>
            </div>
        </div>
    </div>

    <!-- 3. CONVIDAR JOGADORES -->
    <div id="modal-link-convite" class="modal-overlay">
        <div class="modal-box" style="max-width:500px;">
            <i class="fas fa-times modal-close" onclick="fecharModal('modal-link-convite')"></i>
            <h2 style="color:#fff;text-transform:uppercase;letter-spacing:1px;">
                <i class="fas fa-link" style="color:var(--premium-accent);margin-right:10px;"></i>
                Convite da Campanha
            </h2>
            <p style="color:#888;font-size:.85rem;margin-bottom:15px;text-align:center;">
                Compartilhe o link abaixo. Expira em <strong style="color:#fff;">7 dias</strong>.
            </p>

            <div id="convite-loading" style="text-align:center;padding:20px;display:none;">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--premium-accent);"></i>
                <p style="margin-top:10px;color:#aaa;">Gerando link...</p>
            </div>

            <div id="convite-resultado" style="display:none;">
                <div class="bloco-link-convite" style="background:rgba(255,255,255,.05);border:1px dashed rgba(255,255,255,.2);padding:20px;border-radius:12px;word-break:break-all;">
                    <a href="#" id="texto-link-campanha" target="_blank"
                       style="color:var(--premium-accent);font-weight:700;text-decoration:none;font-size:.9rem;"></a>
                </div>
                <div class="modal-footer-acoes" style="margin-top:20px;gap:15px;display:flex;">
                    <button class="btn-resetar" onclick="gerarTokenConvite()" style="border-radius:12px;flex:1;">
                        <i class="fas fa-sync"></i> Novo Link
                    </button>
                    <button class="btn-copiar" id="btn-copiar-convite" onclick="copiarLinkConvite()"
                            style="border-radius:12px;flex:1;background:var(--premium-accent);border:none;color:#fff;font-weight:700;padding:12px;cursor:pointer;">
                        <i class="fas fa-copy"></i> Copiar Link
                    </button>
                </div>
            </div>

            <div id="convite-erro" style="display:none;color:#ff4d4d;text-align:center;padding:20px;">
                <i class="fas fa-exclamation-triangle" style="font-size:2rem;"></i>
                <p style="margin-top:10px;">Erro ao gerar o link. Tente novamente.</p>
                <button onclick="gerarTokenConvite()"
                        style="margin-top:15px;background:var(--premium-accent);border:none;color:#fff;padding:10px 25px;border-radius:10px;cursor:pointer;font-weight:700;">
                    Tentar novamente
                </button>
            </div>
        </div>
    </div>

    <!-- 4. FICHA DE MONSTRO -->
    <div class="modal-overlay" id="modal-ficha-monstro">
        <div class="modal-box" id="ficha-monstro-render"
             style="width:600px;padding:0;background:#0c0816;overflow:hidden;border:1px solid var(--premium-accent);"></div>
    </div>

    <script src="../js/nav-global.js" defer></script>
    <script>
    // ============================================================
    // DADOS PHP → JS
    // ============================================================
    const campanhaInicial            = <?= json_encode($campanhaDados) ?>;
    const campanhaInicialPersonagems = <?= json_encode($PersonagemsCampanha) ?>;
    const idCampanha                 = <?= $id_campanha ? (int)$id_campanha : 'null' ?>;

    // ============================================================
    // INICIALIZAÇÃO
    // ============================================================
    document.addEventListener('DOMContentLoaded', () => {
        if (campanhaInicial) {
            document.getElementById('display-nome-campanha').textContent    = campanhaInicial.nm_campanha;
            document.getElementById('display-descricao-campanha').innerHTML = campanhaInicial.ds_descricao;
            if (campanhaInicial.ds_imagem) {
                const banner = document.getElementById('banner-campanha-display');
                banner.style.backgroundImage = `url('${campanhaInicial.ds_imagem}')`;
                banner.classList.remove('escondido');
            }
        }

        const hash  = window.location.hash.replace('#','');
        const valid = ['sessao-criar','sessao-detalhes','sessao-editar','sessao-combate','sessao-escudo'];
        if (hash && valid.includes(hash)) {
            if      (hash === 'sessao-editar')  irParaEditar();
            else if (hash === 'sessao-combate') irParaCombate();
            else if (hash === 'sessao-escudo')  irParaEscudo();
            else showSection(hash);
        } else {
            showSection(campanhaInicial ? 'sessao-detalhes' : 'sessao-criar');
        }

        if (campanhaInicialPersonagems.length > 0) {
            iniciativaLista = campanhaInicialPersonagems.map(a => ({
                ...a, tipo:'Personagem', iniciativa: Math.floor(Math.random()*20)+1
            })).sort((a,b) => b.iniciativa - a.iniciativa);
            participanteSelecionado = iniciativaLista[0];
            renderListaIniciativa();
            renderDetalheParticipante();
        }

        const bB = document.getElementById('btn-bold');
        const bI = document.getElementById('btn-italic');
        const bU = document.getElementById('btn-underline');
        if (bB) bB.onclick = () => document.execCommand('bold',false,null);
        if (bI) bI.onclick = () => document.execCommand('italic',false,null);
        if (bU) bU.onclick = () => document.execCommand('underline',false,null);
    });

    // ============================================================
    // DROPDOWN DE SISTEMA
    // ============================================================
    async function carregarDetalhesSistema(id, targetId = 'sistema-showcase') {
        const showcase = document.getElementById(targetId);
        if (!id) { showcase.classList.add('escondido'); return; }
        try {
            const res  = await fetch(`../app/ajax/get-sistema-detalhes.php?id=${id}`);
            const data = await res.json();
            if (data.success) {
                const sis = data.sistema;
                showcase.innerHTML = `
                    <div class="system-hero">
                        <img src="${sis.ds_imagem||'../img/logo_icone.png'}" alt="${sis.nm_sistema}" class="system-img">
                        <div class="system-text">
                            <h2>${sis.nm_sistema}</h2>
                            <p>${sis.ds_descricao||'Sem descrição disponível.'}</p>
                        </div>
                    </div>`;
                showcase.classList.remove('escondido');
            }
        } catch(e) { console.error('Erro ao carregar sistema:', e); }
    }

    // ============================================================
    // NAVEGAÇÃO
    // ============================================================
    function showSection(id) {
        ['sessao-criar','sessao-detalhes','sessao-editar','sessao-combate','sessao-escudo'].forEach(s => {
            const el = document.getElementById(s);
            if (!el) return;
            if (s === id) { el.style.display='block'; el.classList.remove('escondido'); }
            else          { el.style.display='none';  el.classList.add('escondido'); }
        });
        if (history.replaceState) history.replaceState(null,null,'#'+id);
        else window.location.hash = id;
        window.scrollTo({top:0,behavior:'smooth'});
    }

    function abrirModal(id)  { const m=document.getElementById(id); if(m) m.classList.add('ativo'); }
    function fecharModal(id) { const m=document.getElementById(id); if(m) m.classList.remove('ativo'); }

    function irParaEditar() {
        document.getElementById('nome-campanha-edit').value         = document.getElementById('display-nome-campanha').textContent;
        document.getElementById('descricao-campanha-edit').innerHTML = document.getElementById('display-descricao-campanha').innerHTML;
        const idSis = <?= $campanhaDados['id_sistema'] ?? 'null' ?>;
        if (idSis) {
            document.getElementById('selecao-sistema-edit').value = idSis;
            carregarDetalhesSistema(idSis, 'sistema-showcase-edit');
        }
        showSection('sessao-editar');
    }

    function irParaCombate() { showSection('sessao-combate'); renderCatalogo(); }
    function irParaEscudo()  { document.getElementById('escudo-titulo-campanha').textContent = document.getElementById('display-nome-campanha').textContent; showSection('sessao-escudo'); if (!escudoDddiceSDK) { initEscudoSDK(); inicializarEventosDadosEscudo(); } }
    function fecharEscudo()  { showSection('sessao-detalhes'); }

    // ============================================================
    // CRIAR / EDITAR CAMPANHA
    // ============================================================
    document.getElementById('form-criar-campanha').onsubmit = async function(e) {
        e.preventDefault();
        const btn  = e.target.querySelector('.btn-criar-campanha');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';
        const payload = {
            id_campanha: idCampanha,
            nome:        document.getElementById('nome-campanha').value,
            id_sistema:  document.getElementById('selecao-sistema').value,
            descricao:   document.getElementById('descricao-campanha').innerHTML
        };
        try {
            const res  = await fetch('../app/ajax/salvar-campanha.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
            const data = await res.json();
            if (data.success) window.location.href = `criar-campanha.php?id=${data.id_campanha}`;
            else alert('Erro ao criar campanha: ' + data.error);
        } catch(e) { console.error(e); alert('Erro de conexão.'); }
        finally    { btn.disabled=false; btn.innerHTML=orig; }
    };

    async function salvarEdicao() {
        const payload = {
            id_campanha: idCampanha,
            nome:        document.getElementById('nome-campanha-edit').value,
            id_sistema:  document.getElementById('selecao-sistema-edit').value,
            descricao:   document.getElementById('descricao-campanha-edit').innerHTML
        };
        try {
            const res  = await fetch('../app/ajax/salvar-campanha.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
            const data = await res.json();
            if (data.success) {
                document.getElementById('display-nome-campanha').textContent      = payload.nome;
                document.getElementById('display-descricao-campanha').innerHTML   = payload.descricao;
                const toast = document.createElement('div');
                toast.style.cssText = 'position:fixed;bottom:30px;left:50%;transform:translateX(-50%);background:#0c9447;color:#fff;padding:14px 30px;border-radius:12px;font-weight:700;z-index:99999;';
                toast.innerHTML = '<i class="fas fa-check-circle"></i> Campanha atualizada!';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
                showSection('sessao-detalhes');
            } else { alert('Erro ao salvar: ' + data.error); }
        } catch(e) { console.error(e); }
    }

    // ============================================================
    // SISTEMA DE CONVITE UUID
    // ============================================================
    function abrirModalConvite() {
        ['convite-loading','convite-resultado','convite-erro']
            .forEach(id => document.getElementById(id).style.display = 'none');
        abrirModal('modal-link-convite');
        gerarTokenConvite();
    }

    async function gerarTokenConvite() {
        if (!idCampanha) { alert('Salve a campanha primeiro.'); return; }
        document.getElementById('convite-loading').style.display   = 'block';
        document.getElementById('convite-resultado').style.display = 'none';
        document.getElementById('convite-erro').style.display      = 'none';
        try {
            const fd = new FormData();
            fd.append('action', 'gerar_convite');
            fd.append('campaign_id', idCampanha);
            const res  = await fetch('criar-campanha.php', {method:'POST', body:fd});
            const data = await res.json();
            document.getElementById('convite-loading').style.display = 'none';
            if (data.sucesso) {
                const linkEl = document.getElementById('texto-link-campanha');
                linkEl.href        = data.link;
                linkEl.textContent = data.link;
                document.getElementById('convite-resultado').style.display = 'block';
            } else {
                document.getElementById('convite-erro').style.display = 'block';
            }
        } catch(e) {
            console.error(e);
            document.getElementById('convite-loading').style.display = 'none';
            document.getElementById('convite-erro').style.display    = 'block';
        }
    }

    async function copiarLinkConvite() {
        const link = document.getElementById('texto-link-campanha').textContent;
        try {
            await navigator.clipboard.writeText(link);
            const btn = document.getElementById('btn-copiar-convite');
            btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
            btn.style.background = '#0c9447';
            setTimeout(() => { btn.innerHTML='<i class="fas fa-copy"></i> Copiar Link'; btn.style.background=''; }, 2500);
        } catch { alert('Copie manualmente:\n\n' + link); }
    }

    // Aliases para compatibilidade com o HTML original
    function resetarLink() { gerarTokenConvite(); }
    function copiarLink()  { copiarLinkConvite(); }

    // ============================================================
    // PERSONAGENS
    // ============================================================
    function switchDashboardTab(tab) {
        ['personagens','combates'].forEach(t => {
            const aba   = document.getElementById('aba-'+t);
            const lista = document.getElementById('lista-'+(t==='personagens'?'Personagems':'combates'));
            if (t === tab) { if(aba) aba.classList.add('ativa');    if(lista) lista.classList.remove('escondido'); }
            else           { if(aba) aba.classList.remove('ativa'); if(lista) lista.classList.add('escondido'); }
        });
    }

    async function abrirModalPersonagens() {
        abrirModal('modal-adc-Personagems');
        const container = document.getElementById('modal-meus-personagens');
        try {
            const res  = await fetch(`../app/ajax/get-meus-personagens.php?id_campanha=${idCampanha}`);
            const data = await res.json();
            if (data.success) {
                if (!data.personagens.length) { container.innerHTML='<p style="text-align:center;padding:20px;color:#888;">Você não tem personagens disponíveis.</p>'; return; }
                container.innerHTML = data.personagens.map(p=>`
                    <div class="card-Personagem">
                        <div class="avatar-Personagem"><img src="${p.ds_foto||'../img/uploads/perfil/avatar1.png'}" alt="Avatar"></div>
                        <div class="info-Personagem"><h3>${p.nm_personagem}</h3><p>${p.nm_sistema} - ${p.nm_classe||'Sem Classe'}</p></div>
                        <button class="btn-ver-ficha" onclick="vincularPersonagem(${p.id_personagem})"><i class="fas fa-plus-circle"></i> Adicionar</button>
                    </div>`).join('');
            }
        } catch(e) { console.error(e); }
    }

    async function vincularPersonagem(idP) {
        try {
            const res  = await fetch('../app/ajax/adicionar-Personagem-campanha.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id_campanha:idCampanha,id_personagem:idP})});
            const data = await res.json();
            if (data.success) location.reload(); else alert('Erro: '+data.error);
        } catch(e) { console.error(e); }
    }

    async function removerPersonagem(idP) {
        if (!confirm('Deseja remover este Personagem da campanha?')) return;
        try {
            const res  = await fetch('../app/ajax/remover-Personagem-campanha.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id_campanha:idCampanha,id_personagem:idP})});
            const data = await res.json();
            if (data.success) location.reload();
        } catch(e) { console.error(e); }
    }

    async function removerCombate(idComb) {
        if (!confirm('Deseja excluir este combate?')) return;
        try {
            const res  = await fetch('../app/ajax/remover-combate.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id_combate:idComb})});
            const data = await res.json();
            if (data.success) location.reload();
        } catch(e) { console.error(e); }
    }

    // ============================================================
    // COMBATE
    // ============================================================
    let ameacasCatalogo = [], ameacasSelecionadas = [], filtroAtual = 'Todos';

    async function renderCatalogo() {
        const container = document.getElementById('catalogo-cards');
        const idSis = <?= $campanhaDados ? $campanhaDados['id_sistema'] : 'null' ?>;
        if (ameacasCatalogo.length === 0 && idSis) {
            try {
                const res  = await fetch(`../app/ajax/get-monstros.php?id_sistema=${idSis}`);
                const data = await res.json();
                if (data.success) ameacasCatalogo = data.monstros;
            } catch(e) { console.error(e); }
        }
        const busca = document.getElementById('busca-ameaca').value.toLowerCase();
        const filtrados = ameacasCatalogo.filter(a =>
            a.nm_monstro.toLowerCase().includes(busca) &&
            (filtroAtual==='Todos' || a.tp_monstro===filtroAtual)
        );
        container.innerHTML = filtrados.map(a=>`
            <div class="card-ameaca-premium">
                <img src="${a.ds_imagem||'../img/logo_icone.png'}" class="card-ameaca-img">
                <div class="card-ameaca-body">
                    <h4>${a.nm_monstro}</h4>
                    <div class="card-ameaca-details">
                        <span>VD: <b>${a.qt_vd||'???'}</b></span>
                        <span>${a.tp_monstro||'Criatura'}</span>
                    </div>
                </div>
                <div class="card-ameaca-actions">
                    <button class="btn-card-ficha" onclick="verFichaMonstro(${a.id_monstro})">Ficha</button>
                    <button class="btn-card-add"   onclick="adicionarAmeaca(${a.id_monstro})">Adicionar</button>
                </div>
            </div>`).join('');
    }

    function filtrarPorElemento(el, btn) {
        filtroAtual = el;
        document.querySelectorAll('#filtros-ameacas .btn-filtro').forEach(b=>b.classList.remove('ativo'));
        btn.classList.add('ativo');
        renderCatalogo();
    }

    function adicionarAmeaca(idM) { const a=ameacasCatalogo.find(x=>x.id_monstro==idM); if(a){ameacasSelecionadas.push(a);renderSelecionadas();} }
    function removerAmeaca(i)     { ameacasSelecionadas.splice(i,1); renderSelecionadas(); }

    function renderSelecionadas() {
        const c=document.getElementById('selecionadas-cards'); let vd=0;
        c.innerHTML = ameacasSelecionadas.map((a,i)=>{
            vd+=parseInt(a.qt_vd||0);
            return `<div class="card-ameaca-premium" style="background:rgba(255,255,255,.05);padding:8px;">
                <div class="card-ameaca-body"><h4 style="font-size:.9rem;">${a.nm_monstro}</h4><span style="font-size:.7rem;color:#aaa;">VD: <b>${a.qt_vd||0}</b></span></div>
                <button onclick="removerAmeaca(${i})" style="background:none;border:none;color:#888;cursor:pointer;"><i class="fas fa-trash"></i></button>
            </div>`;
        }).join('');
        document.getElementById('vd-total-valor').textContent = vd;
    }

    async function salvarCombate() {
        const nome = document.getElementById('nome-combate-input').value;
        if (!nome) { alert('Dê um nome ao combate!'); return; }
        try {
            const res  = await fetch('../app/ajax/salvar-combate.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id_campanha:idCampanha,nome,monstros:ameacasSelecionadas.map(a=>a.id_monstro)})});
            const data = await res.json();
            if (data.success) location.reload(); else alert('Erro: '+data.error);
        } catch(e) { console.error(e); }
    }

    async function verFichaMonstro(idM) {
        const c = document.getElementById('ficha-monstro-render');
        if (!c) return;
        c.innerHTML = '<div style="padding:40px;text-align:center;color:#888;"><i class="fas fa-spinner fa-spin"></i> Lendo Grimório...</div>';
        abrirModal('modal-ficha-monstro');
        try {
            const res  = await fetch(`../app/ajax/get-monstro-detalhes.php?id=${idM}`);
            const data = await res.json();
            if (data.success) {
                const m=data.monstro, attrs=data.atributos;
                c.innerHTML = `
                    <div style="background:linear-gradient(135deg,#1e0b3a,#311c61);padding:25px;border-bottom:2px solid var(--premium-accent);">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                            <div><h1 style="color:#fff;font-weight:900;font-size:1.8rem;margin-bottom:5px;">${m.nm_monstro}</h1><span style="color:var(--premium-accent);font-weight:800;font-size:.9rem;text-transform:uppercase;">${m.tp_monstro||'Desconhecido'}</span></div>
                            <i class="fas fa-times" onclick="fecharModal('modal-ficha-monstro')" style="color:#fff;cursor:pointer;font-size:1.2rem;"></i>
                        </div>
                    </div>
                    <div style="padding:25px;">
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-bottom:25px;">
                            <div style="background:rgba(255,255,255,.05);padding:15px;border-radius:12px;text-align:center;border:1px solid rgba(255,255,255,.1);"><span style="display:block;color:#ff4d4d;font-weight:900;font-size:.7rem;margin-bottom:5px;">VIDA</span><strong style="color:#fff;font-size:1.5rem;">${m.qt_vida}</strong></div>
                            <div style="background:rgba(255,255,255,.05);padding:15px;border-radius:12px;text-align:center;border:1px solid rgba(255,255,255,.1);"><span style="display:block;color:#2980b9;font-weight:900;font-size:.7rem;margin-bottom:5px;">DEFESA</span><strong style="color:#fff;font-size:1.5rem;">${m.qt_defesa}</strong></div>
                            <div style="background:rgba(255,255,255,.05);padding:15px;border-radius:12px;text-align:center;border:1px solid rgba(255,255,255,.1);"><span style="display:block;color:#f1c40f;font-weight:900;font-size:.7rem;margin-bottom:5px;">XP</span><strong style="color:#fff;font-size:1.5rem;">${m.qt_xp_recompensa}</strong></div>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:25px;">
                            ${attrs.map(a=>`<div style="text-align:center;"><span style="font-size:.7rem;display:block;color:var(--premium-accent);">${a.ds_abreviacao}</span><div style="width:45px;height:45px;font-size:1.1rem;border:2px solid ${a.qt_valor>0?'var(--premium-accent)':'#444'};border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;color:#fff;">${a.qt_valor}</div></div>`).join('')}
                        </div>
                        <div style="background:rgba(0,0,0,.3);padding:20px;border-radius:15px;border:1px solid rgba(255,255,255,.05);">
                            <h3 style="color:var(--premium-accent);font-size:.8rem;font-weight:900;margin-bottom:10px;text-transform:uppercase;">HABILIDADES / DETALHES</h3>
                            <p style="color:#ccc;font-size:.9rem;line-height:1.6;">${m.ds_monstro||'Nenhuma habilidade descrita.'}</p>
                        </div>
                    </div>`;
            }
        } catch(e) { console.error(e); }
    }

    // ============================================================
    // CAPA
    // ============================================================
    async function previewCapa(input) {
        if (!input.files || !input.files[0]) return;
        const fd = new FormData();
        fd.append('foto', input.files[0]);
        fd.append('id_campanha', idCampanha);
        try {
            const res  = await fetch('../app/ajax/salvar-foto-capa.php',{method:'POST',body:fd});
            const data = await res.json();
            if (data.success) { document.getElementById('banner-campanha-display').style.backgroundImage=`url('${data.url}')`; fecharModal('modal-foto-capa'); location.reload(); }
            else alert('Erro no upload: '+data.error);
        } catch(e) { console.error(e); }
    }

    // ============================================================
    // ESCUDO DO MESTRE
    // ============================================================
    let combateAtivo=null, iniciativaLista=[], indexTurno=0, subTabAtiva='atributos', participanteSelecionado=null;

    function switchEscudoTab(tab, btn) {
        if (!btn) return;
        document.querySelectorAll('.escudo-link-nav').forEach(b=>b.classList.remove('ativo'));
        btn.classList.add('ativo');
        ['personagens','combates','investigacoes','relatorios','dados','anotacoes'].forEach(t => {
            const el = document.getElementById('escudo-tab-'+t);
            if (el) el.classList[t===tab?'remove':'add']('escondido');
        });
    }

    function iniciarCombateEscudo(id, nome) {
        document.getElementById('escudo-combates-lista').classList.add('escondido');
        document.getElementById('escudo-combate-ativo').classList.remove('escondido');
        iniciativaLista = [
            ...campanhaInicialPersonagems.map(a=>({...a,tipo:'Personagem',iniciativa:Math.floor(Math.random()*20)+10})),
            {nm_personagem:'Criatura de Sangue',qt_vida:60,qt_vida_maxima:60,tipo:'monstro',iniciativa:15,ds_foto:'../img/logo_icone.png'}
        ].sort((a,b)=>b.iniciativa-a.iniciativa);
        renderListaIniciativa();
    }

    function renderListaIniciativa() {
        const c = document.getElementById('lista-iniciativa-escudo'); if (!c) return;
        c.innerHTML = iniciativaLista.map((p,i)=>`
            <div class="item-iniciativa ${i===indexTurno?'ativo':''}" onclick="selecionarParticipanteEscudo(${i})">
                <img src="${p.ds_foto||'../img/uploads/perfil/avatar1.png'}" class="img-iniciativa">
                <div class="info-iniciativa">
                    <h4 style="color:#fff;margin:0;font-size:.95rem;">${p.nm_personagem||p.nm_monstro}</h4>
                    <div style="display:flex;gap:10px;margin-top:4px;">
                        <span style="color:#ff4d4d;font-size:.7rem;font-weight:700;"><i class="fas fa-heart"></i> ${p.qt_vida}/${p.qt_vida_maxima}</span>
                        ${p.tipo==='Personagem'?`<span style="color:#7c3aed;font-size:.7rem;font-weight:700;"><i class="fas fa-brain"></i> ${p.qt_sanidade||0}</span>`:''}
                    </div>
                </div>
                <div style="color:#fff;opacity:.5;font-weight:800;margin-left:auto;">${p.iniciativa}</div>
            </div>`).join('');
    }

    function selecionarParticipanteEscudo(i) { indexTurno=i; participanteSelecionado=iniciativaLista[i]; renderListaIniciativa(); renderDetalheParticipante(); }

    function renderDetalheParticipante() {
        const p=participanteSelecionado; if(!p) return;
        const c=document.getElementById('detalhe-participante-escudo'); if(!c) return;
        c.innerHTML=`
            <div class="detalhe-header"><h2>${p.nm_personagem}</h2><p>${p.nm_classe||'Ameaça'} • ${p.tipo==='Personagem'?'Personagem':'Monstro'}</p></div>
            <div class="barras-detalhes">
                ${renderBarraAjustavel('Vida',p.qt_vida,p.qt_vida_maxima,'vida')}
                ${p.tipo==='Personagem'?renderBarraAjustavel('Sanidade',p.qt_sanidade,p.qt_sanidade_maxima,'sanidade'):''}
                ${p.tipo==='Personagem'?renderBarraAjustavel('Esforço',p.qt_esforco,p.qt_esforco_maximo,'esforco'):''}
            </div>
            <div class="escudo-sub-nav">
                <div class="btn-sub-aba ${subTabAtiva==='atributos'?'ativa':''}" onclick="switchEscudoSubTab('atributos')">Atributos</div>
                <div class="btn-sub-aba ${subTabAtiva==='combates'?'ativa':''}"  onclick="switchEscudoSubTab('combates')">Combates</div>
                <div class="btn-sub-aba ${subTabAtiva==='rituais'?'ativa':''}"   onclick="switchEscudoSubTab('rituais')">Rituais</div>
            </div>
            <div id="escudo-sub-aba-content">${renderSubAbaContent(p)}</div>`;
    }

    function renderBarraAjustavel(label, atual, max, tipo) {
        return `<div class="barra-ajustavel">
            <div class="controle-recurso">
                <div style="display:flex;gap:5px;"><span class="btn-ajuste" onclick="ajustarRecurso('${tipo}',-5)">-5</span><span class="btn-ajuste" onclick="ajustarRecurso('${tipo}',-1)">-1</span></div>
                <div class="valor-barra" style="color:#fff;font-weight:800;">${label}: ${atual}/${max}</div>
                <div style="display:flex;gap:5px;"><span class="btn-ajuste" onclick="ajustarRecurso('${tipo}',1)">+1</span><span class="btn-ajuste" onclick="ajustarRecurso('${tipo}',5)">+5</span></div>
            </div>
            <div class="bg-barra-detalhe"><div class="fill-barra-detalhe fill-${tipo}-d" style="width:${max>0?(atual/max)*100:0}%"></div></div>
        </div>`;
    }

    function ajustarRecurso(tipo, val) {
        if (!participanteSelecionado) return;
        const f=tipo==='vida'?'qt_vida':(tipo==='sanidade'?'qt_sanidade':'qt_esforco');
        const m=f+(tipo==='esforco'?'_maximo':'_maxima');
        participanteSelecionado[f]=Math.max(0,Math.min(participanteSelecionado[m]||1,(parseInt(participanteSelecionado[f])||0)+val));
        renderDetalheParticipante(); renderListaIniciativa();
    }

    function renderSubAbaContent(p) {
        if (subTabAtiva==='atributos') {
            const attrs=(p.atributos&&p.atributos.length)?p.atributos:[{ds_abreviacao:'AGI',qt_valor:p.qt_agilidade||0},{ds_abreviacao:'FOR',qt_valor:p.qt_forca||0},{ds_abreviacao:'INT',qt_valor:p.qt_intelecto||0},{ds_abreviacao:'PRE',qt_valor:p.qt_presenca||0},{ds_abreviacao:'VIG',qt_valor:p.qt_vigor||0}];
            return `<div class="diagrama-atributos-real">${attrs.map((a,i)=>{const angle=(i*2*Math.PI/attrs.length)-(Math.PI/2),r=90,x=150+r*Math.cos(angle),y=150+r*Math.sin(angle);return `<div class="hex-atributo" style="top:${y}px;left:${x}px;transform:translate(-50%,-50%);"><span>${a.ds_abreviacao||a.nm_atributo}</span><strong>${a.qt_valor}</strong></div>`;}).join('')}<div class="texto-central-atributos" style="color:#666;font-size:.7rem;">Atributos</div></div>`;
        }
        if (subTabAtiva==='combates') return `<div style="padding:20px;"><h4 style="color:#fff;margin-bottom:15px;font-size:.9rem;text-transform:uppercase;">Ataques e Habilidades</h4><div style="background:rgba(255,255,255,.03);padding:15px;border-radius:12px;border-left:4px solid var(--premium-accent);"><h5 style="margin:0;color:#fff;font-size:.9rem;">Ataque Básico</h5><p style="margin:5px 0 0;font-size:.8rem;color:#888;">Teste: D20 + Pontaria | Dano: 1d10</p></div></div>`;
        return `<div style="padding:20px;color:#888;font-size:.85rem;">Nenhum ritual ou habilidade especial encontrado.</div>`;
    }

    function switchEscudoSubTab(tab) { subTabAtiva=tab; renderDetalheParticipante(); }

    // ============================================================
    // INVESTIGAÇÕES / RELATÓRIOS
    // ============================================================
    function abrirFichaInvestigacao()  { document.getElementById('inv-modo-lista').classList.add('escondido');    document.getElementById('inv-modo-detalhe').classList.remove('escondido'); }
    function voltarListaInvestigacao() { document.getElementById('inv-modo-lista').classList.remove('escondido'); document.getElementById('inv-modo-detalhe').classList.add('escondido'); }
    function novaFichaInvestigacao()   { abrirFichaInvestigacao(); }
    function abrirRelatorioMissao()    { document.getElementById('rel-modo-lista').classList.add('escondido');    document.getElementById('rel-modo-detalhe').classList.remove('escondido'); }
    function voltarListaRelatorio()    { document.getElementById('rel-modo-lista').classList.remove('escondido'); document.getElementById('rel-modo-detalhe').classList.add('escondido'); }
    function novoRelatorioMissao()     { abrirRelatorioMissao(); }
    function setRelStatus(btn) {
        btn.closest('.status-toggle-group').querySelectorAll('.btn-status-rel').forEach(b=>b.classList.remove('ativo'));
        btn.classList.add('ativo');
    }

    // ============================================================
    // DADOS — ESCUDO DO MESTRE (dddice integrado)
    // ============================================================
    const ESCUDO_API_KEY   = <?php echo json_encode(DDDICE_API_KEY); ?>;
    const ESCUDO_ROOM_SLUG = <?php echo json_encode(DDDICE_ROOM_SLUG); ?>;

    // Mapa de lados → tipo dddice (dados suportados pela API)
    const ESCUDO_DDDICE_MAP = { 4:'d4', 6:'d6', 8:'d8', 10:'d10', 12:'d12', 20:'d20' };

    let escudoSelecao   = {};
    let escudoDddiceSDK = null;
    let escudoThemeId   = '';
    let escudoRolling   = false;

    async function initEscudoSDK() {
        if (escudoDddiceSDK) return;
        setEscudoStatus('loading');
        if (!window.ThreeDDice) { setEscudoStatus('error'); showEscudoToast('SDK dddice não carregou.'); return; }
        try {
            const canvas = document.getElementById('dddice-canvas-escudo');
            escudoDddiceSDK = new window.ThreeDDice(canvas, ESCUDO_API_KEY);
            escudoDddiceSDK.start();
            await escudoDddiceSDK.connect(ESCUDO_ROOM_SLUG);
            await carregarTemasEscudo();
            setEscudoStatus('ok');
        } catch (err) { console.error('initEscudoSDK:', err); setEscudoStatus('error'); showEscudoToast('Erro ao conectar ao dddice: ' + err.message); }
    }

    async function carregarTemasEscudo() {
        const select = document.getElementById('escudo-theme-select');
        select.innerHTML = '<option value="">Carregando...</option>';
        const resp = await fetch('?action=themes_escudo');
        const data = await resp.json();
        if (data.error) throw new Error(data.error);
        const themes = data.themes ?? [];
        select.innerHTML = '';
        if (!themes.length) { select.innerHTML = '<option value="">Nenhum tema na Dice Box</option>'; showEscudoToast('Adicione um tema em dddice.com → Account → Dice Box'); return; }
        themes.forEach(t => { const opt = document.createElement('option'); opt.value = t.id; opt.textContent = t.name || t.id; select.appendChild(opt); });
        select.disabled = false;
        escudoThemeId = select.value;
        select.addEventListener('change', () => { escudoThemeId = select.value; atualizarBtnEscudo(); });
        atualizarBtnEscudo();
    }

    function inicializarEventosDadosEscudo() {
        document.querySelectorAll('#escudo-tab-dados .item-dado').forEach(item => {
            item.addEventListener('click', () => {
                const lados = parseInt(item.dataset.lados);
                const atual = escudoSelecao[lados] ?? 0;
                const novo  = Math.min(10, atual + 1);
                escudoSelecao[lados] = novo;
                const bolinha = document.getElementById(`escudo-bolinha-d${lados}`);
                if (bolinha) { bolinha.textContent = novo; bolinha.classList.add('show'); }
                item.classList.add('selecionado');
                const container = item.querySelector('.dado-icon-container');
                const img = item.querySelector('.img-dado');
                if (container && img) {
                    const src = img.src;
                    container.classList.add('dado-girando');
                    img.src = `../img/dados/D${lados} efeito.png`;
                    setTimeout(() => { container.classList.remove('dado-girando'); img.src = src; }, 600);
                }
                atualizarResumoEscudo();
                atualizarBtnEscudo();
            });
        });
    }

    function limparSelecaoEscudo() {
        escudoSelecao = {};
        [2,4,6,8,10,12,20,100].forEach(d => {
            const bolinha = document.getElementById(`escudo-bolinha-d${d}`);
            if (bolinha) { bolinha.textContent = '0'; bolinha.classList.remove('show'); }
            const item = document.getElementById(`escudo-dado-d${d}`);
            if (item) item.classList.remove('selecionado');
        });
        atualizarResumoEscudo();
        atualizarBtnEscudo();
    }

    function atualizarResumoEscudo() {
        const el    = document.getElementById('escudo-sel-resumo');
        const parts = Object.entries(escudoSelecao).filter(([,q]) => q > 0);
        if (!parts.length) { el.innerHTML = '<span style="color:#555;">Clique nos dados para selecionar...</span>'; return; }
        let html = parts.map(([l,q]) => `<span class="chip">${q}D${l}</span>`).join('');
        html += `<button class="btn-escudo-limpar-sel" onclick="limparSelecaoEscudo()">✕</button>`;
        el.innerHTML = html;
    }

    function atualizarBtnEscudo() {
        const temDados  = Object.values(escudoSelecao).some(q => q > 0);
        const sdkPronto = !!escudoThemeId && !escudoRolling;
        const btn = document.getElementById('escudo-btn-rolar');
        if (btn) btn.disabled = !(temDados && sdkPronto);
    }

    async function escudoExecutarRolagem() {
        if (escudoRolling) return;
        const entries = Object.entries(escudoSelecao).filter(([,q]) => q > 0);
        if (!entries.length) return showEscudoToast('Selecione ao menos um dado!');
        if (!escudoThemeId) return showEscudoToast('Selecione um tema primeiro!');
        escudoRolling = true;
        const btn = document.getElementById('escudo-btn-rolar');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rolando...'; }

        const dddiceEntries = entries.filter(([l]) => ESCUDO_DDDICE_MAP[parseInt(l)]);
        const jsEntries     = entries.filter(([l]) => !ESCUDO_DDDICE_MAP[parseInt(l)]);
        const dddDice = [];
        dddiceEntries.forEach(([lados, qtd]) => { const tipo = ESCUDO_DDDICE_MAP[parseInt(lados)]; for (let i = 0; i < qtd; i++) dddDice.push({ type: tipo, theme: escudoThemeId }); });
        let jsTotal = 0, jsValues = [];
        jsEntries.forEach(([lados, qtd]) => { for (let i = 0; i < qtd; i++) { const v = Math.floor(Math.random() * parseInt(lados)) + 1; jsTotal += v; jsValues.push({ value: v, type: `d${lados}` }); } });
        const label = entries.map(([l,q]) => `${q}D${l}`).join(' + ');

        try {
            let finalTotal = jsTotal, finalValues = [...jsValues];
            if (dddDice.length > 0) {
                const [, phpResult] = await Promise.all([
                    escudoDddiceSDK ? escudoDddiceSDK.roll(dddDice).catch(e => console.warn('SDK:', e)) : Promise.resolve(),
                    fetch('?action=roll_escudo', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ dice: dddDice }) }).then(r => r.json()),
                ]);
                if (phpResult.error) { showEscudoToast('Erro dddice: ' + phpResult.error); escudoRolling = false; if (btn) btn.innerHTML = '<i class="fas fa-dice"></i> Rolar com dddice'; atualizarBtnEscudo(); return; }
                finalTotal  += phpResult.total;
                finalValues  = [...phpResult.values, ...jsValues];
                setTimeout(() => { mostrarResultadoEscudo(finalTotal, finalValues, label); adicionarAoHistoricoEscudo(finalTotal, label); limparSelecaoEscudo(); }, 1200);
            } else {
                mostrarResultadoEscudo(finalTotal, finalValues, label);
                adicionarAoHistoricoEscudo(finalTotal, label);
                limparSelecaoEscudo();
            }
        } catch (err) { console.error(err); showEscudoToast('Erro na rolagem: ' + err.message); }
        finally { escudoRolling = false; if (btn) btn.innerHTML = '<i class="fas fa-dice"></i> Rolar com dddice'; atualizarBtnEscudo(); }
    }

    function mostrarResultadoEscudo(total, values, label) {
        const totalEl     = document.getElementById('escudo-result-total');
        const breakdownEl = document.getElementById('escudo-result-breakdown');
        const labelEl     = document.getElementById('escudo-result-label');
        labelEl.textContent = label;
        totalEl.classList.remove('pop');
        void totalEl.offsetWidth;
        totalEl.textContent = total;
        totalEl.classList.add('pop');
        if (values.length > 1) {
            breakdownEl.innerHTML = values.map(v => `<span class="val">${v.value}</span>`).join('<span class="op">+</span>') + `<span class="op">=</span><span class="val">${total}</span>`;
        } else { breakdownEl.innerHTML = ''; }
        document.getElementById('escudo-result-popup').classList.add('show');
    }

    function fecharResultadoEscudo() { document.getElementById('escudo-result-popup').classList.remove('show'); }

    function adicionarAoHistoricoEscudo(resultado, descricao) {
        const logContainer = document.getElementById('sidebar-dados-lista'); if (!logContainer) return;
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const novoItem = document.createElement('div'); novoItem.className = 'item-dado real-roll';
        novoItem.innerHTML = `<div class="hexa-dado" style="border-color:var(--premium-accent);color:#000;background:#fff;font-weight:800;display:flex;align-items:center;justify-content:center;">${resultado}</div><div class="info-rolagem"><p>${time} • ${descricao}</p><h4 style="color:#fff;">Mestre <span style="font-size:0.6rem;color:var(--premium-accent);font-weight:700;background:rgba(139,92,246,0.15);padding:1px 5px;border-radius:4px;">dddice</span></h4></div>`;
        if (logContainer.querySelectorAll('.real-roll').length === 0) { logContainer.querySelectorAll('.item-dado:not(.real-roll)').forEach(p => p.remove()); }
        logContainer.prepend(novoItem);
    }

    function setEscudoStatus(state) {
        const dot = document.getElementById('escudo-status-dot'); if (!dot) return;
        dot.className = state;
        dot.title = state === 'ok' ? 'Conectado ao dddice' : state === 'loading' ? 'Conectando...' : 'Erro';
    }

    function showEscudoToast(msg) {
        const t = document.getElementById('escudo-toast'); if (!t) return;
        t.textContent = msg; t.classList.add('show');
        clearTimeout(t._timer); t._timer = setTimeout(() => t.classList.remove('show'), 4500);
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharResultadoEscudo(); });
    </script>

</body>
</html>
