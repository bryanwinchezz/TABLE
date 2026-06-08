<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

try {
    $pdo = Database::getConexao();

    // Buscar sistemas com filtro de visibilidade (Próprios + Admin + Oficiais)
    $stmt = $pdo->prepare("
        SELECT s.id_sistema, s.nm_sistema, s.ds_descricao, s.ds_imagem, s.ds_background, s.tp_classificacao 
        FROM tb_sistema s
        LEFT JOIN tb_usuario u ON s.id_usuario_criador = u.id_usuario
        WHERE s.id_usuario_criador = ? OR u.tp_cargo = 'admin' OR s.id_usuario_criador IS NULL
        ORDER BY s.nm_sistema ASC
    ");
    $stmt->execute([$_SESSION['usuario']['id']]);
    $sistemas = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'sistemas' => $sistemas
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

