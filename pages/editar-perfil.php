<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require_once '../app/config/database.php';

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
// PROCESSAMENTO DO FORMULÁRIO (DADOS GERAIS)
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_dados'])) {
    $novo_nome = trim($_POST['nome'] ?? '');
    $novo_email = trim($_POST['email'] ?? '');

    if (empty($novo_nome) || empty($novo_email)) {
        $erro = "Nome e e-mail são obrigatórios.";
    } else {
        try {
            $stmt = $conn->prepare("
                UPDATE tb_usuario 
                SET nm_usuario = :nome, 
                    nm_exibicao = :nome_exibicao, 
                    ds_email = :email,
                    ds_bio = :bio,
                    dt_atualizacao = CURRENT_TIMESTAMP
                WHERE id_usuario = :id
            ");

            $stmt->execute([
                ':nome' => $novo_nome,
                ':nome_exibicao' => $novo_nome,
                ':email' => $novo_email,
                ':bio' => trim($_POST['bio'] ?? ''),
                ':id' => $_SESSION['usuario']['id']
            ]);

            // Atualiza a sessão
            $_SESSION['usuario']['nome'] = $novo_nome;
            $_SESSION['usuario']['email'] = $novo_email;
            $_SESSION['usuario']['bio'] = trim($_POST['bio'] ?? '');

            header("Location: editar-perfil.php?sucesso=1");
            exit;

        } catch (PDOException $e) {
            $erro = "Erro ao atualizar os dados.";
        }
    }
}

// ======================
// PROCESSAMENTO DE FOTO DE PERFIL
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
    $arquivo = $_FILES['foto_perfil'];

    if ($arquivo['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($extensao, $extensoes_permitidas)) {
            $novo_nome_foto = "perfil_" . $_SESSION['usuario']['id'] . "_" . time() . "." . $extensao;
            $destino = "../img/uploads/perfil/" . $novo_nome_foto;

            if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
                try {
                    $stmt = $conn->prepare("UPDATE tb_usuario SET ds_foto = :foto WHERE id_usuario = :id");
                    $stmt->execute([':foto' => $destino, ':id' => $_SESSION['usuario']['id']]);

                    // Atualiza a sessão com a nova foto
                    $_SESSION['usuario']['foto'] = $destino;

                    header("Location: editar-perfil.php?sucesso_foto=1");
                    exit;
                } catch (PDOException $e) {
                    $erro = "Erro ao salvar caminho da foto no banco.";
                }
            } else {
                $erro = "Falha ao mover o arquivo para a pasta de destino.";
            }
        } else {
            $erro = "Formato de arquivo não permitido. Use JPG, PNG ou GIF.";
        }
    }
}

// ======================
// PROCESSAMENTO: VIRAR MESTRE
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['virar_mestre'])) {
    try {
        $stmt = $conn->prepare("UPDATE tb_usuario SET tp_cargo = 'mestre' WHERE id_usuario = :id");
        $stmt->execute([':id' => $_SESSION['usuario']['id']]);
        $_SESSION['usuario']['cargo'] = 'mestre';
        header("Location: editar-perfil.php?sucesso_mestre=1");
        exit;
    } catch (PDOException $e) {
        $erro = "Erro ao promover usuário a mestre.";
    }
}

// ======================
// PROCESSAMENTO: DESISTIR DE MESTRAR
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desistir_mestre'])) {
    try {
        $conn->beginTransaction();

        // 1. Excluir os sistemas criados por este usuário (exceto o oficial com ID 1)
        $stmtDelSistemas = $conn->prepare("DELETE FROM tb_sistema WHERE id_usuario_criador = :id AND id_sistema != 1");
        $stmtDelSistemas->execute([':id' => $_SESSION['usuario']['id']]);

        // 2. Mudar o cargo do usuário para jogador
        $stmt = $conn->prepare("UPDATE tb_usuario SET tp_cargo = 'jogador' WHERE id_usuario = :id");
        $stmt->execute([':id' => $_SESSION['usuario']['id']]);

        $_SESSION['usuario']['cargo'] = 'jogador';

        $conn->commit();
        header("Location: editar-perfil.php?sucesso_jogador=1");
        exit;
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $erro = "Erro ao desistir de mestrar: " . $e->getMessage();
    }
}

