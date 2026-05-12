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
-- ============================================================
-- INSERTS COMPLETOS - ORDEM PARANORMAL RPG v1.3
-- (inserts_ordem_paranormal_v2.sql)
-- ============================================================


-- ============================================================
-- ORIGENS (tb_origem)
-- Complementa as 4 origens do db_table.sql
-- (Acadêmico, Atleta, Policial, Cultista Arrependido)
-- ============================================================

INSERT INTO tb_origem (nm_origem, ds_origem, id_sistema) VALUES
('Agente de Saúde',
 'Profissional da saúde — enfermeiro, farmacêutico, médico, psicólogo ou socorrista. Foi surpreendido por um evento paranormal durante o trabalho. Perícias: Intuição e Medicina. Poder: Técnica Medicinal — ao curar um personagem, adiciona seu Intelecto no total de PV curados.',
 1),
('Amnésico',
 'Perdeu a maior parte da memória — talvez por trauma paranormal ou ritual. Pode ter sido vítima de cultistas, ou até ter sido cultista. A Ordem é a única família que conhece. Perícias: duas à escolha do mestre. Poder: Vislumbres do Passado — uma vez por sessão pode gastar 2 PE (Intelecto DT 10) para reconhecer pessoas ou lugares, recebendo 1d4 PE temporários.',
 1),
('Artista',
 'Ator, músico, escritor, dançarino ou influenciador. Sua criatividade tem um lado paranormal que o público desconhece. Perícias: Artes e Enganação. Poder: Magnum Opus — uma vez por missão pode ser reconhecido por alguém, recebendo +5 em testes de Presença e perícias baseadas em Presença contra essa pessoa.',
 1),
('Chef',
 'Cozinheiro amador ou profissional. Sua comida tem algo de inexplicável que outros agentes adoram. Perícias: Fortitude e Profissão (cozinheiro). Poder: Ingrediente Secreto — em interlúdio, ao se alimentar, pode cozinhar um prato especial que fornece o benefício de dois pratos para todos que também se alimentarem.',
 1),
('Criminoso',
 'Ladrão, batedor de carteiras ou membro de facção criminosa. A Ordem achou melhor recrutar seus talentos. Perícias: Crime e Furtividade. Poder: O Crime Compensa — no final de uma missão, escolha um item encontrado; na próxima missão ele não conta no limite de itens por patente.',
 1),
('Desgarrado',
 'Eremita, em situação de rua, ou alguém que abandonou a rotina após descobrir o paranormal. A vida sem confortos modernos o deixou mais forte. Perícias: Fortitude e Sobrevivência. Poder: Calejado — recebe +1 PV para cada 5% de NEX.',
 1),
('Engenheiro',
 'Engenheiro profissional ou inventor de garagem. Provavelmente criou algum dispositivo paranormal que chamou a atenção da Ordem. Perícias: Profissão e Tecnologia. Poder: Ferramenta Favorita — um item à sua escolha (exceto armas) conta como uma categoria abaixo para você.',
 1),
('Executivo',
 'Administrador, advogado ou contador em grande empresa. Sua vida era normal até descobrir algo que não devia — talvez um ritual por trás do sucesso da empresa. Perícias: Diplomacia e Profissão. Poder: Processo Otimizado — em testes estendidos ou para revisar documentos, pode gastar 2 PE para receber +5 no teste.',
 1),
('Investigador',
 'Investigador do governo (perito forense, policial federal) ou privado (detetive). Teve contato com o paranormal em um caso. Perícias: Investigação e Percepção. Poder: Faro para Pistas — uma vez por cena, ao procurar pistas, pode gastar 1 PE para receber +5 no teste.',
 1),
('Lutador',
 'Pratica arte marcial, esporte de luta ou cresceu em bairro perigoso. Perícias: Luta e Reflexos. Poder: Mão Pesada — recebe +2 em rolagens de dano com ataques corpo a corpo.',
 1),
('Magnata',
 'Herdeiro de família, fundador de empresa ou ganhador de loteria com números amaldiçoados. Possui muito dinheiro. Perícias: Diplomacia e Pilotagem. Poder: Patrocinador da Ordem — seu limite de crédito é sempre considerado um acima do atual.',
 1),
('Mercenário',
 'Soldado de aluguel, que trabalha sozinho ou para organização que vende serviços militares. Perícias: Iniciativa e Intimidação. Poder: Posição de Combate — no primeiro turno de cada cena de ação, pode gastar 2 PE para receber uma ação de movimento adicional.',
 1),
('Militar',
 'Serviu em força militar — exército ou marinha. Acostumado a obedecer ordens e partir em missões. Perícias: Pontaria e Tática. Poder: Para Bellum — recebe +2 em rolagens de dano com armas de fogo.',
 1),
('Operário',
 'Pedreiro, industriário ou operador de máquinas. Sua rotina mundana foi confrontada pelo paranormal. Perícias: Fortitude e Profissão. Poder: Ferramenta de Trabalho — escolha uma arma simples ou tática usável como ferramenta profissional; recebe +1 em ataques, dano e margem de ameaça com ela.',
 1),
('Religioso',
 'Devoto ou sacerdote de uma fé, dedicando-se a auxiliar pessoas com problemas espirituais. Perícias: Religião e Vontade. Poder: Acalentar — recebe +5 em testes de Religião para acalmar; quando acalma uma pessoa, ela recupera 1d6 + sua Presença em Sanidade.',
 1),
('Servidor Público',
 'Carreira em órgão do governo. Sua rotina foi quebrada quando viu que representantes do povo realizavam rituais. Perícias: Intuição e Vontade. Poder: Espírito Cívico — ao ajudar alguém, pode gastar 1 PE para aumentar o bônus concedido em +2.',
 1),
('Teórico da Conspiração',
 'A Terra é plana. Reptilianos governam o mundo. Você investigou tudo isso e quando sua pesquisa esbarrou no paranormal, foi recrutado. Perícias: Investigação e Ocultismo. Poder: Eu Já Sabia — recebe resistência a dano mental igual ao seu Intelecto.',
 1),
('T.I.',
 'Programador, engenheiro de software ou "o cara da T.I.". Seu talento com sistemas informatizados chamou a atenção da Ordem. Perícias: Investigação e Tecnologia. Poder: Motor de Busca — com acesso à internet, pode gastar 2 PE para substituir um teste de perícia qualquer por Tecnologia.',
 1),
('Trabalhador Rural',
 'Fazendeiro, pescador, biólogo ou veterinário. Acostumado ao convívio com a natureza, descobriu que muitas histórias de fantasmas são verdadeiras. Perícias: Adestramento e Sobrevivência. Poder: Desbravador — pode gastar 2 PE para receber +5 em Adestramento ou Sobrevivência; não sofre penalidade em deslocamento por terreno difícil.',
 1),
('Trambiqueiro',
 'Uma vida digna exige muito trabalho, então é melhor nem tentar. Vivia de pequenos golpes e jogatina até enganar a pessoa errada. Perícias: Crime e Enganação. Poder: Impostor — uma vez por cena, pode gastar 2 PE para substituir um teste de perícia qualquer por Enganação.',
 1),
('Universitário',
 'Aluno de faculdade. Descobriu algo — talvez um livro amaldiçoado na biblioteca — e foi convocado pela Ordem. Perícias: Atualidades e Investigação. Poder: Dedicação — recebe +1 PE e mais 1 PE adicional a cada NEX ímpar (15%, 25%...); limite de PE por turno aumenta em 1.',
 1),
('Vítima',
 'Em algum momento você encontrou o paranormal e a experiência foi traumática — viu espíritos, foi atacado ou sequestrado para um ritual. Decidiu lutar para impedir que outros inocentes passem pelo mesmo. Perícias: Reflexos e Vontade. Poder: Cicatrizes Psicológicas — recebe +1 de Sanidade para cada 5% de NEX.',
 1);


-- ============================================================
-- CLASSES (tb_classe)
-- Adiciona a classe opcional Mundano
-- ============================================================

INSERT INTO tb_classe (nm_classe, ds_descricao, ds_habilidade_primaria, qt_nivel_maximo, id_sistema) VALUES
('Mundano',
 'Uma pessoa comum, ainda sem treinamento da Ordem. Usada em campanhas com personagens de NEX 0% que ainda não tiveram contato com o paranormal. Focada em investigação, suspense e terror, pois pessoas comuns dificilmente sobrevivem a um combate direto com criaturas paranormais.',
 'Empenho: ao fazer um teste de perícia, pode gastar 1 PE para receber +2 nesse teste.',
 20, 1);


-- ============================================================
-- PERÍCIAS (tb_pericia)
-- Complementa as 9 perícias do db_table.sql
-- ============================================================

INSERT INTO tb_pericia (nm_pericia, ds_atributo_base, id_sistema) VALUES
('Acrobacia',    'Agilidade',  1),
('Adestramento', 'Presença',   1),
('Artes',        'Presença',   1),
('Atualidades',  'Intelecto',  1),
('Ciências',     'Intelecto',  1),
('Crime',        'Agilidade',  1),
('Enganação',    'Presença',   1),
('Iniciativa',   'Agilidade',  1),
('Intuição',     'Presença',   1),
('Medicina',     'Intelecto',  1),
('Percepção',    'Presença',   1),
('Pilotagem',    'Agilidade',  1),
('Profissão',    'Intelecto',  1),
('Reflexos',     'Agilidade',  1),
('Religião',     'Presença',   1),
('Sobrevivência','Intelecto',  1),
('Tática',       'Intelecto',  1),
('Tecnologia',   'Intelecto',  1),
('Vontade',      'Presença',   1);


-- ============================================================
-- CRIATURAS PARANORMAIS DE SANGUE (tb_monstro)
-- IDs 1–5 já existem. Inserindo os restantes a partir do 7.
-- ============================================================
-- Criaturas de SANGUE já no db_table.sql:
--   1 = O Diabo (Sangue/Conhecimento, Relíquia, VD 400)
--   2 = Aberração de Carne (Sangue, Grande, VD 40)
--   3 = Aniquilação (Sangue, Colossal, VD 380) — Enigma de Medo
--   4 = Carente (Sangue/Morte, Grande, VD 300) — Enigma de Medo
--   5 = Dama de Sangue (Sangue/Morte/Medo, Enorme, VD 60) — Enigma de Medo
-- Faltam: Enpap-X, Kerberos, Minotauro, Mulher Afogada,
--         Titã de Sangue, Zumbi de Sangue, Zumbi de Sangue Bestial
-- ============================================================

