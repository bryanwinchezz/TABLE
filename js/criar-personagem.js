document.addEventListener('DOMContentLoaded', () => {

    // === 1. LÓGICA DE AUTO-PREENCHIMENTO DO NOME ===
    const inputNomeJogador = document.getElementById('nome-jogador');
    const navNomeUsuario = document.getElementById('nav-nome-usuario-global');

    if (inputNomeJogador && navNomeUsuario) {
        const nomeReal = navNomeUsuario.textContent.trim();
        if (nomeReal !== "" && nomeReal !== "Usuário") {
            inputNomeJogador.value = nomeReal;
        }
    }

    // === 2. VARIÁVEIS DE SELEÇÃO E VALIDAÇÃO ===
    let origemSelecionada = "";
    let classeSelecionada = "";
    const inputOcultoOrigem = document.getElementById('input-origem');
    const inputOcultoClasse = document.getElementById('input-classe');


    // === 3. LÓGICA DE DISTRIBUIÇÃO DE ATRIBUTOS (10 PONTOS) ===
    const atributosInputs = document.querySelectorAll('.atributo-input');
    const displayPontos = document.getElementById('pontos-restantes');
    const MAX_PONTOS = 10;
    const VALOR_BASE = 5;

    function calcularPontosGastos() {
        let totalGasto = 0;
        atributosInputs.forEach(input => {
            let val = parseInt(input.value);
            if (isNaN(val)) val = VALOR_BASE;
            totalGasto += (val - VALOR_BASE);
        });
        return totalGasto;
    }

    function atualizarVisualAtributos(pontosRestantes) {
        displayPontos.textContent = pontosRestantes;

        if (pontosRestantes === 0) {
            displayPontos.classList.add('zerado');
        } else {
            displayPontos.classList.remove('zerado');
        }

        atributosInputs.forEach(input => {
            if (parseInt(input.value) > VALOR_BASE) {
                input.classList.add('alterado');
            } else {
                input.classList.remove('alterado');
            }
        });
    }

    atributosInputs.forEach(input => {
        let valorAnteriorSeguro = parseInt(input.value) || VALOR_BASE;

        input.addEventListener('input', (e) => {
            let valStr = e.target.value;

            if (valStr === "") {
                atualizarVisualAtributos(MAX_PONTOS - calcularPontosGastos());
                return;
            }

            let currentValue = parseInt(valStr);

            if (currentValue < VALOR_BASE) {
                e.target.value = VALOR_BASE;
                currentValue = VALOR_BASE;
            }

            if (currentValue > 20) {
                e.target.value = 20;
                currentValue = 20;
            }

            let pontosGastos = calcularPontosGastos();

            if (pontosGastos > MAX_PONTOS) {
                e.target.value = valorAnteriorSeguro;
                pontosGastos = calcularPontosGastos();
            } else {
                valorAnteriorSeguro = currentValue;
            }

            atualizarVisualAtributos(MAX_PONTOS - pontosGastos);
        });

        input.addEventListener('blur', (e) => {
            if (e.target.value === "" || isNaN(parseInt(e.target.value))) {
                e.target.value = VALOR_BASE;
                valorAnteriorSeguro = VALOR_BASE;
                atualizarVisualAtributos(MAX_PONTOS - calcularPontosGastos());
            }
        });
    });

    atualizarVisualAtributos(MAX_PONTOS - calcularPontosGastos());


    // === 4. LÓGICA DE SELECIONAR E DESELECIONAR ORIGEM ===
    const botoesOrigem = document.querySelectorAll('.btn-escolher-origem');
    botoesOrigem.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const origemDiv = e.target.closest('.origem');

            if (origemDiv.classList.contains('selecionado')) {
                origemDiv.classList.remove('selecionado');
                e.target.innerHTML = 'Escolher';
                origemSelecionada = "";
                inputOcultoOrigem.value = "";
            } else {
                document.querySelectorAll('.origem').forEach(o => o.classList.remove('selecionado'));
                botoesOrigem.forEach(b => b.innerHTML = 'Escolher');

                origemDiv.classList.add('selecionado');
                e.target.innerHTML = 'Escolhida <i class="fas fa-check"></i>';

                const nomeOrigem = origemDiv.querySelector('h3').innerText.trim();
                origemSelecionada = nomeOrigem;
                inputOcultoOrigem.value = nomeOrigem;
            }
        });
    });


    // === 5. LÓGICA DE SELECIONAR E DESELECIONAR CLASSE ===
    const botoesClasse = document.querySelectorAll('.btn-selecionar-classe');
    botoesClasse.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const cardDiv = e.target.closest('.class-card');

            if (cardDiv.classList.contains('selecionado')) {
                cardDiv.classList.remove('selecionado');
                e.target.innerHTML = 'Escolher';
                classeSelecionada = "";
                inputOcultoClasse.value = "";
            } else {
                document.querySelectorAll('.class-card').forEach(c => c.classList.remove('selecionado'));
                botoesClasse.forEach(b => b.innerHTML = 'Escolher');

                cardDiv.classList.add('selecionado');
                e.target.innerHTML = 'Escolhida <i class="fas fa-check"></i>';

                const nomeClasse = cardDiv.querySelector('h2').innerText.trim();
                classeSelecionada = nomeClasse;
                inputOcultoClasse.value = nomeClasse;
            }
        });
    });


    // === 6. LÓGICA DE NAVEGAÇÃO E VALIDAÇÃO DAS ABAS ===
    const abas = document.querySelectorAll('.aba');
    const conteudos = document.querySelectorAll('.conteudo-aba');
    const indicador = document.querySelector('.indicador-aba');

    function atualizarIndicador(abaSelecionada) {
        const index = abaSelecionada.getAttribute('data-index');
        indicador.style.transform = `translateX(${index * 100}%)`;
    }

    function ativarAba(aba) {
        abas.forEach(a => a.classList.remove('ativa'));
        conteudos.forEach(c => c.classList.remove('ativa'));

        aba.classList.add('ativa');
        const alvo = document.getElementById(aba.getAttribute('data-alvo'));
        if (alvo) alvo.classList.add('ativa');

        atualizarIndicador(aba);
    }

    abas.forEach(aba => {
        aba.addEventListener('click', () => {
            const indexAlvo = parseInt(aba.getAttribute('data-index'));

            if (indexAlvo > 1 && origemSelecionada === "") {
                alert("Por favor, selecione uma Origem antes de avançar para as próximas etapas!");
                return;
            }
            ativarAba(aba);
        });
    });

    if (abas.length > 0) atualizarIndicador(abas[0]);

    const btnsProximo = document.querySelectorAll('.btn-proximo-aba');
    const btnsVoltar = document.querySelectorAll('.btn-voltar-aba');

    btnsProximo.forEach(btn => {
        btn.addEventListener('click', () => {
            const abaAtual = document.querySelector('.aba.ativa');
            const currentIndex = parseInt(abaAtual.getAttribute('data-index'));

            if (currentIndex === 1 && origemSelecionada === "") {
                alert("Por favor, selecione uma Origem antes de continuar!");
                return;
            }

            const proximaAba = document.querySelector(`.aba[data-index="${currentIndex + 1}"]`);
            if (proximaAba) ativarAba(proximaAba);
        });
    });

    btnsVoltar.forEach(btn => {
        btn.addEventListener('click', () => {
            const abaAtual = document.querySelector('.aba.ativa');
            const currentIndex = parseInt(abaAtual.getAttribute('data-index'));
            const abaAnterior = document.querySelector(`.aba[data-index="${currentIndex - 1}"]`);

            if (abaAnterior) ativarAba(abaAnterior);
        });
    });


    // === 7. INTERCEPTAR O "SALVAR PERSONAGEM" E VALIDAR TUDO ===
    const btnSalvar = document.querySelector('.btn-concluir');
    if (btnSalvar) {
        btnSalvar.addEventListener('click', (e) => {
            e.preventDefault(); // Impede o botão de recarregar a página

            // 1. Verifica se os nomes foram preenchidos
            const inputNome = document.getElementById('nome').value.trim();
            const inputJogador = document.getElementById('nome-jogador').value.trim();

            if (inputNome === "" || inputJogador === "") {
                alert("Por favor, volte na aba DESCRIÇÃO e preencha o Nome do Personagem e o Nome do Jogador.");
                return;
            }

            // 2. Verifica se a Origem foi escolhida (redundância de segurança)
            if (origemSelecionada === "") {
                alert("Por favor, volte na aba ORIGEM e escolha uma origem.");
                return;
            }

            // 3. Verifica se a Classe foi escolhida
            if (classeSelecionada === "") {
                alert("Você precisa selecionar uma CLASSE para concluir a criação do personagem!");
                return;
            }

            // 4. Verifica os pontos de atributos
            let pontosGastos = calcularPontosGastos();
            if (pontosGastos < 10) {
                const confirmacao = confirm(`Você ainda tem ${10 - pontosGastos} pontos de atributo sobrando. Deseja salvar mesmo assim?`);
                if (!confirmacao) return; // Se cancelar, para aqui
            }

            // INÍCIO DA SIMULAÇÃO DE SALVAMENTO
            const textoOriginal = btnSalvar.innerHTML;

            // Muda o botão para "Carregando..."
            btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            btnSalvar.style.background = '#4a3b55'; // Fica cinza/roxo desativado
            btnSalvar.style.pointerEvents = 'none'; // Impede duplo clique

            // Simula comunicação com servidor (1.5 segundos)
            setTimeout(() => {

                // Estado de Sucesso
                btnSalvar.innerHTML = 'Personagem Salvo! <i class="fas fa-check-double"></i>';
                btnSalvar.style.background = '#0c9447'; // Fica verdinho

                // Alerta e Redirecionamento
                setTimeout(() => {
                    alert("Sucesso! Personagem criado com sucesso.");
                    window.location.href = "perfil.html"; // <-- REDIRECIONA PARA O PERFIL
                }, 1000);

            }, 1500);
        });
    }

    // === 8. LÓGICA DO ACORDEÃO (ORIGENS) ===
    const origemHeaders = document.querySelectorAll('.origem-header');
    origemHeaders.forEach(header => {
        header.addEventListener('click', function () {
            this.classList.toggle('active');
            const content = this.nextElementSibling;
            content.classList.toggle('show');
        });
    });
});