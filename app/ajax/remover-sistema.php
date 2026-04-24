<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Sessão expirada.']);
    exit;
}

$id_sistema = $_POST['id'] ?? null;

if (!$id_sistema) {
    echo json_encode(['success' => false, 'error' => 'ID do sistema não informado.']);
    exit;
}

try {
    $pdo = Database::getConexao();

    // Verificar se o usuário é o dono ou admin
    $stmt = $pdo->prepare("SELECT id_usuario_criador FROM tb_sistema WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $sistema = $stmt->fetch();

    if (!$sistema) {
        echo json_encode(['success' => false, 'error' => 'Sistema não encontrado.']);
        exit;
    }

    // Permitir se for o criador ou se for admin
    $isOwner = ($sistema['id_usuario_criador'] == $_SESSION['usuario']['id']);
    $isAdmin = (isset($_SESSION['usuario']['cargo']) && strtolower($_SESSION['usuario']['cargo']) === 'admin');

    if (!$isOwner && !$isAdmin) {
        echo json_encode(['success' => false, 'error' => 'Você não tem permissão para excluir este sistema.']);
        exit;
    }

    // Deletar em cascata manual (respeitar a ordem das FKs)

    // 1. Remover monstros do sistema
    $pdo->prepare("DELETE FROM tb_monstro WHERE id_sistema = ?")->execute([$id_sistema]);

    // 3. Remover classes do sistema
    $pdo->prepare("DELETE FROM tb_classe WHERE id_sistema = ?")->execute([$id_sistema]);

    // 4. Remover origens do sistema
    $pdo->prepare("DELETE FROM tb_origem WHERE id_sistema = ?")->execute([$id_sistema]);

    // 5. Remover atributos do sistema
    $pdo->prepare("DELETE FROM tb_atributo WHERE id_sistema = ?")->execute([$id_sistema]);

    // 6. Remover perícias do sistema
    $pdo->prepare("DELETE FROM tb_pericia WHERE id_sistema = ?")->execute([$id_sistema]);

    // 7. Remover status do sistema
    $pdo->prepare("DELETE FROM tb_sistema_status WHERE id_sistema = ?")->execute([$id_sistema]);

    // 8. Remover habilidades do sistema
    $pdo->prepare("DELETE FROM tb_habilidade WHERE id_sistema = ?")->execute([$id_sistema]);

    // 9. Remover itens do sistema
    $pdo->prepare("DELETE FROM tb_item WHERE id_sistema = ?")->execute([$id_sistema]);

    // 10. Finalmente, remover o sistema
    $pdo->prepare("DELETE FROM tb_sistema WHERE id_sistema = ?")->execute([$id_sistema]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao remover: ' . $e->getMessage()]);
}
