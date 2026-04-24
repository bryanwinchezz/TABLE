-- ======================================================
-- BANCO DE DADOS RPG - TABLE
-- TCC - Versão Final Aprimorada
-- ======================================================
-- Autores: Paulo Guilherme, Ester Carvalho, Kauan Bryan,
--          Filipe Ferreira, Mayara Bezerra
-- ======================================================

-- drop database db_table;

CREATE DATABASE IF NOT EXISTS db_table
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_table;

-- ============================================================
-- 1. USUÁRIO
-- Armazena os dados de login e perfil de cada jogador/mestre.
-- Melhorias: AUTO_INCREMENT, cargo ENUM, foto_perfil, dt_cadastro,
--            UNIQUE em email, hash de senha (campo maior).
-- ============================================================
CREATE TABLE tb_usuario (
    id_usuario    INT            NOT NULL AUTO_INCREMENT,
    nm_exibicao   VARCHAR(70)    DEFAULT NULL,       -- nome real/exibição (pode ter espaços)
    nm_usuario    VARCHAR(70)    NOT NULL,           -- login/handle (sem espaços)
    ds_email      VARCHAR(100)   NOT NULL,
    ds_senha      VARCHAR(255)   NOT NULL,          -- espaço para hash bcrypt
    dt_nascimento DATE           DEFAULT NULL,       -- campo adicionado para o formulário de cadastro
    tp_cargo      ENUM('jogador','mestre','admin')
                  NOT NULL DEFAULT 'jogador',
    ds_foto       VARCHAR(300)   NOT NULL DEFAULT '../img/uploads/perfil/avatar1.png', -- URL/caminho da foto de perfil predefinida
    ds_bio        VARCHAR(500)   DEFAULT NULL,       -- mini-biografia do usuário
    dt_cadastro   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dt_atualizacao DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    fl_ativo      TINYINT(1)     NOT NULL DEFAULT 1, -- soft-delete
    CONSTRAINT pk_usuario PRIMARY KEY (id_usuario),
    CONSTRAINT uq_usuario_email UNIQUE (ds_email)
);

-- ============================================================
-- 2. SISTEMA DE RPG
-- Representa os sistemas suportados (D&D, Ordem Paranormal…).
-- Melhorias: ds_descricao, ds_imagem, dt_cadastro.
-- ============================================================
CREATE TABLE tb_sistema (
    id_sistema         INT          NOT NULL AUTO_INCREMENT,
    id_usuario_criador INT          DEFAULT NULL,
    nm_sistema         VARCHAR(100) NOT NULL,
    ds_descricao       VARCHAR(1000) DEFAULT NULL,
    ds_imagem          VARCHAR(300)  DEFAULT NULL,
    tp_classificacao   VARCHAR(5)   DEFAULT 'L',
    dt_cadastro        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_sistema PRIMARY KEY (id_sistema),
    CONSTRAINT fk_sistema_usuario FOREIGN KEY (id_usuario_criador)
        REFERENCES tb_usuario (id_usuario)
);

-- ============================================================
-- 3. ORIGEM / BACKGROUND
-- Origem do personagem (ex.: Nobre, Forasteiro, Herói).
-- NOVA TABELA — necessária para a tela criar-personagem.html.
-- ============================================================
CREATE TABLE tb_origem (
    id_origem    INT          NOT NULL AUTO_INCREMENT,
    nm_origem    VARCHAR(80)  NOT NULL,
    ds_origem    VARCHAR(800) DEFAULT NULL,
    id_sistema   INT          DEFAULT NULL,
    CONSTRAINT pk_origem       PRIMARY KEY (id_origem),
    CONSTRAINT fk_origem_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema)
);

-- ============================================================
-- 4. CLASSE
-- Classes jogáveis de cada sistema (Guerreiro, Mago…).
-- Melhoria: nivel_maximo, ds_habilidade_primaria.
-- ============================================================
CREATE TABLE tb_classe (
    id_classe              INT          NOT NULL AUTO_INCREMENT,
    nm_classe              VARCHAR(80)  NOT NULL,
    ds_descricao           VARCHAR(800) DEFAULT NULL,
    ds_habilidade_primaria VARCHAR(200) DEFAULT NULL,
    qt_nivel_maximo        INT          DEFAULT 20,
    id_sistema             INT          DEFAULT NULL,
    CONSTRAINT pk_classe        PRIMARY KEY (id_classe),
    CONSTRAINT fk_classe_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema)
);

