<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

try {
    $pdo = Database::getConexao();

    // Buscar todos os sistemas cadastrados
    $stmt = $pdo->prepare("SELECT id_sistema, nm_sistema, ds_descricao, ds_imagem, tp_classificacao FROM tb_sistema ORDER BY nm_sistema ASC");
    $stmt->execute();
    $sistemas = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'sistemas' => $sistemas
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
