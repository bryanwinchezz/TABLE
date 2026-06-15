<?php
    /**
     *  Após a página de login definir a sessão com os dados do usuario a página index lê a sessão e inicia a mesma
     *  Na navbar temos um if e else para cado o usuario esteja conectado ou não, mudando sendo que: 
     *  SE o usuário estiver logado irá mostrar a foto e o nome do usuário
     */
    session_start();

    // Redireciona para login se não estiver logado
    if (!isset($_SESSION['usuario'])) {
        header('Location: index.php');
        exit;
    }

    require_once __DIR__ . '/../app/config/database.php';
    $pdo = Database::getConexao();

    // Migração silenciosa individual para as colunas de planos e desistência caso não existam
    $colunasAdd = [
        'fl_plano_mapas' => "ALTER TABLE tb_usuario ADD COLUMN fl_plano_mapas TINYINT(1) NOT NULL DEFAULT 0",
        'fl_plano_sistemas' => "ALTER TABLE tb_usuario ADD COLUMN fl_plano_sistemas TINYINT(1) NOT NULL DEFAULT 0",
        'fl_plano_completo' => "ALTER TABLE tb_usuario ADD COLUMN fl_plano_completo TINYINT(1) NOT NULL DEFAULT 0",
        'dt_desistencia_mestre' => "ALTER TABLE tb_usuario ADD COLUMN dt_desistencia_mestre DATETIME DEFAULT NULL"
    ];

    foreach ($colunasAdd as $col => $sql) {
        try {
            $stmtCheck = $pdo->query("SHOW COLUMNS FROM tb_usuario LIKE '$col'");
            if ($stmtCheck->rowCount() === 0) {
                $pdo->exec($sql);
            }
        } catch (Exception $e) {
            // Silencioso por coluna
        }
    }

    // Buscar dados completos do usuário (para pegar dt_nascimento)
    $stmt = $pdo->prepare("SELECT * FROM tb_usuario WHERE id_usuario = ?");
    $stmt->execute([$_SESSION['usuario']['id']]);
    $userCompleto = $stmt->fetch();

    if (!$userCompleto) {
        session_destroy();
        header('Location: login.php?erro=sessao_invalida');
        exit;
    }

    $idadeUsuario = 0;
    if ($userCompleto && !empty($userCompleto['dt_nascimento'])) {
        $hoje = new DateTime();
        $nasc = new DateTime($userCompleto['dt_nascimento']);
        $idadeUsuario = $hoje->diff($nasc)->y;
    }

    $possuiPlanoSistemas = $userCompleto && (
        (isset($userCompleto['fl_plano_sistemas']) && (int)$userCompleto['fl_plano_sistemas'] === 1) || 
        (isset($userCompleto['fl_plano_completo']) && (int)$userCompleto['fl_plano_completo'] === 1) || 
        (isset($userCompleto['tp_cargo']) && $userCompleto['tp_cargo'] === 'admin')
    );

    function canAccess($classificacao, $idade) {
        if ($classificacao == 'L') return true;
        $idx = (int)$classificacao;
        return $idade >= $idx;
    }

    function getClassStyle($class) {
        switch($class) {
            case 'L': return ['cor' => '#27ae60', 'label' => 'L'];
            case '10': return ['cor' => '#2980b9', 'label' => '10'];
            case '12': return ['cor' => '#f1c40f', 'label' => '12'];
            case '14': return ['cor' => '#e67e22', 'label' => '14'];
            case '16': return ['cor' => '#c0392b', 'label' => '16'];
            case '18': return ['cor' => '#1a1a1a', 'label' => '18'];
            default: return ['cor' => '#888', 'label' => '?'];
        }
    }
    $usuarioAtivo = $_SESSION['usuario'];
    $cargoUsuario = strtolower($usuarioAtivo['cargo'] ?? 'jogador');
    $isMestreOuAdmin = ($cargoUsuario === 'mestre' || $cargoUsuario === 'admin' || ($userCompleto && strtolower($userCompleto['tp_cargo'] ?? '') === 'admin') || ($userCompleto && strtolower($userCompleto['tp_cargo'] ?? '') === 'mestre'));
    $classeLayout = ($isMestreOuAdmin) ? 'grid-mestre' : 'grid-jogador';
    $fotoUsuario = (!empty($usuarioAtivo['foto']) && realpath(__DIR__ . '/' . $usuarioAtivo['foto']) !== false) ? $usuarioAtivo['foto'] : '../img/uploads/perfil/avatar1.png';
    $fotoNavbar = $fotoUsuario;

    // Buscar personagens do usuário
    require_once __DIR__ . '/../app/config/database.php';
    try {
        $pdo = Database::getConexao();
        $stmt = $pdo->prepare("
            SELECT p.*, c.nm_classe, s.nm_sistema
            FROM tb_personagem p
            LEFT JOIN tb_personagem_classe pc ON p.id_personagem = pc.id_personagem
            LEFT JOIN tb_classe c ON pc.id_classe = c.id_classe
            LEFT JOIN tb_sistema s ON p.id_sistema = s.id_sistema
            WHERE p.id_usuario = ? AND p.fl_ativo = 1
            ORDER BY p.dt_criacao DESC
        ");
        $stmt->execute([$usuarioAtivo['id']]);
        $personagens = $stmt->fetchAll();

        // Buscar Campanhas onde o usuário participa
        $stmt = $pdo->prepare("
            SELECT c.id_campanha, c.nm_campanha, c.ds_imagem, c.dt_criacao, c.id_usuario_mestre, s.nm_sistema 
            FROM tb_campanha c
            INNER JOIN tb_campanha_usuario cu ON c.id_campanha = cu.id_campanha
            LEFT JOIN tb_sistema s ON c.id_sistema = s.id_sistema
            WHERE cu.id_usuario = ? AND c.fl_ativa = 1
            ORDER BY c.dt_criacao DESC
        ");
        $stmt->execute([$usuarioAtivo['id']]);
        $campanhas = $stmt->fetchAll();

        // Buscar Sistemas Disponíveis (Com nome do criador e filtro de visibilidade)
        // Filtramos para não mostrar duplicatas do sistema oficial (Ordem Paranormal) que foram importadas
        $stmt = $pdo->prepare("
            SELECT s.*, u.nm_usuario as criador_nome, u.tp_cargo as criador_cargo
            FROM tb_sistema s
            LEFT JOIN tb_usuario u ON s.id_usuario_criador = u.id_usuario
            WHERE (s.id_usuario_criador = ? OR s.id_usuario_criador IS NULL OR u.tp_cargo = 'admin' OR s.id_sistema IN (SELECT id_sistema FROM tb_usuario_sistema WHERE id_usuario = ?))
            AND NOT (s.nm_sistema = 'Ordem Paranormal' AND s.fl_importado = 1)
            ORDER BY 
                CASE 
                    WHEN s.id_usuario_criador IS NULL OR u.tp_cargo = 'admin' OR u.nm_usuario = 'Kauan Bryan' THEN 0 
                    ELSE 1 
                END ASC,
                CASE 
                    WHEN s.id_usuario_criador IS NULL OR u.tp_cargo = 'admin' OR u.nm_usuario = 'Kauan Bryan' THEN s.nm_sistema 
                    ELSE NULL 
                END ASC,
                s.dt_cadastro DESC
        ");
        $stmt->execute([$usuarioAtivo['id'], $usuarioAtivo['id']]);
        $sistemas = $stmt->fetchAll();

    } catch (Exception $e) {
        $personagens = [];
        $campanhas = [];
        $sistemas = [];
    }
?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Meu Perfil</title>
    <link rel="shortcut icon" href="../img/logo_branco1.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/perfil.css?v=3.0">
    <link rel="stylesheet" href="../css/table-modal.css">
    <style>
        .badget-classificacao {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 22px;
            height: 22px;
            border-radius: 4px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 900;
            border: 1px solid rgba(0,0,0,0.5);
            box-shadow: 0 2px 5px rgba(0,0,0,0.5);
            z-index: 5;
        }
        .sistema-bloqueado {
            opacity: 0.6;
            filter: grayscale(0.8);
        }
        .bloqueio-overlay {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #ff4d4d;
            font-size: 1.2rem;
            opacity: 0.8;
        }
    </style>
</head>

<body class="pagina-perfil">

<header>
    <div class="logotipo">
        <a href="index.php"><img src="../img/logo_horizontal1.png" alt="Logo TABLE"></a>
    </div>

    <!-- BOTÃO MENU MOBILE (HAMBURGER) -->
    <div class="menu-toggle" id="mobile-menu-btn">
        <i class="fas fa-bars"></i>
    </div>

    <nav id="nav-menu">
        <ul>
            <li><a href="index.php">Início</a></li>
            <li><a href="cm-jogar.php">Como Jogar</a></li>
            <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
            </li>
            <li><a href="<?= isset($_SESSION['usuario']['cargo']) && in_array(strtolower($_SESSION['usuario']['cargo']), ['mestre','admin']) ? 'criar-mapa.php' : 'editar-perfil.php?abrir_mestre=1'; ?>">Mundos</a></li>
            <li><a href="rolagem-de-dados.php">Dados</a></li>
            <li><a href="sobre-nos.php">Sobre Nós</a></li>
        </ul>

        <!-- BOTÕES MOBILE -->
        <div class="nav-mobile-footer">
            <?php if (isset($_SESSION['usuario'])): ?>
                <div class="usuario-logado-nav" onclick="window.location.href='perfil.php'">
                    <img src="<?= htmlspecialchars($fotoNavbar) ?>"
                        alt="Avatar Navbar" class="avatar-nav">
                    <span class="nome-nav"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
                </div>
            <?php else: ?>
                <a href="login.php" class="botao-entrar">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="cadastro.php" class="botao-cadastrar">
                    <i class="fas fa-user-plus"></i> Cadastre-se
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <?php if (isset($_SESSION['usuario'])): ?>
        <div class="usuario-logado-nav desktop-only" id="nav-logado" onclick="window.location.href='perfil.php'"
            title="Ir para o Perfil">
            <img src="<?= htmlspecialchars($fotoNavbar) ?>"
                alt="Avatar Navbar" class="avatar-nav">
            <span class="nome-nav"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
        </div>
    <?php else: ?>
        <div class="botoes-navegacao desktop-only" id="nav-deslogado">
            <a href="login.php" class="botao-entrar">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <a href="cadastro.php" class="botao-cadastrar">
                <i class="fas fa-user-plus"></i> Cadastre-se
            </a>
        </div>
    <?php endif; ?>
</header>

    <main class="perfil-container">
        <section class="perfil-header">
            <div class="perfil-avatar-wrapper">
                <img src="<?= $fotoUsuario ?>" alt="Avatar" class="perfil-avatar">
            </div>
            <div class="perfil-info">
                <h1>Nome:</h1>
                <div class="perfil-nome-box">
                    <?= htmlspecialchars($_SESSION['usuario']['nome']) ?>
                </div>
                <a href="editar-perfil.php" class="btn-editar-perfil" style="text-decoration: none; text-align: center;">Editar meu perfil</a>
            </div>
        </section>

        <section id="grid-paineis" class="grid-layout <?= $classeLayout ?>">

            <div class="painel-dark" id="painel-personagens">
                <div class="painel-header">
                    <h2>Personagens:</h2>
                    <button class="btn-criar" onclick="window.location.href='criar-personagem.php'">Criar <i class="fas fa-plus-circle"></i></button>
                </div>
                <div class="painel-body scroller">
                    <?php if (empty($personagens)): ?>
                        <p style="text-align: center; color: rgba(255,255,255,0.5); padding: 20px;">Você ainda não tem personagens.</p>
                    <?php else: ?>
                        <?php foreach ($personagens as $p): ?>
                            <div class="lista-item" onclick="window.location.href='exibir-ficha.php?id=<?= $p['id_personagem'] ?>'" style="cursor: pointer; position: relative;">
                                <div class="item-avatar-quadrado">
                                    <img src="<?= !empty($p['ds_foto']) ? $p['ds_foto'] : '../img/uploads/perfil/avatar1.png' ?>" alt="Avatar">
                                </div>
                                <div class="item-dados">
                                    <h3><?= htmlspecialchars($p['nm_personagem']) ?></h3>
                                    <p><?= htmlspecialchars($p['nm_sistema'] ?? 'Sistema Desconhecido') ?></p>
                                    <span>Criado em: <?= date('d/m/Y', strtotime($p['dt_criacao'])) ?></span>
                                </div>
                                <button class="btn-lixeira-item" onclick="abrirModalExclusao(event, 'personagem', <?= $p['id_personagem'] ?>)" title="Excluir Personagem">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="painel-dark" id="painel-campanhas">
                <div class="painel-header">
                    <h2>Campanhas:</h2>
                    <button class="btn-criar btn-criar-mestre" <?php if (!$isMestreOuAdmin) echo 'style="display: none;"'; ?> onclick="window.location.href='criar-campanha.php'">Criar <i class="fas fa-plus-circle"></i></button>
                </div>
                <div class="painel-body scroller">
                    <?php if (empty($campanhas)): ?>
                        <p style="text-align: center; color: rgba(255,255,255,0.5); padding: 20px;">Você não participa de nenhuma campanha.</p>
                    <?php else: ?>
                        <?php foreach ($campanhas as $c): ?>
                            <div class="lista-item" onclick="window.location.href='criar-campanha.php?id=<?= $c['id_campanha'] ?>'" style="cursor: pointer; position: relative;">
                                <div class="item-avatar"><img src="<?= !empty($c['ds_imagem']) ? $c['ds_imagem'] : '../img/foto-campanha.jpg' ?>" alt="Capa"></div>
                                <div class="item-dados">
                                    <h3><?= htmlspecialchars($c['nm_campanha']) ?></h3>
                                    <p><?= htmlspecialchars($c['nm_sistema'] ?? 'Sistema Desconhecido') ?></p>
                                    <span>Criado em: <?= date('d/m/Y', strtotime($c['dt_criacao'])) ?></span>
                                </div>
                                <?php if ((int)$c['id_usuario_mestre'] === (int)$usuarioAtivo['id']): ?>
                                    <button class="btn-lixeira-item" onclick="abrirModalExclusao(event, 'campanha', <?= $c['id_campanha'] ?>)" title="Excluir Campanha">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="painel-dark" id="painel-sistemas" <?php if (!$isMestreOuAdmin) echo 'style="display: none;"'; ?>>
                <div class="painel-header">
                    <h2>Sistemas:</h2>
                    <?php if ($possuiPlanoSistemas): ?>
                        <button class="btn-criar" onclick="window.location.href='criar-sistema.php'">Criar <i class="fas fa-plus-circle"></i></button>
                    <?php else: ?>
                        <button class="btn-criar" onclick="window.location.href='planos.php?aviso=sistemas'" style="opacity: 0.6;" title="Desbloqueie o Plano de Sistemas para criar seus próprios universos!"><i class="fas fa-lock"></i> Criar</button>
                    <?php endif; ?>
                </div>
                <div class="painel-body scroller">
                    <?php if (empty($sistemas)): ?>
                        <p style="text-align: center; color: rgba(255,255,255,0.5); padding: 20px;">Nenhum sistema cadastrado.</p>
                    <?php else: ?>
                        <?php foreach ($sistemas as $s): 
                            $bloqueado = !canAccess($s['tp_classificacao'], $idadeUsuario);
                            $classStyle = getClassStyle($s['tp_classificacao']);
                        ?>
                            <div class="lista-item <?= $bloqueado ? 'sistema-bloqueado' : '' ?>" 
                                 <?php if (!$bloqueado): ?>onclick="window.location.href='exibir-sistema.php?id=<?= $s['id_sistema'] ?>'"<?php endif; ?> 
                                 style="cursor: <?= $bloqueado ? 'not-allowed' : 'pointer' ?>; position: relative;">
                                
                                <div class="item-avatar">
                                    <img src="<?= !empty($s['ds_imagem']) ? $s['ds_imagem'] : '../img/logo_icone.png' ?>" alt="Sistema">
                                    <span class="badget-classificacao" style="background: <?= $classStyle['cor'] ?>;"><?= $classStyle['label'] ?></span>
                                </div>
                                <div class="item-dados">
                                    <h3><?= htmlspecialchars($s['nm_sistema']) ?></h3>
                                    <p><?= (empty($s['id_usuario_criador']) || (isset($s['criador_cargo']) && strtolower($s['criador_cargo']) === 'admin') || (isset($s['criador_nome']) && $s['criador_nome'] === 'Kauan Bryan')) ? "Sistema Oficial" : "Sistema criado por: " . htmlspecialchars($s['criador_nome'] ?? 'TABLE') ?></p>
                                    <span>Registrado em: <?= date('d/m/Y', strtotime($s['dt_cadastro'])) ?></span>
                                </div>

                                <?php if ($bloqueado): ?>
                                    <div class="bloqueio-overlay" title="Conteúdo restrito para sua idade: <?= $idadeUsuario ?> anos.">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                <?php endif; ?>

                                <?php
                                    $isOfficial = (empty($s['id_usuario_criador']) || (isset($s['criador_cargo']) && strtolower($s['criador_cargo']) === 'admin') || (isset($s['criador_nome']) && $s['criador_nome'] === 'Kauan Bryan'));
                                    if (!$isOfficial && $s['id_usuario_criador'] != $usuarioAtivo['id']):
                                ?>
                                        <button class="btn-lixeira-item" onclick="abrirModalExclusao(event, 'sistema_vinculo', <?= $s['id_sistema'] ?>)" title="Remover da Minha Conta">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </section>
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
                <li><a href="cm-jogar.php" class="ativo">Como Jogar</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
                </li>
                <li><a href="<?= isset($_SESSION['usuario']['cargo']) && in_array(strtolower($_SESSION['usuario']['cargo']), ['mestre','admin']) ? 'criar-mapa.php' : 'editar-perfil.php?abrir_mestre=1'; ?>">Mundos</a></li>
                <li><a href="rolagem-de-dados.php">Dados</a></li>
                <li><a href="sobre-nos.php">Sobre Nós</a></li>
            </ul>
        </div>
        <div class="rodape-links">
            <h4>Jogar</h4>
            <ul>
                <li><a href="cm-jogador.php">Como Player</a></li>
                <li><a href="cm-mestre.php">Como Mestre</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Campanhas</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Meu Perfil</a>
                </li>
            </ul>
        </div>
    </div>
    <div class="rodape-inferior">
        <p>© 2026 TABLE. Todos os direitos reservados.</p>
        <div class="redes-sociais">
            <a href="#"><i class="fa-brands fa-discord"></i></a>
            <a href="#"><i class="fa-brands fa-instagram"></i></a>
        </div>
    </div>
</footer>

    <!-- MODAL DE EXCLUSÃO PREMIUM -->
    <div id="modal-exclusao" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Tem certeza?</h2>
            </div>
            <div class="modal-body">
                <p id="texto-modal-exclusao">Esta ação não pode ser desfeita. Para confirmar, escreva <strong style="color: #e63946;">DELETAR</strong> abaixo:</p>
                <input type="text" id="input-confirmar-exclusao" placeholder="Escreva DELETAR aqui..." class="input-modal">
            </div>
            <div class="modal-footer">
                <button class="btn-modal-cancelar" onclick="fecharModalExclusao()">Cancelar</button>
                <button id="btn-confirmar-delete" class="btn-modal-deletar" disabled>Deletar</button>
            </div>
        </div>
    </div>

    <script>
        let tipoExclusao = '';
        let idExclusao = null;
        
        function abrirModalExclusao(event, tipo, id) {
            // Impedir que o clique no botão dispare o clique no card (div pai)
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            tipoExclusao = tipo;
            idExclusao = id;
            
            const modal = document.getElementById('modal-exclusao');
            const textoModal = document.getElementById('texto-modal-exclusao');
            const tituloModal = document.querySelector('.modal-header h2');
            
            if (tipo === 'sistema_vinculo') {
                tituloModal.innerText = 'Aviso: Remoção Crítica';
                textoModal.innerHTML = 'Você está prestes a remover o vínculo deste sistema da sua conta.<br><br><strong style="color: #e63946; font-size: 0.95rem;">ATENÇÃO: Ao fazer isso, você automaticamente SAIRÁ de todas as campanhas que usam este sistema e todos os seus PERSONAGENS vinculados a ele serão EXCLUÍDOS permanentemente!</strong><br><br>Para confirmar, escreva <strong style="color: #e63946;">DELETAR</strong> abaixo:';
            } else if (tipo === 'sistema') {
                tituloModal.innerText = 'Tem certeza?';
                textoModal.innerHTML = 'Você está apagando este sistema permanentemente para TODOS os usuários.<br><br>Para confirmar, escreva <strong style="color: #e63946;">DELETAR</strong> abaixo:';
            } else {
                tituloModal.innerText = 'Tem certeza?';
                textoModal.innerHTML = 'Esta ação não pode ser desfeita. Para confirmar, escreva <strong style="color: #e63946;">DELETAR</strong> abaixo:';
            }

            if (modal) {
                modal.style.display = 'flex';
                modal.offsetHeight; // Force reflow
                modal.classList.add('ativa');
                document.body.style.overflow = 'hidden';
                
                const input = document.getElementById('input-confirmar-exclusao');
                if (input) {
                    input.value = '';
                    input.focus();
                }
                
                const btn = document.getElementById('btn-confirmar-delete');
                if (btn) btn.disabled = true;
            }
        }

        function fecharModalExclusao() {
            const modal = document.getElementById('modal-exclusao');
            if (modal) {
                modal.classList.remove('ativa');
                setTimeout(() => {
                    if (!modal.classList.contains('ativa')) {
                        modal.style.display = 'none';
                    }
                }, 400);
                document.body.style.overflow = '';
            }
        }

        // Fechar ao clicar no overlay
        window.onclick = function(event) {
            const modal = document.getElementById('modal-exclusao');
            if (event.target === modal) {
                fecharModalExclusao();
            }
        }

        document.getElementById('input-confirmar-exclusao').addEventListener('input', function() {
            const btn = document.getElementById('btn-confirmar-delete');
            if (this.value.trim().toUpperCase() === 'DELETAR') {
                btn.disabled = false;
            } else {
                btn.disabled = true;
            }
        });

        document.getElementById('btn-confirmar-delete').addEventListener('click', async function() {
            const btn = this;
            const originalTxt = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.style.pointerEvents = 'none';

            try {
                const response = await fetch('../app/ajax/deletar-item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ tipo: tipoExclusao, id: idExclusao })
                });
                
                const data = await response.json();

                if (data.success) {
                    fecharModalExclusao();
                    window.location.reload();
                } else {
                    TableModal.alert('Erro ao excluir: ' + data.error, 'Erro ao Deletar', 'error');
                    btn.innerHTML = originalTxt;
                    btn.style.pointerEvents = 'auto';
                }
            } catch (err) {
                console.error(err);
                TableModal.alert('Erro de conexão com o servidor.', 'Erro de Rede', 'error');
                btn.innerHTML = originalTxt;
                btn.style.pointerEvents = 'auto';
            }
        });
    </script>
    <script src="../js/table-modal.js"></script>
    <script src="../js/nav-global.js" defer></script>
</body>

</html>