-- ============================================================
-- 5. ATRIBUTO
-- Atributos de um sistema (Força, Agilidade, Intelecto…).
-- Melhoria: ds_abreviacao, qt_valor_minimo, qt_valor_maximo.
-- ============================================================
CREATE TABLE tb_atributo (
    id_atributo    INT         NOT NULL AUTO_INCREMENT,
    nm_atributo    VARCHAR(80) NOT NULL,
    ds_abreviacao  VARCHAR(10) DEFAULT NULL,   -- ex.: FOR, AGI, INT
    qt_valor_minimo INT        DEFAULT 0,
    qt_valor_maximo INT        DEFAULT 100,
    id_sistema     INT         DEFAULT NULL,
    CONSTRAINT pk_atributo        PRIMARY KEY (id_atributo),
    CONSTRAINT fk_sistema_atributo FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema)
);

-- ============================================================
-- 6. PERÍCIA
-- Perícias de um sistema (Atletismo, Furtividade…).
-- Melhoria: ds_atributo_base (qual atributo rege a perícia).
-- ============================================================
CREATE TABLE tb_pericia (
    id_pericia        INT         NOT NULL AUTO_INCREMENT,
    nm_pericia        VARCHAR(80) NOT NULL,
    ds_atributo_base  VARCHAR(80) DEFAULT NULL, -- ex.: "Agilidade"
    id_sistema        INT         DEFAULT NULL,
    CONSTRAINT pk_pericia        PRIMARY KEY (id_pericia),
    CONSTRAINT fk_sistema_pericia FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema)
);

-- ============================================================
-- 7. STATUS DO SISTEMA
-- Define as barras de status (Vida, Mana, etc.) e escudos de defesa.
-- NOVA TABELA — permite customização total do sistema.
-- ============================================================
CREATE TABLE tb_sistema_status (
    id_status_sistema INT NOT NULL AUTO_INCREMENT,
    nm_status         VARCHAR(50) NOT NULL,
    ds_cor            VARCHAR(7) NOT NULL,
    tp_status         ENUM('barra', 'defesa') NOT NULL DEFAULT 'barra',
    id_sistema        INT NOT NULL,
    CONSTRAINT pk_sistema_status PRIMARY KEY (id_status_sistema),
    CONSTRAINT fk_status_sistema FOREIGN KEY (id_sistema) 
        REFERENCES tb_sistema (id_sistema)
);

-- ============================================================
-- 8. HABILIDADE
-- Habilidades/poderes disponíveis num sistema.
-- Melhoria: tp_habilidade ENUM, qt_custo_esforco, fl_passiva.
-- ============================================================
CREATE TABLE tb_habilidade (
    id_habilidade     INT          NOT NULL AUTO_INCREMENT,
    nm_habilidade     VARCHAR(80)  NOT NULL,
    ds_habilidade     VARCHAR(600) DEFAULT NULL,
    tp_habilidade     ENUM('ativa','passiva','reacao') DEFAULT 'ativa',
    qt_custo_esforco  INT          DEFAULT 0,   -- custo em pontos de esforço/mana
    id_sistema        INT          DEFAULT NULL,
    CONSTRAINT pk_habilidade        PRIMARY KEY (id_habilidade),
    CONSTRAINT fk_habilidade_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema)
);

-- ============================================================
-- 8. ITEM
-- Itens do jogo (armas, armaduras, consumíveis, acessórios).
-- Melhoria: qt_peso, qt_valor_ouro, qt_bonus_dano, qt_bonus_defesa.
-- ============================================================
CREATE TABLE tb_item (
    id_item        INT          NOT NULL AUTO_INCREMENT,
    nm_item        VARCHAR(100) NOT NULL,
    ds_item        VARCHAR(600) DEFAULT NULL,
    tp_item        ENUM('arma','armadura','consumivel','acessorio','ferramenta','outro')
                   NOT NULL DEFAULT 'outro',
    qt_peso        DECIMAL(6,2) DEFAULT 0.00,
    qt_valor_ouro  INT          DEFAULT 0,
    qt_bonus_dano  INT          DEFAULT 0,
    qt_bonus_defesa INT         DEFAULT 0,
    id_sistema     INT          DEFAULT NULL,
    CONSTRAINT pk_item        PRIMARY KEY (id_item),
    CONSTRAINT fk_item_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema)
);

