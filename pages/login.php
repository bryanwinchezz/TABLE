<?php
session_start();

// Se já estiver logado, redireciona direto pro perfil
if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

require_once '../app/config/database.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['nome-email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($login) || empty($senha)) {
        $erro = "Preencha todos os campos.";
    } else {
        try {
            $conn = Database::getConexao();

            // Busca por e-mail OU por nome de usuário
            $stmt = $conn->prepare(
                "SELECT id_usuario, nm_usuario, ds_email, ds_senha, tp_cargo, ds_foto, fl_ativo
                        FROM tb_usuario
                            WHERE ds_email = :email OR nm_usuario = :nome
                                LIMIT 1"
            );
            $stmt->execute([
                ':email' => $login,
                ':nome' => $login,
            ]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                $erro = "Usuário ou e-mail não encontrado.";
            } elseif (!password_verify($senha, $usuario['ds_senha'])) {
                $erro = "Senha incorreta.";
            } else {
                // Se a conta estava desativada, reativa automaticamente
                if ((int)$usuario['fl_ativo'] === 0) {
                    $stmtReact = $conn->prepare("UPDATE tb_usuario SET fl_ativo = 1, dt_atualizacao = NOW() WHERE id_usuario = ?");
                    $stmtReact->execute([$usuario['id_usuario']]);
                    $_SESSION['alerta_boas_vindas'] = "Sua conta foi reativada com sucesso! Bem-vindo de volta.";
                }

                // Login OK — salva sessão
                $_SESSION['usuario'] = [
                    'id' => $usuario['id_usuario'],
                    'nome' => $usuario['nm_usuario'],
                    'email' => $usuario['ds_email'],
                    'cargo' => $usuario['tp_cargo'],
                    'foto' => $usuario['ds_foto'],
                ];

                // Redireciona para o convite pendente se existir
                if (isset($_SESSION['invite_token_pendente'])) {
                    $tokenPend = $_SESSION['invite_token_pendente'];
                    unset($_SESSION['invite_token_pendente']);
                    header('Location: invite.php?token=' . urlencode($tokenPend));
                    exit;
                }

                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $erro = "Erro interno. Tente novamente mais tarde.";
            // Para debug local, descomente a linha abaixo:
            $erro = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TABLE | Login</title>

    <link rel="stylesheet" href="../css/login-cadastro.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="shortcut icon" href="../img/logo_branco1.png" type="image/x-icon">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="pagina-autenticacao">
    <div class="autenticacao-wrapper">
        <div class="autenticacao-caixa">
            <a href="index.php">
                <img class="logotipo" src="../img/logo_horizontal1.png" alt="TABLE">
            </a>
            <h2>Login</h2>
            <?php if (!empty($erro)): ?>
                <div style="background:#ffe0e0; color:#c0392b; border:1px solid #e74c3c;
                padding:10px 15px; border-radius:8px; margin-bottom:15px;
                font-size:0.9rem; font-weight:600; text-align:left;">

                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post" id="form-login" onsubmit="return true">
                <div class="grupo-formulario">
                    <label for="nome">Usuário ou E-mail</label>
                    <div class="grupo-input">
                        <span class="input-icone"><i class="fas fa-user"></i></span>
                        <input type="text" id="nome" name="nome-email" required
                            placeholder="Digite seu usuário ou e-mail" maxlength="50">
                    </div>
                </div>

                <div class="grupo-formulario">
                    <label for="senha-login">Senha</label>
                    <div class="grupo-input senha-wrapper">
                        <span class="input-icone"><i class="fas fa-lock"></i></span>
                        <input type="password" id="senha-login" name="senha" required placeholder="Digite sua senha"
                            maxlength="50">
                        <i class="fas fa-eye alternar-senha"></i>
                    </div>
                    <span id="senha-login-erro-msg" class="erro-validacao"></span>
                </div>

                <button type="submit" id="login-submit-btn" class="botao-gradiente botao-cheio">Login</button>

                <p class="autenticacao-link">Não tem uma conta? <a href="cadastro.php">Cadastre-se</a></p>
            </form>
        </div>
    </div>

    <script src="../js/script.js" defer></script>
</body>

</html>

