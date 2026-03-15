set names utf8mb4;
set time_zone = '-03:00';

set foreign_key_checks = 0; 

drop database if exists db_table_web; 

create database if not exists db_table_web 
    character set utf8mb4 
    collate utf8mb4_unicode_ci;
    
use db_table_web;

-- modulo 1: usuarios
create table tb_usuario(
    id_usuario int not null auto_increment,
    nm_usuario varchar(70) not null,
    ds_email varchar(100) not null unique,
    ds_senha varchar(255) not null, 
    ft_perfil varchar(255) null default null, 
    dt_cadastro datetime null default current_timestamp,
    tp_usuario varchar(20) not null default 'jogador', -- ex: 'jogador', 'mestre', 'admin'
    primary key (id_usuario)
);

-- modulo 2: sistemas (criacao customizada)
create table tb_sistema_rpg(
    id_sistema_rpg int not null auto_increment,
    id_usuario_criador int not null,
    nm_sistema varchar(100) not null,
    ds_descricao varchar(1500),
    cd_classificacao_etaria varchar(2) not null default 'L', -- ex: 'L', '10', '12', '14', '16', '18'
    ft_sistema varchar(255) null default null, 
    ft_fundo_ficha varchar(255) default 'assets/img/fundo_ficha_padrao.jpg',
    ic_publicado int default 0, -- 0 = falso (nao publicado), 1 = verdadeiro (publicado)
    dt_criacao datetime default current_timestamp,
    primary key (id_sistema_rpg),
    foreign key (id_usuario_criador) references tb_usuario(id_usuario) on delete cascade
);

-- atributos (ex: vigor, agilidade)
create table tb_sistema_atributo(
    id_atributo int not null auto_increment,
    id_sistema_rpg int not null,
    nm_atributo varchar(50) not null,
    ds_abreviacao varchar(5) null, 
    qt_valor_inicial int not null default 0,
    nr_ordem int not null default 1,
    primary key(id_atributo),
    foreign key (id_sistema_rpg) references tb_sistema_rpg(id_sistema_rpg) on delete cascade
);

-- barras de status (vida, sanidade - com cor e atributo base)
create table tb_sistema_status(
    id_status int not null auto_increment,
    id_sistema_rpg int not null,
    nm_status varchar(50) not null,
    ds_cor varchar(7) not null default '#FFFFFF', 
    id_atributo_base int null, 
    primary key (id_status),
    foreign key (id_sistema_rpg) references tb_sistema_rpg(id_sistema_rpg) on delete cascade,
    foreign key (id_atributo_base) references tb_sistema_atributo(id_atributo) on delete set null
);

-- barras de defesa (sem cor)
create table tb_sistema_defesa(
    id_defesa int not null auto_increment,
    id_sistema_rpg int not null,
    nm_defesa varchar(50) not null,
    id_atributo_base int null, 
    primary key (id_defesa),
    foreign key (id_sistema_rpg) references tb_sistema_rpg(id_sistema_rpg) on delete cascade,
    foreign key (id_atributo_base) references tb_sistema_atributo(id_atributo) on delete set null
);

-- categorias de personalizacao
create table tb_sistema_classe (
    id_classe int not null auto_increment,
    id_sistema_rpg int not null,
    nm_classe varchar(50) not null,
    ds_descricao varchar(500),
    primary key(id_classe),
    foreign key (id_sistema_rpg) references tb_sistema_rpg(id_sistema_rpg) on delete cascade
);

create table tb_sistema_pericia (
    id_pericia int not null auto_increment,
    id_sistema_rpg int not null,
    nm_pericia varchar(50) not null,
    ds_descricao varchar(500),
    primary key(id_pericia),
    foreign key (id_sistema_rpg) references tb_sistema_rpg(id_sistema_rpg) on delete cascade
);

create table tb_sistema_origem (
    id_origem int not null auto_increment,
    id_sistema_rpg int not null,
    nm_origem varchar(50) not null,
    ds_descricao varchar(500),
    primary key(id_origem),
    foreign key (id_sistema_rpg) references tb_sistema_rpg(id_sistema_rpg) on delete cascade
);