-- ============================================================
-- 9. MONSTRO
-- Criaturas que podem aparecer nos combates.
-- Melhoria: qt_vida, qt_defesa, qt_xp_recompensa, tp_monstro.
-- ============================================================
CREATE TABLE tb_monstro (
    id_monstro       INT          NOT NULL AUTO_INCREMENT,
    nm_monstro       VARCHAR(80)  NOT NULL,
    ds_monstro       VARCHAR(1500) DEFAULT NULL,
    tp_monstro       VARCHAR(50)  DEFAULT NULL,  -- ex.: besta, morto-vivo, elemental
    ds_imagem        VARCHAR(255) DEFAULT NULL,
    qt_vida          INT          DEFAULT 0,
    qt_defesa        INT          DEFAULT 0,
    qt_xp_recompensa INT          DEFAULT 0,
    qt_vd            INT          DEFAULT 0,
    id_sistema       INT          DEFAULT NULL,
    CONSTRAINT pk_monstro        PRIMARY KEY (id_monstro),
    CONSTRAINT fk_monstro_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema)
);

-- ============================================================
-- 10. PERSONAGEM
-- Personagem criado por um usuário para jogar nas campanhas.
-- Melhorias: nivel, experiencia, foto, origem e classe diretamente
--            no registro (atalho para consultas rápidas).
-- ============================================================
CREATE TABLE tb_personagem (
    id_personagem     INT           NOT NULL AUTO_INCREMENT,
    id_usuario        INT           NOT NULL,
    id_sistema        INT           DEFAULT NULL,
    nm_personagem     VARCHAR(100)  NOT NULL,
    ds_aparencia      VARCHAR(1500) DEFAULT NULL,
    ds_personalidade  VARCHAR(1500) DEFAULT NULL,
    ds_historia       VARCHAR(1500) DEFAULT NULL,
    ds_objetivos      VARCHAR(1500) DEFAULT NULL,
    ds_caracteristicas VARCHAR(1500) DEFAULT NULL, -- Mantido por compatibilidade se necessário
    ds_foto           VARCHAR(300)  DEFAULT NULL,
    qt_nivel          INT           NOT NULL DEFAULT 1,
    qt_experiencia    INT           NOT NULL DEFAULT 0,
    qt_vida           INT           DEFAULT 0,
    qt_vida_maxima    INT           DEFAULT 0,
    qt_defesa         INT           DEFAULT 0,
    qt_sanidade       INT           DEFAULT 0,
    qt_sanidade_maxima INT          DEFAULT 0,
    qt_esforco        INT           DEFAULT 0,
    qt_esforco_maximo INT           DEFAULT 0,
    dt_criacao        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fl_ativo          TINYINT(1)    NOT NULL DEFAULT 1,
    CONSTRAINT pk_personagem        PRIMARY KEY (id_personagem),
    CONSTRAINT fk_personagem_usuario FOREIGN KEY (id_usuario)
        REFERENCES tb_usuario (id_usuario),
    CONSTRAINT fk_personagem_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema)
);

-- ============================================================
-- 11. PERSONAGEM ↔ ATRIBUTO (tabela de valores)
-- ============================================================
CREATE TABLE tb_personagem_atributo (
    id_personagem_atributo INT NOT NULL AUTO_INCREMENT,
    id_personagem          INT NOT NULL,
    id_atributo            INT NOT NULL,
    qt_valor               INT DEFAULT 0,
    CONSTRAINT pk_personagem_atributo  PRIMARY KEY (id_personagem_atributo),
    CONSTRAINT fk_pers_attr_personagem FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem),
    CONSTRAINT fk_pers_attr_atributo   FOREIGN KEY (id_atributo)
        REFERENCES tb_atributo (id_atributo),
    CONSTRAINT uq_pers_atributo UNIQUE (id_personagem, id_atributo)
);

