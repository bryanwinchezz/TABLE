<?php
session_start();

// ============================================================
//  DDDICE — Configurações (mesmas do dddice-hybrid.php)
// ============================================================
define('DDDICE_API_KEY',   'Insira sua API Key do DDDice aqui');
define('DDDICE_ROOM_SLUG', 'Insira seu room slug do DDDice aqui');

// ---- Endpoint AJAX: ?action=roll (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'roll') {
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

// ---- Endpoint AJAX: ?action=themes (GET) ----
if (($_GET['action'] ?? '') === 'themes') {
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
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Rolagem de Dados</title>
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Montserrat:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- SDK dddice -->
    <script src="https://cdn.dddice.com/js/dddice-latest.js"></script>
    <style>
        :root {
            --premium-accent: #8b5cf6;
            --premium-bg: #0d091a;
            --premium-card: rgba(255, 255, 255, 0.05);
        }

        body {
            background-color: var(--premium-bg);
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 120px 5% 60px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .rolador-container {
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }

        @media (max-width: 900px) {
            .rolador-container {
                grid-template-columns: 1fr;
            }
        }

        /* ÁREA DOS DADOS */
        .dados-grid-box {
            background: var(--premium-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(10px);
        }

        .titulo-secao {
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            margin-bottom: 10px;
            text-align: center;
            color: #fff;
            text-shadow: 0 0 15px rgba(139, 92, 246, 0.4);
        }

        /* SELETOR DE TEMA */
        .tema-selector-wrap {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px;
            padding: 12px 16px;
        }

        .tema-selector-wrap label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            white-space: nowrap;
        }

        #theme-select {
            flex: 1;
            background: transparent;
            border: none;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.85rem;
            outline: none;
            cursor: pointer;
        }

        #theme-select option { background: #0d091a; }

        #status-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #e74c3c; flex-shrink: 0;
            transition: background 0.3s, box-shadow 0.3s;
        }
        #status-dot.ok      { background: #2ecc71; box-shadow: 0 0 7px #2ecc71; }
        #status-dot.loading { background: var(--premium-accent); animation: blink 0.9s infinite; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.15} }

        /* GRID DE DADOS */
        .grid-dados {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .item-dado {
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            position: relative; /* para a bolinha */
        }

        .dado-icon-container {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }

        .img-dado {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.5));
        }

        .item-dado:hover .dado-icon-container {
            transform: scale(1.15) rotate(5deg);
        }

        .label-dado {
            font-weight: 800;
            font-size: 0.9rem;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .dado-girando {
            animation: girarDado 0.6s ease-in-out;
        }

        @keyframes girarDado {
            0%   { transform: rotate(0deg) scale(1); }
            25%  { transform: rotate(90deg) scale(1.3) translateY(-5px); }
            50%  { transform: rotate(180deg) scale(1); }
            75%  { transform: rotate(270deg) scale(1.3) translateY(-5px); }
            100% { transform: rotate(360deg) scale(1); }
        }

        /* ── BOLINHA CONTADOR (estilo do index.php enviado) ── */
        .bolinha-contador {
            position: absolute;
            top: -12px;
            right: -12px;
            width: 36px;
            height: 36px;
            background-color: var(--premium-accent);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 900;
            font-size: 0.85rem;
            box-shadow: 0 2px 8px rgba(139,92,246,0.6);
            opacity: 0;
            transform: scale(0);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 5;
            pointer-events: none;
        }

        .bolinha-contador.show {
            opacity: 1;
            transform: scale(1);
        }

        /* Destaque do dado selecionado */
        .item-dado.selecionado .dado-icon-container {
            filter: drop-shadow(0 0 12px var(--premium-accent));
        }

        /* RESUMO DA SELEÇÃO */
        .selecao-resumo {
            background: rgba(139, 92, 246, 0.08);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 12px;
            padding: 12px 18px;
            font-size: 0.8rem;
            color: #aaa;
            margin-bottom: 20px;
            min-height: 40px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .selecao-resumo .chip {
            background: rgba(139,92,246,0.2);
            border: 1px solid rgba(139,92,246,0.4);
            border-radius: 20px;
            padding: 3px 10px;
            font-weight: 700;
            color: #c4b5fd;
            font-size: 0.8rem;
        }

        .btn-limpar-selecao {
            margin-left: auto;
            background: none;
            border: 1px solid rgba(255,255,255,0.1);
            color: #666;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 0.72rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-limpar-selecao:hover { border-color: #ff4d4d; color: #ff4d4d; }

        /* BOTÃO ROLAR */
        .btn-multi-rolagem {
            width: 100%;
            background: linear-gradient(135deg, #6d28d9, #8b5cf6);
            color: #fff;
            border: none;
            padding: 18px;
            border-radius: 15px;
            font-weight: 800;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 20px rgba(109, 40, 217, 0.3);
        }

        .btn-multi-rolagem:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(109, 40, 217, 0.5);
            filter: brightness(1.1);
        }

        .btn-multi-rolagem:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
        }

        /* HISTÓRICO */
        .historico-box {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 30px;
            display: flex;
            flex-direction: column;
            max-height: 580px;
            box-sizing: border-box;
        }

        .historico-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .historico-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .btn-limpar-log {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 0.8rem;
            transition: color 0.3s;
        }

        .btn-limpar-log:hover { color: #ff4d4d; }

        #historico-lista {
            overflow-y: auto;
            flex: 1;
            padding-right: 10px;
        }

        #historico-lista::-webkit-scrollbar { width: 5px; }
        #historico-lista::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); }
        #historico-lista::-webkit-scrollbar-thumb { background: var(--premium-accent); border-radius: 10px; }

        .log-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 15px;
            margin-bottom: 12px;
            animation: slideIn 0.3s ease-out;
            border-left: 3px solid transparent;
        }

        .log-item.dddice-roll { border-left-color: var(--premium-accent); }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .log-resultado {
            width: 45px;
            height: 45px;
            background: #fff;
            color: #000;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.2rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        .log-info p { margin: 0; font-size: 0.75rem; color: #888; }
        .log-info h4 { margin: 2px 0 0; font-size: 0.9rem; color: #fff; }
        .log-info .badge-dddice {
            display: inline-block;
            background: rgba(139,92,246,0.2);
            color: var(--premium-accent);
            border-radius: 4px;
            padding: 1px 6px;
            font-size: 0.65rem;
            font-weight: 700;
            margin-left: 4px;
        }

        /* ── CANVAS DDDICE — fullscreen overlay ── */
        #dddice-canvas {
            position: fixed;
            inset: 0;
            width: 100vw;
            height: 100vh;
            display: block;
            z-index: 9000;
            pointer-events: none;
        }

        /* ── POP-UP DE RESULTADO ── */
        #result-popup {
            position: fixed;
            inset: 0;
            z-index: 9500;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        #result-popup.show { opacity: 1; pointer-events: auto; }

        #result-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            opacity: 0;
            transition: opacity 0.3s;
        }
        #result-popup.show #result-backdrop { opacity: 1; }

        #result-card {
            position: relative;
            background: linear-gradient(155deg, #0d0e18 0%, #130f1c 100%);
            border: 2px solid var(--premium-accent);
            border-radius: 24px;
            padding: 48px 60px 40px;
            text-align: center;
            box-shadow:
                0 0 0 1px rgba(139,92,246,0.08),
                0 0 60px rgba(139,92,246,0.2),
                0 32px 80px rgba(0,0,0,0.8);
            transform: scale(0.82) translateY(18px);
            transition: transform 0.38s cubic-bezier(0.34,1.56,0.64,1);
            min-width: 300px;
            cursor: pointer;
        }
        #result-popup.show #result-card { transform: scale(1) translateY(0); }

        #result-label {
            font-family: 'Cinzel', serif;
            font-size: 0.72rem;
            letter-spacing: 4px;
            color: var(--premium-accent);
            text-transform: uppercase;
            margin-bottom: 12px;
            opacity: 0.85;
        }

        #result-total {
            font-family: 'Cinzel', serif;
            font-size: 6rem;
            font-weight: 900;
            line-height: 1;
            color: #fff;
            text-shadow: 0 0 30px rgba(139,92,246,0.6), 0 0 60px rgba(139,92,246,0.3);
            margin-bottom: 12px;
        }

        @keyframes pop-in {
            0%   { transform: scale(0.4); opacity: 0; }
            65%  { transform: scale(1.12); }
            100% { transform: scale(1); opacity: 1; }
        }
        #result-total.pop { animation: pop-in 0.45s cubic-bezier(0.34,1.56,0.64,1) forwards; }

        #result-breakdown {
            font-size: 0.85rem;
            color: #666;
            letter-spacing: 1px;
            margin-bottom: 6px;
            min-height: 20px;
        }
        #result-breakdown .val { color: #c4b5fd; font-weight: 700; }
        #result-breakdown .op  { color: #3a3528; margin: 0 3px; }

        #result-dice-info { font-size: 0.75rem; color: #444; margin-top: 4px; font-style: italic; }

        #result-dismiss {
            margin-top: 22px;
            font-size: 0.62rem;
            color: #333;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-family: 'Cinzel', serif;
        }

        /* TOAST */
        #toast {
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
            max-width: 420px;
            text-align: center;
        }
        #toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
    </style>