INSERT INTO tb_monstro (nm_monstro, ds_monstro, tp_monstro, ds_imagem, qt_vida, qt_defesa, qt_xp_recompensa, qt_vd, id_sistema) VALUES

('Enpap-X',
 'Originada da dor e tortura de um prisioneiro de guerra sumério forçado a marcar em sua pele os feitos terríveis de seu soberano. Começa como um Existido e, ao ser reduzido a 0 PV, se transforma: cresce para uma monstruosidade enorme de quatro braços movida por vingança. Usa correntes paranormais para agarrar e estrangular alvos à distância. Elementos: Sangue e Conhecimento.',
 'Criatura - Grande', '../img/logo_icone.png', 360, 36, 40, 180, 1),

('Kerberos',
 'O cão de três cabeças, o guardião do portão do inferno. Uma besta enorme com mais de três metros de altura, seis patas, espinhos e veias saltadas por toda a pele vermelha gosmenta, e três cabeças com bocas asquerosas. Só se manifesta em "Infernos" — áreas paranormais tomadas pelo Sangue com a Membrana devastada. Guardião de uma importante entrada, nunca age de forma estratégica, apenas protegendo um ponto específico.',
 'Criatura - Enorme', '../img/logo_icone.png', 1150, 46, 60, 340, 1),

('Minotauro',
 'Um animal enorme e bípede com mais de três metros, infectado com pústulas de Sangue e veias pulsantes. Seu braço esquerdo tem um machado fundido nos ossos com lâmina dupla. Realiza investidas brutais e usa seu instinto bestial para navegar em labirintos, caçando vítimas para destroçar e devorar. Uma das primeiras criaturas mantidas em cativeiro para estudo paranormal.',
 'Criatura - Grande', '../img/logo_icone.png', 750, 44, 55, 280, 1),

('Mulher Afogada',
 'Invocação associada a mulheres que morreram em afogamentos brutais. Na forma líquida, move-se por encanamentos como Sangue avermelhado. Criatura de Medo: apenas confronto direto não basta — a origem deve ser investigada, todas as saídas de água bloqueadas e o registro hidráulico fechado para forçá-la a se manifestar fisicamente. Elementos: Sangue e Energia.',
 'Criatura - Grande', '../img/logo_icone.png', 240, 28, 30, 140, 1),

('Titã de Sangue',
 'A maior versão já encontrada de um zumbi de Sangue — uma criatura monstruosa com mais de quatro metros de altura. Uma massa de carne e Sangue endurecida e musculosa, com veias saltadas e quatro gigantescas quelíceras capazes de desfigurar qualquer alvo. Manifesta-se apenas em locais onde a membrana foi debilitada por carnificinas terríveis.',
 'Criatura - Enorme', '../img/logo_icone.png', 550, 35, 45, 220, 1),

('Zumbi de Sangue',
 'Cadáveres mortos de forma brutal abandonados em uma área servem como passagem para o Sangue devorá-los e tomar controle. A pele se transforma em material gosmento e vermelho, os ossos viram carne pura, os olhos são destruídos, os dentes crescem pontudos e as unhas se estendem em garras. São cegos — detectam presenças pela movimentação da corrente de ar através de sua pele exposta e sensível.',
 'Criatura - Médio', '../img/logo_icone.png', 45, 17, 5, 20, 1),

('Zumbi de Sangue Bestial',
 'Uma versão maior, mais forte e brutal do zumbi de Sangue. Resultado de cadáver torturado brutalmente ou de alguém com alta exposição paranormal. A massa corporal cresce até o triplo, o corpo assume forma quadrúpede animalesca e o crânio é partido ao meio formando uma bocarra capaz de decapitar em uma mordida. Ao contrário de sua versão inferior, age com instintos predatórios, se escondendo para atacar de surpresa.',
 'Criatura - Grande', '../img/logo_icone.png', 200, 23, 15, 100, 1);


-- ============================================================
-- CRIATURAS PARANORMAIS DE MORTE (tb_monstro)
-- ============================================================

INSERT INTO tb_monstro (nm_monstro, ds_monstro, tp_monstro, ds_imagem, qt_vida, qt_defesa, qt_xp_recompensa, qt_vd, id_sistema) VALUES

('Aracnasita',
 'Também chamada de aranha preta da Morte. Originada de uma criatura da Realidade exposta a um símbolo de invocação de Morte. Em estado minúsculo, surpreende vítimas para se agarrar ao rosto e as manter desacordadas. Depois absorve o corpo inteiro em um casulo no abdômen, crescendo e se reproduzindo em cópias menores. Criatura de Medo: imune a todo dano exceto fogo; fogo temporariamente remove sua imunidade.',
 'Criatura - Grande', '../img/logo_icone.png', 140, 23, 20, 80, 1),

('Carniçal Preto da Morte',
 'Originado de uma tentativa fracassada de dar consciência à entidade da Morte. Formado de Lodo entrelaçado a partir de um crânio humano apodrecido, com sigilos de Conhecimento que replicam a anatomia humana exposta. Age com estratégia, usa hipnose para controlar até três seres simultaneamente e reanima corpos próximos. Fica cada vez mais irracional conforme é machucado. Imune a dano balístico (exceto tiro na cabeça). Elementos: Morte e Conhecimento.',
 'Criatura - Médio', '../img/logo_icone.png', 400, 38, 50, 200, 1),

('Ceifador Espiral',
 'Uma das criaturas de Morte mais poderosas registradas. Aqueles que sobreviveram a experiências de quase morte descrevem ter visto uma silhueta que se tornava cada vez mais definida. Empunha uma foice paranormal capaz de decepar alvos reduzindo-os a 25 PV instantaneamente. Cria ao seu redor uma área de Cinzas das Terras Desoladas onde tudo é transformado em pó. Criatura de Medo: resistência a dano 50.',
 'Criatura - Grande', '../img/logo_icone.png', 999, 58, 80, 380, 1),

('Enraizado',
 'Resultado de um corpo enterrado próximo à vegetação infestada pela Morte, invadido por raízes grossas e preenchido com Lodo que movimenta seus membros reforçados. Possui Imortalidade: ao morrer, se desfaz em uma poça de Lodo e retorna em 1d2 rodadas com 70 PV. Só é permanentemente destruído com 20 pontos de dano de fogo ou Energia enquanto em forma de poça. Veneno pútrido em seus ataques.',
 'Criatura - Médio', '../img/logo_icone.png', 140, 28, 15, 120, 1),

('Escutado',
 'Originado de alguém que ouviu a "Melodia Espiral" — uma música paranormal proibida. Corpo humanoide retorcido e magro que anda de forma quadrúpede e invertida, com uma cabeça capaz de se desprender do corpo, deixando rastro de Lodo vomitado. Criatura de Medo: imune a todo dano. Ao ouvir sua melodia de criação, se multiplica a cada turno. Depois de 4 rodadas ininterruptas ouvindo a melodia, perde a imunidade. Elementos: Morte e Energia.',
 'Criatura - Médio', '../img/logo_icone.png', 290, 29, 25, 160, 1),

('Esqueleto de Lodo',
 'Um cadáver consumido pela Morte, tomando a forma de um esqueleto completamente cinzento com Lodo escorrendo por todos os orifícios. Sons bizarros saem de dentro do crânio — palavras sem sentido e rugidos invertidos. Possui Imortalidade: ao morrer, se desfaz em uma poça de Lodo e ossos, retornando em 1d3 rodadas com 20 PV. Dano de fogo ou Energia enquanto na forma de poça o destrói permanentemente.',
 'Criatura - Médio', '../img/logo_icone.png', 40, 14, 5, 20, 1),

('Marionete',
 'O resquício de uma memória distorcida por um acontecimento terrível — a tentativa desesperada de trazer alguém de volta dos mortos. Esqueleto retorcido preenchido por Lodo, com um fio forçando a mandíbula aberta e braços erguidos como se pendurados por força invisível. No topo, uma foice de ossos com a ponta tocando o chão. Seu deslocamento nunca é reduzido e ignora terreno difícil e dano que dependa de toque no chão.',
 'Criatura - Médio', '../img/logo_icone.png', 700, 40, 55, 280, 1),

('Múmia Xipófaga',
 'Obsessão milenar pela vida eterna que, através de cultos ao Outro Lado, gerou resultados macabros. Múmias entrelaçadas de dois corpos fundidos ainda vivos. Aqueles absorvidos e reduzidos a 0 PV tornam-se esqueletos de Lodo. Ao chegar a 0 PV, não morre imediatamente — pode continuar agindo e absorver corpos mortos ou criaturas de Morte adjacentes para se amalgamar e recuperar PV.',
 'Criatura - Médio', '../img/logo_icone.png', 400, 35, 40, 240, 1),

('Nidere',
 'O lobo invertido — manifesta-se somente em ambientes selvagens, em condições extremamente específicas. Similar a um enorme lobo atroz e desfigurado, com corpo retorcido em forma bizarra. Olhos vermelhos refletindo na escuridão e rosnados monstruosos distorcidos. Considerado o maior responsável por desaparecimentos de grupos de campistas. Criatura de Medo: possui Cura Acelerada 50 e nunca se perde. Elementos: Morte e Sangue.',
 'Criatura - Grande', '../img/logo_icone.png', 800, 50, 60, 320, 1),

('Sempiternal',
 'Resultado da manifestação da Morte através de uma evolução gradual passada por inúmeras gerações de uma civilização isolada. Uma figura esquelética bizarra e retorcida com olhos pretos e pele acinzentada que emite sons invertidos. Seu toque envelhece as vítimas 1d10 anos — quem envelhecer 60 anos ou mais morre. Imune a condições de paralisia e a dano e efeitos de Morte.',
 'Criatura - Médio', '../img/logo_icone.png', 990, 53, 65, 360, 1),

