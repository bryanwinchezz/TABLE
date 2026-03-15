// js/perfil-render.js
document.addEventListener('DOMContentLoaded', () => {
    const sessao = JSON.parse(localStorage.getItem('table_sessao_ativa'));

    if (!sessao) {
        alert('Acesso negado. Faça login primeiro.');
        window.location.href = 'login.html';
        return;
    }

    // Preenche os dados na Navbar e no Perfil
    document.getElementById('nav-nome-usuario').textContent = sessao.nome;
    document.getElementById('display-nome-usuario').textContent = sessao.nome;

    // Lógica do Layout
    const grid = document.getElementById('grid-paineis');
    const painelSistemas = document.getElementById('painel-sistemas');
    const botoesCriarMestre = document.querySelectorAll('.btn-criar-mestre');

    if (sessao.cargo === 'mestre') {
        grid.classList.add('grid-mestre');
        painelSistemas.style.display = 'flex';
        botoesCriarMestre.forEach(btn => btn.style.display = 'block');
    } else {
        grid.classList.add('grid-jogador');
    }

    // Logout agora é clicando no perfil inteiro da navbar
    document.getElementById('btn-logout').addEventListener('click', () => {
        if (confirm("Deseja realmente sair?")) {
            localStorage.removeItem('table_sessao_ativa');
            window.location.href = 'login.html';
        }
    });
});