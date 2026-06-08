-- =============================================================================
--                  CARGA DE DADOS - SISTEMA TABLE (CRIATURAS E MONSTROS)
-- =============================================================================
--  Este arquivo contém os inserts do sistema oficial da TABLE (id_sistema = 2).
--  Inclui monstros icônicos de filmes de terror, suspense e ficção científica.
-- =============================================================================

USE db_table;

-- ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
--              [01] SISTEMA TABLE
-- ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
INSERT INTO tb_sistema (id_sistema, nm_sistema, ds_descricao, ds_imagem, ds_background, tp_classificacao, id_usuario_criador) VALUES
(2, 'TABLE', 'Sistema oficial e nativo do TABLE RPG. Reúne criaturas clássicas da cultura pop, cinema de terror, suspense e ficção científica. Ideal para campanhas que misturam horror clássico, sobrevivência urbana e investigações paranormais multissistema.', '../img/fundo_inicial.jpg', '../img/fundo_regras.jpg', '16', 1);

-- ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
--              [02] MONSTROS DO SISTEMA TABLE
-- ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
INSERT INTO tb_monstro (id_monstro, nm_monstro, ds_monstro, tp_monstro, ds_imagem, qt_vida, qt_defesa, qt_xp_recompensa, qt_vd, id_sistema) VALUES
(100, 'Demogorgon', 'Uma criatura humanoide predadora nativa da dimensão paralela conhecida como o Mundo Invertido. Possui pele cinza espessa resistente a balas e uma cabeça que se abre como uma flor carnívora cheia de dentes afiados. Extremamente sensível ao cheiro de sangue.', 'Interdimensional', '../img/uploads/perfil/avatar1.png', 380, 24, 25, 160, 2),
(101, 'Xenomorfo', 'A máquina de matar perfeita do espaço profundo. Possui sangue ácido altamente corrosivo, agilidade extrema, uma cauda pontiaguda mortal e uma mandíbula retrátil secundária. Caça de forma furtiva usando dutos de ventilação.', 'Alienígena', '../img/uploads/perfil/avatar1.png', 290, 28, 20, 140, 2),
(102, 'Pennywise', 'Uma entidade cósmica ancestral de puro Medo que desperta a cada 27 anos para se alimentar de crianças. Pode assumir a forma de seus piores pesadelos e usa ilusões mentais para enlouquecer suas vítimas.', 'Entidade', '../img/uploads/perfil/avatar1.png', 999, 45, 80, 340, 2),
(103, 'Freddy Krueger', 'O assassino dos sonhos. Ataca suas vítimas enquanto elas dormem. Suas garras de metal e controle total sobre a realidade dos sonhos o tornam virtualmente invencível no mundo onírico.', 'Espírito', '../img/uploads/perfil/avatar1.png', 450, 32, 50, 220, 2),
(104, 'Jason Voorhees', 'O assassino imortal de Crystal Lake. Um titã silencioso de força descomunal usando uma máscara de hóquei. Possui regeneração absurda e persegue suas vítimas incansavelmente, não importando o dano recebido.', 'Assassino', '../img/uploads/perfil/avatar1.png', 600, 20, 45, 180, 2),
(105, 'Predador', 'Um caçador alienígena altamente tecnológico de uma raça guerreira. Utiliza camuflagem ativa de invisibilidade, visão térmica e um canhão de plasma de ombro. Caça apenas alvos que oferecem perigo real como troféus.', 'Alienígena', '../img/uploads/perfil/avatar1.png', 500, 30, 40, 200, 2),
(106, 'Godzilla', 'O Rei dos Monstros. Uma força colossal da natureza despertada por testes nucleares. Possui uma carapaça indestrutível e dispara um sopro atômico devastador de pura radiação.', 'Kaiju', '../img/uploads/perfil/avatar1.png', 2500, 60, 100, 400, 2),
(107, 'Pinhead', 'O líder dos Cenobitas, sacerdotes da ordem da dor e do prazer no submundo dos espíritos. Invocado através da Configuração do Lamento (uma caixa quebra-cabeça). Ataca usando correntes com ganchos.', 'Cenobita', '../img/uploads/perfil/avatar1.png', 800, 40, 60, 300, 2),
(108, 'Vecna', 'Um mago outrora humano que se corrompeu no Mundo Invertido, tornando-se uma entidade sombria capaz de criar conexões psíquicas com suas vítimas, explorando seus traumas e culpas para consumi-las de dentro para fora.', 'Entidade', '../img/uploads/perfil/avatar1.png', 900, 35, 75, 280, 2),
(109, 'Slender Man', 'Uma criatura misteriosa, alta e magra, sem rosto, vestindo um terno preto. Ele persegue suas presas silenciosamente por florestas e locais isolados, distorcendo aparelhos eletrônicos e causando paranoia extrema a quem o observa.', 'Lenda', '../img/uploads/perfil/avatar1.png', 400, 22, 30, 150, 2),
(110, 'Chucky', 'O infame boneco "Good Guy" possuído pela alma de um assassino em série através de um ritual de vodu. Apesar de seu tamanho reduzido, ele é ágil, sádico e mestre em ataques surpresa usando facas ou qualquer objeto ao seu alcance.', 'Brinquedo', '../img/uploads/perfil/avatar1.png', 120, 18, 15, 80, 2),
(111, 'Michael Myers', 'A encarnação do mal puro em forma humana. Um assassino silencioso que persegue suas vítimas usando uma máscara branca sem expressão. Possui uma resistência física sobrenatural, continuando a avançar mesmo após ferimentos fatais.', 'Assassino', '../img/uploads/perfil/avatar1.png', 550, 15, 40, 170, 2),
(112, 'Babadook', 'Uma criatura sombria nascida de um livro infantil assustador. Ele se alimenta da dor, do luto e da negação. Uma vez que alguém se torna consciente de sua existência, ele os atormenta até levá-los à loucura.', 'Entidade', '../img/uploads/perfil/avatar1.png', 480, 25, 45, 190, 2),
(113, 'Cthulhu', 'Uma divindade cósmica ancestral adormecida nas profundezas da cidade submersa de Rlyeh. Sua simples presença ou visualização é suficiente para corromper a sanidade de qualquer mente mortal, levando-os à loucura instantânea.', 'Divindade', '../img/uploads/perfil/avatar1.png', 3000, 65, 150, 500, 2),
(114, 'Monstro de Frankenstein', 'Uma criatura artificial montada a partir de partes de cadáveres e trazida à vida através de descargas elétricas. Embora seja incompreendido e busque afeto, sua força descomunal e fúria o tornam extremamente perigoso quando provocado.', 'Constructo', '../img/uploads/perfil/avatar1.png', 350, 18, 20, 110, 2),
(115, 'Conde Drácula', 'O vampiro supremo e lorde das trevas. Possui a habilidade de se transformar em morcego ou névoa, hipnotizar mortais e regenerar seus ferimentos ao consumir sangue. Vulnerável apenas à luz solar e estacas de madeira.', 'Vampiro', '../img/uploads/perfil/avatar1.png', 700, 30, 55, 240, 2);