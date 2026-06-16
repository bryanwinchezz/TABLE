<?php
/**
 * AJAX Endpoint - CassIA (Criador Inteligente com Gemini)
 */
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

// 1. Obter parâmetros da requisição (JSON ou POST)
$requestData = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$tipo = $requestData['tipo'] ?? ''; // 'personagem' ou 'sistema'
$promptUsuario = $requestData['prompt'] ?? '';
$sistemaNome = $requestData['sistema_nome'] ?? ''; // Contexto opcional do sistema selecionado
$sistemaAtributos = $requestData['sistema_atributos'] ?? []; // Atributos do sistema no personagem

if (empty($tipo) || empty($promptUsuario)) {
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos. Informe o tipo e o prompt.']);
    exit;
}

// 2. Carregar configuração do Gemini a partir do banco de dados (Qualidade Absoluta)
require_once __DIR__ . '/../config/database.php';
$apiKey = '';
try {
    $pdo = Database::getConexao();
    $stmtKey = $pdo->prepare("SELECT ds_api_key_gemini FROM tb_usuario WHERE id_usuario = ? LIMIT 1");
    $stmtKey->execute([$_SESSION['usuario']['id']]);
    $apiKey = $stmtKey->fetchColumn() ?: '';
} catch (Exception $dbE) {
    // Silencioso
}

// Fallback ou validação da chave
if (empty($apiKey)) {
    // Para fins de demonstração/teste caso não haja chave, podemos mockar uma resposta realista e criativa 
    // ou retornar um erro explicativo. Vamos dar suporte para mock se a chave estiver vazia, facilitando homologação imediata!
    // Mas se o usuário colocar uma chave válida, ela chamará a API real.
    $usandoMock = true;
} else {
    $usandoMock = false;
}

if ($usandoMock) {
    // Gerar uma resposta simulada premium baseada no prompt do usuário
    $respostaMock = gerarRespostaMock($tipo, $promptUsuario, $sistemaNome, $sistemaAtributos);
    echo json_encode(['success' => true, 'data' => $respostaMock, 'mock' => true]);
    exit;
}

// 3. Fazer chamada real para o Gemini
$model = 'gemini-2.5-flash';
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

