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

// js/editar-sistema.js
console.log('TABLE | editar-sistema.js carregado v1.4');
document.addEventListener('DOMContentLoaded', () => {

    // 1. Verificação de Mestre (Simulação)
    const sessao = JSON.parse(localStorage.getItem('table_sessao_ativa'));
    if (!sessao || (sessao.cargo !== 'mestre' && sessao.cargo !== 'admin')) {
        // window.location.href = 'perfil.php'; 
    }

    // =======================================================
    // REMOVIDO: FORÇAR MAIÚSCULAS NOS INPUTS (Agora permite minúsculas)
    // =======================================================


    // =======================================================
    // NAVEGAÇÃO POR ABAS
    // =======================================================
    const abas = document.querySelectorAll('.aba');
    const conteudos = document.querySelectorAll('.conteudo-aba');
    const indicador = document.querySelector('.indicador-aba');

    function ativarAba(aba) {
        abas.forEach(a => a.classList.remove('ativa'));
        conteudos.forEach(c => c.classList.remove('ativa'));
        aba.classList.add('ativa');
        const alvo = document.getElementById(aba.getAttribute('data-alvo'));
        if (alvo) alvo.classList.add('ativa');
        const index = aba.getAttribute('data-index');
        indicador.style.transform = `translateX(${index * 100}%)`;
    }

    abas.forEach(aba => {
        aba.addEventListener('click', () => ativarAba(aba));
    });

    if (abas.length > 0) ativarAba(abas[0]);

    document.querySelectorAll('.btn-proximo-aba').forEach(btn => {
        btn.addEventListener('click', () => {
            const abaAtual = document.querySelector('.aba.ativa');
            const currentIndex = parseInt(abaAtual.getAttribute('data-index'));
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

    // =======================================================
    // PREENCHIMENTO INICIAL (MODO EDIÇÃO)
    // =======================================================
    const gerarID = () => '_' + Math.random().toString(36).substr(2, 9);
    const gerarIDComp = () => '_' + Math.random().toString(36).substr(2, 9);

    window.atributosObj = [];
    window.componentesDb = {
        'CLASSES': { limit: 15, labels: ['DESCRIÇÃO', 'HABILIDADES'], items: [] },
        'PERÍCIAS': { limit: 30, labels: ['DESCRIÇÃO', 'HABILIDADES'], items: [] },
        'ORIGENS': { limit: 75, labels: ['DESCRIÇÃO', 'HABILIDADES'], items: [] },
        'EQUIPAMENTOS': { limit: 100, labels: ['DESCRIÇÃO', 'CATEGORIA'], items: [] },
        'PODERES': { limit: 50, labels: ['DESCRIÇÃO', 'DURAÇÃO'], items: [] },
        'AMEAÇAS': { limit: 50, labels: ['TIPO DA AMEAÇA', 'VD'], items: [] }
    };
    let statusObj = [];
    let defesasObj = [];
    let statusEditandoID = null;
    let editandoTipo = 'status';
    let imagemBase64 = null;

    if (typeof SYSTEM_DB !== 'undefined') {
        document.getElementById('input-nome-sistema').value = SYSTEM_DB.nm_sistema;
        document.getElementById('input-classificacao').value = SYSTEM_DB.tp_classificacao;
        
        const nomeSisLower = (SYSTEM_DB.nm_sistema || '').toLowerCase();
        const isOrdemParanormal = nomeSisLower.includes('ordem paranormal');
        if (!isOrdemParanormal) {
            window.componentesDb['AMEAÇAS'].labels[1] = 'VT';
        }
        
        const btnIdade = document.querySelector(`.btn-idade[data-idade="${SYSTEM_DB.tp_classificacao}"]`);
        if (btnIdade) {
            document.querySelectorAll('.btn-idade').forEach(b => b.classList.remove('ativo'));
            btnIdade.classList.add('ativo');
        }

        const botoesIdade = document.querySelectorAll('.btn-idade');
        botoesIdade.forEach(botao => {
            botao.addEventListener('click', () => {
                botoesIdade.forEach(b => b.classList.remove('ativo'));
                botao.classList.add('ativo');
                document.getElementById('input-classificacao').value = botao.getAttribute('data-idade');
            });
        });

        if (SYSTEM_DB.ds_descricao) {
            const blocos = SYSTEM_DB.ds_descricao.split('\n\n');

            // Função que extrai o título da primeira linha de um bloco
            const extrairTituloEConteudo = (bloco) => {
                const linhas = bloco.split('\n');
                // Considera a 1ª linha como título se tiver no máximo 100 caracteres
                if (linhas.length > 1 && linhas[0].length <= 100) {
                    return { titulo: linhas[0].trim(), conteudo: linhas.slice(1).join('\n').trim() };
                }
                return { titulo: '', conteudo: bloco.trim() };
            };

            // Primeiro bloco (fixo)
            const primeiroBloco = extrairTituloEConteudo(blocos[0]);
            const firstTextArea = document.querySelector('#desc-fixa-1 textarea');
            const firstTituloInput = document.querySelector('#desc-fixa-1 .input-titulo-desc');
            if (firstTextArea) firstTextArea.value = primeiroBloco.conteudo || blocos[0].trim();
            if (firstTituloInput && primeiroBloco.titulo) firstTituloInput.value = primeiroBloco.titulo;

            // Blocos adicionais
            for (let i = 1; i < blocos.length; i++) {
                const blocoAtual = extrairTituloEConteudo(blocos[i]);
                const idUnico = Date.now() + i;
                const tituloValor = blocoAtual.titulo || `Descrição ${i+1}`;
                const html = `
                    <div class="item-descricao" id="desc-${idUnico}">
                        <div class="cabecalho-descricao" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <input type="text" class="input-titulo-desc" value="${tituloValor.replace(/"/g, '&quot;')}" style="width: 50%;">
                            <button type="button" class="btn-texto btn-excluir-desc-inline" data-id="desc-${idUnico}">Excluir <i class="fas fa-times"></i></button>
                        </div>
                        <textarea class="input-escuro textarea-escuro" required placeholder="Digite os detalhes aqui..."></textarea>
                    </div>
                `;
                document.getElementById('container-descricoes').insertAdjacentHTML('beforeend', html);
                // Atribui o conteúdo via .value para evitar problemas de encoding
                const novoItem = document.getElementById(`desc-${idUnico}`);
                if (novoItem) {
                    const ta = novoItem.querySelector('textarea');
                    if (ta) ta.value = blocoAtual.conteudo || blocos[i].trim();
                }
            }
        }

        if (SYSTEM_DB.ds_imagem) {
            const previewImagem = document.getElementById('preview-imagem');
            const silhuetas = previewImagem.querySelectorAll('div');
            previewImagem.style.backgroundImage = `url(${SYSTEM_DB.ds_imagem})`;
            silhuetas.forEach(s => s.style.display = 'none');
        }
    }

    if (typeof STATUS_DB !== 'undefined' && STATUS_DB.length > 0) {
        statusObj = STATUS_DB.filter(s => s.tp_status === 'barra').map(s => ({
            id: s.id_status_sistema,
            nome: s.nm_status,
            cor: s.ds_cor,
            base: 'null'
        }));
        defesasObj = STATUS_DB.filter(s => s.tp_status === 'defesa').map(s => ({
            id: s.id_status_sistema,
            nome: s.nm_status,
            cor: s.ds_cor,
            base: 'null'
        }));
    } else {
        statusObj = [];
        defesasObj = [];
    }

    if (typeof ATRIBS_DB !== 'undefined' && ATRIBS_DB.length > 0) {
        window.atributosObj = ATRIBS_DB.map(a => ({ id: a.id_atributo, nome: a.nm_atributo, abrev: a.ds_abreviacao, valor: a.qt_valor_minimo || '0' }));
    } else {
        window.atributosObj = [];
    }

    if (typeof CLASSES_DB !== 'undefined') window.componentesDb['CLASSES'].items = CLASSES_DB.map(c => ({ id: c.id_classe, nome: c.nm_classe, val1: c.ds_descricao, val2: c.ds_habilidade }));
    if (typeof PERICIAS_DB !== 'undefined') window.componentesDb['PERÍCIAS'].items = PERICIAS_DB.map(p => ({ id: p.id_pericia, nome: p.nm_pericia, val1: p.ds_descricao, val2: p.ds_habilidade, val3: p.ds_atributo_base }));
    if (typeof ORIGENS_DB !== 'undefined') window.componentesDb['ORIGENS'].items = ORIGENS_DB.map(o => ({ id: o.id_origem, nome: o.nm_origem, val1: o.ds_origem, val2: o.ds_habilidade }));
    if (typeof MONSTROS_DB !== 'undefined') {
        window.componentesDb['AMEAÇAS'].items = MONSTROS_DB.map(m => ({
            id: m.id_monstro,
            nome: m.nm_monstro,
            val1: m.tp_monstro,
            val2: m.qt_vd,
            desc: m.ds_monstro,
            vida: m.qt_vida,
            defesa: m.qt_defesa,
            xp: m.qt_xp_recompensa,
            atributos_monstro: m.atributos || [], 
            foto_base64: m.ds_imagem
        }));
    }
    if (typeof ITENS_DB !== 'undefined') window.componentesDb['EQUIPAMENTOS'].items = ITENS_DB.map(i => ({ id: i.id_item, nome: i.nm_item, val1: i.ds_item, val2: i.tp_item }));
    if (typeof PODERES_DB !== 'undefined') window.componentesDb['PODERES'].items = PODERES_DB.map(h => ({ id: h.id_habilidade, nome: h.nm_habilidade, val1: h.ds_habilidade, val2: h.tp_habilidade }));

    // =======================================================
    // SUBMISSÃO
    // =======================================================
    document.getElementById('form-criar-sistema').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.querySelector('.btn-concluir');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        
        let descTotal = '';
        const descItemsEdit = document.querySelectorAll('.item-descricao');
        descItemsEdit.forEach((item, index) => {
            const tituloInput = item.querySelector('.input-titulo-desc');
            const textareaEl = item.querySelector('textarea');
            if (!textareaEl || textareaEl.value.trim() === '') return;
            const titulo = (tituloInput ? tituloInput.value.trim().replace(/:$/, '') : `Descrição ${index + 1}`);
            const bloco = titulo + '\n' + textareaEl.value.trim();
            descTotal += (descTotal ? '\n\n' : '') + bloco;
        });

        const payload = {
            id_sistema: ID_SISTEMA_EDIT,
            nome: document.getElementById('input-nome-sistema').value,
            classificacao: document.getElementById('input-classificacao').value,
            descricao: descTotal,
            atributos: window.atributosObj,
            status: statusObj,
            defesas: defesasObj,
            imagem_base64: imagemBase64,
            classes: window.componentesDb['CLASSES'].items,
            pericias: window.componentesDb['PERÍCIAS'].items,
            origens: window.componentesDb['ORIGENS'].items,
            equipamentos: window.componentesDb['EQUIPAMENTOS'].items,
            poderes: window.componentesDb['PODERES'].items,
            monstros: window.componentesDb['AMEAÇAS'].items
        };

        try {
            const req = await fetch('../app/ajax/update-sistema.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            const res = await req.json();
            if(res.success) {
                alert("Alterações salvas com sucesso!");
                window.location.href = 'exibir-sistema.php?id=' + ID_SISTEMA_EDIT;
            } else {
                alert("Falha ao atualizar: " + res.error);
                btn.innerHTML = 'Salvar Sistema <i class="fas fa-check"></i>';
            }
        } catch(e) {
            alert("Erro de comunicação.");
            btn.innerHTML = 'Salvar Sistema <i class="fas fa-check"></i>';
        }
    });

    // =======================================================
    // UPLOAD DE IMAGEM
    // =======================================================
    const btnTrocarFoto = document.getElementById('btn-trocar-foto');
    const inputFoto = document.getElementById('input-foto-sistema');
    const previewImagem = document.getElementById('preview-imagem');
    const silhuetas = previewImagem.querySelectorAll('div');

    if (btnTrocarFoto) {
        btnTrocarFoto.addEventListener('click', () => inputFoto.click());
    }

    if (inputFoto) {
        inputFoto.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                // Validação de tamanho (ex: 5MB) para evitar estouro de POST
                if (file.size > 5 * 1024 * 1024) {
                    TableModal.alert("A imagem é muito pesada! Escolha uma de até 5MB.", "Imagem muito grande", "warning");
                    return;
                }

                abrirCropperModal(file, 16/9, (croppedBlob, croppedBase64) => {
                    previewImagem.style.backgroundImage = `url(${croppedBase64})`;
                    previewImagem.style.backgroundSize = 'cover';
                    previewImagem.style.backgroundPosition = 'center';
                    silhuetas.forEach(s => s.style.display = 'none');
                    imagemBase64 = croppedBase64;
                });
            }
        });
    }

    // =======================================================
    // DESCRIÇÕES DINÂMICAS
    // =======================================================
    const containerDescricoes = document.getElementById('container-descricoes');
    const btnAddDescGlobal = document.getElementById('btn-add-desc-global');
    const btnExcluirDescGlobal = document.getElementById('btn-excluir-desc-global');
    let modoExclusao = false;

    const cancelarModoExclusao = () => {
        modoExclusao = false;
        containerDescricoes.classList.remove('modo-exclusao');
        btnExcluirDescGlobal.innerHTML = 'Excluir tópico <i class="far fa-minus-square"></i>';
        btnExcluirDescGlobal.style.color = '';
    };

    btnAddDescGlobal.addEventListener('click', () => {
        const itensAtuais = containerDescricoes.querySelectorAll('.item-descricao');
        if (itensAtuais.length >= 10) {
            alert("Limite atingido: Você já possui 10 tópicos de descrição.");
            return;
        }
        const count = itensAtuais.length + 1;
        const idUnico = Date.now();
        const html = `
            <div class="item-descricao" id="desc-${idUnico}">
                <div class="cabecalho-descricao" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <input type="text" class="input-titulo-desc" value="Descrição ${count}:" style="width: 50%;">
                    <button type="button" class="btn-texto btn-excluir-desc-inline" data-id="desc-${idUnico}">Excluir <i class="fas fa-times"></i></button>
                </div>
                <textarea class="input-escuro textarea-escuro" required placeholder="Digite os detalhes aqui..."></textarea>
            </div>
        `;
        containerDescricoes.insertAdjacentHTML('beforeend', html);
    });

    btnExcluirDescGlobal.addEventListener('click', () => {
        if (!modoExclusao && containerDescricoes.querySelectorAll('.item-descricao').length <= 1) return alert("Não pode excluir a última descrição.");
        modoExclusao = !modoExclusao;
        containerDescricoes.classList.toggle('modo-exclusao', modoExclusao);
        btnExcluirDescGlobal.innerHTML = modoExclusao ? 'Cancelar <i class="far fa-times-circle"></i>' : 'Excluir tópico <i class="far fa-minus-square"></i>';
        btnExcluirDescGlobal.style.color = modoExclusao ? '#ff4d4d' : '';
    });

    containerDescricoes.addEventListener('click', (e) => {
        if (e.target.closest('.btn-excluir-desc-inline')) {
            const id = e.target.closest('.btn-excluir-desc-inline').getAttribute('data-id');
            document.getElementById(id).remove();
            cancelarModoExclusao();
        }
    });

    // =======================================================
    // ATRIBUTOS E STATUS
    // =======================================================
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
        window.atributosObj.forEach(attr => {
            container.insertAdjacentHTML('beforeend', `<button type="button" class="btn-sel btn-sel-base ${atual === attr.abrev ? 'ativo' : ''}" data-base="${attr.abrev}">${attr.abrev}</button>`);
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
            
            thumb.style.width = (ratio >= 1 ? 0 : thumbW) + 'px';
            thumb.style.left = thumbLeft + 'px';
            thumb.style.opacity = ratio >= 1 ? '0' : '1';
        };

        sc.onscroll = update;

        let drag = false, startX = 0, startScroll = 0;
        
        thumb.onmousedown = (e) => {
            drag = true; startX = e.clientX;
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
                thumb.style.background = '';
            }
        };

        track.onclick = (e) => {
            if (e.target === thumb) return;
            const rect = track.getBoundingClientRect();
            const clickPos = (e.clientX - rect.left) / track.clientWidth;
            sc.scrollLeft = clickPos * (sc.scrollWidth - sc.clientWidth);
        };

        sc.onwheel = (e) => {
            if (Math.abs(e.deltaY) > 0) {
                sc.scrollLeft += e.deltaY;
                e.preventDefault();
            }
        };

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
        window.atributosObj.forEach(a => {
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
        document.getElementById('contador-atributos').textContent = `${window.atributosObj.length}/8`;
        atualizarBotoesBaseStatus();
    };

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

    document.getElementById('btn-add-status-vazio').addEventListener('click', () => {
        if (statusObj.length >= 3) return alert("Máximo atingido.");
        resetarFormStatus();
        document.getElementById('titulo-painel-status').textContent = 'Novo Status';
    });

    document.getElementById('btn-add-defesa-vazio').addEventListener('click', () => {
        if (defesasObj.length >= 3) return alert("Máximo atingido.");
        resetarFormStatus();
        document.getElementById('titulo-painel-status').textContent = 'Nova Defesa';
    });

    document.getElementById('btn-salvar-status').addEventListener('click', () => {
        const nome = document.getElementById('input-nome-status').value.trim();
        const cor = document.getElementById('input-cor-status').value;
        const base = document.getElementById('input-base-status').value;

        if (!nome) return alert("O nome é obrigatório!");

        if (statusEditandoID) {
            let lista = editandoTipo === 'status' ? statusObj : defesasObj;
            const stat = lista.find(s => s.id == statusEditandoID);
            stat.nome = nome;
            stat.cor = cor;
            stat.base = base;
        } else {
            const titulo = document.getElementById('titulo-painel-status').textContent;
            const isDefesa = titulo.toLowerCase().includes('defesa');
            if (isDefesa) {
                if (defesasObj.length >= 3) return alert("Máximo atingido.");
                defesasObj.push({ id: gerarID(), nome, cor, base });
            } else {
                if (statusObj.length >= 3) return alert("Máximo atingido.");
                statusObj.push({ id: gerarID(), nome, cor, base });
            }
        }
        renderStatusEDefesas();
        resetarFormStatus();
    });

    document.querySelector('#aba-status .btn-cancelar-escuro').addEventListener('click', resetarFormStatus);

    document.getElementById('container-listas-status-defesa').addEventListener('click', (e) => {
        const id = e.target.dataset.id;
        const tipo = e.target.dataset.tipo;
        if (!id) return;
        let isStatus = tipo === 'status';
        let lista = isStatus ? statusObj : defesasObj;

        if (e.target.classList.contains('btn-deletar-status')) {
            if (isStatus) statusObj = statusObj.filter(s => s.id != id);
            else defesasObj = defesasObj.filter(d => d.id != id);
            renderStatusEDefesas();
            if (statusEditandoID == id) resetarFormStatus();
        } else if (e.target.classList.contains('btn-editar-status')) {
            statusEditandoID = id;
            editandoTipo = tipo;
            document.getElementById('titulo-painel-status').textContent = isStatus ? 'Editar Status' : 'Editar Defesa';
            const item = lista.find(x => x.id == id);
            document.getElementById('input-nome-status').value = item.nome;
            document.getElementById('input-cor-status').value = item.cor;
            document.getElementById('input-base-status').value = item.base;
            atualizarBotoesBaseStatus();
        }
    });

    document.getElementById('btn-add-atributo-vazio').addEventListener('click', () => {
        if (window.atributosObj.length >= 8) return alert("Máximo atingido.");
        const novoID = gerarID();
        window.atributosObj.push({ id: novoID, nome: 'Novo Atributo', abrev: '...', valor: '0' });
        renderAtributos();
        
        // Seleciona automaticamente para edição
        window.atributoEditandoID = novoID;
        document.getElementById('titulo-painel-attr').textContent = 'Editar Atributo';
        document.getElementById('input-nome-atributo').value = 'Novo Atributo';
        document.getElementById('input-abrev-atributo').value = '...';
        document.getElementById('input-valor-atributo').value = '0';
        document.getElementById('input-nome-atributo').focus();
    });

    document.getElementById('lista-atributos').addEventListener('click', (e) => {
        const id = e.target.dataset.id;
        if (e.target.classList.contains('btn-deletar-attr')) {
            window.atributosObj = window.atributosObj.filter(a => a.id != id);
            renderAtributos();
        } else if (e.target.classList.contains('btn-editar-attr')) {
            const a = window.atributosObj.find(x => x.id == id);
            document.getElementById('titulo-painel-attr').textContent = 'Editar Atributo';
            document.getElementById('input-nome-atributo').value = a.nome;
            document.getElementById('input-abrev-atributo').value = a.abrev;
            document.getElementById('input-valor-atributo').value = a.valor;
            botoesValorAttr.forEach(b => b.classList.toggle('ativo', b.getAttribute('data-valor') == a.valor));
            window.atributoEditandoID = id;
        }
    });

    document.getElementById('btn-salvar-atributo').addEventListener('click', () => {
        const nome = document.getElementById('input-nome-atributo').value.trim();
        const abrev = document.getElementById('input-abrev-atributo').value.trim();
        const valor = document.getElementById('input-valor-atributo').value;

        if (!nome || !abrev) return alert("Nome e Abreviação são obrigatórios!");

        if (window.atributoEditandoID) {
            const attr = window.atributosObj.find(a => a.id == window.atributoEditandoID);
            attr.nome = nome;
            attr.abrev = abrev;
            attr.valor = valor;
        } else {
            if (window.atributosObj.length >= 8) return alert("Máximo atingido.");
            window.atributosObj.push({ id: gerarID(), nome, abrev, valor });
        }
        renderAtributos();
        resetarFormAtributo();
    });

    renderAtributos();
    renderStatusEDefesas();

    // =======================================================
    // COMPONENTES
    // =======================================================
    let abaCompAtual = 0;
    let catAtiva = 'CLASSES';

    const trackComp = document.getElementById('track-comp');
    const botoesMenuComp = document.querySelectorAll('.btn-comp-aba');
    const paineisCategoria = document.querySelectorAll('.painel-categoria');

    botoesMenuComp.forEach((btn, index) => {
        btn.addEventListener('click', () => {
            botoesMenuComp.forEach(b => b.classList.remove('ativa'));
            btn.classList.add('ativa');
            abaCompAtual = index;
            catAtiva = btn.getAttribute('data-cat') || btn.textContent.trim();
            trackComp.style.transform = `translateX(-${abaCompAtual * (100 / 6)}%)`;
            atualizarUIComponentes();
        });
    });

    const atualizarUIComponentes = () => {
        const nomeSis = document.getElementById('input-nome-sistema').value.toLowerCase();
        const isOrdem = nomeSis.includes('ordem paranormal');
        if (window.componentesDb['AMEAÇAS']) {
            window.componentesDb['AMEAÇAS'].labels[1] = isOrdem ? 'VD' : 'VT';
        }
        const catData = window.componentesDb[catAtiva];
        if (!catData) return;
        document.getElementById('contador-comp-atual').textContent = `${catData.items.length}/${catData.limit}`;
        paineisCategoria[abaCompAtual].innerHTML = '';
        catData.items.forEach(comp => {
            const isAmeaca = catAtiva === 'AMEAÇAS' || catAtiva === 'MONSTROS';
            paineisCategoria[abaCompAtual].insertAdjacentHTML('beforeend', `
                <div class="item-comp">
                    <h3 class="titulo-comp">${comp.nome}</h3>
                    <div class="item-comp-info">
                        <div class="item-comp-col"><span class="lbl">${catData.labels[0]}</span><span class="val">${comp.val1}</span></div>
                        <div class="item-comp-col"><span class="lbl">${catData.labels[1]}</span><span class="val">${comp.val2}</span></div>
                    </div>
                    <button type="button" class="btn-pilula btn-editar-comp" data-id="${comp.id}">EDITAR</button>
                </div>
            `);
        });
    };
    window.atualizarUIComponentes = atualizarUIComponentes;

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
        if (catAtiva === 'AMEAÇAS' || catAtiva === 'MONSTROS') {
            resetarModalMonstro();
            document.getElementById('modal-criar-monstro').classList.add('ativo');
        } else {
            window.compEditandoID = null;
            document.getElementById('lbl-val1').textContent = window.componentesDb[catAtiva].labels[0];
            document.getElementById('lbl-val2').textContent = window.componentesDb[catAtiva].labels[1];
            document.getElementById('btn-excluir-modal').style.display = 'none';
            
            configurarModalDinamico();
            document.getElementById('modal-comp').classList.add('ativo');
        }
    });

    document.getElementById('btn-fechar-modal').addEventListener('click', () => document.getElementById('modal-comp').classList.remove('ativo'));

    document.getElementById('btn-salvar-modal').addEventListener('click', () => {
        const nome = document.getElementById('modal-input-nome').value.trim();
        if (!nome) return;

        const val1 = document.getElementById('modal-input-val1').value;
        const val2 = document.getElementById('modal-input-val2').value;
        let val3 = '';

        if (catAtiva === 'PERÍCIAS') {
            val3 = document.getElementById('modal-select-val3').value;
        }

        if (window.compEditandoID) {
            // Modo edição: atualiza o item existente
            const item = window.componentesDb[catAtiva].items.find(c => c.id == window.compEditandoID);
            if (item) { 
                item.nome = nome; item.val1 = val1; item.val2 = val2; 
                if (catAtiva === 'PERÍCIAS') item.val3 = val3;
            }
            window.compEditandoID = null;
        } else {
            // Modo criação: adiciona novo item
            const novoItem = { id: gerarIDComp(), nome, val1, val2 };
            if (catAtiva === 'PERÍCIAS') novoItem.val3 = val3;
            window.componentesDb[catAtiva].items.push(novoItem);
        }

        document.getElementById('modal-comp').classList.remove('ativo');
        document.getElementById('modal-input-nome').value = '';
        document.getElementById('modal-input-val1').value = '';
        document.getElementById('modal-input-val2').value = '';
        atualizarUIComponentes();
    });

    trackComp.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-editar-comp')) {
            const id = e.target.dataset.id;
            const catData = window.componentesDb[catAtiva];
            const comp = catData.items.find(c => c.id == id);
            
            if (catAtiva === 'AMEAÇAS' || catAtiva === 'MONSTROS') {
                abrirModalEdicaoMonstro(comp);
                return;
            }

            window.compEditandoID = id;
            document.getElementById('lbl-val1').textContent = catData.labels[0];
            document.getElementById('lbl-val2').textContent = catData.labels[1];

            document.getElementById('modal-input-nome').value = comp.nome;
            document.getElementById('modal-input-val1').value = comp.val1 || '';
            document.getElementById('modal-input-val2').value = comp.val2 || '';

            configurarModalDinamico(comp);

            const btnExcluir = document.getElementById('btn-excluir-modal');
            btnExcluir.style.display = catData.items.length <= 1 ? 'none' : 'block';

            document.getElementById('modal-comp').classList.add('ativo');
        }
    });

    document.getElementById('btn-excluir-modal').addEventListener('click', () => {
        if (confirm("Tem certeza que deseja excluir?")) {
            window.componentesDb[catAtiva].items = window.componentesDb[catAtiva].items.filter(c => c.id != window.compEditandoID);
            document.getElementById('modal-comp').classList.remove('ativo');
            atualizarUIComponentes();
        }
    });

    atualizarUIComponentes();
});

