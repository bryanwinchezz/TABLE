document.addEventListener('DOMContentLoaded', () => {

    /* ==========================================================
        1. LÓGICA DE MOSTRAR/ESCONDER SENHA
    ========================================================== */
    document.querySelectorAll('.alternar-senha').forEach(icone => {
        icone.addEventListener('click', () => {
            const wrapper = icone.closest('.senha-wrapper');
            if (!wrapper) return;

            const input = wrapper.querySelector('input');
            if (!input) return;

            const tipoAtual = input.getAttribute('type');
            input.setAttribute('type', tipoAtual === 'password' ? 'text' : 'password');
            icone.classList.toggle('fa-eye');
            icone.classList.toggle('fa-eye-slash');
        });
    });

    /* ==========================================================
        2. VALIDAÇÃO DE SENHA EM TEMPO REAL
    ========================================================== */
    const configurarValidacaoSenha = (idFormulario, idInputSenha, idSpanErro, idBotaoSubmit) => {
        const formulario = document.getElementById(idFormulario);
        if (!formulario) return;

        const inputSenha = document.getElementById(idInputSenha);
        const spanErro = document.getElementById(idSpanErro);
        const botaoSubmit = document.getElementById(idBotaoSubmit);

        if (inputSenha && spanErro && botaoSubmit) {
            inputSenha.addEventListener('input', () => {
                const senha = inputSenha.value;
                const temErro = senha.length > 0 && senha.length < 6;

                spanErro.textContent = temErro ? 'A senha deve ter pelo menos 6 caracteres.' : '';
                botaoSubmit.disabled = temErro;
                botaoSubmit.style.opacity = temErro ? '0.5' : '1';
                botaoSubmit.style.cursor = temErro ? 'not-allowed' : 'pointer';
            });
        }
    };

    configurarValidacaoSenha('form-cadastro', 'senha', 'senha-cadastro-erro-msg', 'cadastro-submit-btn');
    configurarValidacaoSenha('form-login', 'senha-login', 'senha-login-erro-msg', 'login-submit-btn');

    /* ==========================================================
        3. LÓGICA DO CARROSSEL (100% À PROVA DE BUGS)
    ========================================================== */
    const trilho = document.querySelector('.carrossel-trilho');

    if (trilho) {
        const slides = Array.from(trilho.children);
        const btnProximo = document.querySelector('.btn-proximo');
        const btnAnterior = document.querySelector('.btn-anterior');

        const moverSlide = (slideAtual, slideAlvo, indexAlvo) => {
            trilho.style.transform = `translateX(-${indexAlvo * 100}%)`;
            slideAtual.classList.remove('slide-atual');
            slideAlvo.classList.add('slide-atual');
        }

        if (btnProximo) {
            btnProximo.addEventListener('click', () => {
                const slideAtual = trilho.querySelector('.slide-atual');
                let proximoSlide = slideAtual.nextElementSibling;
                let proximoIndex = slides.findIndex(slide => slide === proximoSlide);

                if (!proximoSlide) {
                    proximoSlide = slides[0];
                    proximoIndex = 0;
                }
                moverSlide(slideAtual, proximoSlide, proximoIndex);
            });
        }

        if (btnAnterior) {
            btnAnterior.addEventListener('click', () => {
                const slideAtual = trilho.querySelector('.slide-atual');
                let slideAnterior = slideAtual.previousElementSibling;
                let indexAnterior = slides.findIndex(slide => slide === slideAnterior);

                if (!slideAnterior) {
                    slideAnterior = slides[slides.length - 1];
                    indexAnterior = slides.length - 1;
                }
                moverSlide(slideAtual, slideAnterior, indexAnterior);
            });
        }
    }
});