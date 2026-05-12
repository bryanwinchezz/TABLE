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
    header('Location: login.php?next=' . urlencode('/TABLE-main/pages/invite.php?token=' . $token));
    exit;
}

$usuario_id = (int) $_SESSION['usuario']['id'];

// ------------------------------------------------------------------
// 3. Processa seleção de personagem (POST)
// ------------------------------------------------------------------
$feedback = '';
$sucesso  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['personagem_id'])) {
    $personagem_id = (int) $_POST['personagem_id'];

    // Confirma que o personagem pertence ao usuário logado
    $chk = $pdo->prepare("
        SELECT id_personagem FROM tb_personagem
         WHERE id_personagem = ? AND id_usuario = ? AND fl_ativo = 1
    ");
    $chk->execute([$personagem_id, $usuario_id]);

    if ($chk->fetch()) {
        // Vincula personagem à campanha (ignora se já existe)
        $pdo->prepare("
            INSERT IGNORE INTO tb_campanha_personagem (id_campanha, id_personagem)
            VALUES (?, ?)
        ")->execute([$campaign_id, $personagem_id]);

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
        $feedback = 'Personagem adicionado à campanha com sucesso!';
    } else {
        $feedback = 'Personagem inválido. Selecione um que pertença à sua conta.';
    }
}

// ------------------------------------------------------------------
// 4. Busca personagens disponíveis do usuário (ainda não na campanha)
// ------------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT p.id_personagem, p.nm_personagem
      FROM tb_personagem p
     WHERE p.id_usuario  = ?
       AND p.fl_ativo    = 1
       AND p.id_personagem NOT IN (
               SELECT cp.id_personagem
                 FROM tb_campanha_personagem cp
                WHERE cp.id_campanha = ?
           )
     ORDER BY p.nm_personagem ASC
");
$stmt->execute([$usuario_id, $campaign_id]);
$personagens_disponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ------------------------------------------------------------------
// HELPER — exibe tela de erro genérica e para execução
// ------------------------------------------------------------------
function exibirErro(string $titulo, string $detalhe): never {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Convite Inválido | TABLE</title>
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
    <title>Convite para Campanha | TABLE</title>
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
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($feedback) ?>
        </div>
        <a href="criar-campanha.php?id=<?= $campaign_id ?>" class="btn-ir">
            <i class="fas fa-arrow-right"></i> Ir para a Campanha
        </a>

    <?php elseif (empty($personagens_disponiveis)): ?>
        <!-- ── Estado: sem personagens ── -->
        <?php if ($feedback): ?>
            <div class="feedback err">
                <i class="fas fa-times-circle"></i> <?= htmlspecialchars($feedback) ?>
            </div>
        <?php endif; ?>
        <p class="sem-personagem">
            Você não tem personagens disponíveis para adicionar.<br>
            <a href="criar-personagem.php" class="link-criar">Criar um novo personagem</a>
        </p>

    <?php else: ?>
        <!-- ── Estado: seleção de personagem ── -->
        <?php if ($feedback): ?>
            <div class="feedback err">
                <i class="fas fa-times-circle"></i> <?= htmlspecialchars($feedback) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <label class="label-campo" for="sel-personagem">
                Escolha seu personagem
            </label>

            <select name="personagem_id" id="sel-personagem"
                    class="sel-personagem" required>
                <option value="" disabled selected>-- Selecione --</option>
                <?php foreach ($personagens_disponiveis as $p): ?>
                    <option value="<?= (int) $p['id_personagem'] ?>">
                        <?= htmlspecialchars($p['nm_personagem']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-entrar">
                <i class="fas fa-user-plus"></i> Entrar na Campanha
            </button>
        </form>
    <?php endif; ?>

    <hr>
    <p class="rodape">
        <i class="fas fa-lock" style="font-size:.7rem;"></i>
        Apenas usuários registrados podem entrar em campanhas.
    </p>

</div>
</body>
</html>