('Succ',
 'Um ser quadrúpede com patas pontudas, pele enrugada e acinzentada, dois buracos onde deveriam estar seus pulmões, um longo pescoço invertebrado que leva a uma enorme boca circular com vários dentes em camadas internas. Busca apenas sugar todo o ar de dentro dos pulmões de sua vítima. O som emitido pela sucção constante é facilmente confundido com o de um aspirador de pó. Elementos: Morte e Energia.',
 'Criatura - Médio', '../img/logo_icone.png', 65, 20, 10, 40, 1),

('O Deus da Morte',
 'Entidade suprema também conhecida como Parasita de Dimensões. Manifesta-se como forma disforme que infecta o ambiente consumindo a entropia de tudo que é vivo. Pode parasitar o cadáver de alguém com alto NEX dedicado à Morte para assumir forma física. Possui Ciclo Infinito: recupera 50 PV por turno e ao ser reduzido a 0 PV se transforma em Lodo e retorna no próximo turno no cadáver mais próximo. A única coisa capaz de resolver o Enigma de Medo do Diabo. Elementos: Morte e Conhecimento.',
 'Relíquia - Grande', '../img/logo_icone.png', 2000, 60, 80, 400, 1);


-- ============================================================
-- CRIATURAS PARANORMAIS DE CONHECIMENTO (tb_monstro)
-- ============================================================

INSERT INTO tb_monstro (nm_monstro, ds_monstro, tp_monstro, ds_imagem, qt_vida, qt_defesa, qt_xp_recompensa, qt_vd, id_sistema) VALUES

('Anjo',
 'Poucos encontros paranormais têm mais impacto do que a visita de um anjo. Uma criatura tão poderosa que desmantelei a sanidade de uma pessoa apenas ao ser observada — derretendo os olhos, que escorrem como lágrimas douradas. Descrita como "o rosto da verdade impossível". Voa em alta velocidade, garra alvos com as faixas de suas asas e dispara raios dourados que colapsam mentes. Criatura de Medo: resistência a dano 50.',
 'Criatura - Enorme', '../img/logo_icone.png', 1111, 57, 70, 380, 1),

('Bicho-Papão',
 'Originado do Medo gerado por tantas crianças, o bicho-papão é uma figura encapuzada e alongada similar a uma centopeia de braços e pernas humanoides. Se esconde em telhados e dutos de ventilação, comunicando-se com vítimas através de sussurros durante o sono, implantando medos e alucinações. Fica desprevenido ao ouvir cantiga de ninar e enfurecido ao ouvir choro de criança. Criatura de Medo.',
 'Criatura - Grande', '../img/logo_icone.png', 750, 41, 55, 300, 1),

('Espreitador',
 'Uma forma asquerosa e curvada com dezenas de olhos de diferentes tamanhos com pupilas amarelas que se multiplicam. Escolhe um alvo para assombrar e devora sua sanidade impedindo o sono. Criatura de Medo: imune a todo dano quando não encurralado. Para encurralar: o alvo deve simular dormir às 2h11, e quando o espreitador sair de seu esconderijo, a porta deve ser fechada antes que ele retorne, privando-o de sua imunidade.',
 'Criatura - Médio', '../img/logo_icone.png', 500, 34, 50, 220, 1),

('Estrangeiro',
 'Uma manifestação paranormal muitíssimo inteligente com motivações complexas e misteriosas. Parece vir de uma civilização superior com estrutura hierárquica própria, sempre associado a possibilidades de alienígenas extraterrestres. Capaz de aprender novas linguagens. Abduz vítimas, lê seus pensamentos e coloca larvas que eclodem consumindo a Sanidade do hospedeiro, criando um novo Estrangeiro. Criatura de Medo: imune a todo dano. Elementos: Conhecimento e Energia.',
 'Criatura - Grande', '../img/logo_icone.png', 750, 50, 60, 340, 1),

('Existido',
 'Uma vez humano, hoje apenas uma casca buscando desesperadamente existir. Alguém que foi longe demais, ultrapassou a barreira do Conhecimento e entendeu o Outro Lado por completo. Tudo que pode fazer é continuar repetindo seu próprio nome. Textos ao redor de seu corpo brilham em dourado quando ameaçado, causando dano mental em todos próximos.',
 'Criatura - Médio', '../img/logo_icone.png', 36, 13, 5, 20, 1),

('Lembrado',
 'Uma manifestação amplificada do existido, gerada quando a barreira mental de alguém com alta exposição paranormal se quebra completamente. Cercado por uma aura dourada de faces flutuantes que gritam com todos que se aproximam. Grita o nome que lhe foi entregue pelo Outro Lado. Sua aura causa –2d de penalidade em todos os testes de personagens em alcance curto.',
 'Criatura - Médio', '../img/logo_icone.png', 180, 22, 15, 100, 1),

('Ocioso',
 'Uma criatura que aparenta ter vontade própria nem realizar ações independentes. O alvo é o único ser que consegue vê-lo — para todos os demais, é invisível. Vítimas desenvolvem comportamento claustrofóbico. Nunca ataca ativamente, mas se agredido reage com força imobilizante. Muitas vítimas são encontradas mortas sós, com pernas ou colunas quebradas, sem poder se movimentar, observadas pelo ocioso até o fim de suas vidas.',
 'Criatura - Grande', '../img/logo_icone.png', 390, 37, 40, 260, 1),

('Parasita de Culpa',
 'Uma criatura disforme que se alimenta da culpa de suas vítimas através de pesadelos e ilusões. Fixa-se em um ser dormindo e cria um sonho compartilhado com todos que estejam em alcance médio. Dentro do sonho, manifesta cópias das vítimas para atormentá-las. Criatura de Medo: imune a todo dano exceto o causado pelo próprio hospedeiro. Elementos: Conhecimento, Sangue e Morte.',
 'Criatura - Médio', '../img/logo_icone.png', 90, 15, 15, 60, 1),

('Rastejador Sombrio',
 'Uma forma humanoide acinzentada trajando sobretudo e chapéu, com uma enorme boca que se abre no rosto usada para se camuflar nas sombras. Dentro das roupas, tentáculos que tomam aspecto de sombra. Age de forma sádica, escolhendo sempre causar a maior quantidade de dor possível. Vulnerável à luz direta forte: perde Defesa e habilidades de sombra. Elementos: Conhecimento e Sangue.',
 'Criatura - Médio', '../img/logo_icone.png', 330, 41, 30, 180, 1),

('Silhueta',
 'A ausência consciente de si mesma. Alguém que foi inexistido pelo Conhecimento, mas perdura na Realidade através do eco de suas memórias. Forma humanoide vazia cercada por sigilos flutuantes que reescrevem a Realidade a cada instante. Tudo que a toca sofre 20d12 de dano de Conhecimento e, se reduzido a 0 PV, é instantaneamente desintegrado. Imune a condições de paralisia, dano e efeitos de Conhecimento.',
 'Criatura - Médio', '../img/logo_icone.png', 500, 55, 55, 360, 1),

('Vulto',
 'Em ambientes com a Membrana danificada, a paranoia de um observador assustado pode criar uma criatura que não estava lá. O vulto é uma manifestação paranormal que toma forma através do susto de uma possibilidade, aparecendo como uma criatura humanoide formada de névoa sólida. Busca pessoas assustadas para se alimentar — seus ataques contra alvos com condição de medo causam dano extra.',
 'Criatura - Médio', '../img/logo_icone.png', 60, 19, 8, 40, 1),

('Máscara do Desespero',
 'Também conhecida como Relíquia do Conhecimento — uma das manifestações mais antigas na Realidade, possivelmente existente antes da civilização humana. Uma máscara indestrutível que contém toda a verdade do Outro Lado. Aquele que a porta tem a mente soterrada pelo Conhecimento infinito e seu ego é inexistido, tornando-se a Magistrada. Imune a todas as condições e dano. A única forma de ser enfrentada é pelo Diabo ou por quem quebre as regras da Realidade através do Medo.',
 'Relíquia - Minúsculo', '../img/logo_icone.png', 1200, 55, 80, 400, 1);


-- ============================================================
-- CRIATURAS PARANORMAIS DE ENERGIA (tb_monstro)
-- ============================================================

INSERT INTO tb_monstro (nm_monstro, ds_monstro, tp_monstro, ds_imagem, qt_vida, qt_defesa, qt_xp_recompensa, qt_vd, id_sistema) VALUES

('Anárquico',
 'Quando uma pessoa morre em situações extremamente azaradas em ambiente com a Membrana danificada, o cadáver é consumido pela Energia. A pele toma aspecto semitransparente com veias coloridas e brilhosas. Age de modo completamente aleatório: no começo do seu turno, rola 1d6 para determinar sua ação. Rápido e imprevisível, com movimentos erráticos.',
 'Criatura - Médio', '../img/logo_icone.png', 30, 21, 5, 20, 1),

('Anárquico Descontrolado',
 'Quando alguém com altíssima exposição paranormal se tornaria um anárquico, o caos se multiplica infinitamente, distorcendo por completo as noções de regras e razão. Forma translúcida completamente alucinada, vibrando e flutuando em euforia. Executa três comportamentos aleatórios por turno (rola 1d6 três vezes). Pode se autodestruir causando 8d12 de Energia em alcance curto — morre após usar esta habilidade.',
 'Criatura - Médio', '../img/logo_icone.png', 120, 28, 20, 120, 1),

('Anomalia',
 'Existe apenas dentro de um objeto que possa ser aberto por uma porta. Uma vez manifestada, persegue aqueles que a manifestaram, surgindo sempre que abrirem uma porta ou compartimento. Sua forma não é contida por barreiras físicas compreensíveis. Criatura de Medo: imune a dano e todas as condições. A única forma de derrotá-la é resolvendo seu Enigma de Medo — mergulhando nela para entendê-la no Outro Lado.',
 'Criatura - Médio', '../img/logo_icone.png', 1000, 0, 70, 380, 1),

('Anomiático',
 'Quando alguém com exposição paranormal extremamente alta se tornaria um anárquico descontrolado, o caos se multiplica ao infinito. Um espectro da loucura que se teleporta de forma desenfreada por todo o ambiente, rindo caoticamente. Executa três comportamentos aleatórios (1d6 três vezes). Para cada ação que não consegue executar, aumenta suas resistências a dano em 10 até o próximo turno.',
 'Criatura - Médio', '../img/logo_icone.png', 600, 41, 45, 240, 1),

