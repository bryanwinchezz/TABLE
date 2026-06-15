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

// js/criar-sistema.js
const gerarID = () => '_' + Math.random().toString(36).substr(2, 9);
const gerarIDComp = () => '_' + Math.random().toString(36).substr(2, 9);

document.addEventListener('DOMContentLoaded', () => {

    // 1. Verificação de Mestre (Simulação)
    try {
        const sessao = JSON.parse(localStorage.getItem('table_sessao_ativa'));
        if (!sessao || sessao.cargo !== 'mestre') {
            // window.location.href = 'perfil.html'; // Descomente para produção
        }
    } catch (e) {
        console.warn("Não foi possível ler o localStorage para validação de mestre:", e);
    }

    // === CASSIA INTEGRATION (Registrado no topo para evitar quebras por erros subsequentes de DOM) ===
    const btnIaSistema = document.getElementById('btn-ia-sistema');
    if (btnIaSistema) {
        btnIaSistema.addEventListener('click', async () => {
            if (typeof TEM_API_KEY === 'undefined' || !TEM_API_KEY) {
                await TableModal.alert("Você não possui uma chave de API do Gemini configurada no seu perfil! Para utilizar a geração por IA da CassIA, configure sua chave primeiro.", "API Key Faltando", "warning");
                window.location.href = "editar-perfil.php?foco=gemini-key";
                return;
            }
            const modalIa = document.getElementById('modal-ia-sistema');
            if (modalIa) {
                modalIa.style.setProperty('display', 'flex', 'important');
                modalIa.classList.add('ativo');
            }
            const conceitoInput = document.getElementById('ia-conceito-texto');
            if (conceitoInput) {
                conceitoInput.focus();
            }
        });
    }

    // =======================================================
    // REMOVIDO: FORÇAR MAIÚSCULAS NOS INPUTS (Agora permite minúsculas)
    // =======================================================


    // =======================================================
    // NAVEGAÇÃO POR ABAS (Estilo Criar Personagem)
    // =======================================================
    const abas = document.querySelectorAll('.aba');
    const conteudos = document.querySelectorAll('.conteudo-aba');
    const indicador = document.querySelector('.indicador-aba');

    function ativarAba(aba) {
        // Remove 'ativa' de todos
        abas.forEach(a => a.classList.remove('ativa'));
        conteudos.forEach(c => c.classList.remove('ativa'));

        // Ativa a aba atual
        aba.classList.add('ativa');
        const alvo = document.getElementById(aba.getAttribute('data-alvo'));
        if (alvo) alvo.classList.add('ativa');

        // Move indicador roxo
        const index = aba.getAttribute('data-index');
        indicador.style.transform = `translateX(${index * 100}%)`;

        // Scroll suave pro topo
        // document.getElementById('form-criar-sistema').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Clique direto nas abas do menu superior (Com Validação)
    abas.forEach(aba => {
        aba.addEventListener('click', (e) => {
            const abaAlvoIndex = parseInt(aba.getAttribute('data-index'));
            const abaAtual = document.querySelector('.aba.ativa');
            const currentIndex = parseInt(abaAtual.getAttribute('data-index'));

            // Se tentar ir para frente saindo da aba 0, valida
            if (currentIndex === 0 && abaAlvoIndex > 0) {
                const nomeSistema = document.getElementById('input-nome-sistema').value.trim();
                const descricoes = document.querySelectorAll('.item-descricao textarea');
                let descPreenchida = false;
                descricoes.forEach(t => { if (t.value.trim() !== '') descPreenchida = true; });

                if (!nomeSistema) {
                    alert("Por favor, informe o nome do sistema antes de prosseguir.");
                    return;
                }
                if (!descPreenchida) {
                    alert("Por favor, preencha pelo menos um tópico de descrição.");
                    return;
                }
            }

            // Permite navegar para trás ou se validado
            ativarAba(aba);
        });
    });

    // Inicializa
    if (abas.length > 0) ativarAba(abas[0]);

    // Função global para navegação mobile via botões Próximo/Anterior
    window.mudarAba = function(index) {
        const abaAlvo = document.querySelector(`.aba[data-index="${index}"]`);
        if (abaAlvo) {
            abaAlvo.click();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    // Botões Próximo e Voltar
    document.querySelectorAll('.btn-proximo-aba').forEach(btn => {
        btn.addEventListener('click', () => {
            const abaAtual = document.querySelector('.aba.ativa');
            const currentIndex = parseInt(abaAtual.getAttribute('data-index'));

            // Validação da Aba de Descrição (Aba 0)
            if (currentIndex === 0) {
                const nomeSistema = document.getElementById('input-nome-sistema').value.trim();
                const descricoes = document.querySelectorAll('.item-descricao textarea');
                let descPreenchida = false;
                descricoes.forEach(t => { if (t.value.trim() !== '') descPreenchida = true; });

                if (!nomeSistema) {
                    alert("Por favor, informe o nome do sistema.");
                    return;
                }

                if (!descPreenchida) {
                    alert("Por favor, preencha pelo menos um tópico de descrição antes de prosseguir.");
                    return;
                }
            }

            const proximaAba = document.querySelector(`.aba[data-index="${currentIndex + 1}"]`);
            if (proximaAba) ativarAba(proximaAba);
        });
    });

    document.querySelectorAll('.btn-voltar-aba').forEach(btn => {
        btn.addEventListener('click', () => {
            const abaAtual = document.querySelector('.aba.ativa');
            const currentIndex = parseInt(abaAtual.getAttribute('data-index'));
            const abaAnterior = document.querySelector(`.aba[data-index="${currentIndex - 1}"]`);
            if (abaAnterior) ativarAba(abaAnterior);
        });
    });

    // Submissão do Formulário para API
    document.getElementById('form-criar-sistema').addEventListener('submit', async (e) => {
        e.preventDefault();

        const nomeSistema = document.getElementById('input-nome-sistema').value.trim();
        if (!nomeSistema) {
            TableModal.alert("Falha arcana: O nome do sistema é obrigatório!", "Campo Obrigatório", "error");
            const abaIdent = document.querySelector('.aba[data-index="0"]');
            if(abaIdent) ativarAba(abaIdent);
            return;
        }
        
        const btn = document.querySelector('.btn-proximo-aba'); // Qualquer botão que serviu de submit ou um indicativo de carregamento
        if(btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        
        let descTotal = '';
        document.querySelectorAll('.item-descricao textarea').forEach((t, index) => {
             if(t.value.trim() !== '') descTotal += (index > 0 ? '\n\n' : '') + t.value.trim();
        });

        const payload = {
            nome: document.getElementById('input-nome-sistema').value,
            classificacao: document.getElementById('input-classificacao').value,
            descricao: descTotal,
            atributos: atributosObj,
            status: statusObj,
            defesas: defesasObj,
            imagem_base64: imagemBase64,
            classes: componentesDb['CLASSES'] ? componentesDb['CLASSES'].items : [],
            pericias: componentesDb['PERÍCIAS'] ? componentesDb['PERÍCIAS'].items : [],
            origens: componentesDb['ORIGENS'] ? componentesDb['ORIGENS'].items : [],
            equipamentos: componentesDb['EQUIPAMENTOS'] ? componentesDb['EQUIPAMENTOS'].items : [],
            poderes: [
                ...(componentesDb['HABILIDADES'] ? componentesDb['HABILIDADES'].items.map(h => ({ ...h, val2: h.val2 || 'habilidade' })) : []),
                ...(componentesDb['PODERES'] ? componentesDb['PODERES'].items.map(p => ({ ...p, val2: p.val2 || 'poder' })) : [])
            ],
            monstros: componentesDb['AMEAÇAS'] ? componentesDb['AMEAÇAS'].items : []
        };

        try {
            const req = await fetch('../app/ajax/salvar-sistema.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            const res = await req.json();
            if(res.success) {
                await TableModal.alert("Mundo criado com sucesso! O Grimório o registra na história.", "Mundo Criado", "success");
                window.location.href = 'perfil.php';
            } else {
                await TableModal.alert("Falha arcana: " + res.error, "Erro de Criação", "error");
            }
        } catch(e) {
            await TableModal.alert("Erro de comunicação com o servidor.", "Erro de Conexão", "error");
        }
    });

    // =======================================================
    // UPLOAD DE IMAGEM
    // =======================================================
    const btnTrocarFoto = document.getElementById('btn-trocar-foto');
    const inputFoto = document.getElementById('input-foto-sistema');
    const previewImagem = document.getElementById('preview-imagem');
    const silhuetas = previewImagem.querySelectorAll('div');

    btnTrocarFoto.addEventListener('click', () => inputFoto.click());

    let imagemBase64 = null;

    inputFoto.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            abrirCropperModal(file, 16/9, (croppedBlob, croppedBase64) => {
                previewImagem.style.backgroundImage = `url(${croppedBase64})`;
                silhuetas.forEach(s => s.style.display = 'none');
                imagemBase64 = croppedBase64; // Salva o Base64
            });
        }
    });


    // =======================================================
    // CLASSIFICAÇÃO DE IDADE
    // =======================================================
    const botoesIdade = document.querySelectorAll('.btn-idade');
    botoesIdade.forEach(botao => {
        botao.addEventListener('click', () => {
            botoesIdade.forEach(b => b.classList.remove('ativo'));
            botao.classList.add('ativo');
            document.getElementById('input-classificacao').value = botao.getAttribute('data-idade');
        });
    });


    // =======================================================
    // ABA 1: DESCRIÇÕES DINÂMICAS
    // =======================================================
    const containerDescricoes = document.getElementById('container-descricoes');
    const btnAddDescGlobal = document.getElementById('btn-add-desc-global');
    const btnExcluirDescGlobal = document.getElementById('btn-excluir-desc-global');
    let modoExclusao = false;
    let contadorDescricao = 1;

    const cancelarModoExclusao = () => {
        modoExclusao = false;
        containerDescricoes.classList.remove('modo-exclusao');
        btnExcluirDescGlobal.innerHTML = 'Excluir tópico <i class="far fa-minus-square"></i>';
        btnExcluirDescGlobal.style.color = '';
    };

    const adicionarDescricao = () => {
        const itensAtuais = document.querySelectorAll('#container-descricoes .item-descricao');
        if (itensAtuais.length >= 10) {
            alert("Limite atingido: Você já possui 10 tópicos de descrição.");
            return;
        }

        contadorDescricao++;
        const idUnico = Date.now();
        const html = `
            <div class="item-descricao" id="desc-${idUnico}">
                <div class="cabecalho-descricao" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <input type="text" class="input-titulo-desc" value="Descrição ${contadorDescricao}:" style="width: 50%;">
                    <button type="button" class="btn-texto btn-excluir-desc-inline" data-id="desc-${idUnico}">Excluir <i class="fas fa-times"></i></button>
                </div>
                <textarea class="input-escuro textarea-escuro" placeholder="Digite os detalhes aqui..."></textarea>
            </div>
        `;
        containerDescricoes.insertAdjacentHTML('beforeend', html);
    };

    btnAddDescGlobal.addEventListener('click', adicionarDescricao);

    btnExcluirDescGlobal.addEventListener('click', () => {
        const itens = containerDescricoes.querySelectorAll('.item-descricao');

        if (!modoExclusao && itens.length <= 1) {
            alert("Apenas a Descrição 1 resta, e ela não pode ser excluída.");
            cancelarModoExclusao();
            return;
        }

        modoExclusao = !modoExclusao;
        containerDescricoes.classList.toggle('modo-exclusao', modoExclusao);

        if (modoExclusao) {
            btnExcluirDescGlobal.innerHTML = 'Cancelar <i class="far fa-times-circle"></i>';
            btnExcluirDescGlobal.style.color = '#ff4d4d';
        } else {
            cancelarModoExclusao();
        }
    });

    containerDescricoes.addEventListener('click', (e) => {
        if (e.target.closest('.btn-excluir-desc-inline')) {
            const id = e.target.closest('.btn-excluir-desc-inline').getAttribute('data-id');
            document.getElementById(id).remove();

            contadorDescricao = 1;
            const titulo1 = document.getElementById('titulo-desc-1');
            if (titulo1 && titulo1.value.startsWith('Descrição')) {
                titulo1.value = 'Descrição 1:';
            }

            containerDescricoes.querySelectorAll('.item-descricao').forEach(item => {
                if (item.id !== 'desc-fixa-1') {
                    contadorDescricao++;
                    const input = item.querySelector('.input-titulo-desc');
                    if (input && input.value.startsWith('Descrição')) {
                        input.value = `Descrição ${contadorDescricao}:`;
                    }
                }
            });

            cancelarModoExclusao();
        }
    });


    // Variáveis de estado globais para serem acessadas por funções fora do DOMContentLoaded (como salvarMonstro)
    window.atributosObj = [
        { id: gerarID(), nome: 'Força', abrev: 'FOR', valor: '0' },
        { id: gerarID(), nome: 'Agilidade', abrev: 'AGI', valor: '0' }
    ];
    window.statusObj = [
        { id: gerarID(), nome: 'Vida', cor: '#ed1c24', base: 'null' }
    ];
    window.defesasObj = [];
    window.componentesDb = {
        'CLASSES': {
            limit: 15, labels: ['Descrição', 'Habilidades'], items: [
                { id: gerarIDComp(), nome: 'Guerreiro', val1: 'Focado em força física e resistência. Ideal para iniciantes aprenderem as regras de combate.', val2: 'Ataque Especial: 1 vez por cena, soma +5 de dano puro em um ataque físico.' }
            ]
        },
        'PERÍCIAS': { limit: 30, labels: ['Descrição', 'Habilidades'], items: [
                { id: gerarIDComp(), nome: 'Luta', val1: 'Habilidade de combate corpo a corpo e testes de força em conflito.', val2: 'Baseado em Força' }
        ] },
        'ORIGENS': { limit: 75, labels: ['Descrição', 'Habilidades'], items: [
                { id: gerarIDComp(), nome: 'Desgarrado', val1: 'Alguém que viveu à margem da sociedade e aprendeu a se virar sozinho.', val2: 'Sobrevivente Nato: +2 em testes de Percepção.' }
        ] },
        'EQUIPAMENTOS': { limit: 100, labels: ['Descrição', 'Categoria'], items: [] },
        'HABILIDADES': { limit: 50, labels: ['Descrição', 'Requisito'], items: [] },
        'PODERES': { limit: 50, labels: ['Descrição', 'Duração'], items: [] },
        'AMEAÇAS': { limit: 50, labels: ['Tipo da Ameaça', 'VT'], items: [] }
    };

    let atributoEditandoID = null;
    let statusEditandoID = null;
    let editandoTipo = 'status';

    const botoesValorAttr = document.querySelectorAll('.botoes-valor-atributo .btn-sel');
    botoesValorAttr.forEach(btn => {
        btn.addEventListener('click', () => {
            botoesValorAttr.forEach(b => b.classList.remove('ativo'));
            btn.classList.add('ativo');
            document.getElementById('input-valor-atributo').value = btn.getAttribute('data-valor');
        });
    });

    const atualizarBotoesBaseStatus = () => {
        const container = document.getElementById('botoes-base-status');
        const atual = document.getElementById('input-base-status').value;
        container.innerHTML = `<button type="button" class="btn-sel btn-sel-base ${atual === 'null' ? 'ativo' : ''}" data-base="null">Ø</button>`;

        atributosObj.forEach(attr => {
            const ativo = (atual === attr.abrev) ? 'ativo' : '';
            container.insertAdjacentHTML('beforeend', `<button type="button" class="btn-sel btn-sel-base ${ativo}" data-base="${attr.abrev}">${attr.abrev}</button>`);
        });

        // Atualiza scrollbar customizada após reconstruir os botões
        setTimeout(initScrollBase, 150);
    };

    // Scrollbar customizada para #botoes-base-status (Refatorado para Robustez)
    const initScrollBase = () => {
        const sc = document.getElementById('botoes-base-status');
        const track = document.getElementById('scroll-track-base');
        const thumb = document.getElementById('scroll-thumb-base');
        if (!sc || !track || !thumb) return;

        const update = () => {
            if (!sc.scrollWidth) return;
            const ratio = sc.clientWidth / sc.scrollWidth;
            const thumbW = Math.max(ratio * track.clientWidth, 40);
            const maxLeft = track.clientWidth - thumbW;
            const scrollRange = sc.scrollWidth - sc.clientWidth;
            const thumbLeft = scrollRange <= 0 ? 0 : (sc.scrollLeft / scrollRange) * maxLeft;
            
            thumb.style.width = (ratio >= 1 ? 0 : thumbW) + 'px'; // Esconde se não houver overflow
            thumb.style.left = thumbLeft + 'px';
            thumb.style.opacity = ratio >= 1 ? '0' : '1';
        };

        // Eventos de Scroll
        sc.onscroll = update;

        // Arrastar o Thumb
        let drag = false, startX = 0, startScroll = 0;
        
        thumb.onmousedown = (e) => {
            drag = true; 
            startX = e.clientX;
            startScroll = sc.scrollLeft;
            document.body.style.userSelect = 'none';
            thumb.style.background = '#444';
            e.preventDefault();
        };

        document.onmousemove = (e) => {
            if (!drag) return;
            const dx = e.clientX - startX;
            const trackW = track.clientWidth;
            const thumbW = thumb.clientWidth;
            const scrollableWidth = sc.scrollWidth - sc.clientWidth;
            const trackScrollableW = trackW - thumbW;
            
            if (trackScrollableW > 0) {
                const ratioScroll = scrollableWidth / trackScrollableW;
                sc.scrollLeft = startScroll + dx * ratioScroll;
            }
        };

        document.onmouseup = () => { 
            if (drag) {
                drag = false; 
                document.body.style.userSelect = '';
                thumb.style.background = ''; // Volta pro CSS
            }
        };

        // Click no Track
        track.onclick = (e) => {
            if (e.target === thumb) return;
            const rect = track.getBoundingClientRect();
            const clickPos = (e.clientX - rect.left) / track.clientWidth;
            sc.scrollLeft = clickPos * (sc.scrollWidth - sc.clientWidth);
        };

        // Suporte a wheel
        sc.onwheel = (e) => {
            if (Math.abs(e.deltaY) > 0) {
                sc.scrollLeft += e.deltaY;
                e.preventDefault();
            }
        };

        // ResizeObserver para manter sincronizado
        if (window._scrollBaseObserver) window._scrollBaseObserver.disconnect();
        window._scrollBaseObserver = new ResizeObserver(update);
        window._scrollBaseObserver.observe(sc);
        window._scrollBaseObserver.observe(track);

        update();
    };

    const handleWheel = (e) => {
        const sc = document.getElementById('botoes-base-status');
        if (Math.abs(e.deltaY) > 0) {
            sc.scrollLeft += e.deltaY;
            e.preventDefault();
        }
    };

    document.getElementById('botoes-base-status').addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-sel-base')) {
            document.querySelectorAll('#botoes-base-status .btn-sel-base').forEach(b => b.classList.remove('ativo'));
            e.target.classList.add('ativo');
            document.getElementById('input-base-status').value = e.target.getAttribute('data-base');
        }
    });

    const renderAtributos = () => {
        const box = document.getElementById('lista-atributos');
        box.innerHTML = '';
        atributosObj.forEach(a => {
            box.insertAdjacentHTML('beforeend', `
                <div class="item-painel">
                    <span>${a.nome} (${a.abrev})</span> 
                    <div class="botoes-item">
                        <button type="button" class="btn-pilula btn-editar-attr" data-id="${a.id}">Editar</button>
                        <button type="button" class="btn-pilula btn-deletar-attr" data-id="${a.id}">Excluir</button>
                    </div>
                </div>
            `);
        });
        document.getElementById('contador-atributos').textContent = `${atributosObj.length}/8`;
        atualizarBotoesBaseStatus();
    };

    // =======================================================
    // LOGICA DE SCROLL CUSTOMIZADO (ATRIBUTOS E STATUS)
    // =======================================================
    function initCustomScroll(containerId, trackId, thumbId) {
        const container = document.getElementById(containerId);
        const track = document.getElementById(trackId);
        const thumb = document.getElementById(thumbId);

        if (!container || !track || !thumb) return;

        function updateThumb() {
            const scrollPercent = container.scrollLeft / (container.scrollWidth - container.clientWidth);
            const thumbWidth = Math.max(30, (container.clientWidth / container.scrollWidth) * track.clientWidth);
            thumb.style.width = thumbWidth + 'px';
            const maxLeft = track.clientWidth - thumbWidth;
            thumb.style.left = (scrollPercent * maxLeft) + 'px';
        }

        container.addEventListener('scroll', updateThumb);
        window.addEventListener('resize', updateThumb);
        setTimeout(updateThumb, 100);

        let isDragging = false;
        let startX, startScrollLeft;

        thumb.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.pageX - thumb.offsetLeft;
            startScrollLeft = container.scrollLeft;
            thumb.style.cursor = 'grabbing';
            e.preventDefault();
        });

        window.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            const x = e.pageX - startX;
            const thumbWidth = thumb.clientWidth;
            const maxLeft = track.clientWidth - thumbWidth;
            const percent = Math.min(Math.max(x / maxLeft, 0), 1);
            container.scrollLeft = percent * (container.scrollWidth - container.clientWidth);
        });

        window.addEventListener('mouseup', () => {
            isDragging = false;
            thumb.style.cursor = 'grab';
        });

        track.addEventListener('click', (e) => {
            if (e.target === thumb) return;
            const rect = track.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const thumbWidth = thumb.clientWidth;
            const percent = Math.min(Math.max((clickX - thumbWidth / 2) / (track.clientWidth - thumbWidth), 0), 1);
            container.scrollLeft = percent * (container.scrollWidth - container.clientWidth);
        });
    }

    // Inicializa os scrolls
    initCustomScroll('botoes-base-status', 'scroll-track-base', 'scroll-thumb-base');
    initCustomScroll('botoes-valor-atributo', 'scroll-track-attr-valor', 'scroll-thumb-attr-valor');

    const renderStatusEDefesas = () => {
        const boxStatus = document.getElementById('lista-status');
        boxStatus.innerHTML = '';
        statusObj.forEach(s => {
            const tag = s.base !== 'null' ? ` [${s.base}]` : '';
            boxStatus.insertAdjacentHTML('beforeend', `
                <div class="item-painel" style="border-left: 5px solid ${s.cor};">
                    <span>${s.nome}${tag}</span>
                    <div class="botoes-item">
                        <button type="button" class="btn-pilula btn-editar-status" data-id="${s.id}" data-tipo="status">Editar</button>
                        <button type="button" class="btn-pilula btn-deletar-status" data-id="${s.id}" data-tipo="status">Excluir</button>
                    </div>
                </div>
            `);
        });
        document.getElementById('contador-status').textContent = `${statusObj.length}/3`;

        const boxDefesas = document.getElementById('lista-defesas');
        boxDefesas.innerHTML = '';
        defesasObj.forEach(d => {
            const tag = d.base !== 'null' ? ` [${d.base}]` : '';
            boxDefesas.insertAdjacentHTML('beforeend', `
                <div class="item-painel" style="border-left: 5px solid ${d.cor};">
                    <span>${d.nome}${tag}</span>
                    <div class="botoes-item">
                        <button type="button" class="btn-pilula btn-editar-status" data-id="${d.id}" data-tipo="defesa">Editar</button>
                        <button type="button" class="btn-pilula btn-deletar-status" data-id="${d.id}" data-tipo="defesa">Excluir</button>
                    </div>
                </div>
            `);
        });
        document.getElementById('contador-defesas').textContent = `${defesasObj.length}/3`;
    };

    document.getElementById('btn-add-atributo-vazio').addEventListener('click', () => {
        if (atributosObj.length >= 8) return alert("Máximo de 8 atributos atingido.");
        const novoID = gerarID();
        atributosObj.push({ id: novoID, nome: 'Novo Atributo', abrev: '...', valor: '0' });
        renderAtributos();
        
        // Seleciona automaticamente para edição
        atributoEditandoID = novoID;
        document.getElementById('titulo-painel-attr').textContent = 'Editar Atributo';
        document.getElementById('input-nome-atributo').value = 'Novo Atributo';
        document.getElementById('input-abrev-atributo').value = '...';
        document.getElementById('input-valor-atributo').value = '0';
        document.getElementById('input-nome-atributo').focus();
    });

    document.getElementById('btn-add-status-vazio').addEventListener('click', () => {
        if (statusObj.length >= 3) return alert("Máximo de 3 status atingido.");
        statusObj.push({ id: gerarID(), nome: 'NOVO', cor: '#888888', base: 'null' });
        renderStatusEDefesas();
    });

    document.getElementById('btn-add-defesa-vazio').addEventListener('click', () => {
        if (defesasObj.length >= 3) return alert("Máximo de 3 defesas atingido.");
        defesasObj.push({ id: gerarID(), nome: 'NOVO', cor: '#888888', base: 'null' });
        renderStatusEDefesas();
    });

    const resetarFormAtributo = () => {
        atributoEditandoID = null;
        document.getElementById('titulo-painel-attr').textContent = 'Novo Atributo';
        document.getElementById('input-nome-atributo').value = '';
        document.getElementById('input-abrev-atributo').value = '';
        botoesValorAttr.forEach(b => b.classList.remove('ativo'));
        document.querySelector('.botoes-valor-atributo .btn-sel[data-valor="0"]').classList.add('ativo');
        document.getElementById('input-valor-atributo').value = '0';
    };

    const resetarFormStatus = () => {
        statusEditandoID = null;
        editandoTipo = 'status';
        document.getElementById('titulo-painel-status').textContent = 'Novo Status/Defesa';
        document.getElementById('input-nome-status').value = '';
        document.getElementById('input-cor-status').value = '#ed1c24';
        document.querySelectorAll('#botoes-base-status .btn-sel-base').forEach(b => b.classList.remove('ativo'));
        const baseNula = document.querySelector('#botoes-base-status .btn-sel-base[data-base="null"]');
        if (baseNula) baseNula.classList.add('ativo');
        document.getElementById('input-base-status').value = 'null';
    };

    document.querySelector('#aba-atributos .btn-cancelar-escuro').addEventListener('click', resetarFormAtributo);
    document.querySelector('#aba-status .btn-cancelar-escuro').addEventListener('click', resetarFormStatus);

    document.getElementById('btn-salvar-atributo').addEventListener('click', () => {
        const nome = document.getElementById('input-nome-atributo').value.trim();
        const abrev = document.getElementById('input-abrev-atributo').value.trim();
        const valor = document.getElementById('input-valor-atributo').value;

        if (!nome || !abrev) return alert("Nome e Abreviação são obrigatórios!");

        if (atributoEditandoID) {
            const attr = atributosObj.find(a => a.id === atributoEditandoID);
            attr.nome = nome;
            attr.abrev = abrev;
            attr.valor = valor;
        } else {
            if (atributosObj.length >= 8) return alert("Máximo de 8 atributos atingido.");
            atributosObj.push({ id: gerarID(), nome, abrev, valor });
        }

        renderAtributos();
        resetarFormAtributo();
    });

    document.getElementById('btn-salvar-status').addEventListener('click', () => {
        const nome = document.getElementById('input-nome-status').value.trim();
        const cor = document.getElementById('input-cor-status').value;
        const base = document.getElementById('input-base-status').value;

        if (!nome) return alert("O nome é obrigatório!");

        if (statusEditandoID) {
            let lista = editandoTipo === 'status' ? statusObj : defesasObj;
            const stat = lista.find(s => s.id === statusEditandoID);
            stat.nome = nome;
            stat.cor = cor;
            stat.base = base;
        } else {
            // Se não está editando, assume que quer criar um novo Status por padrão (ou o que estiver no título)
            const titulo = document.getElementById('titulo-painel-status').textContent;
            const isDefesa = titulo.toLowerCase().includes('defesa');
            
            if (isDefesa) {
                if (defesasObj.length >= 3) return alert("Máximo de 3 defesas atingido.");
                defesasObj.push({ id: gerarID(), nome, cor, base });
            } else {
                if (statusObj.length >= 3) return alert("Máximo de 3 status atingido.");
                statusObj.push({ id: gerarID(), nome, cor, base });
            }
        }

        renderStatusEDefesas();
        resetarFormStatus();
    });

    document.getElementById('lista-atributos').addEventListener('click', (e) => {
        const id = e.target.dataset.id;
        if (e.target.classList.contains('btn-deletar-attr')) {
            atributosObj = atributosObj.filter(a => a.id !== id);
            renderAtributos();
            if (atributoEditandoID === id) resetarFormAtributo();
        } else if (e.target.classList.contains('btn-editar-attr')) {
            atributoEditandoID = id;
            document.getElementById('titulo-painel-attr').textContent = 'Editar Atributo';

            const a = atributosObj.find(x => x.id === id);
            document.getElementById('input-nome-atributo').value = a.nome;
            document.getElementById('input-abrev-atributo').value = a.abrev;
            document.getElementById('input-valor-atributo').value = a.valor;
            botoesValorAttr.forEach(b => {
                b.classList.toggle('ativo', b.getAttribute('data-valor') === a.valor);
            });
        }
    });

    document.getElementById('container-listas-status-defesa').addEventListener('click', (e) => {
        const id = e.target.dataset.id;
        const tipo = e.target.dataset.tipo;
        if (!id) return;

        let isStatus = tipo === 'status';
        let lista = isStatus ? statusObj : defesasObj;

        if (e.target.classList.contains('btn-deletar-status')) {
            if (isStatus) {
                statusObj = statusObj.filter(s => s.id !== id);
            } else {
                defesasObj = defesasObj.filter(d => d.id !== id);
            }
            renderStatusEDefesas();
            if (statusEditandoID === id) resetarFormStatus();
        } else if (e.target.classList.contains('btn-editar-status')) {
            statusEditandoID = id;
            editandoTipo = tipo;
            document.getElementById('titulo-painel-status').textContent = isStatus ? 'Editar Status' : 'Editar Defesa';

            const item = lista.find(x => x.id === id);
            document.getElementById('input-nome-status').value = item.nome;
            document.getElementById('input-cor-status').value = item.cor;
            document.getElementById('input-base-status').value = item.base;
            atualizarBotoesBaseStatus();
        }
    });

    renderAtributos();
    renderStatusEDefesas();


    // ABA 4: COMPONENTES (Carrossel e Modal)
    // =======================================================
    // (As variáveis foram movidas para o topo para escopo global)
    // =======================================================


    let abaCompAtual = 0;
    let catAtiva = 'CLASSES';
    let compEditandoID = null;

    const trackComp = document.getElementById('track-comp');
    const botoesMenuComp = document.querySelectorAll('.btn-comp-aba');
    const paineisCategoria = document.querySelectorAll('.painel-categoria');
    const contadorCompEl = document.getElementById('contador-comp-atual');

    botoesMenuComp.forEach((btn, index) => {
        btn.addEventListener('click', () => {
            botoesMenuComp.forEach(b => b.classList.remove('ativa'));
            btn.classList.add('ativa');
            abaCompAtual = index;
            catAtiva = btn.textContent.trim();

            trackComp.style.transform = `translateX(-${abaCompAtual * (100 / 7)}%)`;
            atualizarUIComponentes();
        });
    });

    const atualizarUIComponentes = () => {
        const catData = componentesDb[catAtiva];
        contadorCompEl.textContent = `${catData.items.length}/${catData.limit}`;
        paineisCategoria[abaCompAtual].innerHTML = '';

        catData.items.forEach(comp => {
            const verFichaBtn = '';
            paineisCategoria[abaCompAtual].insertAdjacentHTML('beforeend', `
                <div class="item-comp">
                    <h3 class="titulo-comp">${comp.nome}</h3>
                    <div class="item-comp-info">
                        <div class="item-comp-col">
                            <span class="lbl">${catData.labels[0]}</span>
                            <span class="val">${comp.val1}</span> 
                        </div>
                        <div class="item-comp-col">
                            <span class="lbl">${catData.labels[1]}</span>
                            <span class="val">${comp.val2}</span> 
                        </div>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:8px;">
                        ${verFichaBtn}
                        <button type="button" class="btn-pilula btn-editar-comp" data-id="${comp.id}">EDITAR</button>
                    </div>
                </div>
            `);
        });
    };

    window.atualizarUIComponentesIa = atualizarUIComponentes;

    const modalComp = document.getElementById('modal-comp');
    const btnExcluirModal = document.getElementById('btn-excluir-modal');

    const abrirModalComp = () => modalComp.classList.add('ativo');
    const fecharModalComp = () => {
        modalComp.classList.remove('ativo');
        compEditandoID = null;
        document.getElementById('modal-input-nome').value = '';
        document.getElementById('modal-input-val1').value = '';
        document.getElementById('modal-input-val2').value = '';
    };

    document.getElementById('btn-fechar-modal').addEventListener('click', fecharModalComp);

    function configurarModalDinamico(comp = null) {
        const inputNome = document.getElementById('modal-input-nome');
        const inputVal1 = document.getElementById('modal-input-val1');
        const inputVal2 = document.getElementById('modal-input-val2');
        const selectVal2 = document.getElementById('modal-select-val2');
        
        let labelCat = '';
        switch(catAtiva) {
            case 'CLASSES': 
                labelCat = 'Classe'; 
                inputNome.placeholder = 'Ex: Guerreiro, Atirador...';
                inputVal1.placeholder = 'Detalhes e mecânicas...';
                inputVal2.placeholder = 'Habilidades extras...';
                break;
            case 'PERÍCIAS': 
                labelCat = 'Perícia'; 
                inputNome.placeholder = 'Ex: Atletismo, Furtividade...';
                inputVal1.placeholder = 'O que permite fazer...';
                break;
            case 'ORIGENS': 
                labelCat = 'Origem'; 
                inputNome.placeholder = 'Ex: Soldado, Acadêmico...';
                inputVal1.placeholder = 'A história do personagem...';
                inputVal2.placeholder = 'Itens ou poderes bônus...';
                break;
            case 'EQUIPAMENTOS': 
                labelCat = 'Equipamento'; 
                inputNome.placeholder = 'Ex: Espada de Ferro, Capa...';
                inputVal1.placeholder = 'Efeito e características...';
                inputVal2.placeholder = 'Arma, Consumível...';
                break;
            case 'HABILIDADES':
                labelCat = 'Habilidade';
                inputNome.placeholder = 'Ex: Visão Noturna, Mente Sã...';
                inputVal1.placeholder = 'Descrição do efeito...';
                inputVal2.placeholder = 'Requisito de uso (Ex: Nível 2)...';
                break;
            case 'PODERES': 
                labelCat = 'Poder'; 
                inputNome.placeholder = 'Ex: Rajada Mística, Cura...';
                inputVal1.placeholder = 'Dano ou cura descritos...';
                inputVal2.placeholder = 'Ação necessária (Ativa/Passiva)...';
                break;
            default:
                labelCat = 'Componente';
                inputNome.placeholder = 'Ex: Nome do item...';
                inputVal1.placeholder = 'Detalhes breves...';
                inputVal2.placeholder = 'Habilidades extras...';
        }

        document.getElementById('modal-comp-titulo').textContent = comp ? `Editar ${labelCat}` : `Criar ${labelCat}`;

        const grupoVal3 = document.getElementById('grupo-val3');
        const selectVal3 = document.getElementById('modal-select-val3');
        inputVal2.style.display = 'block';

        if (catAtiva === 'PERÍCIAS') {
            grupoVal3.style.display = 'block';
            selectVal3.innerHTML = '';
            if (!window.atributosObj || window.atributosObj.length === 0) {
                selectVal3.innerHTML = '<option value="">Sem Atributos Criados</option>';
            } else {
                window.atributosObj.forEach(a => {
                    const abrev = a.abrev || a.nome.substring(0,3).toUpperCase();
                    selectVal3.insertAdjacentHTML('beforeend', `<option value="${abrev}">${a.nome} (${abrev})</option>`);
                });
            }
            if (comp && comp.val3) selectVal3.value = comp.val3;
        } else {
            grupoVal3.style.display = 'none';
        }
    }

    document.getElementById('btn-criar-comp').addEventListener('click', () => {
        const catData = componentesDb[catAtiva];
        if (catData.items.length >= catData.limit) return alert(`Limite atingido!`);

        if (catAtiva === 'AMEAÇAS' || catAtiva === 'MONSTROS') {
            document.getElementById('m-id-local').value = '';
            document.getElementById('m-nome').value = '';
            document.getElementById('m-tipo').value = 'Ameaça';
            document.getElementById('m-vd').value = 0;
            document.getElementById('m-vida').value = 0;
            document.getElementById('m-defesa').value = 0;
            document.getElementById('m-xp').value = 0;
            document.getElementById('m-desc').value = '';
            document.getElementById('preview-monstro-container').innerHTML = '<i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: var(--premium-accent); opacity: 0.5;"></i>';
            document.querySelector('#modal-criar-monstro h2').textContent = 'Nova Ameaça';
            document.getElementById('btn-save-monstro-local').innerHTML = '<i class="fas fa-skull"></i> CONVOCAR AMEAÇA';

            // Renderiza atributos dinamicamente
            const grid = document.getElementById('grid-atributos-monstro');
            grid.innerHTML = '';
            atributosObj.forEach(at => {
                grid.insertAdjacentHTML('beforeend', `
                    <div class="input-premium-group" style="margin-bottom: 0; display: flex; flex-direction: column;">
                        <label class="input-premium-label" style="text-align: center; margin: 0 0 5px 0; font-size: 0.6rem; color: #888; font-weight: 800;">${at.abrev}</label>
                        <input type="number" class="input-premium-field attr-input-premium" data-id="${at.id}" data-abrev="${at.abrev}" value="0" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 8px; border-radius: 8px; text-align: center; font-weight: 900; font-family: 'Montserrat', sans-serif; outline: none;">
                    </div>
                `);
            });
            document.getElementById('modal-criar-monstro').classList.add('ativo');
            return;
        }

        compEditandoID = null;
        document.getElementById('lbl-val1').textContent = catData.labels[0];
        document.getElementById('lbl-val2').textContent = catData.labels[1];
        btnExcluirModal.style.display = 'none';
        
        configurarModalDinamico();
        abrirModalComp();
    });

    document.getElementById('btn-salvar-modal').addEventListener('click', () => {
        const nome = document.getElementById('modal-input-nome').value.trim();
        const val1 = document.getElementById('modal-input-val1').value;
        const val2 = document.getElementById('modal-input-val2').value;
        let val3 = '';

        if (catAtiva === 'PERÍCIAS') {
            val3 = document.getElementById('modal-select-val3').value;
        }

        if (!nome) return alert("O Nome é obrigatório!");

        if (compEditandoID) {
            const item = componentesDb[catAtiva].items.find(c => c.id === compEditandoID);
            item.nome = nome; item.val1 = val1; item.val2 = val2;
            if (catAtiva === 'PERÍCIAS') item.val3 = val3;
        } else {
            const novoItem = { id: gerarIDComp(), nome, val1, val2 };
            if (catAtiva === 'PERÍCIAS') novoItem.val3 = val3;
            componentesDb[catAtiva].items.push(novoItem);
        }

        fecharModalComp();
        atualizarUIComponentes();
    });

    trackComp.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-editar-comp')) {
            const id = e.target.dataset.id;
            const catData = componentesDb[catAtiva];
            const comp = catData.items.find(c => c.id === id);

            if (!comp) return;

            if (catAtiva === 'AMEAÇAS' || catAtiva === 'MONSTROS') {
                abrirModalEdicaoMonstro(comp);
                return;
            }

            compEditandoID = id;
            document.getElementById('lbl-val1').textContent = catData.labels[0];
            document.getElementById('lbl-val2').textContent = catData.labels[1];

            document.getElementById('modal-input-nome').value = comp.nome;
            document.getElementById('modal-input-val1').value = comp.val1;
            document.getElementById('modal-input-val2').value = comp.val2;

            configurarModalDinamico(comp);
            // Sempre mostra o botão EXCLUIR ao editar
            btnExcluirModal.style.display = 'block';

            abrirModalComp();
        }
    });

    btnExcluirModal.addEventListener('click', () => {
        if (confirm("Tem certeza que deseja excluir?")) {
            componentesDb[catAtiva].items = componentesDb[catAtiva].items.filter(c => c.id !== compEditandoID);
            fecharModalComp();
            atualizarUIComponentes();
        }
    });



    window.executarCanalizacaoIa = async function() {
        const conceito = document.getElementById('ia-conceito-texto').value.trim();
        if (!conceito) {
            await TableModal.alert("Por favor, descreva o conceito do seu universo antes de canalizar.", "Campo Vazio", "warning");
            return;
        }

        if (!TEM_API_KEY) {
            await TableModal.alert("Chave de API do Gemini não configurada! Redirecionando para as configurações de perfil...", "API Key Faltando", "warning");
            window.location.href = "editar-perfil.php?foco=gemini-key";
            return;
        }

        const inputContainer = document.getElementById('ia-input-container');
        const loadingContainer = document.getElementById('ia-loading-container');
        const fraseLoading = document.getElementById('ia-loading-frase');

        // Mostra o loading
        inputContainer.style.display = 'none';
        loadingContainer.style.display = 'block';

        const frases = [
            "Tecendo as regras da realidade...",
            "Moldando as leis da física e da magia...",
            "Forjando os atributos arcanos...",
            "Gerando classes e habilidades lendárias...",
            "Quase lá! CassIA está polindo os monstros e equipamentos..."
        ];
        let fraseIdx = 0;
        const intervalFrases = setInterval(() => {
            fraseIdx = (fraseIdx + 1) % frases.length;
            fraseLoading.textContent = frases[fraseIdx];
        }, 4000);

        try {
            const response = await fetch('../app/ajax/gerar-com-ia.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tipo: 'sistema',
                    conceito: conceito
                })
            });

            const res = await response.json();
            clearInterval(intervalFrases);

            if (res.success && res.data && !res.mock) {
                const data = res.data;

                // 1. Nome do Sistema
                if (data.nome) {
                    document.getElementById('input-nome-sistema').value = data.nome;
                    const event = new Event('input', { bubbles: true });
                    document.getElementById('input-nome-sistema').dispatchEvent(event);
                }

                // 2. Classificação
                if (data.classificacao) {
                    const age = String(data.classificacao).toUpperCase();
                    document.getElementById('input-classificacao').value = age;
                    const btnIdade = document.querySelector(`.btn-idade[data-idade="${age}"]`);
                    if (btnIdade) {
                        document.querySelectorAll('.btn-idade').forEach(b => b.classList.remove('ativo'));
                        btnIdade.classList.add('ativo');
                    }
                }

                // 3. Descrição Tópicos (Aba 1)
                if (data.descricao_topicos && data.descricao_topicos.length > 0) {
                    containerDescricoes.querySelectorAll('.item-descricao').forEach(item => {
                        if (item.id !== 'desc-fixa-1') item.remove();
                    });
                    contadorDescricao = 1;
                    const tit1 = document.getElementById('titulo-desc-1');
                    if (tit1) tit1.value = 'Descrição 1:';

                    const text1 = document.querySelector('#desc-fixa-1 textarea');
                    if (text1) text1.value = data.descricao_topicos[0].conteudo || '';
                    if (tit1 && data.descricao_topicos[0].titulo) {
                        tit1.value = data.descricao_topicos[0].titulo;
                    }
                    
                    for (let i = 1; i < data.descricao_topicos.length; i++) {
                        adicionarDescricao();
                        const items = containerDescricoes.querySelectorAll('.item-descricao');
                        const lastItem = items[items.length - 1];
                        if (lastItem) {
                            const inp = lastItem.querySelector('.input-titulo-desc');
                            const txt = lastItem.querySelector('textarea');
                            if (inp && data.descricao_topicos[i].titulo) inp.value = data.descricao_topicos[i].titulo;
                            if (txt && data.descricao_topicos[i].conteudo) txt.value = data.descricao_topicos[i].conteudo;
                        }
                    }
                }

                // 4. Atributos (Aba Atributos)
                if (data.atributos && data.atributos.length > 0) {
                    window.atributosObj = data.atributos.map(at => ({
                        id: gerarID(),
                        nome: at.nome,
                        abrev: String(at.sigla || at.abrev).toUpperCase().substring(0, 3),
                        valor: String(at.valor || '0')
                    })).slice(0, 8);
                    
                    renderAtributos();
                }

                // 5. Status & Defesas
                if (data.status && data.status.length > 0) {
                    const cores = ['#ed1c24', '#2f3590', '#39b54a'];
                    window.statusObj = data.status.map((st, idx) => ({
                        id: gerarID(),
                        nome: st.nome,
                        cor: cores[idx % cores.length],
                        base: String(st.base || 'null')
                    })).slice(0, 3);
                }

                if (data.defesas && data.defesas.length > 0) {
                    const coresDef = ['#7b4ff7', '#f7931e', '#00aeef'];
                    window.defesasObj = data.defesas.map((df, idx) => ({
                        id: gerarID(),
                        nome: df.nome,
                        cor: coresDef[idx % coresDef.length],
                        base: String(df.base || 'null')
                    })).slice(0, 3);
                }

                renderStatusEDefesas();

                // 6. Componentes (CLASSES, PERÍCIAS, ORIGENS, EQUIPAMENTOS, AMEAÇAS)
                // Classes
                if (data.classes && Array.isArray(data.classes)) {
                    window.componentesDb['CLASSES'].items = data.classes.map(item => {
                        let nomeClasse = item.nome || '';
                        nomeClasse = nomeClasse.split(/[—\-:]/)[0].trim();
                        return {
                            id: '_' + Math.random().toString(36).substr(2, 9),
                            nome: nomeClasse,
                            val1: item.descricao || '',
                            val2: item.habilidade || item.habilidades || item.habilidade_inicial || item.beneficios || item.beneficio || ''
                        };
                    });
                }

                // Perícias
                if (data.pericias && Array.isArray(data.pericias)) {
                    window.componentesDb['PERÍCIAS'].items = data.pericias.map(item => ({
                        id: '_' + Math.random().toString(36).substr(2, 9),
                        nome: (item.nome || '').trim(),
                        val1: item.descricao || '',
                        val2: item.habilidade || item.habilidades || item.habilidade_inicial || item.beneficios || item.beneficio || '',
                        val3: String(item.atributo_chave || '').toUpperCase().substring(0, 3)
                    }));
                }

                // Origens
                if (data.origens && Array.isArray(data.origens)) {
                    window.componentesDb['ORIGENS'].items = data.origens.map(item => {
                        let nomeOrigem = item.nome || '';
                        nomeOrigem = nomeOrigem.split(/[—\-:]/)[0].trim();
                        return {
                            id: '_' + Math.random().toString(36).substr(2, 9),
                            nome: nomeOrigem,
                            val1: item.descricao || '',
                            val2: item.habilidade || item.habilidades || item.habilidade_inicial || item.beneficios || item.beneficio || ''
                        };
                    });
                }

                // Equipamentos
                if (data.equipamentos && Array.isArray(data.equipamentos)) {
                    window.componentesDb['EQUIPAMENTOS'].items = data.equipamentos.map(item => {
                        let props = item.propriedades ? ' | ' + item.propriedades : '';
                        let catItem = (item.tipo || 'outro').toLowerCase();
                        if (catItem.includes('arma')) catItem = 'Arma';
                        else if (catItem.includes('prote') || catItem.includes('escudo')) catItem = 'Proteção';
                        else catItem = 'Utilitário';

                        return {
                            id: '_' + Math.random().toString(36).substr(2, 9),
                            nome: (item.nome || '').trim(),
                            val1: (item.descricao || '') + props,
                            val2: catItem
                        };
                    });
                }

                // Ameaças / Monstros
                if (data.ameacas && Array.isArray(data.ameacas)) {
                    window.componentesDb['AMEAÇAS'].items = data.ameacas.map(item => {
                        const atributosMonstroMapeados = [];
                        if (item.atributos && Array.isArray(item.atributos)) {
                            item.atributos.forEach(mAttr => {
                                const siglaLimpa = String(mAttr.sigla || mAttr.abrev || '').toUpperCase().substring(0, 3);
                                const attrSis = window.atributosObj.find(a => a.abrev === siglaLimpa);
                                if (attrSis) {
                                    atributosMonstroMapeados.push({
                                        id_atributo_temp: attrSis.id,
                                        abrev: attrSis.abrev,
                                        valor: parseInt(mAttr.valor) || 0
                                    });
                                }
                            });
                        }

                        return {
                            id: '_' + Math.random().toString(36).substr(2, 9),
                            nome: (item.nome || '').trim(),
                            val1: item.tipo || 'Ameaça',
                            val2: parseInt(item.vida) || 20,
                            vida: parseInt(item.vida) || 20,
                            defesa: parseInt(item.defesa) || 10,
                            xp: parseInt(item.xp) || 0,
                            desc: item.descricao || '',
                            atributos_monstro: atributosMonstroMapeados
                        };
                    });
                }

                // Distribui Habilidades (passivas) e Poderes (ativos) pelos campos corretos
                if (data.habilidades && Array.isArray(data.habilidades)) {
                    window.componentesDb['HABILIDADES'].items = data.habilidades.map(item => {
                        // Separar "Requer X" da descrição para o campo Requisito
                        let descricao = item.descricao || item.val1 || '';
                        let requisito = item.requisito || item.val2 || '';
                        // Limpar possível "Requer ..." embutido na descrição e mover para requisito
                        const requerMatch = descricao.match(/[.\.\s]*(Requer[^.]+\.?)/i);
                        if (requerMatch && !requisito) {
                            requisito = requerMatch[1].replace(/^[.\s]+/, '').trim();
                            descricao = descricao.replace(requerMatch[0], '').trim();
                        }
                        return {
                            id: '_' + Math.random().toString(36).substr(2, 9),
                            nome: (item.nome || '').trim(),
                            val1: descricao,
                            val2: requisito
                        };
                    });
                }

                if (data.poderes && Array.isArray(data.poderes)) {
                    window.componentesDb['PODERES'].items = data.poderes.map(item => ({
                        id: '_' + Math.random().toString(36).substr(2, 9),
                        nome: (item.nome || '').trim(),
                        val1: item.descricao || item.val1 || '',
                        val2: item.custo || item.val2 || 'Ativo'
                    }));
                }

                // Forçar atualização do painel de componentes
                atualizarUIComponentes();

                // Fechar modal de IA
                const modalIa = document.getElementById('modal-ia-sistema');
                if (modalIa) {
                    modalIa.style.display = 'none';
                    modalIa.classList.remove('ativo');
                }
                await TableModal.alert("O universo foi canalizado e estruturado pela CassIA com sucesso! Revise as abas antes de salvar.", "Canalização Concluída", "success");

            } else {
                const modalIa = document.getElementById('modal-ia-sistema');
                if (modalIa) {
                    modalIa.style.display = 'none';
                    modalIa.classList.remove('ativo');
                }
                if (res.mock && res.aviso) {
                    await TableModal.alert(res.aviso, "CassIA Indisponível", "warning");
                } else if (res.error === 'API_KEY_MISSING') {
                    await TableModal.alert("Sua chave de API da CassIA não foi encontrada no banco. Redirecionando para cadastrar...", "Erro de Configuração", "error");
                    window.location.href = "editar-perfil.php?foco=gemini-key";
                } else {
                    await TableModal.alert("Houve uma falha na canalização da CassIA: " + res.error, "Erro de IA", "error");
                }
            }
        } catch (err) {
            clearInterval(intervalFrases);
            const modalIa = document.getElementById('modal-ia-sistema');
            if (modalIa) {
                modalIa.style.display = 'none';
                modalIa.classList.remove('ativo');
            }
            await TableModal.alert("Erro de comunicação com a CassIA: " + err.message, "Falha de Conexão", "error");
        } finally {
            // Restaura o modal de input para o próximo uso
            inputContainer.style.display = 'block';
            loadingContainer.style.display = 'none';
        }
    };

    window.renderAtributosIa = renderAtributos;
    window.renderStatusEDefesasIa = renderStatusEDefesas;

    atualizarUIComponentes();
});

