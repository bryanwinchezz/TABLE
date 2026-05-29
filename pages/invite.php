<?php
/**
 * invite.php
 * Página que o jogador acessa ao clicar no link de convite.
 *
 * Fluxo:
 *   1. Lê ?token=UUID da URL
 *   2. Valida na tb_convite_campanha:
 *        ds_token = ? AND tp_status = 'pendente' AND dt_expiracao > NOW()
 *   3. Redireciona para login se não estiver autenticado
 *   4. Exibe seletor de personagens disponíveis do usuário
 *   5. Ao confirmar: insere em tb_campanha_personagem
 *                    e tb_campanha_usuario (se ainda não estiver)
 *                    Marca o convite como 'aceito'
 */
session_start();
require_once __DIR__ . '/../app/config/database.php'; // expõe $pdo
$pdo = Database::getConexao();

$token = trim($_GET['token'] ?? '');

// ------------------------------------------------------------------
// 1. Valida token
// ------------------------------------------------------------------
if (!$token) {
    exibirErro('Link de convite inválido.', 'Nenhum token foi fornecido.');
}

$stmt = $pdo->prepare("
    SELECT cc.id_convite,
           cc.id_campanha,
           tc.nm_campanha
      FROM tb_convite_campanha cc
      JOIN tb_campanha         tc  ON tc.id_campanha = cc.id_campanha
     WHERE cc.ds_token    = ?
       AND cc.tp_status   = 'pendente'
       AND cc.dt_expiracao > NOW()
");
$stmt->execute([$token]);
$convite = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$convite) {
    exibirErro('Convite inválido ou expirado.',
               'Peça ao Mestre da campanha para gerar um novo link.');
}

$campaign_id = (int) $convite['id_campanha'];
$id_convite  = (int) $convite['id_convite'];

// ------------------------------------------------------------------
// 2. Usuário precisa estar logado
// ------------------------------------------------------------------
if (!isset($_SESSION['usuario'])) {
    $_SESSION['invite_token_pendente'] = $token;
    header('Location: login.php?next=' . urlencode('invite.php?token=' . $token));
    exit;
}

$usuario_id = (int) $_SESSION['usuario']['id'];

