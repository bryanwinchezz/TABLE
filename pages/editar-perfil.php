<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once '../app/config/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_conta'])) {
    
    $senha_digitada = $_POST['senha_confirmacao'] ?? '';

    try {
        $pdo = Database::getConexao();

        $stmt = $pdo->prepare("SELECT ds_senha FROM tb_usuario WHERE id_usuario = ? AND fl_ativo = 1");
        $stmt->execute([$_SESSION['usuario']['id']]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha_digitada, $usuario['ds_senha'])) {
            // Senha correta → mostra o segundo modal de confirmação
            header("Location: editar-perfil.php?confirmar_exclusao=1");
            exit;
        } else {
            // Senha incorreta
            header("Location: editar-perfil.php?erro=senha_incorreta");
            exit;
        }
    } catch (Exception $e) {
        header("Location: editar-perfil.php?erro=erro_sistema");
        exit;
    }
}
// ====================== CONFIRMAÇÃO FINAL ======================
if (isset($_GET['confirmar_exclusao'])) {
    // Aqui vamos usar JavaScript para mostrar o segundo popup
}

$erro = '';
$conn = Database::getConexao();

// ======================
// PROCESSAMENTO DO FORMULÁRIO
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novo_nome  = trim($_POST['nome'] ?? '');
    $novo_email = trim($_POST['email'] ?? '');

    if (empty($novo_nome) || empty($novo_email)) {
        $erro = "Nome e e-mail são obrigatórios.";
    } else {
        try {
            $stmt = $conn->prepare("
                UPDATE tb_usuario 
                SET nm_usuario = :nome, 
                    ds_email = :email,
                    dt_atualizacao = CURRENT_TIMESTAMP
                WHERE id_usuario = :id
            ");

            $stmt->execute([
                ':nome' => $novo_nome,
                ':email' => $novo_email,
                ':id'   => $_SESSION['usuario']['id']
            ]);

            // Atualiza a sessão
            $_SESSION['usuario']['nome']  = $novo_nome;
            $_SESSION['usuario']['email'] = $novo_email;

        } catch (PDOException $e) {
            $erro = "Erro ao atualizar os dados.";
        }
    }
}