('Ciborgue',
 'Um cientista seduzido pela tecnologia que amputou seus próprios membros e os substituiu por mecanismos ativados por Energia paranormal. O processo agressivo distorceu seu ser em uma criatura descontrolada com partes mecânicas e orgânicas. Possui quatro estados de combate (Alpha, Beta, Gama, Delta) e Regeneração Energética de 20 PV por turno. Criatura de Medo: cada estado possui uma fraqueza específica. Elementos: Energia e Sangue.',
 'Criatura - Grande', '../img/logo_icone.png', 160, 25, 20, 80, 1),

('Infecticídio',
 'Uma doença paranormal que se origina como vírus digital dentro de dispositivos eletrônicos. Infecta seres vivos da Realidade, distorcendo suas formas físicas e formando uma horda descontrolada com mente ensandecida e compartilhada. Os infectados têm olhos manchados com cores pulsantes e dentes pontudos e brilhosos. É uma horda: sofre metade do dano de ataques individuais, mas o dobro de efeitos de área. Apenas eliminando todos os infectados e congelando seus corpos o vírus pode ser erradicado. Elementos: Energia e Sangue.',
 'Criatura - Enorme', '../img/logo_icone.png', 600, 25, 50, 280, 1),

('Perturbado de Energia',
 'Quando uma alma é enlouquecida de forma brusca e agressiva, sua psique pode ser tão violentada que a percepção da Realidade se esvai antes de perceber que não está viva. Uma forma plasmática inconsistente e desesperada. Se agarra a qualquer consciência próxima implantando imagens de memórias traumáticas na mente da vítima, causando vulnerabilidade a Energia.',
 'Criatura - Médio', '../img/logo_icone.png', 60, 19, 8, 40, 1),

('Sukkalgir',
 'Uma alma torturada através do fogo. Originada da antiga Suméria como resultado de textos cravados com brasa ardente na pele de vítimas. Um grito enlouquecedor e constante capaz de desmantelar até as mentes mais acostumadas com o paranormal. Corpo formado de Energia com aspecto de labareda de cores impossíveis, pairando sobre o chão com as marcas macabras flutuando pela pele. Parcialmente intangível — atravessa paredes. Elementos: Energia e Conhecimento.',
 'Criatura - Médio', '../img/logo_icone.png', 220, 34, 30, 160, 1),

('Telopsia',
 'A lenda da fita VHS amaldiçoada. Um homem bizarro vestindo um longo sobretudo preto, com corpo esquelético e cabeça similar a uma televisão antiga com imagens perturbadoras e enlouquecedoras na "tela". Todos que assistiram ao filme são encontrados pela criatura, que os assiste enquanto desintegra suas formas. Deixa apenas uma mancha preta no formato de silhueta onde a vítima morreu. Criatura de Medo: ressurge se a fita não for destruída. Elementos: Energia e Morte.',
 'Criatura - Médio', '../img/logo_icone.png', 560, 48, 55, 340, 1),

('Tempestuoso',
 'Uma tempestade do caos — uma nuvem de pura Energia como uma supernova implodindo em forma humanoide, em constante transformação, contida por cabos metálicos incompreensíveis. Encontros são descritos como sons de raios violentos em tempestade enfurecida. Só pode ser originado em ambiente com a Membrana extremamente danificada. Emite radiação paranormal ao redor que perdura muito tempo após a batalha.',
 'Criatura - Médio', '../img/logo_icone.png', 950, 56, 70, 360, 1),

('Viajante',
 'Uma criatura maligna e cruel com corpo esbranquiçado, membros alongados e uma cabeça com incontáveis rostos terríveis mesclados. Fisicamente invisível, capaz de andar em superfícies como paredes e tetos. Distorce lentamente as memórias de um alvo através de fotografias. Após gerar traumas suficientes, manifesta-se fisicamente causando amnésia temporária. Criatura de Medo: pode ser visto apenas por dispositivos de captura de imagem. Elementos: Energia e Conhecimento.',
 'Criatura - Médio', '../img/logo_icone.png', 360, 34, 40, 200, 1),

('Anfitrião',
 'Personificação do caos e da irracionalidade. No Ato 1, divide-se em 5 facetas separadas (Amphitruo, Aeneas, Liber, Silenus e Plautus), cada uma com 250 PV e habilidades únicas. No Ato 2, retorna à forma única com todos os PV, podendo usar todas as habilidades e executar 3 ações padrão diferentes por rodada. A única coisa capaz de resolver o Enigma de Medo do Deus da Morte. Criatura de Medo: imune a dano e efeitos de Energia. Elementos: Energia e Conhecimento.',
 'Criatura - Médio', '../img/logo_icone.png', 1413, 59, 80, 400, 1),

('Degolificada',
 'Uma das criaturas paranormais mais temidas. Figura humanoide flutuante com corpo retorcido, vestes rasgadas e pele cinzenta. Longos cabelos pretos tampam o rosto e se arrastam pelo chão com vontade própria. Olhos ausentes revelam dois buracos vazios e a boca parece costurada pela pele. Carrega neblina com rostos atormentados ao seu redor. Criatura de Medo: imune a todo dano até que o mistério de sua origem seja confrontado com a causa de sua morte. Pode se metamorfosear em 4 formas: Devoradora (Sangue), Decrépita (Morte), Conturbada (Energia) e Gnóstica (Conhecimento).',
 'Criatura - Médio', '../img/logo_icone.png', 850, 45, 80, 320, 1);


-- ============================================================
-- AMEAÇAS MUNDANAS — CRIMINOSOS & MERCENÁRIOS (tb_monstro)
-- ============================================================

INSERT INTO tb_monstro (nm_monstro, ds_monstro, tp_monstro, ds_imagem, qt_vida, qt_defesa, qt_xp_recompensa, qt_vd, id_sistema) VALUES

('Bandido',
 'Um criminoso típico, como um ladrão ou assaltante. Usa faca e seu instinto furtivo. Habilidade: Ataque Furtivo — uma vez por rodada causa +1d6 pontos de dano com ataques corpo a corpo, ou à distância em alcance curto, contra alvos desprevenidos ou flanqueados.',
 'Pessoa - Médio', '../img/logo_icone.png', 8, 14, 3, 10, 1),

('Capanga',
 'Pessoas embrutecidas que vivem pela violência. Pode representar membros de gangue, executores da máfia e leões de chácara. Usa bastão e revólver. Habilidade: Ataque Furtivo — uma vez por rodada causa +2d6 de dano contra alvos desprevenidos ou flanqueados.',
 'Pessoa - Médio', '../img/logo_icone.png', 17, 13, 5, 20, 1),

('Soldado de Aluguel',
 'Um combatente profissional, que trabalha para quem pagar mais. Usa machete e fuzil de assalto. Habilidade: Ataque em Movimento — pode percorrer seu deslocamento e atacar em qualquer ponto durante o movimento.',
 'Pessoa - Médio', '../img/logo_icone.png', 25, 18, 8, 40, 1),

('Assassino',
 'Um matador habilidoso e furtivo, que surge quando ameaças da Realidade precisam eliminar alguém de forma discreta e eficiente. Usa faca e pistola. Habilidades: Evasão, Ataque Furtivo (+4d6), Mão na Boca (impede gritos) e Assassinar (dobra dados de dano extra do Ataque Furtivo no próximo turno).',
 'Pessoa - Médio', '../img/logo_icone.png', 90, 26, 20, 80, 1),

('Comandante Mercenário',
 'Um homem ou mulher endurecido por anos de conflitos. Tanto um oficial competente, capaz de liderar subordinados, quanto um combatente perigoso por si só. Usa machete e metralhadora. Habilidades: Sadismo (ataque seguinte recebe bônus após causar dano), Ataque em Movimento e Ordens (aliados em alcance médio recebem bônus até o fim da cena).',
 'Pessoa - Médio', '../img/logo_icone.png', 145, 29, 30, 120, 1);


-- ============================================================
-- AMEAÇAS MUNDANAS — CULTISTAS (tb_monstro)
-- ============================================================

INSERT INTO tb_monstro (nm_monstro, ds_monstro, tp_monstro, ds_imagem, qt_vida, qt_defesa, qt_xp_recompensa, qt_vd, id_sistema) VALUES

('Iniciado Cultista',
 'Ainda que seja um iniciado no caminho da adoração às entidades, já é capaz de conjurar rituais. Habilidade: Conjurador — escolhe 2 rituais de 1º círculo de um elemento e pode conjurá-los sem pagar PE, até 3 PE por conjuração. DT para resistir: 15.',
 'Pessoa - Médio', '../img/logo_icone.png', 15, 16, 5, 20, 1),

('Cultista Investido',
 'Tendo provado sua lealdade ao Elemento, o cultista investido é um perigo para a Realidade. Habilidade: Conjurador — escolhe 2 rituais de 1º círculo e 2 de 2º círculo de até dois elementos. Conjura sem pagar PE, até 5 PE por conjuração. DT para resistir: 17.',
 'Pessoa - Médio', '../img/logo_icone.png', 35, 17, 10, 40, 1),

('Líder de Culto',
 'Experiente e capaz de conjurar rituais mais poderosos. Mantém habilmente seu disfarce de bom cidadão — pode ser qualquer um, até mesmo alguém muito próximo dos agentes. Habilidade: Conjurador — escolhe 2 rituais de 1º, 2 de 2º e 2 de 3º círculo de até dois elementos. Conjura sem pagar PE, até 10 PE por conjuração. DT para resistir: 25.',
 'Pessoa - Médio', '../img/logo_icone.png', 150, 27, 35, 140, 1);


-- ============================================================
-- AMEAÇAS MUNDANAS — POLICIAIS (tb_monstro)
-- ============================================================

INSERT INTO tb_monstro (nm_monstro, ds_monstro, tp_monstro, ds_imagem, qt_vida, qt_defesa, qt_xp_recompensa, qt_vd, id_sistema) VALUES

