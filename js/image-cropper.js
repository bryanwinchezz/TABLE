/**
 * TABLE - Ferramenta Global de Ajuste e Recorte de Imagem (Crop/Resize)
 * Intercepta qualquer input file de imagem e provê um crop elegante 100% integrado.
 */

(function() {
    // Evita carregar em duplicidade
    if (window.TableImageCropper) return;

    window.TableImageCropper = {
        cropperInstance: null,
        activeInput: null,
        originalFile: null,

        // Carrega dependências do Cropper.js via CDN de forma assíncrona e limpa
        init() {
            this.loadAssets();
            this.createModalHTML();
            this.bindEvents();
        },

        loadAssets() {
            if (!document.getElementById('cropper-css')) {
                const css = document.createElement('link');
                css.id = 'cropper-css';
                css.rel = 'stylesheet';
                css.href = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css';
                document.head.appendChild(css);
            }
            if (typeof Cropper === 'undefined' && !document.getElementById('cropper-js')) {
                const js = document.createElement('script');
                js.id = 'cropper-js';
                js.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js';
                document.head.appendChild(js);
            }
        },

        createModalHTML() {
            if (document.getElementById('table-cropper-modal')) return;

            // Injeta CSS Premium diretamente para evitar dependências de arquivos extras
            const style = document.createElement('style');
            style.innerHTML = `
                .table-cropper-overlay {
                    position: fixed;
                    top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(10, 5, 20, 0.85);
                    backdrop-filter: blur(10px);
                    z-index: 99999;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    opacity: 0;
                    pointer-events: none;
                    transition: opacity 0.3s ease;
                }
                .table-cropper-overlay.active {
                    opacity: 1;
                    pointer-events: auto;
                }
                .table-cropper-box {
                    background: rgba(30, 20, 45, 0.95);
                    border: 2px solid rgba(193, 147, 253, 0.3);
                    border-radius: 24px;
                    width: 90%;
                    max-width: 600px;
                    padding: 24px;
                    display: flex;
                    flex-direction: column;
                    gap: 20px;
                    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 30px rgba(101, 57, 199, 0.15);
                    transform: translateY(20px);
                    transition: transform 0.3s ease;
                }
                .table-cropper-overlay.active .table-cropper-box {
                    transform: translateY(0);
                }
                .table-cropper-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .table-cropper-title {
                    font-family: 'Montserrat', sans-serif;
                    font-size: 1.3rem;
                    font-weight: 800;
                    color: #fff;
                    letter-spacing: -0.5px;
                    margin: 0;
                }
                .table-cropper-subtitle {
                    font-size: 0.8rem;
                    color: rgba(255, 255, 255, 0.5);
                    margin: 2px 0 0 0;
                }
                .table-cropper-container {
                    width: 100%;
                    height: 350px;
                    background: #0e0a14;
                    border-radius: 16px;
                    overflow: hidden;
                    border: 1px solid rgba(255, 255, 255, 0.05);
                }
                .table-cropper-container img {
                    max-width: 100%;
                    max-height: 100%;
                    display: block;
                }
                .table-cropper-footer {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 15px;
                }
                .table-cropper-btn {
                    padding: 12px 24px;
                    border-radius: 12px;
                    font-family: 'Montserrat', sans-serif;
                    font-weight: 700;
                    font-size: 0.95rem;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    border: none;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                }
                .table-cropper-btn-cancel {
                    background: transparent;
                    color: rgba(255, 255, 255, 0.6);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                }
                .table-cropper-btn-cancel:hover {
                    background: rgba(255, 255, 255, 0.05);
                    color: #fff;
                }
                .table-cropper-btn-confirm {
                    background: linear-gradient(135deg, #6539C7, #8b5cf6);
                    color: #fff;
                    box-shadow: 0 6px 15px rgba(101, 57, 199, 0.3);
                }
                .table-cropper-btn-confirm:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(101, 57, 199, 0.4);
                }
                .table-cropper-tools {
                    display: flex;
                    gap: 8px;
                }
                .table-cropper-tool-btn {
                    background: rgba(255, 255, 255, 0.05);
                    color: #fff;
                    border: 1px solid rgba(255, 255, 255, 0.05);
                    width: 42px; height: 42px;
                    border-radius: 10px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    transition: all 0.2s;
                }
                .table-cropper-tool-btn:hover {
                    background: rgba(193, 147, 253, 0.2);
                    border-color: rgba(193, 147, 253, 0.4);
                }
            `;
            document.head.appendChild(style);

            const overlay = document.createElement('div');
            overlay.id = 'table-cropper-overlay';
            overlay.className = 'table-cropper-overlay';
            overlay.innerHTML = `
                <div class="table-cropper-box" id="table-cropper-modal">
                    <div class="table-cropper-header">
                        <div>
                            <h4 class="table-cropper-title">Ajustar Imagem</h4>
                            <p class="table-cropper-subtitle">Selecione a área ideal da imagem</p>
                        </div>
                        <div class="table-cropper-tools">
                            <button class="table-cropper-tool-btn" id="table-crop-rotate-left" title="Girar Anti-horário"><i class="fas fa-undo"></i></button>
                            <button class="table-cropper-tool-btn" id="table-crop-rotate-right" title="Girar Horário"><i class="fas fa-redo"></i></button>
                        </div>
                    </div>
                    <div class="table-cropper-container">
                        <img id="table-cropper-image" src="" alt="Imagem para recortar">
                    </div>
                    <div class="table-cropper-footer">
                        <button class="table-cropper-btn table-cropper-btn-cancel" id="table-crop-cancel">Cancelar</button>
                        <button class="table-cropper-btn table-cropper-btn-confirm" id="table-crop-confirm">Aplicar Recorte <i class="fas fa-check"></i></button>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
        },

        bindEvents() {
            // Monitora uploads de imagem em todo o document (evento delegado)
            document.addEventListener('change', (e) => {
                if (e.target && e.target.type === 'file' && e.target.accept && e.target.accept.includes('image')) {
                    // Evita recursão infinita ao disparar evento de troca do arquivo já cortado
                    if (e.target.dataset.croppedState === 'true') {
                        delete e.target.dataset.croppedState;
                        return;
                    }
                    this.handleFileSelect(e);
                }
            }, true);

            // Eventos do modal
            document.getElementById('table-crop-cancel').addEventListener('click', () => this.closeModal());
            document.getElementById('table-crop-confirm').addEventListener('click', () => this.confirmCrop());
            document.getElementById('table-crop-rotate-left').addEventListener('click', () => {
                if (this.cropperInstance) this.cropperInstance.rotate(-90);
            });
            document.getElementById('table-crop-rotate-right').addEventListener('click', () => {
                if (this.cropperInstance) this.cropperInstance.rotate(90);
            });
        },

        handleFileSelect(e) {
            const input = e.target;
            const file = input.files[0];
            if (!file) return;

            // Bloqueia comportamento padrão para interceptarmos
            e.preventDefault();
            e.stopPropagation();

            this.activeInput = input;
            this.originalFile = file;

            // Determinar a proporção ideal (aspectRatio) de acordo com o ID do input ou contexto
            let aspect = null; // Livre por padrão
            const inputId = (input.id || '').toLowerCase();
            const inputName = (input.name || '').toLowerCase();

            if (
                inputId.includes('avatar') || 
                inputId.includes('perfil') || 
                inputId.includes('monstro') || 
                inputId.includes('m-foto') || 
                inputId.includes('personagem') ||
                inputName.includes('avatar') ||
                inputName.includes('perfil') ||
                inputName.includes('foto')
            ) {
                aspect = 1; // 1:1 Quadrado para Avatares/Fotos
            } else if (
                inputId.includes('capa') || 
                inputId.includes('sistema') || 
                inputId.includes('background') ||
                inputName.includes('capa') ||
                inputName.includes('sistema')
            ) {
                aspect = 16 / 9; // Widescreen para Capas/Backgrounds
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                const img = document.getElementById('table-cropper-image');
                img.src = event.target.result;

                this.openModal(aspect);
            };
            reader.readAsDataURL(file);
        },

        openModal(aspectRatio) {
            const overlay = document.getElementById('table-cropper-overlay');
            overlay.classList.add('active');

            // Certifica-se de que a biblioteca Cropper.js está pronta
            const checkAndInit = () => {
                if (typeof Cropper !== 'undefined') {
                    const img = document.getElementById('table-cropper-image');
                    if (this.cropperInstance) {
                        this.cropperInstance.destroy();
                    }
                    this.cropperInstance = new Cropper(img, {
                        aspectRatio: aspectRatio,
                        viewMode: 1,
                        background: false,
                        autoCropArea: 0.9,
                        responsive: true,
                        restore: false
                    });
                } else {
                    setTimeout(checkAndInit, 100);
                }
            };
            checkAndInit();
        },

        closeModal() {
            const overlay = document.getElementById('table-cropper-overlay');
            overlay.classList.remove('active');
            if (this.cropperInstance) {
                this.cropperInstance.destroy();
                this.cropperInstance = null;
            }
            if (this.activeInput) {
                // Se cancelou, reseta o valor para disparar change novamente na mesma imagem se desejado
                this.activeInput.value = '';
            }
        },

        confirmCrop() {
            if (!this.cropperInstance || !this.activeInput) return;

            // Obter canvas recortado
            const canvas = this.cropperInstance.getCroppedCanvas({
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });

            if (!canvas) {
                this.closeModal();
                return;
            }

            // Converter canvas em blob de arquivo
            canvas.toBlob((blob) => {
                if (!blob) {
                    this.closeModal();
                    return;
                }

                // Criar arquivo a partir do blob
                const croppedFile = new File([blob], this.originalFile.name, {
                    type: this.originalFile.type || 'image/jpeg',
                    lastModified: Date.now()
                });

                // Atualizar o input file original com o arquivo cortado
                try {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(croppedFile);
                    
                    this.activeInput.dataset.croppedState = 'true'; // Previne recursão
                    this.activeInput.files = dataTransfer.files;

                    // Dispara evento de change no input original para que o frontend da página processe o novo arquivo cortado
                    const changeEvent = new Event('change', { bubbles: true });
                    this.activeInput.dispatchEvent(changeEvent);
                } catch (err) {
                    console.error("Erro ao transferir arquivo recortado:", err);
                }

                this.closeModal();
            }, this.originalFile.type || 'image/jpeg', 0.92);
        }
    };

    // Inicializa
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        window.TableImageCropper.init();
    } else {
        document.addEventListener('DOMContentLoaded', () => window.TableImageCropper.init());
    }
})();
