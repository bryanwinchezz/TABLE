<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../app/config/database.php';

// Foto do avatar do usuário ativo na barra de navegação
$fotoNavbar = '../img/uploads/perfil/avatar1.png';
if (isset($_SESSION['usuario'])) {
    $fotoUsuario = $_SESSION['usuario']['foto'] ?? '';
    // Corrigir caminho relativo para a raiz se necessário
    if (!empty($fotoUsuario)) {
        $fotoNavbar = $fotoUsuario;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Sobre Nós</title>
    <link rel="shortcut icon" href="../img/logo_branco1.png" type="image/x-icon">
    <!-- Tipografia premium Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- FontAwesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Estilos padrão de layout global e da página -->
    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/sobre-nos.css">
</head>

<body class="pagina-sobre">

    <!-- CABEÇALHO (HEADER) -->
    <header>
        <div class="logotipo">
            <a href="index.php"><img src="../img/logo_horizontal1.png" alt="Logo TABLE"></a>
        </div>

        <!-- MENU MOBILE (HAMBURGER) -->
        <div class="menu-toggle" id="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </div>

        <nav id="nav-menu">
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="cm-jogar.php">Como Jogar</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
                </li>
                <li><a
                        href="<?= isset($_SESSION['usuario']['cargo']) && in_array(strtolower($_SESSION['usuario']['cargo']), ['mestre', 'admin']) ? 'criar-mapa.php' : 'editar-perfil.php?abrir_mestre=1'; ?>">Mundos</a>
                </li>
                <li><a href="rolagem-de-dados.php">Dados</a></li>
                <li><a href="sobre-nos.php" class="ativo">Sobre Nós</a></li>
            </ul>

            <!-- BOTÕES MOBILE -->
            <div class="nav-mobile-footer">
                <?php if (isset($_SESSION['usuario'])): ?>
                    <div class="usuario-logado-nav" onclick="window.location.href='perfil.php'">
                        <img src="<?= htmlspecialchars($fotoNavbar) ?>" alt="Avatar Navbar" class="avatar-nav">
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

        <!-- BOTÕES DESKTOP -->
        <?php if (isset($_SESSION['usuario'])): ?>
            <div class="usuario-logado-nav desktop-only" id="nav-logado" onclick="window.location.href='perfil.php'"
                title="Ir para o Perfil">
                <img src="<?= htmlspecialchars($fotoNavbar) ?>" alt="Avatar Navbar" class="avatar-nav">
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

    <main>
        <!-- SEÇÃO 1: HERO BANNER (SYNERA) -->
        <section class="secao-hero-synera"></section>

        <!-- SEÇÃO 2: QUEM SOMOS (SOBRE NÓS) -->
        <section class="secao-sobre-nos">
            <div class="sobre-nos-content">
                <h2 class="titulo-secao-grande">Sobre Nós</h2>
                <div class="sobre-nos-texto">
                    <p>A Synera é uma empresa de desenvolvimento de software criada por uma equipe de cinco
                        desenvolvedores apaixonados por tecnologia, inovação e criação de soluções digitais. Nosso
                        objetivo é transformar ideias em produtos úteis, modernos e acessíveis para pessoas e
                        comunidades.</p>
                    <p>Acreditamos que a tecnologia pode conectar pessoas, simplificar processos e criar novas
                        experiências. Por isso, buscamos desenvolver softwares que não apenas funcionem bem, mas que
                        também tragam valor real para quem utiliza.</p>
                    <p>Nosso trabalho é guiado por criatividade, colaboração e pelo desejo constante de evoluir e
                        aprender.</p>
                </div>
            </div>
        </section>

        <!-- SEÇÃO 3: PRIMEIRO PROJETO (TABLE) -->
        <section class="secao-primeiro-projeto">
            <div class="primeiro-projeto-content">
                <h2>Nosso Primeiro Projeto</h2>
                <p>Nosso primeiro projeto é a <strong>Table</strong>, uma plataforma criada especialmente para jogadores
                    de RPG. A Table foi desenvolvido com o objetivo de facilitar a organização de mesas de RPG, conectar
                    jogadores e mestres, além de oferecer ferramentas que ajudam a tornar as sessões mais práticas e
                    imersivas. Acreditamos que o RPG é uma forma incrível de contar histórias, criar mundos e unir
                    pessoas. Por isso, criamos o Table para ajudar essa comunidade a crescer e ter uma experiência ainda
                    melhor.</p>
            </div>
        </section>

        <!-- SEÇÃO 4: NOSSOS VALORES E MISSÃO/VISÃO -->
        <section class="secao-nossos-valores">
            <div class="valores-content">
                <h2>Nossos Valores</h2>

                <!-- Grid com os 5 cards translúcidos glassmorphic -->
                <div class="valores-grid">
                    <div class="valor-card">
                        <h3>Inovação</h3>
                        <p>Buscamos sempre novas ideias e tecnologias para criar soluções melhores.</p>
                    </div>
                    <div class="valor-card">
                        <h3>Colaboração</h3>
                        <p>Trabalhamos juntos para alcançar resultados maiores do que conseguiríamos individualmente.
                        </p>
                    </div>
                    <div class="valor-card">
                        <h3>Qualidade</h3>
                        <p>Nos comprometemos em entregar produtos bem construídos, confiáveis e eficientes.</p>
                    </div>
                    <div class="valor-card">
                        <h3>Comunidade</h3>
                        <p>Valorizamos as pessoas que utilizam nossas plataformas e buscamos sempre melhorar suas
                            experiências.</p>
                    </div>
                    <div class="valor-card">
                        <h3>Aprendizado constante</h3>
                        <p>Estamos sempre evoluindo, aprendendo e aprimorando nossas habilidades.</p>
                    </div>
                </div>

                <!-- Missão e Visão -->
                <div class="missao-visao-row">
                    <div class="col-missao">
                        <h3>Nossa Missão</h3>
                        <p>Criar soluções tecnológicas inovadoras que ajudem pessoas e comunidades a se conectarem,
                            colaborarem e transformarem suas ideias em realidade.</p>
                    </div>

                    <!-- Pino vertical divisório do Figma -->
                    <div class="divisoria-vertical">
                        <div class="divisoria-pino"></div>
                        <div class="divisoria-linha"></div>
                    </div>

                    <div class="col-visao">
                        <h3>Nossa Visão</h3>
                        <p>Ser uma empresa reconhecida pela criação de softwares criativos, úteis e inovadores,
                            impactando positivamente comunidades digitais ao redor do mundo.</p>
                    </div>
                </div>

                <!-- Botão de Download do Manual de Identidade -->
                <div class="area-botao-manual">
                    <a href="../pdf/brandbook.pdf" target="_blank" title="Ver Manual de Identidade Visual"
                        class="btn-manual-identidade">
                        <i class="fa-solid fa-book-open"></i>
                        <span>Ver Manual de Identidade</span>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- RODAPÉ (FOOTER) -->
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
                    <li><a
                            href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
                    </li>
                    <li><a
                            href="<?= isset($_SESSION['usuario']['cargo']) && in_array(strtolower($_SESSION['usuario']['cargo']), ['mestre', 'admin']) ? 'criar-mapa.php' : 'editar-perfil.php?abrir_mestre=1'; ?>">Mundos</a>
                    </li>
                    <li><a href="rolagem-de-dados.php">Dados</a></li>
                    <li><a href="sobre-nos.php" class="ativo">Sobre Nós</a></li>
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
            <p>© 2025 T.A.B.L.E. Desenvolvido com <i class="fa-solid fa-heart" style="color: #e74c3c;"></i> para os Fãs
                do RPG.</p>
            <div class="redes-sociais">
                <a href="#" aria-label="Discord"><i class="fa-brands fa-discord"></i></a>
                <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <!-- Scripts padrão do TABLE para menu mobile -->
    <script src="../js/script.js" defer></script>
    <script src="../js/nav-global.js" defer></script>

</body>

</html>