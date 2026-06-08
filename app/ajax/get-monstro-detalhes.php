<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

$id_monstro = $_GET['id'] ?? null;

if (!$id_monstro) {
    echo json_encode(['success' => false, 'error' => 'ID não fornecido']);
    exit;
}

try {
    $pdo = Database::getConexao();

    // 1. Dados básicos
    $stmt = $pdo->prepare("SELECT * FROM tb_monstro WHERE id_monstro = ?");
    $stmt->execute([$id_monstro]);
    $monstro = $stmt->fetch();

    if (!$monstro) {
        echo json_encode(['success' => false, 'error' => 'Monstro não encontrado']);
        exit;
    }

    $id_sistema = $monstro['id_sistema'];

    // 2. Atributos (Garante que exibe todos os atributos do sistema, mesmo que o monstro não tenha valores salvos ainda)
    $stmt = $pdo->prepare("
        SELECT a.id_atributo, a.nm_atributo, a.ds_abreviacao, COALESCE(ma.qt_valor, 0) as qt_valor 
        FROM tb_atributo a
        LEFT JOIN tb_monstro_atributo ma ON a.id_atributo = ma.id_atributo AND ma.id_monstro = ?
        WHERE a.id_sistema = ?
        ORDER BY a.id_atributo ASC
    ");
    $stmt->execute([$id_monstro, $id_sistema]);
    $atributos = $stmt->fetchAll();

    echo json_encode([
        'success' => true, 
        'monstro' => $monstro, 
        'atributos' => $atributos
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

