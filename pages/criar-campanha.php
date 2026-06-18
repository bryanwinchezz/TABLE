<?php
/* ATUALIZADO - conexão DB + dropdown sistemas + sistema de convite UUID + dddice */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// ============================================================
// DDDICE — Credenciais (usadas pelo escudo do mestre)
// ============================================================
define('DDDICE_API_KEY',   'loKtWZoIgQgepNUC44LpaeYJZdAU7mqs1S9FxHTabc00dd7b');
define('DDDICE_ROOM_SLUG', 'mF9ol6O');

require_once __DIR__ . '/../app/config/database.php';
$pdo = Database::getConexao();

$flPlanoMapas = 0;
if (isset($_SESSION['usuario'])) {
    try {
        $stmtPlan = $pdo->prepare("SELECT fl_plano_mapas, fl_plano_completo FROM tb_usuario WHERE id_usuario = ? LIMIT 1");
        $stmtPlan->execute([$_SESSION['usuario']['id']]);
        $userPlan = $stmtPlan->fetch();
        if ($userPlan) {
            if ((int)$userPlan['fl_plano_mapas'] === 1 || (int)$userPlan['fl_plano_completo'] === 1) {
                $flPlanoMapas = 1;
            }
        }
    } catch (Exception $e) {}
}

try {
    $stmtCheckCol = $pdo->query("SHOW COLUMNS FROM tb_campanha_personagem LIKE 'fl_publico'");
    if ($stmtCheckCol->rowCount() === 0) {
        $pdo->exec("ALTER TABLE tb_campanha_personagem ADD COLUMN fl_publico TINYINT(1) NOT NULL DEFAULT 0");
    }
} catch (Exception $e) {
    // Silencioso
}

// ============================================================
// FUNÇÕES
// ============================================================

/** Gera UUID v4 (36 chars) conforme RFC 4122. */
function guidv4(): string {
    $data    = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// ---- Endpoint AJAX: ?action=roll_escudo (POST) ----
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'roll_escudo') {
    header('Content-Type: application/json');
    $body = json_decode(file_get_contents('php://input'), true);
    $dice = $body['dice'] ?? [];
    $id_campanha_post = isset($body['id_campanha']) ? (int)$body['id_campanha'] : null;
    $label_post = $body['label'] ?? '';

    $roomSlug = DDDICE_ROOM_SLUG;
    if ($id_campanha_post) {
        try {
            $stmtSlug = $pdo->prepare("SELECT ds_dddice_room_slug FROM tb_campanha WHERE id_campanha = ? LIMIT 1");
            $stmtSlug->execute([$id_campanha_post]);
            $campRoom = $stmtSlug->fetch();
            if ($campRoom && !empty($campRoom['ds_dddice_room_slug'])) {
                $roomSlug = $campRoom['ds_dddice_room_slug'];
            }
        } catch (Exception $e) {}
    }

    if (empty($dice)) { echo json_encode(['error' => 'Nenhum dado enviado.']); exit; }

    $ch = curl_init('https://dddice.com/api/1.0/roll');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['dice' => $dice, 'room' => $roomSlug, 'label' => $label_post]),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . DDDICE_API_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) { echo json_encode(['error' => 'cURL: ' . $curlErr]); exit; }

    $data = json_decode($response, true);
    if ($httpCode !== 200 && $httpCode !== 201) {
        $msg = $data['data']['message'] ?? $data['message'] ?? $response;
        echo json_encode(['error' => "HTTP $httpCode: $msg"]);
        exit;
    }

    $values = $data['data']['values'] ?? [];
    $total  = array_sum(array_column($values, 'value'));
    echo json_encode([
        'ok'     => true,
        'total'  => $total,
        'values' => array_map(fn($v) => ['value' => $v['value'], 'type' => $v['type'] ?? ''], $values),
    ]);
    exit;
}

// ---- Endpoint AJAX: ?action=themes_escudo (GET) ----
if (($_GET['action'] ?? '') === 'themes_escudo') {
    header('Content-Type: application/json');
    $ch = curl_init('https://dddice.com/api/1.0/dice-box');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . DDDICE_API_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($response, true);
    if ($httpCode !== 200) { echo json_encode(['error' => "HTTP $httpCode"]); exit; }
    echo json_encode(['themes' => $data['data'] ?? []]);
    exit;
}

