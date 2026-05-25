-- ============================================================
--  ██████╗  █████╗ ██████╗  █████╗ ██╗   ██╗███████╗██████╗ ███████╗ ██████╗ 
--  ██╔══██╗██╔══██╗██╔══██╗██╔══██╗██║   ██║██╔════╝██╔══██╗██╔════╝██╔═══██╗
--  ██████╔╝███████║██████╔╝███████║██║   ██║█████╗  ██████╔╝███████╗██║   ██║
--  ██╔═══╝ ██╔══██║██╔══██╗██╔══██║╚██╗ ██╔╝██╔══╝  ██╔══██╗╚════██║██║   ██║
--  ██║     ██║  ██║██║  ██║██║  ██║ ╚████╔╝ ███████╗██║  ██║███████║╚██████╔╝
--  ╚═╝     ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝  ╚═══╝  ╚══════╝╚═╝  ╚═╝╚══════╝ ╚═════╝ 
-- ============================================================
--  PARAVERSO — Inserts completos · id_sistema = 2
--  ATENÇÃO: Este arquivo deve ser rodado APÓS o db_table.sql
--  Não contém estrutura de tabelas — apenas dados do sistema ParaVerso.
-- ============================================================
--  CORREÇÕES APLICADAS (v2):
--  [1] Limpeza completa e ordenada antes de reinserção (evita duplicatas)
--  [2] Subqueries de itens, habilidades e perícias agora filtram id_sistema = 2
--  [3] tb_personagem_status: removido 'Sanidade' (não existe em tb_sistema_status
--      do ParaVerso); Sanidade fica em qt_sanidade/qt_sanidade_maxima do tb_personagem
--  [4] SET SQL_SAFE_UPDATES restaurado ao final
--  [5] INSERT do sistema usa ON DUPLICATE KEY UPDATE em vez de INSERT IGNORE
--  [6] Comentários de cabeçalho detalhados em cada bloco
-- ============================================================

SET SQL_SAFE_UPDATES = 0;
SET FOREIGN_KEY_CHECKS = 1;

USE db_table;

-- ============================================================
-- LIMPEZA SEGURA — remove TODOS os dados do sistema 2
-- Ordem respeitada para não violar FK constraints
-- ============================================================

-- Dependentes de tb_personagem (devem vir antes)
DELETE FROM tb_personagem_status
WHERE id_personagem IN (SELECT id_personagem FROM tb_personagem WHERE id_sistema = 2);

DELETE FROM tb_habilidade_personagem
WHERE id_personagem IN (SELECT id_personagem FROM tb_personagem WHERE id_sistema = 2);

DELETE FROM tb_personagem_item
WHERE id_personagem IN (SELECT id_personagem FROM tb_personagem WHERE id_sistema = 2);

DELETE FROM tb_personagem_pericia
WHERE id_personagem IN (SELECT id_personagem FROM tb_personagem WHERE id_sistema = 2);

DELETE FROM tb_personagem_atributo
WHERE id_personagem IN (SELECT id_personagem FROM tb_personagem WHERE id_sistema = 2);

DELETE FROM tb_personagem_origem
WHERE id_personagem IN (SELECT id_personagem FROM tb_personagem WHERE id_sistema = 2);

DELETE FROM tb_personagem_classe
WHERE id_personagem IN (SELECT id_personagem FROM tb_personagem WHERE id_sistema = 2);

DELETE FROM tb_personagem WHERE id_sistema = 2;

-- Dependentes de tb_monstro
DELETE FROM tb_monstro_atributo
WHERE id_monstro IN (SELECT id_monstro FROM tb_monstro WHERE id_sistema = 2);

DELETE FROM tb_monstro WHERE id_sistema = 2;

-- Demais dados do sistema
DELETE FROM tb_sistema_status  WHERE id_sistema = 2;
DELETE FROM tb_habilidade      WHERE id_sistema = 2;
DELETE FROM tb_item            WHERE id_sistema = 2;
DELETE FROM tb_pericia         WHERE id_sistema = 2;
DELETE FROM tb_atributo        WHERE id_sistema = 2;
DELETE FROM tb_classe          WHERE id_sistema = 2;
DELETE FROM tb_origem          WHERE id_sistema = 2;

-- Sistema em último (referenciado por tudo acima via FK)
DELETE FROM tb_sistema WHERE id_sistema = 2;

-- ============================================================
-- 1. SISTEMA
-- ON DUPLICATE KEY UPDATE garante idempotência sem silenciar erros reais
-- ============================================================
INSERT INTO tb_sistema (id_sistema, nm_sistema, ds_descricao, ds_imagem, tp_classificacao, id_usuario_criador) VALUES
(2,
 'ParaVerso',
 'Um RPG de ficção científica multiversal onde qualquer personagem pode existir. Jogadores são arrancados de seus universos de origem e convergem para o Nexus — o centro do multiverso. Lá, precisam descobrir o que é aquele lugar, por que foram parar ali e como voltar para casa. A realidade reage à presença de cada personagem, e quanto mais tentam dobrar as leis do mundo ao redor deles, mais ela empurra de volta.',
 '../img/paraverso-icon.png',
 'L',
 1)