// ======================
// BUSCA DOS DADOS DO USUÁRIO
// ======================
try {
    $stmt = $conn->prepare("
        SELECT id_usuario, nm_usuario, ds_email, dt_nascimento, dt_cadastro, tp_cargo, ds_foto, ds_bio 
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

$fotoPerfil = (!empty($usuario['ds_foto']) && realpath(__DIR__ . '/' . $usuario['ds_foto']) !== false) ? $usuario['ds_foto'] : '../img/uploads/perfil/avatar.png';
$fotoNavbar = (!empty($_SESSION['usuario']['foto']) && realpath(__DIR__ . '/' . $_SESSION['usuario']['foto']) !== false) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar.png';

// Lógica de Classificação Indicativa
$idade = 0;
if (!empty($usuario['dt_nascimento'])) {
    $hoje = new DateTime();
    $nasc = new DateTime($usuario['dt_nascimento']);
    $idade = $hoje->diff($nasc)->y;
}

function obterClassificacao($idd)
{
    if ($idd < 10)
        return ['sigla' => 'L', 'cor' => '#27ae60', 'label' => 'Livre'];
    if ($idd < 12)
        return ['sigla' => '10', 'cor' => '#2980b9', 'label' => '10 Anos'];
    if ($idd < 14)
        return ['sigla' => '12', 'cor' => '#f1c40f', 'label' => '12 Anos'];
    if ($idd < 16)
        return ['sigla' => '14', 'cor' => '#e67e22', 'label' => '14 Anos'];
    if ($idd < 18)
        return ['sigla' => '16', 'cor' => '#c0392b', 'label' => '16 Anos'];
    return ['sigla' => '18', 'cor' => '#1a1a1a', 'label' => '18 Anos'];
}
$permissao = obterClassificacao($idade);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Editar Perfil</title>
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
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
                <li><a href="cm-jogar.php">Como Jogar</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
                </li>
                <li><a href="criar-mapa.php">Mundos</a></li>
                <li><a href="rolagem-de-dados.php">Dados</a></li>
                <li><a href="sobre-nos.php">Sobre Nós</a></li>
            </ul>
        </nav>
        <?php if (isset($_SESSION['usuario'])): ?>
            <div class="usuario-logado-nav" id="nav-logado" onclick="window.location.href='perfil.php'"
                title="Ir para o Perfil">
                <img src="<?= htmlspecialchars($fotoNavbar) ?>"
                    alt="Avatar Navbar" class="avatar-nav">
                <span class="nome-nav"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
            </div>
        <?php else: ?>
            <div class="botoes-navegacao" id="nav-deslogado">
                <a href="login.php" class="botao-entrar">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="cadastro.php" class="botao-cadastrar">
                    <i class="fas fa-user-plus"></i> Cadastre-se
                </a>
            </div>
        <?php endif; ?>
    </header>

    <main class="container-principal-edicao">
        <div class="cartao-edicao">

            <a href="perfil.php" class="link-voltar">
                <i class="fas fa-arrow-left"></i> Voltar ao Perfil
            </a>

            <div class="cabecalho-edicao">
                <div class="foto-container" onclick="document.getElementById('input-foto').click()"
                    style="cursor:pointer;" title="Clique para mudar a foto">
                    <img src="<?= htmlspecialchars($fotoPerfil) ?>"
                        alt="Foto de perfil" class="foto-perfil">
                    <div class="icone-camera">
                        <i class="fas fa-camera"></i>
                    </div>
                    <!-- Form escondido para upload de foto -->
                    <form id="form-foto" method="POST" enctype="multipart/form-data" style="display:none;">
                        <input type="file" name="foto_perfil" id="input-foto"
                            onchange="document.getElementById('form-foto').submit()">
                    </form>
                </div>

                <div class="info-usuario">
                    <h2><?= htmlspecialchars($usuario['nm_usuario']) ?></h2>
                    <p><?= htmlspecialchars($usuario['ds_email']) ?></p>
                    <p class="membro-desde">
                        Membro desde:
                        <?= $usuario['dt_cadastro'] ? date('d/m/Y', strtotime($usuario['dt_cadastro'])) : 'Não informado' ?>
                    </p>
                </div>
            </div>

            <hr class="divisor">

            <section>
                <h3 class="titulo-roxo">Meus Dados</h3>
                <form class="formulario-edicao" method="POST">
                    <input type="hidden" name="salvar_dados" value="1">
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
                        <label for="input-bio">Biografia:</label>
                        <textarea id="input-bio" name="bio" rows="4" placeholder="Conte um pouco sobre você..."><?= htmlspecialchars($usuario['ds_bio'] ?? '') ?></textarea>
                    </div>

                    <div class="campo-edicao">
                        <label for="input-data">Data de nascimento:</label>
                        <input type="text" id="input-data"
                            value="<?= $usuario['dt_nascimento'] ? date('d/m/Y', strtotime($usuario['dt_nascimento'])) : 'Não cadastrada' ?>"
                            disabled>
                    </div>

                    <div class="classificacao-indicativa-box"
                        style="margin-top: 15px; background: #f9f9f9; padding: 15px; border-radius: 10px; border: 1px solid #eee;">
                        <p style="font-weight: 700; color: #4A3A69; margin-bottom: 8px; font-size: 0.9rem;">Sua
                            Classificação Indicativa:</p>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span
                                style="background: <?= $permissao['cor'] ?>; color: #fff; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-weight: 800; border-radius: 6px; font-size: 1.1rem;"><?= $permissao['sigla'] ?></span>
                            <span style="color: #666; font-size: 0.85rem;">Você está apto a jogar sistemas de RPG com
                                classificação <strong><?= $permissao['label'] ?></strong>.</span>
                        </div>
                    </div>

                    <div class="area-botao-centro" style="margin-top: 25px;">
                        <button type="submit" class="botao-roxo">Salvar alterações</button>
                    </div>
                </form>
            </section>

            <hr class="divisor">

            <section>
                <h3 class="titulo-roxo"><?= strtolower($usuario['tp_cargo']) === 'mestre' ? 'Mestre' : 'Ser Mestre' ?>
                </h3>
                <p class="texto-descricao">
                    <?= strtolower($usuario['tp_cargo']) === 'mestre'
                        ? 'Você é um Mestre! Possui acesso à criação de campanhas, sistemas e mundos. Se decidir que não quer mais essa responsabilidade, você pode voltar a ser um jogador a qualquer momento.'
                        : 'Torne-se um verdadeiro mestre e desbloqueie a habilidade de criar seus próprios sistemas de RPG. Construa mundos, regras e experiências únicas, compartilhe tudo com a comunidade para que outros vivam as aventuras que você imaginar.'
                        ?>
                </p>
                <div class="area-botoes-centro">
                    <?php if (strtolower($usuario['tp_cargo']) === 'mestre'): ?>
                        <button type="button" onclick="abrirModalDesistirMestre()" class="botao-roxo"
                            style="background: #6c757d;">
                            <i class="fas fa-undo"></i> <span>Desistir de Mestrar</span>
                        </button>
                    <?php else: ?>
                        <button type="button" onclick="window.location.href='planos.php'" class="botao-roxo">
                            <i class="fas fa-book"></i> <span>Seja mestre</span>
                        </button>
                    <?php endif; ?>

                    <button type="button" onclick="window.location.href='planos.php'" class="botao-roxo" style="background: #6c757d;">
                        <i class="fas fa-ticket-alt"></i> <span>Planos</span>
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
                    <li><a
                            href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
                    </li>
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
                    <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Campanhas</a>
                    </li>
                    <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Meu Perfil</a>
                    </li>
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

    <script src="../js/nav-global.js" defer></script>
</body>
<div id="modal-senha"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; justify-content:center; align-items:center;">
    <div
        style="background:#fff; padding:30px; border-radius:12px; width:90%; max-width:420px; color:#333; text-align:center;">
        <h2 style="color:#dc3545;">⚠️ Excluir Perfil</h2>
        <p style="margin:20px 0 25px;">Digite sua senha para continuar.</p>

        <form method="POST">
            <input type="hidden" name="excluir_conta" value="1">

            <input type="password" name="senha_confirmacao" id="senha-input" placeholder="Sua senha atual" required
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
<div id="modal-confirmacao"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; justify-content:center; align-items:center;">
    <div
        style="background:#fff; padding:30px; border-radius:12px; width:90%; max-width:420px; color:#333; text-align:center;">
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

    btnExcluir.addEventListener('click', function () {
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
        window.onload = function () {
            document.getElementById('modal-senha').style.display = 'flex';
        };
    <?php endif; ?>

    // Mostra o modal de confirmação se senha estiver correta
    <?php if (isset($_GET['confirmar_exclusao'])): ?>
        window.onload = function () {
            document.getElementById('modal-confirmacao').style.display = 'flex';
        };
    <?php endif; ?>

    // Funções para os Modais de Mestre
    function abrirModalSerMestre() {
        document.getElementById('modal-ser-mestre').style.display = 'flex';
    }
    function fecharModalSerMestre() {
        document.getElementById('modal-ser-mestre').style.display = 'none';
    }
    function abrirModalDesistirMestre() {
        document.getElementById('confirmacao-desistir-1').value = '';
        document.getElementById('confirmacao-desistir-2').value = '';
        validarConfirmacaoDesistir();
        document.getElementById('modal-desistir-mestre').style.display = 'flex';
    }
    function fecharModalDesistirMestre() {
        document.getElementById('modal-desistir-mestre').style.display = 'none';
    }
    function validarConfirmacaoDesistir() {
        const val1 = document.getElementById('confirmacao-desistir-1').value.trim().toUpperCase();
        const val2 = document.getElementById('confirmacao-desistir-2').value.trim().toUpperCase();
        const btn = document.getElementById('btn-confirmar-desistencia');
        
        if (val1 === 'DESISTIR' && val2 === 'DESISTIR') {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
            btn.style.background = '#c0392b';
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            btn.style.background = '#6c757d';
        }
    }

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

<!-- MODAL: SER MESTRE -->
<div id="modal-ser-mestre"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; justify-content:center; align-items:center;">
    <div
        style="background:#fff; padding:30px; border-radius:12px; width:90%; max-width:420px; color:#333; text-align:center;">
        <h2 style="color:#4A3A69;"><i class="fas fa-crown"></i> Você tem certeza?</h2>
        <p style="margin:20px 0 25px; line-height: 1.5;">
            Ser Mestre é uma grande responsabilidade! <br>
            Você desbloqueia a opção de <strong>criar suas próprias campanhas, sistemas e mundos</strong> para outros
            jogadores.
        </p>
        <form method="POST">
            <div style="display:flex; gap:12px; justify-content:center;">
                <button type="button" onclick="fecharModalSerMestre()"
                    style="padding:10px 25px; background:#6c757d; color:white; border:none; border-radius:6px; cursor:pointer;">Cancelar</button>
                <button type="submit" name="virar_mestre"
                    style="padding:10px 25px; background:#4A3A69; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:700;">Sim,
                    quero ser Mestre!</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: DESISTIR MESTRE -->
<div id="modal-desistir-mestre"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; justify-content:center; align-items:center;">
    <div
        style="background:#fff; padding:30px; border-radius:12px; width:90%; max-width:440px; color:#333; text-align:center;">
        <h2 style="color:#c0392b; display:flex; align-items:center; justify-content:center; gap:10px; font-weight:800;">
            <i class="fas fa-exclamation-triangle"></i> ATENÇÃO CRÍTICA!
        </h2>
        <p style="margin:15px 0; line-height: 1.5; color:#555; font-size:0.95rem;">
            Ao desistir de mestrar, você voltará a ser um jogador comum. 
            <br><strong style="color:#c0392b;">ISSO IRÁ DELETAR PERMANENTEMENTE todos os sistemas personalizados criados por você (incluindo monstros, classes, perícias e fichas vinculadas)!</strong>
        </p>
        <p style="margin-bottom:20px; font-size:0.85rem; color:#888;">
            Para confirmar essa ação destrutiva irreversível, digite a palavra <strong style="color:#c0392b;">DESISTIR</strong> em ambos os campos abaixo:
        </p>
        <form method="POST">
            <div style="margin-bottom: 15px;">
                <input type="text" id="confirmacao-desistir-1" oninput="validarConfirmacaoDesistir()" placeholder="Digite DESISTIR" required
                    style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; text-align:center; font-weight:700; letter-spacing:1px; text-transform:uppercase;">
            </div>
            <div style="margin-bottom: 20px;">
                <input type="text" id="confirmacao-desistir-2" oninput="validarConfirmacaoDesistir()" placeholder="Digite DESISTIR novamente" required
                    style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; text-align:center; font-weight:700; letter-spacing:1px; text-transform:uppercase;">
            </div>
            <div style="display:flex; gap:12px; justify-content:center;">
                <button type="button" onclick="fecharModalDesistirMestre()"
                    style="padding:10px 25px; background:#6c757d; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600;">
                    Cancelar
                </button>
                <button type="submit" name="desistir_mestre" id="btn-confirmar-desistencia" disabled
                    style="padding:10px 25px; background:#6c757d; color:white; border:none; border-radius:6px; cursor:not-allowed; font-weight:700; opacity:0.5; transition: all 0.3s ease;">
                    Confirmar Desistência
                </button>
            </div>
        </form>
    </div>
</div>

</html>


