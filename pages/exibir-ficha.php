<?php
require_once __DIR__ . '/../app/config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

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

    // Migração Silenciosa de Qualidade Absoluta
    try {
        // Alterar tp_habilidade de ENUM para VARCHAR para suportar 'habilidade' e 'poder'
        $pdo->exec("ALTER TABLE tb_habilidade MODIFY COLUMN tp_habilidade VARCHAR(50) DEFAULT 'habilidade'");
        
        // Colunas adicionais de Defesa, Bloqueio, Esquiva na tb_personagem
        $chk = $pdo->query("SHOW COLUMNS FROM tb_personagem LIKE 'qt_bloqueio'");
        if ($chk->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_personagem ADD COLUMN qt_bloqueio INT DEFAULT 0");
        }
        $chk = $pdo->query("SHOW COLUMNS FROM tb_personagem LIKE 'qt_esquiva'");
        if ($chk->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_personagem ADD COLUMN qt_esquiva INT DEFAULT 0");
        }
        $chk = $pdo->query("SHOW COLUMNS FROM tb_personagem LIKE 'qt_defesa_equip'");
        if ($chk->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_personagem ADD COLUMN qt_defesa_equip INT DEFAULT 0");
        }
        $chk = $pdo->query("SHOW COLUMNS FROM tb_personagem LIKE 'qt_defesa_outros'");
        if ($chk->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_personagem ADD COLUMN qt_defesa_outros INT DEFAULT 0");
        }
        
        // Colunas de Proteção, Resistências, Proficiências na tb_personagem
        $chk = $pdo->query("SHOW COLUMNS FROM tb_personagem LIKE 'ds_protecao'");
        if ($chk->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_personagem ADD COLUMN ds_protecao VARCHAR(300) NULL");
        }
        $chk = $pdo->query("SHOW COLUMNS FROM tb_personagem LIKE 'ds_resistencias'");
        if ($chk->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_personagem ADD COLUMN ds_resistencias VARCHAR(300) NULL");
        }
        $chk = $pdo->query("SHOW COLUMNS FROM tb_personagem LIKE 'ds_proficiencias'");
        if ($chk->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_personagem ADD COLUMN ds_proficiencias VARCHAR(300) NULL");
        }
        
        // Coluna qt_outros na tb_personagem_pericia para bônus adicionais
        $chk = $pdo->query("SHOW COLUMNS FROM tb_personagem_pericia LIKE 'qt_outros'");
        if ($chk->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_personagem_pericia ADD COLUMN qt_outros INT DEFAULT 0");
        }

        // --- ATUALIZAÇÃO AUTOMÁTICA DE DADOS (PATCH DO BANCO DE DADOS) ---
        // 1. Reclassificar as Habilidades do Paraverso (id_sistema = 2) de acordo com o pedido do usuário
        $pdo->exec("UPDATE tb_habilidade SET tp_habilidade = 'habilidade' WHERE id_sistema = 2 AND nm_habilidade IN ('Resistência Extrema', 'Segundo Fôlego', 'Análise Rápida', 'Interface Neural', 'Leitura de Intenções', 'Comando Tático', 'Sentir Fissuras', 'Improviso')");
        $pdo->exec("UPDATE tb_habilidade SET tp_habilidade = 'poder' WHERE id_sistema = 2 AND nm_habilidade IN ('Golpe Brutal', 'Projeção de Ilusão', 'Emboscada', 'Visão Antecipada', 'Singularidade Ampliada', 'Portal Curto', 'Projétil de Energia', 'Escudo de Plasma', 'Desaceleração Local', 'Invocação Menor', 'Cura pela Vontade', 'Controle de Máquina', 'Véu das Sombras', 'Abraço da Escuridão', 'Garra da Sombra', 'Sumir na Penumbra', 'Apagão')");
        
        // 2. Atualizar bônus e treinos da Nrya Riorson
        $pdo->exec("UPDATE tb_personagem SET qt_bloqueio = 0, qt_esquiva = 17, qt_defesa_equip = 0, qt_defesa_outros = 0, ds_protecao = 'Proteção Leve', ds_resistencias = '', ds_proficiencias = 'Armas simples, táticas e proteções leves' WHERE nm_personagem = 'Nrya Riorson'");
        
        // 3. Atualizar perícias da Nrya Riorson para Bônus 5 e Treino 5 (+5)
        $pdo->exec("
            UPDATE tb_personagem_pericia pp
            JOIN tb_personagem p ON pp.id_personagem = p.id_personagem
            SET pp.qt_valor = 5, pp.fl_treinado = 1
            WHERE p.nm_personagem = 'Nrya Riorson' AND pp.id_pericia IN (
                SELECT id_pericia FROM tb_pericia WHERE id_sistema = 2 AND nm_pericia IN ('Briga', 'Pontaria', 'Investigação', 'Manipulação')
            )
        ");
    } catch (Exception $migE) {
        // Silencioso
    }

    // Higienização inteligente de perícias de Ordem Paranormal (ID 1)
    $stmtCheckPer = $pdo->query("SELECT COUNT(*) FROM tb_pericia WHERE id_sistema = 1 AND (ds_atributo_base IS NULL OR CHAR_LENGTH(ds_atributo_base) > 3 OR nm_pericia NOT IN ('Acrobacia','Adestramento','Artes','Atletismo','Atualidades','Ciências','Crime','Diplomacia','Enganação','Fortitude','Furtividade','Iniciativa','Intimidação','Intuição','Investigação','Luta','Medicina','Ocultismo','Percepção','Pilotagem','Pontaria','Profissão','Reflexos','Religião','Sobrevivência','Tática','Tecnologia','Vontade'))");
    if ($stmtCheckPer && $stmtCheckPer->fetchColumn() > 0) {
        $pericias_oficiais = [
            'Acrobacia' => 'AGI', 'Adestramento' => 'PRE', 'Artes' => 'PRE', 'Atletismo' => 'FOR',
            'Atualidades' => 'INT', 'Ciências' => 'INT', 'Crime' => 'AGI', 'Diplomacia' => 'PRE',
            'Enganação' => 'PRE', 'Fortitude' => 'VIG', 'Furtividade' => 'AGI', 'Iniciativa' => 'AGI',
            'Intimidação' => 'PRE', 'Intuição' => 'PRE', 'Investigação' => 'INT', 'Luta' => 'FOR',
            'Medicina' => 'INT', 'Ocultismo' => 'INT', 'Percepção' => 'PRE', 'Pilotagem' => 'AGI',
            'Pontaria' => 'AGI', 'Profissão' => 'INT', 'Reflexos' => 'AGI', 'Religião' => 'INT',
            'Sobrevivência' => 'INT', 'Tática' => 'INT', 'Tecnologia' => 'INT', 'Vontade' => 'PRE'
        ];
        
        $nomes_oficiais_str = implode("', '", array_map('addslashes', array_keys($pericias_oficiais)));
        
        // Deletar vínculos de perícias inúteis do sistema 1
        $pdo->exec("
            DELETE pp FROM tb_personagem_pericia pp
            JOIN tb_pericia p ON pp.id_pericia = p.id_pericia
            WHERE p.id_sistema = 1 AND p.nm_pericia NOT IN ('$nomes_oficiais_str')
        ");
        
        // Deletar as perícias inúteis/insignificantes
        $pdo->exec("
            DELETE FROM tb_pericia 
            WHERE id_sistema = 1 AND nm_pericia NOT IN ('$nomes_oficiais_str')
        ");
        
        // Cadastrar/atualizar as corretas
        foreach ($pericias_oficiais as $nome => $attr) {
            $stmtC = $pdo->prepare("SELECT id_pericia FROM tb_pericia WHERE nm_pericia = ? AND id_sistema = 1");
            $stmtC->execute([$nome]);
            $id_per = $stmtC->fetchColumn();
            
            if ($id_per) {
                $stmtU = $pdo->prepare("UPDATE tb_pericia SET ds_atributo_base = ? WHERE id_pericia = ?");
                $stmtU->execute([$attr, $id_per]);
            } else {
                $stmtI = $pdo->prepare("INSERT INTO tb_pericia (nm_pericia, ds_atributo_base, id_sistema) VALUES (?, ?, 1)");
                $stmtI->execute([$nome, $attr]);
            }
        }
        
        // Re-sincronizar os personagens existentes do sistema 1
        $stmtP = $pdo->query("SELECT id_personagem FROM tb_personagem WHERE id_sistema = 1");
        $persIds = $stmtP->fetchAll(PDO::FETCH_COLUMN);
        
        $stmtPer = $pdo->query("SELECT id_pericia FROM tb_pericia WHERE id_sistema = 1");
        $perIds = $stmtPer->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($persIds as $id_p) {
            foreach ($perIds as $id_per) {
                $stmtCheckV = $pdo->prepare("SELECT 1 FROM tb_personagem_pericia WHERE id_personagem = ? AND id_pericia = ?");
                $stmtCheckV->execute([$id_p, $id_per]);
                if (!$stmtCheckV->fetch()) {
                    $stmtInV = $pdo->prepare("INSERT INTO tb_personagem_pericia (id_personagem, id_pericia, qt_valor) VALUES (?, ?, 0)");
                    $stmtInV->execute([$id_p, $id_per]);
                }
            }
        }
    }

    // Buscar dados básicos do personagem
    $stmt = $pdo->prepare("
        SELECT p.*, s.nm_sistema, s.ds_background, c.nm_classe, o.nm_origem 
        FROM tb_personagem p
        LEFT JOIN tb_sistema s ON p.id_sistema = s.id_sistema
        LEFT JOIN tb_personagem_classe pc ON p.id_personagem = pc.id_personagem
        LEFT JOIN tb_classe c ON pc.id_classe = c.id_classe
        LEFT JOIN tb_personagem_origem po ON p.id_personagem = po.id_personagem
        LEFT JOIN tb_origem o ON po.id_origem = o.id_origem
        WHERE p.id_personagem = ?
    ");
    $stmt->execute([$id_personagem]);
    $personagem = $stmt->fetch();

    if (!$personagem) {
        die("Personagem não encontrado.");
    }

    // Lógica de Permissão: Dono do personagem ou Mestre de alguma campanha que o personagem participa
    $pode_ver = false;
    if ((int)$personagem['id_usuario'] === (int)$_SESSION['usuario']['id']) {
        $pode_ver = true;
    } else {
        // Verifica se o usuário logado é mestre de alguma campanha onde este personagem está
        $stmtMestre = $pdo->prepare("
            SELECT 1 FROM tb_campanha_personagem cp
            JOIN tb_campanha c ON cp.id_campanha = c.id_campanha
            WHERE cp.id_personagem = ? AND c.id_usuario_mestre = ?
            LIMIT 1
        ");
        $stmtMestre->execute([$id_personagem, $_SESSION['usuario']['id']]);
        if ($stmtMestre->fetch()) {
            $pode_ver = true;
        } else {
            // Verifica se o usuário logado é jogador de alguma campanha onde o personagem está e se a ficha está configurada como pública
            $stmtPublica = $pdo->prepare("
                SELECT 1 FROM tb_campanha_personagem cp
                JOIN tb_campanha_usuario cu ON cp.id_campanha = cu.id_campanha
                WHERE cp.id_personagem = ? AND cu.id_usuario = ? AND cp.fl_publico = 1
                LIMIT 1
            ");
            $stmtPublica->execute([$id_personagem, $_SESSION['usuario']['id']]);
            if ($stmtPublica->fetch()) {
                $pode_ver = true;
            }
        }
    }

    if (!$pode_ver) {
        die("Esta ficha está privada ou você não tem permissão para visualizá-la.");
    }

    // Lógica para Temas Dinâmicos
    $nomeSistemaLower = strtolower($personagem['nm_sistema'] ?? '');
    $classeBackground = '';
    if (strpos($nomeSistemaLower, 'ordem paranormal') !== false) {
        $classeBackground = 'tema-ordem-paranormal';
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
        SELECT p.id_pericia, p.nm_pericia, p.ds_atributo_base, COALESCE(pp.qt_valor, 0) as qt_valor, COALESCE(pp.fl_treinado, 0) as fl_treinado, COALESCE(pp.qt_outros, 0) as qt_outros
        FROM tb_pericia p
        LEFT JOIN tb_personagem_pericia pp ON p.id_pericia = pp.id_pericia AND pp.id_personagem = ?
        WHERE p.id_sistema = ?
        ORDER BY p.nm_pericia ASC
    ");
    $stmt->execute([$id_personagem, $personagem['id_sistema']]);
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
            ['nm_status' => 'SANIDADE', 'ds_cor' => '#a855f7', 'qt_atual' => $personagem['qt_sanidade'], 'qt_max' => $personagem['qt_sanidade_maxima'], 'id_status_sistema' => 'sanidade'],
            ['nm_status' => 'ESFORÇO', 'ds_cor' => '#f97316', 'qt_atual' => $personagem['qt_esforco'], 'qt_max' => $personagem['qt_esforco_maximo'], 'id_status_sistema' => 'esforco']
        ];
    }

    // Buscar Defesas Customizadas do Sistema com valores do Personagem
    $stmt = $pdo->prepare("
        SELECT ss.*, COALESCE(ps.qt_valor_atual, 10) as qt_atual 
        FROM tb_sistema_status ss
        LEFT JOIN tb_personagem_status ps ON ss.id_status_sistema = ps.id_status_sistema AND ps.id_personagem = ?
        WHERE ss.id_sistema = ? AND ss.tp_status = 'defesa'
    ");
    $stmt->execute([$id_personagem, $personagem['id_sistema']]);
    $status_defesas = $stmt->fetchAll();

    // Buscar Itens do Personagem (Inventário)
    $stmt = $pdo->prepare("
        SELECT i.*, pi.qt_quantidade, pi.id_personagem_item
        FROM tb_personagem_item pi
        JOIN tb_item i ON pi.id_item = i.id_item
        WHERE pi.id_personagem = ?
    ");
    $stmt->execute([$id_personagem]);
    $itens_personagem = $stmt->fetchAll();

    // Buscar Habilidades e Poderes do Personagem
    $stmt = $pdo->prepare("
        SELECT h.*, hp.id_habilidade_personagem
        FROM tb_habilidade_personagem hp
        JOIN tb_habilidade h ON hp.id_habilidade = h.id_habilidade
        WHERE hp.id_personagem = ?
    ");
    $stmt->execute([$id_personagem]);
    $habilidades_todas = $stmt->fetchAll();

    $habilidades_personagem = array_filter($habilidades_todas, function($h) {
        return strtolower($h['tp_habilidade'] ?? '') !== 'poder';
    });
    $poderes_personagem = array_filter($habilidades_todas, function($h) {
        return strtolower($h['tp_habilidade'] ?? '') === 'poder';
    });

    // --- BUSCAR COMPONENTES GLOBAIS DO SISTEMA (Para o botão Adicionar) ---
    // Itens Globais
    $stmt = $pdo->prepare("SELECT * FROM tb_item WHERE id_sistema = ? ORDER BY nm_item ASC");
    $stmt->execute([$personagem['id_sistema']]);
    $itens_sistema = $stmt->fetchAll();

    // Habilidades Globais
    $stmt = $pdo->prepare("SELECT * FROM tb_habilidade WHERE id_sistema = ? ORDER BY nm_habilidade ASC");
    $stmt->execute([$personagem['id_sistema']]);
    $habilidades_todas_sistema = $stmt->fetchAll();

    $habilidades_sistema = array_filter($habilidades_todas_sistema, function($h) {
        return strtolower($h['tp_habilidade'] ?? '') !== 'poder';
    });
    $poderes_sistema = array_filter($habilidades_todas_sistema, function($h) {
        return strtolower($h['tp_habilidade'] ?? '') === 'poder';
    });

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/ficha.css?v=<?= time() ?>">
    <style>
        .premium-bars-area { display: flex; flex-direction: column; gap: 20px !important; margin-bottom: 30px; }
        .attr-circle { position: relative; }

        /* ESTILOS DE FILTROS PREMIUM DE CATÁLOGO (GLASSMORPHISM & GLOW NEON) */
        .pills-container {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
            padding: 5px 0;
            width: 100%;
        }
        .sub-pills-container {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 15px;
            padding: 8px 12px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.03);
            width: 100%;
            animation: fadeInSubPills 0.3s ease;
        }
        @keyframes fadeInSubPills {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .pill-btn {
            background: rgba(255, 255, 255, 0.03);
            color: #888;
            border: 1px solid rgba(255, 255, 255, 0.07);
            padding: 6px 16px;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .pill-btn:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
        }
        .pill-btn.ativo {
            color: #fff !important;
            background: var(--premium-accent, #9d7aff) !important;
            border-color: var(--premium-accent, #9d7aff) !important;
            box-shadow: 0 0 12px rgba(157, 122, 255, 0.45);
        }
        /* Ajuste do glow temático vermelho de Ordem Paranormal */
        body.tema-ordem-paranormal .pill-btn.ativo {
            box-shadow: 0 0 12px rgba(255, 50, 50, 0.6) !important;
        }

        .bar-num span[contenteditable="true"] { border-bottom: 1px dashed rgba(255,255,255,0.2); padding: 0 2px; display: inline-block; min-width: 20px; outline: none; transition: all 0.2s; }
        .bar-num span[contenteditable="true"]:focus { background: rgba(255,255,255,0.1); border-radius: 4px; }
        .shield-number[contenteditable="true"] { cursor: text; border-bottom: 1px dashed rgba(255,255,255,0.3); outline: none; }
        .bar-bg { overflow: hidden; position: relative; }
        .bar-fill { transition: width 0.4s cubic-bezier(0.23, 1, 0.32, 1); height: 100%; position: absolute !important; left: 0; top: 0; z-index: 1; }
        .bar-num { position: absolute; width: 100%; text-align: center; z-index: 5; pointer-events: none; left: 0; }
        .bar-num span { pointer-events: auto; }
        .step-btn { outline: none !important; }

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
        body.tema-ordem-paranormal .btn-p,
        body.tema-ordem-paranormal .extra-stat-box,
        body.tema-ordem-paranormal .formula-label {
            font-weight: 700 !important;
            color: white !important;
            text-shadow: 0 0 10px rgba(255,255,255,0.8) !important;
            letter-spacing: 2px !important;
        }

        /* Background Dinâmico - Posicionado debaixo da navbar */
        body.tema-ordem-paranormal::before {
            content: '';
            position: fixed;
            top: 80px; /* Debaixo da navbar */
            left: 0; 
            width: 100%; 
            height: calc(100vh - 80px);
            background: radial-gradient(circle at center, transparent 0%, #000 90%),
                        url('<?= !empty($personagem['ds_background']) ? $personagem['ds_background'] : '../img/ordem-paranormal-icon.png' ?>') center/cover no-repeat;
            opacity: 0.55;
            z-index: -1;
            pointer-events: none;
            filter: grayscale(0.2) contrast(1.1);
        }

        /* Borda da foto de capa e avatar */
        body.tema-ordem-paranormal .premium-avatar {
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
        body.tema-ordem-paranormal .premium-header {
            background: rgba(15, 5, 5, 0.85) !important;
            border: 2px solid rgba(255, 50, 50, 0.3) !important;
            box-shadow: 0 0 25px rgba(255, 50, 50, 0.15) !important;
            padding: 30px !important;
            border-radius: 20px !important;
            box-sizing: border-box !important;
        }

        body.tema-ordem-paranormal .nivel-deslocamento-row {
            background: rgba(15, 5, 5, 0.85) !important;
            border: 2px solid rgba(255, 50, 50, 0.3) !important;
            box-shadow: 0 0 20px rgba(255, 50, 50, 0.15) !important;
            padding: 12px 30px !important;
            border-radius: 16px !important;
            margin-left: auto !important;
            width: fit-content !important;
            box-sizing: border-box !important;
            display: flex !important;
            align-items: center !important;
        }

        body.tema-ordem-paranormal .premium-main {
            background: rgba(15, 5, 5, 0.85) !important;
            border: 2px solid rgba(255, 50, 50, 0.3) !important;
            box-shadow: 0 0 35px rgba(255, 50, 50, 0.2) !important;
            padding: 35px !important;
            border-radius: 24px !important;
            margin-top: 25px !important;
            box-sizing: border-box !important;
        }

        @media (max-width: 768px) {
            body.tema-ordem-paranormal .premium-header {
                padding: 20px 15px !important;
                border-radius: 16px !important;
                text-align: center !important;
            }
            body.tema-ordem-paranormal .nivel-deslocamento-row {
                padding: 12px 15px !important;
                border-radius: 15px !important;
                width: 100% !important;
                margin-left: 0 !important;
                justify-content: center !important;
            }
            body.tema-ordem-paranormal .premium-main {
                padding: 20px 15px !important;
                border-radius: 16px !important;
                margin-top: 15px !important;
            }
        }

        body.tema-ordem-paranormal .btn-p {
            background: linear-gradient(135deg, #660000 0%, #ff3232 100%) !important;
            border-color: rgba(255, 50, 50, 0.3) !important;
            box-shadow: 0 4px 10px rgba(255, 50, 50, 0.25) !important;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1) !important;
        }

        body.tema-ordem-paranormal .btn-p:hover {
            box-shadow: 0 6px 15px rgba(255, 50, 50, 0.45) !important;
            transform: translateY(-3px) scale(1.02) !important;
        }

        body.tema-ordem-paranormal .btn-add-modal,
        body.tema-ordem-paranormal .modal-list-item button {
            box-shadow: 0 4px 10px rgba(255, 50, 50, 0.25) !important;
            transition: all 0.3s ease !important;
        }

        body.tema-ordem-paranormal .btn-add-modal:hover,
        body.tema-ordem-paranormal .modal-list-item button:hover {
            box-shadow: 0 6px 15px rgba(255, 50, 50, 0.45) !important;
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

        body.tema-ordem-paranormal .premium-attr-box {
            box-shadow: 0 4px 15px rgba(255, 50, 50, 0.5) !important;
        }

        body.tema-ordem-paranormal .extra-stat-box span {
            background: #ffffff !important;
            color: #000000 !important;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.6) !important;
            text-shadow: none !important;
        }

        body.tema-ordem-paranormal .premium-info-item label,
        body.tema-ordem-paranormal .p-name,
        body.tema-ordem-paranormal .bar-name {
            color: #ff3232 !important;
        }

        body.tema-ordem-paranormal .premium-info-item input:focus,
        body.tema-ordem-paranormal .bar-num span:focus {
            border-color: #ff3232 !important;
            background: rgba(255, 50, 50, 0.05) !important;
        }

        /* Tooltips Premium no topo para as siglas de atributos grandes */
        .attr-tooltip {
            position: relative;
            cursor: help;
        }

        .attr-tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 135%; /* Exibe acima */
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            background: #1a1220;
            color: #f0e6ff;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif !important;
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s cubic-bezier(0.23, 1, 0.32, 1);
            border: 1px solid var(--premium-accent, #ff3232);
            white-space: nowrap;
            box-shadow: 0 5px 15px rgba(0,0,0,0.4);
            z-index: 9999;
            pointer-events: none;
            letter-spacing: 0.5px;
            text-shadow: none !important;
        }

        .attr-tooltip::before {
            content: "";
            position: absolute;
            bottom: 110%; /* Posiciona a setinha entre a tooltip e o atributo */
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            border-width: 6px;
            border-style: solid;
            border-color: var(--premium-accent, #ff3232) transparent transparent transparent;
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s cubic-bezier(0.23, 1, 0.32, 1);
            z-index: 9999;
            pointer-events: none;
        }

        .attr-tooltip:hover::after,
        .attr-tooltip:hover::before {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        .p-attr {
            font-size: 0.7rem;
            color: #aaa;
            font-weight: 800;
            text-transform: uppercase;
        }
    </style>
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">
    <script src="../js/ficha.js?v=<?= time() ?>" defer></script>
</head>

<body class="ficha-body <?= $classeBackground ?>">

    <!-- NAVBAR PADRÃO INDEX.PHP (MESMO DO INDEX) -->
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
                        <label>Sistema:</label>
                        <input type="text" value="<?= htmlspecialchars($personagem['nm_sistema'] ?? 'Padrão') ?>" readonly
                            style="color: var(--premium-accent); font-weight: 900;">
                    </div>
                    <div class="premium-info-item">
                        <label>Origem:</label>
                        <input type="text" value="<?= htmlspecialchars($personagem['nm_origem'] ?? 'Nenhuma') ?>"
                            readonly>
                    </div>
                </div>
            </div>

            <div class="nivel-deslocamento-row">
                <div class="extra-stat-box">NÍVEL <span id="nivel-valor" contenteditable="true" inputmode="numeric" style="border-bottom:1px dashed rgba(255,255,255,0.3); outline:none; cursor:text; min-width:20px; display:inline-block; text-align:center; font-weight:700;"><?= $personagem['qt_nivel'] ?></span></div>
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
                                <span class="attr-abbr attr-tooltip" data-tooltip="<?= htmlspecialchars($nomeFull) ?>"><?= htmlspecialchars(strtoupper($abbr)) ?></span>
                                <div class="attr-circle" contenteditable="true" inputmode="numeric">
                                    <?= $valor ?>
                                </div>
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
                                            style="width: <?= ($s['qt_max'] > 0) ? min(100, ($s['qt_atual'] / $s['qt_max']) * 100) : 0 ?>%; background: <?= $s['ds_cor'] ?>; box-shadow: 0 0 15px <?= $s['ds_cor'] ?>55;">
                                        </div>
                                        <span class="bar-num">
                                            <span class="val-atual" contenteditable="true" inputmode="numeric"><?= $s['qt_atual'] ?></span>/<span class="val-max" contenteditable="true" inputmode="numeric"><?= $s['qt_max'] ?: 100 ?></span>
                                        </span>
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
                                    <span class="shield-number" contenteditable="true" id="valor-defesa"><?= $personagem['qt_defesa'] ?: 10 ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($status_defesas as $d): ?>
                                <div class="defesa-shield-box" title="<?= htmlspecialchars($d['nm_status']) ?>" data-id-status="<?= $d['id_status_sistema'] ?>">
                                    <i class="fas fa-shield-alt shield-bg-icon" style="color: <?= $d['ds_cor'] ?> !important;"></i>
                                    <div class="shield-text">
                                        <span class="shield-number" contenteditable="true"><?= $d['qt_atual'] ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="defesa-details">
                            <div class="defesa-formula">
                                <span class="formula-label">DEFESA</span>
                                <span class="formula-text">= 10 + AGI + 
                                    <span class="defesa-formula-val" contenteditable="true" inputmode="numeric" data-campo="qt_defesa_equip" style="border-bottom: 1px dashed rgba(255,255,255,0.3); outline:none; cursor:text; min-width:15px; display:inline-block; text-align:center; font-weight: 700;"><?= $personagem['qt_defesa_equip'] ?? 0 ?></span> + 
                                    <span class="defesa-formula-val" contenteditable="true" inputmode="numeric" data-campo="qt_defesa_outros" style="border-bottom: 1px dashed rgba(255,255,255,0.3); outline:none; cursor:text; min-width:15px; display:inline-block; text-align:center; font-weight: 700;"><?= $personagem['qt_defesa_outros'] ?? 0 ?></span>
                                </span>
                                <div class="formula-sub"><span>Equip.</span><span>Outros.</span></div>
                            </div>
                            <div class="defesa-stats-row">
                                <div class="defesa-stat-item">
                                    <label>BLOQUEIO</label>
                                    <span class="val" contenteditable="true" inputmode="numeric" data-campo="qt_bloqueio" style="border-bottom: 1px dashed rgba(255,255,255,0.3); outline:none; cursor:text; min-width:20px; display:inline-block; text-align:center; font-weight: 700;"><?= $personagem['qt_bloqueio'] ?? 0 ?></span>
                                </div>
                                <div class="defesa-stat-item">
                                    <label>ESQUIVA</label>
                                    <span class="val" contenteditable="true" inputmode="numeric" data-campo="qt_esquiva" style="border-bottom: 1px dashed rgba(255,255,255,0.3); outline:none; cursor:text; min-width:20px; display:inline-block; text-align:center; font-weight: 700;"><?= $personagem['qt_esquiva'] ?? 0 ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="premium-footer-lines">
                        <div class="line-item" style="border-bottom: 2px solid rgba(255,255,255,0.15); display: flex; align-items: center; padding-bottom: 8px; margin-bottom: 12px;">
                            <label style="font-weight: 900; font-size: 0.85rem; color: rgba(255,255,255,0.7); margin-right: 15px; flex-shrink: 0; letter-spacing: 1.5px; text-transform: uppercase;">PROTEÇÃO</label>
                            <span class="line-text" contenteditable="true" data-campo="ds_protecao" style="flex: 1; color: #fff; font-size: 0.95rem; font-weight: 600; outline: none; cursor: text; min-height: 20px;"><?= htmlspecialchars($personagem['ds_protecao'] ?? '') ?></span>
                        </div>
                        <div class="line-item" style="border-bottom: 2px solid rgba(255,255,255,0.15); display: flex; align-items: center; padding-bottom: 8px; margin-bottom: 12px;">
                            <label style="font-weight: 900; font-size: 0.85rem; color: rgba(255,255,255,0.7); margin-right: 15px; flex-shrink: 0; letter-spacing: 1.5px; text-transform: uppercase;">RESISTÊNCIAS</label>
                            <span class="line-text" contenteditable="true" data-campo="ds_resistencias" style="flex: 1; color: #fff; font-size: 0.95rem; font-weight: 600; outline: none; cursor: text; min-height: 20px;"><?= htmlspecialchars($personagem['ds_resistencias'] ?? '') ?></span>
                        </div>
                        <div class="line-item" style="border-bottom: 2px solid rgba(255,255,255,0.15); display: flex; align-items: center; padding-bottom: 8px; margin-bottom: 12px;">
                            <label style="font-weight: 900; font-size: 0.85rem; color: rgba(255,255,255,0.7); margin-right: 15px; flex-shrink: 0; letter-spacing: 1.5px; text-transform: uppercase;">PROFICIÊNCIAS</label>
                            <span class="line-text" contenteditable="true" data-campo="ds_proficiencias" style="flex: 1; color: #fff; font-size: 0.95rem; font-weight: 600; outline: none; cursor: text; min-height: 20px;"><?= htmlspecialchars($personagem['ds_proficiencias'] ?? '') ?></span>
                        </div>
                    </div>

                </div>

                <!-- COLUNA DIREIT: PERÍCIAS -->
                <div class="premium-col-right">
                    <div class="pericias-premium-container">
                        <div class="pericias-premium-header">
                            <span class="h-main">PERÍCIA</span>
                            <span class="h-stat">BÔNUS</span>
                            <span class="h-stat">TREINO</span>
                            <span class="h-stat">OUTROS</span>
                        </div>
                        <div class="pericias-premium-list">
                            <?php foreach ($pericias as $p): ?>
                                <div class="p-row">
                                    <div class="p-desc">
                                        <span class="p-name"><?= htmlspecialchars($p['nm_pericia']) ?></span>
                                        <?php 
                                            $attr_base = $p['ds_atributo_base'] ?? '???';
                                            $mapeamento_siglas = [
                                                'força' => 'FOR',
                                                'agilidade' => 'AGI',
                                                'intelecto' => 'INT',
                                                'presença' => 'PRE',
                                                'vigor' => 'VIG',
                                                'resistência' => 'RES',
                                                'instinto' => 'INS',
                                                'sanidade' => 'SAN',
                                                'vontade' => 'VON',
                                                'carisma' => 'CAR',
                                                'destreza' => 'DES',
                                                'sabedoria' => 'SAB',
                                                'constituição' => 'CON',
                                                'inteligência' => 'INT'
                                            ];
                                            $attr_base_lower = mb_strtolower($attr_base, 'UTF-8');
                                            if (isset($mapeamento_siglas[$attr_base_lower])) {
                                                $attr_base = $mapeamento_siglas[$attr_base_lower];
                                            } elseif (strlen($attr_base) > 4) {
                                                $attr_base = mb_strtoupper(mb_substr($attr_base, 0, 3, 'UTF-8'), 'UTF-8');
                                            }
                                        ?>
                                        <span class="p-attr">(<?= htmlspecialchars($attr_base) ?>)</span>
                                    </div>
                                    <div class="p-values" data-pericia-id="<?= $p['id_pericia'] ?>">
                                        <span class="p-bonus" data-campo="qt_valor" data-pericia-id="<?= $p['id_pericia'] ?>">(<?= $p['qt_valor'] ?>)</span>
                                        <span class="p-treino" data-pericia-id="<?= $p['id_pericia'] ?>" title="Clique para alternar treino">+<?= (int)$p['fl_treinado'] ?></span>
                                        <span class="p-outros" contenteditable="true" inputmode="numeric" data-campo="qt_outros" data-pericia-id="<?= $p['id_pericia'] ?>"><?= $p['qt_outros'] ?></span>
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
        const SISTEMA_NOME = <?= json_encode($personagem['nm_sistema'] ?? 'Padrão') ?>;
        const CLASSE_NOME = <?= json_encode($personagem['nm_classe'] ?? 'Mundano') ?>;
        const DADOS_DESCRICAO = {
            aparencia: <?= json_encode($personagem['ds_aparencia'] ?? '') ?>,
            personalidade: <?= json_encode($personagem['ds_personalidade'] ?? '') ?>,
            historia: <?= json_encode($personagem['ds_historia'] ?? '') ?>,
            objetivos: <?= json_encode($personagem['ds_objetivos'] ?? '') ?>
        };
        const DADOS_HABILIDADES = <?= json_encode(array_values($habilidades_personagem)) ?>;
        const DADOS_PODERES = <?= json_encode(array_values($poderes_personagem)) ?>;
        const DADOS_INVENTARIO = <?= json_encode(array_values($itens_personagem)) ?>;

        // Dados Globais do Sistema
        const SISTEMA_HABILIDADES = <?= json_encode(array_values($habilidades_sistema)) ?>;
        const SISTEMA_PODERES = <?= json_encode(array_values($poderes_sistema)) ?>;
        const SISTEMA_ITENS = <?= json_encode(array_values($itens_sistema)) ?>;
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