ON DUPLICATE KEY UPDATE
    nm_sistema         = VALUES(nm_sistema),
    ds_descricao       = VALUES(ds_descricao),
    ds_imagem          = VALUES(ds_imagem),
    tp_classificacao   = VALUES(tp_classificacao),
    id_usuario_criador = VALUES(id_usuario_criador);

-- ============================================================
-- 2. ATRIBUTOS
-- ============================================================
INSERT INTO tb_atributo (nm_atributo, ds_abreviacao, id_sistema) VALUES
('Força',       'FOR', 2),
('Agilidade',   'AGI', 2),
('Intelecto',   'INT', 2),
('Presença',    'PRE', 2),
('Resistência', 'RES', 2),
('Instinto',    'INS', 2);

-- ============================================================
-- 3. ORIGENS
-- ============================================================
INSERT INTO tb_origem (nm_origem, ds_origem, id_sistema) VALUES
('Fantasia Medieval',
 'Vindo de um mundo de magia, espadas e deuses ativos. Acostumado a batalhas físicas e misticismo antigo. O Nexus é um choque total de realidade para alguém que nunca imaginou que outros universos existissem. Perícias recomendadas: Briga e Ocultismo. Poder inicial: pode usar Ocultismo para identificar entidades dimensionais como se fossem criaturas mágicas conhecidas.',
 2),
('Cyberpunk',
 'Vindo de metrópoles neon e corporações onipotentes. Possui implantes e conhecimento tecnológico avançado. Vê o Nexus como um sistema a ser hackeado. Perícias recomendadas: Tecnologia e Furtividade. Poder inicial: pode usar Tecnologia para interagir com qualquer dispositivo do Nexus, mesmo sem conhecê-lo.',
 2),
('Horror Paranormal',
 'Sobrevivente de um mundo onde entidades e rituais são reais. Já viu o impossível antes — mas o Nexus ainda consegue surpreendê-lo. Perícias recomendadas: Ocultismo e Intuição. Poder inicial: testes de Sanidade têm dificuldade reduzida em 2 pontos.',
 2),
('Pós-Apocalipse',
 'Forjado pela escassez e pela luta pela sobrevivência. Adaptável, desconfiado e extremamente prático diante do caos do Nexus. Perícias recomendadas: Sobrevivência e Atletismo. Poder inicial: Descanso Curto recupera +2 de Vida adicional.',
 2),
('Futurismo Espacial',
 'Habituado a viagens interestelares e política galáctica. O Nexus é apenas mais uma fronteira desconhecida — mas desta vez sem mapa nem protocolo. Perícias recomendadas: Tecnologia e Percepção. Poder inicial: pode identificar criaturas e anomalias dimensionais com um teste de Ciências DC 12.',
 2),
('Mitológico',
 'Descendente ou servo de deuses, heróis épicos e destinos escritos. O Nexus desafia a ideia de que o destino é imutável. Perícias recomendadas: Atletismo e Ocultismo. Poder inicial: uma vez por sessão pode invocar proteção divina e ignorar 3 pontos de dano de um único ataque.',
 2),
('Cartoon / Anômalo',
 'Oriundo de uma realidade onde a física é apenas uma sugestão. Suas regras de existência confundem todos ao redor — inclusive o próprio Nexus. Perícias recomendadas: Acrobacia e Intuição. Poder inicial: uma vez por cena pode declarar que algo absurdo simplesmente acontece (DC 20 de Instinto para o Mestre aceitar).',
 2);

-- ============================================================
-- 4. CLASSES (Arquétipos)
-- ============================================================
INSERT INTO tb_classe (nm_classe, ds_descricao, ds_habilidade_primaria, qt_nivel_maximo, id_sistema) VALUES
('Combatente',
 'Especialista em confronto direto. Resolve problemas na força e na resistência, protegendo o grupo com o próprio corpo. É o primeiro a entrar e o último a sair.',
 'Golpe Brutal — ao acertar um ataque corpo a corpo, pode gastar 2 de Energia para causar 1D8 de dano extra e empurrar o alvo até 3 metros.',
 5, 2),

('Especialista',
 'Mestre de perícias técnicas e precisão. Focado em investigação, tecnologia e utilidades táticas. Resolve com inteligência o que o Combatente resolve com força.',
 'Análise Rápida — ao examinar um objeto, criatura ou ambiente, pode gastar 2 de Energia para receber +3 em qualquer perícia de Intelecto nessa cena.',
 5, 2),