create table tb_sistema_poder (
    id_poder int not null auto_increment,
    id_sistema_rpg int not null,
    nm_poder varchar(50) not null,
    ds_descricao varchar(500),
    primary key(id_poder),
    foreign key (id_sistema_rpg) references tb_sistema_rpg(id_sistema_rpg) on delete cascade
);

create table tb_sistema_equipamento (
    id_equipamento int not null auto_increment,
    id_sistema_rpg int not null,
    nm_equipamento varchar(100) not null,
    ds_descricao varchar(500),
    tp_equipamento varchar(50), 
    primary key (id_equipamento),
    foreign key (id_sistema_rpg) references tb_sistema_rpg(id_sistema_rpg) on delete cascade
);

create table tb_sistema_monstro(
    id_monstro int not null auto_increment,
    id_sistema_rpg int not null,
    nm_monstro varchar(50) not null,
    ds_monstro varchar(1500),
    primary key(id_monstro),
    foreign key (id_sistema_rpg) references tb_sistema_rpg(id_sistema_rpg) on delete cascade
);

-- modulo 3: campanhas e personagens
create table tb_campanha(
    id_campanha int not null auto_increment,
    id_usuario_mestre int not null, 
    id_sistema_rpg int not null,    
    nm_campanha varchar(100) not null,
    ds_descricao varchar(1500),
    primary key (id_campanha),
    foreign key (id_usuario_mestre) references tb_usuario(id_usuario) on delete cascade,
    foreign key (id_sistema_rpg) references tb_sistema_rpg(id_sistema_rpg) on delete cascade
);

create table tb_personagem(
    id_personagem int not null auto_increment,
    id_usuario int not null, 
    id_sistema_rpg int not null,
    nm_personagem varchar(100) not null,
    ds_historia text,
    primary key (id_personagem),
    foreign key (id_usuario) references tb_usuario(id_usuario) on delete cascade,
    foreign key (id_sistema_rpg) references tb_sistema_rpg(id_sistema_rpg) on delete cascade
);

create table tb_campanha_personagem(
    id_campanha int not null,
    id_personagem int not null,
    primary key(id_campanha, id_personagem),
    foreign key (id_campanha) references tb_campanha(id_campanha) on delete cascade,
    foreign key (id_personagem) references tb_personagem(id_personagem) on delete cascade
);

create table tb_personagem_atributo_valor(
    id_personagem int not null,
    id_atributo int not null,
    qt_valor int not null default 0,
    primary key(id_personagem, id_atributo),
    foreign key (id_personagem) references tb_personagem(id_personagem) on delete cascade,
    foreign key (id_atributo) references tb_sistema_atributo(id_atributo) on delete cascade
);

create table tb_personagem_status_valor(
    id_personagem int not null,
    id_status int not null,
    qt_atual int not null default 0,
    qt_maximo int not null default 0,
    primary key(id_personagem, id_status),
    foreign key (id_personagem) references tb_personagem(id_personagem) on delete cascade,
    foreign key (id_status) references tb_sistema_status(id_status) on delete cascade
);

-- modulo 4: utilitarios
create table tb_rolagem_dado(
    id_rolagem int not null auto_increment,
    id_campanha int not null,
    id_usuario int not null,
    id_personagem int null, 
    ds_dado varchar(50), 
    ds_resultado varchar(255), 
    qt_total int,
    dt_rolagem datetime default current_timestamp,
    primary key (id_rolagem),
    foreign key (id_campanha) references tb_campanha(id_campanha) on delete cascade,
    foreign key (id_usuario) references tb_usuario(id_usuario) on delete cascade,
    foreign key (id_personagem) references tb_personagem(id_personagem) on delete cascade
);

create table tb_password_resets (
    id_reset int not null auto_increment,
    id_usuario int not null,
    ds_token varchar(255) not null unique,
    dt_expires datetime not null,
    primary key (id_reset),
    foreign key (id_usuario) references tb_usuario(id_usuario) on delete cascade
);

set foreign_key_checks = 1;

show tables;