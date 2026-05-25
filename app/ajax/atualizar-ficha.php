<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id_personagem = $data['id_personagem'] ?? null;
$tipo = $data['tipo'] ?? null; // 'stat' ou 'atributo'
$campo = $data['campo'] ?? null;
$valor = $data['valor'] ?? null;

if (!$id_personagem || !$campo) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

try {
    $pdo = Database::getConexao();

    if ($tipo === 'stat') {
        $campos_permitidos = [
            'VIDA' => 'qt_vida',
            'SANIDADE' => 'qt_sanidade',
            'ESFORÇO' => 'qt_esforco'
        ];
        $campo_upper = strtoupper($campo);
        if (isset($campos_permitidos[$campo_upper])) {
            $coluna = $campos_permitidos[$campo_upper];
            $stmt = $pdo->prepare("UPDATE tb_personagem SET $coluna = ? WHERE id_personagem = ?");
            $stmt->execute([$valor, $id_personagem]);
        }
    } elseif ($tipo === 'stat_max') {
        $campos_max = [
            'VIDA' => 'qt_vida_maxima',
            'SANIDADE' => 'qt_sanidade_maxima',
            'ESFORÇO' => 'qt_esforco_maximo'
        ];
        $campo_upper = strtoupper($campo);
        if (isset($campos_max[$campo_upper])) {
            $coluna = $campos_max[$campo_upper];
            $stmt = $pdo->prepare("UPDATE tb_personagem SET $coluna = ? WHERE id_personagem = ?");
            $stmt->execute([$valor, $id_personagem]);
        }
    } elseif ($tipo === 'status_custom') {
        $stmt = $pdo->prepare("
            INSERT INTO tb_personagem_status (id_personagem, id_status_sistema, qt_valor_atual)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE qt_valor_atual = VALUES(qt_valor_atual)
        ");
        $stmt->execute([$id_personagem, $campo, $valor]);
    } elseif ($tipo === 'status_custom_max') {
        $stmt = $pdo->prepare("
            INSERT INTO tb_personagem_status (id_personagem, id_status_sistema, qt_valor_maximo)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE qt_valor_maximo = VALUES(qt_valor_maximo)
        ");
        $stmt->execute([$id_personagem, $campo, $valor]);
    } elseif ($tipo === 'defesa') {
        $stmt = $pdo->prepare("UPDATE tb_personagem SET qt_defesa = ? WHERE id_personagem = ?");
        $stmt->execute([$valor, $id_personagem]);
    } elseif ($tipo === 'atributo') {
        // Busca o ID real do atributo baseado no nome para evitar erros de mapeamento
        $stmt = $pdo->prepare("SELECT id_atributo FROM tb_atributo a JOIN tb_personagem p ON a.id_sistema = p.id_sistema WHERE a.nm_atributo = ? AND p.id_personagem = ?");
        $stmt->execute([$campo, $id_personagem]);
        $id_attr = $stmt->fetchColumn();
        
        if ($id_attr) {
            $stmt = $pdo->prepare("
                INSERT INTO tb_personagem_atributo (id_personagem, id_atributo, qt_valor)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE qt_valor = VALUES(qt_valor)
            ");
            $stmt->execute([$id_personagem, $id_attr, $valor]);
        }
    } elseif ($tipo === 'descricao') {
        // Mapeamento de campos de texto permitidos
        $campos_desc = [
            'aparencia' => 'ds_aparencia',
            'personalidade' => 'ds_personalidade',
            'historia' => 'ds_historia',
            'objetivos' => 'ds_objetivos'
        ];

        if (!isset($campos_desc[$campo])) {
            throw new Exception("Campo de descrição inválido");
        }

        $coluna = $campos_desc[$campo];
        $stmt = $pdo->prepare("UPDATE tb_personagem SET $coluna = ? WHERE id_personagem = ?");
        $stmt->execute([$valor, $id_personagem]);
    } elseif ($tipo === 'personagem_campo') {
        $campos_permitidos = ['ds_protecao', 'ds_resistencias', 'ds_proficiencias', 'qt_nivel'];
        if (in_array($campo, $campos_permitidos)) {
            $stmt = $pdo->prepare("UPDATE tb_personagem SET $campo = ? WHERE id_personagem = ?");
            $stmt->execute([$valor, $id_personagem]);
        }
    } elseif ($tipo === 'defesa_calc') {
        $campos_permitidos = ['qt_defesa_equip', 'qt_defesa_outros', 'qt_bloqueio', 'qt_esquiva'];
        if (in_array($campo, $campos_permitidos)) {
            $stmt = $pdo->prepare("UPDATE tb_personagem SET $campo = ? WHERE id_personagem = ?");
            $stmt->execute([$valor, $id_personagem]);
        }
    } elseif ($tipo === 'pericia_treino') {
        $id_pericia = $campo;
        $stmt = $pdo->prepare("
            INSERT INTO tb_personagem_pericia (id_personagem, id_pericia, fl_treinado)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE fl_treinado = VALUES(fl_treinado)
        ");
        $stmt->execute([$id_personagem, $id_pericia, $valor]);
    } elseif ($tipo === 'pericia_val') {
        list($id_pericia, $coluna) = explode('|', $campo);
        if ($coluna === 'qt_valor' || $coluna === 'qt_outros') {
            $stmt = $pdo->prepare("
                INSERT INTO tb_personagem_pericia (id_personagem, id_pericia, $coluna)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE $coluna = ?
            ");
            $stmt->execute([$id_personagem, $id_pericia, $valor, $valor]);
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