('Sobrevivente',
 'Adaptado ao caos e à improvisação. Encontra saída em qualquer situação, mesmo sem recursos. Onde outros param, o Sobrevivente continua.',
 'Segundo Fôlego — quando cair abaixo de 4 de Vida, pode gastar 2 de Energia como Reação para recuperar 1D6 de Vida imediatamente.',
 5, 2),

('Estrategista',
 'Pensador tático e líder nato. Planeja antes de agir e potencializa o grupo inteiro. Sua maior arma é sempre saber o que vai acontecer antes que aconteça.',
 'Comando Tático — uma vez por turno, pode gastar 1 de Energia para conceder a um aliado visível +2 no próximo teste ou ataque.',
 5, 2),

('Caçador',
 'Especialista em rastreio, emboscadas e caça. Mais eficiente quando ataca antes de ser visto. No Nexus, aprende rapidamente que caçador e presa podem trocar de papel.',
 'Emboscada — se atacar um alvo que ainda não agiu no combate, causa +1D6 de dano extra e o alvo perde a Reação neste turno.',
 5, 2),

('Anômalo',
 'Ser imprevisível com poderes instáveis. Tudo ao redor dele reage de formas inesperadas — inclusive o Nexus. Recebe +1 de Instabilidade permanente desde o início da campanha.',
 'Singularidade Ampliada — pode gastar 3 de Energia para amplificar sua Singularidade, dobrando seu efeito por 1 turno. O custo em Instabilidade também dobra.',
 5, 2);

-- ============================================================
-- 5. PERÍCIAS (Com atributos base mapeados)
-- ============================================================
INSERT INTO tb_pericia (nm_pericia, ds_atributo_base, id_sistema) VALUES
('Atletismo',    'Força',       2),
('Briga',        'Força',       2),
('Carga Pesada', 'Força',       2),
('Pontaria',     'Agilidade',   2),
('Reflexo',      'Agilidade',   2),
('Furtividade',  'Agilidade',   2),
('Acrobacia',    'Agilidade',   2),
('Investigação', 'Intelecto',   2),
('Tecnologia',   'Intelecto',   2),
('Medicina',     'Intelecto',   2),
('Ciências',     'Intelecto',   2),
('Ocultismo',    'Intelecto',   2),
('Persuasão',    'Presença',    2),
('Intimidação',  'Presença',    2),
('Manipulação',  'Presença',    2),
('Liderança',    'Presença',    2),
('Sobrevivência','Resistência', 2),
('Sanidade',     'Resistência', 2),
('Percepção',    'Instinto',    2),
('Rastreamento', 'Instinto',    2),
('Intuição',     'Instinto',    2);

-- ============================================================
-- 6. STATUS DO SISTEMA
-- Barras: Vida, Energia, Instabilidade
-- Defesa: valor único (não é barra) — fica em qt_defesa do tb_personagem
-- NOTA: Sanidade não é um tb_sistema_status do ParaVerso;
--       ela é armazenada em qt_sanidade / qt_sanidade_maxima do tb_personagem
-- ============================================================
INSERT INTO tb_sistema_status (nm_status, ds_cor, tp_status, id_sistema) VALUES
('Vida',          '#E74C3C', 'barra',  2),
('Energia',       '#5DADE2', 'barra',  2),
('Instabilidade', '#0B0C10', 'barra',  2),
('Defesa',        '#95A5A6', 'defesa', 2);

-- ============================================================
-- 7. HABILIDADES
-- Colunas: nm_habilidade, ds_habilidade, tp_habilidade, qt_custo_esforco, id_sistema
-- ============================================================
INSERT INTO tb_habilidade (nm_habilidade, ds_habilidade, tp_habilidade, qt_custo_esforco, id_sistema) VALUES

-- Combatente
('Golpe Brutal',
 'Ao acertar um ataque corpo a corpo, causa 1D8 de dano extra e pode empurrar o alvo até 3 metros.',
 'poder', 2, 2),

('Resistência Extrema',
 'Como Reação, ignora a próxima condição negativa aplicada neste turno.',
 'habilidade', 1, 2),

('Segundo Fôlego',
 'Quando cair abaixo de 4 de Vida, como Reação recupera 1D6 de Vida imediatamente.',
 'habilidade', 2, 2),

-- Especialista
('Análise Rápida',
 'Gasta 2 de Energia para receber +3 em qualquer perícia de Intelecto durante toda esta cena.',
 'habilidade', 2, 2),

('Interface Neural',
 'Conecta-se mentalmente a qualquer sistema eletrônico próximo sem dispositivo físico.',
 'habilidade', 2, 2),

('Leitura de Intenções',
 'Detecta se um NPC mente ou planeja algo contra o personagem. Dura 1 cena completa.',
 'habilidade', 2, 2),

