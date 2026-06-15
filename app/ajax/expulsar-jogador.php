<?php
if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__FILE__, true);
}

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Segurança: Verifica autenticação
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Usuário não autenticado.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = Database::getConexao();

    // 2. Leitura dos parâmetros do payload
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $id_campanha = isset($data['id_campanha']) ? (int)$data['id_campanha'] : null;
    $id_usuario_expulsar = isset($data['id_usuario']) ? (int)$data['id_usuario'] : null;
    $usuario_logado_id = (int)$_SESSION['usuario']['id'];

    if (!$id_campanha || !$id_usuario_expulsar) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos.']);
        exit;
    }

    // 3. Validação: Apenas o Mestre da campanha pode expulsar participantes
    $stmtMestre = $pdo->prepare("SELECT id_usuario_mestre FROM tb_campanha WHERE id_campanha = ? LIMIT 1");
    $stmtMestre->execute([$id_campanha]);
    $campanha = $stmtMestre->fetch(PDO::FETCH_ASSOC);

    if (!$campanha) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Campanha não encontrada.']);
        exit;
    }

    if ((int)$campanha['id_usuario_mestre'] !== $usuario_logado_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Apenas o Mestre desta campanha pode expulsar participantes.']);
        exit;
    }

    // O Mestre não pode se expulsar
    if ($id_usuario_expulsar === $usuario_logado_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Você não pode expulsar a si mesmo da campanha.']);
        exit;
    }

    // 4. Executar a Expulsão em Transação
    $pdo->beginTransaction();

    // Remover os personagens do jogador expulso vinculados a esta campanha
    $stmtDelPersonagens = $pdo->prepare("
        DELETE FROM tb_campanha_personagem 
        WHERE id_campanha = :id_campanha 
          AND id_personagem IN (SELECT id_personagem FROM tb_personagem WHERE id_usuario = :id_usuario)
    ");
    $stmtDelPersonagens->execute([
        ':id_campanha' => $id_campanha,
        ':id_usuario'  => $id_usuario_expulsar
    ]);

    // Remover a participação do jogador na campanha
    $stmtDelUsuario = $pdo->prepare("
        DELETE FROM tb_campanha_usuario 
        WHERE id_campanha = :id_campanha 
          AND id_usuario = :id_usuario
    ");
    $stmtDelUsuario->execute([
        ':id_campanha' => $id_campanha,
        ':id_usuario'  => $id_usuario_expulsar
    ]);

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
