<?php
session_start();
// Se não houver sessão, podemos deixar o usuário ver o rolador, mas alguns recursos (como salvar histórico) podem não funcionar se o sistema exigir.
// Por enquanto, seguiremos o pedido do usuário para ser um site funcional com navbar e footer.
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Rolador de Dados</title>
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Montserrat:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --premium-accent: #8b5cf6;
            --premium-bg: #0d091a;
            --premium-card: rgba(255, 255, 255, 0.05);
        }

        body {
            background-color: var(--premium-bg);
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 120px 5% 60px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .rolador-container {
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }

        @media (max-width: 900px) {
            .rolador-container {
                grid-template-columns: 1fr;
            }
        }

        /* ÁREA DOS DADOS */
        .dados-grid-box {
            background: var(--premium-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(10px);
        }

        .titulo-secao {
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            margin-bottom: 30px;
            text-align: center;
            color: #fff;
            text-shadow: 0 0 15px rgba(139, 92, 246, 0.4);
        }

        .grid-dados {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .item-dado {
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .dado-icon-container {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }

        .img-dado {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.5));
        }

        .item-dado:hover .dado-icon-container {
            transform: scale(1.15) rotate(5deg);
        }

        .label-dado {
            font-weight: 800;
            font-size: 0.9rem;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .dado-girando {
            animation: girarDado 0.6s ease-in-out;
        }

        @keyframes girarDado {
            0% { transform: rotate(0deg) scale(1); }
            25% { transform: rotate(90deg) scale(1.3) translateY(-5px); }
            50% { transform: rotate(180deg) scale(1); }
            75% { transform: rotate(270deg) scale(1.3) translateY(-5px); }
            100% { transform: rotate(360deg) scale(1); }
        }

        /* HISTÓRICO */
        .historico-box {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 30px;
            display: flex;
            flex-direction: column;
            max-height: 480px; /* Alinhado com a altura da grade de dados */
            box-sizing: border-box;
        }

        .historico-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .historico-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .btn-limpar-log {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 0.8rem;
            transition: color 0.3s;
        }

        .btn-limpar-log:hover {
            color: #ff4d4d;
        }

        #historico-lista {
            overflow-y: auto;
            flex: 1;
            padding-right: 10px;
        }

        #historico-lista::-webkit-scrollbar {
            width: 5px;
        }

        #historico-lista::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
        }

        #historico-lista::-webkit-scrollbar-thumb {
            background: var(--premium-accent);
            border-radius: 10px;
        }

        .log-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 15px;
            margin-bottom: 12px;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .log-resultado {
            width: 45px;
            height: 45px;
            background: #fff;
            color: #000;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.2rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        .log-info p {
            margin: 0;
            font-size: 0.75rem;
            color: #888;
        }

        .log-info h4 {
            margin: 2px 0 0;
            font-size: 0.9rem;
            color: #fff;
        }

        /* BOTÃO MULTIPLO */
        .btn-multi-rolagem {
            width: 100%;
            background: linear-gradient(135deg, #6d28d9, #8b5cf6);
            color: #fff;
            border: none;
            padding: 18px;
            border-radius: 15px;
            font-weight: 800;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 20px rgba(109, 40, 217, 0.3);
        }

        .btn-multi-rolagem:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(109, 40, 217, 0.5);
            filter: brightness(1.1);
        }

        /* MODAL */
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            z-index: 10000;
            display: none;
        }

        .popup-dados {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 400px;
            background: #110e1a;
            border: 2px solid var(--premium-accent);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.9);
            z-index: 10001;
            padding: 35px;
            display: none;
            animation: modalPop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes modalPop {
            from { opacity: 0; transform: translate(-50%, -40%) scale(0.9); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }

        .campo-multi {
            margin-bottom: 20px;
        }

        .campo-multi label {
            display: block;
            color: #888;
            font-size: 0.8rem;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .campo-multi input {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 15px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.3s;
        }

        .campo-multi input:focus {
            border-color: var(--premium-accent);
        }

        .btn-confirmar {
            width: 100%;
            background: var(--premium-accent);
            color: #fff;
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            font-size: 1rem;
        }

        .btn-confirmar:hover {
            filter: brightness(1.2);
            box-shadow: 0 5px 15px var(--premium-accent);
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <header>
        <div class="logotipo">
            <a href="index.php"><img src="../img/logo_horizontal.png" alt="Logo TABLE"></a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="cm-jogar.php">Como Jogar</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a></li>
                <li><a href="criar-mapa.php">Mundos</a></li>
                <li><a href="rolador-de-dados.php" class="ativo">Dados</a></li>
                <li><a href="sobre-nos.php">Sobre Nós</a></li>
            </ul>
        </nav>

        <?php if (isset($_SESSION['usuario'])): ?>
            <div class="usuario-logado-nav" id="nav-logado" onclick="window.location.href='perfil.php'" title="Ir para o Perfil">
                <img src="<?= !empty($_SESSION['usuario']['foto']) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png' ?>" alt="Avatar Navbar" class="avatar-nav">
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

    <main class="main-content">
        <div class="rolador-container">
            <div class="dados-area">
                <div class="dados-grid-box">
                    <h2 class="titulo-secao">Rolador de Dados</h2>
                    
                    <div class="grid-dados">
                        <div class="item-dado" onclick="rolarDado(2, this)">
                            <div class="dado-icon-container">
                                <img src="../img/dados/D2.png" alt="D2" class="img-dado">
                            </div>
                            <span class="label-dado">1D2</span>
                        </div>
                        <div class="item-dado" onclick="rolarDado(4, this)">
                            <div class="dado-icon-container">
                                <img src="../img/dados/D4.png" alt="D4" class="img-dado">
                            </div>
                            <span class="label-dado">1D4</span>
                        </div>
                        <div class="item-dado" onclick="rolarDado(6, this)">
                            <div class="dado-icon-container">
                                <img src="../img/dados/D6.png" alt="D6" class="img-dado">
                            </div>
                            <span class="label-dado">1D6</span>
                        </div>
                        <div class="item-dado" onclick="rolarDado(8, this)">
                            <div class="dado-icon-container">
                                <img src="../img/dados/D8.png" alt="D8" class="img-dado">
                            </div>
                            <span class="label-dado">1D8</span>
                        </div>
                        <div class="item-dado" onclick="rolarDado(10, this)">
                            <div class="dado-icon-container">
                                <img src="../img/dados/D10.png" alt="D10" class="img-dado">
                            </div>
                            <span class="label-dado">1D10</span>
                        </div>
                        <div class="item-dado" onclick="rolarDado(12, this)">
                            <div class="dado-icon-container">
                                <img src="../img/dados/D12.png" alt="D12" class="img-dado">
                            </div>
                            <span class="label-dado">1D12</span>
                        </div>
                        <div class="item-dado" onclick="rolarDado(20, this)">
                            <div class="dado-icon-container">
                                <img src="../img/dados/D20.png" alt="D20" class="img-dado">
                            </div>
                            <span class="label-dado">1D20</span>
                        </div>
                        <div class="item-dado" onclick="rolarDado(100, this)">
                            <div class="dado-icon-container">
                                <img src="../img/dados/D100.png" alt="D100" class="img-dado">
                            </div>
                            <span class="label-dado">1D100</span>
                        </div>
                    </div>

                    <button class="btn-multi-rolagem" onclick="togglePopupDados()">
                        <i class="fas fa-dice"></i> Rolagem Múltipla
                    </button>
                </div>
            </div>

            <div class="historico-area">
                <div class="historico-box">
                    <div class="historico-header">
                        <h3>Histórico</h3>
                        <button class="btn-limpar-log" onclick="limparHistorico()">Limpar</button>
                    </div>
                    <div id="historico-lista">
                        <!-- Itens do histórico aparecerão aqui -->
                        <div style="text-align: center; padding: 40px; color: #555;" id="msg-vazio">
                            Nenhuma rolagem ainda.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Popup Rolagem Múltipla -->
    <div id="overlay-dados" class="popup-overlay" onclick="togglePopupDados()"></div>
    <div id="popup-dados" class="popup-dados">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="color: #fff; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Adicionar Dados</h3>
            <i class="fas fa-times" style="cursor: pointer; color: #888;" onclick="togglePopupDados()"></i>
        </div>
        <div class="campo-multi">
            <label>Quantidade de Dados (Máx: 10)</label>
            <input type="number" id="qtd-dados-multi" value="1" min="1" max="10">
        </div>
        <div class="campo-multi">
            <label>Quantos Lados</label>
            <input type="number" id="lados-dados-multi" value="20" min="2" max="100">
        </div>
        <button class="btn-confirmar" onclick="confirmarRolagemMultipla()">Confirmar Rolagem</button>
    </div>

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

    <script>
        function rolarDado(lados, elemento, qtd = 1) {
            const container = elemento ? elemento.querySelector('.dado-icon-container') : null;
            const img = container ? container.querySelector('img') : null;
            const originalSrc = img ? img.src : null;

            if (container && img) {
                container.classList.add('dado-girando');
                img.src = `../img/dados/D${lados} efeito.png`;

                setTimeout(() => {
                    container.classList.remove('dado-girando');
                    img.src = originalSrc;
                }, 600);
            }

            let total = 0;
            let resultados = [];
            for (let i = 0; i < qtd; i++) {
                const roll = Math.floor(Math.random() * lados) + 1;
                total += roll;
                resultados.push(roll);
            }

            const desc = qtd > 1 ? `${qtd}d${lados} (${resultados.join(' + ')})` : `1d${lados}`;
            adicionarAoHistorico(total, desc);
        }

        // Carregar histórico do localStorage ao carregar a página
        document.addEventListener('DOMContentLoaded', () => {
            const historicoSalvo = localStorage.getItem('table_historico_dados');
            if (historicoSalvo) {
                const logs = JSON.parse(historicoSalvo);
                // Renderiza do mais antigo para o mais novo usando prepend para que o mais novo fique no topo
                logs.forEach(log => renderizarItemHistorico(log.resultado, log.descricao, log.time));
            }
        });

        function adicionarAoHistorico(resultado, descricao) {
            const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            // 1. Renderiza na UI
            renderizarItemHistorico(resultado, descricao, time);

            // 2. Salva no localStorage
            const historicoSalvo = localStorage.getItem('table_historico_dados');
            let logs = historicoSalvo ? JSON.parse(historicoSalvo) : [];
            logs.push({ resultado, descricao, time });
            
            // Limite de 50 rolagens para performance
            if (logs.length > 50) logs.shift();
            
            localStorage.setItem('table_historico_dados', JSON.stringify(logs));
        }

        function renderizarItemHistorico(resultado, descricao, time) {
            const logContainer = document.getElementById('historico-lista');
            const msgVazio = document.getElementById('msg-vazio');
            if (msgVazio) msgVazio.remove();

            const novoItem = document.createElement('div');
            novoItem.className = 'log-item';
            novoItem.innerHTML = `
                <div class="log-resultado">${resultado}</div>
                <div class="log-info">
                    <p>${time} • ${descricao}</p>
                    <h4>Resultado</h4>
                </div>
            `;

            // Sempre adiciona no topo
            logContainer.prepend(novoItem);
        }

        function togglePopupDados() {
            const popup = document.getElementById('popup-dados');
            const overlay = document.getElementById('overlay-dados');
            const isVisible = popup.style.display === 'block';
            
            popup.style.display = isVisible ? 'none' : 'block';
            overlay.style.display = isVisible ? 'none' : 'block';
        }

        function confirmarRolagemMultipla() {
            const qtd = parseInt(document.getElementById('qtd-dados-multi').value) || 1;
            const lados = parseInt(document.getElementById('lados-dados-multi').value) || 20;

            if (qtd > 10) {
                alert("O limite máximo é de 10 dados por vez!");
                return;
            }

            if (qtd < 1) return;

            rolarDado(lados, null, qtd);
            togglePopupDados();
        }

        function limparHistorico() {
            if (confirm('Deseja limpar todo o histórico de rolagens?')) {
                // Limpa UI
                document.getElementById('historico-lista').innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #555;" id="msg-vazio">
                        Nenhuma rolagem ainda.
                    </div>
                `;
                // Limpa LocalStorage
                localStorage.removeItem('table_historico_dados');
            }
        }
    </script>
</body>

</html>
