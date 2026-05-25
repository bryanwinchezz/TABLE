<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

/** Gera UUID v4 (36 chars) conforme RFC 4122. */
function guidv4(): string {
    $data    = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$pdo = Database::getConexao();
$usuario_id = (int)$_SESSION['usuario']['id'];
$id_sistema = (int)($_POST['id_sistema'] ?? 0);

if (!$id_sistema) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID do sistema não fornecido.']);
    exit;
}

// Verifica se o usuário é o criador do sistema ou admin
$stmt = $pdo->prepare("
    SELECT id_sistema FROM tb_sistema 
    WHERE id_sistema = ? AND (id_usuario_criador = ? OR (SELECT tp_cargo FROM tb_usuario WHERE id_usuario = ?) = 'admin')
");
$stmt->execute([$id_sistema, $usuario_id, $usuario_id]);

if (!$stmt->fetch()) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Você não tem permissão para compartilhar este sistema.']);
    exit;
}

// Verifica se já existe um convite permanente para este sistema
$stmt = $pdo->prepare("
    SELECT ds_token FROM tb_convite_sistema 
    WHERE id_sistema = ? AND tp_status = 'pendente' AND dt_expiracao IS NULL
    LIMIT 1
");
$stmt->execute([$id_sistema]);
$conviteExistente = $stmt->fetch();

if ($conviteExistente) {
    $token = $conviteExistente['ds_token'];
} else {
    // Gera novo token permanente
    $token = guidv4();
    $pdo->prepare("
        INSERT INTO tb_convite_sistema (id_sistema, ds_token, tp_status, dt_criacao, dt_expiracao)
        VALUES (?, ?, 'pendente', NOW(), NULL)
    ")->execute([$id_sistema, $token]);
}

$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_path = '/TABLE%20-%2012052026/TABLE-main'; // Mantendo o padrão do projeto
$link = "$protocolo://$host$base_path/pages/invite-sistema.php?token=$token";

echo json_encode(['sucesso' => true, 'link' => $link, 'token' => $token]);
exit;
