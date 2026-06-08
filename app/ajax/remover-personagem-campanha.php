<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id_campanha = $data['id_campanha'] ?? null;
$id_personagem = $data['id_personagem'] ?? null;

if (!$id_campanha || !$id_personagem) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

try {
    $pdo = Database::getConexao();
    $stmt = $pdo->prepare("DELETE FROM tb_campanha_personagem WHERE id_campanha = ? AND id_personagem = ?");
    $stmt->execute([$id_campanha, $id_personagem]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

