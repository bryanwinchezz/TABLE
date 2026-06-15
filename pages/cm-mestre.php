<?php
/**
 *  Após a página de login definir a sessão com os dados do usuario a página index lê a sessão e inicia a mesma
 *  Na navbar temos um if e else para cado o usuario esteja conectado ou não, mudando sendo que: 
 *  SE o usuário estiver logado irá mostrar a foto e o nome do usuário
 *  SE NÃO irá mostrar os botões para navegar até a página de login ou cadastro
 */
session_start();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Mestre</title>
    <link rel="shortcut icon" href="../img/logo_branco1.png" type="image/x-icon">

    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/pgs-como.css?v=6">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body style="display: flex; flex-direction: column; min-height: 100vh; background-color: #190e35; color: #fff;">

    <header>
        <div class="logotipo">
            <a href="index.php"><img src="../img/logo_horizontal1.png" alt="Logo TABLE"></a>
        </div>

        <!-- BOTÃO MENU MOBILE (HAMBURGER) -->
        <div class="menu-toggle" id="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </div>

        <nav id="nav-menu">
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="cm-jogar.php" class="ativo">Como Jogar</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
                </li>
                <li><a href="<?= isset($_SESSION['usuario']['cargo']) && in_array(strtolower($_SESSION['usuario']['cargo']), ['mestre','admin']) ? 'criar-mapa.php' : 'editar-perfil.php?abrir_mestre=1'; ?>">Mundos</a></li>
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

    <main style="flex: 1; padding-top: 120px; padding-bottom: 40px; text-align: center;">
        <section class="comojogar2">
            <img src="../img/cm-mestre.png" alt="Imagem ilustrativa de um mestre de RPG" class="img-comojogar">
            <div class="desc-comojogar">
                <h1>Como Mestre</h1>
                <p>Ser Mestre em um RPG é assumir o papel de guia e contador de histórias. O Mestre é responsável por
                    criar a história por trás, apresentar desafios e controlar tudo o que não pertence diretamente aos
                    jogadores, como os cenários, inimigos e NPCs (Personagens Não Jogadores).</p>
            </div>
        </section>

        <section class="expli-jogador">
            <div class="txt-jogador">
                <h1>O que significa ser um mestre</h1>
                <br>
                <p>
                    O Mestre é o coração do jogo: sem ele, não há enredo nem direção. Sua função é preparar um mundo —
                    ou improvisar um — que servirá de palco para a imaginação dos jogadores. Mais do que um “juiz”, o
                    Mestre é um facilitador da experiência, garantindo que todos se divirtam e que a narrativa siga
                    coerente e envolvente.
                </p>
            </div>
        </section>

        <section class="componentes">
            <h1>Principais Responsabilidades do Mestre</h1>

            <div class="lista-comp">
                <div class="item-comp">
                    <h3>Criação do Mundo</h3>
                    <p>
                        O Mestre descrever o cenário onde a aventura acontece. Pode ser um reino medieval cheio de
                        cavaleiros e dragões, um futuro pós-apocalíptico ou até mesmo uma cidade contemporânea. Cabe a
                        ele definir locais, culturas, perigos e recompensas.
                    </p>
                </div>

                <div class="item-comp">
                    <h3>Interpretação de NPCs</h3>
                    <p>
                        O Mestre dá vida a todos os personagens que não são controlados pelos jogadores. Isso inclui
                        aliados, vilões, comerciantes, criaturas, guardas e qualquer figura que ajude a compor o
                        universo narrativo.
                    </p>
                </div>

                <div class="item-comp">
                    <h3>Apresentação de Desafios</h3>
                    <p>
                        O Mestre é quem garante que o sistema de regras seja respeitado. Isso significa arbitrar jogadas
                        de dados, interpretar os resultados e decidir o que acontece após cada ação dos jogadores.
                    </p>
                </div>

                <div class="item-comp">
                    <h3>Aplicação das Regras</h3>
                    <p>
                        O Mestre descreve o cenário onde a aventura acontece. Pode ser um reino medieval cheio de
                        cavaleiros e dragões, um futuro pós-apocalíptico ou até mesmo uma cidade contemporânea. Cabe a
                        ele definir locais, culturas, perigos e recompensas.
                    </p>
                </div>

                <div class="item-comp">
                    <h3>Equilíbrio e Diversão </h3>
                    <p>
                        É papel do Mestre garantir que todos os jogadores tenham espaço para participar, que a
                        dificuldade seja justa e que o jogo avance de forma dinâmica.
                    </p>
                </div>
            </div>
        </section>

        <section class="expli-rpg">
            <div class="txt-expli">
                <h1>Experiência como Mestre</h1>
                <p>
                    Ser Mestre pode parecer desafiador, mas também é extremamente gratificante. Você terá a oportunidade
                    de criar mundos inteiros, controlar dezenas de personagens, narrar cenas épicas e, principalmente,
                    guiar os jogadores em uma experiência única.
                    <br><br>
                    É importante lembrar que o Mestre não é um inimigo dos jogadores. Seu objetivo não é derrotá-los,
                    mas sim proporcionar situações que os desafiem e os motivem a crescer como personagens. Quanto mais
                    rica e equilibrada for a narrativa, mais memorável será a sessão.
                    <br><br>
                    Em resumo: ser Mestre é ser o arquiteto da aventura, aquele que conduz a história, cria
                    possibilidades e garante que todos possam viver momentos marcantes em conjunto.
                </p>
            </div>
            <img src="../img/ex-cm-mestre.png" alt="Imagem ilustrativa de um mestre de RPG"
                class="img-expli-rpg-mestre">
        </section>

        <div class="btn-navegacao">
            <a href="cm-jogar.php">
                <button><i class="fas fa-arrow-left"></i> Voltar para Como Jogar</button>
            </a>
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
                    <li><a href="<?= isset($_SESSION['usuario']['cargo']) && in_array(strtolower($_SESSION['usuario']['cargo']), ['mestre','admin']) ? 'criar-mapa.php' : 'editar-perfil.php?abrir_mestre=1'; ?>">Mundos</a></li>
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

</html>