-- Estrategista
('Comando Tático',
 'Concede a um aliado visível +2 no próximo teste ou ataque deste turno.',
 'habilidade', 1, 2),

('Projeção de Ilusão',
 'Cria uma imagem ilusória convincente num raio de 10 metros por 1D4 turnos.',
 'poder', 3, 2),

-- Caçador
('Emboscada',
 'Ao atacar um alvo que ainda não agiu no combate, causa +1D6 extra e o alvo perde a Reação neste turno.',
 'poder', 0, 2),

('Sentir Fissuras',
 'Detecta passivamente portais, anomalias e criaturas dimensionais num raio de 30 metros.',
 'habilidade', 0, 2),

('Visão Antecipada',
 'O personagem vê 3 segundos à frente. Recebe +3 em Defesa e na Esquiva por 1 turno.',
 'poder', 2, 2),

-- Sobrevivente
('Improviso',
 'Usa qualquer objeto do ambiente como arma ou ferramenta improvisada sem penalidade.',
 'habilidade', 0, 2),

-- Anômalo
('Singularidade Ampliada',
 'Amplifica a Singularidade do personagem, dobrando seu efeito por 1 turno. O custo em Instabilidade também dobra.',
 'poder', 3, 2),

('Portal Curto',
 'Abre um portal de até 10 metros de distância. Aumenta a Instabilidade em 1.',
 'poder', 3, 2),

-- Poderes dimensionais gerais
('Projétil de Energia',
 'Dispara um projétil de energia pura a distância média. Causa 1D8 de dano e ignora armadura física.',
 'poder', 2, 2),

('Escudo de Plasma',
 'Cria uma barreira de energia que absorve até 5 pontos de dano. Dura até ser destruída.',
 'poder', 3, 2),

('Desaceleração Local',
 'O tempo desacelera num raio de 5 metros por 1 turno. Todos nessa área agem por último.',
 'poder', 4, 2),

('Invocação Menor',
 'Convoca uma entidade espiritual fraca para executar uma tarefa simples.',
 'poder', 3, 2),

('Cura pela Vontade',
 'Recupera 1D6 de Vida de um aliado tocado. Custa 1 ponto de Sanidade ao usuário.',
 'poder', 2, 2),

('Controle de Máquina',
 'Assume controle de um robô ou veículo autônomo por 1D4 turnos.',
 'poder', 4, 2),

-- Singularidade da Nrya — Manipulação das Sombras
('Véu das Sombras',
 'As sombras escondem os passos de Nrya completamente. Recebe +3 em Furtividade durante esta cena.',
 'poder', 1, 2),

('Abraço da Escuridão',
 'Sombras se erguem e envolvem um inimigo visível. O alvo fica Atordoado por 1 turno.',
 'poder', 2, 2),

('Garra da Sombra',
 'Ataque à distância usando sombras como projéteis. Rola 1D20 + Instinto vs. Defesa do alvo. Dano: 1D6.',
 'poder', 2, 2),

('Sumir na Penumbra',
 'Nrya desaparece na escuridão e fica invisível enquanto houver sombra ao redor. Dura 1D4 turnos.',
 'poder', 3, 2),

('Apagão',
 'Apaga todas as fontes de luz num raio de 10 metros por 2 turnos. Criaturas da escuridão ficam ativas.',
 'poder', 4, 2);