-- ============================================================
-- 13. PERSONAGEM ↔ STATUS (tabela de valores)
-- Armazena os valores atuais e máximos das barras customizáveis.
-- NOVA TABELA.
-- ============================================================
CREATE TABLE tb_personagem_status (
    id_personagem_status INT NOT NULL AUTO_INCREMENT,
    id_personagem        INT NOT NULL,
    id_status_sistema    INT NOT NULL,
    qt_valor_atual       INT DEFAULT 0,
    qt_valor_maximo      INT DEFAULT 0,
    CONSTRAINT pk_personagem_status PRIMARY KEY (id_personagem_status),
    CONSTRAINT fk_ps_personagem     FOREIGN KEY (id_personagem) 
        REFERENCES tb_personagem (id_personagem),
    CONSTRAINT fk_ps_status_sistema FOREIGN KEY (id_status_sistema) 
        REFERENCES tb_sistema_status (id_status_sistema),
    CONSTRAINT uq_pers_status       UNIQUE (id_personagem, id_status_sistema)
);

-- ============================================================
-- 14. PERSONAGEM ↔ PERÍCIA (tabela de valores)
-- ============================================================
CREATE TABLE tb_personagem_pericia (
    id_personagem_pericia INT NOT NULL AUTO_INCREMENT,
    id_personagem         INT NOT NULL,
    id_pericia            INT NOT NULL,
    qt_valor              INT DEFAULT 0,
    fl_treinado           TINYINT(1) DEFAULT 0,  -- 1 = perito treinado
    CONSTRAINT pk_personagem_pericia  PRIMARY KEY (id_personagem_pericia),
    CONSTRAINT fk_pers_per_personagem FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem),
    CONSTRAINT fk_pers_per_pericia    FOREIGN KEY (id_pericia)
        REFERENCES tb_pericia (id_pericia),
    CONSTRAINT uq_pers_pericia UNIQUE (id_personagem, id_pericia)
);

-- ============================================================
-- 13. PERSONAGEM ↔ HABILIDADE
-- ============================================================
CREATE TABLE tb_habilidade_personagem (
    id_habilidade_personagem INT NOT NULL AUTO_INCREMENT,
    id_personagem            INT NOT NULL,
    id_habilidade            INT NOT NULL,
    CONSTRAINT pk_habilidade_personagem  PRIMARY KEY (id_habilidade_personagem),
    CONSTRAINT fk_hab_pers_personagem    FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem),
    CONSTRAINT fk_hab_pers_habilidade    FOREIGN KEY (id_habilidade)
        REFERENCES tb_habilidade (id_habilidade),
    CONSTRAINT uq_pers_habilidade UNIQUE (id_personagem, id_habilidade)
);

-- ============================================================
-- 14. PERSONAGEM ↔ CLASSE
-- ============================================================
CREATE TABLE tb_personagem_classe (
    id_personagem_classe INT NOT NULL AUTO_INCREMENT,
    id_personagem        INT NOT NULL,
    id_classe            INT NOT NULL,
    qt_nivel_classe      INT DEFAULT 1,
    CONSTRAINT pk_personagem_classe  PRIMARY KEY (id_personagem_classe),
    CONSTRAINT fk_pers_cls_personagem FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem),
    CONSTRAINT fk_pers_cls_classe     FOREIGN KEY (id_classe)
        REFERENCES tb_classe (id_classe)
);

-- ============================================================
-- 15. PERSONAGEM ↔ ORIGEM
-- ============================================================
CREATE TABLE tb_personagem_origem (
    id_personagem_origem INT NOT NULL AUTO_INCREMENT,
    id_personagem        INT NOT NULL,
    id_origem            INT NOT NULL,
    CONSTRAINT pk_personagem_origem  PRIMARY KEY (id_personagem_origem),
    CONSTRAINT fk_pers_orig_personagem FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem),
    CONSTRAINT fk_pers_orig_origem     FOREIGN KEY (id_origem)
        REFERENCES tb_origem (id_origem),
    CONSTRAINT uq_pers_origem UNIQUE (id_personagem, id_origem)
);

