-- =============================================================================
--                   ████████╗ █████╗ ██████╗ ██╗     ███████╗
--                   ╚══██╔══╝██╔══██╗██╔══██╗██║     ██╔════╝
--                      ██║   ███████║██████╔╝██║     █████╗  
--                      ██║   ██╔══██║██╔══██╗██║     ██╔══╝  
--                      ██║   ██║  ██║██████╔╝███████╗███████╗
--                      ╚═╝   ╚═╝  ╚═╝╚═════╝ ╚══════╝╚══════╝
-- ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
--              ESTRUTURA E CARGA DE DADOS DO BANCO DE DADOS - TABLE RPG
-- ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
--  Autores: Paulo Guilherme, Ester Carvalho, Kauan Bryan, 
--           Filipe Ferreira, Mayara Bezerra
--  Melhorias Premium: Índices de busca, integridade relacional robusta e performance
-- ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬

DROP DATABASE IF EXISTS db_table;

CREATE DATABASE IF NOT EXISTS db_table
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_table;

-- ┌────────────────────────────────────────────────────────┐
-- │  [01] TABELA: tb_usuario                               │
-- ├────────────────────────────────────────────────────────┤
-- │  Armazena os dados de autenticação e perfis dos        │
-- │  jogadores, mestres e administradores do TABLE.        │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_usuario (
    id_usuario      INT            NOT NULL AUTO_INCREMENT,
    nm_exibicao     VARCHAR(70)    DEFAULT NULL,       -- Nome real/exibição
    nm_usuario      VARCHAR(70)    NOT NULL,           -- Login/handle único
    ds_email        VARCHAR(100)   NOT NULL,
    ds_senha        VARCHAR(255)   NOT NULL,           -- Hash bcrypt
    dt_nascimento   DATE           DEFAULT NULL,
    tp_cargo        ENUM('jogador','mestre','admin') NOT NULL DEFAULT 'jogador',
    ds_foto         VARCHAR(300)   NOT NULL DEFAULT '../img/uploads/perfil/avatar1.png',
    ds_bio          VARCHAR(500)   DEFAULT NULL,
    dt_cadastro     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dt_atualizacao  DATETIME       DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    fl_ativo        TINYINT(1)     NOT NULL DEFAULT 1, -- Soft-delete
    CONSTRAINT pk_usuario PRIMARY KEY (id_usuario),
    CONSTRAINT uq_usuario_email UNIQUE (ds_email)
);

CREATE INDEX idx_usuario_login ON tb_usuario (nm_usuario);

-- ┌────────────────────────────────────────────────────────┐
-- │  [02] TABELA: tb_sistema                               │
-- ├────────────────────────────────────────────────────────┤
-- │  Representa os sistemas de RPG suportados pela         │
-- │  plataforma (Ordem Paranormal, D&D, etc.).             │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_sistema (
    id_sistema          INT          NOT NULL AUTO_INCREMENT,
    id_usuario_criador  INT          DEFAULT NULL,
    nm_sistema          VARCHAR(100) NOT NULL,
    ds_descricao        VARCHAR(1000) DEFAULT NULL,
    ds_imagem           VARCHAR(300)  DEFAULT NULL,
    ds_background       VARCHAR(300)  DEFAULT NULL,
    tp_classificacao    VARCHAR(5)   DEFAULT 'L',
    dt_cadastro         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fl_importado        TINYINT(1)   NOT NULL DEFAULT 0,
    id_sistema_original INT          DEFAULT NULL,
    CONSTRAINT pk_sistema PRIMARY KEY (id_sistema),
    CONSTRAINT fk_sistema_usuario FOREIGN KEY (id_usuario_criador)
        REFERENCES tb_usuario (id_usuario) ON DELETE SET NULL,
    CONSTRAINT fk_sistema_original FOREIGN KEY (id_sistema_original)
        REFERENCES tb_sistema (id_sistema) ON DELETE SET NULL
);

CREATE INDEX idx_sistema_criador ON tb_sistema (id_usuario_criador);

-- ┌────────────────────────────────────────────────────────┐
-- │  [03] TABELA: tb_convite_sistema                       │
-- ├────────────────────────────────────────────────────────┤
-- │  Gerencia os links e tokens de compartilhamento de    │
-- │  sistemas customizados criados pelos usuários.        │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_convite_sistema (
    id_convite_sistema INT          NOT NULL AUTO_INCREMENT,
    id_sistema         INT          NOT NULL,
    ds_token           VARCHAR(100) NOT NULL,
    tp_status          ENUM('pendente', 'aceito', 'expirado') DEFAULT 'pendente',
    dt_criacao         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dt_expiracao       DATETIME     DEFAULT NULL,
    CONSTRAINT pk_convite_sistema PRIMARY KEY (id_convite_sistema),
    CONSTRAINT fk_convite_sistema_sistema FOREIGN KEY (id_sistema) 
        REFERENCES tb_sistema (id_sistema) ON DELETE CASCADE
);

CREATE INDEX idx_convite_sistema_token ON tb_convite_sistema (ds_token);

-- ┌────────────────────────────────────────────────────────┐
-- │  [03-A] TABELA: tb_usuario_sistema                     │
-- ├────────────────────────────────────────────────────────┤
-- │  Tabela pivô que gerencia os sistemas importados e     │
-- │  compartilhados vinculados a cada usuário.             │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_usuario_sistema (
    id_usuario INT NOT NULL,
    id_sistema INT NOT NULL,
    CONSTRAINT pk_usuario_sistema PRIMARY KEY (id_usuario, id_sistema),
    CONSTRAINT fk_usuario_sistema_usuario FOREIGN KEY (id_usuario)
        REFERENCES tb_usuario (id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_usuario_sistema_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema) ON DELETE CASCADE
);

CREATE INDEX idx_usuario_sistema_usuario ON tb_usuario_sistema (id_usuario);
CREATE INDEX idx_usuario_sistema_sistema ON tb_usuario_sistema (id_sistema);

-- ┌────────────────────────────────────────────────────────┐
-- │  [04] TABELA: tb_origem                                │
-- ├────────────────────────────────────────────────────────┤
-- │  Origem do personagem vinculada a um sistema de RPG    │
-- │  específico (ex: Nobre, Acadêmico, etc.).              │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_origem (
    id_origem    INT          NOT NULL AUTO_INCREMENT,
    nm_origem    VARCHAR(80)  NOT NULL,
    ds_origem    VARCHAR(800) DEFAULT NULL,
    id_sistema   INT          DEFAULT NULL,
    CONSTRAINT pk_origem PRIMARY KEY (id_origem),
    CONSTRAINT fk_origem_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema) ON DELETE CASCADE
);

CREATE INDEX idx_origem_sistema ON tb_origem (id_sistema);

-- ┌────────────────────────────────────────────────────────┐
-- │  [05] TABELA: tb_classe                                │
-- ├────────────────────────────────────────────────────────┤
-- │  Classes jogáveis de cada sistema (Guerreiro,         │
-- │  Combatente, Ocultista, Mago, etc.).                  │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_classe (
    id_classe              INT          NOT NULL AUTO_INCREMENT,
    nm_classe              VARCHAR(80)  NOT NULL,
    ds_descricao           VARCHAR(800) DEFAULT NULL,
    ds_habilidade_primaria VARCHAR(200) DEFAULT NULL,
    qt_nivel_maximo        INT          DEFAULT 20,
    id_sistema             INT          DEFAULT NULL,
    CONSTRAINT pk_classe PRIMARY KEY (id_classe),
    CONSTRAINT fk_classe_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema) ON DELETE CASCADE
);

CREATE INDEX idx_classe_sistema ON tb_classe (id_sistema);

-- ┌────────────────────────────────────────────────────────┐
-- │  [06] TABELA: tb_atributo                              │
-- ├────────────────────────────────────────────────────────┤
-- │  Atributos que regem um sistema de RPG (Força,         │
-- │  Intelecto, Agilidade, etc.).                          │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_atributo (
    id_atributo     INT         NOT NULL AUTO_INCREMENT,
    nm_atributo     VARCHAR(80) NOT NULL,
    ds_abreviacao   VARCHAR(10) DEFAULT NULL,
    qt_valor_minimo INT         DEFAULT 0,
    qt_valor_maximo INT         DEFAULT 100,
    id_sistema      INT         DEFAULT NULL,
    CONSTRAINT pk_atributo PRIMARY KEY (id_atributo),
    CONSTRAINT fk_sistema_atributo FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema) ON DELETE CASCADE
);

CREATE INDEX idx_atributo_sistema ON tb_atributo (id_sistema);

-- ┌────────────────────────────────────────────────────────┐
-- │  [07] TABELA: tb_pericia                               │
-- ├────────────────────────────────────────────────────────┤
-- │  Perícias ativas ligadas a atributos em cada sistema   │
-- │  de RPG (ex: Atletismo rege por Força).               │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_pericia (
    id_pericia        INT         NOT NULL AUTO_INCREMENT,
    nm_pericia        VARCHAR(80) NOT NULL,
    ds_atributo_base  VARCHAR(80) DEFAULT NULL,
    id_sistema        INT         DEFAULT NULL,
    CONSTRAINT pk_pericia PRIMARY KEY (id_pericia),
    CONSTRAINT fk_sistema_pericia FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema) ON DELETE CASCADE
);

