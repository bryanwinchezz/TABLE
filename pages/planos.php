<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../app/config/database.php';

// Buscar status dos planos do usuário ativo
$possuiMapas = false;
$possuiSistemas = false;
$possuiCompleto = false;
$jaMestre = false;
$isAdmin  = false;

if (isset($_SESSION['usuario'])) {
    try {
        $pdo = Database::getConexao();

        // Migração silenciosa individual para as colunas de planos e desistência caso não existam
        $colunasAdd = [
            'fl_plano_mapas' => "ALTER TABLE tb_usuario ADD COLUMN fl_plano_mapas TINYINT(1) NOT NULL DEFAULT 0",
            'fl_plano_sistemas' => "ALTER TABLE tb_usuario ADD COLUMN fl_plano_sistemas TINYINT(1) NOT NULL DEFAULT 0",
            'fl_plano_completo' => "ALTER TABLE tb_usuario ADD COLUMN fl_plano_completo TINYINT(1) NOT NULL DEFAULT 0",
            'dt_desistencia_mestre' => "ALTER TABLE tb_usuario ADD COLUMN dt_desistencia_mestre DATETIME DEFAULT NULL"
        ];

        foreach ($colunasAdd as $col => $sql) {
            try {
                $stmtCheck = $pdo->query("SHOW COLUMNS FROM tb_usuario LIKE '$col'");
                if ($stmtCheck->rowCount() === 0) {
                    $pdo->exec($sql);
                }
            } catch (Exception $e) {
                // Silencioso por coluna
            }
        }

        // Garante que o administrador Kauan Bryan sempre tenha seus privilégios ativos ao entrar
        if ($_SESSION['usuario']['nome'] === 'Kauan Bryan') {
            $stmtGarante = $pdo->prepare("
                UPDATE tb_usuario 
                SET tp_cargo = 'admin', fl_plano_mapas = 1, fl_plano_sistemas = 1, fl_plano_completo = 1, dt_desistencia_mestre = NULL 
                WHERE id_usuario = ?
            ");
            $stmtGarante->execute([$_SESSION['usuario']['id']]);
            $_SESSION['usuario']['cargo'] = 'admin';
        }

        // Busca nm_usuario e dt_desistencia_mestre para controle de privilégios de Kauan Bryan
        $stmtUsr = $pdo->prepare("SELECT nm_usuario, fl_plano_mapas, fl_plano_sistemas, fl_plano_completo, tp_cargo, dt_desistencia_mestre FROM tb_usuario WHERE id_usuario = ? LIMIT 1");
        $stmtUsr->execute([$_SESSION['usuario']['id']]);
        $dadosUsr = $stmtUsr->fetch();
        if ($dadosUsr) {
            $cargoLower = strtolower($dadosUsr['tp_cargo'] ?? 'jogador');
            $nmUsuario  = $dadosUsr['nm_usuario'] ?? '';
            $dtDesistencia = $dadosUsr['dt_desistencia_mestre'] ?? null;

            // Verificar expiração do período de carência (1 mês) para o Kauan Bryan
            if ($nmUsuario === 'Kauan Bryan' && !empty($dtDesistencia)) {
                $timeDesistencia = strtotime($dtDesistencia);
                $timeExpiracao = strtotime('+1 month', $timeDesistencia);
                if (time() > $timeExpiracao) {
                    // Limpa os privilégios temporários no banco de dados
                    $stmtLimpar = $pdo->prepare("
                        UPDATE tb_usuario 
                        SET fl_plano_mapas = 0, fl_plano_sistemas = 0, fl_plano_completo = 0, dt_desistencia_mestre = NULL 
                        WHERE id_usuario = ?
                    ");
                    $stmtLimpar->execute([$_SESSION['usuario']['id']]);
                    
                    $dadosUsr['fl_plano_mapas'] = 0;
                    $dadosUsr['fl_plano_sistemas'] = 0;
                    $dadosUsr['fl_plano_completo'] = 0;
                    $dtDesistencia = null;
                }
            }

            // O privilégio de "tudo adquirido" sem assinatura é exclusivo do Kauan Bryan como admin
            $isAdminPrivilegio = ($cargoLower === 'admin' && $nmUsuario === 'Kauan Bryan');
            $isAdmin = $isAdminPrivilegio;

            $possuiMapas    = ((int)$dadosUsr['fl_plano_mapas']    === 1 || $isAdminPrivilegio);
            $possuiSistemas = ((int)$dadosUsr['fl_plano_sistemas'] === 1 || $isAdminPrivilegio);
            $possuiCompleto = ((int)$dadosUsr['fl_plano_completo'] === 1 || $isAdminPrivilegio);
            $jaMestre       = ($cargoLower === 'mestre' || $isAdminPrivilegio);
        }
    } catch (Exception $e) {}
}

// ============================================================
// CONFIGURAÇÃO DO MERCADO PAGO
// Troque pelos valores reais do seu painel:
// mercadopago.com.br/developers/panel/app/2950252907685269
// ============================================================
define('MP_ACCESS_TOKEN', 'TEST-2950252907685269-052820-5638955fb1ef3463c2366736171cab4d-66270410'); // ← cole o Access Token
define('MP_BASE_URL',     'https://api.mercadopago.com');

// IDs dos Planos de Assinatura (gerados pelo criar_planos.php)
// Cole os IDs aqui após rodá-lo uma vez no terminal: php criar_planos.php
define('MP_PLANO_MAPAS_ID',    '8bff3570299b446b8bfe467cc5a2ad6d'); // ← ex: '2c938084726fca480172750000000001'
define('MP_PLANO_SISTEMAS_ID', 'ba486f10c60f41509308a9d73dabab63'); // ← ex: '2c938084726fca480172750000000002'
define('MP_PLANO_COMPLETO_ID', 'e7df00cba75c44d6bb2c43d97ba6b408'); // ← ex: '2c938084726fca480172750000000003'

// ============================================================

// Patch dinâmico para a navbar com verificação física absoluta
$fotoNavbar = (!empty($_SESSION['usuario']['foto']) && file_exists(dirname(__DIR__) . '/' . ltrim(str_replace('../', '', $_SESSION['usuario']['foto']), '/'))) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png';

// ============================================================
// RETORNO DO MERCADO PAGO (back_url após pagamento)
// Seguro: verifica o status diretamente na API do MP,
// gera a chave de ativação pendente e exibe o código para cópia.
// ============================================================
$pagamentoSucesso = false;
$chaveGerada = '';
$planoAssinadoNome = '';

$processarPagamento = false;
$preapproval_id = '';
$plano_slug = '';

if (isset($_GET['preapproval_id']) && isset($_SESSION['usuario'])) {
    $preapproval_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['preapproval_id']);

    // Consulta o status real da assinatura na API do MP
    $ch = curl_init(MP_BASE_URL . '/preapproval/' . $preapproval_id);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . MP_ACCESS_TOKEN],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $mp_response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $status_mp      = $mp_response['status']             ?? '';
    $ext_ref        = $mp_response['external_reference'] ?? '';
    $plano_mp_id    = $mp_response['preapproval_plan_id'] ?? '';

    if ($status_mp === 'authorized' && (string)$ext_ref === (string)$_SESSION['usuario']['id']) {
        $plano_slug = 'completo';
        if ($plano_mp_id === MP_PLANO_MAPAS_ID) $plano_slug = 'mapas';
        elseif ($plano_mp_id === MP_PLANO_SISTEMAS_ID) $plano_slug = 'sistemas';
        
        $processarPagamento = true;
    }
} elseif (isset($_GET['simular_pagamento']) && isset($_SESSION['usuario'])) {
    // 🎲 MODO SIMULADOR DE DESENVOLVEDOR 
    $plano_slug = in_array($_GET['simular_pagamento'], ['mapas', 'sistemas', 'completo']) ? $_GET['simular_pagamento'] : 'completo';
    $preapproval_id = 'SIM-' . strtoupper($plano_slug) . '-' . time() . '-' . $_SESSION['usuario']['id'];
    $processarPagamento = true;
}

