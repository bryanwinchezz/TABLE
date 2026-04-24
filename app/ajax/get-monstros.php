<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$id_sistema = $_GET['id_sistema'] ?? null;

try {
    $pdo = Database::getConexao();

    $sql = "SELECT id_monstro, nm_monstro, ds_monstro, tp_monstro, qt_vida, qt_defesa, ds_imagem, qt_vd FROM tb_monstro";
    if ($id_sistema) {
        $sql .= " WHERE id_sistema = ?";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($id_sistema ? [$id_sistema] : []);
    $monstros = $stmt->fetchAll();

    echo json_encode(['success' => true, 'monstros' => $monstros]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
