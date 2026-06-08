<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id_campanha = $data['id_campanha'] ?? null;
$nome = $data['nome'] ?? 'Novo Combate';
$monstros = $data['monstros'] ?? []; // Array de IDs
$id_combate = $data['id_combate'] ?? null;

if (!$id_campanha) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

try {
    $pdo = Database::getConexao();
    $pdo->beginTransaction();

    if ($id_combate) {
        // 1. Atualizar Combate existente
        $stmt = $pdo->prepare("UPDATE tb_combate SET nm_combate = ? WHERE id_combate = ?");
        $stmt->execute([$nome, $id_combate]);

        // 2. Limpar monstros anteriores
        $stmtDel = $pdo->prepare("DELETE FROM tb_combate_monstro WHERE id_combate = ?");
        $stmtDel->execute([$id_combate]);
    } else {
        // 1. Inserir Novo Combate
        $stmt = $pdo->prepare("INSERT INTO tb_combate (id_campanha, nm_combate) VALUES (?, ?)");
        $stmt->execute([$id_campanha, $nome]);
        $id_combate = $pdo->lastInsertId();
    }

    // 3. Inserir Monstros selecionados
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

