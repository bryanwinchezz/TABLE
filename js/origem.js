function toggleOrigem(header) {
    // Toggle a classe active no header
    header.classList.toggle('active');
    
    // Encontra o conteúdo (próximo elemento após o header)
    const content = header.nextElementSibling;
    
    // Toggle a classe show no conteúdo
    content.classList.toggle('show');
}

// Se você quiser que uma origem específica comece aberta
document.addEventListener('DOMContentLoaded', function() {
    // Exemplo: abrir a primeira origem automaticamente
    const primeiroHeader = document.querySelector('.origem-header');
    if (primeiroHeader) {
        toggleOrigem(primeiroHeader);
    }
});