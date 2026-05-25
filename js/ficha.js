// js/ficha.js

const NOMES_TIPOS = {
    habilidades: { singular: 'habilidade', plural: 'habilidades', artigoPlural: 'minhas Habilidades', titulo: 'Habilidades' },
    inventario: { singular: 'item', plural: 'itens', artigoPlural: 'meu Inventário', titulo: 'Inventário' },
    poderes: { singular: 'poder', plural: 'poderes', artigoPlural: 'meus Poderes', titulo: 'Poderes' },
    descricao: { singular: 'descrição', plural: 'descrições', artigoPlural: 'minha Descrição', titulo: 'Descrição' }
};

const RENDER_DESCRICAO_LABELS = {
    aparencia: 'Aparência',
    personalidade: 'Personalidade',
    historia: 'História',
    objetivos: 'Objetivos'
};

document.addEventListener('DOMContentLoaded', () => {
    inicializarBarras();
    inicializarAtributos();
    inicializarAvatar();
    inicializarFichaEditavel();
});

// --- STATUS BARS (Dinâmicas por Sistema) ---
function inicializarBarras() {
    document.querySelectorAll('.bar-unit').forEach(unit => {
        const campo = unit.dataset.campo;
        const idStatus = unit.dataset.idStatus;
        const barFill = unit.querySelector('.bar-fill');
        const spanAtual = unit.querySelector('.val-atual');
        const spanMax = unit.querySelector('.val-max');

        const atualizarUI = (salvar = true) => {
            // Captura os valores brutos e limpa
            let rawAtual = spanAtual.textContent.trim();
            let rawMax = spanMax.textContent.trim();
            
            let valAtual = rawAtual.replace(/\D/g, '');
            let valMax = rawMax.replace(/\D/g, '');
            
            let atual = parseInt(valAtual) || 0;
            let max = parseInt(valMax) || 1;
            
            if (max < 1) max = 1;
            if (atual < 0) atual = 0;

            // Visualmente, a barra não passa de 100%, mas o texto pode ser maior (ex: 18/10)
            const percent = (atual / max) * 100;
            barFill.style.width = Math.min(100, Math.max(0, percent)) + '%';
            
            if (salvar) {
                // Sincroniza o texto para garantir que está limpo, mas mantém o valor digitado
                if (spanAtual.textContent !== valAtual) spanAtual.textContent = valAtual;
                if (spanMax.textContent !== valMax) spanMax.textContent = valMax;

                const tipoSave = isNaN(idStatus) ? 'stat' : 'status_custom';
                salvarDados(tipoSave, idStatus || campo, atual);
                
                const tipoSaveMax = isNaN(idStatus) ? 'stat_max' : 'status_custom_max';
                salvarDados(tipoSaveMax, idStatus || campo, max);
            }
        };

        const bloquearNaoNumeros = (e) => {
            const keysPermitidas = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', 'Escape'];
            if (!/[0-9]/.test(e.key) && !keysPermitidas.includes(e.key)) {
                e.preventDefault();
            }
        };

        const setupEvents = (el) => {
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    el.blur();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    el.blur();
                } else {
                    bloquearNaoNumeros(e);
                }
            });

            el.addEventListener('blur', () => atualizarUI(true));
            el.addEventListener('input', () => atualizarUI(false));
            
            // Prevenir que colem texto formatado ou não numérico
            el.addEventListener('paste', (e) => {
                e.preventDefault();
                const text = (e.originalEvent || e).clipboardData.getData('text/plain').replace(/\D/g, '');
                document.execCommand('insertText', false, text);
            });
        };

        setupEvents(spanAtual);
        setupEvents(spanMax);

        // Botões de clique
        unit.querySelectorAll('.step-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                let atual = parseInt(spanAtual.textContent) || 0;
                const step = parseInt(btn.dataset.step);
                spanAtual.textContent = atual + step;
                atualizarUI(true);
            });
        });
    });
}

// --- ATRIBUTOS (FOR, INT, AGI, etc) ---
function inicializarAtributos() {
    document.querySelectorAll('.premium-attr-box').forEach(box => {
        const circle = box.querySelector('.attr-circle');
        const attrName = box.dataset.attr;

        const bloquearNaoNumeros = (e) => {
            if (!/[0-9]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'ArrowLeft' && e.key !== 'ArrowRight' && e.key !== 'Tab' && e.key !== 'Enter') {
                e.preventDefault();
            }
        };
        
        circle.onkeydown = (e) => { 
            if (e.key === 'Enter') { e.preventDefault(); circle.blur(); }
            else bloquearNaoNumeros(e);
        };

        circle.onblur = () => {
            const val = parseInt(circle.textContent.replace(/\D/g, '')) || 0;
            circle.textContent = val;
            salvarDados('atributo', attrName, val);
            recalcularValoresFicha();
        };
    });

    // --- DEFESA / ESCUDOS ---
    document.querySelectorAll('.defesa-shield-box .shield-number').forEach(shield => {
        const idStatus = shield.closest('.defesa-shield-box').dataset.idStatus;

        const bloquearNaoNumeros = (e) => {
            if (!/[0-9]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'ArrowLeft' && e.key !== 'ArrowRight' && e.key !== 'Tab' && e.key !== 'Enter') {
                e.preventDefault();
            }
        };

        shield.onkeydown = (e) => { 
            if (e.key === 'Enter') { e.preventDefault(); shield.blur(); }
            else bloquearNaoNumeros(e);
        };
        
        shield.onblur = () => {
            const val = parseInt(shield.textContent.replace(/\D/g, '')) || 0;
            shield.textContent = val;
            
            if (idStatus === 'defesa') {
                salvarDados('defesa', 'defesa', val);
            } else {
                salvarDados('status_custom', idStatus, val);
            }
            recalcularValoresFicha();
        };
    });
}