// Funções globais para o Modal Premium de Monstros
function fecharModal(id) {
    document.getElementById(id).classList.remove('ativo');
}

function previewImagemMonstro(input) {
    if (input.files && input.files[0]) {
        abrirCropperModal(input.files[0], 1, (croppedBlob, croppedBase64) => {
            const container = document.getElementById('preview-monstro-container');
            container.innerHTML = `<img src="${croppedBase64}" style="width:100%; height:100%; object-fit:cover;">`;
            
            // Injetar o arquivo recortado de volta no input para consistência
            const newFile = new File([croppedBlob], input.files[0].name || "ameaca.jpg", { type: "image/jpeg" });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(newFile);
            input.files = dataTransfer.files;
        });
    }
}

function abrirModalEdicaoMonstro(comp) {
    document.getElementById('m-id-local').value = comp.id;
    document.getElementById('m-nome').value = comp.nome;
    document.getElementById('m-tipo').value = comp.val1;
    document.getElementById('m-vd').value = comp.val2;
    document.getElementById('m-vida').value = comp.vida || 0;
    document.getElementById('m-defesa').value = comp.defesa || 0;
    document.getElementById('m-xp').value = comp.xp || 0;
    document.getElementById('m-desc').value = comp.desc || '';
    
    const grid = document.getElementById('grid-atributos-monstro');
    grid.innerHTML = '';
    window.atributosObj.forEach(at => {
        let valAttr = 0;
        if (comp.atributos_monstro) {
            const attrMatch = comp.atributos_monstro.find(a => a.abrev === at.abrev || a.id_atributo_temp === at.id);
            if (attrMatch) valAttr = attrMatch.valor;
        }

        grid.insertAdjacentHTML('beforeend', `
            <div class="premium-attr-box-escudo" style="display: flex; align-items: stretch; border: 1.5px solid #fff; border-radius: 8px; overflow: hidden; height: 38px; width: 100%; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
                <span class="attr-abbr-escudo" style="background: #fff; color: #2d1b4e; width: 34px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 0.72rem; border-radius: 6px 0 0 6px; text-transform: uppercase; flex-shrink: 0; font-family: inherit;">${at.abrev}</span>
                <input type="number" class="input-premium-field attr-input-premium" data-id="${at.id}" data-abrev="${at.abrev}" value="${valAttr}" style="background: rgba(0,0,0,0.4); color: #fff; border: none; flex: 1; text-align: center; font-size: 1.1rem; font-weight: 900; border-radius: 0 6px 6px 0; outline: none; padding: 0; margin: 0; height: 100%;">
            </div>
        `);
    });

    if (comp.foto_base64) {
        document.getElementById('preview-monstro-container').innerHTML = `<img src="${comp.foto_base64}" style="width:100%; height:100%; object-fit:cover;">`;
    } else {
        document.getElementById('preview-monstro-container').innerHTML = '<i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: var(--premium-accent); opacity: 0.5;"></i>';
    }

    document.querySelector('#modal-criar-monstro h2').textContent = 'Editar Ameaça';
    document.getElementById('btn-save-monstro-local').innerHTML = '<i class="fas fa-skull"></i> ATUALIZAR AMEAÇA';
    document.getElementById('modal-criar-monstro').classList.add('ativo');
}

