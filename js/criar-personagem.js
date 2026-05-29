document.addEventListener('DOMContentLoaded', () => {

    // === VARIÁVEIS GLOBAIS DE ESTADO ===
    let sistemaSelecionadoId = "";
    let origemSelecionada = "";
    let classeSelecionada = "";
    let totalOrigensSistema = 0;
    let totalClassesSistema = 0;
    const inputOcultoSistema = document.getElementById('input-sistema');
    const inputOcultoOrigem = document.getElementById('input-origem');
    const inputOcultoClasse = document.getElementById('input-classe');

    // === 1. SELEÇÃO DE SISTEMA ===
    const cardsSistema = document.querySelectorAll('.card-sistema-selecao');
    console.log("Sistema cards encontrados:", cardsSistema.length);

    cardsSistema.forEach(card => {
        card.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            
            // Se já estiver selecionado, não faz nada (evita re-load e bugs)
            if (id === sistemaSelecionadoId) return;

            console.log("Sistema selecionado ID:", id);
            
            // UI Feedback
            cardsSistema.forEach(c => {
                c.classList.remove('selecionado');
                const btn = c.querySelector('.btn-selecionar-sistema');
                if(btn) btn.innerHTML = 'Selecionar';
            });

            this.classList.add('selecionado');
            const targetBtn = this.querySelector('.btn-selecionar-sistema');
            if(targetBtn) targetBtn.innerHTML = 'Selecionado <i class="fas fa-check"></i>';

            sistemaSelecionadoId = id;
            if(inputOcultoSistema) inputOcultoSistema.value = id;

            // Carregar dados do sistema via AJAX
            carregarDadosSistema(id);
        });
    });

    // Pré-selecionar sistema se via URL
    const urlParams = new URLSearchParams(window.location.search);
    const sysParam = urlParams.get('sys');
    if (sysParam) {
        const sysCard = document.querySelector(`.card-sistema-selecao[data-id="${sysParam}"]`);
        if (sysCard) {
            sysCard.click();
            setTimeout(() => document.querySelector('.btn-proximo-aba').click(), 500);
        }
    }

    async function carregarDadosSistema(id) {
        try {
            const response = await fetch(`../app/ajax/get-sistema-detalhes.php?id=${id}`);
            const data = await response.json();

            if (data.success) {
                totalOrigensSistema = data.origens.length;
                totalClassesSistema = data.classes.length;
                renderizarOrigens(data.origens);
                renderizarAtributos(data.atributos);
                renderizarClasses(data.classes);
                
                // Mudar fundo se o sistema tiver um background oficial definido no banco
                if(data.sistema.ds_background) {
                    document.body.style.backgroundImage = `linear-gradient(rgba(0,0,0,0.85), rgba(0,0,0,0.85)), url('${data.sistema.ds_background}')`;
                    document.body.style.backgroundSize = 'cover';
                    document.body.style.backgroundPosition = 'center';
                    document.body.style.backgroundAttachment = 'fixed';
                } else {
                    // Fundo padrão caso não tenha customizado
                    document.body.style.backgroundImage = 'none';
                    document.body.style.backgroundColor = '#311c61';
                }
            } else {
                console.error("Erro ao carregar detalhes do sistema:", data.error);
            }
        } catch (error) {
            console.error("Erro na requisição AJAX:", error);
        }
    }

    // === 2. RENDERIZAÇÃO DINÂMICA ===

    function renderizarOrigens(origens) {
        const container = document.getElementById('container-origens-dinamico');
        if (origens.length === 0) {
            container.innerHTML = '<p style="text-align: center; opacity: 0.5; padding: 40px;">Este sistema não possui origens cadastradas.</p>';
            return;
        }

        let html = `
            <div class="pesq-ori">
                <i class="fas fa-search icon-pesq"></i>
                <input class="input-field" type="search" placeholder="Pesquisar origem...">
            </div>
        `;

        origens.forEach(ori => {
            html += `
                <div class="origem">
                    <div class="origem-header">
                        <i class="fas fa-chevron-down arrow-icon"></i>
                        <h3>${ori.nm_origem}</h3>
                    </div>
                    <div class="origem-content">
                        <p class="descricao">${ori.ds_origem || 'Sem descrição.'}</p>
                        <div class="area-btn-escolher">
                            <button type="button" class="btn-escolher-origem" data-nome="${ori.nm_origem}">Escolher</button>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;

        // Reatribuir eventos de acordeão e escolha
        const headers = container.querySelectorAll('.origem-header');
        headers.forEach(header => {
            header.addEventListener('click', function() {
                this.classList.toggle('active');
                this.nextElementSibling.classList.toggle('show');
            });
        });

        const btns = container.querySelectorAll('.btn-escolher-origem');
        btns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const div = e.target.closest('.origem');
                const nome = e.target.getAttribute('data-nome');

                if (div.classList.contains('selecionado')) {
                    div.classList.remove('selecionado');
                    e.target.innerHTML = 'Escolher';
                    origemSelecionada = "";
                    inputOcultoOrigem.value = "";
                } else {
                    container.querySelectorAll('.origem').forEach(o => o.classList.remove('selecionado'));
                    btns.forEach(b => b.innerHTML = 'Escolher');
                    div.classList.add('selecionado');
                    e.target.innerHTML = 'Escolhida <i class="fas fa-check"></i>';
                    origemSelecionada = nome;
                    inputOcultoOrigem.value = nome;
                }
            });
        });

        // === Lógica de Pesquisa/Filtro ===
        const inputPesquisa = container.querySelector('.input-field');
        const listaOrigens = container.querySelectorAll('.origem');

        inputPesquisa.addEventListener('input', function() {
            const termo = this.value.toLowerCase().trim();

            listaOrigens.forEach(div => {
                const nome = div.querySelector('h3').textContent.toLowerCase();
                if (nome.includes(termo)) {
                    div.style.display = 'block';
                } else {
                    div.style.display = 'none';
                }
            });
        });
    }

    function renderizarAtributos(atributos) {
        const container = document.getElementById('container-atributos-dinamico');
        if (atributos.length === 0) {
            container.innerHTML = '<p style="text-align: center; opacity: 0.5; padding: 40px;">Este sistema não possui atributos cadastrados.</p>';
            return;
        }

        let html = '<div class="trio-atri">';
        
        atributos.forEach(attr => {
            html += `
                <div class="atributo-item">
                    <span class="atributo-sigla" data-tooltip="${attr.nm_atributo}">${attr.ds_abreviacao || attr.nm_atributo.substring(0,3).toUpperCase()}</span>
                    <input type="number" class="atributo-input" name="attr_${attr.nm_atributo}" min="0" max="20" value="0">
                </div>
            `;
        });
        
        html += '</div>';

        container.innerHTML = html;

        // Inicializar lógica de pontos para os novos inputs
        vincularLogicaAtributos();
    }

    function renderizarClasses(classes) {
        const container = document.getElementById('container-classes-dinamico');
        if (classes.length === 0) {
            container.innerHTML = '<p style="text-align: center; opacity: 0.5; padding: 40px;">Este sistema não possui classes cadastradas.</p>';
            return;
        }

        let html = '';
        classes.forEach(cls => {
            html += `
                <div class="class-card">
                    <h2>${cls.nm_classe}</h2>
                    <hr>
                    <p class="desc-classe">${cls.ds_descricao || 'Sem descrição.'}</p>
                    <button type="button" class="btn-selecionar-classe" data-nome="${cls.nm_classe}">Escolher</button>
                </div>
            `;
        });

        container.innerHTML = html;

        const btns = container.querySelectorAll('.btn-selecionar-classe');
        btns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const card = e.target.closest('.class-card');
                const nome = e.target.getAttribute('data-nome');

                if (card.classList.contains('selecionado')) {
                    card.classList.remove('selecionado');
                    e.target.innerHTML = 'Escolher';
                    classeSelecionada = "";
                    inputOcultoClasse.value = "";
                } else {
                    container.querySelectorAll('.class-card').forEach(c => c.classList.remove('selecionado'));
                    btns.forEach(b => b.innerHTML = 'Escolher');
                    card.classList.add('selecionado');
                    e.target.innerHTML = 'Escolhida <i class="fas fa-check"></i>';
                    classeSelecionada = nome;
                    inputOcultoClasse.value = nome;
                }
            });
        });
    }



    // === 3. LÓGICA DE ATRIBUTOS (DISTRIBUIÇÃO) ===
    function vincularLogicaAtributos() {
        const inputs = document.querySelectorAll('.atributo-input');
        const display = document.getElementById('pontos-restantes');
        const MAX_PONTOS = 10;
        const VALOR_BASE = 0;

        function calcular() {
            let gasto = 0;
            inputs.forEach(i => {
                let v = parseInt(i.value) || 0;
                gasto += (v - VALOR_BASE);
            });
            return gasto;
        }

        function atualizar() {
            let gasto = calcular();
            let restam = MAX_PONTOS - gasto;
            display.textContent = restam;
            display.classList.toggle('zerado', restam <= 0);

            inputs.forEach(i => {
                i.classList.toggle('alterado', (parseInt(i.value) || 0) > VALOR_BASE);
            });
        }

        inputs.forEach(input => {
            let anterior = parseInt(input.value) || 0;

            input.addEventListener('input', (e) => {
                let v = parseInt(e.target.value);
                if (isNaN(v)) return;

                if (v < VALOR_BASE) e.target.value = VALOR_BASE;
                if (v > 20) e.target.value = 20;

                if (calcular() > MAX_PONTOS) {
                    e.target.value = anterior;
                } else {
                    anterior = parseInt(e.target.value);
                }
                atualizar();
            });
        });

        atualizar();
    }


    // === 4. NAVEGAÇÃO ENTRE ABAS ===
    const abas = document.querySelectorAll('.aba');
    const conteudos = document.querySelectorAll('.conteudo-aba');
    const indicador = document.querySelector('.indicador-aba');

    function atualizarIndicador(aba) {
        const index = aba.getAttribute('data-index');
        indicador.style.transform = `translateX(${index * 100}%)`;
    }

    function ativarAba(aba) {
        abas.forEach(a => a.classList.remove('ativa'));
        conteudos.forEach(c => c.classList.remove('ativa'));

        aba.classList.add('ativa');
        document.getElementById(aba.getAttribute('data-alvo')).classList.add('ativa');
        atualizarIndicador(aba);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    abas.forEach(aba => {
        aba.addEventListener('click', () => {
            const index = parseInt(aba.getAttribute('data-index'));

            // Validações de Sequência
            if (index > 0 && sistemaSelecionadoId === "") {
                alert("Selecione um Sistema de RPG primeiro!");
                ativarAba(abas[0]);
                return;
            }
            if (index > 2 && origemSelecionada === "" && totalOrigensSistema > 0) {
                alert("Selecione uma Origem primeiro!");
                ativarAba(abas[2]);
                return;
            }

            ativarAba(aba);
        });
    });

    const btnsProximo = document.querySelectorAll('.btn-proximo-aba');
    const btnsVoltar = document.querySelectorAll('.btn-voltar-aba');

    btnsProximo.forEach(btn => {
        btn.addEventListener('click', () => {
            const index = parseInt(document.querySelector('.aba.ativa').getAttribute('data-index'));
            
            if (index === 0 && sistemaSelecionadoId === "") {
                alert("Selecione um Sistema antes de continuar!");
                return;
            }
            if (index === 2 && origemSelecionada === "" && totalOrigensSistema > 0) {
                alert("Escolha uma Origem antes de avançar!");
                return;
            }

            const proxima = document.querySelector(`.aba[data-index="${index + 1}"]`);
            if (proxima) ativarAba(proxima);
        });
    });

    btnsVoltar.forEach(btn => {
        btn.addEventListener('click', () => {
            const index = parseInt(document.querySelector('.aba.ativa').getAttribute('data-index'));
            const anterior = document.querySelector(`.aba[data-index="${index - 1}"]`);
            if (anterior) ativarAba(anterior);
        });
    });


    // === 5. FINALIZAÇÃO E ENVIO ===
    const btnSalvar = document.querySelector('.btn-concluir');
    if (btnSalvar) {
        btnSalvar.addEventListener('click', async (e) => {
            e.preventDefault();

            // Validações Finais
            const nomePers = document.getElementById('nome').value.trim();
            if (!sistemaSelecionadoId) return alert("Selecione um Sistema!");
            if (!nomePers) return alert("Dê um nome ao seu personagem!");
            if (!origemSelecionada && totalOrigensSistema > 0) return alert("Escolha uma Origem!");
            if (!classeSelecionada && totalClassesSistema > 0) return alert("Selecione uma Classe!");

            // Feedback de carregamento
            const originalTxt = btnSalvar.innerHTML;
            btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            btnSalvar.style.pointerEvents = 'none';

            const formData = new FormData(document.getElementById('form-cria-pers'));

            try {
                const response = await fetch('../app/controllers/personagem_controller.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    btnSalvar.innerHTML = 'Sucesso! <i class="fas fa-check-double"></i>';
                    btnSalvar.style.background = '#0c9447';
                    const inviteToken = document.getElementById('input-invite-token')?.value;
                    if (inviteToken) {
                        setTimeout(() => window.location.href = "invite.php?token=" + inviteToken + "&auto_join=" + data.id_personagem, 1200);
                    } else {
                        setTimeout(() => window.location.href = "exibir-ficha.php?id=" + data.id_personagem, 1200);
                    }
                } else {
                    alert("Erro: " + data.message);
                    btnSalvar.innerHTML = originalTxt;
                    btnSalvar.style.pointerEvents = 'auto';
                }
            } catch (err) {
                console.error(err);
                alert("Erro de conexão.");
                btnSalvar.innerHTML = originalTxt;
                btnSalvar.style.pointerEvents = 'auto';
            }
        });
    }

    if (abas.length > 0) atualizarIndicador(abas[0]);

    // Função global para navegação mobile via botões Próximo/Anterior
    window.mudarAba = function(index) {
        const abaAlvo = document.querySelector(`.aba[data-index="${index}"]`);
        if (abaAlvo) {
            abaAlvo.click();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };
});