CREATE INDEX idx_pericia_sistema ON tb_pericia (id_sistema);

-- ┌────────────────────────────────────────────────────────┐
-- │  [08] TABELA: tb_sistema_status                        │
-- ├────────────────────────────────────────────────────────┤
-- │  Define barras de status customizáveis (Vida, Esforço) │
-- │  e defesas físicas de cada sistema de RPG.             │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_sistema_status (
    id_status_sistema INT NOT NULL AUTO_INCREMENT,
    nm_status         VARCHAR(50) NOT NULL,
    ds_cor            VARCHAR(7) NOT NULL,
    tp_status         ENUM('barra', 'defesa') NOT NULL DEFAULT 'barra',
    id_sistema        INT NOT NULL,
    CONSTRAINT pk_sistema_status PRIMARY KEY (id_status_sistema),
    CONSTRAINT fk_status_sistema FOREIGN KEY (id_sistema) 
        REFERENCES tb_sistema (id_sistema) ON DELETE CASCADE
);

CREATE INDEX idx_sistema_status_sistema ON tb_sistema_status (id_sistema);

-- ┌────────────────────────────────────────────────────────┐
-- │  [09] TABELA: tb_habilidade                            │
-- ├────────────────────────────────────────────────────────┤
-- │  Poderes, habilidades, magias e rituais que estão      │
-- │  disponíveis nos sistemas do TABLE.                    │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_habilidade (
    id_habilidade     INT          NOT NULL AUTO_INCREMENT,
    nm_habilidade     VARCHAR(80)  NOT NULL,
    ds_habilidade     VARCHAR(600) DEFAULT NULL,
    tp_habilidade     VARCHAR(50) DEFAULT 'habilidade',
    qt_custo_esforco  INT          DEFAULT 0,
    id_sistema        INT          DEFAULT NULL,
    CONSTRAINT pk_habilidade PRIMARY KEY (id_habilidade),
    CONSTRAINT fk_habilidade_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema) ON DELETE CASCADE
);

CREATE INDEX idx_habilidade_sistema ON tb_habilidade (id_sistema);

-- ┌────────────────────────────────────────────────────────┐
-- │  [10] TABELA: tb_item                                  │
-- ├────────────────────────────────────────────────────────┤
-- │  Itens e equipamentos (armas, proteções, acessórios)   │
-- │  cadastrados nos sistemas de RPG.                      │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_item (
    id_item         INT          NOT NULL AUTO_INCREMENT,
    nm_item         VARCHAR(100) NOT NULL,
    ds_item         VARCHAR(600) DEFAULT NULL,
    tp_item         ENUM('arma','armadura','consumivel','acessorio','ferramenta','outro') NOT NULL DEFAULT 'outro',
    qt_peso         DECIMAL(6,2) DEFAULT 0.00,
    qt_valor_ouro   INT          DEFAULT 0,
    qt_bonus_dano   INT          DEFAULT 0,
    qt_bonus_defesa INT          DEFAULT 0,
    id_sistema      INT          DEFAULT NULL,
    CONSTRAINT pk_item PRIMARY KEY (id_item),
    CONSTRAINT fk_item_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema) ON DELETE CASCADE
);

CREATE INDEX idx_item_sistema ON tb_item (id_sistema);

-- ┌────────────────────────────────────────────────────────┐
-- │  [11] TABELA: tb_monstro                               │
-- ├────────────────────────────────────────────────────────┤
-- │  Criaturas, NPCs ou ameaças que podem ser inseridas    │
-- │  em encontros de combate nas campanhas.                │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_monstro (
    id_monstro       INT          NOT NULL AUTO_INCREMENT,
    nm_monstro       VARCHAR(80)  NOT NULL,
    ds_monstro       VARCHAR(1500) DEFAULT NULL,
    tp_monstro       VARCHAR(50)  DEFAULT NULL,
    ds_imagem        VARCHAR(255) DEFAULT NULL,
    qt_vida          INT          DEFAULT 0,
    qt_defesa        INT          DEFAULT 0,
    qt_xp_recompensa INT          DEFAULT 0,
    qt_vd            INT          DEFAULT 0,
    id_sistema       INT          DEFAULT NULL,
    CONSTRAINT pk_monstro PRIMARY KEY (id_monstro),
    CONSTRAINT fk_monstro_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema) ON DELETE CASCADE
);

CREATE INDEX idx_monstro_sistema ON tb_monstro (id_sistema);

-- ┌────────────────────────────────────────────────────────┐
-- │  [12] TABELA: tb_personagem                            │
-- ├────────────────────────────────────────────────────────┤
-- │  Personagem principal de um jogador com todos os      │
-- │  seus dados, atributos, defesas e históricos.         │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_personagem (
    id_personagem      INT           NOT NULL AUTO_INCREMENT,
    id_usuario         INT           NOT NULL,
    id_sistema         INT           DEFAULT NULL,
    nm_personagem      VARCHAR(100)  NOT NULL,
    ds_aparencia       VARCHAR(1500) DEFAULT NULL,
    ds_personalidade   VARCHAR(1500) DEFAULT NULL,
    ds_historia        VARCHAR(1500) DEFAULT NULL,
    ds_objetivos       VARCHAR(1500) DEFAULT NULL,
    ds_caracteristicas VARCHAR(1500) DEFAULT NULL,
    ds_foto            VARCHAR(300)  DEFAULT NULL,
    qt_nivel           INT           NOT NULL DEFAULT 1,
    qt_experiencia     INT           NOT NULL DEFAULT 0,
    qt_vida            INT            DEFAULT 0,
    qt_vida_maxima     INT            DEFAULT 0,
    qt_defesa          INT            DEFAULT 0,
    qt_sanidade        INT            DEFAULT 0,
    qt_sanidade_maxima INT            DEFAULT 0,
    qt_esforco         INT            DEFAULT 0,
    qt_esforco_maximo  INT            DEFAULT 0,
    qt_bloqueio        INT            DEFAULT 0,
    qt_esquiva         INT            DEFAULT 0,
    qt_defesa_equip    INT            DEFAULT 0,
    qt_defesa_outros   INT            DEFAULT 0,
    ds_protecao        VARCHAR(300)   DEFAULT NULL,
    ds_resistencias    VARCHAR(300)   DEFAULT NULL,
    ds_proficiencias   VARCHAR(300)   DEFAULT NULL,
    dt_criacao         DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fl_ativo           TINYINT(1)     NOT NULL DEFAULT 1,
    CONSTRAINT pk_personagem PRIMARY KEY (id_personagem),
    CONSTRAINT fk_personagem_usuario FOREIGN KEY (id_usuario)
        REFERENCES tb_usuario (id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_personagem_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema) ON DELETE CASCADE
);

CREATE INDEX idx_personagem_usuario ON tb_personagem (id_usuario);
CREATE INDEX idx_personagem_sistema ON tb_personagem (id_sistema);

-- ┌────────────────────────────────────────────────────────┐
-- │  [13] TABELA: tb_personagem_atributo                   │
-- ├────────────────────────────────────────────────────────┤
-- │  Tabela associativa que armazena os valores de        │
-- │  atributos específicos do personagem.                  │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_personagem_atributo (
    id_personagem_atributo INT NOT NULL AUTO_INCREMENT,
    id_personagem          INT NOT NULL,
    id_atributo            INT NOT NULL,
    qt_valor               INT DEFAULT 0,
    CONSTRAINT pk_personagem_atributo PRIMARY KEY (id_personagem_atributo),
    CONSTRAINT fk_pers_attr_personagem FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem) ON DELETE CASCADE,
    CONSTRAINT fk_pers_attr_atributo FOREIGN KEY (id_atributo)
        REFERENCES tb_atributo (id_atributo) ON DELETE CASCADE,
    CONSTRAINT uq_pers_atributo UNIQUE (id_personagem, id_atributo)
);

CREATE INDEX idx_pers_attr_personagem ON tb_personagem_atributo (id_personagem);

-- ┌────────────────────────────────────────────────────────┐
-- │  [14] TABELA: tb_personagem_status                     │
-- ├────────────────────────────────────────────────────────┤
-- │  Armazena os valores atuais e máximos das barras de   │
-- │  status customizadas de cada personagem.               │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_personagem_status (
    id_personagem_status INT NOT NULL AUTO_INCREMENT,
    id_personagem        INT NOT NULL,
    id_status_sistema    INT NOT NULL,
    qt_valor_atual       INT DEFAULT 0,
    qt_valor_maximo      INT DEFAULT 0,
    CONSTRAINT pk_personagem_status PRIMARY KEY (id_personagem_status),
    CONSTRAINT fk_ps_personagem FOREIGN KEY (id_personagem) 
        REFERENCES tb_personagem (id_personagem) ON DELETE CASCADE,
    CONSTRAINT fk_ps_status_sistema FOREIGN KEY (id_status_sistema) 
        REFERENCES tb_sistema_status (id_status_sistema) ON DELETE CASCADE,
    CONSTRAINT uq_pers_status UNIQUE (id_personagem, id_status_sistema)
);

CREATE INDEX idx_pers_status_personagem ON tb_personagem_status (id_personagem);

