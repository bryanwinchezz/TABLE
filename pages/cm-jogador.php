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
    <title>TABLE | Jogador</title>
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">

    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/pgs-como.css?v=2">

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
                <li><a href="<?= isset($_SESSION['usuario']['cargo']) && in_array(strtolower($_SESSION['usuario']['cargo']), ['mestre','admin']) ? 'criar-mapa.php' : 'editar-perfil.php?abrir_mestre=1'; ?>">Mundos</a></li>
                <li><a href="rolagem-de-dados.php">Dados</a></li>
                <li><a href="sobre-nos.php">Sobre Nós</a></li>
            </ul>

            <!-- BOTÕES MOBILE -->
            <div class="nav-mobile-footer">
                <?php if (isset($_SESSION['usuario'])): ?>
                    <div class="usuario-logado-nav" onclick="window.location.href='perfil.php'">
                        <img src="<?= !empty($_SESSION['usuario']['foto']) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar.png' ?>"
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
                <img src="<?= !empty($_SESSION['usuario']['foto']) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar.png' ?>"
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
            <img src="../img/cm-jogador.png" alt="Um player jogando RPG" class="img-comojogar">
            <div class="desc-comojogar">
                <h1>Como Jogador</h1>
                <p>Ser Player em um RPG é mergulhar em um universo de imaginação e interpretação, vivendo aventuras
                    através de um personagem que você mesmo cria. Seu papel é dar voz, atitude e decisões a esse
                    personagem dentro da narrativa proposta pelo Mestre. Cada escolha sua impacta o rumo da história,
                    que se desenvolve coletivamente com os demais jogadores.</p>
            </div>
        </section>

        <section class="expli-jogador">
            <div class="txt-jogador">
                <h1>O que significa ser um jogador</h1>
                <br>
                <p>
                    Como jogador, você tem a oportunidade de criar uma identidade única dentro do jogo. Seu personagem
                    pode ser um herói, um mercenário, um vilão, ou qualquer figura que faça sentido no cenário. A graça
                    está em viver o personagem: agir, falar e pensar como ele faria. Isso torna a experiência envolvente
                    e dá vida à narrativa.
                </p>
            </div>
        </section>

        <section class="componentes">
            <h1>Principais Responsabilidades do Jogador</h1>

            <div class="lista-comp">
                <div class="item-comp">
                    <h3>Criação do Personagens </h3>
                    <p>
                        O primeiro passo é desenvolver quem será seu avatar dentro do jogo. Isso inclui definir sua
                        história, personalidade, atributos, habilidades, medos e até fraquezas. A profundidade do
                        personagem enriquece a experiência coletiva.
                    </p>
                </div>

                <div class="item-comp">
                    <h3>Interpretação</h3>
                    <p>
                        Durante o jogo, você deve reagir às situações da forma como o personagem faria. Isso envolve
                        encenar diálogos, tomar decisões difíceis, demonstrar emoções e interagir com o mundo narrado
                        pelo Mestre.
                    </p>
                </div>

                <div class="item-comp">
                    <h3>Uso da Ficha</h3>
                    <p>
                        A ficha do personagem é seu guia. Ela contém dados como força, inteligência, destrezas,
                        habilidades especiais, equipamentos, pontos de vida, etc. Consultar a ficha durante o jogo
                        garante que suas ações estejam de acordo com as regras e limites estabelecidos.
                    </p>
                </div>

                <div class="item-comp">
                    <h3>Participação Ativa</h3>
                    <p>
                        Além de controlar seu personagem, você deve colaborar para que a narrativa avance. Isso
                        significa ouvir os outros jogadores, respeitar o turno de fala e contribuir para a história
                        coletiva.
                    </p>
                </div>

                <div class="item-comp">
                    <h3>Trabalho em Equipe </h3>
                    <p>
                        Na maioria dos RPGs, os personagens enfrentam desafios em grupo. Saber cooperar, dividir funções
                        e respeitar os papéis de cada um é essencial para que a aventura seja divertida e equilibrada.
                    </p>
                </div>
            </div>
        </section>

        <section class="expli-rpg">
            <div class="txt-expli">
                <h1>Experiência como Jogador</h1>
                <p>
                    O foco não está em “vencer” o Mestre ou os outros jogadores, mas em contar uma história em conjunto.
                </p>
                <br>
                <p>
                    O prazer está em ver o desenrolar das situações, explorar o cenário, interagir com personagens e
                    enfrentar dilemas. Muitas vezes, as falhas ou derrotas podem ser até mais interessantes que as
                    vitórias, pois geram momentos memoráveis.
                </p>
                <br>
                <p>
                    Em resumo: ser Player é dar vida a um personagem e contribuir com criatividade, interpretação e
                    escolhas para que o grupo viva uma história única.
                </p>
            </div>
            <img src="../img/ex-cm-jogador.png" alt="Uma mesa de RPG com pessoas jogando." class="img-expli-rpg">
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


