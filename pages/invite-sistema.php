<?php
session_start();
require_once __DIR__ . '/../app/config/database.php';
$pdo = Database::getConexao();

$token = trim($_GET['token'] ?? '');

if (!$token) {
    exibirErro('Link Inválido', 'Nenhum token de compartilhamento foi fornecido.');
}

// Valida o token
$stmt = $pdo->prepare("
    SELECT cs.*, s.nm_sistema, s.ds_descricao, s.ds_imagem, u.nm_usuario as criador_nome
    FROM tb_convite_sistema cs
    JOIN tb_sistema s ON cs.id_sistema = s.id_sistema
    LEFT JOIN tb_usuario u ON s.id_usuario_criador = u.id_usuario
    WHERE cs.ds_token = ? AND cs.tp_status = 'pendente' AND (cs.dt_expiracao IS NULL OR cs.dt_expiracao > NOW())
");
$stmt->execute([$token]);
$convite = $stmt->fetch();

if (!$convite) {
    exibirErro('Link Expirado ou Inválido', 'Este link de compartilhamento não é mais válido ou já expirou.');
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$usuario_id = (int)$_SESSION['usuario']['id'];

function exibirErro($titulo, $mensagem) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Erro | TABLE</title>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;800&display=swap" rel="stylesheet">
        <style>
            body { background:#1a1428; color:#fff; font-family:'Montserrat',sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
            .caixa { text-align:center; padding:40px; max-width:450px; background:rgba(255,255,255,.05); border-radius:24px; border:1px solid rgba(255,255,255,.1); backdrop-filter: blur(10px); }
            h1 { font-size:1.8rem; margin-bottom:15px; color: #ff4d4d; }
            p { color:#aaa; margin-bottom:30px; line-height: 1.6; }
            .btn { background:linear-gradient(135deg,#7b4ff7,#5b2be0); color:#fff; padding:12px 30px; border-radius:12px; text-decoration:none; font-weight:700; transition: 0.3s; display: inline-block; }
            .btn:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        </style>
    </head>
    <body>
        <div class="caixa">
            <h1>⚠️ <?= htmlspecialchars($titulo) ?></h1>
            <p><?= htmlspecialchars($mensagem) ?></p>
            <a href="index.php" class="btn">Voltar ao Início</a>
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
    <title>Importar Sistema | TABLE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/table-modal.css">
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">
    <script src="../js/table-modal.js"></script>
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
            border-radius: 30px;
            padding: 50px;
            max-width: 550px;
            width: 100%;
            text-align: center;
            backdrop-filter: blur(15px);
            box-shadow: 0 30px 60px rgba(0,0,0,.5);
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .icone-grande { font-size:4rem; margin-bottom:25px; color:#27ae60; filter: drop-shadow(0 0 15px rgba(39, 174, 96, 0.4)); }
        h1 { font-size:2rem; font-weight:900; margin-bottom:10px; text-transform: uppercase; letter-spacing: -1px; }
        .criador { color:#9d7aff; font-weight:700; font-size:0.9rem; margin-bottom:25px; display: block; }
        
        .sistema-preview {
            background: rgba(0,0,0,0.2);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 35px;
            text-align: left;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .sistema-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 15px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .descricao {
            color: #ccc;
            font-size: 0.95rem;
            line-height: 1.6;
            max-height: 150px;
            overflow-y: auto;
            padding-right: 10px;
        }
        .descricao::-webkit-scrollbar { width: 5px; }
        .descricao::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

        .btn-importar {
            width: 100%;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: #fff;
            border: none;
            padding: 20px;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 25px rgba(39, 174, 96, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .btn-importar:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 35px rgba(39, 174, 96, 0.5);
            filter: brightness(1.1);
        }
        .btn-importar:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

        .rodape { margin-top: 30px; color: #666; font-size: 0.8rem; }
        .loading-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); backdrop-filter: blur(10px);
            display: none; flex-direction: column; align-items: center; justify-content: center;
            z-index: 100;
        }
        .spinner { width: 60px; height: 60px; border: 5px solid rgba(255,255,255,0.1); border-top-color: #27ae60; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="card">
    <div class="icone-grande"><i class="fas fa-file-import"></i></div>
    <h1>Importar Sistema</h1>
    <span class="criador">Criado por: <?= htmlspecialchars($convite['criador_nome'] ?? 'TABLE') ?></span>

    <div class="sistema-preview">
        <?php if ($convite['ds_imagem']): ?>
            <img src="<?= htmlspecialchars($convite['ds_imagem']) ?>" class="sistema-img" alt="Capa do Sistema">
        <?php endif; ?>
        <h3 style="margin-bottom: 10px; color: #fff;"><?= htmlspecialchars($convite['nm_sistema']) ?></h3>
        <div class="descricao">
            <?= nl2br(htmlspecialchars($convite['ds_descricao'] ?? 'Sem descrição disponível.')) ?>
        </div>
    </div>

    <button id="btn-confirmar" class="btn-importar" onclick="importarSistema()">
        <i class="fas fa-magic"></i> IMPORTAR PARA MINHA CONTA
    </button>

    <div class="rodape">
        <p><i class="fas fa-info-circle"></i> Uma cópia completa deste sistema será criada no seu perfil.</p>
    </div>
</div>

<div class="loading-overlay" id="loading">
    <div class="spinner"></div>
    <h2 id="loading-text">Clonando Sistema...</h2>
    <p style="color: #666; margin-top: 10px;">Isso pode levar alguns segundos.</p>
</div>

<script>
async function importarSistema() {
    const btn = document.getElementById('btn-confirmar');
    const loading = document.getElementById('loading');
    const token = '<?= $token ?>';

    btn.disabled = true;
    loading.style.display = 'flex';

    try {
        const formData = new FormData();
        formData.append('token', token);

        const res = await fetch('../app/ajax/importar-sistema.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.sucesso) {
            document.getElementById('loading-text').textContent = 'Sucesso! Redirecionando...';
            setTimeout(() => {
                window.location.href = 'exibir-sistema.php?id=' + data.id_sistema;
            }, 1500);
        } else {
            loading.style.display = 'none';
            btn.disabled = false;
            await TableModal.alert('Erro ao importar: ' + data.mensagem, 'Falha na Importação', 'error');
        }
    } catch (e) {
        console.error(e);
        loading.style.display = 'none';
        btn.disabled = false;
        await TableModal.alert('Erro de conexão ao importar sistema.', 'Erro de Conexão', 'error');
    }
}
</script>

</body>
</html>