('Policial',
 'O policial padrão, patrulhando ruas e praças. Provavelmente nunca teve contato com o paranormal e vai considerar qualquer menção a monstros uma brincadeira de mau gosto. Esta ficha pode ser usada para vigias, seguranças corporativos e pessoas com algum treinamento com armas em geral.',
 'Pessoa - Médio', '../img/logo_icone.png', 15, 19, 5, 20, 1),

('Policial de Elite',
 'Treinado e equipado para enfrentar situações extremas. Provavelmente o primeiro a aparecer quando uma investigação discreta se transformar em confronto armado. Usa fuzil de assalto e lança-granadas. Habilidade: Fortificação — 50% de chance de ignorar o dano adicional de um acerto crítico ou ataque furtivo. Habilidade: Empurrar e Atirar.',
 'Pessoa - Médio', '../img/logo_icone.png', 40, 27, 15, 60, 1),

('Chefe de Polícia',
 'Um delegado ou coronel, que já passou por situações difíceis e não se intimida facilmente. Usa bastão e espingarda. Habilidade: Teimoso — uma vez por cena pode ignorar um efeito que exija teste de resistência ou reduzir um dano recém sofrido à metade.',
 'Pessoa - Médio', '../img/logo_icone.png', 105, 25, 25, 100, 1);


-- ============================================================
-- AMEAÇAS MUNDANAS — ANIMAIS (tb_monstro)
-- ============================================================

INSERT INTO tb_monstro (nm_monstro, ds_monstro, tp_monstro, ds_imagem, qt_vida, qt_defesa, qt_xp_recompensa, qt_vd, id_sistema) VALUES

('Cão de Guarda',
 'Cães treinados para guarda podem causar problemas para grupos que precisam ser furtivos. Possui faro e visão na penumbra. Habilidade: Derrubar — se acertar um ataque de mordida, pode fazer a manobra derrubar. Esta ficha pode representar cães policiais e lobos.',
 'Animal - Médio', '../img/logo_icone.png', 12, 14, 3, 10, 1),

('Enxame de Abelhas',
 'Normalmente pacíficas, podem se tornar agressivas se a colmeia for ameaçada. Encontradas em zonas rurais e casas abandonadas. Como enxame: imune a manobras de combate e efeitos que afetam apenas um ser. Sofre apenas metade do dano de ataques com armas, mas 50% a mais de efeitos de área. Habilidade: Zumbido Nauseante — ser que sofra dano fica enjoado por 1 rodada (Fortitude DT 15 evita).',
 'Animal - Médio (Enxame)', '../img/logo_icone.png', 10, 15, 2, 10, 1),

('Enxame de Ratos',
 'Tímidos normalmente, podem se unir em enxames perigosos movidos por fome intensa ou energias paranormais. Como enxame: imune a manobras de combate e efeitos que afetam apenas um ser. Sofre apenas metade do dano de ataques com armas, mas 50% a mais de efeitos de área. Habilidade: Doença — ser que sofra dano contrai febre hemorrágica (Fortitude DT 15 evita).',
 'Animal - Médio (Enxame)', '../img/logo_icone.png', 15, 13, 2, 10, 1),

('Jacaré',
 'Existem diversas espécies de jacarés, algumas encontradas até em áreas urbanas. Este espécime grande pode ser perigoso para agentes. Possui visão na penumbra. Habilidade: Agarrão — se acertar mordida em ser Médio ou menor, pode tentar agarrar. Habilidade: Giro da Morte — se estiver agarrando um ser dentro da água e fizer a manobra agarrar novamente para causar dano, causa +2d8 pontos de dano.',
 'Animal - Grande', '../img/logo_icone.png', 40, 16, 8, 40, 1),

('Javaporco',
 'Resultado do cruzamento de javalis com porcos domésticos, tornou-se uma praga em regiões rurais. Voraz e agressivo. Habilidade: Ferocidade — ao sofrer dano, recebe bônus em ataques e causa um dado de dano adicional em todas as rolagens até o fim da cena. Habilidade: Mordida Final — ao ser reduzido a 0 PV, faz um ataque de mordida antes de morrer.',
 'Animal - Médio', '../img/logo_icone.png', 35, 14, 5, 20, 1),

('Onça-Pintada',
 'O maior felino das Américas e principal predador das selvas brasileiras. Dificilmente encontrada fora de seu território nativo, mas pode ser um oponente mortal para agentes em campo. Possui faro e visão na penumbra. Habilidade: Agarrão — se acertar mordida em ser Médio ou menor, pode tentar agarrar. Habilidade: Bote — faz uma investida e ataca com mordida e garras em um mesmo alvo.',
 'Animal - Grande', '../img/logo_icone.png', 55, 16, 10, 40, 1),

('Sucuri',
 'Uma grande cobra constritora originária das selvas amazônicas. Pode ser encontrada sob posse de colecionadores de animais exóticos ou como mascote de cultistas excêntricos. Habilidade: Agarrão — se acertar mordida em ser Médio ou menor, pode tentar agarrar. Habilidade: Constrição — no início de cada um dos seus turnos, causa 2d6+8 pontos de dano de impacto em qualquer ser que esteja agarrando.',
 'Animal - Grande', '../img/logo_icone.png', 68, 16, 8, 40, 1);


-- ============================================================
-- FIM DO SCRIPT
-- ============================================================
-- Resumo dos dados inseridos neste arquivo:
--
-- tb_origem:  +22 origens (total: 26 com as 4 do db_table.sql)
-- tb_classe:  +1 classe Mundano (total: 4 com as 3 do db_table.sql)
-- tb_pericia: +19 perícias (total: 28 com as 9 do db_table.sql)
-- tb_monstro: +48 criaturas e ameaças
--
-- CRIATURAS PARANORMAIS (total: 48 incluindo as 5 do db_table.sql)
--   Sangue (12): O Diabo*, Aberração de Carne*, Aniquilação*,
--                Carente*, Dama de Sangue*, Enpap-X, Kerberos,
--                Minotauro, Mulher Afogada, Titã de Sangue,
--                Zumbi de Sangue, Zumbi de Sangue Bestial
--   Morte (12):  Aracnasita, Carniçal Preto da Morte, Ceifador
--                Espiral, Enraizado, Escutado, Esqueleto de Lodo,
--                Marionete, Múmia Xipófaga, Nidere, Sempiternal,
--                Succ, O Deus da Morte
--   Conhecimento(12): Anjo, Bicho-Papão, Espreitador, Estrangeiro,
--                Existido, Lembrado, Ocioso, Parasita de Culpa,
--                Rastejador Sombrio, Silhueta, Vulto,
--                Máscara do Desespero
--   Energia (12): Anárquico, Anárquico Descontrolado, Anomalia,
--                Anomiático, Ciborgue, Infecticídio, Perturbado de
--                Energia, Sukkalgir, Telopsia, Tempestuoso,
--                Viajante, Anfitrião + Degolificada (todos elementos)
--
-- AMEAÇAS MUNDANAS (18):
--   Criminosos/Mercenários (5): Bandido, Capanga, Soldado de
--                Aluguel, Assassino, Comandante Mercenário
--   Cultistas (3): Iniciado, Investido, Líder de Culto
--   Policiais (3): Policial, Policial de Elite, Chefe de Polícia
--   Animais (7):  Cão de Guarda, Enxame de Abelhas, Enxame de
--                Ratos, Jacaré, Javaporco, Onça-Pintada, Sucuri
--
-- * = Inserido no db_table.sql original (id_monstro 1-5)
-- Criaturas com múltiplos elementos têm todos listados na ds_monstro
-- ============================================================

-- ============================================================
-- INSERTS DE EQUIPAMENTOS - ORDEM PARANORMAL RPG v1.3
-- (inserts_equipamentos_ordem_paranormal.sql)
-- ============================================================


-- ============================================================
-- ARMAS SIMPLES — CORPO A CORPO LEVES
-- ============================================================

INSERT INTO tb_item (nm_item, ds_item, tp_item, qt_peso, qt_valor_ouro, qt_bonus_dano, qt_bonus_defesa, id_sistema) VALUES

('Coronhada',
 'Ataque improvisado usando o cabo de uma arma de fogo como arma corpo a corpo. Dano: 1d4 (leve/uma mão) ou 1d6 (duas mãos) de impacto. Não ocupa espaço adicional — é parte da arma de fogo. Todos são proficientes.',
 'arma', 0, 0, 0, 0, 1),

('Faca',
 'Uma lâmina afiada — navalha, faca de churrasco ou faca militar. Arma ágil (pode usar Agilidade em vez de Força). Pode ser arremessada em alcance curto. Dano: 1d4. Crítico: 19. Tipo: Corte. Categoria 0. Espaços: 1.',
 'arma', 1, 0, 0, 0, 1),

('Martelo',
 'Ferramenta comum usada como arma na falta de opções melhores. Dano: 1d6. Crítico: x2. Tipo: Impacto. Categoria 0. Espaços: 1.',
 'arma', 1, 0, 0, 0, 1),

('Punhal',
 'Faca de lâmina longa e pontiaguda, usada por cultistas em rituais. Arma ágil (pode usar Agilidade em vez de Força). Dano: 1d4. Crítico: x3. Tipo: Perfuração. Categoria 0. Espaços: 1.',
 'arma', 1, 0, 0, 0, 1),

-- ============================================================
-- ARMAS SIMPLES — CORPO A CORPO UMA MÃO
-- ============================================================

('Bastão',
 'Cilindro de madeira maciça — taco de beisebol, cassetete, tonfa ou clava com pregos. Pode ser empunhado com uma mão (1d6) ou com as duas (1d8). Crítico: x2. Tipo: Impacto. Categoria 0. Espaços: 1.',
 'arma', 1, 0, 0, 0, 1),

('Machete',
 'Lâmina longa e larga, muito usada para abrir trilhas. Dano: 1d6. Crítico: 19. Tipo: Corte. Categoria 0. Espaços: 1.',
 'arma', 1, 0, 0, 0, 1),

('Lança',
 'Haste de madeira com ponta metálica afiada. Arma arcaica, ainda usada por artistas marciais. Pode ser arremessada. Dano: 1d6. Crítico: x2. Alcance: Curto. Tipo: Perfuração. Categoria 0. Espaços: 1.',
 'arma', 1, 0, 0, 0, 1),

