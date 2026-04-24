// js/ficha.js

document.addEventListener('DOMContentLoaded', () => {
    inicializarBarras();
    inicializarAtributos();
    inicializarAvatar();
});

// --- STATUS BARS (Dinâmicas por Sistema) ---
function inicializarBarras() {
    document.querySelectorAll('.bar-unit').forEach(unit => {
        const campo = unit.dataset.campo;
        const idStatus = unit.dataset.idStatus;
        const barFill = unit.querySelector('.bar-fill');
        const numSpan = unit.querySelector('.bar-num span');
        const maxVal = parseInt(unit.querySelector('.bar-num').textContent.split('/')[1]) || 100;

        const atualizarUI = (novoValor) => {
            novoValor = Math.max(0, Math.min(novoValor, maxVal));
            numSpan.textContent = novoValor;
            const percent = (novoValor / maxVal) * 100;
            barFill.style.width = percent + '%';
            
            // Auto-save: Se idStatus for numérico, é customizado. Se for string (vida/sanidade), é padrão.
            const tipoSave = isNaN(idStatus) ? 'stat' : 'status_custom';
            salvarDados(tipoSave, idStatus || campo, novoValor);
        };

        // Botões de clique usando data-step
        unit.querySelectorAll('.step-btn').forEach(btn => {
            btn.onclick = () => {
                let atual = parseInt(numSpan.textContent) || 0;
                const step = parseInt(btn.dataset.step);
                atualizarUI(atual + step);
            };
        });

        // Edição manual
        numSpan.onblur = () => atualizarUI(parseInt(numSpan.textContent) || 0);
        numSpan.onkeydown = (e) => { if (e.key === 'Enter') { e.preventDefault(); numSpan.blur(); } };
    });
}

// --- ATRIBUTOS (FOR, INT, AGI, etc) ---
function inicializarAtributos() {
    document.querySelectorAll('.attr-circle').forEach(circle => {
        const attrName = circle.parentElement.dataset.attr;
        
        circle.onblur = () => {
            const val = parseInt(circle.textContent) || 0;
            circle.textContent = val;
            salvarDados('atributo', attrName, val);
        };

        circle.onkeydown = (e) => { if (e.key === 'Enter') { e.preventDefault(); circle.blur(); } };
    });

    // --- DEFESA / ESCUDOS ---
    document.querySelectorAll('.defesa-shield-box .shield-number').forEach(shield => {
        const idStatus = shield.closest('.defesa-shield-box').dataset.idStatus;
        
        shield.onblur = () => {
            const val = parseInt(shield.textContent) || 0;
            const tipoSave = isNaN(idStatus) ? 'stat' : 'status_custom';
            const campo = isNaN(idStatus) ? 'defesa' : idStatus;
            salvarDados(tipoSave, campo, val);
        };
        shield.onkeydown = (e) => { if (e.key === 'Enter') { e.preventDefault(); shield.blur(); } };
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

    titulo.textContent = tipo.charAt(0).toUpperCase() + tipo.slice(1);
    
    // Gerar estrutura base baseada no Figma
    let html = `
        <div class="modal-search-area">
            <input type="text" placeholder="Filtrar ${tipo}...">
            <button class="btn-add-modal">Adicionar</button>
        </div>
        <div class="modal-list">
    `;

    if (tipo === 'habilidades') {
        html += `<div class="modal-list-item"><span>Faro para pistas</span> <i class="fas fa-chevron-down"></i></div>`;
    } else if (tipo === 'inventario') {
        html += `
            <div style="display:flex; gap:10px; margin-bottom:15px; font-size:0.8rem;">
                <div>Limite: 0 0 0</div>
                <div>Carga: 0 0</div>
            </div>
            <div class="modal-list-item"><span>Mochila de Carga</span> <i class="fas fa-chevron-down"></i></div>
        `;
    } else if (tipo === 'descricao') {
        const d = DADOS_DESCRICAO;
        html = `
            <div class="modal-descricao-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="grupo-form">
                    <label style="display:block; margin-bottom:10px; font-weight:700; color:var(--premium-accent)">Aparência</label>
                    <textarea class="desc-edit" data-campo="aparencia" style="width:100%; height:120px; background:#222; color:#fff; border:1px solid #444; border-radius:10px; padding:10px; resize:none;">${d.aparencia}</textarea>
                </div>
                <div class="grupo-form">
                    <label style="display:block; margin-bottom:10px; font-weight:700; color:var(--premium-accent)">Personalidade</label>
                    <textarea class="desc-edit" data-campo="personalidade" style="width:100%; height:120px; background:#222; color:#fff; border:1px solid #444; border-radius:10px; padding:10px; resize:none;">${d.personalidade}</textarea>
                </div>
                <div class="grupo-form">
                    <label style="display:block; margin-bottom:10px; font-weight:700; color:var(--premium-accent)">História</label>
                    <textarea class="desc-edit" data-campo="historia" style="width:100%; height:120px; background:#222; color:#fff; border:1px solid #444; border-radius:10px; padding:10px; resize:none;">${d.historia}</textarea>
                </div>
                <div class="grupo-form">
                    <label style="display:block; margin-bottom:10px; font-weight:700; color:var(--premium-accent)">Objetivos</label>
                    <textarea class="desc-edit" data-campo="objetivos" style="width:100%; height:120px; background:#222; color:#fff; border:1px solid #444; border-radius:10px; padding:10px; resize:none;">${d.objetivos}</textarea>
                </div>
            </div>
            <div style="margin-top:20px; text-align:right; font-size:0.8rem; color:#888;">* Alterações são salvas automaticamente ao sair do campo.</div>
        `;
        
        body.innerHTML = html;
        overlay.style.display = 'flex';

        // Adicionar eventos de salvamento
        body.querySelectorAll('.desc-edit').forEach(tx => {
            tx.onblur = () => {
                const val = tx.value;
                const campo = tx.dataset.campo;
                DADOS_DESCRICAO[campo] = val; // Atualiza cache local
                salvarDados('descricao', campo, val);
            };
        });
        return; // Pula o wrapper padrão abaixo
    } else {
        html += `<div class="modal-list-item"><span>Vazio</span></div>`;
    }

    html += `</div>`;
    body.innerHTML = html;
    overlay.style.display = 'flex';
}

function fecharModal() {
    document.getElementById('modal-overlay').style.display = 'none';
}

// Fechar modal ao clicar fora
window.onclick = (e) => {
    if (e.target.id === 'modal-overlay') fecharModal();
};