// --- AVATAR UPLOAD ---
function inicializarAvatar() {
    const input = document.getElementById('input-avatar');
    const img = document.getElementById('img-personagem');

    input.onchange = async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('avatar', file);
        formData.append('id_personagem', ID_PERSONAGEM);

        try {
            const resp = await fetch('../app/ajax/upload-avatar.php', {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();
            if (data.success) {
                img.src = data.path + '?v=' + Date.now();
                // Opcional: feedback visual de sucesso
            } else {
                alert('Erro: ' + data.error);
            }
        } catch (err) {
            console.error('Erro no upload:', err);
        }
    };
}

// --- PERSISTÊNCIA (AJAX) ---
async function salvarDados(tipo, campo, valor) {
    try {
        await fetch('../app/ajax/atualizar-ficha.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_personagem: ID_PERSONAGEM,
                tipo: tipo,
                campo: campo,
                valor: valor
            })
        });
    } catch (err) {
        console.error('Erro ao salvar:', err);
    }
}

// --- MODAIS ---
function abrirModal(tipo) {
    const overlay = document.getElementById('modal-overlay');
    const titulo = document.getElementById('modal-titulo');
    const body = document.getElementById('modal-body');

    const content = overlay.querySelector('.modal-content');

    let displayTipo = NOMES_TIPOS[tipo]?.titulo || tipo;
    titulo.textContent = displayTipo;
    
    // Ajustar largura do modal conforme o tipo
    if (tipo === 'descricao') {
        content.style.maxWidth = '1000px';
    } else {
        content.style.maxWidth = '700px';
    }

    let html = '';

    if (tipo === 'descricao') {
        const d = DADOS_DESCRICAO;
        html = `
            <div class="modal-descricao-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                ${['aparencia', 'personalidade', 'historia', 'objetivos'].map(campo => `
                    <div class="grupo-form" id="wrapper-${campo}">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <label style="font-weight:700; color:var(--premium-accent); text-transform:uppercase; font-size:0.8rem;">${RENDER_DESCRICAO_LABELS[campo] || campo}</label>
                            <button onclick="toggleExpandCampo('${campo}')" style="background:none; border:none; color:#666; cursor:pointer; font-size:0.8rem;"><i class="fas fa-expand-alt"></i></button>
                        </div>
                        <textarea class="desc-edit" data-campo="${campo}" style="width:100%; height:120px; background:rgba(0,0,0,0.3); color:#fff; border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:15px; resize:none; overflow-y:auto; transition:0.3s;">${d[campo]}</textarea>
                    </div>
                `).join('')}
            </div>
            <div style="margin-top:20px; text-align:right; font-size:0.8rem; color:#666;">* Alterações salvas automaticamente.</div>
        `;
        body.innerHTML = html;
        body.querySelectorAll('.desc-edit').forEach(tx => {
            tx.onblur = () => {
                const val = tx.value;
                const campo = tx.dataset.campo;
                DADOS_DESCRICAO[campo] = val;
                salvarDados('descricao', campo, val);
            };
        });
    } else {
        // Habilidades, Poderes ou Inventário
        let lista = [];
        if (tipo === 'habilidades') lista = DADOS_HABILIDADES;
        if (tipo === 'poderes') lista = DADOS_PODERES;
        if (tipo === 'inventario') lista = DADOS_INVENTARIO;

        const isOrdem = typeof SISTEMA_NOME !== 'undefined' && SISTEMA_NOME.toLowerCase().includes('ordem paranormal');
        const glowStyle = isOrdem ? 'box-shadow: 0 0 15px rgba(255, 50, 50, 0.85);' : '';

        html = `
            <div class="modal-search-area" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; background:rgba(255,255,255,0.03); padding:15px; border-radius:12px;">
                <input type="text" placeholder="Filtrar ${NOMES_TIPOS[tipo]?.artigoPlural || 'meus itens'}..." style="flex:1; background:none; border:none; color:#fff; outline:none;" oninput="filtrarListaModal(this.value, '${tipo}')">
                <button class="btn-add-modal" onclick="abrirCatalogo('${tipo}')" style="background:var(--premium-accent); border:none; color:#fff; padding:8px 20px; border-radius:8px; font-weight:800; cursor:pointer; font-size:0.8rem; letter-spacing:1px; ${glowStyle}">ADICIONAR</button>
            </div>
            <div class="modal-list" id="lista-modal-itens" style="max-height: 500px; overflow-y: auto; padding-right:5px;">
                ${renderizarListaComponentes(lista, tipo)}
            </div>
        `;
        body.innerHTML = html;
    }

    overlay.style.display = 'flex';
    setTimeout(() => overlay.classList.add('ativo'), 10);
}

// Variáveis de controle de filtros do catálogo (resetadas ao abrir)
let filtroClasseAtiva = 'Todos';
let filtroTrilhaAtiva = 'Todos';
let filtroPoderPrincipal = 'Todos';
let filtroPoderSub = 'Todos';
let filtroPoderElemento = 'Todos';