// Construir prompt do sistema (System Instruction)
if ($tipo === 'personagem') {
    $sysInstruction = "Você é a CassIA, uma inteligência artificial assistente e Game Designer de RPG veterana e renomada. O usuário deseja criar um PERSONAGEM de RPG extremamente imersivo, criativo e coeso com base no tema, prompt e contexto fornecidos.
Fuja de termos e nomes clichês de heróis genéricos. Escolha nomes únicos, com sonoridade marcante e que contem uma história por si só.
Sua resposta deve ser EXCLUSIVAMENTE um objeto JSON válido, sem markdown ou delimitadores de código adicionais (como ```json).
O JSON deve seguir rigorosamente esta estrutura:
{
  \"nome\": \"Nome completo criativo e marcante do Personagem (ex: Vladis, o Sombrio)\",
  \"aparencia\": \"Descrição física imersiva, rica em detalhes estéticos do cenário (gênero, idade, vestimentas temáticas, cicatrizes ou detalhes marcantes)\",
  \"personalidade\": \"Traços psicológicos profundos, virtudes, falhas trágicas, segredos ou manias que guiam a interpretação\",
  \"historia\": \"História de origem rica e envolvente que justifique seu passado e como ele se inseriu no universo (máximo 4 parágrafos)\",
  \"objetivos\": \"Desejos imediatos e metas de longo prazo do personagem\",
  \"atributos\": {
     \"NOME_ATRIBUTO\": VALOR, ... (apenas se atributos do sistema forem informados no contexto, distribua 10 pontos adicionais além da base 0 para cada atributo de forma estratégica de acordo com o conceito do herói)
  },
  \"classe_sugerida\": \"Nome da classe sugerida (crie ou use um nome temático fantástico e imersivo no sistema)\",
  \"origem_sugerida\": \"Nome da origem/antecedente sugerida (deve exalar a atmosfera do universo)\"
}";
    if (!empty($sistemaNome)) {
        $sysInstruction .= " O sistema de RPG atual é: '{$sistemaNome}'. Os atributos do sistema são: " . json_encode($sistemaAtributos) . ". Tente sugerir dados, mecânicas e nomes de classes/origens condizentes com este universo específico.";
    }
} else {
    // Sistema
    $sysInstruction = "Imagine que você é o mestre de RPG e designer de jogos mais criativo, natural e foda que existe, com alma de jogador raiz. Quero que você crie um sistema de RPG completo, imersivo e totalmente original baseado no conceito enviado abaixo. 
Por favor, escreva de forma orgânica, viva e envolvente. Evite falar como uma máquina ou IA fria; adote o tom da lore em tudo, desde as descrições conceituais até as mecânicas. Escreva como se estivesse apresentando um livro de RPG de luxo para o seu grupo de amigos!

Conceito do Sistema: \"{$promptUsuario}\"

Diretrizes de Ouro para RPG de Mesa:
1. Tópicos de Descrição Temáticos: NÃO use descrições clichês ou genéricas como 'Descrição Geral' ou 'Regras'. Crie títulos dinâmicos, poéticos e imersivos que descrevam a lore e a alma do cenário (Ex: se for um cenário de Programadores Dev, use títulos como 'A Compilação Maldita', 'A Rebelião dos Scripts', 'O Limbo do StackOverflow'. Se for um cenário medieval, use 'As Cinzas do Trono', 'Os Sussurros do Bosque', etc.).
2. Atributos Vivos e Únicos: Esqueça os atributos clássicos de sempre a menos que o tema exija. Se for um RPG sobre Programadores, use atributos como 'Lógica (LOG)', 'Gambiarra (GAM)', 'Café (CAF)', 'Foco (FOC)'. Crie siglas de 3 letras em maiúsculo exatas e condizentes.
3. Atributo Base para Status e Defesas:
   - Defina OBRIGATORIAMENTE um atributo base para cada status e defesa. No campo 'base' de cada Status e Defesa, informe EXCLUSIVAMENTE a sigla de 3 letras de um dos Atributos criados por você (Ex: 'LOG', 'FOC'). Não insira fórmulas inteiras no campo 'base'!
   - No campo 'val2' de classes, preencha os modificadores e bônus reais de RPG (Ex: 'BÔNUS: +2 no Atributo X, +1 no Atributo Y. Vida Inicial: 12 + Atributo. Habilidade Inicial: Nome (efeito)').
   - No campo 'val2' de perícias, coloque o bônus na mesa (Ex: 'BÔNUS: Concede bônus de +2 em testes envolvendo esta perícia').
   - No campo 'val2' de origens, coloque as vantagens de lore com números (Ex: 'BENEFÍCIO: Concede +2 na Perícia X e +1 na Defesa Y').
   - No campo 'val2' de equipamentos, coloque as propriedades mecânicas completas (Ex: 'arma (Dano: 2d6+LOG, peso 1)').
   - No campo 'val1' de poderes, coloque a mecânica detalhada com valores numéricos (Ex: 'Custo: 2 PE. Soma +2 em testes de Gambiarra por 3 turnos') e no campo 'val2' use apenas 'ativa' ou 'passiva'.
   - No campo 'desc' de monstros, detalhe o perigo com estatísticas completas e reais de combate (Ex: 'Lore da criatura. ATAQUES: Garras +4 para acertar (dano 1d6+3). Habilidades especiais numéricas').
4. Equilíbrio de Cores: Utilize cores hexadecimais ricas e temáticas (ex: roxos profundos, verdes radioativos, vermelhos sangue, etc.) para os campos 'cor' de status e defesas.
5. Componentes Ricos: Gere exatamente de 3 a 5 classes, de 4 a 8 perícias, de 3 a 6 origens, de 3 a 6 equipamentos, de 3 a 6 poderes e de 1 a 3 monstros para enriquecer o sistema.

Sua resposta deve ser EXCLUSIVAMENTE um objeto JSON válido, sem markdown ou delimitadores de código adicionais (como ```json).
O JSON deve seguir rigorosamente esta estrutura:
{
  \"nome\": \"Nome original, criativo e marcante do sistema de RPG\",
  \"classificacao\": \"L, 10, 12, 14, 16 ou 18\",
  \"descricoes\": [
     \"Título Temático do Mundo: Detalhes imersivos sobre o universo, o tom e a proposta do sistema.\",
     \"Título Temático das Regras: Explicação de como as rolagens e desafios ocorrem na mesa.\",
     \"Título Temático da Mecânica Única: Um diferencial de jogabilidade temático que traduz a atmosfera em regras.\"
  ],
  \"atributos\": [
     { \"nome\": \"Nome do Atributo Temático (Ex: Frieza, Lógica)\", \"abrev\": \"Sigla de 3 letras maiúsculas (Ex: FRI, LOG)\", \"valor\": \"0\" }
  ],
  \"status\": [
     { \"nome\": \"Nome do Status (Ex: Pontos de Vontade, Sanidade)\", \"cor\": \"Código Hexadecimal\", \"base\": \"Sigla de 3 letras do Atributo associado\" }
  ],
  \"defesas\": [
     { \"nome\": \"Nome da Defesa\", \"cor\": \"Código Hexadecimal\", \"base\": \"Sigla de 3 letras do Atributo associado\" }
  ],
  \"classes\": [
     { \"nome\": \"Nome Temático da Classe\", \"val1\": \"Papel e estilo de jogo da classe no cenário.\", \"val2\": \"Bônus e mecânicas: +2 no Atributo X e +1 no Atributo Y. Vida Inicial: 12 + Atributo. Habilidade Inicial: Nome (efeito).\" }
  ],
  \"pericias\": [
     { \"nome\": \"Nome da Perícia\", \"val1\": \"O que a perícia permite fazer no jogo.\", \"val2\": \"Mecânica: Concede +2 em testes envolvendo esta ação.\", \"val3\": \"Sigla de 3 letras do Atributo associado (deve estar na lista de atributos acima)\" }
  ],
  \"origens\": [
     { \"nome\": \"Nome da Origem/Passado\", \"val1\": \"O passado do personagem no universo.\", \"val2\": \"Bônus: Concede +2 na Perícia X e +1 na Defesa Y.\" }
  ],
  \"equipamentos\": [
     { \"nome\": \"Nome Criativo do Equipamento\", \"val1\": \"Descrição narrativa do item.\", \"val2\": \"Tipo e dados (Ex: arma (Dano: 1d8+FOR, peso 1))\" }
  ],
  \"poderes\": [
     { \"nome\": \"Nome da Habilidade Especial\", \"val1\": \"Descrição do efeito mecânico da habilidade com números e custo (Ex: Custo: 2 PE. Soma +2 em Defesa por 2 rodadas).\", \"val2\": \"ativa ou passiva\" }
  ],
  \"monstros\": [
     {
       \"nome\": \"Nome Criativo da Ameaça/Criatura\",
       \"val1\": \"Elemento/Família/Categoria da Ameaça\",
       \"val2\": 20,
       \"desc\": \"Lore e ações de combate com números (Ex: ATAQUES: Garras +4 para acertar (dano 1d6+3)).\",
       \"vida\": 40,
       \"defesa\": 12,
       \"xp\": 100,
       \"atributos_monstro\": [
          { \"abrev\": \"Sigla de 3 letras do Atributo\", \"valor\": 3 }
       ]
     }
  ]
}";
}

