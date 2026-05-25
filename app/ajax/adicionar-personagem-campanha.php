<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id_campanha = (int)($data['id_campanha'] ?? 0);
$id_personagem = (int)($data['id_personagem'] ?? 0);
$id_usuario = (int)$_SESSION['usuario']['id'];

if (!$id_campanha || !$id_personagem) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

try {
    $pdo = Database::getConexao();

    // Verifica se o usuário logado é o mestre da campanha
    $stmtMestre = $pdo->prepare("SELECT id_usuario_mestre FROM tb_campanha WHERE id_campanha = ?");
    $stmtMestre->execute([$id_campanha]);
    $campanha = $stmtMestre->fetch();
    
    $isMaster = ($campanha && (int)$campanha['id_usuario_mestre'] === $id_usuario);

    // Se NÃO for o mestre, limita a no máximo 1 personagem na campanha
    if (!$isMaster) {
        $stmtCount = $pdo->prepare("
            SELECT COUNT(*) as total 
              FROM tb_campanha_personagem cp
              JOIN tb_personagem p ON cp.id_personagem = p.id_personagem
             WHERE cp.id_campanha = ? AND p.id_usuario = ?
        ");
        $stmtCount->execute([$id_campanha, $id_usuario]);
        $resCount = $stmtCount->fetch();

        if ($resCount && (int)$resCount['total'] >= 1) {
            echo json_encode(['success' => false, 'error' => 'Você já possui 1 personagem nesta campanha. Por favor, remova o seu personagem atual antes de adicionar outro.']);
            exit;
        }
    }

    // Inserir vinculo (Verifica antes para evitar duplicidade manual)
    $stmtCheck = $pdo->prepare("SELECT 1 FROM tb_campanha_personagem WHERE id_campanha = ? AND id_personagem = ?");
    $stmtCheck->execute([$id_campanha, $id_personagem]);
    
    if (!$stmtCheck->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO tb_campanha_personagem (id_campanha, id_personagem) VALUES (?, ?)");
        $stmt->execute([$id_campanha, $id_personagem]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