-- ============================================================
-- 8. ITENS
-- Colunas: nm_item, ds_item, tp_item, qt_peso, qt_valor_ouro,
--          qt_bonus_dano, qt_bonus_defesa, id_sistema
-- ============================================================
INSERT INTO tb_item (nm_item, ds_item, tp_item, qt_peso, qt_valor_ouro, qt_bonus_dano, qt_bonus_defesa, id_sistema) VALUES
('Pistola',              'Arma de fogo padrão. Dano 1D6, alcance médio, 12 tiros por recarga.',                                                                          'arma',       1.00,  30,  6,  0, 2),
('Rifle',                'Rifle de precisão. Dano 1D8, alcance longo, 30 tiros. +1 em Pontaria a longa distância.',                                                      'arma',       3.50,  60,  8,  0, 2),
('Escopeta',             'Shotgun de curto alcance. Dano 1D10, acerta todos num cone de 3m, 6 tiros.',                                                                   'arma',       3.00,  50, 10,  0, 2),
('Sniper',               'Rifle extremo. Dano 1D12, 5 tiros, ignora meia cobertura.',                                                                                    'arma',       5.00, 120, 12,  0, 2),
('Espada Curta',         'Lâmina ágil. Dano 1D6, +1 Agilidade em duelos corpo a corpo.',                                                                                 'arma',       1.50,  25,  6,  0, 2),
('Espada Longa',         'Lâmina de guerra. Dano 1D8, pode atacar 2 alvos adjacentes no mesmo turno.',                                                                   'arma',       2.50,  45,  8,  0, 2),
('Machado de Batalha',   'Dano 1D10. Acertos críticos causam a condição Sangrando no alvo.',                                                                              'arma',       4.00,  55, 10,  0, 2),
('Arco Longo',           'Silencioso. Dano 1D6, alcance longo, 20 flechas por aljava.',                                                                                  'arma',       1.50,  35,  6,  0, 2),
('Lança-chamas',         'Dano 1D8 em cone de 3m, aplica condição Queimando, 10 usos.',                                                                                  'arma',       5.00, 100,  8,  0, 2),
('Adagas de Sombra',     'Adagas imbuídas de escuridão. Dano 1D4 cada. Podem ser lançadas silenciosamente sem penalidade.',                                              'arma',       0.30,  80,  4,  0, 2),
('Armadura Leve',        'Couro reforçado. Reduz 1 ponto de todo dano físico recebido.',                                                                                 'armadura',   3.00,  40,  0,  1, 2),
('Armadura Média',       'Placas e correntes. Reduz 2 pontos de todo dano físico recebido.',                                                                             'armadura',   6.00,  80,  0,  2, 2),
('Armadura Pesada',      'Armadura completa. Reduz 3 pontos de dano. Penalidade: -1 Agilidade permanente.',                                                              'armadura',  12.00, 150,  0,  3, 2),
('Escudo Pequeno',       '+1 na Defesa base. Ocupa uma mão.',                                                                                                            'armadura',   2.00,  20,  0,  1, 2),
('Escudo Grande',        '+2 na Defesa base. Ocupa uma mão. Penalidade: -1 Agilidade.',                                                                                  'armadura',   5.00,  50,  0,  2, 2),
('Kit Médico Básico',    'Estabiliza personagem com 0 de Vida (DC 12) ou recupera 1D6 de Vida. 3 usos.',                                                                 'consumivel', 0.50,  20,  0,  0, 2),
('Estimulante',          'Recupera toda a Energia perdida. Causa Fadiga 1 no turno seguinte.',                                                                           'consumivel', 0.10,  15,  0,  0, 2),
('Granada Comum',        'Explosão em raio de 3m. Dano 1D8 para todos na área. Uso único.',                                                                              'consumivel', 0.30,  25,  8,  0, 2),
('Antisséptico Dim.',    'Remove a condição Corrompido. Reduz Instabilidade em 1. Uso único.',                                                                            'consumivel', 0.20,  40,  0,  0, 2),
('Scanner Dimensional',  'Detecta portais e anomalias num raio de 20m. 5 cargas por Descanso Longo.',                                                                    'ferramenta', 0.50,  80,  0,  0, 2),
('Kit de Ferramentas',   'Necessário para reparos, crafting e sabotagem mecânica. Não consome usos.',                                                                    'ferramenta', 2.00,  30,  0,  0, 2),
('Espelho da Última Chance','Mostra o que teria acontecido com outra decisão. Custa 1 Sanidade permanente.',                                                             'outro',      0.20, 500,  0,  0, 2),
('Bússola do Perdido',   'Aponta para o que você mais precisa — não o que deseja. Nunca repete o mesmo destino.',                                                        'outro',      0.10, 500,  0,  0, 2),
('Relógio Partido',      'Congela o tempo num raio de 5m por 1D4 turnos. O portador sofre Fadiga equivalente ao tempo congelado.',                                       'outro',      0.30, 500,  0,  0, 2);

-- ============================================================
-- 9. MONSTROS
-- ============================================================
INSERT INTO tb_monstro (nm_monstro, ds_monstro, tp_monstro, ds_imagem, qt_vida, qt_defesa, qt_xp_recompensa, qt_vd, id_sistema) VALUES
('Predador Dimensional',
 'Seus olhos não têm pupila e seu corpo emite frio dimensional. Atravessa paredes como se fossem névoa. Caça por instinto, não por fome.',
 'Criatura - Médio',    '../img/paraverso/predador_dimensional.png',   20,  14,  30,  3, 2),

('Eco Corrompido',
 'Versão distorcida de um ser que já existiu em algum universo. Imita vozes e aparências de pessoas conhecidas pelos personagens para desorientá-los.',
 'Anomalia - Pequeno',  '../img/paraverso/eco_corrompido.png',         12,  11,  15,  1, 2),

('Colosso de Fissura',
 'Gigante formado por fragmentos de universos colapsados. Cada parte do corpo pertence a uma realidade diferente e obedece a leis físicas distintas.',
 'Criatura - Colossal', '../img/paraverso/colosso_fissura.png',        80,  18, 150,  5, 2),