function filtrarListaModal(termo, tipo, isCatalogo = false) {
    termo = termo.toLowerCase();
    let base = [];
    if (isCatalogo) {
        if (tipo === 'habilidades') base = SISTEMA_HABILIDADES;
        if (tipo === 'poderes') base = SISTEMA_PODERES;
        if (tipo === 'inventario') base = SISTEMA_ITENS;
    } else {
        if (tipo === 'habilidades') base = DADOS_HABILIDADES;
        if (tipo === 'poderes') base = DADOS_PODERES;
        if (tipo === 'inventario') base = DADOS_INVENTARIO;
    }

    const filtrada = base.filter(i => {
        const nome = (i.nm_habilidade || i.nm_item || '').toLowerCase();
        const desc = (i.ds_habilidade || i.ds_item || '').toLowerCase();
        
        // 1. Busca textual padrão
        const bateBusca = nome.includes(termo) || desc.includes(termo);
        if (!bateBusca) return false;

        // Se for o catálogo de habilidades, aplica filtros dinâmicos
        if (isCatalogo && tipo === 'habilidades') {
            if (filtroClasseAtiva !== 'Todos') {
                if (filtroClasseAtiva === 'Combatente') {
                    const kw = ['combatente', 'ataque', 'luta', 'combate', 'durão', 'guerreiro', 'aniquilador', 'choque', 'operações', 'valentão', 'força', 'vigor', 'armamento', 'pesado', 'marcial'];
                    if (!kw.some(k => nome.includes(k) || desc.includes(k))) return false;

                    if (filtroTrilhaAtiva !== 'Todos') {
                        const mapTrilha = {
                            'Aniquilador': ['aniquilador', 'aniquilação', 'arma favorita'],
                            'Comandante de Campo': ['comandante', 'liderança', 'ordens', 'tático', 'campo'],
                            'Guerreiro': ['guerreiro', 'contra-ataque', 'técnica de luta'],
                            'Operações Especiais': ['operações especiais', 'iniciativa', 'ação extra', 'deslocamento'],
                            'Tropa de Choque': ['tropa de choque', 'casca grossa', 'provocação', 'defesa corporal']
                        };
                        if (!(mapTrilha[filtroTrilhaAtiva] || []).some(k => nome.includes(k) || desc.includes(k))) return false;
                    }
                }
                else if (filtroClasseAtiva === 'Especialista') {
                    const kw = ['especialista', 'perícia', 'habilidoso', 'pesquisa', 'técnico', 'médico', 'atirador', 'infiltrador', 'negociador', 'intelecto', 'presença', 'agilidade', 'ecletismo', 'genialidade'];
                    if (!kw.some(k => nome.includes(k) || desc.includes(k))) return false;

                    if (filtroTrilhaAtiva !== 'Todos') {
                        const mapTrilha = {
                            'Atirador': ['atirador', 'pontaria', 'mira', 'disparo', 'balística'],
                            'Infiltrador': ['infiltrador', 'furtivo', 'ataque surpresa', 'arrombamento'],
                            'Médico de Campo': ['médico de campo', 'cura', 'medicina', 'primeiros socorros'],
                            'Técnico': ['técnico', 'inventário', 'dispositivos', 'remendos'],
                            'Negociador': ['negociador', 'diplomacia', 'lábia', 'influência', 'acordo']
                        };
                        if (!(mapTrilha[filtroTrilhaAtiva] || []).some(k => nome.includes(k) || desc.includes(k))) return false;
                    }
                }
                else if (filtroClasseAtiva === 'Ocultista') {
                    const kw = ['ocultista', 'ritual', 'paranormal', 'conduíte', 'graduado', 'lâmina', 'intuitivo', 'flagelador', 'presença', 'pe', 'misticismo', 'ocultismo', 'transcendência'];
                    if (!kw.some(k => nome.includes(k) || desc.includes(k))) return false;

                    if (filtroTrilhaAtiva !== 'Todos') {
                        const mapTrilha = {
                            'Conduíte': ['conduíte', 'alcance ampliado', 'conduzir', 'ritmo acelerado'],
                            'Graduado': ['graduado', 'grimório', 'conhecimento ritual', 'memória'],
                            'Lâmina Paranormal': ['lâmina', 'combate amaldiçoado', 'espada', 'ataque místico'],
                            'Intuitivo': ['intuitivo', 'intuição', 'presença defensiva', 'percepção da mente'],
                            'Flagelador': ['flagelador', 'sangue pe', 'sacrifício', 'dor']
                        };
                        if (!(mapTrilha[filtroTrilhaAtiva] || []).some(k => nome.includes(k) || desc.includes(k))) return false;
                    }
                }
                else if (filtroClasseAtiva === 'Origens') {
                    const kw = ['origem', 'trabalho', 'acadêmico', 'cidadão', 'cultista', 'policial', 'militar', 'medicina', 'artista', 'investigador', 'atlético', 'mercador', 'religioso', 'teórico'];
                    if (!kw.some(k => nome.includes(k) || desc.includes(k))) return false;
                }
                else if (filtroClasseAtiva === 'Poderes Paranormais') {
                    const kw = ['paranormal', 'sangue', 'morte', 'conhecimento', 'energia', 'medo', 'afinidade', 'elemento'];
                    if (!kw.some(k => nome.includes(k) || desc.includes(k))) return false;

                    if (filtroTrilhaAtiva !== 'Todos') {
                        const elLower = filtroTrilhaAtiva.toLowerCase();
                        if (!nome.includes(elLower) && !desc.includes(elLower)) return false;
                    }
                }
            }
        }

        // Se for o catálogo de poderes/rituais, aplica filtros de rituais e uso
        if (isCatalogo && tipo === 'poderes') {
            const ehRitual = nome.includes('ritual') || desc.includes('ritual') || desc.includes('círculo') || desc.includes('circulo');
            const tipoUso = (i.tp_habilidade || '').toLowerCase();

            if (filtroPoderPrincipal !== 'Todos') {
                if (filtroPoderPrincipal === 'Rituais' && !ehRitual) return false;
                if (filtroPoderPrincipal === 'Poderes de Classe') {
                    if (ehRitual) return false;
                    const kwClasse = ['combatente', 'especialista', 'ocultista', 'classe', 'poder de'];
                    if (!kwClasse.some(k => nome.includes(k) || desc.includes(k))) return false;
                }
                if (filtroPoderPrincipal === 'Poderes Paranormais') {
                    if (ehRitual) return false;
                    const kwPara = ['paranormal', 'sangue', 'morte', 'conhecimento', 'energia', 'medo', 'afinidade'];
                    if (!kwPara.some(k => nome.includes(k) || desc.includes(k))) return false;
                }
            }

            if (ehRitual && (filtroPoderPrincipal === 'Rituais' || filtroPoderPrincipal === 'Todos')) {
                // Filtro de Círculo
                if (filtroPoderSub !== 'Todos') {
                    const circLower = filtroPoderSub.toLowerCase(); // '1º círculo' etc.
                    if (!desc.includes(circLower) && !nome.includes(circLower)) return false;
                }
                // Filtro de Elemento
                if (filtroPoderElemento !== 'Todos') {
                    const elLower = filtroPoderElemento.toLowerCase();
                    if (!desc.includes(elLower) && !nome.includes(elLower)) return false;
                }
            } else {
                // Poderes normais - Filtro de Uso
                if (filtroPoderSub !== 'Todos') {
                    if (filtroPoderSub === '1 Turno') {
                        const kwTurno = ['1 rodada', '1 turno', 'ação livre', 'reação', 'rodada', 'turno'];
                        if (!kwTurno.some(k => desc.includes(k) || nome.includes(k))) return false;
                    }
                    else if (filtroPoderSub === 'Reação') {
                        if (!desc.includes('reação') && !nome.includes('reação') && tipoUso !== 'reacao') return false;
                    }
                    else if (filtroPoderSub === 'Passivo') {
                        if (!desc.includes('passiva') && !nome.includes('passivo') && tipoUso !== 'passiva') return false;
                    }
                }
            }
        }

        return true;
    });

    const target = isCatalogo ? 'lista-catalogo-itens' : 'lista-modal-itens';
    document.getElementById(target).innerHTML = renderizarListaComponentes(filtrada, tipo, isCatalogo);
}

