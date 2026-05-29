<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Para upload de arquivos, usamos $_POST e $_FILES
$id_sistema = $_POST['id_sistema'] ?? null;
$id_monstro = $_POST['id_monstro'] ?? null;
$nome = $_POST['nome'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$desc = $_POST['descricao'] ?? '';
$vida = $_POST['vida'] ?? 0;
$defesa = $_POST['defesa'] ?? 0;
$xp = $_POST['xp'] ?? 0;
$vd = $_POST['vd'] ?? 0;
// Atributos vêm como JSON string no FormData
$atributos = isset($_POST['atributos']) ? json_decode($_POST['atributos'], true) : [];

if (!$id_sistema || !$nome) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

try {
    $pdo = Database::getConexao();
    $pdo->beginTransaction();

    // 1. Lidar com Upload de Imagem
    $caminho_imagem = $_POST['imagem_atual'] ?? null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $diretorio = __DIR__ . '/../../img/uploads/';
        if (!is_dir($diretorio)) mkdir($diretorio, 0777, true);

        $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nome_arquivo = 'monstro_' . uniqid() . '.' . $extensao;
        $destino = $diretorio . $nome_arquivo;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
            $caminho_imagem = '../img/uploads/' . $nome_arquivo;
        }
    }

    if ($id_monstro) {
        // UPDATE
        $stmt = $pdo->prepare("
            UPDATE tb_monstro 
            SET nm_monstro = ?, ds_monstro = ?, tp_monstro = ?, qt_vida = ?, qt_defesa = ?, qt_xp_recompensa = ?, ds_imagem = ?, qt_vd = ?
            WHERE id_monstro = ? AND id_sistema = ?
        ");
        $stmt->execute([$nome, $desc, $tipo, $vida, $defesa, $xp, $caminho_imagem, $vd, $id_monstro, $id_sistema]);

        // Sincronizar Atributos (Deletar e Reincerir)
        $stmtDel = $pdo->prepare("DELETE FROM tb_monstro_atributo WHERE id_monstro = ?");
        $stmtDel->execute([$id_monstro]);
    } else {
        // INSERT
        $stmt = $pdo->prepare("
            INSERT INTO tb_monstro (nm_monstro, ds_monstro, tp_monstro, qt_vida, qt_defesa, qt_xp_recompensa, id_sistema, ds_imagem, qt_vd) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nome, $desc, $tipo, $vida, $defesa, $xp, $id_sistema, $caminho_imagem, $vd]);
        $id_monstro = $pdo->lastInsertId();
    }

    // 3. Inserir Atributos
    if (!empty($atributos)) {
        $stmtAttr = $pdo->prepare("INSERT INTO tb_monstro_atributo (id_monstro, id_atributo, qt_valor) VALUES (?, ?, ?)");
        foreach ($atributos as $attr) {
            $stmtAttr->execute([$id_monstro, $attr['id'], $attr['valor']]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'id_monstro' => $id_monstro]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

