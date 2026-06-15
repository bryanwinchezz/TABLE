<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

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
        $id_sistema = $_POST['id_sistema'] ?? 1;
        $nm_personagem = $_POST['nome'] ?? 'Sem Nome';
        $nm_jogador = $_POST['nome-jogador'] ?? $_SESSION['usuario']['nome'];
        
        $ds_aparencia = $_POST['aparencia'] ?? '';
        $ds_personalidade = $_POST['personalidade'] ?? '';
        $ds_historia = $_POST['historia'] ?? '';
        $ds_objetivos = $_POST['objetivos'] ?? '';
        
        $ds_caracteristicas = "Reduzido: " . substr($ds_aparencia, 0, 100);
        
        $classe_nome = $_POST['classeEscolhida'] ?? '';
        $origem_nome = $_POST['origemEscolhida'] ?? '';

        // Obter nome do sistema selecionado
        $stmtSis = $pdo->prepare("SELECT nm_sistema FROM tb_sistema WHERE id_sistema = ?");
        $stmtSis->execute([$id_sistema]);
        $nomeSistema = $stmtSis->fetchColumn();

        $inicialPV = 10;
        $inicialSAN = 10;
        $inicialPE = 10;
        $agiVal = 0;
        $vigVal = 0;
        $preVal = 0;

        if ($nomeSistema && strpos(strtolower($nomeSistema), 'ordem paranormal') !== false) {
            // Localizar valores de Vigor, Presença e Agilidade nos parâmetros do POST
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'attr_') === 0) {
                    $nomeAttr = str_replace('attr_', '', $key);
                    $nomeAttrLower = strtolower($nomeAttr);
                    if ($nomeAttrLower === 'vigor' || $nomeAttrLower === 'vig') {
                        $vigVal = (int)$value;
                    } elseif ($nomeAttrLower === 'presença' || $nomeAttrLower === 'pre') {
                        $preVal = (int)$value;
                    } elseif ($nomeAttrLower === 'agilidade' || $nomeAttrLower === 'agi') {
                        $agiVal = (int)$value;
                    }
                }
            }

            // Cálculos para Nível 1 (NEX 5%)
            $classe_lower = strtolower($classe_nome);
            if (strpos($classe_lower, 'combatente') !== false) {
                $inicialPV = 20 + $vigVal;
                $inicialSAN = 12;
                $inicialPE = 2 + $preVal;
            } elseif (strpos($classe_lower, 'especialista') !== false) {
                $inicialPV = 16 + $vigVal;
                $inicialSAN = 16;
                $inicialPE = 3 + $preVal;
            } elseif (strpos($classe_lower, 'ocultista') !== false) {
                $inicialPV = 12 + $vigVal;
                $inicialSAN = 20;
                $inicialPE = 4 + $preVal;
            } else {
                // Mundano (se classe vazia ou Mundano)
                $inicialPV = 8 + $vigVal;
                $inicialSAN = 8;
                $inicialPE = 1;
            }
        }

        $ds_foto = '../img/uploads/perfil/avatar1.png'; // Padrão
        
        if (!empty($_POST['imagem_base64'])) {
            $base64 = $_POST['imagem_base64'];
            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                $data_img = substr($base64, strpos($base64, ',') + 1);
                $type = strtolower($type[1]);

                if (in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'webp'])) {
                    $data_img = base64_decode($data_img);
                    if ($data_img !== false) {
                        $newFileName = 'personagem_' . time() . '_' . uniqid() . '.' . $type;
                        $uploadFileDir = dirname(__DIR__, 2) . '/img/uploads/personagens/';
                        
                        if (!is_dir($uploadFileDir)) {
                            mkdir($uploadFileDir, 0777, true);
                        }

                        $dest_path = $uploadFileDir . $newFileName;
                        if (file_put_contents($dest_path, $data_img)) {
                            $ds_foto = '../img/uploads/personagens/' . $newFileName;
                        }
                    }
                }
            }
        } elseif (isset($_FILES['foto_personagem']) && $_FILES['foto_personagem']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto_personagem']['tmp_name'];
            $fileName = $_FILES['foto_personagem']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $newFileName = 'pers_' . time() . '_' . uniqid() . '.' . $fileExtension;
                $uploadFileDir = dirname(__DIR__, 2) . '/img/uploads/personagens/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $ds_foto = '../img/uploads/personagens/' . $newFileName;
                }
            }
        }

        // 1. Inserir Personagem principal
        $stmt = $pdo->prepare("
            INSERT INTO tb_personagem 
            (id_usuario, id_sistema, nm_personagem, ds_aparencia, ds_personalidade, ds_historia, ds_objetivos, ds_caracteristicas, qt_nivel,
             qt_vida, qt_vida_maxima, qt_sanidade, qt_sanidade_maxima, qt_esforco, qt_esforco_maximo, qt_defesa, qt_esquiva, ds_foto) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $defesa_inicial = 10 + $agiVal;
        $esquiva_inicial = $defesa_inicial + $agiVal;
        $stmt->execute([
            $id_usuario, $id_sistema, $nm_personagem, 
            $ds_aparencia, $ds_personalidade, $ds_historia, $ds_objetivos, $ds_caracteristicas,
            $inicialPV, $inicialPV, $inicialSAN, $inicialSAN, $inicialPE, $inicialPE, $defesa_inicial, $esquiva_inicial,
            $ds_foto
        ]);
        $id_personagem = $pdo->lastInsertId();

        // 2. Inserir Atributos Dinâmicos
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'attr_') === 0) {
                $nomeAttr = str_replace('attr_', '', $key);
                $stmt = $pdo->prepare("SELECT id_atributo FROM tb_atributo WHERE nm_atributo = ? AND id_sistema = ?");
                $stmt->execute([$nomeAttr, $id_sistema]);
                $id_attr = $stmt->fetchColumn();
                
                if ($id_attr) {
                    $stmt = $pdo->prepare("INSERT INTO tb_personagem_atributo (id_personagem, id_atributo, qt_valor) VALUES (?, ?, ?)");
                    $stmt->execute([$id_personagem, $id_attr, (int)$value]);
                }
            }
        }

        // 3. Inserir Classe
        if ($classe_nome) {
            $stmt = $pdo->prepare("SELECT id_classe FROM tb_classe WHERE nm_classe = ? AND id_sistema = ?");
            $stmt->execute([$classe_nome, $id_sistema]);
            $id_classe = $stmt->fetchColumn();
            if ($id_classe) {
                $stmt = $pdo->prepare("INSERT INTO tb_personagem_classe (id_personagem, id_classe) VALUES (?, ?)");
                $stmt->execute([$id_personagem, $id_classe]);
            }
        }

        // 4. Inserir Origem
        if ($origem_nome) {
            $stmt = $pdo->prepare("SELECT id_origem FROM tb_origem WHERE nm_origem = ? AND id_sistema = ?");
            $stmt->execute([$origem_nome, $id_sistema]);
            $id_origem = $stmt->fetchColumn();
            if ($id_origem) {
                $stmt = $pdo->prepare("INSERT INTO tb_personagem_origem (id_personagem, id_origem) VALUES (?, ?)");
                $stmt->execute([$id_personagem, $id_origem]);
            }
        }

        // 5. Inserir Perícias do Sistema
        $stmt = $pdo->prepare("SELECT id_pericia FROM tb_pericia WHERE id_sistema = ?");
        $stmt->execute([$id_sistema]);
        $periciasIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($periciasIds as $id_p) {
            $stmt = $pdo->prepare("INSERT INTO tb_personagem_pericia (id_personagem, id_pericia, qt_valor) VALUES (?, ?, 0)");
            $stmt->execute([$id_personagem, $id_p]);
        }

        // 6. Inicializar Barras de Status do Sistema
        $stmt = $pdo->prepare("SELECT id_status_sistema, nm_status, tp_status FROM tb_sistema_status WHERE id_sistema = ?");
        $stmt->execute([$id_sistema]);
        $statusRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($statusRows as $statusRow) {
            $id_s = $statusRow['id_status_sistema'];
            $nm_s = strtolower($statusRow['nm_status']);
            $tp_s = $statusRow['tp_status'];
            
            $val = 100;
            if ($tp_s === 'barra') {
                if (strpos($nm_s, 'vida') !== false) {
                    $val = $inicialPV;
                } elseif (strpos($nm_s, 'sanidade') !== false || strpos($nm_s, 'mental') !== false) {
                    $val = $inicialSAN;
                } elseif (strpos($nm_s, 'esforço') !== false || strpos($nm_s, 'pe') !== false) {
                    $val = $inicialPE;
                }
                $stmt = $pdo->prepare("INSERT INTO tb_personagem_status (id_personagem, id_status_sistema, qt_valor_atual, qt_valor_maximo) VALUES (?, ?, ?, ?)");
                $stmt->execute([$id_personagem, $id_s, $val, $val]);
            } else {
                // Defesa
                $val = 10 + $agiVal;
                $stmt = $pdo->prepare("INSERT INTO tb_personagem_status (id_personagem, id_status_sistema, qt_valor_atual) VALUES (?, ?, ?)");
                $stmt->execute([$id_personagem, $id_s, $val]);
            }
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