-- ============================================================
-- 16. PERSONAGEM ↔ ITEM (inventário)
-- ============================================================
CREATE TABLE tb_personagem_item (
    id_personagem_item INT NOT NULL AUTO_INCREMENT,
    id_personagem      INT NOT NULL,
    id_item            INT NOT NULL,
    qt_quantidade      INT DEFAULT 1,
    fl_equipado        TINYINT(1) DEFAULT 0,   -- 1 = item equipado no momento
    CONSTRAINT pk_personagem_item  PRIMARY KEY (id_personagem_item),
    CONSTRAINT fk_pers_item_personagem FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem),
    CONSTRAINT fk_pers_item_item       FOREIGN KEY (id_item)
        REFERENCES tb_item (id_item)
);

-- ============================================================
-- 17. CAMPANHA
-- Representa uma sessão de jogo com mestre e jogadores.
-- Melhorias: id_usuario_mestre (FK), dt_inicio, fl_ativa, ds_imagem.
-- ============================================================
CREATE TABLE tb_campanha (
    id_campanha       INT           NOT NULL AUTO_INCREMENT,
    id_usuario_mestre INT           NOT NULL,   -- quem criou/conduz a campanha
    id_sistema        INT           DEFAULT NULL,
    nm_campanha       VARCHAR(100)  NOT NULL,
    ds_descricao      VARCHAR(1500) DEFAULT NULL,
    ds_imagem         VARCHAR(300)  DEFAULT NULL,
    dt_inicio         DATE          DEFAULT NULL,
    fl_ativa          TINYINT(1)    NOT NULL DEFAULT 1,
    dt_criacao        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_campanha         PRIMARY KEY (id_campanha),
    CONSTRAINT fk_campanha_mestre  FOREIGN KEY (id_usuario_mestre)
        REFERENCES tb_usuario (id_usuario),
    CONSTRAINT fk_campanha_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema)
);

-- ============================================================
-- 18. CAMPANHA ↔ USUÁRIO (jogadores da campanha)
-- Melhoria: tp_papel ENUM (mestre/jogador), dt_entrada.
-- ============================================================
CREATE TABLE tb_campanha_usuario (
    id_campanha_usuario INT  NOT NULL AUTO_INCREMENT,
    id_campanha         INT  NOT NULL,
    id_usuario          INT  NOT NULL,
    tp_papel            ENUM('mestre','jogador') NOT NULL DEFAULT 'jogador',
    dt_entrada          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_campanha_usuario  PRIMARY KEY (id_campanha_usuario),
    CONSTRAINT fk_cu_campanha       FOREIGN KEY (id_campanha)
        REFERENCES tb_campanha (id_campanha),
    CONSTRAINT fk_cu_usuario        FOREIGN KEY (id_usuario)
        REFERENCES tb_usuario (id_usuario),
    CONSTRAINT uq_campanha_usuario  UNIQUE (id_campanha, id_usuario)
);

-- ============================================================
-- 19. CAMPANHA ↔ PERSONAGEM
-- ============================================================
CREATE TABLE tb_campanha_personagem (
    id_campanha_personagem INT NOT NULL AUTO_INCREMENT,
    id_campanha            INT NOT NULL,
    id_personagem          INT NOT NULL,
    CONSTRAINT pk_campanha_personagem  PRIMARY KEY (id_campanha_personagem),
    CONSTRAINT fk_cp_campanha          FOREIGN KEY (id_campanha)
        REFERENCES tb_campanha (id_campanha),
    CONSTRAINT fk_cp_personagem        FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem),
    CONSTRAINT uq_campanha_personagem  UNIQUE (id_campanha, id_personagem)
);

-- ============================================================
-- 20. SESSÃO
-- Registro de cada sessão de jogo de uma campanha.
-- NOVA TABELA — permite controlar histórico de partidas.
-- ============================================================
CREATE TABLE tb_sessao (
    id_sessao    INT           NOT NULL AUTO_INCREMENT,
    id_campanha  INT           NOT NULL,
    nm_sessao    VARCHAR(100)  DEFAULT NULL,
    ds_resumo    VARCHAR(2000) DEFAULT NULL,
    dt_sessao    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    qt_duracao_min INT         DEFAULT NULL,  -- duração em minutos
    CONSTRAINT pk_sessao        PRIMARY KEY (id_sessao),
    CONSTRAINT fk_sessao_campanha FOREIGN KEY (id_campanha)
        REFERENCES tb_campanha (id_campanha)
);

