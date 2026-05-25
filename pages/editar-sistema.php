<?php
session_start();
require_once __DIR__ . '/../app/config/database.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$id_sistema = $_GET['id'] ?? null;
if (!$id_sistema) {
    header('Location: index.php');
    exit;
}

try {
    $pdo = Database::getConexao();

    // Dados do Sistema
    $stmt = $pdo->prepare("SELECT * FROM tb_sistema WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $sistema = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sistema)
        throw new Exception("Sistema não encontrado.");

    $isOficial = (isset($_SESSION['usuario']['cargo']) && strtolower($_SESSION['usuario']['cargo']) === 'admin');
    $isDono = ($sistema['id_usuario_criador'] == $_SESSION['usuario']['id']);

    if (!$isOficial && !$isDono) {
        header('Location: perfil.php');
        exit;
    }
    // Atributos
    $stmtAttr = $pdo->prepare("SELECT * FROM tb_atributo WHERE id_sistema = ?");
    $stmtAttr->execute([$id_sistema]);
    $atributos = $stmtAttr->fetchAll(PDO::FETCH_ASSOC);

    // Classes
    $stmtClasses = $pdo->prepare("SELECT * FROM tb_classe WHERE id_sistema = ?");
    $stmtClasses->execute([$id_sistema]);
    $classes = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

    // Perícias
    $stmtPericias = $pdo->prepare("SELECT * FROM tb_pericia WHERE id_sistema = ?");
    $stmtPericias->execute([$id_sistema]);
    $pericias = $stmtPericias->fetchAll(PDO::FETCH_ASSOC);

    // Origens
    $stmtOrigens = $pdo->prepare("SELECT * FROM tb_origem WHERE id_sistema = ?");
    $stmtOrigens->execute([$id_sistema]);
    $origens = $stmtOrigens->fetchAll(PDO::FETCH_ASSOC);

    // Monstros
    $stmtMonstros = $pdo->prepare("SELECT * FROM tb_monstro WHERE id_sistema = ?");
    $stmtMonstros->execute([$id_sistema]);
    $monstros = $stmtMonstros->fetchAll(PDO::FETCH_ASSOC);

    // Itens (Equipamentos)
    $stmtItens = $pdo->prepare("SELECT * FROM tb_item WHERE id_sistema = ?");
    $stmtItens->execute([$id_sistema]);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    // Habilidades (Poderes)
    $stmtHab = $pdo->prepare("SELECT * FROM tb_habilidade WHERE id_sistema = ?");
    $stmtHab->execute([$id_sistema]);
    $habilidades = $stmtHab->fetchAll(PDO::FETCH_ASSOC);

    foreach ($monstros as &$m) {
        $stmtMAttr = $pdo->prepare("
            SELECT ma.qt_valor as valor, a.ds_abreviacao as abrev 
            FROM tb_monstro_atributo ma 
            JOIN tb_atributo a ON ma.id_atributo = a.id_atributo 
            WHERE ma.id_monstro = ?
        ");
        $stmtMAttr->execute([$m['id_monstro']]);
        $m['atributos'] = $stmtMAttr->fetchAll(PDO::FETCH_ASSOC);
    }

    // Status (Barras e Defesas)
    $stmtStatus = $pdo->prepare("SELECT * FROM tb_sistema_status WHERE id_sistema = ?");
    $stmtStatus->execute([$id_sistema]);
    $status_db = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Erro ao carregar o sistema para edição: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Editar Sistema</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/criar-sistema.css?v=1.3">
</head>

<body class="pagina-criacao-sistema">

    <header>
        <div class="logotipo">
            <a href="index.php"><img src="../img/logo_horizontal.png" alt="Logo TABLE"></a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="cm-jogar.php" class="ativo">Como Jogar</a></li>
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

    <main class="container-sistema">

        <nav class="menu-abas" id="menu-principal">
            <div class="indicador-aba"></div>
            <button type="button" class="aba ativa" data-index="0" data-alvo="aba-descricao">Descrição</button>
            <button type="button" class="aba" data-index="1" data-alvo="aba-atributos">Atributos</button>
            <button type="button" class="aba" data-index="2" data-alvo="aba-status">Status</button>
            <button type="button" class="aba" data-index="3" data-alvo="aba-componentes">Componentes</button>
        </nav>

        <form id="form-criar-sistema">

            <div id="aba-descricao" class="conteudo-aba ativa">
                <section class="secao-topo">
                    <div class="area-imagem">
                        <div class="caixa-imagem" id="preview-imagem">
                            <div class="silhueta-cabeca" id="silhueta-1"></div>
                            <div class="silhueta-corpo" id="silhueta-2"></div>
                        </div>
                        <p class="dica-imagem">Recomendado: 1920x1080px (Wallpaper)</p>
                        <input type="file" id="input-foto-sistema" accept="image/*" hidden>
                        <button type="button" class="btn-contorno" id="btn-trocar-foto">Trocar foto</button>
                    </div>

                    <div class="area-inputs">
                        <div class="grupo-form">
                            <label for="input-nome-sistema">Nome do Sistema:</label>
                            <input type="text" id="input-nome-sistema" class="input-escuro" required
                                placeholder="Digite o nome do sistema...">
                        </div>
                        <div class="grupo-form">
                            <label>Classificação de Idade:</label>
                            <input type="hidden" id="input-classificacao" value="L">
                            <div class="grupo-classificacao" id="botoes-idade">
                                <button type="button" class="btn-idade bg-livre ativo" data-idade="L">L</button>
                                <button type="button" class="btn-idade bg-10" data-idade="10">10</button>
                                <button type="button" class="btn-idade bg-12" data-idade="12">12</button>
                                <button type="button" class="btn-idade bg-14" data-idade="14">14</button>
                                <button type="button" class="btn-idade bg-16" data-idade="16">16</button>
                                <button type="button" class="btn-idade bg-18" data-idade="18">18</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="secao-descricao">
                    <div class="acoes-globais-desc"
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <input type="text" class="input-titulo-desc" id="titulo-desc-1" value="Descrição 1:"
                            style="width: 50%; font-size: 1.2rem; font-weight: 800; margin: 0;">
                        <div class="botoes-acao-desc" style="display: flex; gap: 15px;">
                            <button type="button" class="btn-texto" id="btn-excluir-desc-global">Excluir tópico <i
                                    class="far fa-minus-square"></i></button>
                            <button type="button" class="btn-texto btn-add-desc" id="btn-add-desc-global">Adicionar
                                tópico <i class="far fa-plus-square"></i></button>
                        </div>
                    </div>

                    <div id="container-descricoes" class="lista-descricoes">
                        <div class="item-descricao" id="desc-fixa-1">
                            <textarea class="input-escuro textarea-escuro" required
                                placeholder="Digite os detalhes da Descrição 1 aqui..."></textarea>
                        </div>
                    </div>
                </section>

                <div class="botoes-nav-form apenas-proximo">
                    <button type="button" class="btn-form-nav btn-proximo-aba">Próximo <i
                            class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div id="aba-atributos" class="conteudo-aba">
                <div class="container-painel-duplo">
                    <div class="painel-esquedo">
                        <div class="cabecalho-painel">
                            <h2>ATRIBUTOS</h2>
                            <div>
                                <span class="contador-painel" id="contador-atributos">0/8</span>
                                <button type="button" class="btn-icone-add" id="btn-add-atributo-vazio"><i
                                        class="fas fa-plus-circle"></i></button>
                            </div>
                        </div>
                        <div class="lista-itens" id="lista-atributos">
                        </div>
                    </div>

                    <div class="painel-direito">
                        <h3 id="titulo-painel-attr">Novo Atributo</h3>
                        <div class="grupo-form-painel">
                            <label>Nome do Atributo</label>
                            <input type="text" id="input-nome-atributo" class="input-painel"
                                placeholder="Digite o nome do atributo..." maxlength="12">
                        </div>
                        <div class="grupo-form-painel">
                            <label>Abreviação</label>
                            <input type="text" id="input-abrev-atributo" class="input-painel"
                                placeholder="Digite a abreviação (Máx. 3)..." maxlength="3">
                        </div>
                        <input type="hidden" id="input-valor-atributo" value="0">

                        <div class="acoes-form-painel">
                            <button type="button" id="btn-salvar-atributo" class="btn-salvar-escuro">Salvar</button>
                            <button type="button" id="btn-cancelar-atributo"
                                class="btn-cancelar-escuro">Cancelar</button>
                        </div>
                    </div>
                </div>

                <div class="botoes-nav-form">
                    <button type="button" class="btn-form-nav btn-voltar-aba"><i class="fas fa-arrow-left"></i>
                        Voltar</button>
                    <button type="button" class="btn-form-nav btn-proximo-aba">Próximo <i
                            class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div id="aba-status" class="conteudo-aba">
                <div class="container-painel-duplo">
                    <div class="painel-esquedo" id="container-listas-status-defesa">
                        <div class="cabecalho-painel" style="margin-bottom: 20px;">
                            <h2>BARRAS DE STATUS & DEFESA</h2>
                        </div>

                        <div class="sub-cabecalho">
                            <h3>STATUS</h3>
                            <div>
                                <span class="contador-painel" id="contador-status">1/3</span>
                                <button type="button" class="btn-icone-add" id="btn-add-status-vazio"><i
                                        class="fas fa-plus-circle"></i></button>
                            </div>
                        </div>
                        <div class="lista-itens" id="lista-status">
                        </div>

                        <hr class="divisor-painel">

                        <div class="sub-cabecalho">
                            <h3>DEFESA</h3>
                            <div>
                                <span class="contador-painel" id="contador-defesas">1/3</span>
                                <button type="button" class="btn-icone-add" id="btn-add-defesa-vazio"><i
                                        class="fas fa-plus-circle"></i></button>
                            </div>
                        </div>
                        <div class="lista-itens" id="lista-defesas">
                        </div>
                    </div>

                    <div class="painel-direito">
                        <h3 id="titulo-painel-status">Novo Status</h3>
                        <div class="grupo-form-painel">
                            <label>Nome do Status</label>
                            <input type="text" id="input-nome-status" class="input-painel"
                                placeholder="Digite o nome do status..." maxlength="12">
                        </div>
                        <div class="grupo-form-painel">
                            <label>Cor</label>
                            <input type="color" id="input-cor-status" class="btn-cor" value="#ed1c24">
                        </div>
                        <div class="grupo-form-painel">
                            <label>Atributo Base</label>
                            <input type="hidden" id="input-base-status" value="null">
                            <div class="grupo-botoes-sel" id="botoes-base-status">
                                <button type="button" class="btn-sel btn-sel-base ativo"
                                    data-base="null">&Oslash;</button>
                            </div>
                            <div class="scrollbar-custom-track" id="scroll-track-base">
                                <div class="scrollbar-custom-thumb" id="scroll-thumb-base"></div>
                            </div>
                        </div>
                        <div class="acoes-form-painel">
                            <button type="button" id="btn-salvar-status" class="btn-salvar-escuro">Salvar</button>
                            <button type="button" id="btn-cancelar-status" class="btn-cancelar-escuro">Cancelar</button>
                        </div>
                    </div>
                </div>

                <div class="botoes-nav-form" style="margin-bottom: 100px;">
                    <button type="button" class="btn-form-nav btn-voltar-aba"><i class="fas fa-arrow-left"></i>
                        Voltar</button>
                    <button type="button" class="btn-form-nav btn-proximo-aba">Próximo <i
                            class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div id="aba-componentes" class="conteudo-aba">
                <div class="container-componentes">
                    <div class="menu-componentes" id="menu-comp">
                        <button type="button" class="btn-comp-aba ativa" data-index="0">CLASSES</button>
                        <button type="button" class="btn-comp-aba" data-index="1">PERÍCIAS</button>
                        <button type="button" class="btn-comp-aba" data-index="2">ORIGENS</button>
                        <button type="button" class="btn-comp-aba" data-index="3">EQUIPAMENTOS</button>
                        <button type="button" class="btn-comp-aba" data-index="4">PODERES</button>
                        <button type="button" class="btn-comp-aba" data-index="5">AMEAÇAS</button>
                    </div>

                    <div class="cabecalho-comp">
                        <span class="btn-criar-nova" id="btn-criar-comp">Criar Nova <i
                                class="fas fa-plus-circle"></i></span>
                        <span class="contador-comp" id="contador-comp-atual">0/30</span>
                    </div>

                    <div class="viewport-comp">
                        <div class="track-comp" id="track-comp">
                            <div class="painel-categoria" data-cat="CLASSES"></div>
                            <div class="painel-categoria" data-cat="PERÍCIAS"></div>
                            <div class="painel-categoria" data-cat="ORIGENS"></div>
                            <div class="painel-categoria" data-cat="EQUIPAMENTOS"></div>
                            <div class="painel-categoria" data-cat="PODERES"></div>
                            <div class="painel-categoria" data-cat="MONSTROS"></div>
                        </div>
                    </div>
                </div>

                <div class="botoes-nav-form">
                    <button type="button" class="btn-form-nav btn-voltar-aba"><i class="fas fa-arrow-left"></i>
                        Voltar</button>
                    <button type="submit" class="btn-concluir">Salvar Sistema <i class="fas fa-check"></i></button>
                </div>
            </div>

        </form>
    </main>

    <div class="modal-overlay" id="modal-comp">
        <div class="modal-box">
            <h3 id="modal-comp-titulo">Criar Componente</h3>

            <div class="grupo-form-painel">
                <label>Nome</label>
                <input type="text" id="modal-input-nome" class="input-painel" placeholder="EX: COMBATENTE, ATLETA..."
                    maxlength="12">
            </div>

            <div class="grupo-form-painel">
                <label id="lbl-val1">Descrição</label>
                <textarea id="modal-input-val1" class="input-painel" placeholder="Detalhes breves..."
                    style="min-height: 100px; max-height: 100px; resize: none; overflow-y: auto;"></textarea>
            </div>

            <div class="grupo-form-painel">
                <label id="lbl-val2">Habilidades / Extras</label>
                <textarea id="modal-input-val2" class="input-painel" placeholder="Ataque especial, Bônus..."
                    style="min-height: 100px; max-height: 100px; resize: none; overflow-y: auto; margin-bottom: 25px;"></textarea>
            </div>

            <div class="grupo-form-painel" id="grupo-val3" style="display: none;">
                <label id="lbl-val3">Atributo Base</label>
                <select id="modal-select-val3" class="input-painel" style="margin-bottom: 25px; height: 50px; cursor: pointer;">
                </select>
            </div>

            <div class="acoes-form-painel" style="justify-content: space-between;">
                <button type="button" class="btn-cancelar-escuro" id="btn-excluir-modal"
                    style="background-color: #ff4d4d; border-color: #ff4d4d; display: none;">Excluir</button>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-cancelar-escuro" id="btn-fechar-modal">Cancelar</button>
                    <button type="button" class="btn-salvar-escuro" id="btn-salvar-modal">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CRIAR MONSTRO (PREMIUM) -->
    <div class="modal-overlay" id="modal-criar-monstro">
        <div class="modal-box" style="max-width: 650px; max-height: 90vh; overflow-y: auto;">
            <i class="fas fa-times modal-close" onclick="fecharModal('modal-criar-monstro')"
                style="position: absolute; top: 20px; right: 20px; font-size: 1.5rem; color: #fff; cursor: pointer; transition: 0.3s;"></i>

            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: #fff; font-size: 1.8rem; font-weight: 900; letter-spacing: -1px; margin-bottom: 5px;">
                    NOVA AMEAÇA</h2>
                <p style="color: #666; font-size: 0.9rem;">Catalogando perigos do Outro Lado no sistema.</p>
            </div>

            <div id="form-criar-ameaca">
                <input type="hidden" id="m-id-local" value="">
                <div class="form-section-title"
                    style="color: var(--premium-accent); font-size: 0.8rem; font-weight: 900; text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px;">
                    <i class="fas fa-fingerprint"></i> IDENTIDADE
                </div>

                <div style="display: flex; gap: 25px; align-items: stretch; margin-bottom: 25px;">
                    <div id="preview-monstro-container" onclick="document.getElementById('m-foto').click()" style="width: 120px; height: 120px; border: 2px dashed rgba(157, 122, 255, 0.3); border-radius: 20px; 
                                display: flex; align-items: center; justify-content: center; cursor: pointer; 
                                background: rgba(0,0,0,0.4); overflow: hidden; transition: 0.3s; position: relative;">
                        <i class="fas fa-cloud-upload-alt"
                            style="font-size: 2rem; color: var(--premium-accent); opacity: 0.5;"></i>
                        <span
                            style="position: absolute; bottom: 10px; font-size: 0.6rem; color: #aaa; font-weight: 800; text-transform: uppercase;">Imagem</span>
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div class="input-premium-group"
                            style="margin-bottom: 10px; display: flex; flex-direction: column;">
                            <label class="input-premium-label"
                                style="font-size: 0.7rem; font-weight: 800; color: #888; margin-bottom: 5px;">NOME DA
                                CRIATURA</label>
                            <input type="text" id="m-nome" class="input-premium-field"
                                placeholder="Ex: Degolador, Aniquilação..."
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px 15px; border-radius: 10px; font-family: 'Montserrat', sans-serif; outline: none;">
                        </div>
                        <input type="file" id="m-foto" accept="image/*" style="display: none;"
                            onchange="previewImagemMonstro(this)">
                        <div class="input-premium-group" style="margin: 0; display: flex; flex-direction: column;">
                            <label class="input-premium-label"
                                style="font-size: 0.7rem; font-weight: 800; color: #888; margin-bottom: 5px;">TIPO /
                                ELEMENTO</label>
                            <input type="text" id="m-tipo" class="input-premium-field"
                                placeholder="Ex: Medo, Conhecimento..."
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px 15px; border-radius: 10px; font-family: 'Montserrat', sans-serif; outline: none;">
                        </div>
                    </div>
                </div>

                <div class="form-section-title"
                    style="color: var(--premium-accent); font-size: 0.8rem; font-weight: 900; text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px;">
                    <i class="fas fa-skull"></i> STATUS DE COMBATE
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 15px;">
                    <div class="input-premium-group" style="display: flex; flex-direction: column;">
                        <label class="input-premium-label"
                            style="color: #ff4d4d; font-size: 0.7rem; font-weight: 800; margin-bottom: 5px;">NÍVEL DE
                            PERIGO (VD)</label>
                        <input type="number" id="m-vd" class="input-premium-field"
                            style="background: rgba(255, 77, 77, 0.05); border: 1px solid rgba(255, 77, 77, 0.2); color: #ff4d4d; font-weight: 900; padding: 12px 15px; border-radius: 10px; font-family: 'Montserrat', sans-serif; outline: none;"
                            placeholder="0">
                    </div>
                    <div class="input-premium-group" style="display: flex; flex-direction: column;">
                        <label class="input-premium-label"
                            style="color: #f1c40f; font-size: 0.7rem; font-weight: 800; margin-bottom: 5px;">RECOMPENSA
                            (XP)</label>
                        <input type="number" id="m-xp" class="input-premium-field"
                            style="background: rgba(241, 196, 15, 0.05); border: 1px solid rgba(241, 196, 15, 0.2); color: #f1c40f; font-weight: 900; padding: 12px 15px; border-radius: 10px; font-family: 'Montserrat', sans-serif; outline: none;"
                            placeholder="0">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 25px;">
                    <div class="input-premium-group" style="display: flex; flex-direction: column;">
                        <label class="input-premium-label"
                            style="font-size: 0.7rem; font-weight: 800; color: #888; margin-bottom: 5px;">PONTOS DE
                            VIDA</label>
                        <input type="number" id="m-vida" class="input-premium-field"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px 15px; border-radius: 10px; font-family: 'Montserrat', sans-serif; outline: none;"
                            placeholder="0">
                    </div>
                    <div class="input-premium-group" style="display: flex; flex-direction: column;">
                        <label class="input-premium-label"
                            style="font-size: 0.7rem; font-weight: 800; color: #888; margin-bottom: 5px;">DEFESA</label>
                        <input type="number" id="m-defesa" class="input-premium-field"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px 15px; border-radius: 10px; font-family: 'Montserrat', sans-serif; outline: none;"
                            placeholder="0">
                    </div>
                </div>

                <div class="form-section-title"
                    style="color: var(--premium-accent); font-size: 0.8rem; font-weight: 900; text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px;">
                    <i class="fas fa-dice-d20"></i> ATRIBUTOS
                </div>
                <div id="grid-atributos-monstro"
                    style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.03); margin-bottom: 25px;">
                    <!-- Preenchido via JS dinamicamente com base em atributosObj -->
                </div>

                <div class="form-section-title"
                    style="color: var(--premium-accent); font-size: 0.8rem; font-weight: 900; text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px;">
                    <i class="fas fa-align-left"></i> DETALHES
                </div>
                <div class="input-premium-group" style="display: flex; flex-direction: column;">
                    <label class="input-premium-label"
                        style="font-size: 0.7rem; font-weight: 800; color: #888; margin-bottom: 5px;">DESCRIÇÃO E
                        HABILIDADES</label>
                    <textarea id="m-desc" class="input-premium-field"
                        style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px 15px; border-radius: 10px; font-family: 'Montserrat', sans-serif; outline: none; height: 120px; resize: none;"
                        placeholder="Descreva as peculiaridades e poderes desta ameaça..."></textarea>
                </div>

                <button type="button" class="btn-p" id="btn-save-monstro-local"
                    style="width: 100%; margin-top: 10px; padding: 20px; background: linear-gradient(135deg, var(--premium-purple), var(--premium-accent)); border: none; border-radius: 15px; color: #fff; font-weight: 900; font-size: 1rem; letter-spacing: 3px; text-transform: uppercase; cursor: pointer; transition: 0.4s; box-shadow: 0 10px 40px rgba(157, 122, 255, 0.3);"
                    onclick="salvarMonstro()">
                    <i class="fas fa-skull"></i> CONVOCAR AMEAÇA
                </button>
            </div>
        </div>
    </div>

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

    <script>
        const ID_SISTEMA_EDIT = <?= json_encode($id_sistema) ?>;
        const SYSTEM_DB = <?= json_encode($sistema) ?>;
        const ATRIBS_DB = <?= json_encode($atributos) ?>;
        const STATUS_DB = <?= json_encode($status_db) ?>;
        const CLASSES_DB = <?= json_encode($classes) ?>;
        const PERICIAS_DB = <?= json_encode($pericias) ?>;
        const ORIGENS_DB = <?= json_encode($origens) ?>;
        const MONSTROS_DB = <?= json_encode($monstros) ?>;
        const ITENS_DB = <?= json_encode($itens) ?>;
        const PODERES_DB = <?= json_encode($habilidades) ?>;
    </script>

    <script src="../js/nav-global.js?v=1.2" defer></script>
    <script src="../js/editar-sistema.js?v=1.7" defer></script>
</body>

</html>