function salvarMonstro() {
    const nome = document.getElementById('m-nome').value.trim();
    const idLocal = document.getElementById('m-id-local').value;
    const tipo = document.getElementById('m-tipo').value || 'Ameaça';
    const vd = document.getElementById('m-vd').value || 0;
    const vida = document.getElementById('m-vida').value || 0;
    const defesa = document.getElementById('m-defesa').value || 0;
    const xp = document.getElementById('m-xp').value || 0;
    const desc = document.getElementById('m-desc').value || '';

    if(!nome) {
        TableModal.alert('Dê um nome à ameaça!', 'Campo Obrigatório', 'warning');
        return;
    }

    const atributos = [];
    document.querySelectorAll('.attr-input-premium').forEach(input => {
        atributos.push({
            id_atributo_temp: input.getAttribute('data-id'),
            abrev: input.getAttribute('data-abrev'),
            valor: input.value || 0
        });
    });

    const ameacaData = {
        nome: nome,
        val1: tipo,
        val2: vida,
        desc: desc,
        vida: vida,
        defesa: defesa,
        xp: xp,
        atributos_monstro: atributos,
        foto_base64: document.querySelector('#preview-monstro-container img')?.src || null
    };

    if (idLocal) {
        const idx = componentesDb['AMEAÇAS'].items.findIndex(c => c.id == idLocal);
        componentesDb['AMEAÇAS'].items[idx] = { ...componentesDb['AMEAÇAS'].items[idx], ...ameacaData };
    } else {
        const gerarIDComp = () => '_' + Math.random().toString(36).substr(2, 9);
        componentesDb['AMEAÇAS'].items.push({
            id: gerarIDComp(),
            ...ameacaData
        });
    }

    fecharModal('modal-criar-monstro');
    
    // Atualiza a listagem programaticamente simulando clique na aba atual
    const btnAtivo = document.querySelector('.btn-comp-aba.ativa');
    if(btnAtivo && btnAtivo.textContent.trim() === 'AMEAÇAS') btnAtivo.click();
}

