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
        // Mapeamento de campos permitidos para segurança
        $campos_permitidos = [
            'VIDA' => 'qt_vida',
            'SANIDADE' => 'qt_sanidade',
            'ESFORÇO' => 'qt_esforco'
        ];

        $campo_upper = strtoupper($campo);
        if (!isset($campos_permitidos[$campo_upper])) {
            throw new Exception("Campo inválido: " . $campo_upper);
        }

        $coluna = $campos_permitidos[$campo_upper];
        $stmt = $pdo->prepare("UPDATE tb_personagem SET $coluna = ? WHERE id_personagem = ?");
        $stmt->execute([$valor, $id_personagem]);

    } elseif ($tipo === 'status_custom') {
        // Salva na tabela pivot de status customizados
        $stmt = $pdo->prepare("
            INSERT INTO tb_personagem_status (id_personagem, id_status_sistema, qt_valor_atual)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE qt_valor_atual = VALUES(qt_valor_atual)
        ");
        $stmt->execute([$id_personagem, $campo, $valor]);

    } elseif ($tipo === 'atributo') {
        // $campo aqui é o id_atributo
        $stmt = $pdo->prepare("
            INSERT INTO tb_personagem_atributo (id_personagem, id_atributo, qt_valor)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE qt_valor = VALUES(qt_valor)
        ");
        $stmt->execute([$id_personagem, $campo, $valor]);

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
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
