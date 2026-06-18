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

    $tipo = trim($data['tipo'] ?? '');
    $conceito = trim($data['conceito'] ?? '');
    $id_sistema = isset($data['id_sistema']) ? (int) $data['id_sistema'] : null;

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
Você é o game designer mais criativo e imersivo que já existiu. Sua missão é criar um sistema de RPG de mesa completo, rico em atmosfera e mecanicamente coerente a partir do conceito abaixo.

CONCEITO DO SISTEMA: "{$conceito}"

════════ REGRA FUNDAMENTAL — EXPANSÃO CRIATIVA E INGESTÃO DE LINKS/PROMPTS ════════
Não importa o tamanho ou vagueza do conceito informado — você DEVE criar um sistema de RPG de mesa completo, coerente e rico a partir dele.

- SE O USUÁRIO ENVIAR UM LINK (URL) OU UM PROMPT PRONTO GIGANTESCO: "sugue" (absorva e analise detalhadamente) todas as informações presentes no texto ou descritas como provenientes desse link. Extraia as classes, origens, atributos, equipamentos, habilidades, status e ameaças exatamente como foram descritos ali, mesmo que as descrições sejam extensas. Traduza a lore, regras e mecânicas descritas fielmente para o formato do JSON, sem omitir ou ignorar detalhes importantes.
- Se o conceito for uma única palavra (ex: "gay", "cozinha", "verde", "a", "amor"), use essa palavra como inspiração central e construa um universo RPG completo ao redor dela. Por exemplo:
  → "gay" pode se tornar um RPG sobre identidade, revolução social, drag queens, ativismo, subculturas, romance e resistência.
  → "cozinha" pode se tornar um RPG culinário épico com classes de chefs, ingredientes mágicos e batalhas gastronômicas.
  → "amor" pode ser um RPG sobre jornadas emocionais, deuses do amor, corações partidos e rituais de vínculo.
- Se o conceito mencionar um universo de ficção real (Dragon Ball, Naruto, Harry Potter, Star Wars, Marvel, Demon Slayer, etc.), crie o sistema com fidelidade total a esse universo: personagens canônicos, poderes, locais, fações e eventos reais.
- Se o conceito for um gênero (medieval, cyberpunk, horror, etc.), crie o sistema dentro desse gênero com profundidade e riqueza.
- Se o conceito tiver detalhes específicos ("inventário com Espada Flamejante", "itens do filme X", "chakra de Naruto"), inclua esses elementos com os nomes exatos pedidos.
═══════════════════════════════════════════════════════

━━━ NOME DO SISTEMA ━━━
O campo "nome" deve ser CURTO, direto e referenciar o conceito pedido.
- NUNCA coloque a palavra "RPG" ou "Sistema" no final do nome. O nome gerado deve ser limpo e criativo, focando apenas no nome da obra ou conceito.
- Se for baseado em uma obra real ou livro existente (ex: "Ponyo", "Harry Potter", "Percy Jackson", "Ordem Paranormal"), use EXATAMENTE o nome oficial da obra ou algo criativo conexo, SEM colocar "RPG" ou "Sistema". Por exemplo, se for pedido "RPG da Ponyo", o título gerado deve ser apenas "Ponyo".
- Se for um tema próprio/livre (ex: "gay", "cozinha"), use um nome criativo e curto (ex: "Pridefall", "Mesa Épica").
- NUNCA use travessão (—), parênteses, subtítulos ou explicações no nome.

━━━ TÍTULOS DOS TÓPICOS DE DESCRIÇÃO ━━━
Cada tópico de descrição deve ter um título curto e temático no formato: Categoria: Subtítulo.
Use uma destas categorias: Historia, Regras, Jogabilidade, Poderes, Faccoes, Mundo, Conflito.
Exemplos:
- "Historia: A Revolução que Acendeu a Chama"
- "Regras: Como o Ki Flui na Mesa"
- "Jogabilidade: Lutar, Crescer e Transcender"
Gere de 3 a 5 tópicos com categorias variadas. Os títulos devem ser curtos e impactantes.