// Fazer chamada cURL
$ch = curl_init($url);
$payload = json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => "Prompt do usuário: {$promptUsuario}\nContexto: Sistema {$sistemaNome}. Atributos disponíveis: " . json_encode($sistemaAtributos)]
            ]
        ]
    ],
    'system_instruction' => [
        'parts' => [
            ['text' => $sysInstruction]
        ]
    ],
    'generation_config' => [
        'temperature' => 0.7,
        'responseMimeType' => 'application/json'
    ]
]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Permitir em localhost para evitar problemas com certificados localmente

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errorMsg = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['success' => false, 'error' => "Erro na requisição da IA: {$errorMsg}"]);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode(['success' => false, 'error' => "Erro do servidor Gemini (HTTP {$httpCode}): {$response}"]);
    exit;
}

$decodedResponse = json_decode($response, true);
$rawText = $decodedResponse['candidates'][0]['content']['parts'][0]['text'] ?? '';

if (!function_exists('normalizarEFormatacaoJson')) {
    function normalizarEFormatacaoJson($jsonStr) {
        // Escapar quebras de linha literais (novas linhas, retornos) e tabulações dentro de strings delimitadas por aspas duplas
        $jsonStr = preg_replace_callback(
            '/"([^"\\\\]*|\\\\.)*"/s',
            function ($matches) {
                return str_replace(
                    ["\n", "\r", "\t"],
                    ["\\n", "\\r", "\\t"],
                    $matches[0]
                );
            },
            $jsonStr
        );
        // Remover vírgulas flutuantes/órfãs que precedem o fechamento de colchetes ou chaves
        $jsonStr = preg_replace('/,\s*([\]}])/m', '$1', $jsonStr);
        return $jsonStr;
    }
}

$rawTextNormalizado = normalizarEFormatacaoJson($rawText);
$jsonData = null;

// Estratégia 0: tentar direto (com normalização)
$jsonData = json_decode(trim($rawTextNormalizado), true);