function fecharModal(id) { document.getElementById(id).classList.remove('ativo'); }

function resetarModalMonstro() {
    const fotoInput = document.getElementById('m-foto');
    if (fotoInput) fotoInput.value = '';
    document.getElementById('m-id-local').value = '';
    document.getElementById('m-imagem-atual').value = '';
    document.getElementById('m-nome').value = '';
    document.getElementById('m-tipo').value = 'Ameaça';
    document.getElementById('m-vd').value = 0;
    document.getElementById('m-vd-status').value = 0;
    document.getElementById('m-vida').value = 0;
    document.getElementById('m-defesa').value = 0;
    document.getElementById('m-xp').value = 0;
    document.getElementById('m-desc').value = '';

    const preview = document.getElementById('preview-monstro-container');
    if (preview) {
        preview.innerHTML = `
            <img id="m-foto-preview" src="../img/uploads/perfil/avatar1.png" style="width: 100%; height: 100%; object-fit: cover;">
            <div style="position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.6); color: #fff; font-size: 0.55rem; text-align: center; padding: 3px 0; font-weight: bold; text-transform: uppercase;">Mudar</div>
        `;
    }

    const headerGrad = document.getElementById('m-header-gradient');
    if (headerGrad) {
        headerGrad.style.backgroundImage = "linear-gradient(135deg, rgba(30, 11, 58, 0.95), rgba(49, 28, 97, 0.9)), url('../img/uploads/perfil/avatar1.png')";
    }

    const btnSave = document.getElementById('btn-save-monstro-local');
    if (btnSave) btnSave.innerHTML = '<i class="fas fa-skull"></i> CONVOCAR AMEAÇA';

    const btnExcluir = document.getElementById('btn-excluir-monstro');
    if (btnExcluir) btnExcluir.style.display = 'none';

    // Dinamismo VD/VT e Vida
    const nomeSis = document.getElementById('input-nome-sistema').value.toLowerCase();
    const isOrdem = nomeSis.includes('ordem paranormal');
    const labelVdVt = document.getElementById('label-vd-vt');
    if (labelVdVt) labelVdVt.textContent = isOrdem ? 'VD:' : 'VT:';

    const boxVida = document.getElementById('box-m-vida');
    const boxVd = document.getElementById('box-m-vd');
    const gridStatus = document.getElementById('m-status-grid');
    if (boxVida && boxVd && gridStatus) {
        gridStatus.style.gridTemplateColumns = 'repeat(2, 1fr)';
        if (isOrdem) {
            boxVida.style.display = 'flex';
            boxVd.style.display = 'none';
        } else {
            boxVida.style.display = 'none';
            boxVd.style.display = 'flex';
        }
    }

    // Renderiza atributos dinamicamente
    const grid = document.getElementById('grid-atributos-monstro');
    grid.innerHTML = '';
    window.atributosObj.forEach(at => {
        grid.insertAdjacentHTML('beforeend', `
            <div class="premium-attr-box" style="height: 50px; position: relative; display: flex; align-items: stretch; border: 1.5px solid #444; border-radius: 8px; overflow: hidden;">
                <span class="attr-abbr" style="font-size: 0.85rem; width: 45px; background: #fff; color: #1e0b3a; display: flex; align-items: center; justify-content: center; font-weight: 900;">${at.abrev}</span>
                <input type="number" class="input-premium-field attr-input-premium" data-id="${at.id}" data-abrev="${at.abrev}" value="0" min="0" oninput="if(parseInt(this.value)<0)this.value=0;" style="background: transparent; border: none; color: #fff; font-size: 1.2rem; font-weight: bold; flex: 1; text-align: center; outline: none; padding: 0; margin: 0; height: 100%;">
            </div>
        `);
    });
}
window.resetarModalMonstro = resetarModalMonstro;

