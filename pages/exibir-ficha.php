<?php
require_once __DIR__ . '/../app/config/database.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$id_personagem = $_GET['id'] ?? null;

if (!$id_personagem) {
    die("Personagem não encontrado.");
}

try {
    $pdo = Database::getConexao();

    // Buscar dados básicos do personagem
    $stmt = $pdo->prepare("
        SELECT p.*, s.nm_sistema, c.nm_classe, o.nm_origem 
        FROM tb_personagem p
        LEFT JOIN tb_sistema s ON p.id_sistema = s.id_sistema
        LEFT JOIN tb_personagem_classe pc ON p.id_personagem = pc.id_personagem
        LEFT JOIN tb_classe c ON pc.id_classe = c.id_classe
        LEFT JOIN tb_personagem_origem po ON p.id_personagem = po.id_personagem
        LEFT JOIN tb_origem o ON po.id_origem = o.id_origem
        WHERE p.id_personagem = ? AND p.id_usuario = ?
    ");
    $stmt->execute([$id_personagem, $_SESSION['usuario']['id']]);
    $personagem = $stmt->fetch();

    if (!$personagem) {
        die("Personagem não encontrado ou acesso negado.");
    }

    // Buscar Atributos do Sistema e Valores do Personagem
    $stmt = $pdo->prepare("
        SELECT a.nm_atributo, a.ds_abreviacao, COALESCE(pa.qt_valor, 0) as qt_valor 
        FROM tb_atributo a
        LEFT JOIN tb_personagem_atributo pa ON a.id_atributo = pa.id_atributo AND pa.id_personagem = ?
        WHERE a.id_sistema = ?
    ");
    $stmt->execute([$id_personagem, $personagem['id_sistema']]);
    $atributos_rows = $stmt->fetchAll();

    $atributos = $atributos_rows; // Usaremos a lista completa diretamente na renderização

    // Buscar Perícias
    $stmt = $pdo->prepare("
        SELECT p.nm_pericia, p.ds_atributo_base, pp.qt_valor, pp.fl_treinado
        FROM tb_personagem_pericia pp
        JOIN tb_pericia p ON pp.id_pericia = p.id_pericia
        WHERE pp.id_personagem = ?
        ORDER BY p.nm_pericia ASC
    ");
    $stmt->execute([$id_personagem]);
    $pericias = $stmt->fetchAll();

    // Buscar Status Customizados do Sistema
    $stmt = $pdo->prepare("
        SELECT ss.*, COALESCE(ps.qt_valor_atual, 0) as qt_atual, COALESCE(ps.qt_valor_maximo, 0) as qt_max 
        FROM tb_sistema_status ss
        LEFT JOIN tb_personagem_status ps ON ss.id_status_sistema = ps.id_status_sistema AND ps.id_personagem = ?
        WHERE ss.id_sistema = ? AND ss.tp_status = 'barra'
    ");
    $stmt->execute([$id_personagem, $personagem['id_sistema']]);
    $status_barras = $stmt->fetchAll();

    // Fallback para Vida, Sanidade, Esforço caso o sistema não tenha barras customizadas
    if (empty($status_barras)) {
        $status_barras = [
            ['nm_status' => 'VIDA', 'ds_cor' => '#ed1c24', 'qt_atual' => $personagem['qt_vida'], 'qt_max' => $personagem['qt_vida_maxima'], 'id_status_sistema' => 'vida'],
            ['nm_status' => 'SANIDADE', 'ds_cor' => '#00aeef', 'qt_atual' => $personagem['qt_sanidade'], 'qt_max' => $personagem['qt_sanidade_maxima'], 'id_status_sistema' => 'sanidade'],
            ['nm_status' => 'ESFORÇO', 'ds_cor' => '#f1c40f', 'qt_atual' => $personagem['qt_esforco'], 'qt_max' => $personagem['qt_esforco_maximo'], 'id_status_sistema' => 'esforco']
        ];
    }

    // Buscar Defesas Customizadas do Sistema
    $stmt = $pdo->prepare("
        SELECT * FROM tb_sistema_status WHERE id_sistema = ? AND tp_status = 'defesa'
    ");
    $stmt->execute([$personagem['id_sistema']]);
    $status_defesas = $stmt->fetchAll();

} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | <?= htmlspecialchars($personagem['nm_personagem']) ?></title>
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/ficha.css?v=1.4">
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">
    <script src="../js/ficha.js" defer></script>
</head>

<body class="ficha-body">

    <!-- NAVBAR PADRÃO INDEX.PHP (MESMO DO INDEX) -->
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

    <div class="ficha-container-master">

        <div class="ficha-layout-premium">
            <!-- CABEÇALHO DA FICHA (IDENTIDADE) -->
            <div class="premium-header">
                <div class="premium-avatar" onclick="document.getElementById('input-avatar').click()"
                    title="Clique para mudar a foto">
                    <img src="<?= !empty($personagem['ds_foto']) ? $personagem['ds_foto'] : '../img/uploads/perfil/avatar1.png' ?>"
                        alt="Avatar" id="img-personagem">
                    <div class="avatar-overlay">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
                <input type="file" id="input-avatar" hidden accept="image/*">
                <div class="premium-info-grid">
                    <div class="premium-info-item">
                        <label>Nome Personagem:</label>
                        <input type="text" value="<?= htmlspecialchars($personagem['nm_personagem']) ?>" readonly>
                    </div>
                    <div class="premium-info-item">
                        <label>Classe:</label>
                        <input type="text" value="<?= htmlspecialchars($personagem['nm_classe'] ?? 'Nenhuma') ?>"
                            readonly>
                    </div>
                    <div class="premium-info-item">
                        <label>Nome Jogador:</label>
                        <input type="text" value="<?= htmlspecialchars($_SESSION['usuario']['nome']) ?>" readonly>
                    </div>
                    <div class="premium-info-item">
                        <label>Origem:</label>
                        <input type="text" value="<?= htmlspecialchars($personagem['nm_origem'] ?? 'Rural') ?>"
                            readonly>
                    </div>
                </div>
            </div>

            <div class="nivel-deslocamento-row">
                <div class="extra-stat-box">NÍVEL <span><?= $personagem['qt_nivel'] ?></span></div>
                <div class="extra-stat-box">DESLOCAMENTO <span>9 m / 6 q</span></div>
            </div>

            <section class="premium-main">
                <!-- COLUNA ESQUERDA: ATRIBUTOS E STATUS -->
                <div class="premium-col-left">

                    <div class="premium-atributos-grid">
                        <?php foreach ($atributos as $attr): 
                            $nomeFull = $attr['nm_atributo'];
                            $abbr = $attr['ds_abreviacao'] ?: substr($nomeFull, 0, 3);
                            $valor = $attr['qt_valor'];
                        ?>
                            <div class="premium-attr-box" data-attr="<?= $nomeFull ?>">
                                <span class="attr-abbr"><?= htmlspecialchars(strtoupper($abbr)) ?></span>
                                <div class="attr-circle" contenteditable="true" inputmode="numeric"><?= $valor ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="premium-bars-area">
                        <?php foreach ($status_barras as $s): ?>
                            <div class="bar-unit" data-campo="<?= htmlspecialchars($s['nm_status']) ?>" data-id-status="<?= $s['id_status_sistema'] ?>">
                                <span class="bar-name"><?= htmlspecialchars($s['nm_status']) ?></span>
                                <div class="bar-interact">
                                    <div class="bar-bg">
                                        <div class="btns-left">
                                            <button class="step-btn" data-step="-5">«</button> 
                                            <button class="step-btn" data-step="-1">‹</button>
                                        </div>
                                        <div class="bar-fill"
                                            style="width: <?= $s['qt_max'] > 0 ? ($s['qt_atual'] / $s['qt_max']) * 100 : 100 ?>%; background: <?= $s['ds_cor'] ?>; box-shadow: 0 0 15px <?= $s['ds_cor'] ?>55;">
                                        </div>
                                        <span class="bar-num"><span
                                                contenteditable="true"><?= $s['qt_atual'] ?></span>/<?= $s['qt_max'] ?: 100 ?></span>
                                        <div class="btns-right">
                                            <button class="step-btn" data-step="1">›</button> 
                                            <button class="step-btn" data-step="5">»</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- COMBATE (DEFESA ETC) -->
                    <div class="premium-combat-section">
                        <?php if (empty($status_defesas)): ?>
                            <div class="defesa-shield-box" data-campo="defesa" data-id-status="defesa">
                                <i class="fas fa-shield-alt shield-bg-icon"></i>
                                <div class="shield-text">
                                    <span class="shield-number" id="valor-defesa"><?= $personagem['qt_defesa'] ?: 10 ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($status_defesas as $d): ?>
                                <div class="defesa-shield-box" title="<?= htmlspecialchars($d['nm_status']) ?>" data-id-status="<?= $d['id_status_sistema'] ?>">
                                    <i class="fas fa-shield-alt shield-bg-icon" style="color: <?= $d['ds_cor'] ?> !important;"></i>
                                    <div class="shield-text">
                                        <span class="shield-number" contenteditable="true"><?= $personagem['qt_defesa'] ?: 10 ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="defesa-details">
                            <div class="defesa-formula">
                                <span class="formula-label">DEFESA</span>
                                <span class="formula-text">= 10 + AGI + 0 + 0</span>
                                <div class="formula-sub"><span>Equip.</span><span>Outros.</span></div>
                            </div>
                            <div class="defesa-stats-row">
                                <div class="defesa-stat-item">
                                    <label>BLOQUEIO</label>
                                    <span class="val">0</span>
                                </div>
                                <div class="defesa-stat-item">
                                    <label>ESQUIVA</label>
                                    <span class="val">17</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="premium-footer-lines">
                        <div class="line-item"><label>PROTEÇÃO</label>
                            <div class="underline"></div>
                        </div>
                        <div class="line-item"><label>RESISTÊNCIAS</label>
                            <div class="underline"></div>
                        </div>
                        <div class="line-item"><label>PROFICIÊNCIAS</label>
                            <div class="underline"></div>
                        </div>
                    </div>

                </div>

                <!-- COLUNA DIREIT: PERÍCIAS -->
                <div class="premium-col-right">
                    <div class="pericias-premium-container">
                        <div class="pericias-premium-header">
                            <span class="h-main">PERÍCIA</span>
                            <div class="h-stats">
                                <span>BÔNUS</span>
                                <span>TREINO</span>
                                <span>OUTROS</span>
                            </div>
                        </div>
                        <div class="pericias-premium-list">
                            <?php foreach ($pericias as $p): ?>
                                <div class="p-row">
                                    <div class="p-desc">
                                        <span class="p-name"><?= htmlspecialchars($p['nm_pericia']) ?></span>
                                        <span class="p-attr">(<?= $p['ds_atributo_base'] ?? '???' ?>)</span>
                                    </div>
                                    <div class="p-values">
                                        <span class="p-bonus">(<?= $p['qt_valor'] ?>)</span>
                                        <span class="p-treino"><?= $p['fl_treinado'] ? '+5' : '0' ?></span>
                                        <span class="p-outros">0</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- BOTÕES DE NAVEGAÇÃO INTERNA -->
                    <div class="premium-buttons-grid">
                        <button class="btn-p" onclick="abrirModal('habilidades')">Habilidades</button>
                        <button class="btn-p" onclick="abrirModal('inventario')">Inventário</button>
                        <button class="btn-p" onclick="abrirModal('poderes')">Poderes</button>
                        <button class="btn-p" onclick="abrirModal('descricao')">Descrição</button>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- MODAIS -->
    <div class="modal-overlay" id="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-titulo">Título</h2>
                <button class="btn-close-modal" onclick="fecharModal()">&times;</button>
            </div>
            <div class="modal-body" id="modal-body">
                <!-- Conteúdo preenchido via JS -->
            </div>
        </div>
    </div>

    <!-- Script para passar o ID e dados da descrição do personagem para o JS -->
    <script>
        const ID_PERSONAGEM = <?= $id_personagem ?>;
        const DADOS_DESCRICAO = {
            aparencia: <?= json_encode($personagem['ds_aparencia'] ?? '') ?>,
            personalidade: <?= json_encode($personagem['ds_personalidade'] ?? '') ?>,
            historia: <?= json_encode($personagem['ds_historia'] ?? '') ?>,
            objetivos: <?= json_encode($personagem['ds_objetivos'] ?? '') ?>
        };
    </script>

    <!-- RODAPÉ PADRÃO INDEX.PHP -->
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

    <script src="../js/nav-global.js" defer></script>
</body>

</html>
