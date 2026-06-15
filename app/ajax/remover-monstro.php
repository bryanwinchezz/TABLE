<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID não informado']);
    exit;
}

try {
    $pdo = Database::getConexao();
    
    // Verificar se o usuário é o dono do sistema do monstro
    $stmtCheck = $pdo->prepare("
        SELECT s.id_usuario_criador 
        FROM tb_monstro m 
        JOIN tb_sistema s ON m.id_sistema = s.id_sistema 
        WHERE m.id_monstro = ?
    ");
    $stmtCheck->execute([$id]);
    $sistema = $stmtCheck->fetch();

    $isAdmin = isset($_SESSION['usuario']['cargo']) && strtolower($_SESSION['usuario']['cargo']) === 'admin';
    if (!$sistema || ($sistema['id_usuario_criador'] != $_SESSION['usuario']['id'] && !$isAdmin)) {
        echo json_encode(['success' => false, 'error' => 'Permissão negada']);
        exit;
    }

    // Remover atributos do monstro primeiro (se houver foreign keys)
    $pdo->prepare("DELETE FROM tb_monstro_atributo WHERE id_monstro = ?")->execute([$id]);
    
    // Remover o monstro
    $stmt = $pdo->prepare("DELETE FROM tb_monstro WHERE id_monstro = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

