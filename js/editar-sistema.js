// js/editar-sistema.js
console.log('TABLE | editar-sistema.js carregado v1.4');
document.addEventListener('DOMContentLoaded', () => {

    // 1. Verificação de Mestre (Simulação)
    const sessao = JSON.parse(localStorage.getItem('table_sessao_ativa'));
    if (!sessao || (sessao.cargo !== 'mestre' && sessao.cargo !== 'admin')) {
        // window.location.href = 'perfil.php'; 
    }

    // =======================================================
    // FORÇAR MAIÚSCULAS NOS INPUTS
    // =======================================================
    const forcarMaiuscula = (e) => {
        e.target.value = e.target.value.toUpperCase();
    };
    document.getElementById('input-nome-atributo').addEventListener('input', forcarMaiuscula);
    document.getElementById('input-abrev-atributo').addEventListener('input', forcarMaiuscula);
    document.getElementById('input-nome-status').addEventListener('input', forcarMaiuscula);
    document.getElementById('modal-input-nome').addEventListener('input', forcarMaiuscula);


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
        
        const btnIdade = document.querySelector(`.btn-idade[data-idade="${SYSTEM_DB.tp_classificacao}"]`);
        if (btnIdade) {
            document.querySelectorAll('.btn-idade').forEach(b => b.classList.remove('ativo'));
            btnIdade.classList.add('ativo');
        }

        if (SYSTEM_DB.ds_descricao) {
            const descricoes = SYSTEM_DB.ds_descricao.split('\n\n');
            const firstTextArea = document.querySelector('#desc-fixa-1 textarea');
            if (firstTextArea) firstTextArea.value = descricoes[0];

            for (let i = 1; i < descricoes.length; i++) {
                const idUnico = Date.now() + i;
                const html = `
                    <div class="item-descricao" id="desc-${idUnico}">
                        <div class="cabecalho-descricao" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <input type="text" class="input-titulo-desc" value="Descrição ${i+1}:" style="width: 50%;">
                            <button type="button" class="btn-texto btn-excluir-desc-inline" data-id="desc-${idUnico}">Excluir <i class="fas fa-times"></i></button>
                        </div>
                        <textarea class="input-escuro textarea-escuro" required placeholder="Digite os detalhes aqui...">${descricoes[i]}</textarea>
                    </div>
                `;
                document.getElementById('container-descricoes').insertAdjacentHTML('beforeend', html);
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
            id: s.id_status,
            nome: s.nm_status,
            cor: s.ds_cor,
            base: 'null'
        }));
        defesasObj = STATUS_DB.filter(s => s.tp_status === 'defesa').map(s => ({
            id: s.id_status,
            nome: s.nm_status,
            cor: s.ds_cor,
            base: 'null'
        }));
    } else {
        statusObj = [{ id: gerarID(), nome: 'VIDA', cor: '#ed1c24', base: 'null' }];
        defesasObj = [{ id: gerarID(), nome: 'DEFESA', cor: '#0f75bc', base: 'null' }];
    }

    if (typeof ATRIBS_DB !== 'undefined' && ATRIBS_DB.length > 0) {
        window.atributosObj = ATRIBS_DB.map(a => ({ id: a.id_atributo, nome: a.nm_atributo, abrev: a.ds_abreviacao, valor: a.qt_valor_minimo || '0' }));
    } else {
        window.atributosObj = [{ id: gerarID(), nome: 'FORÇA', abrev: 'FOR', valor: '0' }];
    }

    if (typeof CLASSES_DB !== 'undefined') window.componentesDb['CLASSES'].items = CLASSES_DB.map(c => ({ id: c.id_classe, nome: c.nm_classe, val1: c.ds_descricao, val2: '' }));
    if (typeof PERICIAS_DB !== 'undefined') window.componentesDb['PERÍCIAS'].items = PERICIAS_DB.map(p => ({ id: p.id_pericia, nome: p.nm_pericia, val1: p.ds_atributo_base, val2: '' }));
    if (typeof ORIGENS_DB !== 'undefined') window.componentesDb['ORIGENS'].items = ORIGENS_DB.map(o => ({ id: o.id_origem, nome: o.nm_origem, val1: o.ds_origem, val2: '' }));
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

    // =======================================================
    // SUBMISSÃO
    // =======================================================
    document.getElementById('form-criar-sistema').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.querySelector('.btn-concluir');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        
        let descTotal = '';
        document.querySelectorAll('.item-descricao textarea').forEach((t, index) => {
             if(t.value.trim() !== '') descTotal += (index > 0 ? '\n\n' : '') + t.value.trim();
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
                const reader = new FileReader();
                reader.onload = (event) => {
                    previewImagem.style.backgroundImage = `url(${event.target.result})`;
                    silhuetas.forEach(s => s.style.display = 'none');
                    imagemBase64 = event.target.result; 
                };
                reader.readAsDataURL(file);
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
        const count = containerDescricoes.querySelectorAll('.item-descricao').length + 1;
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
        statusObj.push({ id: gerarID(), nome: 'NOVO', cor: '#888888', base: 'null' });
        renderStatusEDefesas();
    });

    document.getElementById('btn-add-defesa-vazio').addEventListener('click', () => {
        if (defesasObj.length >= 3) return alert("Máximo atingido.");
        defesasObj.push({ id: gerarID(), nome: 'NOVO', cor: '#888888', base: 'null' });
        renderStatusEDefesas();
    });

    document.getElementById('btn-salvar-status').addEventListener('click', () => {
        if (!statusEditandoID) return alert("Selecione um status ou defesa para editar primeiro.");
        let lista = editandoTipo === 'status' ? statusObj : defesasObj;
        const stat = lista.find(s => s.id === statusEditandoID);
        stat.nome = document.getElementById('input-nome-status').value.toUpperCase();
        stat.cor = document.getElementById('input-cor-status').value;
        stat.base = document.getElementById('input-base-status').value;
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
            if (isStatus) statusObj = statusObj.filter(s => s.id !== id);
            else defesasObj = defesasObj.filter(d => d.id !== id);
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

    document.getElementById('btn-add-atributo-vazio').addEventListener('click', () => {
        if (window.atributosObj.length >= 8) return alert("Máximo atingido.");
        window.atributosObj.push({ id: gerarID(), nome: 'NOVO', abrev: 'NOV', valor: '0' });
        renderAtributos();
    });

    document.getElementById('lista-atributos').addEventListener('click', (e) => {
        const id = e.target.dataset.id;
        if (e.target.classList.contains('btn-deletar-attr')) {
            window.atributosObj = window.atributosObj.filter(a => a.id !== id);
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
        if (!window.atributoEditandoID) return;
        const attr = window.atributosObj.find(a => a.id == window.atributoEditandoID);
        attr.nome = document.getElementById('input-nome-atributo').value.toUpperCase();
        attr.abrev = document.getElementById('input-abrev-atributo').value.toUpperCase();
        attr.valor = document.getElementById('input-valor-atributo').value;
        renderAtributos();
        window.atributoEditandoID = null;
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
            catAtiva = btn.textContent.trim();
            trackComp.style.transform = `translateX(-${abaCompAtual * (100 / 6)}%)`;
            atualizarUIComponentes();
        });
    });

    const atualizarUIComponentes = () => {
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

    document.getElementById('btn-criar-comp').addEventListener('click', () => {
        if (catAtiva === 'AMEAÇAS' || catAtiva === 'MONSTROS') {
            document.getElementById('m-id-local').value = '';
            document.getElementById('m-nome').value = '';
            document.getElementById('m-tipo').value = 'Criatura';
            document.getElementById('m-vd').value = 0;
            document.getElementById('m-vida').value = 0;
            document.getElementById('m-defesa').value = 0;
            document.getElementById('m-xp').value = 0;
            document.getElementById('m-desc').value = '';
            document.getElementById('preview-monstro-container').innerHTML = '<i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: var(--premium-accent); opacity: 0.5;"></i>';
            document.querySelector('#modal-criar-monstro h2').textContent = 'Nova Ameaça';
            document.getElementById('btn-save-monstro-local').innerHTML = '<i class="fas fa-skull"></i> CONVOCAR AMEAÇA';

            const grid = document.getElementById('grid-atributos-monstro');
            grid.innerHTML = '';
            window.atributosObj.forEach(at => {
                grid.insertAdjacentHTML('beforeend', `
                    <div class="input-premium-group" style="margin-bottom: 0; display: flex; flex-direction: column;">
                        <label class="input-premium-label" style="text-align: center; margin: 0 0 5px 0; font-size: 0.6rem; color: #888; font-weight: 800;">${at.abrev}</label>
                        <input type="number" class="input-premium-field attr-input-premium" data-id="${at.id}" data-abrev="${at.abrev}" value="0">
                    </div>
                `);
            });
            document.getElementById('modal-criar-monstro').classList.add('ativo');
        } else {
            window.compEditandoID = null;
            document.getElementById('modal-comp-titulo').textContent = `Novo ${catAtiva}`;
            document.getElementById('modal-input-nome').value = '';
            document.getElementById('modal-input-val1').value = '';
            document.getElementById('modal-input-val2').value = '';
            document.getElementById('btn-excluir-modal').style.display = 'none';
            document.getElementById('lbl-val1').textContent = window.componentesDb[catAtiva].labels[0];
            document.getElementById('lbl-val2').textContent = window.componentesDb[catAtiva].labels[1];
            document.getElementById('modal-comp').classList.add('ativo');
        }
    });

    document.getElementById('btn-fechar-modal').addEventListener('click', () => document.getElementById('modal-comp').classList.remove('ativo'));

    document.getElementById('btn-salvar-modal').addEventListener('click', () => {
        const nome = document.getElementById('modal-input-nome').value.toUpperCase();
        if (!nome) return;

        const val1 = document.getElementById('modal-input-val1').value;
        const val2 = document.getElementById('modal-input-val2').value;

        if (window.compEditandoID) {
            // Modo edição: atualiza o item existente
            const item = window.componentesDb[catAtiva].items.find(c => c.id == window.compEditandoID);
            if (item) { item.nome = nome; item.val1 = val1; item.val2 = val2; }
            window.compEditandoID = null;
        } else {
            // Modo criação: adiciona novo item
            window.componentesDb[catAtiva].items.push({ id: gerarIDComp(), nome, val1, val2 });
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
            document.getElementById('modal-comp-titulo').textContent = `Editar ${catAtiva}`;
            document.getElementById('lbl-val1').textContent = catData.labels[0];
            document.getElementById('lbl-val2').textContent = catData.labels[1];

            document.getElementById('modal-input-nome').value = comp.nome;
            document.getElementById('modal-input-val1').value = comp.val1 || '';
            document.getElementById('modal-input-val2').value = comp.val2 || '';

            const btnExcluir = document.getElementById('btn-excluir-modal');
            btnExcluir.style.display = catData.items.length <= 1 ? 'none' : 'block';

            document.getElementById('modal-comp').classList.add('ativo');
        }
    });

    document.getElementById('btn-excluir-modal').addEventListener('click', () => {
        if (confirm("Tem certeza que deseja excluir?")) {
            window.componentesDb[catAtiva].items = window.componentesDb[catAtiva].items.filter(c => c.id !== window.compEditandoID);
            document.getElementById('modal-comp').classList.remove('ativo');
            atualizarUIComponentes();
        }
    });

    atualizarUIComponentes();
});