function previewImagemMonstro(input) {
    const preview = document.getElementById('preview-monstro-container');
    const headerGrad = document.getElementById('m-header-gradient');
    if (input.files && input.files[0] && preview) {
        const file = input.files[0];
        if (typeof abrirCropperModal === 'function') {
            abrirCropperModal(file, 1, (croppedBlob, croppedBase64) => {
                preview.innerHTML = `
                    <img id="m-foto-preview" src="${croppedBase64}" style="width: 100%; height: 100%; object-fit: cover;">
                    <div style="position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.6); color: #fff; font-size: 0.55rem; text-align: center; padding: 3px 0; font-weight: bold; text-transform: uppercase;">Mudar</div>
                `;
                if (headerGrad) {
                    headerGrad.style.backgroundImage = `linear-gradient(135deg, rgba(30, 11, 58, 0.95), rgba(49, 28, 97, 0.9)), url('${croppedBase64}')`;
                }
                
                // Injetar o arquivo recortado de volta no input para consistência
                const newFile = new File([croppedBlob], input.files[0].name || "ameaca.jpg", { type: "image/jpeg" });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(newFile);
                input.files = dataTransfer.files;
            });
        } else {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `
                    <img id="m-foto-preview" src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
                    <div style="position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.6); color: #fff; font-size: 0.55rem; text-align: center; padding: 3px 0; font-weight: bold; text-transform: uppercase;">Mudar</div>
                `;
                if (headerGrad) {
                    headerGrad.style.backgroundImage = `linear-gradient(135deg, rgba(30, 11, 58, 0.95), rgba(49, 28, 97, 0.9)), url('${e.target.result}')`;
                }
            };
            reader.readAsDataURL(file);
        }
    }
}
window.previewImagemMonstro = previewImagemMonstro;

