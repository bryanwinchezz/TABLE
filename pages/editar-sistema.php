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

    // Habilidades e Poderes
    $stmtHab = $pdo->prepare("SELECT * FROM tb_habilidade WHERE id_sistema = ?");
    $stmtHab->execute([$id_sistema]);
    $habilidades_todas = $stmtHab->fetchAll(PDO::FETCH_ASSOC);

    $habilidades = array_filter($habilidades_todas, function($h) {
        return strtolower($h['tp_habilidade'] ?? '') !== 'poder';
    });
    $poderes = array_filter($habilidades_todas, function($h) {
        return strtolower($h['tp_habilidade'] ?? '') === 'poder';
    });

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
    <link rel="shortcut icon" href="../img/logo_branco1.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/criar-sistema.css?v=1.3">
    <style>
        .track-comp { width: 600% !important; }
        .painel-categoria { width: 16.666% !important; }
    </style>
</head>

<?php
$classeBackground = '';
if (isset($sistema['nm_sistema'])) {
    $nomeSistemaLower = strtolower($sistema['nm_sistema']);
    if (strpos($nomeSistemaLower, 'ordem paranormal') !== false) {
        $classeBackground = 'tema-ordem-paranormal';
    }
}
?>
<body class="pagina-criacao-sistema <?= $classeBackground ?>">

    <header>
        <div class="logotipo">
            <a href="index.php"><img src="../img/logo_horizontal1.png" alt="Logo TABLE"></a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="cm-jogar.php" class="ativo">Como Jogar</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
                </li>
                <li><a href="<?= isset($_SESSION['usuario']['cargo']) && in_array(strtolower($_SESSION['usuario']['cargo']), ['mestre','admin']) ? 'criar-mapa.php' : 'editar-perfil.php?abrir_mestre=1'; ?>">Mundos</a></li>
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
                        style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 10px;">
                        <div class="botoes-acao-desc" style="display: flex; gap: 15px;">
                            <button type="button" class="btn-texto" id="btn-excluir-desc-global">Excluir tópico <i
                                    class="far fa-minus-square"></i></button>
                            <button type="button" class="btn-texto btn-add-desc" id="btn-add-desc-global">Adicionar
                                tópico <i class="far fa-plus-square"></i></button>
                        </div>
                    </div>

                    <div id="container-descricoes" class="lista-descricoes">
                        <div class="item-descricao" id="desc-fixa-1">
                            <div class="cabecalho-descricao" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <input type="text" id="titulo-desc-1" class="input-titulo-desc" value="Descrição 1:" style="width: 50%;">
                            </div>
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
                                placeholder="Digite o nome do atributo..." maxlength="50">
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
                        <button type="button" class="btn-comp-aba ativa" data-index="0" data-cat="CLASSES">CLASSES</button>
                        <button type="button" class="btn-comp-aba" data-index="1" data-cat="PERÍCIAS">PERÍCIAS</button>
                        <button type="button" class="btn-comp-aba" data-index="2" data-cat="ORIGENS">ORIGENS</button>
                        <button type="button" class="btn-comp-aba" data-index="3" data-cat="EQUIPAMENTOS">EQUIPAMENTOS</button>
                        <button type="button" class="btn-comp-aba" data-index="4" data-cat="PODERES">PODERES</button>
                        <button type="button" class="btn-comp-aba" data-index="5" data-cat="AMEAÇAS">AMEAÇAS</button>
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
        <div class="modal-box" style="width: 550px; max-height: 80vh; padding: 0; background: #0c0816; overflow-y: auto; overflow-x: hidden; border: 1.5px solid var(--premium-accent); border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.85);">
            <div id="form-criar-ameaca">
                <input type="hidden" id="m-id-local" value="">
                <input type="hidden" id="m-imagem-atual" value="">
                
                <!-- Header no estilo Ficha de Monstro -->
                <div class="ficha-header-comp" id="m-header-gradient" style="position: sticky; top: 0; z-index: 100; background: linear-gradient(135deg, rgba(30, 11, 58, 0.95), rgba(49, 28, 97, 0.9)), url('../img/uploads/perfil/avatar1.png') center/cover; padding: 25px 30px; border-bottom: 2px solid var(--premium-accent); display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.4);">
                    <div id="preview-monstro-container" onclick="document.getElementById('m-foto').click()" style="width: 100px; height: 100px; border-radius: 15px; border: 3px solid var(--premium-accent); object-fit: cover; box-shadow: 0 10px 30px rgba(0,0,0,0.8); cursor: pointer; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.4); flex-shrink: 0;">
                        <img id="m-foto-preview" src="../img/uploads/perfil/avatar1.png" style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.6); color: #fff; font-size: 0.55rem; text-align: center; padding: 3px 0; font-weight: bold; text-transform: uppercase;">Mudar</div>
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                        <input type="text" id="m-nome" class="input-premium-field" placeholder="Nome da Ameaça..." style="font-weight: 900; font-size: 1.6rem; color: #fff; background: rgba(0,0,0,0.4); border: 1px dashed rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 8px; width: 90%; outline: none;" required>
                        <input type="file" id="m-foto" accept="image/*" style="display: none;" onchange="previewImagemMonstro(this)">
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" id="m-tipo" class="input-premium-field" placeholder="TIPO / ELEMENTO" style="background: var(--premium-accent); color: #fff; padding: 4px 12px; border-radius: 6px; font-weight: 800; font-size: 0.8rem; border: 1.5px dashed rgba(255,255,255,0.5); outline: none; width: 130px; cursor: text;">
                            <div style="display: flex; align-items: center; background: rgba(255, 50, 50, 0.2); border: 1px solid rgba(255, 50, 50, 0.5); padding: 2px 10px; border-radius: 6px; gap: 5px; height: 26px; box-sizing: border-box;">
                                <span id="label-vd-vt" style="color: #ff4d4d; font-weight: 900; font-size: 0.8rem; text-transform: uppercase;">VD:</span>
                                <input type="number" id="m-vd" placeholder="0" style="background: transparent; color: #ff4d4d; border: none; font-weight: 900; font-size: 0.8rem; width: 40px; outline: none; text-align: center; padding: 0; margin: 0;">
                            </div>
                        </div>
                    </div>
                    <i class="fas fa-times" onclick="fecharModal('modal-criar-monstro')" style="color: #fff; cursor: pointer; font-size: 1.5rem; filter: drop-shadow(0 2px 5px rgba(0,0,0,0.8)); transition: 0.3s;" onmouseover="this.style.color='var(--premium-accent)'" onmouseout="this.style.color='#fff'"></i>
                </div>
                
                <!-- Corpo no estilo Ficha de Monstro -->
                <div style="padding: 25px; background: #0c0816;">
                    <div id="m-status-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 25px;">
                        <div id="box-m-vida" style="background: rgba(255, 77, 77, 0.05); padding: 15px; border-radius: 12px; border: 1px solid rgba(255, 77, 77, 0.2); display: flex; flex-direction: column; align-items: center;">
                            <span style="display: block; color: #ff4d4d; font-weight: 900; font-size: 0.75rem; margin-bottom: 5px; letter-spacing: 1px;"><i class="fas fa-heart"></i> PONTOS DE VIDA</span>
                            <input type="number" id="m-vida" style="background: transparent; border: none; border-bottom: 1px dashed rgba(255,255,255,0.2); color: #fff; font-size: 1.8rem; font-weight: bold; width: 80px; text-align: center; outline: none;" placeholder="0">
                        </div>
                        <div id="box-m-vd" style="background: rgba(255, 77, 77, 0.05); padding: 15px; border-radius: 12px; border: 1px solid rgba(255, 77, 77, 0.2); display: none; flex-direction: column; align-items: center;">
                            <span id="label-box-vd-vt" style="display: block; color: #ff4d4d; font-weight: 900; font-size: 0.75rem; margin-bottom: 5px; letter-spacing: 1px;"><i class="fas fa-heart"></i> VIDA TOTAL (VT)</span>
                            <input type="number" id="m-vd-status" style="background: transparent; border: none; border-bottom: 1px dashed rgba(255,255,255,0.2); color: #fff; font-size: 1.8rem; font-weight: bold; width: 80px; text-align: center; outline: none;" placeholder="0">
                        </div>
                        <div style="background: rgba(41, 128, 185, 0.05); padding: 15px; border-radius: 12px; border: 1px solid rgba(41, 128, 185, 0.2); display: flex; flex-direction: column; align-items: center;">
                            <span style="display: block; color: #3498db; font-weight: 900; font-size: 0.75rem; margin-bottom: 5px; letter-spacing: 1px;"><i class="fas fa-shield-alt"></i> DEFESA</span>
                            <input type="number" id="m-defesa" style="background: transparent; border: none; border-bottom: 1px dashed rgba(255,255,255,0.2); color: #fff; font-size: 1.8rem; font-weight: bold; width: 80px; text-align: center; outline: none;" placeholder="0">
                        </div>
                    </div>
                    
                    <input type="hidden" id="m-xp" value="0">

                    <label style="color: var(--premium-accent); font-size: 0.8rem; font-weight: 900; text-transform: uppercase; margin-bottom: 15px; display: block; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px;">ATRIBUTOS PRINCIPAIS</label>
                    <div class="premium-atributos-grid" id="grid-atributos-monstro" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 25px;">
                        <!-- Preenchido via JS dinamicamente -->
                    </div>

                    <label style="color: var(--premium-accent); font-size: 0.8rem; font-weight: 900; text-transform: uppercase; margin-bottom: 15px; display: block; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px;">DESCRIÇÃO / COMPORTAMENTO</label>
                    <div style="background: rgba(0,0,0,0.5); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 25px;">
                        <textarea id="m-desc" style="width: 100%; height: 120px; background: transparent; border: none; color: #ccc; font-size: 0.95rem; line-height: 1.8; resize: none; outline: none; padding: 0; box-shadow: none;" placeholder="Descreva as peculiaridades e poderes desta ameaça..."></textarea>
                    </div>

                    <div class="acoes-form-painel" style="display: flex; justify-content: space-between; gap: 15px; margin-top: 25px;">
                        <button type="button" class="btn-cancelar-escuro" id="btn-excluir-monstro" style="background-color: #ff4d4d; border-color: #ff4d4d; display: none;">Excluir</button>
                        <div style="display: flex; gap: 10px; width: 100%; justify-content: flex-end;" id="botoes-acao-monstro-container">
                            <button type="button" class="btn-cancelar-escuro" onclick="fecharModal('modal-criar-monstro')">Cancelar</button>
                            <button type="button" class="btn-premium-dragon" id="btn-save-monstro-local" style="padding: 15px 30px;" onclick="salvarMonstro()">
                                <i class="fas fa-skull"></i> ATUALIZAR AMEAÇA
                            </button>
                        </div>
                    </div>
                </div>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script src="../js/cropper-helper.js?v=<?= time() ?>"></script>
    <script src="../js/nav-global.js?v=1.2" defer></script>
    <script src="../js/editar-sistema.js?v=1.8" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const inputNome = document.getElementById('input-nome-sistema');
            if (inputNome) {
                const checkTheme = () => {
                    if (inputNome.value.toLowerCase().includes('ordem paranormal')) {
                        document.body.classList.add('tema-ordem-paranormal');
                    } else {
                        document.body.classList.remove('tema-ordem-paranormal');
                    }
                };
                inputNome.addEventListener('input', checkTheme);
                checkTheme();
            }
        });
    </script>
</body>

</html>


