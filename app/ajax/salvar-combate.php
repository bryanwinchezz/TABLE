<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id_campanha = $data['id_campanha'] ?? null;
$nome = $data['nome'] ?? 'Novo Combate';
$monstros = $data['monstros'] ?? []; // Array de IDs

if (!$id_campanha) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

try {
    $pdo = Database::getConexao();
    $pdo->beginTransaction();

    // 1. Inserir Combate
    $stmt = $pdo->prepare("INSERT INTO tb_combate (id_campanha, nm_combate) VALUES (?, ?)");
    $stmt->execute([$id_campanha, $nome]);
    $id_combate = $pdo->lastInsertId();

    // 2. Inserir Monstros
    $stmtMonstro = $pdo->prepare("INSERT INTO tb_combate_monstro (id_combate, id_monstro, qt_quantidade) VALUES (?, ?, 1)");
    foreach ($monstros as $id_monstro) {
        $stmtMonstro->execute([$id_combate, $id_monstro]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'id_combate' => $id_combate]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
