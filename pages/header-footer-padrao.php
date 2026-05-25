<!-- HEADER / CABEÇALHO -->

<header>
    <div class="logotipo">
        <a href="index.php"><img src="../img/logo_horizontal.png" alt="Logo TABLE"></a>
    </div>

    <!-- BOTÃO MENU MOBILE (HAMBURGER) -->
    <div class="menu-toggle" id="mobile-menu-btn">
        <i class="fas fa-bars"></i>
    </div>

    <nav id="nav-menu">
        <ul>
            <li><a href="index.php">Início</a></li>
            <li><a href="cm-jogar.php" class="ativo">Como Jogar</a></li>
            <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
            </li>
            <li><a href="criar-mapa.php">Mundos</a></li>
            <li><a href="rolagem-de-dados.php">Dados</a></li>
            <li><a href="sobre-nos.php">Sobre Nós</a></li>
        </ul>

        <!-- BOTÕES MOBILE (Aparecem apenas dentro do nav no mobile) -->
        <div class="nav-mobile-footer">
            <?php if (isset($_SESSION['usuario'])): ?>
                <div class="usuario-logado-nav" onclick="window.location.href='perfil.php'">
                    <img src="<?= !empty($_SESSION['usuario']['foto']) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png' ?>"
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
            <img src="<?= !empty($_SESSION['usuario']['foto']) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png' ?>"
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

<!-- FOOTER / RODAPÉ -->

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

