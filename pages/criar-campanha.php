<?php /* ATUALIZADO EM 22/04/2026 - ESCUDO PREMIUM OK */
/**
 *  Após a página de login definir a sessão com os dados do usuario a página index lê a sessão e inicia a mesma
 *  Na navbar temos um if e else para cado o usuario esteja conectado ou não, mudando sendo que: 
 *  SE o usuário estiver logado irá mostrar a foto e o nome do usuário
 */
session_start();

// Redireciona para login se não estiver logado
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../app/config/database.php';
$pdo = Database::getConexao();

// Buscar todos os sistemas disponíveis
$stmt = $pdo->query("SELECT id_sistema, nm_sistema FROM tb_sistema ORDER BY nm_sistema ASC");
$sistemas = $stmt->fetchAll();

// Buscar dados de uma campanha específica se houver ID
$campanhaDados = null;
$PersonagemsCampanha = [];
$combatesCampanha = [];

$id_campanha = $_GET['id'] ?? null;
if ($id_campanha) {
    $stmt = $pdo->prepare("
            SELECT c.*, s.nm_sistema 
            FROM tb_campanha c
            LEFT JOIN tb_sistema s ON c.id_sistema = s.id_sistema
            WHERE c.id_campanha = ?
        ");
    $stmt->execute([$id_campanha]);
    $campanhaDados = $stmt->fetch();

    if ($campanhaDados) {
        // Buscar Personagems da Campanha
        $stmt = $pdo->prepare("
                SELECT p.*, s.nm_sistema, cl.nm_classe, o.nm_origem
                FROM tb_campanha_personagem cp
                JOIN tb_personagem p ON cp.id_personagem = p.id_personagem
                LEFT JOIN tb_sistema s ON p.id_sistema = s.id_sistema
                LEFT JOIN tb_personagem_classe pc ON p.id_personagem = pc.id_personagem
                LEFT JOIN tb_classe cl ON pc.id_classe = cl.id_classe
                LEFT JOIN tb_personagem_origem po ON p.id_personagem = po.id_personagem
                LEFT JOIN tb_origem o ON po.id_origem = o.id_origem
                WHERE cp.id_campanha = ?
            ");
        $stmt->execute([$id_campanha]);
        $PersonagemsCampanha = $stmt->fetchAll();

        // Buscar Atributos de cada Personagem
        foreach ($PersonagemsCampanha as &$Personagem) {
            $stmtAttr = $pdo->prepare("
                    SELECT a.nm_atributo, a.ds_abreviacao, pa.qt_valor
                    FROM tb_personagem_atributo pa
                    JOIN tb_atributo a ON pa.id_atributo = a.id_atributo
                    WHERE pa.id_personagem = ?
                ");
            $stmtAttr->execute([$Personagem['id_personagem']]);
            $Personagem['atributos'] = $stmtAttr->fetchAll();
        }

        // Buscar Atributos do Sistema (para o layout do pentágono)
        $stmtSisAttr = $pdo->prepare("SELECT * FROM tb_atributo WHERE id_sistema = ? ORDER BY id_atributo ASC");
        $stmtSisAttr->execute([$campanhaDados['id_sistema']]);
        $atributosSistema = $stmtSisAttr->fetchAll();

        // Buscar Combates da Campanha
        $stmt = $pdo->prepare("
                SELECT c.*, (SELECT SUM(m.qt_vida) FROM tb_monstro m JOIN tb_combate_monstro cm ON m.id_monstro = cm.id_monstro WHERE cm.id_combate = c.id_combate) as vd_total
                FROM tb_combate c WHERE c.id_campanha = ?
            ");
        $stmt->execute([$id_campanha]);
        $combatesCampanha = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Criar Campanha</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">

    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/criar-campanha.css?v=2.2">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* VITRINE DE SISTEMA - PREMIUM CSS */
        .sistema-showcase {
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: slideDown 0.4s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .system-hero {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            align-items: center;
        }

        .system-img {
            width: 120px;
            height: 120px;
            background: #222;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--cor-destaque-claro);
        }

        .system-text h2 {
            font-size: 1.8rem;
            color: var(--cor-destaque-claro);
            margin-bottom: 8px;
        }

        .system-text p {
            font-size: 0.95rem;
            color: #ccc;
            line-height: 1.4;
        }

        .system-board {
            display: none;
        }

        .btn-criar-campanha:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* DADOS - ESTILOS E ANIMAÇÃO */
        .item-dado {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dado-icon-container {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }

        .img-dado {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.5));
        }

        .item-dado:hover .dado-icon-container {
            transform: scale(1.15) rotate(5deg);
        }

        .dado-girando {
            animation: girarDado 0.6s ease-in-out;
        }

        @keyframes girarDado {
            0% { transform: rotate(0deg) scale(1); }
            25% { transform: rotate(90deg) scale(1.3) translateY(-5px); }
            50% { transform: rotate(180deg) scale(1); }
            75% { transform: rotate(270deg) scale(1.3) translateY(-5px); }
            100% { transform: rotate(360deg) scale(1); }
        }

        /* AJUSTE DE COR DO NÚMERO NO HISTÓRICO */
        .hexa-dado {
            color: #000 !important;
            font-weight: 800;
        }

        /* MODAL DE DADOS PROFISSIONAL */
        .popup-dados {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            width: 90% !important;
            max-width: 380px !important;
            background: #110e1a !important;
            border: 2px solid var(--premium-accent) !important;
            border-radius: 24px !important;
            box-shadow: 0 20px 60px rgba(0,0,0,0.9) !important;
            z-index: 10001 !important;
            padding: 35px !important;
            animation: modalPop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes modalPop {
            from { opacity: 0; transform: translate(-50%, -40%) scale(0.9); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }

        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            z-index: 10000;
            display: none;
        }

        .btn-confirmar-rolagem {
            width: 100%;
            background: var(--premium-accent);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 700;
            margin-top: 20px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-confirmar-rolagem:hover {
            filter: brightness(1.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--premium-accent);
        }

        /* CARD DE AMEAÇA PREMIUM (SYNC COM SISTEMA) */
        .card-ameaca-premium {
            background: linear-gradient(90deg, rgba(30, 10, 10, 0.9) 0%, rgba(60, 20, 20, 0.4) 100%);
            border: 1px solid rgba(255, 50, 50, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            padding: 10px;
            gap: 15px;
            margin-bottom: 12px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            text-align: left;
        }

        .card-ameaca-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #ff3232;
            box-shadow: 0 0 10px #ff3232;
        }

        .card-ameaca-img {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: #111;
        }

        .card-ameaca-body {
            flex: 1;
        }

        .card-ameaca-body h4 {
            color: #fff;
            font-weight: 800;
            font-size: 1rem;
            margin-bottom: 2px;
        }

        .card-ameaca-details {
            display: flex;
            flex-direction: column;
        }

        .card-ameaca-details span {
            font-size: 0.75rem;
            color: #ccc;
            font-weight: 600;
        }

        .card-ameaca-details b {
            color: #ff4d4d;
        }

        .card-ameaca-actions {
            display: flex;
            gap: 8px;
        }

        .btn-card-ficha {
            background: none;
            border: 1px solid #fff;
            color: #fff;
            padding: 5px 12px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            cursor: pointer;
        }

        .btn-card-add {
            background: #cd1d1d;
            border: none;
            color: #fff;
            padding: 5px 12px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            cursor: pointer;
        }

        /* REAJUSTE DE GRID NO COMBATE */
        .lista-ameacas-cards {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
    </style>
</head>


<body class="body-criar-campanha">

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
                <li><a href="rolador-de-dados.php">Dados</a></li>
                <li><a href="sobre-nos.php">Sobre Nós</a></li>
            </ul>
        </nav>
        <?php if (isset($_SESSION['usuario'])): ?>
            <div class="usuario-logado-nav" id="nav-logado" onclick="window.location.href='perfil.php'"
                title="Ir para o Perfil">
                <img src="<?= !empty($_SESSION['usuario']['foto']) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png' ?>"
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

    <main class="main-criar-campanha">
        <div class="conteudo-campanha">

            <!-- TELA 01: FORMULÁRIO DE CRIAÇÃO -->
            <div id="sessao-criar">
                <h1 class="titulo-pagina">Criar Campanha</h1>

                <section class="card-formulario-campanha">
                    <form id="form-criar-campanha">
                        <div class="grupo-form">
                            <label for="selecao-sistema">Sistema de RPG:</label>
                            <select id="selecao-sistema" class="input-campanha"
                                onchange="carregarDetalhesSistema(this.value)">
                                <option value="" disabled selected>Selecione um sistema...</option>
                                <?php foreach ($sistemas as $sis): ?>
                                    <option value="<?= $sis['id_sistema'] ?>"><?= htmlspecialchars($sis['nm_sistema']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- VITRINE DO SISTEMA (SPOTLIGHT) -->
                        <div id="sistema-showcase" class="sistema-showcase escondido">
                            <!-- Injetado via JS -->
                        </div>

                        <div class="grupo-form">
                            <label for="nome-campanha">Nome:</label>
                            <input type="text" id="nome-campanha" class="input-campanha"
                                placeholder="Nome da nova Campanha..." required>
                        </div>

                        <div class="grupo-form">
                            <label for="descricao-campanha">Descrição:</label>
                            <div class="editor-container">
                                <div class="editor-toolbar">
                                    <button type="button" class="toolbar-btn bold" id="btn-bold" title="Negrito"><i
                                            class="fas fa-bold"></i></button>
                                    <button type="button" class="toolbar-btn italic" id="btn-italic" title="Itálico"><i
                                            class="fas fa-italic"></i></button>
                                    <button type="button" class="toolbar-btn underline" id="btn-underline"
                                        title="Sublinhado"><i class="fas fa-underline"></i></button>
                                </div>
                                <div id="descricao-campanha" class="textarea-campanha" contenteditable="true"
                                    placeholder="Descreva sua campanha aqui..."></div>
                            </div>
                        </div>

                        <div class="form-acoes">
                            <a href="#" class="btn-cancelar"><i class="fas fa-times"></i> Cancelar</a>
                            <button type="submit" class="btn-criar-campanha"><i class="fas fa-plus-circle"></i>
                                Criar</button>
                        </div>
                    </form>
                </section>
            </div>

            <!-- TELA 02: DETALHES DA CAMPANHA (PÓS-CRIAÇÃO) -->
            <div id="sessao-detalhes" class="sessao-detalhes">
                <h1 id="display-nome-campanha" class="titulo-campanha-criada">Nome da campanha</h1>

                <!-- Banner de Capa Dinâmico -->
                <div id="banner-campanha-display" class="banner-campanha escondido"></div>

                <!-- Descrição da Campanha -->
                <div class="descricao-campanha-display" id="display-descricao-campanha">
                    <p>Sua campanha aparecerá aqui...</p>
                </div>

                <div class="barra-acoes">
                    <button class="btn-acao" onclick="abrirModal('modal-foto-capa')"><i class="fas fa-image"></i> Foto
                        de Capa</button>
                    <button class="btn-acao" onclick="abrirModalPersonagens()"><i class="fas fa-user-plus"></i> Adicionar
                        Personagem</button>
                    <button class="btn-acao" onclick="abrirModal('modal-link-convite')"><i class="fas fa-link"></i>
                        Convidar Jogadores</button>
                    <button class="btn-acao" onclick="irParaEditar()"><i class="fas fa-edit"></i> Editar
                        Campanha</button>
                    <button class="btn-acao" onclick="irParaCombate()"><i class="fas fa-skull-crossbones"></i> Criar
                        Combate</button>
                    <button class="btn-acao especial" onclick="irParaEscudo()"><i class="fas fa-shield-halved"></i>
                        Escudo do Mestre</button>
                </div>

                <div class="sub-nav-campanha">
                    <a href="javascript:void(0)" class="link-sub-nav ativa" id="aba-personagens"
                        onclick="switchDashboardTab('personagens')">Personagens</a>
                    <a href="javascript:void(0)" class="link-sub-nav" id="aba-combates"
                        onclick="switchDashboardTab('combates')">Combates</a>
                </div>

                <!-- Lista de Personagems -->
                <div class="lista-Personagems" id="lista-Personagems">
                    <?php if (empty($PersonagemsCampanha)): ?>
                        <p style="text-align:center; opacity:0.5; margin-top:20px;">Nenhum personagem na campanha ainda.</p>
                    <?php endif; ?>
                    <?php foreach ($PersonagemsCampanha as $Personagem): ?>
                        <div class="card-Personagem">
                            <div class="avatar-Personagem">
                                <img src="<?= !empty($Personagem['ds_foto']) ? $Personagem['ds_foto'] : '../img/uploads/perfil/avatar1.png' ?>"
                                    alt="Avatar">
                            </div>
                            <div class="info-Personagem">
                                <h3><?= htmlspecialchars($Personagem['nm_personagem']) ?></h3>
                                <p><?= htmlspecialchars($Personagem['nm_sistema'] . ' - ' . $Personagem['nm_classe']) ?></p>
                                <span>Nexus: <?= $Personagem['qt_nivel'] ?>%</span>
                            </div>
                            <button class="btn-ver-ficha"
                                onclick="window.location.href='exibir-ficha.php?id=<?= $Personagem['id_personagem'] ?>'"><i
                                    class="fas fa-eye"></i> Ver Ficha</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Lista de Combates -->
                <div class="lista-combates escondido" id="lista-combates">
                    <?php if (empty($combatesCampanha)): ?>
                        <p style="text-align:center; opacity:0.5; margin-top:20px;">Nenhum combate adicionado ainda.</p>
                    <?php endif; ?>
                    <?php foreach ($combatesCampanha as $combate): ?>
                        <div class="card-combate">
                            <h3><?= htmlspecialchars($combate['nm_combate']) ?></h3>
                            <p>VD: <?= $combate['vd_total'] ?: 0 ?> (vida total)</p>
                            <div class="card-combate-footer">
                                <button class="btn-remover-combate"
                                    onclick="removerCombate(<?= $combate['id_combate'] ?>, this)"><i
                                        class="fas fa-trash"></i> Remover</button>
                                <button class="btn-editar-combate"><i class="fas fa-edit"></i> Editar</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- TELA 03: EDITAR CAMPANHA (IDÊNTICA À CRIAÇÃO) -->
            <div id="sessao-editar" class="sessao-criar escondido sessao-editar-container">
                <h1 class="titulo-pagina">Editar Campanha</h1>

                <section class="card-formulario-campanha">
                    <form id="form-editar-campanha">
                        <div class="grupo-form">
                            <label for="selecao-sistema-edit">Sistema de RPG:</label>
                            <select id="selecao-sistema-edit" class="input-campanha"
                                onchange="carregarDetalhesSistema(this.value, 'sistema-showcase-edit')">
                                <option value="" disabled>Selecione um sistema...</option>
                                <?php foreach ($sistemas as $sis): ?>
                                    <option value="<?= $sis['id_sistema'] ?>"><?= htmlspecialchars($sis['nm_sistema']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- VITRINE DO SISTEMA (SPOTLIGHT) - EDIÇÃO -->
                        <div id="sistema-showcase-edit" class="sistema-showcase escondido">
                            <!-- Injetado via JS -->
                        </div>

                        <div class="grupo-form">
                            <label for="nome-campanha-edit">Nome:</label>
                            <input type="text" id="nome-campanha-edit" class="input-campanha"
                                placeholder="Nome da Campanha..." required>
                        </div>

                        <div class="grupo-form">
                            <label for="descricao-campanha-edit">Descrição:</label>
                            <div class="editor-container">
                                <div class="editor-toolbar">
                                    <button type="button" class="toolbar-btn bold" onclick="document.execCommand('bold', false, null)" title="Negrito"><i
                                            class="fas fa-bold"></i></button>
                                    <button type="button" class="toolbar-btn italic" onclick="document.execCommand('italic', false, null)" title="Itálico"><i
                                            class="fas fa-italic"></i></button>
                                    <button type="button" class="toolbar-btn underline" onclick="document.execCommand('underline', false, null)"
                                        title="Sublinhado"><i class="fas fa-underline"></i></button>
                                </div>
                                <div id="descricao-campanha-edit" class="textarea-campanha" contenteditable="true"
                                    placeholder="Descreva sua campanha aqui..."></div>
                            </div>
                        </div>

                        <div class="form-acoes">
                            <button type="button" class="btn-cancelar" onclick="showSection('sessao-detalhes')"><i class="fas fa-times"></i> Cancelar</button>
                            <button type="button" class="btn-criar-campanha" onclick="salvarEdicao()"><i class="fas fa-save"></i>
                                Salvar Alterações</button>
                        </div>
                    </form>
                </section>
            </div>

            <!-- TELA 04: CRIAR COMBATE -->
            <div id="sessao-combate" class="sessao-combate">
                <div class="combate-header">
                    <div class="combate-titulo-area">
                        <div>
                            <label>Nome do Combate:</label><br>
                            <input type="text" class="input-nome-combate" id="nome-combate-input"
                                placeholder="Nome do novo Combate...">
                        </div>
                        <div class="vd-total-display">
                            VD Total:
                            <span id="vd-total-valor">0</span>
                        </div>
                    </div>
                    <div class="combate-botoes-topo">
                        <button class="btn-combate-sair" onclick="showSection('sessao-detalhes')"><i
                                class="fas fa-times-circle"></i> Sair sem Salvar</button>
                        <button class="btn-combate-salvar" onclick="salvarCombate()"><i class="fas fa-save"></i>
                            Salvar</button>
                    </div>
                </div>

                <div class="combate-grid">
                    <!-- Esquerda: Catálogo -->
                    <div class="catalogo-ameacas">
                        <div class="area-banners-combate">
                            <div class="banners-flex">
                                <div class="banner-card banner-ordem">
                                    <img src="../img/ordem-paranormal-icon.png" alt="Ordem Logo">
                                </div>
                                <div class="banner-card banner-table">
                                    <img src="../img/logo_branco.png" alt="TABLE Logo">
                                    <span>TABLE</span>
                                </div>
                                <div class="banner-card banner-novas">
                                    <span>CRIAR NOVAS CRIATURAS!</span>
                                </div>
                            </div>
                            <p class="banner-subtexto">Conteúdo oficial da TABLE. Veja mais <a href="#">aqui</a> em
                                breve!</p>
                        </div>

                        <div class="lista-ameacas-header">
                            <label>Lista de Ameaças</label>
                            <div class="search-container">
                                <i class="fas fa-search"></i>
                                <input type="text" id="busca-ameaca" placeholder="Buscar..." oninput="renderCatalogo()">
                            </div>
                            <div class="filtros-elemento" id="filtros-ameacas">
                                <button class="btn-filtro ativo"
                                    onclick="filtrarPorElemento('Todos', this)">Todos</button>
                                <button class="btn-filtro"
                                    onclick="filtrarPorElemento('Conhecimento', this)">Conhecimento</button>
                                <button class="btn-filtro" onclick="filtrarPorElemento('Morte', this)">Morte</button>
                                <button class="btn-filtro" onclick="filtrarPorElemento('Sangue', this)">Sangue</button>
                                <button class="btn-filtro" onclick="filtrarPorElemento('Medo', this)">Medo</button>
                                <button class="btn-filtro"
                                    onclick="filtrarPorElemento('Realidade', this)">Realidade</button>
                            </div>
                        </div>

                        <div class="lista-ameacas-cards" id="catalogo-cards">
                            <!-- Cards injetados via JS -->
                        </div>
                    </div>

                    <!-- Direita: Selecionadas -->
                    <div class="ameacas-selecionadas">
                        <h2 class="titulo-ameacas-selecionadas">Ameaças Adicionadas</h2>
                        <div class="lista-ameacas-cards" id="selecionadas-cards">
                            <!-- Cards selecionados aqui -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- TELA 05: ESCUDO DO MESTRE (NOVO) -->
            <div id="sessao-escudo" class="sessao-escudo">
                <div class="escudo-wrapper">
                    <!-- Sidebar de Dados -->
                    <aside class="escudo-sidebar">
                        <h3>Histórico de Dados</h3>
                        <div class="sidebar-dados-lista" id="sidebar-dados-lista">
                            <div class="item-dado">
                                <div class="hexa-dado">11</div>
                                <div class="info-rolagem">
                                    <p>Resultado [11]</p>
                                    <h4>1d20 = 11</h4>
                                </div>
                            </div>
                            <div class="item-dado">
                                <div class="hexa-dado">8</div>
                                <div class="info-rolagem">
                                    <p>Resultado [8]</p>
                                    <h4>1d20 = 8</h4>
                                </div>
                            </div>
                            <!-- Adicionar mais conforme necessário -->
                        </div>
                    </aside>

                    <!-- Conteúdo Principal -->
                    <section class="escudo-principal">
                        <div class="escudo-topo">
                            <h1 id="escudo-titulo-campanha">Nome da Campanha</h1>
                            <div class="escudo-acoes-topo">
                                <button class="btn-escudo-sair" onclick="fecharEscudo()">Sair sem Salvar</button>
                                <button class="btn-escudo-salvar" onclick="fecharEscudo()">Salvar</button>
                            </div>
                        </div>

                        <div class="escudo-nav">
                            <a class="escudo-link-nav ativo" onclick="switchEscudoTab('personagens', this)">Personagens</a>
                            <a class="escudo-link-nav" onclick="switchEscudoTab('combates', this)">Combates</a>
                            <a class="escudo-link-nav"
                                onclick="switchEscudoTab('investigacoes', this)">Investigações</a>
                            <a class="escudo-link-nav" onclick="switchEscudoTab('relatorios', this)">Relatórios</a>
                            <a class="escudo-link-nav" onclick="switchEscudoTab('dados', this)">Dados</a>
                            <a class="escudo-link-nav" onclick="switchEscudoTab('anotacoes', this)">Anotações</a>
                            <a href="criar-mapa.php?id=<?= $id_campanha ?>" target="_blank"
                                class="escudo-link-nav link-mapa-especial">Mapas <i
                                    class="fas fa-external-link-alt"></i></a>
                        </div>

                        <div id="escudo-tab-personagens" class="escudo-Personagems-grid">
                            <?php foreach ($PersonagemsCampanha as $Personagem): ?>
                                <div class="card-Personagem-compacto">
                                    <div class="card-compacto-header">
                                        <h3><?= htmlspecialchars($Personagem['nm_personagem']) ?></h3>
                                        <p><?= htmlspecialchars($Personagem['nm_classe'] ?: 'Mundano') ?> •
                                            <?= htmlspecialchars($Personagem['nm_origem'] ?? 'Acadêmico') ?>
                                        </p>
                                        <span>NEX: <?= $Personagem['qt_nivel'] ?>%</span>
                                    </div>

                                    <div class="atributos-Personagem-p1">
                                        <?php
                                        $attrsExibidos = array_slice($Personagem['atributos'], 0, 5);
                                        foreach ($attrsExibidos as $attr):
                                            ?>
                                            <div class="attr-p1-box">
                                                <span><?= htmlspecialchars($attr['ds_abreviacao']) ?></span>
                                                <strong><?= $attr['qt_valor'] ?></strong>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="status-bars-p1">
                                        <div class="barra-p1-container">
                                            <div class="barra-p1-label">VIDA</div>
                                            <div class="barra-p1-bg">
                                                <div class="barra-p1-fill fill-vida-p1"
                                                    style="width: <?= ($Personagem['qt_vida_maxima'] > 0 ? ($Personagem['qt_vida'] / $Personagem['qt_vida_maxima'] * 100) : 0) ?>%">
                                                </div>
                                                <div class="barra-p1-text">
                                                    <?= $Personagem['qt_vida'] ?>/<?= $Personagem['qt_vida_maxima'] ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="barra-p1-container">
                                            <div class="barra-p1-label">SANIDADE</div>
                                            <div class="barra-p1-bg">
                                                <div class="barra-p1-fill fill-sanidade-p1"
                                                    style="width: <?= ($Personagem['qt_sanidade_maxima'] > 0 ? ($Personagem['qt_sanidade'] / $Personagem['qt_sanidade_maxima'] * 100) : 0) ?>%">
                                                </div>
                                                <div class="barra-p1-text">
                                                    <?= $Personagem['qt_sanidade'] ?>/<?= $Personagem['qt_sanidade_maxima'] ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="barra-p1-container">
                                            <div class="barra-p1-label">ESFORÇO</div>
                                            <div class="barra-p1-bg">
                                                <div class="barra-p1-fill fill-esforco-p1"
                                                    style="width: <?= ($Personagem['qt_esforco_maximo'] > 0 ? ($Personagem['qt_esforco'] / $Personagem['qt_esforco_maximo'] * 100) : 0) ?>%">
                                                </div>
                                                <div class="barra-p1-text">
                                                    <?= $Personagem['qt_esforco'] ?>/<?= $Personagem['qt_esforco_maximo'] ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="compacto-footer">
                                        <div class="footer-stats-grid">
                                            <div class="footer-stat-item"><span>PE/T</span><strong>1</strong></div>
                                            <div class="footer-stat-item"><span>DESL</span><strong>9m</strong></div>
                                            <div class="footer-stat-item">
                                                <span>DEF</span><strong><?= $Personagem['qt_defesa'] ?></strong>
                                            </div>
                                            <div class="footer-stat-item"><span>BLQ</span><strong>0</strong></div>
                                            <div class="footer-stat-item">
                                                <span>ESQ</span><strong><?= $Personagem['qt_esquiva'] ?? $Personagem['qt_defesa'] ?></strong>
                                            </div>
                                        </div>
                                        <a href="exibir-ficha.php?id=<?= $Personagem['id_personagem'] ?>"
                                            class="btn-ficha-compacto">Ver Ficha Completa</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="escudo-tab-combates" class="escondido">
                            <!-- Modo 1: Lista de Combates -->
                            <div id="escudo-combates-lista" class="lista-combates">
                                <?php foreach ($combatesCampanha as $combate): ?>
                                    <div class="card-combate-escudo"
                                        style="background: var(--fundo-card-escudo); padding: 30px; border-radius: 15px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--cor-borda-escudo);">
                                        <div>
                                            <h3 style="font-size: 1.5rem; margin-bottom: 5px;">
                                                <?= htmlspecialchars($combate['nm_combate']) ?>
                                            </h3>
                                            <p style="color: #888;">VD: <?= $combate['vd_total'] ?: 0 ?> (vida total)</p>
                                        </div>
                                        <button class="btn-iniciar-combate"
                                            onclick="iniciarCombateEscudo(<?= $combate['id_combate'] ?>, '<?= htmlspecialchars($combate['nm_combate']) ?>')"
                                            style="background: #fff; color: #000; padding: 10px 30px; border-radius: 20px; font-weight: 800; cursor: pointer;">Iniciar</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Modo 2: Combate Ativo (Gesto de Iniciativa e Ficha) -->
                            <div id="escudo-combate-ativo" class="escudo-combate-ativo escondido">
                                <!-- Coluna Iniciativa -->
                                <div class="coluna-iniciativa">
                                    <div class="header-iniciativa">
                                        <h2>Iniciativa</h2>
                                        <div class="controles-turno">
                                            <button class="btn-turno">Voltar Turno</button>
                                            <button class="btn-turno">Próximo Turno</button>
                                        </div>
                                    </div>
                                    <div id="lista-iniciativa-escudo">
                                        <!-- Injetado via JS -->
                                    </div>
                                </div>

                                <!-- Coluna Ficha Detalhada -->
                                <div class="ficha-detalhes-escudo" id="detalhe-participante-escudo">
                                    <p style="text-align: center; color: #888; padding-top: 50px;">Selecione um
                                        participante para ver detalhes.</p>
                                </div>
                            </div>
                        </div>
                        <div id="escudo-tab-investigacoes" class="escondido">
                            <!-- Modo 1: Lista -->
                            <div id="inv-modo-lista">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                    <h2 style="font-size: 1.2rem; font-weight: 700;">Fichas de Investigação</h2>
                                    <button class="btn-adicionar-investigacao"
                                        onclick="novaFichaInvestigacao()">Adicionar</button>
                                </div>
                                <div class="investigacao-lista">
                                    <div class="item-investigacao">
                                        <h3>Nova Ficha de Investigação</h3>
                                        <div class="acoes-investigacao">
                                            <button class="btn-inv-del">Deletar</button>
                                            <button class="btn-inv-abrir"
                                                onclick="abrirFichaInvestigacao()">Abrir</button>
                                        </div>
                                    </div>
                                    <div class="item-investigacao">
                                        <h3>Nova Ficha de Investigação</h3>
                                        <div class="acoes-investigacao">
                                            <button class="btn-inv-del">Deletar</button>
                                            <button class="btn-inv-abrir"
                                                onclick="abrirFichaInvestigacao()">Abrir</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modo 2: Ficha Detalhada -->
                            <div id="inv-modo-detalhe" class="escondido">
                                <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
                                    <button class="btn-voltar-investigacao"
                                        onclick="voltarListaInvestigacao()">Voltar</button>
                                </div>
                                <div class="form-investigacao"
                                    style="background: rgba(0,0,0,0.3); padding: 30px; border-radius: 20px;">
                                    <div class="campo-investigacao">
                                        <label>Nome do caso</label>
                                        <input type="text" placeholder="Nome do caso">
                                    </div>
                                    <div class="campo-investigacao">
                                        <label>Resumo:</label>
                                        <div class="textarea-p1" contenteditable="true" placeholder="..."></div>
                                    </div>
                                    <div class="campo-investigacao">
                                        <label>Objetivo:</label>
                                        <div class="textarea-p1" contenteditable="true" placeholder="..."></div>
                                    </div>
                                    <div class="campo-investigacao">
                                        <label>Perguntas:</label>
                                        <div class="textarea-p1" contenteditable="true" placeholder="..."></div>
                                    </div>
                                    <div class="campo-investigacao">
                                        <label>Pistas:</label>
                                        <div class="textarea-p1" contenteditable="true" placeholder="..."></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="escudo-tab-relatorios" class="escondido">
                            <!-- Modo 1: Lista -->
                            <div id="rel-modo-lista">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                    <h2 style="font-size: 1.2rem; font-weight: 700;">Relatórios de Missão</h2>
                                    <button class="btn-adicionar-investigacao"
                                        onclick="novoRelatorioMissao()">Adicionar</button>
                                </div>
                                <div class="investigacao-lista">
                                    <div class="item-investigacao">
                                        <h3>Nova Ficha de Investigação</h3>
                                        <div class="acoes-investigacao">
                                            <button class="btn-inv-del">Deletar</button>
                                            <button class="btn-inv-abrir"
                                                onclick="abrirRelatorioMissao()">Abrir</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modo 2: Ficha Detalhada -->
                            <div id="rel-modo-detalhe" class="escondido">
                                <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
                                    <button class="btn-voltar-investigacao"
                                        onclick="voltarListaRelatorio()">Voltar</button>
                                </div>
                                <div class="form-investigacao"
                                    style="background: rgba(0,0,0,0.3); padding: 30px; border-radius: 20px;">
                                    <div class="form-relatorio-row">
                                        <div class="campo-investigacao">
                                            <label>Missão:</label>
                                            <input type="text" placeholder="Nome do relatório de missão...">
                                        </div>
                                        <div class="campo-investigacao">
                                            <label>Equipe:</label>
                                            <input type="text" placeholder="Nome da equipe...">
                                        </div>
                                    </div>
                                    <div class="campo-investigacao">
                                        <label>Personagens Envolvidos:</label>
                                        <input type="text" placeholder="Nome da equipe...">
                                    </div>
                                    <div class="campo-investigacao">
                                        <label>Pistas Encontradas</label>
                                        <div class="textarea-p1" contenteditable="true"
                                            placeholder="Todas as pistas encontradas durante a missão..."></div>
                                    </div>
                                    <div class="campo-investigacao">
                                        <label>Causalidades</label>
                                        <div class="textarea-p1" contenteditable="true"
                                            placeholder="Mortes de inocentes, membros da equipe, perda de itens etc...">
                                        </div>
                                    </div>
                                    <div class="campo-investigacao">
                                        <label>Resumo da Missão:</label>
                                        <div class="textarea-p1" contenteditable="true"
                                            placeholder="Resumo e conclusão da missão..."></div>
                                    </div>
                                    <div class="campo-investigacao">
                                        <label>Resultado da Missão:</label>
                                        <div class="status-toggle-group">
                                            <button class="btn-status-rel ativo" data-status="aberto"
                                                onclick="setRelStatus(this)">Em aberto</button>
                                            <button class="btn-status-rel" data-status="sucesso"
                                                onclick="setRelStatus(this)">Sucesso</button>
                                            <button class="btn-status-rel" data-status="fracasso"
                                                onclick="setRelStatus(this)">Fracasso</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="escudo-tab-dados" class="escondido" style="position: relative;">
                            <div class="dados-header">
                                <div class="titulo-dados">Rolar Dados <i class="fas fa-dice-d20"></i></div>
                                <button class="btn-adicionar-investigacao" onclick="togglePopupDados()">Adicionar
                                    Dados</button>
                            </div>

                            <div class="grid-dados">
                                <div class="item-dado" onclick="rolarDado(2, this)">
                                    <div class="dado-icon-container">
                                        <img src="../img/dados/D2.png" alt="D2" class="img-dado">
                                    </div>
                                    <span class="label-dado">1D2</span>
                                </div>
                                <div class="item-dado" onclick="rolarDado(4, this)">
                                    <div class="dado-icon-container">
                                        <img src="../img/dados/D4.png" alt="D4" class="img-dado">
                                    </div>
                                    <span class="label-dado">1D4</span>
                                </div>
                                <div class="item-dado" onclick="rolarDado(6, this)">
                                    <div class="dado-icon-container">
                                        <img src="../img/dados/D6.png" alt="D6" class="img-dado">
                                    </div>
                                    <span class="label-dado">1D6</span>
                                </div>
                                <div class="item-dado" onclick="rolarDado(8, this)">
                                    <div class="dado-icon-container">
                                        <img src="../img/dados/D8.png" alt="D8" class="img-dado">
                                    </div>
                                    <span class="label-dado">1D8</span>
                                </div>
                                <div class="item-dado" onclick="rolarDado(10, this)">
                                    <div class="dado-icon-container">
                                        <img src="../img/dados/D10.png" alt="D10" class="img-dado">
                                    </div>
                                    <span class="label-dado">1D10</span>
                                </div>
                                <div class="item-dado" onclick="rolarDado(12, this)">
                                    <div class="dado-icon-container">
                                        <img src="../img/dados/D12.png" alt="D12" class="img-dado">
                                    </div>
                                    <span class="label-dado">1D12</span>
                                </div>
                                <div class="item-dado" onclick="rolarDado(20, this)">
                                    <div class="dado-icon-container">
                                        <img src="../img/dados/D20.png" alt="D20" class="img-dado">
                                    </div>
                                    <span class="label-dado">1D20</span>
                                </div>
                                <div class="item-dado" onclick="rolarDado(100, this)">
                                    <div class="dado-icon-container">
                                        <img src="../img/dados/D100.png" alt="D100" class="img-dado">
                                    </div>
                                    <span class="label-dado">1D100</span>
                                </div>
                            </div>

                            <!-- Popup Adicionar Dados -->
                            <div id="overlay-dados" class="popup-overlay" onclick="togglePopupDados()"></div>
                            <div id="popup-dados" class="popup-dados escondido">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                                    <h3 style="color: #fff; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Adicionar Dados</h3>
                                    <i class="fas fa-times" style="cursor: pointer; color: #888;" onclick="togglePopupDados()"></i>
                                </div>
                                <div class="campo-investigacao" style="margin-bottom: 20px;">
                                    <label style="color: #888; font-size: 0.8rem; text-transform: uppercase;">Quantidade de Dados (Máx: 10)</label>
                                    <input type="number" id="qtd-dados-multi" value="1" min="1" max="10" style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px; border-radius: 10px;">
                                </div>
                                <div class="campo-investigacao">
                                    <label style="color: #888; font-size: 0.8rem; text-transform: uppercase;">Quantos Lados</label>
                                    <input type="number" id="lados-dados-multi" value="20" min="2" max="100" style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px; border-radius: 10px;">
                                </div>
                                <button class="btn-confirmar-rolagem" onclick="confirmarRolagemMultipla()">Confirmar Rolagem</button>
                            </div>
                        </div>

                        <div id="escudo-tab-anotacoes" class="escondido">
                            <div class="form-investigacao"
                                style="background: rgba(0,0,0,0.3); padding: 30px; border-radius: 20px;">
                                <div class="secao-anotacao">
                                    <h3>GERAL:</h3>
                                    <div class="textarea-p1" contenteditable="true"
                                        placeholder="informações gerais ao longo da sessão..."></div>
                                </div>
                                <div class="secao-anotacao">
                                    <h3>Sessões Futuras:</h3>
                                    <div class="textarea-p1" contenteditable="true"
                                        placeholder="notas de possíveis eventos futuros..."></div>
                                </div>
                                <div class="secao-anotacao">
                                    <h3>Sessões Anteriores:</h3>
                                    <div class="textarea-p1" contenteditable="true"
                                        placeholder="eventos importantes que ocorreram..."></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
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
                    <li><a href="rolador-de-dados.php">Dados</a></li>
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

    <!-- MODAIS DA CAMPANHA -->

    <!-- 1. FOTO DE CAPA -->
    <div id="modal-foto-capa" class="modal-overlay">
        <div class="modal-box" style="max-width: 450px;">
            <i class="fas fa-times modal-close" onclick="fecharModal('modal-foto-capa')"></i>
            <h2 style="color: #fff; text-transform: uppercase; letter-spacing: 1px;">Foto de Capa</h2>
            <div class="modal-body-central">
                <p style="color: #888; font-size: 0.85rem; margin-bottom: 25px; text-align: center;">Recomendado: 1200x300px para um ajuste cinematográfico perfeito.</p>
                <input type="file" id="input-foto-capa" style="display: none;" accept="image/*" onchange="previewCapa(this)">
                <button class="btn-confirmar-rolagem" onclick="document.getElementById('input-foto-capa').click()"><i class="fas fa-cloud-upload-alt"></i> Selecionar Imagem</button>
            </div>
        </div>
    </div>

    <!-- 2. ADICIONAR PERSONAGENS -->
    <div id="modal-adc-Personagems" class="modal-overlay">
        <div class="modal-box" style="max-width: 700px;">
            <i class="fas fa-times modal-close" onclick="fecharModal('modal-adc-Personagems')"></i>
            <h2 style="color: #fff; text-transform: uppercase; letter-spacing: 1px;">Adicionar Personagens</h2>
            <p style="color: #888; font-size: 0.85rem; margin-bottom: 20px;">Escolha um personagem para integrar à sua campanha:</p>
            <div class="modal-lista-Personagems" id="modal-meus-personagens" style="max-height: 400px; overflow-y: auto;">
                <!-- Injetado via AJAX -->
                <p style="text-align:center; padding:20px; color:#888;">Carregando grimório de personagens...</p>
            </div>
        </div>
    </div>

    <!-- 3. CONVIDAR JOGADORES -->
    <div id="modal-link-convite" class="modal-overlay">
        <div class="modal-box" style="max-width: 500px;">
            <i class="fas fa-times modal-close" onclick="fecharModal('modal-link-convite')"></i>
            <h2 style="color: #fff; text-transform: uppercase; letter-spacing: 1px;">Convite da Campanha</h2>
            <div class="modal-body-central">
                <p style="color: #888; font-size: 0.85rem; margin-bottom: 15px; text-align: center;">Compartilhe o link abaixo para que seus jogadores entrem na aventura:</p>
                <div class="bloco-link-convite" style="background: rgba(255,255,255,0.05); border: 1px dashed rgba(255,255,255,0.2); padding: 25px;">
                    <a href="#" id="texto-link-campanha" style="color: var(--premium-accent); font-weight: 700; text-decoration: none;">https://linkcampanhalink.campanha180</a>
                </div>
                <div class="modal-footer-acoes" style="margin-top: 25px; gap: 15px;">
                    <button class="btn-resetar" onclick="resetarLink()" style="border-radius: 12px; flex: 1;"><i class="fas fa-sync"></i> Novo Link</button>
                    <button class="btn-copiar" onclick="copiarLink()" style="border-radius: 12px; flex: 1; background: var(--premium-accent); border: none; color: #fff; font-weight: 700;"><i class="fas fa-copy"></i> Copiar Link</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/nav-global.js" defer></script>
    <script>
        // Dados da Campanha (caso venha do PHP)
        const campanhaInicial = <?= json_encode($campanhaDados) ?>;

        document.addEventListener('DOMContentLoaded', () => {
            if (campanhaInicial) {
                // Preencher dados básicos da campanha para uso global
                document.getElementById('display-nome-campanha').textContent = campanhaInicial.nm_campanha;
                document.getElementById('display-descricao-campanha').innerHTML = campanhaInicial.ds_descricao;
                if (campanhaInicial.ds_imagem) {
                    const banner = document.getElementById('banner-campanha-display');
                    banner.style.backgroundImage = `url('${campanhaInicial.ds_imagem}')`;
                    banner.classList.remove('escondido');
                }
            }

            // Lógica de Persistência via Hash
            const currentHash = window.location.hash.replace('#', '');
            const validSections = ['sessao-criar', 'sessao-detalhes', 'sessao-editar', 'sessao-combate', 'sessao-escudo'];

            if (currentHash && validSections.includes(currentHash)) {
                // Casos especiais que precisam de inicialização
                if (currentHash === 'sessao-editar') irParaEditar();
                else if (currentHash === 'sessao-combate') irParaCombate();
                else if (currentHash === 'sessao-escudo') irParaEscudo();
                else showSection(currentHash);
            } else if (campanhaInicial) {
                showSection('sessao-detalhes');
            } else {
                showSection('sessao-criar');
            }
        });

        // Funções de Vitrine Dinâmica
        async function carregarDetalhesSistema(id, targetShowcaseId = 'sistema-showcase') {
            const showcase = document.getElementById(targetShowcaseId);
            if (!id) {
                showcase.classList.add('escondido');
                return;
            }

            try {
                const response = await fetch(`../app/ajax/get-sistema-detalhes.php?id=${id}`);
                const data = await response.json();

                if (data.success) {
                    renderizarVitrine(data, targetShowcaseId);
                    showcase.classList.remove('escondido');
                } else {
                    console.error(data.error);
                }
            } catch (error) {
                console.error('Erro ao buscar detalhes do sistema:', error);
            }
        }

        function renderizarVitrine(data, targetShowcaseId = 'sistema-showcase') {
            const showcase = document.getElementById(targetShowcaseId);
            const sis = data.sistema;

            let html = `
                <div class="system-hero">
                    <img src="${sis.ds_imagem || '../img/logo_icone.png'}" alt="${sis.nm_sistema}" class="system-img">
                    <div class="system-text">
                        <h2>${sis.nm_sistema}</h2>
                        <p>${sis.ds_descricao || 'Sem descrição disponível.'}</p>
                    </div>
                </div>
            `;
            showcase.innerHTML = html;
        }

        // Funções de Modal Globais
        function abrirModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.add('ativo');
        }

        function fecharModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.remove('ativo');
        }

        // Sistema de Troca de Telas
        function showSection(id) {
            const secoes = ['sessao-criar', 'sessao-detalhes', 'sessao-editar', 'sessao-combate', 'sessao-escudo'];
            secoes.forEach(s => {
                const el = document.getElementById(s);
                if (el) {
                    if (s === id) {
                        el.style.display = 'block';
                        el.classList.remove('escondido');
                    } else {
                        el.style.display = 'none';
                        el.classList.add('escondido');
                    }
                }
            });

            // Atualiza o hash na URL sem recarregar a página (Persistência)
            if (history.replaceState) {
                history.replaceState(null, null, '#' + id);
            } else {
                window.location.hash = id;
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Lógica de Salvamento Real no Banco
        document.getElementById('form-criar-campanha').onsubmit = async function (e) {
            e.preventDefault();

            const btn = e.target.querySelector('.btn-criar-campanha');
            const originalTxt = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';

            const payload = {
                id_campanha: <?= $id_campanha ? $id_campanha : 'null' ?>,
                nome: document.getElementById('nome-campanha').value,
                id_sistema: document.getElementById('selecao-sistema').value,
                descricao: document.getElementById('descricao-campanha').innerHTML
            };

            try {
                const response = await fetch('../app/ajax/salvar-campanha.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    // Redirecionar para a página com o ID da nova campanha
                    window.location.href = `criar-campanha.php?id=${data.id_campanha}`;
                } else {
                    alert('Erro ao criar campanha: ' + data.error);
                }
            } catch (error) {
                console.error('Erro na requisição:', error);
                alert('Erro de conexão com o servidor.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalTxt;
            }
        };

        // Funções para outros botões (Manter protótipo funcional por enquanto)
        function irParaEditar() {
            document.getElementById('nome-campanha-edit').value = document.getElementById('display-nome-campanha').textContent;
            document.getElementById('descricao-campanha-edit').innerHTML = document.getElementById('display-descricao-campanha').innerHTML;
            
            const idSis = <?= $campanhaDados['id_sistema'] ?? 'null' ?>;
            if (idSis) {
                document.getElementById('selecao-sistema-edit').value = idSis;
                carregarDetalhesSistema(idSis, 'sistema-showcase-edit');
            }
            
            showSection('sessao-editar');
        }

        async function salvarEdicao() {
            const nome = document.getElementById('nome-campanha-edit').value;
            const desc = document.getElementById('descricao-campanha-edit').innerHTML;
            const idSis = document.getElementById('selecao-sistema-edit').value;
            const idC = <?= $id_campanha ?: 'null' ?>;

            try {
                const res = await fetch('../app/ajax/salvar-campanha.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_campanha: idC, nome: nome, id_sistema: idSis, descricao: desc })
                });
                const data = await res.json();
                if (data.success) {
                    // Atualizar o nome exibido na tela de detalhes sem reload
                    if (nome) {
                        const displayNome = document.getElementById('display-nome-campanha');
                        if (displayNome) displayNome.textContent = nome;
                    }
                    // Atualizar a descrição exibida
                    const displayDesc = document.getElementById('display-descricao-campanha');
                    if (displayDesc && desc) displayDesc.innerHTML = desc;

                    // Mostrar toast de sucesso
                    const toast = document.createElement('div');
                    toast.style.cssText = 'position:fixed;bottom:30px;left:50%;transform:translateX(-50%);background:#0c9447;color:#fff;padding:14px 30px;border-radius:12px;font-weight:700;font-size:1rem;z-index:99999;box-shadow:0 8px 24px rgba(0,0,0,0.4);animation:fadeIn .3s ease;';
                    toast.innerHTML = '<i class="fas fa-check-circle"></i> Campanha atualizada com sucesso!';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 3000);

                    // Voltar para a tela de detalhes
                    showSection('sessao-detalhes');
                } else {
                    alert('Erro ao salvar: ' + data.error);
                }
            } catch (e) { console.error(e); }
        }

        async function removerPersonagem(idP) {
            if (!confirm('Deseja remover este Personagem da campanha?')) return;
            const idC = <?= $id_campanha ?: 'null' ?>;
            try {
                const res = await fetch('../app/ajax/remover-Personagem-campanha.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_campanha: idC, id_personagem: idP })
                });
                const data = await res.json();
                if (data.success) location.reload();
            } catch (e) { console.error(e); }
        }

        async function removerCombate(idComb, btn) {
            if (!confirm('Deseja excluir este combate?')) return;
            try {
                const res = await fetch('../app/ajax/remover-combate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_combate: idComb })
                });
                const data = await res.json();
                if (data.success) location.reload();
            } catch (e) { console.error(e); }
        }

        function irParaCombate() {
            showSection('sessao-combate');
            renderCatalogo();
        }

        function irParaEscudo() {
            document.getElementById('escudo-titulo-campanha').textContent = document.getElementById('display-nome-campanha').textContent;
            showSection('sessao-escudo');
        }

        function fecharEscudo() { showSection('sessao-detalhes'); }

        // ─── ABA DASHBOARD ──────────────────────────────────────────
        function switchDashboardTab(tab) {
            const btnPersonagens = document.getElementById('aba-personagens');
            const btnCombates = document.getElementById('aba-combates');
            const listaPersonagens = document.getElementById('lista-Personagems');
            const listaCombates = document.getElementById('lista-combates');

            if (tab === 'personagens') {
                if (btnPersonagens) btnPersonagens.classList.add('ativa');
                if (btnCombates) btnCombates.classList.remove('ativa');
                if (listaPersonagens) listaPersonagens.classList.remove('escondido');
                if (listaCombates) listaCombates.classList.add('escondido');
            } else {
                if (btnPersonagens) btnPersonagens.classList.remove('ativa');
                if (btnCombates) btnCombates.classList.add('ativa');
                if (listaPersonagens) listaPersonagens.classList.add('escondido');
                if (listaCombates) listaCombates.classList.remove('escondido');
            }
        }

        // ─── GESTÃO DE PERSONAGENS ──────────────────────────────────
        async function abrirModalPersonagens() {
            abrirModal('modal-adc-Personagems');
            const idc = <?= $id_campanha ?: 'null' ?>;
            const container = document.getElementById('modal-meus-personagens');

            try {
                const res = await fetch(`../app/ajax/get-meus-personagens.php?id_campanha=${idc}`);
                const data = await res.json();

                if (data.success) {
                    if (data.personagens.length === 0) {
                        container.innerHTML = '<p style="text-align:center; padding:20px; color:#888;">Você não tem personagens disponíveis para adicionar.</p>';
                        return;
                    }
                    container.innerHTML = data.personagens.map(p => `
                        <div class="card-Personagem">
                            <div class="avatar-Personagem">
                                <img src="${p.ds_foto || '../img/uploads/perfil/avatar1.png'}" alt="Avatar">
                            </div>
                            <div class="info-Personagem">
                                <h3>${p.nm_personagem}</h3>
                                <p>${p.nm_sistema} - ${p.nm_classe || 'Sem Classe'}</p>
                            </div>
                            <button class="btn-ver-ficha" onclick="vincularPersonagem(${p.id_personagem})"><i class="fas fa-plus-circle"></i> Adicionar</button>
                        </div>
                    `).join('');
                }
            } catch (e) { console.error(e); }
        }

        async function vincularPersonagem(idP) {
            const idC = <?= $id_campanha ?: 'null' ?>;
            try {
                const res = await fetch('../app/ajax/adicionar-Personagem-campanha.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_campanha: idC, id_personagem: idP })
                });
                const data = await res.json();
                if (data.success) {
                    location.reload(); // Recarregar para mostrar na lista oficial
                } else {
                    alert('Erro ao adicionar personagem: ' + data.error);
                }
            } catch (e) { console.error(e); }
        }

        // ─── SISTEMA DE COMBATE ──────────────────────────────────────
        let ameacasCatalogo = [];
        let ameacasSelecionadas = [];
        let filtroAtual = 'Todos';

        async function renderCatalogo() {
            const container = document.getElementById('catalogo-cards');
            const idSis = <?= $campanhaDados ? $campanhaDados['id_sistema'] : 'null' ?>;

            if (ameacasCatalogo.length === 0) {
                try {
                    const res = await fetch(`../app/ajax/get-monstros.php?id_sistema=${idSis}`);
                    const data = await res.json();
                    if (data.success) ameacasCatalogo = data.monstros;
                } catch (e) { console.error(e); }
            }

            const busca = document.getElementById('busca-ameaca').value.toLowerCase();
            const filtrados = ameacasCatalogo.filter(a => {
                const matchBusca = a.nm_monstro.toLowerCase().includes(busca);
                const matchFiltro = filtroAtual === 'Todos' || a.tp_monstro === filtroAtual;
                return matchBusca && matchFiltro;
            });

            container.innerHTML = filtrados.map(a => `
                <div class="card-ameaca-premium">
                    <img src="${a.ds_imagem || '../img/logo_icone.png'}" class="card-ameaca-img">
                    <div class="card-ameaca-body">
                        <h4>${a.nm_monstro}</h4>
                        <div class="card-ameaca-details">
                            <span>VD: <b>${a.qt_vd || '???'}</b></span>
                            <span>${a.tp_monstro || 'Criatura'}</span>
                        </div>
                    </div>
                    <div class="card-ameaca-actions">
                        <button class="btn-card-ficha" onclick="verFichaMonstro(${a.id_monstro})">Ficha</button>
                        <button class="btn-card-add" onclick="adicionarAmeaca(${a.id_monstro})">Adicionar</button>
                    </div>
                </div>
            `).join('');
        }

        async function verFichaMonstro(idM) {
            const container = document.getElementById('ficha-monstro-render');
            if (!container) return;
            container.innerHTML = '<div style="padding: 40px; text-align: center; color: #888;"><i class="fas fa-spinner fa-spin"></i> Lendo Grimório...</div>';
            abrirModal('modal-ficha-monstro');

            try {
                const res = await fetch(`../app/ajax/get-monstro-detalhes.php?id=${idM}`);
                const data = await res.json();

                if (data.success) {
                    const m = data.monstro;
                    const attrs = data.atributos;

                    container.innerHTML = `
                        <div class="ficha-header-comp" style="background: linear-gradient(135deg, #1e0b3a, #311c61); padding: 25px; border-bottom: 2px solid var(--premium-accent);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <h1 style="color: #fff; font-weight: 900; font-size: 1.8rem; margin-bottom: 5px;">${m.nm_monstro}</h1>
                                    <span style="color: var(--premium-accent); font-weight: 800; font-size: 0.9rem; text-transform: uppercase;">${m.tp_monstro || 'Desconhecido'}</span>
                                </div>
                                <i class="fas fa-times" onclick="fecharModal('modal-ficha-monstro')" style="color: #fff; cursor: pointer; font-size: 1.2rem;"></i>
                            </div>
                        </div>
                        <div style="padding: 25px;">
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px;">
                                <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px; text-align: center; border: 1px solid rgba(255,255,255,0.1);">
                                    <span style="display: block; color: #ff4d4d; font-weight: 900; font-size: 0.7rem; margin-bottom: 5px;">VIDA</span>
                                    <strong style="color: #fff; font-size: 1.5rem;">${m.qt_vida}</strong>
                                </div>
                                <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px; text-align: center; border: 1px solid rgba(255,255,255,0.1);">
                                    <span style="display: block; color: #2980b9; font-weight: 900; font-size: 0.7rem; margin-bottom: 5px;">DEFESA</span>
                                    <strong style="color: #fff; font-size: 1.5rem;">${m.qt_defesa}</strong>
                                </div>
                                <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px; text-align: center; border: 1px solid rgba(255,255,255,0.1);">
                                    <span style="display: block; color: #f1c40f; font-weight: 900; font-size: 0.7rem; margin-bottom: 5px;">XP</span>
                                    <strong style="color: #fff; font-size: 1.5rem;">${m.qt_xp_recompensa}</strong>
                                </div>
                            </div>

                            <div class="premium-atributos-grid" style="grid-template-columns: repeat(5, 1fr); margin-bottom: 25px; display: grid;">
                                ${attrs.map(a => `
                                    <div class="premium-attr-box" title="${a.nm_atributo}" style="text-align: center;">
                                        <span class="attr-abbr" style="font-size: 0.7rem; display: block; color: var(--premium-accent);">${a.ds_abreviacao}</span>
                                        <div class="attr-circle" style="width: 45px; height: 45px; font-size: 1.1rem; border: 2px solid ${a.qt_valor > 0 ? 'var(--premium-accent)' : '#444'}; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; color: #fff;">${a.qt_valor}</div>
                                    </div>
                                `).join('')}
                            </div>

                            <div style="background: rgba(0,0,0,0.3); padding: 20px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.05);">
                                <h3 style="color: var(--premium-accent); font-size: 0.8rem; font-weight: 900; margin-bottom: 10px; text-transform: uppercase;">HABILIDADES / DETALHES</h3>
                                <p style="color: #ccc; font-size: 0.9rem; line-height: 1.6;">${m.ds_monstro || 'Nenhuma habilidade descrita.'}</p>
                            </div>
                        </div>
                    `;
                }
            } catch (e) { console.error(e); }
        }

        function filtrarPorElemento(elemento, btn) {
            filtroAtual = elemento;
            document.querySelectorAll('#filtros-ameacas .btn-filtro').forEach(b => b.classList.remove('ativo'));
            btn.classList.add('ativo');
            renderCatalogo();
        }

        function adicionarAmeaca(idM) {
            const ameaca = ameacasCatalogo.find(a => a.id_monstro == idM);
            if (ameaca) {
                ameacasSelecionadas.push(ameaca);
                renderSelecionadas();
            }
        }

        function removerAmeaca(index) {
            ameacasSelecionadas.splice(index, 1);
            renderSelecionadas();
        }

        function renderSelecionadas() {
            const container = document.getElementById('selecionadas-cards');
            let vdTotal = 0;

            container.innerHTML = ameacasSelecionadas.map((a, i) => {
                vdTotal += parseInt(a.qt_vd || 0);
                return `
                    <div class="card-ameaca-premium" style="background: rgba(255,255,255,0.05); padding: 8px;">
                         <div class="card-ameaca-body">
                            <h4 style="font-size: 0.9rem;">${a.nm_monstro}</h4>
                            <span style="font-size: 0.7rem; color: #aaa;">VD: <b>${a.qt_vd || 0}</b></span>
                        </div>
                        <button class="btn-ameaca-remover" onclick="removerAmeaca(${i})" style="background: none; border: none; color: #888; cursor: pointer;"><i class="fas fa-trash"></i></button>
                    </div>
                `;
            }).join('');

            document.getElementById('vd-total-valor').textContent = vdTotal;
        }

        async function salvarCombate() {
            const nome = document.getElementById('nome-combate-input').value;
            if (!nome) { alert('Dê um nome ao combate!'); return; }
            const idC = <?= $id_campanha ?: 'null' ?>;
            const monstrosIds = ameacasSelecionadas.map(a => a.id_monstro);

            try {
                const res = await fetch('../app/ajax/salvar-combate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_campanha: idC, nome: nome, monstros: monstrosIds })
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao salvar combate: ' + data.error);
                }
            } catch (e) { console.error(e); }
        }

        // ─── CAPA E FOTO ──────────────────────────────────────────
        async function previewCapa(input) {
            if (!input.files || !input.files[0]) return;
            const formData = new FormData();
            formData.append('foto', input.files[0]);
            formData.append('id_campanha', <?= $id_campanha ?: 'null' ?>);

            try {
                const res = await fetch('../app/ajax/salvar-foto-capa.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('banner-campanha-display').style.backgroundImage = `url('${data.url}')`;
                    fecharModal('modal-foto-capa');
                    location.reload();
                } else {
                    alert('Erro no upload: ' + data.error);
                }
            } catch (e) { console.error(e); }
        }

        // ─── ESCUDO DO MESTRE ──────────────────────────────────────
        let combateAtivo = null;
        let iniciativaLista = [];
        let indexTurno = 0;
        let subTabAtiva = 'atributos';
        let participanteSelecionado = null;

        function switchEscudoTab(tab, btn) {
            if (!btn) return;
            document.querySelectorAll('.escudo-link-nav').forEach(b => b.classList.remove('ativo'));
            btn.classList.add('ativo');

            const tabPersonagens = document.getElementById('escudo-tab-personagens');
            const tabCombates = document.getElementById('escudo-tab-combates');
            const tabInvestigacoes = document.getElementById('escudo-tab-investigacoes');
            const tabRelatorios = document.getElementById('escudo-tab-relatorios');
            const tabDados = document.getElementById('escudo-tab-dados');
            const tabAnotacoes = document.getElementById('escudo-tab-anotacoes');

            // Esconder todas primeiro
            if (tabPersonagens) tabPersonagens.classList.add('escondido');
            if (tabCombates) tabCombates.classList.add('escondido');
            if (tabInvestigacoes) tabInvestigacoes.classList.add('escondido');
            if (tabRelatorios) tabRelatorios.classList.add('escondido');
            if (tabDados) tabDados.classList.add('escondido');
            if (tabAnotacoes) tabAnotacoes.classList.add('escondido');

            if (tab === 'personagens') {
                if (tabPersonagens) tabPersonagens.classList.remove('escondido');
            } else if (tab === 'combates') {
                if (tabCombates) tabCombates.classList.remove('escondido');
            } else if (tab === 'investigacoes') {
                if (tabInvestigacoes) tabInvestigacoes.classList.remove('escondido');
            } else if (tab === 'relatorios') {
                if (tabRelatorios) tabRelatorios.classList.remove('escondido');
            } else if (tab === 'dados') {
                if (tabDados) tabDados.classList.remove('escondido');
            } else if (tab === 'anotacoes') {
                if (tabAnotacoes) tabAnotacoes.classList.remove('escondido');
            }
        }

        function iniciarCombateEscudo(id, nome) {
            document.getElementById('escudo-combates-lista').classList.add('escondido');
            document.getElementById('escudo-combate-ativo').classList.remove('escondido');

            // Simulação de carregamento de participantes (Personagems + Monstros do combate)
            iniciativaLista = [
                ...campanhaInicialPersonagems.map(a => ({ ...a, tipo: 'Personagem', iniciativa: Math.floor(Math.random() * 20) + 10 })),
                { nm_personagem: 'Criatura de Sangue', qt_vida: 60, qt_vida_maxima: 60, tipo: 'monstro', iniciativa: 15, ds_foto: '../img/logo_icone.png' }
            ].sort((a, b) => b.iniciativa - a.iniciativa);

            renderListaIniciativa();
        }

        function renderListaIniciativa() {
            const container = document.getElementById('lista-iniciativa-escudo');
            if (!container) return;
            container.innerHTML = iniciativaLista.map((p, index) => `
                <div class="item-iniciativa ${index === indexTurno ? 'ativo' : ''}" onclick="selecionarParticipanteEscudo(${index})">
                    <img src="${p.ds_foto || '../img/uploads/perfil/avatar1.png'}" class="img-iniciativa">
                    <div class="info-iniciativa">
                        <h4 style="color: #fff; margin: 0; font-size: 0.95rem;">${p.nm_personagem || p.nm_monstro}</h4>
                        <div class="status-rapido" style="display: flex; gap: 10px; margin-top: 4px;">
                            <span style="color: #ff4d4d; font-size: 0.7rem; font-weight: 700;"><i class="fas fa-heart"></i> ${p.qt_vida}/${p.qt_vida_maxima}</span>
                            ${p.tipo === 'Personagem' ? `<span style="color: #7c3aed; font-size: 0.7rem; font-weight: 700;"><i class="fas fa-brain"></i> ${p.qt_sanidade || 0}</span>` : ''}
                        </div>
                    </div>
                    <div class="valor-iniciativa" style="color: #fff; opacity: 0.5; font-weight: 800; margin-left: auto;">${p.iniciativa}</div>
                </div>
            `).join('');
        }

        function selecionarParticipanteEscudo(index) {
            indexTurno = index;
            participanteSelecionado = iniciativaLista[index];
            renderListaIniciativa();
            renderDetalheParticipante();
        }

        function renderDetalheParticipante() {
            const p = participanteSelecionado;
            if (!p) return;
            const container = document.getElementById('detalhe-participante-escudo');
            if (!container) return;

            container.innerHTML = `
                <div class="detalhe-header">
                    <h2>${p.nm_personagem}</h2>
                    <p>${p.nm_classe || 'Ameaça'} • ${p.tipo === 'Personagem' ? 'Personagem' : 'Monstro'}</p>
                </div>

                <div class="barras-detalhes">
                    ${renderBarraAjustavel('Vida', p.qt_vida, p.qt_vida_maxima, 'vida')}
                    ${p.tipo === 'Personagem' ? renderBarraAjustavel('Sanidade', p.qt_sanidade, p.qt_sanidade_maxima, 'sanidade') : ''}
                    ${p.tipo === 'Personagem' ? renderBarraAjustavel('Esforço', p.qt_esforco, p.qt_esforco_maximo, 'esforco') : ''}
                </div>

                <div class="escudo-sub-nav">
                    <div class="btn-sub-aba ${subTabAtiva === 'atributos' ? 'ativa' : ''}" onclick="switchEscudoSubTab('atributos')">Atributos</div>
                    <div class="btn-sub-aba ${subTabAtiva === 'combates' ? 'ativa' : ''}" onclick="switchEscudoSubTab('combates')">Combates</div>
                    <div class="btn-sub-aba ${subTabAtiva === 'rituais' ? 'ativa' : ''}" onclick="switchEscudoSubTab('rituais')">Rituais</div>
                </div>

                <div id="escudo-sub-aba-content">
                    ${renderSubAbaContent(p)}
                </div>
            `;
        }

        function renderBarraAjustavel(label, atual, max, tipo) {
            return `
                <div class="barra-ajustavel">
                    <div class="controle-recurso">
                        <div style="display: flex; gap: 5px;">
                            <span class="btn-ajuste" onclick="ajustarRecurso('${tipo}', -5)">-5</span>
                            <span class="btn-ajuste" onclick="ajustarRecurso('${tipo}', -1)">-1</span>
                        </div>
                        <div class="valor-barra" style="color: #fff; font-weight: 800;">${label}: ${atual}/${max}</div>
                        <div style="display: flex; gap: 5px;">
                            <span class="btn-ajuste" onclick="ajustarRecurso('${tipo}', 1)">+1</span>
                            <span class="btn-ajuste" onclick="ajustarRecurso('${tipo}', 5)">+5</span>
                        </div>
                    </div>
                    <div class="bg-barra-detalhe">
                        <div class="fill-barra-detalhe fill-${tipo}-d" style="width: ${max > 0 ? (atual / max) * 100 : 0}%"></div>
                    </div>
                </div>
            `;
        }

        function ajustarRecurso(tipo, valor) {
            if (!participanteSelecionado) return;
            const field = tipo === 'vida' ? 'qt_vida' : (tipo === 'sanidade' ? 'qt_sanidade' : 'qt_esforco');
            const maxField = field + '_maxima';

            participanteSelecionado[field] = Math.max(0, Math.min(participanteSelecionado[maxField] || 1, (parseInt(participanteSelecionado[field]) || 0) + valor));

            renderDetalheParticipante();
            renderListaIniciativa();
        }

        function renderSubAbaContent(p) {
            if (subTabAtiva === 'atributos') {
                const attrs = p.atributos || [];
                const listaFinal = attrs.length ? attrs : [
                    { ds_abreviacao: 'AGI', qt_valor: p.qt_agilidade || 0 },
                    { ds_abreviacao: 'FOR', qt_valor: p.qt_forca || 0 },
                    { ds_abreviacao: 'INT', qt_valor: p.qt_intelecto || 0 },
                    { ds_abreviacao: 'PRE', qt_valor: p.qt_presenca || 0 },
                    { ds_abreviacao: 'VIG', qt_valor: p.qt_vigor || 0 }
                ];

                let htmlAttrs = '';
                listaFinal.forEach((attr, i) => {
                    const angle = (i * 2 * Math.PI / listaFinal.length) - (Math.PI / 2);
                    const radius = 90;
                    const x = 150 + radius * Math.cos(angle);
                    const y = 150 + radius * Math.sin(angle);

                    htmlAttrs += `
                        <div class="hex-atributo" style="top: ${y}px; left: ${x}px; transform: translate(-50%, -50%);">
                            <span>${attr.ds_abreviacao || attr.nm_atributo}</span>
                            <strong>${attr.qt_valor}</strong>
                        </div>
                    `;
                });

                return `
                    <div class="diagrama-atributos-real">
                        ${htmlAttrs}
                        <div class="texto-central-atributos" style="color: #666; font-size: 0.7rem;">Atributos</div>
                    </div>
                `;
            } else if (subTabAtiva === 'combates') {
                return `
                    <div style="padding: 20px;">
                        <h4 style="color: #fff; margin-bottom: 15px; font-size: 0.9rem; text-transform: uppercase;">Ataques e Habilidades</h4>
                        <div class="lista-ataques" style="display: flex; flex-direction: column; gap: 10px;">
                            <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 12px; border-left: 4px solid var(--premium-accent);">
                                <h5 style="margin: 0; color: #fff; font-size: 0.9rem;">Ataque Básico</h5>
                                <p style="margin: 5px 0 0; font-size: 0.8rem; color: #888;">Teste: D20 + Pontaria | Dano: 1d10</p>
                            </div>
                        </div>
                    </div>
                `;
            }
            return `<div style="padding: 20px; color: #888; font-size: 0.85rem;">Nenhum ritual ou habilidade especial encontrado.</div>`;
        }

        function switchEscudoSubTab(tab) {
            subTabAtiva = tab;
            renderDetalheParticipante();
        }

        const campanhaInicialPersonagems = <?= json_encode($PersonagemsCampanha) ?>;

        function abrirFichaInvestigacao() {
            document.getElementById('inv-modo-lista').classList.add('escondido');
            document.getElementById('inv-modo-detalhe').classList.remove('escondido');
        }

        function voltarListaInvestigacao() {
            document.getElementById('inv-modo-lista').classList.remove('escondido');
            document.getElementById('inv-modo-detalhe').classList.add('escondido');
        }

        function novaFichaInvestigacao() {
            abrirFichaInvestigacao();
        }

        function abrirRelatorioMissao() {
            document.getElementById('rel-modo-lista').classList.add('escondido');
            document.getElementById('rel-modo-detalhe').classList.remove('escondido');
        }

        function voltarListaRelatorio() {
            document.getElementById('rel-modo-lista').classList.remove('escondido');
            document.getElementById('rel-modo-detalhe').classList.add('escondido');
        }

        function novaRelatorioMissao() {
            abrirRelatorioMissao();
        }

        function setRelStatus(btn) {
            const group = btn.closest('.status-toggle-group');
            group.querySelectorAll('.btn-status-rel').forEach(b => b.classList.remove('ativo'));
            btn.classList.add('ativo');
        }

        // ─── DADOS E ANOTAÇÕES ──────────────────────────────────────
        function rolarDado(lados, elemento, qtd = 1) {
            const container = elemento ? elemento.querySelector('.dado-icon-container') : null;
            const img = container ? container.querySelector('img') : null;
            const originalSrc = img ? img.src : null;

            if (container && img) {
                container.classList.add('dado-girando');
                img.src = `../img/dados/D${lados} efeito.png`;

                setTimeout(() => {
                    container.classList.remove('dado-girando');
                    img.src = originalSrc;
                }, 600);
            }

            let total = 0;
            for (let i = 0; i < qtd; i++) {
                total += Math.floor(Math.random() * lados) + 1;
            }

            adicionarAoHistórico(total, `${qtd}d${lados}`);
        }

        function adicionarAoHistórico(resultado, descricao) {
            const logContainer = document.getElementById('sidebar-dados-lista');
            if (!logContainer) return;

            const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            const novoItem = document.createElement('div');
            novoItem.className = 'item-dado';
            novoItem.innerHTML = `
                <div class="hexa-dado" style="border-color: var(--premium-accent); color: #000; background: #fff; font-weight: 800; display: flex; align-items: center; justify-content: center;">${resultado}</div>
                <div class="info-rolagem">
                    <p>${time} • ${descricao}</p>
                    <h4 style="color: #fff;">Mestre</h4>
                </div>
            `;

            logContainer.prepend(novoItem);
        }

        function togglePopupDados() {
            const popup = document.getElementById('popup-dados');
            const overlay = document.getElementById('overlay-dados');
            if (popup) popup.classList.toggle('escondido');
            if (overlay) {
                overlay.style.display = (overlay.style.display === 'block') ? 'none' : 'block';
            }
        }

        function confirmarRolagemMultipla() {
            const qtd = parseInt(document.getElementById('qtd-dados-multi').value) || 1;
            const lados = parseInt(document.getElementById('lados-dados-multi').value) || 20;

            if (qtd > 10) {
                alert("O limite máximo é de 10 dados por vez!");
                return;
            }

            if (qtd < 1) return;

            rolarDado(lados, null, qtd);
            togglePopupDados();
        }

        // Atalhos do editor
        const bB = document.getElementById('btn-bold'); if (bB) bB.onclick = () => document.execCommand('bold', false, null);
        const bI = document.getElementById('btn-italic'); if (bI) bI.onclick = () => document.execCommand('italic', false, null);
        const bU = document.getElementById('btn-underline'); if (bU) bU.onclick = () => document.execCommand('underline', false, null);

        // Inicialização
        document.addEventListener('DOMContentLoaded', () => {
            if (campanhaInicialPersonagems.length > 0) {
                // Simulação de lista de iniciativa inicial
                iniciativaLista = campanhaInicialPersonagems.map(a => ({
                    ...a,
                    tipo: 'Personagem',
                    iniciativa: Math.floor(Math.random() * 20) + 1
                })).sort((a, b) => b.iniciativa - a.iniciativa);

                participanteSelecionado = iniciativaLista[0];
                renderListaIniciativa();
                renderDetalheParticipante();
            }
        });
    </script>

    <!-- MODAL FICHA MONSTRO (PREMIUM) -->
    <div class="modal-overlay" id="modal-ficha-monstro">
        <div class="modal-box" id="ficha-monstro-render"
            style="width: 600px; padding: 0; background: #0c0816; overflow: hidden; border: 1px solid var(--premium-accent);">
            <!-- Renderizado via AJAX -->
        </div>
    </div>
</body>

</html>