-- ============================================================
-- 21. COMBATE
-- Encontro de combate dentro de uma campanha/sessão.
-- Melhoria: id_sessao, fl_concluido, dt_combate.
-- ============================================================
CREATE TABLE tb_combate (
    id_combate   INT          NOT NULL AUTO_INCREMENT,
    id_campanha  INT          NOT NULL,
    id_sessao    INT          DEFAULT NULL,
    nm_combate   VARCHAR(100) DEFAULT NULL,
    fl_concluido TINYINT(1)   DEFAULT 0,
    dt_combate   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_combate         PRIMARY KEY (id_combate),
    CONSTRAINT fk_combate_campanha FOREIGN KEY (id_campanha)
        REFERENCES tb_campanha (id_campanha),
    CONSTRAINT fk_combate_sessao   FOREIGN KEY (id_sessao)
        REFERENCES tb_sessao (id_sessao)
);

-- ============================================================
-- 22. COMBATE ↔ MONSTRO
-- Melhoria: qt_quantidade (vários do mesmo monstro no combate).
-- ============================================================
CREATE TABLE tb_combate_monstro (
    id_combate_monstro INT NOT NULL AUTO_INCREMENT,
    id_combate         INT NOT NULL,
    id_monstro         INT NOT NULL,
    qt_quantidade      INT DEFAULT 1,
    CONSTRAINT pk_combate_monstro  PRIMARY KEY (id_combate_monstro),
    CONSTRAINT fk_cm_combate       FOREIGN KEY (id_combate)
        REFERENCES tb_combate (id_combate),
    CONSTRAINT fk_cm_monstro       FOREIGN KEY (id_monstro)
        REFERENCES tb_monstro (id_monstro)
);

-- ============================================================
-- 23. MONSTRO ↔ ATRIBUTO
-- ============================================================
CREATE TABLE tb_monstro_atributo (
    id_monstro_atributo INT NOT NULL AUTO_INCREMENT,
    id_monstro          INT NOT NULL,
    id_atributo         INT NOT NULL,
    qt_valor            INT DEFAULT 0,
    CONSTRAINT pk_monstro_atributo  PRIMARY KEY (id_monstro_atributo),
    CONSTRAINT fk_ma_monstro        FOREIGN KEY (id_monstro)
        REFERENCES tb_monstro (id_monstro),
    CONSTRAINT fk_ma_atributo       FOREIGN KEY (id_atributo)
        REFERENCES tb_atributo (id_atributo),
    CONSTRAINT uq_monstro_atributo  UNIQUE (id_monstro, id_atributo)
);

-- ============================================================
-- 24. MONSTRO ↔ PERÍCIA
-- ============================================================
CREATE TABLE tb_monstro_pericia (
    id_monstro_pericia INT NOT NULL AUTO_INCREMENT,
    id_monstro         INT NOT NULL,
    id_pericia         INT NOT NULL,
    qt_valor           INT DEFAULT 0,
    CONSTRAINT pk_monstro_pericia  PRIMARY KEY (id_monstro_pericia),
    CONSTRAINT fk_mp_monstro       FOREIGN KEY (id_monstro)
        REFERENCES tb_monstro (id_monstro),
    CONSTRAINT fk_mp_pericia       FOREIGN KEY (id_pericia)
        REFERENCES tb_pericia (id_pericia),
    CONSTRAINT uq_monstro_pericia  UNIQUE (id_monstro, id_pericia)
);