-- ┌────────────────────────────────────────────────────────┐
-- │  [15] TABELA: tb_personagem_pericia                    │
-- ├────────────────────────────────────────────────────────┤
-- │  Armazena o bônus e o estado de treino de perícias   │
-- │  específicas de cada personagem.                       │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_personagem_pericia (
    id_personagem_pericia INT NOT NULL AUTO_INCREMENT,
    id_personagem         INT NOT NULL,
    id_pericia            INT NOT NULL,
    qt_valor              INT DEFAULT 0,
    fl_treinado           TINYINT(1) DEFAULT 0,
    qt_outros             INT DEFAULT 0,
    CONSTRAINT pk_personagem_pericia PRIMARY KEY (id_personagem_pericia),
    CONSTRAINT fk_pers_per_personagem FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem) ON DELETE CASCADE,
    CONSTRAINT fk_pers_per_pericia FOREIGN KEY (id_pericia)
        REFERENCES tb_pericia (id_pericia) ON DELETE CASCADE,
    CONSTRAINT uq_pers_pericia UNIQUE (id_personagem, id_pericia)
);

CREATE INDEX idx_pers_per_personagem ON tb_personagem_pericia (id_personagem);

-- ┌────────────────────────────────────────────────────────┐
-- │  [16] TABELA: tb_habilidade_personagem                 │
-- ├────────────────────────────────────────────────────────┤
-- │  Vínculo das habilidades e rituais que pertencem a     │
-- │  determinado personagem.                               │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_habilidade_personagem (
    id_habilidade_personagem INT NOT NULL AUTO_INCREMENT,
    id_personagem            INT NOT NULL,
    id_habilidade            INT NOT NULL,
    CONSTRAINT pk_habilidade_personagem PRIMARY KEY (id_habilidade_personagem),
    CONSTRAINT fk_hab_pers_personagem FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem) ON DELETE CASCADE,
    CONSTRAINT fk_hab_pers_habilidade FOREIGN KEY (id_habilidade)
        REFERENCES tb_habilidade (id_habilidade) ON DELETE CASCADE,
    CONSTRAINT uq_pers_habilidade UNIQUE (id_personagem, id_habilidade)
);

CREATE INDEX idx_hab_pers_personagem ON tb_habilidade_personagem (id_personagem);

-- ┌────────────────────────────────────────────────────────┐
-- │  [17] TABELA: tb_personagem_classe                     │
-- ├────────────────────────────────────────────────────────┤
-- │  Vínculo de classe com o respectivo nível atingido     │
-- │  em cada uma pelo personagem.                          │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_personagem_classe (
    id_personagem_classe INT NOT NULL AUTO_INCREMENT,
    id_personagem        INT NOT NULL,
    id_classe            INT NOT NULL,
    qt_nivel_classe      INT DEFAULT 1,
    CONSTRAINT pk_personagem_classe PRIMARY KEY (id_personagem_classe),
    CONSTRAINT fk_pers_cls_personagem FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem) ON DELETE CASCADE,
    CONSTRAINT fk_pers_cls_classe FOREIGN KEY (id_classe)
        REFERENCES tb_classe (id_classe) ON DELETE CASCADE
);

CREATE INDEX idx_pers_cls_personagem ON tb_personagem_classe (id_personagem);

-- ┌────────────────────────────────────────────────────────┐
-- │  [18] TABELA: tb_personagem_origem                     │
-- ├────────────────────────────────────────────────────────┤
-- │  Vínculo da origem histórica selecionada pelo          │
-- │  personagem em sua criação.                            │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_personagem_origem (
    id_personagem_origem INT NOT NULL AUTO_INCREMENT,
    id_personagem        INT NOT NULL,
    id_origem            INT NOT NULL,
    CONSTRAINT pk_personagem_origem PRIMARY KEY (id_personagem_origem),
    CONSTRAINT fk_pers_orig_personagem FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem) ON DELETE CASCADE,
    CONSTRAINT fk_pers_orig_origem FOREIGN KEY (id_origem)
        REFERENCES tb_origem (id_origem) ON DELETE CASCADE,
    CONSTRAINT uq_pers_origem UNIQUE (id_personagem, id_origem)
);

CREATE INDEX idx_pers_orig_personagem ON tb_personagem_origem (id_personagem);

-- ┌────────────────────────────────────────────────────────┐
-- │  [19] TABELA: tb_personagem_item                       │
-- ├────────────────────────────────────────────────────────┤
-- │  Inventário do personagem contendo a lista e a         │
-- │  quantidade de itens carregados e se estão equipados. │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_personagem_item (
    id_personagem_item INT NOT NULL AUTO_INCREMENT,
    id_personagem      INT NOT NULL,
    id_item            INT NOT NULL,
    qt_quantidade      INT DEFAULT 1,
    fl_equipado        TINYINT(1) DEFAULT 0,
    CONSTRAINT pk_personagem_item PRIMARY KEY (id_personagem_item),
    CONSTRAINT fk_pers_item_personagem FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem) ON DELETE CASCADE,
    CONSTRAINT fk_pers_item_item FOREIGN KEY (id_item)
        REFERENCES tb_item (id_item) ON DELETE CASCADE
);

CREATE INDEX idx_pers_item_personagem ON tb_personagem_item (id_personagem);

-- ┌────────────────────────────────────────────────────────┐
-- │  [20] TABELA: tb_campanha                              │
-- ├────────────────────────────────────────────────────────┤
-- │  Mesa de jogo conduzida por um Mestre para jogar e     │
-- │  compartilhar aventuras com outros participantes.      │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_campanha (
    id_campanha        INT           NOT NULL AUTO_INCREMENT,
    id_usuario_mestre  INT           NOT NULL,
    id_sistema         INT           DEFAULT NULL,
    nm_campanha        VARCHAR(100)  NOT NULL,
    ds_descricao       VARCHAR(1500) DEFAULT NULL,
    ds_imagem          VARCHAR(300)  DEFAULT NULL,
    dt_inicio          DATE          DEFAULT NULL,
    fl_ativa           TINYINT(1)    NOT NULL DEFAULT 1,
    dt_criacao         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_campanha PRIMARY KEY (id_campanha),
    CONSTRAINT fk_campanha_mestre FOREIGN KEY (id_usuario_mestre)
        REFERENCES tb_usuario (id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_campanha_sistema FOREIGN KEY (id_sistema)
        REFERENCES tb_sistema (id_sistema) ON DELETE CASCADE
);

CREATE INDEX idx_campanha_mestre ON tb_campanha (id_usuario_mestre);
CREATE INDEX idx_campanha_sistema ON tb_campanha (id_sistema);

-- ┌────────────────────────────────────────────────────────┐
-- │  [21] TABELA: tb_campanha_usuario                      │
-- ├────────────────────────────────────────────────────────┤
-- │  Participantes vinculados à mesa de jogo com seus      │
-- │  cargos ativos (Mestre, Jogador).                      │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_campanha_usuario (
    id_campanha_usuario INT  NOT NULL AUTO_INCREMENT,
    id_campanha         INT  NOT NULL,
    id_usuario          INT  NOT NULL,
    tp_papel            ENUM('mestre','jogador') NOT NULL DEFAULT 'jogador',
    dt_entrada          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_campanha_usuario PRIMARY KEY (id_campanha_usuario),
    CONSTRAINT fk_cu_campanha FOREIGN KEY (id_campanha)
        REFERENCES tb_campanha (id_campanha) ON DELETE CASCADE,
    CONSTRAINT fk_cu_usuario FOREIGN KEY (id_usuario)
        REFERENCES tb_usuario (id_usuario) ON DELETE CASCADE,
    CONSTRAINT uq_campanha_usuario UNIQUE (id_campanha, id_usuario)
);

CREATE INDEX idx_cu_campanha ON tb_campanha_usuario (id_campanha);
CREATE INDEX idx_cu_usuario ON tb_campanha_usuario (id_usuario);

-- ┌────────────────────────────────────────────────────────┐
-- │  [22] TABELA: tb_campanha_personagem                  │
-- ├────────────────────────────────────────────────────────┤
-- │  Controla os personagens ativos associados à           │
-- │  campanha e seu status de visibilidade pública.       │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_campanha_personagem (
    id_campanha_personagem INT NOT NULL AUTO_INCREMENT,
    id_campanha            INT NOT NULL,
    id_personagem          INT NOT NULL,
    fl_publico             TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT pk_campanha_personagem PRIMARY KEY (id_campanha_personagem),
    CONSTRAINT fk_cp_campanha FOREIGN KEY (id_campanha)
        REFERENCES tb_campanha (id_campanha) ON DELETE CASCADE,
    CONSTRAINT fk_cp_personagem FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem) ON DELETE CASCADE,
    CONSTRAINT uq_campanha_personagem UNIQUE (id_campanha, id_personagem)
);

CREATE INDEX idx_cp_campanha ON tb_campanha_personagem (id_campanha);

-- ┌────────────────────────────────────────────────────────┐
-- │  [23] TABELA: tb_sessao                                │
-- ├────────────────────────────────────────────────────────┤
-- │  Histórico de sessões de jogo de uma campanha com     │
-- │  seus resumos estruturados.                            │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_sessao (
    id_sessao      INT           NOT NULL AUTO_INCREMENT,
    id_campanha    INT           NOT NULL,
    nm_sessao      VARCHAR(100)  DEFAULT NULL,
    ds_resumo      VARCHAR(2000) DEFAULT NULL,
    dt_sessao      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    qt_duracao_min INT           DEFAULT NULL,
    CONSTRAINT pk_sessao PRIMARY KEY (id_sessao),
    CONSTRAINT fk_sessao_campanha FOREIGN KEY (id_campanha)
        REFERENCES tb_campanha (id_campanha) ON DELETE CASCADE
);

