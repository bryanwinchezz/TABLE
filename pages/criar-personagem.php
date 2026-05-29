<?php
/**
 *  Após a página de login definir a sessão com os dados do usuario a página index lê a sessão e inicia a mesma
 *  Na navbar temos um if e else para cado o usuario esteja conectado ou não, mudando sendo que: 
 *  SE o usuário estiver logado irá mostrar a foto e o nome do usuário
 */
session_start();

// Redireciona para login se não estiver logado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../app/config/database.php';
$pdo = Database::getConexao();

$fotoNavbar = (!empty($_SESSION['usuario']['foto']) && file_exists(dirname(__DIR__) . '/' . ltrim(str_replace('../', '', $_SESSION['usuario']['foto']), '/'))) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar.png';

// Buscar todos os sistemas disponíveis (Oficiais, Criados pelo usuário e Vinculados)
$stmt = $pdo->prepare("
    SELECT DISTINCT s.id_sistema, s.nm_sistema, s.ds_imagem, s.ds_descricao 
    FROM tb_sistema s
    LEFT JOIN tb_usuario u ON s.id_usuario_criador = u.id_usuario
    LEFT JOIN tb_usuario_sistema us ON s.id_sistema = us.id_sistema
    WHERE s.id_usuario_criador IS NULL 
       OR s.id_usuario_criador = ? 
       OR u.tp_cargo = 'admin'
       OR us.id_usuario = ?
    ORDER BY s.nm_sistema ASC
");
$stmt->execute([$_SESSION['usuario']['id'], $_SESSION['usuario']['id']]);
$sistemas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Criar Personagem</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">

    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/criar-personagem.css?v=2.4">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Estilos Base */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #311c61;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
        }


    </style>
</head>