-- ============================================================
-- 25. ROLAGEM DE DADOS
-- Histórico de cada rolagem feita na plataforma.
-- Melhoria: id_campanha, id_sessao, ds_contexto (motivo da rolagem),
--           qt_modificador (bônus/penalidade aplicado).
-- ============================================================
CREATE TABLE tb_rolagem_dado (
    id_rolagem      INT          NOT NULL AUTO_INCREMENT,
    id_usuario      INT          NOT NULL,
    id_personagem   INT          DEFAULT NULL,
    id_campanha     INT          DEFAULT NULL,
    id_sessao       INT          DEFAULT NULL,
    ds_dado         VARCHAR(10)  NOT NULL,   -- ex.: d20, d6, d100
    qt_resultado    INT          NOT NULL,
    qt_modificador  INT          DEFAULT 0,  -- bônus/penalidade
    ds_contexto     VARCHAR(200) DEFAULT NULL, -- ex.: "Ataque com espada"
    dt_rolagem      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_rolagem           PRIMARY KEY (id_rolagem),
    CONSTRAINT fk_rolagem_usuario   FOREIGN KEY (id_usuario)
        REFERENCES tb_usuario (id_usuario),
    CONSTRAINT fk_rolagem_personagem FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem),
    CONSTRAINT fk_rolagem_campanha  FOREIGN KEY (id_campanha)
        REFERENCES tb_campanha (id_campanha),
    CONSTRAINT fk_rolagem_sessao    FOREIGN KEY (id_sessao)
        REFERENCES tb_sessao (id_sessao)
);

-- ============================================================
-- 26. CONVITE DE CAMPANHA
-- NOVA TABELA — permite o mestre convidar jogadores por e-mail
--               ou link, com controle de status.
-- ============================================================
CREATE TABLE tb_convite_campanha (
    id_convite   INT          NOT NULL AUTO_INCREMENT,
    id_campanha  INT          NOT NULL,
    ds_email     VARCHAR(100) DEFAULT NULL,  -- convidado pelo e-mail
    ds_token     VARCHAR(64)  DEFAULT NULL,  -- token único para link de convite
    tp_status    ENUM('pendente','aceito','recusado','expirado')
                 NOT NULL DEFAULT 'pendente',
    dt_criacao   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dt_expiracao DATETIME     DEFAULT NULL,
    CONSTRAINT pk_convite_campanha  PRIMARY KEY (id_convite),
    CONSTRAINT fk_convite_campanha  FOREIGN KEY (id_campanha)
        REFERENCES tb_campanha (id_campanha)
);

-- ============================================================
-- FIM DO SCRIPT
-- ============================================================
-- Total de tabelas: 28
-- Tabelas do projeto original: 19 (tb_usuario, tb_personagem,
--   tb_campanha, tb_campanha_usuario, tb_campanha_personagem,
--   tb_combate, tb_monstro, tb_combate_monstro, tb_sistema,
--   tb_pericia, tb_personagem_pericia, tb_atributo,
--   tb_personagem_atributo, tb_monstro_pericia, tb_monstro_atributo,
--   tb_classe, tb_personagem_classe, tb_habilidade,
--   tb_habilidade_personagem, tb_rolagem_dado, tb_item,
--   tb_personagem_item)
-- Tabelas NOVAS adicionadas: tb_origem, tb_personagem_origem,
--   tb_sessao, tb_convite_campanha, tb_sistema_status, tb_personagem_status
-- ============================================================

-- ============================================================
-- DADO INICIAL PREDEFINIDO: SISTEMA ORDEM PARANORMAL
-- ============================================================
INSERT INTO tb_sistema (id_sistema, nm_sistema, ds_descricao, ds_imagem, tp_classificacao, id_usuario_criador) VALUES
(1, 'Ordem Paranormal', 'Um sistema de RPG focado em investigação e terror paranormal. Em Ordem Paranormal, os jogadores assumem papéis de personagens de uma organização secreta conhecida como Ordo Realitas, cujo objetivo é combater entidades e fenômenos paranormais que ameaçam a realidade. O Medo é a força motriz que enfraquece a membrana entre o nosso mundo e o Outro Lado.', '../img/fundo-ordem-paranormal.png', '14', NULL);

INSERT INTO tb_atributo (nm_atributo, ds_abreviacao, id_sistema) VALUES
('Força', 'FOR', 1),
('Agilidade', 'AGI', 1),
('Intelecto', 'INT', 1),
('Presença', 'PRE', 1),
('Vigor', 'VIG', 1);

