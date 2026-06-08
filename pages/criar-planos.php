<?php
/**
 * ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
 * SCRIPT DE CRIAÇÃO AUTOMÁTICA DE PLANOS DE ASSINATURA RECORRENTE - MERCADO PAGO
 * ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
 * Este script cria os 3 planos recorrentes (Mapas, Sistemas e Completo) no MP
 * e aplica a migration no banco de dados automaticamente.
 * ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
 */

require_once __DIR__ . '/app/config/database.php';

// ============================================================
// CONFIGURAÇÃO: Insira seu Access Token do Mercado Pago abaixo
// ============================================================
$access_token = 'TEST-2950252907685269-052820-5638955fb1ef3463c2366736171cab4d-66270410'; 
// ============================================================

$base_url = 'https://api.mercadopago.com';

echo "=====================================================================\n";
echo "    BEM-VINDO AO INSTALADOR DE ASSINATURAS DO MERCADO PAGO - TABLE   \n";
echo "=====================================================================\n\n";

// 1. Executar a alteração de banco de dados (Adiciona mp_assinatura_id)
try {
    $pdo = Database::getConexao();
    echo "[BANCO] Verificando estrutura de banco de dados...\n";
    $chkCol = $pdo->query("SHOW COLUMNS FROM tb_usuario LIKE 'mp_assinatura_id'");
    if ($chkCol->rowCount() === 0) {
        echo "[BANCO] Adicionando coluna 'mp_assinatura_id' na tabela tb_usuario...\n";
        $pdo->exec("ALTER TABLE tb_usuario ADD COLUMN mp_assinatura_id VARCHAR(100) NULL");
        echo "[BANCO] Coluna adicionada com sucesso!\n\n";
    } else {
        echo "[BANCO] A coluna 'mp_assinatura_id' já existe no banco. Tudo pronto!\n\n";
    }
} catch (Exception $e) {
    echo "[ERRO BANCO] Falha ao verificar/alterar o banco: " . $e->getMessage() . "\n\n";
}

// 2. Verificar se o Access Token foi inserido
if ($access_token === 'APP_USR-SEU-ACCESS-TOKEN-AQUI' || empty($access_token)) {
    echo "[AVISO CRÍTICO] Substitua o Access Token no topo deste arquivo para criar os planos reais no Mercado Pago!\n";
    echo "Saindo...\n";
    exit;
}

// 3. Criar os planos recorrentes no Mercado Pago
$planos_a_criar = [
    'mapas' => [
        'reason' => 'TABLE - Plano de Mapas',
        'preco' => 19.90,
        'desc' => 'Acesso premium a ferramentas avançadas de criação de mapas'
    ],
    'sistemas' => [
        'reason' => 'TABLE - Plano de Sistemas',
        'preco' => 29.90,
        'desc' => 'Desenvolva sistemas completos e gerencie sem limites'
    ],
    'completo' => [
        'reason' => 'TABLE - Plano Completo',
        'preco' => 49.90,
        'desc' => 'Tudo desbloqueado: mapas avançados, sistemas ilimitados e prioridade'
    ]
];

// Configura a URL de retorno baseada no localhost padrão ou no host de desenvolvimento
$http_host = 'localhost';
$back_url = "https://www.google.com.br";

echo "[API] Criando planos recorrentes no painel do Mercado Pago...\n";

$resultados = [];

foreach ($planos_a_criar as $chave => $plano) {
    echo "\n-> Criando '{$plano['reason']}' (R$ " . number_format($plano['preco'], 2, ',', '.') . "/mês)...\n";
    
    $body = [
        'reason' => $plano['reason'],
        'auto_recurring' => [
            'frequency' => 1,
            'frequency_type' => 'months',
            'transaction_amount' => (float)$plano['preco'],
            'currency_id' => 'BRL'
        ],
        'back_url' => $back_url
    ];
    
    $ch = curl_init($base_url . '/preapproval_plan');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = json_decode(curl_exec($ch), true);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 || $http_code === 201) {
        $resultados[$chave] = $response['id'];
        echo "[SUCESSO] Plano criado! ID: " . $response['id'] . "\n";
    } else {
        echo "[ERRO] Falha ao criar o plano. Status HTTP: $http_code\n";
        echo "[DETALHES] Mensagem: " . ($response['message'] ?? 'Desconhecida') . "\n";
    }
}

echo "\n=====================================================================\n";
echo "                        INSTALAÇÃO CONCLUÍDA!                        \n";
echo "=====================================================================\n";
if (count($resultados) === 3) {
    echo "Copie e cole estes IDs nas constantes correspondentes no topo do seu planos.php:\n\n";
    echo "define('MP_PLANO_MAPAS_ID',    '{$resultados['mapas']}');\n";
    echo "define('MP_PLANO_SISTEMAS_ID', '{$resultados['sistemas']}');\n";
    echo "define('MP_PLANO_COMPLETO_ID', '{$resultados['completo']}');\n\n";
} else {
    echo "Alguns planos não puderam ser criados automaticamente. Verifique as mensagens de erro acima.\n";
}
echo "=====================================================================\n";
