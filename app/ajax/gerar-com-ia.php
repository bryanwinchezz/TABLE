<?php
// Invalida o cache do OPcache para forçar atualização no XAMPP imediatamente
if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__FILE__, true);
}

/**
 * TABLE | CassIA AI Integration Engine
 * Endpoint AJAX para processamento de inteligência artificial via Google Gemini API
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Autenticação e Segurança
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Usuário não autenticado.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = Database::getConexao();

    // 2. Obtenção da API Key do Gemini para o Usuário logado
    $stmt = $pdo->prepare("SELECT ds_api_key_gemini FROM tb_usuario WHERE id_usuario = ? LIMIT 1");
    $stmt->execute([$_SESSION['usuario']['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $apiKey = trim($user['ds_api_key_gemini'] ?? '');

    if (empty($apiKey)) {
        echo json_encode(['success' => false, 'error' => 'API_KEY_MISSING']);
        exit;
    }

    // 3. Leitura e Validação dos Parâmetros de Entrada
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $tipo       = trim($data['tipo']       ?? '');
    $conceito   = trim($data['conceito']   ?? '');
    $id_sistema = isset($data['id_sistema']) ? (int)$data['id_sistema'] : null;

    if (!in_array($tipo, ['sistema', 'personagem']) || empty($conceito)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parâmetros de entrada inválidos.']);
        exit;
    }

    $origens_sistema = [];
    $classes_sistema = [];
    $atributos_sistema = [];

    if ($id_sistema) {
        // Buscar origens reais do sistema no banco
        $stmtOri = $pdo->prepare("SELECT nm_origem FROM tb_origem WHERE id_sistema = ?");
        $stmtOri->execute([$id_sistema]);
        $origens_sistema = $stmtOri->fetchAll(PDO::FETCH_COLUMN);

        // Buscar classes reais do sistema no banco
        $stmtCls = $pdo->prepare("SELECT nm_classe FROM tb_classe WHERE id_sistema = ?");
        $stmtCls->execute([$id_sistema]);
        $classes_sistema = $stmtCls->fetchAll(PDO::FETCH_COLUMN);

        // Buscar atributos reais do sistema no banco
        $stmtAttr = $pdo->prepare("SELECT nm_atributo, ds_abreviacao FROM tb_atributo WHERE id_sistema = ?");
        $stmtAttr->execute([$id_sistema]);
        $atributos_sistema = $stmtAttr->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. Definição de Prompts Estruturados de Engenharia de Prompt
    if ($tipo === 'sistema') {
        $prompt = <<<PROMPT
Você é o game designer mais criativo, ousado e imersivo que já existiu. Crie um sistema de RPG de mesa completo, rico em atmosfera e mecanicamente coerente com o conceito a seguir.

════════════════════════════════════════
CONCEITO DO SISTEMA: "{$conceito}"
════════════════════════════════════════

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGRA 1 — NOME DO SISTEMA
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
O campo "nome" do JSON DEVE conter o nome da obra, universo ou tema solicitado de forma reconhecível.
- Se o usuário pediu "RPG de Dragon Ball", o nome deve ser algo como "Dragon Ball RPG", "Dragon Ball — O Caminho do Ki", "Dragon Ball — O Torneio dos Deuses", etc.
- Se o usuário pediu "RPG de Programadores", o nome pode ser "CodeBattle RPG", "Dev Wars — A Mesa dos Bugs", etc.
- NUNCA crie um nome genérico desconexo do conceito pedido.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGRA 2 — TÍTULOS DOS TÓPICOS DE DESCRIÇÃO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Cada tópico de descrição deve ter um título no formato CATEGORIA: Subtítulo Temático.
Use uma das categorias abaixo conforme o assunto do tópico:
- Historia: [Subtítulo imersivo sobre a origem do mundo ou lore]
- Regras: [Subtítulo imersivo sobre como funcionam as mecânicas de jogo]
- Jogabilidade: [Subtítulo imersivo sobre o estilo de jogo e o que os jogadores fazem]
- Poderes: [Subtítulo imersivo sobre os sistemas de poder do universo]
- Faccoes: [Subtítulo imersivo sobre grupos, organizações e alianças]
- Mundo: [Subtítulo imersivo sobre locais, geografias e cenários]
- Conflito: [Subtítulo imersivo sobre guerras, ameaças e antagonistas]
Exemplos de títulos corretos:
- "Historia: A Saga Antes do Torneio do Poder"
- "Regras: Como o Ki Flui na Mesa de Jogo"
- "Jogabilidade: Lutar, Evoluir e Transcender"
- "Faccoes: Os Guerreiros Z e seus Rivais"
Gere de 3 a 5 tópicos de descrição, cada um com categoria diferente.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGRA 3 — PROIBIÇÃO DE ASPAS SIMPLES NOS TEXTOS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Nos campos de texto narrativo (titulo, conteudo, descricao, habilidade, requisito, custo, propriedades), NUNCA use aspas simples (') para citar nomes ou exemplos. Use apenas aspas duplas ou reescreva a frase sem aspas. Use travessão (—) quando precisar destacar algo.
ERRADO: "como o 'Kamehameha' de Goku"
CORRETO: "como o Kamehameha de Goku" ou "como o Kamehameha — técnica icônica de Goku"

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGRA 4 — DETECÇÃO DE UNIVERSO CONHECIDO (FILMES / SÉRIES / ANIMES / JOGOS)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Se o conceito mencionar um universo conhecido (Dragon Ball, Naruto, Harry Potter, Star Wars, The Witcher, Demon Slayer, Attack on Titan, One Piece, Game of Thrones, Marvel, DC, Fullmetal Alchemist, etc.), você DEVE:
- Nomear personagens icônicos como exemplos em classes, origens e poderes.
- Usar eventos canônicos reais como contexto histórico do mundo.
- Criar locais e fações fiéis ao cânone (ex: Organização Akatsuki, Vila da Folha, Kamar-Taj, Hogwarts, Dothraki).
- Criar mecânicas que simulam sistemas de poder do universo (ex: Chakra, Reiatsu, Nen, A Força, Haki, Ki).
- Os atributos devem refletir os sistemas de poder do IP (ex: Dragon Ball — POD, KI, AGI, RES; Naruto — CHA, NIN, TAI, GEN).

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGRA 5 — CRIATIVIDADE MÁXIMA COM TERMINOLOGIA DO TEMA
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Mergulhe FUNDO no tema. Se for RPG de Programadores, use: bugs, deploys, refatoração, stack overflow, commits, pull requests, APIs, logs de erro, terminais, frameworks, frontend, backend, DevOps, CI/CD, containers Docker, linting, merge conflicts, hotfixes, sprints, null pointers, segfaults, regex, injeção de dependência. Se for medieval, use: feudalismo, cavalaria, guilds, pergaminhos, alquimia, sigilografia, heráldica, trebuchet.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGRA 6 — QUANTIDADE DE COMPONENTES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Gere a maior quantidade possível dentro dos limites abaixo:
- classes: entre 6 e 10 (limite máximo: 15)
- pericias: entre 10 e 15 (limite máximo: 30)
- origens: entre 5 e 8 (limite máximo: 75)
- equipamentos: entre 8 e 12 (limite máximo: 100)
- habilidades passivas: entre 6 e 10 (limite máximo: 50)
- poderes ativos: entre 6 e 10 (limite máximo: 50)
- ameacas (monstros/inimigos): entre 3 e 5 (limite máximo: 50)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGRA 7 — REQUISITOS POR EXTENSO (SEM ABREVIAÇÕES)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
O campo "requisito" das habilidades passivas deve ser escrito de forma descritiva e legível, nunca em abreviações:
- ERRADO: "3 BFE", "Nv2 NET", "CAF >= 4"
- CORRETO: "Possuir pelo menos 3 pontos no atributo Brutalidade de Ferro", "Ser da classe Netrunner de nível 2 ou superior"

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGRA 8 — OUTRAS DIRETRIZES TÉCNICAS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
- CAMPO base DE STATUS E DEFESAS: apenas a sigla exata de 3 letras de um atributo gerado. NUNCA uma fórmula.
- HABILIDADES (passivas): campo descricao = efeito mecânico com números. Campo requisito = condição por extenso. NUNCA escreva Requer dentro da descricao.
- PODERES (ativos): campo descricao = efeito com duração e números. Campo custo = custo de ativação baseado nos status gerados.
- EQUIPAMENTOS: campo tipo = exatamente "Arma", "Proteção" ou "Utilitário" (letra maiúscula).
- Atributos: entre 5 e 8 atributos, siglas de 3 letras maiúsculas únicas do universo.
- CLASSES E ORIGENS: Devem conter obrigatoriamente as chaves "nome", "descricao" e "habilidade". A chave "habilidade" DEVE descrever detalhadamente o bônus, item ou benefício inicial concedido pela classe/origem, com valores mecânicos e números exatos. NUNCA deixe a chave "habilidade" vazia ou omitida no JSON.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGRA 9 — COMPONENTES COM NOMES COMPLETOS (SEM ABREVIAÇÕES VAZIAS)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Os nomes de Classes e Origens devem ser descritivos, completos e com significado claro.
- NUNCA use siglas ou abreviações vazias de duas letras ou sem contexto (ex: "EX", "MIL", "DEV").
- Em vez disso, use nomes completos por extenso e bem contextualizados (ex: "Ex-Militar", "Ex-Programador", "Desenvolvedor de Software", "Soldado Imperial").

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REGRA 10 — NOMES DE STATUS, DEFESAS E CORES TEMÁTICAS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
- NUNCA use o termo "Pontos de..." nos nomes de Status ou Defesas. Use termos simples, diretos e temáticos (ex: usar "Vida" em vez de "Pontos de Vida", "Sanidade" em vez de "Pontos de Sanidade", "Mana" em vez de "Pontos de Mana").
- As cores e os próprios nomes dos status e defesas devem estar 100% integrados e temáticos com o universo pedido!
- Por exemplo: Se o tema for "Toy Story", as cores das barras de status devem refletir o universo (ex: verde e roxo como o Buzz Lightyear, ou amarelo e azul como o Woody) e os nomes devem ser temáticos (ex: "Bateria", "Imaginação"). Se o tema for "Programadores", as cores e nomes devem remeter a isso (ex: vermelho para "Bugs", verde para "Compilação", etc.).
- Certifique-se de que os status/defesas combinem de forma muito visual e contextual com o tema!

Responda EXCLUSIVAMENTE com JSON válido, sem markdown, sem blocos de código, sem texto extra:

{
  "nome": "Nome do sistema referenciando a obra ou tema pedido — ex: Dragon Ball RPG — A Era dos Guerreiros Z",
  "classificacao": "L, 10, 12, 14, 16 ou 18",
  "descricao_topicos": [
    {
      "titulo": "Historia: Subtítulo imersivo e temático",
      "conteudo": "Texto imersivo e denso com terminologia específica do tema. Use aspas duplas quando necessário, nunca aspas simples. Máximo 2 parágrafos."
    },
    {
      "titulo": "Regras: Subtítulo imersivo e temático",
      "conteudo": "Como as mecânicas do jogo funcionam na mesa. Use aspas duplas quando necessário, nunca aspas simples. Máximo 2 parágrafos."
    },
    {
      "titulo": "Jogabilidade: Subtítulo imersivo e temático",
      "conteudo": "O que os jogadores fazem, como evoluem e que experiência vivem na mesa. Use aspas duplas quando necessário, nunca aspas simples. Máximo 2 parágrafos."
    }
  ],
  "atributos": [
    {
      "nome": "Nome do Atributo Temático e Único",
      "sigla": "3 letras maiúsculas exclusivas deste universo",
      "descricao": "Quais ações e testes este atributo governa na mesa de jogo."
    }
  ],
  "status": [
    {
      "nome": "Nome Temático do Status (sem o prefixo 'Pontos de' — ex: 'Vida', 'Sanidade', 'Bateria', 'Esforço')",
      "sigla": "Sigla curta",
      "cor": "#hexadecimal da cor que representa o status tematicamente",
      "base": "Sigla EXATA de 3 letras de um dos atributos gerados",
      "valor_inicial": "Fórmula: ex 10 + POD",
      "recuperacao": "Como recuperar: ex Restaura 1d6+KI por descanso curto"
    }
  ],
  "defesas": [
    {
      "nome": "Nome Temático da Defesa (sem o prefixo 'Pontos de' — ex: 'Defesa', 'Bloqueio', 'Esquiva')",
      "cor": "#hexadecimal da cor que representa a defesa tematicamente",
      "base": "Sigla EXATA de 3 letras de um dos atributos gerados",
      "formula": "Fórmula simples: ex 10 + RES",
      "descricao": "Que tipo de dano ou ameaça esta defesa neutraliza."
    }
  ],
  "classes": [
    {
      "nome": "Nome Temático da Classe — imersivo, com referência canônica se houver",
      "descricao": "Papel narrativo e estilo de jogo desta classe. Se o universo for conhecido, cite personagens icônicos como exemplos. Use aspas duplas, nunca aspas simples. (2 parágrafos)",
      "habilidade": "Nome da Habilidade: descrição do bônus, dano ou perícia extra. Exemplo: Ataque Especial: 1 vez por cena, soma +5 de dano puro em um ataque físico."
    }
  ],
  "pericias": [
    {
      "nome": "Nome Temático da Perícia",
      "atributo_chave": "Sigla EXATA de 3 letras de um dos atributos gerados",
      "descricao": "O que esta perícia permite fazer na mesa. Sem aspas simples.",
      "habilidade": "Descrição simples do atributo associado ou bônus. Exemplo: Baseado em Força"
    }
  ],
  "origens": [
    {
      "nome": "Nome da Origem Temática (apenas o nome direto da origem, sem subtítulos, travessões ou explicações adicionais — ex: Soldado, Acadêmico, etc.)",
      "descricao": "Histórico de vida e lore desta origem. Se houver universo canônico, contextualize com locais, fações ou eventos reais do IP. Use aspas duplas, nunca aspas simples. (2 parágrafos)",
      "habilidade": "Nome do Benefício/Poder: descrição do bônus ou vantagem inicial. Exemplo: Sobrevivente Nato: +2 em testes de Percepção."
    }
  ],
  "equipamentos": [
    {
      "nome": "Nome Criativo e Temático do Equipamento",
      "tipo": "Arma, Proteção ou Utilitário",
      "descricao": "Descrição narrativa do item. Sem aspas simples.",
      "propriedades": "Propriedades mecânicas com dados: ex Dano: 2d6+POD, Carga: 1"
    }
  ],
  "habilidades": [
    {
      "nome": "Nome da Habilidade Passiva",
      "descricao": "Efeito permanente e mecânico com números exatos. Sem Requer aqui. Sem aspas simples.",
      "requisito": "Condição por extenso e legível, sem abreviações: ex Possuir pelo menos 4 pontos no atributo Poder de Combate e ser da classe Guerreiro Z"
    }
  ],
  "poderes": [
    {
      "nome": "Nome do Poder Ativo — pode ser técnica famosa do universo",
      "descricao": "Efeito mecânico detalhado com duração, alcance e valores numéricos. Sem aspas simples.",
      "custo": "Custo de ativação baseado nos status: ex 3 Ki por rodada, 1 Ação Padrão"
    }
  ],
  "ameacas": [
    {
      "nome": "Nome da Ameaça — vilão, criatura ou organização. Use inimigos reais do IP se houver.",
      "tipo": "Tipo ou família da ameaça",
      "vd": 20,
      "vida": 45,
      "defesa": 14,
      "xp": 100,
      "descricao": "Lore da ameaça com referências ao IP. Inclua ataques com valores numéricos. Sem aspas simples.",
      "atributos": [
        {
          "sigla": "Sigla de 3 letras do atributo do sistema",
          "valor": 3
        }
      ]
    }
  ]
}
PROMPT;
    } else if ($tipo === 'personagem') {
        $listaOrigensStr = !empty($origens_sistema) ? implode(', ', array_map(function($o) { return '"' . $o . '"'; }, $origens_sistema)) : 'Nenhuma cadastrada';
        $listaClassesStr = !empty($classes_sistema) ? implode(', ', array_map(function($c) { return '"' . $c . '"'; }, $classes_sistema)) : 'Nenhuma cadastrada';
        $listaAtributosStr = '';
        if (!empty($atributos_sistema)) {
            foreach ($atributos_sistema as $attr) {
                $listaAtributosStr .= "{$attr['nm_atributo']} (sigla: {$attr['ds_abreviacao']}), ";
            }
            $listaAtributosStr = rtrim($listaAtributosStr, ', ');
        } else {
            $listaAtributosStr = 'Nenhum atributo cadastrado';
        }

        $prompt = <<<PROMPT
Aja como um Game Designer de RPG e escritor de fantasia veterano.
Crie um personagem rico, carismático e mecanicamente coerente que habite o universo de RPG sugerido.
Por favor, distribua exatamente 10 pontos adicionais além do valor base 0 para cada atributo fornecido no sistema, de forma condizente com o papel e conceito do herói.

Se o conceito solicitar ou fizer referência a um personagem conhecido de alguma obra (filme, série, anime, jogo, livro — ex: "faça o Goku", "crie o Harry Potter", "faça o Geralt de Rivia"), você DEVE criar EXATAMENTE esse personagem solicitado.
- Nome: Deve ser exatamente o nome real do personagem na obra (ex: "Goku", "Geralt de Rivia", "Harry Potter"). NUNCA crie um nome diferente ou genérico se um personagem específico foi pedido.
- História, Aparência, Personalidade e Objetivos: Devem ser totalmente fiéis e detalhar a jornada e os traços reais desse personagem na obra original.
- Adaptação: Adapte as mecânicas dele (classe, origem e atributos) para as opções disponíveis do Sistema de RPG informado abaixo, escolhendo a classe e origem que melhor se assemelham ao conceito dele na obra original.

Se o conceito NÃO for de um personagem conhecido específico, crie um personagem original que se encaixe no tema sugerido.

Aqui estão os dados exatos do Sistema de RPG selecionado no qual o personagem deve ser criado. Você DEVE escolher rigorosamente uma das classes e origens disponíveis abaixo e distribuir pontos nos atributos listados usando suas respectivas siglas exatas:
- ORIGENS DISPONÍVEIS: [ {$listaOrigensStr} ]
- CLASSES DISPONÍVEIS: [ {$listaClassesStr} ]
- ATRIBUTOS DO SISTEMA: [ {$listaAtributosStr} ]

Nos textos narrativos, use aspas duplas quando necessário — NUNCA aspas simples.

Conceito do Personagem solicitado pelo Usuário: "{$conceito}"

Diretrizes Críticas:
1. Imersão no Cenário: Toda a terminologia, equipamentos e histórico devem se encaixar organicamente na lore do universo informado.
2. Nome Fiel ou Evocativo: Use exatamente o nome do personagem solicitado se for um personagem conhecido, ou escolha um nome marcante condizente com o universo.
3. Coesão Mecânica: Os atributos devem refletir as forças e fraquezas descritas no conceito e na história do herói.
4. História Cativante: Detalhe o passado do personagem com riqueza de detalhes, incluindo traumas, alianças passadas ou ambições (máximo 2 parágrafos).
5. Características Extras: Preencha com requinte a aparência do personagem, seus traços de personalidade e seus objetivos de jornada.

Você deve responder EXCLUSIVAMENTE com um objeto JSON válido no formato abaixo:

{
  "nome": "Nome completo e título do personagem",
  "historia": "História de origem detalhada, rica em lore e motivadora. Use aspas duplas quando necessário, nunca aspas simples. (máximo 2 parágrafos)",
  "aparencia": "Descrição detalhada da aparência física, vestimentas e marcas marcantes. Sem aspas simples.",
  "personalidade": "Traços de personalidade, crenças, medos e maneirismos. Sem aspas simples.",
  "objetivos": "Objetivos imediatos e ambições de longo prazo do herói. Sem aspas simples.",
  "atributos": {
    "SIGLA1": valor_inteiro,
    "SIGLA2": valor_inteiro
  },
  "classes": [
    "Nome exato da classe sugerida condizente com as classes do sistema"
  ],
  "origens": [
    "Nome exato da origem sugerida condizente com as origens do sistema"
  ],
  "equipamentos": [
    "Equipamento Temático Inicial 1",
    "Equipamento Temático Inicial 2"
  ]
}
Nota: Use siglas de atributos coerentes com o sistema caso informado. Caso contrário, use as siglas clássicas do cenário correspondente.
PROMPT;
    }

    // 5. Configuração e Execução do cURL HTTP Request para a Gemini API v1
    $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=" . rawurlencode($apiKey);

    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7
        ]
    ];

    // Garante que o script PHP tenha tempo suficiente de execução (150 segundos)
    @set_time_limit(150);

    // 5a. Retry automático com backoff exponencial para erros temporários (503, 429)
    $maxTentativas   = 3;           // número máximo de tentativas
    $backoffInicial  = 2;           // segundos de espera na 1ª retentativa
    $tentativa       = 0;
    $response        = '';
    $httpCode        = 0;
    $curlError       = '';

    while ($tentativa < $maxTentativas) {
        $tentativa++;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Sucesso: sai do loop
        if ($httpCode === 200) {
            break;
        }

        // Erros recuperáveis com retry: 503 (servidor sobrecarregado) e 429 sem cota diária esgotada
        $isServicoBloqueado  = ($httpCode === 503 || strpos($response, 'UNAVAILABLE') !== false);
        $isRateLimitMomento  = ($httpCode === 429 && strpos($response, 'GenerateRequestsPerDayPerProjectPerModel') === false);

        if (($isServicoBloqueado || $isRateLimitMomento) && $tentativa < $maxTentativas) {
            // Backoff exponencial: 2s, 4s, 8s...
            $espera = $backoffInicial * pow(2, $tentativa - 1);
            sleep($espera);
            continue; // próxima tentativa
        }

        // Erro não recuperável ou tentativas esgotadas: sai do loop
        break;
    }

    // 6. Tratamento de Erros da API
    if ($httpCode !== 200) {
        $isKeyInvalid      = false;
        $isQuotaExceeded   = false;
        $isServicoBloqueado = ($httpCode === 503 || strpos($response, 'UNAVAILABLE') !== false);
        $retryDelay        = 60; // segundos padrão

        // API Key inválida
        if (strpos($response, 'API key not valid') !== false || strpos($response, 'API_KEY_INVALID') !== false || $httpCode === 400) {
            $isKeyInvalid = true;
        }

        // Cota diária esgotada (plano gratuito ou limite diário)
        if ($httpCode === 429 || strpos($response, 'RESOURCE_EXHAUSTED') !== false || strpos($response, 'quota') !== false) {
            $isQuotaExceeded = true;

            // Tenta extrair o retryDelay da resposta da API
            $responseData = json_decode($response, true);
            if (!empty($responseData['error']['details'])) {
                foreach ($responseData['error']['details'] as $detail) {
                    if (isset($detail['retryDelay'])) {
                        $retryDelay = (int) filter_var($detail['retryDelay'], FILTER_SANITIZE_NUMBER_INT);
                    }
                }
            }
        }

        // Mock para API inválida, cota esgotada OU servidor indisponível após retentativas
        if ($isKeyInvalid || $isQuotaExceeded || $isServicoBloqueado) {
            $respostaMock = gerarRespostaMockParaEngine($tipo, $conceito, $id_sistema, $atributos_sistema);

            if ($isQuotaExceeded) {
                $mensagem = "Cota diária da API Gemini atingida (plano gratuito: 20 req/dia). Exibindo resultado de demonstração. Tente novamente em aproximadamente {$retryDelay} segundos, ou faça upgrade do seu plano em ai.google.dev.";
            } elseif ($isServicoBloqueado) {
                $mensagem = "Os servidores da API Gemini estão temporariamente sobrecarregados. Foram realizadas {$tentativa} tentativa(s) automática(s). Exibindo resultado de demonstração — tente novamente em alguns instantes.";
            } else {
                $mensagem = "API Key inválida. Exibindo resultado de demonstração.";
            }

            echo json_encode([
                'success'   => true,
                'data'      => $respostaMock,
                'mock'      => true,
                'aviso'     => $mensagem,
                'retry_em'  => $isQuotaExceeded ? $retryDelay : null
            ]);
            exit;
        }

        echo json_encode([
            'success' => false,
            'error'   => "Erro na API do Gemini (HTTP {$httpCode}): " . ($response ?: $curlError)
        ]);
        exit;
    }

    $respData     = json_decode($response, true);
    $textResponse = $respData['candidates'][0]['content']['parts'][0]['text'] ?? '';

    if (empty($textResponse)) {
        echo json_encode(['success' => false, 'error' => 'A API do Gemini retornou uma resposta em branco.']);
        exit;
    }

    // 7. Parsing e Sanitização do JSON
    // Substitui aspas simples por aspas duplas dentro de strings para evitar quebra de JSON
    $jsonResult = json_decode(trim($textResponse), true);
    if ($jsonResult === null) {
        // Fallback: limpar possíveis blocos de formatação markdown
        $cleanedText = preg_replace('/^```json\s*/i', '', trim($textResponse));
        $cleanedText = preg_replace('/```$/',          '', $cleanedText);
        $jsonResult  = json_decode(trim($cleanedText), true);
    }

    if ($jsonResult === null) {
        echo json_encode([
            'success' => false,
            'error'   => 'A resposta gerada pela IA não pôde ser decodificada como JSON.',
            'raw'     => $textResponse
        ]);
        exit;
    }

    // 8. Sanitização recursiva: remove aspas simples dos valores string do resultado
    function sanitizarAspas($value) {
        if (is_string($value)) {
            return str_replace("'", "", $value);
        }
        if (is_array($value)) {
            return array_map('sanitizarAspas', $value);
        }
        return $value;
    }
    $jsonResult = sanitizarAspas($jsonResult);

    // 9. Resposta Final bem-sucedida
    echo json_encode([
        'success' => true,
        'data'    => $jsonResult
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

/**
 * Função Auxiliar: Gera uma resposta fictícia criativa de alta qualidade caso a API key seja inválida.
 */
function gerarRespostaMockParaEngine($tipo, $prompt, $id_sistema = null, $atributos_sistema = []) {
    $promptLower = mb_strtolower($prompt);
    
    if ($tipo === 'personagem') {
        $nome = "Vladis, o Renegado";
        $aparencia = "Um homem de cabelos grisalhos curtos, olhos âmbar penetrantes e cicatrizes visíveis nas mãos. Veste roupas pretas utilitárias cobertas por um casaco de couro desgastado com capuz.";
        $personalidade = "Cínico, reservado e extremamente observador. Fala pouco, mas suas palavras carregam peso. Nutre um forte senso de justiça com quem confia, embora seja implacável com inimigos.";
        $historia = "Vladis era um soldado de elite de uma ordem esquecida até ser traído por seu comandante por se recusar a executar camponeses inocentes. Ele sobreviveu au massacre da sua unidade e desde então vaga pelas sombras, buscando expiação e vingança.";
        $objetivos = "Destruir a facção corrompida que aniquilou sua ordem e resgatar o grimório antigo roubado.";
        
        $attrs = [];
        if (!empty($atributos_sistema)) {
            foreach ($atributos_sistema as $att) {
                $sigla = $att['ds_abreviacao'] ?? $att['sigla'] ?? '';
                if ($sigla) {
                    $attrs[$sigla] = 0;
                }
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
            "classes" => ["Combatente"],
            "origens" => ["Mercenário"],
            "equipamentos" => ["Espada de Aço", "Cota de Malha"]
        ];
    } else {
        return [
            "nome" => "Chronos Cyberpunk",
            "classificacao" => "16",
            "descricao_topicos" => [
                [ "titulo" => "Historia: O Despertar do Neon", "conteudo" => "Um universo onde a tecnologia e a fusão biológica dividiram a humanidade. Em megalópoles tomadas pela chuva ácida e neon, gangues e corporações disputam dados e território." ],
                [ "titulo" => "Regras: O Fluxo do Código", "conteudo" => "Foco em tiroteios táticos e invasão de implantes em tempo real. Cada ação gasta pontos de esforço tecnológico." ],
                [ "titulo" => "Jogabilidade: Transcendendo o Metal", "conteudo" => "Os jogadores encarnam mercenários e hackers urbanos que realizam missões perigosas em troca de créditos e implantes de alta tecnologia." ]
            ],
            "atributos" => [
                [ "nome" => "Físico", "sigla" => "FIS", "descricao" => "Resistência corporal e força bruta" ],
                [ "nome" => "Reflexos", "sigla" => "REF", "descricao" => "Agilidade, tempo de reação e pontaria" ],
                [ "nome" => "Intelecto", "sigla" => "INT", "descricao" => "Capacidade de raciocínio, hacks e engenharia" ],
                [ "nome" => "Sintonia", "sigla" => "SIN", "descricao" => "Conexão com a rede e implantes cibernéticos" ],
                [ "nome" => "Presença", "sigla" => "PRE", "descricao" => "Carisma, intimidação e força de vontade" ]
            ],
            "status" => [
                [ "nome" => "Integridade", "sigla" => "INTG", "cor" => "#2ecc71", "base" => "FIS", "valor_inicial" => "10 + FIS", "recuperacao" => "Recupera por descanso curto" ],
                [ "nome" => "Calor", "sigla" => "CAL", "cor" => "#e74c3c", "base" => "REF", "valor_inicial" => "5 + REF", "recuperacao" => "Resfria por ação manual" ]
            ],
            "defesas" => [
                [ "nome" => "Blindagem", "cor" => "#9b59b6", "base" => "REF", "formula" => "10 + REF", "descricao" => "Neutraliza danos físicos comuns" ]
            ],
            "classes" => [
                [ "nome" => "Netrunner", "descricao" => "Hackers capazes de invadir qualquer sistema cibernético à distância.", "habilidade" => "Invasão Rápida: Infiltra-se em implantes inimigos à distância de 10 metros." ],
                [ "nome" => "Solo", "descricao" => "Guerreiros urbanos aprimorados com foco em armas pesadas e defesa.", "habilidade" => "Adrenalina: Regenera 5 pontos de escudo no início de seu turno." ],
                [ "nome" => "Techie", "descricao" => "Engenheiros mecânicos que criam drones e customizam armas.", "habilidade" => "Drone de Apoio: Invoca um robô utilitário voador com 10 PV." ]
            ],
            "pericias" => [
                [ "nome" => "Pontaria", "descricao" => "Uso de pistolas, rifles e canhões laser.", "habilidade" => "Baseado em Reflexos", "atributo_chave" => "REF" ],
                [ "nome" => "Interface", "descricao" => "Controle de sistemas eletrônicos e hacking de portas.", "habilidade" => "Baseado em Intelecto", "atributo_chave" => "INT" ],
                [ "nome" => "Atletismo", "descricao" => "Ações de esforço físico como pular, correr e escalar.", "habilidade" => "Baseado em Físico", "atributo_chave" => "FIS" ]
            ],
            "origens" => [
                [ "nome" => "Corporativo", "descricao" => "Ex-funcionário de megacorporações com conexões ricas.", "habilidade" => "Cartão de Crédito: Ganha 20% de desconto em itens comprados." ],
                [ "nome" => "Nômade das Ruas", "descricao" => "Sobrevivente criado nas favelas verticais.", "habilidade" => "Faro de Sucata: Encontra peças sobressalentes com facilidade." ]
            ],
            "equipamentos" => [
                [ "nome" => "Pistola Inteligente", "tipo" => "Arma", "descricao" => "Arma leve com projéteis guiados.", "propriedades" => "Dano: 1d6+REF, Carga: 1" ],
                [ "nome" => "Placa Subdérmica", "tipo" => "Proteção", "descricao" => "Implante de proteção sob a pele.", "propriedades" => "Dano Neutralizado: -2, Carga: 2" ]
            ],
            "habilidades" => [
                [ "nome" => "Olho Biônico", "descricao" => "Escaneia fraquezas inimigas e detecta armadilhas no cenário.", "requisito" => "Ser da classe Netrunner com pelo menos 2 pontos de Intelecto" ]
            ],
            "poderes" => [
                [ "nome" => "Sobrecarga de Chip", "descricao" => "Eleva temporariamente os reflexos sacrificando integridade em combate.", "custo" => "2 Calor por ativação" ]
            ],
            "ameacas" => [
                [
                    "nome" => "Drone de Segurança",
                    "tipo" => "Robótico",
                    "vd" => 15,
                    "vida" => 35,
                    "defesa" => 14,
                    "xp" => 80,
                    "descricao" => "Robô de patrulha blindado equipado com metralhadoras térmicas de alta velocidade.",
                    "atributos" => [
                        [ "sigla" => "FIS", "valor" => 2 ],
                        [ "sigla" => "REF", "valor" => 4 ],
                        [ "sigla" => "INT", "valor" => 1 ]
                    ]
                ]
            ]
        ];
    }
}
