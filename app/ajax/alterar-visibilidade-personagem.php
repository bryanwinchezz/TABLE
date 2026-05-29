<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id_campanha = $data['id_campanha'] ?? null;
$id_personagem = $data['id_personagem'] ?? null;
$fl_publico = isset($data['fl_publico']) ? (int)$data['fl_publico'] : 0;

if (!$id_campanha || !$id_personagem) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos.']);
    exit;
}

try {
    $pdo = Database::getConexao();

    // Validar se o usuário logado é o dono do personagem ou o mestre da campanha
    $stmtValida = $pdo->prepare("SELECT id_usuario FROM tb_personagem WHERE id_personagem = ?");
    $stmtValida->execute([$id_personagem]);
    $pers = $stmtValida->fetch();

    if (!$pers) {
        echo json_encode(['success' => false, 'error' => 'Personagem não encontrado.']);
        exit;
    }

    $isOwner = ((int)$pers['id_usuario'] === (int)$_SESSION['usuario']['id']);

    $stmtCamp = $pdo->prepare("SELECT id_usuario_mestre FROM tb_campanha WHERE id_campanha = ?");
    $stmtCamp->execute([$id_campanha]);
    $camp = $stmtCamp->fetch();
    $isMaster = ($camp && (int)$camp['id_usuario_mestre'] === (int)$_SESSION['usuario']['id']);

    // Apenas o dono do personagem pode alterar sua visibilidade na campanha
    if (!$isOwner) {
        echo json_encode(['success' => false, 'error' => 'Apenas o dono do personagem pode alterar a visibilidade da sua ficha.']);
        exit;
    }

    // Alterar a visibilidade no vínculo da campanha
    $stmtUpdate = $pdo->prepare("
        UPDATE tb_campanha_personagem 
           SET fl_publico = ? 
         WHERE id_campanha = ? AND id_personagem = ?
    ");
    $stmtUpdate->execute([$fl_publico, $id_campanha, $id_personagem]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