-- ============================================================
-- ARMAS SIMPLES — CORPO A CORPO DUAS MÃOS
-- ============================================================

('Cajado',
 'Cabo de madeira ou barra de ferro longa (inclui o bo de artes marciais). Arma ágil. Pode ser usado com Combater com Duas Armas como se fosse uma arma de uma mão e uma arma leve. Dano: 1d6/1d6. Crítico: x2. Tipo: Impacto. Categoria 0. Espaços: 2.',
 'arma', 2, 0, 0, 0, 1),

-- ============================================================
-- ARMAS SIMPLES — DISPARO DUAS MÃOS
-- ============================================================

('Arco',
 'Arco e flecha comum, próprio para tiro ao alvo. Recarregar exige as duas mãos. Dano: 1d6. Crítico: x3. Alcance: Médio. Tipo: Perfuração. Categoria 0. Espaços: 2.',
 'arma', 2, 0, 0, 0, 1),

('Besta',
 'Arma da antiguidade. Exige uma ação de movimento para ser recarregada a cada disparo. Dano: 1d8. Crítico: 19. Alcance: Médio. Tipo: Perfuração. Categoria 0. Espaços: 2.',
 'arma', 2, 0, 0, 0, 1),

-- ============================================================
-- ARMAS SIMPLES — FOGO LEVES
-- ============================================================

('Pistola',
 'Arma de mão comum entre policiais e militares, facilmente recarregável. Arma de fogo leve. Dano: 1d12. Crítico: 18. Alcance: Curto. Tipo: Balístico. Categoria I. Espaços: 1.',
 'arma', 1, 1, 0, 0, 1),

('Revólver',
 'A arma de fogo mais comum e confiável. Arma de fogo leve. Dano: 2d6. Crítico: 19/x3. Alcance: Curto. Tipo: Balístico. Categoria I. Espaços: 1.',
 'arma', 1, 1, 0, 0, 1),

-- ============================================================
-- ARMAS SIMPLES — FOGO DUAS MÃOS
-- ============================================================

('Fuzil de Caça',
 'Arma de fogo popular entre fazendeiros, caçadores e atiradores esportistas. Dano: 2d8. Crítico: 19/x3. Alcance: Médio. Tipo: Balístico. Categoria I. Espaços: 2.',
 'arma', 2, 1, 0, 0, 1),

-- ============================================================
-- ARMAS TÁTICAS — CORPO A CORPO LEVES
-- ============================================================

('Machadinha',
 'Ferramenta útil para cortar madeira, comum em fazendas e canteiros. Pode ser arremessada. Dano: 1d6. Crítico: x3. Alcance: Curto. Tipo: Corte. Categoria 0. Espaços: 1.',
 'arma', 1, 0, 0, 0, 1),

('Nunchaku',
 'Dois bastões curtos de madeira ligados por uma corrente. Arma ágil (pode usar Agilidade em vez de Força). Dano: 1d8. Crítico: x2. Tipo: Impacto. Categoria 0. Espaços: 1.',
 'arma', 1, 0, 0, 0, 1),

-- ============================================================
-- ARMAS TÁTICAS — CORPO A CORPO UMA MÃO
-- ============================================================

('Corrente',
 'Pedaço de corrente grossa usado como arma. Fornece +2 em testes para desarmar e derrubar. Dano: 1d8. Crítico: x2. Tipo: Impacto. Categoria 0. Espaços: 1.',
 'arma', 1, 0, 2, 0, 1),

('Espada',
 'Arma medieval — espada longa europeia ou cimitarra sarracena. Pode ser empunhada com uma mão (1d8) ou com as duas (1d10). Crítico: 19. Tipo: Corte. Categoria I. Espaços: 1.',
 'arma', 1, 1, 0, 0, 1),

('Florete',
 'Espada de lâmina fina e comprida, usada por esgrimistas. Arma ágil (pode usar Agilidade em vez de Força). Dano: 1d6. Crítico: 18. Tipo: Corte. Categoria I. Espaços: 1.',
 'arma', 1, 1, 0, 0, 1),

('Machado',
 'Ferramenta importante para lenhadores e bombeiros, um machado pode causar ferimentos terríveis. Dano: 1d8. Crítico: x3. Tipo: Corte. Categoria I. Espaços: 1.',
 'arma', 1, 1, 0, 0, 1),

('Maça',
 'Bastão com uma cabeça metálica cheia de protuberâncias. Dano: 2d4. Crítico: x2. Tipo: Impacto. Categoria I. Espaços: 1.',
 'arma', 1, 1, 0, 0, 1),

-- ============================================================
-- ARMAS TÁTICAS — CORPO A CORPO DUAS MÃOS
-- ============================================================

('Acha',
 'Machado grande e pesado, usado no corte de árvores largas. Dano: 1d12. Crítico: x3. Tipo: Corte. Categoria I. Espaços: 2.',
 'arma', 2, 1, 0, 0, 1),

('Gadanho',
 'Ferramenta agrícola — versão maior da foice para uso com as duas mãos. Criada para ceifar cereais, mas também pode ceifar vidas. Dano: 2d4. Crítico: x4. Tipo: Corte. Categoria I. Espaços: 2.',
 'arma', 2, 1, 0, 0, 1),

('Katana',
 'Espada longa e levemente curvada de origem japonesa. Arma ágil. Se veterano em Luta, pode usá-la com uma mão. Dano: 1d10. Crítico: 19. Tipo: Corte. Categoria I. Espaços: 2.',
 'arma', 2, 1, 0, 0, 1),

('Marreta',
 'Normalmente usada para demolir paredes, também pode ser usada contra pessoas. Serve para outras ferramentas de construção, como picaretas. Dano: 3d4. Crítico: x2. Tipo: Impacto. Categoria I. Espaços: 2.',
 'arma', 2, 1, 0, 0, 1),

('Montante',
 'Enorme e pesada espada de 1,5m de comprimento — uma das armas mais poderosas em seu tempo. Dano: 2d6. Crítico: 19. Tipo: Corte. Categoria I. Espaços: 2.',
 'arma', 2, 1, 0, 0, 1),

('Motosserra',
 'Ferramenta capaz de causar ferimentos profundos. Sempre que rolar um 6 em um dado de dano, role um dado adicional. Impõe –1d em testes de ataque. Ligar gasta uma ação de movimento. Dano: 3d6. Crítico: x2. Tipo: Corte. Categoria I. Espaços: 2.',
 'arma', 2, 1, 0, 0, 1),

-- ============================================================
-- ARMAS TÁTICAS — DISPARO DUAS MÃOS
-- ============================================================

('Arco Composto',
 'Arco moderno com materiais de alta tensão e sistema de roldanas. Ao contrário de outras armas de disparo, permite aplicar Força às rolagens de dano. Dano: 1d10. Crítico: x3. Alcance: Médio. Tipo: Perfuração. Categoria I. Espaços: 2.',
 'arma', 2, 1, 0, 0, 1),

('Balestra',
 'Besta pesada capaz de disparos poderosos. Exige uma ação de movimento para ser recarregada a cada disparo. Dano: 1d12. Crítico: 19. Alcance: Médio. Tipo: Perfuração. Categoria I. Espaços: 2.',
 'arma', 2, 1, 0, 0, 1),

-- ============================================================
-- ARMAS TÁTICAS — FOGO UMA MÃO
-- ============================================================

('Submetralhadora',
 'Arma de fogo automática que pode ser empunhada com apenas uma mão. Arma automática: pode disparar rajadas (–1d em ataque, +1 dado de dano). Dano: 2d6. Crítico: 19/x3. Alcance: Curto. Tipo: Balístico. Categoria I. Espaços: 1.',
 'arma', 1, 1, 0, 0, 1),

-- ============================================================
-- ARMAS TÁTICAS — FOGO DUAS MÃOS
-- ============================================================

('Espingarda',
 'Arma de fogo longa com cano liso. Causa apenas metade do dano em alcance médio ou maior. Dano: 4d6. Crítico: x3. Alcance: Curto. Tipo: Balístico. Categoria I. Espaços: 2.',
 'arma', 2, 1, 0, 0, 1),

('Fuzil de Assalto',
 'Arma de fogo padrão da maioria dos exércitos modernos. Arma automática: pode disparar rajadas (–1d em ataque, +1 dado de dano). Dano: 2d10. Crítico: 19/x3. Alcance: Médio. Tipo: Balístico. Categoria II. Espaços: 2.',
 'arma', 2, 2, 0, 0, 1),

('Fuzil de Precisão',
 'Arma de fogo militar para disparos longos e precisos. Se veterano em Pontaria e mirar, recebe +5 na margem de ameaça do ataque. Dano: 2d10. Crítico: 19/x3. Alcance: Longo. Tipo: Balístico. Categoria III. Espaços: 2.',
 'arma', 2, 3, 0, 0, 1),

-- ============================================================
-- ARMAS PESADAS — FOGO DUAS MÃOS
-- ============================================================

('Bazuca',
 'Lança-foguetes concebido como arma anti-tanques. Causa dano no alvo atingido e em todos os seres em raio de 3m (Reflexos DT Agi reduz à metade para os demais). Pode ser disparada em ponto qualquer sem rolar ataque. Exige ação de movimento para recarregar a cada disparo. Dano: 10d8. Crítico: x2. Alcance: Médio. Tipo: Impacto. Categoria III. Espaços: 2.',
 'arma', 2, 3, 0, 0, 1),

('Lança-Chamas',
 'Equipamento militar que esguicha líquido inflamável incandescente. Atinge todos os seres em uma linha de 1,5m de largura com alcance curto. Seres atingidos ficam em chamas além de sofrer dano. Dano: 6d6. Crítico: x2. Alcance: Curto. Tipo: Fogo. Categoria III. Espaços: 2.',
 'arma', 2, 3, 0, 0, 1),

('Metralhadora',
 'Arma de fogo pesada de uso militar. Exige Força 4 ou maior, ou uma ação de movimento para apoiar no tripé; caso contrário, –5 em ataques. Arma automática: pode disparar rajadas. Dano: 2d12. Crítico: 19/x3. Alcance: Médio. Tipo: Balístico. Categoria II. Espaços: 2.',
 'arma', 2, 2, 0, 0, 1),

