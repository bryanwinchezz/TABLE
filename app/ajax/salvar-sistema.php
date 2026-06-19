<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$cargo = $_SESSION['usuario']['cargo'] ?? '';
if ($cargo !== 'mestre' && $cargo !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas mestres ou admins podem criar sistemas.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['nome'])) {
    echo json_encode(['success' => false, 'error' => 'Payload inválido ou nome do sistema ausente.']);
    exit;
}

try {
    $pdo = Database::getConexao();

    // ============================================================
    // MIGRATION AUTOMÁTICA SILENCIOSA (Evita colunas desconhecidas)
    // ============================================================
    try {
        // Verifica tb_classe - ds_habilidade
        $chkCla = $pdo->query("SHOW COLUMNS FROM tb_classe LIKE 'ds_habilidade'");
        if ($chkCla->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_classe ADD COLUMN ds_habilidade TEXT NULL");
        }
        // Verifica tb_classe - ds_descricao
        $chkClaDesc = $pdo->query("SHOW COLUMNS FROM tb_classe LIKE 'ds_descricao'");
        if ($chkClaDesc->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_classe ADD COLUMN ds_descricao TEXT NULL");
        }
        // Verifica tb_pericia - ds_habilidade
        $chkPer = $pdo->query("SHOW COLUMNS FROM tb_pericia LIKE 'ds_habilidade'");
        if ($chkPer->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_pericia ADD COLUMN ds_habilidade TEXT NULL");
        }
        // Verifica tb_pericia - ds_descricao
        $chkPerDesc = $pdo->query("SHOW COLUMNS FROM tb_pericia LIKE 'ds_descricao'");
        if ($chkPerDesc->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_pericia ADD COLUMN ds_descricao TEXT NULL");
        }
        // Verifica tb_origem - ds_habilidade
        $chkOri = $pdo->query("SHOW COLUMNS FROM tb_origem LIKE 'ds_habilidade'");
        if ($chkOri->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_origem ADD COLUMN ds_habilidade TEXT NULL");
        }
    } catch (Exception $e) {
        // Silencioso
    }

    $pdo->beginTransaction();

    $id_usuario = $_SESSION['usuario']['id'];
    $nome = $data['nome'];
    $classificacao = $data['classificacao'] ?? 'L';
    $descricao = $data['descricao'] ?? '';
    
    // Processamento da Imagem (Base64)
    $imagem = '../img/foto-como_jogar.jpg'; // Default quando nenhuma capa for enviada
    if (!empty($data['imagem_base64'])) {
        $base64 = $data['imagem_base64'];
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            $data_img = substr($base64, strpos($base64, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'webp'])) {
                $data_img = base64_decode($data_img);
                if ($data_img !== false) {
                    $nome_arquivo = 'sistema_' . time() . '_' . uniqid() . '.' . $type;
                    $caminho_salvamento = __DIR__ . '/../../img/uploads/sistemas/' . $nome_arquivo;
                    
                    // Garante que o diretório existe
                    if (!is_dir(__DIR__ . '/../../img/uploads/sistemas/')) {
                        mkdir(__DIR__ . '/../../img/uploads/sistemas/', 0777, true);
                    }

                    if (file_put_contents($caminho_salvamento, $data_img)) {
                        $imagem = '../img/uploads/sistemas/' . $nome_arquivo;
                    }
                }
            }
        }
    }

    $stmtSis = $pdo->prepare("INSERT INTO tb_sistema (nm_sistema, ds_descricao, tp_classificacao, ds_imagem, id_usuario_criador) VALUES (?, ?, ?, ?, ?)");
    $stmtSis->execute([$nome, $descricao, $classificacao, $imagem, $id_usuario]);
    $id_sistema = $pdo->lastInsertId();

    // Inserir Atributos e guardar os IDs
    $attrIdMap = [];
    if (!empty($data['atributos'])) {
        $stmtAttr = $pdo->prepare("INSERT INTO tb_atributo (nm_atributo, ds_abreviacao, id_sistema) VALUES (?, ?, ?)");
        foreach ($data['atributos'] as $attr) {
            $stmtAttr->execute([$attr['nome'], $attr['abrev'], $id_sistema]);
            $attrIdMap[$attr['id']] = $pdo->lastInsertId();
        }
    }

    // Inserir Classes
    if (!empty($data['classes'])) {
        $stmtCla = $pdo->prepare("INSERT INTO tb_classe (nm_classe, ds_descricao, ds_habilidade, id_sistema) VALUES (?, ?, ?, ?)");
        foreach ($data['classes'] as $cla) {
            $stmtCla->execute([$cla['nome'], $cla['val1'] ?? null, $cla['val2'] ?? null, $id_sistema]);
        }
    }

    // Inserir Perícias
    if (!empty($data['pericias'])) {
        $stmtPer = $pdo->prepare("INSERT INTO tb_pericia (nm_pericia, ds_descricao, ds_habilidade, ds_atributo_base, id_sistema) VALUES (?, ?, ?, ?, ?)");
        foreach ($data['pericias'] as $per) {
            $stmtPer->execute([$per['nome'], $per['val1'] ?? null, $per['val2'] ?? null, $per['val3'] ?? null, $id_sistema]);
        }
    }

    // Inserir Origens
    if (!empty($data['origens'])) {
        $stmtOri = $pdo->prepare("INSERT INTO tb_origem (nm_origem, ds_origem, ds_habilidade, id_sistema) VALUES (?, ?, ?, ?)");
        foreach ($data['origens'] as $ori) {
            $stmtOri->execute([$ori['nome'], $ori['val1'] ?? null, $ori['val2'] ?? null, $id_sistema]);
        }
    }

    // Inserir Equipamentos (Itens)
    if (!empty($data['equipamentos'])) {
        $stmtItem = $pdo->prepare("INSERT INTO tb_item (nm_item, ds_item, tp_item, id_sistema) VALUES (?, ?, ?, ?)");
        foreach ($data['equipamentos'] as $item) {
            $stmtItem->execute([$item['nome'], $item['val1'], $item['val2'] ?? 'outro', $id_sistema]);
        }
    }

    // Inserir Poderes (Habilidades)
    if (!empty($data['poderes'])) {
        $stmtHab = $pdo->prepare("INSERT INTO tb_habilidade (nm_habilidade, ds_habilidade, tp_habilidade, id_sistema) VALUES (?, ?, ?, ?)");
        foreach ($data['poderes'] as $hab) {
            $stmtHab->execute([$hab['nome'], $hab['val1'], $hab['val2'] ?? 'ativa', $id_sistema]);
        }
    }

    // Inserir Status (Barras)
    if (!empty($data['status'])) {
        $stmtStat = $pdo->prepare("INSERT INTO tb_sistema_status (nm_status, ds_cor, tp_status, id_sistema) VALUES (?, ?, 'barra', ?)");
        foreach ($data['status'] as $stat) {
            $stmtStat->execute([$stat['nome'], $stat['cor'], $id_sistema]);
        }
    }

    // Inserir Defesas (Escudos)
    if (!empty($data['defesas'])) {
        $stmtDef = $pdo->prepare("INSERT INTO tb_sistema_status (nm_status, ds_cor, tp_status, id_sistema) VALUES (?, ?, 'defesa', ?)");
        foreach ($data['defesas'] as $def) {
            $stmtDef->execute([$def['nome'], $def['cor'], $id_sistema]);
        }
    }

    // Inserir Monstros e Atributos de Monstros
    if (!empty($data['monstros'])) {
        $stmtMonstro = $pdo->prepare("INSERT INTO tb_monstro (nm_monstro, ds_monstro, tp_monstro, ds_imagem, qt_vida, qt_defesa, qt_xp_recompensa, qt_vd, id_sistema) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtMonAttr = $pdo->prepare("INSERT INTO tb_monstro_atributo (id_monstro, id_atributo, qt_valor) VALUES (?, ?, ?)");
        
        foreach ($data['monstros'] as $monstro) {
            $ds_imagem = '../img/uploads/perfil/avatar1.png'; // Padrão
            if (!empty($monstro['foto_base64'])) {
                $base64 = $monstro['foto_base64'];
                if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                    $data_img = substr($base64, strpos($base64, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, gif

                    if (in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'webp'])) {
                        $data_img = base64_decode($data_img);
                        if ($data_img !== false) {
                            $nome_arquivo = 'monstro_' . time() . '_' . uniqid() . '.' . $type;
                            $caminho_salvamento = __DIR__ . '/../../img/uploads/perfil/' . $nome_arquivo;
                            
                            // Garante que o diretório existe
                            if (!is_dir(__DIR__ . '/../../img/uploads/perfil/')) {
                                mkdir(__DIR__ . '/../../img/uploads/perfil/', 0777, true);
                            }

                            if (file_put_contents($caminho_salvamento, $data_img)) {
                                $ds_imagem = '../img/uploads/perfil/' . $nome_arquivo;
                            }
                        }
                    }
                }
            }
            if (empty($ds_imagem) || $ds_imagem === '../img/logo_icone.png' || $ds_imagem === 'undefined') {
                $ds_imagem = '../img/uploads/perfil/avatar1.png';
            }

            $stmtMonstro->execute([
                $monstro['nome'], 
                $monstro['desc'] ?? '', 
                $monstro['val1'] ?? 'Criatura', // tipo/elemento vem de val1 no js
                $ds_imagem, 
                $monstro['vida'] ?? 0, 
                $monstro['defesa'] ?? 0, 
                $monstro['xp'] ?? 0, 
                $monstro['val2'] ?? 0, // VD vem de val2 no js
                $id_sistema
            ]);
            $id_monstro = $pdo->lastInsertId();
            
            if (!empty($monstro['atributos_monstro'])) {
                foreach ($monstro['atributos_monstro'] as $mAttr) {
                    $realAttrId = $attrIdMap[$mAttr['id_atributo_temp']] ?? null;
                    if ($realAttrId) {
                        $stmtMonAttr->execute([$id_monstro, $realAttrId, $mAttr['valor']]);
                    }
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'id_sistema' => $id_sistema]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

