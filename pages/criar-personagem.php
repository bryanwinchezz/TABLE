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

// Buscar se o usuário possui API Key do Gemini configurada
$temApiKey = 'false';
try {
    $stmtKey = $pdo->prepare("SELECT ds_api_key_gemini FROM tb_usuario WHERE id_usuario = ? LIMIT 1");
    $stmtKey->execute([$_SESSION['usuario']['id']]);
    $userPlan = $stmtKey->fetch();
    $temApiKey = !empty($userPlan['ds_api_key_gemini']) ? 'true' : 'false';
} catch (Exception $e) {
    $temApiKey = 'false';
}

$fotoNavbar = (!empty($_SESSION['usuario']['foto']) && file_exists(dirname(__DIR__) . '/' . ltrim(str_replace('../', '', $_SESSION['usuario']['foto']), '/'))) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png';

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
    <link rel="shortcut icon" href="../img/logo_branco1.png" type="image/x-icon">

    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/criar-personagem.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script src="../js/cropper-helper.js?v=<?= time() ?>"></script>
    <link rel="stylesheet" href="../css/table-modal.css">
    <script src="../js/table-modal.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script>
        const TEM_API_KEY = <?= $temApiKey ?>;
    </script>
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

        /* Animações Premium para os Modais da CassIA */
        @keyframes spinIa {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes overlayFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-15px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        .modal-overlay {
            animation: overlayFadeIn 0.2s ease-out forwards;
        }
        .modal-box {
            animation: modalFadeIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        /* UPLOAD DE IMAGEM DO PERSONAGEM ULTRA PREMIUM E ESTILIZADO */
        .area-imagem {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            flex-shrink: 0;
            margin-right: 25px;
        }
        .caixa-imagem {
            width: 140px;
            height: 140px;
            border-radius: 18px !important;
            border: 3px solid #7b4ff7;
            background-color: rgba(0,0,0,0.4) !important;
            position: relative;
            cursor: pointer;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 25px rgba(123, 79, 247, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-size: cover;
            background-position: center;
        }
        .caixa-imagem:hover {
            transform: scale(1.05) translateY(-2px);
            border-color: #9d7aff;
            box-shadow: 0 0 35px rgba(123, 79, 247, 0.8);
        }
        .caixa-imagem::after {
            content: "\f030";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(123, 79, 247, 0.7);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            backdrop-filter: blur(2px);
        }
        .caixa-imagem:hover::after {
            opacity: 1;
        }
        .btn-contorno-premium {
            background: transparent;
            border: 2px solid #7b4ff7;
            color: #fff;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(123, 79, 247, 0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            outline: none;
        }
        .btn-contorno-premium:hover {
            background: #7b4ff7;
            box-shadow: 0 4px 15px rgba(123, 79, 247, 0.5);
            transform: translateY(-1px);
        }
    </style>
</head>

<body id="body-criar-personagem">

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
            <input type="hidden" name="imagem_base64" id="input-imagem-base64" value="">

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
                                <img src="<?= !empty($sis['ds_imagem']) ? $sis['ds_imagem'] : '../img/logo_branco1.png' ?>" alt="<?= htmlspecialchars($sis['nm_sistema']) ?>">
                            </div>
                            <div class="sistema-info">
                                <h3><?= htmlspecialchars($sis['nm_sistema']) ?></h3>
                                <?php
                                $descExibir = 'Sem descrição disponível.';
                                if (!empty($sis['ds_descricao'])) {
                                    $blocos = preg_split('/(\r\n){2,}|\n{2,}/', $sis['ds_descricao']);
                                    if (count($blocos) > 0) {
                                        $primeiroBloco = trim($blocos[0]);
                                        $linhas = preg_split('/(\r\n)+|\n+/', $primeiroBloco);
                                        if (count($linhas) > 1) {
                                            array_shift($linhas);
                                            $descExibir = trim(implode("\n", $linhas));
                                        } else {
                                            $descExibir = $primeiroBloco;
                                        }
                                    }
                                }
                                ?>
                                <p><?= htmlspecialchars($descExibir) ?></p>
                            </div>
                            <button type="button" class="btn-selecionar-sistema">Selecionar</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="botoes-nav-form apenas-proximo">
                    <button type="button" class="btn-form-nav btn-proximo-aba">Próximo <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div id="aba-descricao" class="conteudo-aba">
                <section class="desc">
                    <h1>1. DESCRIÇÃO</h1>
                    <p>Todo herói precisa de uma identidade. Antes de definirmos suas habilidades, escolha o nome, detalhe a aparência e aprofunde a história que conectará seu personagem ao mundo ao seu redor.</p>
                </section>

                <div class="ia-geracao-container" style="background: linear-gradient(135deg, rgba(123, 79, 247, 0.15), rgba(74, 42, 133, 0.15)); border: 1px solid rgba(123, 79, 247, 0.3); border-radius: 12px; padding: 20px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; gap: 20px;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 5px 0; color: #fff; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-wand-magic-sparkles" style="color: #7b4ff7;"></i> Criar Personagem com CassIA
                        </h4>
                        <p style="margin: 0; color: #aaa; font-size: 0.85rem; line-height: 1.4;" id="ia-personagem-msg">
                            Escreva o conceito do seu personagem e a CassIA irá distribuir seus atributos, história, objetivos e escolher a classe/origem de acordo com o RPG selecionado na aba anterior.
                        </p>
                    </div>
                    <button type="button" id="btn-ia-personagem" style="background: linear-gradient(135deg, #7b4ff7, #4a2a85); color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(123, 79, 247, 0.4);">
                        <i class="fas fa-wand-magic-sparkles"></i> Gerar com CassIA
                    </button>
                </div>

                <section class="secao-topo">
                    <div class="area-imagem">
                        <div class="caixa-imagem" id="preview-imagem" style="background-image: url('../img/uploads/perfil/avatar1.png'); cursor: pointer;" title="Clique para escolher a foto">
                        </div>
                        <p class="dica-imagem">Recomendado: Imagem quadrada (Avatar)</p>
                        <input type="file" id="input-foto-personagem" accept="image/*" hidden>
                    </div>

                    <div class="area-inputs" style="flex: 2; display: flex; flex-direction: column; gap: 20px;">
                        <div class="grupo-form">
                            <label>Nome do Personagem:</label>
                            <input type="text" id="nome" class="input-escuro" name="nome" placeholder="Ex: Arthur Pendragon...">
                        </div>
                        <div class="grupo-form">
                            <label>Nome do Jogador:</label>
                            <input type="text" id="nome-jogador" class="input-escuro" name="nome-jogador" value="<?= htmlspecialchars($_SESSION['usuario']['nome']) ?>" placeholder="Seu nome...">
                        </div>
                    </div>
                </section>

                <div class="caracteristicas-grid">
                    <div class="grupo-form">
                        <label>Aparência</label>
                        <textarea name="aparencia" id="input-aparencia" class="textarea-escuro" placeholder="Gênero, Descrição física, Idade, Cicatrizes..."></textarea>
                    </div>
                    <div class="grupo-form">
                        <label>Personalidade</label>
                        <textarea name="personalidade" id="input-personalidade" class="textarea-escuro" placeholder="Traços de personalidade, motivações, medos..."></textarea>
                    </div>
                    <div class="grupo-form">
                        <label>História</label>
                        <textarea name="historia" id="input-historia" class="textarea-escuro" placeholder="História do personagem, eventos importantes, relações com a família..."></textarea>
                    </div>
                    <div class="grupo-form">
                        <label>Objetivos</label>
                        <textarea name="objetivos" id="input-objetivos" class="textarea-escuro" placeholder="Metas e desejos principais da sua jornada..."></textarea>
                    </div>
                </div>

                <div class="botoes-nav-form">
                    <button type="button" class="btn-form-nav btn-voltar-aba"><i class="fas fa-arrow-left"></i>
                        Voltar</button>
                    <button type="button" class="btn-form-nav btn-proximo-aba">Próximo <i
                            class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div id="aba-origem" class="conteudo-aba">
                <section class="desc">
                    <h1>2. ORIGEM</h1>
                    <p>Com a identidade definida, de onde você veio? A origem dita o seu passado, oferecendo vantagens
                        únicas e ganchos narrativos. Escolha a que melhor se alinha com a sua história.</p>
                </section>

                <div class="origens-container" id="container-origens-dinamico">
                    <p style="text-align: center; opacity: 0.5; padding: 40px;">Selecione um sistema primeiro.</p>
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
                        médio. Você possui <strong>10 pontos extras</strong> para distribuir como quiser. Valores que
                        passam de 13 são excepcionais.</p>

                    <div class="pontos-disponiveis">
                        Pontos Disponíveis: <span id="pontos-restantes">10</span>
                    </div>
                </section>

                <div class="atributos-section" id="container-atributos-dinamico">
                    <p style="text-align: center; opacity: 0.5; padding: 40px;">Selecione um sistema primeiro.</p>
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
                        combate, perícias técnicas e o papel definitivo que você exercerá dentro do grupo.</p>
                </section>

                <div class="tri-class" id="container-classes-dinamico">
                    <p style="text-align: center; opacity: 0.5; padding: 40px;">Selecione um sistema primeiro.</p>
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
        <div class="rodape-inferior">
            <p>© 2026 TABLE. Todos os direitos reservados.</p>
            <div class="redes-sociais">
                <a href="#"><i class="fa-brands fa-discord"></i></a>
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <!-- MODAL DE CANALIZAÇÃO DE IA (PERSONAGEM) -->
    <div class="modal-overlay" id="modal-ia-personagem" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; justify-content:center; align-items:center; backdrop-filter: blur(5px);">
        <div class="modal-box" style="max-width:550px; background:#1e1b26; border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6); border-radius: 16px; padding: 35px; font-family: 'Montserrat', sans-serif; position: relative;">
            <i class="fas fa-times modal-close" onclick="document.getElementById('modal-ia-personagem').style.display='none'" style="position:absolute; right:24px; top:24px; color:#aaa; cursor:pointer; font-size:1.3rem; transition: color 0.2s, transform 0.2s;" onmouseover="this.style.color='#7b4ff7'; this.style.transform='scale(1.15)'" onmouseout="this.style.color='#aaa'; this.style.transform='none'"></i>
            
            <!-- Conteúdo de Input -->
            <div id="ia-input-container-personagem">
                <div style="text-align: center; margin-top: 15px; margin-bottom: 30px;">
                    <h2 style="color: #fff; font-size: 1.8rem; font-weight: 900; letter-spacing: -1px; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <i class="fas fa-wand-magic-sparkles" style="color: #7b4ff7;"></i> CONVOCAR PERSONAGEM
                    </h2>
                    <p style="color: #aaa; font-size: 0.9rem;">Dê vida ao seu herói através de inteligência artificial</p>
                </div>

                <div class="grupo-form-painel" style="margin-bottom: 25px;">
                    <label style="color:#fff; font-weight:700; font-size:0.9rem; display:block; margin-bottom:10px;">Descreva o conceito do seu personagem:</label>
                    <textarea id="ia-personagem-conceito" class="input-painel" style="height: 120px; resize: none; width: 100%; box-sizing: border-box; background: rgba(0,0,0,0.3); border: 1px solid rgba(123,79,247,0.3); border-radius: 8px; color: #fff; padding: 12px; font-family: inherit; font-size: 0.95rem; line-height: 1.5; outline: none; transition: border-color 0.2s;" placeholder="Ex: Um guerreiro veterano cansado das batalhas, que usa uma armadura rústica e busca redimir seu passado protegendo os fracos..."></textarea>
                </div>

                <button type="button" onclick="executarCanalizacaoPersonagem()" style="width: 100%; background: linear-gradient(135deg, #7b4ff7, #4a2a85); color: #fff; border: none; padding: 15px; border-radius: 8px; font-weight: 700; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 4px 15px rgba(123, 79, 247, 0.4); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <i class="fas fa-wand-magic-sparkles"></i> CANALIZAR PERSONAGEM
                </button>
            </div>

            <!-- Conteúdo de Carregamento (Loading) -->
            <div id="ia-loading-container-personagem" style="display: none; text-align: center; padding: 20px 0;">
                <div class="ia-loading-spinner" style="width: 60px; height: 60px; border: 4px solid rgba(123, 79, 247, 0.1); border-left-color: #7b4ff7; border-radius: 50%; margin: 0 auto 20px auto; animation: spinIa 1s linear infinite;"></div>
                <h3 style="color: #fff; font-size: 1.3rem; font-weight: 700; margin-bottom: 5px;">CassIA está forjando seu personagem...</h3>
                <p style="color: #888; font-size: 0.85rem; margin-bottom: 15px; font-weight: 500;">Tempo médio de espera: 1min a 1min30s</p>
                <p id="ia-loading-frase-personagem" style="color: #aaa; font-size: 0.95rem; font-style: italic; min-height: 24px;">Tecendo as regras da reality...</p>
            </div>
        </div>
    </div>

    <script src="../js/nav-global.js" defer></script>
    <script src="../js/criar-personagem.js?v=<?= time() ?>" defer></script>

</body>

</html>
