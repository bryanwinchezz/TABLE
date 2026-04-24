<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$id_campanha = $_GET['id_campanha'] ?? null;

try {
    $pdo = Database::getConexao();

    // Buscar personagens do usuário que ainda não estão nessa campanha
    $sql = "
        SELECT p.id_personagem, p.nm_personagem, p.ds_foto, s.nm_sistema, c.nm_classe
        FROM tb_personagem p
        LEFT JOIN tb_sistema s ON p.id_sistema = s.id_sistema
        LEFT JOIN tb_personagem_classe pc ON p.id_personagem = pc.id_personagem
        LEFT JOIN tb_classe c ON pc.id_classe = c.id_classe
        WHERE p.id_usuario = ? AND p.fl_ativo = 1
    ";

    if ($id_campanha) {
        $sql .= " AND p.id_personagem NOT IN (SELECT id_personagem FROM tb_campanha_personagem WHERE id_campanha = ?)";
    }

    $stmt = $pdo->prepare($sql);
    $params = [$id_usuario];
    if ($id_campanha) $params[] = $id_campanha;
    
    $stmt->execute($params);
    $personagens = $stmt->fetchAll();

    echo json_encode(['success' => true, 'personagens' => $personagens]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