function renderizarListaComponentes(lista, tipo, isCatalogo = false) {
    if (lista.length === 0) {
        return `<div style="text-align:center; padding:40px; color:#666;">Nenhum item disponível.</div>`;
    }

    const isOrdem = typeof SISTEMA_NOME !== 'undefined' && SISTEMA_NOME.toLowerCase().includes('ordem paranormal');
    const glowColor = isOrdem ? 'rgba(255, 50, 50, 0.85)' : 'rgba(157, 122, 255, 0.7)';

    return lista.map((item, idx) => {
        const id = item.id_habilidade || item.id_item;
        const nome = item.nm_habilidade || item.nm_item || 'Sem Nome';
        const desc = item.ds_habilidade || item.ds_item || 'Sem descrição.';
        const extra = item.qt_quantidade ? ` <span style="color:#666;">(x${item.qt_quantidade})</span>` : '';
        const idEl = isCatalogo ? `cat-${idx}` : `item-${idx}`;
        
        return `
            <div class="modal-list-item" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:12px; margin-bottom:10px; overflow:hidden;">
                <div class="item-header" style="padding:15px; display:flex; align-items:center; width:100%; justify-content:space-between;">
                    <span onclick="toggleItemDesc('${idEl}')" style="font-weight:700; color:#ddd; font-size:0.9rem; cursor:pointer; flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-right:20px;">${nome}${extra}</span>
                    <div style="display:flex; align-items:center; gap:15px; flex-shrink:0; justify-content:flex-end; min-width:85px;">
                        <i class="fas fa-chevron-down" id="icon-${idEl}" onclick="toggleItemDesc('${idEl}')" style="transition:0.3s; color:#666; font-size:0.8rem; cursor:pointer; padding:5px;"></i>
                        ${isCatalogo ? `<button onclick="adicionarAoPersonagem('${tipo}', ${id})" style="background:var(--premium-accent); border:none; width:35px; height:35px; border-radius:50%; color:#fff; font-size:1.1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow: 0 0 15px ${glowColor};"><i class="fas fa-plus"></i></button>` : `<div style="width:35px; height:35px; flex-shrink:0;"></div>`}
                    </div>
                </div>
                <div class="item-desc" id="desc-${idEl}" style="display:none; padding:0 15px 15px 15px; font-size:0.85rem; color:#aaa; border-top:1px solid rgba(255,255,255,0.03);">
                    <p style="margin:10px 0; line-height:1.5;">${desc}</p>
                    ${item.tp_item ? `<div style="font-size:0.65rem; color:var(--premium-accent); font-weight:900; text-transform:uppercase; letter-spacing:1px;">CATEGORIA: ${item.tp_item}</div>` : ''}
                    ${item.tp_habilidade ? `<div style="font-size:0.65rem; color:var(--premium-accent); font-weight:900; text-transform:uppercase; letter-spacing:1px;">USO: ${item.tp_habilidade}</div>` : ''}
                </div>
            </div>
        `;
    }).join('');
}