function fecharModal(id) { document.getElementById(id).classList.remove('ativo'); }
function previewImagemMonstro(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('preview-monstro-container').innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
        reader.readAsDataURL(input.files[0]);
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
        const valAttr = (comp.atributos_monstro || []).find(a => a.abrev == at.abrev)?.valor || 0;
        grid.insertAdjacentHTML('beforeend', `
            <div class="input-premium-group" style="margin-bottom: 0; display: flex; flex-direction: column;">
                <label class="input-premium-label" style="text-align: center; margin: 0 0 5px 0; font-size: 0.6rem; color: #888; font-weight: 800;">${at.abrev}</label>
                <input type="number" class="input-premium-field attr-input-premium" data-id="${at.id}" data-abrev="${at.abrev}" value="${valAttr}" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 8px; border-radius: 8px; text-align: center; font-weight: 900; font-family: 'Montserrat', sans-serif; outline: none;">
            </div>
        `);
    });

    if (comp.foto_base64) {
        document.getElementById('preview-monstro-container').innerHTML = `<img src="${comp.foto_base64}" style="width:100%; height:100%; object-fit:cover;">`;
    } else {
        document.getElementById('preview-monstro-container').innerHTML = '<i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: var(--premium-accent); opacity: 0.5;"></i>';
    }

    document.querySelector('#modal-criar-monstro h2').textContent = 'Editar Ameaça';
    document.getElementById('btn-save-monstro-local').textContent = 'ATUALIZAR';
    document.getElementById('modal-criar-monstro').classList.add('ativo');
}