if ($processarPagamento) {
    try {
        $pdo = Database::getConexao();

        // 2. Verificar se já existe uma chave de ativação para esta assinatura
        $stmtChkChave = $pdo->prepare("SELECT ds_codigo FROM tb_chave_ativacao WHERE mp_assinatura_id = ? LIMIT 1");
        $stmtChkChave->execute([$preapproval_id]);
        $chaveExistente = $stmtChkChave->fetch();

        if ($chaveExistente) {
            $chaveGerada = $chaveExistente['ds_codigo'];
        } else {
            // Função auxiliar para gerar chave aleatória
            if (!function_exists('gerarChaveAleatoria')) {
                function gerarChaveAleatoria($prefixo = 'TBL') {
                    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                    $bloco1 = '';
                    $bloco2 = '';
                    $bloco3 = '';
                    for ($i = 0; $i < 4; $i++) {
                        $bloco1 .= $chars[rand(0, strlen($chars) - 1)];
                        $bloco2 .= $chars[rand(0, strlen($chars) - 1)];
                        $bloco3 .= $chars[rand(0, strlen($chars) - 1)];
                    }
                    return "{$prefixo}-{$bloco1}-{$bloco2}-{$bloco3}";
                }
            }

            $chaveGerada = gerarChaveAleatoria('TBL');

            // Insere a nova chave pendente vinculada ao comprador
            $stmtInsChave = $pdo->prepare("
                INSERT INTO tb_chave_ativacao (ds_codigo, tp_plano, fl_usada, id_usuario_comprador, mp_assinatura_id)
                VALUES (?, ?, 0, ?, ?)
            ");
            $stmtInsChave->execute([$chaveGerada, $plano_slug, $_SESSION['usuario']['id'], $preapproval_id]);
            
            // Atualiza a coluna de assinatura no usuário para fins de referência
            $stmtUsrAss = $pdo->prepare("UPDATE tb_usuario SET mp_assinatura_id = ? WHERE id_usuario = ?");
            $stmtUsrAss->execute([$preapproval_id, $_SESSION['usuario']['id']]);
        }

        $nomes_planos = [
            'mapas' => 'Plano de Mapas',
            'sistemas' => 'Plano de Sistemas',
            'completo' => 'Plano Completo'
        ];
        $planoAssinadoNome = $nomes_planos[$plano_slug] ?? 'Premium';

    } catch (Exception $e) {
        // falha silenciosa
    }
}


// ============================================================
// ENDPOINT: RESGATE DE CHAVE VIA AJAX POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resgatar_chave_ajax'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['usuario'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Você precisa estar logado para ativar uma chave!']);
        exit;
    }
    
    $chave = trim($_POST['chave'] ?? '');
    
    if (empty($chave)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A chave não pode estar vazia.']);
        exit;
    }
    
    try {
        $pdo = Database::getConexao();
        
        // Buscar a chave pendente
        $stmt = $pdo->prepare("SELECT * FROM tb_chave_ativacao WHERE ds_codigo = ? AND fl_usada = 0 LIMIT 1");
        $stmt->execute([$chave]);
        $chaveDb = $stmt->fetch();
        
        $tp_plano = '';
        
        if (!$chaveDb) {
            // Suporte para chaves estáticas de teste
            $chavesValidasTeste = [
                'TBL-MAPS-2026' => 'mapas',
                'TBL-SIST-2026' => 'sistemas',
                'TBL-PREM-2026' => 'completo'
            ];
            
            if (isset($chavesValidasTeste[$chave])) {
                $tp_plano = $chavesValidasTeste[$chave];
            } else {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Chave inválida, já utilizada ou inexistente.']);
                exit;
            }
        } else {
            $tp_plano = $chaveDb['tp_plano'];
            
            // Marcar chave real como usada
            $stmtUsar = $pdo->prepare("UPDATE tb_chave_ativacao SET fl_usada = 1, id_usuario_ativador = ?, dt_ativacao = NOW() WHERE id_chave = ?");
            $stmtUsar->execute([$_SESSION['usuario']['id'], $chaveDb['id_chave']]);
        }
        
        // Ativar as flags correspondentes na conta do usuário
        if ($tp_plano === 'mapas') {
            $stmtAct = $pdo->prepare("UPDATE tb_usuario SET fl_plano_mapas = 1, tp_cargo = 'mestre' WHERE id_usuario = ?");
        } elseif ($tp_plano === 'sistemas') {
            $stmtAct = $pdo->prepare("UPDATE tb_usuario SET fl_plano_sistemas = 1, tp_cargo = 'mestre' WHERE id_usuario = ?");
        } else {
            $stmtAct = $pdo->prepare("UPDATE tb_usuario SET fl_plano_completo = 1, fl_plano_mapas = 1, fl_plano_sistemas = 1, tp_cargo = 'mestre' WHERE id_usuario = ?");
        }
        $stmtAct->execute([$_SESSION['usuario']['id']]);
        
        // Atualizar sessão ativamente
        $_SESSION['usuario']['cargo'] = 'mestre';
        
        $nomes_planos = [
            'mapas' => 'Plano de Mapas',
            'sistemas' => 'Plano de Sistemas',
            'completo' => 'Plano Completo'
        ];
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Sua chave de ativação foi resgatada com sucesso!',
            'plano' => $nomes_planos[$tp_plano]
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao ativar a chave: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================================
// PLANO GRÁTIS — promove para Mestre sem pagamento
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['virar_mestre_plano'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['usuario'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Você precisa estar logado para virar Mestre!']);
        exit;
    }
    try {
        $pdo = Database::getConexao();
        $stmt = $pdo->prepare("UPDATE tb_usuario SET tp_cargo = 'mestre' WHERE id_usuario = ?");
        $stmt->execute([$_SESSION['usuario']['id']]);
        $_SESSION['usuario']['cargo'] = 'mestre';
        echo json_encode(['sucesso' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao processar sua solicitação: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================================
// CRIAR ASSINATURA NO MERCADO PAGO (Preapproval)
// Endpoint correto para pagamentos recorrentes mensais
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_preferencia_mp'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['usuario'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Você precisa estar logado para realizar a assinatura!']);
        exit;
    }

    $plano_slug = $_POST['plano_id'] ?? 'completo';

    $planos_info = [
        'mapas'    => ['titulo' => 'Plano de Mapas',    'preco' => 19.90, 'mp_id' => MP_PLANO_MAPAS_ID],
        'sistemas' => ['titulo' => 'Plano de Sistemas', 'preco' => 29.90, 'mp_id' => MP_PLANO_SISTEMAS_ID],
        'completo' => ['titulo' => 'Plano Completo',    'preco' => 49.90, 'mp_id' => MP_PLANO_COMPLETO_ID],
    ];

    if (!isset($planos_info[$plano_slug])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Plano inválido.']);
        exit;
    }

    $plano = $planos_info[$plano_slug];

    // Se os planos ainda não foram criados no MP, avisa
    if (empty($plano['mp_id'])) {
        echo json_encode([
            'sucesso'  => false,
            'mensagem' => 'Planos ainda não configurados. Rode criar_planos.php e preencha os IDs em planos.php.',
        ]);
        exit;
    }

    // Consulta o plano de assinatura diretamente no MP para obter o link do Checkout Pro (init_point)
    $ch = curl_init(MP_BASE_URL . '/preapproval_plan/' . $plano['mp_id']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . MP_ACCESS_TOKEN,
            'Content-Type: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response  = json_decode(curl_exec($ch), true);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (($http_code === 200 || $http_code === 201) && isset($response['init_point'])) {
        // init_point = Link oficial do Checkout Pro do Mercado Pago para esta assinatura
        echo json_encode([
            'sucesso'      => true,
            'checkout_url' => $response['init_point'],
        ]);
    } else {
        echo json_encode([
            'sucesso'  => false,
            'mensagem' => 'Erro ao iniciar assinatura no Mercado Pago: ' . ($response['message'] ?? 'Tente novamente.'),
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Planos</title>
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">

    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/planos.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        /* ===== RESET & BASE ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --cor-fundo:       #0d0b1a;
            --cor-fundo-card:  #13102280;
            --cor-borda-card:  #2a2050;
            --cor-acento:      #6c3fd4;
            --cor-acento-vivo: #7c4fe0;
            --cor-texto:       #e8e0f7;
            --cor-texto-suave: #9b8fc0;
            --cor-branco:      #ffffff;
            --cor-preco-bg:    #1a1530;
        }

        body.pagina-inicial {
            background-color: var(--cor-fundo);
            color: var(--cor-texto);
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
        }

        /* ===== HERO / SECAO-DESTAQUE ===== */
        .secao-destaque {
            padding: 120px 5% 60px;
            background: linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.75)), url("../img/fundo_inicial.jpg") center center / cover no-repeat fixed;
            border-bottom: 2px solid var(--borda-escura);
            display: flex;
            flex-direction: column;
        }

        /* orbs decorativos */
        .secao-destaque::before,
        .secao-destaque::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }
        .secao-destaque::before {
            width: 480px; height: 480px;
            right: 5%; top: -80px;
            background: radial-gradient(circle, #6c3fd455 0%, transparent 70%);
            filter: blur(30px);
        }
        .secao-destaque::after {
            width: 280px; height: 280px;
            right: 20%; top: 10%;
            background: radial-gradient(circle, #9b60ff33 0%, transparent 70%);
            filter: blur(20px);
        }

        /* ===== PLANOS TÍTULO ===== */
        .planos-titulo {
            position: relative;
            z-index: 2;
            margin-bottom: 16px;
        }

        .planos-titulo h1 {
            font-size: clamp(1.8rem, 3vw, 2.6rem);
            font-weight: 800;
            color: var(--cor-branco);
            letter-spacing: -0.5px;
        }

        .planos-titulo p {
            margin-top: 12px;
            max-width: 860px;
            font-size: 0.97rem;
            line-height: 1.65;
            color: var(--cor-texto-suave);
        }

        /* ===== GRID DE CARDS ===== */
        .planos-grid {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin: 36px auto 0;
            max-width: 960px;
        }

        @media (max-width: 900px) {
            .secao-destaque { padding: 40px 24px 60px; }
        }
        @media (max-width: 680px) {
            .planos-grid { grid-template-columns: 1fr; }
        }

        /* ===== CARD ===== */
        .plano-card {
            background: #13102299;
            border: 1px solid var(--cor-borda-card);
            border-radius: 16px;
            padding: 28px 22px 24px;
            display: flex;
            flex-direction: column;
            gap: 18px;
            backdrop-filter: blur(8px);
            transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
            cursor: default;
        }

        .plano-card:hover {
            transform: translateY(-5px);
            border-color: var(--cor-acento);
            box-shadow: 0 8px 32px #6c3fd430;
        }

        /* cabeçalho do card */
        .plano-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .plano-card-header h3 {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--cor-branco);
            line-height: 1.25;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .plano-card-header .plano-icone {
            font-size: 1.2rem;
            color: var(--cor-texto-suave);
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* barra de descrição */
        .plano-descricao {
            display: flex;
            gap: 10px;
            flex: 1;
        }

        .plano-descricao .barra {
            width: 3px;
            border-radius: 4px;
            background: var(--cor-acento);
            flex-shrink: 0;
        }

        .plano-descricao p {
            font-size: 0.84rem;
            line-height: 1.6;
            color: var(--cor-texto-suave);
        }

        /* preço */
        .plano-preco {
            margin-top: auto;
        }

        .plano-preco .btn-preco {
            display: inline-block;
            background: var(--cor-preco-bg);
            border: 1.5px solid var(--cor-borda-card);
            color: var(--cor-branco);
            font-size: 0.95rem;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: background .2s, border-color .2s;
            text-decoration: none;
            white-space: nowrap;
        }

        .plano-preco .btn-preco:hover {
            background: var(--cor-acento);
            border-color: var(--cor-acento-vivo);
        }

        /* botão grátis (branco) */
        .plano-preco .btn-gratis {
            display: inline-block;
            background: var(--cor-branco);
            color: #0d0b1a;
            font-size: 0.95rem;
            font-weight: 700;
            padding: 10px 32px;
            border-radius: 50px;
            cursor: pointer;
            transition: background .2s, color .2s;
            text-decoration: none;
            border: none;
        }

        .plano-preco .btn-gratis:hover {
            background: #e0d4ff;
        }

        /* ===== SEÇÃO CHAVES DE ATIVAÇÃO ===== */
        .secao-chaves {
            margin-top: 64px;
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 960px;
            margin-left: auto;
            margin-right: auto;
        }

        .chaves-container {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 32px;
            background: rgba(19, 16, 34, 0.6);
            border: 1px solid var(--cor-borda-card);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(12px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
        }

        @media (max-width: 768px) {
            .chaves-container {
                grid-template-columns: 1fr;
                gap: 32px;
                padding: 28px 20px;
            }
            .secao-chaves {
                margin-top: 48px;
            }
        }

        .chaves-info h2 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--cor-branco);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chaves-info h2 i {
            color: var(--cor-acento-vivo);
            filter: drop-shadow(0 0 8px rgba(124, 79, 224, 0.5));
        }

        .chaves-info > p {
            font-size: 0.95rem;
            line-height: 1.6;
            color: var(--cor-texto-suave);
            margin-bottom: 28px;
        }

        .lista-passos {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .passo-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .passo-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--cor-acento);
            color: var(--cor-branco);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 0 10px rgba(108, 63, 212, 0.4);
        }

        .passo-item h4 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--cor-branco);
            margin-bottom: 4px;
        }

        .passo-item p {
            font-size: 0.85rem;
            line-height: 1.5;
            color: var(--cor-texto-suave);
        }

        .chaves-ativacao {
            background: rgba(26, 21, 48, 0.4);
            border: 1px solid rgba(108, 63, 212, 0.2);
            border-radius: 20px;
            padding: 32px 24px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .chaves-ativacao h3 {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--cor-branco);
            margin-bottom: 10px;
            letter-spacing: -0.3px;
        }

        .chaves-ativacao > p {
            font-size: 0.88rem;
            line-height: 1.5;
            color: var(--cor-texto-suave);
            margin-bottom: 24px;
        }

        .form-ativacao {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .input-glow-container {
            position: relative;
            width: 100%;
        }

        .input-glow-container input {
            width: 100%;
            background: rgba(13, 11, 26, 0.8);
            border: 1.5px solid var(--cor-borda-card);
            border-radius: 12px;
            padding: 14px 16px 14px 44px;
            color: var(--cor-branco);
            font-family: monospace;
            font-size: 1.05rem;
            letter-spacing: 1.5px;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .input-glow-container input::placeholder {
            font-family: 'Segoe UI', sans-serif;
            font-size: 0.9rem;
            letter-spacing: 0;
            color: #635885;
        }

        .input-glow-container input:focus {
            border-color: var(--cor-acento-vivo);
            box-shadow: 0 0 15px rgba(124, 79, 224, 0.25);
        }

        .input-glow-container .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #635885;
            font-size: 1.1rem;
            transition: color 0.3s;
        }

        .input-glow-container input:focus + .input-icon {
            color: var(--cor-acento-vivo);
        }

        .btn-ativar {
            width: 100%;
            background: linear-gradient(135deg, var(--cor-acento), var(--cor-acento-vivo));
            color: var(--cor-branco);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(108, 63, 212, 0.3);
        }

        .btn-ativar:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
            box-shadow: 0 6px 20px rgba(108, 63, 212, 0.45);
        }

        .btn-ativar:active {
            transform: translateY(0);
        }

        .chave-feedback-msg {
            margin-top: 16px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.88rem;
            line-height: 1.5;
            display: none;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .feedback-sucesso {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(46, 204, 113, 0.15);
            border: 1px solid #2ecc71;
            color: #2ecc71;
        }

        .feedback-erro {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        
        .feedback-erro a {
            color: inherit;
            text-decoration: underline;
            font-weight: 700;
        }

        /* ===== ALERTA DE BARREIRA (SAAS PAYWALL) ===== */
        .alerta-paywall {
            background: rgba(231, 76, 60, 0.15);
            border: 2px solid #e74c3c;
            border-radius: 16px;
            padding: 18px 24px;
            margin: 0 auto 30px;
            max-width: 960px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            backdrop-filter: blur(10px);
            animation: slideDownPaywall 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 30px rgba(231, 76, 60, 0.15);
            position: relative;
            z-index: 5;
            width: 100%;
        }

        @keyframes slideDownPaywall {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alerta-paywall.alerta-sistemas {
            background: rgba(108, 63, 212, 0.15);
            border-color: var(--cor-acento-vivo);
            box-shadow: 0 10px 30px rgba(108, 63, 212, 0.25);
        }
        
        .alerta-paywall.alerta-mapas {
            background: rgba(46, 204, 113, 0.15);
            border-color: #2ecc71;
            box-shadow: 0 10px 30px rgba(46, 204, 113, 0.25);
        }

        .alerta-paywall-content {
            display: flex;
            align-items: center;
            gap: 16px;
            color: var(--cor-texto);
            font-size: 0.95rem;
            line-height: 1.5;
            text-align: left;
        }

        .alerta-paywall-content i {
            font-size: 1.5rem;
            color: #e74c3c;
            filter: drop-shadow(0 0 5px rgba(231, 76, 60, 0.5));
        }

        .alerta-sistemas .alerta-paywall-content i {
            color: var(--cor-acento-vivo);
            filter: drop-shadow(0 0 8px rgba(124, 79, 224, 0.5));
        }
        
        .alerta-mapas .alerta-paywall-content i {
            color: #2ecc71;
            filter: drop-shadow(0 0 8px rgba(46, 204, 113, 0.5));
        }

        .btn-fechar-alerta {
            background: transparent;
            border: none;
            color: var(--cor-texto-suave);
            cursor: pointer;
            font-size: 1.2rem;
            transition: color 0.2s, transform 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-fechar-alerta:hover {
            color: var(--cor-branco);
            transform: scale(1.1);
        }

        /* ===== NAVEGAÇÃO DE ABAS COM LINHA ROXA ANIMADA ===== */
        .planos-abas-navegacao {
            display: flex;
            gap: 10px;
            margin: 30px auto 16px;
            max-width: 960px;
            z-index: 2;
            position: relative;
            justify-content: center;
            border-bottom: 2px solid rgba(108, 63, 212, 0.15);
            padding-bottom: 0;
            width: 100%;
        }

        .btn-aba-planos {
            background: transparent;
            border: none;
            color: var(--cor-texto-suave);
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            padding: 12px 16px;
            transition: color 0.3s ease;
            position: relative;
            outline: none;
            width: 180px; /* Largura idêntica para os botões para congelar a posição centralizada! */
            text-align: center;
        }

        .btn-aba-planos::after {
            content: '';
            position: absolute;
            bottom: -2px; /* Alinha perfeitamente em cima da borda inferior do container */
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--cor-acento-vivo);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 3px 3px 0 0;
            box-shadow: 0 0 10px rgba(124, 79, 224, 0.8);
        }

        .btn-aba-planos:hover {
            color: var(--cor-branco);
        }

        .btn-aba-planos.ativa {
            color: var(--cor-branco) !important;
        }

        .btn-aba-planos.ativa::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        /* ===== MODAL MESTRE SUCCESSO ===== */
        .modal-mestre-overlay {
            position: fixed;
            inset: 0;
            background: rgba(13, 11, 26, 0.9);
            backdrop-filter: blur(15px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            animation: fadeInModal 0.4s ease;
        }

        .modal-mestre-card {
            background: linear-gradient(155deg, #131022 0%, #1a1530 100%);
            border: 2px solid var(--cor-acento-vivo);
            border-radius: 28px;
            padding: 48px 36px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(108, 63, 212, 0.3), 0 0 80px rgba(108, 63, 212, 0.15);
            animation: scaleInModal 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
        }

        @keyframes fadeInModal {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes scaleInModal {
            from { transform: scale(0.85); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-mestre-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(124, 79, 224, 0.15);
            border: 2px solid var(--cor-acento-vivo);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 2.2rem;
            color: var(--cor-acento-vivo);
            filter: drop-shadow(0 0 10px rgba(124, 79, 224, 0.4));
        }

        .modal-mestre-card h2 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--cor-branco);
            margin-bottom: 16px;
            letter-spacing: -0.5px;
        }

        .modal-mestre-card p {
            font-size: 0.98rem;
            line-height: 1.6;
            color: var(--cor-texto-suave);
            margin-bottom: 32px;
        }

        .btn-modal-mestre {
            width: 100%;
            background: linear-gradient(135deg, var(--cor-acento), var(--cor-acento-vivo));
            color: var(--cor-branco);
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(108, 63, 212, 0.4);
        }

        .btn-modal-mestre:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
            box-shadow: 0 6px 20px rgba(108, 63, 212, 0.55);
        }

        .btn-modal-mestre:active {
            transform: translateY(0);
        }
    </style>
</head>

<body class="pagina-inicial">

    <header>
        <div class="logotipo">
            <a href="index.php"><img src="../img/logo_horizontal.png" alt="Logo TABLE"></a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="cm-jogar.php">Como Jogar</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a></li>
                <li><a href="criar-mapa.php">Mundos</a></li>
                <li><a href="rolagem-de-dados.php">Dados</a></li>
                <li><a href="sobre-nos.php">Sobre Nós</a></li>
            </ul>
        </nav>
        <?php if (isset($_SESSION['usuario'])): ?>
            <div class="usuario-logado-nav" id="nav-logado" onclick="window.location.href='perfil.php'" title="Ir para o Perfil">
                <img src="<?= htmlspecialchars($fotoNavbar) ?>" alt="Avatar Navbar" class="avatar-nav">
                <span class="nome-nav"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
            </div>
        <?php else: ?>
            <div class="botoes-navegacao" id="nav-deslogado">
                <a href="login.php" class="botao-entrar">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="cadastro.php" class="botao-cadastrar">
                    <i class="fas fa-user-plus"></i> Cadastre-se
                </a>
            </div>
        <?php endif; ?>
    </header>

    <section class="secao-destaque">

        <div class="planos-titulo">
            <h1>Planos da TABLE</h1>
            <p>Transforme suas ideias em projetos incríveis com uma plataforma completa para criação de mapas, sistemas e automações.
               Escolha o plano ideal para você, evolua seus projetos com ferramentas avançadas e tenha acesso a recursos exclusivos feitos
               para criadores que querem ir além.</p>
        </div>
        
        <!-- NAVEGAÇÃO DE ABAS GLASSMORPHIC -->
        <div class="planos-abas-navegacao">
            <button type="button" class="btn-aba-planos ativa" onclick="switchPlanosTab('planos', this)">Planos</button>
            <button type="button" class="btn-aba-planos" onclick="switchPlanosTab('chaves', this)">Minhas Chaves</button>
            <div class="planos-aba-indicador" id="aba-indicador"></div>
        </div>

        <!-- CONTEÚDO DA ABA 1: PLANOS -->
        <div id="painel-planos" class="aba-planos-content" style="display: block;">
            <div class="planos-grid">

            <!-- GRATUITO -->
            <div class="plano-card">
                <div class="plano-card-header">
                    <h3>GRATUITO</h3>
                    <span class="plano-icone"><i class="fas fa-gift"></i></span>
                </div>
                <div class="plano-descricao">
                    <div class="barra"></div>
                    <p>Comece grátis e teste os recursos básicos da plataforma.</p>
                </div>
                <div class="plano-preco">
                    <?php if ($jaMestre): ?>
                        <button type="button" class="btn-gratis" style="background: #2ecc71; color: #fff; cursor: default;" disabled>
                            Adquirido!
                        </button>
                    <?php else: ?>
                        <button type="button" onclick="abrirModalSerMestre()" class="btn-gratis">
                            Grátis
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PLANO DE MAPAS -->
            <div class="plano-card">
                <div class="plano-card-header">
                    <h3>Plano de<br>Mapas</h3>
                    <span class="plano-icone"><i class="fas fa-map"></i></span>
                </div>
                <div class="plano-descricao">
                    <div class="barra"></div>
                    <p>Crie mapas profissionais com ferramentas avançadas.</p>
                </div>
                <div class="plano-preco">
                    <?php if ($possuiMapas || $possuiCompleto): ?>
                        <button type="button" class="btn-preco" style="background: #2ecc71; border-color: #2ecc71; color: #fff; cursor: default;" disabled>Adquirido!</button>
                    <?php else: ?>
                        <button type="button" onclick="iniciarCheckout('mapas')" class="btn-preco">R$19,90/mês</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PLANO DE SISTEMAS -->
            <div class="plano-card">
                <div class="plano-card-header">
                    <h3>Plano de<br>Sistemas</h3>
                    <span class="plano-icone"><i class="fas fa-microchip"></i></span>
                </div>
                <div class="plano-descricao">
                    <div class="barra"></div>
                    <p>Desenvolva sistemas completos sem limites.</p>
                </div>
                <div class="plano-preco">
                    <?php if ($possuiSistemas || $possuiCompleto): ?>
                        <button type="button" class="btn-preco" style="background: #2ecc71; border-color: #2ecc71; color: #fff; cursor: default;" disabled>Adquirido!</button>
                    <?php else: ?>
                        <button type="button" onclick="iniciarCheckout('sistemas')" class="btn-preco">R$29,90/mês</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PLANO COMPLETO -->
            <div class="plano-card">
                <div class="plano-card-header">
                    <h3>Plano<br>Completo</h3>
                    <span class="plano-icone"><i class="fas fa-crown"></i></span>
                </div>
                <div class="plano-descricao">
                    <div class="barra"></div>
                    <p>Tudo desbloqueado em um só plano.</p>
                </div>
                <div class="plano-preco">
                    <?php if ($possuiCompleto): ?>
                        <button type="button" class="btn-preco" style="background: #2ecc71; border-color: #2ecc71; color: #fff; cursor: default;" disabled>Adquirido!</button>
                    <?php else: ?>
                        <button type="button" onclick="iniciarCheckout('completo')" class="btn-preco">R$49,90/mês</button>
                    <?php endif; ?>
                </div>
            </div>

            </div>
        </div>

        <!-- CONTEÚDO DA ABA 2: MINHAS CHAVES -->
        <div id="painel-chaves" class="aba-planos-content" style="display: none; max-width: 960px; margin: 0 auto; width: 100%; position: relative; z-index: 2;">
            <?php
            $chavesUsuario = [];
            if (isset($_SESSION['usuario'])) {
                try {
                    $pdo = Database::getConexao();
                    $stmtCh = $pdo->prepare("
                        SELECT * FROM tb_chave_ativacao 
                        WHERE id_usuario_comprador = ? OR id_usuario_ativador = ?
                        ORDER BY dt_criacao DESC
                    ");
                    $stmtCh->execute([$_SESSION['usuario']['id'], $_SESSION['usuario']['id']]);
                    $chavesUsuario = $stmtCh->fetchAll();
                } catch (Exception $e) {}
            }
            ?>
            <div class="chaves-usuario-painel" style="margin-top: 20px;">
                <?php if (!isset($_SESSION['usuario'])): ?>
                    <p style="text-align: center; color: var(--cor-texto-suave); padding: 40px; background: rgba(19, 16, 34, 0.6); border: 1px solid var(--cor-borda-card); border-radius: 16px;">
                        Você precisa estar logado para visualizar suas chaves de ativação. <a href="login.php" style="color: var(--cor-acento-vivo); font-weight: 700; text-decoration: underline;">Fazer Login</a>
                    </p>
                <?php elseif (empty($chavesUsuario)): ?>
                    <p style="text-align: center; color: var(--cor-texto-suave); padding: 40px; background: rgba(19, 16, 34, 0.6); border: 1px solid var(--cor-borda-card); border-radius: 16px;">
                        Você ainda não possui chaves adquiridas. Escolha um plano acima para comprar e gerar sua primeira chave!
                    </p>
                <?php else: ?>
                    <div class="chaves-lista-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                        <?php foreach ($chavesUsuario as $ch): 
                            $usada = (int)$ch['fl_usada'] === 1;
                            $nomes_planos = [
                                'mapas' => 'Plano de Mapas',
                                'sistemas' => 'Plano de Sistemas',
                                'completo' => 'Plano Completo'
                            ];
                            $cores_planos = [
                                'mapas' => '#2ecc71',
                                'sistemas' => '#3498db',
                                'completo' => '#e67e22'
                            ];
                            $cor_plano = $cores_planos[$ch['tp_plano']] ?? '#9b59b6';
                            $nome_plano = $nomes_planos[$ch['tp_plano']] ?? 'Premium';
                        ?>
                            <div class="chave-usuario-card" style="background: rgba(19, 16, 34, 0.8); border: 1px solid <?= $usada ? 'rgba(255,255,255,0.05)' : 'var(--cor-borda-card)' ?>; border-radius: 16px; padding: 20px; display: flex; flex-direction: column; gap: 15px; position: relative; transition: all 0.3s; opacity: <?= $usada ? 0.75 : 1 ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 0.75rem; font-weight: 800; background: <?= $cor_plano ?>15; color: <?= $cor_plano ?>; border: 1px solid <?= $cor_plano ?>30; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;"><?= $nome_plano ?></span>
                                    <span style="font-size: 0.75rem; font-weight: 800; color: <?= $usada ? '#aaa' : '#2ecc71' ?>; display: flex; align-items: center; gap: 6px;">
                                        <i class="<?= $usada ? 'fas fa-check-circle' : 'fas fa-clock' ?>"></i>
                                        <?= $usada ? 'Resgatado' : 'Pendente' ?>
                                    </span>
                                </div>
                                
                                <div class="chave-codigo-display" style="background: rgba(0,0,0,0.3); padding: 12px 15px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(255,255,255,0.05);">
                                    <span class="codigo-texto" data-codigo="<?= htmlspecialchars($ch['ds_codigo']) ?>" style="font-family: monospace; font-size: 1.05rem; letter-spacing: 1.5px; color: var(--cor-branco);">••••-••••-••••-••••</span>
                                    <div style="display: flex; gap: 10px;">
                                        <button type="button" onclick="toggleChaveVisibilidade(this)" class="btn-olhinho" style="background: transparent; border: none; color: var(--cor-texto-suave); cursor: pointer; font-size: 1rem; transition: color 0.2s;" title="Mostrar/Esconder Chave">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                        <button type="button" onclick="copiarChaveCodigo('<?= htmlspecialchars($ch['ds_codigo']) ?>', this)" class="btn-copiar-ch" style="background: transparent; border: none; color: var(--cor-texto-suave); cursor: pointer; font-size: 1rem; transition: color 0.2s;" title="Copiar Chave">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                                    <span style="font-size: 0.75rem; color: var(--cor-texto-suave);">Adquirido: <?= date('d/m/Y', strtotime($ch['dt_criacao'])) ?></span>
                                    <?php if (!$usada): ?>
                                        <button type="button" onclick="resgatarChaveRapido('<?= htmlspecialchars($ch['ds_codigo']) ?>', this)" class="btn-resgate-ch" style="background: linear-gradient(135deg, var(--cor-acento), var(--cor-acento-vivo)); border: none; color: var(--cor-branco); font-size: 0.8rem; font-weight: 700; padding: 8px 16px; border-radius: 30px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 10px rgba(108,63,212,0.2);">Resgatar</button>
                                    <?php else: ?>
                                        <span style="font-size: 0.72rem; color: var(--cor-texto-suave); font-style: italic;">Ativado: <?= date('d/m/Y', strtotime($ch['dt_ativacao'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- NOVA SEÇÃO: CHAVES DE ATIVAÇÃO -->
        <div class="secao-chaves">
            <div class="chaves-container">
                <div class="chaves-info">
                    <h2><i class="fas fa-key"></i> Como funcionam as chaves?</h2>
                    <p>As Chaves de Ativação da <strong>TABLE</strong> são códigos especiais gerados e disponibilizados imediatamente após a aquisição de um de nossos planos pagos. Elas servem exclusivamente para habilitar o plano adquirido na sua conta.</p>
                    <div class="lista-passos">
                        <div class="passo-item">
                            <span class="passo-num">1</span>
                            <div>
                                <h4>Adquira um Plano</h4>
                                <p>Escolha um plano de sua preferência acima e finalize a aquisição para gerar sua chave de ativação única.</p>
                            </div>
                        </div>
                        <div class="passo-item">
                            <span class="passo-num">2</span>
                            <div>
                                <h4>Receba e Insira</h4>
                                <p>Com a chave de ativação gerada em mãos, basta digitá-la no campo ao lado.</p>
                            </div>
                        </div>
                        <div class="passo-item">
                            <span class="passo-num">3</span>
                            <div>
                                <h4>Ative na Hora</h4>
                                <p>Após a confirmação da chave, o seu plano premium correspondente é ativado instantaneamente!</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="chaves-ativacao">
                    <h3>Resgatar Chave</h3>
                    <p>Desbloqueie ferramentas premium adicionando uma chave de ativação válida abaixo:</p>
                    <div class="form-ativacao">
                        <div class="input-glow-container">
                            <input type="text" id="chave-input" placeholder="TBL-XXXX-XXXX-XXXX" maxlength="19">
                            <i class="fas fa-ticket-alt input-icon"></i>
                        </div>
                        <button type="button" id="btn-ativar-chave" class="btn-ativar">
                            <i class="fas fa-magic"></i> Ativar Chave
                        </button>
                    </div>
                    <div id="chave-feedback" class="chave-feedback-msg"></div>
                </div>
            </div>
        </div>

    </section>

    <footer class="rodape-principal">
        <div class="rodape-conteudo">
            <div class="rodape-logo-area">
                <div class="rodape-marca">
                    <img src="../img/logo_branco.png" alt="Logo TABLE">
                    <span>TABLE</span>
                </div>
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

    <!-- MODAL AGORA VOCÊ É UM MESTRE -->
    <div id="modal-sucesso-mestre" class="modal-mestre-overlay" style="display:none;">
        <div class="modal-mestre-card">
            <div class="modal-mestre-icon">
                <i class="fas fa-crown"></i>
            </div>
            <h2>Agora você é um Mestre!</h2>
            <p>Parabéns! Sua conta foi atualizada com sucesso. </p>
            <p>Agora você tem acesso ilimitado à criação de campanhas, sistemas de regras personalizados e mundos incríveis na TABLE!</p>
            <button type="button" class="btn-modal-mestre" id="btn-confirmar-mestre">
                <i class="fas fa-user-shield"></i> Ir para o Perfil
            </button>
        </div>
    </div>

    <script src="../js/script.js" defer></script>
    <script src="../js/nav-global.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const keyInput = document.getElementById('chave-input');
            const btnAct = document.getElementById('btn-ativar-chave');
            const feedback = document.getElementById('chave-feedback');

            // Lógica do Mercado Pago Checkout
            window.iniciarCheckout = function(planoId) {
                const usuarioLogado = <?php echo isset($_SESSION['usuario']) ? 'true' : 'false'; ?>;
                if (!usuarioLogado) {
                    window.location.href = 'login.php';
                    return;
                }

                // Identificar o botão
                let btn = null;
                const buttons = document.querySelectorAll('.btn-preco');
                buttons.forEach(b => {
                    if (b.getAttribute('onclick') && b.getAttribute('onclick').includes(planoId)) {
                        btn = b;
                    }
                });

                if (!btn) return;
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';

                // AJAX
                fetch('planos.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'criar_preferencia_mp=1&plano_id=' + encodeURIComponent(planoId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso) {
                        window.location.href = data.checkout_url;
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        alert(data.mensagem || 'Ocorreu um erro ao iniciar o checkout.');
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    console.error('Error:', error);
                    alert('Erro de conexão ao processar checkout.');
                });
            };

            // Lógica de promoção para Mestre ao clicar no plano grátis
            window.abrirModalSerMestre = function() {
                const usuarioLogado = <?php echo isset($_SESSION['usuario']) ? 'true' : 'false'; ?>;
                if (!usuarioLogado) {
                    window.location.href = 'login.php';
                    return;
                }

                const btnGratis = document.querySelector('.btn-gratis');
                const originalText = btnGratis.innerHTML;
                btnGratis.disabled = true;
                btnGratis.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';

                fetch('planos.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'virar_mestre_plano=1'
                })
                .then(response => response.json())
                .then(data => {
                    btnGratis.disabled = false;
                    btnGratis.innerHTML = originalText;

                    if (data.sucesso) {
                        document.getElementById('modal-sucesso-mestre').style.display = 'flex';
                    } else {
                        alert(data.mensagem || 'Ocorreu um erro ao promover para Mestre.');
                    }
                })
                .catch(error => {
                    btnGratis.disabled = false;
                    btnGratis.innerHTML = originalText;
                    console.error('Error:', error);
                    alert('Erro de conexão ao promover usuário.');
                });
            };

            const btnConfirmarMestre = document.getElementById('btn-confirmar-mestre');
            if (btnConfirmarMestre) {
                btnConfirmarMestre.addEventListener('click', function() {
                    window.location.href = 'perfil.php';
                });
            }

            if (keyInput) {
                keyInput.addEventListener('input', function(e) {
                    let val = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                    if (val.startsWith('TBL')) {
                        let rest = val.substring(3);
                        let formatted = 'TBL';
                        if (rest.length > 0) {
                            formatted += '-' + rest.substring(0, 4);
                        }
                        if (rest.length > 4) {
                            formatted += '-' + rest.substring(4, 8);
                        }
                        if (rest.length > 8) {
                            formatted += '-' + rest.substring(8, 12);
                        }
                        e.target.value = formatted.substring(0, 19);
                    } else {
                        if (val.length > 0) {
                            let formatted = 'TBL';
                            formatted += '-' + val.substring(0, 4);
                            if (val.length > 4) {
                                formatted += '-' + val.substring(4, 8);
                            }
                            if (val.length > 8) {
                                formatted += '-' + val.substring(8, 12);
                            }
                            e.target.value = formatted.substring(0, 19);
                        } else {
                            e.target.value = '';
                        }
                    }
                });

                btnAct.addEventListener('click', function() {
                    const val = keyInput.value.trim();
                    feedback.style.display = 'none';
                    feedback.className = 'chave-feedback-msg';

                    if (!val) {
                        feedback.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Por favor, insira uma chave de ativação.';
                        feedback.classList.add('feedback-erro');
                        feedback.style.display = 'flex';
                        return;
                    }

                    const usuarioLogado = <?php echo isset($_SESSION['usuario']) ? 'true' : 'false'; ?>;
                    if (!usuarioLogado) {
                        feedback.innerHTML = '<i class="fas fa-user-slash"></i> Você precisa estar logado para ativar uma chave! <a href="login.php" style="margin-left: 5px;">Fazer Login</a>';
                        feedback.classList.add('feedback-erro');
                        feedback.style.display = 'flex';
                        return;
                    }

                    btnAct.disabled = true;
                    btnAct.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Validando...';

                    // AJAX dinâmico para resgatar
                    fetch('planos.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'resgatar_chave_ajax=1&chave=' + encodeURIComponent(val)
                    })
                    .then(response => response.json())
                    .then(data => {
                        btnAct.disabled = false;
                        btnAct.innerHTML = '<i class="fas fa-magic"></i> Ativar Chave';
                        
                        if (data.sucesso) {
                            feedback.innerHTML = `<i class="fas fa-check-circle"></i> <span>Chave ativada com sucesso! O <strong>${data.plano}</strong> foi desbloqueado na sua conta.</span>`;
                            feedback.classList.add('feedback-sucesso');
                            feedback.style.display = 'flex';
                            keyInput.value = '';
                            setTimeout(() => { window.location.reload(); }, 2000);
                        } else {
                            feedback.innerHTML = '<i class="fas fa-times-circle"></i> ' + data.mensagem;
                            feedback.classList.add('feedback-erro');
                            feedback.style.display = 'flex';
                        }
                    })
                    .catch(err => {
                        btnAct.disabled = false;
                        btnAct.innerHTML = '<i class="fas fa-magic"></i> Ativar Chave';
                        feedback.innerHTML = '<i class="fas fa-times-circle"></i> Erro de conexão ao validar chave.';
                        feedback.classList.add('feedback-erro');
                        feedback.style.display = 'flex';
                    });
                });
            }
        });
    </script>
    <!-- MODAL PREMIUM CHAVE COMPRADA -->
    <?php if (!empty($chaveGerada)): ?>
    <div id="modal-chave-comprada" class="modal-mestre-overlay" style="display:flex;">
        <div class="modal-mestre-card" style="border-color: #2ecc71; box-shadow: 0 20px 50px rgba(46, 204, 113, 0.3);">
            <div class="modal-mestre-icon" style="color: #2ecc71; border-color: #2ecc71; background: rgba(46, 204, 113, 0.15);">
                <i class="fas fa-key"></i>
            </div>
            <h2>Assinatura Criada!</h2>
            <p>Parabéns! O seu **<?= $planoAssinadoNome ?>** foi processado. Geramos a sua chave de ativação única abaixo. Você pode copiá-la para dar de presente, ou clicar em Ativar para resgatá-la instantaneamente na sua conta!</p>
            
            <div style="background: rgba(0,0,0,0.35); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08); font-family: monospace; font-size: 1.3rem; letter-spacing: 2px; color: #fff; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: inset 0 0 15px rgba(0,0,0,0.5);">
                <span id="txt-chave-comprada"><?= htmlspecialchars($chaveGerada) ?></span>
                <button type="button" onclick="copiarChaveCodigo('<?= htmlspecialchars($chaveGerada) ?>', this)" style="background: transparent; border: none; color: #2ecc71; cursor: pointer; font-size: 1.3rem; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            
            <button type="button" class="btn-modal-mestre" style="background: linear-gradient(135deg, #2ecc71, #27ae60); box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);" onclick="fecharModalChaveComprada('<?= htmlspecialchars($chaveGerada) ?>')">
                <i class="fas fa-magic"></i> Ativar na Minha Conta
            </button>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Funções para a aba Minhas Chaves
        function switchPlanosTab(tabId, btn) {
            document.querySelectorAll('.aba-planos-content').forEach(el => {
                el.style.display = 'none';
            });
            document.querySelectorAll('.btn-aba-planos').forEach(b => {
                b.classList.remove('ativa');
            });
            
            const target = document.getElementById('painel-' + tabId);
            if (target) target.style.display = 'block';
            if (btn) btn.classList.add('ativa');
            
            atualizarIndicadorAba(btn);
        }

        function atualizarIndicadorAba(btnAtivo) {
            const indicador = document.getElementById('aba-indicador');
            if (indicador && btnAtivo) {
                indicador.style.left = btnAtivo.offsetLeft + 'px';
                indicador.style.width = btnAtivo.offsetWidth + 'px';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const btnAtivo = document.querySelector('.btn-aba-planos.ativa');
            if (btnAtivo) {
                setTimeout(() => {
                    atualizarIndicadorAba(btnAtivo);
                }, 150);
            }
            window.addEventListener('resize', function() {
                const btnActive = document.querySelector('.btn-aba-planos.ativa');
                if (btnActive) {
                    atualizarIndicadorAba(btnActive);
                }
            });
        });
        
        function toggleChaveVisibilidade(btn) {
            const display = btn.closest('.chave-codigo-display');
            const texto = display.querySelector('.codigo-texto');
            const icon = btn.querySelector('i');
            
            if (icon.classList.contains('fa-eye-slash')) {
                texto.textContent = texto.getAttribute('data-codigo');
                icon.className = 'fas fa-eye';
                btn.style.color = 'var(--cor-acento-vivo)';
            } else {
                texto.textContent = '••••-••••-••••-••••';
                icon.className = 'fas fa-eye-slash';
                btn.style.color = 'var(--cor-texto-suave)';
            }
        }
        
        function copiarChaveCodigo(codigo, btn) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(codigo);
            } else {
                const temp = document.createElement('input');
                temp.value = codigo;
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
            }
            
            const icon = btn.querySelector('i');
            const origClass = icon.className;
            icon.className = 'fas fa-check';
            btn.style.color = '#2ecc71';
            
            setTimeout(() => {
                icon.className = origClass;
                btn.style.color = 'var(--cor-texto-suave)';
            }, 1500);
        }
        
        function resgatarChaveRapido(codigo, btn) {
            const origText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('planos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'resgatar_chave_ajax=1&chave=' + encodeURIComponent(codigo)
            })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    btn.innerHTML = '<i class="fas fa-check"></i> OK';
                    btn.style.background = '#2ecc71';
                    alert(data.mensagem);
                    setTimeout(() => { window.location.reload(); }, 1500);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = origText;
                    alert(data.mensagem);
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = origText;
                alert('Erro de conexão ao ativar.');
            });
        }
        
        function fecharModalChaveComprada(codigo) {
            const modal = document.getElementById('modal-chave-comprada');
            if (modal) {
                modal.style.display = 'none';
            }
            
            // Ativa automaticamente
            fetch('planos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'resgatar_chave_ajax=1&chave=' + encodeURIComponent(codigo)
            })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    alert('Chave ativada com sucesso na sua conta!');
                }
                window.location.href = 'planos.php';
            })
            .catch(() => {
                window.location.href = 'planos.php';
            });
        }
    </script>
</body>

</html>

