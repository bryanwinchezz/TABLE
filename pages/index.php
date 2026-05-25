<?php
/**
 *  Após a página de login definir a sessão com os dados do usuario a página index lê a sessão e inicia a mesma
 *  Na navbar temos um if e else para cado o usuario esteja conectado ou não, mudando sendo que: 
 *  SE o usuário estiver logado irá mostrar a foto e o nome do usuário
 *  SE NÃO irá mostrar os botões para navegar até a página de login ou cadastro
 */
session_start();

// RESTAURADOR SEGURO E AUTOMÁTICO DO AVATAR PADRÃO (SAFETY FIRST)
try {
    $avatarPath = __DIR__ . '/../img/uploads/perfil/avatar1.png';
    $sourcePath = __DIR__ . '/../img/avatar.png';
    if (!file_exists($avatarPath)) {
        @mkdir(dirname($avatarPath), 0777, true);
        if (file_exists($sourcePath)) {
            @copy($sourcePath, $avatarPath);
        }
    }
} catch (Exception $e) {
    // Silencioso
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Início</title>
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">

    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/index.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="pagina-inicial">

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
                <li><a href="index.php" class="ativo">Início</a></li>
                <li><a href="cm-jogar.php">Como Jogar</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
                </li>
                <li><a href="criar-mapa.php">Mundos</a></li>
                <li><a href="rolagem-de-dados.php">Dados</a></li>
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

    <section class="secao-destaque">
        <div class="destaque-conteudo">
            <div class="destaque-texto">
                <h2>Feito por</h2>
                <h1>SYNERA</h1>
                <p>A TABLE é um sistema que reúne, em uma única plataforma, as ferramentas essenciais para jogadores e
                    mestres de RPG, facilitando a criação, organização e condução de campanhas.</p>
                <div class="destaque-botoes">
                    <a href="#oque-e-rpg" class="botao botao-primario"><i class="fas fa-dice-d20"></i> Começar</a>
                    <a href="#servicos" class="botao botao-contorno"><i class="fas fa-scroll"></i> Saiba Mais</a>
                </div>
            </div>
            <div class="destaque-imagem">
                <img src="../img/logo_icone.png" alt="Logo TABLE">
            </div>
        </div>
    </section>

    <section class="o-que-e-rpg" id="oque-e-rpg">
        <div class="rpg-container">
            <div class="rpg-texto">
                <h2>Afinal, o que é um RPG?</h2>
                <p>Sabe aquela clássica cena de <strong>Stranger Things</strong> com os garotos no porão jogando contra
                    o Demogorgon? Aquilo é o RPG de mesa! Muito mais do que um jogo comum, é uma experiência de contar
                    histórias onde você assume o papel de um personagem e vive aventuras épicas em mundos imaginários.
                </p>
                <p><strong>Como funciona?</strong> Um jogador atua como o <strong>Mestre</strong> (assim como o Mike na
                    série): ele narra a história, cria o universo e controla os monstros. Os outros jogadores decidem o
                    que seus heróis vão fazer para sobreviver. O grande charme é que o sucesso ou fracasso de qualquer
                    ação — como acertar uma bola de fogo ou desarmar uma armadilha — é decidido na sorte,
                    <strong>rolando dados</strong>. É o encontro perfeito entre teatro, estratégia e pura imaginação!
                </p>
            </div>
            <div class="rpg-icone">
                <i class="fas fa-dice-d20"></i>
            </div>
        </div>
    </section>

    <main class="secao-recursos" id="servicos">
        <h2>A TABLE conta com serviços como:</h2>

        <div class="carrossel-container">
            <button class="btn-carrossel btn-anterior"><i class="fas fa-chevron-left"></i></button>

            <div class="carrossel-trilho-container">
                <ul class="carrossel-trilho">
                    <li class="carrossel-slide slide-atual">
                        <div class="recurso-linha">
                            <div class="recurso-imagem">
                                <img src="../img/foto-ficha.jpg" alt="Fichas Digitais">
                            </div>
                            <div class="recurso-texto">
                                <h3>Fichas Digitais</h3>
                                <p>A TABLE oferece aos jogadores e mestres de RPG uma experiência completa e imersiva de
                                    ficha digital, projetada para tornar suas aventuras ainda mais práticas e
                                    envolventes. Com uma interface intuitiva, personalizável e
                                    de fácil navegação, a plataforma permite criar, editar e acessar fichas de
                                    personagem de forma rápida e eficiente — seja em sessões presenciais ou
                                    online.<br><br>Seja você um aventureiro iniciante ou um mestre veterano,
                                    a TABLE é a ferramenta ideal para elevar suas campanhas a outro nível, unindo
                                    tecnologia e imaginação em cada sessão.</p>
                                <div class="recurso-botoes">
                                    <a href="criar-personagem.php" class="botao-claro"><i
                                            class="fas fa-file-signature"></i> Criar
                                        Ficha</a>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="carrossel-slide">
                        <div class="recurso-linha invertido">
                            <div class="recurso-imagem">
                                <img src="../img/foto-campanha.jpg" alt="Criação de Campanhas">
                            </div>
                            <div class="recurso-texto">
                                <h3>Criação de Campanhas</h3>
                                <p>A TABLE foi criado para facilitar a vida de mestres e jogadores na hora de construir
                                    e administrar suas campanhas de RPG. Com recursos completos e um design intuitivo, a
                                    plataforma permite desenvolver histórias, organizar
                                    enredos, personagens e locais, além de acompanhar cada detalhe da jornada de forma
                                    simples e prática — tanto em mesas presenciais quanto no ambiente
                                    online.<br><br>Desde a concepção do mundo até o desfecho da aventura,
                                    a TABLE é o companheiro ideal para mestres e jogadores transformarem ideias em
                                    campanhas vivas e dinâmicas.</p>
                                <div class="recurso-botoes">
                                    <a href="criar-campanha.php" class="botao-claro"><i
                                            class="fas fa-map-marked-alt"></i> Criar
                                        Campanha</a>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="carrossel-slide">
                        <div class="recurso-linha">
                            <div class="recurso-imagem">
                                <img src="../img/foto-regra.jpg" alt="Ensinamentos de Regras">
                            </div>
                            <div class="recurso-texto">
                                <h3>Ensinamentos de Regras</h3>
                                <p>A TABLE torna o aprendizado das regras de RPG simples, interativo e acessível para
                                    todos os níveis de jogadores. A plataforma oferece um ambiente prático e intuitivo,
                                    onde é possível explorar sistemas, consultar mecânicas
                                    e entender conceitos de jogo de maneira clara e dinâmica.<br><br>Com recursos que
                                    facilitam a compreensão e aplicação das regras durante as sessões, a TABLE ajuda
                                    tanto novos aventureiros a dominar o básico quanto mestres
                                    experientes a aprofundar seus conhecimentos e ensinar com mais agilidade.</p>
                                <div class="recurso-botoes">
                                    <a href="cm-jogar.php" class="botao-claro"><i class="fas fa-book-open"></i> Como
                                        Jogar</a>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <button class="btn-carrossel btn-proximo"><i class="fas fa-chevron-right"></i></button>
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

    <script src="../js/script.js" defer></script>
    <script src="../js/nav-global.js" defer></script>
</body>

</html>
</body>

</html>