('Sombra do Nexus',
 'Não tem forma própria. Habita sombras e drena Sanidade ao invés de Vida. Quem olha diretamente perde 1D4 de Sanidade imediatamente.',
 'Entidade - Médio',    '../img/paraverso/sombra_nexus.png',           18,  13,  25,  2, 2),

('Arquivista Corrompido',
 'Ex-membro dos Arquivistas que absorveu conhecimento demais e perdeu a sanidade. Ainda tenta documentar tudo — inclusive os personagens, vivos ou não.',
 'Humanóide - Médio',   '../img/paraverso/arquivista_corrompido.png',  30,  15,  60,  3, 2),

('O Vazio Andante',
 'Chefe. Uma aberração que não deveria existir em nenhum universo. Onde passa, apaga — memórias, matéria, realidade. Nível de ameaça máximo.',
 'Chefe - Colossal',    '../img/paraverso/vazio_andante.png',         120,  22, 500,  5, 2);

-- ============================================================
-- 10. PERSONAGEM — NRYA RIORSON
-- Vinculada ao usuário admin (id_usuario = 1)
-- Atributos: FOR 3 / AGI 3 / INT 2 / PRE 1 / RES 1 / INS 2 = 12 pts
-- Vida: 10 + (1×2) = 12  |  Energia: 5 + 2 + 1 = 8
-- Sanidade: 10 + 1 = 11  |  Defesa: 10 + 3 = 13
-- ============================================================
INSERT INTO tb_personagem (
    id_usuario, id_sistema,
    nm_personagem,
    ds_aparencia,
    ds_personalidade,
    ds_historia,
    ds_objetivos,
    ds_foto,
    qt_nivel, qt_experiencia,
    qt_vida, qt_vida_maxima,
    qt_defesa,
    qt_sanidade, qt_sanidade_maxima,
    qt_esforco, qt_esforco_maximo,
    qt_bloqueio, qt_esquiva,
    qt_defesa_equip, qt_defesa_outros,
    ds_protecao, ds_resistencias, ds_proficiencias
) VALUES (
    1, 2,
    'Nrya Riorson',
    'Cabelos longos e ondulados loiro etéreo com uma mecha verde esmeralda impossível de ignorar. Sardas suaves espalhadas pelo nariz e maçãs do rosto. Olhos de ônix puro que absorvem a luz ao invés de refletí-la. Corpo forte e ágil em formato violão. Armadura de couro negro ajustada como segunda pele, com adagas posicionadas na coxa, costelas, lombar e antebraços. Em suas costas: o Selo do Nexus — linhas negras entrelaçadas que pulsam lentamente como se respirassem.',
    'Fria à primeira impressão, mas nunca cruel sem motivo. Sarcástica, observadora e perigosamente inteligente. Fala pouco — cada palavra é escolhida como um golpe preciso. Extremamente leal a quem conquista sua confiança e implacável com quem a trai. Não acredita em heróis. Acredita em sobreviventes.',
    'Nasceu no Reino de Valerion. Filha de Kael Riorson, capitão da guarda real executado pelo rei após se opor a ordens que sacrificavam inocentes. Nrya assistiu à execução escondida na multidão. Foi levada ao castelo como símbolo vivo do poder do trono. Tornou-se guerreira excepcional. Numa noite silenciosa encontrou a biblioteca proibida e o livro com um único nome na capa: Nexus. Ao tocá-lo, sombras se ergueram, símbolos queimaram sua pele e um selo pulsante surgiu em suas costas. Desde então, manipula as sombras como extensões da própria alma.',
    'Descobrir o que é o Nexus e por que foi escolhida. Decidir, dia após dia, se nasceu para proteger o trono que matou seu pai — ou para derrubá-lo quando a noite finalmente cair.',
    '../img/uploads/perfil/nrya_riorson.png',
    1, 0,
    12, 12,
    13,
    11, 11,
    8, 8,
    0, 17, -- qt_bloqueio = 0, qt_esquiva = 17
    0, 0,  -- qt_defesa_equip = 0, qt_defesa_outros = 0
    'Proteção Leve', '', 'Armas simples, táticas e proteções leves'
);

-- ============================================================
-- 11. ATRIBUTOS DA NRYA
-- Subqueries filtram id_sistema = 2 para evitar colisão com outros sistemas
-- ============================================================
INSERT INTO tb_personagem_atributo (id_personagem, id_atributo, qt_valor) VALUES
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_atributo   FROM tb_atributo   WHERE nm_atributo   = 'Força'       AND id_sistema = 2), 3),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_atributo   FROM tb_atributo   WHERE nm_atributo   = 'Agilidade'   AND id_sistema = 2), 3),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_atributo   FROM tb_atributo   WHERE nm_atributo   = 'Intelecto'   AND id_sistema = 2), 2),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_atributo   FROM tb_atributo   WHERE nm_atributo   = 'Presença'    AND id_sistema = 2), 1),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_atributo   FROM tb_atributo   WHERE nm_atributo   = 'Resistência' AND id_sistema = 2), 1),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_atributo   FROM tb_atributo   WHERE nm_atributo   = 'Instinto'    AND id_sistema = 2), 2);

