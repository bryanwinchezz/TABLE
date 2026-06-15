<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id_combate = $data['id_combate'] ?? null;

if (!$id_combate) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

try {
    $pdo = Database::getConexao();
    $pdo->beginTransaction();

    // 1. Remover monstros do combate
    $stmt = $pdo->prepare("DELETE FROM tb_combate_monstro WHERE id_combate = ?");
    $stmt->execute([$id_combate]);

    // 2. Remover o combate
    $stmt = $pdo->prepare("DELETE FROM tb_combate WHERE id_combate = ?");
    $stmt->execute([$id_combate]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

