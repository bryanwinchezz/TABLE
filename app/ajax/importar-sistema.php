<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit;
}

$pdo = Database::getConexao();
$usuario_id = (int)$_SESSION['usuario']['id'];
$token = trim($_POST['token'] ?? '');

if (!$token) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Token não fornecido.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Valida o token
    $stmt = $pdo->prepare("
        SELECT id_sistema FROM tb_convite_sistema 
        WHERE ds_token = ? AND tp_status = 'pendente' AND (dt_expiracao IS NULL OR dt_expiracao > NOW())
    ");
    $stmt->execute([$token]);
    $convite = $stmt->fetch();

    if (!$convite) {
        throw new Exception('Convite inválido ou expirado.');
    }

    $id_sistema_origem = (int)$convite['id_sistema'];

    // 1.1 Bloqueia importação do sistema oficial (Ordem Paranormal ID 1)
    if ($id_sistema_origem === 1) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['sucesso' => true, 'id_sistema' => 1, 'mensagem' => 'Este é um sistema oficial e já está disponível para todos!']);
        exit;
    }

    // 2. Verifica se o usuário já é o criador do sistema
    $stmt = $pdo->prepare("SELECT id_sistema FROM tb_sistema WHERE id_usuario_criador = ? AND id_sistema = ?");
    $stmt->execute([$usuario_id, $id_sistema_origem]);
    $isCriador = $stmt->fetch();

    if ($isCriador) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['sucesso' => true, 'id_sistema' => $id_sistema_origem, 'mensagem' => 'Você já é o criador deste sistema!']);
        exit;
    }

    // 3. Adiciona na tb_usuario_sistema para acesso em tempo real
    $stmt = $pdo->prepare("SELECT 1 FROM tb_usuario_sistema WHERE id_usuario = ? AND id_sistema = ?");
    $stmt->execute([$usuario_id, $id_sistema_origem]);
    
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO tb_usuario_sistema (id_usuario, id_sistema) VALUES (?, ?)")
            ->execute([$usuario_id, $id_sistema_origem]);
    } else {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['sucesso' => true, 'id_sistema' => $id_sistema_origem, 'mensagem' => 'Você já possui este sistema em sua conta!']);
        exit;
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true, 'id_sistema' => $id_sistema_origem]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}
exit;
