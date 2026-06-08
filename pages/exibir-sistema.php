<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
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
        SELECT s.*, u.nm_usuario as criador_nome, u.tp_cargo
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

    $isOficial = (empty($sistema['id_usuario_criador']) || (isset($sistema['tp_cargo']) && $sistema['tp_cargo'] === 'admin') || (isset($sistema['criador_nome']) && $sistema['criador_nome'] === 'Kauan Bryan'));
    $isDono = ($sistema['id_usuario_criador'] == $_SESSION['usuario']['id']);

    $stmtCheck = $pdo->prepare("SELECT 1 FROM tb_usuario_sistema WHERE id_usuario = ? AND id_sistema = ?");
    $stmtCheck->execute([$_SESSION['usuario']['id'], $id_sistema]);
    $isImportado = (bool)$stmtCheck->fetch();

    // Se não for oficial, não for o dono e não tiver importado o sistema, redireciona
    if (!$isOficial && !$isDono && !$isImportado) {
        header('Location: perfil.php');
        exit;
    }
    $criadorDisplay = $isOficial ? 'TABLE' : ($sistema['criador_nome'] ?? 'TABLE');

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

    // 6. Buscar Status e Defesas
    $stmt = $pdo->prepare("SELECT * FROM tb_sistema_status WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $status_do_sistema = $stmt->fetchAll();
    
    $barras_sistema = array_filter($status_do_sistema, fn($s) => $s['tp_status'] === 'barra');
    $defesas_sistema = array_filter($status_do_sistema, fn($s) => $s['tp_status'] === 'defesa');

    // Executa silenciosamente a atualização de cores pedida pelo usuário na base de dados
    try {
        $pdo->query("UPDATE tb_sistema_status SET ds_cor = '#a855f7' WHERE LOWER(nm_status) LIKE '%sanidade%'");
        $pdo->query("UPDATE tb_sistema_status SET ds_cor = '#f97316' WHERE LOWER(nm_status) LIKE '%esforço%' OR LOWER(nm_status) LIKE '%esforco%'");
        $pdo->query("UPDATE tb_sistema_status SET ds_cor = '#95a5a6' WHERE LOWER(nm_status) LIKE '%defesa%' OR LOWER(nm_status) LIKE '%proteção%' OR LOWER(nm_status) LIKE '%protecao%'");
        
        // --- MIGRAÇÃO SILENCIOSA E PATRONIZADA DOS MONSTROS DE ORDEM PARANORMAL (SISTEMA 1) ---
        $elementos_monstros = [
            'Sangue' => ['O Diabo', 'Aberração de Carne', 'Aniquilação', 'Carente', 'Dama de Sangue', 'Enpap-X', 'Kerberos', 'Minotauro', 'Mulher Afogada', 'Titã de Sangue', 'Zumbi de Sangue', 'Zumbi de Sangue Bestial'],
            'Morte' => ['Aracnasita', 'Carniçal Preto da Morte', 'Ceifador Espiral', 'Enraizado', 'Escutado', 'Esqueleto de Lodo', 'Marionete', 'Múmia Xipófaga', 'Nidere', 'Sempiternal', 'Succ', 'O Deus da Morte'],
            'Conhecimento' => ['Anjo', 'Bicho-Papão', 'Espreitador', 'Estrangeiro', 'Existido', 'Lembrado', 'Ocioso', 'Parasita de Culpa', 'Rastejador Sombrio', 'Silhueta', 'Vulto', 'Máscara do Desespero'],
            'Energia' => ['Anárquico', 'Anárquico Descontrolado', 'Anomalia', 'Anomiático', 'Ciborgue', 'Infecticídio', 'Perturbado de Energia', 'Sukkalgir', 'Telopsia', 'Tempestuoso', 'Viajante', 'Anfitrião'],
            'Medo' => ['Degolificada']
        ];

        foreach ($elementos_monstros as $elemento => $nomes) {
            $nomes_str = implode("', '", array_map('addslashes', $nomes));
            $pdo->exec("UPDATE tb_monstro SET tp_monstro = '$elemento' WHERE id_sistema = 1 AND nm_monstro IN ('$nomes_str')");
        }

        // Garantir que as ameaças mundanas fiquem categorizadas como Mundano
        $pdo->exec("UPDATE tb_monstro SET tp_monstro = 'Mundano' WHERE id_sistema = 1 AND (tp_monstro IS NULL OR tp_monstro = '' OR tp_monstro LIKE 'Pessoa%' OR tp_monstro LIKE 'Animal%')");

        // Buscar os atributos do sistema 1
        $stmtAttrsSist = $pdo->query("SELECT id_atributo, nm_atributo FROM tb_atributo WHERE id_sistema = 1");
        $attrs_sistema1 = $stmtAttrsSist->fetchAll(PDO::FETCH_KEY_PAIR); // Retorna array ['Força' => id, ...]

        if (!empty($attrs_sistema1)) {
            // Buscar monstros do sistema 1
            $stmtMonstros = $pdo->query("SELECT id_monstro, nm_monstro, qt_vd FROM tb_monstro WHERE id_sistema = 1");
            $monstros_sistema1 = $stmtMonstros->fetchAll();

            foreach ($monstros_sistema1 as $m) {
                // Verificar se o monstro já possui atributos associados
                $stmtCountAttrs = $pdo->prepare("SELECT COUNT(*) FROM tb_monstro_atributo WHERE id_monstro = ?");
                $stmtCountAttrs->execute([$m['id_monstro']]);
                if ($stmtCountAttrs->fetchColumn() == 0) {
                    // Determinar valores de atributos coerentes com o VD da criatura
                    $vd = $m['qt_vd'];
                    
                    // Escalar os atributos entre 1 e 5 baseado no VD
                    $valBase = 1;
                    if ($vd >= 300) $valBase = 5;
                    elseif ($vd >= 180) $valBase = 4;
                    elseif ($vd >= 80) $valBase = 3;
                    elseif ($vd >= 30) $valBase = 2;

                    // Mapeamento dinâmico de atributos
                    foreach ($attrs_sistema1 as $nomeAttr => $idAttr) {
                        $valor = $valBase;
                        
                        // Fazer pequenas variações temáticas baseadas na criatura
                        $nomeLower = strtolower($m['nm_monstro']);
                        if (strpos($nomeLower, 'zumbi') !== false || strpos($nomeLower, 'aberração') !== false) {
                            if ($nomeAttr === 'Força' || $nomeAttr === 'Vigor') $valor = min(5, $valor + 1);
                            if ($nomeAttr === 'Intelecto') $valor = max(0, $valor - 2);
                        } elseif (strpos($nomeLower, 'anjo') !== false || strpos($nomeLower, 'espreitador') !== false || strpos($nomeLower, 'existido') !== false) {
                            if ($nomeAttr === 'Intelecto' || $nomeAttr === 'Presença') $valor = min(5, $valor + 1);
                        }

                        $stmtInsertAttr = $pdo->prepare("INSERT INTO tb_monstro_atributo (id_monstro, id_atributo, qt_valor) VALUES (?, ?, ?)");
                        $stmtInsertAttr->execute([$m['id_monstro'], $idAttr, $valor]);
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Ignora silenciosamente em caso de restrições de escrita em tabelas de backup
    }

    // Fallback premium para sistemas oficiais ou sem status customizados no banco (ex: Ordem Paranormal)
    if (empty($barras_sistema)) {
        $barras_sistema = [
            ['nm_status' => 'VIDA', 'ds_cor' => '#ff3232'],
            ['nm_status' => 'SANIDADE', 'ds_cor' => '#a855f7'],
            ['nm_status' => 'ESFORÇO', 'ds_cor' => '#f97316']
        ];
    }
    if (empty($defesas_sistema)) {
        $defesas_sistema = [
            ['nm_status' => 'DEFESA', 'ds_cor' => '#95a5a6']
        ];
    }

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

    // Lógica para Temas Dinâmicos
    $nomeSistemaLower = strtolower($sistema['nm_sistema'] ?? '');
    $classeBackground = '';
    if (strpos($nomeSistemaLower, 'ordem paranormal') !== false) {
        $classeBackground = 'tema-ordem-paranormal';
    }

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/nav-footer.css?v=1.4">
    <link rel="stylesheet" href="../css/table-modal.css">
    <link rel="stylesheet" href="../css/ficha.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../css/criar-sistema.css?v=<?= time() ?>">
    <script src="../js/table-modal.js"></script>
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">
    <style>
        /* ============================================================ 
           DESIGN SYSTEM: PREMIUM DARK (SISTEMA)
           ============================================================ */

        .btn-premium-dragon {
            background: linear-gradient(135deg, rgba(30, 20, 50, 0.95), rgba(60, 30, 100, 0.9));
            color: #fff;
            border: 1px solid rgba(157, 122, 255, 0.4);
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 800;
            text-transform: uppercase;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(157, 122, 255, 0.2);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .btn-premium-dragon:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 25px rgba(157, 122, 255, 0.4);
            background: linear-gradient(135deg, rgba(40, 25, 70, 0.95), rgba(80, 40, 130, 0.9));
            border-color: #fff;
        }

        /* ============================================================ 
           MODAIS (OVERLAY E BOX) E INPUTS PREMIUM
           ============================================================ */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 99999 !important;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.ativa,
        .modal-overlay.ativo {
            display: flex !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .modal-box {
            background: linear-gradient(135deg, rgba(20, 10, 30, 0.95), rgba(40, 20, 60, 0.95));
            border: 1px solid rgba(157, 122, 255, 0.3);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8), 0 0 40px rgba(157, 122, 255, 0.1);
            transform: translateY(20px) scale(0.95);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .modal-box::-webkit-scrollbar {
            width: 8px;
        }
        .modal-box::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.2);
            border-radius: 4px;
        }
        .modal-box::-webkit-scrollbar-thumb {
            background: rgba(157, 122, 255, 0.5);
            border-radius: 4px;
        }

        .modal-overlay.ativa .modal-box {
            transform: translateY(0) scale(1);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 1.5rem;
            color: #aaa;
            cursor: pointer;
            transition: color 0.2s, transform 0.2s;
            z-index: 10;
        }

        .modal-close:hover {
            color: #ff4d4d;
            transform: scale(1.1) rotate(90deg);
        }

        /* Formulario Interno do Modal */
        .form-section-title {
            color: var(--premium-accent);
            font-size: 0.9rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 30px 0 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .monstro-identidade-container {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            margin-bottom: 25px;
        }

        .monstro-identidade-inputs {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .input-premium-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
        }

        .input-premium-label {
            font-size: 0.75rem;
            font-weight: 800;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-premium-field {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 12px 15px;
            border-radius: 10px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s;
            width: 100%;
        }

        .input-premium-field:focus {
            border-color: var(--premium-accent);
            box-shadow: 0 0 15px rgba(157, 122, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
        }
        
        .input-premium-field::placeholder {
            color: rgba(255, 255, 255, 0.2);
        }

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
            position: relative;
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
            position: relative;
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
            z-index: 100;
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
            z-index: 101;
            pointer-events: auto !important;
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

        .card-ameaca-premium {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            padding: 10px 15px;
            gap: 15px;
            margin-bottom: 12px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }

        .sistema-row-premium {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: background 0.3s;
            width: 100%;
            box-sizing: border-box;
            position: relative;
            z-index: 1;
        }
        .sistema-row-premium:hover {
            background: rgba(255, 255, 255, 0.02);
            z-index: 100 !important;
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
            width: 60px;
            height: 60px;
            border-radius: 8px;
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
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.3s;
            font-size: 0.8rem;
        }

        .btn-card-delete:hover {
            background: #ff4d4d;
            color: #fff;
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 5px 15px rgba(255, 77, 77, 0.4);
        }

        .btn-card-edit {
            background: rgba(0, 209, 178, 0.1);
            color: #00d1b2;
            border: 1px solid rgba(0, 209, 178, 0.2);
            font-size: 0.75rem;
            padding: 6px 14px !important;
            border-radius: 6px !important;
            text-transform: uppercase;
            font-weight: 800;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-card-edit:hover {
            background: #00d1b2;
            color: #fff;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 209, 178, 0.4);
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
            padding: 8px 14px;
            position: absolute;
            z-index: 10000;
            top: 50%;
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
            font-weight: 600;
        }

        /* Tooltip padrão para perícias (à esquerda com seta para a direita) */
        .p-values .tooltip,
        .info-icon-wrapper .tooltip {
            right: 120%;
            top: 50%;
            left: auto;
            bottom: auto;
            transform: translateY(-50%) scale(0.9);
        }
        .p-values .tooltip::after,
        .info-icon-wrapper .tooltip::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 100%; /* Seta à direita */
            margin-top: -6px;
            margin-left: 0;
            border-width: 6px;
            border-style: solid;
            border-color: transparent transparent transparent var(--premium-accent);
        }
        .p-values:hover .tooltip {
            visibility: visible;
            opacity: 1;
            transform: translateY(-50%) scale(1);
        }

        /* As tooltips de info-icon-wrapper agora são geridas por JS para evitar cortes do overflow */
        .info-icon-wrapper .tooltip {
            display: none !important;
        }

        /* Tooltip Premium para Atributos e Bolhas de Info (no topo com seta para baixo) */
        .premium-attr-box .tooltip {
            left: 50%;
            bottom: 135%;
            top: auto; /* Desfaz o top: 50% geral */
            transform: translateX(-50%) translateY(10px) scale(0.9);
        }
        .premium-attr-box .tooltip::after {
            content: "";
            position: absolute;
            top: 100%; /* Seta no rodapé da tooltip */
            left: 50%;
            margin-left: -6px;
            border-width: 6px;
            border-style: solid;
            border-color: var(--premium-accent) transparent transparent transparent;
        }
        .premium-attr-box .attr-abbr {
            cursor: help;
        }
        .premium-attr-box .attr-abbr:hover ~ .tooltip {
            visibility: visible;
            opacity: 1;
            transform: translateX(-50%) translateY(0) scale(1);
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

        .pericias-premium-list,
        #lista-monstros-sistema {
            max-height: 500px;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 5px 15px 5px 5px;
        }

        .pericias-premium-list::-webkit-scrollbar,
        #lista-monstros-sistema::-webkit-scrollbar {
            width: 6px;
        }

        .pericias-premium-list::-webkit-scrollbar-track,
        #lista-monstros-sistema::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
        }

        .pericias-premium-list::-webkit-scrollbar-thumb,
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

        @media (max-width: 768px) {
            .header-sistema-premium {
                padding: 20px;
                gap: 25px;
            }

            .img-sistema-grande {
                width: 100%;
                max-width: 400px;
                height: auto;
                aspect-ratio: 16 / 9;
            }

            .tab-nav-sistema {
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
            }

            .btn-tab-sistema {
                padding: 8px 18px;
                font-size: 0.8rem;
                flex: 1 1 calc(50% - 10px);
                min-width: 140px;
            }

            .card-ameaca-premium {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            .card-ameaca-actions {
                margin: 15px 0 0 0;
                width: 100%;
                justify-content: center;
            }

            .attr-abbr {
                width: 60px;
            }
        }

        @media (max-width: 480px) {
            .info-sistema-grid {
                grid-template-columns: 1fr;
            }

            .btn-tab-sistema {
                flex: 1 1 100%;
            }

            .secao-descricao-completa {
                padding: 20px;
                font-size: 0.95rem;
            }

            .premium-attr-box {
                height: 50px;
            }

            .attr-circle {
                font-size: 1.3rem;
            }
        }

        /* CORREÇÕES GERAIS DE ESTRUTURA */
        body.ficha-body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            width: 100%;
        }

        main.ficha-container-master {
            flex: 1;
        }

        /* MODAIS */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(15px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 99999 !important;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .modal-overlay.ativa,
        .modal-overlay.ativo {
            display: flex !important;
            opacity: 1 !important;
            visibility: visible !important;
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
            padding: 35px;
            width: 95%;
            box-sizing: border-box;
        }

        /* Estilo responsivo mobile do modal-box */
        @media (max-width: 600px) {
            .modal-box {
                padding: 25px 15px !important;
                border-radius: 16px !important;
                width: 100% !important;
            }

            .modal-box div[style*="grid-template-columns: repeat(2, 1fr)"] {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }
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

        /* Estilos de inputs/labels/form do modal agora vêm de criar-sistema.css */

        .btn-card-ficha {
            background: linear-gradient(135deg, var(--premium-purple), var(--premium-accent));
            font-size: 0.75rem;
            padding: 6px 16px !important;
            color: #fff;
            border-radius: 6px !important;
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

        /* .btn-premium-dragon agora vem de criar-sistema.css */

        .card-ameaca-actions {
            margin-left: auto;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        /* TEMAS DINÂMICOS - PREMIUM EXPERIENCE */
        /* Tema: Ordem Paranormal */
        /* Forçar Cinzel em TUDO no tema Ordem Paranormal, EXCETO ícones */
        body.tema-ordem-paranormal,
        body.tema-ordem-paranormal *:not(i):not([class^="fa-"]):not([class*=" fa-"]) {
            font-family: 'Cinzel', serif !important;
            font-optical-sizing: auto !important;
        }

        body.tema-ordem-paranormal {
            --premium-accent: #ff3232 !important;
            --cor-destaque-claro: #ff4d4d !important;
            --fundo-cartao-escuro: rgba(10, 5, 5, 0.98) !important;
            --cor-primaria: #ff3232 !important;
            
            background: #050202 !important;
            color: #fff !important;
            letter-spacing: 1px;
        }

        /* Glow e Estilo para Títulos/Botões */
        body.tema-ordem-paranormal h1, 
        body.tema-ordem-paranormal h2, 
        body.tema-ordem-paranormal h3, 
        body.tema-ordem-paranormal h4,
        body.tema-ordem-paranormal .btn-tab-sistema,
        body.tema-ordem-paranormal .btn-premium-dragon,
        body.tema-ordem-paranormal .btn-pilula {
            font-weight: 700 !important;
            color: white !important;
            text-shadow: none !important;
            letter-spacing: 2px !important;
        }

        /* Background Dinâmico - Mostrar mais */
        body.tema-ordem-paranormal::before {
            content: '';
            position: fixed;
            top: 80px; left: 0; width: 100%; height: calc(100vh - 80px);
            background: radial-gradient(circle at center, transparent 0%, #000 90%),
                        url('<?= !empty($sistema['ds_background']) ? $sistema['ds_background'] : '../img/ordem-paranormal-icon.png' ?>') center/cover no-repeat;
            opacity: 0.55;
            z-index: -1;
            pointer-events: none;
            filter: grayscale(0.2) contrast(1.1);
        }

        /* Borda da foto de capa e sistema */
        body.tema-ordem-paranormal .img-sistema-grande,
        body.tema-ordem-paranormal .card-ameaca-img {
            border: 3px solid #ff3232 !important;
            box-shadow: 0 0 25px rgba(255, 50, 50, 0.5) !important;
        }

        /* Scrollbars Temáticas - Sangue e Escuridão */
        body.tema-ordem-paranormal::-webkit-scrollbar,
        body.tema-ordem-paranormal *::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        body.tema-ordem-paranormal::-webkit-scrollbar-track,
        body.tema-ordem-paranormal *::-webkit-scrollbar-track {
            background: #0a0606;
        }
        body.tema-ordem-paranormal::-webkit-scrollbar-thumb,
        body.tema-ordem-paranormal *::-webkit-scrollbar-thumb {
            background: #8b0000;
            border-radius: 10px;
            border: 2px solid #0a0606;
            transition: background 0.3s;
        }
        body.tema-ordem-paranormal::-webkit-scrollbar-thumb:hover,
        body.tema-ordem-paranormal *::-webkit-scrollbar-thumb:hover {
            background: #ff3232;
        }

        /* Overrides de UI Elements */
        body.tema-ordem-paranormal .header-sistema-premium {
            background: linear-gradient(to right, rgba(20, 5, 5, 0.95), rgba(255, 50, 50, 0.1)) !important;
            border-color: rgba(255, 50, 50, 0.2) !important;
        }

        body.tema-ordem-paranormal .secao-descricao-completa,
        body.tema-ordem-paranormal .card-ameaca-premium,
        body.tema-ordem-paranormal .modal-box,
        body.tema-ordem-paranormal .btn-importar-sistema {
            background: rgba(15, 5, 5, 0.7) !important;
            border: 1px solid rgba(255, 50, 50, 0.15) !important;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6) !important;
        }

        body.tema-ordem-paranormal .card-ameaca-premium.elemento-sangue { background: linear-gradient(90deg, rgba(30,0,0,0.9), rgba(60,20,20,0.6)) !important; border-color: rgba(255,50,50,0.4) !important; }
        body.tema-ordem-paranormal .card-ameaca-premium.elemento-sangue::before { background: #ff3232 !important; box-shadow: 0 0 10px #ff3232 !important; }
        body.tema-ordem-paranormal .card-ameaca-premium.elemento-morte { background: linear-gradient(90deg, rgba(10,10,10,0.9), rgba(20,20,20,0.6)) !important; border-color: rgba(100,100,100,0.4) !important; }
        body.tema-ordem-paranormal .card-ameaca-premium.elemento-morte::before { background: #1a1a1a !important; box-shadow: 0 0 10px #666 !important; }
        body.tema-ordem-paranormal .card-ameaca-premium.elemento-conhecimento { background: linear-gradient(90deg, rgba(30,25,0,0.9), rgba(50,40,0,0.6)) !important; border-color: rgba(241,196,15,0.4) !important; }
        body.tema-ordem-paranormal .card-ameaca-premium.elemento-conhecimento::before { background: #f1c40f !important; box-shadow: 0 0 10px #f1c40f !important; }
        body.tema-ordem-paranormal .card-ameaca-premium.elemento-energia { background: linear-gradient(90deg, rgba(0,30,25,0.9), rgba(0,50,40,0.6)) !important; border-color: rgba(0,209,178,0.4) !important; }
        body.tema-ordem-paranormal .card-ameaca-premium.elemento-energia::before { background: #00d1b2 !important; box-shadow: 0 0 10px #00d1b2 !important; }
        body.tema-ordem-paranormal .card-ameaca-premium.elemento-medo { background: linear-gradient(90deg, rgba(20,0,30,0.9), rgba(40,0,50,0.6)) !important; border-color: rgba(168,85,247,0.4) !important; }
        body.tema-ordem-paranormal .card-ameaca-premium.elemento-medo::before { background: #a855f7 !important; box-shadow: 0 0 10px #a855f7 !important; }

        body.tema-ordem-paranormal .btn-tab-sistema.ativa,
        body.tema-ordem-paranormal .btn-pilula {
            background: #ff3232 !important;
            border-color: #ff3232 !important;
            box-shadow: 0 0 20px rgba(255, 50, 50, 0.5) !important;
            color: #fff !important;
        }

        body.tema-ordem-paranormal .btn-pilula:hover {
            background: #ff4d4d !important;
            transform: translateY(-2px);
            box-shadow: 0 0 30px rgba(255, 50, 50, 0.7) !important;
        }

        body.tema-ordem-paranormal .btn-premium-dragon {
            background: linear-gradient(135deg, #660000 0%, #ff3232 100%) !important;
            border-color: rgba(255, 50, 50, 0.3) !important;
            box-shadow: 0 10px 25px rgba(255, 50, 50, 0.3) !important;
        }

        body.tema-ordem-paranormal .attr-abbr {
            background: #fff !important;
            color: #ff3232 !important;
        }

        body.tema-ordem-paranormal .attr-circle {
            border-color: #ff3232 !important;
            background: #ff3232 !important;
            color: #fff !important;
        }

        body.tema-ordem-paranormal .info-sistema-item label,
        body.tema-ordem-paranormal .board-title-modern,
        body.tema-ordem-paranormal .form-section-title {
            color: #ff3232 !important;
        }

        body.tema-ordem-paranormal .input-premium-field:focus {
            border-color: #ff3232 !important;
            background: rgba(255, 50, 50, 0.05) !important;
            box-shadow: 0 0 15px rgba(255, 50, 50, 0.1) !important;
        }
    </style>
</head>

<body class="ficha-body <?= $classeBackground ?>">

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

    <main class="ficha-container-master">
        <div class="ficha-layout-premium">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <a href="perfil.php"
                    style="color: #aaa; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-arrow-left"></i> Voltar ao Perfil
                </a>
                        <?php if ($isDono || $isImportado || (isset($_SESSION['usuario']['cargo']) && strtolower($_SESSION['usuario']['cargo']) === 'admin')): ?>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <?php if ($id_sistema != 1): ?>
                                    <button class="btn-pilula" onclick="gerarLinkCompartilhamento(this)"
                                            style="background: #27ae60; color: #fff; padding: 10px 25px; font-size: 0.85rem; border: none; border-radius: 30px; font-weight: 800; cursor: pointer; transition: 0.3s;">
                                        <i class="fas fa-share-alt"></i> COMPARTILHAR
                                    </button>
                                <?php endif; ?>
                                <?php if ($isDono || (isset($_SESSION['usuario']['cargo']) && strtolower($_SESSION['usuario']['cargo']) === 'admin')): ?>
                                    <a href="editar-sistema.php?id=<?= $id_sistema ?>" style="text-decoration: none;">
                                        <button class="btn-pilula"
                                            style="background: #b39ddb; color: #fff; padding: 10px 25px; font-size: 0.85rem; border: none; border-radius: 30px; font-weight: 800; cursor: pointer; transition: 0.3s;"><i
                                                class="fas fa-edit"></i> EDITAR SISTEMA</button>
                                    </a>
                                <?php endif; ?>
                                <button class="btn-lixeira-item" onclick="abrirModalExclusaoSistema()" 
                                        title="Excluir Sistema" style="width: 42px; height: 42px; font-size: 1.1rem; background: rgba(230, 57, 70, 0.1); color: #e63946; border: 1px solid rgba(230, 57, 70, 0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">
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

                <?php if ($isImportado && !$isDono): ?>
                    <div style="position: absolute; bottom: 15px; right: 20px; font-size: 0.6rem; color: rgba(255,255,255,0.25); font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; pointer-events: none; padding: 4px 10px; border-radius: 6px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                        <i class="fas fa-file-import" style="margin-right: 5px;"></i> Sistema Importado
                    </div>
                <?php endif; ?>
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

                    <!-- STATUS E DEFESAS (NOVIDADE) -->
                    <?php if(!empty($barras_sistema) || !empty($defesas_sistema)): ?>
                        <h2 class="board-title-modern" style="margin-top: 40px;"><i class="fas fa-heartbeat"></i> Status Iniciais</h2>
                        
                        <?php if(!empty($barras_sistema)): ?>
                            <div class="premium-status-list" style="margin-bottom: 20px;">
                                <?php foreach($barras_sistema as $barra): ?>
                                    <div class="p-barra-status" style="margin-bottom: 18px;">
                                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; color: <?= htmlspecialchars($barra['ds_cor']) ?>; margin-bottom: 8px;">
                                            <span><?= htmlspecialchars($barra['nm_status']) ?></span>
                                            <span style="opacity: 0.8;">100 / 100</span>
                                        </div>
                                        <div style="width: 100%; height: 14px; background: rgba(0,0,0,0.5); border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.08);">
                                            <div style="width: 100%; height: 100%; background: <?= htmlspecialchars($barra['ds_cor']) ?>; border-radius: 8px; box-shadow: 0 0 15px <?= htmlspecialchars($barra['ds_cor']) ?>;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if(!empty($defesas_sistema)): ?>
                            <div class="premium-defesas-grid" style="display: flex; gap: 15px; margin-top: 10px; flex-wrap: wrap;">
                                <?php foreach($defesas_sistema as $defesa): ?>
                                    <div class="p-defesa-item" style="flex: 1; min-width: 120px; display: flex; align-items: center; gap: 12px; background: rgba(0,0,0,0.3); padding: 12px 18px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); box-shadow: inset 0 0 20px rgba(0,0,0,0.2);">
                                        <i class="fas fa-shield-alt" style="color: <?= htmlspecialchars($defesa['ds_cor']) ?>; font-size: 1.8rem; filter: drop-shadow(0 0 8px <?= htmlspecialchars($defesa['ds_cor']) ?>);"></i>
                                        <div style="display: flex; flex-direction: column;">
                                            <span style="font-size: 0.65rem; color: #aaa; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;"><?= htmlspecialchars($defesa['nm_status']) ?></span>
                                            <span style="color: #fff; font-weight: 900; font-size: 1.2rem;">0</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- ATRIBUTOS FIM -->
                </div>

                <!-- COLUNA DIREITA: CLASSES, PERÍCIAS E BESTIÁRIO -->
                <div class="premium-col-right">

                    <!-- TAB NAV PARA COMPONENTES DIREITOS -->
                    <div class="tab-nav-sistema">
                        <button class="btn-tab-sistema ativa"
                            onclick="switchSistemaTab('classes', this)">Classes</button>
                        <button class="btn-tab-sistema" onclick="switchSistemaTab('pericias', this)">Perícias</button>
                        <button class="btn-tab-sistema" onclick="switchSistemaTab('origens', this)">Origens</button>
                        <button class="btn-tab-sistema" onclick="switchSistemaTab('criaturas', this)">Ameaças</button>
                    </div>

                    <!-- CLASSES -->
                    <div id="tab-classes" class="pericias-premium-container tab-content-sistema"
                        style="height: auto; min-height: 300px; margin-bottom: 40px;">
                        <div class="pericias-premium-header">
                            <span class="h-main"><i class="fas fa-users-cog"></i> CLASSES DO SISTEMA</span>
                        </div>
                        <div class="pericias-premium-list">
                            <?php if (empty($classes)): ?>
                                <p style="text-align:center; opacity:0.5; margin-top:20px;">Nenhuma classe cadastrada.</p>
                            <?php else: ?>
                                <?php foreach ($classes as $cl): ?>
                                    <div class="sistema-row-premium">
                                        <span class="p-name"><?= htmlspecialchars($cl['nm_classe']) ?></span>
                                        <div class="info-icon-wrapper">
                                            <i class="fas fa-info"></i>
                                            <div class="tooltip">
                                                <?= htmlspecialchars($cl['ds_descricao'] ?? 'Sem descrição da classe.') ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- PERÍCIAS -->
                    <div id="tab-pericias" class="pericias-premium-container tab-content-sistema escondido"
                        style="height: auto; min-height: 300px; margin-bottom: 40px;">
                        <div class="pericias-premium-header">
                            <span class="h-main"><i class="fas fa-scroll"></i> PERÍCIAS ATIVAS</span>
                        </div>
                        <div class="pericias-premium-list">
                            <?php if (empty($pericias)): ?>
                                <p style="text-align:center; opacity:0.5; margin-top:20px;">Nenhuma perícia cadastrada.</p>
                            <?php else: ?>
                                <?php foreach ($pericias as $pe): ?>
                                    <div class="sistema-row-premium">
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <span class="p-name"><?= htmlspecialchars($pe['nm_pericia']) ?></span>
                                            <span class="p-attr" style="font-weight: 800; color: #666; text-transform: uppercase;">
                                                (<?= htmlspecialchars(substr((string) ($pe['ds_atributo_base'] ?? ''), 0, 3) ?: '???') ?>)
                                            </span>
                                        </div>
                                        <div class="info-icon-wrapper">
                                            <i class="fas fa-info"></i>
                                            <div class="tooltip" style="z-index: 9999;">
                                                <b>Descrição:</b> <?= htmlspecialchars($pe['ds_descricao'] ?? 'Nenhuma.') ?><br>
                                                <b>Habilidade:</b> <?= htmlspecialchars($pe['ds_habilidade'] ?? 'Nenhuma.') ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ORIGENS -->
                    <div id="tab-origens" class="pericias-premium-container tab-content-sistema escondido"
                        style="height: auto; min-height: 300px; margin-bottom: 40px;">
                        <div class="pericias-premium-header">
                            <span class="h-main"><i class="fas fa-history"></i> ORIGENS DISPONÍVEIS</span>
                        </div>
                        <div class="pericias-premium-list">
                            <?php if (empty($origens)): ?>
                                <p style="text-align:center; opacity:0.5; margin-top:20px;">Nenhuma origem cadastrada.</p>
                            <?php else: ?>
                                <?php foreach ($origens as $or): ?>
                                    <div class="sistema-row-premium">
                                        <span class="p-name"><?= htmlspecialchars($or['nm_origem']) ?></span>
                                        <div class="info-icon-wrapper">
                                            <i class="fas fa-info"></i>
                                            <div class="tooltip" style="z-index: 9999;">
                                                <?= htmlspecialchars($or['ds_origem'] ?? 'Sem descrição da origem.') ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- CRIATURAS -->
                    <div id="tab-criaturas" class="tab-content-sistema escondido" style="min-height: 300px; margin-bottom: 40px;">
                        <div class="pericias-premium-header" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <span class="h-main"><i class="fas fa-dragon"></i> AMEAÇAS</span>
                            <?php if ($sistema['id_usuario_criador'] == $_SESSION['usuario']['id'] || (isset($_SESSION['usuario']['cargo']) && strtolower($_SESSION['usuario']['cargo']) === 'admin')): ?>
                                <button type="button" class="btn-premium-dragon" onclick="forceAbrirModalCriarMonstro()">
                                    <i class="fas fa-dragon"></i> + CRIAR Ameaça
                                </button>
                            <?php endif; ?>
                        </div>

                        <div id="lista-monstros-sistema">
                            <?php
                            $stmtM = $pdo->prepare("SELECT * FROM tb_monstro WHERE id_sistema = ? ORDER BY qt_vd DESC");
                            $stmtM->execute([$id_sistema]);
                            $monstros = $stmtM->fetchAll();

                            $nomeSistemaLower = strtolower($sistema['nm_sistema'] ?? '');
                            $isOrdemParanormal = (strpos($nomeSistemaLower, 'ordem paranormal') !== false);

                            if ($isOrdemParanormal) {
                                $obterElementoMonstro = function($m) {
                                    $dsLower = strtolower($m['ds_monstro'] ?? '');
                                    $tpLower = strtolower($m['tp_monstro'] ?? '');

                                    if (strpos($dsLower, 'sangue') !== false || strpos($tpLower, 'sangue') !== false) {
                                        return 'sangue';
                                    }
                                    if (strpos($dsLower, 'conhecimento') !== false || strpos($tpLower, 'conhecimento') !== false) {
                                        return 'conhecimento';
                                    }
                                    if (strpos($dsLower, 'morte') !== false || strpos($tpLower, 'morte') !== false) {
                                        return 'morte';
                                    }
                                    if (strpos($dsLower, 'energia') !== false || strpos($tpLower, 'energia') !== false) {
                                        return 'energia';
                                    }
                                    if (strpos($dsLower, 'medo') !== false || strpos($tpLower, 'medo') !== false) {
                                        return 'medo';
                                    }
                                    return 'mundano';
                                };

                                $ordemElementos = [
                                    'sangue' => 1,
                                    'conhecimento' => 2,
                                    'morte' => 3,
                                    'energia' => 4,
                                    'medo' => 5,
                                    'mundano' => 6
                                ];

                                usort($monstros, function($a, $b) use ($obterElementoMonstro, $ordemElementos) {
                                    $elA = $obterElementoMonstro($a);
                                    $elB = $obterElementoMonstro($b);

                                    $pesoA = $ordemElementos[$elA] ?? 6;
                                    $pesoB = $ordemElementos[$elB] ?? 6;

                                    if ($pesoA !== $pesoB) {
                                        return $pesoA <=> $pesoB;
                                    }

                                    $vdA = (int)($a['qt_vd'] ?? 0);
                                    $vdB = (int)($b['qt_vd'] ?? 0);
                                    return $vdB <=> $vdA;
                                });
                            }
                            ?>
                            <?php if (empty($monstros)): ?>
                                <p
                                    style="text-align:center; opacity:0.5; margin-top:40px; padding: 20px; border: 1px dashed rgba(255,255,255,0.1); border-radius: 10px;">
                                    Nenhuma ameaça catalogada no sistema.</p>
                            <?php else: ?>
                                <?php foreach ($monstros as $m): 
                                    $elClass = '';
                                    $dsLower = strtolower($m['ds_monstro'] ?? '');
                                    $tpLower = strtolower($m['tp_monstro'] ?? '');
                                    if (strpos($dsLower, 'sangue') !== false || strpos($tpLower, 'sangue') !== false) $elClass = ' elemento-sangue';
                                    elseif (strpos($dsLower, 'morte') !== false || strpos($tpLower, 'morte') !== false) $elClass = ' elemento-morte';
                                    elseif (strpos($dsLower, 'conhecimento') !== false || strpos($tpLower, 'conhecimento') !== false) $elClass = ' elemento-conhecimento';
                                    elseif (strpos($dsLower, 'energia') !== false || strpos($tpLower, 'energia') !== false) $elClass = ' elemento-energia';
                                    elseif (strpos($dsLower, 'medo') !== false || strpos($tpLower, 'medo') !== false) $elClass = ' elemento-medo';
                                ?>
                                    <div class="card-ameaca-premium<?= $elClass ?>">
                                        <img src="<?= (!empty($m['ds_imagem']) && $m['ds_imagem'] !== '../img/logo_icone.png') ? htmlspecialchars($m['ds_imagem']) : '../img/logo_icone.png' ?>"
                                            alt="Monstro" class="card-ameaca-img">
                                        <div class="card-ameaca-body">
                                            <h4 style="color: #fff; font-weight: 800; font-size: 0.95rem; margin-bottom: 3px;">
                                                <?= htmlspecialchars($m['nm_monstro']) ?>
                                            </h4>
                                            <div class="card-ameaca-details"
                                                style="display: flex; gap: 10px; font-size: 0.75rem; color: #aaa;">
                                                <span
                                                    style="background: rgba(255, 50, 50, 0.15); color: #ff4d4d; padding: 1px 6px; border-radius: 4px; font-weight: 700;">VD:
                                                    <b><?= $m['qt_vd'] ?? '???' ?></b></span>
                                                <span style="display: flex; align-items: center; gap: 4px;"><i
                                                        class="fas fa-tag"></i>
                                                    <?= htmlspecialchars($m['tp_monstro'] ?? 'Ameaça') ?></span>
                                            </div>
                                        </div>
                                        <div class="card-ameaca-actions">
                                            <button class="btn-card-ficha"
                                                onclick="exibirFichaMonstro(<?= $m['id_monstro'] ?>)">FICHA</button>
                                            <?php if ($sistema['id_usuario_criador'] == $_SESSION['usuario']['id'] || (isset($_SESSION['usuario']['cargo']) && strtolower($_SESSION['usuario']['cargo']) === 'admin')): ?>
                                                <button class="btn-card-edit" onclick="editarMonstro(<?= $m['id_monstro'] ?>)"
                                                    title="Editar Ameaça">
                                                    <i class="fas fa-edit"></i> EDITAR
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
                    NOVA AMEAÇA</h2>
                <p style="color: #666; font-size: 0.9rem;">Catalogando perigos do Outro Lado</p>
            </div>
            <div id="form-criar-ameaca">
                <input type="hidden" id="m-id" value="">
                <input type="hidden" id="m-imagem-atual" value="">
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

    <!-- MODAL COMPARTILHAR SISTEMA -->
    <div class="modal-overlay" id="modal-compartilhar-sistema">
        <div class="modal-box" style="max-width: 500px; padding: 40px; text-align: center;">
            <i class="fas fa-times modal-close" onclick="fecharModal('modal-compartilhar-sistema')"></i>
            <i class="fas fa-share-nodes" style="font-size: 3rem; color: #27ae60; margin-bottom: 20px;"></i>
            <h2 style="color: #fff; margin-bottom: 10px;">Compartilhar Sistema</h2>
            <p style="color: #aaa; font-size: 0.9rem; margin-bottom: 25px;">Envie este link para um amigo importar seu sistema completo para a conta dele.</p>
            
            <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); display: flex; gap: 10px; align-items: center; margin-bottom: 20px;">
                <input type="text" id="input-link-compartilhar" readonly 
                       style="flex: 1; background: transparent; border: none; color: #fff; font-family: 'Montserrat', sans-serif; font-size: 0.8rem; outline: none;">
                <button onclick="copiarLinkSistema()" style="background: #27ae60; color: #fff; border: none; padding: 8px 15px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s;">
                    COPIAR
                </button>
            </div>
            <p id="msg-copiado" style="color: #27ae60; font-size: 0.8rem; font-weight: 700; opacity: 0; transition: 0.3s;">Link copiado com sucesso!</p>
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

    <script src="../js/nav-global.js?v=1.4" defer></script>
    <script>
        // --- NAVEGAÇÃO DE ABAS ---
        function switchSistemaTab(tabId, btn) {
            document.querySelectorAll('.tab-content-sistema').forEach(t => {
                t.classList.add('escondido');
            });
            document.querySelectorAll('.btn-tab-sistema').forEach(b => {
                b.classList.remove('ativa');
            });

            const target = document.getElementById('tab-' + tabId);
            if (target) {
                target.classList.remove('escondido');
            }
            if (btn) btn.classList.add('ativa');
        }

        // --- GESTÃO DE MODAIS ---
        function abrirModal(id) {
            const el = document.getElementById(id);
            if (el) {
                el.style.display = 'flex';
                el.offsetHeight; // Force reflow
                el.classList.add('ativa');
                el.classList.add('ativo');
                document.body.style.overflow = 'hidden';
            }
        }

        function fecharModal(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.remove('ativa');
                el.classList.remove('ativo');
                setTimeout(() => { 
                    if (!el.classList.contains('ativa') && !el.classList.contains('ativo')) {
                        el.style.display = 'none'; 
                    }
                }, 400);
                document.body.style.overflow = '';
            }
        }

        function forceAbrirModalCriarMonstro() {
            try {
                resetarModalMonstro();
                const el = document.getElementById('modal-criar-monstro');
                if (el) {
                    document.body.appendChild(el);
                    
                    el.style.setProperty('display', 'flex', 'important');
                    el.style.setProperty('position', 'fixed', 'important');
                    el.style.setProperty('top', '0', 'important');
                    el.style.setProperty('left', '0', 'important');
                    el.style.setProperty('width', '100vw', 'important');
                    el.style.setProperty('height', '100vh', 'important');
                    el.style.setProperty('background-color', 'rgba(0, 0, 0, 0.95)', 'important');
                    el.style.setProperty('z-index', '9999999', 'important');
                    el.style.setProperty('opacity', '1', 'important');
                    el.style.setProperty('visibility', 'visible', 'important');
                    
                    const box = el.querySelector('.modal-box');
                    if (box) {
                        box.style.setProperty('opacity', '1', 'important');
                        box.style.setProperty('visibility', 'visible', 'important');
                        box.style.setProperty('display', 'block', 'important');
                    }
                    
                    el.classList.add('ativa');
                    document.body.style.overflow = 'hidden';
                } else {
                    alert('Erro crítico: HTML do modal não encontrado!');
                }
            } catch (e) {
                alert('Erro JS identificado ao abrir modal: ' + e.message);
                console.error(e);
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                fecharModal(event.target.id);
            }
        }



        // --- COMPARTILHAMENTO ---
        async function gerarLinkCompartilhamento(btn) {
            const originalHtml = btn ? btn.innerHTML : 'COMPARTILHAR';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> GERANDO...';
            }

            try {
                const formData = new FormData();
                formData.append('id_sistema', <?= $id_sistema ?>);

                const res = await fetch('../app/ajax/gerar-convite-sistema.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.sucesso) {
                    document.getElementById('input-link-compartilhar').value = data.link;
                    abrirModal('modal-compartilhar-sistema');
                } else {
                    await TableModal.alert('Erro: ' + data.mensagem, 'Erro ao Compartilhar', 'error');
                }
            } catch (e) {
                console.error(e);
                await TableModal.alert('Erro ao gerar o link de compartilhamento.', 'Erro', 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }
        }

        function copiarLinkSistema() {
            const input = document.getElementById('input-link-compartilhar');
            input.select();
            input.setSelectionRange(0, 99999);
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(input.value);
            } else {
                document.execCommand('copy');
            }

            const msg = document.getElementById('msg-copiado');
            msg.style.opacity = '1';
            setTimeout(() => { msg.style.opacity = '0'; }, 2000);
        }

        // --- EXCLUSÃO DE SISTEMA ---
        function abrirModalExclusaoSistema() {
            const input1 = document.getElementById('input-confirm-1');
            const input2 = document.getElementById('input-confirm-2');
            if(input1) input1.value = '';
            if(input2) input2.value = '';
            const btnExcluir = document.getElementById('btn-confirmar-exclusao-sistema');
            if(btnExcluir && input1 && input2) {
                btnExcluir.disabled = true;
            }
            abrirModal('modal-excluir-sistema');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Gatilho para criar nova criatura vindo de link externo
            const urlParams = new URLSearchParams(window.location.search);
            const action = urlParams.get('action');
            if (action === 'criar_criatura' || action === 'criar_ameaca') {
                const btnCriaturas = document.querySelector('.btn-tab-sistema[onclick*="criaturas"]');
                if (btnCriaturas) {
                    switchSistemaTab('criaturas', btnCriaturas);
                }
                resetarModalMonstro();
                abrirModal('modal-criar-monstro');
            }

            const input1 = document.getElementById('input-confirm-1');
            const input2 = document.getElementById('input-confirm-2');
            const btnExcluir = document.getElementById('btn-confirmar-exclusao-sistema');
            const nomeSistemaOriginal = "<?= addslashes($sistema['nm_sistema'] ?? '') ?>".trim().toUpperCase();

            function validarExclusao() {
                if (input1 && input2 && btnExcluir) {
                    if (input1.value.trim().toUpperCase() === nomeSistemaOriginal && input2.value.trim().toUpperCase() === nomeSistemaOriginal) {
                        btnExcluir.disabled = false;
                    } else {
                        btnExcluir.disabled = true;
                    }
                }
            }

            if (input1) input1.addEventListener('input', validarExclusao);
            if (input2) input2.addEventListener('input', validarExclusao);

            if (btnExcluir) {
                btnExcluir.addEventListener('click', async function() {
                    btnExcluir.disabled = true;
                    btnExcluir.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                    try {
                        const res = await fetch('../app/ajax/deletar-item.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ tipo: '<?= ($isDono || (isset($_SESSION['usuario']['cargo']) && strtolower($_SESSION['usuario']['cargo']) === 'admin')) ? 'sistema' : 'sistema_vinculo' ?>', id: <?= $id_sistema ?> })
                        });
                        const data = await res.json();

                        if (data.success) {
                            window.location.href = 'perfil.php';
                        } else {
                            alert('Erro ao excluir: ' + data.error);
                            btnExcluir.disabled = false;
                            btnExcluir.innerHTML = 'EXCLUIR';
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Erro de conexão.');
                        btnExcluir.disabled = false;
                        btnExcluir.innerHTML = 'EXCLUIR';
                    }
                });
            }

            // GESTÃO GLOBAL DE TOOLTIPS (EVITA CORTE DO OVERFLOW)
            const globalTooltip = document.createElement('div');
            globalTooltip.className = 'tooltip global-tooltip';
            globalTooltip.style.position = 'fixed';
            globalTooltip.style.zIndex = '999999';
            globalTooltip.style.pointerEvents = 'none';
            globalTooltip.style.transition = 'opacity 0.2s, transform 0.2s';
            globalTooltip.style.opacity = '0';
            globalTooltip.style.transform = 'translateY(-50%) scale(0.9)';
            globalTooltip.style.visibility = 'hidden';
            globalTooltip.style.display = 'block';
            
            const arrow = document.createElement('div');
            arrow.style.position = 'absolute';
            arrow.style.top = '50%';
            arrow.style.left = '100%';
            arrow.style.marginTop = '-6px';
            arrow.style.borderWidth = '6px';
            arrow.style.borderStyle = 'solid';
            arrow.style.borderColor = 'transparent transparent transparent var(--premium-accent)';
            globalTooltip.appendChild(arrow);
            
            const contentDiv = document.createElement('div');
            globalTooltip.appendChild(contentDiv);
            document.body.appendChild(globalTooltip);

            document.querySelectorAll('.info-icon-wrapper').forEach(wrapper => {
                const innerTooltip = wrapper.querySelector('.tooltip');
                if(!innerTooltip) return;

                wrapper.addEventListener('mouseenter', () => {
                    contentDiv.innerHTML = innerTooltip.innerHTML;
                    globalTooltip.style.visibility = 'visible';
                    globalTooltip.style.opacity = '1';
                    globalTooltip.style.transform = 'translateY(-50%) scale(1)';
                    
                    const rect = wrapper.getBoundingClientRect();
                    globalTooltip.style.top = (rect.top + rect.height / 2) + 'px';
                    globalTooltip.style.left = (rect.left - globalTooltip.offsetWidth - 15) + 'px';
                });

                wrapper.addEventListener('mouseleave', () => {
                    globalTooltip.style.opacity = '0';
                    globalTooltip.style.transform = 'translateY(-50%) scale(0.9)';
                    setTimeout(() => {
                        if(globalTooltip.style.opacity === '0') {
                            globalTooltip.style.visibility = 'hidden';
                        }
                    }, 200);
                });
            });
        });

        async function removerSistema(idS) {
            if (!await TableModal.confirm("Deseja realmente apagar este sistema? Esta ação é irreversível.", "Apagar Sistema", "warning")) return;
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
                    await TableModal.alert("Erro ao remover: " + data.error, "Erro ao Remover", "error");
                }
            } catch (e) { 
                console.error(e); 
                await TableModal.alert("Erro de comunicação com o servidor.", "Erro de Conexão", "error");
            }
        }

        // --- AMEAÇAS (MONSTROS) ---
        function resetarModalMonstro() {
            const fields = ['m-id', 'm-imagem-atual', 'm-nome', 'm-vd', 'm-vida', 'm-defesa', 'm-xp', 'm-desc'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = (id === 'm-nome' || id === 'm-desc' || id === 'm-imagem-atual' || id === 'm-id') ? '' : 0;
            });
            const tipo = document.getElementById('m-tipo');
            if (tipo) tipo.value = 'Ameaça';
            
            const preview = document.getElementById('preview-monstro-container');
            if (preview) {
                preview.innerHTML = `
                    <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: var(--premium-accent); opacity: 0.5;"></i>
                    <span style="position: absolute; bottom: 10px; font-size: 0.6rem; color: #aaa; font-weight: 800; text-transform: uppercase;">Imagem</span>
                `;
            }
            
            const btnSave = document.getElementById('btn-save-monstro');
            if (btnSave) btnSave.innerHTML = '<i class="fas fa-skull"></i> CONVOCAR Ameaça';
            
            document.querySelectorAll('.attr-input-premium').forEach(input => input.value = 0);
        }

        function previewImagemMonstro(input) {
            const preview = document.getElementById('preview-monstro-container');
            if (input.files && input.files[0] && preview) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        async function editarMonstro(idM) {
            try {
                const res = await fetch(`../app/ajax/get-monstro-detalhes.php?id=${idM}`);
                const data = await res.json();
                if (data.success) {
                    const m = data.monstro;
                    document.getElementById('m-id').value = m.id_monstro;
                    document.getElementById('m-nome').value = m.nm_monstro;
                    document.getElementById('m-tipo').value = m.tp_monstro || 'Ameaça';
                    document.getElementById('m-vd').value = m.qt_vd || 0;
                    document.getElementById('m-vida').value = m.qt_vida || 0;
                    document.getElementById('m-defesa').value = m.qt_defesa || 0;
                    document.getElementById('m-xp').value = m.qt_xp_recompensa || 0;
                    document.getElementById('m-desc').value = m.ds_monstro || '';
                    document.getElementById('m-imagem-atual').value = m.ds_imagem || '';
                    
                    const preview = document.getElementById('preview-monstro-container');
                    if (m.ds_imagem && preview) {
                        preview.innerHTML = `<img src="${m.ds_imagem}" style="width:100%; height:100%; object-fit:cover;">`;
                    }
                    data.atributos.forEach(at => {
                        const input = document.querySelector(`.attr-input-premium[data-id="${at.id_atributo}"]`);
                        if (input) input.value = at.qt_valor;
                    });
                    document.getElementById('btn-save-monstro').innerHTML = '<i class="fas fa-skull"></i> ATUALIZAR Ameaça';
                    abrirModal('modal-criar-monstro');
                }
            } catch (e) { console.error(e); }
        }

        async function salvarMonstro() {
            const idS = <?= $id_sistema ?>;
            const idM = document.getElementById('m-id') ? document.getElementById('m-id').value : '';
            const nome = document.getElementById('m-nome') ? document.getElementById('m-nome').value : '';
            const tipo = document.getElementById('m-tipo') ? document.getElementById('m-tipo').value : '';
            const vd = document.getElementById('m-vd') ? document.getElementById('m-vd').value : 0;
            const vida = document.getElementById('m-vida') ? document.getElementById('m-vida').value : 0;
            const defesa = document.getElementById('m-defesa') ? document.getElementById('m-defesa').value : 0;
            const xp = document.getElementById('m-xp') ? document.getElementById('m-xp').value : 0;
            const desc = document.getElementById('m-desc') ? document.getElementById('m-desc').value : '';
            const fotoInput = document.getElementById('m-foto');
            const foto = (fotoInput && fotoInput.files) ? fotoInput.files[0] : null;
            const imgAtual = document.getElementById('m-imagem-atual') ? document.getElementById('m-imagem-atual').value : '';

            if (!nome) {
                if (typeof TableModal !== 'undefined') {
                    await TableModal.alert('Dê um nome à ameaça!', 'Nome Requerido', 'warning');
                } else {
                    alert('Dê um nome à ameaça!');
                }
                return;
            }

            const atributos = [];
            document.querySelectorAll('.attr-input-premium').forEach(input => {
                atributos.push({ id: input.dataset.id, valor: input.value });
            });

            const btn = document.getElementById('btn-save-monstro');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> SALVANDO...';
            }

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
                    if (typeof TableModal !== 'undefined') await TableModal.alert('Erro: ' + data.error, 'Erro ao Convocação', 'error');
                    else alert('Erro: ' + data.error);
                }
            } catch (e) { 
                console.error(e); 
                if (typeof TableModal !== 'undefined') await TableModal.alert("Erro de comunicação com o servidor.", "Erro de Conexão", "error");
                else alert("Erro de comunicação com o servidor.");
            }
            finally { 
                if (btn) {
                    btn.disabled = false; 
                    btn.innerHTML = '<i class="fas fa-skull"></i> CONVOCAR Ameaça'; 
                }
            }
        }

        async function removerMonstro(idM) {
            if (!await TableModal.confirm("Tem certeza que deseja banir esta ameaça para o Outro Lado?", "Banir Ameaça", "warning")) return;

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
                    await TableModal.alert("Erro ao remover: " + data.error, "Erro ao Remover", "error");
                }
            } catch (e) { 
                console.error(e); 
                await TableModal.alert("Erro de comunicação com o servidor.", "Erro de Conexão", "error");
            }
        }

        async function exibirFichaMonstro(idM) {
            const container = document.getElementById('ficha-monstro-render');
            if (container) container.innerHTML = '<div style="padding: 40px; text-align: center; color: #888;"><i class="fas fa-spinner fa-spin"></i> Lendo Grimório...</div>';
            abrirModal('modal-ficha-monstro');

            try {
                const res = await fetch(`../app/ajax/get-monstro-detalhes.php?id=${idM}`);
                const data = await res.json();
                if (data.success) {
                    const m = data.monstro;
                    const attrs = data.atributos;
                    const imgCriatura = (m.ds_imagem && m.ds_imagem !== '../img/logo_icone.png') ? m.ds_imagem : '../img/logo_icone.png';
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
                                    <div class="premium-attr-box" style="height: 50px;">
                                        <span class="attr-abbr" style="font-size: 0.85rem; width: 60px;">${a.ds_abreviacao || a.nm_atributo.substring(0, 3).toUpperCase()}</span>
                                        <div class="attr-circle" style="border-color: ${a.qt_valor > 0 ? 'var(--premium-accent)' : '#444'}; font-size: 1.2rem;">${a.qt_valor}</div>
                                        <div class="tooltip">${a.nm_atributo}</div>
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

    <!-- ESTILO DO MODAL DE EXCLUSÃO (IGUAL PERFIL) -->
    <style>
        .modal-perfil-content {
            background: #ffffff; color: #333; width: 90%; max-width: 450px;
            padding: 40px; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            text-align: center; animation: slideUp 0.3s ease; position: relative; margin: auto;
        }
        .modal-perfil-header i { font-size: 3rem; color: #f1c40f; margin-bottom: 15px; }
        .modal-perfil-header h2 { color: #e63946; font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; }
        .modal-perfil-body p { font-size: 1rem; color: #555; margin-bottom: 25px; }
        .input-perfil-modal {
            width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 10px;
            font-size: 1rem; text-align: center; margin-bottom: 30px; outline: none; transition: border-color 0.3s;
            color: #333; background: #fff;
        }
        .input-perfil-modal:focus { border-color: #e63946; }
        .modal-perfil-footer { display: flex; gap: 15px; justify-content: center; }
        .btn-perfil-cancelar {
            background: #718096; color: white; border: none; padding: 12px 25px;
            border-radius: 10px; font-weight: 700; cursor: pointer; transition: opacity 0.2s; font-size: 1rem;
        }
        .btn-perfil-cancelar:hover { opacity: 0.8; }
        .btn-perfil-deletar {
            background: #e63946; color: white; border: none; padding: 12px 35px;
            border-radius: 10px; font-weight: 700; cursor: pointer; transition: all 0.3s; font-size: 1rem;
        }
        .btn-perfil-deletar:disabled { background: #ccc; cursor: not-allowed; opacity: 0.6; }
        .btn-perfil-deletar:not(:disabled):hover { background: #c92a3a; transform: translateY(-2px); }
    </style>

    <div id="modal-excluir-sistema" class="modal-overlay">
        <div class="modal-perfil-content">
            <div class="modal-perfil-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Excluir Sistema</h2>
            </div>
            <div class="modal-perfil-body">
                <?php if ($isImportado && !$isDono): ?>
                    <p>Tem certeza que deseja remover este sistema importado da sua conta?</p>
                <?php else: ?>
                    <p>Esta ação é <strong>permanente</strong> e excluirá o sistema para <strong>TODOS</strong> os usuários que o importaram.</p>
                    <p style="margin-top: 15px; font-size: 0.9rem; color: #555;">Para confirmar, digite o nome do sistema duas vezes abaixo:</p>
                    <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
                        <input type="text" id="input-confirm-1" placeholder="Nome do sistema..." class="input-perfil-modal" style="margin-bottom: 0;">
                        <input type="text" id="input-confirm-2" placeholder="Repita o nome..." class="input-perfil-modal" style="margin-bottom: 20px;">
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-perfil-footer">
                <button class="btn-perfil-cancelar" onclick="fecharModal('modal-excluir-sistema')">Cancelar</button>
                <button id="btn-confirmar-exclusao-sistema" class="btn-perfil-deletar" <?= ($isImportado && !$isDono) ? '' : 'disabled' ?>>Deletar</button>
            </div>
        </div>
    </div>
</body>
</html>


