// js/auth-simulado.js
document.addEventListener('DOMContentLoaded', () => {
    const formCadastro = document.getElementById('form-cadastro');
    const formLogin = document.getElementById('form-login');

    // Simulação de Cadastro
    if (formCadastro) {
        formCadastro.addEventListener('submit', (e) => {
            e.preventDefault();
            const nome = document.getElementById('nome').value;
            const email = document.getElementById('email').value;
            const senha = document.getElementById('senha').value;

            // Define o cargo baseado no nome para facilitar seus testes
            const cargo = nome.toLowerCase().includes('mestre') ? 'mestre' : 'jogador';

            const usuario = { nome, email, senha, cargo };
            localStorage.setItem('table_usuario_db', JSON.stringify(usuario));

            alert('Cadastro simulado com sucesso! Redirecionando para login...');
            window.location.href = 'login.html';
        });
    }

    // Simulação de Login
    if (formLogin) {
        formLogin.addEventListener('submit', (e) => {
            e.preventDefault();
            const nomeLog = document.getElementById('nome').value;
            const senhaLog = document.getElementById('senha-login').value;

            const db = JSON.parse(localStorage.getItem('table_usuario_db'));

            if (db && (db.nome === nomeLog || db.email === nomeLog) && db.senha === senhaLog) {
                // Cria a "sessão"
                localStorage.setItem('table_sessao_ativa', JSON.stringify(db));
                window.location.href = 'perfil.html';
            } else {
                alert('Credenciais inválidas! Tente novamente.');
            }
        });
    }
});