function abrirCatalogo(tipo) {
    const titulo = document.getElementById('modal-titulo');
    const body = document.getElementById('modal-body');
    
    // Resetar filtros ao abrir
    filtroClasseAtiva = 'Todos';
    filtroTrilhaAtiva = 'Todos';
    filtroPoderPrincipal = 'Todos';
    filtroPoderSub = 'Todos';
    filtroPoderElemento = 'Todos';

    titulo.innerHTML = `<i class="fas fa-arrow-left" onclick="abrirModal('${tipo}')" style="margin-right:15px; cursor:pointer; font-size:1.2rem; vertical-align:middle;"></i> Catálogo de ${NOMES_TIPOS[tipo]?.titulo || tipo}`;

    // Renderizar painel de filtros dependendo do tipo
    let filtersHtml = '';
    
    if (tipo === 'habilidades') {
        filtersHtml = `
            <div class="pills-container">
                ${['Todos', 'Combatente', 'Especialista', 'Ocultista', 'Origens', 'Poderes Paranormais'].map(c => `
                    <button class="pill-btn ${c === 'Todos' ? 'ativo' : ''}" onclick="toggleFiltroClasse('${c}', this)">${c}</button>
                `).join('')}
            </div>
            <div id="sub-pills-area" style="display:none;"></div>
        `;
    } 
    else if (tipo === 'poderes') {
        filtersHtml = `
            <div class="pills-container">
                ${['Todos', 'Rituais', 'Poderes de Classe', 'Poderes Paranormais'].map(p => `
                    <button class="pill-btn ${p === 'Todos' ? 'ativo' : ''}" onclick="toggleFiltroPoder('${p}', this)">${p}</button>
                `).join('')}
            </div>
            <div id="sub-pills-area-1" style="display:none;"></div>
            <div id="sub-pills-area-2" style="display:none;"></div>
        `;
    }

    let html = `
        <div class="modal-search-area" style="margin-bottom:15px; background:rgba(255,255,255,0.03); padding:15px; border-radius:12px;">
            <input type="text" id="busca-catalogo-input" placeholder="Buscar ${NOMES_TIPOS[tipo]?.plural || 'itens'} no sistema..." style="flex:1; background:none; border:none; color:#fff; outline:none; width:100%;" oninput="filtrarListaModal(this.value, '${tipo}', true)">
        </div>
        ${filtersHtml}
        <div class="modal-list" id="lista-catalogo-itens" style="max-height: 400px; overflow-y: auto;">
            ${renderizarListaComponentes((tipo === 'habilidades' ? SISTEMA_HABILIDADES : (tipo === 'poderes' ? SISTEMA_PODERES : SISTEMA_ITENS)), tipo, true)}
        </div>
    `;
    body.innerHTML = html;
}

// Funções de clique de filtro de habilidades
function toggleFiltroClasse(classe, btn) {
    filtroClasseAtiva = classe;
    filtroTrilhaAtiva = 'Todos'; // Resetar sub-trilha

    document.querySelectorAll('.pills-container .pill-btn').forEach(b => b.classList.remove('ativo'));
    btn.classList.add('ativo');

    const subArea = document.getElementById('sub-pills-area');
    if (!subArea) return;

    if (['Combatente', 'Especialista', 'Ocultista', 'Poderes Paranormais'].includes(classe)) {
        let options = [];
        if (classe === 'Combatente') options = ['Todos', 'Aniquilador', 'Comandante de Campo', 'Guerreiro', 'Operações Especiais', 'Tropa de Choque'];
        if (classe === 'Especialista') options = ['Todos', 'Atirador', 'Infiltrador', 'Médico de Campo', 'Técnico', 'Negociador'];
        if (classe === 'Ocultista') options = ['Todos', 'Conduíte', 'Graduado', 'Lâmina Paranormal', 'Intuitivo', 'Flagelador'];
        if (classe === 'Poderes Paranormais') options = ['Todos', 'Energia', 'Morte', 'Sangue', 'Conhecimento', 'Medo'];

        subArea.className = 'sub-pills-container';
        subArea.style.display = 'flex';
        subArea.innerHTML = options.map(opt => `
            <button class="pill-btn ${opt === 'Todos' ? 'ativo' : ''}" onclick="toggleFiltroTrilha('${opt}', this)">${opt}</button>
        `).join('');
    } else {
        subArea.style.display = 'none';
        subArea.innerHTML = '';
    }

    triggerFiltroCatalogo('habilidades');
}

function toggleFiltroTrilha(trilha, btn) {
    filtroTrilhaAtiva = trilha;
    document.querySelectorAll('#sub-pills-area .pill-btn').forEach(b => b.classList.remove('ativo'));
    btn.classList.add('ativo');
    triggerFiltroCatalogo('habilidades');
}

// Funções de clique de filtro de poderes/rituais
function toggleFiltroPoder(categoria, btn) {
    filtroPoderPrincipal = categoria;
    filtroPoderSub = 'Todos';
    filtroPoderElemento = 'Todos';

    document.querySelectorAll('.pills-container .pill-btn').forEach(b => b.classList.remove('ativo'));
    btn.classList.add('ativo');

    const subArea1 = document.getElementById('sub-pills-area-1');
    const subArea2 = document.getElementById('sub-pills-area-2');

    if (categoria === 'Rituais') {
        // Rituais: Sub-aba 1 (Círculo)
        subArea1.className = 'sub-pills-container';
        subArea1.style.display = 'flex';
        subArea1.innerHTML = ['Todos', '1º Círculo', '2º Círculo', '3º Círculo', '4º Círculo'].map(circ => `
            <button class="pill-btn ${circ === 'Todos' ? 'ativo' : ''}" onclick="toggleFiltroPoderSub('${circ}', this, 'rituais')">${circ}</button>
        `).join('');

        // Rituais: Sub-aba 2 (Elemento)
        subArea2.className = 'sub-pills-container';
        subArea2.style.display = 'flex';
        subArea2.style.marginTop = '10px';
        subArea2.innerHTML = ['Todos', 'Conhecimento', 'Energia', 'Morte', 'Sangue', 'Medo'].map(el => `
            <button class="pill-btn ${el === 'Todos' ? 'ativo' : ''}" onclick="toggleFiltroPoderElemento('${el}', this)">${el}</button>
        `).join('');
    } 
    else if (categoria === 'Poderes de Classe' || categoria === 'Poderes Paranormais' || categoria === 'Todos') {
        // Poderes: Sub-aba de Uso
        subArea1.className = 'sub-pills-container';
        subArea1.style.display = 'flex';
        subArea1.innerHTML = ['Todos', '1 Turno', 'Reação', 'Passivo'].map(uso => `
            <button class="pill-btn ${uso === 'Todos' ? 'ativo' : ''}" onclick="toggleFiltroPoderSub('${uso}', this, 'poderes')">${uso}</button>
        `).join('');
        subArea2.style.display = 'none';
        subArea2.innerHTML = '';
    } else {
        subArea1.style.display = 'none';
        subArea2.style.display = 'none';
        subArea1.innerHTML = '';
        subArea2.innerHTML = '';
    }

    triggerFiltroCatalogo('poderes');
}