</head>

<body>
    <!-- Canvas dddice — fullscreen, sobrepõe tudo durante a animação -->
    <canvas id="dddice-canvas"></canvas>

    <header>
        <div class="logotipo">
            <a href="index.php"><img src="../img/logo_horizontal.png" alt="Logo TABLE"></a>
        </div>

        <!-- BOTÃO MENU MOBILE (HAMBURGER) -->
        <div class="menu-toggle" id="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </div>

        <nav id="nav-menu">
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="cm-jogar.php">Como Jogar</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
                </li>
                <li><a href="criar-mapa.php">Mundos</a></li>
                <li><a href="rolagem-de-dados.php" class="ativo">Dados</a></li>
                <li><a href="sobre-nos.php">Sobre Nós</a></li>
            </ul>

            <!-- BOTÕES MOBILE -->
            <div class="nav-mobile-footer">
                <?php if (isset($_SESSION['usuario'])): ?>
                    <div class="usuario-logado-nav" onclick="window.location.href='perfil.php'">
                        <img src="<?= !empty($_SESSION['usuario']['foto']) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png' ?>"
                            alt="Avatar Navbar" class="avatar-nav">
                        <span class="nome-nav"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="botao-entrar">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="cadastro.php" class="botao-cadastrar">
                        <i class="fas fa-user-plus"></i> Cadastre-se
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <?php if (isset($_SESSION['usuario'])): ?>
            <div class="usuario-logado-nav desktop-only" id="nav-logado" onclick="window.location.href='perfil.php'"
                title="Ir para o Perfil">
                <img src="<?= !empty($_SESSION['usuario']['foto']) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png' ?>"
                    alt="Avatar Navbar" class="avatar-nav">
                <span class="nome-nav"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
            </div>
        <?php else: ?>
            <div class="botoes-navegacao desktop-only" id="nav-deslogado">
                <a href="login.php" class="botao-entrar">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="cadastro.php" class="botao-cadastrar">
                    <i class="fas fa-user-plus"></i> Cadastre-se
                </a>
            </div>
        <?php endif; ?>
    </header>

    <main class="main-content">
        <div class="rolador-container">
            <div class="dados-area">
                <div class="dados-grid-box">
                    <h2 class="titulo-secao">Rolagem de Dados</h2>

                    <!-- Seletor de tema dddice -->
                    <div class="tema-selector-wrap">
                        <span id="status-dot" class="loading" title="Conectando..."></span>
                        <label>Tema dddice:</label>
                        <select id="theme-select" disabled>
                            <option value="">Conectando...</option>
                        </select>
                    </div>

                    <!-- Grade de dados — cada clique incrementa a bolinha contadora -->
                    <div class="grid-dados">
                        <?php
                        $dados = [2, 4, 6, 8, 10, 12, 20, 100];
                        foreach ($dados as $d):
                            // D2 e D100 não têm suporte nativo no dddice, usam JS puro
                            $suportaDddice = !in_array($d, [2, 100]);
                        ?>
                        <div class="item-dado" data-lados="<?= $d ?>" data-suporte="<?= $suportaDddice ? '1' : '0' ?>">
                            <div class="dado-icon-container">
                                <img src="../img/dados/D<?= $d ?>.png" alt="D<?= $d ?>" class="img-dado">
                            </div>
                            <span class="label-dado">D<?= $d ?></span>
                            <!-- Bolinha contadora (estilo index.php) -->
                            <div class="bolinha-contador" id="bolinha-d<?= $d ?>">0</div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Resumo da seleção atual -->
                    <div class="selecao-resumo" id="sel-resumo">
                        <span style="color: #555;">Clique nos dados para selecionar quantidades...</span>
                    </div>

                    <!-- Botão Rolar Principal -->
                    <button class="btn-multi-rolagem" id="btn-rolar" disabled onclick="executarRolagem()">
                        <i class="fas fa-dice"></i> Rolar Dados
                    </button>
                </div>
            </div>

            <div class="historico-area">
                <div class="historico-box">
                    <div class="historico-header">
                        <h3>Histórico</h3>
                        <button class="btn-limpar-log" onclick="limparHistorico()">Limpar</button>
                    </div>
                    <div id="historico-lista">
                        <div style="text-align: center; padding: 40px; color: #555;" id="msg-vazio">
                            Nenhuma rolagem ainda.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Pop-up de resultado central -->
    <div id="result-popup" onclick="fecharResultado()">
        <div id="result-backdrop"></div>
        <div id="result-card" onclick="event.stopPropagation()">
            <div id="result-label">Resultado</div>
            <div id="result-total">—</div>
            <div id="result-breakdown"></div>
            <div id="result-dice-info"></div>
            <div id="result-dismiss">Clique para fechar · ESC</div>
        </div>
    </div>

    <!-- Toast de erro -->
    <div id="toast"></div>

    <footer class="rodape-principal">
        <div class="rodape-conteudo">
            <div class="rodape-logo-area">
                <div class="rodape-marca">
                    <img src="../img/logo_branco.png" alt="Logo TABLE">
                    <span>TABLE</span>
                </div>
                <p>Acompanhe uma experiência imersiva nos mundos de RPG. Aprenda e jogue com seus amigos!</p>
            </div>
            <div class="rodape-links">
                <h4>Navegação</h4>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="cm-jogar.php" class="ativo">Como Jogar</a></li>
                    <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a></li>
                    <li><a href="criar-mapa.php">Mundos</a></li>
                    <li><a href="rolagem-de-dados.php">Dados</a></li>
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

    <script>
    // ============================================================
    //  Credenciais injetadas pelo PHP (sem exposição via fetch)
    // ============================================================
    const API_KEY   = <?php echo json_encode(DDDICE_API_KEY); ?>;
    const ROOM_SLUG = <?php echo json_encode(DDDICE_ROOM_SLUG); ?>;

    // Mapa de lados → tipo dddice (só dados suportados)
    const DDDICE_TYPE_MAP = {
        4:   'd4',
        6:   'd6',
        8:   'd8',
        10:  'd10',
        12:  'd12',
        20:  'd20',
    };

    // Estado global
    let selecao   = {};   // { 6: 2, 20: 1, ... }
    let dddiceSDK = null;
    let themeId   = '';
    let rolling   = false;

    // ── INIT ──────────────────────────────────────────────────
    window.addEventListener('DOMContentLoaded', () => {
        inicializarEventosDados();
        initSDK();
        carregarHistoricoLocal();
    });

    // ── EVENTOS DOS DADOS (clique = +1 bolinha) ───────────────
    function inicializarEventosDados() {
        document.querySelectorAll('.item-dado').forEach(item => {
            item.addEventListener('click', () => {
                const lados = parseInt(item.dataset.lados);
                // Incrementa (máx 10)
                const atual = selecao[lados] ?? 0;
                const novo  = Math.min(10, atual + 1);
                selecao[lados] = novo;

                // Atualiza bolinha (padrão index.php)
                const bolinha = document.getElementById(`bolinha-d${lados}`);
                if (bolinha) {
                    bolinha.textContent = novo;
                    bolinha.classList.add('show');
                }

                item.classList.add('selecionado');

                // Animação visual do dado
                const container = item.querySelector('.dado-icon-container');
                const img       = item.querySelector('.img-dado');
                if (container && img) {
                    const src = img.src;
                    container.classList.add('dado-girando');
                    img.src = `../img/dados/D${lados} efeito.png`;
                    setTimeout(() => {
                        container.classList.remove('dado-girando');
                        img.src = src;
                    }, 600);
                }

                atualizarResumo();
                atualizarBtnRolar();
            });
        });
    }

    function limparSelecao() {
        selecao = {};
        document.querySelectorAll('.item-dado').forEach(item => {
            const lados   = parseInt(item.dataset.lados);
            const bolinha = document.getElementById(`bolinha-d${lados}`);
            if (bolinha) {
                bolinha.textContent = '0';
                bolinha.classList.remove('show');
            }
            item.classList.remove('selecionado');
        });
        atualizarResumo();
        atualizarBtnRolar();
    }

    function atualizarResumo() {
        const el    = document.getElementById('sel-resumo');
        const parts = Object.entries(selecao).filter(([,q]) => q > 0);

        if (!parts.length) {
            el.innerHTML = '<span style="color:#555;">Clique nos dados para selecionar quantidades...</span>';
            return;
        }

        let html = parts.map(([l, q]) => `<span class="chip">${q}D${l}</span>`).join('');
        html += `<button class="btn-limpar-selecao" onclick="limparSelecao()">✕ Limpar</button>`;
        el.innerHTML = html;
    }

    function atualizarBtnRolar() {
        const temDados  = Object.values(selecao).some(q => q > 0);
        const sdkPronto = !!themeId && !rolling;
        document.getElementById('btn-rolar').disabled = !(temDados && sdkPronto);
    }

    // ── SDK dddice ────────────────────────────────────────────
    async function initSDK() {
        setStatus('loading');

        if (!window.ThreeDDice) {
            setStatus('error');
            showToast('SDK dddice não carregou. Verifique sua conexão.');
            return;
        }

        try {
            const canvas = document.getElementById('dddice-canvas');
            dddiceSDK = new window.ThreeDDice(canvas, API_KEY);
            dddiceSDK.start();
            await dddiceSDK.connect(ROOM_SLUG);
            await carregarTemas();
            setStatus('ok');
        } catch (err) {
            console.error('initSDK:', err);
            setStatus('error');
            showToast('Erro ao conectar ao dddice: ' + err.message);
        }
    }

    async function carregarTemas() {
        const select = document.getElementById('theme-select');
        select.innerHTML = '<option value="">Carregando...</option>';

        const resp = await fetch('?action=themes');
        const data = await resp.json();

        if (data.error) throw new Error(data.error);

        const themes = data.themes ?? [];
        select.innerHTML = '';

        if (!themes.length) {
            select.innerHTML = '<option value="">Nenhum tema na Dice Box</option>';
            showToast('Adicione um tema em dddice.com → Account → Dice Box');
            return;
        }

        themes.forEach(t => {
            const opt       = document.createElement('option');
            opt.value       = t.id;
            opt.textContent = t.name || t.id;
            select.appendChild(opt);
        });

        select.disabled = false;
        themeId = select.value;
        select.addEventListener('change', () => { themeId = select.value; atualizarBtnRolar(); });
        atualizarBtnRolar();
    }

    // ── ROLAR DADOS (híbrido: SDK animação + PHP resultado) ───
    async function executarRolagem() {
        if (rolling) return;

        const entries = Object.entries(selecao).filter(([,q]) => q > 0);
        if (!entries.length) return showToast('Selecione ao menos um dado!');
        if (!themeId)        return showToast('Selecione um tema primeiro!');

        rolling = true;
        const btn = document.getElementById('btn-rolar');
        btn.disabled   = true;
        btn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i> Rolando...';

        // Separar dados com/sem suporte dddice
        const dddiceEntries = entries.filter(([l]) => DDDICE_TYPE_MAP[parseInt(l)]);
        const jsEntries     = entries.filter(([l]) => !DDDICE_TYPE_MAP[parseInt(l)]);

        // Monta array para a API
        const dddDice = [];
        dddiceEntries.forEach(([lados, qtd]) => {
            const tipo = DDDICE_TYPE_MAP[parseInt(lados)];
            for (let i = 0; i < qtd; i++) dddDice.push({ type: tipo, theme: themeId });
        });

        // Resultado JS para dados sem suporte (d2, d100)
        let jsTotal  = 0;
        let jsValues = [];
        jsEntries.forEach(([lados, qtd]) => {
            for (let i = 0; i < qtd; i++) {
                const v = Math.floor(Math.random() * parseInt(lados)) + 1;
                jsTotal += v;
                jsValues.push({ value: v, type: `d${lados}` });
            }
        });

        try {
            let finalTotal  = jsTotal;
            let finalValues = [...jsValues];
            let finalLabel  = entries.map(([l,q]) => `${q}D${l}`).join(' + ');

            if (dddDice.length > 0) {
                // Dispara SDK (animação 3D) + PHP REST (resultado) em paralelo
                const [, phpResult] = await Promise.all([
                    dddiceSDK
                        ? dddiceSDK.roll(dddDice).catch(e => console.warn('SDK roll:', e))
                        : Promise.resolve(),
                    fetch('?action=roll', {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body:    JSON.stringify({ dice: dddDice }),
                    }).then(r => r.json()),
                ]);

                if (phpResult.error) {
                    showToast('Erro da API dddice: ' + phpResult.error);
                    rolling = false;
                    btn.innerHTML = '<i class="fas fa-dice"></i> Rolar Dados';
                    atualizarBtnRolar();
                    return;
                }

                finalTotal  += phpResult.total;
                finalValues  = [...phpResult.values, ...jsValues];

                // Aguarda animação terminar (~1.2s) para exibir popup
                setTimeout(() => {
                    mostrarResultado(finalTotal, finalValues, finalLabel, true);
                    adicionarAoHistorico(finalTotal, finalLabel, true);
                    limparSelecao();
                }, 1200);

            } else {
                // Só dados JS (d2 / d100)
                mostrarResultado(finalTotal, finalValues, finalLabel, false);
                adicionarAoHistorico(finalTotal, finalLabel, false);
                limparSelecao();
            }

        } catch (err) {
            console.error(err);
            showToast('Erro na rolagem: ' + err.message);
        } finally {
            rolling = false;
            btn.innerHTML = '<i class="fas fa-dice"></i> Rolar Dados';
            atualizarBtnRolar();
        }
    }

    // ── POP-UP RESULTADO ──────────────────────────────────────
    function mostrarResultado(total, values, label, viaDddice) {
        const totalEl     = document.getElementById('result-total');
        const breakdownEl = document.getElementById('result-breakdown');
        const infoEl      = document.getElementById('result-dice-info');
        const labelEl     = document.getElementById('result-label');

        labelEl.textContent = label;

        totalEl.classList.remove('pop');
        void totalEl.offsetWidth;
        totalEl.textContent = total;
        totalEl.classList.add('pop');

        if (values.length > 1) {
            breakdownEl.innerHTML = values
                .map(v => `<span class="val">${v.value}</span>`)
                .join('<span class="op">+</span>')
                + `<span class="op">=</span><span class="val">${total}</span>`;
        } else {
            breakdownEl.innerHTML = '';
        }

        infoEl.textContent = viaDddice ? '🎲 Animado via dddice' : '🎲 Rolagem local';

        document.getElementById('result-popup').classList.add('show');
    }

    function fecharResultado() {
        document.getElementById('result-popup').classList.remove('show');
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharResultado(); });

    // ── HISTÓRICO ─────────────────────────────────────────────
    function adicionarAoHistorico(resultado, descricao, viaDddice) {
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        renderizarItemHistorico(resultado, descricao, time, viaDddice);

        // Persiste no localStorage
        const logs = JSON.parse(localStorage.getItem('table_historico_dados') || '[]');
        logs.push({ resultado, descricao, time, viaDddice });
        if (logs.length > 50) logs.shift();
        localStorage.setItem('table_historico_dados', JSON.stringify(logs));
    }

    function renderizarItemHistorico(resultado, descricao, time, viaDddice) {
        const logContainer = document.getElementById('historico-lista');
        const msgVazio     = document.getElementById('msg-vazio');
        if (msgVazio) msgVazio.remove();

        const novoItem = document.createElement('div');
        novoItem.className = 'log-item' + (viaDddice ? ' dddice-roll' : '');
        novoItem.innerHTML = `
            <div class="log-resultado">${resultado}</div>
            <div class="log-info">
                <p>${time} • ${descricao}${viaDddice ? '<span class="badge-dddice">dddice</span>' : ''}</p>
                <h4>Resultado</h4>
            </div>
        `;
        logContainer.prepend(novoItem);
    }

    function carregarHistoricoLocal() {
        const logs = JSON.parse(localStorage.getItem('table_historico_dados') || '[]');
        logs.forEach(log => renderizarItemHistorico(log.resultado, log.descricao, log.time, log.viaDddice));
    }

    function limparHistorico() {
        if (confirm('Deseja limpar todo o histórico de rolagens?')) {
            document.getElementById('historico-lista').innerHTML = `
                <div style="text-align: center; padding: 40px; color: #555;" id="msg-vazio">
                    Nenhuma rolagem ainda.
                </div>`;
            localStorage.removeItem('table_historico_dados');
        }
    }

    // ── HELPERS ───────────────────────────────────────────────
    function setStatus(state) {
        const dot  = document.getElementById('status-dot');
        dot.className = state;
        dot.title = state === 'ok' ? 'Conectado ao dddice' : state === 'loading' ? 'Conectando...' : 'Erro de conexão';
    }

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.classList.add('show');
        clearTimeout(t._timer);
        t._timer = setTimeout(() => t.classList.remove('show'), 4500);
    }
    </script>
    <script src="../js/nav-global.js" defer></script>
</body>
</html>

