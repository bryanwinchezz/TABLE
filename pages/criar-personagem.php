<?php
    /**
     *  Após a página de login definir a sessão com os dados do usuario a página index lê a sessão e inicia a mesma
     *  Na navbar temos um if e else para cado o usuario esteja conectado ou não, mudando sendo que: 
     *  SE o usuário estiver logado irá mostrar a foto e o nome do usuário
     */
    session_start();

    // Redireciona para login se não estiver logado
    if (!isset($_SESSION['usuario'])) {
        header('Location: login.php');
        exit;
    }
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Criar Personagem</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">

    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/criar-personagem.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body
    style="display: flex; flex-direction: column; min-height: 100vh; background-color: #311c61; color: #fff; font-family: 'Montserrat', sans-serif;">

    <header>
        <div class="logotipo">
            <a href="index.php"><img src="../img/logo_horizontal.png" alt="Logo TABLE"></a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php" class="ativo">Início</a></li>
                <li><a href="cm-jogar.php">Como Jogar</a></li>
                <li><a href="#">Personagens</a></li>
                <li><a href="#">Mundos</a></li>
                <li><a href="#">Dados</a></li>
                <li><a href="#">Sobre Nós</a></li>
            </ul>
        </nav>
        <?php if (isset($_SESSION['usuario'])): ?>
            <div class="usuario-logado-nav" id="nav-logado"
                onclick="window.location.href='perfil.php'" title="Ir para o Perfil">
                <img src="../img/foto-ficha.jpg" alt="Avatar Navbar" class="avatar-nav">
                <span class="nome-nav"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
                <i class="far fa-star icone-nav"></i>
            </div>
        <?php endif; ?>
    </header>

    <main class="container-sistema">

        <nav class="menu-abas" id="menu-criacao">
            <div class="indicador-aba"></div>
            <button type="button" class="aba ativa" data-index="0" data-alvo="aba-descricao">Descrição</button>
            <button type="button" class="aba" data-index="1" data-alvo="aba-origem">Origem</button>
            <button type="button" class="aba" data-index="2" data-alvo="aba-atributos">Atributos</button>
            <button type="button" class="aba" data-index="3" data-alvo="aba-classe">Classe</button>
        </nav>

        <form id="form-cria-pers" action="" method="post">
            <input type="hidden" name="origemEscolhida" id="input-origem" value="">
            <input type="hidden" name="classeEscolhida" id="input-classe" value="">

            <div id="aba-descricao" class="conteudo-aba ativa">
                <section class="desc">
                    <h1>1. DESCRIÇÃO</h1>
                    <p>Todo herói precisa de uma identidade. Antes de definirmos suas habilidades, escolha o nome,
                        detalhe a aparência e aprofunde a história que conectará seu personagem ao mundo ao seu redor.
                    </p>
                </section>

                <div class="area-inputs-grid">
                    <div class="grupo-form">
                        <label>Nome do Personagem:</label>
                        <input type="text" id="nome" class="input-escuro" name="nome"
                            placeholder="Ex: Arthur Pendragon...">
                    </div>
                    <div class="grupo-form">
                        <label>Nome do Jogador:</label>
                        <input type="text" id="nome-jogador" class="input-escuro" name="nome-jogador"
                            placeholder="Seu nome...">
                    </div>
                </div>

                <div class="caracteristicas-grid">
                    <div class="grupo-form">
                        <label>Aparência</label>
                        <textarea name="aparencia" class="textarea-escuro"
                            placeholder="Gênero, Descrição física, Idade, Cicatrizes..."></textarea>
                    </div>
                    <div class="grupo-form">
                        <label>Personalidade</label>
                        <textarea name="personalidade" class="textarea-escuro"
                            placeholder="Traços de personalidade, motivações, medos..."></textarea>
                    </div>
                    <div class="grupo-form">
                        <label>História</label>
                        <textarea name="historia" class="textarea-escuro"
                            placeholder="História do personagem, eventos importantes, relações com a família..."></textarea>
                    </div>
                    <div class="grupo-form">
                        <label>Objetivos</label>
                        <textarea name="objetivos" class="textarea-escuro"
                            placeholder="Metas e desejos principais da sua jornada..."></textarea>
                    </div>
                </div>

                <div class="botoes-nav-form apenas-proximo">
                    <button type="button" class="btn-form-nav btn-proximo-aba">Próximo <i
                            class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div id="aba-origem" class="conteudo-aba">
                <section class="desc">
                    <h1>2. ORIGEM</h1>
                    <p>Com a identidade definida, de onde você veio? A origem dita o seu passado, oferecendo vantagens
                        únicas e ganchos narrativos. Escolha a que melhor se alinha com a sua história.</p>
                </section>

                <div class="origens-container">
                    <div class="pesq-ori">
                        <i class="fas fa-search icon-pesq"></i>
                        <input class="input-field" type="search" placeholder="Pesquisar">
                    </div>

                    <div class="origem">
                        <div class="origem-header">
                            <i class="fas fa-chevron-down arrow-icon"></i>
                            <h3>Urbano</h3>
                        </div>
                        <div class="origem-content">
                            <p class="descricao">Personagens de origem urbana cresceram nas ruas movimentadas, becos
                                escuros e praças mercantes das grandes cidades. Sabem lidar com a multidão e encontrar o
                                que precisam rápido.</p>
                            <div class="treinamento">
                                <span class="roxo-italico">Treinado em:</span> Intuição ou Ladinagem
                            </div>
                            <div class="area-btn-escolher">
                                <button type="button" class="btn-escolher-origem">Escolher</button>
                            </div>
                        </div>
                    </div>

                    <div class="origem">
                        <div class="origem-header">
                            <i class="fas fa-chevron-down arrow-icon"></i>
                            <h3>Rural</h3>
                        </div>
                        <div class="origem-content">
                            <p class="descricao">Personagens de origem rural cresceram longe dos grandes centros
                                urbanos, em fazendas, aldeias pequenas, comunidades agrícolas ou regiões de floresta
                                densa. Desde cedo tiveram contato direto com a natureza, aprendendo a sobreviver com
                                poucos recursos e a reconhecer perigos que outros talvez nem percebam.</p>
                            <div class="treinamento">
                                <span class="roxo-italico">Treinado em:</span> Sobrevivência
                            </div>
                            <div class="arquetipos">
                                <span class="roxo-italico">Arquétipos Comuns:</span>
                                <ul>
                                    <li><span class="roxo-italico">Caçadores:</span> habilidosos em rastrear, mover-se
                                        silenciosamente, montar armadilhas e conhecer hábitos de animais.</li>
                                    <li><span class="roxo-italico">Batedores:</span> ótimos exploradores, acostumados a
                                        percorrer trilhas, identificar riscos naturais e navegar sem mapas.</li>
                                    <li><span class="roxo-italico">Herbalistas:</span> dedicados ao estudo prático de
                                        plantas, capazes de encontrar ervas curativas, venenos naturais e componentes
                                        raros.</li>
                                </ul>
                            </div>
                            <div class="area-btn-escolher">
                                <button type="button" class="btn-escolher-origem">Escolher</button>
                            </div>
                        </div>
                    </div>

                    <div class="origem">
                        <div class="origem-header">
                            <i class="fas fa-chevron-down arrow-icon"></i>
                            <h3>Acadêmico</h3>
                        </div>
                        <div class="origem-content">
                            <p class="descricao">Passou boa parte da vida entre livros e laboratórios. O conhecimento é
                                sua maior arma e sua sede de saber o levou para a aventura, decifrando idiomas e enigmas
                                antigos.</p>
                            <div class="treinamento">
                                <span class="roxo-italico">Treinado em:</span> Investigação ou Ciências
                            </div>
                            <div class="arquetipos">
                                <span class="roxo-italico">Arquétipos Comuns:</span>
                                <ul>
                                    <li><span class="roxo-italico">Pesquisadores:</span> focados em desvendar mistérios
                                        antigos e ruínas.</li>
                                    <li><span class="roxo-italico">Cientistas:</span> especialistas em criar soluções
                                        lógicas para problemas mágicos.</li>
                                </ul>
                            </div>
                            <div class="area-btn-escolher">
                                <button type="button" class="btn-escolher-origem">Escolher</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="botoes-nav-form">
                    <button type="button" class="btn-form-nav btn-voltar-aba"><i class="fas fa-arrow-left"></i>
                        Voltar</button>
                    <button type="button" class="btn-form-nav btn-proximo-aba">Próximo <i
                            class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div id="aba-atributos" class="conteudo-aba">
                <section class="desc">
                    <h1>3. ATRIBUTOS</h1>
                    <p>Seu passado moldou suas capacidades naturais. Todos começam com 5 pontos básicos de um humano
                        médio. Você possui <strong>10 pontos extras</strong> para distribuir como quiser. Valores que
                        passam de 13 são excepcionais.</p>

                    <div class="pontos-disponiveis">
                        Pontos Disponíveis: <span id="pontos-restantes">10</span>
                    </div>
                </section>

                <div class="atributos-section">
                    <div class="trio-atri">

                        <div class="atributo-item">
                            <span class="atributo-sigla" data-tooltip="Força">FOR</span>
                            <input type="number" class="atributo-input" name="forca" min="5" max="20" value="5">
                        </div>

                        <div class="atributo-item">
                            <span class="atributo-sigla" data-tooltip="Inteligência">INT</span>
                            <input type="number" class="atributo-input" name="inteligencia" min="5" max="20" value="5">
                        </div>

                        <div class="atributo-item">
                            <span class="atributo-sigla" data-tooltip="Vontade">VON</span>
                            <input type="number" class="atributo-input" name="vontade" min="5" max="20" value="5">
                        </div>

                    </div>
                    <div class="trio-atri">

                        <div class="atributo-item">
                            <span class="atributo-sigla" data-tooltip="Agilidade">AGI</span>
                            <input type="number" class="atributo-input" name="agilidade" min="5" max="20" value="5">
                        </div>

                        <div class="atributo-item">
                            <span class="atributo-sigla" data-tooltip="Carisma">CAR</span>
                            <input type="number" class="atributo-input" name="carisma" min="5" max="20" value="5">
                        </div>

                        <div class="atributo-item">
                            <span class="atributo-sigla" data-tooltip="Vigor">VIG</span>
                            <input type="number" class="atributo-input" name="vigor" min="5" max="20" value="5">
                        </div>

                    </div>
                </div>

                <div class="botoes-nav-form">
                    <button type="button" class="btn-form-nav btn-voltar-aba"><i class="fas fa-arrow-left"></i>
                        Voltar</button>
                    <button type="button" class="btn-form-nav btn-proximo-aba">Próximo <i
                            class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div id="aba-classe" class="conteudo-aba">
                <section class="desc">
                    <h1>4. CLASSE</h1>
                    <p>Para finalizar, o seu treinamento dita o seu futuro. A Classe determina suas habilidades de
                        combate, perícias técnicas e o papel definitivo que você exercerá dentro do grupo.</p>
                </section>

                <div class="tri-class">

                    <div class="class-card">
                        <h2>COMBATENTE</h2>
                        <hr>
                        <p class="desc-classe">Treinado desde cedo para enfrentar ameaças diretamente, o Combatente
                            domina o uso de armas e dependem tanto de força quanto de disciplina. São aqueles que
                            seguram a linha de frente, protegem seus aliados e impõem respeito apenas pela postura.</p>

                        <div class="caract-class">
                            <h4 class="caract-titulo">CARACTERÍSTICAS</h4>
                            <div class="topic-title">Perícias principais</div>
                            <hr class="topic-line">
                            <div class="topic-desc">Luta, Armas (qualquer)</div>

                            <div class="topic-title">Perícias secundárias</div>
                            <hr class="topic-line">
                            <div class="topic-desc">Intimidação, Tática</div>

                            <div class="topic-title">Talento</div>
                            <hr class="topic-line">
                            <div class="topic-desc">Ataque Poderoso — uma vez por cena, pode aplicar +2 de dano em um
                                ataque bem-sucedido.</div>

                            <div class="topic-title">Pequena resistência física</div>
                            <hr class="topic-line">
                            <div class="topic-desc">+1 em testes de força ou carga</div>

                            <div class="topic-title">Ao evoluir, role 1d6:</div>
                            <ol class="topic-list">
                                <li>+5 em Luta – seu combate corpo a corpo fica mais eficiente.</li>
                                <li>+5 em Armas – domina melhor algum tipo de arma.</li>
                                <li>+5 em Tática – coordena aliados em combate de forma mais eficiente.</li>
                                <li>+5 em Intimidação – inspira medo nos inimigos.</li>
                                <li>Ataque Poderoso aprimorado – aumenta o bônus de dano em +5 na próxima cena.</li>
                                <li>Resistência Física – +5 em testes de FOR ou VIT em desafios físicos.</li>
                            </ol>
                        </div>
                        <button type="button" class="btn-selecionar-classe">Escolher</button>
                    </div>

                    <div class="class-card">
                        <h2>ESPECIALISTA</h2>
                        <hr>
                        <p class="desc-classe">Astuto, analítico e versátil, o Especialista utiliza conhecimento e
                            criatividade para resolver problemas que força bruta não alcança. É o tipo de agente que
                            sempre tem um plano — ou inventa um na hora.</p>

                        <div class="caract-class">
                            <h4 class="caract-titulo">CARACTERÍSTICAS</h4>
                            <div class="topic-title">Perícias principais:</div>
                            <hr class="topic-line">
                            <div class="topic-desc">Furtividade, Mecânica/Informática</div>

                            <div class="topic-title">Perícias secundárias:</div>
                            <hr class="topic-line">
                            <div class="topic-desc">Persuasão, Percepção</div>

                            <div class="topic-title">Talento:</div>
                            <hr class="topic-line">
                            <div class="topic-desc">Improvisador — uma vez por cena, pode realizar um teste difícil sem
                                penalidade.</div>

                            <div class="topic-title">Ao evoluir, role 1d6:</div>
                            <ol class="topic-list">
                                <li>+1 em Furtividade – movimenta-se com mais discrição.</li>
                                <li>+1 em Mecânica/Informática – maior habilidade em tecnologia ou equipamentos.</li>
                                <li>+1 em Persuasão – melhor capacidade de convencer ou negociar.</li>
                                <li>+1 em Percepção – nota detalhes que outros não percebem.</li>
                                <li>Improvisador aprimorado – pode usar o talento duas vezes por cena.</li>
                                <li>Insight rápido – consegue encontrar soluções criativas em situações inesperadas.
                                </li>
                            </ol>
                        </div>
                        <button type="button" class="btn-selecionar-classe">Escolher</button>
                    </div>

                    <div class="class-card">
                        <h2>CURANDEIRO</h2>
                        <hr>
                        <p class="desc-classe">Cuidadoso, atento e altamente treinado, o Curandeiro é responsável por
                            manter o grupo vivo e funcional. Sua expertise em primeiros socorros e medicina o torna
                            fundamental em situações críticas, tratando ferimentos, estabilizando aliados e ajudando a
                            prevenir novas complicações.<br>Muitos também são bons observadores e sabem como manter a
                            calma em cenários de tensão</p>

                        <div class="caract-class">
                            <h4 class="caract-titulo">CARACTERÍSTICAS</h4>
                            <div class="topic-title">Perícias principais:</div>
                            <hr class="topic-line">
                            <div class="topic-desc">Medicina, Primeiros Socorros</div>

                            <div class="topic-title">Perícias secundárias:</div>
                            <hr class="topic-line">
                            <div class="topic-desc">Sobrevivência</div>

                            <div class="topic-title">Talento:</div>
                            <hr class="topic-line">
                            <div class="topic-desc">Cura Rápida — uma vez por cena, pode restaurar ou estabilizar um
                                aliado de forma imediata.</div>

                            <div class="topic-title">Ao evoluir, role 1d6:</div>
                            <ol class="topic-list">
                                <li>+1 em Medicina – cuidados médicos mais eficazes.</li>
                                <li>+1 em Primeiros Socorros – estabiliza aliados com mais eficiência.</li>
                                <li>+1 em Empatia – entende melhor o estado emocional dos aliados.</li>
                                <li>+1 em Sobrevivência – mais adaptado a ambientes hostis.</li>
                                <li>Cura Rápida aprimorada – recupera mais pontos ou estabiliza aliados mais rápido.
                                </li>
                                <li>Técnica Especial – uma vez por sessão, impede que um efeito crítico continue
                                    afetando um aliado.</li>
                            </ol>
                        </div>
                        <button type="button" class="btn-selecionar-classe">Escolher</button>
                    </div>
                </div>

                <div class="botoes-nav-form">
                    <button type="button" class="btn-form-nav btn-voltar-aba"><i class="fas fa-arrow-left"></i>
                        Voltar</button>
                    <button type="button" class="btn-concluir">Salvar Personagem <i class="fas fa-check"></i></button>
                </div>
            </div>

        </form>
    </main>

    <footer class="rodape-principal">
        <div class="rodape-conteudo">
            <div class="rodape-logo-area">
                <div class="rodape-marca">
                    <img src="../img/logo_branco.png" alt="Logo TABLE">
                    <span>TABLE</span>
                </div>
                <p>Acompanhe uma experiência imersiva nos mundos de RPG. Aprenda e jogue com seus amigos!</p>
            </div>
            <div class="rodape-links">
                <h4>Navegação</h4>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="cm-jogar.php">Como Jogar</a></li>
                    <li><a href="#">Personagens</a></li>
                    <li><a href="#">Mundos</a></li>
                    <li><a href="#">Dados</a></li>
                    <li><a href="#">Sobre nós</a></li>
                </ul>
            </div>
            <div class="rodape-links">
                <h4>Jogar</h4>
                <ul>
                    <li><a href="#">Campanhas</a></li>
                    <li><a href="cm-jogador.php">Como Jogador</a></li>
                    <li><a href="cm-mestre.php">Como Mestre</a></li>
                    <li><a href="#">Outros</a></li>
                </ul>
            </div>
        </div>
        <div class="rodape-inferior">
            <p>© 2026 TABLE. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="../js/nav-global.js" defer></script>
    <script src="../js/criar-personagem.js" defer></script>
</body>

</html>