function toggleFiltroPoderSub(sub, btn, contexto) {
    filtroPoderSub = sub;
    const targetArea = (contexto === 'rituais') ? 'sub-pills-area-1' : 'sub-pills-area-1';
    document.querySelectorAll(`#${targetArea} .pill-btn`).forEach(b => b.classList.remove('ativo'));
    btn.classList.add('ativo');
    triggerFiltroCatalogo('poderes');
}

function toggleFiltroPoderElemento(el, btn) {
    filtroPoderElemento = el;
    document.querySelectorAll('#sub-pills-area-2 .pill-btn').forEach(b => b.classList.remove('ativo'));
    btn.classList.add('ativo');
    triggerFiltroCatalogo('poderes');
}

function triggerFiltroCatalogo(tipo) {
    const input = document.getElementById('busca-catalogo-input');
    const termo = input ? input.value : '';
    filtrarListaModal(termo, tipo, true);
}

async function adicionarAoPersonagem(tipo, id) {
    try {
        const resp = await fetch('../app/ajax/adicionar-componente-ficha.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_personagem: ID_PERSONAGEM,
                tipo: tipo,
                id_componente: id
            })
        });
        
        const data = await resp.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Falha ao salvar item'));
        }
    } catch (err) {
        console.error('Erro:', err);
        alert('Erro de conexão: Não foi possível salvar o item no banco de dados.');
    }
}

function toggleExpandCampo(campo) {
    const wrapper = document.getElementById(`wrapper-${campo}`);
    const textarea = wrapper.querySelector('textarea');
    const icon = wrapper.querySelector('i');

    if (wrapper.style.gridColumn === 'span 2') {
        wrapper.style.gridColumn = 'auto';
        textarea.style.height = '120px';
        icon.className = 'fas fa-expand-alt';
    } else {
        wrapper.style.gridColumn = 'span 2';
        textarea.style.height = '300px';
        icon.className = 'fas fa-compress-alt';
    }
}

