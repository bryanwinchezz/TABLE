<?php
session_start();
require_once __DIR__ . '/../app/config/database.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$id_sistema = $_GET['id'] ?? null;

if (!$id_sistema) {
    header('Location: perfil.php');
    exit;
}

try {
    $pdo = Database::getConexao();

    // 1. Buscar dados do sistema e do criador
    $stmt = $pdo->prepare("
        SELECT s.*, u.nm_usuario as criador_nome 
        FROM tb_sistema s
        LEFT JOIN tb_usuario u ON s.id_usuario_criador = u.id_usuario
        WHERE s.id_sistema = ?
    ");
    $stmt->execute([$id_sistema]);
    $sistema = $stmt->fetch();

    if (!$sistema) {
        header('Location: perfil.php');
        exit;
    }

    $criadorDisplay = $sistema['criador_nome'] ?? 'TABLE';

    // 2. Buscar Atributos
    $stmt = $pdo->prepare("SELECT * FROM tb_atributo WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $atributos = $stmt->fetchAll();

    // 3. Buscar Classes
    $stmt = $pdo->prepare("SELECT * FROM tb_classe WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $classes = $stmt->fetchAll();

    // 4. Buscar Perícias
    $stmt = $pdo->prepare("SELECT * FROM tb_pericia WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $pericias = $stmt->fetchAll();

    // 5. Buscar Origens
    $stmt = $pdo->prepare("SELECT * FROM tb_origem WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $origens = $stmt->fetchAll();

    function getClassStyle($class)
    {
        switch ($class) {
            case 'L':
                return ['cor' => '#27ae60', 'label' => 'L'];
            case '10':
                return ['cor' => '#2980b9', 'label' => '10'];
            case '12':
                return ['cor' => '#f1c40f', 'label' => '12'];
            case '14':
                return ['cor' => '#e67e22', 'label' => '14'];
            case '16':
                return ['cor' => '#c0392b', 'label' => '16'];
            case '18':
                return ['cor' => '#1a1a1a', 'label' => '18'];
            default:
                return ['cor' => '#888', 'label' => '?'];
        }
    }
    $classStyle = getClassStyle($sistema['tp_classificacao'] ?? 'L');

} catch (Exception $e) {
    die("Erro ao carregar sistema: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | <?= htmlspecialchars($sistema['nm_sistema']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/nav-footer.css?v=1.4">
    <link rel="stylesheet" href="../css/ficha.css?v=2.4">
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">
    <style>
        /* ============================================================ 
           DESIGN SYSTEM: PREMIUM DARK (SISTEMA)
           ============================================================ */

        .system-display-container {
            margin-top: 40px;
        }

        .header-sistema-premium {
            background: linear-gradient(to right, rgba(25, 14, 53, 0.95), rgba(157, 122, 255, 0.1));
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
            display: flex;
            gap: 40px;
            align-items: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            width: 100%;
        }

        .img-sistema-grande {
            width: 320px;
            height: 180px;
            border-radius: 15px;
            border: 2px solid var(--premium-accent);
            object-fit: cover;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
            flex-shrink: 0;
        }

        .info-sistema-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            flex: 1;
            width: 100%;
        }

        .info-sistema-item label {
            display: block;
            text-transform: uppercase;
            font-weight: 800;
            font-size: 0.75rem;
            color: var(--premium-accent);
            margin-bottom: 5px;
            letter-spacing: 1.5px;
        }

        .info-sistema-item span {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
        }

        .criador-nome {
            color: #fff !important;
            font-weight: 800;
        }

        .classificacao-tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background:
                <?= $classStyle['cor'] ?>
            ;
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            font-weight: 900;
            font-size: 1.1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            flex-shrink: 0;
        }

        .secao-descricao-completa {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 40px;
            line-height: 1.8;
            font-size: 1.05rem;
            color: #ccc;
            width: 100%;
        }

        .secao-descricao-completa h2 {
            font-weight: 900;
            color: #fff;
            margin-bottom: 15px;
            font-size: 1.3rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .secao-descricao-completa h2::before {
            content: '';
            display: inline-block;
            width: 5px;
            height: 22px;
            background: var(--premium-accent);
            border-radius: 3px;
        }

        /* Configuração DA GRID PRINCIPAL */
        .premium-main {
            display: grid;
            grid-template-columns: 460px 1fr;
            gap: 50px;
            align-items: start;
            margin-bottom: 60px;
        }

        .board-title-modern {
            font-weight: 900;
            font-size: 1rem;
            color: var(--premium-accent);
            text-transform: uppercase;
            letter-spacing: 2.5px;
            margin-bottom: 30px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ATRIBUTOS */
        .premium-attr-box {
            position: relative;
            display: flex;
            align-items: stretch;
            border-radius: 12px;
            overflow: visible !important;
            height: 60px;
            filter: drop-shadow(0 5px 10px rgba(0, 0, 0, 0.4));
            margin-bottom: 10px;
            cursor: help;
        }

        .attr-abbr {
            background: #fff;
            color: #1e0b3a;
            width: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1rem;
            text-transform: uppercase;
            border-radius: 12px 0 0 12px;
        }

        .attr-circle {
            background: rgba(255, 255, 255, 0.02);
            border: 3px solid #fff;
            border-left: none;
            color: #fff;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            font-weight: 950;
            border-radius: 0 12px 12px 0;
            transition: all 0.3s;
        }

        /* Navegação DE ABAS Reforçada */
        .tab-nav-sistema {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            position: relative;
            z-index: 999;
            pointer-events: auto !important;
        }

        .btn-tab-sistema {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #aaa;
            padding: 10px 25px;
            border-radius: 40px;
            font-weight: 800;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 1px;
            outline: none;
        }

        .btn-tab-sistema:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-tab-sistema.ativa {
            background: var(--premium-accent);
            color: #fff;
            border-color: var(--premium-accent);
            box-shadow: 0 5px 20px rgba(193, 147, 253, 0.5);
            transform: scale(1.05);
        }

        /* Conteúdo DAS ABAS */
        .tab-content-sistema {
            animation: premiumFadeIn 0.5s ease-out;
            position: relative;
            z-index: 10;
        }

        @keyframes premiumFadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .escondido {
            display: none !important;
        }

        /* BESTIÁRIO PREMIUM */
        .card-ameaca-premium {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            display: flex;
            align-items: center;
            padding: 15px;
            gap: 20px;
            margin-bottom: 18px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .card-ameaca-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #ff3232, #8b0000);
            box-shadow: 2px 0 15px rgba(255, 50, 50, 0.4);
        }

        .card-ameaca-premium:hover {
            transform: translateX(8px) scale(1.01);
            border-color: rgba(255, 50, 50, 0.3);
            background: rgba(255, 255, 255, 0.07);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }

        .card-ameaca-img {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.1);
            background: #000;
            transition: 0.3s;
        }

        .card-ameaca-premium:hover .card-ameaca-img {
            border-color: var(--premium-accent);
            transform: rotate(-3deg) scale(1.1);
        }

        .btn-card-delete {
            background: rgba(255, 50, 50, 0.1);
            color: #ff4d4d;
            border: 1px solid rgba(255, 50, 50, 0.2);
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-card-delete:hover {
            background: #ff4d4d;
            color: #fff;
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 5px 15px rgba(255, 77, 77, 0.4);
        }

        /* TOOLTIPS (BOLHAS) UNIFICADAS */
        .p-values,
        .premium-attr-box,
        .info-icon-wrapper {
            position: relative;
        }

        .tooltip {
            visibility: hidden;
            background: rgba(18, 11, 34, 0.98);
            color: #fff;
            text-align: left;
            border-radius: 12px;
            padding: 12px 18px;
            position: absolute;
            z-index: 10000;
            top: 50%;
            right: 40px;
            transform: translateY(-50%) scale(0.9);
            opacity: 0;
            transition: all 0.2s ease-out;
            width: max-content;
            max-width: 250px;
            font-size: 0.8rem;
            border: 1px solid var(--premium-accent);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(15px);
            pointer-events: none;
            line-height: 1.4;
            font-weight: 500;
        }

        .tooltip::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 100%;
            margin-top: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: transparent transparent transparent var(--premium-accent);
        }

        .premium-attr-box:hover .tooltip,
        .p-values:hover .tooltip {
            visibility: visible;
            opacity: 1;
            transform: translateY(-50%) scale(1);
        }

        .info-icon-wrapper {
            background: rgba(157, 122, 255, 0.1);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: help;
            transition: 0.3s;
            border: 1px solid rgba(157, 122, 255, 0.2);
        }

        .info-icon-wrapper:hover {
            background: var(--premium-accent);
            border-color: #fff;
        }

        .info-icon-wrapper i {
            font-size: 0.7rem;
            color: var(--premium-accent);
        }

        .info-icon-wrapper:hover i {
            color: #fff;
        }

        /* GESTÃO DE OVERFLOW */
        .premium-col-right,
        .premium-col-left,
        .pericias-premium-container,
        .tab-content-sistema {
            overflow: visible !important;
        }

        #lista-monstros-sistema {
            max-height: 500px;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 5px 15px 5px 5px;
        }

        #lista-monstros-sistema::-webkit-scrollbar {
            width: 6px;
        }

        #lista-monstros-sistema::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
        }

        #lista-monstros-sistema::-webkit-scrollbar-thumb {
            background: var(--premium-accent);
            border-radius: 10px;
        }

        @media (max-width: 1150px) {
            .premium-main {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .header-sistema-premium {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .info-sistema-grid {
                width: 100%;
                justify-items: center;
            }
        }

        /* CORREÇÕES GERAIS DE ESTRUTURA */
        body.ficha-body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }

        main.ficha-container-master {
            flex: 1;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 20px;
        }

        .modal-overlay.ativa {
            display: flex !important;
        }

        /* ESTILIZAÇÃO PREMIUM DE INPUTS NO MODAL */
        .modal-box {
            background: rgba(15, 10, 25, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(157, 122, 255, 0.2);
            border-radius: 24px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.8);
            position: relative;
            animation: modalPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes modalPop {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(30px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: #666;
            font-size: 1.5rem;
            cursor: pointer;
            transition: 0.3s;
            z-index: 100;
        }

        .modal-close:hover {
            color: #ff4d4d;
            transform: rotate(90deg);
        }

        .form-section-title {
            color: var(--premium-accent);
            font-size: 0.7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 25px 0 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, rgba(157, 122, 255, 0.3), transparent);
        }

        .input-premium-group {
            margin-bottom: 20px;
        }

        .input-premium-label {
            display: block;
            color: #aaa;
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 8px;
            margin-left: 5px;
        }

        .input-premium-field {
            width: 100%;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 10px 15px;
            color: #fff;
            font-size: 0.9rem;
            transition: 0.3s;
            outline: none;
        }

        .input-premium-field:focus {
            border-color: var(--premium-accent);
            background: rgba(157, 122, 255, 0.05);
        }

        .attr-input-premium {
            text-align: center;
            font-weight: 900;
            font-size: 1.1rem;
            color: var(--premium-accent);
            padding: 8px !important;
        }
            font-size: 0.95rem;
            transition: 0.3s;
        }

        .input-premium-field:focus {
            outline: none;
            border-color: var(--premium-accent);
            background: rgba(157, 122, 255, 0.05);
            box-shadow: 0 0 15px rgba(157, 122, 255, 0.1);
        }

        .attr-input-premium {
            text-align: center;
            font-weight: 900;
            font-size: 1.2rem;
            color: var(--premium-accent);
        }

        .btn-card-ficha {
            background: linear-gradient(135deg, var(--premium-purple), var(--premium-accent));
            font-size: 0.85rem;
            padding: 8px 24px !important;
            color: #fff;
            border-radius: 8px !important;
            border: none;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(157, 122, 255, 0.4);
            letter-spacing: 1px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 800;
        }

        .btn-card-ficha:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(157, 122, 255, 0.7);
            filter: brightness(1.2);
        }

        .btn-premium-dragon {
            background: linear-gradient(135deg, #1e0b3a 0%, #3d147a 50%, #6127c9 100%);
            background-size: 200% 200%;
            animation: gradientMove 3s ease infinite;
            color: #fff;
            padding: 12px 28px;
            border-radius: 12px;
            border: 1px solid rgba(157, 122, 255, 0.3);
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4), 0 0 0 0 rgba(157, 122, 255, 0);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        @keyframes gradientMove {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .btn-premium-dragon::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            transform: scale(0);
            transition: 0.6s;
        }

        .btn-premium-dragon:hover {
            transform: translateY(-5px) scale(1.05);
            border-color: var(--premium-accent);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6), 0 0 20px rgba(157, 122, 255, 0.4);
        }

        .btn-premium-dragon:hover::before {
            transform: scale(1);
        }

        .btn-premium-dragon i {
            font-size: 1.1rem;
            filter: drop-shadow(0 0 5px rgba(255, 255, 255, 0.3));
        }

        .card-ameaca-actions {
            margin-left: auto;
            display: flex;
            gap: 12px;
            align-items: center;
        }
    </style>
</head>

<body class="ficha-body">

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

    <main class="ficha-container-master">
        <div class="ficha-layout-premium">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <a href="perfil.php"
                    style="color: #aaa; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-arrow-left"></i> Voltar ao Perfil
                </a>
                <?php if ($sistema['id_usuario_criador'] == $_SESSION['usuario']['id'] || (isset($_SESSION['usuario']['cargo']) && strtolower($_SESSION['usuario']['cargo']) === 'admin')): ?>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <a href="editar-sistema.php?id=<?= $id_sistema ?>" style="text-decoration: none;">
                            <button class="btn-pilula"
                                style="background: var(--premium-purple); color: #fff; padding: 10px 25px; font-size: 0.85rem; border: none; border-radius: 30px; font-weight: 800; cursor: pointer; transition: 0.3s; box-shadow: 0 0 15px rgba(157, 122, 255, 0.4);"><i
                                    class="fas fa-edit"></i> EDITAR SISTEMA</button>
                        </a>
                        <button class="btn-card-delete" onclick="removerSistema(<?= $id_sistema ?>)" 
                                title="Excluir Sistema" style="width: 42px; height: 42px; font-size: 1.1rem;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <!-- HEADER DO SISTEMA -->
            <section class="header-sistema-premium">
                <img src="<?= !empty($sistema['ds_imagem']) ? $sistema['ds_imagem'] : '../img/logo_icone.png' ?>"
                    alt="Capa" class="img-sistema-grande">
                <div class="info-sistema-grid">
                    <div class="info-sistema-item">
                        <label>Nome do Sistema</label>
                        <span><?= htmlspecialchars($sistema['nm_sistema']) ?></span>
                    </div>
                    <div class="info-sistema-item">
                        <label>Criado por</label>
                        <span class="criador-nome"><?= htmlspecialchars($criadorDisplay) ?></span>
                    </div>
                    <div class="info-sistema-item">
                        <label>Classificação Indicativa</label>
                        <div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
                            <span class="classificacao-tag"><?= $classStyle['label'] ?></span>
                            <span
                                style="font-size: 0.8rem; opacity: 0.6; color: #fff;">(<?= $sistema['tp_classificacao'] === 'L' ? 'Livre para todos os públicos' : 'Maiores de ' . $sistema['tp_classificacao'] . ' anos' ?>)</span>
                        </div>
                    </div>
                    <div class="info-sistema-item">
                        <label>Data de Registro</label>
                        <span><?= date('d/m/Y', strtotime($sistema['dt_cadastro'])) ?></span>
                    </div>
                </div>
            </section>

            <!-- DESCRIÇÃO COMPLETA -->
            <section class="secao-descricao-completa">
                <h2>O que é este sistema?</h2>
                <p><?= nl2br(htmlspecialchars($sistema['ds_descricao'] ?? 'Sem descrição disponível.')) ?></p>
            </section>

            <!-- DASHBOARD DE REGRAS (IDÊNTICO À FICHA) -->
            <section class="premium-main" style="margin-top: 40px;">
                <!-- COLUNA ESQUERDA: ATRIBUTOS E ORIGENS -->
                <div class="premium-col-left">
                    <h2 class="board-title-modern"><i class="fas fa-brain"></i> Atributos</h2>
                    <div class="premium-atributos-grid">
                        <?php foreach ($atributos as $at):
                            $valorBase = $at['qt_valor_minimo'] ?? 0;
                            ?>
                            <div class="premium-attr-box">
                                <span
                                    class="attr-abbr"><?= htmlspecialchars($at['ds_abreviacao'] ?? substr($at['nm_atributo'], 0, 3)) ?></span>
                                <div class="attr-circle"
                                    style="border-color: <?= $valorBase > 0 ? 'var(--premium-accent)' : '#fff' ?>;">
                                    <?= $valorBase ?>
                                </div>
                                <div class="tooltip"><?= htmlspecialchars($at['nm_atributo']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- ORIGENS NO PADRÃO PREMIUM -->
                    <div class="pericias-premium-container" style="height: auto; min-height: 350px; margin-top: 40px;">
                        <div class="pericias-premium-header">
                            <span class="h-main"><i class="fas fa-history"></i> ORIGENS</span>
                        </div>
                        <div class="pericias-premium-list" style="overflow: visible !important;">
                            <?php if (empty($origens)): ?>
                                <p style="text-align:center; opacity:0.5; margin-top:20px;">Nenhuma origem cadastrada.</p>
                            <?php else: ?>
                                <?php foreach ($origens as $or): ?>
                                    <div class="p-row">
                                        <div class="p-desc">
                                            <span class="p-name"><?= htmlspecialchars($or['nm_origem']) ?></span>
                                        </div>
                                        <div class="p-values">
                                            <i class="fas fa-info-circle info-icon"
                                                style="color: var(--premium-accent); cursor: help;"></i>
                                            <div class="tooltip">
                                                <?= htmlspecialchars($or['ds_origem'] ?? 'Sem descrição da origem.') ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- COLUNA DIREITA: CLASSES, PERÍCIAS E BESTIÁRIO -->
                <div class="premium-col-right">

                    <!-- TAB NAV PARA COMPONENTES DIREITOS -->
                    <div class="tab-nav-sistema">
                        <button class="btn-tab-sistema ativa"
                            onclick="switchSistemaTab('classes', this)">Classes</button>
                        <button class="btn-tab-sistema" onclick="switchSistemaTab('pericias', this)">Perícias</button>
                        <button class="btn-tab-sistema" onclick="switchSistemaTab('criaturas', this)">Criaturas</button>
                    </div>

                    <!-- CLASSES -->
                    <div id="tab-classes" class="pericias-premium-container tab-content-sistema"
                        style="height: auto; min-height: 200px; margin-bottom: 40px;">
                        <div class="pericias-premium-header">
                            <span class="h-main"><i class="fas fa-users-cog"></i> CLASSES DO SISTEMA</span>
                        </div>
                        <div class="pericias-premium-list" style="overflow: visible !important;">
                            <?php if (empty($classes)): ?>
                                <p style="text-align:center; opacity:0.5; margin-top:20px;">Nenhuma classe cadastrada.</p>
                            <?php else: ?>
                                <?php foreach ($classes as $cl): ?>
                                    <div class="p-row">
                                        <div class="p-desc">
                                            <span class="p-name"><?= htmlspecialchars($cl['nm_classe']) ?></span>
                                        </div>
                                        <div class="p-values" style="display: flex; align-items: center; gap: 10px;">
                                            <div class="info-icon-wrapper">
                                                <i class="fas fa-info"></i>
                                                <div class="tooltip">
                                                    <?= htmlspecialchars($cl['ds_descricao'] ?? 'Sem descrição da classe.') ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- PERÍCIAS -->
                    <div id="tab-pericias" class="pericias-premium-container tab-content-sistema escondido"
                        style="height: auto; min-height: 200px;">
                        <div class="pericias-premium-header">
                            <span class="h-main"><i class="fas fa-scroll"></i> PERÍCIAS ATIVAS</span>
                        </div>
                        <div class="pericias-premium-list" style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($pericias)): ?>
                                <p style="text-align:center; opacity:0.5; margin-top:20px;">Nenhuma perícia cadastrada.</p>
                            <?php else: ?>
                                <?php foreach ($pericias as $pe): ?>
                                    <div class="p-row">
                                        <div class="p-desc">
                                            <span class="p-name"><?= htmlspecialchars($pe['nm_pericia']) ?></span>
                                        </div>
                                        <div class="p-values" style="justify-content: flex-end; width: 100px;">
                                            <span class="p-attr"
                                                style="font-weight: 800; color: #aaa; text-transform: uppercase;">
                                                <?= htmlspecialchars(substr((string) ($pe['ds_atributo_base'] ?? ''), 0, 3) ?: '???') ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- CRIATURAS -->
                    <div id="tab-criaturas" class="tab-content-sistema escondido">
                        <div class="pericias-premium-header" style="margin-bottom: 20px;">
                            <span class="h-main"><i class="fas fa-dragon"></i> AMEAÇAS</span>
                            <?php if ($sistema['id_usuario_criador'] == $_SESSION['usuario']['id']): ?>
                                <button class="btn-premium-dragon" onclick="resetarModalMonstro(); abrirModal('modal-criar-monstro')">
                                    <i class="fas fa-dragon"></i> + CRIAR Ameaça
                                </button>
                            <?php endif; ?>
                        </div>

                        <div id="lista-monstros-sistema">
                            <?php
                            $stmtM = $pdo->prepare("SELECT * FROM tb_monstro WHERE id_sistema = ? ORDER BY qt_vd DESC");
                            $stmtM->execute([$id_sistema]);
                            $monstros = $stmtM->fetchAll();
                            ?>
                            <?php if (empty($monstros)): ?>
                                <p
                                    style="text-align:center; opacity:0.5; margin-top:40px; padding: 20px; border: 1px dashed rgba(255,255,255,0.1); border-radius: 10px;">
                                    Nenhuma ameaça catalogada no sistema.</p>
                            <?php else: ?>
                                <?php foreach ($monstros as $m): ?>
                                    <div class="card-ameaca-premium">
                                        <img src="<?= !empty($m['ds_imagem']) ? htmlspecialchars($m['ds_imagem']) : '../img/logo_icone.png' ?>"
                                            alt="Monstro" class="card-ameaca-img">
                                        <div class="card-ameaca-body">
                                            <h4 style="color: #fff; font-weight: 800; font-size: 1.1rem; margin-bottom: 5px;">
                                                <?= htmlspecialchars($m['nm_monstro']) ?>
                                            </h4>
                                            <div class="card-ameaca-details"
                                                style="display: flex; gap: 15px; font-size: 0.85rem; color: #aaa;">
                                                <span
                                                    style="background: rgba(255, 50, 50, 0.15); color: #ff4d4d; padding: 2px 8px; border-radius: 4px; font-weight: 700;">VD:
                                                    <b><?= $m['qt_vd'] ?? '???' ?></b></span>
                                                <span style="display: flex; align-items: center; gap: 5px;"><i
                                                        class="fas fa-tag"></i>
                                                    <?= htmlspecialchars($m['tp_monstro'] ?? 'Criatura') ?></span>
                                            </div>
                                        </div>
                                        <div class="card-ameaca-actions">
                                            <button class="btn-card-ficha"
                                                onclick="verFichaMonstro(<?= $m['id_monstro'] ?>)">Ficha</button>
                                            <?php if ($sistema['id_usuario_criador'] == $_SESSION['usuario']['id']): ?>
                                                <button class="btn-card-edit" onclick="editarMonstro(<?= $m['id_monstro'] ?>)"
                                                    title="Editar Ameaça" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #00d1b2; padding: 8px; border-radius: 8px; cursor: pointer;">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-card-delete" onclick="removerMonstro(<?= $m['id_monstro'] ?>)"
                                                    title="Excluir Ameaça">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- MODAL CRIAR MONSTRO -->
    <div class="modal-overlay" id="modal-criar-monstro">
        <div class="modal-box" style="max-width: 650px; max-height: 90vh; overflow-y: auto;">
            <i class="fas fa-times modal-close" onclick="fecharModal('modal-criar-monstro')"></i>

            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: #fff; font-size: 1.8rem; font-weight: 900; letter-spacing: -1px; margin-bottom: 5px;">
                    NOVA Ameaça</h2>
                <p style="color: #666; font-size: 0.9rem;">Catalogando perigos do Outro Lado</p>
            </div>
            <div id="form-criar-ameaca">
                <input type="hidden" id="m-id" value="">
                <input type="hidden" id="m-imagem-atual" value="">
                <div class="form-section-title"><i class="fas fa-fingerprint"></i> IDENTIDADE</div>

                <div style="display: flex; gap: 25px; align-items: stretch; margin-bottom: 25px;">
                    <div id="preview-monstro-container" onclick="document.getElementById('m-foto').click()" style="width: 120px; height: 120px; border: 2px dashed rgba(157, 122, 255, 0.3); border-radius: 20px; 
                                 display: flex; align-items: center; justify-content: center; cursor: pointer; 
                                 background: rgba(0,0,0,0.4); overflow: hidden; transition: 0.3s; position: relative;">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: var(--premium-accent); opacity: 0.5;"></i>
                        <span style="position: absolute; bottom: 10px; font-size: 0.6rem; color: #aaa; font-weight: 800; text-transform: uppercase;">Imagem</span>
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
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

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 15px;">
                    <div class="input-premium-group">
                        <label class="input-premium-label" style="color: #ff4d4d;">NÍVEL DE PERIGO (VD)</label>
                        <input type="number" id="m-vd" class="input-premium-field" style="border-color: rgba(255, 77, 77, 0.2); color: #ff4d4d; font-weight: 900;" placeholder="0">
                    </div>
                    <div class="input-premium-group">
                        <label class="input-premium-label" style="color: #f1c40f;">RECOMPENSA (XP)</label>
                        <input type="number" id="m-xp" class="input-premium-field" style="border-color: rgba(241, 196, 15, 0.2); color: #f1c40f; font-weight: 900;" placeholder="0">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 25px;">
                    <div class="input-premium-group">
                        <label class="input-premium-label">PONTOS DE VIDA</label>
                        <input type="number" id="m-vida" class="input-premium-field" placeholder="0">
                    </div>
                    <div class="input-premium-group">
                        <label class="input-premium-label">DEFESA</label>
                        <input type="number" id="m-defesa" class="input-premium-field" placeholder="0">
                    </div>
                </div>

                <div class="form-section-title"><i class="fas fa-dice-d20"></i> ATRIBUTOS</div>
                <div id="grid-atributos-monstro-form" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.03); margin-bottom: 25px;">
                    <?php foreach ($atributos as $at): ?>
                        <div class="input-premium-group" style="margin-bottom: 0;">
                            <label class="input-premium-label" style="text-align: center; margin: 0 0 5px 0; font-size: 0.6rem;"><?= htmlspecialchars($at['ds_abreviacao'] ?: $at['nm_atributo']) ?></label>
                            <input type="number" class="input-premium-field attr-input-premium" data-id="<?= $at['id_atributo'] ?>" value="0">
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-section-title"><i class="fas fa-align-left"></i> DETALHES</div>
                <div class="input-premium-group">
                    <label class="input-premium-label">DESCRIÇÃO E HABILIDADES</label>
                    <textarea id="m-desc" class="input-premium-field" style="height: 120px; resize: none;" placeholder="Descreva as peculiaridades e poderes desta ameaça..."></textarea>
                </div>

                <button type="button" class="btn-premium-dragon" id="btn-save-monstro" style="width: 100%; padding: 20px; justify-content: center;" onclick="salvarMonstro()">
                    <i class="fas fa-skull"></i> CONVOCAR Ameaça
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL FICHA MONSTRO (PREMIUM) -->
    <div class="modal-overlay" id="modal-ficha-monstro">
        <div class="modal-box" id="ficha-monstro-render"
            style="max-width: 700px; max-height: 90vh; padding: 0; overflow-y: auto; overflow-x: hidden;">
            <!-- Renderizado via AJAX -->
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

    <script src="../js/nav-global.js?v=1.4" defer></script>
    <script>
        function switchSistemaTab(tab, btn) {
            document.querySelectorAll('.tab-content-sistema').forEach(t => t.classList.add('escondido'));
            document.querySelectorAll('.btn-tab-sistema').forEach(b => b.classList.remove('ativa'));

            document.getElementById('tab-' + tab).classList.remove('escondido');
            btn.classList.add('ativa');
        }

        function abrirModal(id) {
            const el = document.getElementById(id);
            if (el) {
                el.style.display = 'flex';
                el.offsetHeight; // Force reflow
                el.classList.add('ativa');
            }
        }

        function fecharModal(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.remove('ativa');
                setTimeout(() => { el.style.display = 'none'; }, 400);
            }
        }

        function previewImagemMonstro(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const container = document.getElementById('preview-monstro-container');
                    container.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        async function salvarMonstro() {
            const idS = <?= $id_sistema ?>;
            const idM = document.getElementById('m-id').value;
            const nome = document.getElementById('m-nome').value;
            const tipo = document.getElementById('m-tipo').value;
            const vd = document.getElementById('m-vd').value;
            const vida = document.getElementById('m-vida').value;
            const defesa = document.getElementById('m-defesa').value;
            const xp = document.getElementById('m-xp').value;
            const desc = document.getElementById('m-desc').value;
            const foto = document.getElementById('m-foto').files[0];
            const imgAtual = document.getElementById('m-imagem-atual').value;

            if (!nome) return alert('Dê um nome à criatura!');

            const atributos = [];
            document.querySelectorAll('.attr-input-premium').forEach(input => {
                atributos.push({ id: input.dataset.id, valor: input.value });
            });

            const btn = document.getElementById('btn-save-monstro');
            btn.disabled = true;
            btn.textContent = idM ? 'ATUALIZANDO...' : 'CRIANDO...';

            const formData = new FormData();
            formData.append('id_sistema', idS);
            if (idM) formData.append('id_monstro', idM);
            formData.append('nome', nome);
            formData.append('tipo', tipo);
            formData.append('vd', vd);
            formData.append('vida', vida);
            formData.append('defesa', defesa);
            formData.append('xp', xp);
            formData.append('descricao', desc);
            formData.append('atributos', JSON.stringify(atributos));
            formData.append('imagem_atual', imgAtual);
            if (foto) formData.append('foto', foto);

            try {
                const res = await fetch('../app/ajax/salvar-monstro.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro: ' + data.error);
                }
            } catch (e) { console.error(e); }
            finally { btn.disabled = false; btn.textContent = 'CONVOCAR Ameaça'; }
        }

        function resetarModalMonstro() {
            console.log('Resetando modal de ameaça...');
            const fields = ['m-id', 'm-imagem-atual', 'm-nome', 'm-vd', 'm-vida', 'm-defesa', 'm-xp', 'm-desc'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = (id === 'm-nome' || id === 'm-desc' || id === 'm-imagem-atual' || id === 'm-id') ? '' : 0;
            });
            
            const tipo = document.getElementById('m-tipo');
            if (tipo) tipo.value = 'Criatura';
            document.getElementById('preview-monstro-container').innerHTML = '<i class="fas fa-image" style="font-size: 2rem; color: rgba(255,255,255,0.1);"></i>';
            document.querySelector('#modal-criar-monstro h2').textContent = 'Nova Ameaça';
            document.getElementById('btn-save-monstro').innerHTML = '<i class="fas fa-skull"></i> CONVOCAR Ameaça';
            document.querySelectorAll('.attr-input-premium').forEach(input => input.value = 0);
        }

        async function editarMonstro(idM) {
            try {
                const res = await fetch(`../app/ajax/get-monstro-detalhes.php?id=${idM}`);
                const data = await res.json();
                if (data.success) {
                    const m = data.monstro;
                    document.getElementById('m-id').value = m.id_monstro;
                    document.getElementById('m-nome').value = m.nm_monstro;
                    document.getElementById('m-tipo').value = m.tp_monstro || 'Criatura';
                    document.getElementById('m-vd').value = m.qt_vd || 0;
                    document.getElementById('m-vida').value = m.qt_vida || 0;
                    document.getElementById('m-defesa').value = m.qt_defesa || 0;
                    document.getElementById('m-xp').value = m.qt_xp_recompensa || 0;
                    document.getElementById('m-desc').value = m.ds_monstro || '';
                    document.getElementById('m-imagem-atual').value = m.ds_imagem || '';
                    
                    if (m.ds_imagem) {
                        document.getElementById('preview-monstro-container').innerHTML = `<img src="${m.ds_imagem}" style="width:100%; height:100%; object-fit:cover;">`;
                    } else {
                        document.getElementById('preview-monstro-container').innerHTML = '<i class="fas fa-image" style="font-size: 2rem; color: rgba(255,255,255,0.1);"></i>';
                    }

                    // Preencher Atributos
                    data.atributos.forEach(at => {
                        const input = document.querySelector(`.attr-input-premium[data-id="${at.id_atributo}"]`);
                        if (input) input.value = at.qt_valor;
                    });

                    document.getElementById('btn-save-monstro').innerHTML = '<i class="fas fa-skull"></i> ATUALIZAR Ameaça';
                    document.querySelector('#modal-criar-monstro h2').textContent = 'Editar Ameaça';
                    abrirModal('modal-criar-monstro');
                }
            } catch (e) { console.error(e); }
        }

        async function removerMonstro(idM) {
            if (!confirm("Tem certeza que deseja banir esta ameaça para o Outro Lado?")) return;

            try {
                const res = await fetch('../app/ajax/remover-monstro.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${idM}`
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert("Erro ao remover: " + data.error);
                }
            } catch (e) { console.error(e); }
        }

        async function removerSistema(idS) {
            if (!confirm("Deseja realmente apagar este sistema? Esta ação é irreversível e removerá todas as classes, atributos e ameaças vinculadas.")) return;

            try {
                const res = await fetch('../app/ajax/remover-sistema.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${idS}`
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = 'perfil.php';
                } else {
                    alert("Erro ao remover: " + data.error);
                }
            } catch (e) { console.error(e); }
        }

        async function verFichaMonstro(idM) {
            const container = document.getElementById('ficha-monstro-render');
            container.innerHTML = '<div style="padding: 40px; text-align: center; color: #888;"><i class="fas fa-spinner fa-spin"></i> Lendo Grimório...</div>';
            abrirModal('modal-ficha-monstro');

            try {
                const res = await fetch(`../app/ajax/get-monstro-detalhes.php?id=${idM}`);
                const data = await res.json();

                if (data.success) {
                    const m = data.monstro;
                    const attrs = data.atributos;
                    const imgPlaceholder = '../img/logo_icone.png';
                    const imgCriatura = m.ds_imagem ? m.ds_imagem : imgPlaceholder;

                    container.innerHTML = `
                        <div class="ficha-header-comp" style="position: relative; background: linear-gradient(135deg, rgba(30, 11, 58, 0.95), rgba(49, 28, 97, 0.9)), url('${imgCriatura}') center/cover; padding: 30px; border-bottom: 2px solid var(--premium-accent); display: flex; align-items: center; gap: 20px;">
                            <img src="${imgCriatura}" style="width: 100px; height: 100px; border-radius: 15px; border: 3px solid var(--premium-accent); object-fit: cover; box-shadow: 0 10px 30px rgba(0,0,0,0.8);" />
                            <div style="flex: 1;">
                                <h1 style="color: #fff; font-weight: 900; font-size: 2rem; margin-bottom: 5px; text-shadow: 0 5px 15px rgba(0,0,0,0.8);">${m.nm_monstro}</h1>
                                <span style="display: inline-block; background: var(--premium-accent); color: #fff; padding: 4px 12px; border-radius: 6px; font-weight: 800; font-size: 0.8rem; text-transform: uppercase;">${m.tp_monstro || 'Desconhecido'}</span>
                                <span style="display: inline-block; background: rgba(255, 50, 50, 0.2); border: 1px solid rgba(255, 50, 50, 0.5); color: #ff4d4d; padding: 4px 12px; border-radius: 6px; font-weight: 900; font-size: 0.8rem; margin-left: 10px;">VD ${m.qt_vd || '???'}</span>
                            </div>
                            <i class="fas fa-times" onclick="fecharModal('modal-ficha-monstro')" style="color: #fff; cursor: pointer; font-size: 1.5rem; filter: drop-shadow(0 2px 5px rgba(0,0,0,0.8)); transition: 0.3s;" onmouseover="this.style.color='var(--premium-accent)'" onmouseout="this.style.color='#fff'"></i>
                        </div>
                        <div style="padding: 25px; background: #0c0816;">
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px;">
                                <div style="background: rgba(255, 77, 77, 0.05); padding: 15px; border-radius: 12px; text-align: center; border: 1px solid rgba(255, 77, 77, 0.2);">
                                    <span style="display: block; color: #ff4d4d; font-weight: 900; font-size: 0.75rem; margin-bottom: 5px; letter-spacing: 1px;"><i class="fas fa-heart"></i> VIDA</span>
                                    <strong style="color: #fff; font-size: 1.8rem;">${m.qt_vida}</strong>
                                </div>
                                <div style="background: rgba(41, 128, 185, 0.05); padding: 15px; border-radius: 12px; text-align: center; border: 1px solid rgba(41, 128, 185, 0.2);">
                                    <span style="display: block; color: #3498db; font-weight: 900; font-size: 0.75rem; margin-bottom: 5px; letter-spacing: 1px;"><i class="fas fa-shield-alt"></i> DEFESA</span>
                                    <strong style="color: #fff; font-size: 1.8rem;">${m.qt_defesa}</strong>
                                </div>
                                <div style="background: rgba(241, 196, 15, 0.05); padding: 15px; border-radius: 12px; text-align: center; border: 1px solid rgba(241, 196, 15, 0.2);">
                                    <span style="display: block; color: #f1c40f; font-weight: 900; font-size: 0.75rem; margin-bottom: 5px; letter-spacing: 1px;"><i class="fas fa-star"></i> RECOMPENSA</span>
                                    <strong style="color: #fff; font-size: 1.8rem;">${m.qt_xp_recompensa} <span style="font-size: 0.9rem; font-weight: normal; color: #aaa;">XP</span></strong>
                                </div>
                            </div>

                            <label style="color: var(--premium-accent); font-size: 0.8rem; font-weight: 900; text-transform: uppercase; margin-bottom: 10px; display: block; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px;">ATRIBUTOS PRINCIPAIS</label>
                            <div class="premium-atributos-grid" style="grid-template-columns: repeat(5, 1fr); margin-bottom: 25px; gap: 8px;">
                                ${attrs.map(a => `
                                    <div class="premium-attr-box" title="${a.nm_atributo}" style="height: 50px;">
                                        <span class="attr-abbr" style="font-size: 0.85rem; width: 60px;">${a.ds_abreviacao || a.nm_atributo.substring(0, 3).toUpperCase()}</span>
                                        <div class="attr-circle" style="border-color: ${a.qt_valor > 0 ? 'var(--premium-accent)' : '#444'}; font-size: 1.2rem;">${a.qt_valor}</div>
                                    </div>
                                `).join('')}
                            </div>

                            <label style="color: var(--premium-accent); font-size: 0.8rem; font-weight: 900; text-transform: uppercase; margin-bottom: 10px; display: block; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px;">DESCRIÇÃO / COMPORTAMENTO</label>
                            <div style="background: rgba(0,0,0,0.5); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                                <p style="color: #ccc; font-size: 0.95rem; line-height: 1.8; margin: 0; white-space: pre-wrap;">${m.ds_monstro || '<i style="opacity: 0.5;">Nenhuma descrição detalhada disponível nos tomos.</i>'}</p>
                            </div>
                        </div>
                    `;
                }
            } catch (e) { console.error(e); }
        }
    </script>
</body>

</html>