// Estratégia 1: remover blocos de markdown
if ($jsonData === null) {
    $cleanedText = preg_replace('/^```(?:json)?\s*/i', '', trim($rawTextNormalizado));
    $cleanedText = preg_replace('/\s*```\s*$/s', '', $cleanedText);
    $jsonData  = json_decode(trim($cleanedText), true);
}

// Estratégia 2: extrair o primeiro bloco JSON { ... } de nível raiz
if ($jsonData === null) {
    if (preg_match('/\{[\s\S]*\}/s', $rawTextNormalizado, $matches)) {
        $jsonData = json_decode($matches[0], true);
    }
}

// Estratégia 3: normalizar aspas curvas tipográficas
if ($jsonData === null) {
    $normalizado = str_replace(
        ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", "\u{201A}", "\u{201B}"],
        ['"',        '"',        "'",         "'",         "'",        "'"],
        $rawTextNormalizado
    );
    $jsonData = json_decode(trim($normalizado), true);
    if ($jsonData === null && preg_match('/\{[\s\S]*\}/s', $normalizado, $matches)) {
        $jsonData = json_decode($matches[0], true);
    }
}

// Estratégia 4: remover caracteres de controle invisíveis e BOM
if ($jsonData === null) {
    $semControle = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $rawTextNormalizado);
    $semControle = ltrim($semControle, "\xEF\xBB\xBF");
    $jsonData  = json_decode(trim($semControle), true);
    if ($jsonData === null && preg_match('/\{[\s\S]*\}/s', $semControle, $matches)) {
        $jsonData = json_decode($matches[0], true);
    }
}

// Fallback absoluto: tenta rodar nas strings originais não normalizadas
if ($jsonData === null) {
    $jsonData = json_decode(trim($rawText), true);
    if ($jsonData === null) {
        $cleanedText = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
        $cleanedText = preg_replace('/\s*```\s*$/s', '', $cleanedText);
        $jsonData  = json_decode(trim($cleanedText), true);
    }
    if ($jsonData === null) {
        if (preg_match('/\{[\s\S]*\}/s', $rawText, $matches)) {
            $jsonData = json_decode($matches[0], true);
        }
    }
}

if ($jsonData === null) {
    echo json_encode([
        'success' => false,
        'error'   => 'A resposta gerada pela IA não pôde ser decodificada como JSON.',
        'raw'     => $rawText
    ]);
    exit;
}

echo json_encode(['success' => true, 'data' => $jsonData]);
exit;


/**
 * Função Auxiliar: Gera uma resposta fictícia criativa caso não haja chave da API.
 */
