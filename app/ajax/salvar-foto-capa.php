<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

if (!isset($_FILES['foto']) || !isset($_POST['id_campanha'])) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

$id_campanha = $_POST['id_campanha'];
$id_usuario = $_SESSION['usuario']['id'];
$arquivo = $_FILES['foto'];

// Validar extensões
$extensoes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $extensoes)) {
    echo json_encode(['success' => false, 'error' => 'Formato não suportado']);
    exit;
}

// Criar pasta se não existir
$diretorio = __DIR__ . '/../../img/uploads/';
if (!is_dir($diretorio)) mkdir($diretorio, 0777, true);

$nomeArquivo = 'capa_' . $id_campanha . '_' . time() . '.' . $ext;
$caminhoFinal = $diretorio . $nomeArquivo;
$urlRelativa = '../img/uploads/' . $nomeArquivo;

if (move_uploaded_file($arquivo['tmp_name'], $caminhoFinal)) {
    try {
        $pdo = Database::getConexao();
        $stmt = $pdo->prepare("UPDATE tb_campanha SET ds_imagem = ? WHERE id_campanha = ? AND id_usuario_mestre = ?");
        $stmt->execute([$urlRelativa, $id_campanha, $id_usuario]);
        
        echo json_encode(['success' => true, 'url' => $urlRelativa]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao mover arquivo']);
}

