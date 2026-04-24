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

    // Buscar dados completos do usuário (para pegar dt_nascimento)
    $stmt = $pdo->prepare("SELECT * FROM tb_usuario WHERE id_usuario = ?");
    $stmt->execute([$_SESSION['usuario']['id']]);
    $userCompleto = $stmt->fetch();

    $idadeUsuario = 0;
    if (!empty($userCompleto['dt_nascimento'])) {
        $hoje = new DateTime();
        $nasc = new DateTime($userCompleto['dt_nascimento']);
        $idadeUsuario = $hoje->diff($nasc)->y;
    }

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
    $classeLayout = ($cargoUsuario === 'mestre') ? 'grid-mestre' : 'grid-jogador';
    $fotoUsuario = !empty($usuarioAtivo['foto']) ? $usuarioAtivo['foto'] : '../img/uploads/perfil/avatar1.png';

    // Buscar personagens do usuário
    require_once __DIR__ . '/../app/config/database.php';
    try {
        $pdo = Database::getConexao();
        $stmt = $pdo->prepare("
            SELECT p.*, c.nm_classe 
            FROM tb_personagem p
            LEFT JOIN tb_personagem_classe pc ON p.id_personagem = pc.id_personagem
            LEFT JOIN tb_classe c ON pc.id_classe = c.id_classe
            WHERE p.id_usuario = ? AND p.fl_ativo = 1
            ORDER BY p.dt_criacao DESC
        ");
        $stmt->execute([$usuarioAtivo['id']]);
        $personagens = $stmt->fetchAll();

        // Buscar Campanhas onde o usuário participa
        $stmt = $pdo->prepare("
            SELECT c.*, s.nm_sistema 
            FROM tb_campanha c
            INNER JOIN tb_campanha_usuario cu ON c.id_campanha = cu.id_campanha
            LEFT JOIN tb_sistema s ON c.id_sistema = s.id_sistema
            WHERE cu.id_usuario = ? AND c.fl_ativa = 1
            ORDER BY c.dt_criacao DESC
        ");
        $stmt->execute([$usuarioAtivo['id']]);
        $campanhas = $stmt->fetchAll();

        // Buscar Sistemas Disponíveis (Com nome do criador)
        $stmt = $pdo->query("
            SELECT s.*, u.nm_usuario as criador_nome, u.tp_cargo as criador_cargo
            FROM tb_sistema s
            LEFT JOIN tb_usuario u ON s.id_usuario_criador = u.id_usuario
            ORDER BY s.nm_sistema ASC
        ");
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
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="../css/perfil.css">
    <style>
        .badget-classificacao {
            position: absolute;
            bottom: -8px;
            right: -8px;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 900;
            border: 2px solid #111;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
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
        <a href="index.php"><img src="../img/logo_horizontal.png" alt="Logo TABLE"></a>
    </div>
    <nav>
        <ul>
            <li><a href="index.php">Início</a></li>
            <li><a href="cm-jogar.php">Como Jogar</a></li>
            <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
            </li>
            <li><a href="criar-mapa.php">Mundos</a></li>
            <li><a href="rolador-de-dados.php">Dados</a></li>
            <li><a href="sobre-nos.php">Sobre Nós</a></li>
        </ul>
    </nav>
    <?php if (isset($_SESSION['usuario'])): ?>
        <div class="usuario-logado-nav" id="nav-logado" onclick="window.location.href='perfil.php'"
            title="Ir para o Perfil">
            <img src="<?= !empty($_SESSION['usuario']['foto']) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png' ?>"
                alt="Avatar Navbar" class="avatar-nav">
            <span class="nome-nav"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
        </div>
    <?php else: ?>
        <div class="botoes-navegacao" id="nav-deslogado">
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
                            <div class="lista-item" onclick="window.location.href='exibir-ficha.php?id=<?= $p['id_personagem'] ?>'" style="cursor: pointer;">
                                <div class="item-avatar">
                                    <img src="<?= !empty($p['ds_foto']) ? $p['ds_foto'] : '../img/uploads/perfil/avatar1.png' ?>" alt="Avatar">
                                </div>
                                <div class="item-dados">
                                    <h3><?= htmlspecialchars($p['nm_personagem']) ?></h3>
                                    <p><?= htmlspecialchars($p['nm_classe'] ?? 'Sem Classe') ?></p>
                                    <span>Criado em: <?= date('d/m/Y', strtotime($p['dt_criacao'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="painel-dark" id="painel-campanhas">
                <div class="painel-header">
                    <h2>Campanhas:</h2>
                    <button class="btn-criar btn-criar-mestre" <?php if ($cargoUsuario !== 'mestre') echo 'style="display: none;"'; ?> onclick="window.location.href='criar-campanha.php'">Criar <i class="fas fa-plus-circle"></i></button>
                </div>
                <div class="painel-body scroller">
                    <?php if (empty($campanhas)): ?>
                        <p style="text-align: center; color: rgba(255,255,255,0.5); padding: 20px;">Você não participa de nenhuma campanha.</p>
                    <?php else: ?>
                        <?php foreach ($campanhas as $c): ?>
                            <div class="lista-item" onclick="window.location.href='criar-campanha.php?id=<?= $c['id_campanha'] ?>'" style="cursor: pointer;">
                                <div class="item-avatar"><img src="<?= !empty($c['ds_imagem']) ? $c['ds_imagem'] : '../img/foto-campanha.jpg' ?>" alt="Capa"></div>
                                <div class="item-dados">
                                    <h3><?= htmlspecialchars($c['nm_campanha']) ?></h3>
                                    <p><?= htmlspecialchars($c['nm_sistema'] ?? 'Sistema Desconhecido') ?></p>
                                    <span>Criado em: <?= date('d/m/Y', strtotime($c['dt_criacao'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="painel-dark" id="painel-sistemas" <?php if ($cargoUsuario !== 'mestre') echo 'style="display: none;"'; ?>>
                <div class="painel-header">
                    <h2>Sistemas:</h2>
                    <button class="btn-criar" onclick="window.location.href='criar-sistema.php'">Criar <i class="fas fa-plus-circle"></i></button>
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
                                    <img src="<?= !empty($s['ds_imagem']) ? $s['ds_imagem'] : '../img/foto-regra.jpg' ?>" alt="Sistema">
                                    <span class="badget-classificacao" style="background: <?= $classStyle['cor'] ?>;"><?= $classStyle['label'] ?></span>
                                </div>
                                <div class="item-dados">
                                    <h3><?= htmlspecialchars($s['nm_sistema']) ?></h3>
                                    <p><?= (empty($s['id_usuario_criador']) || (isset($s['criador_cargo']) && strtolower($s['criador_cargo']) === 'admin')) ? "Sistema Oficial" : "Sistema criado por: " . htmlspecialchars($s['criador_nome'] ?? 'TABLE') ?></p>
                                    <span>Registrado em: <?= date('d/m/Y', strtotime($s['dt_cadastro'])) ?></span>
                                </div>

                                <?php if ($bloqueado): ?>
                                    <div class="bloqueio-overlay" title="Conteúdo restrito para sua idade: <?= $idadeUsuario ?> anos.">
                                        <i class="fas fa-lock"></i>
                                    </div>
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
                <li><a href="criar-mapa.php">Mundos</a></li>
                <li><a href="rolador-de-dados.php">Dados</a></li>
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

    <script src="../js/nav-global.js" defer></script>
</body>

</html>