CREATE INDEX idx_sessao_campanha ON tb_sessao (id_campanha);

-- ┌────────────────────────────────────────────────────────┐
-- │  [24] TABELA: tb_combate                               │
-- ├────────────────────────────────────────────────────────┤
-- │  Encontros de combate criados pelo mestre no escudo    │
-- │  para conduzir cenas de ação.                          │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_combate (
    id_combate   INT          NOT NULL AUTO_INCREMENT,
    id_campanha  INT          NOT NULL,
    id_sessao    INT          DEFAULT NULL,
    nm_combate   VARCHAR(100) DEFAULT NULL,
    fl_concluido TINYINT(1)   DEFAULT 0,
    dt_combate   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_combate PRIMARY KEY (id_combate),
    CONSTRAINT fk_combate_campanha FOREIGN KEY (id_campanha)
        REFERENCES tb_campanha (id_campanha) ON DELETE CASCADE,
    CONSTRAINT fk_combate_sessao FOREIGN KEY (id_sessao)
        REFERENCES tb_sessao (id_sessao) ON DELETE SET NULL
);

CREATE INDEX idx_combate_campanha ON tb_combate (id_campanha);

-- ┌────────────────────────────────────────────────────────┐
-- │  [25] TABELA: tb_combate_monstro                       │
-- ├────────────────────────────────────────────────────────┤
-- │  Ameaças vinculadas ao encontro de combate com suas    │
-- │  quantidades definidas pelo mestre.                    │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_combate_monstro (
    id_combate_monstro INT NOT NULL AUTO_INCREMENT,
    id_combate         INT NOT NULL,
    id_monstro         INT NOT NULL,
    qt_quantidade      INT DEFAULT 1,
    CONSTRAINT pk_combate_monstro PRIMARY KEY (id_combate_monstro),
    CONSTRAINT fk_cm_combate FOREIGN KEY (id_combate)
        REFERENCES tb_combate (id_combate) ON DELETE CASCADE,
    CONSTRAINT fk_cm_monstro FOREIGN KEY (id_monstro)
        REFERENCES tb_monstro (id_monstro) ON DELETE CASCADE
);

CREATE INDEX idx_cm_combate ON tb_combate_monstro (id_combate);

-- ┌────────────────────────────────────────────────────────┐
-- │  [26] TABELA: tb_monstro_atributo                      │
-- ├────────────────────────────────────────────────────────┤
-- │  Valores de atributos individuais de cada monstro do   │
-- │  bestiário de um sistema.                              │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_monstro_atributo (
    id_monstro_atributo INT NOT NULL AUTO_INCREMENT,
    id_monstro          INT NOT NULL,
    id_atributo         INT NOT NULL,
    qt_valor            INT DEFAULT 0,
    CONSTRAINT pk_monstro_atributo PRIMARY KEY (id_monstro_atributo),
    CONSTRAINT fk_ma_monstro FOREIGN KEY (id_monstro)
        REFERENCES tb_monstro (id_monstro) ON DELETE CASCADE,
    CONSTRAINT fk_ma_atributo FOREIGN KEY (id_atributo)
        REFERENCES tb_atributo (id_atributo) ON DELETE CASCADE,
    CONSTRAINT uq_monstro_atributo UNIQUE (id_monstro, id_atributo)
);

CREATE INDEX idx_ma_monstro ON tb_monstro_atributo (id_monstro);

-- ┌────────────────────────────────────────────────────────┐
-- │  [27] TABELA: tb_monstro_pericia                       │
-- ├────────────────────────────────────────────────────────┤
-- │  Bônus de perícias que as ameaças do bestiário         │
-- │  possuem ativas.                                       │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_monstro_pericia (
    id_monstro_pericia INT NOT NULL AUTO_INCREMENT,
    id_monstro         INT NOT NULL,
    id_pericia         INT NOT NULL,
    qt_valor           INT DEFAULT 0,
    CONSTRAINT pk_monstro_pericia PRIMARY KEY (id_monstro_pericia),
    CONSTRAINT fk_mp_monstro FOREIGN KEY (id_monstro)
        REFERENCES tb_monstro (id_monstro) ON DELETE CASCADE,
    CONSTRAINT fk_mp_pericia FOREIGN KEY (id_pericia)
        REFERENCES tb_pericia (id_pericia) ON DELETE CASCADE,
    CONSTRAINT uq_monstro_pericia UNIQUE (id_monstro, id_pericia)
);

CREATE INDEX idx_mp_monstro ON tb_monstro_pericia (id_monstro);