function verFichaMonstroLocal(idLocal) {
    const c = document.getElementById('ficha-monstro-render');
    if (!c) return;
    
    const m = componentesDb['AMEAÇAS'].items.find(x => x.id === idLocal);
    if (!m) return;
    
    c.innerHTML = '<div style="padding:40px;text-align:center;color:#888;"><i class="fas fa-spinner fa-spin"></i> Lendo Grimório...</div>';
    document.getElementById('modal-ficha-monstro').classList.add('ativo');
    
    const attrsHtml = (m.atributos_monstro || []).map(a => `
        <div style="text-align:center; min-width:48px;">
            <span style="font-size:.7rem;display:block;color:var(--premium-accent);text-transform:uppercase;font-weight:700;">${a.abrev}</span>
            <div style="width:40px;height:40px;font-size:1rem;border:2px solid ${parseInt(a.valor)>0?'var(--premium-accent)':'#444'};border-radius:50%;display:flex;align-items:center;justify-content:center;margin:5px auto 0 auto;color:#fff;font-weight:800;">
                ${a.valor}
            </div>
        </div>
    `).join('');

    const fotoImg = m.foto_base64 ? `<img src="${m.foto_base64}" style="width:75px; height:75px; border-radius:50%; border:3px solid var(--premium-accent); object-fit:cover; box-shadow:0 0 15px rgba(139,92,246,0.4);">` : '';

    setTimeout(() => {
        c.innerHTML = `
            <div style="background:linear-gradient(135deg,#1e0b3a,#311c61);padding:20px 25px;border-bottom:2px solid var(--premium-accent); display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:15px;">
                    ${fotoImg}
                    <div>
                        <h1 style="color:#fff;font-weight:900;font-size:1.6rem;margin:0 0 4px 0;line-height:1.2;text-transform:uppercase;">${m.nome}</h1>
                        <span style="color:var(--premium-accent);font-weight:800;font-size:.8rem;text-transform:uppercase;letter-spacing:1px;">${m.val1||'Ameaça'}</span>
                    </div>
                </div>
                <i class="fas fa-times" onclick="fecharModal('modal-ficha-monstro')" style="color:#fff;cursor:pointer;font-size:1.2rem;opacity:0.7;transition:opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'"></i>
            </div>
            <div style="padding:25px;">
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;margin-bottom:25px;">
                    <div style="background:rgba(255,255,255,.03);padding:12px;border-radius:12px;text-align:center;border:1px solid rgba(255,255,255,.05);"><span style="display:block;color:#ff4d4d;font-weight:900;font-size:.65rem;margin-bottom:3px;letter-spacing:1px;text-transform:uppercase;">VIDA</span><strong style="color:#fff;font-size:1.4rem;">${m.vida}</strong></div>
                    <div style="background:rgba(255,255,255,.03);padding:12px;border-radius:12px;text-align:center;border:1px solid rgba(255,255,255,.05);"><span style="display:block;color:#2980b9;font-weight:900;font-size:.65rem;margin-bottom:3px;letter-spacing:1px;text-transform:uppercase;">DEFESA</span><strong style="color:#fff;font-size:1.4rem;">${m.defesa}</strong></div>
                </div>
                
                <h3 style="color:var(--premium-accent);font-size:.75rem;font-weight:900;margin-bottom:10px;text-transform:uppercase;letter-spacing:1px;">Atributos</h3>
                <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:15px;margin-bottom:25px;background:rgba(0,0,0,0.2);padding:15px;border-radius:12px;border:1px solid rgba(255,255,255,0.03);">
                    ${attrsHtml || '<p style="color:#666;font-size:0.8rem;font-style:italic;margin:0;">Nenhum atributo associado</p>'}
                </div>
                
                <div style="background:rgba(0,0,0,.3);padding:20px;border-radius:15px;border:1px solid rgba(255,255,255,.05);">
                    <h3 style="color:var(--premium-accent);font-size:.75rem;font-weight:900;margin-bottom:10px;text-transform:uppercase;letter-spacing:1px;">HABILIDADES / DETALHES</h3>
                    <p style="color:#ccc;font-size:.85rem;line-height:1.6;margin:0;white-space:pre-wrap;">${m.desc||'Nenhuma habilidade descrita.'}</p>
                </div>
            </div>`;
    }, 300);
}