// ---- Endpoint AJAX: ?action=get_personagens_escudo (GET) ----
if (($_GET['action'] ?? '') === 'get_personagens_escudo') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['usuario'])) {
        echo json_encode(['sucesso' => false, 'error' => 'Não autorizado']);
        exit;
    }
    $campaign_id = (int)($_GET['id_campanha'] ?? 0);
    $usuario_id = (int)$_SESSION['usuario']['id'];

    // Validar se o usuário é o mestre da campanha
    $stmt = $pdo->prepare("SELECT id_campanha FROM tb_campanha WHERE id_campanha = ? AND id_usuario_mestre = ?");
    $stmt->execute([$campaign_id, $usuario_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['sucesso' => false, 'error' => 'Acesso negado']);
        exit;
    }

    // Buscar Personagens vinculados à campanha
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.*, s.nm_sistema, cp.fl_publico, u.nm_usuario as jogador_nome
          FROM tb_campanha_personagem cp
          JOIN tb_personagem p ON cp.id_personagem = p.id_personagem
          LEFT JOIN tb_sistema s ON p.id_sistema = s.id_sistema
          LEFT JOIN tb_usuario u ON p.id_usuario = u.id_usuario
         WHERE cp.id_campanha = ?
    ");
    $stmt->execute([$campaign_id]);
    $personagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($personagens as &$p) {
        // Buscar Atributos
        $stmtAttr = $pdo->prepare("
            SELECT a.nm_atributo, a.ds_abreviacao, pa.qt_valor
              FROM tb_personagem_atributo pa
              JOIN tb_atributo a ON pa.id_atributo = a.id_atributo
             WHERE pa.id_personagem = ?
        ");
        $stmtAttr->execute([$p['id_personagem']]);
        $p['atributos'] = $stmtAttr->fetchAll(PDO::FETCH_ASSOC);

        // Buscar Classe
        $stmtCl = $pdo->prepare("SELECT c.nm_classe FROM tb_classe c JOIN tb_personagem_classe pc ON c.id_classe = pc.id_classe WHERE pc.id_personagem = ? LIMIT 1");
        $stmtCl->execute([$p['id_personagem']]);
        $cl = $stmtCl->fetch();
        $p['nm_classe'] = $cl ? $cl['nm_classe'] : 'Mundano';

        // Buscar Origem
        $stmtOr = $pdo->prepare("SELECT o.nm_origem FROM tb_origem o JOIN tb_personagem_origem po ON o.id_origem = po.id_origem WHERE po.id_personagem = ? LIMIT 1");
        $stmtOr->execute([$p['id_personagem']]);
        $or = $stmtOr->fetch();
        $p['nm_origem'] = $or ? $or['nm_origem'] : 'Cidadão';

        // Buscar Itens do Personagem (Inventário) para o Escudo
        $stmtItens = $pdo->prepare("
            SELECT i.*, pi.qt_quantidade
            FROM tb_personagem_item pi
            JOIN tb_item i ON pi.id_item = i.id_item
            WHERE pi.id_personagem = ?
        ");
        $stmtItens->execute([$p['id_personagem']]);
        $p['itens'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        // Buscar Habilidades/Rituais/Poderes do Personagem para o Escudo
        $stmtHabs = $pdo->prepare("
            SELECT h.*
            FROM tb_habilidade_personagem hp
            JOIN tb_habilidade h ON hp.id_habilidade = h.id_habilidade
            WHERE hp.id_personagem = ?
        ");
        $stmtHabs->execute([$p['id_personagem']]);
        $p['habilidades'] = $stmtHabs->fetchAll(PDO::FETCH_ASSOC);

        // Buscar Status Customizados do Sistema (Barras)
        $stmtStatus = $pdo->prepare("
            SELECT ss.*, COALESCE(ps.qt_valor_atual, 0) as qt_atual, COALESCE(ps.qt_valor_maximo, 0) as qt_max 
            FROM tb_sistema_status ss
            LEFT JOIN tb_personagem_status ps ON ss.id_status_sistema = ps.id_status_sistema AND ps.id_personagem = ?
            WHERE ss.id_sistema = ? AND ss.tp_status = 'barra'
        ");
        $stmtStatus->execute([$p['id_personagem'], $p['id_sistema']]);
        $p['status_barras'] = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);

        // Fallback para Vida, Sanidade, Esforço caso o sistema não tenha barras customizadas
        if (empty($p['status_barras'])) {
            $p['status_barras'] = [
                ['nm_status' => 'VIDA', 'ds_cor' => '#ed1c24', 'qt_atual' => (int)$p['qt_vida'], 'qt_max' => (int)$p['qt_vida_maxima'], 'id_status_sistema' => 'vida'],
                ['nm_status' => 'SANIDADE', 'ds_cor' => '#a855f7', 'qt_atual' => (int)$p['qt_sanidade'], 'qt_max' => (int)$p['qt_sanidade_maxima'], 'id_status_sistema' => 'sanidade'],
                ['nm_status' => 'ESFORÇO', 'ds_cor' => '#f97316', 'qt_atual' => (int)$p['qt_esforco'], 'qt_max' => (int)$p['qt_esforco_maximo'], 'id_status_sistema' => 'esforco']
            ];
        }

        // Buscar Defesas Customizadas do Sistema com valores do Personagem
        $stmtDefesas = $pdo->prepare("
            SELECT ss.*, COALESCE(ps.qt_valor_atual, 10) as qt_atual 
            FROM tb_sistema_status ss
            LEFT JOIN tb_personagem_status ps ON ss.id_status_sistema = ps.id_status_sistema AND ps.id_personagem = ?
            WHERE ss.id_sistema = ? AND ss.tp_status = 'defesa'
        ");
        $stmtDefesas->execute([$p['id_personagem'], $p['id_sistema']]);
        $p['status_defesas'] = $stmtDefesas->fetchAll(PDO::FETCH_ASSOC);

        // Fallback para Defesa, Bloqueio, Esquiva caso o sistema não tenha defesas customizadas
        if (empty($p['status_defesas'])) {
            $p['status_defesas'] = [
                ['nm_status' => 'DEFESA', 'ds_cor' => '#95a5a6', 'qt_atual' => (int)($p['qt_defesa'] ?: 10), 'id_status_sistema' => 'defesa'],
                ['nm_status' => 'BLOQUEIO', 'ds_cor' => '#f39c12', 'qt_atual' => (int)$p['qt_bloqueio'], 'id_status_sistema' => 'bloqueio'],
                ['nm_status' => 'ESQUIVA', 'ds_cor' => '#2980b9', 'qt_atual' => (int)($p['qt_esquiva'] ?: ($p['qt_defesa'] ?: 10)), 'id_status_sistema' => 'esquiva']
            ];
        }

        // Bloqueio / Esquiva fallbacks
        $p['qt_bloqueio'] = $p['qt_bloqueio'] ?? 0;
        $p['qt_esquiva'] = $p['qt_esquiva'] ?? ($p['qt_defesa'] ?? 10);
    }
    unset($p);

    echo json_encode(['sucesso' => true, 'personagens' => $personagens]);
    exit;
}

if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}


// ============================================================
// ENDPOINTS AJAX (POST)
// ============================================================
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $usuario_id = (int) $_SESSION['usuario']['id'];

    if ($_POST['action'] === 'gerar_convite') {
        $campaign_id = (int) ($_POST['campaign_id'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT id_campanha FROM tb_campanha
            WHERE id_campanha = ? AND id_usuario_mestre = ?
        ");
        $stmt->execute([$campaign_id, $usuario_id]);

        if (!$stmt->fetch()) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
            exit;
        }

        $pdo->prepare("
            UPDATE tb_convite_campanha
               SET tp_status = 'expirado'
             WHERE id_campanha = ? AND tp_status = 'pendente'
        ")->execute([$campaign_id]);

        $token = guidv4();
        $pdo->prepare("
            INSERT INTO tb_convite_campanha
                (id_campanha, ds_token, tp_status, dt_criacao, dt_expiracao)
            VALUES (?, ?, 'pendente', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY))
        ")->execute([$campaign_id, $token]);

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $current_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $current_dir = rtrim($current_dir, '/');
        $link = $protocol . $_SERVER['HTTP_HOST'] . $current_dir . '/invite.php?token=' . $token;
        echo json_encode(['sucesso' => true, 'link' => $link, 'token' => $token]);
        exit;
    }

    if ($_POST['action'] === 'remover_personagem') {
        $campaign_id = (int) ($_POST['campaign_id'] ?? 0);
        $personagem_id = (int) ($_POST['personagem_id'] ?? 0);

        // Verifica permissão: mestre da campanha ou dono do personagem
        $stmt = $pdo->prepare("
            SELECT c.id_usuario_mestre, p.id_usuario as id_dono
            FROM tb_campanha c
            JOIN tb_campanha_personagem cp ON c.id_campanha = cp.id_campanha
            JOIN tb_personagem p ON cp.id_personagem = p.id_personagem
            WHERE c.id_campanha = ? AND p.id_personagem = ?
        ");
        $stmt->execute([$campaign_id, $personagem_id]);
        $vinculo = $stmt->fetch();

        if ($vinculo && ($vinculo['id_usuario_mestre'] == $usuario_id || $vinculo['id_dono'] == $usuario_id)) {
            $pdo->prepare("DELETE FROM tb_campanha_personagem WHERE id_campanha = ? AND id_personagem = ?")
                ->execute([$campaign_id, $personagem_id]);
            echo json_encode(['sucesso' => true]);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Permissão negada.']);
        }
        exit;
    }

    if ($_POST['action'] === 'sair_campanha') {
        $campaign_id = (int) ($_POST['campaign_id'] ?? 0);

        // Segurança: verifica se o usuário logado ainda possui personagem na campanha
        $stmtCheck = $pdo->prepare("
            SELECT COUNT(*) as total 
              FROM tb_campanha_personagem cp
              JOIN tb_personagem p ON cp.id_personagem = p.id_personagem
             WHERE cp.id_campanha = ? AND p.id_usuario = ?
        ");
        $stmtCheck->execute([$campaign_id, $usuario_id]);
        $res = $stmtCheck->fetch();

        if ($res && (int)$res['total'] > 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Por favor, retire primeiro o seu personagem da campanha clicando no botão "Sair" do seu personagem.']);
            exit;
        }

        // Se não tem personagem vinculado à campanha, remove o usuário da campanha
        $pdo->prepare("
            DELETE FROM tb_campanha_usuario
             WHERE id_campanha = ? AND id_usuario = ?
        ")->execute([$campaign_id, $usuario_id]);

        echo json_encode(['sucesso' => true]);
        exit;
    }

    echo json_encode(['sucesso' => false, 'mensagem' => 'Ação desconhecida.']);
    exit;
}

// ============================================================
// DADOS DA PÁGINA
// ============================================================
$stmt = $pdo->prepare("
    SELECT DISTINCT s.id_sistema, s.nm_sistema 
    FROM tb_sistema s
    LEFT JOIN tb_usuario u ON s.id_usuario_criador = u.id_usuario
    LEFT JOIN tb_usuario_sistema us ON s.id_sistema = us.id_sistema
    WHERE s.id_usuario_criador = ? 
       OR u.tp_cargo = 'admin' 
       OR s.id_usuario_criador IS NULL 
       OR us.id_usuario = ?
    ORDER BY s.nm_sistema ASC
");
$stmt->execute([$_SESSION['usuario']['id'], $_SESSION['usuario']['id']]);
$sistemas = $stmt->fetchAll();

$campanhaDados       = null;
$PersonagemsCampanha = [];
$combatesCampanha    = [];
$atributosSistema    = [];
$jogadoresCampanha   = [];
$jogadoresAgrupados  = [];
$isMaster            = false;
$roomSlug            = '';
$mapaParticipantesPersonagens = [];
$sistemaStatusBarras = [];
$sistemaStatusDefesas = [];


$id_campanha = $_GET['id'] ?? null;
if ($id_campanha) {
    // Garantir que a coluna ds_dddice_room_slug existe no banco
    try {
        $stmtCheckCol = $pdo->query("SHOW COLUMNS FROM tb_campanha LIKE 'ds_dddice_room_slug'");
        if ($stmtCheckCol->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tb_campanha ADD COLUMN ds_dddice_room_slug VARCHAR(50) DEFAULT NULL");
        }
    } catch (Exception $e) {}

    $stmt = $pdo->prepare("
        SELECT c.*, s.nm_sistema, s.ds_background, s.ds_imagem AS ds_imagem_sistema
          FROM tb_campanha c
          LEFT JOIN tb_sistema s ON c.id_sistema = s.id_sistema
         WHERE c.id_campanha = ?
    ");
    $stmt->execute([$id_campanha]);
    $campanhaDados = $stmt->fetch();

    if ($campanhaDados) {
        if (isset($_SESSION['usuario']['id'])) {
            $isMaster = ((int)$campanhaDados['id_usuario_mestre'] === (int)$_SESSION['usuario']['id']);
        }

        // Se o slug estiver vazio, vamos gerar uma sala no dddice
        $roomSlug = $campanhaDados['ds_dddice_room_slug'] ?? '';
        if (empty($roomSlug)) {
            $ch = curl_init('https://dddice.com/api/1.0/room');
            $roomName = 'TABLE - ' . ($campanhaDados['nm_campanha'] ?? 'Campanha');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode(['name' => $roomName]),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . DDDICE_API_KEY,
                ],
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            $responseRoom = curl_exec($ch);
            $httpCodeRoom = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCodeRoom === 200 || $httpCodeRoom === 201) {
                $dataRoom = json_decode($responseRoom, true);
                $newSlug = $dataRoom['data']['slug'] ?? '';
                if (!empty($newSlug)) {
                    $roomSlug = $newSlug;
                    $campanhaDados['ds_dddice_room_slug'] = $newSlug; // Atualiza localmente
                    // Salvar no banco
                    $stmtUp = $pdo->prepare("UPDATE tb_campanha SET ds_dddice_room_slug = ? WHERE id_campanha = ?");
                    $stmtUp->execute([$newSlug, $id_campanha]);
                }
            }
        }
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.*, s.nm_sistema, p.id_usuario as id_dono, cp.fl_publico, u.nm_usuario as jogador_nome
              FROM tb_campanha_personagem cp
              JOIN tb_personagem p  ON cp.id_personagem = p.id_personagem
              LEFT JOIN tb_sistema s ON p.id_sistema = s.id_sistema
              LEFT JOIN tb_usuario u ON p.id_usuario = u.id_usuario
             WHERE cp.id_campanha = ?
        ");
        $stmt->execute([$id_campanha]);
        $PersonagemsCampanha = $stmt->fetchAll();

        foreach ($PersonagemsCampanha as &$Personagem) {
            $stmtAttr = $pdo->prepare("
                SELECT a.nm_atributo, a.ds_abreviacao, pa.qt_valor
                  FROM tb_personagem_atributo pa
                  JOIN tb_atributo a ON pa.id_atributo = a.id_atributo
                 WHERE pa.id_personagem = ?
            ");
            $stmtAttr->execute([$Personagem['id_personagem']]);
            $Personagem['atributos'] = $stmtAttr->fetchAll();

            // Buscar Classe para o Escudo
            $stmtCl = $pdo->prepare("SELECT c.nm_classe FROM tb_classe c JOIN tb_personagem_classe pc ON c.id_classe = pc.id_classe WHERE pc.id_personagem = ? LIMIT 1");
            $stmtCl->execute([$Personagem['id_personagem']]);
            $cl = $stmtCl->fetch();
            $Personagem['nm_classe'] = $cl ? $cl['nm_classe'] : 'Mundano';

            // Buscar Origem para o Escudo
            $stmtOr = $pdo->prepare("SELECT o.nm_origem FROM tb_origem o JOIN tb_personagem_origem po ON o.id_origem = po.id_origem WHERE po.id_personagem = ? LIMIT 1");
            $stmtOr->execute([$Personagem['id_personagem']]);
            $or = $stmtOr->fetch();
            $Personagem['nm_origem'] = $or ? $or['nm_origem'] : 'Cidadão';

            // Buscar Itens do Personagem (Inventário) para o Escudo
            $stmtItens = $pdo->prepare("
                SELECT i.*, pi.qt_quantidade
                FROM tb_personagem_item pi
                JOIN tb_item i ON pi.id_item = i.id_item
                WHERE pi.id_personagem = ?
            ");
            $stmtItens->execute([$Personagem['id_personagem']]);
            $Personagem['itens'] = $stmtItens->fetchAll();

            // Buscar Habilidades/Rituais/Poderes do Personagem para o Escudo
            $stmtHabs = $pdo->prepare("
                SELECT h.*
                FROM tb_habilidade_personagem hp
                JOIN tb_habilidade h ON hp.id_habilidade = h.id_habilidade
                WHERE hp.id_personagem = ?
            ");
            $stmtHabs->execute([$Personagem['id_personagem']]);
            $Personagem['habilidades'] = $stmtHabs->fetchAll();

            // Buscar Status Customizados do Sistema (Barras)
            $stmtStatus = $pdo->prepare("
                SELECT ss.*, COALESCE(ps.qt_valor_atual, 0) as qt_atual, COALESCE(ps.qt_valor_maximo, 0) as qt_max 
                FROM tb_sistema_status ss
                LEFT JOIN tb_personagem_status ps ON ss.id_status_sistema = ps.id_status_sistema AND ps.id_personagem = ?
                WHERE ss.id_sistema = ? AND ss.tp_status = 'barra'
            ");
            $stmtStatus->execute([$Personagem['id_personagem'], $Personagem['id_sistema']]);
            $Personagem['status_barras'] = $stmtStatus->fetchAll();

            // Fallback para Vida, Sanidade, Esforço caso o sistema não tenha barras customizadas
            if (empty($Personagem['status_barras'])) {
                $Personagem['status_barras'] = [
                    ['nm_status' => 'VIDA', 'ds_cor' => '#ed1c24', 'qt_atual' => (int)$Personagem['qt_vida'], 'qt_max' => (int)$Personagem['qt_vida_maxima'], 'id_status_sistema' => 'vida'],
                    ['nm_status' => 'SANIDADE', 'ds_cor' => '#a855f7', 'qt_atual' => (int)$Personagem['qt_sanidade'], 'qt_max' => (int)$Personagem['qt_sanidade_maxima'], 'id_status_sistema' => 'sanidade'],
                    ['nm_status' => 'ESFORÇO', 'ds_cor' => '#f97316', 'qt_atual' => (int)$Personagem['qt_esforco'], 'qt_max' => (int)$Personagem['qt_esforco_maximo'], 'id_status_sistema' => 'esforco']
                ];
            }

            // Buscar Defesas Customizadas do Sistema com valores do Personagem
            $stmtDefesas = $pdo->prepare("
                SELECT ss.*, COALESCE(ps.qt_valor_atual, 10) as qt_atual 
                FROM tb_sistema_status ss
                LEFT JOIN tb_personagem_status ps ON ss.id_status_sistema = ps.id_status_sistema AND ps.id_personagem = ?
                WHERE ss.id_sistema = ? AND ss.tp_status = 'defesa'
            ");
            $stmtDefesas->execute([$Personagem['id_personagem'], $Personagem['id_sistema']]);
            $Personagem['status_defesas'] = $stmtDefesas->fetchAll();

            // Fallback para Defesa, Bloqueio, Esquiva caso o sistema não tenha defesas customizadas
            if (empty($Personagem['status_defesas'])) {
                $Personagem['status_defesas'] = [
                    ['nm_status' => 'DEFESA', 'ds_cor' => '#95a5a6', 'qt_atual' => (int)($Personagem['qt_defesa'] ?: 10), 'id_status_sistema' => 'defesa'],
                    ['nm_status' => 'BLOQUEIO', 'ds_cor' => '#f39c12', 'qt_atual' => (int)$Personagem['qt_bloqueio'], 'id_status_sistema' => 'bloqueio'],
                    ['nm_status' => 'ESQUIVA', 'ds_cor' => '#2980b9', 'qt_atual' => (int)($Personagem['qt_esquiva'] ?: ($Personagem['qt_defesa'] ?: 10)), 'id_status_sistema' => 'esquiva']
                ];
            }
        }
        unset($Personagem);

        $stmtSisAttr = $pdo->prepare("SELECT * FROM tb_atributo WHERE id_sistema = ? ORDER BY id_atributo ASC");
        $stmtSisAttr->execute([$campanhaDados['id_sistema']]);
        $atributosSistema = $stmtSisAttr->fetchAll();

        // Buscar status de barras e defesas globais do sistema da campanha
        $stmtSisStatus = $pdo->prepare("SELECT * FROM tb_sistema_status WHERE id_sistema = ? AND tp_status = 'barra'");
        $stmtSisStatus->execute([$campanhaDados['id_sistema']]);
        $sistemaStatusBarras = $stmtSisStatus->fetchAll();

        $stmtSisStatusDef = $pdo->prepare("SELECT * FROM tb_sistema_status WHERE id_sistema = ? AND tp_status = 'defesa'");
        $stmtSisStatusDef->execute([$campanhaDados['id_sistema']]);
        $sistemaStatusDefesas = $stmtSisStatusDef->fetchAll();

        $stmt = $pdo->prepare("
            SELECT c.*,
                   (SELECT SUM(m.qt_vd * cm.qt_quantidade)
                      FROM tb_monstro m
                      JOIN tb_combate_monstro cm ON m.id_monstro = cm.id_monstro
                     WHERE cm.id_combate = c.id_combate) AS vd_total
              FROM tb_combate c WHERE c.id_campanha = ?
        ");
        $stmt->execute([$id_campanha]);
        $combatesCampanha = $stmt->fetchAll();

        foreach ($combatesCampanha as &$comb) {
            $stmtM = $pdo->prepare("
                SELECT m.*, s.ds_imagem AS ds_imagem_sistema
                FROM tb_monstro m
                JOIN tb_combate_monstro cm ON m.id_monstro = cm.id_monstro
                LEFT JOIN tb_sistema s ON m.id_sistema = s.id_sistema
                WHERE cm.id_combate = ?
            ");
            $stmtM->execute([$comb['id_combate']]);
            $monstros = $stmtM->fetchAll();

            foreach ($monstros as &$m) {
                $stmtMonAttr = $pdo->prepare("
                    SELECT a.id_atributo, a.nm_atributo, a.ds_abreviacao, COALESCE(ma.qt_valor, 0) as qt_valor 
                    FROM tb_atributo a
                    LEFT JOIN tb_monstro_atributo ma ON a.id_atributo = ma.id_atributo AND ma.id_monstro = ?
                    WHERE a.id_sistema = ?
                    ORDER BY a.id_atributo ASC
                ");
                $stmtMonAttr->execute([$m['id_monstro'], $m['id_sistema']]);
                $m['atributos'] = $stmtMonAttr->fetchAll();
            }
            unset($m);
            $comb['monstros'] = $monstros;
        }
        unset($comb);

        // Buscar participantes da campanha (Mestre + Jogadores) e seus personagens vinculados
        $stmtJog = $pdo->prepare("
            SELECT DISTINCT 
                   u.id_usuario, 
                   u.nm_exibicao, 
                   u.nm_usuario, 
                   u.ds_foto as foto_usuario,
                   u.ds_email,
                   u.dt_nascimento,
                   u.ds_bio,
                   CASE WHEN u.id_usuario = c.id_usuario_mestre THEN 'mestre' ELSE 'jogador' END as papel_campanha,
                   p.id_personagem, 
                   p.nm_personagem, 
                   p.ds_foto as foto_personagem,
                   cp.fl_publico
              FROM tb_usuario u
              JOIN tb_campanha c ON c.id_campanha = ?
              LEFT JOIN tb_campanha_usuario cu ON u.id_usuario = cu.id_usuario AND cu.id_campanha = c.id_campanha
              LEFT JOIN tb_personagem p ON p.id_usuario = u.id_usuario AND p.fl_ativo = 1 AND p.id_personagem IN (
                  SELECT cp2.id_personagem FROM tb_campanha_personagem cp2 WHERE cp2.id_campanha = c.id_campanha
              )
              LEFT JOIN tb_campanha_personagem cp ON cp.id_personagem = p.id_personagem AND cp.id_campanha = c.id_campanha
             WHERE u.id_usuario = c.id_usuario_mestre OR cu.id_usuario IS NOT NULL
        ");
        $stmtJog->execute([$id_campanha]);
        $jogadoresCampanha = $stmtJog->fetchAll(PDO::FETCH_ASSOC);

        $jogadoresAgrupados = [];
        foreach ($jogadoresCampanha as $row) {
            $uid = (int)$row['id_usuario'];
            if (!isset($jogadoresAgrupados[$uid])) {
                $jogadoresAgrupados[$uid] = [
                    'id_usuario'     => $row['id_usuario'],
                    'nm_exibicao'    => $row['nm_exibicao'],
                    'nm_usuario'     => $row['nm_usuario'],
                    'foto_usuario'   => $row['foto_usuario'],
                    'ds_email'       => $row['ds_email'],
                    'dt_nascimento'  => $row['dt_nascimento'],
                    'ds_bio'         => $row['ds_bio'],
                    'papel_campanha' => $row['papel_campanha'],
                    'personagens'    => []
                ];
            }
            if (!empty($row['id_personagem'])) {
                $jogadoresAgrupados[$uid]['personagens'][] = [
                    'id_personagem'   => $row['id_personagem'],
                    'nm_personagem'   => $row['nm_personagem'],
                    'foto_personagem' => $row['foto_personagem'],
                    'fl_publico'      => $row['fl_publico'] ?? 0
                ];
            }
        }

        // Mapear username/display_name para o nome do personagem ativo dele na campanha
        $mapaParticipantesPersonagens = [];
        foreach ($jogadoresAgrupados as $uid => $jog) {
            $personagemNome = '';
            if (!empty($jog['personagens'])) {
                $personagemNome = $jog['personagens'][0]['nm_personagem'];
            }
            $mapaParticipantesPersonagens[strtolower(trim($jog['nm_usuario']))] = $personagemNome;
            $mapaParticipantesPersonagens[strtolower(trim($jog['nm_exibicao']))] = $personagemNome;
        }
    }
}

// ------------------------------------------------------------------
// Lógica de Permissão e Customização Visual (Background)
// ------------------------------------------------------------------
$isMaster = false;
$classeBackground = '';

if ($campanhaDados) {
    $isMaster = ((int)$campanhaDados['id_usuario_mestre'] === (int)$_SESSION['usuario']['id']);
    
    // Tutorial: Para adicionar novos temas, basta adicionar um "case" ou "if" 
    // verificando o nome do sistema e definindo uma classe CSS correspondente.
    $nomeSistemaLower = strtolower($campanhaDados['nm_sistema'] ?? '');
    if (strpos($nomeSistemaLower, 'ordem paranormal') !== false) {
        $classeBackground = 'tema-ordem-paranormal';
    }
}
$isOrdemParanormal = ($campanhaDados && strpos(strtolower($campanhaDados['nm_sistema'] ?? ''), 'ordem paranormal') !== false);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | <?= $campanhaDados ? htmlspecialchars($campanhaDados['nm_campanha']) : 'Nova Campanha' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="../img/logo_branco1.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/criar-campanha.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../css/table-modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- SDK dddice para animação 3D no Escudo do Mestre -->
    <script>
        var exports = {};
        window.isOrdemParanormal = <?= $isOrdemParanormal ? 'true' : 'false' ?>;
    </script>
    <script src="https://cdn.dddice.com/js/dddice-latest.js"></script>
    <script src="../js/table-modal.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script src="../js/cropper-helper.js"></script>
    <style>
        /* ── CORREÇÕES DE DESIGN - EXPANSÃO DAS FICHAS NO ESCUDO ── */
        .card-agente-compacto {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
            overflow: hidden !important;
            max-height: 600px !important;
            cursor: pointer !important;
        }
        .card-agente-compacto.recolhido {
            max-height: 110px !important;
            border-color: rgba(255,255,255,0.06) !important;
        }
        .card-agente-compacto.recolhido .toggle-escudo-ficha {
            transform: rotate(0deg) !important;
        }
        .card-agente-compacto:not(.recolhido) .toggle-escudo-ficha {
            transform: rotate(180deg) !important;
            color: var(--premium-accent) !important;
        }
        .card-agente-compacto.recolhido .atributos-agente-p1,
        .card-agente-compacto.recolhido .status-bars-p1,
        .card-agente-compacto.recolhido .compacto-footer {
            opacity: 0 !important;
            pointer-events: none !important;
            transition: opacity 0.2s ease !important;
        }
        .card-agente-compacto:not(.recolhido) .atributos-agente-p1,
        .card-agente-compacto:not(.recolhido) .status-bars-p1,
        .card-agente-compacto:not(.recolhido) .compacto-footer {
            opacity: 1 !important;
            transition: opacity 0.4s ease 0.1s !important;
        }

        /* ── CANVAS dddice — fullscreen overlay durante a animação ── */
        #dddice-canvas-escudo {
            position: fixed;
            inset: 0;
            width: 100vw;
            height: 100vh;
            display: block;
            z-index: 8000;
            pointer-events: none;
        }

        /* ── POP-UP DE RESULTADO (escudo) ── */
        #escudo-result-popup {
            position: fixed;
            inset: 0;
            z-index: 8500;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        #escudo-result-popup.show { opacity: 1; pointer-events: auto; }

        #escudo-result-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(6px);
            opacity: 0;
            transition: opacity 0.3s;
        }
        #escudo-result-popup.show #escudo-result-backdrop { opacity: 1; }

        #escudo-result-card {
            position: relative;
            background: linear-gradient(155deg, #0a0610 0%, #11091c 100%);
            border: 2px solid var(--premium-accent);
            border-radius: 28px;
            padding: 60px 85px 50px;
            text-align: center;
            box-shadow: 0 0 90px rgba(139,92,246,0.35), 0 32px 100px rgba(0,0,0,0.9);
            transform: scale(0.82) translateY(18px);
            transition: transform 0.38s cubic-bezier(0.34,1.56,0.64,1);
            min-width: 550px;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            #escudo-result-card {
                padding: 40px 25px;
                min-width: 90%;
            }
            #escudo-result-total {
                font-size: 5.5rem !important;
            }
        }
        #escudo-result-popup.show #escudo-result-card { transform: scale(1) translateY(0); }

        #escudo-result-label {
            font-size: 0.8rem;
            letter-spacing: 5px;
            color: var(--premium-accent);
            text-transform: uppercase;
            margin-bottom: 15px;
            font-weight: 800;
        }

        #escudo-result-total {
            font-size: 8.5rem;
            font-weight: 950;
            line-height: 0.95;
            color: #fff;
            text-shadow: 0 0 45px rgba(139,92,246,0.85), 0 0 90px rgba(139,92,246,0.5);
            margin-bottom: 20px;
        }

        @keyframes escudo-pop-in {
            0%   { transform: scale(0.4); opacity: 0; }
            65%  { transform: scale(1.12); }
            100% { transform: scale(1); opacity: 1; }
        }
        #escudo-result-total.pop { animation: escudo-pop-in 0.45s cubic-bezier(0.34,1.56,0.64,1) forwards; }

        #escudo-result-breakdown {
            font-size: 1.7rem;
            color: #a78bfa;
            letter-spacing: 1.5px;
            margin-top: 25px;
            margin-bottom: 25px;
            min-height: 35px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        #escudo-result-breakdown .val { 
            color: #ffffff; 
            font-weight: 800; 
            background: rgba(255, 255, 255, 0.05); 
            border: 1px solid rgba(255, 255, 255, 0.15); 
            padding: 6px 14px; 
            border-radius: 10px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            text-shadow: 0 0 10px rgba(255,255,255,0.4); 
        }
        #escudo-result-breakdown .op  { 
            color: var(--premium-accent); 
            font-weight: 900; 
            font-size: 1.8rem;
            margin: 0 4px;
        }
        #escudo-result-dismiss {
            margin-top: 30px;
            font-size: 0.65rem;
            color: #666;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-family: 'Cinzel', serif;
        }

        /* ── BOLINHA CONTADORA na sessao-escudo ── */
        .escudo-bolinha {
            position: absolute;
            top: -14px;
            right: -14px;
            width: 38px;
            height: 38px;
            background-color: var(--premium-accent);
            color: #fff;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 900;
            font-size: 0.9rem;
            box-shadow: 0 2px 10px rgba(139,92,246,0.6);
            opacity: 0;
            transform: scale(0);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 5;
            pointer-events: none;
        }
        .escudo-bolinha.show { opacity: 1; transform: scale(1); }

        #escudo-tab-dados .item-dado {
            position: relative;
            cursor: pointer;
        }
        #escudo-tab-dados .item-dado.selecionado .dado-icon-container {
            filter: drop-shadow(0 0 10px var(--premium-accent));
        }

        /* Seletor de tema */
        .escudo-tema-row {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 10px 14px;
            margin-bottom: 18px;
        }
        .escudo-tema-row label {
            font-size: 0.7rem;
            font-weight: 700;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            white-space: nowrap;
        }
        #escudo-theme-select {
            flex: 1;
            background: transparent;
            border: none;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.82rem;
            outline: none;
            cursor: pointer;
        }
        #escudo-theme-select option { background: #0d091a; }

        #escudo-status-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #e74c3c; flex-shrink: 0;
            transition: background 0.3s, box-shadow 0.3s;
        }
        #escudo-status-dot.ok      { background: #2ecc71; box-shadow: 0 0 7px #2ecc71; }
        #escudo-status-dot.loading { background: var(--premium-accent); animation: blink 0.9s infinite; }
        #escudo-status-dot.local   { background: #3498db; box-shadow: 0 0 7px #3498db; }
        #escudo-status-dot.error   { background: #e74c3c; box-shadow: 0 0 7px #e74c3c; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.15} }

        /* Resumo da seleção */
        .escudo-sel-resumo {
            background: rgba(139,92,246,0.07);
            border: 1px solid rgba(139,92,246,0.15);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.78rem;
            color: #666;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            min-height: 38px;
        }
        .escudo-sel-resumo .chip {
            background: rgba(139,92,246,0.2);
            border: 1px solid rgba(139,92,246,0.35);
            border-radius: 20px;
            padding: 2px 10px;
            font-weight: 700;
            color: #c4b5fd;
            font-size: 0.76rem;
        }
        .btn-escudo-limpar-sel {
            margin-left: auto;
            background: none;
            border: 1px solid rgba(255,255,255,0.08);
            color: #555;
            border-radius: 6px;
            padding: 3px 8px;
            font-size: 0.68rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-escudo-limpar-sel:hover { border-color: #ff4d4d; color: #ff4d4d; }

        /* Botão rolar */
        .btn-escudo-rolar {
            width: 100%;
            background: linear-gradient(135deg, #5b21b6, #8b5cf6);
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 8px 20px rgba(91,33,182,0.4);
            margin-top: 8px;
        }
        .btn-escudo-rolar:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(91,33,182,0.6);
            filter: brightness(1.1);
        }
        .btn-escudo-rolar:disabled { opacity: 0.35; cursor: not-allowed; transform: none; }

        /* Toast */
        #escudo-toast {
            position: fixed;
            bottom: 28px;
            left: 50%;
            transform: translateX(-50%) translateY(16px);
            background: #1e0808;
            border: 1px solid #c0392b;
            border-radius: 10px;
            padding: 11px 22px;
            font-size: 0.82rem;
            color: #f1948a;
            z-index: 9999;
            opacity: 0;
            transition: all 0.28s;
            pointer-events: none;
        }
        #escudo-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

        /* Card jogador clicável premium */
        .card-jogador-clicavel {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        }
        .card-jogador-clicavel:hover {
            transform: translateY(-4px) scale(1.01);
            background: rgba(139, 92, 246, 0.08) !important;
            border-color: rgba(139, 92, 246, 0.3) !important;
            box-shadow: 0 12px 30px rgba(0,0,0,0.5), 0 0 20px rgba(139, 92, 246, 0.15) !important;
        }
        body.tema-ordem-paranormal .card-jogador-clicavel:hover {
            background: rgba(255, 50, 50, 0.08) !important;
            border-color: rgba(255, 50, 50, 0.3) !important;
            box-shadow: 0 12px 30px rgba(0,0,0,0.5), 0 0 20px rgba(255, 50, 50, 0.15) !important;
        }
    </style>
    <style>
        .sistema-showcase { margin-bottom:30px; background:rgba(255,255,255,.05); border-radius:15px; padding:25px; border:1px solid rgba(255,255,255,.1); animation:slideDown .4s ease-out; }
        @keyframes slideDown { from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:translateY(0)} }
        .system-hero { display:flex; gap:20px; margin-bottom:25px; align-items:center; }
        .system-img  { width:120px; height:120px; background:#222; border-radius:12px; object-fit:cover; border:2px solid var(--cor-destaque-claro); }
        .system-text h2 { font-size:1.8rem; color:var(--cor-destaque-claro); margin-bottom:8px; }
        .system-text p  { font-size:.95rem; color:#ccc; line-height:1.4; }
        .btn-criar-campanha:disabled { opacity:.5; cursor:not-allowed; }

        .item-dado { cursor:pointer; transition:all .3s ease; }
        .dado-icon-container { width:50px; height:50px; display:flex; align-items:center; justify-content:center; margin:0 auto 10px; transition:transform .3s cubic-bezier(.175,.885,.32,1.275); }
        .img-dado { width:100%; height:100%; object-fit:contain; filter:drop-shadow(0 4px 8px rgba(0,0,0,.5)); }
        .item-dado:hover .dado-icon-container { transform:scale(1.15) rotate(5deg); }
        .dado-girando { animation:girarDado .6s ease-in-out; }
        @keyframes girarDado {
            0%{transform:rotate(0deg) scale(1)} 25%{transform:rotate(90deg) scale(1.3) translateY(-5px)}
            50%{transform:rotate(180deg) scale(1)} 75%{transform:rotate(270deg) scale(1.3) translateY(-5px)}
            100%{transform:rotate(360deg) scale(1)}
        }
        .hexa-dado { color:#000 !important; font-weight:800; }

        .popup-dados { position:fixed !important; top:50% !important; left:50% !important; transform:translate(-50%,-50%) !important; width:90% !important; max-width:380px !important; background:#110e1a !important; border:2px solid var(--premium-accent) !important; border-radius:24px !important; box-shadow:0 20px 60px rgba(0,0,0,.9) !important; z-index:10001 !important; padding:35px !important; animation:modalPop .3s cubic-bezier(.175,.885,.32,1.275); }
        @keyframes modalPop { from{opacity:0;transform:translate(-50%,-40%) scale(.9)} to{opacity:1;transform:translate(-50%,-50%) scale(1)} }
        .popup-overlay { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.8); backdrop-filter:blur(8px); z-index:10000; display:none; }
        .btn-confirmar-rolagem { width:100%; background:var(--premium-accent); color:#fff; border:none; padding:12px; border-radius:12px; font-weight:700; margin-top:20px; cursor:pointer; transition:all .3s; text-transform:uppercase; letter-spacing:1px; }
        .btn-confirmar-rolagem:hover { filter:brightness(1.2); transform:translateY(-2px); }

        .card-ameaca-premium { background:linear-gradient(90deg,rgba(30,10,10,.9) 0%,rgba(60,20,20,.4) 100%); border:1px solid rgba(255,50,50,.2); border-radius:12px; display:flex; align-items:center; padding:10px; gap:15px; margin-bottom:12px; position:relative; overflow:hidden; text-align:left; }
        .card-ameaca-premium::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background:#ff3232; box-shadow:0 0 10px #ff3232; }
        .card-ameaca-img { width:70px; height:70px; border-radius:8px; object-fit:cover; border:1px solid rgba(255,255,255,.1); background:#111; }
        .card-ameaca-body { flex:1; }
        .card-ameaca-body h4 { color:#fff; font-weight:800; font-size:1rem; margin-bottom:2px; }
        .card-ameaca-details { display:flex; flex-direction:column; }
        .card-ameaca-details span { font-size:.75rem; color:#ccc; font-weight:600; }
        .card-ameaca-details b { color:#ff4d4d; }
        .card-ameaca-actions { display:flex; gap:8px; }
        .lista-ameacas-cards { display:flex; flex-direction:column; gap:5px; }

        /* Vitrine de Sistema Estilo Clean (Sem Blocos) */
        .sistema-showcase-clean {
            padding: 10px 0;
            margin-bottom: 30px;
            animation: fadeIn 0.4s ease;
        }

        .sistema-clean-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .cartaz-sistema-clean {
            width: 150px;
            height: 85px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
            flex-shrink: 0;
        }

        .sistema-clean-header h2 {
            font-size: 2.5rem;
            font-weight: 900;
            color: #fff;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: -1px;
        }

        .sistema-clean-descricao {
            font-size: 1.05rem;
            color: #ccc;
            line-height: 1.6;
            margin: 0;
            max-width: 900px;
            max-height: 200px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .sistema-clean-descricao::-webkit-scrollbar {
            width: 6px;
        }
        .sistema-clean-descricao::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }
        .sistema-clean-descricao::-webkit-scrollbar-thumb {
            background: var(--premium-accent, #8b5cf6);
            border-radius: 4px;
        }


        @media (max-width: 768px) {
            .sistema-clean-header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            .cartaz-sistema-clean {
                width: 100%;
                height: 180px;
            }
            .sistema-clean-header h2 {
                font-size: 1.8rem;
            }
            .sistema-clean-descricao {
                text-align: center;
                font-size: 0.95rem;
            }
        }

        /* TEMAS DINÂMICOS - PREMIUM EXPERIENCE */
        /* Tema: Ordem Paranormal */
        /* Forçar Cinzel em TUDO no tema Ordem Paranormal, EXCETO ícones */
        body.tema-ordem-paranormal,
        body.tema-ordem-paranormal *:not(i):not([class^="fa-"]):not([class*=" fa-"]) {
            font-family: 'Cinzel', serif !important;
            font-optical-sizing: auto !important;
        }

        body.tema-ordem-paranormal {
            --premium-accent: #ff3232 !important;
            --cor-destaque-claro: #ff4d4d !important;
            --fundo-cartao-escuro: rgba(10, 5, 5, 0.98) !important;
            --cor-primaria: #ff3232 !important;
            
            background: #050202 !important;
            color: #fff !important;
            letter-spacing: 1px;
        }

        /* Glow e Estilo para Títulos/Botões */
        body.tema-ordem-paranormal h1, 
        body.tema-ordem-paranormal h2, 
        body.tema-ordem-paranormal h3, 
        body.tema-ordem-paranormal h4,
        body.tema-ordem-paranormal .btn-escudo-rolar,
        body.tema-ordem-paranormal .btn-criar-campanha,
        body.tema-ordem-paranormal .btn-confirmar-rolagem {
            font-weight: 700 !important;
            color: white !important;
            text-shadow: 0 0 10px rgba(255,255,255,0.8) !important;
            letter-spacing: 2px !important;
        }

        /* Background Dinâmico - Posicionado debaixo da navbar */
        body.tema-ordem-paranormal::before {
            content: '';
            position: fixed;
            top: 80px; /* Debaixo da navbar */
            left: 0; 
            width: 100%; 
            height: calc(100vh - 80px);
            background: radial-gradient(circle at center, transparent 0%, #000 90%),
                        var(--tema-background, url('../img/ordem_paranormal_background.webp')) center/cover no-repeat;
            opacity: 0.55;
            z-index: -1;
            pointer-events: none;
            filter: grayscale(0.2) contrast(1.1);
        }
    
        /* Borda da foto de capa e sistema */
        body.tema-ordem-paranormal .banner-campanha,
        body.tema-ordem-paranormal .img-sistema-grande {
            border: 3px solid #ff3232 !important;
            box-shadow: 0 0 25px rgba(255, 50, 50, 0.5) !important;
        }

        /* Scrollbars Temáticas - Sangue e Escuridão */
        body.tema-ordem-paranormal::-webkit-scrollbar,
        body.tema-ordem-paranormal *::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        body.tema-ordem-paranormal::-webkit-scrollbar-track,
        body.tema-ordem-paranormal *::-webkit-scrollbar-track {
            background: #0a0606;
        }
        body.tema-ordem-paranormal::-webkit-scrollbar-thumb,
        body.tema-ordem-paranormal *::-webkit-scrollbar-thumb {
            background: #8b0000;
            border-radius: 10px;
            border: 2px solid #0a0606;
            transition: background 0.3s;
        }
        body.tema-ordem-paranormal::-webkit-scrollbar-thumb:hover,
        body.tema-ordem-paranormal *::-webkit-scrollbar-thumb:hover {
            background: #ff3232;
        }

        /* Overrides de UI Elements */
        body.tema-ordem-paranormal .btn-escudo-rolar,
        body.tema-ordem-paranormal .btn-criar-campanha,
        body.tema-ordem-paranormal .btn-confirmar-rolagem {
            background: linear-gradient(135deg, #660000 0%, #ff3232 100%) !important;
            box-shadow: 0 8px 25px rgba(255, 50, 50, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        body.tema-ordem-paranormal .btn-escudo-rolar:hover,
        body.tema-ordem-paranormal .btn-criar-campanha:hover {
            box-shadow: 0 10px 30px rgba(255, 50, 50, 0.4) !important;
            filter: brightness(1.2);
        }

        body.tema-ordem-paranormal .card-formulario-campanha,
        body.tema-ordem-paranormal .modal-box {
            border: 1px solid rgba(255, 50, 50, 0.15) !important;
            box-shadow: 0 20px 60px rgba(0,0,0,0.9), inset 0 0 40px rgba(255, 50, 50, 0.05) !important;
        }

        body.tema-ordem-paranormal .input-campanha,
        body.tema-ordem-paranormal .textarea-campanha,
        body.tema-ordem-paranormal .editor-container,
        body.tema-ordem-paranormal .editor-toolbar {
            background: rgba(20, 10, 10, 0.8) !important;
            border-color: rgba(255, 50, 50, 0.2) !important;
        }

        body.tema-ordem-paranormal .input-campanha:focus {
            border-color: #ff3232 !important;
            box-shadow: 0 0 15px rgba(255, 50, 50, 0.2) !important;
        }

        body.tema-ordem-paranormal .campanha-info-wrapper,
        body.tema-ordem-paranormal .card-Personagem {
            background: rgba(15, 5, 5, 0.6) !important;
            border: 1px solid rgba(255, 50, 50, 0.1) !important;
            backdrop-filter: blur(5px);
        }

        body.tema-ordem-paranormal .descricao-campanha-display {
            border-left-color: #ff3232 !important;
        }

        body.tema-ordem-paranormal .btn-ver-ficha:hover {
            background-color: #ff3232 !important;
            color: #fff !important;
            border-color: #ff3232 !important;
        }

        body.tema-ordem-paranormal .escudo-bolinha {
            background-color: #ff3232 !important;
            box-shadow: 0 0 15px rgba(255, 50, 50, 0.6) !important;
        }

        body.tema-ordem-paranormal #escudo-result-card {
            border-color: #ff3232 !important;
            box-shadow: 0 0 80px rgba(255, 50, 50, 0.2) !important;
        }

        body.tema-ordem-paranormal #escudo-result-total {
            text-shadow: 0 0 35px rgba(255, 50, 50, 0.8) !important;
        }

        body.tema-ordem-paranormal .btn-acao.especial {
            border-color: #ff3232 !important;
            color: #ff3232 !important;
            background: rgba(255, 50, 50, 0.1) !important;
        }

        body.tema-ordem-paranormal .btn-acao.especial:hover {
            background: #ff3232 !important;
            color: #fff !important;
        }

        body.tema-ordem-paranormal .header-sistema-premium {
            background: linear-gradient(to right, rgba(20, 5, 5, 0.95), rgba(255, 50, 50, 0.1)) !important;
            border-color: rgba(255, 50, 50, 0.2) !important;
        }

        /* ── ABA DADOS DO JOGADOR — Layout fiel ao rolagem-de-dados.php ── */
        .camp-dados-container {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            margin-top: 20px;
        }
        @media (max-width: 900px) {
            .camp-dados-container { grid-template-columns: 1fr; }
        }
        .camp-dados-grid-box {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(10px);
        }
        .camp-titulo-secao {
            font-family: 'Cinzel', serif;
            font-size: 1.8rem;
            margin-bottom: 10px;
            text-align: center;
            color: #fff;
            text-shadow: 0 0 15px rgba(139, 92, 246, 0.4);
        }
        .camp-tema-row {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px;
            padding: 12px 16px;
        }
        .camp-tema-row label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            white-space: nowrap;
        }
        #camp-theme-select {
            flex: 1;
            background: transparent;
            border: none;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.85rem;
            outline: none;
            cursor: pointer;
        }
        #camp-theme-select option { background: #0d091a; }
        #camp-status-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #e74c3c; flex-shrink: 0;
            transition: background 0.3s, box-shadow 0.3s;
        }
        #camp-status-dot.ok      { background: #2ecc71; box-shadow: 0 0 7px #2ecc71; }
        #camp-status-dot.loading { background: var(--premium-accent); animation: blink 0.9s infinite; }
        #camp-status-dot.local   { background: #3498db; box-shadow: 0 0 7px #3498db; }
        #camp-status-dot.error   { background: #e74c3c; box-shadow: 0 0 7px #e74c3c; }
        .camp-grid-dados {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .camp-item-dado {
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
        }
        .camp-item-dado .dado-icon-container {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }
        .camp-item-dado:hover .dado-icon-container { transform: scale(1.15) rotate(5deg); }
        .camp-item-dado.selecionado .dado-icon-container {
            filter: drop-shadow(0 0 12px var(--premium-accent));
        }
        .camp-item-dado .label-dado {
            font-weight: 800;
            font-size: 0.9rem;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .camp-bolinha {
            position: absolute;
            top: -12px; right: -12px;
            width: 36px; height: 36px;
            background-color: var(--premium-accent);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 900;
            font-size: 0.85rem;
            box-shadow: 0 2px 8px rgba(139,92,246,0.6);
            opacity: 0;
            transform: scale(0);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 5;
            pointer-events: none;
        }
        .camp-bolinha.show { opacity: 1; transform: scale(1); }
        .camp-sel-resumo {
            background: rgba(139, 92, 246, 0.08);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 12px;
            padding: 12px 18px;
            font-size: 0.8rem;
            color: #aaa;
            margin-bottom: 20px;
            min-height: 40px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .camp-sel-resumo .chip {
            background: rgba(139,92,246,0.2);
            border: 1px solid rgba(139,92,246,0.4);
            border-radius: 20px;
            padding: 3px 10px;
            font-weight: 700;
            color: #c4b5fd;
            font-size: 0.8rem;
        }
        .camp-btn-limpar {
            margin-left: auto;
            background: none;
            border: 1px solid rgba(255,255,255,0.1);
            color: #666;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 0.72rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .camp-btn-limpar:hover { border-color: #ff4d4d; color: #ff4d4d; }
        .camp-btn-rolar {
            width: 100%;
            background: linear-gradient(135deg, #6d28d9, #8b5cf6);
            color: #fff;
            border: none;
            padding: 18px;
            border-radius: 15px;
            font-weight: 800;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 20px rgba(109, 40, 217, 0.3);
        }
        .camp-btn-rolar:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(109, 40, 217, 0.5);
            filter: brightness(1.1);
        }
        .camp-btn-rolar:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }
        .camp-historico-box {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 30px;
            display: flex;
            flex-direction: column;
            max-height: 600px;
            box-sizing: border-box;
        }
        .camp-historico-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .camp-historico-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #fff;
        }
        .camp-btn-limpar-log {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 0.8rem;
            transition: color 0.3s;
        }
        .camp-btn-limpar-log:hover { color: #ff4d4d; }
        #camp-historico-lista {
            overflow-y: auto;
            flex: 1;
            padding-right: 10px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        #camp-historico-lista::-webkit-scrollbar { width: 5px; }
        #camp-historico-lista::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); }
        #camp-historico-lista::-webkit-scrollbar-thumb { background: var(--premium-accent); border-radius: 10px; }
        .camp-log-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 15px;
            border-left: 3px solid var(--premium-accent);
            animation: campSlideIn 0.3s ease-out;
        }
        @keyframes campSlideIn {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        .camp-log-resultado {
            width: 45px; height: 45px;
            background: #fff;
            color: #000;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.2rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        .camp-log-info p { margin: 0; font-size: 0.75rem; color: #888; }
        .camp-log-info h4 { margin: 2px 0 0; font-size: 0.9rem; color: #fff; }
        /* Tema Ordem Paranormal overrides */
        body.tema-ordem-paranormal .camp-dados-grid-box {
            border-color: rgba(255, 50, 50, 0.15) !important;
            background: rgba(15, 5, 5, 0.6) !important;
        }
        body.tema-ordem-paranormal .camp-historico-box {
            border-color: rgba(255, 50, 50, 0.15) !important;
            background: rgba(10, 5, 5, 0.8) !important;
        }
        body.tema-ordem-paranormal .camp-log-item {
            border-left-color: #ff3232 !important;
        }
        body.tema-ordem-paranormal .camp-btn-rolar {
            background: linear-gradient(135deg, #660000 0%, #ff3232 100%) !important;
        }
        body.tema-ordem-paranormal #camp-status-dot.ok { background: #2ecc71; box-shadow: 0 0 7px #2ecc71; }
        body.tema-ordem-paranormal #camp-status-dot.loading { background: #ff3232; }
        .camp-item-dado .img-dado {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.5));
        }
        .dado-girando { animation: girarDado 0.6s ease-in-out; }
        @keyframes girarDado {
            0%   { transform: rotate(0deg) scale(1); }
            25%  { transform: rotate(90deg) scale(1.3) translateY(-5px); }
            50%  { transform: rotate(180deg) scale(1); }
            75%  { transform: rotate(270deg) scale(1.3) translateY(-5px); }
            100% { transform: rotate(360deg) scale(1); }
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.15} }

        /* Atributos e Tooltips da Ficha Premium de Ameaças */
        .premium-atributos-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-bottom: 25px;
        }
        .premium-attr-box {
            position: relative;
            display: flex;
            align-items: stretch;
            border-radius: 12px;
            overflow: visible !important;
            height: 50px;
            filter: drop-shadow(0 5px 10px rgba(0, 0, 0, 0.4));
            margin-bottom: 10px;
            cursor: help;
        }
        .premium-attr-box .attr-abbr {
            background: #fff;
            color: #1e0b3a;
            width: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 0.85rem;
            text-transform: uppercase;
            border-radius: 12px 0 0 12px;
            cursor: help;
        }
        .premium-attr-box .attr-circle {
            background: rgba(255, 255, 255, 0.02);
            border: 3px solid #fff;
            border-left: none;
            color: #fff;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 950;
            border-radius: 0 12px 12px 0;
            transition: all 0.3s;
        }
        .premium-attr-box .tooltip {
            visibility: hidden;
            background: var(--premium-accent);
            color: #fff;
            text-align: center;
            padding: 5px 10px;
            border-radius: 6px;
            position: absolute;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
            pointer-events: none;
            left: 50%;
            bottom: 135%;
            top: auto;
            transform: translateX(-50%) translateY(10px) scale(0.9);
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }
        .premium-attr-box .tooltip::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -6px;
            border-width: 6px;
            border-style: solid;
            border-color: var(--premium-accent) transparent transparent transparent;
        }
        .premium-attr-box .attr-abbr:hover ~ .tooltip {
            visibility: visible;
            opacity: 1;
            transform: translateX(-50%) translateY(0) scale(1);
        }
        
        /* Loader de Tela Cheia */
        #fullscreen-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #0d091a;
            z-index: 100000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }

        #fullscreen-loader .spinner {
            width: 50px;
            height: 50px;
            border: 3.5px solid rgba(139, 92, 246, 0.1);
            border-left-color: #8b5cf6;
            border-radius: 50%;
            animation: loader-spin 1s linear infinite;
        }

        #fullscreen-loader p {
            margin-top: 15px;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 1.5px;
        }

        @keyframes loader-spin {
            to { transform: rotate(360deg); }
        }

    </style>
</head>
<?php
$estiloBackground = '';
if ($campanhaDados && !empty($campanhaDados['ds_background'])) {
    $bg = htmlspecialchars($campanhaDados['ds_background']);
    $estiloBackground = "style=\"background-image: linear-gradient(rgba(0,0,0,0.85), rgba(0,0,0,0.85)), url('$bg'); background-size: cover; background-position: center; background-attachment: fixed;\"";
}
?>
<body class="body-criar-campanha <?= $classeBackground ?>" <?= $estiloBackground ?>>

    <!-- Loader de Tela Cheia -->
    <div id="fullscreen-loader">
        <div class="spinner"></div>
        <p>Carregando para Campanha...</p>
    </div>

    <!-- Canvas dddice — fullscreen, sobrepõe a tela durante a animação do Escudo -->
    <canvas id="dddice-canvas-escudo"></canvas>

    <!-- Pop-up de resultado do Escudo do Mestre -->
    <div id="escudo-result-popup" onclick="fecharResultadoEscudo()">
        <div id="escudo-result-backdrop"></div>
        <div id="escudo-result-card" onclick="event.stopPropagation()">
            <div id="escudo-result-label">Resultado</div>
            <div id="escudo-result-total">—</div>
            <div id="escudo-result-breakdown"></div>
            <div id="escudo-result-dismiss">Clique para fechar · ESC</div>
        </div>
    </div>

    <header>
        <div class="logotipo">
            <a href="index.php"><img src="../img/logo_horizontal1.png" alt="Logo TABLE"></a>
        </div>

        <!-- BOTÃO MENU MOBILE (HAMBURGER) -->
        <div class="menu-toggle" id="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </div>

        <nav id="nav-menu">
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="cm-jogar.php">Como Jogar</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
                </li>
                <li><a href="criar-mapa.php">Mundos</a></li>
                <li><a href="rolagem-de-dados.php">Dados</a></li>
                <li><a href="sobre-nos.php">Sobre Nós</a></li>
            </ul>

            <!-- BOTÕES MOBILE -->
            <div class="nav-mobile-footer">
                <?php if (isset($_SESSION['usuario'])): ?>
                    <div class="usuario-logado-nav" onclick="window.location.href='perfil.php'">
                        <img src="<?= !empty($_SESSION['usuario']['foto']) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png' ?>"
                            alt="Avatar Navbar" class="avatar-nav">
                        <span class="nome-nav"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="botao-entrar">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="cadastro.php" class="botao-cadastrar">
                        <i class="fas fa-user-plus"></i> Cadastre-se
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <?php if (isset($_SESSION['usuario'])): ?>
            <div class="usuario-logado-nav desktop-only" id="nav-logado" onclick="window.location.href='perfil.php'"
                title="Ir para o Perfil">
                <img src="<?= !empty($_SESSION['usuario']['foto']) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png' ?>"
                    alt="Avatar Navbar" class="avatar-nav">
                <span class="nome-nav"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
            </div>
        <?php else: ?>
            <div class="botoes-navegacao desktop-only" id="nav-deslogado">
                <a href="login.php" class="botao-entrar">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="cadastro.php" class="botao-cadastrar">
                    <i class="fas fa-user-plus"></i> Cadastre-se
                </a>
            </div>
        <?php endif; ?>
    </header>

    <main class="main-criar-campanha">
        <div class="conteudo-campanha">

            <!-- TELA 01: CRIAR -->
            <div id="sessao-criar">
                <h1 class="titulo-pagina">Criar Campanha</h1>
                <section class="card-formulario-campanha">
                    <form id="form-criar-campanha">
                        <div class="grupo-form">
                            <label for="selecao-sistema">Sistema de RPG:</label>
                            <select id="selecao-sistema" class="input-campanha" onchange="carregarDetalhesSistema(this.value)" required>
                                <option value="" disabled selected>Selecione um sistema...</option>
                                <?php foreach ($sistemas as $sis): ?>
                                    <option value="<?= $sis['id_sistema'] ?>"><?= htmlspecialchars($sis['nm_sistema']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="sistema-showcase" class="sistema-showcase escondido"></div>

                        <div class="grupo-form">
                            <label for="nome-campanha">Nome:</label>
                            <input type="text" id="nome-campanha" class="input-campanha" placeholder="Nome da nova Campanha..." required>
                        </div>

                        <div class="grupo-form">
                            <label>Descrição:</label>
                            <div class="editor-container">
                                <div class="editor-toolbar">
                                    <button type="button" class="toolbar-btn bold"      id="btn-bold"      title="Negrito"><i class="fas fa-bold"></i></button>
                                    <button type="button" class="toolbar-btn italic"    id="btn-italic"    title="Itálico"><i class="fas fa-italic"></i></button>
                                    <button type="button" class="toolbar-btn underline" id="btn-underline" title="Sublinhado"><i class="fas fa-underline"></i></button>
                                </div>
                                <div id="descricao-campanha" class="textarea-campanha" contenteditable="true" placeholder="Descreva sua campanha aqui..."></div>
                            </div>
                        </div>

                        <div class="form-acoes">
                            <a href="#" class="btn-cancelar"><i class="fas fa-times"></i> Cancelar</a>
                            <button type="submit" class="btn-criar-campanha"><i class="fas fa-plus-circle"></i> Criar</button>
                        </div>
                    </form>
                </section>
            </div>

            <!-- TELA 02: DETALHES -->
            <div id="sessao-detalhes" class="sessao-detalhes">
                <h1 id="display-nome-campanha" class="titulo-campanha-criada">Nome da campanha</h1>
                <div class="campanha-info-wrapper">
                    <div id="banner-campanha-display" class="banner-campanha escondido"></div>
                    <div class="descricao-campanha-display" id="display-descricao-campanha"><p>Sua campanha aparecerá aqui...</p></div>
                </div>

                <div class="barra-acoes">
                    <?php if ($isMaster): ?>
                        <button class="btn-acao" onclick="abrirModal('modal-foto-capa')"><i class="fas fa-image"></i> Foto de Capa</button>
                    <?php endif; ?>

                    <button class="btn-acao" onclick="abrirModalPersonagens()"><i class="fas fa-user-plus"></i> Adicionar Personagem</button>

                    <button class="btn-acao" onclick="switchDashboardTab('dados'); document.querySelector('.sub-nav-campanha').scrollIntoView({behavior:'smooth'});"><i class="fas fa-dice-d20"></i> Dados</button>

                    <?php if ($isMaster): ?>
                        <button class="btn-acao" onclick="abrirModalConvite()"><i class="fas fa-link"></i> Convidar Jogadores</button>
                    <?php endif; ?>

                    <?php if (!$isMaster): ?>
                        <button class="btn-acao" style="background: rgba(255, 77, 77, 0.1); border-color: rgba(255, 77, 77, 0.3); color: #ff4d4d;" onclick="sairDaCampanha()"><i class="fas fa-sign-out-alt"></i> Sair da Campanha</button>
                    <?php endif; ?>

                    <?php if ($isMaster): ?>
                        <button class="btn-acao" onclick="irParaEditar()"><i class="fas fa-edit"></i> Editar Campanha</button>
                        <button class="btn-acao" onclick="novoCombate()"><i class="fas fa-skull-crossbones"></i> Criar Combate</button>
                        <button class="btn-acao especial" onclick="irParaEscudo()"><i class="fas fa-shield-halved"></i> Escudo do Mestre</button>
                    <?php endif; ?>
                </div>

                <div class="sub-nav-campanha">
                    <a href="javascript:void(0)" class="link-sub-nav ativa" id="aba-personagens" onclick="switchDashboardTab('personagens')">Personagens</a>
                    <a href="javascript:void(0)" class="link-sub-nav" id="aba-combates" onclick="switchDashboardTab('combates')">Combates</a>
                    <a href="javascript:void(0)" class="link-sub-nav" id="aba-jogadores" onclick="switchDashboardTab('jogadores')">Jogadores</a>
                    <a href="javascript:void(0)" class="link-sub-nav" id="aba-dados" onclick="switchDashboardTab('dados')">Dados</a>
                </div>

                <div class="lista-Personagems" id="lista-Personagems">
                    <?php if (empty($PersonagemsCampanha)): ?>
                        <p style="text-align:center;opacity:.5;margin-top:20px;">Nenhum personagem na campanha ainda.</p>
                    <?php endif; ?>
                    <?php foreach ($PersonagemsCampanha as $Personagem): 
                        $isMaster = $campanhaDados ? ((int)$campanhaDados['id_usuario_mestre'] === (int)$_SESSION['usuario']['id']) : false;
                        $isOwner  = ((int)$Personagem['id_dono'] === (int)$_SESSION['usuario']['id']);
                        $flPublico = (int)($Personagem['fl_publico'] ?? 0);
                        $podeVerFicha = $isMaster || $isOwner || ($flPublico === 1);
                    ?>
                        <div class="card-Personagem">
                            <div class="avatar-Personagem">
                                <img src="<?= !empty($Personagem['ds_foto']) ? $Personagem['ds_foto'] : '../img/uploads/perfil/avatar1.png' ?>" alt="Avatar">
                            </div>
                            <div class="info-Personagem">
                                <h3 style="display: flex; align-items: center; gap: 8px;">
                                    <?= htmlspecialchars($Personagem['nm_personagem']) ?>
                                    <?php if ((int)$Personagem['id_dono'] === (int)$campanhaDados['id_usuario_mestre']): ?>
                                        <span style="background: rgba(255, 77, 77, 0.15); color: #ff4d4d; font-weight: 800; font-size: 0.7rem; padding: 2px 8px; border-radius: 4px; border: 1px solid rgba(255,77,77,0.2);">NPC</span>
                                    <?php endif; ?>
                                </h3>
                                <p><?= htmlspecialchars($Personagem['nm_sistema'] ?? 'Sistema Desconhecido') ?></p>
                            </div>
                            <div class="acoes-Personagem-card" style="display: flex; gap: 10px; align-items: center;">
                                <?php if ($podeVerFicha): ?>
                                    <button class="btn-ver-ficha" onclick="window.location.href='exibir-ficha.php?id=<?= $Personagem['id_personagem'] ?>'">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                <?php else: ?>
                                    <button class="btn-ver-ficha desabilitado" style="opacity: 0.4; cursor: not-allowed; display: flex; align-items: center; gap: 6px;" title="Esta ficha está privada somente para o Mestre.">
                                        <i class="fas fa-eye-slash"></i> Privada
                                    </button>
                                <?php endif; ?>

                                <?php if ($isOwner && !$isMaster): ?>
                                    <!-- Botão de Visibilidade para o Dono -->
                                    <button class="btn-visibilidade" 
                                            onclick="toggleVisibilidade(<?= $Personagem['id_personagem'] ?>, <?= $flPublico === 1 ? 0 : 1 ?>)" 
                                            style="border-radius: 20px; font-weight: 600; font-size: 0.8rem; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 6px; padding: 8px 15px; border: 1px solid <?= $flPublico === 1 ? 'rgba(0, 200, 100, 0.3)' : 'rgba(255, 255, 255, 0.15)' ?>; background: <?= $flPublico === 1 ? 'rgba(0, 200, 100, 0.05)' : 'rgba(255, 255, 255, 0.03)' ?>; color: <?= $flPublico === 1 ? '#00c864' : '#bbb' ?>;"
                                            title="Clique para alternar a visibilidade da ficha">
                                        <i class="fas <?= $flPublico === 1 ? 'fa-globe' : 'fa-lock' ?>"></i> 
                                        <?= $flPublico === 1 ? 'Pública' : 'Privada' ?>
                                    </button>
                                <?php elseif (!$isOwner && !$isMaster && $flPublico === 1): ?>
                                    <span style="font-size: 0.75rem; color: #00c864; background: rgba(0, 200, 100, 0.1); padding: 4px 10px; border-radius: 12px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;">
                                        <i class="fas fa-globe"></i> Pública
                                    </span>
                                <?php endif; ?>

                                <?php if ($isMaster || $isOwner): ?>
                                    <button class="btn-remover-personagem" 
                                            onclick="removerPersonagem(<?= $Personagem['id_personagem'] ?>, '<?= $isOwner && !$isMaster ? 'sair' : 'remover' ?>')"
                                            style="color: #ff4d4d; border: 1px solid rgba(255,77,77,0.3); padding: 8px 15px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 6px; background: rgba(255,77,77,0.05);">
                                        <i class="fas fa-sign-out-alt"></i> <?= $isOwner && !$isMaster ? 'Sair' : 'Tirar' ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="lista-combates escondido" id="lista-combates">
                    <?php if (empty($combatesCampanha)): ?>
                        <p style="text-align:center;opacity:.5;margin-top:20px;">Nenhum combate adicionado ainda.</p>
                    <?php endif; ?>
                    <?php foreach ($combatesCampanha as $combate): ?>
                        <div class="card-combate">
                            <h3><?= htmlspecialchars($combate['nm_combate']) ?></h3>
                            <p><?= $isOrdemParanormal ? 'VD' : 'VT' ?> Total: <?= $combate['vd_total'] ?: 0 ?></p>
                            <?php if ($isMaster): ?>
                                <div class="card-combate-footer">
                                    <button class="btn-remover-combate" onclick="removerCombate(<?= $combate['id_combate'] ?>, this)"><i class="fas fa-trash"></i> Remover</button>
                                    <button class="btn-editar-combate" onclick="editarCombate(<?= $combate['id_combate'] ?>)"><i class="fas fa-edit"></i> Editar</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="lista-Personagems escondido" id="lista-jogadores">
                    <?php if (empty($jogadoresAgrupados)): ?>
                        <p style="text-align:center;opacity:.5;margin-top:20px;">Nenhum participante na campanha ainda.</p>
                    <?php endif; ?>
                    <?php foreach ($jogadoresAgrupados as $jog): 
                        $fotoUsuario = !empty($jog['foto_usuario']) ? $jog['foto_usuario'] : '../img/uploads/perfil/avatar1.png';
                        $nomeUsuario = htmlspecialchars(!empty($jog['nm_exibicao']) ? $jog['nm_exibicao'] : $jog['nm_usuario']);
                        $eMestre = $jog['papel_campanha'] === 'mestre';
                    ?>
                        <div class="card-Personagem card-jogador-clicavel" 
                             style="flex-direction: column; align-items: stretch; gap: 15px; padding: 20px; cursor: pointer;"
                             onclick="abrirModalJogador(this)"
                             data-foto="<?= $fotoUsuario ?>"
                             data-nome="<?= $nomeUsuario ?>"
                             data-papel="<?= $jog['papel_campanha'] ?>"
                             data-username="<?= htmlspecialchars($jog['nm_usuario']) ?>"
                             data-email="<?= htmlspecialchars($jog['ds_email'] ?? 'Não cadastrado') ?>"
                             data-nascimento="<?= $jog['dt_nascimento'] ? date('d/m/Y', strtotime($jog['dt_nascimento'])) : 'Não cadastrada' ?>"
                             data-bio="<?= htmlspecialchars($jog['ds_bio'] ?? 'Esse jogador não escreveu nenhuma biografia ainda...') ?>">
                            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div class="avatar-Personagem" style="margin: 0;">
                                        <img src="<?= $fotoUsuario ?>" alt="Avatar de <?= $nomeUsuario ?>">
                                    </div>
                                    <div class="info-Personagem" style="margin: 0; display: flex; flex-direction: column; gap: 4px;">
                                        <h3 style="margin: 0;"><?= $nomeUsuario ?></h3>
                                        <div>
                                            <?php if ($eMestre): ?>
                                                <span style="background: rgba(157, 122, 255, 0.15); color: var(--cor-destaque-claro); font-weight: 700; font-size: 0.75rem; padding: 3px 10px; border-radius: 20px; border: 1px solid rgba(157,122,255,0.3); display: inline-flex; align-items: center; gap: 4px;">
                                                    <i class="fas fa-crown" style="font-size: 0.7rem;"></i> Mestre
                                                </span>
                                            <?php else: ?>
                                                <span style="background: rgba(0, 200, 100, 0.15); color: #00c864; font-weight: 700; font-size: 0.75rem; padding: 3px 10px; border-radius: 20px; border: 1px solid rgba(0,200,100,0.3); display: inline-flex; align-items: center; gap: 4px;">
                                                    <i class="fas fa-user" style="font-size: 0.7rem;"></i> Jogador
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($isMaster && !$eMestre): ?>
                                    <button type="button" class="btn-remover-personagem" 
                                            style="color: #ff4d4d; border: 1px solid rgba(255,77,77,0.3); padding: 8px 15px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 6px; background: rgba(255,77,77,0.05); margin: 0;"
                                            onclick="event.stopPropagation(); expulsarJogador(<?= (int)$jog['id_usuario'] ?>, '<?= addslashes($nomeUsuario) ?>')">
                                        <i class="fas fa-user-minus"></i> Expulsar
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Lista de Personagens/NPCs do participante -->
                            <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05);" onclick="event.stopPropagation();">
                                <?php if (empty($jog['personagens'])): ?>
                                    <p style="font-size: 0.85rem; color: #777; font-style: italic; margin: 0;">Nenhum personagem adicionado</p>
                                <?php else: ?>
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        <?php 
                                        $totalPers = count($jog['personagens']);
                                        foreach ($jog['personagens'] as $index => $pers): 
                                            $isLast = ($index === $totalPers - 1);
                                            $borderStyle = !$isLast ? 'border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 10px;' : '';
                                            
                                            $idDonoPers = (int)$jog['id_usuario'];
                                            $isOwnerPers = ($idDonoPers === (int)$_SESSION['usuario']['id']);
                                            $flPublicoPers = (int)($pers['fl_publico'] ?? 0);
                                            $podeVerPers = $isMaster || $isOwnerPers || ($flPublicoPers === 1);
                                        ?>
                                            <div style="display: flex; align-items: center; justify-content: space-between; <?= $borderStyle ?>">
                                                <p style="font-size: 0.9rem; color: #ccc; margin: 0; display: flex; align-items: center; gap: 8px;">
                                                    Personagem: <strong style="color: #fff;"><?= htmlspecialchars($pers['nm_personagem']) ?></strong>
                                                    <?php if ($eMestre): ?>
                                                        <span style="background: rgba(255, 77, 77, 0.15); color: #ff4d4d; font-weight: 800; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; border: 1px solid rgba(255,77,77,0.2);">NPC</span>
                                                    <?php endif; ?>
                                                </p>
                                                <?php if ($podeVerPers): ?>
                                                    <button class="btn-ver-ficha" onclick="event.stopPropagation(); window.location.href='exibir-ficha.php?id=<?= $pers['id_personagem'] ?>'" style="margin: 0; padding: 5px 12px; font-size: 0.8rem;">
                                                        <i class="fas fa-eye"></i> Ver <?= $eMestre ? 'NPC' : 'Personagem' ?>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn-ver-ficha desabilitado" onclick="event.stopPropagation();" style="margin: 0; padding: 5px 12px; font-size: 0.8rem; opacity: 0.4; cursor: not-allowed; display: flex; align-items: center; gap: 6px;" title="Esta ficha está privada somente para o Mestre.">
                                                        <i class="fas fa-eye-slash"></i> Privado
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- ABA DADOS — Layout idêntico ao rolagem-de-dados.php -->
                <div class="lista-Personagems escondido" id="lista-dados-jogador">
                    <div class="camp-dados-container">

                        <!-- Coluna esquerda: Painel de Rolagem -->
                        <div class="camp-dados-grid-box">
                            <h2 class="camp-titulo-secao">Rolagem de Dados</h2>

                            <!-- Seletor de aparência dos dados -->
                            <div class="camp-tema-row">
                                <span id="camp-status-dot" class="loading" title="Conectando..."></span>
                                <label>Aparência dos Dados:</label>
                                <select id="camp-theme-select" disabled>
                                    <option value="">Conectando...</option>
                                </select>
                            </div>

                            <!-- Grade de dados -->
                            <div class="camp-grid-dados">
                                <?php foreach ([2,4,6,8,10,12,20,100] as $l): ?>
                                    <div class="camp-item-dado" id="camp-dado-d<?= $l ?>" data-lados="<?= $l ?>">
                                        <div class="dado-icon-container">
                                            <img src="../img/dados/D<?= $l ?>.png" alt="D<?= $l ?>" class="img-dado">
                                        </div>
                                        <span class="label-dado">D<?= $l ?></span>
                                        <div class="camp-bolinha" id="camp-bolinha-d<?= $l ?>">0</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Resumo da seleção -->
                            <div class="camp-sel-resumo" id="camp-sel-resumo">
                                <span style="color:#555;">Clique nos dados para selecionar quantidades...</span>
                            </div>

                            <!-- Botão Rolar -->
                            <button class="camp-btn-rolar" id="camp-btn-rolar" disabled onclick="campExecutarRolagem()">
                                <i class="fas fa-dice"></i> Rolar Dados
                            </button>
                        </div>

                        <!-- Coluna direita: Histórico de Rolagens -->
                        <div class="camp-historico-box">
                            <div class="camp-historico-header">
                                <h3>Histórico</h3>
                                <button class="camp-btn-limpar-log" onclick="limparHistoricoJogador()">Limpar</button>
                            </div>
                            <div id="camp-historico-lista">
                                <div style="text-align: center; padding: 40px; color: #555;" id="camp-msg-vazio">
                                    Nenhuma rolagem feita ainda nesta sessão.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- TELA 03: EDITAR -->
            <div id="sessao-editar" class="sessao-criar escondido sessao-editar-container">
                <h1 class="titulo-pagina">Editar Campanha</h1>
                <section class="card-formulario-campanha">
                    <form id="form-editar-campanha">
                        <div class="grupo-form">
                            <label for="selecao-sistema-edit">Sistema de RPG:</label>
                            <select id="selecao-sistema-edit" class="input-campanha" onchange="carregarDetalhesSistema(this.value,'sistema-showcase-edit')" required>
                                <option value="" disabled>Selecione um sistema...</option>
                                <?php foreach ($sistemas as $sis): ?>
                                    <option value="<?= $sis['id_sistema'] ?>"><?= htmlspecialchars($sis['nm_sistema']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="sistema-showcase-edit" class="sistema-showcase escondido"></div>

                        <div class="grupo-form">
                            <label for="nome-campanha-edit">Nome:</label>
                            <input type="text" id="nome-campanha-edit" class="input-campanha" placeholder="Nome da Campanha..." required>
                        </div>

                        <div class="grupo-form">
                            <label>Descrição:</label>
                            <div class="editor-container">
                                <div class="editor-toolbar">
                                    <button type="button" class="toolbar-btn bold"      onclick="document.execCommand('bold',false,null)"      title="Negrito"><i class="fas fa-bold"></i></button>
                                    <button type="button" class="toolbar-btn italic"    onclick="document.execCommand('italic',false,null)"    title="Itálico"><i class="fas fa-italic"></i></button>
                                    <button type="button" class="toolbar-btn underline" onclick="document.execCommand('underline',false,null)" title="Sublinhado"><i class="fas fa-underline"></i></button>
                                </div>
                                <div id="descricao-campanha-edit" class="textarea-campanha" contenteditable="true" placeholder="Descreva sua campanha aqui..."></div>
                            </div>
                        </div>

                        <div class="form-acoes">
                            <button type="button" class="btn-cancelar" onclick="showSection('sessao-detalhes')"><i class="fas fa-times"></i> Cancelar</button>
                            <button type="button" class="btn-criar-campanha" onclick="salvarEdicao()"><i class="fas fa-save"></i> Salvar Alterações</button>
                        </div>
                    </form>
                </section>
            </div>

            <!-- TELA 04: COMBATE -->
            <div id="sessao-combate" class="sessao-combate">
                <div class="combate-header">
                    <div class="combate-titulo-area">
                        <div>
                            <label>Nome do Combate:</label><br>
                            <input type="text" class="input-nome-combate" id="nome-combate-input" placeholder="Nome do novo Combate...">
                        </div>
                        <div class="vd-total-display"><?= $isOrdemParanormal ? 'VD' : 'VT' ?> Total: <span id="vd-total-valor">0</span></div>
                    </div>
                    <div class="combate-botoes-topo">
                        <button class="btn-combate-sair"   onclick="showSection('sessao-detalhes')"><i class="fas fa-times-circle"></i> Sair sem Salvar</button>
                        <button class="btn-combate-salvar" onclick="salvarCombate()"><i class="fas fa-save"></i> Salvar</button>
                    </div>
                </div>

                <div class="combate-grid">
                    <div class="catalogo-ameacas">
                        <div class="area-banners-combate">
                            <?php
                            $idSisCampanha = $campanhaDados ? (int)$campanhaDados['id_sistema'] : 0;
                            $sistemaPersonalizado = ($idSisCampanha !== 1 && $idSisCampanha !== 2 && $idSisCampanha > 0);
                            $nomeSistemaCampanha = $campanhaDados ? $campanhaDados['nm_sistema'] : '';
                            $imagemSistemaCampanha = ($campanhaDados && !empty($campanhaDados['ds_imagem_sistema'])) ? $campanhaDados['ds_imagem_sistema'] : '../img/logo_icone.png';
                            ?>
                            <div class="banners-flex">
                                <?php if ($sistemaPersonalizado): ?>
                                    <div class="banner-card banner-custom ativo" onclick="selecionarOrigemCriatura('custom', this)" style="display: flex; align-items: center; justify-content: center; gap: 8px; border: 1.5px solid var(--premium-accent); border-radius: 10px; background: rgba(139, 92, 246, 0.1); padding: 10px 15px; cursor: pointer; transition: all 0.3s; color: #fff;">
                                        <img src="<?= htmlspecialchars($imagemSistemaCampanha) ?>" alt="Sistema Logo" style="width: 28px; height: 28px; border-radius: 6px; object-fit: cover;">
                                        <span style="font-weight: 800; font-size: 0.85rem; letter-spacing: 0.5px; text-transform: uppercase;"><?= htmlspecialchars($nomeSistemaCampanha) ?></span>
                                    </div>
                                    <div class="banner-card banner-ordem" onclick="selecionarOrigemCriatura('oficial', this)"><img src="../img/ordem-paranormal-icon.png" alt="Ordem Logo"></div>
                                <?php else: ?>
                                    <div class="banner-card banner-ordem ativo" onclick="selecionarOrigemCriatura('oficial', this)"><img src="../img/ordem-paranormal-icon.png" alt="Ordem Logo"></div>
                                <?php endif; ?>
                                <div class="banner-card banner-novas" onclick="redirecionarNovaCriatura()"><span>CRIAR NOVAS AMEAÇAS!</span></div>
                            </div>
                            <p class="banner-subtexto">Conteúdo oficial da TABLE. Veja mais <a href="biblioteca.php" style="background-color: #7b4ff7; color: #fff; padding: 4px 12px; border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 0.8rem; display: inline-block; transition: 0.3s; box-shadow: 0 4px 10px rgba(123, 79, 247, 0.3);" onmouseover="this.style.backgroundColor='#9166ff'" onmouseout="this.style.backgroundColor='#7b4ff7'">aqui</a></p>
                        </div>
                        <div class="lista-ameacas-header">
                            <label>Lista de Ameaças</label>
                            <div class="search-container">
                                <i class="fas fa-search"></i>
                                <input type="text" id="busca-ameaca" placeholder="Buscar..." oninput="renderCatalogo()">
                            </div>
                            <div class="filtros-elemento" id="filtros-ameacas">
                                <button class="btn-filtro ativo"  onclick="filtrarPorElemento('Todos',this)">Todos</button>
                                <button class="btn-filtro"        onclick="filtrarPorElemento('Conhecimento',this)">Conhecimento</button>
                                <button class="btn-filtro"        onclick="filtrarPorElemento('Morte',this)">Morte</button>
                                <button class="btn-filtro"        onclick="filtrarPorElemento('Sangue',this)">Sangue</button>
                                <button class="btn-filtro"        onclick="filtrarPorElemento('Medo',this)">Medo</button>
                                <button class="btn-filtro"        onclick="filtrarPorElemento('Energia',this)">Energia</button>
                                <button class="btn-filtro"        onclick="filtrarPorElemento('Mundano',this)">Mundano</button>
                            </div>
                        </div>
                        <div class="lista-ameacas-cards" id="catalogo-cards"></div>
                    </div>
                    <div class="ameacas-selecionadas">
                        <h2 class="titulo-ameacas-selecionadas">Ameaças Adicionadas</h2>
                        <div class="lista-ameacas-cards" id="selecionadas-cards"></div>
                    </div>
                </div>
            </div>

            <!-- TELA 05: ESCUDO -->
            <div id="sessao-escudo" class="sessao-escudo">
                <div class="escudo-wrapper">
                    <aside class="escudo-sidebar">
                        <h3>Histórico de Dados</h3>
                        <div class="sidebar-dados-lista" id="sidebar-dados-lista">
                            <div class="placeholder-historico" style="text-align:center; color: rgba(255,255,255,0.35); font-size:0.75rem; padding: 20px 10px; font-style: italic;">
                                Nenhuma rolagem ainda.<br>Role um dado para começar.
                            </div>
                        </div>
                    </aside>

                    <section class="escudo-principal">
                        <div class="escudo-topo">
                            <h1 id="escudo-titulo-campanha">Nome da Campanha</h1>
                            <div class="escudo-acoes-topo">
                                <button class="btn-escudo-sair"   onclick="fecharEscudo()">Sair sem Salvar</button>
                                <button class="btn-escudo-salvar" onclick="fecharEscudo()">Salvar</button>
                            </div>
                        </div>

                        <div class="escudo-nav">
                            <a class="escudo-link-nav ativo" onclick="switchEscudoTab('personagens',this)">Personagens</a>
                            <a class="escudo-link-nav"       onclick="switchEscudoTab('combates',this)">Combates</a>
                            <a class="escudo-link-nav"       onclick="switchEscudoTab('investigacoes',this)">Investigações</a>
                            <a class="escudo-link-nav"       onclick="switchEscudoTab('relatorios',this)">Relatórios</a>
                            <a class="escudo-link-nav"       onclick="switchEscudoTab('anotacoes',this)">Anotações</a>
                            <a class="escudo-link-nav"       onclick="switchEscudoTab('dados',this)">Dados</a>
                            <?php if ($flPlanoMapas): ?>
                                <a href="criar-mapa.php?id=<?= $id_campanha ?>" target="_blank" class="escudo-link-nav link-mapa-especial">Mapas <i class="fas fa-external-link-alt"></i></a>
                            <?php else: ?>
                                <a href="planos.php?aviso=mapas" class="escudo-link-nav link-mapa-especial" style="opacity: 0.5; cursor: pointer;" title="Desbloqueie o Plano de Mapas para acessar!"><i class="fas fa-lock"></i> Mapas</a>
                            <?php endif; ?>
                        </div>

                        <!-- Personagens -->
                        <div id="escudo-tab-personagens" class="escudo-agentes-grid">
                            <?php if (empty($PersonagemsCampanha)): ?>
                                <div class="escudo-estado-vazio">
                                    <i class="fas fa-users-slash icone-vazio"></i>
                                    <h3>Nenhum personagem vinculado</h3>
                                    <p>Convide seus jogadores para criarem ou vincularem suas fichas de personagens a esta campanha.</p>
                                    <button class="btn-escudo-vazio-action" onclick="irParaAbaDashboard('jogadores')">Convidar Jogadores</button>
                                </div>
                            <?php else: ?>
                                <?php foreach ($PersonagemsCampanha as $Personagem): ?>
                                    <div class="card-agente-compacto recolhido" data-id-personagem="<?= $Personagem['id_personagem'] ?>">
                                        <div class="card-compacto-header" style="display: flex; gap: 12px; align-items: center; width: 100%; position: relative;">
                                            <img src="<?= !empty($Personagem['ds_foto']) ? $Personagem['ds_foto'] : '../img/uploads/perfil/avatar1.png' ?>" 
                                                 alt="Avatar" 
                                                 style="width: 44px; height: 44px; border-radius: 6px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1); flex-shrink: 0;">
                                            <div style="flex: 1; min-width: 0;">
                                                <i class="fas fa-chevron-down toggle-escudo-ficha" style="float: right; color: rgba(255,255,255,0.4); font-size: 0.8rem; margin-top: 4px; transition: transform 0.3s;"></i>
                                                <h3 style="margin: 0; font-size: 0.95rem; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #fff;"><?= htmlspecialchars($Personagem['nm_personagem']) ?></h3>
                                                <p style="margin: 1px 0 0 0; font-size: 0.72rem; color: #aaa; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($Personagem['nm_classe'] ?: 'Mundano') ?> • <?= htmlspecialchars($Personagem['nm_origem'] ?? 'Acadêmico') ?></p>
                                                <p class="nome-jogador-p1" style="margin: 1px 0 0 0; font-size: 0.7rem; color: #888; font-style: italic; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Jogador: <?= htmlspecialchars($Personagem['jogador_nome'] ?? 'Sem jogador') ?></p>
                                                <span class="nivel-badge" style="display: block; margin-top: 2px; font-size: 0.72rem; font-weight: 800; color: #C193FD;">Nivel: <?= $Personagem['qt_nivel'] ?></span>
                                            </div>
                                        </div>
                                        <div class="atributos-agente-p1">
                                            <?php foreach ($Personagem['atributos'] as $attr): 
                                                $abbr = $attr['ds_abreviacao'] ?: substr($attr['nm_atributo'], 0, 3);
                                            ?>
                                                <div class="attr-p1-box">
                                                    <span><?= htmlspecialchars(strtoupper($abbr)) ?></span>
                                                    <div><?= $attr['qt_valor'] ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="status-bars-p1">
                                            <?php foreach ($Personagem['status_barras'] as $barra): 
                                                $pct = $barra['qt_max'] > 0 ? min(100, max(0, round(($barra['qt_atual'] / $barra['qt_max']) * 100))) : 0;
                                                $cor = $barra['ds_cor'] ?: '#9d7aff';
                                            ?>
                                                <div class="barra-p1-container">
                                                    <div class="barra-p1-label"><?= htmlspecialchars($barra['nm_status']) ?></div>
                                                    <div class="barra-p1-bg">
                                                        <div class="barra-p1-fill" style="width:<?= $pct ?>%; background-color: <?= htmlspecialchars($cor) ?>;"></div>
                                                        <div class="barra-p1-text"><?= $barra['qt_atual'] ?>/<?= $barra['qt_max'] ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="compacto-footer">
                                            <div class="footer-stats-grid" style="display: flex; gap: 15px; justify-content: center; align-items: center; margin-bottom: 12px;">
                                                <?php foreach ($Personagem['status_defesas'] as $def): 
                                                    $cor = $def['ds_cor'] ?: '#95a5a6';
                                                ?>
                                                    <div class="mini-shield-item" style="display: flex; flex-direction: column; align-items: center; position: relative;">
                                                        <i class="fas fa-shield-alt" style="font-size: 1.8rem; color: <?= htmlspecialchars($cor) ?>; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"></i>
                                                        <span style="position: absolute; top: 6px; font-size: 0.75rem; font-weight: 900; color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.8);"><?= $def['qt_atual'] ?></span>
                                                        <span style="font-size: 0.55rem; color: #aaa; font-weight: 700; text-transform: uppercase; margin-top: 4px;"><?= htmlspecialchars(substr($def['nm_status'], 0, 3)) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <a href="exibir-ficha.php?id=<?= $Personagem['id_personagem'] ?>" class="btn-ficha-compacto">Ver Ficha Completa</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Combates -->
                        <div id="escudo-tab-combates" class="escondido">
                            <?php if (empty($combatesCampanha)): ?>
                                <div class="escudo-estado-vazio">
                                    <i class="fas fa-skull-crossbones icone-vazio"></i>
                                    <h3>Nenhum combate criado</h3>
                                    <p>Monte os combates e gerencie os turnos dos seus jogadores e criaturas no painel principal.</p>
                                    <button class="btn-escudo-vazio-action" onclick="irParaAbaDashboard('combates')">Criar Combate</button>
                                </div>
                            <?php else: ?>
                                <div id="escudo-combates-lista" class="lista-combates">
                                    <?php foreach ($combatesCampanha as $combate): ?>
                                        <div class="card-combate-escudo" style="background:var(--fundo-card-escudo);padding:30px;border-radius:15px;display:flex;justify-content:space-between;align-items:center;border:1px solid var(--cor-borda-escudo);">
                                            <div>
                                                <h3 style="font-size:1.5rem;margin-bottom:5px;"><?= htmlspecialchars($combate['nm_combate']) ?></h3>
                                                <p style="color:#888;"><?= $isOrdemParanormal ? 'VD' : 'VT' ?>: <?= $combate['vd_total'] ?: 0 ?></p>
                                            </div>
                                            <button class="btn-iniciar-combate"
                                                    onclick="iniciarCombateEscudo(<?= $combate['id_combate'] ?>,'<?= htmlspecialchars($combate['nm_combate']) ?>')"
                                                    style="background:#fff;color:#000;padding:10px 30px;border-radius:20px;font-weight:800;cursor:pointer;">Iniciar</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div id="escudo-combate-ativo" class="escudo-combate-ativo escondido">
                                    <div class="coluna-iniciativa">
                                        <div class="header-iniciativa">
                                            <h2>Iniciativa</h2>
                                            <div class="controles-turno">
                                                <button class="btn-turno" onclick="voltarTurnoEscudo()">Voltar Turno</button>
                                                <button class="btn-turno" onclick="proximoTurnoEscudo()">Próximo Turno</button>
                                            </div>
                                        </div>
                                        <div id="lista-iniciativa-escudo"></div>
                                    </div>
                                    <div class="ficha-detalhes-escudo" id="detalhe-participante-escudo">
                                        <p style="text-align:center;color:#888;padding-top:50px;">Selecione um participante para ver detalhes.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Investigações -->
                        <div id="escudo-tab-investigacoes" class="escondido">
                            <div id="inv-modo-lista">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                                    <h2 style="font-size:1.2rem;font-weight:700;">Fichas de Investigação</h2>
                                    <button class="btn-adicionar-investigacao" onclick="novaFichaInvestigacao()">Adicionar</button>
                                </div>
                                <div class="investigacao-lista">
                                    <!-- Carregado via LocalStorage JS -->
                                </div>
                            </div>
                            <div id="inv-modo-detalhe" class="escondido">
                                <div style="display:flex;justify-content:flex-end;margin-bottom:20px;gap:10px;">
                                    <button class="btn-voltar-investigacao" onclick="voltarListaInvestigacao()" style="background:none;border:1px solid rgba(255,255,255,0.2);color:#fff;padding:8px 20px;border-radius:20px;cursor:pointer;">Voltar</button>
                                    <button class="btn-combate-salvar" onclick="salvarFichaInvestigacao()" style="background:#27ae60;color:#fff;border:none;padding:8px 25px;border-radius:20px;cursor:pointer;font-weight:700;">Salvar Caso</button>
                                </div>
                                <div class="form-investigacao" style="background:rgba(0,0,0,.3);padding:30px;border-radius:20px;">
                                    <div class="campo-investigacao"><label>Nome do caso</label><input type="text" id="inv-nome-input" placeholder="Nome do caso"></div>
                                    <div class="campo-investigacao"><label>Resumo:</label><div class="textarea-p1" id="inv-resumo-input" contenteditable="true" placeholder="..."></div></div>
                                    <div class="campo-investigacao"><label>Objetivo:</label><div class="textarea-p1" id="inv-objetivo-input" contenteditable="true" placeholder="..."></div></div>
                                    <div class="campo-investigacao"><label>Perguntas:</label><div class="textarea-p1" id="inv-perguntas-input" contenteditable="true" placeholder="..."></div></div>
                                    <div class="campo-investigacao"><label>Pistas:</label><div class="textarea-p1" id="inv-pistas-input" contenteditable="true" placeholder="..."></div></div>
                                </div>
                            </div>
                        </div>

                        <!-- Relatórios -->
                        <div id="escudo-tab-relatorios" class="escondido">
                            <div id="rel-modo-lista">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                                    <h2 style="font-size:1.2rem;font-weight:700;">Relatórios de Missão</h2>
                                    <button class="btn-adicionar-investigacao" onclick="novoRelatorioMissao()">Adicionar</button>
                                </div>
                                <div class="investigacao-lista">
                                    <!-- Carregado via LocalStorage JS -->
                                </div>
                            </div>
                            <div id="rel-modo-detalhe" class="escondido">
                                <div style="display:flex;justify-content:flex-end;margin-bottom:20px;gap:10px;">
                                    <button class="btn-voltar-investigacao" onclick="voltarListaRelatorio()" style="background:none;border:1px solid rgba(255,255,255,0.2);color:#fff;padding:8px 20px;border-radius:20px;cursor:pointer;">Voltar</button>
                                    <button class="btn-combate-salvar" onclick="salvarFichaRelatorio()" style="background:#27ae60;color:#fff;border:none;padding:8px 25px;border-radius:20px;cursor:pointer;font-weight:700;">Salvar Relatório</button>
                                </div>
                                <div class="form-investigacao" style="background:rgba(0,0,0,.3);padding:30px;border-radius:20px;">
                                    <div class="form-relatorio-row" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:15px;">
                                        <div class="campo-investigacao" style="margin-bottom:0;"><label>Missão:</label><input type="text" id="rel-missao-input" placeholder="Nome do relatório..."></div>
                                        <div class="campo-investigacao" style="margin-bottom:0;"><label>Equipe:</label><input type="text" id="rel-equipe-input" placeholder="Nome da equipe..."></div>
                                    </div>
                                    <div class="campo-investigacao"><label>Personagens Envolvidos:</label><input type="text" id="rel-personagens-input" placeholder="..."></div>
                                    <div class="campo-investigacao"><label>Pistas Encontradas</label><div class="textarea-p1" id="rel-pistas-input" contenteditable="true" placeholder="Todas as pistas..."></div></div>
                                    <div class="campo-investigacao"><label>Causalidades</label><div class="textarea-p1" id="rel-casualidades-input" contenteditable="true" placeholder="Mortes, perda de itens..."></div></div>
                                    <div class="campo-investigacao"><label>Resumo da Missão:</label><div class="textarea-p1" id="rel-resumo-input" contenteditable="true" placeholder="Resumo e conclusão..."></div></div>
                                    <div class="campo-investigacao">
                                        <label>Resultado:</label>
                                        <div class="status-toggle-group">
                                            <button class="btn-status-rel ativo" data-status="aberto"   onclick="setRelStatus(this)">Em aberto</button>
                                            <button class="btn-status-rel"       data-status="sucesso"  onclick="setRelStatus(this)">Sucesso</button>
                                            <button class="btn-status-rel"       data-status="fracasso" onclick="setRelStatus(this)">Fracasso</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dados -->
                        <div id="escudo-tab-dados" class="escondido" style="position:relative;">

                            <!-- Seletor de aparência dos dados -->
                            <div class="escudo-tema-row">
                                <span id="escudo-status-dot" class="loading" title="Conectando..."></span>
                                <label>Aparência dos Dados:</label>
                                <select id="escudo-theme-select" disabled>
                                    <option value="">Conectando...</option>
                                </select>
                            </div>

                            <!-- Grade de dados com bolinhas contadoras -->
                            <div class="grid-dados">
                                <?php foreach ([2,4,6,8,10,12,20,100] as $l): ?>
                                    <div class="item-dado" id="escudo-dado-d<?= $l ?>" data-lados="<?= $l ?>">
                                        <div class="dado-icon-container">
                                            <img src="../img/dados/D<?= $l ?>.png" alt="D<?= $l ?>" class="img-dado">
                                        </div>
                                        <span class="label-dado">D<?= $l ?></span>
                                        <div class="escudo-bolinha" id="escudo-bolinha-d<?= $l ?>">0</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Resumo dos dados selecionados -->
                            <div class="escudo-sel-resumo" id="escudo-sel-resumo">
                                <span style="color:#555;">Clique nos dados para selecionar...</span>
                            </div>

                            <!-- Botão rolar com Dados 3D -->
                            <button class="btn-escudo-rolar" id="escudo-btn-rolar" disabled onclick="escudoExecutarRolagem()">
                                <i class="fas fa-dice"></i> Rolar Dados
                            </button>
                        </div>

                        <!-- Anotações -->
                        <div id="escudo-tab-anotacoes" class="escondido">
                            <div class="form-investigacao" style="background:rgba(0,0,0,.3);padding:30px;border-radius:20px;">
                                <div class="secao-anotacao"><h3>GERAL:</h3><div class="textarea-p1" id="anot-geral-input" contenteditable="true" placeholder="Informações gerais ao longo da sessão..."></div></div>
                                <div class="secao-anotacao" style="margin-top:20px;"><h3>Sessões Futuras:</h3><div class="textarea-p1" id="anot-futuras-input" contenteditable="true" placeholder="Notas de possíveis eventos futuros..."></div></div>
                                <div class="secao-anotacao" style="margin-top:20px;"><h3>Sessões Anteriores:</h3><div class="textarea-p1" id="anot-anteriores-input" contenteditable="true" placeholder="Eventos importantes que ocorreram..."></div></div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

        </div>
    </main>

    <footer class="rodape-principal">
        <div class="rodape-conteudo">
            <div class="rodape-logo-area">
                <div class="rodape-marca"><img src="../img/logo_branco.png" alt="Logo TABLE"><span>TABLE</span></div>
                <p>Acompanhe uma experiência imersiva nos mundos de RPG. Aprenda e jogue com seus amigos!</p>
            </div>
            <div class="rodape-links">
                <h4>Navegação</h4>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="cm-jogar.php">Como Jogar</a></li>
                    <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a></li>
                    <li><a href="criar-mapa.php">Mundos</a></li>
                    <li><a href="rolagem-de-dados.php">Dados</a></li>
                    <li><a href="sobre-nos.php">Sobre Nós</a></li>
                </ul>
            </div>
            <div class="rodape-links">
                <h4>Jogar</h4>
                <ul>
                    <li><a href="cm-jogador.php">Como Player</a></li>
                    <li><a href="cm-mestre.php">Como Mestre</a></li>
                    <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Campanhas</a></li>
                    <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Meu Perfil</a></li>
                </ul>
            </div>
        </div>
        <div class="rodape-inferior">
            <p>© 2026 TABLE. Todos os direitos reservados.</p>
            <div class="redes-sociais">
                <a href="#"><i class="fa-brands fa-discord"></i></a>
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <!-- MODAIS -->

    <!-- 1. FOTO DE CAPA -->
    <div id="modal-foto-capa" class="modal-overlay">
        <div class="modal-box" style="max-width:450px;">
            <i class="fas fa-times modal-close" onclick="fecharModal('modal-foto-capa')"></i>
            <h2 style="color:#fff;text-transform:uppercase;letter-spacing:1px;">Foto de Capa</h2>
            <div class="modal-body-central">
                <p style="color:#888;font-size:.85rem;margin-bottom:25px;text-align:center;">Recomendado: 1200x300px.</p>
                <input type="file" id="input-foto-capa" style="display:none;" accept="image/*" onchange="previewCapa(this)">
                <button class="btn-confirmar-rolagem" onclick="document.getElementById('input-foto-capa').click()">
                    <i class="fas fa-cloud-upload-alt"></i> Selecionar Imagem
                </button>
            </div>
        </div>
    </div>

    <!-- 2. ADICIONAR PERSONAGENS -->
    <div id="modal-adc-Personagems" class="modal-overlay">
        <div class="modal-box" style="max-width:700px;">
            <i class="fas fa-times modal-close" onclick="fecharModal('modal-adc-Personagems')"></i>
            <h2 style="color:#fff;text-transform:uppercase;letter-spacing:1px;">Adicionar Personagens</h2>
            <p style="color:#888;font-size:.85rem;margin-bottom:20px;">Escolha um personagem para integrar à sua campanha:</p>
            <div class="modal-lista-Personagems" id="modal-meus-personagens">
                <p style="text-align:center;padding:20px;color:#888;">Carregando grimório de personagens...</p>
            </div>
        </div>
    </div>

    <!-- 3. CONVIDAR JOGADORES -->
    <div id="modal-link-convite" class="modal-overlay">
        <div class="modal-box" style="max-width:500px;">
            <i class="fas fa-times modal-close" onclick="fecharModal('modal-link-convite')"></i>
            <h2 style="color:#fff;text-transform:uppercase;letter-spacing:1px;">
                <i class="fas fa-link" style="color:var(--premium-accent);margin-right:10px;"></i>
                Convite da Campanha
            </h2>
            <p style="color:#888;font-size:.85rem;margin-bottom:15px;text-align:center;">
                Compartilhe o link abaixo. Expira em <strong style="color:#fff;">7 dias</strong>.
            </p>

            <div id="convite-loading" style="text-align:center;padding:20px;display:none;">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--premium-accent);"></i>
                <p style="margin-top:10px;color:#aaa;">Gerando link...</p>
            </div>

            <div id="convite-resultado" style="display:none;">
                <div class="bloco-link-convite" style="background:rgba(255,255,255,.05);border:1px dashed rgba(255,255,255,.2);padding:20px;border-radius:12px;word-break:break-all;">
                    <a href="#" id="texto-link-campanha" target="_blank"
                       style="color:var(--premium-accent);font-weight:700;text-decoration:none;font-size:.9rem;"></a>
                </div>
                <div class="modal-footer-acoes" style="margin-top:20px;gap:15px;display:flex;">
                    <button class="btn-resetar" onclick="gerarTokenConvite()" style="border-radius:12px;flex:1;">
                        <i class="fas fa-sync"></i> Novo Link
                    </button>
                    <button class="btn-copiar" id="btn-copiar-convite" onclick="copiarLinkConvite()"
                            style="border-radius:12px;flex:1;background:var(--premium-accent);border:none;color:#fff;font-weight:700;padding:12px;cursor:pointer;">
                        <i class="fas fa-copy"></i> Copiar Link
                    </button>
                </div>
            </div>

            <div id="convite-erro" style="display:none;color:#ff4d4d;text-align:center;padding:20px;">
                <i class="fas fa-exclamation-triangle" style="font-size:2rem;"></i>
                <p style="margin-top:10px;">Erro ao gerar o link. Tente novamente.</p>
                <button onclick="gerarTokenConvite()"
                        style="margin-top:15px;background:var(--premium-accent);border:none;color:#fff;padding:10px 25px;border-radius:10px;cursor:pointer;font-weight:700;">
                    Tentar novamente
                </button>
            </div>
        </div>
    </div>

    <!-- 4. FICHA DE MONSTRO -->
    <div class="modal-overlay" id="modal-ficha-monstro">
        <div class="modal-box" id="ficha-monstro-render"
             style="width: 550px; max-height: 80vh; padding: 0; background: #0c0816; overflow-y: auto; overflow-x: hidden; border: 1.5px solid var(--premium-accent); border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.85);"></div>
    </div>

    <!-- 5. DETALHES DO JOGADOR -->
    <div class="modal-overlay" id="modal-detalhes-jogador" onclick="fecharModal('modal-detalhes-jogador')">
        <div class="modal-box" onclick="event.stopPropagation()" style="width: 550px; padding: 0; background: #0c0816; overflow: hidden; border: 1px solid var(--premium-accent); border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.8);">
            <div style="background: linear-gradient(135deg, #1e0b3a, #311c61); padding: 30px 25px; border-bottom: 1px solid rgba(255,255,255,0.1); position: relative;">
                <button onclick="fecharModal('modal-detalhes-jogador')" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer; opacity: 0.7; transition: opacity 0.2s;"><i class="fas fa-times"></i></button>
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="width: 90px; height: 90px; border-radius: 50%; border: 3px solid var(--premium-accent); overflow: hidden; box-shadow: 0 0 20px rgba(139, 92, 246, 0.4);">
                        <img id="player-modal-foto" src="../img/uploads/perfil/avatar1.png" alt="Foto do Jogador" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div>
                        <h2 id="player-modal-nome" style="color: #fff; font-weight: 800; font-size: 1.6rem; margin: 0 0 5px 0;">Nome do Jogador</h2>
                        <span id="player-modal-papel" style="background: rgba(157, 122, 255, 0.15); color: var(--cor-destaque-claro); font-weight: 700; font-size: 0.75rem; padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(157,122,255,0.3); display: inline-flex; align-items: center; gap: 4px;">
                            <i class="fas fa-crown"></i> Mestre
                        </span>
                    </div>
                </div>
            </div>
            
            <div style="padding: 30px 25px; display: flex; flex-direction: column; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="color: var(--premium-accent); font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Nome de Usuário (Username):</label>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 12px 15px; border-radius: 10px; color: #fff; font-weight: 500;" id="player-modal-username">—</div>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="color: var(--premium-accent); font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">E-mail:</label>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 12px 15px; border-radius: 10px; color: #fff; font-weight: 500;" id="player-modal-email">—</div>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="color: var(--premium-accent); font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Data de Nascimento:</label>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 12px 15px; border-radius: 10px; color: #fff; font-weight: 500;" id="player-modal-nascimento">—</div>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="color: var(--premium-accent); font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Biografia:</label>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 15px; border-radius: 10px; color: #ccc; font-size: 0.95rem; line-height: 1.5; min-height: 80px; white-space: pre-wrap;" id="player-modal-bio">Esse jogador não escreveu nenhuma biografia ainda...</div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/nav-global.js" defer></script>
    <script>
    // ============================================================
    // DADOS PHP → JS
    // ============================================================
    const campanhaInicial            = <?= json_encode($campanhaDados ?: null) ?: 'null' ?>;
    const campanhaInicialPersonagems = <?= json_encode($PersonagemsCampanha ?: []) ?: '[]' ?>;
    const combatesCampanha           = <?= json_encode($combatesCampanha ?: []) ?: '[]' ?>;
    const sistemaStatusBarras        = <?= json_encode($sistemaStatusBarras ?: []) ?: '[]' ?>;
    const sistemaStatusDefesas       = <?= json_encode($sistemaStatusDefesas ?: []) ?: '[]' ?>;
    const idCampanha                 = <?= $id_campanha ? (int)$id_campanha : 'null' ?>;
    const usuarioLogadoId            = <?= isset($_SESSION['usuario']['id']) ? (int)$_SESSION['usuario']['id'] : 'null' ?>;
    const isMasterLogado             = <?= $isMaster ? 'true' : 'false' ?>;

    // Captura de erros JS — apenas console (não bloqueia o usuário com alert)
    window.onerror = function(message, source, lineno, colno, error) {
        console.error('🚨 JS Error:', message, '\nAt:', source, 'L:' + lineno, '\nStack:', error ? error.stack : 'N/A');
        return false;
    };
    window.addEventListener('unhandledrejection', function(event) {
        console.error('🚨 Promise rejeitada:', event.reason);
    });

    // ============================================================
    // INICIALIZAÇÃO
    // ============================================================
    document.addEventListener('DOMContentLoaded', () => {
        // Lógica de Tema Dinâmico para Seleção de Sistema
        const selectSistema = document.getElementById('selecao-sistema');
        if (selectSistema) {
            selectSistema.addEventListener('change', function() {
                const texto = this.options[this.selectedIndex].text.toLowerCase();
                if (texto.includes('ordem paranormal')) {
                    document.body.classList.add('tema-ordem-paranormal');
                } else {
                    document.body.classList.remove('tema-ordem-paranormal');
                }
            });
        }

        if (campanhaInicial) {
            document.getElementById('display-nome-campanha').textContent    = campanhaInicial.nm_campanha;
            document.getElementById('display-descricao-campanha').innerHTML = campanhaInicial.ds_descricao;
            if (campanhaInicial.ds_imagem) {
                const banner = document.getElementById('banner-campanha-display');
                banner.style.backgroundImage = `url('${campanhaInicial.ds_imagem}')`;
                banner.classList.remove('escondido');
            }

            // Inicializar funcionalidades do Escudo do Mestre
            inicializarFichasEscudo();
            renderInvestigacoes();
            renderRelatorios();
            inicializarAnotacoes();

            // Redirecionamento de abas via query params
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            if (activeTab === 'combates') {
                switchDashboardTab('combates');
            }
        }

        const hash  = window.location.hash.replace('#','');
        const valid = ['sessao-criar','sessao-detalhes','sessao-editar','sessao-combate','sessao-escudo'];
        if (hash && valid.includes(hash)) {
            if      (hash === 'sessao-editar')  irParaEditar();
            else if (hash === 'sessao-combate') irParaCombate();
            else if (hash === 'sessao-escudo')  irParaEscudo();
            else showSection(hash);
        } else {
            showSection(campanhaInicial ? 'sessao-detalhes' : 'sessao-criar');
        }

        if (campanhaInicialPersonagems.length > 0) {
            iniciativaLista = campanhaInicialPersonagems.map(a => ({
                ...a, tipo:'Personagem', iniciativa: Math.floor(Math.random()*20)+1
            })).sort((a,b) => b.iniciativa - a.iniciativa);
            participanteSelecionado = iniciativaLista[0];
            renderListaIniciativa();
            renderDetalheParticipante();
        }

        const bB = document.getElementById('btn-bold');
        const bI = document.getElementById('btn-italic');
        const bU = document.getElementById('btn-underline');
        if (bB) bB.onclick = () => document.execCommand('bold',false,null);
        if (bI) bI.onclick = () => document.execCommand('italic',false,null);
        if (bU) bU.onclick = () => document.execCommand('underline',false,null);

        // ============================================================
        // CRIAR / EDITAR CAMPANHA — formulário de nova campanha
        // ============================================================
        const formCriar = document.getElementById('form-criar-campanha');
        if (formCriar) {
            formCriar.onsubmit = async function(e) {
                e.preventDefault();
                const btn  = e.target.querySelector('.btn-criar-campanha');
                const orig = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';
                const payload = {
                    id_campanha: idCampanha,
                    nome:        document.getElementById('nome-campanha').value.trim(),
                    id_sistema:  document.getElementById('selecao-sistema').value,
                    descricao:   document.getElementById('descricao-campanha').innerHTML
                };
                if (!payload.nome || !payload.id_sistema) {
                    alert('Preencha o nome e selecione um sistema.');
                    btn.disabled = false;
                    btn.innerHTML = orig;
                    return;
                }
                try {
                    const res  = await fetch('../app/ajax/salvar-campanha.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
                    const data = await res.json();
                    if (data.success) window.location.href = `criar-campanha.php?id=${data.id_campanha}`;
                    else alert('Erro ao criar campanha: ' + (data.error || 'Desconhecido'));
                } catch(err) { console.error(err); alert('Erro de conexão ao criar campanha.'); }
                finally    { btn.disabled=false; btn.innerHTML=orig; }
            };
        }
    });

    // ============================================================
    // DROPDOWN DE SISTEMA
    // ============================================================
    async function carregarDetalhesSistema(id, targetId = 'sistema-showcase') {
        const showcase = document.getElementById(targetId);
        if (!id) { showcase.classList.add('escondido'); return; }
        try {
            const res  = await fetch(`../app/ajax/get-sistema-detalhes.php?id=${id}`);
            const data = await res.json();
            if (data.success) {
                const sis = data.sistema;
                
                // Extrai apenas o conteúdo do primeiro bloco (sem o título)
                let descExibir = 'Sem descrição disponível.';
                if (sis.ds_descricao) {
                    const blocos = sis.ds_descricao.split(/[\r\n]{2,}/);
                    if (blocos.length > 0) {
                        const primeiroBloco = blocos[0].trim();
                        const linhas = primeiroBloco.split(/[\r\n]+/);
                        if (linhas.length > 1) {
                            // Ignora a primeira linha se for um título e junta as demais
                            descExibir = linhas.slice(1).join('\n').trim();
                        } else {
                            // Se tiver apenas uma linha
                            descExibir = primeiroBloco;
                        }
                    }
                }

                showcase.innerHTML = `
                    <div class="sistema-showcase-clean">
                        <div class="sistema-clean-header">
                            <img src="${sis.ds_imagem||'../img/logo_icone.png'}" alt="${sis.nm_sistema}" class="cartaz-sistema-clean">
                            <h2>${sis.nm_sistema}</h2>
                        </div>
                        <p class="sistema-clean-descricao">${descExibir}</p>
                    </div>`;
                showcase.classList.remove('escondido');

                // Mudar fundo se o sistema tiver um background oficial definido no banco
                if(sis.ds_background) {
                    document.body.style.setProperty('--tema-background', `url('${sis.ds_background}')`);
                    document.body.style.backgroundImage = `linear-gradient(rgba(0,0,0,0.85), rgba(0,0,0,0.85)), url('${sis.ds_background}')`;
                    document.body.style.backgroundSize = 'cover';
                    document.body.style.backgroundPosition = 'center';
                    document.body.style.backgroundAttachment = 'fixed';
                    document.body.style.transition = 'background 0.5s ease-in-out';
                } else {
                    document.body.style.removeProperty('--tema-background');
                    document.body.style.backgroundImage = 'none';
                    document.body.style.backgroundColor = '#311c61';
                }

                // Garantir aplicação robusta do tema oficial de Ordem Paranormal com base no nome do sistema
                const nomeSisLower = (sis.nm_sistema || '').toLowerCase();
                if (nomeSisLower.includes('ordem paranormal')) {
                    document.body.classList.add('tema-ordem-paranormal');
                } else {
                    document.body.classList.remove('tema-ordem-paranormal');
                }
            }
        } catch(e) { console.error('Erro ao carregar sistema:', e); }
    }

    // ============================================================
    // NAVEGAÇÃO
    // ============================================================
    function showSection(id) {
        ['sessao-criar','sessao-detalhes','sessao-editar','sessao-combate','sessao-escudo'].forEach(s => {
            const el = document.getElementById(s);
            if (!el) return;
            if (s === id) { el.style.display='block'; el.classList.remove('escondido'); }
            else          { el.style.display='none';  el.classList.add('escondido'); }
        });
        if (history.replaceState) history.replaceState(null,null,'#'+id);
        else window.location.hash = id;
        window.scrollTo({top:0,behavior:'smooth'});
    }

    function abrirModal(id)  { const m=document.getElementById(id); if(m) m.classList.add('ativo'); }
    function fecharModal(id) { const m=document.getElementById(id); if(m) m.classList.remove('ativo'); }

    function abrirModalJogador(card) {
        const foto = card.dataset.foto;
        const nome = card.dataset.nome;
        const papel = card.dataset.papel;
        const username = card.dataset.username;
        const email = card.dataset.email;
        const nascimento = card.dataset.nascimento;
        const bio = card.dataset.bio;

        document.getElementById('player-modal-foto').src = foto;
        document.getElementById('player-modal-nome').textContent = nome;
        
        const papelBadge = document.getElementById('player-modal-papel');
        if (papel === 'mestre') {
            papelBadge.style.background = 'rgba(157, 122, 255, 0.15)';
            papelBadge.style.color = 'var(--cor-destaque-claro)';
            papelBadge.style.borderColor = 'rgba(157,122,255,0.3)';
            papelBadge.innerHTML = '<i class="fas fa-crown"></i> Mestre';
        } else {
            papelBadge.style.background = 'rgba(0, 200, 100, 0.15)';
            papelBadge.style.color = '#00c864';
            papelBadge.style.borderColor = 'rgba(0,200,100,0.3)';
            papelBadge.innerHTML = '<i class="fas fa-user"></i> Jogador';
        }

        document.getElementById('player-modal-username').textContent = username;
        document.getElementById('player-modal-email').textContent = email;
        document.getElementById('player-modal-nascimento').textContent = nascimento;
        document.getElementById('player-modal-bio').textContent = bio && bio.trim() !== '' ? bio : 'Esse jogador não escreveu nenhuma biografia ainda...';

        abrirModal('modal-detalhes-jogador');
    }

    function irParaEditar() {
        document.getElementById('nome-campanha-edit').value         = document.getElementById('display-nome-campanha').textContent;
        document.getElementById('descricao-campanha-edit').innerHTML = document.getElementById('display-descricao-campanha').innerHTML;
        const idSis = <?= ($campanhaDados && isset($campanhaDados['id_sistema'])) ? (int)$campanhaDados['id_sistema'] : 'null' ?>;
        if (idSis) {
            document.getElementById('selecao-sistema-edit').value = idSis;
            carregarDetalhesSistema(idSis, 'sistema-showcase-edit');
        }
        showSection('sessao-editar');
    }

    function irParaCombate() { showSection('sessao-combate'); renderCatalogo(); }
    function irParaEscudo()  { document.getElementById('escudo-titulo-campanha').textContent = document.getElementById('display-nome-campanha').textContent; showSection('sessao-escudo'); if (!escudoDddiceSDK) { initEscudoSDK(); inicializarEventosDadosEscudo(); } iniciarEscudoPolling(); }
    function fecharEscudo()  { showSection('sessao-detalhes'); pararEscudoPolling(); }

    let escudoPollInterval = null;

    function iniciarEscudoPolling() {
        if (escudoPollInterval) clearInterval(escudoPollInterval);
        
        // Executa imediatamente e depois a cada 3 segundos
        atualizarFichasEscudoEmTempoReal();
        escudoPollInterval = setInterval(atualizarFichasEscudoEmTempoReal, 3000);
    }

    function pararEscudoPolling() {
        if (escudoPollInterval) {
            clearInterval(escudoPollInterval);
            escudoPollInterval = null;
        }
    }

    async function atualizarFichasEscudoEmTempoReal() {
        if (!idCampanha) return;
        try {
            const res = await fetch(`criar-campanha.php?action=get_personagens_escudo&id_campanha=${idCampanha}`);
            const data = await res.json();
            if (data.sucesso && data.personagens) {
                // 1. Atualizar array global local
                campanhaInicialPersonagems = data.personagens;
                
                // 2. Atualizar UI do escudo (Aba de Personagens)
                data.personagens.forEach(p => {
                    const card = document.querySelector(`.card-agente-compacto[data-id-personagem="${p.id_personagem}"]`);
                    if (card) {
                        // Atualizar cabeçalho (Nivel, Jogador, Classe, Origem, Imagem)
                        const header = card.querySelector('.card-compacto-header');
                        if (header) {
                            const pClasseOrigem = header.querySelector('p:not(.nome-jogador-p1)');
                            if (pClasseOrigem) {
                                pClasseOrigem.textContent = `${p.nm_classe || 'Mundano'} • ${p.nm_origem || 'Cidadão'}`;
                            }
                            const pJogador = header.querySelector('.nome-jogador-p1');
                            if (pJogador) {
                                pJogador.textContent = `Jogador: ${p.jogador_nome || 'Sem jogador'}`;
                            }
                            const spanNex = header.querySelector('.nivel-badge');
                            if (spanNex) {
                                spanNex.textContent = `Nivel: ${p.qt_nivel}`;
                            }
                            const imgAvatar = header.querySelector('img');
                            if (imgAvatar) {
                                imgAvatar.src = p.ds_foto || '../img/uploads/perfil/avatar1.png';
                            }
                        }
                        
                        // Atualizar todos os atributos dinamicamente
                        const attrArea = card.querySelector('.atributos-agente-p1');
                        if (attrArea && p.atributos) {
                            attrArea.innerHTML = p.atributos.map(attr => {
                                const abrev = attr.ds_abreviacao || (attr.nm_atributo ? attr.nm_atributo.substring(0, 3).toUpperCase() : 'ATT');
                                return `
                                    <div class="attr-p1-box">
                                        <span>${abrev.toUpperCase()}</span>
                                        <div>${attr.qt_valor}</div>
                                    </div>
                                `;
                            }).join('');
                        }
                        
                        // Atualizar barras de status
                        const statusArea = card.querySelector('.status-bars-p1');
                        if (statusArea && p.status_barras) {
                            statusArea.innerHTML = p.status_barras.map(barra => {
                                const curVal = parseInt(barra.qt_atual) || 0;
                                const maxVal = parseInt(barra.qt_max) || 1;
                                const pct = maxVal > 0 ? Math.min(100, Math.max(0, (curVal / maxVal) * 100)) : 0;
                                const cor = barra.ds_cor || '#9d7aff';
                                return `
                                    <div class="barra-p1-container">
                                        <div class="barra-p1-label">${barra.nm_status}</div>
                                        <div class="barra-p1-bg">
                                            <div class="barra-p1-fill" style="width:${pct}%; background-color: ${cor};"></div>
                                            <div class="barra-p1-text">${curVal}/${maxVal}</div>
                                        </div>
                                    </div>
                                `;
                            }).join('');
                        }
                        
                        // Atualizar valores do footer compacto (Defesas)
                        const footer = card.querySelector('.compacto-footer');
                        if (footer) {
                            const statsGrid = footer.querySelector('.footer-stats-grid');
                            if (statsGrid && p.status_defesas) {
                                statsGrid.innerHTML = p.status_defesas.map(def => {
                                    const cor = def.ds_cor || '#95a5a6';
                                    const val = def.qt_atual;
                                    const shortName = def.nm_status.substring(0, 3).toUpperCase();
                                    return `
                                        <div class="mini-shield-item" style="display: flex; flex-direction: column; align-items: center; position: relative;">
                                            <i class="fas fa-shield-alt" style="font-size: 1.8rem; color: ${cor}; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"></i>
                                            <span style="position: absolute; top: 6px; font-size: 0.75rem; font-weight: 900; color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.8);">${val}</span>
                                            <span style="font-size: 0.55rem; color: #aaa; font-weight: 700; text-transform: uppercase; margin-top: 4px;">${shortName}</span>
                                        </div>
                                    `;
                                }).join('');
                            }
                        }
                    }
                    
                    // 3. Sincronizar em tempo real com o Combate Ativo (Iniciativa + Detalhes)
                    if (iniciativaLista && iniciativaLista.length > 0) {
                        const participante = iniciativaLista.find(part => part.tipo === 'Personagem' && part.id_personagem == p.id_personagem);
                        if (participante) {
                            participante.qt_vida = parseInt(p.qt_vida) || 0;
                            participante.qt_vida_maxima = parseInt(p.qt_vida_maxima) || 1;
                            participante.qt_sanidade = parseInt(p.qt_sanidade) || 0;
                            participante.qt_sanidade_maxima = parseInt(p.qt_sanidade_maxima) || 1;
                            participante.qt_esforco = parseInt(p.qt_esforco) || 0;
                            participante.qt_esforco_maximo = parseInt(p.qt_esforco_maximo) || 1;
                            participante.qt_defesa = parseInt(p.qt_defesa) || 10;
                            participante.qt_bloqueio = parseInt(p.qt_bloqueio) || 0;
                            participante.qt_esquiva = parseInt(p.qt_esquiva) || (parseInt(p.qt_defesa) || 10);
                            participante.atributos = p.atributos;
                            participante.itens = p.itens;
                            participante.habilidades = p.habilidades;
                            participante.nm_classe = p.nm_classe;
                            participante.nm_origem = p.nm_origem;
                            participante.status_barras = p.status_barras;
                            participante.status_defesas = p.status_defesas;
                            
                            // Re-renderizar track de iniciativa do combate ativo
                            renderListaIniciativa();
                            
                            // Se o participante selecionado for este personagem, atualizar o painel de detalhes na hora
                            if (participanteSelecionado && participanteSelecionado.id_personagem == p.id_personagem) {
                                participanteSelecionado = participante;
                                renderDetalheParticipante();
                            }
                        }
                    }
                });
            }
        } catch (e) {
            console.error('Erro na sincronização das fichas no Escudo:', e);
        }
    }

    // ============================================================
    // EDITAR CAMPANHA (salvar edição)
    // ============================================================
    async function salvarEdicao() {
        const payload = {
            id_campanha: idCampanha,
            nome:        document.getElementById('nome-campanha-edit').value,
            id_sistema:  document.getElementById('selecao-sistema-edit').value,
            descricao:   document.getElementById('descricao-campanha-edit').innerHTML
        };
        try {
            const res  = await fetch('../app/ajax/salvar-campanha.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
            const data = await res.json();
            if (data.success) {
                document.getElementById('display-nome-campanha').textContent      = payload.nome;
                document.getElementById('display-descricao-campanha').innerHTML   = payload.descricao;
                const toast = document.createElement('div');
                toast.style.cssText = 'position:fixed;bottom:30px;left:50%;transform:translateX(-50%);background:#0c9447;color:#fff;padding:14px 30px;border-radius:12px;font-weight:700;z-index:99999;';
                toast.innerHTML = '<i class="fas fa-check-circle"></i> Campanha atualizada!';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
                showSection('sessao-detalhes');
            } else { alert('Erro ao salvar: ' + data.error); }
        } catch(e) { console.error(e); }
    }

    // ============================================================
    // SISTEMA DE CONVITE UUID
    // ============================================================
    function abrirModalConvite() {
        ['convite-loading','convite-resultado','convite-erro']
            .forEach(id => document.getElementById(id).style.display = 'none');
        abrirModal('modal-link-convite');
        gerarTokenConvite();
    }

    async function gerarTokenConvite() {
        if (!idCampanha) { alert('Salve a campanha primeiro.'); return; }
        document.getElementById('convite-loading').style.display   = 'block';
        document.getElementById('convite-resultado').style.display = 'none';
        document.getElementById('convite-erro').style.display      = 'none';
        try {
            const fd = new FormData();
            fd.append('action', 'gerar_convite');
            fd.append('campaign_id', idCampanha);
            const res  = await fetch('criar-campanha.php', {method:'POST', body:fd});
            const data = await res.json();
            document.getElementById('convite-loading').style.display = 'none';
            if (data.sucesso) {
                const linkEl = document.getElementById('texto-link-campanha');
                linkEl.href        = data.link;
                linkEl.textContent = data.link;
                document.getElementById('convite-resultado').style.display = 'block';
            } else {
                document.getElementById('convite-erro').style.display = 'block';
            }
        } catch(e) {
            console.error(e);
            document.getElementById('convite-loading').style.display = 'none';
            document.getElementById('convite-erro').style.display    = 'block';
        }
    }

    async function copiarLinkConvite() {
        const link = document.getElementById('texto-link-campanha').textContent;
        try {
            await navigator.clipboard.writeText(link);
            const btn = document.getElementById('btn-copiar-convite');
            btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
            btn.style.background = '#0c9447';
            setTimeout(() => { btn.innerHTML='<i class="fas fa-copy"></i> Copiar Link'; btn.style.background=''; }, 2500);
        } catch { alert('Copie manualmente:\n\n' + link); }
    }

    // Aliases para compatibilidade com o HTML original
    function resetarLink() { gerarTokenConvite(); }
    function copiarLink()  { copiarLinkConvite(); }

    // ============================================================
    // PERSONAGENS
    // ============================================================
    function switchDashboardTab(tab) {
        ['personagens','combates','jogadores','dados'].forEach(t => {
            const aba   = document.getElementById('aba-'+t);
            let listId = 'lista-combates';
            if (t === 'personagens') listId = 'lista-Personagems';
            if (t === 'jogadores') listId = 'lista-jogadores';
            if (t === 'dados') listId = 'lista-dados-jogador';
            
            const lista = document.getElementById(listId);
            if (t === tab) { 
                if(aba) aba.classList.add('ativa');    
                if(lista) lista.classList.remove('escondido'); 
            } else { 
                if(aba) aba.classList.remove('ativa'); 
                if(lista) lista.classList.add('escondido'); 
            }
        });
    }

    function irParaAbaDashboard(tab) {
        fecharEscudo();
        switchDashboardTab(tab);
        const target = document.querySelector('.sub-nav-campanha') || document.querySelector('.sessao-detalhes');
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    }


    async function abrirModalPersonagens() {
        abrirModal('modal-adc-Personagems');
        const container = document.getElementById('modal-meus-personagens');
        try {
            const res  = await fetch(`../app/ajax/get-meus-personagens.php?id_campanha=${idCampanha}`);
            const data = await res.json();
            if (data.success) {
                if (!data.personagens.length) { container.innerHTML='<p style="text-align:center;padding:20px;color:#888;">Você não tem personagens disponíveis.</p>'; return; }
                container.innerHTML = data.personagens.map(p=>`
                    <div class="card-Personagem">
                        <div class="avatar-Personagem"><img src="${p.ds_foto||'../img/uploads/perfil/avatar1.png'}" alt="Avatar"></div>
                        <div class="info-Personagem"><h3>${p.nm_personagem}</h3><p>${p.nm_sistema} - ${p.nm_classe||'Sem Classe'}</p></div>
                        <button class="btn-ver-ficha" onclick="vincularPersonagem(${p.id_personagem})"><i class="fas fa-plus-circle"></i> Adicionar</button>
                    </div>`).join('');
            }
        } catch(e) { console.error(e); }
    }

    async function vincularPersonagem(idP) {
        // Se não for o mestre, limita a no máximo 1 personagem na campanha
        if (!isMasterLogado) {
            const meusPersonagens = campanhaInicialPersonagems.filter(p => parseInt(p.id_dono) === usuarioLogadoId);
            if (meusPersonagens.length >= 1) {
                alert('Você já possui 1 personagem nesta campanha. Por favor, remova o seu personagem atual para poder adicionar outro.');
                return;
            }
        }

        try {
            const res  = await fetch('../app/ajax/adicionar-personagem-campanha.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id_campanha:idCampanha,id_personagem:idP})});
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert('Erro: ' + (data.error || 'Não foi possível adicionar o personagem.'));
            }
        } catch(e) { 
            console.error(e); 
            alert('Erro de conexão ao tentar adicionar o personagem.');
        }
    }

    async function removerPersonagem(idP) {
        if (!await TableModal.confirm('Deseja remover este Personagem da campanha?', 'Remover Personagem', 'warning')) return;
        try {
            const res  = await fetch('../app/ajax/remover-personagem-campanha.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id_campanha:idCampanha,id_personagem:idP})});
            const data = await res.json();
            if (data.success) location.reload();
        } catch(e) { console.error(e); }
    }

    async function removerCombate(idComb) {
        if (!await TableModal.confirm('Deseja excluir este combate?', 'Excluir Combate', 'warning')) return;
        try {
            const res  = await fetch('../app/ajax/remover-combate.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id_combate:idComb})});
            const data = await res.json();
            if (data.success) {
                window.location.href = `criar-campanha.php?id=${idCampanha}&tab=combates`;
            }
        } catch(e) { console.error(e); }
    }

    // ============================================================
    // COMBATE (CRIATURAS E CATÁLOGO)
    // ============================================================
    let ameacasCatalogo = [], ameacasSelecionadas = [], filtroAtual = 'Todos';
    let origemCriaturaFiltro = <?= $sistemaPersonalizado ? "'custom'" : "'oficial'" ?>; // 'custom', 'oficial' ou 'table'
    let idCombateSendoEditado = null;

    async function renderCatalogo() {
        const container = document.getElementById('catalogo-cards');
        const idSis = <?= ($campanhaDados && isset($campanhaDados['id_sistema'])) ? (int)$campanhaDados['id_sistema'] : 'null' ?>;
        
        if (ameacasCatalogo.length === 0) {
            try {
                // Carrega todos os monstros de todos os sistemas
                const res = await fetch(`../app/ajax/get-monstros.php`);
                const data = await res.json();
                if (data.success) {
                    ameacasCatalogo = data.monstros;
                }
            } catch(e) { console.error(e); }
        }
        
        const busca = document.getElementById('busca-ameaca').value.toLowerCase();
        
        const filtrados = ameacasCatalogo.filter(a => {
            const buscaLimpa = (busca || '').trim();
            const bateBusca = a.nm_monstro.toLowerCase().includes(buscaLimpa);
            let bateFiltro = false;
            
            if (origemCriaturaFiltro === 'table') {
                // Ao selecionar TABLE, os filtros de elemento não se aplicam
                bateFiltro = true;
            } else {
                if (filtroAtual === 'Todos') {
                    bateFiltro = true;
                } else if (filtroAtual === 'Mundano') {
                    const elM = (a.tp_monstro || '').toLowerCase().trim();
                    bateFiltro = (elM === 'pessoa' || elM === 'animal' || elM === 'mundano');
                } else {
                    bateFiltro = (a.tp_monstro === filtroAtual);
                }
            }
            
            const idMonstroInt = parseInt(a.id_monstro);
            const idSistemaInt = parseInt(a.id_sistema);
            
            let bateOrigem = false;
            if (origemCriaturaFiltro === 'oficial') {
                // Criaturas do sistema Ordem Paranormal (id_sistema = 1)
                bateOrigem = (idSistemaInt === 1);
            } else if (origemCriaturaFiltro === 'table') {
                // Criaturas do sistema TABLE (id_sistema = 2)
                bateOrigem = (idSistemaInt === 2);
            } else {
                // Criaturas customizadas (outro sistema da campanha)
                bateOrigem = (idSistemaInt === idSis && idSistemaInt !== 1 && idSistemaInt !== 2);
            }
            
            return bateBusca && bateFiltro && bateOrigem;
        });
        
        container.innerHTML = filtrados.map(a=>{
            let elClass = '';
            if (parseInt(a.id_sistema) === 2) {
                elClass = ' elemento-table';
            } else {
                let el = (a.tp_monstro || '').toLowerCase().trim();
                if(el === 'sangue') elClass = ' elemento-sangue';
                else if(el === 'morte') elClass = ' elemento-morte';
                else if(el === 'conhecimento') elClass = ' elemento-conhecimento';
                else if(el === 'energia') elClass = ' elemento-energia';
                else if(el === 'medo') elClass = ' elemento-medo';
                else if(el === 'pessoa' || el === 'animal' || el === 'mundano') elClass = ' elemento-mundano';
            }

            const fotoCapa = (a.ds_imagem && a.ds_imagem !== '../img/logo_icone.png' && a.ds_imagem !== '../img/uploads/perfil/avatar1.png') ? a.ds_imagem : '../img/uploads/perfil/avatar1.png';

            return `
            <div class="card-ameaca-premium${elClass}">
                <img src="${fotoCapa}" class="card-ameaca-img">
                <div class="card-ameaca-body">
                    <h4>${a.nm_monstro}</h4>
                    <div class="card-ameaca-details">
                        <span>${window.isOrdemParanormal ? 'VD' : 'VT'}: <b>${a.qt_vd||'???'}</b></span>
                        <span>${a.tp_monstro||'Ameaça'}</span>
                    </div>
                </div>
                <div class="card-ameaca-actions">
                    <button class="btn-card-ficha" onclick="verFichaMonstro(${a.id_monstro})">Ficha da Ameaça</button>
                    <button class="btn-card-add"   onclick="adicionarAmeaca(${a.id_monstro})">Adicionar</button>
                </div>
            </div>`;
        }).join('');
    }

    function selecionarOrigemCriatura(origem, element) {
        origemCriaturaFiltro = origem;
        document.querySelectorAll('.banners-flex .banner-card').forEach(b => b.classList.remove('ativo'));
        element.classList.add('ativo');
        
        const filtrosAmeacas = document.getElementById('filtros-ameacas');
        if (filtrosAmeacas) {
            if (origem === 'table') {
                filtrosAmeacas.style.display = 'none';
            } else {
                filtrosAmeacas.style.display = 'flex';
            }
        }
        
        renderCatalogo();
    }

    function redirecionarNovaCriatura() {
        const idSis = <?= ($campanhaDados && isset($campanhaDados['id_sistema'])) ? (int)$campanhaDados['id_sistema'] : 'null' ?>;
        if (idSis) {
            window.location.href = `exibir-sistema.php?id=${idSis}&action=criar_criatura`;
        }
    }

    function filtrarPorElemento(el, btn) {
        filtroAtual = el;
        document.querySelectorAll('#filtros-ameacas .btn-filtro').forEach(b=>b.classList.remove('ativo'));
        btn.classList.add('ativo');
        renderCatalogo();
    }

    function adicionarAmeaca(idM) { const a=ameacasCatalogo.find(x=>x.id_monstro==idM); if(a){ameacasSelecionadas.push(a);renderSelecionadas();} }
    function removerAmeaca(i)     { ameacasSelecionadas.splice(i,1); renderSelecionadas(); }

    function renderSelecionadas() {
        const c=document.getElementById('selecionadas-cards'); let vd=0;
        c.innerHTML = ameacasSelecionadas.map((a,i)=>{
            let elClass = '';
            if (parseInt(a.id_sistema) === 2) {
                elClass = ' elemento-table';
            } else {
                let el = (a.tp_monstro || '').toLowerCase().trim();
                if(el === 'sangue') elClass = ' elemento-sangue';
                else if(el === 'morte') elClass = ' elemento-morte';
                else if(el === 'conhecimento') elClass = ' elemento-conhecimento';
                else if(el === 'energia') elClass = ' elemento-energia';
                else if(el === 'medo') elClass = ' elemento-medo';
                else if(el === 'pessoa' || el === 'animal' || el === 'mundano') elClass = ' elemento-mundano';
            }

            vd+=parseInt(a.qt_vd||0);
            return `<div class="card-ameaca-premium${elClass}" style="background:rgba(255,255,255,.05);padding:8px;">
                <div class="card-ameaca-body"><h4 style="font-size:.9rem;">${a.nm_monstro}</h4><span style="font-size:.7rem;color:#aaa;">${window.isOrdemParanormal ? 'VD' : 'VT'}: <b>${a.qt_vd||0}</b></span></div>
                <button onclick="removerAmeaca(${i})" style="background:none;border:none;color:#888;cursor:pointer;"><i class="fas fa-trash"></i></button>
            </div>`;
        }).join('');
        document.getElementById('vd-total-valor').textContent = vd;
    }

    function novoCombate() {
        idCombateSendoEditado = null;
        document.getElementById('nome-combate-input').value = '';
        ameacasSelecionadas = [];
        renderSelecionadas();
        irParaCombate();
    }

    function editarCombate(id) {
        const combate = combatesCampanha.find(c => parseInt(c.id_combate) === id);
        if (!combate) return;
        idCombateSendoEditado = id;
        document.getElementById('nome-combate-input').value = combate.nm_combate;
        ameacasSelecionadas = combate.monstros ? [...combate.monstros] : [];
        renderSelecionadas();
        irParaCombate();
    }

    async function salvarCombate() {
        const nome = document.getElementById('nome-combate-input').value;
        if (!nome) { alert('Dê um nome ao combate!'); return; }
        try {
            const payload = {
                id_campanha: idCampanha,
                nome: nome,
                monstros: ameacasSelecionadas.map(a => a.id_monstro)
            };
            if (idCombateSendoEditado) {
                payload.id_combate = idCombateSendoEditado;
            }
            const res  = await fetch('../app/ajax/salvar-combate.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
            const data = await res.json();
            if (data.success) {
                idCombateSendoEditado = null;
                window.location.href = `criar-campanha.php?id=${idCampanha}&tab=combates`;
            } else {
                alert('Erro: '+data.error);
            }
        } catch(e) { console.error(e); }
    }

    async function verFichaMonstro(idM) {
        const c = document.getElementById('ficha-monstro-render');
        if (!c) return;
        c.innerHTML = '<div style="padding:40px;text-align:center;color:#888;"><i class="fas fa-spinner fa-spin"></i> Lendo Grimório...</div>';
        abrirModal('modal-ficha-monstro');
        try {
            const res  = await fetch(`../app/ajax/get-monstro-detalhes.php?id=${idM}`);
            const data = await res.json();
            if (data.success) {
                const m = data.monstro;
                const attrs = data.atributos;
                const imgCriatura = (m.ds_imagem && m.ds_imagem !== '../img/logo_icone.png' && m.ds_imagem !== '../img/uploads/perfil/avatar1.png') ? m.ds_imagem : '../img/uploads/perfil/avatar1.png';
                
                // Mapeamento dinâmico de cor de destaque baseado no elemento da criatura
                let corDestaque = 'var(--premium-accent)';
                if (parseInt(m.id_sistema) === 1) {
                    const el = (m.tp_monstro || '').toLowerCase().trim();
                    if (el === 'sangue') corDestaque = '#ff3232';
                    else if (el === 'morte') corDestaque = '#cfd8dc'; // Cinza claro legível no escuro
                    else if (el === 'conhecimento') corDestaque = '#f1c40f';
                    else if (el === 'energia') corDestaque = '#1565c0'; // Azul escuro
                    else if (el === 'medo') corDestaque = '#a855f7';
                    else if (el === 'pessoa' || el === 'animal' || el === 'mundano') corDestaque = '#4caf50'; // Verde
                } else if (parseInt(m.id_sistema) === 2) {
                    corDestaque = '#7b4ff7'; // Roxo TABLE
                }

                c.innerHTML = `
                    <div class="ficha-header-comp" style="position: sticky; top: 0; z-index: 100; background: linear-gradient(135deg, rgba(30, 11, 58, 0.95), rgba(49, 28, 97, 0.9)), url('${imgCriatura}') center/cover; padding: 25px 30px; border-bottom: 2px solid ${corDestaque}; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.4);">
                        <img src="${imgCriatura}" style="width: 100px; height: 100px; border-radius: 15px; border: 3px solid ${corDestaque}; object-fit: cover; box-shadow: 0 10px 30px rgba(0,0,0,0.8);" />
                        <div style="flex: 1;">
                            <h1 style="color: #fff; font-weight: 900; font-size: 2rem; margin-bottom: 5px; text-shadow: 0 5px 15px rgba(0,0,0,0.8);">${m.nm_monstro}</h1>
                            <span style="display: inline-block; background: ${corDestaque}; color: #fff; padding: 4px 12px; border-radius: 6px; font-weight: 800; font-size: 0.8rem; text-transform: uppercase;">${m.tp_monstro || 'Desconhecido'}</span>
                            <span style="display: inline-block; background: rgba(255, 50, 50, 0.2); border: 1px solid rgba(255, 50, 50, 0.5); color: #ff4d4d; padding: 4px 12px; border-radius: 6px; font-weight: 900; font-size: 0.8rem; margin-left: 10px;">${window.isOrdemParanormal ? 'VD' : 'VT'} ${m.qt_vd || '???'}</span>
                        </div>
                        <i class="fas fa-times" onclick="fecharModal('modal-ficha-monstro')" style="color: #fff; cursor: pointer; font-size: 1.5rem; filter: drop-shadow(0 2px 5px rgba(0,0,0,0.8)); transition: 0.3s;" onmouseover="this.style.color='${corDestaque}'" onmouseout="this.style.color='#fff'"></i>
                    </div>
                    <div style="padding: 25px; background: #0c0816;">
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px;">
                            <div style="background: rgba(255, 77, 77, 0.05); padding: 15px; border-radius: 12px; text-align: center; border: 1px solid rgba(255, 77, 77, 0.2);">
                                <span style="display: block; color: #ff4d4d; font-weight: 900; font-size: 0.75rem; margin-bottom: 5px; letter-spacing: 1px;"><i class="fas fa-heart"></i> ${window.isOrdemParanormal ? 'VIDA' : 'VIDA TOTAL (VT)'}</span>
                                <strong style="color: #fff; font-size: 1.8rem;">${window.isOrdemParanormal ? m.qt_vida : m.qt_vd}</strong>
                            </div>
                            <div style="background: rgba(41, 128, 185, 0.05); padding: 15px; border-radius: 12px; text-align: center; border: 1px solid rgba(41, 128, 185, 0.2);">
                                <span style="display: block; color: #3498db; font-weight: 900; font-size: 0.75rem; margin-bottom: 5px; letter-spacing: 1px;"><i class="fas fa-shield-alt"></i> DEFESA</span>
                                <strong style="color: #fff; font-size: 1.8rem;">${m.qt_defesa}</strong>
                            </div>
                            <div style="background: rgba(241, 196, 15, 0.05); padding: 15px; border-radius: 12px; text-align: center; border: 1px solid rgba(241, 196, 15, 0.2);">
                                <span style="display: block; color: #f1c40f; font-weight: 900; font-size: 0.75rem; margin-bottom: 5px; letter-spacing: 1px;"><i class="fas fa-star"></i> RECOMPENSA</span>
                                <strong style="color: #fff; font-size: 1.8rem;">${m.qt_xp_recompensa} <span style="font-size: 0.9rem; font-weight: normal; color: #aaa;">XP</span></strong>
                            </div>
                        </div>

                        <label style="color: ${corDestaque}; font-size: 0.8rem; font-weight: 900; text-transform: uppercase; margin-bottom: 10px; display: block; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px;">ATRIBUTOS PRINCIPAIS</label>
                        <div class="premium-atributos-grid">
                            ${attrs.map(a => `
                                <div class="premium-attr-box" style="height: 50px;">
                                    <span class="attr-abbr" style="font-size: 0.85rem; width: 50px; background: #fff; color: #1e0b3a;">${a.ds_abreviacao || a.nm_atributo.substring(0, 3).toUpperCase()}</span>
                                    <div class="attr-circle" style="border-color: ${a.qt_valor > 0 ? corDestaque : '#444'}; font-size: 1.2rem; border-style: solid; border-width: 3px; border-left: none;">${a.qt_valor}</div>
                                    <div class="tooltip" style="background: ${corDestaque};">${a.nm_atributo}</div>
                                </div>
                            `).join('')}
                        </div>

                        <label style="color: ${corDestaque}; font-size: 0.8rem; font-weight: 900; text-transform: uppercase; margin-bottom: 10px; display: block; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px;">DESCRIÇÃO / COMPORTAMENTO</label>
                        <div style="background: rgba(0,0,0,0.5); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <p style="color: #ccc; font-size: 0.95rem; line-height: 1.8; margin: 0; white-space: pre-wrap;">${m.ds_monstro || '<i style="opacity: 0.5;">Nenhuma descrição detalhada disponível nos tomos.</i>'}</p>
                        </div>
                    </div>`;
            }
        } catch(e) { console.error(e); }
    }

    // ============================================================
    // CAPA
    // ============================================================
    async function previewCapa(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];

        // Se for um GIF animado, envia diretamente mantendo a animação intacta
        if (file.type === 'image/gif' || file.name.toLowerCase().endsWith('.gif')) {
            const fd = new FormData();
            fd.append('foto', file, 'capa.gif');
            fd.append('id_campanha', idCampanha);
            try {
                const res  = await fetch('../app/ajax/salvar-foto-capa.php',{method:'POST',body:fd});
                const data = await res.json();
                if (data.success) { 
                    document.getElementById('banner-campanha-display').style.backgroundImage=`url('${data.url}')`; 
                    fecharModal('modal-foto-capa'); 
                    location.reload(); 
                } else {
                    alert('Erro no upload: '+data.error);
                }
            } catch(e) { console.error(e); }
            return;
        }

        if (typeof abrirCropperModal === 'function') {
            abrirCropperModal(file, 16/9, async (croppedBlob, croppedBase64) => {
                const fd = new FormData();
                const ext = (croppedBlob.type === 'image/gif' || (croppedBlob.name && croppedBlob.name.toLowerCase().endsWith('.gif'))) ? 'gif' : 'jpg';
                fd.append('foto', croppedBlob, `capa.${ext}`);
                fd.append('id_campanha', idCampanha);
                try {
                    const res  = await fetch('../app/ajax/salvar-foto-capa.php',{method:'POST',body:fd});
                    const data = await res.json();
                    if (data.success) { 
                        document.getElementById('banner-campanha-display').style.backgroundImage=`url('${data.url}')`; 
                        fecharModal('modal-foto-capa'); 
                        location.reload(); 
                    } else {
                        alert('Erro no upload: '+data.error);
                    }
                } catch(e) { console.error(e); }
            });
        } else {
            const fd = new FormData();
            fd.append('foto', file);
            fd.append('id_campanha', idCampanha);
            try {
                const res  = await fetch('../app/ajax/salvar-foto-capa.php',{method:'POST',body:fd});
                const data = await res.json();
                if (data.success) { 
                    document.getElementById('banner-campanha-display').style.backgroundImage=`url('${data.url}')`; 
                    fecharModal('modal-foto-capa'); 
                    location.reload(); 
                } else {
                    alert('Erro no upload: '+data.error);
                }
            } catch(e) { console.error(e); }
        }
    }

    // ============================================================
    // ESCUDO DO MESTRE
    // ============================================================
    let combateAtivo=null, iniciativaLista=[], indexTurno=0, subTabAtiva='atributos', participanteSelecionado=null;

    function switchEscudoTab(tab, btn) {
        if (!btn) return;
        document.querySelectorAll('.escudo-link-nav').forEach(b=>b.classList.remove('ativo'));
        btn.classList.add('ativo');
        ['personagens','combates','investigacoes','relatorios','dados','anotacoes'].forEach(t => {
            const el = document.getElementById('escudo-tab-'+t);
            if (el) el.classList[t===tab?'remove':'add']('escondido');
        });
    }

    function inicializarFichasEscudo() {
        document.querySelectorAll('.card-agente-compacto').forEach(card => {
            const header = card.querySelector('.card-compacto-header');
            if (header) {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    card.classList.toggle('recolhido');
                    const chevron = card.querySelector('.toggle-escudo-ficha');
                    if (chevron) {
                        if (card.classList.contains('recolhido')) {
                            chevron.style.transform = 'rotate(0deg)';
                        } else {
                            chevron.style.transform = 'rotate(180deg)';
                        }
                    }
                });
            }
        });
    }

    function iniciarCombateEscudo(id, nome) {
        const combate = combatesCampanha.find(c => parseInt(c.id_combate) === id);
        if (!combate) return;

        document.getElementById('escudo-combates-lista').classList.add('escondido');
        document.getElementById('escudo-combate-ativo').classList.remove('escondido');
        
        // Personagens reais da campanha
        const persList = campanhaInicialPersonagems.map(p => ({
            ...p,
            tipo: 'Personagem',
            iniciativa: Math.floor(Math.random() * 20) + 1,
            status_barras: p.status_barras ? JSON.parse(JSON.stringify(p.status_barras)) : [
                { nm_status: 'VIDA', ds_cor: '#ed1c24', qt_atual: parseInt(p.qt_vida) || 0, qt_max: parseInt(p.qt_vida_maxima) || 1, id_status_sistema: 'vida' },
                { nm_status: 'SANIDADE', ds_cor: '#a855f7', qt_atual: parseInt(p.qt_sanidade) || 0, qt_max: parseInt(p.qt_sanidade_maxima) || 1, id_status_sistema: 'sanidade' },
                { nm_status: 'ESFORÇO', ds_cor: '#f97316', qt_atual: parseInt(p.qt_esforco) || 0, qt_max: parseInt(p.qt_esforco_maximo) || 1, id_status_sistema: 'esforco' }
            ],
            status_defesas: p.status_defesas ? JSON.parse(JSON.stringify(p.status_defesas)) : [
                { nm_status: 'DEFESA', ds_cor: '#95a5a6', qt_atual: parseInt(p.qt_defesa) || 10, id_status_sistema: 'defesa' },
                { nm_status: 'BLOQUEIO', ds_cor: '#f39c12', qt_atual: parseInt(p.qt_bloqueio) || 0, id_status_sistema: 'bloqueio' },
                { nm_status: 'ESQUIVA', ds_cor: '#2980b9', qt_atual: parseInt(p.qt_esquiva) || (parseInt(p.qt_defesa) || 10), id_status_sistema: 'esquiva' }
            ]
        }));
        
        // Monstros reais do combate
        const monstList = (combate.monstros || []).map(m => {
            const mVida = parseInt(m.qt_vida) || 0;
            let mBars = [];
            let mDefs = [];
            
            if (m.id_sistema && sistemaStatusBarras && sistemaStatusBarras.length > 0) {
                mBars = sistemaStatusBarras.map(b => ({
                    nm_status: b.nm_status,
                    ds_cor: b.ds_cor,
                    qt_atual: mVida,
                    qt_max: mVida,
                    id_status_sistema: b.id_status_sistema
                }));
            } else {
                mBars = [
                    { nm_status: 'VIDA', ds_cor: '#ed1c24', qt_atual: mVida, qt_max: mVida, id_status_sistema: 'vida' }
                ];
            }

            if (m.id_sistema && sistemaStatusDefesas && sistemaStatusDefesas.length > 0) {
                mDefs = sistemaStatusDefesas.map(d => ({
                    nm_status: d.nm_status,
                    ds_cor: d.ds_cor,
                    qt_atual: parseInt(m.qt_defesa) || 10,
                    id_status_sistema: d.id_status_sistema
                }));
            } else {
                mDefs = [
                    { nm_status: 'DEFESA', ds_cor: '#95a5a6', qt_atual: parseInt(m.qt_defesa) || 10, id_status_sistema: 'defesa' }
                ];
            }

            return {
                ...m,
                nm_personagem: m.nm_monstro,
                tipo: 'monstro',
                iniciativa: Math.floor(Math.random() * 20) + 1,
                status_barras: mBars,
                status_defesas: mDefs,
                ds_foto: m.ds_imagem && m.ds_imagem !== '../img/logo_icone.png' && m.ds_imagem !== '../img/uploads/perfil/avatar1.png' ? m.ds_imagem : '../img/uploads/perfil/avatar1.png'
            };
        });
        
        iniciativaLista = [...persList, ...monstList].sort((a, b) => b.iniciativa - a.iniciativa);
        indexTurno = 0;
        participanteSelecionado = iniciativaLista[0] || null;
        
        renderListaIniciativa();
        renderDetalheParticipante();
    }

    function renderListaIniciativa() {
        const c = document.getElementById('lista-iniciativa-escudo'); if (!c) return;
        c.innerHTML = iniciativaLista.map((p,i)=> {
            let vidaAtual = p.qt_vida || 0;
            let vidaMax = p.qt_vida_maxima || 1;
            
            if (p.status_barras && p.status_barras.length > 0) {
                const barraVida = p.status_barras.find(b => b.nm_status.toLowerCase() === 'vida');
                if (barraVida) {
                    vidaAtual = barraVida.qt_atual;
                    vidaMax = barraVida.qt_max;
                } else {
                    vidaAtual = p.status_barras[0].qt_atual;
                    vidaMax = p.status_barras[0].qt_max;
                }
            }

            return `
            <div class="item-iniciativa ${i===indexTurno?'ativo':''}" onclick="selecionarParticipanteEscudo(${i})">
                <img src="${p.ds_foto||'../img/uploads/perfil/avatar1.png'}" class="img-iniciativa">
                <div class="info-iniciativa">
                    <h4 style="color:#fff;margin:0;font-size:.95rem;">${p.nm_personagem}</h4>
                    <div style="display:flex;gap:10px;margin-top:4px;">
                        <span style="color:#ff4d4d;font-size:.7rem;font-weight:700;"><i class="fas fa-heart"></i> ${vidaAtual}/${vidaMax}</span>
                    </div>
                </div>
                <input type="number" class="input-iniciativa-combate" value="${p.iniciativa}" 
                       onclick="event.stopPropagation()" 
                       onchange="alterarIniciativaParticipante(${i}, this.value)" 
                       style="width: 45px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; text-align: center; border-radius: 4px; font-weight: 800; margin-left: auto; outline: none;">
            </div>`;
        }).join('');
    }

    function alterarIniciativaParticipante(idx, val) {
        const novoVal = parseInt(val) || 0;
        const partOriginal = iniciativaLista[idx];
        if (!partOriginal) return;

        // Atualizar o valor de iniciativa do participante no array
        partOriginal.iniciativa = novoVal;

        // Manter referência para remapear o participante selecionado
        const idSel = participanteSelecionado ? (participanteSelecionado.tipo === 'Personagem' ? participanteSelecionado.id_personagem : participanteSelecionado.id_monstro) : null;
        const tipoSel = participanteSelecionado ? participanteSelecionado.tipo : null;

        // Salvar qual o participante do turno atual
        const partTurno = iniciativaLista[indexTurno];

        // Reordenar a lista decrescente pela iniciativa
        iniciativaLista.sort((a, b) => b.iniciativa - a.iniciativa);

        // Achar o novo index do participante do turno
        if (partTurno) {
            indexTurno = iniciativaLista.findIndex(x => x.tipo === partTurno.tipo && (x.tipo === 'Personagem' ? x.id_personagem == partTurno.id_personagem : x.id_monstro == partTurno.id_monstro));
            if (indexTurno === -1) indexTurno = 0;
        }

        // Achar o novo participante selecionado
        if (idSel && tipoSel) {
            const novoSel = iniciativaLista.find(x => x.tipo === tipoSel && (tipoSel === 'Personagem' ? x.id_personagem == idSel : x.id_monstro == idSel));
            if (novoSel) {
                participanteSelecionado = novoSel;
            }
        }

        renderListaIniciativa();
        renderDetalheParticipante();
    }

    function selecionarParticipanteEscudo(i) { indexTurno=i; participanteSelecionado=iniciativaLista[i]; renderListaIniciativa(); renderDetalheParticipante(); }

    function voltarTurnoEscudo() {
        if (iniciativaLista.length === 0) return;
        indexTurno = (indexTurno - 1 + iniciativaLista.length) % iniciativaLista.length;
        participanteSelecionado = iniciativaLista[indexTurno];
        renderListaIniciativa();
        renderDetalheParticipante();
    }

    function proximoTurnoEscudo() {
        if (iniciativaLista.length === 0) return;
        indexTurno = (indexTurno + 1) % iniciativaLista.length;
        participanteSelecionado = iniciativaLista[indexTurno];
        renderListaIniciativa();
        renderDetalheParticipante();
    }

    function renderDetalheParticipante() {
        const p=participanteSelecionado; if(!p) return;
        const c=document.getElementById('detalhe-participante-escudo'); if(!c) return;
        c.innerHTML=`
            <div class="detalhe-header"><h2>${p.nm_personagem}</h2><p>${p.nm_classe||'Ameaça'} • ${p.tipo==='Personagem'?'Personagem':'Monstro'}</p></div>
            <div class="barras-detalhes">
                ${(p.status_barras || []).map((barra, bIdx) => renderBarraAjustavel(barra.nm_status, barra.qt_atual, barra.qt_max, barra.id_status_sistema, barra.ds_cor)).join('')}
            </div>
            <div class="escudo-sub-nav">
                <div class="btn-sub-aba ${subTabAtiva==='atributos'?'ativa':''}" onclick="switchEscudoSubTab('atributos')">Atributos</div>
                <div class="btn-sub-aba ${subTabAtiva==='combates'?'ativa':''}"  onclick="switchEscudoSubTab('combates')">Descrição</div>
                <div class="btn-sub-aba ${subTabAtiva==='rituais'?'ativa':''}"   onclick="switchEscudoSubTab('rituais')">Habilidades</div>
            </div>
            <div id="escudo-sub-aba-content">${renderSubAbaContent(p)}</div>`;
    }

    function renderBarraAjustavel(label, atual, max, idStatus, cor) {
        const barColor = cor || '#9d7aff';
        return `<div class="barra-ajustavel">
            <div class="controle-recurso">
                <div style="display:flex;gap:5px;"><span class="btn-ajuste" onclick="ajustarRecurso('${idStatus}',-5)">-5</span><span class="btn-ajuste" onclick="ajustarRecurso('${idStatus}',-1)">-1</span></div>
                <div class="valor-barra" style="color:#fff;font-weight:800;">${label}: ${atual}/${max}</div>
                <div style="display:flex;gap:5px;"><span class="btn-ajuste" onclick="ajustarRecurso('${idStatus}',1)">+1</span><span class="btn-ajuste" onclick="ajustarRecurso('${idStatus}',5)">+5</span></div>
            </div>
            <div class="bg-barra-detalhe"><div class="fill-barra-detalhe" style="width:${max>0?(atual/max)*100:0}%; background-color: ${barColor};"></div></div>
        </div>`;
    }

    async function ajustarRecurso(idStatus, val) {
        if (!participanteSelecionado) return;
        
        const barra = (participanteSelecionado.status_barras || []).find(b => String(b.id_status_sistema) === String(idStatus));
        if (!barra) return;
        
        barra.qt_atual = Math.max(0, Math.min(barra.qt_max || 1, (parseInt(barra.qt_atual) || 0) + val));
        
        // Sincronizar com os campos legados para manter compatibilidade
        if (idStatus === 'vida' || idStatus === 'VIDA') {
            participanteSelecionado.qt_vida = barra.qt_atual;
        } else if (idStatus === 'sanidade' || idStatus === 'SANIDADE') {
            participanteSelecionado.qt_sanidade = barra.qt_atual;
        } else if (idStatus === 'esforco' || idStatus === 'ESFORÇO') {
            participanteSelecionado.qt_esforco = barra.qt_atual;
        }
        
        renderDetalheParticipante(); 
        renderListaIniciativa();

        // Se for um personagem real da campanha, atualiza no banco de dados via AJAX
        if (participanteSelecionado.tipo === 'Personagem') {
            let mappedTipo = 'status_custom';
            let mappedCampo = idStatus;
            
            if (idStatus === 'vida' || idStatus === 'sanidade' || idStatus === 'esforco') {
                mappedTipo = 'stat';
                mappedCampo = idStatus === 'vida' ? 'VIDA' : (idStatus === 'sanidade' ? 'SANIDADE' : 'ESFORÇO');
            }
            
            try {
                await fetch('../app/ajax/atualizar-ficha.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_personagem: participanteSelecionado.id_personagem,
                        tipo: mappedTipo,
                        campo: mappedCampo,
                        valor: barra.qt_atual
                    })
                });
            } catch (e) {
                console.error('Erro ao sincronizar recurso do personagem:', e);
            }
        }
    }

    function rolarDadoEscudoRapido(formula, label) {
        const match = formula.match(/(\d+)d(\d+)/i);
        if (!match) return;
        const qtd = parseInt(match[1]) || 1;
        const lados = parseInt(match[2]) || 20;
        
        let valores = [];
        let total = 0;
        for (let i = 0; i < qtd; i++) {
            const v = Math.floor(Math.random() * lados) + 1;
            valores.push({ value: v, type: `d${lados}` });
            total += v;
        }
        
        mostrarResultadoEscudo(total, valores, `${qtd}D${lados} (${label})`);
        adicionarAoHistoricoEscudo(total, `${qtd}D${lados} • ${label}`);
        showEscudoToast(`Rolou ${qtd}D${lados} para ${label}: total ${total}`);
    }

    function renderSubAbaContent(p) {
        if (subTabAtiva === 'atributos') {
            const attrs = (p.atributos && p.atributos.length) ? p.atributos : [
                { ds_abreviacao: 'AGI', qt_valor: p.qt_agilidade || 0 },
                { ds_abreviacao: 'FOR', qt_valor: p.qt_forca || 0 },
                { ds_abreviacao: 'INT', qt_valor: p.qt_intelecto || 0 },
                { ds_abreviacao: 'PRE', qt_valor: p.qt_presenca || 0 },
                { ds_abreviacao: 'VIG', qt_valor: p.qt_vigor || 0 }
            ];
            return `
                <div class="atributos-combate-grid">
                    ${attrs.map(a => {
                        const abrev = a.ds_abreviacao || a.nm_atributo || 'ATT';
                        const shortAbrev = abrev.substring(0, 3).toUpperCase();
                        return `
                            <div class="attr-p1-box">
                                <span>${shortAbrev}</span>
                                <div>${a.qt_valor}</div>
                            </div>
                        `;
                    }).join('')}
                </div>
                <div style="text-align: center; margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 10px;">
                    <span style="color:#aaa; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing: 0.5px;">DEFESA: ${p.qt_defesa || 10} | BLOQUEIO: ${p.qt_bloqueio || 0} | ESQUIVA: ${p.qt_esquiva || p.qt_defesa || 10}</span>
                </div>
            `;
        }
        
        if (p.tipo === 'Personagem') {
            if (subTabAtiva === 'combates') {
                // Agora é DESCRIÇÃO
                return `
                    <div style="padding:15px; display:flex; flex-direction:column; gap:12px; max-height:280px; overflow-y:auto; scrollbar-width:thin;">
                        <h4 style="color:#fff; margin:0 0 5px 0; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; font-weight:800; color:var(--premium-accent);">Descrição do Personagem</h4>
                        ${p.ds_aparencia ? `
                            <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:10px; border-radius:8px;">
                                <strong style="color:var(--premium-accent); font-size:0.75rem; text-transform:uppercase; display:block; margin-bottom:4px;">Aparência</strong>
                                <p style="margin:0; font-size:0.8rem; color:#ccc; line-height:1.4;">${p.ds_aparencia}</p>
                            </div>
                        ` : ''}
                        ${p.ds_personalidade ? `
                            <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:10px; border-radius:8px;">
                                <strong style="color:var(--premium-accent); font-size:0.75rem; text-transform:uppercase; display:block; margin-bottom:4px;">Personalidade</strong>
                                <p style="margin:0; font-size:0.8rem; color:#ccc; line-height:1.4;">${p.ds_personalidade}</p>
                            </div>
                        ` : ''}
                        ${p.ds_historia ? `
                            <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:10px; border-radius:8px;">
                                <strong style="color:var(--premium-accent); font-size:0.75rem; text-transform:uppercase; display:block; margin-bottom:4px;">História</strong>
                                <p style="margin:0; font-size:0.8rem; color:#ccc; line-height:1.4;">${p.ds_historia}</p>
                            </div>
                        ` : ''}
                        ${p.ds_objetivos ? `
                            <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:10px; border-radius:8px;">
                                <strong style="color:var(--premium-accent); font-size:0.75rem; text-transform:uppercase; display:block; margin-bottom:4px;">Objetivos</strong>
                                <p style="margin:0; font-size:0.8rem; color:#ccc; line-height:1.4;">${p.ds_objetivos}</p>
                            </div>
                        ` : ''}
                        ${(!p.ds_aparencia && !p.ds_personalidade && !p.ds_historia && !p.ds_objetivos) ? `
                            <div style="padding:20px; text-align:center; color:#888; font-size:0.85rem;">Nenhuma descrição cadastrada.</div>
                        ` : ''}
                    </div>
                `;
            }
            
            if (subTabAtiva === 'rituais') {
                // Agora é HABILIDADES
                const habs = p.habilidades || [];
                const itens = p.itens || [];
                
                if (habs.length === 0 && itens.length === 0) {
                    return `<div style="padding:20px; text-align:center; color:#888; font-size:0.85rem;"><i class="fas fa-magic" style="font-size:1.5rem; display:block; margin-bottom:10px; color:#444;"></i> Nenhuma habilidade ou equipamento encontrado.</div>`;
                }
                
                return `
                    <div style="padding:15px; display:flex; flex-direction:column; gap:10px; max-height:280px; overflow-y:auto; scrollbar-width:thin;">
                        ${itens.length > 0 ? `
                            <h4 style="color:#fff; margin:0 0 5px 0; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; font-weight:800; color:var(--premium-accent);">Equipamentos</h4>
                            ${itens.map(item => `
                                <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); border-left:4px solid var(--premium-accent); padding:10px; border-radius:8px; display:flex; flex-direction:column; gap:6px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <h5 style="margin:0; color:#fff; font-size:0.8rem; font-weight:800;">${item.nm_item} <span style="font-size:0.7rem; color:#888; font-weight:400;">(x${item.qt_quantidade})</span></h5>
                                        <span style="font-size:0.65rem; color:#aaa; font-weight:700; background:rgba(255,255,255,0.05); padding:1px 6px; border-radius:8px;">${item.ds_categoria || 'Item'}</span>
                                    </div>
                                    <p style="margin:0; font-size:0.75rem; color:#aaa; line-height:1.4;">${item.ds_item || 'Sem descrição.'}</p>
                                    <div style="display:flex; gap:8px; margin-top:2px;">
                                        <button class="btn-ajuste" onclick="rolarDadoEscudoRapido('1d20', 'Ataque - ${item.nm_item}')" style="background:rgba(139,92,246,0.15); color:var(--premium-accent); border:1px solid rgba(139,92,246,0.3); padding:3px 8px; border-radius:4px; font-size:0.65rem; cursor:pointer; font-weight:700;"><i class="fas fa-dice-d20"></i> Ataque</button>
                                        ${item.ds_item && item.ds_item.match(/\d+d\d+/i) ? `
                                            <button class="btn-ajuste" onclick="rolarDadoEscudoRapido('${item.ds_item.match(/\d+d\d+/i)[0]}', 'Dano - ${item.nm_item}')" style="background:rgba(231,76,60,0.15); color:#e74c3c; border:1px solid rgba(231,76,60,0.3); padding:3px 8px; border-radius:4px; font-size:0.65rem; cursor:pointer; font-weight:700;"><i class="fas fa-fire"></i> Dano (${item.ds_item.match(/\d+d\d+/i)[0]})</button>
                                        ` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        ` : ''}

                        ${habs.length > 0 ? `
                            <h4 style="color:#fff; margin:15px 0 5px 0; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; font-weight:800; color:var(--premium-accent);">Habilidades, Poderes e Rituais</h4>
                            ${habs.map(h => {
                                const isPoder = (h.tp_habilidade || '').toLowerCase() === 'poder';
                                const accentColor = isPoder ? '#ff9f43' : '#a855f7';
                                return `
                                    <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); border-left:4px solid ${accentColor}; padding:10px; border-radius:8px;">
                                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                            <h5 style="margin:0; color:#fff; font-size:0.8rem; font-weight:800;">${h.nm_habilidade}</h5>
                                            <span style="font-size:0.6rem; color:#fff; font-weight:800; background:${accentColor}; padding:1px 6px; border-radius:4px; text-transform:uppercase;">${h.tp_habilidade || 'Habilidade'}</span>
                                        </div>
                                        <p style="margin:0; font-size:0.75rem; color:#aaa; line-height:1.4;">${h.ds_habilidade || 'Sem descrição.'}</p>
                                        ${h.ds_habilidade && h.ds_habilidade.match(/\d+d\d+/i) ? `
                                            <div style="margin-top:6px;">
                                                <button class="btn-ajuste" onclick="rolarDadoEscudoRapido('${h.ds_habilidade.match(/\d+d\d+/i)[0]}', 'Efeito - ${h.nm_habilidade}')" style="background:rgba(168,85,247,0.15); color:#a855f7; border:1px solid rgba(168,85,247,0.3); padding:3px 8px; border-radius:4px; font-size:0.65rem; cursor:pointer; font-weight:700;"><i class="fas fa-dice"></i> Rolar Efeito (${h.ds_habilidade.match(/\d+d\d+/i)[0]})</button>
                                            </div>
                                        ` : ''}
                                    </div>
                                `;
                            }).join('')}
                        ` : ''}
                    </div>
                `;
            }
        } else {
            // Se for Monstro
            if (subTabAtiva === 'combates') {
                // Descrição do Monstro
                return `
                    <div style="padding:15px; max-height:280px; overflow-y:auto; scrollbar-width:thin;">
                        <h4 style="color:#fff; margin:0 0 10px 0; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; font-weight:800; color:#ff4d4d;"><i class="fas fa-skull"></i> Descrição do Monstro</h4>
                        <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); border-left:4px solid #ff4d4d; padding:15px; border-radius:10px;">
                            <p style="margin:0; font-size:0.8rem; color:#ccc; line-height:1.6; white-space:pre-wrap;">${p.ds_monstro || 'Nenhuma descrição cadastrada.'}</p>
                        </div>
                    </div>
                `;
            }
            if (subTabAtiva === 'rituais') {
                // Habilidades/Ataques do Monstro
                return `
                    <div style="padding:15px; max-height:280px; overflow-y:auto; scrollbar-width:thin;">
                        <h4 style="color:#fff; margin:0 0 10px 0; font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; font-weight:800; color:#ff4d4d;"><i class="fas fa-sword"></i> Habilidades e Ataques</h4>
                        <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); border-left:4px solid #ff4d4d; padding:15px; border-radius:10px;">
                            <p style="margin:0; font-size:0.8rem; color:#ccc; line-height:1.6; white-space:pre-wrap;">${p.ds_monstro || 'Nenhum detalhe de ataque cadastrado.'}</p>
                        </div>
                    </div>
                `;
            }
        }
        
        return `<div style="padding:20px;color:#888;font-size:.85rem;">Nenhum detalhe encontrado.</div>`;
    }

    function switchEscudoSubTab(tab) { subTabAtiva=tab; renderDetalheParticipante(); }

    // ============================================================
    // INVESTIGAÇÕES (Persistência Local)
    // ============================================================
    let indexInvestigacaoEditando = null;

    function obterInvestigacoesLocal() {
        const key = `campanha_${idCampanha}_investigacoes`;
        try {
            const data = localStorage.getItem(key);
            return data ? JSON.parse(data) : [];
        } catch(e) {
            console.error(e);
            return [];
        }
    }

    function salvarInvestigacoesLocal(lista) {
        const key = `campanha_${idCampanha}_investigacoes`;
        localStorage.setItem(key, JSON.stringify(lista));
    }

    function renderInvestigacoes() {
        const lista = obterInvestigacoesLocal();
        const container = document.querySelector('#escudo-tab-investigacoes .investigacao-lista');
        if (!container) return;
        
        if (lista.length === 0) {
            container.innerHTML = '<p style="text-align:center;padding:20px;color:#888;font-style:italic;">Nenhuma investigação criada ainda.</p>';
            return;
        }
        
        container.innerHTML = lista.map((inv, idx) => `
            <div class="card-combate-escudo" style="background:rgba(255,255,255,0.03);padding:20px;border-radius:12px;margin-bottom:15px;display:flex;justify-content:space-between;align-items:center;border:1px solid rgba(255,255,255,0.05);">
                <div>
                    <h3 style="font-size:1.1rem;margin:0 0 5px 0;color:#fff;">${inv.nome || 'Caso sem nome'}</h3>
                    <p style="color:#aaa;font-size:0.8rem;margin:0;max-width:450px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${inv.resumo || 'Sem resumo...'}</p>
                </div>
                <div style="display:flex;gap:10px;">
                    <button onclick="abrirInvestigacao(${idx})" style="background:var(--premium-accent);color:#fff;border:none;padding:6px 15px;border-radius:15px;cursor:pointer;font-weight:700;font-size:0.8rem;"><i class="fas fa-edit"></i> Abrir</button>
                    <button onclick="deletarInvestigacao(${idx})" style="background:rgba(255,77,77,0.15);color:#ff4d4d;border:1px solid rgba(255,77,77,0.3);padding:6px 12px;border-radius:15px;cursor:pointer;font-weight:700;font-size:0.8rem;"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        `).join('');
    }

    function abrirFichaInvestigacao()  { document.getElementById('inv-modo-lista').classList.add('escondido');    document.getElementById('inv-modo-detalhe').classList.remove('escondido'); }
    function voltarListaInvestigacao() { document.getElementById('inv-modo-lista').classList.remove('escondido'); document.getElementById('inv-modo-detalhe').classList.add('escondido'); }
    
    function novaFichaInvestigacao() {
        indexInvestigacaoEditando = null;
        document.getElementById('inv-nome-input').value = '';
        document.getElementById('inv-resumo-input').innerHTML = '';
        document.getElementById('inv-objetivo-input').innerHTML = '';
        document.getElementById('inv-perguntas-input').innerHTML = '';
        document.getElementById('inv-pistas-input').innerHTML = '';
        abrirFichaInvestigacao();
    }

    function abrirInvestigacao(idx) {
        const lista = obterInvestigacoesLocal();
        const inv = lista[idx];
        if (!inv) return;
        indexInvestigacaoEditando = idx;
        document.getElementById('inv-nome-input').value = inv.nome || '';
        document.getElementById('inv-resumo-input').innerHTML = inv.resumo || '';
        document.getElementById('inv-objetivo-input').innerHTML = inv.objetivo || '';
        document.getElementById('inv-perguntas-input').innerHTML = inv.perguntas || '';
        document.getElementById('inv-pistas-input').innerHTML = inv.pistas || '';
        abrirFichaInvestigacao();
    }

    function salvarFichaInvestigacao() {
        const nome = document.getElementById('inv-nome-input').value.trim();
        if (!nome) {
            alert('Digite pelo menos o Nome do Caso para salvar!');
            return;
        }
        const inv = {
            nome: nome,
            resumo: document.getElementById('inv-resumo-input').innerHTML,
            objetivo: document.getElementById('inv-objetivo-input').innerHTML,
            perguntas: document.getElementById('inv-perguntas-input').innerHTML,
            pistas: document.getElementById('inv-pistas-input').innerHTML
        };
        
        const lista = obterInvestigacoesLocal();
        if (indexInvestigacaoEditando === null) {
            lista.push(inv);
        } else {
            lista[indexInvestigacaoEditando] = inv;
        }
        salvarInvestigacoesLocal(lista);
        renderInvestigacoes();
        voltarListaInvestigacao();
    }

    async function deletarInvestigacao(idx) {
        if (!await TableModal.confirm('Deseja realmente deletar este caso de investigação?', 'Deletar Investigação', 'warning')) return;
        const lista = obterInvestigacoesLocal();
        lista.splice(idx, 1);
        salvarInvestigacoesLocal(lista);
        renderInvestigacoes();
    }

    // ============================================================
    // RELATÓRIOS (Persistência Local)
    // ============================================================
    let indexRelatorioEditando = null;
    let statusRelatorioSelecionado = 'aberto';

    function setRelStatus(btn) {
        btn.closest('.status-toggle-group').querySelectorAll('.btn-status-rel').forEach(b=>b.classList.remove('ativo'));
        btn.classList.add('ativo');
        statusRelatorioSelecionado = btn.dataset.status;
    }

    function obterRelatoriosLocal() {
        const key = `campanha_${idCampanha}_relatorios`;
        try {
            const data = localStorage.getItem(key);
            return data ? JSON.parse(data) : [];
        } catch(e) {
            console.error(e);
            return [];
        }
    }

    function salvarRelatoriosLocal(lista) {
        const key = `campanha_${idCampanha}_relatorios`;
        localStorage.setItem(key, JSON.stringify(lista));
    }

    function renderRelatorios() {
        const lista = obterRelatoriosLocal();
        const container = document.querySelector('#escudo-tab-relatorios .investigacao-lista');
        if (!container) return;
        
        if (lista.length === 0) {
            container.innerHTML = '<p style="text-align:center;padding:20px;color:#888;font-style:italic;">Nenhum relatório de missão criado ainda.</p>';
            return;
        }
        
        container.innerHTML = lista.map((rel, idx) => {
            let statusBadge = '';
            if (rel.status === 'sucesso') {
                statusBadge = '<span style="background:rgba(46,204,113,0.15);color:#2ecc71;font-weight:700;font-size:0.75rem;padding:3px 10px;border-radius:10px;border:1px solid rgba(46,204,113,0.3);">Sucesso</span>';
            } else if (rel.status === 'fracasso') {
                statusBadge = '<span style="background:rgba(231,76,60,0.15);color:#e74c3c;font-weight:700;font-size:0.75rem;padding:3px 10px;border-radius:10px;border:1px solid rgba(231,76,60,0.3);">Fracasso</span>';
            } else {
                statusBadge = '<span style="background:rgba(241,196,15,0.15);color:#f1c40f;font-weight:700;font-size:0.75rem;padding:3px 10px;border-radius:10px;border:1px solid rgba(241,196,15,0.3);">Em aberto</span>';
            }
            
            return `
                <div class="card-combate-escudo" style="background:rgba(255,255,255,0.03);padding:20px;border-radius:12px;margin-bottom:15px;display:flex;justify-content:space-between;align-items:center;border:1px solid rgba(255,255,255,0.05);">
                    <div>
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:5px;">
                            <h3 style="font-size:1.1rem;margin:0;color:#fff;">${rel.missao || 'Relatório sem nome'}</h3>
                            ${statusBadge}
                        </div>
                        <p style="color:#aaa;font-size:0.8rem;margin:0;max-width:450px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Equipe: ${rel.equipe || 'Não informada'} • Personagens: ${rel.personagens || 'Nenhum...'}</p>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button onclick="abrirRelatorio(${idx})" style="background:var(--premium-accent);color:#fff;border:none;padding:6px 15px;border-radius:15px;cursor:pointer;font-weight:700;font-size:0.8rem;"><i class="fas fa-edit"></i> Abrir</button>
                        <button onclick="deletarRelatorio(${idx})" style="background:rgba(255,77,77,0.15);color:#ff4d4d;border:1px solid rgba(255,77,77,0.3);padding:6px 12px;border-radius:15px;cursor:pointer;font-weight:700;font-size:0.8rem;"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `;
        }).join('');
    }

    function abrirRelatorioMissao()    { document.getElementById('rel-modo-lista').classList.add('escondido');    document.getElementById('rel-modo-detalhe').classList.remove('escondido'); }
    function voltarListaRelatorio()    { document.getElementById('rel-modo-lista').classList.remove('escondido'); document.getElementById('rel-modo-detalhe').classList.add('escondido'); }

    function novoRelatorioMissao() {
        indexRelatorioEditando = null;
        document.getElementById('rel-missao-input').value = '';
        document.getElementById('rel-equipe-input').value = '';
        document.getElementById('rel-personagens-input').value = '';
        document.getElementById('rel-pistas-input').innerHTML = '';
        document.getElementById('rel-casualidades-input').innerHTML = '';
        document.getElementById('rel-resumo-input').innerHTML = '';
        statusRelatorioSelecionado = 'aberto';
        
        const btns = document.querySelectorAll('#rel-modo-detalhe .btn-status-rel');
        btns.forEach(b => {
            if (b.dataset.status === 'aberto') b.classList.add('ativo');
            else b.classList.remove('ativo');
        });
        
        abrirRelatorioMissao();
    }

    function abrirRelatorio(idx) {
        const lista = obterRelatoriosLocal();
        const rel = lista[idx];
        if (!rel) return;
        indexRelatorioEditando = idx;
        
        document.getElementById('rel-missao-input').value = rel.missao || '';
        document.getElementById('rel-equipe-input').value = rel.equipe || '';
        document.getElementById('rel-personagens-input').value = rel.personagens || '';
        document.getElementById('rel-pistas-input').innerHTML = rel.pistas || '';
        document.getElementById('rel-casualidades-input').innerHTML = rel.casualidades || '';
        document.getElementById('rel-resumo-input').innerHTML = rel.resumo || '';
        statusRelatorioSelecionado = rel.status || 'aberto';
        
        const btns = document.querySelectorAll('#rel-modo-detalhe .btn-status-rel');
        btns.forEach(b => {
            if (b.dataset.status === statusRelatorioSelecionado) b.classList.add('ativo');
            else b.classList.remove('ativo');
        });
        
        abrirRelatorioMissao();
    }

    function salvarFichaRelatorio() {
        const missao = document.getElementById('rel-missao-input').value.trim();
        if (!missao) {
            alert('Digite pelo menos o Nome da Missão para salvar!');
            return;
        }
        const rel = {
            missao: missao,
            equipe: document.getElementById('rel-equipe-input').value.trim(),
            personagens: document.getElementById('rel-personagens-input').value.trim(),
            pistas: document.getElementById('rel-pistas-input').innerHTML,
            casualidades: document.getElementById('rel-casualidades-input').innerHTML,
            resumo: document.getElementById('rel-resumo-input').innerHTML,
            status: statusRelatorioSelecionado
        };
        
        const lista = obterRelatoriosLocal();
        if (indexRelatorioEditando === null) {
            lista.push(rel);
        } else {
            lista[indexRelatorioEditando] = rel;
        }
        salvarRelatoriosLocal(lista);
        renderRelatorios();
        voltarListaRelatorio();
    }

    async function deletarRelatorio(idx) {
        if (!await TableModal.confirm('Deseja realmente deletar este relatório de missão?', 'Deletar Relatório', 'warning')) return;
        const lista = obterRelatoriosLocal();
        lista.splice(idx, 1);
        salvarRelatoriosLocal(lista);
        renderRelatorios();
    }

    // ============================================================
    // ANOTAÇÕES (Persistência Local com Autosave)
    // ============================================================
    function inicializarAnotacoes() {
        const key = `campanha_${idCampanha}_anotacoes`;
        let salvas = { geral: '', futuras: '', anteriores: '' };
        try {
            const data = localStorage.getItem(key);
            if (data) salvas = JSON.parse(data);
        } catch(e) {
            console.error(e);
        }
        
        const inputGeral = document.getElementById('anot-geral-input');
        const inputFuturas = document.getElementById('anot-futuras-input');
        const inputAnteriores = document.getElementById('anot-anteriores-input');
        
        if (inputGeral) inputGeral.innerHTML = salvas.geral || '';
        if (inputFuturas) inputFuturas.innerHTML = salvas.futuras || '';
        if (inputAnteriores) inputAnteriores.innerHTML = salvas.anteriores || '';
        
        const salvarNotas = () => {
            const dados = {
                geral: inputGeral ? inputGeral.innerHTML : '',
                futuras: inputFuturas ? inputFuturas.innerHTML : '',
                anteriores: inputAnteriores ? inputAnteriores.innerHTML : ''
            };
            localStorage.setItem(key, JSON.stringify(dados));
        };
        
        if (inputGeral) inputGeral.addEventListener('input', salvarNotas);
        if (inputFuturas) inputFuturas.addEventListener('input', salvarNotas);
        if (inputAnteriores) inputAnteriores.addEventListener('input', salvarNotas);
    }

    // ============================================================
    // DADOS — ESCUDO DO MESTRE (dddice integrado)
    // ============================================================
    const ESCUDO_API_KEY   = <?php echo json_encode(DDDICE_API_KEY); ?>;
    const ESCUDO_ROOM_SLUG = <?php echo json_encode($roomSlug ?: DDDICE_ROOM_SLUG); ?>;
    const PARTICIPANTES_MAPA = <?php echo json_encode($mapaParticipantesPersonagens ?? []); ?>;
    const USUARIO_NOME = <?php echo json_encode($_SESSION['usuario']['nome'] ?? 'Jogador'); ?>;

    // Mapa de lados → tipo dddice (dados suportados pela API)
    const ESCUDO_DDDICE_MAP = { 4:'d4', 6:'d6', 8:'d8', 10:'d10', 12:'d12', 20:'d20' };

    let escudoSelecao   = {};
    let escudoDddiceSDK = null;
    let escudoThemeId   = '';
    let escudoRolling   = false;

    // Variáveis da Rolagem do Jogador Comum
    let jogadorSelecao  = {};
    let jogadorThemeId  = '';
    let jogadorRolling  = false;

    async function initEscudoSDK() {
        if (escudoDddiceSDK) return;
        setEscudoStatus('loading');
        
        const isApiKeyPlaceholder = ESCUDO_API_KEY.includes('Insira sua');
        if (isApiKeyPlaceholder || !ESCUDO_API_KEY) {
            setEscudoStatus('local');
            const btn = document.getElementById('escudo-btn-rolar');
            if (btn) {
                btn.innerHTML = '<i class="fas fa-dice"></i> Rolar Dados';
                btn.disabled = false;
            }
            const select = document.getElementById('escudo-theme-select');
            if (select) {
                select.innerHTML = '<option value="local">Rolagem Local Premium</option>';
                select.disabled = true;
            }
            const btnJog = document.getElementById('jogador-btn-rolar');
            if (btnJog) {
                btnJog.innerHTML = '<i class="fas fa-dice"></i> Rolar Dados';
                btnJog.disabled = false;
            }
            const selectJog = document.getElementById('jogador-theme-select');
            if (selectJog) {
                selectJog.innerHTML = '<option value="local">Rolagem Local Premium</option>';
                selectJog.disabled = true;
            }
            return;
        }
        
        if (!window.ThreeDDice) { setEscudoStatus('error'); showEscudoToast('Motor de dados 3D não carregou.'); return; }
        try {
            const canvas = document.getElementById('dddice-canvas-escudo');
            escudoDddiceSDK = new window.ThreeDDice(canvas, ESCUDO_API_KEY);
            escudoDddiceSDK.start();
            await escudoDddiceSDK.connect(ESCUDO_ROOM_SLUG);
            if (escudoDddiceSDK.participant && escudoDddiceSDK.participant.id) {
                fetch(`https://dddice.com/api/1.0/room/${ESCUDO_ROOM_SLUG}/participant/${escudoDddiceSDK.participant.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${ESCUDO_API_KEY}`,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        username: USUARIO_NOME
                    })
                }).catch(err => console.warn('Erro ao atualizar participante dddice:', err));
            }
            await carregarTemasEscudo();
            setEscudoStatus('ok');

            // Ouvir rolagens externas na sala em tempo real!
            escudoDddiceSDK.on('roll:finished', (roll) => {
                const rawLabel = roll.label || roll.data?.label || '';
                let userWhoRolled = 'Outro participante';
                let equation = '';

                if (rawLabel.includes(' | ')) {
                    const parts = rawLabel.split(' | ');
                    userWhoRolled = parts[0].trim();
                    equation = parts[1].trim();
                } else {
                    // Tentar resolver via participantes da sala no SDK
                    let encontrado = false;
                    if (escudoDddiceSDK && escudoDddiceSDK.room && escudoDddiceSDK.room.participants) {
                        const userUuid = roll.user?.uuid;
                        const participantId = roll.participant?.id;
                        const part = escudoDddiceSDK.room.participants.find(p => 
                            (userUuid && p.user?.uuid === userUuid) || 
                            (participantId && p.id === participantId)
                        );
                        if (part && part.username) {
                            userWhoRolled = part.username;
                            encontrado = true;
                        }
                    }
                    if (!encontrado) {
                        userWhoRolled = (roll.participant && roll.participant.username) ? roll.participant.username : (roll.user.username || 'Outro participante');
                    }

                    // Mapear os dados rolandos para criar a legenda de fallback
                    const diceSummary = {};
                    roll.values.forEach(v => {
                        const cleanType = (v.type || 'd20').toLowerCase();
                        diceSummary[cleanType] = (diceSummary[cleanType] || 0) + 1;
                    });
                    equation = Object.entries(diceSummary).map(([type, qtd]) => `${qtd}${type.toUpperCase()}`).join(' + ');
                }

                // Normalização e Fallback amigável para contas conhecidas de dddice
                const nomeLimpo = userWhoRolled.toLowerCase().trim();
                if (nomeLimpo === 'llip35' || nomeLimpo === 'll1p35') {
                    userWhoRolled = 'Kauan Bryan';
                }

                // Evita duplicação se fomos nós quem rolamos
                if (userWhoRolled.toLowerCase().trim() === USUARIO_NOME.toLowerCase().trim()) return;

                const total = roll.total_value;
                const finalLabel = `${userWhoRolled} rolou ${equation}`;
                
                // Força o delay de 5 segundos para exibir no histórico local para rolagens recebidas de outros
                setTimeout(() => {
                    adicionarAoHistoricoCompartilhado(total, finalLabel, false, userWhoRolled);
                }, 5000);
            });
        } catch (err) { 
            console.error('initEscudoSDK:', err); 
            setEscudoStatus('error'); 
            showEscudoToast('Erro ao inicializar dados 3D. Modo Local Ativo.'); 
            const btn = document.getElementById('escudo-btn-rolar');
            if (btn) btn.innerHTML = '<i class="fas fa-dice"></i> Rolar Dados';
            const select = document.getElementById('escudo-theme-select');
            if (select) {
                select.innerHTML = '<option value="local">Rolagem Local Premium (Offline)</option>';
                select.disabled = true;
            }
            const btnJog = document.getElementById('jogador-btn-rolar');
            if (btnJog) btnJog.innerHTML = '<i class="fas fa-dice"></i> Rolar Dados';
            const selectJog = document.getElementById('jogador-theme-select');
            if (selectJog) {
                selectJog.innerHTML = '<option value="local">Rolagem Local Premium (Offline)</option>';
                selectJog.disabled = true;
            }
        }
    }

    async function carregarTemasEscudo() {
        const select = document.getElementById('escudo-theme-select');
        const playerSelect = document.getElementById('jogador-theme-select');
        
        const populateSelect = (sel, themes) => {
            if (!sel) return;
            sel.innerHTML = '';
            themes.forEach(t => { const opt = document.createElement('option'); opt.value = t.id; opt.textContent = t.name || t.id; sel.appendChild(opt); });
            sel.disabled = false;
        };

        if (select) select.innerHTML = '<option value="">Carregando...</option>';
        if (playerSelect) playerSelect.innerHTML = '<option value="">Carregando...</option>';
        
        const resp = await fetch('?action=themes_escudo');
        const data = await resp.json();
        if (data.error) throw new Error(data.error);
        const themes = data.themes ?? [];
        
        if (!themes.length) { 
            if (select) select.innerHTML = '<option value="">Nenhum tema encontrado</option>'; 
            if (playerSelect) playerSelect.innerHTML = '<option value="">Nenhum tema encontrado</option>'; 
            showEscudoToast('Nenhum tema de dados disponível. Usando dados clássicos.'); 
            return; 
        }
        
        populateSelect(select, themes);
        populateSelect(playerSelect, themes);
        
        if (select) {
            escudoThemeId = select.value;
            select.addEventListener('change', () => { escudoThemeId = select.value; atualizarBtnEscudo(); });
        }
        atualizarBtnEscudo();
    }

    function inicializarEventosDadosEscudo() {
        document.querySelectorAll('#escudo-tab-dados .item-dado').forEach(item => {
            item.addEventListener('click', () => {
                const lados = parseInt(item.dataset.lados);
                const atual = escudoSelecao[lados] ?? 0;
                const novo  = Math.min(10, atual + 1);
                escudoSelecao[lados] = novo;
                const bolinha = document.getElementById(`escudo-bolinha-d${lados}`);
                if (bolinha) { bolinha.textContent = novo; bolinha.classList.add('show'); }
                item.classList.add('selecionado');
                const container = item.querySelector('.dado-icon-container');
                const img = item.querySelector('.img-dado');
                if (container && img) {
                    const src = img.src;
                    container.classList.add('dado-girando');
                    img.src = `../img/dados/D${lados} efeito.png`;
                    setTimeout(() => { container.classList.remove('dado-girando'); img.src = src; }, 600);
                }
                atualizarResumoEscudo();
                atualizarBtnEscudo();
            });
        });
    }

    function limparSelecaoEscudo() {
        escudoSelecao = {};
        [2,4,6,8,10,12,20,100].forEach(d => {
            const bolinha = document.getElementById(`escudo-bolinha-d${d}`);
            if (bolinha) { bolinha.textContent = '0'; bolinha.classList.remove('show'); }
            const item = document.getElementById(`escudo-dado-d${d}`);
            if (item) item.classList.remove('selecionado');
        });
        atualizarResumoEscudo();
        atualizarBtnEscudo();
    }

    function atualizarResumoEscudo() {
        const el    = document.getElementById('escudo-sel-resumo');
        const parts = Object.entries(escudoSelecao).filter(([,q]) => q > 0);
        if (!parts.length) { el.innerHTML = '<span style="color:#555;">Clique nos dados para selecionar...</span>'; return; }
        let html = parts.map(([l,q]) => `<span class="chip">${q}D${l}</span>`).join('');
        html += `<button class="btn-escudo-limpar-sel" onclick="limparSelecaoEscudo()">✕</button>`;
        el.innerHTML = html;
    }

    function atualizarBtnEscudo() {
        const temDados  = Object.values(escudoSelecao).some(q => q > 0);
        const dot = document.getElementById('escudo-status-dot');
        const isLocal = dot && (dot.classList.contains('local') || dot.classList.contains('error'));
        const sdkPronto = isLocal || (!!escudoThemeId && !escudoRolling);
        const btn = document.getElementById('escudo-btn-rolar');
        if (btn) btn.disabled = !(temDados && sdkPronto);
    }

    async function escudoExecutarRolagem() {
        if (escudoRolling) return;
        const entries = Object.entries(escudoSelecao).filter(([,q]) => q > 0);
        if (!entries.length) return showEscudoToast('Selecione ao menos um dado!');
        
        const dot = document.getElementById('escudo-status-dot');
        const isLocal = dot && (dot.classList.contains('local') || dot.classList.contains('error'));
        
        if (!isLocal && !escudoThemeId) return showEscudoToast('Selecione um tema primeiro!');
        
        escudoRolling = true;
        const btn = document.getElementById('escudo-btn-rolar');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rolando...'; }

        const label = entries.map(([l,q]) => `${q}D${l}`).join(' + ');

        const finalizarComDelay = (total, values, lbl) => {
            setTimeout(() => {
                mostrarResultadoEscudo(total, values, lbl);
                adicionarAoHistoricoEscudo(total, lbl);
                adicionarAoHistoricoCompartilhado(total, lbl, true);
                limparSelecaoEscudo();
                
                escudoRolling = false;
                if (btn) btn.innerHTML = isLocal ? '<i class="fas fa-dice"></i> Rolar Dados' : '<i class="fas fa-dice"></i> Rolar com Dados 3D';
                atualizarBtnEscudo();
            }, 5000);
        };

        try {
            let finalTotal = 0;
            let finalValues = [];
            
            if (isLocal || !escudoDddiceSDK) {
                entries.forEach(([lados, qtd]) => {
                    for (let i = 0; i < qtd; i++) {
                        const v = Math.floor(Math.random() * parseInt(lados)) + 1;
                        finalTotal += v;
                        finalValues.push({ value: v, type: `d${lados}` });
                    }
                });
                finalizarComDelay(finalTotal, finalValues, label);
            } else {
                const dddiceEntries = entries.filter(([l]) => ESCUDO_DDDICE_MAP[parseInt(l)]);
                const jsEntries     = entries.filter(([l]) => !ESCUDO_DDDICE_MAP[parseInt(l)]);
                const dddDice = [];
                dddiceEntries.forEach(([lados, qtd]) => { 
                    const tipo = ESCUDO_DDDICE_MAP[parseInt(lados)]; 
                    for (let i = 0; i < qtd; i++) dddDice.push({ type: tipo, theme: escudoThemeId }); 
                });
                
                let jsTotal = 0;
                let jsValues = [];
                jsEntries.forEach(([lados, qtd]) => { 
                    for (let i = 0; i < qtd; i++) { 
                       const v = Math.floor(Math.random() * parseInt(lados)) + 1; 
                       jsTotal += v; 
                       jsValues.push({ value: v, type: `d${lados}` }); 
                    } 
                });
                
                if (dddDice.length > 0) {
                    let phpResult;
                    try {
                        if (escudoDddiceSDK) {
                            const sdkRes = await escudoDddiceSDK.roll(dddDice, { label: `${USUARIO_NOME} | ${label}` });
                            const resValues = sdkRes.data?.values || sdkRes.values || [];
                            const resTotal = sdkRes.data?.total_value !== undefined ? sdkRes.data.total_value : (sdkRes.total_value || 0);
                            
                            phpResult = {
                                ok: true,
                                total: parseInt(resTotal),
                                values: resValues.map(v => ({ value: parseInt(v.value), type: v.type }))
                            };
                        } else {
                            phpResult = await fetch('?action=roll_escudo', { 
                                method: 'POST', 
                                headers: { 'Content-Type': 'application/json' }, 
                                body: JSON.stringify({ 
                                    dice: dddDice, 
                                    id_campanha: <?php echo json_encode($id_campanha); ?>,
                                    label: `${USUARIO_NOME} | ${label}`
                                }) 
                            }).then(r => r.json());
                        }
                    } catch (e) {
                        console.warn('SDK:', e);
                        phpResult = { error: e.message };
                    }
                    if (phpResult.error) { 
                        showEscudoToast('Conexão 3D indisponível. Rolando local.'); 
                        entries.forEach(([lados, qtd]) => {
                            for (let i = 0; i < qtd; i++) {
                                const v = Math.floor(Math.random() * parseInt(lados)) + 1;
                                finalTotal += v;
                                finalValues.push({ value: v, type: `d${lados}` });
                            }
                        });
                        finalizarComDelay(finalTotal, finalValues, label);
                    } else {
                        finalTotal  = phpResult.total + jsTotal;
                        finalValues  = [...phpResult.values, ...jsValues];
                        finalizarComDelay(finalTotal, finalValues, label);
                    }
                } else {
                    finalTotal = jsTotal;
                    finalValues = jsValues;
                    finalizarComDelay(finalTotal, finalValues, label);
                }
            }
        } catch (err) { 
            console.error(err); 
            showEscudoToast('Erro na rolagem. Modo Local ativo.');
            let fallbackTotal = 0;
            let fallbackValues = [];
            entries.forEach(([lados, qtd]) => {
                for (let i = 0; i < qtd; i++) {
                    const v = Math.floor(Math.random() * parseInt(lados)) + 1;
                    fallbackTotal += v;
                    fallbackValues.push({ value: v, type: `d${lados}` });
                }
            });
            finalizarComDelay(fallbackTotal, fallbackValues, label);
        }
    }

    function mostrarResultadoEscudo(total, values, label) {
        const totalEl     = document.getElementById('escudo-result-total');
        const breakdownEl = document.getElementById('escudo-result-breakdown');
        const labelEl     = document.getElementById('escudo-result-label');
        if (labelEl) labelEl.textContent = label;
        if (totalEl) {
            totalEl.classList.remove('pop');
            void totalEl.offsetWidth;
            totalEl.textContent = total;
            totalEl.classList.add('pop');
        }
        if (breakdownEl) {
            if (values.length > 1) {
                breakdownEl.innerHTML = values.map(v => `<span class="val">${v.value}</span>`).join('<span class="op">+</span>') + `<span class="op">=</span><span class="val">${total}</span>`;
            } else { breakdownEl.innerHTML = ''; }
        }
        const popup = document.getElementById('escudo-result-popup');
        if (popup) popup.classList.add('show');
    }

    function fecharResultadoEscudo() { 
        const popup = document.getElementById('escudo-result-popup');
        if (popup) popup.classList.remove('show'); 
    }

    function adicionarAoHistoricoEscudo(resultado, descricao) {
        const logContainer = document.getElementById('sidebar-dados-lista'); if (!logContainer) return;
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const novoItem = document.createElement('div'); novoItem.className = 'item-dado real-roll';
        novoItem.innerHTML = `<div class="hexa-dado" style="border-color:var(--premium-accent);color:#000;background:#fff;font-weight:800;display:flex;align-items:center;justify-content:center;">${resultado}</div><div class="info-rolagem"><p>${time} • ${descricao}</p><h4 style="color:#fff;">Mestre <span style="font-size:0.6rem;color:var(--premium-accent);font-weight:700;background:rgba(139,92,246,0.15);padding:1px 5px;border-radius:4px;">3D</span></h4></div>`;
        logContainer.innerHTML = '';
        logContainer.appendChild(novoItem);
    }

    // ============================================================
    // DADOS — ABA CAMPANHA (layout fiel ao rolagem-de-dados.php)
    // ============================================================
    let campSelecao  = {};
    let campThemeId  = '';
    let campRolling  = false;

    function inicializarEventosDadosCamp() {
        document.querySelectorAll('.camp-item-dado').forEach(item => {
            item.addEventListener('click', () => {
                const lados = parseInt(item.dataset.lados);
                const atual = campSelecao[lados] ?? 0;
                const novo  = Math.min(10, atual + 1);
                campSelecao[lados] = novo;

                const bolinha = document.getElementById(`camp-bolinha-d${lados}`);
                if (bolinha) { bolinha.textContent = novo; bolinha.classList.add('show'); }
                item.classList.add('selecionado');

                const container = item.querySelector('.dado-icon-container');
                const img = item.querySelector('.img-dado');
                if (container && img) {
                    const src = img.src;
                    container.classList.add('dado-girando');
                    img.src = `../img/dados/D${lados} efeito.png`;
                    setTimeout(() => { container.classList.remove('dado-girando'); img.src = src; }, 600);
                }
                atualizarResumoCamp();
                atualizarBtnCamp();
            });
        });
    }

    function limparSelecaoCamp() {
        campSelecao = {};
        [2,4,6,8,10,12,20,100].forEach(d => {
            const bolinha = document.getElementById(`camp-bolinha-d${d}`);
            if (bolinha) { bolinha.textContent = '0'; bolinha.classList.remove('show'); }
            const item = document.getElementById(`camp-dado-d${d}`);
            if (item) item.classList.remove('selecionado');
        });
        atualizarResumoCamp();
        atualizarBtnCamp();
    }

    function atualizarResumoCamp() {
        const el = document.getElementById('camp-sel-resumo');
        if (!el) return;
        const parts = Object.entries(campSelecao).filter(([,q]) => q > 0);
        if (!parts.length) {
            el.innerHTML = '<span style="color:#555;">Clique nos dados para selecionar quantidades...</span>';
            return;
        }
        let html = parts.map(([l,q]) => `<span class="chip">${q}D${l}</span>`).join('');
        html += `<button class="camp-btn-limpar" onclick="limparSelecaoCamp()">✕ Limpar</button>`;
        el.innerHTML = html;
    }

    function atualizarBtnCamp() {
        const temDados = Object.values(campSelecao).some(q => q > 0);
        const dot = document.getElementById('camp-status-dot');
        const isLocal = dot && (dot.classList.contains('local') || dot.classList.contains('error'));
        const sdkPronto = isLocal || (!!campThemeId && !campRolling);
        const btn = document.getElementById('camp-btn-rolar');
        if (btn) btn.disabled = !(temDados && sdkPronto);
    }

    function setCampStatus(state) {
        const dot = document.getElementById('camp-status-dot');
        if (!dot) return;
        dot.className = state;
        dot.title = state === 'ok' ? 'Dados 3D ativos' : state === 'loading' ? 'Inicializando dados...' : 'Modo clássico ativo';
    }

    async function initCampThemes() {
        const select = document.getElementById('camp-theme-select');
        if (!select) return;

        const escudoDot = document.getElementById('escudo-status-dot');
        const escudoIsLocal = escudoDot && (escudoDot.classList.contains('local') || escudoDot.classList.contains('error'));

        if (escudoIsLocal || !escudoDddiceSDK) {
            setCampStatus('local');
            select.innerHTML = '<option value="local">Rolagem Local Premium</option>';
            select.disabled = true;
            const btn = document.getElementById('camp-btn-rolar');
            if (btn) btn.disabled = false;
            return;
        }

        try {
            setCampStatus('loading');
            const resp = await fetch('?action=themes_escudo');
            const data = await resp.json();
            if (data.error) throw new Error(data.error);
            const themes = data.themes ?? [];
            select.innerHTML = '';
            themes.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name || t.id;
                select.appendChild(opt);
            });
            select.disabled = false;
            campThemeId = select.value;
            select.addEventListener('change', () => { campThemeId = select.value; atualizarBtnCamp(); });
            setCampStatus('ok');
            atualizarBtnCamp();
        } catch(e) {
            console.error('initCampThemes:', e);
            setCampStatus('local');
            select.innerHTML = '<option value="local">Rolagem Local Premium (Offline)</option>';
            select.disabled = true;
            const btn = document.getElementById('camp-btn-rolar');
            if (btn) btn.disabled = false;
        }
    }

    async function campExecutarRolagem() {
        if (campRolling) return;
        const entries = Object.entries(campSelecao).filter(([,q]) => q > 0);
        if (!entries.length) return showEscudoToast('Selecione ao menos um dado!');

        const dot = document.getElementById('camp-status-dot');
        const isLocal = dot && (dot.classList.contains('local') || dot.classList.contains('error'));
        if (!isLocal && !campThemeId) return showEscudoToast('Selecione um tema primeiro!');

        campRolling = true;
        const btn = document.getElementById('camp-btn-rolar');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rolando...'; }

        const label = entries.map(([l,q]) => `${q}D${l}`).join(' + ');

        const finalizarComDelay = (total, values, lbl) => {
            setTimeout(() => {
                mostrarResultadoEscudo(total, values, lbl);
                adicionarAoHistoricoCompartilhado(total, lbl, true);
                limparSelecaoCamp();
                campRolling = false;
                if (btn) btn.innerHTML = '<i class="fas fa-dice"></i> Rolar Dados';
                atualizarBtnCamp();
            }, 5000);
        };

        try {
            let finalTotal = 0, finalValues = [];

            if (isLocal || !escudoDddiceSDK) {
                entries.forEach(([lados, qtd]) => {
                    for (let i = 0; i < qtd; i++) {
                        const v = Math.floor(Math.random() * parseInt(lados)) + 1;
                        finalTotal += v;
                        finalValues.push({ value: v, type: `d${lados}` });
                    }
                });
                finalizarComDelay(finalTotal, finalValues, label);
            } else {
                const dddiceEntries = entries.filter(([l]) => ESCUDO_DDDICE_MAP[parseInt(l)]);
                const jsEntries     = entries.filter(([l]) => !ESCUDO_DDDICE_MAP[parseInt(l)]);
                const dddDice = [];
                dddiceEntries.forEach(([lados, qtd]) => {
                    const tipo = ESCUDO_DDDICE_MAP[parseInt(lados)];
                    for (let i = 0; i < qtd; i++) dddDice.push({ type: tipo, theme: campThemeId });
                });
                let jsTotal = 0, jsValues = [];
                jsEntries.forEach(([lados, qtd]) => {
                    for (let i = 0; i < qtd; i++) {
                        const v = Math.floor(Math.random() * parseInt(lados)) + 1;
                        jsTotal += v;
                        jsValues.push({ value: v, type: `d${lados}` });
                    }
                });

                if (dddDice.length > 0) {
                    let phpResult;
                    try {
                        const sdkRes = await escudoDddiceSDK.roll(dddDice, { label: `${USUARIO_NOME} | ${label}` });
                        const resValues = sdkRes.data?.values || sdkRes.values || [];
                        const resTotal  = sdkRes.data?.total_value !== undefined ? sdkRes.data.total_value : (sdkRes.total_value || 0);
                        phpResult = { ok: true, total: parseInt(resTotal), values: resValues.map(v => ({ value: parseInt(v.value), type: v.type })) };
                    } catch(e) {
                        phpResult = { error: e.message };
                    }
                    if (phpResult.error) {
                        showEscudoToast('Conexão 3D indisponível. Rolando local.');
                        entries.forEach(([lados, qtd]) => {
                            for (let i = 0; i < qtd; i++) {
                                const v = Math.floor(Math.random() * parseInt(lados)) + 1;
                                finalTotal += v;
                                finalValues.push({ value: v, type: `d${lados}` });
                            }
                        });
                        finalizarComDelay(finalTotal, finalValues, label);
                    } else {
                        finalTotal  = phpResult.total + jsTotal;
                        finalValues = [...phpResult.values, ...jsValues];
                        finalizarComDelay(finalTotal, finalValues, label);
                    }
                } else {
                    finalizarComDelay(jsTotal, jsValues, label);
                }
            }
        } catch(err) {
            console.error(err);
            showEscudoToast('Erro na rolagem. Modo Local ativo.');
            let fb = 0, fv = [];
            entries.forEach(([lados, qtd]) => {
                for (let i = 0; i < qtd; i++) {
                    const v = Math.floor(Math.random() * parseInt(lados)) + 1;
                    fb += v; fv.push({ value: v, type: `d${lados}` });
                }
            });
            finalizarComDelay(fb, fv, label);
        }
    }

    // Carregar histórico persistido da aba Dados
    function carregarHistoricoCamp() {
        carregarHistoricoJogadorLocal();
    }

    function adicionarAoHistoricoCompartilhado(resultado, descricao, souEu = false, remetente = null) {
        const logContainer = document.getElementById('camp-historico-lista');
        if (!logContainer) return;

        // Remover a mensagem de vazio
        const msgVazio = document.getElementById('camp-msg-vazio');
        if (msgVazio) msgVazio.remove();

        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        let nomeExibicao = 'Você';
        let keyBusca = '';
        if (!souEu) {
            let nomeTratado = remetente || 'Outro participante';
            const nomeLower = nomeTratado.toLowerCase().trim();
            if (nomeLower === 'llip35' || nomeLower === 'll1p35') {
                nomeTratado = 'Kauan Bryan';
            }
            nomeExibicao = nomeTratado;
            keyBusca = nomeExibicao;
        } else {
            const navNome = document.querySelector('.usuario-logado-nav .nome-nav');
            if (navNome) nomeExibicao = navNome.textContent.trim();
            keyBusca = nomeExibicao;
        }

        // Tentar buscar o nome do personagem do mapa PARTICIPANTES_MAPA
        let personagemNome = '';
        if (typeof PARTICIPANTES_MAPA !== 'undefined' && keyBusca) {
            const normalizedKey = keyBusca.toLowerCase().trim();
            personagemNome = PARTICIPANTES_MAPA[normalizedKey] || '';
        }

        // Criar elemento do item no histórico
        const novoItem = document.createElement('div');
        novoItem.className = 'camp-log-item';
        
        let personagemHTML = '';
        if (personagemNome) {
            personagemHTML = `<span class="camp-log-personagem">${personagemNome}</span>`;
        }
        
        novoItem.innerHTML = `
            <div class="camp-log-resultado">${resultado}</div>
            <div class="camp-log-info">
                <p>${time} • ${descricao}</p>
                <h4>${nomeExibicao} <span style="font-size:0.6rem; color:var(--premium-accent); font-weight:700; background:rgba(139,92,246,0.15); padding:1px 5px; border-radius:4px; margin-left:5px;">3D</span></h4>
                ${personagemHTML}
            </div>
        `;

        logContainer.prepend(novoItem);

        // Salvar no localStorage da campanha (chave unificada: table_historico_camp_dados_ID)
        const key = `table_historico_camp_dados_${idCampanha}`;
        let historico = [];
        try {
            const data = localStorage.getItem(key);
            if (data) historico = JSON.parse(data);
        } catch(e) { console.error(e); }
        
        historico.unshift({ resultado, descricao, time, nomeExibicao, personagemNome });
        if (historico.length > 50) historico.pop();
        localStorage.setItem(key, JSON.stringify(historico));

        // Sincronizar também com o histórico do Escudo (se o mestre estiver na tela e for rolagem própria)
        if (typeof isMasterLogado !== 'undefined' && isMasterLogado && souEu) {
            const sidebarLog = document.getElementById('sidebar-dados-lista');
            if (sidebarLog) {
                const itemEscudo = document.createElement('div');
                itemEscudo.className = 'item-dado real-roll';
                itemEscudo.innerHTML = `
                    <div class="hexa-dado" style="border-color:var(--premium-accent);color:#000;background:#fff;font-weight:800;display:flex;align-items:center;justify-content:center;">${resultado}</div>
                    <div class="info-rolagem">
                        <p>${time} • ${descricao}</p>
                        <h4 style="color:#fff;">${nomeExibicao} <span style="font-size:0.6rem;color:var(--premium-accent);font-weight:700;background:rgba(139,92,246,0.15);padding:1px 5px;border-radius:4px;">3D</span></h4>
                        ${personagemHTML}
                    </div>
                `;
                sidebarLog.innerHTML = '';
                sidebarLog.appendChild(itemEscudo);
            }
        }
    }

    function carregarHistoricoJogadorLocal() {
        const logContainer = document.getElementById('camp-historico-lista');
        if (!logContainer) return;
        
        const key = `table_historico_camp_dados_${idCampanha}`;
        let historico = [];
        try {
            const data = localStorage.getItem(key);
            if (data) historico = JSON.parse(data);
        } catch(e) { console.error(e); }

        if (historico.length > 0) {
            const msgVazio = document.getElementById('camp-msg-vazio');
            if (msgVazio) msgVazio.remove();
            
            logContainer.innerHTML = '';
            historico.forEach(item => {
                const div = document.createElement('div');
                div.className = 'camp-log-item';
                
                let personagemHTML = '';
                if (item.personagemNome) {
                    personagemHTML = `<span class="camp-log-personagem">${item.personagemNome}</span>`;
                }
                
                div.innerHTML = `
                    <div class="camp-log-resultado">${item.resultado}</div>
                    <div class="camp-log-info">
                        <p>${item.time} • ${item.descricao}</p>
                        <h4>${item.nomeExibicao} <span style="font-size:0.6rem; color:var(--premium-accent); font-weight:700; background:rgba(139,92,246,0.15); padding:1px 5px; border-radius:4px; margin-left:5px;">3D</span></h4>
                        ${personagemHTML}
                    </div>
                `;
                logContainer.appendChild(div);
            });
        }
    }

    function limparHistoricoJogador() {
        if (!confirm('Deseja limpar o histórico de rolagens desta sessão?')) return;
        // Limpar aba Dados da campanha
        const listaCamp = document.getElementById('camp-historico-lista');
        if (listaCamp) listaCamp.innerHTML = '<div style="text-align:center;padding:40px;color:#555;" id="camp-msg-vazio">Nenhuma rolagem feita ainda nesta sessão.</div>';
        try { localStorage.removeItem(`table_historico_camp_dados_${idCampanha}`); } catch(e) {}
    }

    function setEscudoStatus(state) {
        const dot = document.getElementById('escudo-status-dot');
        if (dot) {
            dot.className = state;
            dot.title = state === 'ok' ? 'Dados 3D ativos' : state === 'loading' ? 'Inicializando dados...' : 'Erro';
        }
        
        const dotJogador = document.getElementById('jogador-status-dot');
        if (dotJogador) {
            dotJogador.className = state;
            dotJogador.title = state === 'ok' ? 'Dados 3D ativos' : state === 'loading' ? 'Inicializando dados...' : 'Erro';
            
            // Alterar background do dot jogador dinamicamente para maior clareza visual
            if (state === 'ok') {
                dotJogador.style.backgroundColor = '#2ecc71';
                dotJogador.style.boxShadow = '0 0 10px #2ecc71';
            } else if (state === 'loading') {
                dotJogador.style.backgroundColor = '#f1c40f';
                dotJogador.style.boxShadow = '0 0 10px #f1c40f';
            } else {
                dotJogador.style.backgroundColor = '#e74c3c';
                dotJogador.style.boxShadow = '0 0 10px #e74c3c';
            }
        }
    }

    function showEscudoToast(msg) {
        const t = document.getElementById('escudo-toast'); if (!t) return;
        t.textContent = msg; t.classList.add('show');
        clearTimeout(t._timer); t._timer = setTimeout(() => t.classList.remove('show'), 4500);
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharResultadoEscudo(); });

    async function removerPersonagem(id_personagem, acao = 'remover') {
        const confirmMsg = acao === 'sair' 
            ? 'Tem certeza que deseja tirar seu personagem desta campanha?' 
            : 'Tem certeza que deseja remover este personagem da campanha?';
        
        if (!await TableModal.confirm(confirmMsg, 'Tirar Personagem', 'warning')) return;

        try {
            const formData = new FormData();
            formData.append('action', 'remover_personagem');
            formData.append('campaign_id', '<?= (int)$id_campanha ?>');
            formData.append('personagem_id', id_personagem);

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.sucesso) {
                window.location.reload();
            } else {
                await TableModal.alert('Erro: ' + (data.mensagem || 'Não foi possível realizar a ação.'), 'Falha ao Remover', 'error');
            }
        } catch (err) {
            console.error(err);
            await TableModal.alert('Erro de conexão ao tentar remover o personagem.', 'Erro de Conexão', 'error');
        }
    }

    async function sairDaCampanha() {
        // Verifica se há algum personagem que pertence ao usuário logado na lista de personagens da campanha
        const temPersonagem = campanhaInicialPersonagems.some(p => parseInt(p.id_dono) === usuarioLogadoId);

        if (temPersonagem) {
            await TableModal.alert('Para sair da campanha, primeiro retire o seu personagem da campanha clicando em "Sair" no card do seu personagem.', 'Remova o Personagem Primeiro', 'warning');
            return;
        }

        executarSaidaCampanha();
    }

    async function executarSaidaCampanha() {
        if (!await TableModal.confirm('Deseja realmente sair desta campanha?', 'Sair da Campanha', 'warning')) return;

        try {
            const formData = new FormData();
            formData.append('action', 'sair_campanha');
            formData.append('campaign_id', idCampanha);

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.sucesso) {
                window.location.href = 'perfil.php';
            } else {
                await TableModal.alert('Erro: ' + (data.mensagem || 'Não foi possível sair da campanha.'), 'Falha ao Sair', 'error');
            }
        } catch (err) {
            console.error(err);
            await TableModal.alert('Erro de conexão ao tentar sair da campanha.', 'Erro de Conexão', 'error');
        }
    }

    async function expulsarJogador(idUsuario, nomeUsuario) {
        const confirmMsg = `Tem certeza que deseja expulsar o jogador "${nomeUsuario}" da campanha? Isso desvinculará todos os personagens dele pertencentes a esta campanha e o removerá da lista de participantes.`;
        
        if (!await TableModal.confirm(confirmMsg, 'Expulsar Jogador', 'warning')) return;

        try {
            const response = await fetch('../app/ajax/expulsar-jogador.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_campanha: idCampanha,
                    id_usuario: idUsuario
                })
            });

            const data = response.ok ? await response.json() : null;
            if (data && data.success) {
                await TableModal.alert(`Jogador "${nomeUsuario}" foi expulso da campanha com sucesso!`, 'Jogador Expulso', 'success');
                window.location.reload();
            } else {
                await TableModal.alert('Erro: ' + ((data && data.error) || 'Não foi possível expulsar o jogador.'), 'Falha ao Expulsar', 'error');
            }
        } catch (err) {
            console.error(err);
            await TableModal.alert('Erro de conexão ao tentar expulsar o jogador.', 'Erro de Conexão', 'error');
        }
    }

    async function toggleVisibilidade(id_personagem, novo_estado) {
        try {
            const response = await fetch('../app/ajax/alterar-visibilidade-personagem.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_campanha: '<?= (int)$id_campanha ?>',
                    id_personagem: id_personagem,
                    fl_publico: novo_estado
                })
            });

            const data = await response.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Erro: ' + (data.error || 'Não foi possível alterar a visibilidade.'));
            }
        } catch (err) {
            console.error(err);
            alert('Erro de conexão ao tentar alterar a visibilidade.');
        }
    }
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const inputNomeCampanha = document.getElementById('nome-campanha');
        if (inputNomeCampanha) {
            inputNomeCampanha.addEventListener('input', function() {
                let titulo = this.value.trim();
                document.title = 'TABLE | ' + (titulo ? titulo : 'Nova Campanha');
            });
        }

        // Inicialização da aba Dados e SDK 3D no dashboard da campanha
        if (typeof campanhaInicial !== 'undefined' && campanhaInicial) {
            initEscudoSDK().then(() => {
                // Inicializar aba Dados após SDK estar pronto
                inicializarEventosDadosCamp();
                carregarHistoricoCamp();
                // Inicializar temas da aba camp (usa SDK do escudo compartilhado)
                setTimeout(initCampThemes, 1500);
            }).catch(() => {
                inicializarEventosDadosCamp();
                carregarHistoricoCamp();
                setCampStatus('local');
                const btn = document.getElementById('camp-btn-rolar');
                if (btn) btn.disabled = false;
                const select = document.getElementById('camp-theme-select');
                if (select) { select.innerHTML = '<option value="local">Rolagem Local Premium</option>'; select.disabled = true; }
            });
            carregarHistoricoJogadorLocal();
        }
    });
    </script>
    <script>
    // Gerenciamento de Transição Suave do Loader de Tela Cheia
    window.addEventListener('load', () => {
        const loader = document.getElementById('fullscreen-loader');
        if (loader) {
            loader.style.opacity = '0';
            loader.style.visibility = 'hidden';
            setTimeout(() => {
                loader.style.display = 'none';
            }, 400); // Aguarda o fim da animação de opacidade (0.4s)
        }
    });

    // Ativa o loader ao submeter formulários ou recarregar a página
    window.addEventListener('beforeunload', () => {
        const loader = document.getElementById('fullscreen-loader');
        if (loader) {
            loader.style.display = 'flex';
            loader.style.opacity = '1';
            loader.style.visibility = 'visible';
        }
    });
    </script>
</body>
</html>