-- ┌────────────────────────────────────────────────────────┐
-- │  [28] TABELA: tb_rolagem_dado                          │
-- ├────────────────────────────────────────────────────────┤
-- │  Histórico de rolagens de dados (D20, D6, etc.) feitas │
-- │  por usuários em campanhas e sessões.                  │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_rolagem_dado (
    id_rolagem       INT          NOT NULL AUTO_INCREMENT,
    id_usuario       INT          NOT NULL,
    id_personagem    INT          DEFAULT NULL,
    id_campanha      INT          DEFAULT NULL,
    id_sessao        INT          DEFAULT NULL,
    ds_dado          VARCHAR(10)  NOT NULL,
    qt_resultado     INT          NOT NULL,
    qt_modificador   INT          DEFAULT 0,
    ds_contexto      VARCHAR(200) DEFAULT NULL,
    dt_rolagem       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_rolagem PRIMARY KEY (id_rolagem),
    CONSTRAINT fk_rolagem_usuario FOREIGN KEY (id_usuario)
        REFERENCES tb_usuario (id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_rolagem_personagem FOREIGN KEY (id_personagem)
        REFERENCES tb_personagem (id_personagem) ON DELETE CASCADE,
    CONSTRAINT fk_rolagem_campanha FOREIGN KEY (id_campanha)
        REFERENCES tb_campanha (id_campanha) ON DELETE CASCADE,
    CONSTRAINT fk_rolagem_sessao FOREIGN KEY (id_sessao)
        REFERENCES tb_sessao (id_sessao) ON DELETE SET NULL
);

CREATE INDEX idx_rolagem_usuario ON tb_rolagem_dado (id_usuario);
CREATE INDEX idx_rolagem_campanha ON tb_rolagem_dado (id_campanha);

-- ┌────────────────────────────────────────────────────────┐
-- │  [29] TABELA: tb_convite_campanha                      │
-- ├────────────────────────────────────────────────────────┤
-- │  Gerenciamento de convites enviados para participantes │
-- │  via tokens e links com controle de expiração.         │
-- └────────────────────────────────────────────────────────┘
CREATE TABLE tb_convite_campanha (
    id_convite    INT          NOT NULL AUTO_INCREMENT,
    id_campanha   INT          NOT NULL,
    ds_email      VARCHAR(100) DEFAULT NULL,
    ds_token      VARCHAR(64)  DEFAULT NULL,
    tp_status     ENUM('pendente','aceito','recusado','expirado') NOT NULL DEFAULT 'pendente',
    dt_criacao    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dt_expiracao  DATETIME     DEFAULT NULL,
    CONSTRAINT pk_convite_campanha PRIMARY KEY (id_convite),
    CONSTRAINT fk_convite_campanha_ref FOREIGN KEY (id_campanha)
        REFERENCES tb_campanha (id_campanha) ON DELETE CASCADE
);

CREATE INDEX idx_convite_campanha_token ON tb_convite_campanha (ds_token);

-- ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
--              DADO INICIAL PREDEFINIDO: SISTEMA ORDEM PARANORMAL
-- ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
INSERT INTO tb_sistema (id_sistema, nm_sistema, ds_descricao, ds_imagem, ds_background, tp_classificacao, id_usuario_criador) VALUES
(1, 'Ordem Paranormal', 'Sistema oficial, criado por TABLE. Um sistema de RPG focado em investigação e terror paranormal. Em Ordem Paranormal, os jogadores assumem papéis de personagens de uma organização secreta conhecida como Ordo Realitas, cujo objetivo é combater entidades e fenômenos paranormais que ameaçam a realidade. O Medo é a força motriz que enfraquece a membrana entre o nosso mundo e o Outro Lado.', '../img/ordem-paranormal-icon.png', '../img/ordem_paranormal_background.webp', '14', NULL);

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
('Atletismo',    'Força',      1),
('Luta',         'Força',      1),
('Pontaria',     'Agilidade',  1),
('Furtividade',  'Agilidade',  1),
('Acrobacia',    'Agilidade',  1),
('Crime',        'Agilidade',  1),
('Iniciativa',   'Agilidade',  1),
('Pilotagem',    'Agilidade',  1),
('Reflexos',     'Agilidade',  1),
('Ocultismo',    'Intelecto',  1),
('Investigação', 'Intelecto',  1),
('Atualidades',  'Intelecto',  1),
('Ciências',     'Intelecto',  1),
('Medicina',     'Intelecto',  1),
('Profissão',    'Intelecto',  1),
('Sobrevivência','Intelecto',  1),
('Tática',       'Intelecto',  1),
('Tecnologia',   'Intelecto',  1),
('Diplomacia',   'Presença',   1),
('Intimidação',  'Presença',   1),
('Adestramento', 'Presença',   1),
('Artes',        'Presença',   1),
('Enganação',    'Presença',   1),
('Intuição',     'Presença',   1),
('Percepção',    'Presença',   1),
('Religião',     'Presença',   1),
('Vontade',      'Presença',   1),
('Fortitude',    'Vigor',      1);


-- ============================================================
-- DADOS INICIAIS DE USUÁRIO E MONSTROS (Gerados via Atualização)
-- ============================================================

-- Inserindo o novo Admin: Kauan Bryan
-- Lembre-se: A senha continua sendo: admin123
INSERT INTO tb_usuario (id_usuario, nm_exibicao, nm_usuario, ds_email, ds_senha, dt_nascimento, tp_cargo, ds_foto, ds_bio) VALUES 
(1, 'Kauan Bryan', 'Kauan Bryan', 'table@gmail.com', '$2y$10$eIVnZbA5xAVj1dDjDHQnKOhTiTj0LbibokkPhLuWtO.mgIgfDplfq', '1990-01-01', 'admin', '../img/uploads/perfil/avatar1.png', 'Administrador principal da plataforma TABLE. Responsável pelo gerenciamento de sistemas e manutenção da ordem.');


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
-- INSERTS — HABILIDADES E RITUAIS — ORDEM PARANORMAL (id_sistema = 1)
-- Tabela alvo: tb_habilidade
-- Colunas: nm_habilidade, ds_habilidade, tp_habilidade, qt_custo_esforco, id_sistema
-- tp_habilidade: 'ativa' | 'passiva' | 'reacao'
-- qt_custo_esforco: custo em PE (Pontos de Esforço)
-- ============================================================
-- Este arquivo deve ser executado APÓS o db_table.sql
-- (que já cria a tabela tb_habilidade e insere o sistema 1)
-- ============================================================

USE db_table;

-- ============================================================
-- HABILIDADES DE CLASSE — COMBATENTE
-- ============================================================

INSERT INTO tb_habilidade (nm_habilidade, ds_habilidade, tp_habilidade, qt_custo_esforco, id_sistema) VALUES

-- NEX 5%
('Ataque Pesado',
 'Você pode gastar 2 PE para realizar um ataque poderoso. Se acertar, causa +1d6 de dano. A cada 2 círculos acima do 1º que possuir desta habilidade, o bônus de dano aumenta em +1d6.',
 'ativa', 2, 1),

('Postura de Combate',
 'Você aprende uma das seguintes posturas de combate: Agressiva (+2 dano, -2 Defesa), Defensiva (+2 Defesa, -2 ataques) ou Reflexiva (+2 Reflexos em armadilhas). Ativar ou desativar uma postura é uma ação de movimento. Você pode aprender posturas adicionais comprando esta habilidade novamente.',
 'ativa', 0, 1),

('Estilo de Luta',
 'Você adota um estilo de luta e recebe seus benefícios enquanto nas condições indicadas: Combate com Duas Armas (penalidade reduzida), Estilo de Uma Arma (+2 Defesa com arma de uma mão e outra mão vazia), Estilo Desarmado (dano desarmado aumenta uma categoria), Combate com Arma de Arremesso (+2 dano em arremessos) ou Atirador (+2 dano com armas de fogo).',
 'passiva', 0, 1),

('Movimento de Combate',
 'Você pode se mover e realizar uma ação de ataque em qualquer ponto durante o movimento. Para cada 3 NEX acima do 5% que possuir desta habilidade, você pode atacar um alvo adicional durante o movimento.',
 'ativa', 0, 1),

-- NEX 15%
('Ataque Multiplo',
 'Você pode gastar 2 PE para realizar um segundo ataque no mesmo turno como ação livre após o primeiro ataque. O segundo ataque sofre -1d de penalidade. Cada vez que comprar esta habilidade, pode fazer um ataque adicional. Limite de ataques por turno com esta habilidade: 3.',
 'ativa', 2, 1),

('Durão',
 'Seu limite de pontos de vida aumenta em +4 por NEX. Além disso, você pode gastar 2 PE como reação para reduzir o dano recebido em 5 pontos.',
 'passiva', 0, 1),

('Valentão',
 'Ao fazer um ataque corpo a corpo, você pode gastar 2 PE para tentar intimidar o alvo. Se acertar o ataque, realize um teste de Intimidação (DT = resultado do teste de resistência do alvo). Se vencer, o alvo fica abalado por 1 rodada.',
 'ativa', 2, 1),

-- NEX 25%
('Bloqueio',
 'Você pode gastar 2 PE como reação quando sofrer um ataque para reduzir o dano recebido em 1d12 + metade do seu nível. Esta habilidade melhora conforme o NEX.',
 'reacao', 2, 1),

('Desarmar',
 'Você pode gastar 2 PE para fazer a manobra de combate Desarmar sem ação adicional. Além disso, você pode usar a manobra Desarmar como reação quando sofrer um ataque com arma.',
 'ativa', 2, 1),

('Resistência a Dano',
 'Você recebe resistência a dano de impacto, perfuração e corte igual ao seu modificador de Vigor.',
 'passiva', 0, 1),

-- NEX 35%
('Ataques Rapidos',
 'Uma vez por turno, quando você causar dano a um alvo, pode gastar 3 PE para realizar um ataque adicional imediatamente contra o mesmo alvo.',
 'ativa', 3, 1),

('Golpe Certeiro',
 'Ao acertar um ataque, você pode gastar 2 PE para anular qualquer resistência a dano que o alvo possua até o fim do seu próximo turno.',
 'ativa', 2, 1),

('Implacável',
 'Você pode continuar agindo mesmo com condições que o impediriam. Quando for reduzido a 0 PV, pode gastar 4 PE como reação para agir normalmente durante mais um turno antes de cair inconsciente.',
 'reacao', 4, 1),

-- NEX 45%
('Maquina de Guerra',
 'Você pode realizar uma ação de ataque e uma ação de movimento adicionais em cada um de seus turnos. Você deve ter esta habilidade ativa para receber o bônus (ação de movimento para ativar).',
 'ativa', 0, 1);


-- ============================================================
-- HABILIDADES DE CLASSE — ESPECIALISTA
-- ============================================================

INSERT INTO tb_habilidade (nm_habilidade, ds_habilidade, tp_habilidade, qt_custo_esforco, id_sistema) VALUES

-- NEX 5%
('Habilidoso',
 'Escolha duas perícias quaisquer. Você recebe +2 nessas perícias. Você pode comprar esta habilidade várias vezes, sempre escolhendo duas novas perícias.',
 'passiva', 0, 1),

('Foco em Perícia',
 'Escolha uma perícia. Ao fazer um teste nessa perícia, você pode gastar 1 PE para receber +5 no resultado. Você pode comprar esta habilidade várias vezes, escolhendo uma perícia diferente a cada vez.',
 'ativa', 1, 1),

('Pesquisa Rápida',
 'Você pode gastar 2 PE para fazer um teste de Investigação, Ocultismo, Ciências ou Atualidades como ação livre uma vez por rodada.',
 'ativa', 2, 1),

-- NEX 15%
('Ataque Furtivo',
 'Quando você ataca um alvo desprevenido ou flanqueado, causa +2d6 de dano adicional. Este bônus aumenta em +1d6 a cada 2 NEX acima de 15% que você possuir desta habilidade.',
 'passiva', 0, 1),

('Esquiva Reflexiva',
 'Você pode gastar 2 PE como reação para receber +5 em Reflexos ao resistir a um efeito de área.',
 'reacao', 2, 1),

('Expert',
 'Escolha uma perícia. Você pode usar essa perícia mesmo sem treinamento e recebe +2 nela. Além disso, pode gastar 2 PE para rolar duas vezes e ficar com o melhor resultado nessa perícia.',
 'ativa', 2, 1),

-- NEX 25%
('Parceiro',
 'Você pode gastar 2 PE para conceder a um aliado adjacente bônus de +2 em qualquer teste até o início do seu próximo turno.',
 'ativa', 2, 1),

('Bote',
 'Você pode gastar 2 PE para realizar uma ação de ataque após usar uma ação de movimento, mesmo que já tenha atacado neste turno.',
 'ativa', 2, 1),

('Perfil Falso',
 'Você pode gastar 2 PE para criar uma identidade falsa convincente, recebendo +5 em Enganação para mantê-la. Cada tentativa de desmascarar o perfil requer um teste de Investigação DT 20.',
 'ativa', 2, 1),

-- NEX 35%
('Golpe Preciso',
 'Quando você acerta um ataque, pode gastar 3 PE para que ele trate qualquer resultado no dado como o valor máximo possível.',
 'ativa', 3, 1),

('Mestre das Sombras',
 'Em condições de pouca iluminação, você recebe +3 em Furtividade e seus ataques contra alvos desprevenidos causam +1d6 de dano extra.',
 'passiva', 0, 1),

-- NEX 45%
('Letal',
 'Seus ataques críticos causam o dobro de dados de dano, em vez de simplesmente multiplicar o dano base.',
 'passiva', 0, 1),

('Sombra Perfeita',
 'Você pode gastar 3 PE para se tornar indetectável por todos os sentidos comuns por 1d4 rodadas. Qualquer ação ofensiva encerra o efeito imediatamente.',
 'ativa', 3, 1);


-- ============================================================
-- HABILIDADES DE CLASSE — OCULTISTA
-- ============================================================

INSERT INTO tb_habilidade (nm_habilidade, ds_habilidade, tp_habilidade, qt_custo_esforco, id_sistema) VALUES

-- NEX 5%
('Poder Paranormal',
 'Você aprende um poder paranormal de 1º círculo de um elemento à sua escolha (Sangue, Morte, Energia ou Conhecimento). Você pode comprar esta habilidade mais vezes para aprender poderes adicionais de círculos maiores conforme seu NEX avança.',
 'ativa', 0, 1),

('Sentido Paranormal',
 'Você sente a presença de criaturas e objetos paranormais a até 30 metros. Você sabe a direção da fonte, mas não o tipo nem a identidade. Gastar 2 PE permite detectar o elemento da fonte.',
 'passiva', 0, 1),

('Proteção Paranormal',
 'Você recebe resistência a dano de Sangue, Morte, Energia e Conhecimento igual ao seu modificador de Presença.',
 'passiva', 0, 1),

-- NEX 15%
('Mediunidade',
 'Você pode gastar 3 PE para se comunicar com espíritos de seres falecidos por até 10 minutos. Os espíritos só respondem perguntas que soubessem em vida e podem recusar-se a colaborar (teste de Presença DT 15).',
 'ativa', 3, 1),

('Canal Paranormal',
 'Ao conjurar um ritual ou usar um poder paranormal, você pode gastar PE adicional para aumentar o efeito. Para cada 2 PE extras gastos, adicione +1 ao grau de efeito (dano, cura ou duração), limitado pelo seu NEX.',
 'ativa', 0, 1),

('Mente Resistente',
 'Você recebe +5 em testes de resistência contra efeitos mentais e paranormais. Além disso, quando falhar em um teste de Sanidade, pode gastar 2 PE para rolar novamente e ficar com o melhor resultado.',
 'passiva', 0, 1),

-- NEX 25%
('Invocacao Menor',
 'Você pode gastar 4 PE para invocar uma criatura paranormal de grau menor (VD até 2) do mesmo elemento de um de seus poderes. A criatura age no seu turno por 1d4 rodadas, depois retorna ao Outro Lado.',
 'ativa', 4, 1),

('Despertar Paranormal',
 'Você pode gastar 3 PE para ativar uma área de influência paranormal em raio de 9 metros por 1 minuto. Dentro da área, testes relacionados ao seu elemento recebem +3 e criaturas do elemento oposto sofrem penalidade de -2.',
 'ativa', 3, 1),

-- NEX 35%
('Absorvendo Elemento',
 'Você pode gastar 3 PE como reação ao sofrer dano paranormal do seu elemento para absorver parte do dano: ignore pontos de dano iguais ao seu modificador de Presença e converta esse valor em PE (até o limite máximo).',
 'reacao', 3, 1),

('Possessao Menor',
 'Você pode gastar 5 PE para tentar possuir um ser humanóide por 1 rodada. O alvo pode resistir com um teste de Vontade DT 15 + seu modificador de Presença. Enquanto possuído, o alvo age sob seu comando.',
 'ativa', 5, 1),

-- NEX 45%
('Ritual Aprimorado',
 'Ao conjurar rituais de qualquer círculo, o custo de PE é reduzido em 2 (mínimo 1). Além disso, você pode conjurar rituais de 1º círculo como ação livre uma vez por rodada.',
 'passiva', 0, 1),

('Transcendência',
 'Uma vez por missão, você pode gastar 6 PE para entrar em estado de transcendência paranormal por 3 rodadas. Neste estado, todos os seus poderes e rituais custam metade dos PE, sua DT aumenta em 5 e você recebe resistência 15 a todos os tipos de dano paranormal.',
 'ativa', 6, 1);


-- ============================================================
-- RITUAIS — ELEMENTO SANGUE
-- ============================================================

INSERT INTO tb_habilidade (nm_habilidade, ds_habilidade, tp_habilidade, qt_custo_esforco, id_sistema) VALUES

-- 1º Círculo de Sangue
('Chamas do Inferno (1)',
 'Ritual de 1º círculo de Sangue. Você cria labaredas paranormais que causam 2d6 de dano de Sangue em um alvo em alcance curto (DT Agi para reduzir à metade). Componentes: fósforo e sangue.',
 'ativa', 2, 1),

('Escudo de Sangue (1)',
 'Ritual de 1º círculo de Sangue. Cria uma barreira de Sangue que absorve até 10 pontos de dano físico. Dura até ser destruída ou até o fim da missão. Componentes: sangue e metal.',
 'ativa', 2, 1),

('Drenar Vitalidade (1)',
 'Ritual de 1º círculo de Sangue. Em contato com um ser vivo, drena 1d6 de PV e você recupera a mesma quantidade (DT For para resistir). Componentes: navalha e fluido corporal.',
 'ativa', 2, 1),

('Sangue Fervente (1)',
 'Ritual de 1º círculo de Sangue. Causa 1d6 de dano de Sangue por rodada em uma criatura tocada por até 3 rodadas (DT Vig para encerrar). Componentes: pimenta-malagueta e sangue.',
 'ativa', 2, 1),

('Correntes de Sangue (1)',
 'Ritual de 1º círculo de Sangue. Cria correntes paranormais que imobilizam um alvo em alcance curto por 1d4 rodadas (DT Força para resistir). Componentes: arame farpado e sangue.',
 'ativa', 3, 1),

-- 2º Círculo de Sangue
('Banho de Sangue (2)',
 'Ritual de 2º círculo de Sangue. Você borrifa Sangue paranormal em raio de 6 metros, causando 3d6 de dano em todos na área (DT Agi para reduzir à metade). Componentes: sangue em quantidade, navalha.',
 'ativa', 4, 1),

('Armor de Carne (2)',
 'Ritual de 2º círculo de Sangue. Envolve seu corpo com carne e Sangue paranormals, fornecendo resistência 10 a dano físico por 1 minuto. Componentes: carne crua e correntes.',
 'ativa', 4, 1),

('Explosão Sanguínea (2)',
 'Ritual de 2º círculo de Sangue. Causa 4d6 de dano de Sangue em um alvo em alcance médio (DT Vig para resistir). Alvos reduzidos a 0 PV por este ritual explodem, causando 2d6 de dano em todos adjacentes. Componentes: órgão e sangue.',
 'ativa', 4, 1),

('Transferência de Ferimento (2)',
 'Ritual de 2º círculo de Sangue. Transfere metade dos PV perdidos de um aliado tocado para um inimigo que você possa ver (DT Vig para resistir ao dano). Componentes: sangue dos dois alvos, agulha.',
 'ativa', 4, 1),

-- 3º Círculo de Sangue
('Forma Bestial (3)',
 'Ritual de 3º círculo de Sangue. Você se transforma parcialmente em uma criatura de Sangue por até 10 minutos: ganha +3 For, garras (1d8 dano) e resistência a dano físico 10. Cada minuto adicional custa 2 PE. Componentes: sangue de animal predador, faca.',
 'ativa', 6, 1),

('Apocalipse de Sangue (3)',
 'Ritual de 3º círculo de Sangue. Cria uma explosão devastadora de Sangue em raio de 9 metros causando 6d6 de dano (DT Vig para reduzir à metade). Componentes: órgão, sangue em grande quantidade.',
 'ativa', 8, 1),

('Reanimar (3)',
 'Ritual de 3º círculo de Sangue. Você reanima um cadáver fresco como um zumbi de Sangue (perfil de Zumbi de Sangue). O zumbi obedece comandos simples e dura até ser destruído ou até o fim da missão. Componentes: sangue do morto, correntes.',
 'ativa', 6, 1);


-- ============================================================
-- RITUAIS — ELEMENTO MORTE
-- ============================================================

INSERT INTO tb_habilidade (nm_habilidade, ds_habilidade, tp_habilidade, qt_custo_esforco, id_sistema) VALUES

-- 1º Círculo de Morte
('Lodo da Morte (1)',
 'Ritual de 1º círculo de Morte. Joga Lodo paranormal em um alvo em alcance curto, reduzindo seu deslocamento à metade e impondo -2 em ataques por 2 rodadas (DT Vig para resistir). Componentes: argila, folhas secas.',
 'ativa', 2, 1),

('Toque da Podridão (1)',
 'Ritual de 1º círculo de Morte. Ao tocar um alvo, causa 2d6 de dano de Morte e aplica a condição Vulnerável a Morte por 1 rodada (DT Vig para resistir). Componentes: osso pulverizado, cinzas.',
 'ativa', 2, 1),

('Escuridão Sombria (1)',
 'Ritual de 1º círculo de Morte. Cria uma área de escuridão sobrenatural de 6 metros de raio por 3 rodadas. Criaturas de Morte dentro da área recebem +2 em ataques. Componentes: vela apagada, cinzas.',
 'ativa', 2, 1),

('Vislumbre da Morte (1)',
 'Ritual de 1º círculo de Morte. Você vê claramente a presença da Morte em objetos e pessoas em alcance curto — criaturas moribundas, objetos amaldiçoados com Morte e portais de Morte ficam visíveis. Dura 10 minutos. Componentes: osso, pó.',
 'ativa', 2, 1),

('Drenar Sanidade (1)',
 'Ritual de 1º círculo de Morte. Drenar a sanidade de um alvo visível, causando 1d6 de dano de Sanidade (DT Pre para resistir). Componentes: cristal negro, fios de cabelo.',
 'ativa', 2, 1),

-- 2º Círculo de Morte
('Caminhar com os Mortos (2)',
 'Ritual de 2º círculo de Morte. Você se torna indetectável por criaturas mortas-vivas por 10 minutos. Além disso, pode dar comandos simples a esqueletos e zumbis que não estejam sob controle de outro conjurador. Componentes: osso humano, Lodo.',
 'ativa', 4, 1),

('Necrópole (2)',
 'Ritual de 2º círculo de Morte. Cria uma área sagrada da Morte em raio de 6 metros por 10 minutos. Dentro da área, criaturas mortas-vivas recuperam 5 PV no início de cada turno e seres vivos sofrem -2 em todos os testes. Componentes: ossos, galhos secos, velas negras.',
 'ativa', 5, 1),

('Maldição da Decadência (2)',
 'Ritual de 2º círculo de Morte. Maldiz um objeto ou criatura com a Morte. Objetos malditos se deterioram em 24 horas. Criaturas malditas recebem penalidade de -2 em todos os testes e sofrem 2 de dano de Morte por rodada (DT Vig para resistir). Componentes: pó de osso, cristal negro.',
 'ativa', 4, 1),

('Sufocar (2)',
 'Ritual de 2º círculo de Morte. Força Lodo para dentro dos pulmões de um alvo em alcance curto, causando 3d6 de dano de Morte e a condição Sufocando por 2 rodadas (DT Vig para resistir à condição). Componentes: Lodo, dentes.',
 'ativa', 4, 1),

-- 3º Círculo de Morte
('Clamor dos Mortos (3)',
 'Ritual de 3º círculo de Morte. Invoca 1d4 Esqueletos de Lodo que obedecem seus comandos por 10 minutos. Componentes: múltiplos ossos, Lodo em quantidade, vela negra.',
 'ativa', 6, 1),

('Toque da Extinção (3)',
 'Ritual de 3º círculo de Morte. Ao tocar uma criatura, causa 8d6 de dano de Morte. Se a criatura morrer por este ritual, ela não pode ser reanimada por nenhum meio por 24 horas. Componentes: osso de criatura paranormal, cinzas de morto.',
 'ativa', 8, 1),

('Portal da Morte (3)',
 'Ritual de 3º círculo de Morte. Abre um portal conectando sua posição a qualquer ponto que você já tenha visitado. O portal permanece aberto por 1 minuto. Componentes: ossos, cristal negro grande, Lodo.',
 'ativa', 6, 1);


-- ============================================================
-- RITUAIS — ELEMENTO ENERGIA
-- ============================================================

INSERT INTO tb_habilidade (nm_habilidade, ds_habilidade, tp_habilidade, qt_custo_esforco, id_sistema) VALUES

-- 1º Círculo de Energia
('Eletrocutar (1)',
 'Ritual de 1º círculo de Energia. Lança uma descarga elétrica em um alvo em alcance curto, causando 2d6 de dano de Energia (DT Agi para reduzir à metade). Componentes: bateria, fio de cobre.',
 'ativa', 2, 1),

('Pulso de Luz (1)',
 'Ritual de 1º círculo de Energia. Emite um pulso de luz paranormal que cega um alvo visível por 1 rodada (DT Agi para resistir). Componentes: lanterna ou vela, pólvora.',
 'ativa', 2, 1),

('Interferência Eletrônica (1)',
 'Ritual de 1º círculo de Energia. Inutiliza todos os dispositivos eletrônicos em raio de 9 metros por 3 rodadas. Componentes: ímã, moedas.',
 'ativa', 2, 1),

('Aura de Caos (1)',
 'Ritual de 1º círculo de Energia. Você emite uma aura caótica por 2 rodadas. Qualquer ser que tente atacar você ou aliados adjacentes deve rolar 1d6: 1-3 = o ataque é desviado e erra automaticamente. Componentes: dados, pilha.',
 'ativa', 3, 1),

('Velocidade Paranormal (1)',
 'Ritual de 1º círculo de Energia. Você ou um aliado tocado age dois vezes mais rápido por 1 rodada: pode realizar uma ação padrão e uma ação de movimento extras. Componentes: bateria, moeda.',
 'ativa', 3, 1),

-- 2º Círculo de Energia
('Tempestade de Relâmpagos (2)',
 'Ritual de 2º círculo de Energia. Cria uma chuva de relâmpagos em raio de 6 metros, causando 3d6 de dano de Energia em todos na área por 2 rodadas (DT Agi para reduzir à metade por rodada). Componentes: circuito eletrônico, pólvora.',
 'ativa', 5, 1),

('Escudo de Energia (2)',
 'Ritual de 2º círculo de Energia. Envolve um alvo em alcance curto com uma barreira de Energia por 2 rodadas. O alvo recebe resistência 10 a todos os tipos de dano paranormal. Componentes: fio de cobre, cristal.',
 'ativa', 4, 1),

('Surto de Energia (2)',
 'Ritual de 2º círculo de Energia. Lança uma rajada poderosa de Energia pura em um alvo, causando 4d6 de dano de Energia e empurrando-o até 6 metros (DT Agi para resistir ao empurrão). Componentes: bateria grande, cabo de cobre.',
 'ativa', 4, 1),

('Rastejar na Rede (2)',
 'Ritual de 2º círculo de Energia. Sua consciência pode entrar em qualquer rede ou sistema eletrônico em alcance médio por 10 minutos. Você pode acessar dados, câmeras e controles remotos. Corpo fica vulnerável durante o ritual. Componentes: smartphone, fio de prata.',
 'ativa', 4, 1),

-- 3º Círculo de Energia
('Colapso de Energia (3)',
 'Ritual de 3º círculo de Energia. Cria uma explosão caótica de Energia em raio de 9 metros causando 6d6 de dano de Energia. Dispositivos eletrônicos são destruídos. Alvos que falhem no teste de resistência ficam atordoados por 1 rodada (DT Agi). Componentes: vários dispositivos eletrônicos, pólvora, ímãs.',
 'ativa', 8, 1),

('Possessão Tecnológica (3)',
 'Ritual de 3º círculo de Energia. Você toma controle de um robô, veículo automatizado ou sistema de IA em alcance médio por 10 minutos. Componentes: smartphone, circuito integrado, fio de prata.',
 'ativa', 6, 1),

('Fragmentação da Realidade (3)',
 'Ritual de 3º círculo de Energia. Distorce as leis da física em raio de 6 metros por 3 rodadas. Dentro da área, todos os movimentos são aleatórios (1d4 direções), as distâncias parecem maiores ou menores (testes de Pontaria com -2d), e os objetos parecem flutuar. Componentes: ímãs, dados, moedas em ouro.',
 'ativa', 7, 1);


-- ============================================================
-- RITUAIS — ELEMENTO CONHECIMENTO
-- ============================================================

INSERT INTO tb_habilidade (nm_habilidade, ds_habilidade, tp_habilidade, qt_custo_esforco, id_sistema) VALUES

-- 1º Círculo de Conhecimento
('Decifrar Símbolos (1)',
 'Ritual de 1º círculo de Conhecimento. Você pode compreender qualquer texto, código ou símbolo paranormal que ver pelo próximo 1 hora, independente do idioma ou elemento. Componentes: papel, instrumento de escrita.',
 'ativa', 2, 1),

('Leitura da Mente (1)',
 'Ritual de 1º círculo de Conhecimento. Lê os pensamentos superficiais de um alvo visível (o que está pensando no momento). O alvo pode resistir com um teste de Vontade DT 15. Componentes: pedra preciosa, papel com sigilo.',
 'ativa', 2, 1),

('Sigilo de Proteção (1)',
 'Ritual de 1º círculo de Conhecimento. Traça um sigilo protetor em uma área de até 3x3 metros. Qualquer criatura paranormal que cruzar o sigilo recebe 2d6 de dano de Conhecimento. O sigilo dura 24 horas. Componentes: giz, lápis, papel.',
 'ativa', 2, 1),

('Apagar Memória (1)',
 'Ritual de 1º círculo de Conhecimento. Apaga até 10 minutos de memória de um alvo tocado (DT Vontade para resistir). Componentes: papel com nome do alvo, borracha.',
 'ativa', 3, 1),

('Projeção Astral (1)',
 'Ritual de 1º círculo de Conhecimento. Sua consciência se separa do corpo e pode explorar até 30 metros de distância por 10 minutos. O corpo fica vulnerável. Componentes: papel, cordão de seda.',
 'ativa', 3, 1),

-- 2º Círculo de Conhecimento
('Implante de Memória (2)',
 'Ritual de 2º círculo de Conhecimento. Implanta uma memória falsa na mente de um alvo tocado (DT Vontade para resistir DT 20). A memória é indistinguível de real por até 24 horas. Componentes: papel, tinta, pedra preciosa.',
 'ativa', 5, 1),

('Inexistir (2)',
 'Ritual de 2º círculo de Conhecimento. Você se torna completamente imperceptível por todos os sentidos comuns por 3 rodadas. Qualquer ação ofensiva encerra o efeito. Componentes: máscara, cordão de seda, papel em branco.',
 'ativa', 4, 1),

('Conhecimento Proibido (2)',
 'Ritual de 2º círculo de Conhecimento. Você absorve instantaneamente todo o conhecimento paranormal presente em um livro, manuscrito ou objeto de Conhecimento. Ganha +5 em Ocultismo por 1 hora. O custo: perde 1d6 de Sanidade. Componentes: o objeto a ser absorvido.',
 'ativa', 4, 1),

('Barreira do Conhecimento (2)',
 'Ritual de 2º círculo de Conhecimento. Cria uma barreira de sigilos dourados em uma área de até 6x6 metros. Criaturas de Conhecimento não podem cruzar a barreira. Dura 10 minutos ou até ser destruída (20 PV). Componentes: giz, pergaminho, pedra preciosa.',
 'ativa', 5, 1),

-- 3º Círculo de Conhecimento
('Reescrever a Realidade (3)',
 'Ritual de 3º círculo de Conhecimento. Você declara uma mudança pequena na Realidade que é aceita como verdadeira por até 10 minutos (ex.: uma porta que não estava lá passa a estar, uma memória coletiva é alterada). O Mestre decide o alcance exato do efeito. Custo: 2d6 de Sanidade. Componentes: pergaminho, ouro, máscara.',
 'ativa', 8, 1),

('Nexus do Conhecimento (3)',
 'Ritual de 3º círculo de Conhecimento. Cria um campo de Conhecimento em raio de 9 metros por 3 rodadas. Dentro do campo, você conhece a posição e as intenções de todos. Aliados recebem +3 em todos os testes e criaturas de Conhecimento sofrem 3d6 de dano no início de cada turno. Componentes: múltiplos pergaminhos, pedras preciosas, ouro.',
 'ativa', 8, 1),

('Inexistência (3)',
 'Ritual de 3º círculo de Conhecimento. Apaga completamente um ser ou objeto pequeno da Realidade por até 24 horas — ele simplesmente não existe para efeitos práticos. O ser pode resistir com Vontade DT 25. Seres com NEX muito alto ou criaturas de Conhecimento poderosoas podem ser imunes. Componentes: máscara, ouro, papel com nome do alvo.',
 'ativa', 9, 1);


-- ============================================================
-- PODERES PARANORMAIS — COMBATENTE (Truques)
-- ============================================================

INSERT INTO tb_habilidade (nm_habilidade, ds_habilidade, tp_habilidade, qt_custo_esforco, id_sistema) VALUES

('Armamento Paranormal',
 'Poder paranormal do Combatente. Sua arma corpo a corpo ou arma de arremesso passa a causar dano de Sangue, Morte, Energia ou Conhecimento (à sua escolha ao ativar) por 1 rodada. Dano bônus: +1d6 do elemento escolhido.',
 'ativa', 2, 1),

('Escudo Paranormal',
 'Poder paranormal do Combatente. Como reação ao sofrer dano, você pode criar um escudo paranormal que absorve 1d10 + seu modificador de Vigor pontos de dano. O escudo desaparece ao final do seu próximo turno.',
 'reacao', 2, 1),

('Impacto Paranormal',
 'Poder paranormal do Combatente. Ao acertar um ataque corpo a corpo, causa +1d6 de dano paranormal adicional do elemento à sua escolha e empurra o alvo 3 metros.',
 'ativa', 2, 1);


-- ============================================================
-- PODERES PARANORMAIS — OCULTISTA (Truques)
-- ============================================================

INSERT INTO tb_habilidade (nm_habilidade, ds_habilidade, tp_habilidade, qt_custo_esforco, id_sistema) VALUES

('Destruir Paranormal',
 'Poder paranormal do Ocultista. Ao acertar uma criatura paranormal com um ataque, causa dano adicional de Sangue, Morte, Energia ou Conhecimento igual ao seu modificador de Presença. Escolha o elemento ao usar.',
 'ativa', 1, 1),

('Luz Sagrada',
 'Poder paranormal do Ocultista. Emite um flash de luz paranormal dourada que causa 1d8 de dano de Conhecimento em todas as criaturas paranormais em alcance curto (DT Pre para resistir à metade).',
 'ativa', 2, 1),

('Ancoragem',
 'Poder paranormal do Ocultista. Estabiliza a Membrana em raio de 6 metros por 10 minutos: rituais custam +2 PE a mais dentro da área e criaturas paranormais não conseguem usar habilidades que dependam de danificar a Membrana.',
 'ativa', 3, 1),

('Canalizar Elemento',
 'Poder paranormal do Ocultista. Você toca um aliado e o protege com um escudo elemental por 2 rodadas: ele recebe resistência 10 ao elemento à sua escolha (Sangue, Morte, Energia ou Conhecimento).',
 'ativa', 3, 1);


-- ============================================================
-- HABILIDADES GERAIS (disponíveis a todas as classes)
-- ============================================================

INSERT INTO tb_habilidade (nm_habilidade, ds_habilidade, tp_habilidade, qt_custo_esforco, id_sistema) VALUES

('Medicina de Campo',
 'Habilidade geral. Você pode gastar uma ação completa e 2 PE para estabilizar um aliado com 0 PV sem necessidade de teste (normalmente exigiria Medicina DT 15). Além disso, ao recuperar PV de um aliado com Medicina, adicione seu modificador de Intelecto ao total curado.',
 'ativa', 2, 1),

('Primeiro Socorros',
 'Habilidade geral. Durante um interlúdio, você pode cuidar de até 3 aliados feridos. Cada aliado recupera PV adicionais iguais ao seu modificador de Intelecto além do valor normal do interlúdio.',
 'ativa', 0, 1),

('Tática de Grupo',
 'Habilidade geral. Uma vez por cena, ao analisar o ambiente por 1 rodada (ação de movimento e gasto de 1 PE), você distribui bônus táticos para a equipe: cada aliado recebe +1 em uma perícia específica ou +1 em Defesa até o início do seu próximo turno.',
 'ativa', 1, 1),

('Veterano de Campo',
 'Habilidade geral. Você já viu o suficiente para saber o que esperar. Recebe +2 em Iniciativa e, na primeira rodada do combate, pode agir como se tivesse rolado um resultado 5 pontos mais alto.',
 'passiva', 0, 1),

('Interrogação',
 'Habilidade geral. Ao interrogar um NPC por pelo menos 5 minutos, você pode gastar 2 PE para rolar Intimidação ou Diplomacia com +3. Se tiver sucesso por 5 ou mais pontos, o alvo revela uma informação adicional além do que pretendia contar.',
 'ativa', 2, 1),

('Resistência Mental',
 'Habilidade geral. Você recebe +3 em todos os testes de Vontade para resistir a efeitos mentais. Além disso, quando recuperar Sanidade, recupera 1 ponto a mais.',
 'passiva', 0, 1),

('Improviso Tecnico',
 'Habilidade geral. Uma vez por cena, com materiais disponíveis no ambiente, você pode criar um item improvisado (coquetel molotov, armadilha simples, ferramenta básica) com um teste de Intelecto DT variável pelo mestre (geralmente 10-15).',
 'ativa', 1, 1),

('Camuflagem Urbana',
 'Habilidade geral. Em ambiente urbano, você se mistura à multidão naturalmente. Recebe +3 em Furtividade e Enganação quando tentar se passar por uma pessoa comum, e pode gastar 2 PE para desaparecer em multidão como ação de movimento.',
 'ativa', 2, 1);


-- ============================================================
-- FIM DO SCRIPT
-- ============================================================
-- Resumo dos inserts:
--
-- HABILIDADES DE CLASSE (38):
--   Combatente (12): Ataque Pesado, Postura de Combate, Estilo de Luta,
--     Movimento de Combate, Ataque Múltiplo, Durão, Valentão,
--     Bloqueio, Desarmar, Resistência a Dano, Ataques Rápidos,
--     Golpe Certeiro, Implacável, Máquina de Guerra
--   Especialista (12): Habilidoso, Foco em Perícia, Pesquisa Rápida,
--     Ataque Furtivo, Esquiva Reflexiva, Expert, Parceiro, Bote,
--     Perfil Falso, Golpe Preciso, Mestre das Sombras, Letal, Sombra Perfeita
--   Ocultista (12): Poder Paranormal, Sentido Paranormal, Proteção
--     Paranormal, Mediunidade, Canal Paranormal, Mente Resistente,
--     Invocação Menor, Despertar Paranormal, Absorvendo Elemento,
--     Possessão Menor, Ritual Aprimorado, Transcendência
--
-- RITUAIS POR ELEMENTO (44):
--   Sangue (12): 5 de 1º círculo + 4 de 2º círculo + 3 de 3º círculo
--   Morte (11): 5 de 1º círculo + 4 de 2º círculo + 3 de 3º círculo
--   Energia (11): 5 de 1º círculo + 4 de 2º círculo + 3 de 3º círculo
--   Conhecimento (11): 5 de 1º círculo + 4 de 2º círculo + 3 de 3º círculo
--
-- PODERES PARANORMAIS TRUQUES (7):
--   Combatente (3): Armamento, Escudo, Impacto Paranormal
--   Ocultista (4): Destruir Paranormal, Luz Sagrada, Ancoragem,
--                  Canalizar Elemento
--
-- HABILIDADES GERAIS (8): Medicina de Campo, Primeiro Socorros,
--   Tática de Grupo, Veterano de Campo, Interrogação,
--   Resistência Mental, Improviso Técnico, Camuflagem Urbana
--
-- TOTAL: ~97 habilidades inseridas (id_sistema = 1)
-- ============================================================


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
