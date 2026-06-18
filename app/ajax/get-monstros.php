<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

$id_sistema = $_GET['id_sistema'] ?? null;

try {
    $pdo = Database::getConexao();

    $sql = "SELECT m.id_monstro, m.nm_monstro, m.ds_monstro, m.tp_monstro, m.qt_vida, m.qt_defesa, m.ds_imagem, m.qt_vd, m.id_sistema, s.ds_imagem AS ds_imagem_sistema 
            FROM tb_monstro m
            LEFT JOIN tb_sistema s ON m.id_sistema = s.id_sistema";
    if ($id_sistema) {
        $sql .= " WHERE m.id_sistema = ?";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($id_sistema ? [$id_sistema] : []);
    $monstros = $stmt->fetchAll();

    echo json_encode(['success' => true, 'monstros' => $monstros]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