function gerarRespostaMock($tipo, $prompt, $sistemaNome, $sistemaAtributos) {
    $promptLower = mb_strtolower($prompt);
    
    if ($tipo === 'personagem') {
        // Personagem Mock
        $nome = "Vladis, o Renegado";
        $aparencia = "Um homem de cabelos grisalhos curtos, olhos âmbar penetrantes e cicatrizes visíveis nas mãos. Veste roupas pretas utilitárias cobertas por um casaco de couro desgastado com capuz.";
        $personalidade = "Cínico, reservado e extremamente observador. Fala pouco, mas suas palavras carregam peso. Nutre um forte senso de justiça com quem confia, embora seja implacável com inimigos.";
        $historia = "Vladis era um soldado de elite de uma ordem esquecida até ser traído por seu comandante por se recusar a executar camponeses inocentes. Ele sobreviveu ao massacre da sua unidade e desde então vaga pelas sombras, buscando expiação e vingança.";
        $objetivos = "Destruir a facção corrompida que aniquilou sua ordem e resgatar o grimório antigo roubado.";
        
        // Atributos dinâmicos
        $attrs = [];
        if (!empty($sistemaAtributos)) {
            // Distribuir 10 pontos entre os atributos fornecidos
            $pontos = 10;
            foreach ($sistemaAtributos as $att) {
                $attrs[$att] = 0;
            }
            $chaves = array_keys($attrs);
            if (!empty($chaves)) {
                $attrs[$chaves[0]] = 3;
                if (isset($chaves[1])) $attrs[$chaves[1]] = 3;
                if (isset($chaves[2])) $attrs[$chaves[2]] = 2;
                if (isset($chaves[3])) $attrs[$chaves[3]] = 2;
            }
        } else {
            $attrs = ["FOR" => 3, "AGI" => 4, "VIG" => 2, "INT" => 1];
        }
        
        return [
            "nome" => $nome,
            "aparencia" => $aparencia,
            "personalidade" => $personalidade,
            "historia" => $historia,
            "objetivos" => $objetivos,
            "atributos" => $attrs,
            "classe_sugerida" => "Combatente",
            "origem_sugerida" => "Mercenário"
        ];
    } else {
        // Sistema Mock
        return [
            "nome" => "Chronos Cyberpunk",
            "classificacao" => "16",
            "descricoes" => [
                "Descrição Geral: Um universo onde a tecnologia e a fusão biológica dividiram a humanidade. Em megalópoles tomadas pela chuva ácida e neon, gangues e corporações disputam dados e território.",
                "Regras de Combate: Foco em tiroteios táticos e invasão de implantes em tempo real. Cada ação gasta pontos de esforço tecnológico (PE).",
                "Mecânica Única: Implantes de Cyberware que geram superaquecimento se usados excessivamente, exigindo refrigeração manual."
            ],
            "atributos" => [
                [ "nome" => "Físico", "abrev" => "FIS", "valor" => "0" ],
                [ "nome" => "Reflexos", "abrev" => "REF", "valor" => "0" ],
                [ "nome" => "Intelecto", "abrev" => "INT", "valor" => "0" ],
                [ "nome" => "Sintonia", "abrev" => "SIN", "valor" => "0" ],
                [ "nome" => "Presença", "abrev" => "PRE", "valor" => "0" ]
            ],
            "status" => [
                [ "nome" => "Integridade", "cor" => "#2ecc71", "base" => "FIS" ],
                [ "nome" => "Calor", "cor" => "#e74c3c", "base" => "REF" ]
            ],
            "defesas" => [
                [ "nome" => "Blindagem", "cor" => "#9b59b6", "base" => "REF" ]
            ],
            "classes" => [
                [ "nome" => "Netrunner", "val1" => "Hackers capazes de invadir qualquer sistema cibernético à distância.", "val2" => "Invasão Rápida: Infiltra-se em implantes inimigos à distância de 10 metros." ],
                [ "nome" => "Solo", "val1" => "Guerreiros urbanos aprimorados com foco em armas pesadas e defesa.", "val2" => "Adrenalina: Regenera 5 pontos de escudo no início de seu turno." ],
                [ "nome" => "Techie", "val1" => "Engenheiros mecânicos que criam drones e customizam armas.", "val2" => "Drone de Apoio: Invoca um robô utilitário voador com 10 PV." ]
            ],
            "pericias" => [
                [ "nome" => "Pontaria", "val1" => "Uso de pistolas, rifles e canhões laser.", "val2" => "Mira Laser: +1 em ataques à distância.", "val3" => "REF" ],
                [ "nome" => "Interface", "val1" => "Controle de sistemas eletrônicos e hacking de portas.", "val2" => "Bypass: Abre trancas digitais com facilidade.", "val3" => "INT" ],
                [ "nome" => "Atletismo", "val1" => "Ações de esforço físico como pular, correr e escalar.", "val2" => "Salto Vertical: Salta o dobro da altura padrão.", "val3" => "FIS" ]
            ],
            "origens" => [
                [ "nome" => "Corporativo", "val1" => "Ex-funcionário de megacorporações com conexões ricas.", "val2" => "Cartão de Crédito: Ganha 20% de desconto em itens comprados." ],
                [ "nome" => "Nômade das Ruas", "val1" => "Sobrevivente criado nas favelas verticais.", "val2" => "Faro de Sucata: Encontra peças sobressalentes com facilidade." ]
            ],
            "equipamentos" => [
                [ "nome" => "Pistola Inteligente", "val1" => "Arma leve com projéteis guiados.", "val2" => "arma" ],
                [ "nome" => "Placa Subdérmica", "val1" => "Implante de proteção sob a pele.", "val2" => "armadura" ]
            ],
            "poderes" => [
                [ "nome" => "Olho Biônico", "val1" => "Escaneia fraquezas inimigas e detecta armadilhas.", "val2" => "passiva" ],
                [ "nome" => "Sobrecarga de Chip", "val1" => "Eleva temporariamente os reflexos sacrificando integridade.", "val2" => "ativa" ]
            ],
            "monstros" => [
                [
                    "nome" => "Drone de Segurança",
                    "val1" => "Robótico",
                    "val2" => 15,
                    "desc" => "Robô de patrulha blindado equipado com metralhadoras térmicas de alta velocidade.",
                    "vida" => 35,
                    "defesa" => 14,
                    "xp" => 80,
                    "atributos_monstro" => [
                        [ "abrev" => "FIS", "valor" => 2 ],
                        [ "abrev" => "REF", "valor" => 4 ],
                        [ "abrev" => "INT", "valor" => 1 ]
                    ]
                ]
            ]
        ];
    }
}