-- ============================================================
-- 12. PERÍCIAS DA NRYA
-- Subqueries filtram id_sistema = 2
-- ============================================================
INSERT INTO tb_personagem_pericia (id_personagem, id_pericia, qt_valor, fl_treinado) VALUES
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_pericia    FROM tb_pericia    WHERE nm_pericia    = 'Briga'        AND id_sistema = 2), 5, 1),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_pericia    FROM tb_pericia    WHERE nm_pericia    = 'Pontaria'     AND id_sistema = 2), 5, 1),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_pericia    FROM tb_pericia    WHERE nm_pericia    = 'Investigação' AND id_sistema = 2), 5, 1),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_pericia    FROM tb_pericia    WHERE nm_pericia    = 'Manipulação'  AND id_sistema = 2), 5, 1);

-- ============================================================
-- 13. STATUS DA NRYA
-- CORREÇÃO: apenas barras definidas em tb_sistema_status do ParaVerso:
--   Vida, Energia, Instabilidade.
--   Sanidade NÃO é uma entrada em tb_sistema_status — está em qt_sanidade
--   do tb_personagem (já preenchida acima com 11/11).
--   Defesa é tp_status = 'defesa' — não vai em tb_personagem_status.
-- ============================================================
DELETE FROM tb_personagem_status
WHERE id_personagem = (SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson');

INSERT INTO tb_personagem_status (id_personagem, id_status_sistema, qt_valor_atual, qt_valor_maximo) VALUES
((SELECT id_personagem     FROM tb_personagem    WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_status_sistema FROM tb_sistema_status WHERE nm_status    = 'Vida'          AND id_sistema = 2), 12, 12),
((SELECT id_personagem     FROM tb_personagem    WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_status_sistema FROM tb_sistema_status WHERE nm_status    = 'Energia'       AND id_sistema = 2),  8,  8),
((SELECT id_personagem     FROM tb_personagem    WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_status_sistema FROM tb_sistema_status WHERE nm_status    = 'Instabilidade' AND id_sistema = 2),  1, 10);

-- ============================================================
-- 14. CLASSE DA NRYA
-- ============================================================
INSERT INTO tb_personagem_classe (id_personagem, id_classe, qt_nivel_classe) VALUES
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_classe     FROM tb_classe     WHERE nm_classe     = 'Anômalo' AND id_sistema = 2), 1);

-- ============================================================
-- 15. ORIGEM DA NRYA
-- ============================================================
INSERT INTO tb_personagem_origem (id_personagem, id_origem) VALUES
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_origem     FROM tb_origem     WHERE nm_origem     = 'Fantasia Medieval' AND id_sistema = 2));

-- ============================================================
-- 16. ITENS DA NRYA
-- CORREÇÃO: subqueries filtram id_sistema = 2 para garantir que os itens
--   exclusivos do ParaVerso (Pistola, Espada Curta, etc.) sejam referenciados
--   corretamente, sem risco de pegar itens homônimos de outro sistema.
-- ============================================================
INSERT INTO tb_personagem_item (id_personagem, id_item, qt_quantidade, fl_equipado) VALUES
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_item FROM tb_item WHERE nm_item = 'Pistola'          AND id_sistema = 2), 1, 1),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_item FROM tb_item WHERE nm_item = 'Espada Curta'     AND id_sistema = 2), 1, 1),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_item FROM tb_item WHERE nm_item = 'Armadura Média'   AND id_sistema = 2), 1, 1),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_item FROM tb_item WHERE nm_item = 'Adagas de Sombra' AND id_sistema = 2), 3, 1);