-- ============================================================
-- MUNIÇÕES
-- ============================================================

('Balas Curtas',
 'Munição básica para pistolas, revólveres e submetralhadoras. Um pacote dura duas cenas. Categoria 0. Espaços: 1.',
 'outro', 1, 0, 0, 0, 1),

('Balas Longas',
 'Munição maior e mais potente para fuzis e metralhadoras. Um pacote dura uma cena. Categoria I. Espaços: 1.',
 'outro', 1, 1, 0, 0, 1),

('Cartuchos',
 'Munição para espingardas, carregada com esferas de chumbo. Um pacote dura uma cena. Categoria I. Espaços: 1.',
 'outro', 1, 1, 0, 0, 1),

('Combustível (Lança-chamas)',
 'Tanque de combustível para lança-chamas. Dura uma cena. Categoria I. Espaços: 1.',
 'outro', 1, 1, 0, 0, 1),

('Flechas',
 'Usadas em arcos e bestas. Podem ser reaproveitadas após cada combate — um pacote dura uma missão inteira. Categoria 0. Espaços: 1.',
 'outro', 1, 0, 0, 0, 1),

('Foguete (Bazuca)',
 'Ao contrário de outras munições, cada foguete dura um único disparo. Categoria I. Espaços: 1.',
 'outro', 1, 1, 0, 0, 1),

-- ============================================================
-- PROTEÇÕES
-- ============================================================

('Proteção Leve',
 'Jaqueta de couro pesada ou colete de kevlar. Tipicamente usada por seguranças e policiais. Defesa: +5. Todos os combatentes e especialistas são proficientes. Categoria I. Espaços: 2.',
 'armadura', 2, 1, 0, 5, 1),

('Proteção Pesada',
 'Equipamento de forças especiais e exército — capacete, ombreiras, joelheiras, caneleiras e colete com várias camadas de kevlar. Defesa: +10. Resistência a balístico, corte, impacto e perfuração 2. Penalidade: –5 em perícias com carga. Categoria II. Espaços: 5.',
 'armadura', 5, 2, 0, 10, 1),

('Escudo',
 'Escudo medieval ou moderno (como os de tropas de choque). Precisa ser empunhado em uma mão. Defesa: +2 (acumula com proteção). Para efeitos de proficiência, conta como proteção pesada. Categoria I. Espaços: 2.',
 'armadura', 2, 1, 0, 2, 1),

-- ============================================================
-- EXPLOSIVOS
-- ============================================================

('Granada de Atordoamento',
 'Também chamada de flash-bang — cria um estouro barulhento e luminoso. Raio de efeito: 6m do ponto de impacto. Efeito: seres na área ficam atordoados por 1 rodada (Fortitude DT Agi reduz para ofuscado e surdo por uma rodada). Categoria 0. Espaços: 1.',
 'consumivel', 1, 0, 0, 0, 1),

('Granada de Fragmentação',
 'Espalha fragmentos perfurantes em raio de 6m do ponto de impacto. Efeito: seres na área sofrem 8d6 pontos de dano de perfuração (Reflexos DT Agi reduz à metade). Categoria I. Espaços: 1.',
 'consumivel', 1, 1, 8, 0, 1),

('Granada de Fumaça',
 'Produz fumaça espessa e escura em raio de 6m do ponto de impacto. Seres na área ficam cegos e sob camuflagem total. A fumaça dura 2 rodadas. Categoria 0. Espaços: 1.',
 'consumivel', 1, 0, 0, 0, 1),

('Granada Incendiária',
 'Espalha labaredas incandescentes em raio de 6m do ponto de impacto. Efeito: seres na área sofrem 6d6 pontos de dano de fogo e ficam em chamas (Reflexos DT Agi reduz o dano à metade e evita a condição em chamas). Categoria I. Espaços: 1.',
 'consumivel', 1, 1, 6, 0, 1),

('Mina Antipessoal',
 'Ativada por controle remoto em até alcance longo. Ao explodir, dispara centenas de bolas de aço em um cone de 6m, causando 12d6 pontos de dano de perfuração (Reflexos DT Int reduz à metade). Instalar exige ação completa e Tática DT 15. Categoria I. Espaços: 1.',
 'consumivel', 1, 1, 12, 0, 1),

-- ============================================================
-- ITENS OPERACIONAIS
-- ============================================================

('Algemas',
 'Par de algemas de aço. Para prender alguém não indefeso, precisa agarrá-la e vencer novo teste de agarrar. Pode prender os dois pulsos (–5 em testes com mãos, impede conjuração) ou um pulso a objeto imóvel. Escapar: Acrobacia DT 30. Categoria 0. Espaços: 1.',
 'ferramenta', 1, 0, 0, 0, 1),

('Arpéu',
 'Gancho de aço fixado na ponta de uma corda para escalar muros, janelas e parapeitos. Prender o arpéu: Pontaria DT 15. Fornece +5 em testes de Atletismo para escalar. Categoria 0. Espaços: 1.',
 'ferramenta', 1, 0, 0, 0, 1),

('Bandoleira',
 'Cinto com bolsos e alças. Uma vez por rodada, você pode sacar ou guardar um item em seu inventário como uma ação livre. Categoria I. Espaços: 1.',
 'acessorio', 1, 1, 0, 0, 1),

('Binóculos',
 'Binóculos militares que fornecem +5 em testes de Percepção para observar coisas distantes. Categoria 0. Espaços: 1.',
 'ferramenta', 1, 0, 0, 0, 1),

('Bloqueador de Sinal',
 'Dispositivo compacto que emite ondas poluindo a frequência de rádio usada por celulares. Impede que qualquer aparelho em alcance médio se conecte. Categoria I. Espaços: 1.',
 'ferramenta', 1, 1, 0, 0, 1),

('Cicatrizante',
 'Spray com potente efeito cicatrizante. Gaste uma ação padrão e este item para curar 2d8+2 PV em você ou em um ser adjacente. Categoria I. Espaços: 1.',
 'consumivel', 1, 1, 0, 0, 1),

('Corda',
 'Rolo com 10 metros de corda resistente. Fornece +5 em Atletismo para descer buracos ou prédios. Pode ser usada para amarrar pessoas inconscientes. Categoria 0. Espaços: 1.',
 'ferramenta', 1, 0, 0, 0, 1),

('Equipamento de Sobrevivência',
 'Mochila com saco de dormir, panelas, GPS e outros itens úteis. Fornece +5 em testes de Sobrevivência para acampar e orientar-se, e permite que você faça esses testes sem treinamento. Categoria 0. Espaços: 2.',
 'ferramenta', 2, 0, 0, 0, 1),

('Lanterna Tática',
 'Ilumina um cone de 9m. Você pode gastar uma ação de movimento para mirar a luz nos olhos de um ser em alcance curto — ele fica ofuscado por 1 rodada, mas imune à lanterna pelo resto da cena. Categoria I. Espaços: 1.',
 'ferramenta', 1, 1, 0, 0, 1),

('Máscara de Gás',
 'Máscara com filtro que cobre o rosto inteiro. Fornece +10 em testes de Fortitude contra efeitos que dependam de respiração. Categoria 0. Espaços: 1.',
 'armadura', 1, 0, 0, 0, 1),

('Mochila Militar',
 'Mochila leve e de alta qualidade. Não usa nenhum espaço e aumenta sua capacidade de carga em 2 espaços. Categoria I. Espaços: * (não ocupa espaço).',
 'ferramenta', 0, 1, 0, 0, 1),

('Óculos de Visão Térmica',
 'Óculos que eliminam a penalidade em testes por camuflagem. Categoria I. Espaços: 1.',
 'ferramenta', 1, 1, 0, 0, 1),

('Pé de Cabra',
 'Barra de ferro que fornece +5 em testes de Força para arrombar portas. Pode ser usado em combate como um bastão. Categoria 0. Espaços: 1.',
 'ferramenta', 1, 0, 0, 0, 1),

('Pistola de Dardos',
 'Arma leve que dispara dardos com sonífero em alcance curto. Se acertar, o alvo fica inconsciente até o fim da cena (Fortitude DT Agi reduz para desprevenido e lento por uma rodada). Vem com 2 dardos. Caixa adicional (2 dardos) é categoria 0. Categoria I. Espaços: 1.',
 'arma', 1, 1, 0, 0, 1),

('Pistola Sinalizadora',
 'Pistola que dispara um sinalizador luminoso para chamar outras pessoas para sua localização. Pode ser usada como arma leve em alcance curto causando 2d6 de dano de fogo. Vem com 2 cargas. Caixa adicional (2 cargas) é categoria 0. Categoria 0. Espaços: 1.',
 'arma', 1, 0, 0, 0, 1),

('Soqueira',
 'Peça de metal usada entre os dedos para socos mais perigosos. Fornece +1 em rolagens de dano desarmado e os torna letais. Pode receber modificações e maldições de armas corpo a corpo. Categoria 0. Espaços: 1.',
 'arma', 1, 0, 1, 0, 1),

('Spray de Pimenta',
 'Spray com composto químico que causa dor e lacrimação. Ação padrão para atingir ser adjacente — alvo fica cego por 1d4 rodadas (Fortitude DT Agi evita). A carga dura dois usos. Categoria I. Espaços: 1.',
 'consumivel', 1, 1, 0, 0, 1),

('Taser',
 'Dispositivo de eletrochoque. Ação padrão para atingir ser adjacente — alvo sofre 1d6 de eletricidade e fica atordoado por uma rodada (Fortitude DT Agi evita). A bateria dura dois usos. Categoria I. Espaços: 1.',
 'ferramenta', 1, 1, 0, 0, 1),

('Traje Hazmat',
 'Roupa impermeável que cobre o corpo inteiro, usada para impedir contato com materiais tóxicos. Fornece +5 em testes de resistência contra efeitos ambientais e resistência a químico 10. Categoria I. Espaços: 2.',
 'armadura', 2, 1, 0, 0, 1),

-- ============================================================
-- ACESSÓRIOS
-- ============================================================