// ------------------------------------------------------------------
// 2.1 Verifica se o usuário tem o sistema da campanha
// ------------------------------------------------------------------
$stmtSis = $pdo->prepare("
    SELECT c.id_sistema, c.nm_campanha, s.id_usuario_criador 
      FROM tb_campanha c
      LEFT JOIN tb_sistema s ON c.id_sistema = s.id_sistema
     WHERE c.id_campanha = ?
");
$stmtSis->execute([$campaign_id]);
$campanhaInfo = $stmtSis->fetch();

$id_sistema_campanha = $campanhaInfo ? (int)$campanhaInfo['id_sistema'] : 0;
// Um sistema é oficial se id_usuario_criador for NULL
$is_sistema_oficial = $campanhaInfo && array_key_exists('id_usuario_criador', $campanhaInfo) && $campanhaInfo['id_usuario_criador'] === null;

$tem_sistema = false;
if ($id_sistema_campanha) {
    if ($is_sistema_oficial) {
        $tem_sistema = true;
    } else {
        $stmtCheck = $pdo->prepare("
            SELECT 1 FROM tb_sistema s
            LEFT JOIN tb_usuario u ON s.id_usuario_criador = u.id_usuario
            LEFT JOIN tb_usuario_sistema us ON s.id_sistema = us.id_sistema
            WHERE s.id_sistema = ? AND (
                s.id_usuario_criador = ? OR u.tp_cargo = 'admin' OR s.id_usuario_criador IS NULL OR us.id_usuario = ?
            )
        ");
        $stmtCheck->execute([$id_sistema_campanha, $usuario_id, $usuario_id]);
        $tem_sistema = (bool) $stmtCheck->fetch();
    }
}

// ------------------------------------------------------------------
// 2.2 Verifica se o usuário já é participante da campanha (como mestre ou jogador)
// ------------------------------------------------------------------
$stmtPart = $pdo->prepare("
    SELECT 1 FROM tb_campanha c
    LEFT JOIN tb_campanha_usuario cu ON c.id_campanha = cu.id_campanha AND cu.id_usuario = ?
    WHERE c.id_campanha = ? AND (c.id_usuario_mestre = ? OR cu.id_usuario IS NOT NULL)
");
$stmtPart->execute([$usuario_id, $campaign_id, $usuario_id]);
$ja_na_campanha = (bool) $stmtPart->fetch();

$feedback_sucesso = '';
$feedback_erro    = '';
$sucesso          = false;

if ($ja_na_campanha) {
    $sucesso = true;
    $feedback_sucesso = 'Você já é um participante ativo desta campanha!';
}

// ------------------------------------------------------------------
// 3. Processa seleção de personagem (POST) ou entrada automática
// ------------------------------------------------------------------
if (!$ja_na_campanha) {
    if (isset($_GET['auto_join']) && !isset($_POST['personagem_id'])) {
        $_POST['personagem_id'] = $_GET['auto_join'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['personagem_id'])) {
        $personagem_id = (int) $_POST['personagem_id'];

        // Confirma que o personagem pertence ao usuário logado, é ativo e condiz com o sistema da campanha
        $chk = $pdo->prepare("
            SELECT id_personagem FROM tb_personagem
             WHERE id_personagem = ? AND id_usuario = ? AND id_sistema = ? AND fl_ativo = 1
        ");
        $chk->execute([$personagem_id, $usuario_id, $id_sistema_campanha]);

        if ($chk->fetch()) {
            // Vincula personagem à campanha (Verifica antes para evitar duplicidade)
            $stmtCheck = $pdo->prepare("SELECT 1 FROM tb_campanha_personagem WHERE id_campanha = ? AND id_personagem = ?");
            $stmtCheck->execute([$campaign_id, $personagem_id]);
            
            if (!$stmtCheck->fetch()) {
                $pdo->prepare("INSERT INTO tb_campanha_personagem (id_campanha, id_personagem) VALUES (?, ?)")
                    ->execute([$campaign_id, $personagem_id]);
            }

            // Vincula usuário à campanha como jogador (ignora se já existe)
            $pdo->prepare("
                INSERT IGNORE INTO tb_campanha_usuario (id_campanha, id_usuario, tp_papel)
                VALUES (?, ?, 'jogador')
            ")->execute([$campaign_id, $usuario_id]);

            // Marca o convite como aceito
            $pdo->prepare("
                UPDATE tb_convite_campanha
                   SET tp_status = 'aceito'
                 WHERE id_convite = ?
            ")->execute([$id_convite]);

            $sucesso  = true;
            $feedback_sucesso = 'Personagem adicionado à campanha com sucesso!';
        } else {
            $feedback_erro = 'Personagem inválido. Selecione um que pertença à sua conta e seja do sistema correspondente.';
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entrar_sistema'])) {
        if ($is_sistema_oficial) {
            $feedback_erro = 'Este sistema é oficial e já está disponível para todos os usuários!';
            $tem_sistema = true;
        } elseif ($campanhaInfo && $id_sistema_campanha) {
            $stmtChkSys = $pdo->prepare("SELECT 1 FROM tb_usuario_sistema WHERE id_usuario = ? AND id_sistema = ?");
            $stmtChkSys->execute([$usuario_id, $id_sistema_campanha]);
            
            if (!$stmtChkSys->fetch()) {
                $pdo->prepare("INSERT INTO tb_usuario_sistema (id_usuario, id_sistema) VALUES (?, ?)")
                    ->execute([$usuario_id, $id_sistema_campanha]);
                $tem_sistema = true;
                $feedback_sucesso = 'Você obteve o sistema desta campanha com sucesso! Agora, selecione ou crie seu personagem abaixo.';
            } else {
                $feedback_erro = 'Você já possui o sistema desta campanha em sua conta!';
                $tem_sistema = true;
            }
        } else {
            $feedback_erro = 'Esta campanha não possui um sistema válido vinculado.';
        }
    }
}

// ------------------------------------------------------------------
// 4. Busca personagens disponíveis do usuário (ainda não na campanha)
// ------------------------------------------------------------------
$personagens_disponiveis = [];
if (!$ja_na_campanha) {
    $stmt = $pdo->prepare("
        SELECT p.id_personagem, p.nm_personagem, p.ds_foto, s.nm_sistema
          FROM tb_personagem p
          LEFT JOIN tb_sistema s ON p.id_sistema = s.id_sistema
         WHERE p.id_usuario  = ?
           AND p.fl_ativo    = 1
           AND p.id_sistema  = ?
           AND p.id_personagem NOT IN (
                   SELECT cp.id_personagem
                     FROM tb_campanha_personagem cp
                    WHERE cp.id_campanha = ?
               )
         ORDER BY p.nm_personagem ASC
    ");
    $stmt->execute([$usuario_id, $id_sistema_campanha, $campaign_id]);
    $personagens_disponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ------------------------------------------------------------------
// HELPER — exibe tela de erro genérica e para execução
// ------------------------------------------------------------------
function exibirErro(string $titulo, string $detalhe): never {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>TABLE | Convite Inválido</title>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;800&display=swap" rel="stylesheet">
        <style>
            body { background:#1a1428; color:#fff; font-family:'Montserrat',sans-serif;
                   display:flex; align-items:center; justify-content:center;
                   min-height:100vh; margin:0; }
            .caixa { text-align:center; padding:40px; max-width:400px;
                     background:rgba(255,255,255,.05); border-radius:20px;
                     border:1px solid rgba(255,255,255,.1); }
            h1  { font-size:1.8rem; margin-bottom:12px; }
            p   { color:#aaa; margin-bottom:28px; }
            .btn { background:linear-gradient(135deg,#7b4ff7,#5b2be0); color:#fff;
                   padding:12px 30px; border-radius:12px; text-decoration:none;
                   font-weight:700; }
        </style>
    </head>
    <body>
        <div class="caixa">
            <h1>⚠️ <?= htmlspecialchars($titulo) ?></h1>
            <p><?= htmlspecialchars($detalhe) ?></p>
            <a href="index.php" class="btn">Ir para o Início</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Convite para Campanha</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }

        body {
            background: linear-gradient(135deg, #1a1428 0%, #311c61 100%);
            min-height: 100vh;
            font-family: 'Montserrat', sans-serif;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 24px;
            padding: 50px 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            backdrop-filter: blur(10px);
            box-shadow: 0 30px 60px rgba(0,0,0,.5);
        }

        .icone { font-size:3rem; margin-bottom:20px; color:#9d7aff; }

        .card h1 { font-size:1.8rem; font-weight:800; margin-bottom:8px; }

        .subtitulo { color:#aaa; font-size:.95rem; margin-bottom:35px; }
        .subtitulo strong { color:#9d7aff; }

        /* Seletor */
        .label-campo {
            display: block;
            font-size: .8rem;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            text-align: left;
        }

        select.sel-personagem {
            width: 100%;
            background: rgba(0,0,0,.3);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 12px;
            padding: 14px 20px;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
            margin-bottom: 20px;
            outline: none;
            cursor: pointer;
            transition: border-color .3s;
            appearance: none;
        }
        select.sel-personagem:focus { border-color:#9d7aff; }
        select.sel-personagem option { background:#1a1428; }

        /* Botões */
        .btn-entrar {
            width: 100%;
            background: linear-gradient(135deg,#7b4ff7,#5b2be0);
            color: #fff;
            border: none;
            padding: 16px;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .3s;
            letter-spacing: .5px;
            box-shadow: 0 8px 25px rgba(91,43,224,.4);
        }
        .btn-entrar:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(91,43,224,.6);
        }
        .btn-entrar:disabled { opacity:.5; cursor:not-allowed; transform:none; }

        .btn-ir {
            display: block;
            width: 100%;
            padding: 16px;
            background: #00c864;
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: background .3s;
        }
        .btn-ir:hover { background:#00a854; }

        /* Feedback */
        .feedback {
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: .9rem;
        }
        .feedback.ok  { background:rgba(0,200,100,.15); border:1px solid #00c864; color:#00c864; }
        .feedback.err { background:rgba(255,77,77,.15);  border:1px solid #ff4d4d; color:#ff4d4d; }

        .sem-personagem { color:#aaa; font-size:.9rem; margin-bottom:20px; }
        .link-criar { color:#9d7aff; font-weight:700; text-decoration:none; }
        .link-criar:hover { text-decoration:underline; }

        hr { border:none; border-top:1px solid rgba(255,255,255,.1); margin:25px 0; }
        .rodape { color:#666; font-size:.8rem; }

        /* Preview Avatar Style */
        .preview-avatar-container {
            width: 140px;
            margin: 0 auto 25px auto;
            position: relative;
            display: none; /* Escondido até selecionar */
            animation: fadeInScale 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-align: center;
        }
        .preview-avatar-container .img-wrapper {
            width: 120px;
            height: 120px;
            margin: 0 auto 15px auto;
            position: relative;
        }
        .preview-avatar-container .img-wrapper img {
            width: 100%;
            height: 100%;
            border-radius: 24px;
            object-fit: cover;
            border: 3px solid #9d7aff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }
        .preview-avatar-container .img-wrapper::after {
            content: '';
            position: absolute;
            inset: -8px;
            border: 2px dashed rgba(157, 122, 255, 0.3);
            border-radius: 30px;
            z-index: -1;
            animation: rotate 10s linear infinite;
        }
        .preview-info-nomes h3 { font-size: 1.1rem; font-weight: 800; color: #fff; margin-bottom: 2px; }
        .preview-info-nomes p { font-size: 0.85rem; color: #9d7aff; font-weight: 600; margin: 0; }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.8) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsividade para Mobile */
        @media (max-width: 500px) {
            .card {
                padding: 30px 20px;
                border-radius: 16px;
            }
            .icone { font-size: 2.2rem; margin-bottom: 12px; }
            .card h1 { font-size: 1.4rem; }
            .subtitulo { font-size: 0.85rem; margin-bottom: 25px; }
            .btn-entrar, .btn-ir { padding: 12px; font-size: 0.9rem; }
        }
    </style>
</head>
<body>
<div class="card">

    <div class="icone"><i class="fas fa-scroll"></i></div>

    <h1><?= htmlspecialchars($convite['nm_campanha']) ?></h1>
    <p class="subtitulo">
        Você foi convidado para participar desta campanha.<br>
        Olá, <strong><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></strong>!
    </p>

    <?php if ($sucesso): ?>
        <!-- ── Estado: sucesso ── -->
        <div class="feedback ok">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($feedback_sucesso) ?>
        </div>
        <a href="criar-campanha.php?id=<?= $campaign_id ?>" class="btn-ir">
            <i class="fas fa-arrow-right"></i> Ir para a Campanha
        </a>

    <?php elseif (empty($personagens_disponiveis)): ?>
        <!-- ── Estado: sem personagens ── -->
        <?php if ($feedback_sucesso): ?>
            <div class="feedback ok" style="margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($feedback_sucesso) ?>
            </div>
        <?php endif; ?>
        <?php if ($feedback_erro): ?>
            <div class="feedback err" style="margin-bottom: 20px;">
                <i class="fas fa-times-circle"></i> <?= htmlspecialchars($feedback_erro) ?>
            </div>
        <?php endif; ?>
        <p class="sem-personagem">
            Você não tem personagens disponíveis para adicionar.<br>
            <a href="criar-personagem.php?invite_token=<?= htmlspecialchars($token) ?>&sys=<?= $id_sistema_campanha ?>" class="link-criar">Criar um novo personagem</a>
        </p>

    <?php else: ?>
        <!-- ── Estado: seleção de personagem ── -->
        <?php if ($feedback_sucesso): ?>
            <div class="feedback ok" style="margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($feedback_sucesso) ?>
            </div>
        <?php endif; ?>
        <?php if ($feedback_erro): ?>
            <div class="feedback err" style="margin-bottom: 20px;">
                <i class="fas fa-times-circle"></i> <?= htmlspecialchars($feedback_erro) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div id="preview-personagem" class="preview-avatar-container">
                <div class="img-wrapper">
                    <img src="" alt="Avatar" id="img-preview">
                </div>
                <div class="preview-info-nomes">
                    <h3 id="preview-nome">Nome</h3>
                    <p id="preview-sistema">Sistema</p>
                </div>
            </div>

            <label class="label-campo" for="sel-personagem">
                Escolha seu personagem
            </label>

            <select name="personagem_id" id="sel-personagem"
                    class="sel-personagem" required>
                <option value="" disabled selected>-- Selecione --</option>
                <?php foreach ($personagens_disponiveis as $p): 
                    $fotoUrl = !empty($p['ds_foto']) ? $p['ds_foto'] : '../img/uploads/perfil/avatar.png';
                ?>
                    <option value="<?= (int) $p['id_personagem'] ?>" 
                            data-foto="<?= htmlspecialchars($fotoUrl) ?>"
                            data-nome="<?= htmlspecialchars($p['nm_personagem']) ?>"
                            data-sistema="<?= htmlspecialchars($p['nm_sistema'] ?? 'Sistema Desconhecido') ?>">
                        <?= htmlspecialchars($p['nm_personagem']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-entrar">
                <i class="fas fa-user-plus"></i> Entrar na Campanha
            </button>
        </form>
    <?php endif; ?>

    <?php if (!$ja_na_campanha): ?>
        <?php if (!$tem_sistema): ?>
            <hr>
            <p style="font-size: 0.9rem; color: #ccc; margin-bottom: 10px;">
                O sistema desta campanha não está na sua conta. Você precisa dele para criar a ficha!
            </p>
            <form method="POST" action="" style="margin-top: 15px;">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="entrar_sistema" value="1">
                <button type="submit" class="btn-entrar" style="background: linear-gradient(135deg, #00c864, #00a854); box-shadow: 0 8px 25px rgba(0,200,100,.4);">
                    <i class="fas fa-magic"></i> Adquirir Sistema da Campanha
                </button>
            </form>
        <?php else: ?>
            <hr>
            <a href="criar-personagem.php?invite_token=<?= htmlspecialchars($token) ?>&sys=<?= $id_sistema_campanha ?>" class="btn-ir" style="background: linear-gradient(135deg, #5b21b6, #8b5cf6); border: none; padding: 15px; border-radius: 12px; width: 100%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; text-decoration: none; margin-top: 10px; font-size: 1rem;">
                <i class="fas fa-user-plus" style="margin-right: 8px;"></i> Criar Personagem Novo
            </a>
        <?php endif; ?>
    <?php endif; ?>

    <hr>
    <p class="rodape">
        <i class="fas fa-lock" style="font-size:.7rem;"></i>
        Apenas usuários registrados podem entrar em campanhas.
    </p>

    <a href="perfil.php" style="display: inline-flex; align-items: center; justify-content: center; margin-top: 25px; color: #aaa; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: color 0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#aaa'">
        <i class="fas fa-arrow-left" style="margin-right: 6px;"></i> Voltar para o Perfil
    </a>

</div>

<script>
document.getElementById('sel-personagem').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const fotoUrl = selectedOption.getAttribute('data-foto');
    const nome = selectedOption.getAttribute('data-nome');
    const sistema = selectedOption.getAttribute('data-sistema');
    
    const previewContainer = document.getElementById('preview-personagem');
    const previewImg = document.getElementById('img-preview');
    const previewNome = document.getElementById('preview-nome');
    const previewSistema = document.getElementById('preview-sistema');

    if (fotoUrl) {
        previewImg.src = fotoUrl;
        previewNome.textContent = nome;
        previewSistema.textContent = sistema;
        previewContainer.style.display = 'block';
    } else {
        previewContainer.style.display = 'none';
    }
});
</script>
</body>
</html>

