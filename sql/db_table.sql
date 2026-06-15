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
    fl_plano_mapas  TINYINT(1)     NOT NULL DEFAULT 0,
    fl_plano_sistemas TINYINT(1)   NOT NULL DEFAULT 0,
    fl_plano_completo TINYINT(1)   NOT NULL DEFAULT 0,
    dt_desistencia_mestre DATETIME DEFAULT NULL,
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

-- ============================================================
-- DADOS DO USUÁRIO ADMINISTRADOR INICIAL
-- ============================================================
-- Inserindo o Admin: Kauan Bryan (Senha: admin123)
INSERT INTO tb_usuario (id_usuario, nm_exibicao, nm_usuario, ds_email, ds_senha, dt_nascimento, tp_cargo, ds_foto, ds_bio, dt_desistencia_mestre) VALUES 
(1, 'Kauan Bryan', 'Kauan Bryan', 'table@gmail.com', '$2y$10$eIVnZbA5xAVj1dDjDHQnKOhTiTj0LbibokkPhLuWtO.mgIgfDplfq', '1990-01-01', 'admin', '../img/uploads/perfil/avatar1.png', 'Administrador principal da plataforma TABLE. Responsável pelo gerenciamento de sistemas e manutenção da ordem.', NOW());
