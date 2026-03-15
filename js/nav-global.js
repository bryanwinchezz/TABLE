// js/nav-global.js
document.addEventListener('DOMContentLoaded', () => {
    // 1. Puxa a sessão do Reator Arc (localStorage)
    const sessao = JSON.parse(localStorage.getItem('table_sessao_ativa'));

    // 2. Localiza os elementos no HTML
    const navDeslogado = document.getElementById('nav-deslogado');
    const navLogado = document.getElementById('nav-logado');
    const nomeUsuarioGlobal = document.getElementById('nav-nome-usuario-global');

    // 3. Se existir uma sessão, ajusta o que encontrar na tela
    if (sessao) {
        if (navDeslogado) navDeslogado.style.display = 'none'; // Esconde Login se existir
        if (navLogado) navLogado.style.display = 'flex'; // Mostra Perfil se existir
        if (nomeUsuarioGlobal) nomeUsuarioGlobal.textContent = sessao.nome; // Injeta o nome
    }
});