function toggleItemDesc(idEl) {
    const desc = document.getElementById(`desc-${idEl}`);
    const icon = document.getElementById(`icon-${idEl}`);
    if (desc.style.display === 'none') {
        desc.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        desc.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

function fecharModal() {
    const overlay = document.getElementById('modal-overlay');
    overlay.classList.remove('ativo');
    setTimeout(() => {
        if (!overlay.classList.contains('ativo')) overlay.style.display = 'none';
    }, 400); // Tempo da transição no CSS
}

// Fechar modal removido do clique externo a pedido do usuário
window.onclick = (e) => {
    // Apenas manter se necessário para outras funções globais
};

function inicializarFichaEditavel() {
    // 1. Ouvir os spans de PROTEÇÃO, RESISTÊNCIAS, PROFICIÊNCIAS
    document.querySelectorAll('.line-text').forEach(el => {
        el.addEventListener('blur', () => {
            const campo = el.dataset.campo;
            const val = el.textContent.trim();
            salvarDados('personagem_campo', campo, val);
        });
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                el.blur();
            }
        });
    });

    // 2. Ouvir os spans de fórmula de defesa e stats (bloqueio/esquiva)
    document.querySelectorAll('.defesa-formula-val, .defesa-stats-row .val').forEach(el => {
        el.addEventListener('blur', () => {
            const campo = el.dataset.campo;
            const val = parseInt(el.textContent.replace(/\D/g, '')) || 0;
            el.textContent = val;
            salvarDados('defesa_calc', campo, val);
            recalcularValoresFicha();
        });
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                el.blur();
            }
        });
    });

    // 3. Ouvir cliques nas Perícias (Seletor de Treino Premium com Popup Adaptativo)
    document.querySelectorAll('.p-treino').forEach(el => {
        el.addEventListener('click', (event) => {
            event.stopPropagation(); // Evita fechar imediatamente o popup recém-criado
            
            // Remover qualquer popup anterior que possa estar aberto
            document.querySelectorAll('.treino-selector-popup').forEach(p => p.remove());

            const periciaId = el.dataset.periciaId || el.closest('.p-values')?.dataset.periciaId;
            const valuesContainer = el.closest('.p-values');
            const bonusEl = valuesContainer ? valuesContainer.querySelector('.p-bonus') : null;
            const outrosEl = valuesContainer ? valuesContainer.querySelector('.p-outros') : null;

            // Criar o popup
            const popup = document.createElement('div');
            popup.className = 'treino-selector-popup';
            
            // Determinar o tema do sistema para estilo visual
            const isOrdem = typeof SISTEMA_NOME !== 'undefined' && SISTEMA_NOME.toLowerCase().includes('ordem paranormal');
            const accentColor = isOrdem ? '#ff3232' : 'var(--premium-accent, #9d4edd)';
            const glowColor = isOrdem ? 'rgba(255, 50, 50, 0.4)' : 'rgba(157, 122, 255, 0.3)';
            const borderStyle = `1px solid ${accentColor}`;
            const shadowStyle = `0 10px 25px rgba(0, 0, 0, 0.6), 0 0 15px ${glowColor}`;

            popup.style.cssText = `
                position: absolute;
                background: rgba(15, 10, 15, 0.98);
                border: ${borderStyle};
                border-radius: 12px;
                padding: 8px;
                display: flex;
                gap: 8px;
                box-shadow: ${shadowStyle};
                z-index: 10000;
                backdrop-filter: blur(8px);
                transition: opacity 0.2s, transform 0.2s;
            `;

            // Adicionar opções
            [0, 5, 10, 15].forEach(val => {
                const btn = document.createElement('button');
                btn.className = 'treino-btn-option';
                btn.type = 'button';
                btn.textContent = val;
                
                btn.style.cssText = `
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    color: #fff;
                    font-weight: 800;
                    font-size: 0.9rem;
                    padding: 6px 12px;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.2s;
                    font-family: inherit;
                `;

                btn.addEventListener('mouseenter', () => {
                    btn.style.background = accentColor;
                    btn.style.borderColor = accentColor;
                    btn.style.transform = 'translateY(-2px)';
                    btn.style.boxShadow = `0 4px 10px ${glowColor}`;
                });

                btn.addEventListener('mouseleave', () => {
                    btn.style.background = 'rgba(255, 255, 255, 0.05)';
                    btn.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                    btn.style.transform = 'none';
                    btn.style.boxShadow = 'none';
                });

                btn.addEventListener('click', () => {
                    // Atualiza a UI de Treino
                    el.textContent = '+' + val;
                    
                    // Lê o valor de Outros
                    const outrosVal = outrosEl ? (parseInt(outrosEl.textContent) || 0) : 0;
                    
                    // Calcula o novo Total
                    const total = val + outrosVal;
                    
                    // Atualiza a UI de Bônus
                    if (bonusEl) {
                        bonusEl.textContent = `(${total})`;
                    }

                    // Salva no banco de dados via AJAX
                    salvarDados('pericia_treino', periciaId, val);
                    salvarDados('pericia_val', periciaId + '|qt_valor', total);

                    popup.remove();
                });

                popup.appendChild(btn);
            });

            document.body.appendChild(popup);

            // Posicionamento inteligente abaixo do elemento clicado
            const rect = el.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

            popup.style.top = `${rect.bottom + scrollTop + 6}px`;
            // Centralizar horizontalmente sob o elemento
            popup.style.left = `${rect.left + scrollLeft - (popup.offsetWidth / 2) + (rect.width / 2)}px`;

            // Evitar que o popup saia da tela pelas laterais
            const popupRect = popup.getBoundingClientRect();
            if (popupRect.left < 10) {
                popup.style.left = `${scrollLeft + 10}px`;
            } else if (popupRect.right > window.innerWidth - 10) {
                popup.style.left = `${scrollLeft + window.innerWidth - popupRect.width - 10}px`;
            }
        });
    });

    // Fechar popup de treino se clicar fora
    document.addEventListener('click', () => {
        document.querySelectorAll('.treino-selector-popup').forEach(p => p.remove());
    });

    // 4. Ouvir edição de "Outros" nas Perícias (Validação numérica, Limite de 99 e Recálculo dinâmico)
    document.querySelectorAll('.p-outros').forEach(el => {
        const periciaId = el.dataset.periciaId || el.closest('.p-values')?.dataset.periciaId;
        const valuesContainer = el.closest('.p-values');
        const bonusEl = valuesContainer ? valuesContainer.querySelector('.p-bonus') : null;
        const treinoEl = valuesContainer ? valuesContainer.querySelector('.p-treino') : null;

        const recalcular = () => {
            // Limpa caracteres não-numéricos e limita a 99
            let rawText = el.textContent.trim().replace(/\D/g, '');
            let outrosVal = parseInt(rawText) || 0;
            if (outrosVal > 99) outrosVal = 99;
            
            // Atualiza o texto da UI se houver diferença
            if (el.textContent !== String(outrosVal) && el.textContent !== '') {
                const selection = window.getSelection();
                const range = document.createRange();
                el.textContent = outrosVal;
                
                // Manter cursor no final caso o elemento tenha o foco
                if (document.activeElement === el && el.childNodes.length > 0) {
                    range.selectNodeContents(el);
                    range.collapse(false);
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
            }

            // Lê treino atual
            const treinoText = treinoEl ? treinoEl.textContent.replace(/[^\d]/g, '') : '0';
            const treinoVal = parseInt(treinoText) || 0;

            // Calcula total
            const total = treinoVal + outrosVal;

            if (bonusEl) {
                bonusEl.textContent = `(${total})`;
            }

            return { outrosVal, total };
        };

        el.addEventListener('keydown', (e) => {
            const keysPermitidas = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter', 'Escape'];
            if (!/[0-9]/.test(e.key) && !keysPermitidas.includes(e.key)) {
                e.preventDefault();
            }
            if (e.key === 'Enter') {
                e.preventDefault();
                el.blur();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                el.blur();
            }
        });

        el.addEventListener('input', () => {
            recalcular();
        });

        el.addEventListener('blur', () => {
            const { outrosVal, total } = recalcular();
            el.textContent = outrosVal; // Garante exibição apenas do número limpo
            
            // Persiste no banco de dados via AJAX
            salvarDados('pericia_val', periciaId + '|qt_outros', outrosVal);
            salvarDados('pericia_val', periciaId + '|qt_valor', total);
        });

        // Prevenir colagem de texto inválido
        el.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.originalEvent || e).clipboardData.getData('text/plain').replace(/\D/g, '').slice(0, 2);
            document.execCommand('insertText', false, text);
            recalcular();
        });
    });

    // 4.5 Sincronização e Recálculo visual de todas as perícias na inicialização para consistência
    document.querySelectorAll('.p-row').forEach(row => {
        const valuesContainer = row.querySelector('.p-values');
        if (valuesContainer) {
            const bonusEl = valuesContainer.querySelector('.p-bonus');
            const treinoEl = valuesContainer.querySelector('.p-treino');
            const outrosEl = valuesContainer.querySelector('.p-outros');

            if (bonusEl && treinoEl && outrosEl) {
                const treinoVal = parseInt(treinoEl.textContent.replace(/[^\d]/g, '')) || 0;
                const outrosVal = parseInt(outrosEl.textContent.replace(/\D/g, '')) || 0;
                const total = treinoVal + outrosVal;
                bonusEl.textContent = `(${total})`;
            }
        }
    });

    // 5. Ouvir edição de Nível (NEX)
    const nivelEl = document.getElementById('nivel-valor');
    if (nivelEl) {
        nivelEl.addEventListener('blur', () => {
            const val = parseInt(nivelEl.textContent.replace(/\D/g, '')) || 1;
            nivelEl.textContent = val;
            salvarDados('personagem_campo', 'qt_nivel', val);
            recalcularValoresFicha();
        });
        nivelEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                nivelEl.blur();
            }
        });
    }
}

