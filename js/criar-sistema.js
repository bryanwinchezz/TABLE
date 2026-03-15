// js/criar-sistema.js
document.addEventListener('DOMContentLoaded', () => {

    // 1. Verificação de Mestre (Simulação)
    const sessao = JSON.parse(localStorage.getItem('table_sessao_ativa'));
    if (!sessao || sessao.cargo !== 'mestre') {
        // window.location.href = 'perfil.html'; // Descomente para produção
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

    // Clique direto nas abas do menu superior
    abas.forEach(aba => {
        aba.addEventListener('click', () => ativarAba(aba));
    });

    // Inicializa
    if (abas.length > 0) ativarAba(abas[0]);

    // Botões Próximo e Voltar
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

    // Submissão do Formulário
    document.getElementById('form-criar-sistema').addEventListener('submit', (e) => {
        e.preventDefault();
        alert("Sistema salvo com sucesso!");
        // window.location.href = 'perfil.html';
    });


    // =======================================================
    // UPLOAD DE IMAGEM
    // =======================================================
    const btnTrocarFoto = document.getElementById('btn-trocar-foto');
    const inputFoto = document.getElementById('input-foto-sistema');
    const previewImagem = document.getElementById('preview-imagem');
    const silhuetas = previewImagem.querySelectorAll('div');

    btnTrocarFoto.addEventListener('click', () => inputFoto.click());

    inputFoto.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                previewImagem.style.backgroundImage = `url(${event.target.result})`;
                silhuetas.forEach(s => s.style.display = 'none');
            };
            reader.readAsDataURL(file);
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
        contadorDescricao++;
        const idUnico = Date.now();
        const html = `
            <div class="item-descricao" id="desc-${idUnico}">
                <div class="cabecalho-descricao" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <input type="text" class="input-titulo-desc" value="Descrição ${contadorDescricao}:" style="width: 50%;">
                    <button type="button" class="btn-texto btn-excluir-desc-inline" data-id="desc-${idUnico}">Excluir <i class="fas fa-times"></i></button>
                </div>
                <textarea class="input-escuro textarea-escuro" required placeholder="Digite os detalhes aqui..."></textarea>
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


    // =======================================================
    // ABA 2 e 3: ATRIBUTOS E STATUS
    // =======================================================
    const gerarID = () => '_' + Math.random().toString(36).substr(2, 9);

    let atributosObj = [
        { id: gerarID(), nome: 'FORÇA', abrev: 'FOR', valor: '0' }
    ];
    let statusObj = [
        { id: gerarID(), nome: 'VIDA', cor: '#ed1c24', base: 'null' }
    ];
    let defesasObj = [
        { id: gerarID(), nome: 'DEFESA', cor: '#0f75bc', base: 'null' }
    ];

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
        atributosObj.push({ id: gerarID(), nome: 'NOVO', abrev: 'NOV', valor: '0' });
        renderAtributos();
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
        if (!atributoEditandoID) return alert("Selecione um atributo para editar primeiro, ou clique em +.");
        const attr = atributosObj.find(a => a.id === atributoEditandoID);
        attr.nome = document.getElementById('input-nome-atributo').value.toUpperCase();
        attr.abrev = document.getElementById('input-abrev-atributo').value.toUpperCase();
        attr.valor = document.getElementById('input-valor-atributo').value;

        renderAtributos();
        resetarFormAtributo();
    });

    document.getElementById('btn-salvar-status').addEventListener('click', () => {
        if (!statusEditandoID) return alert("Selecione um status ou defesa para editar primeiro, ou clique em +.");

        let lista = editandoTipo === 'status' ? statusObj : defesasObj;
        const stat = lista.find(s => s.id === statusEditandoID);
        stat.nome = document.getElementById('input-nome-status').value.toUpperCase();
        stat.cor = document.getElementById('input-cor-status').value;
        stat.base = document.getElementById('input-base-status').value;

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


    // =======================================================
    // ABA 4: COMPONENTES (Carrossel e Modal)
    // =======================================================
    const gerarIDComp = () => '_' + Math.random().toString(36).substr(2, 9);

    let componentesDb = {
        'CLASSES': {
            limit: 15, labels: ['DESCRIÇÃO', 'HABILIDADES'], items: [
                { id: gerarIDComp(), nome: 'COMBATENTE', val1: 'Treinado desde cedo...', val2: 'Ataque uma vez por cena...' }
            ]
        },
        'PERÍCIAS': { limit: 30, labels: ['DESCRIÇÃO', 'HABILIDADES'], items: [] },
        'ORIGENS': { limit: 75, labels: ['DESCRIÇÃO', 'HABILIDADES'], items: [] },
        'EQUIPAMENTOS': { limit: 100, labels: ['DESCRIÇÃO', 'CATEGORIA'], items: [] },
        'PODERES': { limit: 50, labels: ['DESCRIÇÃO', 'DURAÇÃO'], items: [] },
        'MONSTROS': { limit: 50, labels: ['DESCRIÇÃO', 'INICIATIVA'], items: [] }
    };

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

            trackComp.style.transform = `translateX(-${abaCompAtual * (100 / 6)}%)`;
            atualizarUIComponentes();
        });
    });

    const atualizarUIComponentes = () => {
        const catData = componentesDb[catAtiva];
        contadorCompEl.textContent = `${catData.items.length}/${catData.limit}`;
        paineisCategoria[abaCompAtual].innerHTML = '';

        catData.items.forEach(comp => {
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
                    <button type="button" class="btn-pilula btn-editar-comp" data-id="${comp.id}">EDITAR</button>
                </div>
            `);
        });
    };

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

    document.getElementById('btn-criar-comp').addEventListener('click', () => {
        const catData = componentesDb[catAtiva];
        if (catData.items.length >= catData.limit) return alert(`Limite atingido!`);

        compEditandoID = null;
        document.getElementById('modal-comp-titulo').textContent = `Criar Nova`;
        document.getElementById('lbl-val1').textContent = catData.labels[0];
        document.getElementById('lbl-val2').textContent = catData.labels[1];
        btnExcluirModal.style.display = 'none';
        abrirModalComp();
    });

    document.getElementById('btn-salvar-modal').addEventListener('click', () => {
        const nome = document.getElementById('modal-input-nome').value.toUpperCase();
        const val1 = document.getElementById('modal-input-val1').value;
        const val2 = document.getElementById('modal-input-val2').value;

        if (!nome) return alert("O Nome é obrigatório!");

        if (compEditandoID) {
            const item = componentesDb[catAtiva].items.find(c => c.id === compEditandoID);
            item.nome = nome; item.val1 = val1; item.val2 = val2;
        } else {
            componentesDb[catAtiva].items.push({ id: gerarIDComp(), nome, val1, val2 });
        }

        fecharModalComp();
        atualizarUIComponentes();
    });

    trackComp.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-editar-comp')) {
            const id = e.target.dataset.id;
            const catData = componentesDb[catAtiva];
            const comp = catData.items.find(c => c.id === id);

            compEditandoID = id;
            document.getElementById('modal-comp-titulo').textContent = `Editar ${catAtiva}`;
            document.getElementById('lbl-val1').textContent = catData.labels[0];
            document.getElementById('lbl-val2').textContent = catData.labels[1];

            document.getElementById('modal-input-nome').value = comp.nome;
            document.getElementById('modal-input-val1').value = comp.val1;
            document.getElementById('modal-input-val2').value = comp.val2;

            if (catAtiva === 'CLASSES' && catData.items.length <= 1) {
                btnExcluirModal.style.display = 'none';
            } else {
                btnExcluirModal.style.display = 'block';
            }

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

    atualizarUIComponentes();
});