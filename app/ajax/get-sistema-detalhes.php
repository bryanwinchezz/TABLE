<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$id_sistema = $_GET['id'] ?? null;

if (!$id_sistema) {
    echo json_encode(['success' => false, 'error' => 'ID do sistema não fornecido']);
    exit;
}

try {
    $pdo = Database::getConexao();

    // 1. Dados do Sistema
    $stmt = $pdo->prepare("SELECT * FROM tb_sistema WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $sistema = $stmt->fetch();

    if (!$sistema) {
        throw new Exception("Sistema não encontrado");
    }

    // 2. Atributos
    $stmt = $pdo->prepare("SELECT nm_atributo, ds_abreviacao FROM tb_atributo WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $atributos = $stmt->fetchAll();

    // 3. Classes
    $stmt = $pdo->prepare("SELECT nm_classe, ds_descricao FROM tb_classe WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $classes = $stmt->fetchAll();

    // 4. Perícias
    $stmt = $pdo->prepare("SELECT nm_pericia, ds_atributo_base FROM tb_pericia WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $pericias = $stmt->fetchAll();

    // 5. Origens
    $stmt = $pdo->prepare("SELECT nm_origem, ds_origem FROM tb_origem WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $origens = $stmt->fetchAll();

    // 6. Itens (Equipamentos)
    $stmt = $pdo->prepare("SELECT nm_item, ds_item, tp_item FROM tb_item WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $itens = $stmt->fetchAll();

    // 7. Habilidades (Poderes)
    $stmt = $pdo->prepare("SELECT nm_habilidade, ds_habilidade, tp_habilidade FROM tb_habilidade WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $habilidades = $stmt->fetchAll();

    // 8. Status e Defesas
    $stmt = $pdo->prepare("SELECT * FROM tb_sistema_status WHERE id_sistema = ?");
    $stmt->execute([$id_sistema]);
    $status = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'sistema' => $sistema,
        'atributos' => $atributos,
        'classes' => $classes,
        'pericias' => $pericias,
        'origens' => $origens,
        'itens' => $itens,
        'habilidades' => $habilidades,
        'status' => $status
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

