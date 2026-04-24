<?php
require_once __DIR__ . '/../config/database.php';

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = Database::getConexao();
        $pdo->beginTransaction();

        $id_usuario = $_SESSION['usuario']['id'];
        $id_sistema = 1; // Ordem Paranormal fixo por enquanto
        $nm_personagem = $_POST['nome'] ?? 'Sem Nome';
        $nm_jogador = $_POST['nome-jogador'] ?? $_SESSION['usuario']['nome'];
        
        $ds_aparencia = $_POST['aparencia'] ?? '';
        $ds_personalidade = $_POST['personalidade'] ?? '';
        $ds_historia = $_POST['historia'] ?? '';
        $ds_objetivos = $_POST['objetivos'] ?? '';
        
        $ds_caracteristicas = "Reduzido: " . substr($ds_aparencia, 0, 100); // Mantido por retrocompatibilidade se houver algum vestígio
        
        $classe_nome = $_POST['classeEscolhida'] ?? '';
        $origem_nome = $_POST['origemEscolhida'] ?? '';

        // Cálculo de Status Base (Regras simplificadas de Ordem)
        $vigor = (int)($_POST['vigor'] ?? 5);
        $vida_base = 12 + $vigor; 
        $sanidade_base = 12;
        $esforco_base = 2 + $vigor;

        // 1. Inserir Personagem principal
        $stmt = $pdo->prepare("
            INSERT INTO tb_personagem 
            (id_usuario, id_sistema, nm_personagem, ds_aparencia, ds_personalidade, ds_historia, ds_objetivos, ds_caracteristicas, qt_vida, qt_vida_maxima, qt_sanidade, qt_sanidade_maxima, qt_esforco, qt_esforco_maximo, qt_nivel) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $id_usuario, $id_sistema, $nm_personagem, 
            $ds_aparencia, $ds_personalidade, $ds_historia, $ds_objetivos, $ds_caracteristicas,
            $vida_base, $vida_base, $sanidade_base, $sanidade_base, $esforco_base, $esforco_base
        ]);
        $id_personagem = $pdo->lastInsertId();

        // 2. Inserir Atributos
        $map_atributos = [
            'forca' => 'Força',
            'inteligencia' => 'Intelecto',
            'vigor' => 'Vigor',
            'agilidade' => 'Agilidade',
            'carisma' => 'Carisma',
            'vontade' => 'Vontade'
        ];

        foreach ($map_atributos as $post_key => $db_nome) {
            $valor = (int)($_POST[$post_key] ?? 5);
            $stmt = $pdo->prepare("SELECT id_atributo FROM tb_atributo WHERE nm_atributo = ? AND id_sistema = 1");
            $stmt->execute([$db_nome]);
            $id_attr = $stmt->fetchColumn();
            
            if ($id_attr) {
                $stmt = $pdo->prepare("INSERT INTO tb_personagem_atributo (id_personagem, id_atributo, qt_valor) VALUES (?, ?, ?)");
                $stmt->execute([$id_personagem, $id_attr, $valor]);
            }
        }

        // 3. Inserir Classe
        if ($classe_nome) {
            $stmt = $pdo->prepare("SELECT id_classe FROM tb_classe WHERE nm_classe = ? AND id_sistema = 1");
            $stmt->execute([$classe_nome]);
            $id_classe = $stmt->fetchColumn();
            if ($id_classe) {
                $stmt = $pdo->prepare("INSERT INTO tb_personagem_classe (id_personagem, id_classe) VALUES (?, ?)");
                $stmt->execute([$id_personagem, $id_classe]);
            }
        }

        // 4. Inserir Origem
        if ($origem_nome) {
            $stmt = $pdo->prepare("SELECT id_origem FROM tb_origem WHERE nm_origem = ? AND id_sistema = 1");
            $stmt->execute([$origem_nome]);
            $id_origem = $stmt->fetchColumn();
            if ($id_origem) {
                $stmt = $pdo->prepare("INSERT INTO tb_personagem_origem (id_personagem, id_origem) VALUES (?, ?)");
                $stmt->execute([$id_personagem, $id_origem]);
            }
        }

        // 5. Inserir Perícias Iniciais (todas com 0 por enquanto)
        $stmt = $pdo->prepare("SELECT id_pericia FROM tb_pericia WHERE id_sistema = 1");
        $stmt->execute();
        $pericias = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($pericias as $id_p) {
            $stmt = $pdo->prepare("INSERT INTO tb_personagem_pericia (id_personagem, id_pericia, qt_valor) VALUES (?, ?, 0)");
            $stmt->execute([$id_personagem, $id_p]);
        }

        $pdo->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id_personagem' => $id_personagem]);

    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
