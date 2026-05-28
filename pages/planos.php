<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Planos</title>
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">

    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/planos.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        /* ===== RESET & BASE ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --cor-fundo:       #0d0b1a;
            --cor-fundo-card:  #13102280;
            --cor-borda-card:  #2a2050;
            --cor-acento:      #6c3fd4;
            --cor-acento-vivo: #7c4fe0;
            --cor-texto:       #e8e0f7;
            --cor-texto-suave: #9b8fc0;
            --cor-branco:      #ffffff;
            --cor-preco-bg:    #1a1530;
        }

        body.pagina-inicial {
            background-color: var(--cor-fundo);
            color: var(--cor-texto);
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
        }

        /* ===== HERO / SECAO-DESTAQUE ===== */
        .secao-destaque {
            padding: 120px 5% 60px;
            background: linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.75)), url("../img/fundo_inicial.jpg") center center / cover no-repeat fixed;
            border-bottom: 2px solid var(--borda-escura);
            display: flex;
            flex-direction: column;
        }

        /* orbs decorativos */
        .secao-destaque::before,
        .secao-destaque::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }
        .secao-destaque::before {
            width: 480px; height: 480px;
            right: 5%; top: -80px;
            background: radial-gradient(circle, #6c3fd455 0%, transparent 70%);
            filter: blur(30px);
        }
        .secao-destaque::after {
            width: 280px; height: 280px;
            right: 20%; top: 10%;
            background: radial-gradient(circle, #9b60ff33 0%, transparent 70%);
            filter: blur(20px);
        }

        /* ===== PLANOS TÍTULO ===== */
        .planos-titulo {
            position: relative;
            z-index: 2;
            margin-bottom: 16px;
        }

        .planos-titulo h1 {
            font-size: clamp(1.8rem, 3vw, 2.6rem);
            font-weight: 800;
            color: var(--cor-branco);
            letter-spacing: -0.5px;
        }

        .planos-titulo p {
            margin-top: 12px;
            max-width: 860px;
            font-size: 0.97rem;
            line-height: 1.65;
            color: var(--cor-texto-suave);
        }

        /* ===== GRID DE CARDS ===== */
        .planos-grid {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(2, 2fr);
            gap: 50px;
            margin-top: 36px;
        }

        @media (max-width: 900px) {
            .planos-grid { grid-template-columns: repeat(2, 1fr); }
            .secao-destaque { padding: 40px 24px 60px; }
        }
        @media (max-width: 540px) {
            .planos-grid { grid-template-columns: 1fr; }
        }

        /* ===== CARD ===== */
        .plano-card {
            background: #13102299;
            border: 1px solid var(--cor-borda-card);
            border-radius: 16px;
            padding: 28px 22px 24px;
            display: flex;
            flex-direction: column;
            gap: 18px;
            backdrop-filter: blur(8px);
            transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
            cursor: default;
        }

        .plano-card:hover {
            transform: translateY(-5px);
            border-color: var(--cor-acento);
            box-shadow: 0 8px 32px #6c3fd430;
        }

        /* cabeçalho do card */
        .plano-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .plano-card-header h3 {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--cor-branco);
            line-height: 1.25;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .plano-card-header .plano-icone {
            font-size: 1.2rem;
            color: var(--cor-texto-suave);
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* barra de descrição */
        .plano-descricao {
            display: flex;
            gap: 10px;
            flex: 1;
        }

        .plano-descricao .barra {
            width: 3px;
            border-radius: 4px;
            background: var(--cor-acento);
            flex-shrink: 0;
        }

        .plano-descricao p {
            font-size: 0.84rem;
            line-height: 1.6;
            color: var(--cor-texto-suave);
        }

        /* preço */
        .plano-preco {
            margin-top: auto;
        }

        .plano-preco .btn-preco {
            display: inline-block;
            background: var(--cor-preco-bg);
            border: 1.5px solid var(--cor-borda-card);
            color: var(--cor-branco);
            font-size: 0.95rem;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: background .2s, border-color .2s;
            text-decoration: none;
            white-space: nowrap;
        }

        .plano-preco .btn-preco:hover {
            background: var(--cor-acento);
            border-color: var(--cor-acento-vivo);
        }

        /* botão grátis (branco) */
        .plano-preco .btn-gratis {
            display: inline-block;
            background: var(--cor-branco);
            color: #0d0b1a;
            font-size: 0.95rem;
            font-weight: 700;
            padding: 10px 32px;
            border-radius: 50px;
            cursor: pointer;
            transition: background .2s, color .2s;
            text-decoration: none;
            border: none;
        }

        .plano-preco .btn-gratis:hover {
            background: #e0d4ff;
        }
    </style>
</head>

<body class="pagina-inicial">

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
                <a href="login.php" class="botao-entrar">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="cadastro.php" class="botao-cadastrar">
                    <i class="fas fa-user-plus"></i> Cadastre-se
                </a>
            </div>
        <?php endif; ?>
    </header>

    <section class="secao-destaque">

        <div class="planos-titulo">
            <h1>Planos da TABLE</h1>
            <p>Transforme suas ideias em projetos incríveis com uma plataforma completa para criação de mapas, sistemas e automações.
               Escolha o plano ideal para você, evolua seus projetos com ferramentas avançadas e tenha acesso a recursos exclusivos feitos
               para criadores que querem ir além.</p>
        </div>

        <div class="planos-grid">

            <!-- FREE -->
            <div class="plano-card">
                <div class="plano-card-header">
                    <h3>FREE</h3>
                    <span class="plano-icone"><i class="fas fa-lock"></i></span>
                </div>
                <div class="plano-descricao">
                    <div class="barra"></div>
                    <p>Comece grátis e teste os recursos básicos da plataforma.</p>
                </div>
                <div class="plano-preco">
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="virar_mestre" class="btn-gratis">
                            Grátis
                        </button>
                    </form>
                </div>
            </div>

            <!-- PLANO DE MAPAS -->
            <div class="plano-card">
                <div class="plano-card-header">
                    <h3>Plano de<br>Mapas</h3>
                    <span class="plano-icone"><i class="fas fa-map"></i></span>
                </div>
                <div class="plano-descricao">
                    <div class="barra"></div>
                    <p>Crie mapas profissionais com ferramentas avançadas.</p>
                </div>
                <div class="plano-preco">
                    <a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>" class="btn-preco">R$19,90/mês</a>
                </div>
            </div>

            <!-- PLANO DE SISTEMAS -->
            <div class="plano-card">
                <div class="plano-card-header">
                    <h3>Plano de<br>Sistemas</h3>
                    <span class="plano-icone"><i class="fas fa-microchip"></i></span>
                </div>
                <div class="plano-descricao">
                    <div class="barra"></div>
                    <p>Desenvolva sistemas completos sem limites.</p>
                </div>
                <div class="plano-preco">
                    <a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>" class="btn-preco">R$29,90/mês</a>
                </div>
            </div>

            <!-- PLANO COMPLETO -->
            <div class="plano-card">
                <div class="plano-card-header">
                    <h3>Plano<br>Completo</h3>
                    <span class="plano-icone"><i class="fas fa-crown"></i></span>
                </div>
                <div class="plano-descricao">
                    <div class="barra"></div>
                    <p>Tudo desbloqueado em um só plano.</p>
                </div>
                <div class="plano-preco">
                    <a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>" class="btn-preco">R$49,90/mês</a>
                </div>
            </div>

        </div>
    </section>

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

    <?php
    require_once '../app/config/database.php';

    // ======================
    // PROCESSAMENTO: VIRAR MESTRE
    // ======================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['virar_mestre'])) {
        if (!isset($_SESSION['usuario'])) {
            header("Location: login.php");
            exit;
        }
        try {
            $conn = Database::getConexao();
            $stmt = $conn->prepare("UPDATE tb_usuario SET tp_cargo = 'mestre' WHERE id_usuario = :id");
            $stmt->execute([':id' => $_SESSION['usuario']['id']]);
            $_SESSION['usuario']['cargo'] = 'mestre';
            
            // Alterado: agora redireciona para editar-perfil.php
            header("Location: editar-perfil.php?sucesso_mestre=1");
            exit;
        } catch (PDOException $e) {
            header("Location: planos.php?erro=mestre");
            exit;
        }
    }
    ?>

    <script src="../js/script.js" defer></script>
    <script src="../js/nav-global.js" defer></script>
</body>

</html>