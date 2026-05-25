<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['cargo'] !== 'mestre' && $_SESSION['usuario']['cargo'] !== 'admin')) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas mestres ou admins podem editar sistemas.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['nome']) || empty($data['id_sistema'])) {
    echo json_encode(['success' => false, 'error' => 'Payload inválido ou ID do sistema ausente.']);
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
    $id_sistema = $data['id_sistema'];
    $nome = $data['nome'];
    $classificacao = $data['classificacao'] ?? 'L';
    $descricao = $data['descricao'] ?? '';

    // Processamento da Imagem (Base64) se enviada
    if (!empty($data['imagem_base64'])) {
        $base64 = $data['imagem_base64'];
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            $data_img = substr($base64, strpos($base64, ',') + 1);
            $type = strtolower($type[1]);

            if (in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'webp'])) {
                $data_img = base64_decode($data_img);
                if ($data_img !== false) {
                    $nome_arquivo = 'sistema_' . time() . '_' . uniqid() . '.' . $type;
                    $caminho_salvamento = __DIR__ . '/../../img/uploads/sistemas/' . $nome_arquivo;
                    
                    if (!is_dir(__DIR__ . '/../../img/uploads/sistemas/')) {
                        mkdir(__DIR__ . '/../../img/uploads/sistemas/', 0777, true);
                    }

                    if (file_put_contents($caminho_salvamento, $data_img)) {
                        $imagem = '../img/uploads/sistemas/' . $nome_arquivo;
                        $stmtImg = $pdo->prepare("UPDATE tb_sistema SET ds_imagem=? WHERE id_sistema=?");
                        $stmtImg->execute([$imagem, $id_sistema]);
                    }
                }
            }
        }
    }

    // 1. Atualizar Sistema (garantindo que pertence ao usuário)
    $stmtSis = $pdo->prepare("UPDATE tb_sistema SET nm_sistema=?, ds_descricao=?, tp_classificacao=? WHERE id_sistema=? AND id_usuario_criador=?");
    $stmtSis->execute([$nome, $descricao, $classificacao, $id_sistema, $id_usuario]);

    if ($stmtSis->rowCount() === 0) {
        // Pode ser porque nada mudou, mas vamos checar se o sistema existe para este usuário
        $check = $pdo->prepare("SELECT 1 FROM tb_sistema WHERE id_sistema=? AND id_usuario_criador=?");
        $check->execute([$id_sistema, $id_usuario]);
        if (!$check->fetch()) {
            throw new Exception("Sistema não encontrado ou você não tem permissão para editá-lo.");
        }
    }

    // Função de Sincronização Dinâmica
    // Identifica o que atualizar, inserir e tentar deletar
    function syncComponent($pdo, $tabela, $colId, $colNome, $colExtra, $idSistema, $itensPayload, $colValor = null) {
        $stmtAtual = $pdo->prepare("SELECT $colId FROM $tabela WHERE id_sistema = ?");
        $stmtAtual->execute([$idSistema]);
        $dbIds = $stmtAtual->fetchAll(PDO::FETCH_COLUMN);

        $payloadIds = [];

        foreach ($itensPayload as $item) {
            $itemId = $item['id'];
            if (is_numeric($itemId)) {
                // UPDATE existente
                $payloadIds[] = $itemId;
                
                $sql = "UPDATE $tabela SET $colNome=?, $colExtra=?";
                $params = [$item['nome'], $item['val1'] ?? $item['abrev']];
                
                if ($colValor) {
                    $valToUse = $item['valor'] ?? $item['val2'] ?? null;
                    if ($valToUse !== null) {
                        $sql .= ", $colValor=?";
                        $params[] = $valToUse;
                    }
                }
                
                $sql .= " WHERE $colId=? AND id_sistema=?";
                $params[] = $itemId;
                $params[] = $idSistema;
                
                $stmtUpdate = $pdo->prepare($sql);
                $stmtUpdate->execute($params);
            } else {
                // INSERT novo
                $sql = "INSERT INTO $tabela ($colNome, $colExtra, id_sistema";
                $placeholders = "?, ?, ?";
                $params = [$item['nome'], $item['val1'] ?? $item['abrev'], $idSistema];
                
                if ($colValor) {
                    $valToUse = $item['valor'] ?? $item['val2'] ?? null;
                    if ($valToUse !== null) {
                        $sql .= ", $colValor";
                        $placeholders .= ", ?";
                        $params[] = $valToUse;
                    }
                }
                
                $sql .= ") VALUES ($placeholders)";
                $stmtInsert = $pdo->prepare($sql);
                $stmtInsert->execute($params);
            }
        }

        // Deletar os que não vieram no payload
        $toDelete = array_diff($dbIds, $payloadIds);
        foreach ($toDelete as $delId) {
            try {
                $stmtDel = $pdo->prepare("DELETE FROM $tabela WHERE $colId=? AND id_sistema=?");
                $stmtDel->execute([$delId, $idSistema]);
            } catch (PDOException $e) {
                // Ignorar se houver falha de Foreign Key (o item está em uso por personagens, logo não pode sumir para sempre)
                continue;
            }
        }
    }

    // Sincronizar Atributos e guardar os IDs
    $attrIdMap = [];
    if (isset($data['atributos'])) {
        // Precisamos coletar os IDs reais mesmo dos que já existem para os monstros
        syncComponent($pdo, 'tb_atributo', 'id_atributo', 'nm_atributo', 'ds_abreviacao', $id_sistema, $data['atributos'], 'qt_valor_minimo');
        
        // Agora buscamos o mapa completo [NM_ATRIBUTO ou DS_ABREV] -> ID real para vincular os monstros
        // Nota: Como o payload de atributos envia o ID original (numérico) ou temporário (_), vamos usar isso.
        $stmtMap = $pdo->prepare("SELECT id_atributo, ds_abreviacao FROM tb_atributo WHERE id_sistema = ?");
        $stmtMap->execute([$id_sistema]);
        $rows = $stmtMap->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            // Mapeamos pela abreviação para facilitar o vínculo com o payload
            $attrIdMap[$r['ds_abreviacao']] = $r['id_atributo'];
        }
    }

    // Sincronizar Classes
    if (isset($data['classes'])) {
        syncComponent($pdo, 'tb_classe', 'id_classe', 'nm_classe', 'ds_descricao', $id_sistema, $data['classes'], 'ds_habilidade');
    }

    // Sincronizar Perícias
    if (isset($data['pericias'])) {
        $stmtAtual = $pdo->prepare("SELECT id_pericia FROM tb_pericia WHERE id_sistema = ?");
        $stmtAtual->execute([$id_sistema]);
        $dbIds = $stmtAtual->fetchAll(PDO::FETCH_COLUMN);
        $payloadIds = [];

        foreach ($data['pericias'] as $item) {
            $itemId = $item['id'];
            if (is_numeric($itemId)) {
                $payloadIds[] = $itemId;
                $stmtUp = $pdo->prepare("UPDATE tb_pericia SET nm_pericia=?, ds_descricao=?, ds_habilidade=?, ds_atributo_base=? WHERE id_pericia=? AND id_sistema=?");
                $stmtUp->execute([$item['nome'], $item['val1'] ?? null, $item['val2'] ?? null, $item['val3'] ?? null, $itemId, $id_sistema]);
            } else {
                $stmtIn = $pdo->prepare("INSERT INTO tb_pericia (nm_pericia, ds_descricao, ds_habilidade, ds_atributo_base, id_sistema) VALUES (?, ?, ?, ?, ?)");
                $stmtIn->execute([$item['nome'], $item['val1'] ?? null, $item['val2'] ?? null, $item['val3'] ?? null, $id_sistema]);
            }
        }

        $toDelete = array_diff($dbIds, $payloadIds);
        foreach ($toDelete as $delId) {
            try {
                $pdo->prepare("DELETE FROM tb_pericia WHERE id_pericia=? AND id_sistema=?")->execute([$delId, $id_sistema]);
            } catch (PDOException $e) { continue; }
        }
    }

    // Sincronizar Origens
    if (isset($data['origens'])) {
        syncComponent($pdo, 'tb_origem', 'id_origem', 'nm_origem', 'ds_origem', $id_sistema, $data['origens'], 'ds_habilidade');
    }
    
    // Sincronizar Equipamentos (Itens)
    if (isset($data['equipamentos'])) {
        syncComponent($pdo, 'tb_item', 'id_item', 'nm_item', 'ds_item', $id_sistema, $data['equipamentos'], 'tp_item');
    }

    // Sincronizar Poderes (Habilidades)
    if (isset($data['poderes'])) {
        syncComponent($pdo, 'tb_habilidade', 'id_habilidade', 'nm_habilidade', 'ds_habilidade', $id_sistema, $data['poderes'], 'tp_habilidade');
    }

    // Sincronizar Status e Defesas (Inteligente)
    if (isset($data['status']) || isset($data['defesas'])) {
        $todosStatusPayload = array_merge(
            array_map(function($s) { $s['tp'] = 'barra'; return $s; }, $data['status'] ?? []),
            array_map(function($d) { $d['tp'] = 'defesa'; return $d; }, $data['defesas'] ?? [])
        );

        $stmtAtual = $pdo->prepare("SELECT id_status_sistema FROM tb_sistema_status WHERE id_sistema = ?");
        $stmtAtual->execute([$id_sistema]);
        $dbIds = $stmtAtual->fetchAll(PDO::FETCH_COLUMN);
        $payloadIds = [];

        foreach ($todosStatusPayload as $item) {
            $itemId = $item['id'];
            if (is_numeric($itemId)) {
                $payloadIds[] = $itemId;
                $stmtUp = $pdo->prepare("UPDATE tb_sistema_status SET nm_status=?, ds_cor=?, tp_status=? WHERE id_status_sistema=? AND id_sistema=?");
                $stmtUp->execute([$item['nome'], $item['cor'], $item['tp'], $itemId, $id_sistema]);
            } else {
                $stmtIn = $pdo->prepare("INSERT INTO tb_sistema_status (nm_status, ds_cor, tp_status, id_sistema) VALUES (?, ?, ?, ?)");
                $stmtIn->execute([$item['nome'], $item['cor'], $item['tp'], $id_sistema]);
            }
        }

        // Deletar órfãos com segurança
        $toDelete = array_diff($dbIds, $payloadIds);
        foreach ($toDelete as $delId) {
            try {
                $pdo->prepare("DELETE FROM tb_sistema_status WHERE id_status_sistema=? AND id_sistema=?")->execute([$delId, $id_sistema]);
            } catch (PDOException $e) {
                continue; // Ignora se estiver em uso
            }
        }
    }

    // Inserir NOVOS Monstros e Atributos de Monstros
    // Monstros existentes são editados via Dashboard, aqui permitimos adicionar novos em lote sem perder o progresso da página
    if (!empty($data['monstros'])) {
        $stmtMonstro = $pdo->prepare("INSERT INTO tb_monstro (nm_monstro, ds_monstro, tp_monstro, ds_imagem, qt_vida, qt_defesa, qt_xp_recompensa, qt_vd, id_sistema) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtMonAttr = $pdo->prepare("INSERT INTO tb_monstro_atributo (id_monstro, id_atributo, qt_valor) VALUES (?, ?, ?)");
        
        foreach ($data['monstros'] as $monstro) {
            // Só processamos os novos (ID não numérico)
            if (is_numeric($monstro['id'])) continue;

            $stmtMonstro->execute([
                $monstro['nome'], 
                $monstro['desc'] ?? '', 
                $monstro['val1'] ?? 'Criatura', 
                '../img/logo_icone.png', 
                $monstro['vida'] ?? 0, 
                $monstro['defesa'] ?? 0, 
                $monstro['xp'] ?? 0, 
                $monstro['val2'] ?? 0, 
                $id_sistema
            ]);
            $id_monstro = $pdo->lastInsertId();
            
            if (!empty($monstro['atributos_monstro'])) {
                foreach ($monstro['atributos_monstro'] as $mAttr) {
                    $realAttrId = $attrIdMap[$mAttr['abrev']] ?? null;
                    if ($realAttrId) {
                        $stmtMonAttr->execute([$id_monstro, $realAttrId, $mAttr['valor']]);
                    }
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