function abrirModalEdicaoMonstro(comp) {
    const fotoInput = document.getElementById('m-foto');
    if (fotoInput) fotoInput.value = '';
    document.getElementById('m-id-local').value = comp.id;
    document.getElementById('m-nome').value = comp.nome;
    document.getElementById('m-tipo').value = comp.val1 || 'Ameaça';
    document.getElementById('m-vd').value = comp.val2 || 0;
    document.getElementById('m-vd-status').value = comp.val2 || 0;
    document.getElementById('m-vida').value = comp.vida || 0;
    document.getElementById('m-defesa').value = comp.defesa || 0;
    document.getElementById('m-xp').value = comp.xp || 0;
    document.getElementById('m-desc').value = comp.desc || '';
    document.getElementById('m-imagem-atual').value = comp.foto_base64 || '';
    
    const imgUrl = (comp.foto_base64 && comp.foto_base64 !== '../img/logo_icone.png' && comp.foto_base64 !== '../img/uploads/perfil/avatar1.png') ? comp.foto_base64 : '../img/uploads/perfil/avatar1.png';
    
    const preview = document.getElementById('preview-monstro-container');
    if (preview) {
        preview.innerHTML = `
            <img id="m-foto-preview" src="${imgUrl}" style="width: 100%; height: 100%; object-fit: cover;">
            <div style="position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.6); color: #fff; font-size: 0.55rem; text-align: center; padding: 3px 0; font-weight: bold; text-transform: uppercase;">Mudar</div>
        `;
    }
    const headerGrad = document.getElementById('m-header-gradient');
    if (headerGrad) {
        headerGrad.style.backgroundImage = `linear-gradient(135deg, rgba(30, 11, 58, 0.95), rgba(49, 28, 97, 0.9)), url('${imgUrl}')`;
    }

    const btnSave = document.getElementById('btn-save-monstro-local');
    if (btnSave) btnSave.innerHTML = '<i class="fas fa-skull"></i> ATUALIZAR AMEAÇA';

    const btnExcluir = document.getElementById('btn-excluir-monstro');
    if (btnExcluir) {
        btnExcluir.style.display = 'block';
        btnExcluir.onclick = () => {
            if (confirm("Tem certeza que deseja excluir esta ameaça?")) {
                window.componentesDb['AMEAÇAS'].items = window.componentesDb['AMEAÇAS'].items.filter(c => c.id !== comp.id);
                fecharModal('modal-criar-monstro');
                if (typeof window.atualizarUIComponentes === 'function') {
                    window.atualizarUIComponentes();
                } else if (typeof atualizarUIComponentes === 'function') {
                    atualizarUIComponentes();
                }
            }
        };
    }

    // Dinamismo VD/VT e Vida
    const nomeSis = document.getElementById('input-nome-sistema').value.toLowerCase();
    const isOrdem = nomeSis.includes('ordem paranormal');
    const labelVdVt = document.getElementById('label-vd-vt');
    if (labelVdVt) labelVdVt.textContent = isOrdem ? 'VD:' : 'VT:';

    const boxVida = document.getElementById('box-m-vida');
    const boxVd = document.getElementById('box-m-vd');
    const gridStatus = document.getElementById('m-status-grid');
    if (boxVida && boxVd && gridStatus) {
        gridStatus.style.gridTemplateColumns = 'repeat(2, 1fr)';
        if (isOrdem) {
            boxVida.style.display = 'flex';
            boxVd.style.display = 'none';
        } else {
            boxVida.style.display = 'none';
            boxVd.style.display = 'flex';
        }
    }
    
    const grid = document.getElementById('grid-atributos-monstro');
    grid.innerHTML = '';
    window.atributosObj.forEach(at => {
        let valAttr = 0;
        if (comp.atributos_monstro) {
            const attrMatch = comp.atributos_monstro.find(a => a.abrev === at.abrev || a.id_atributo_temp === at.id || a.id_atributo === at.id);
            if (attrMatch) valAttr = Math.max(0, parseInt(attrMatch.valor || attrMatch.qt_valor) || 0);
        }

        grid.insertAdjacentHTML('beforeend', `
            <div class="premium-attr-box" style="height: 50px; position: relative; display: flex; align-items: stretch; border: 1.5px solid #444; border-radius: 8px; overflow: hidden;">
                <span class="attr-abbr" style="font-size: 0.85rem; width: 45px; background: #fff; color: #1e0b3a; display: flex; align-items: center; justify-content: center; font-weight: 900;">${at.abrev}</span>
                <input type="number" class="input-premium-field attr-input-premium" data-id="${at.id}" data-abrev="${at.abrev}" value="${valAttr}" min="0" oninput="if(parseInt(this.value)<0)this.value=0;" style="background: transparent; border: none; color: #fff; font-size: 1.2rem; font-weight: bold; flex: 1; text-align: center; outline: none; padding: 0; margin: 0; height: 100%;">
            </div>
        `);
    });

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
        alert('Dê um nome à ameaça!');
        return;
    }

    const atributos = [];
    document.querySelectorAll('.attr-input-premium').forEach(input => {
        atributos.push({
            id_atributo_temp: input.getAttribute('data-id'),
            abrev: input.getAttribute('data-abrev'),
            valor: Math.max(0, parseInt(input.value) || 0)
        });
    });

    const imgPreview = document.querySelector('#m-foto-preview');
    const imgPath = imgPreview ? imgPreview.src : '../img/uploads/perfil/avatar1.png';

    const ameacaData = {
        nome: nome,
        val1: tipo,
        val2: vd,
        desc: desc,
        vida: vida,
        defesa: defesa,
        xp: xp,
        atributos_monstro: atributos,
        foto_base64: imgPath
    };

    if (idLocal) {
        const idx = window.componentesDb['AMEAÇAS'].items.findIndex(c => c.id == idLocal);
        window.componentesDb['AMEAÇAS'].items[idx] = { ...window.componentesDb['AMEAÇAS'].items[idx], ...ameacaData };
    } else {
        const gerarIDComp = () => '_' + Math.random().toString(36).substr(2, 9);
        window.componentesDb['AMEAÇAS'].items.push({
            id: gerarIDComp(),
            ...ameacaData
        });
    }

    fecharModal('modal-criar-monstro');
    if (typeof window.atualizarUIComponentes === 'function') {
        window.atualizarUIComponentes();
    } else if (typeof atualizarUIComponentes === 'function') {
        atualizarUIComponentes();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const mVD = document.getElementById('m-vd');
    const mVDStatus = document.getElementById('m-vd-status');
    if (mVD && mVDStatus) {
        mVD.addEventListener('input', () => { mVDStatus.value = mVD.value; });
        mVDStatus.addEventListener('input', () => { mVD.value = mVDStatus.value; });
    }
});
