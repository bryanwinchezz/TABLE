<?php
    session_start();

    // Se já estiver logado, redireciona direto pro perfil
    if (isset($_SESSION['usuario'])) {
        header('Location: index.php');
        exit;
    }

    require_once '../app/config/Database.php';

    $erro  = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome       = trim($_POST['nome']       ?? '');
        $email      = trim($_POST['email']      ?? '');
        $senha      = $_POST['senha']           ?? '';
        $nascimento = $_POST['nascimento']      ?? '';

        // Validações básicas
        if (empty($nome) || empty($email) || empty($senha) || empty($nascimento)) {
            $erro = "Todos os campos são obrigatórios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = "Digite um e-mail válido.";
        } elseif (strlen($senha) < 6) {
            $erro = "A senha deve ter pelo menos 6 caracteres.";
        } else {
            try {
                $conn = Database::getConexao();

                // Verifica se e-mail já existe
                $check = $conn->prepare("SELECT id_usuario FROM tb_usuario WHERE ds_email = :email LIMIT 1");
                $check->execute([':email' => $email]);

                if ($check->fetch()) {
                    $erro = "Este e-mail já está cadastrado. Tente fazer login.";
                } else {
                    // Criptografa a senha com bcrypt
                    $senhaCriptografada = password_hash($senha, PASSWORD_BCRYPT);

                    $stmt = $conn->prepare(
                        "INSERT INTO tb_usuario (nm_usuario, ds_email, ds_senha, tp_cargo, dt_nascimento)
                            VALUES (:nome, :email, :senha, 'jogador', :nascimento)"
                    );
                    $stmt->execute([
                        ':nome'  => $nome,
                        ':email' => $email,
                        ':senha' => $senhaCriptografada,
                        ':nascimento' => $nascimento,
                    ]);

                    header('Location: login.php');
                    exit;
                }
            } catch (PDOException $e) {
                $erro = "Erro interno. Tente novamente mais tarde.";
                // Para debug local, descomente a linha abaixo:
                // $erro = $e->getMessage();
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Cadastro</title>

    <link rel="stylesheet" href="../css/login-cadastro.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="pagina-autenticacao">
    <div class="autenticacao-wrapper">
        <div class="autenticacao-caixa">
            <a href="index.html">
                <img class="logotipo" src="../img/logo_horizontal.png" alt="TABLE">
            </a>

            <h2>Cadastro</h2>

            <form action="cadastro.php" method="post" id="form-cadastro">
                <div class="grupo-formulario">
                    <label for="nome">Usuário</label>
                    <div class="grupo-input">
                        <span class="input-icone"><i class="fas fa-user"></i></span>
                        <input type="text" id="nome" name="nome" required placeholder="Digite seu nome de usuário" maxlength="50">
                    </div>
                </div>

                <div class="grupo-formulario">
                    <label for="email">E-mail</label>
                    <div class="grupo-input">
                        <span class="input-icone"><i class="fas fa-envelope"></i></span>
                        <input type="email" id="email" name="email" required placeholder="Digite seu e-mail" maxlength="50" inputmode="email">
                    </div>
                </div>

                <div class="grupo-formulario">
                    <label for="senha">Senha</label>
                    <div class="grupo-input senha-wrapper">
                        <span class="input-icone"><i class="fas fa-lock"></i></span>
                        <input type="password" id="senha" name="senha" required placeholder="Digite sua senha" maxlength="50">
                        <i class="fas fa-eye alternar-senha"></i>
                    </div>
                    <span id="senha-cadastro-erro-msg" class="erro-validacao"></span>
                </div>

                <div class="grupo-formulario">
                    <label for="data">Data de Nascimento</label>
                    <div class="grupo-input">
                        <span class="input-icone"><i class="fas fa-calendar"></i></span>
                        <input type="text" name="nascimento" onfocus="(this.type='date')" onblur="if(this.value==''){this.type='text'}" required placeholder="Digite sua data de nascimento">
                    </div>
                </div>

                <button type="submit" id="cadastro-submit-btn" class="botao-gradiente botao-cheio">Cadastrar</button>

                <p class="autenticacao-link ">
                    Já tem uma conta?
                    <a href="login.php ">Login</a>
                </p>
            </form>
        </div>
    </div>

    <script src="../js/script.js" defer></script>
</body>

</html>