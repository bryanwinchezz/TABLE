<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$id_personagem = $_POST['id_personagem'] ?? null;
if (!$id_personagem || !isset($_FILES['avatar'])) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

$file = $_FILES['avatar'];

if ($file['size'] > 5242880) { // Limite de 5MB
    echo json_encode(['success' => false, 'error' => 'O arquivo é muito grande (Máx: 5MB)']);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

// Validação MIME real
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

if (!in_array(strtolower($ext), $allowed) || !in_array($mime, $allowed_mimes)) {
    echo json_encode(['success' => false, 'error' => 'Formato não permitido ou arquivo corrompido']);
    exit;
}

$new_name = 'pers_' . $id_personagem . '_' . time() . '.' . $ext;
$target_dir = __DIR__ . '/../../img/uploads/';
$target_file = $target_dir . $new_name;
$db_path = '../img/uploads/' . $new_name;

if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

try {
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $pdo = Database::getConexao();
        
        // Buscar foto antiga para deletar se não for a padrão
        $stmt = $pdo->prepare("SELECT ds_foto FROM tb_personagem WHERE id_personagem = ? AND id_usuario = ?");
        $stmt->execute([$id_personagem, $_SESSION['usuario']['id']]);
        $old_photo = $stmt->fetchColumn();

        if ($old_photo && strpos($old_photo, 'foto-ficha.jpg') === false) {
            $old_path = __DIR__ . '/../../' . str_replace('../', '', $old_photo);
            if (file_exists($old_path)) unlink($old_path);
        }

        // Atualizar banco
        $stmt = $pdo->prepare("UPDATE tb_personagem SET ds_foto = ? WHERE id_personagem = ? AND id_usuario = ?");
        $stmt->execute([$db_path, $id_personagem, $_SESSION['usuario']['id']]);

        echo json_encode(['success' => true, 'path' => $db_path]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar arquivo']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
