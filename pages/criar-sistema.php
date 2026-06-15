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

// Verificação de Paywall Granular - Plano de Sistemas
require_once __DIR__ . '/../app/config/database.php';
try {
    $pdo = Database::getConexao();

    $stmt = $pdo->prepare("SELECT fl_plano_sistemas, fl_plano_completo, ds_api_key_gemini FROM tb_usuario WHERE id_usuario = ? LIMIT 1");
    $stmt->execute([$_SESSION['usuario']['id']]);
    $userPlan = $stmt->fetch();
    
    if (!$userPlan || ((int)$userPlan['fl_plano_sistemas'] !== 1 && (int)$userPlan['fl_plano_completo'] !== 1)) {
        header('Location: planos.php?aviso=sistemas');
        exit;
    }
    
    $temApiKey = !empty($userPlan['ds_api_key_gemini']) ? 'true' : 'false';
} catch (Exception $e) {
    $temApiKey = 'false';
}
$fotoNavbar = (!empty($_SESSION['usuario']['foto']) && file_exists(dirname(__DIR__) . '/' . ltrim(str_replace('../', '', $_SESSION['usuario']['foto']), '/'))) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Criar Sistema</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="shortcut icon" href="../img/logo_branco1.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/table-modal.css">
    <link rel="stylesheet" href="../css/criar-sistema.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script src="../js/cropper-helper.js"></script>
    <script src="../js/table-modal.js"></script>
    <script>
        // Fallback robusto para TableModal caso o adblocker bloqueie o script de modal
        if (typeof TableModal === 'undefined') {
            window.TableModal = {
                alert: async function(mensagem, titulo = 'Aviso', tipo = 'info') {
                    alert((titulo ? titulo + "\n\n" : "") + mensagem);
                    return true;
                },
                confirm: async function(mensagem, titulo = 'Confirmar', tipo = 'question') {
                    return confirm((titulo ? titulo + "\n\n" : "") + mensagem);
                },
                prompt: async function(mensagem, padrao = '', titulo = 'Entrada') {
                    return prompt((titulo ? titulo + "\n\n" : "") + mensagem, padrao);
                }
            };
        }
        const TEM_API_KEY = <?= $temApiKey ?>;
    </script>
</head>