<body id="body-criar-personagem">

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
                <li><a href="cm-jogar.php">Como Jogar</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>"
                        class="ativo">Personagens</a>
                </li>
                <li><a href="<?= isset($_SESSION['usuario']['cargo']) && in_array(strtolower($_SESSION['usuario']['cargo']), ['mestre','admin']) ? 'criar-mapa.php' : 'editar-perfil.php?abrir_mestre=1'; ?>">Mundos</a></li>
                <li><a href="rolagem-de-dados.php">Dados</a></li>
                <li><a href="sobre-nos.php">Sobre Nós</a></li>
            </ul>

            <!-- BOTÕES MOBILE -->
            <div class="nav-mobile-footer">
                <?php if (isset($_SESSION['usuario'])): ?>
                    <div class="usuario-logado-nav" onclick="window.location.href='perfil.php'">
                        <img src="<?= htmlspecialchars($fotoNavbar) ?>"
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
                <img src="<?= htmlspecialchars($fotoNavbar) ?>"
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

    <main class="container-sistema">

        <nav class="menu-abas" id="menu-criacao">
            <div class="indicador-aba"></div>
            <button type="button" class="aba ativa" data-index="0" data-alvo="aba-sistema">Sistema</button>
            <button type="button" class="aba" data-index="1" data-alvo="aba-descricao">Descrição</button>
            <button type="button" class="aba" data-index="2" data-alvo="aba-origem">Origem</button>
            <button type="button" class="aba" data-index="3" data-alvo="aba-atributos">Atributos</button>
            <button type="button" class="aba" data-index="4" data-alvo="aba-classe">Classe</button>
        </nav>

        <form id="form-cria-pers" action="" method="post">
            <input type="hidden" name="id_sistema" id="input-sistema" value="">
            <input type="hidden" name="origemEscolhida" id="input-origem" value="">
            <input type="hidden" name="classeEscolhida" id="input-classe" value="">
            <input type="hidden" name="invite_token" id="input-invite-token" value="<?= htmlspecialchars($_GET['invite_token'] ?? '') ?>">

            <!-- ABA 0: SELEÇÃO DE SISTEMA -->
            <div id="aba-sistema" class="conteudo-aba ativa">
                <section class="desc">
                    <h1>0. SISTEMA DE RPG</h1>
                    <p>Antes de começar, selecione em qual universo seu personagem irá se aventurar. As opções de origem, atributos e classes serão adaptadas conforme o sistema escolhido.</p>
                </section>

                <div class="sistemas-grid-selecao">
                    <?php foreach ($sistemas as $sis): ?>
                        <div class="card-sistema-selecao" data-id="<?= $sis['id_sistema'] ?>">
                            <div class="sistema-img-wrapper">
                                <img src="<?= !empty($sis['ds_imagem']) ? $sis['ds_imagem'] : '../img/logo_icone.png' ?>" alt="<?= htmlspecialchars($sis['nm_sistema']) ?>">
                            </div>
                            <div class="sistema-info">
                                <h3><?= htmlspecialchars($sis['nm_sistema']) ?></h3>
                                <p><?= htmlspecialchars($sis['ds_descricao'] ?: 'Sem descrição disponível.') ?></p>
                            </div>
                            <button type="button" class="btn-selecionar-sistema">Selecionar</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- NAVEGAÇÃO MOBILE -->
                <div class="nav-abas-mobile">
                    <button type="button" class="btn-nav-aba prox" onclick="mudarAba(1)">Próximo: Descrição <i class="fas fa-arrow-right"></i></button>
                </div>

                <div class="botoes-nav-form apenas-proximo">
                    <button type="button" class="btn-form-nav btn-proximo-aba">Próximo <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div id="aba-descricao" class="conteudo-aba">
                <section class="desc">
                    <h1>1. DESCRIÇÃO</h1>
                    <p>Todo herói precisa de uma identidade. Antes de definirmos suas habilidades, escolha o nome,
                        detalhe a aparência e aprofunde a história que conectará seu personagem ao mundo ao seu redor.
                    </p>
                </section>

                <div class="area-inputs-grid">
                    <div class="grupo-form">
                        <label>Nome do Personagem:</label>
                        <input type="text" id="nome" class="input-escuro" name="nome"
                            placeholder="Ex: Arthur Pendragon...">
                    </div>
                    <div class="grupo-form">
                        <label>Nome do Jogador:</label>
                        <input type="text" id="nome-jogador" class="input-escuro" name="nome-jogador"
                            value="<?= htmlspecialchars($_SESSION['usuario']['nome']) ?>" placeholder="Seu nome...">
                    </div>
                </div>

                <div class="caracteristicas-grid">
                    <div class="grupo-form">
                        <label>Aparência</label>
                        <textarea name="aparencia" class="textarea-escuro"
                            placeholder="Gênero, Descrição física, Idade, Cicatrizes..."></textarea>
                    </div>
                    <div class="grupo-form">
                        <label>Personalidade</label>
                        <textarea name="personalidade" class="textarea-escuro"
                            placeholder="Traços de personalidade, motivações, medos..."></textarea>
                    </div>
                    <div class="grupo-form">
                        <label>História</label>
                        <textarea name="historia" class="textarea-escuro"
                            placeholder="História do personagem, eventos importantes, relações com a família..."></textarea>
                    </div>
                    <div class="grupo-form">
                        <label>Objetivos</label>
                        <textarea name="objetivos" class="textarea-escuro"
                            placeholder="Metas e desejos principais da sua jornada..."></textarea>
                    </div>
                </div>

                <!-- NAVEGAÇÃO MOBILE -->
                <div class="nav-abas-mobile">
                    <button type="button" class="btn-nav-aba ant" onclick="mudarAba(0)"><i class="fas fa-arrow-left"></i> Anterior</button>
                    <button type="button" class="btn-nav-aba prox" onclick="mudarAba(2)">Próximo: Origem <i class="fas fa-arrow-right"></i></button>
                </div>

                <div class="botoes-nav-form apenas-proximo">
                    <button type="button" class="btn-form-nav btn-proximo-aba">Próximo <i
                            class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div id="aba-origem" class="conteudo-aba">
                <section class="desc">
                    <h1>2. ORIGEM</h1>
                    <p>Com a identidade definida, de onde vocêê veio? A origem dita o seu passado, oferecendo vantagens
                        úúnicas e ganchos narrativos. Escolha a que melhor se alinha com a sua história.</p>
                </section>

                <div class="origens-container" id="container-origens-dinamico">
                    <p style="text-align: center; opacity: 0.5; padding: 40px;">Selecione um sistema primeiro.</p>
                </div>

                <!-- NAVEGAÇÃO MOBILE -->
                <div class="nav-abas-mobile">
                    <button type="button" class="btn-nav-aba ant" onclick="mudarAba(1)"><i class="fas fa-arrow-left"></i> Anterior</button>
                    <button type="button" class="btn-nav-aba prox" onclick="mudarAba(3)">Próximo: Atributos <i class="fas fa-arrow-right"></i></button>
                </div>

                <div class="botoes-nav-form">
                    <button type="button" class="btn-form-nav btn-voltar-aba"><i class="fas fa-arrow-left"></i>
                        Voltar</button>
                    <button type="button" class="btn-form-nav btn-proximo-aba">Próximo <i
                            class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div id="aba-atributos" class="conteudo-aba">
                <section class="desc">
                    <h1>3. ATRIBUTOS</h1>
                    <p>Seu passado moldou suas capacidades naturais. Todos começam com 5 pontos básicos de um humano
                        médio. Vocêê possui <strong>10 pontos extras</strong> para distribuir como quiser. Valores que
                        passam de 13 são excepcionais.</p>

                    <div class="pontos-disponiveis">
                        Pontos Disponíveis: <span id="pontos-restantes">10</span>
                    </div>
                </section>

                <div class="atributos-section" id="container-atributos-dinamico">
                    <p style="text-align: center; opacity: 0.5; padding: 40px;">Selecione um sistema primeiro.</p>
                </div>

                <!-- NAVEGAÇÃO MOBILE -->
                <div class="nav-abas-mobile">
                    <button type="button" class="btn-nav-aba ant" onclick="mudarAba(2)"><i class="fas fa-arrow-left"></i> Anterior</button>
                    <button type="button" class="btn-nav-aba prox" onclick="mudarAba(4)">Próximo: Classe <i class="fas fa-arrow-right"></i></button>
                </div>

                <div class="botoes-nav-form">
                    <button type="button" class="btn-form-nav btn-voltar-aba"><i class="fas fa-arrow-left"></i>
                        Voltar</button>
                    <button type="button" class="btn-form-nav btn-proximo-aba">Próximo <i
                            class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div id="aba-classe" class="conteudo-aba">
                <section class="desc">
                    <h1>4. CLASSE</h1>
                    <p>Para finalizar, o seu treinamento dita o seu futuro. A Classe determina suas habilidades de
                        combate, perícias técúnicas e o papel definitivo que vocêê exercerá dentro do grupo.</p>
                </section>

                <div class="tri-class" id="container-classes-dinamico">
                    <p style="text-align: center; opacity: 0.5; padding: 40px;">Selecione um sistema primeiro.</p>
                </div>

                <!-- NAVEGAÇÃO MOBILE -->
                <div class="nav-abas-mobile">
                    <button type="button" class="btn-nav-aba ant" onclick="mudarAba(3)"><i class="fas fa-arrow-left"></i> Anterior</button>
                </div>

                <div class="botoes-nav-form">
                    <button type="button" class="btn-form-nav btn-voltar-aba"><i class="fas fa-arrow-left"></i>
                        Voltar</button>
                    <button type="button" class="btn-concluir">Salvar Personagem <i class="fas fa-check"></i></button>
                </div>
            </div>

        </form>
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
                    <li><a href="cm-jogar.php">Como Jogar</a></li>
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
        </div>
        <div class="rodape-inferior">
            <p>©© 2026 TABLE. Todos os direitos reservados.</p>
            <div class="redes-sociais">
                <a href="#"><i class="fa-brands fa-discord"></i></a>
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <script src="../js/nav-global.js" defer></script>
    <script src="../js/criar-personagem.js?v=2.5" defer></script>

</body>

</html>


