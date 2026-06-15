// Fallback para TableModal se for bloqueado por AdBlocker (ex: Opera GX)
if (typeof TableModal === 'undefined') {
    window.TableModal = {
        alert: async function(mensagem, titulo = 'Aviso', tipo = 'info') {
            alert((titulo ? titulo + "\n\n" : "") + mensagem);
            return true;
        },
        confirm: async function(mensagem, titulo = 'Confirmar', tipo = 'question') {
            return confirm((titulo ? titulo + "\n\n" : "") + mensagem);
        },
        prompt: async function(mensagem, padrao = '', titulo = 'Entrada') {
            return prompt((titulo ? titulo + "\n\n" : "") + mensagem, padrao);
        }
    };
}

// === VARIÁVEIS GLOBAIS DE ESTADO ===
let sistemaSelecionadoId = "";
let origemSelecionada = "";
let classeSelecionada = "";
let totalOrigensSistema = 0;
let totalClassesSistema = 0;
let imagemBase64 = null;

document.addEventListener('DOMContentLoaded', () => {

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

            // Habilitar/Atualizar IA de Personagem com o RPG Selecionado
            const btnIaPers = document.getElementById('btn-ia-personagem');
            const msgIaPers = document.getElementById('ia-personagem-msg');
            const nomeSistema = this.querySelector('h3').textContent.trim();
            
            if (btnIaPers) {
                btnIaPers.disabled = false;
                btnIaPers.style.background = 'linear-gradient(135deg, #7b4ff7, #4a2a85)';
                btnIaPers.style.color = '#fff';
                btnIaPers.style.cursor = 'pointer';
                btnIaPers.style.boxShadow = '0 4px 15px rgba(123, 79, 247, 0.4)';
                btnIaPers.onmouseover = function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 6px 20px rgba(123, 79, 247, 0.6)';
                };
                btnIaPers.onmouseout = function() {
                    this.style.transform = 'none';
                    this.style.boxShadow = '0 4px 15px rgba(123, 79, 247, 0.4)';
                };
            }
            if (msgIaPers) {
                msgIaPers.innerHTML = `Escreva o conceito do seu personagem e a CassIA irá distribuir seus atributos, história, objetivos e escolher a classe/origem de acordo com o RPG <strong style="color: #7b4ff7;">[ ${nomeSistema} ]</strong> selecionado!`;
            }

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

        const alvoId = aba.getAttribute('data-alvo');
        aba.classList.add('ativa');
        document.getElementById(alvoId).classList.add('ativa');
        atualizarIndicador(aba);

        if (alvoId === 'aba-origem' && origemSelecionada) {
            // Rola suavemente até a origem selecionada
            setTimeout(() => {
                const containerOrigem = document.getElementById('container-origens-dinamico');
                if (containerOrigem) {
                    const divSel = Array.from(containerOrigem.querySelectorAll('.origem')).find(o => {
                        const h3 = o.querySelector('h3');
                        return h3 && h3.textContent.trim().toLowerCase() === origemSelecionada.toLowerCase();
                    });
                    if (divSel) {
                        const header = divSel.querySelector('.origem-header');
                        const content = divSel.querySelector('.origem-content');
                        if (header && !header.classList.contains('active')) header.classList.add('active');
                        if (content && !content.classList.contains('show')) content.classList.add('show');
                        divSel.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }
                }
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 100);
        } else {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    abas.forEach(aba => {
        aba.addEventListener('click', () => {
            const index = parseInt(aba.getAttribute('data-index'));

            // Validações de Sequência
            if (index > 0 && sistemaSelecionadoId === "") {
                TableModal.alert("Selecione um Sistema de RPG primeiro!", "Aviso", "warning");
                ativarAba(abas[0]);
                return;
            }
            if (index > 2 && origemSelecionada === "" && totalOrigensSistema > 0) {
                TableModal.alert("Selecione uma Origem primeiro!", "Aviso", "warning");
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
                TableModal.alert("Selecione um Sistema antes de continuar!", "Aviso", "warning");
                return;
            }
            if (index === 2 && origemSelecionada === "" && totalOrigensSistema > 0) {
                TableModal.alert("Escolha uma Origem antes de avançar!", "Aviso", "warning");
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
            if (!sistemaSelecionadoId) {
                TableModal.alert("Selecione um Sistema!", "Aviso", "warning");
                return;
            }
            if (!nomePers) {
                TableModal.alert("Dê um nome ao seu personagem!", "Aviso", "warning");
                return;
            }
            if (!origemSelecionada && totalOrigensSistema > 0) {
                TableModal.alert("Escolha uma Origem!", "Aviso", "warning");
                return;
            }
            if (!classeSelecionada && totalClassesSistema > 0) {
                TableModal.alert("Selecione uma Classe!", "Aviso", "warning");
                return;
            }

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
                    TableModal.alert("Erro: " + data.message, "Falha ao Salvar", "error");
                    btnSalvar.innerHTML = originalTxt;
                    btnSalvar.style.pointerEvents = 'auto';
                }
            } catch (err) {
                console.error(err);
                TableModal.alert("Erro de conexão com o servidor.", "Falha de Conexão", "error");
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

    // === LÓGICA DE UPLOAD E AJUSTE DE IMAGEM DO PERSONAGEM ===
    const inputFoto = document.getElementById('input-foto-personagem');
    const previewImagem = document.getElementById('preview-imagem');
    const silhuetas = previewImagem ? previewImagem.querySelectorAll('div') : [];

    if (inputFoto) {
        if (previewImagem) {
            previewImagem.addEventListener('click', () => inputFoto.click());
        }
        inputFoto.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                abrirCropperModal(file, 1, (croppedBlob, croppedBase64) => {
                    previewImagem.style.setProperty('background-image', `url(${croppedBase64})`, 'important');
                    silhuetas.forEach(s => s.style.display = 'none');
                    imagemBase64 = croppedBase64;
                    const base64Input = document.getElementById('input-imagem-base64');
                    if (base64Input) base64Input.value = croppedBase64;
                });
            }
        });
    }

    // === LÓGICA DA IA DO PERSONAGEM (CASSIA) ===
    const btnIaPersonagem = document.getElementById('btn-ia-personagem');
    if (btnIaPersonagem) {
        btnIaPersonagem.addEventListener('click', async () => {
            if (!sistemaSelecionadoId) {
                TableModal.alert("Selecione um Sistema de RPG primeiro!", "Sistema Não Selecionado", "warning");
                return;
            }
            if (typeof TEM_API_KEY === 'undefined' || !TEM_API_KEY) {
                await TableModal.alert("Você não possui uma chave de API do Gemini configurada no seu perfil! Para utilizar a geração por IA da CassIA, configure sua chave primeiro.", "API Key Faltando", "warning");
                window.location.href = "editar-perfil.php?foco=gemini-key";
                return;
            }
            const modalIa = document.getElementById('modal-ia-personagem');
            if (modalIa) {
                modalIa.style.display = 'flex';
            }
            const conceitoInput = document.getElementById('ia-personagem-conceito');
            if (conceitoInput) {
                conceitoInput.focus();
            }
        });
    }
});

// === FUNÇÃO DE CANALIZAÇÃO DA CASSIA (GLOBAL E INDEPENDENTE DE CLOSURE) ===
window.executarCanalizacaoPersonagem = async function() {
    const conceitoEl = document.getElementById('ia-personagem-conceito');
    const conceito = conceitoEl ? conceitoEl.value.trim() : '';
    if (!conceito) {
        await TableModal.alert("Por favor, descreva o conceito do seu personagem antes de canalizar.", "Campo Vazio", "warning");
        return;
    }

    const api_key_valida = typeof TEM_API_KEY !== 'undefined' ? TEM_API_KEY : false;
    if (!api_key_valida) {
        await TableModal.alert("Chave de API do Gemini não configurada! Redirecionando para as configurações de perfil...", "API Key Faltando", "warning");
        window.location.href = "editar-perfil.php?foco=gemini-key";
        return;
    }

    const inputContainer = document.getElementById('ia-input-container-personagem');
    const loadingContainer = document.getElementById('ia-loading-container-personagem');
    const fraseLoading = document.getElementById('ia-loading-frase-personagem');

    // Mostra o loading
    if (inputContainer) inputContainer.style.display = 'none';
    if (loadingContainer) loadingContainer.style.display = 'block';

    const frases = [
        "Tecendo o passado do seu herói...",
        "Moldando sua personalidade e objetivos...",
        "Calculando a distribuição ideal de atributos...",
        "Forjando seu equipamento inicial...",
        "Escolhendo a melhor classe e origem..."
    ];
    let fraseIdx = 0;
    const intervalFrases = setInterval(() => {
        fraseIdx = (fraseIdx + 1) % frases.length;
        if (fraseLoading) fraseLoading.textContent = frases[fraseIdx];
    }, 3500);

    try {
        const response = await fetch('../app/ajax/gerar-com-ia.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                tipo: 'personagem',
                conceito: conceito,
                id_sistema: sistemaSelecionadoId
            })
        });

        const res = await response.json();
        clearInterval(intervalFrases);

        if (res.success && res.data && !res.mock) {
            const data = res.data;

            // 1. Nome
            if (data.nome) {
                const nomeEl = document.getElementById('nome');
                if (nomeEl) nomeEl.value = data.nome;
            }

            // 2. História, Aparência, Personalidade e Objetivos
            if (data.historia) {
                const histEl = document.getElementById('input-historia');
                if (histEl) histEl.value = data.historia;
            }
            if (data.aparencia) {
                const aparenciaEl = document.getElementById('input-aparencia');
                if (aparenciaEl) aparenciaEl.value = data.aparencia;
            }
            if (data.personalidade) {
                const personalidadeEl = document.getElementById('input-personalidade');
                if (personalidadeEl) personalidadeEl.value = data.personalidade;
            }
            if (data.objetivos) {
                const objetivosEl = document.getElementById('input-objetivos');
                if (objetivosEl) objetivosEl.value = data.objetivos;
            }

            // 3. Atributos
            if (data.atributos && typeof data.atributos === 'object') {
                const inputsAtributo = document.querySelectorAll('.atributo-input');
                inputsAtributo.forEach(input => {
                    const nameAttr = input.getAttribute('name') || '';
                    const cleanName = nameAttr.replace('attr_', '').toUpperCase();
                    
                    // Procurar correspondência no JSON da IA
                    for (const sigla in data.atributos) {
                        if (cleanName.includes(sigla.toUpperCase()) || sigla.toUpperCase().includes(cleanName)) {
                            input.value = Math.min(20, Math.max(0, parseInt(data.atributos[sigla]) || 0));
                            // Disparar evento de input para recalcular pontos disponíveis
                            const event = new Event('input', { bubbles: true });
                            input.dispatchEvent(event);
                            break;
                        }
                    }
                });
            }

            // 4. Origem (Aba Origem)
            const origensIa = data.origens || (data.origem_sugerida ? [data.origem_sugerida] : []);
            if (origensIa && origensIa.length > 0) {
                const sugOrigem = String(origensIa[0]).toLowerCase().trim();
                const containerOrigem = document.getElementById('container-origens-dinamico');
                if (containerOrigem) {
                    const botoesOrigem = containerOrigem.querySelectorAll('.btn-escolher-origem');
                    botoesOrigem.forEach(btn => {
                        const nomeBtn = btn.getAttribute('data-nome').toLowerCase().trim();
                        if (nomeBtn.includes(sugOrigem) || sugOrigem.includes(nomeBtn)) {
                            const divOrigem = btn.closest('.origem');
                            if (divOrigem) {
                                if (!divOrigem.classList.contains('selecionado')) {
                                    btn.click();
                                }
                                // Garante que o acordeão da origem selecionada está aberto
                                const header = divOrigem.querySelector('.origem-header');
                                const content = divOrigem.querySelector('.origem-content');
                                if (header && !header.classList.contains('active')) header.classList.add('active');
                                if (content && !content.classList.contains('show')) content.classList.add('show');
                            }
                        }
                    });
                }
            }

            // 5. Classe (Aba Classe)
            const classesIa = data.classes || (data.classe_sugerida ? [data.classe_sugerida] : []);
            if (classesIa && classesIa.length > 0) {
                const sugClasse = String(classesIa[0]).toLowerCase().trim();
                const containerClasse = document.getElementById('container-classes-dinamico');
                if (containerClasse) {
                    const botoesClasse = containerClasse.querySelectorAll('.btn-selecionar-classe');
                    botoesClasse.forEach(btn => {
                        const nomeBtn = btn.getAttribute('data-nome').toLowerCase().trim();
                        if (nomeBtn.includes(sugClasse) || sugClasse.includes(nomeBtn)) {
                            const cardClasse = btn.closest('.class-card');
                            if (cardClasse && !cardClasse.classList.contains('selecionado')) {
                                btn.click();
                            }
                        }
                    });
                }
            }

            // Fechar modal de IA
            const modalIa = document.getElementById('modal-ia-personagem');
            if (modalIa) modalIa.style.display = 'none';
            await TableModal.alert("O seu herói foi forjado com sucesso pela CassIA! Revise as abas antes de salvar.", "Herói Convocado", "success");

        } else {
            const modalIa = document.getElementById('modal-ia-personagem');
            if (modalIa) modalIa.style.display = 'none';
            if (res.mock && res.aviso) {
                await TableModal.alert(res.aviso, "CassIA Indisponível", "warning");
            } else if (res.error === 'API_KEY_MISSING') {
                await TableModal.alert("Sua chave de API da CassIA não foi encontrada no banco. Redirecionando para cadastrar...", "Erro de Configuração", "error");
                window.location.href = "editar-perfil.php?foco=gemini-key";
            } else {
                await TableModal.alert("Houve uma falha na forja do personagem: " + res.error, "Erro de IA", "error");
            }
        }
    } catch (err) {
        if (typeof intervalFrases !== 'undefined') clearInterval(intervalFrases);
        const modalIa = document.getElementById('modal-ia-personagem');
        if (modalIa) modalIa.style.display = 'none';
        await TableModal.alert("Erro de comunicação com a CassIA: " + err.message, "Falha de Conexão", "error");
    } finally {
        if (inputContainer) inputContainer.style.display = 'block';
        if (loadingContainer) loadingContainer.style.display = 'none';
    }
};