function recalcularValoresFicha() {
    // Só executa se o sistema for Ordem Paranormal
    if (typeof SISTEMA_NOME === 'undefined' || !SISTEMA_NOME.toLowerCase().includes('ordem paranormal')) return;

    // 1. Obter Atributos Atuais de forma insensível a maiúsculas/abreviações
    const getAttr = (names) => {
        for (const name of names) {
            const el = Array.from(document.querySelectorAll('.premium-attr-box')).find(
                box => box.dataset.attr.toLowerCase() === name.toLowerCase() || 
                       box.querySelector('.attr-abbr')?.textContent.toLowerCase() === name.toLowerCase()
            );
            if (el) return parseInt(el.querySelector('.attr-circle').textContent) || 0;
        }
        return 0;
    };

    const vig = getAttr(['Vigor', 'VIG']);
    const pre = getAttr(['Presença', 'PRE']);
    const agi = getAttr(['Agilidade', 'AGI']);
    const force = getAttr(['Força', 'FOR']);
    const intel = getAttr(['Intelecto', 'INT']);

    // 2. Obter Nível (NEX) Atual
    const nivelEl = document.getElementById('nivel-valor');
    const nivel = nivelEl ? (parseInt(nivelEl.textContent) || 1) : 1;

    // 3. Obter Classe
    const classe = typeof CLASSE_NOME !== 'undefined' ? CLASSE_NOME.toLowerCase() : 'mundano';

    // 4. Calcular PV, Sanidade e PE máximos baseados nas classes oficiais
    let maxPV = 8 + vig;
    let maxSAN = 8;
    let maxPE = 1;

    if (classe.includes('combatente')) {
        maxPV = 20 + vig + (nivel - 1) * (4 + vig);
        maxSAN = 12 + (nivel - 1) * 2;
        maxPE = 2 + pre + (nivel - 1) * 2;
    } else if (classe.includes('especialista')) {
        maxPV = 16 + vig + (nivel - 1) * (3 + vig);
        maxSAN = 16 + (nivel - 1) * 3;
        maxPE = 3 + pre + (nivel - 1) * 3;
    } else if (classe.includes('ocultista')) {
        maxPV = 12 + vig + (nivel - 1) * (2 + vig);
        maxSAN = 20 + (nivel - 1) * 4;
        maxPE = 4 + pre + (nivel - 1) * 4;
    }

    // 5. Atualizar as barras no front-end e salvar no banco via AJAX
    const atualizarBarraMax = (label, novoMax) => {
        const unit = Array.from(document.querySelectorAll('.bar-unit')).find(
            el => el.dataset.campo.toLowerCase() === label.toLowerCase()
        );
        if (unit) {
            const spanMax = unit.querySelector('.val-max');
            const spanAtual = unit.querySelector('.val-atual');
            const barFill = unit.querySelector('.bar-fill');
            const idStatus = unit.dataset.idStatus;

            if (spanMax) {
                spanMax.textContent = novoMax;
                let atual = parseInt(spanAtual.textContent) || 0;
                // Limitar atual ao novo máximo para evitar inconsistências visuais
                if (atual > novoMax) {
                    atual = novoMax;
                    spanAtual.textContent = atual;
                    salvarDados(isNaN(idStatus) ? 'stat' : 'status_custom', idStatus || label, atual);
                }
                const percent = novoMax > 0 ? (atual / novoMax) * 100 : 0;
                barFill.style.width = Math.min(100, Math.max(0, percent)) + '%';
                
                salvarDados(isNaN(idStatus) ? 'stat_max' : 'status_custom_max', idStatus || label, novoMax);
            }
        }
    };

    atualizarBarraMax('VIDA', maxPV);
    atualizarBarraMax('SANIDADE', maxSAN);
    atualizarBarraMax('ESFORÇO', maxPE);

    // 6. Calcular Defesa Dinâmica: 10 + AGI + Equipamento + Outros
    const equipValEl = document.querySelector('.defesa-formula-val[data-campo="qt_defesa_equip"]');
    const outrosValEl = document.querySelector('.defesa-formula-val[data-campo="qt_defesa_outros"]');
    
    const equip = equipValEl ? (parseInt(equipValEl.textContent) || 0) : 0;
    const outros = outrosValEl ? (parseInt(outrosValEl.textContent) || 0) : 0;

    const totalDefesa = 10 + agi + equip + outros;

    // Atualizar escudo de defesa na UI e no Banco de Dados
    const shieldNum = document.querySelector('.defesa-shield-box .shield-number');
    if (shieldNum) {
        shieldNum.textContent = totalDefesa;
        const idStatus = shieldNum.closest('.defesa-shield-box').dataset.idStatus;
        if (idStatus === 'defesa') {
            salvarDados('defesa', 'defesa', totalDefesa);
        } else {
            salvarDados('status_custom', idStatus, totalDefesa);
        }
    }

    // 7. Calcular Esquiva Dinâmica: Defesa + Agilidade
    const esquivaValEl = document.querySelector('.defesa-stats-row .val[data-campo="qt_esquiva"]');
    if (esquivaValEl) {
        const totalEsquiva = totalDefesa + agi;
        esquivaValEl.textContent = totalEsquiva;
        salvarDados('defesa_calc', 'qt_esquiva', totalEsquiva);
    }
}