// ======================
// BUSCA DOS DADOS DO USUÁRIO
// ======================
try {
    $stmt = $conn->prepare("
        SELECT id_usuario, nm_usuario, ds_email, dt_nascimento, dt_cadastro, tp_cargo, ds_foto 
        FROM tb_usuario 
        WHERE id_usuario = :id 
        LIMIT 1
    ");

    $stmt->execute([':id' => $_SESSION['usuario']['id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        header('Location: perfil.php');
        exit;
    }

} catch (PDOException $e) {
    $erro = "Erro ao carregar seus dados.";
    $usuario = $_SESSION['usuario']; // fallback
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Editar Perfil</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/editar-perfil.css">
</head>
<body class="pagina-edicao">

    <header>
        <div class="logotipo">
            <a href="index.php"><img src="../img/logo_horizontal.png" alt="Logo TABLE"></a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="#">Como Jogar</a></li>
                <li><a href="#">Personagens</a></li>
                <li><a href="#">Mundos</a></li>
                <li><a href="#">Dados</a></li>
                <li><a href="#">Sobre Nós</a></li>
            </ul>
        </nav>

        <div class="usuario-logado-nav" onclick="window.location.href='perfil.php'" title="Ir para o Perfil">
            <img src="../img/foto-ficha.jpg" alt="Avatar" class="avatar-nav">
            <span class="nome-nav"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
            <i class="far fa-star icone-nav"></i>
        </div>
    </header>

    <main class="container-principal-edicao">
        <div class="cartao-edicao">

            <a href="perfil.php" class="link-voltar">
                <i class="fas fa-arrow-left"></i> Voltar ao Perfil
            </a>

            <div class="cabecalho-edicao">
                <div class="foto-container">
                    <img src="<?= htmlspecialchars($usuario['ds_foto'] ?? '../img/foto-ficha.jpg') ?>" 
                         alt="Foto de perfil" class="foto-perfil">
                    <div class="icone-camera">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>

                <div class="info-usuario">
                    <h2><?= htmlspecialchars($usuario['nm_usuario']) ?></h2>
                    <p><?= htmlspecialchars($usuario['ds_email']) ?></p>
                    <p class="membro-desde">
                        Membro desde: <?= $usuario['dt_cadastro'] ? date('d/m/Y', strtotime($usuario['dt_cadastro'])) : 'Não informado' ?>
                    </p>
                </div>
            </div>

            <hr class="divisor">

            <section>
                <h3 class="titulo-roxo">Meus Dados</h3>
                <form class="formulario-edicao" method="POST">
                    <div class="campo-edicao">
                        <label for="input-nome">Nome de Usuário:</label>
                        <input type="text" id="input-nome" name="nome" 
                               value="<?= htmlspecialchars($usuario['nm_usuario']) ?>" required>
                    </div>

                    <div class="campo-edicao">
                        <label for="input-email">E-mail:</label>
                        <input type="email" id="input-email" name="email" 
                               value="<?= htmlspecialchars($usuario['ds_email']) ?>" required>
                    </div>

                    <div class="campo-edicao">
                        <label for="input-data">Data de nascimento:</label>
                        <input type="text" id="input-data" value="10/09/2007" disabled>
                    </p>
                    </div>

                    <div class="area-botao-centro">
                        <button type="submit" class="botao-roxo">Salvar alterações</button>
                    </div>
                </form>
            </section>

            <hr class="divisor">

            <section>
                <h3 class="titulo-roxo">Ser Mestre</h3>
                <p class="texto-descricao">
                    Torne-se um verdadeiro mestre e desbloqueie a habilidade de criar seus próprios sistemas de RPG. Construa mundos, regras e experiências únicas, compartilhe tudo com a comunidade para que outros vivam as aventuras que você imaginar.
                </p>
                <div class="area-botao-centro">
                    <button type="button" id="btn-mudar-cargo" class="botao-roxo">
                        <i class="fas fa-book"></i> <span id="texto-botao-cargo">Seja mestre</span>
                    </button>
                </div>
            </section>

            <hr class="divisor">

            <div class="acoes-conta">
                <a style="text-decoration: none;" href="../app/controllers/logout.php">
                    <button type="button" id="btn-sair-conta" class="botao-cinza">
                    <i class="fas fa-sign-out-alt"></i> Sair da Conta
                </button>
                </a>
                <button type="button" id="btn-excluir-conta" class="botao-vermelho">
                    <i class="fas fa-trash-alt"></i> Excluir Conta
                </button>
            </div>

        </div>
    </main>

    <footer class="rodape-principal">
        <!-- Seu footer normal aqui -->
        <div class="rodape-conteudo">
            <div class="rodape-logo-area">
                <div class="rodape-marca">
                    <img src="../img/logo_branco.png" alt="Logo TABLE">
                    <span>TABLE</span>
                </div>
                <p>Acompanhe uma experiência imersiva nos mundos de RPG. Aprenda e jogue com seus amigos!</p>
            </div>
            <!-- ... resto do footer ... -->
        </div>
    </footer>

    <script src="../js/nav-global.js" defer></script>
</body>
 <div id="modal-senha" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; justify-content:center; align-items:center;">
        <div style="background:#fff; padding:30px; border-radius:12px; width:90%; max-width:420px; color:#333; text-align:center;">
            <h2 style="color:#dc3545;">⚠️ Excluir Perfil</h2>
            <p style="margin:20px 0 25px;">Digite sua senha para continuar.</p>

            <form method="POST">
                <input type="hidden" name="excluir_conta" value="1">

                <input type="password" name="senha_confirmacao" id="senha-input" 
                       placeholder="Sua senha atual" required 
                       style="width:100%; padding:12px; margin-bottom:20px; border:1px solid #ccc; border-radius:6px;">

                <?php if (isset($_GET['erro']) && $_GET['erro'] === 'senha_incorreta'): ?>
                    <p style="color:red; font-weight:600;">Senha incorreta! Tente novamente.</p> 
                <?php endif; ?>

                    <br>

                <div style="display:flex; gap:12px; justify-content:center;">
                    <button type="button" onclick="fecharModalSenha()" 
                            style="padding:10px 25px; background:#6c757d; color:white; border:none; border-radius:6px; cursor:pointer;">
                        Cancelar
                    </button>
                    <button type="submit" 
                            style="padding:10px 25px; background:#dc3545; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:700;">
                        Continuar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: CONFIRMAÇÃO FINAL -->
    <div id="modal-confirmacao" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; justify-content:center; align-items:center;">
        <div style="background:#fff; padding:30px; border-radius:12px; width:90%; max-width:420px; color:#333; text-align:center;">
            <h2 style="color:#dc3545;">Tem certeza?</h2>
            <p style="margin:20px 0 30px; font-size:1.05rem;">
                Esta ação é irreversível.<br>
                Sua conta será desativada permanentemente.
            </p>

            <div style="display:flex; gap:15px; justify-content:center;">
                <button onclick="fecharModalConfirmacao()" 
                        style="padding:12px 30px; background:#6c757d; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600;">
                    Não, cancelar
                </button>
                <button onclick="confirmarExclusao()" 
                        style="padding:12px 30px; background:#dc3545; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:700;">
                    Sim, excluir conta
                </button>
            </div>
        </div>
    </div>

    <script>
    const btnExcluir = document.getElementById('btn-excluir-conta');

    btnExcluir.addEventListener('click', function() {
        document.getElementById('modal-senha').style.display = 'flex';
        document.getElementById('senha-input').value = '';
    });

    function fecharModalSenha() {
        document.getElementById('modal-senha').style.display = 'none';
    }

    function fecharModalConfirmacao() {
        document.getElementById('modal-confirmacao').style.display = 'none';
    }

    // Reabre o modal de senha se a senha estiver errada
    <?php if (isset($_GET['erro']) && $_GET['erro'] === 'senha_incorreta'): ?>
        window.onload = function() {
            document.getElementById('modal-senha').style.display = 'flex';
        };
    <?php endif; ?>

    // Mostra o modal de confirmação se senha estiver correta
    <?php if (isset($_GET['confirmar_exclusao'])): ?>
        window.onload = function() {
            document.getElementById('modal-confirmacao').style.display = 'flex';
        };
    <?php endif; ?>

    // Função que realmente desativa a conta
    function confirmarExclusao() {
        window.location.href = "editar-perfil.php?excluir_definitivo=1";
    }

    // Exclusão definitiva (soft delete)
    <?php if (isset($_GET['excluir_definitivo'])): ?>
        <?php
            $pdo = Database::getConexao();
            $stmt = $pdo->prepare("UPDATE tb_usuario SET fl_ativo = 0, dt_atualizacao = NOW() WHERE id_usuario = ?");
            $stmt->execute([$_SESSION['usuario']['id']]);
            session_destroy();
        ?>
        alert("Sua conta foi desativada com sucesso.");
        window.location.href = "index.php";
    <?php endif; ?>
</script>
</html>