INSERT INTO tb_origem (nm_origem, ds_origem, id_sistema) VALUES
('Acadêmico', 'Você era pesquisador ou professor. Suas investigações e sede por conhecimento o levaram até o paranormal.', 1),
('Atleta', 'Acostumado a limites físicos e disciplina. Pode ter se envolvido no paranormal após um evento inexplicável em uma competição.', 1),
('Policial', 'Acostumado à violência e crimes mundanos, até que uma investigação o colocou de cara com o que não devia existir.', 1),
('Cultista Arrependido', 'Você fez parte do problema. Você viu o outro lado e sobreviveu, agora luta para impedir outros de fazerem o mesmo.', 1);

INSERT INTO tb_classe (nm_classe, ds_descricao, id_sistema) VALUES
('Combatente', 'Especialista em resolver problemas na força bruta. Personagens de frente que protegem o resto da equipe com o próprio corpo.', 1),
('Especialista', 'Mestres de perícias e inteligência. Focados em investigação profunda e utilidades táticas e sociais.', 1),
('Ocultista', 'Personagens que usam as regras do Outro Lado contra ele mesmo. Compreendem rituais que custam a própria sanidade e mente para serem conjurados.', 1);

INSERT INTO tb_pericia (nm_pericia, ds_atributo_base, id_sistema) VALUES
('Atletismo', 'Força', 1),
('Luta', 'Força', 1),
('Pontaria', 'Agilidade', 1),
('Furtividade', 'Agilidade', 1),
('Ocultismo', 'Intelecto', 1),
('Investigação', 'Intelecto', 1),
('Diplomacia', 'Presença', 1),
('Intimidação', 'Presença', 1),
('Fortitude', 'Vigor', 1);

-- ============================================================
-- DADOS INICIAIS DE USUÁRIO E MONSTROS (Gerados via Atualização)
-- ============================================================

-- Inserindo um usuário Admin Padrão para associar a autoria do sistema
-- Senha: admin123
INSERT IGNORE INTO tb_usuario (id_usuario, nm_exibicao, nm_usuario, ds_email, ds_senha, dt_nascimento, tp_cargo, ds_foto, ds_bio) VALUES 
(1, 'TABLE', 'TABLE', 'table@gmail.com', '$2y$10$eIVnZbA5xAVj1dDjDHQnKOhTiTj0LbibokkPhLuWtO.mgIgfDplfq', '1990-01-01', 'admin', '../img/uploads/perfil/avatar1.png', 'Administrador principal da plataforma TABLE. Responsável pelo gerenciamento de sistemas e manutenção da ordem.');

-- Atualizando o Sistema para pertencer ao usuário recém-criado
UPDATE tb_sistema SET id_usuario_criador = 1 WHERE id_sistema = 1;

-- INSERÇÕES DE MONSTROS DE ORDEM PARANORMAL
INSERT INTO tb_monstro (id_monstro, nm_monstro, ds_monstro, tp_monstro, ds_imagem, qt_vida, qt_defesa, qt_xp_recompensa, qt_vd, id_sistema) VALUES 
(1, 'O Diabo', '', 'Relíquia - Médio', '../img/logo_icone.png', 500, 30, 20, 400, 1),
(2, 'Aberração de Carne', '', 'Criatura - Grande', '../img/logo_icone.png', 500, 30, 20, 40, 1),
(3, 'Aniquilação', '', 'Criatura - Colossal', '../img/logo_icone.png', 500, 30, 20, 380, 1),
(4, 'Carente', '', 'Criatura - Grande', '../img/logo_icone.png', 500, 30, 20, 300, 1),
(5, 'Dama de Sangue', '', 'Criatura - Enorme', '../img/logo_icone.png', 500, 30, 20, 60, 1),
(6, 'Demogorgon', 'BADASS!', 'Medo', '../img/uploads/monstro_69e81ebb95227.png', 500, 300, 100, 500, 1);

-- INSERÇÕES DE ATRIBUTOS DOS MONSTROS
INSERT INTO tb_monstro_atributo (id_monstro_atributo, id_monstro, id_atributo, qt_valor) VALUES 
(1, 6, 1, 5),
(2, 6, 2, 5),
(3, 6, 3, 5),
(4, 6, 4, 5),
(5, 6, 5, 5);