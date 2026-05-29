<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$nome = $data['nome'] ?? '';
$descricao = $data['descricao'] ?? '';
$id_sistema = $data['id_sistema'] ?? null;
$id_usuario = $_SESSION['usuario']['id'];

$id_campanha = $data['id_campanha'] ?? null;

if (!$nome || !$id_sistema) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

try {
    $pdo = Database::getConexao();

    if ($id_campanha) {
        $stmt = $pdo->prepare("
            UPDATE tb_campanha 
            SET nm_campanha = ?, ds_descricao = ?, id_sistema = ?
            WHERE id_campanha = ? AND id_usuario_mestre = ?
        ");
        $stmt->execute([$nome, $descricao, $id_sistema, $id_campanha, $id_usuario]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO tb_campanha (nm_campanha, ds_descricao, id_sistema, id_usuario_mestre)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$nome, $descricao, $id_sistema, $id_usuario]);
        $id_campanha = $pdo->lastInsertId();

        // Adicionar o mestre na tabela de participantes automaticamente
        $stmt = $pdo->prepare("
            INSERT INTO tb_campanha_usuario (id_campanha, id_usuario, tp_papel)
            VALUES (?, ?, 'mestre')
        ");
        $stmt->execute([$id_campanha, $id_usuario]);
    }

    echo json_encode(['success' => true, 'id_campanha' => $id_campanha]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

