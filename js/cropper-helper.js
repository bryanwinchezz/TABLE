// TABLE | Cropper Helper
// Fornece modal de recorte de imagem premium e minimalista integrado ao Cropper.js

function abrirCropperModal(file, aspectRatio, callback) {
    // Fallback robusto caso a CDN do Cropper.js tenha sido bloqueada (ex: no Opera GX)
    if (typeof Cropper === 'undefined') {
        console.warn("Cropper não está definido no escopo global. Usando fallback de leitura simples.");
        const reader = new FileReader();
        reader.onload = (event) => {
            callback(file, event.target.result);
        };
        reader.readAsDataURL(file);
        return;
    }

    // 1. Injetar os estilos se não existirem
    if (!document.getElementById('cropper-helper-styles')) {
        const style = document.createElement('style');
        style.id = 'cropper-helper-styles';
        style.innerHTML = `
            .cropper-modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(10, 8, 16, 0.85);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                z-index: 999999;
                display: flex;
                justify-content: center;
                align-items: center;
                animation: cropperFadeIn 0.3s ease;
            }
            .cropper-container-box {
                background: rgba(22, 20, 30, 0.95);
                border: 1px solid rgba(157, 122, 255, 0.2);
                border-radius: 20px;
                width: 90%;
                max-width: 580px;
                display: flex;
                flex-direction: column;
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255,255,255,0.05);
                overflow: hidden;
                font-family: 'Montserrat', sans-serif;
                color: #fff;
                animation: cropperScaleUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            }
            .cropper-header {
                padding: 20px 24px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            }
            .cropper-header h3 {
                margin: 0;
                font-size: 1.2rem;
                font-weight: 700;
                letter-spacing: -0.5px;
                color: #fff;
            }
            .cropper-close-btn {
                background: none;
                border: none;
                color: rgba(255, 255, 255, 0.5);
                font-size: 1.2rem;
                cursor: pointer;
                transition: color 0.2s, transform 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 4px;
            }
            .cropper-close-btn:hover {
                color: #ff4a4a;
                transform: scale(1.1);
            }
            .cropper-body {
                padding: 24px;
                display: flex;
                justify-content: center;
                align-items: center;
                background: #0f0d15;
            }
            .cropper-wrapper {
                width: 100%;
                max-height: 380px;
                overflow: hidden;
                border-radius: 8px;
                border: 1px solid rgba(255, 255, 255, 0.05);
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .cropper-wrapper img {
                max-width: 100%;
                display: block;
            }
            .cropper-footer {
                padding: 20px 24px;
                display: flex;
                justify-content: flex-end;
                gap: 12px;
                border-top: 1px solid rgba(255, 255, 255, 0.05);
                background: rgba(18, 16, 24, 0.5);
            }
            .cropper-btn {
                padding: 12px 24px;
                border-radius: 10px;
                font-size: 0.9rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: none;
            }
            .cropper-btn-cancel {
                background: rgba(255, 255, 255, 0.05);
                color: rgba(255, 255, 255, 0.7);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            .cropper-btn-cancel:hover {
                background: rgba(255, 255, 255, 0.1);
                color: #fff;
            }
            .cropper-btn-confirm {
                background: linear-gradient(135deg, #7b4ff7 0%, #9d7aff 100%);
                color: #fff;
                box-shadow: 0 4px 15px rgba(123, 79, 247, 0.3);
            }
            .cropper-btn-confirm:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(123, 79, 247, 0.4);
            }
            .cropper-btn-confirm:active {
                transform: translateY(0);
            }
            @keyframes cropperFadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes cropperScaleUp {
                from { transform: scale(0.95); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }

    // 2. Criar a estrutura do modal
    const overlay = document.createElement('div');
    overlay.className = 'cropper-modal-overlay';
    
    const container = document.createElement('div');
    container.className = 'cropper-container-box';
    
    // Header
    const header = document.createElement('div');
    header.className = 'cropper-header';
    header.innerHTML = `
        <h3>Ajustar Imagem</h3>
        <button class="cropper-close-btn" title="Fechar">&times;</button>
    `;
    
    // Body
    const body = document.createElement('div');
    body.className = 'cropper-body';
    
    const wrapper = document.createElement('div');
    wrapper.className = 'cropper-wrapper';
    
    const image = document.createElement('img');
    wrapper.appendChild(image);
    body.appendChild(wrapper);
    
    // Footer
    const footer = document.createElement('div');
    footer.className = 'cropper-footer';
    
    const btnCancel = document.createElement('button');
    btnCancel.className = 'cropper-btn cropper-btn-cancel';
    btnCancel.innerText = 'Cancelar';
    
    const btnConfirm = document.createElement('button');
    btnConfirm.className = 'cropper-btn cropper-btn-confirm';
    btnConfirm.innerText = 'Confirmar Recorte';
    
    footer.appendChild(btnCancel);
    footer.appendChild(btnConfirm);
    
    container.appendChild(header);
    container.appendChild(body);
    container.appendChild(footer);
    overlay.appendChild(container);
    document.body.appendChild(overlay);

    let cropper = null;
    const imageUrl = URL.createObjectURL(file);
    image.src = imageUrl;

    // Inicializar o Cropper após a imagem carregar
    image.onload = function() {
        cropper = new Cropper(image, {
            aspectRatio: aspectRatio,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.9,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            background: false
        });
    };

    // Função de limpeza/fechamento
    function fecharModal() {
        if (cropper) {
            cropper.destroy();
        }
        URL.revokeObjectURL(imageUrl);
        overlay.remove();
    }

    // Ações de clique
    header.querySelector('.cropper-close-btn').addEventListener('click', fecharModal);
    btnCancel.addEventListener('click', fecharModal);
    
    btnConfirm.addEventListener('click', function() {
        if (!cropper) return;
        
        // Obter o canvas cortado com boa qualidade
        const canvas = cropper.getCroppedCanvas({
            maxWidth: 1200,
            maxHeight: 1200,
            fillColor: '#000', // Preenchimento preto caso haja transparência não suportada
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });
        
        if (canvas) {
            canvas.toBlob(function(blob) {
                if (blob) {
                    // Converter para base64 para uso imediato
                    const reader = new FileReader();
                    reader.onloadend = function() {
                        callback(blob, reader.result);
                    };
                    reader.readAsDataURL(blob);
                }
                fecharModal();
            }, 'image/jpeg', 0.9);
        } else {
            fecharModal();
        }
    });
}