━━━ PROIBIÇÃO DE ASPAS SIMPLES ━━━
Nos campos de texto (titulo, conteudo, descricao, habilidade, requisito, custo, propriedades), NUNCA use aspas simples ('). Use aspas duplas ou travessão (—).
ERRADO: "o 'Kamehameha' de Goku"
CORRETO: "o Kamehameha de Goku" ou "o Kamehameha — técnica de Goku"

━━━ DETECÇÃO DE UNIVERSO CONHECIDO ━━━
Se o conceito mencionar universo conhecido (Dragon Ball, Naruto, Harry Potter, Star Wars, One Piece, The Witcher, Marvel, DC, Demon Slayer, Attack on Titan, Game of Thrones, etc.):
- Use personagens canônicos como exemplos em classes, origens e poderes.
- Use eventos reais do cânone como lore.
- Crie locais e fações fiéis (ex: Akatsuki, Vila da Folha, Hogwarts, Dothraki).
- Simule o sistema de poder do universo (ex: Chakra, Haki, Ki, A Força, Nen, Reiatsu).
- Atributos devem refletir o IP (Dragon Ball → POD, KI, AGI, RES; Naruto → CHA, NIN, TAI, GEN).
- Se o usuário pediu itens ou equipamentos específicos ("espada do Geralt", "cajado do Dumbledore"), adicione-os com os nomes reais.

━━━ TERMINOLOGIA TEMÁTICA PROFUNDA ━━━
Mergulhe no tema. Exemplos:
- Programadores: bugs, deploy, refatoração, stack overflow, commits, APIs, Docker, CI/CD, hotfix, null pointer, regex.
- Medieval: feudalismo, cavalaria, guilds, alquimia, heráldica, sigilografia, trebuchet.
- Culinário: temperos, técnicas, mise en place, sabores, receitas, críticos, festivais.
- LGBTQIA+: identidade, expressão de gênero, resistência, comunidade, Stonewall, drag, ativismo.
Use vocabulário rico e específico do tema em TUDO: nomes de classes, habilidades, itens, ameaças.

━━━ ITENS NO INVENTÁRIO E EQUIPAMENTOS ESPECÍFICOS ━━━
Se o usuário mencionou itens, armas, equipamentos específicos ("espada flamejante no inventário", "itens de Star Wars"), você DEVE criá-los com os nomes corretos, propriedades mecânicas e dados reais.
Equipamentos de universos canônicos devem ter os nomes originais (ex: Sabre de Luz, Cajado Ancestral, Keyblade).

━━━ QUANTIDADE DE COMPONENTES ━━━
A quantidade de componentes gerada deve ser dinâmica, variada e sob medida para o conceito e necessidades específicas do sistema sugerido. 
Por exemplo, se você perceber que o cenário exige muitas ameaças (como horror de sobrevivência), crie mais ameaças. Se precisar de mais ou menos classes para se adequar ao tema, adapte essa distribuição proporcionalmente. 
Gere uma quantidade rica e balanceada, mantendo-se dentro destes limites gerais:
- classes: 6 a 10 (máx 15)
- pericias: 10 a 15 (máx 30)
- origens: 5 a 8 (máx 75)
- equipamentos: 10 a 15 (máx 100)
- habilidades passivas: 5 a 10 (máx 50)
- poderes ativos: 5 a 10 (máx 50)
- ameaças: 4 a 8 (máx 50)

━━━ DIRETRIZES TÉCNICAS E DE DESIGN CRIATIVO ━━━
- STATUS/DEFESAS: campo base deve ser sempre a string "null" (sem atributo base associado).
- HABILIDADES (passivas): descricao = efeito com números. requisito = condição por extenso. NUNCA coloque "Requer" dentro da descricao.
- PODERES (ativos): descricao = efeito com duração e valores. custo = custo baseado nos status criados.
- EQUIPAMENTOS: tipo = exatamente "Arma", "Proteção" ou "Utilitário".
- Atributos: 5 a 8 atributos, siglas de 3 letras maiúsculas únicas do universo.
- CLASSES E ORIGENS: obrigatório ter "nome", "descricao" e "habilidade". A chave "habilidade" DEVE detalhar o bônus inicial com valores mecânicos e narrativos ultra criativos. NUNCA deixe "habilidade" vazia.
- Nomes de Classes e Origens: devem ser completos, representativos, imersivos e extremamente bem especificados. É TERMINANTEMENTE E TOTALMENTE PROIBIDO gerar nomes inacabados, vazios ou de apenas duas letras como "Ex". Se uma origem representar o passado do personagem com uma profissão anterior, você DEVE obrigatoriamente especificar por completo (Exemplos obrigatórios: "Ex-Militar de Elite", "Ex-Policial Federal", "Ex-Cientista Corporativo", "Ex-Acadêmico de Ocultismo", "Ex-Atleta Olímpico", "Ex-Mercenário"). NUNCA use a palavra "Ex" sozinha ou isolada sob nenhuma circunstância.
- Criatividade Exponencial: Fuja do genérico! Não crie classes comuns como "Guerreiro" ou "Mago" se o tema for culinário, crie "Mestre de Banquetes" ou "Chef Flamejante". Deixe o prompt inteiro recheado de lore profunda, mistérios e termos surpreendentes que façam o mestre de RPG ficar fascinado ao ler!
- Origens Detalhadas e Completas: Toda origem que represente um histórico deve vir perfeitamente qualificada por extenso. NUNCA use "Ex" de forma isolada. Por exemplo, se a ideia for um ex-médico, gere "Ex-Cirurgião de Trauma" ou "Ex-Médico Militar".


━━━ STATUS, DEFESAS E CORES TEMÁTICAS ━━━
- NUNCA use "Pontos de..." nos nomes. Use termos temáticos diretos ("Vida", "Sanidade", "Mana", "Calor", "Glória").
- Cores e nomes dos status devem ser 100% temáticos com o universo.
- Ex: tema LGBTQIA+ → cores do arco-íris, nomes como "Orgulho", "Resiliência". Tema culinário → cores de comida, nomes como "Sabor", "Energia".

Responda EXCLUSIVAMENTE com JSON válido, sem markdown, sem blocos de código, sem texto extra:

{
  "nome": "Nome CURTO do sistema, sem travessão nem subtítulo — ex: Ponyo, Harry Potter, Pridefall, Mesa Épica",
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
      "base": "Sempre 'null'",
      "valor_inicial": "Fórmula: ex 10 + POD",
      "recuperacao": "Como recuperar: ex Restaura 1d6+KI por descanso curto"
    }
  ],
  "defesas": [
    {
      "nome": "Nome Temático da Defesa (sem o prefixo 'Pontos de' — ex: 'Defesa', 'Bloqueio', 'Esquiva')",
      "cor": "#hexadecimal da cor que representa a defesa tematicamente",
      "base": "Sempre 'null'",
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
      "nome": "Nome da Origem Temática Completo e Criativo por Extenso (apenas o nome direto da origem, sem subtítulos adicionais. Exemplos obrigatórios: Gladiador de Arena, Ex-Militar de Elite, Ex-Cientista Corporativo, Artista de Rua, Ex-Agente Secreto. É TERMINANTEMENTE E ABSOLUTAMENTE PROIBIDO gerar apenas 'Ex' ou abreviações curtas e incompletas)",
      "descricao": "Histórico de vida e lore desta origem. Se houver universo canônico, contextualize com locais, fações ou eventos reais do IP. Use aspas duplas, nunca aspas simples. (2 parágrafos)",
      "habilidade": "Nome do Benefício/Poder: descrição do bônus ou vantagem inicial com valores mecânicos. Exemplo: Sobrevivente do Submundo: Concede +2 em testes de Percepção."
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
        $listaOrigensStr = !empty($origens_sistema) ? implode(', ', array_map(function ($o) {
            return '"' . $o . '"'; }, $origens_sistema)) : 'Nenhuma cadastrada';
        $listaClassesStr = !empty($classes_sistema) ? implode(', ', array_map(function ($c) {
            return '"' . $c . '"'; }, $classes_sistema)) : 'Nenhuma cadastrada';
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
Você é um escritor de ficção e game designer veterano de RPG de mesa. Crie um personagem completo, rico e memorável para o sistema informado abaixo.

CONCEITO DO PERSONAGEM: "{$conceito}"

════════ REGRA FUNDAMENTAL — EXPANSÃO CRIATIVA E INGESTÃO DE LINKS/PROMPTS ════════
Não importa o tamanho ou aparente vagueza do conceito — você DEVE criar um personagem completo e profundo a partir dele.

- SE O CONCEITO CONTIVER UM LINK (URL) OU UM PROMPT DETALHADO E GIGANTESCO: "sugue" (absorva e analise detalhadamente) todas as informações presentes no texto ou descritas como provenientes desse link. Extraia o nome, a história de vida detalhada (lore), a aparência, personalidade, os objetivos, classe, origem e equipamentos iniciais de forma 100% fiel e detalhada a partir do texto/link fornecido, sem ignorar nada, estruturando tudo perfeitamente dentro dos campos do JSON de saída.
- Se o conceito for uma palavra ou ideia curta (ex: "gay", "guerreiro", "a", "bruxa", "amor"), expanda criativamente dentro do universo do sistema informado. Por exemplo:
  → "gay" em um RPG medieval pode ser um nobre que desafiou a ordem, um bardo andrógino, um alquimista que destilou sua identidade em poções.
  → "a" pode ser a letra que inicia um nome épico — crie um personagem poderoso cujo nome começa com "A".
  → "guerreiro" pode ser o guerreiro mais lendário do sistema, com uma história de traumas e glórias.
- Se o conceito mencionar um personagem famoso de uma obra (anime, filme, série, jogo, livro — ex: "faça o Goku", "crie o Geralt", "Harry Potter"):
  → Use o NOME EXATO do personagem.
  → A história, aparência, personalidade e objetivos devem ser fiéis à obra original.
  → Adapte classe e origem para as opções disponíveis do sistema.
- Se o conceito pedir um personagem com itens ou equipamentos específicos (ex: "um guerreiro com espada flamejante e escudo rúnico"), inclua esses itens exatos na lista de equipamentos.
═══════════════════════════════════════════════════════

SISTEMA DE RPG SELECIONADO — use rigorosamente os dados abaixo:
- ORIGENS DISPONÍVEIS: [ {$listaOrigensStr} ]
- CLASSES DISPONÍVEIS: [ {$listaClassesStr} ]
- ATRIBUTOS DO SISTEMA: [ {$listaAtributosStr} ]

Distribua exatamente 10 pontos extras além de 0 nos atributos, coerente com o papel do personagem.
Nos textos narrativos, use aspas duplas — NUNCA aspas simples.

Diretrizes de qualidade:
1. Imersão: Terminologia, equipamentos e histórico devem estar organicamente dentro da lore do universo do sistema.
2. Nome impactante: Use o nome exato se for personagem conhecido, ou crie um nome marcante e temático.
3. Coesão mecânica: Atributos devem refletir forças e fraquezas do personagem.
4. História cativante: Passado rico com traumas, alianças e ambições (máximo 2 parágrafos).
5. Aparência vívida: Descreva traços físicos, vestimentas e marcas únicas com detalhes memoráveis.
6. Objetivos concretos: Metas imediatas e ambições de longo prazo claramente definidas.

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

    // 5. Configuração e Execução do cURL HTTP Request para a Gemini API v1beta
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . rawurlencode($apiKey);

    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'responseMimeType' => 'application/json'
        ]
    ];

    // Garante que o script PHP tenha tempo suficiente de execução (150 segundos)
    @set_time_limit(150);

    // 5a. Retry automático com backoff exponencial para erros temporários (503, 429)
    $maxTentativas = 3;           // número máximo de tentativas
    $backoffInicial = 2;           // segundos de espera na 1ª retentativa
    $tentativa = 0;
    $response = '';
    $httpCode = 0;
    $curlError = '';

    while ($tentativa < $maxTentativas) {
        $tentativa++;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Sucesso: sai do loop
        if ($httpCode === 200) {
            break;
        }

        // Erros recuperáveis com retry: 503 (servidor sobrecarregado) e 429 sem cota diária esgotada
        $isServicoBloqueado = ($httpCode === 503 || strpos($response, 'UNAVAILABLE') !== false);
        $isRateLimitMomento = ($httpCode === 429 && strpos($response, 'GenerateRequestsPerDayPerProjectPerModel') === false);

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
        $isKeyInvalid = false;
        $isQuotaExceeded = false;
        $isServicoBloqueado = ($httpCode === 503 || strpos($response, 'UNAVAILABLE') !== false);
        $retryDelay = 60; // segundos padrão

        // API Key inválida
        if (strpos($response, 'API key not valid') !== false || strpos($response, 'API_KEY_INVALID') !== false) {
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
                'success' => true,
                'data' => $respostaMock,
                'mock' => true,
                'aviso' => $mensagem,
                'retry_em' => $isQuotaExceeded ? $retryDelay : null
            ]);
            exit;
        }

        echo json_encode([
            'success' => false,
            'error' => "Erro na API do Gemini (HTTP {$httpCode}): " . ($response ?: $curlError)
        ]);
        exit;
    }

    $respData = json_decode($response, true);
    $textResponse = $respData['candidates'][0]['content']['parts'][0]['text'] ?? '';

    if (empty($textResponse)) {
        echo json_encode(['success' => false, 'error' => 'A API do Gemini retornou uma resposta em branco.']);
        exit;
    }

    // 7. Parsing e Sanitização do JSON — cascata de estratégias robustas
    if (!function_exists('normalizarEFormatacaoJson')) {
        function normalizarEFormatacaoJson($jsonStr)
        {
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

    $textResponseNormalizado = normalizarEFormatacaoJson($textResponse);
    $jsonResult = null;

    // Estratégia 0: tentar direto (com normalização)
    $jsonResult = json_decode(trim($textResponseNormalizado), true);

    // Estratégia 1: remover blocos de markdown (```json ... ```)
    if ($jsonResult === null) {
        $cleanedText = preg_replace('/^```(?:json)?\s*/i', '', trim($textResponseNormalizado));
        $cleanedText = preg_replace('/\s*```\s*$/s', '', $cleanedText);
        $jsonResult = json_decode(trim($cleanedText), true);
    }

    // Estratégia 2: extrair o primeiro bloco JSON { ... } de nível raiz
    if ($jsonResult === null) {
        if (preg_match('/\{[\s\S]*\}/s', $textResponseNormalizado, $matches)) {
            $jsonResult = json_decode($matches[0], true);
        }
    }

    // Estratégia 3: normalizar aspas "curvas" tipográficas que a IA pode gerar
    if ($jsonResult === null) {
        $normalizado = str_replace(
            ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", "\u{201A}", "\u{201B}"],
            ['"', '"', "'", "'", "'", "'"],
            $textResponseNormalizado
        );
        $jsonResult = json_decode(trim($normalizado), true);
        // Tentar também extrair bloco após normalização
        if ($jsonResult === null && preg_match('/\{[\s\S]*\}/s', $normalizado, $matches)) {
            $jsonResult = json_decode($matches[0], true);
        }
    }

    // Estratégia 4: remover caracteres de controle invisíveis e BOM
    if ($jsonResult === null) {
        $semControle = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $textResponseNormalizado);
        $semControle = ltrim($semControle, "\xEF\xBB\xBF"); // BOM UTF-8
        $jsonResult = json_decode(trim($semControle), true);
        if ($jsonResult === null && preg_match('/\{[\s\S]*\}/s', $semControle, $matches)) {
            $jsonResult = json_decode($matches[0], true);
        }
    }

    // Fallback absoluto: tenta rodar nas strings originais não normalizadas
    if ($jsonResult === null) {
        $jsonResult = json_decode(trim($textResponse), true);
        if ($jsonResult === null) {
            $cleanedText = preg_replace('/^```(?:json)?\s*/i', '', trim($textResponse));
            $cleanedText = preg_replace('/\s*```\s*$/s', '', $cleanedText);
            $jsonResult = json_decode(trim($cleanedText), true);
        }
        if ($jsonResult === null) {
            if (preg_match('/\{[\s\S]*\}/s', $textResponse, $matches)) {
                $jsonResult = json_decode($matches[0], true);
            }
        }
    }

    if ($jsonResult === null) {
        echo json_encode([
            'success' => false,
            'error' => 'A resposta gerada pela IA não pôde ser decodificada como JSON.',
            'raw' => $textResponse
        ]);
        exit;
    }

    // 8. Sanitização recursiva: remove aspas simples dos valores string do resultado
    function sanitizarAspas($value)
    {
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
        'data' => $jsonResult
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

/**
 * Função Auxiliar: Gera uma resposta fictícia criativa de alta qualidade caso a API key seja inválida.
 */
function gerarRespostaMockParaEngine($tipo, $prompt, $id_sistema = null, $atributos_sistema = [])
{
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
                if (isset($chaves[1]))
                    $attrs[$chaves[1]] = 3;
                if (isset($chaves[2]))
                    $attrs[$chaves[2]] = 2;
                if (isset($chaves[3]))
                    $attrs[$chaves[3]] = 2;
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
                ["titulo" => "Historia: O Despertar do Neon", "conteudo" => "Um universo onde a tecnologia e a fusão biológica dividiram a humanidade. Em megalópoles tomadas pela chuva ácida e neon, gangues e corporações disputam dados e território."],
                ["titulo" => "Regras: O Fluxo do Código", "conteudo" => "Foco em tiroteios táticos e invasão de implantes em tempo real. Cada ação gasta pontos de esforço tecnológico."],
                ["titulo" => "Jogabilidade: Transcendendo o Metal", "conteudo" => "Os jogadores encarnam mercenários e hackers urbanos que realizam missões perigosas em troca de créditos e implantes de alta tecnologia."]
            ],
            "atributos" => [
                ["nome" => "Físico", "sigla" => "FIS", "descricao" => "Resistência corporal e força bruta"],
                ["nome" => "Reflexos", "sigla" => "REF", "descricao" => "Agilidade, tempo de reação e pontaria"],
                ["nome" => "Intelecto", "sigla" => "INT", "descricao" => "Capacidade de raciocínio, hacks e engenharia"],
                ["nome" => "Sintonia", "sigla" => "SIN", "descricao" => "Conexão com a rede e implantes cibernéticos"],
                ["nome" => "Presença", "sigla" => "PRE", "descricao" => "Carisma, intimidação e força de vontade"]
            ],
            "status" => [
                ["nome" => "Integridade", "sigla" => "INTG", "cor" => "#2ecc71", "base" => "null", "valor_inicial" => "10 + FIS", "recuperacao" => "Recupera por descanso curto"],
                ["nome" => "Calor", "sigla" => "CAL", "cor" => "#e74c3c", "base" => "null", "valor_inicial" => "5 + REF", "recuperacao" => "Resfria por ação manual"]
            ],
            "defesas" => [
                ["nome" => "Blindagem", "cor" => "#9b59b6", "base" => "null", "formula" => "10 + REF", "descricao" => "Neutraliza danos físicos comuns"]
            ],
            "classes" => [
                ["nome" => "Netrunner", "descricao" => "Hackers capazes de invadir qualquer sistema cibernético à distância.", "habilidade" => "Invasão Rápida: Infiltra-se em implantes inimigos à distância de 10 metros."],
                ["nome" => "Solo", "descricao" => "Guerreiros urbanos aprimorados com foco em armas pesadas e defesa.", "habilidade" => "Adrenalina: Regenera 5 pontos de escudo no início de seu turno."],
                ["nome" => "Techie", "descricao" => "Engenheiros mecânicos que criam drones e customizam armas.", "habilidade" => "Drone de Apoio: Invoca um robô utilitário voador com 10 PV."]
            ],
            "pericias" => [
                ["nome" => "Pontaria", "descricao" => "Uso de pistolas, rifles e canhões laser.", "habilidade" => "Baseado em Reflexos", "atributo_chave" => "REF"],
                ["nome" => "Interface", "descricao" => "Controle de sistemas eletrônicos e hacking de portas.", "habilidade" => "Baseado em Intelecto", "atributo_chave" => "INT"],
                ["nome" => "Atletismo", "descricao" => "Ações de esforço físico como pular, correr e escalar.", "habilidade" => "Baseado em Físico", "atributo_chave" => "FIS"]
            ],
            "origens" => [
                ["nome" => "Corporativo", "descricao" => "Ex-funcionário de megacorporações com conexões ricas.", "habilidade" => "Cartão de Crédito: Ganha 20% de desconto em itens comprados."],
                ["nome" => "Nômade das Ruas", "descricao" => "Sobrevivente criado nas favelas verticais.", "habilidade" => "Faro de Sucata: Encontra peças sobressalentes com facilidade."]
            ],
            "equipamentos" => [
                ["nome" => "Pistola Inteligente", "tipo" => "Arma", "descricao" => "Arma leve com projéteis guiados.", "propriedades" => "Dano: 1d6+REF, Carga: 1"],
                ["nome" => "Placa Subdérmica", "tipo" => "Proteção", "descricao" => "Implante de proteção sob a pele.", "propriedades" => "Dano Neutralizado: -2, Carga: 2"]
            ],
            "habilidades" => [
                ["nome" => "Olho Biônico", "descricao" => "Escaneia fraquezas inimigas e detecta armadilhas no cenário.", "requisito" => "Ser da classe Netrunner com pelo menos 2 pontos de Intelecto"]
            ],
            "poderes" => [
                ["nome" => "Sobrecarga de Chip", "descricao" => "Eleva temporariamente os reflexos sacrificando integridade em combate.", "custo" => "2 Calor por ativação"]
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
                        ["sigla" => "FIS", "valor" => 2],
                        ["sigla" => "REF", "valor" => 4],
                        ["sigla" => "INT", "valor" => 1]
                    ]
                ]
            ]
        ];
    }
}