('Kit de Perícia',
 'Conjunto de ferramentas necessárias para perícias específicas. Sem o kit, você sofre –5 no teste. Existe um kit para cada perícia que exige este item (Crime, Enganação, Medicina, Tecnologia). Categoria 0. Espaços: 1.',
 'ferramenta', 1, 0, 0, 0, 1),

('Utensílio',
 'Item comum com utilidade específica: canivete, lupa, smartphone, notebook, detector portátil etc. Fornece +2 em uma perícia (exceto Luta e Pontaria). Deve ser empunhado para que o bônus seja aplicado. Aprovação do mestre necessária. Categoria I. Espaços: 1.',
 'ferramenta', 1, 1, 0, 0, 1),

('Vestimenta',
 'Peça de vestuário que fornece +2 em uma perícia (exceto Luta e Pontaria): botas militares (+2 Atletismo), terno elegante (+2 Diplomacia), manto com glifos (+2 Ocultismo), etc. Recebe bônus de no máximo duas vestimentas ao mesmo tempo. Vestir/despir: ação completa. Categoria I. Espaços: 1.',
 'acessorio', 1, 1, 0, 0, 1),

-- ============================================================
-- ITENS PARANORMAIS
-- ============================================================

('Amarras de Sangue',
 'Cordas ou correntes feitas do elemento Sangue. Eficazes contra criaturas vulneráveis a Sangue. Usos: Armadilha (ação completa, 2 PE — área 3x3m, Reflexos DT Int ou fica imóvel até fim da cena) ou Laçar (ação padrão, 1 PE — Vontade DT Agi ou paralisado até próximo turno; manter custa 1 PE/rodada). Categoria II. Espaços: 1.',
 'ferramenta', 1, 2, 0, 0, 1),

('Amarras de Morte',
 'Cordas ou correntes feitas do elemento Morte. Eficazes contra criaturas vulneráveis a Morte. Usos: Armadilha (ação completa, 2 PE) ou Laçar (ação padrão, 1 PE). Categoria II. Espaços: 1.',
 'ferramenta', 1, 2, 0, 0, 1),

('Amarras de Conhecimento',
 'Cordas ou correntes feitas do elemento Conhecimento. Eficazes contra criaturas vulneráveis a Conhecimento. Usos: Armadilha (ação completa, 2 PE) ou Laçar (ação padrão, 1 PE). Categoria II. Espaços: 1.',
 'ferramenta', 1, 2, 0, 0, 1),

('Amarras de Energia',
 'Cordas ou correntes feitas do elemento Energia. Eficazes contra criaturas vulneráveis a Energia. Usos: Armadilha (ação completa, 2 PE) ou Laçar (ação padrão, 1 PE). Categoria II. Espaços: 1.',
 'ferramenta', 1, 2, 0, 0, 1),

('Câmera de Aura Paranormal',
 'Câmera amaldiçoada com Energia e sigilos de Conhecimento. Tirar uma foto gasta uma ação padrão e 1 PE. As fotos são instantâneas e revelam a presença de auras paranormais em pessoas e objetos. Auras são da cor associada ao elemento. Categoria II. Espaços: 1.',
 'ferramenta', 1, 2, 0, 0, 1),

('Componentes Ritualísticos de Sangue',
 'Objetos necessários para conjurar rituais de Sangue: órgãos, carne, sangue, animais vivos (para sacrifício), navalhas, agulhas, arame farpado, correntes, metal enferrujado, fluidos corporais etc. Categoria 0. Espaços: 1.',
 'consumivel', 1, 0, 0, 0, 1),

('Componentes Ritualísticos de Morte',
 'Objetos necessários para conjurar rituais de Morte: ossos, dentes, cinzas, fios de cabelo, cristais pretos, relógios, galhos secos, folhas secas, plantas mortas, raízes, areia, poeira, Lodo etc. Categoria 0. Espaços: 1.',
 'consumivel', 1, 0, 0, 0, 1),

('Componentes Ritualísticos de Conhecimento',
 'Objetos necessários para conjurar rituais de Conhecimento: escrituras, papéis, livros, pergaminhos, instrumentos de escrita (lápis, caneta, tinta, giz), pedras preciosas, ouro, cordas, tecido, cristais brancos, vidro, máscaras etc. Categoria 0. Espaços: 1.',
 'consumivel', 1, 0, 0, 0, 1),

('Componentes Ritualísticos de Energia',
 'Objetos necessários para conjurar rituais de Energia: eletricidade, dispositivos tecnológicos (celulares, computadores), circuitos eletrônicos, fontes de calor e luz, pilhas, baterias, cabos de cobre e prata, pólvora, moedas, dados, ímãs etc. Categoria 0. Espaços: 1.',
 'consumivel', 1, 0, 0, 0, 1),

('Emissor de Pulsos Paranormais',
 'Pequena caixa coberta de sigilos que funciona como isca para criaturas paranormais. Ativar: ação completa e 1 PE. Emite um pulso de um elemento definido pelo ativador, atraindo criaturas do mesmo elemento e afastando as do elemento oposto. Criaturas afetadas: Vontade DT Pre para evitar. Categoria II. Espaços: 1.',
 'ferramenta', 1, 2, 0, 0, 1),

('Escuta de Ruídos Paranormais',
 'Microfone espião que capta ruídos paranormais. Ativar: ação completa e 2 PE — grava ruídos por até 24 horas. Ouvir a escuta fornece +5 em testes de Ocultismo para identificar criaturas. Categoria II. Espaços: 1.',
 'ferramenta', 1, 2, 0, 0, 1),

('Medidor de Estabilidade da Membrana',
 'Dispositivo com medidores de temperatura, campo magnético e dilatação temporal. Treinados em Ocultismo podem usá-lo para avaliar o estado da Membrana em uma área, indicando a chance de uma entidade se manifestar. Valores racionais = baixo risco. Grandes variações = risco elevado. Não fornece respostas definitivas. Categoria II. Espaços: 1.',
 'ferramenta', 1, 2, 0, 0, 1),

('Scanner de Manifestação Paranormal de Sangue',
 'Dispositivo conectado a objetos amaldiçoados do elemento Sangue com sigilos. Ativar: ação padrão. Consome 1 PE/rodada do usuário. Indica a direção de todas as manifestações paranormais ativas de Sangue em alcance longo (rituais, criaturas, itens amaldiçoados). Detecta também criaturas com Sangue como elemento complementar. Categoria II. Espaços: 1.',
 'ferramenta', 1, 2, 0, 0, 1),

('Scanner de Manifestação Paranormal de Morte',
 'Dispositivo conectado a objetos amaldiçoados do elemento Morte com sigilos. Ativar: ação padrão. Consome 1 PE/rodada do usuário. Indica a direção de todas as manifestações paranormais ativas de Morte em alcance longo. Detecta também criaturas com Morte como elemento complementar. Categoria II. Espaços: 1.',
 'ferramenta', 1, 2, 0, 0, 1),

('Scanner de Manifestação Paranormal de Conhecimento',
 'Dispositivo conectado a objetos amaldiçoados do elemento Conhecimento com sigilos. Ativar: ação padrão. Consome 1 PE/rodada do usuário. Indica a direção de todas as manifestações paranormais ativas de Conhecimento em alcance longo. Detecta também criaturas com Conhecimento como elemento complementar. Categoria II. Espaços: 1.',
 'ferramenta', 1, 2, 0, 0, 1),

('Scanner de Manifestação Paranormal de Energia',
 'Dispositivo conectado a objetos amaldiçoados do elemento Energia com sigilos. Ativar: ação padrão. Consome 1 PE/rodada do usuário. Indica a direção de todas as manifestações paranormais ativas de Energia em alcance longo. Detecta também criaturas com Energia como elemento complementar. Categoria II. Espaços: 1.',
 'ferramenta', 1, 2, 0, 0, 1);


-- ============================================================
-- FIM DO SCRIPT
-- ============================================================
-- Resumo dos itens inseridos:
--
-- ARMAS (35):
--   Simples corpo a corpo leves (4): Coronhada, Faca, Martelo, Punhal
--   Simples corpo a corpo uma mão (3): Bastão, Machete, Lança
--   Simples corpo a corpo duas mãos (1): Cajado
--   Simples disparo duas mãos (2): Arco, Besta
--   Simples fogo leve (2): Pistola, Revólver
--   Simples fogo duas mãos (1): Fuzil de Caça
--   Táticas corpo a corpo leve (2): Machadinha, Nunchaku
--   Táticas corpo a corpo uma mão (4): Corrente, Espada, Florete, Machado, Maça
--   Táticas corpo a corpo duas mãos (6): Acha, Gadanho, Katana, Marreta, Montante, Motosserra
--   Táticas disparo duas mãos (2): Arco Composto, Balestra
--   Táticas fogo uma mão (1): Submetralhadora
--   Táticas fogo duas mãos (3): Espingarda, Fuzil de Assalto, Fuzil de Precisão
--   Pesadas fogo duas mãos (3): Bazuca, Lança-Chamas, Metralhadora
--   + Pistola de Dardos, Pistola Sinalizadora, Soqueira
--
-- MUNIÇÕES (6): Balas Curtas, Balas Longas, Cartuchos,
--   Combustível, Flechas, Foguete
--
-- PROTEÇÕES (3): Proteção Leve, Proteção Pesada, Escudo
--
-- EXPLOSIVOS (5): Granada de Atordoamento, Fragmentação,
--   Fumaça, Incendiária, Mina Antipessoal
--
-- ITENS OPERACIONAIS (18): Algemas, Arpéu, Bandoleira,
--   Binóculos, Bloqueador de Sinal, Cicatrizante, Corda,
--   Equipamento de Sobrevivência, Lanterna Tática, Máscara
--   de Gás, Mochila Militar, Óculos de Visão Térmica,
--   Pé de Cabra, Spray de Pimenta, Taser, Traje Hazmat
--
-- ACESSÓRIOS (3): Kit de Perícia, Utensílio, Vestimenta
--
-- ITENS PARANORMAIS (16): Amarras (×4), Câmera de Aura,
--   Componentes Ritualísticos (×4), Emissor de Pulsos,
--   Escuta de Ruídos, Medidor de Membrana, Scanner (×4)
--
-- TOTAL: ~86 itens
-- ============================================================
