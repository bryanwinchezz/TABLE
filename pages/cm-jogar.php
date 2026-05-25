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
    <title>TABLE | Como Jogar</title>
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">

    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/pgs-como.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body style="display: flex; flex-direction: column; min-height: 100vh; background-color: #190e35; color: #fff;">

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
                <li><a href="index.php">Início</a></li>
                <li><a href="cm-jogar.php" class="ativo">Como Jogar</a></li>
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

    <main style="flex: 1; padding-top: 120px; padding-bottom: 40px; text-align: center;">

        <section class="comojogar">
            <div class="desc-comojogar">
                <h1>Como Jogar</h1>
                <p>Um grupo de pessoas interpretam personagens em um mundo imaginário, sendo a história guiada por um
                    Mestre/Narrador que descreve o cenário e as situações, e os jogadores cujas ações definem o rumo da
                    narrativa.</p>
            </div>
            <img src="../img/cm-jogar.png" alt="Uma pessoa jogando RPG" class="img-comojogar">
        </section>

        <section class="componentes">
            <h1>Principais Componentes</h1>

            <div class="lista-comp">
                <div class="item-comp">
                    <h3>Mestre</h3>
                    <p>
                        É o "diretor" do jogo, responsável por descrever o ambiente,
                        os desafios, os acontecimentos e interpretar os outros personagens (NPCs).
                    </p>
                </div>

                <div class="item-comp">
                    <h3>Jogadores e Personagens</h3>
                    <p>
                        Cada jogador cria um personagem, definindo suas características
                        e história, e interpreta esse personagem ao longo da aventura.
                    </p>
                </div>

                <div class="item-comp">
                    <h3>Ficha de Personagem</h3>
                    <p>
                        Um documento que contém todas as informações do personagem,
                        incluindo atributos (como força, inteligência), habilidades
                        e características.
                    </p>
                </div>

                <div class="item-comp">
                    <h3>Livro de Regras</h3>
                    <p>
                        Cada RPG possui um sistema de regras que define como as ações
                        são resolvidas, como testes são feitos com dados e como os
                        personagens evoluem.
                    </p>
                </div>

                <div class="item-comp">
                    <h3>Dados</h3>
                    <p>
                        Usados para adicionar um elemento de aleatoriedade e determinar
                        o sucesso ou fracasso de uma ação, geralmente lançando um dado
                        específico, como o D20.
                    </p>
                </div>
            </div>
        </section>

        <section class="rpg-componentes">
            <div class="como-funciona">
                <h2>Como Funciona um RPG</h2>

                <div class="txt-topico">
                    <div class="ico-nm">
                        <span><i class="fa-solid fa-cube"></i></span>
                        <h4>Interpretação de Papéis</h4>
                    </div>
                    <div class="desc-topico">
                        <p>
                            Os jogadores criam e dão vida a personagens, assumindo seus traços, manias e estilos de
                            forma imersiva;
                        </p>
                    </div>
                </div>

                <div class="txt-topico">
                    <div class="ico-nm">
                        <span><i class="fa-solid fa-cube"></i></span>
                        <h4>Narrativa Colaborativa</h4>
                    </div>
                    <div class="desc-topico">
                        <p>
                            O objetivo principal é contar uma história conjunta. As ações dos personagens e as
                            descrições do mestre do jogo (se houver) moldam a narrativa;
                        </p>
                    </div>
                </div>

                <div class="txt-topico">
                    <div class="ico-nm">
                        <span><i class="fa-solid fa-cube"></i></span>
                        <h4>Mestre do Jogo</h4>
                    </div>
                    <div class="desc-topico">
                        <p>
                            Frequentemente, um jogador atua como o mestre, que descreve os cenários e os desafios,
                            enquanto outros jogadores reagem como seus personagens;
                        </p>
                    </div>
                </div>

                <div class="txt-topico">
                    <div class="ico-nm">
                        <span><i class="fa-solid fa-cube"></i></span>
                        <h4>Regras e Sistemas</h4>
                    </div>
                    <div class="desc-topico">
                        <p>
                            Um sistema de regras, pode ser baseado em dados ou outras mecânicas, é estabelecido para
                            guiar o jogo e arbitrar o resultado das ações dos personagens.
                        </p>
                    </div>
                </div>
            </div>

            <div class="tipos-rpg">
                <h2>Tipos de RPG</h2>

                <div class="txt-topico">
                    <div class="ico-nm">
                        <span><i class="fa-solid fa-cube"></i></span>
                        <h4>RPG de Mesa (TTRPG)</h4>
                    </div>
                    <div class="desc-topico">
                        <p>
                            É a forma original, jogada com interações entre jogadores, dados e material físico, como
                            livros e fichas;
                        </p>
                    </div>
                </div>

                <div class="txt-topico">
                    <div class="ico-nm">
                        <span><i class="fa-solid fa-cube"></i></span>
                        <h4>RPG Eletrônico</h4>
                    </div>
                    <div class="desc-topico">
                        <p>
                            Existem diversas variantes em formato digital, como os que usam texto e os MMORPGs (Jogos
                            Massivos Online de Interpretação de Papéis);
                        </p>
                    </div>
                </div>

                <div class="txt-topico">
                    <div class="ico-nm">
                        <span><i class="fa-solid fa-cube"></i></span>
                        <h4>LARP (Live Action Role-Playing)</h4>
                    </div>
                    <div class="desc-topico">
                        <p>
                            Nesses jogos, os participantes encenam fisicamente as ações dos seus personagens.
                        </p>
                    </div>
                </div>
            </div>

            <div class="btns-como">
                <a href="cm-jogador.php">
                    <button><i class="fas fa-shield-halved"></i> Como Jogador</button>
                </a>
                <a href="cm-mestre.php">
                    <button><i class="fas fa-hat-wizard"></i> Como Mestre</button>
                </a>
            </div>
        </section>

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
                    <li><a href="#">Como Jogar</a></li>
                    <li><a href="#">Personagens</a></li>
                    <li><a href="#">Mundos</a></li>
                    <li><a href="#">Dados</a></li>
                    <li><a href="#">Sobre nós</a></li>
                </ul>
            </div>
            <div class="rodape-links">
                <h4>Jogar</h4>
                <ul>
                    <li><a href="#">Campanhas</a></li>
                    <li><a href="#">Como Player</a></li>
                    <li><a href="#">Como Mestre</a></li>
                    <li><a href="#">Outros</a></li>
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

