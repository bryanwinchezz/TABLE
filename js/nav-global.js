// js/nav-global.js
document.addEventListener('DOMContentLoaded', () => {
    // O sistema agora utiliza Sessões PHP para gerenciar a navegação.
    // Este script cuida apenas de interações visuais globais se necessário.
    
    const menuLinks = document.querySelectorAll('nav ul li a');
    const currentPath = window.location.pathname.split('/').pop();

    menuLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('ativo');
        }
    });
});