<body class="pagina-criacao-sistema">

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

        <nav class="menu-abas" id="menu-principal">
            <div class="indicador-aba"></div>
            <button type="button" class="aba ativa" data-index="0" data-alvo="aba-descricao">Descrição</button>
            <button type="button" class="aba" data-index="1" data-alvo="aba-atributos">Atributos</button>
            <button type="button" class="aba" data-index="2" data-alvo="aba-status">Status</button>
            <button type="button" class="aba" data-index="3" data-alvo="aba-componentes">Componentes</button>
        </nav>

        <form id="form-criar-sistema" novalidate>

            <div id="aba-descricao" class="conteudo-aba ativa">
                <div class="ia-geracao-container" style="background: linear-gradient(135deg, rgba(123, 79, 247, 0.15), rgba(74, 42, 133, 0.15)); border: 1px solid rgba(123, 79, 247, 0.3); border-radius: 12px; padding: 20px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; gap: 20px;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 5px 0; color: #fff; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-wand-magic-sparkles" style="color: #7b4ff7;"></i> Canalizar com CassIA
                        </h4>
                        <p style="margin: 0; color: #aaa; font-size: 0.85rem; line-height: 1.4;">
                            Tem um conceito na mente? A CassIA (A IA do TABLE para criar sistemas, fichas e histórias) pode criar todo o universo, atributos, perícias, barras de status, classes e perigos instantaneamente para você revisar e refinar!
                        </p>
                    </div>
                    <button type="button" id="btn-ia-sistema" class="btn-ia-premium" style="background: linear-gradient(135deg, #7b4ff7, #4a2a85); color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(123, 79, 247, 0.4); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(123, 79, 247, 0.6)'" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 15px rgba(123, 79, 247, 0.4)'">
                        <i class="fas fa-wand-magic-sparkles"></i> Gerar com CassIA
                    </button>
                </div>

                <section class="secao-topo">
                    <div class="area-imagem">
                        <div class="caixa-imagem" id="preview-imagem">
                            <div class="silhueta-cabeca" id="silhueta-1"></div>
                            <div class="silhueta-corpo" id="silhueta-2"></div>
                        </div>
                        <p class="dica-imagem">Recomendado: 1920x1080px (Wallpaper)</p>
                        <input type="file" id="input-foto-sistema" accept="image/*" hidden>
                        <div class="btn-container-foto">
                            <button type="button" class="btn-contorno" id="btn-trocar-foto">Trocar foto</button>
                        </div>
                    </div>

                    <div class="area-inputs">
                        <div class="grupo-form">
                            <label for="input-nome-sistema">Nome do Sistema:</label>
                            <input type="text" id="input-nome-sistema" class="input-escuro"
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
                            <textarea class="input-escuro textarea-escuro"
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
                        <button type="button" class="btn-comp-aba ativa" data-index="0">CLASSES</button>
                        <button type="button" class="btn-comp-aba" data-index="1">PERÍCIAS</button>
                        <button type="button" class="btn-comp-aba" data-index="2">ORIGENS</button>
                        <button type="button" class="btn-comp-aba" data-index="3">EQUIPAMENTOS</button>
                        <button type="button" class="btn-comp-aba" data-index="4">HABILIDADES</button>
                        <button type="button" class="btn-comp-aba" data-index="5">PODERES</button>
                        <button type="button" class="btn-comp-aba" data-index="6">AMEAÇAS</button>
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
                            <div class="painel-categoria" data-cat="HABILIDADES"></div>
                            <div class="painel-categoria" data-cat="PODERES"></div>
                            <div class="painel-categoria" data-cat="AMEAÇAS"></div>
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
                    style="height: 100px; resize: none; margin-bottom: 25px;"></textarea>
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
            <i class="fas fa-times modal-close" onclick="fecharModal('modal-criar-monstro')"></i>

            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: #fff; font-size: 1.8rem; font-weight: 900; letter-spacing: -1px; margin-bottom: 5px;">
                    NOVA AMEAÇA</h2>
                <p style="color: #666; font-size: 0.9rem;">Catalogando perigos do Outro Lado</p>
            </div>
            <div id="form-criar-ameaca">
                <input type="hidden" id="m-id-local" value="">
                <div class="form-section-title"><i class="fas fa-fingerprint"></i> IDENTIDADE</div>

                <div class="monstro-identidade-container">
                    <div id="preview-monstro-container" onclick="document.getElementById('m-foto').click()" style="width: 120px; height: 120px; border: 2px dashed rgba(157, 122, 255, 0.3); border-radius: 20px; 
                                 display: flex; align-items: center; justify-content: center; cursor: pointer; 
                                 background: rgba(0,0,0,0.4); overflow: hidden; transition: 0.3s; position: relative; flex-shrink: 0;">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: var(--premium-accent); opacity: 0.5;"></i>
                        <span style="position: absolute; bottom: 10px; font-size: 0.6rem; color: #aaa; font-weight: 800; text-transform: uppercase;">Imagem</span>
                    </div>
                    <div class="monstro-identidade-inputs">
                        <div class="input-premium-group" style="margin-bottom: 10px;">
                            <label class="input-premium-label">NOME DA AMEAÇA</label>
                            <input type="text" id="m-nome" class="input-premium-field" placeholder="Ex: Degolador, Aniquilação...">
                        </div>
                        <input type="file" id="m-foto" accept="image/*" style="display: none;" onchange="previewImagemMonstro(this)">
                        <div class="input-premium-group" style="margin: 0;">
                            <label class="input-premium-label">TIPO / ELEMENTO</label>
                            <input type="text" id="m-tipo" class="input-premium-field" placeholder="Ex: Medo, Conhecimento...">
                        </div>
                    </div>
                </div>

                <div class="form-section-title"><i class="fas fa-skull"></i> STATUS DE COMBATE</div>

                <input type="hidden" id="m-vd" value="0">
                <input type="hidden" id="m-xp" value="0">

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 25px;">
                    <div class="input-premium-group">
                        <label class="input-premium-label" style="color: #ff4d4d;">VIDA TOTAL</label>
                        <input type="number" id="m-vida" class="input-premium-field" style="border-color: rgba(255, 77, 77, 0.2); color: #ff4d4d; font-weight: 900;" placeholder="0">
                    </div>
                    <div class="input-premium-group">
                        <label class="input-premium-label">DEFESA</label>
                        <input type="number" id="m-defesa" class="input-premium-field" placeholder="0">
                    </div>
                </div>

                <div class="form-section-title"><i class="fas fa-dice-d20"></i> ATRIBUTOS</div>
                <div id="grid-atributos-monstro" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px 10px; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.03); margin-bottom: 25px;">
                    <!-- Preenchido via JS dinamicamente com base em atributosObj -->
                </div>

                <div class="form-section-title"><i class="fas fa-align-left"></i> DETALHES</div>
                <div class="input-premium-group">
                    <label class="input-premium-label">DESCRIÇÃO E HABILIDADES</label>
                    <textarea id="m-desc" class="input-premium-field" style="height: 120px; resize: none;" placeholder="Descreva as peculiaridades e poderes desta ameaça..."></textarea>
                </div>

                <div class="acoes-form-painel" style="justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn-cancelar-escuro" onclick="fecharModal('modal-criar-monstro')">Cancelar</button>
                    <button type="button" class="btn-premium-dragon" id="btn-save-monstro-local" style="padding: 15px 30px;" onclick="salvarMonstro()">
                        <i class="fas fa-skull"></i> CONVOCAR AMEAÇA
                    </button>
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
    <script src="../js/criar-sistema.js?v=<?= time() ?>" defer></script>
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

    <!-- MODAL DE CANALIZAÇÃO DE IA (SISTEMA) -->
    <div class="modal-overlay" id="modal-ia-sistema" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:99999; justify-content:center; align-items:center; backdrop-filter: blur(10px);">
        <div class="modal-box" style="max-width:550px; background:#1e1b26; border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6); border-radius: 16px; padding: 35px; font-family: 'Montserrat', sans-serif; position: relative;">
            <i class="fas fa-times modal-close" onclick="const modal = document.getElementById('modal-ia-sistema'); if(modal) { modal.style.display='none'; modal.classList.remove('ativo'); }" style="position:absolute; right:24px; top:24px; color:#aaa; cursor:pointer; font-size:1.3rem; transition: color 0.2s, transform 0.2s;" onmouseover="this.style.color='#7b4ff7'; this.style.transform='scale(1.15)'" onmouseout="this.style.color='#aaa'; this.style.transform='none'"></i>
            
            <!-- Conteúdo de Input -->
            <div id="ia-input-container">
                <div style="text-align: center; margin-top: 15px; margin-bottom: 30px;">
                    <h2 style="color: #fff; font-size: 1.8rem; font-weight: 900; letter-spacing: -1px; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <i class="fas fa-wand-magic-sparkles" style="color: #7b4ff7;"></i> CANALIZAR SISTEMA
                    </h2>
                    <p style="color: #aaa; font-size: 0.9rem;">Dê vida ao seu cenário de RPG através de inteligência artificial</p>
                </div>

                <div class="grupo-form-painel" style="margin-bottom: 25px;">
                    <label style="color:#fff; font-weight:700; font-size:0.9rem; display:block; margin-bottom:10px;">Descreva o conceito do seu sistema:</label>
                    <textarea id="ia-conceito-texto" class="input-painel" style="height: 120px; resize: none; width: 100%; box-sizing: border-box; background: rgba(0,0,0,0.3); border: 1px solid rgba(123,79,247,0.3); border-radius: 8px; color: #fff; padding: 12px; font-family: inherit; font-size: 0.95rem; line-height: 1.5; outline: none; transition: border-color 0.2s;" placeholder="Ex: Um sistema pós-apocalíptico focado em sobrevivência escassa, onde a água é a principal moeda e a radiação causou mutações grotescas na fauna e flora..."></textarea>
                </div>

                <button type="button" onclick="executarCanalizacaoIa()" style="width: 100%; background: linear-gradient(135deg, #7b4ff7, #4a2a85); color: #fff; border: none; padding: 15px; border-radius: 8px; font-weight: 700; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 4px 15px rgba(123, 79, 247, 0.4); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <i class="fas fa-wand-magic-sparkles"></i> CANALIZAR UNIVERSO
                </button>
            </div>

            <!-- Conteúdo de Carregamento (Loading) -->
            <div id="ia-loading-container" style="display: none; text-align: center; padding: 20px 0;">
                <div class="ia-loading-spinner" style="width: 60px; height: 60px; border: 4px solid rgba(123, 79, 247, 0.1); border-left-color: #7b4ff7; border-radius: 50%; margin: 0 auto 20px auto; animation: spinIa 1s linear infinite;"></div>
                <h3 style="color: #fff; font-size: 1.3rem; font-weight: 700; margin-bottom: 5px;">CassIA está canalizando seu universo...</h3>
                <p style="color: #888; font-size: 0.85rem; margin-bottom: 15px; font-weight: 500;">Tempo médio de espera: 15 a 30 segundos</p>
                <p id="ia-loading-frase" style="color: #aaa; font-size: 0.95rem; font-style: italic; min-height: 24px;">Tecendo as regras da realidade...</p>
            </div>
        </div>
    </div>
</body>

</html>