-- ============================================================
-- 17. HABILIDADES DA NRYA
-- CORREÇÃO: todas as subqueries de habilidade filtram id_sistema = 2
-- ============================================================
INSERT INTO tb_habilidade_personagem (id_personagem, id_habilidade) VALUES
-- Singularidade das Sombras
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_habilidade FROM tb_habilidade WHERE nm_habilidade = 'Véu das Sombras'       AND id_sistema = 2)),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_habilidade FROM tb_habilidade WHERE nm_habilidade = 'Abraço da Escuridão'   AND id_sistema = 2)),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_habilidade FROM tb_habilidade WHERE nm_habilidade = 'Garra da Sombra'       AND id_sistema = 2)),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_habilidade FROM tb_habilidade WHERE nm_habilidade = 'Sumir na Penumbra'     AND id_sistema = 2)),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_habilidade FROM tb_habilidade WHERE nm_habilidade = 'Apagão'                AND id_sistema = 2)),
-- Habilidades da Classe Anômalo
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_habilidade FROM tb_habilidade WHERE nm_habilidade = 'Singularidade Ampliada' AND id_sistema = 2)),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_habilidade FROM tb_habilidade WHERE nm_habilidade = 'Portal Curto'           AND id_sistema = 2)),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_habilidade FROM tb_habilidade WHERE nm_habilidade = 'Sentir Fissuras'        AND id_sistema = 2)),
-- Habilidades da Origem Fantasia Medieval
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_habilidade FROM tb_habilidade WHERE nm_habilidade = 'Golpe Brutal'           AND id_sistema = 2)),
((SELECT id_personagem FROM tb_personagem WHERE nm_personagem = 'Nrya Riorson'),
 (SELECT id_habilidade FROM tb_habilidade WHERE nm_habilidade = 'Resistência Extrema'    AND id_sistema = 2));

-- ============================================================
-- 18. ATRIBUTOS DOS MONSTROS
-- Subqueries filtram id_sistema = 2 em ambas as tabelas
-- ============================================================
INSERT INTO tb_monstro_atributo (id_monstro, id_atributo, qt_valor) VALUES

-- Predador Dimensional
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Predador Dimensional' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Força'       AND id_sistema = 2), 4),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Predador Dimensional' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Agilidade'   AND id_sistema = 2), 3),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Predador Dimensional' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Intelecto'   AND id_sistema = 2), 1),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Predador Dimensional' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Presença'    AND id_sistema = 2), 1),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Predador Dimensional' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Resistência' AND id_sistema = 2), 3),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Predador Dimensional' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Instinto'    AND id_sistema = 2), 4),

-- Eco Corrompido
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Eco Corrompido' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Força'       AND id_sistema = 2), 1),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Eco Corrompido' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Agilidade'   AND id_sistema = 2), 3),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Eco Corrompido' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Intelecto'   AND id_sistema = 2), 2),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Eco Corrompido' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Presença'    AND id_sistema = 2), 4),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Eco Corrompido' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Resistência' AND id_sistema = 2), 2),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Eco Corrompido' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Instinto'    AND id_sistema = 2), 3),

-- Colosso de Fissura
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Colosso de Fissura' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Força'       AND id_sistema = 2), 5),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Colosso de Fissura' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Agilidade'   AND id_sistema = 2), 1),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Colosso de Fissura' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Intelecto'   AND id_sistema = 2), 1),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Colosso de Fissura' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Presença'    AND id_sistema = 2), 1),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Colosso de Fissura' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Resistência' AND id_sistema = 2), 5),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Colosso de Fissura' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Instinto'    AND id_sistema = 2), 2),

-- Sombra do Nexus
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Sombra do Nexus' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Força'       AND id_sistema = 2), 1),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Sombra do Nexus' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Agilidade'   AND id_sistema = 2), 4),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Sombra do Nexus' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Intelecto'   AND id_sistema = 2), 3),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Sombra do Nexus' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Presença'    AND id_sistema = 2), 2),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Sombra do Nexus' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Resistência' AND id_sistema = 2), 2),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Sombra do Nexus' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Instinto'    AND id_sistema = 2), 4),

-- Arquivista Corrompido
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Arquivista Corrompido' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Força'       AND id_sistema = 2), 2),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Arquivista Corrompido' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Agilidade'   AND id_sistema = 2), 2),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Arquivista Corrompido' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Intelecto'   AND id_sistema = 2), 5),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Arquivista Corrompido' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Presença'    AND id_sistema = 2), 3),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Arquivista Corrompido' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Resistência' AND id_sistema = 2), 2),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'Arquivista Corrompido' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Instinto'    AND id_sistema = 2), 3),

-- O Vazio Andante (Chefe)
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'O Vazio Andante' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Força'       AND id_sistema = 2), 5),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'O Vazio Andante' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Agilidade'   AND id_sistema = 2), 5),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'O Vazio Andante' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Intelecto'   AND id_sistema = 2), 5),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'O Vazio Andante' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Presença'    AND id_sistema = 2), 5),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'O Vazio Andante' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Resistência' AND id_sistema = 2), 5),
((SELECT id_monstro  FROM tb_monstro  WHERE nm_monstro  = 'O Vazio Andante' AND id_sistema = 2),
 (SELECT id_atributo FROM tb_atributo WHERE nm_atributo = 'Instinto'    AND id_sistema = 2), 5);

-- ============================================================
-- RESTAURAÇÃO DO SAFE MODE
-- ============================================================
SET SQL_SAFE_UPDATES = 1;

-- ============================================================
-- FIM DO SCRIPT PARAVERSO v2
-- ============================================================