function salvarMonstro() {
    const nome = document.getElementById('m-nome').value.toUpperCase();
    const idLocal = document.getElementById('m-id-local').value;
    if(!nome) return alert('Dê um nome!');
    
    const atributos = [];
    document.querySelectorAll('.attr-input-premium').forEach(input => {
        atributos.push({ abrev: input.getAttribute('data-abrev'), valor: input.value || 0 });
    });
    
    const ameacaData = {
        nome: nome,
        val1: document.getElementById('m-tipo').value || 'Criatura',
        val2: document.getElementById('m-vd').value || 0,
        desc: document.getElementById('m-desc').value || '',
        vida: document.getElementById('m-vida').value || 0,
        defesa: document.getElementById('m-defesa').value || 0,
        xp: document.getElementById('m-xp').value || 0,
        atributos_monstro: atributos,
        foto_base64: document.querySelector('#preview-monstro-container img')?.src || null
    };

    if (idLocal) {
        const idx = window.componentesDb['AMEAÇAS'].items.findIndex(c => c.id == idLocal);
        window.componentesDb['AMEAÇAS'].items[idx] = { ...window.componentesDb['AMEAÇAS'].items[idx], ...ameacaData };
    } else {
        window.componentesDb['AMEAÇAS'].items.push({
            id: '_' + Math.random().toString(36).substr(2, 9),
            ...ameacaData
        });
    }

    fecharModal('modal-criar-monstro');
    const btnAtivo = document.querySelector('.btn-comp-aba.ativa');
    if(btnAtivo && btnAtivo.textContent.trim() === 'AMEAÇAS') btnAtivo.click();
}