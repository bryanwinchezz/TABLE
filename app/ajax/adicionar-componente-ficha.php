<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id_personagem = $data['id_personagem'] ?? null;
$tipo = $data['tipo'] ?? null;
$id_componente = $data['id_componente'] ?? null;

if (!$id_personagem || !$tipo || !$id_componente) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

try {
    $pdo = Database::getConexao();
    if ($tipo === 'inventario') {
        // Verificar se já tem o item para aumentar quantidade ou apenas adicionar novo
        $stmt = $pdo->prepare("SELECT id_personagem_item, qt_quantidade FROM tb_personagem_item WHERE id_personagem = ? AND id_item = ?");
        $stmt->execute([$id_personagem, $id_componente]);
        $existente = $stmt->fetch();

        if ($existente) {
            $stmt = $pdo->prepare("UPDATE tb_personagem_item SET qt_quantidade = qt_quantidade + 1 WHERE id_personagem_item = ?");
            $stmt->execute([$existente['id_personagem_item']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO tb_personagem_item (id_personagem, id_item, qt_quantidade) VALUES (?, ?, 1)");
            $stmt->execute([$id_personagem, $id_componente]);
        }
    } else {
        // Habilidades ou Poderes (mesma tabela)
        // Verificar se já possui
        $stmt = $pdo->prepare("SELECT id_habilidade_personagem FROM tb_habilidade_personagem WHERE id_personagem = ? AND id_habilidade = ?");
        $stmt->execute([$id_personagem, $id_componente]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO tb_habilidade_personagem (id_personagem, id_habilidade) VALUES (?, ?)");
            $stmt->execute([$id_personagem, $id_componente]);
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

