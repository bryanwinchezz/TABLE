/**
 * ==========================================================================
 * TABLE-MODAL SYSTEM — JS CONTROL GLOBAL (PROMISES, DYNAMIC DOM)
 * ==========================================================================
 */

(function() {
    // Configurações e ícones padrão para cada tipo de modal
    const TYPE_CONFIGS = {
        success: {
            icon: 'fas fa-check-circle',
            defaultTitle: 'Sucesso!'
        },
        error: {
            icon: 'fas fa-times-circle',
            defaultTitle: 'Erro!'
        },
        warning: {
            icon: 'fas fa-exclamation-triangle',
            defaultTitle: 'Atenção!'
        },
        confirm: {
            icon: 'fas fa-question-circle',
            defaultTitle: 'Confirmação'
        }
    };

    const TableModal = {
        /**
         * Exibe um modal informativo/alerta do tipo ALERT (Apenas um botão OK).
         * @param {string} message Mensagem descritiva do modal.
         * @param {string} [title] Título opcional.
         * @param {string} [type='warning'] Tipo do modal: 'success', 'error', 'warning', 'confirm'.
         * @returns {Promise<void>} Promise resolvida ao fechar o modal.
         */
        alert: function(message, title = '', type = 'warning') {
            return new Promise((resolve) => {
                const config = TYPE_CONFIGS[type] || TYPE_CONFIGS.warning;
                const modalTitle = title || config.defaultTitle;

                // 1. Cria a estrutura do modal no DOM
                const overlay = document.createElement('div');
                overlay.className = 'table-modal-overlay';
                
                overlay.innerHTML = `
                    <div class="table-modal-card ${type}">
                        <div class="table-modal-icon-wrapper">
                            <i class="${config.icon}"></i>
                        </div>
                        <h3 class="table-modal-title">${modalTitle}</h3>
                        <p class="table-modal-message">${message}</p>
                        <div class="table-modal-actions">
                            <button class="table-modal-btn table-modal-btn-primary btn-ok">OK</button>
                        </div>
                    </div>
                `;

                document.body.appendChild(overlay);

                // Força um reflow para rodar a transição CSS de fade-in
                overlay.offsetHeight;
                overlay.classList.add('active');

                // 2. Evento para fechar e resolver
                const btnOk = overlay.querySelector('.btn-ok');
                const fecharModal = () => {
                    overlay.classList.remove('active');
                    overlay.querySelector('.table-modal-card').style.transform = 'scale(0.9) translateY(-10px)';
                    overlay.querySelector('.table-modal-card').style.opacity = '0';
                    setTimeout(() => {
                        overlay.remove();
                        resolve();
                    }, 400); // Aguarda transição do CSS
                };

                btnOk.addEventListener('click', fecharModal);
                
                // Fecha ao apertar ENTER
                const handleKeyDown = (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        fecharModal();
                        document.removeEventListener('keydown', handleKeyDown);
                    }
                };
                document.addEventListener('keydown', handleKeyDown);
            });
        },

        /**
         * Exibe um modal de confirmação do tipo CONFIRM (Botões Confirmar e Cancelar).
         * @param {string} message Mensagem descritiva da pergunta.
         * @param {string} [title] Título opcional.
         * @param {string} [type='confirm'] Tipo do modal: 'success', 'error', 'warning', 'confirm'.
         * @returns {Promise<boolean>} Retorna true se clicado em Confirmar, false se cancelado.
         */
        confirm: function(message, title = '', type = 'confirm') {
            return new Promise((resolve) => {
                const config = TYPE_CONFIGS[type] || TYPE_CONFIGS.confirm;
                const modalTitle = title || config.defaultTitle;

                // 1. Cria a estrutura do modal no DOM
                const overlay = document.createElement('div');
                overlay.className = 'table-modal-overlay';
                
                overlay.innerHTML = `
                    <div class="table-modal-card ${type}">
                        <div class="table-modal-icon-wrapper">
                            <i class="${config.icon}"></i>
                        </div>
                        <h3 class="table-modal-title">${modalTitle}</h3>
                        <p class="table-modal-message">${message}</p>
                        <div class="table-modal-actions">
                            <button class="table-modal-btn table-modal-btn-secondary btn-cancel">Cancelar</button>
                            <button class="table-modal-btn table-modal-btn-primary btn-confirm">Confirmar</button>
                        </div>
                    </div>
                `;

                document.body.appendChild(overlay);

                // Força um reflow para rodar a transição CSS de fade-in
                overlay.offsetHeight;
                overlay.classList.add('active');

                // 2. Eventos de fechar e resolver
                const btnConfirm = overlay.querySelector('.btn-confirm');
                const btnCancel = overlay.querySelector('.btn-cancel');

                const fecharModal = (confirmado) => {
                    overlay.classList.remove('active');
                    overlay.querySelector('.table-modal-card').style.transform = 'scale(0.9) translateY(-10px)';
                    overlay.querySelector('.table-modal-card').style.opacity = '0';
                    setTimeout(() => {
                        overlay.remove();
                        resolve(confirmado);
                    }, 400); // Aguarda transição do CSS
                };

                btnConfirm.addEventListener('click', () => fecharModal(true));
                btnCancel.addEventListener('click', () => fecharModal(false));

                // Controla teclas de atalho (ENTER para confirmar, ESC para cancelar)
                const handleKeyDown = (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        fecharModal(true);
                        document.removeEventListener('keydown', handleKeyDown);
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        fecharModal(false);
                        document.removeEventListener('keydown', handleKeyDown);
                    }
                };
                document.addEventListener('keydown', handleKeyDown);
            });
        }
    };

    // Disponibiliza o componente globalmente
    window.TableModal = TableModal;
})();
