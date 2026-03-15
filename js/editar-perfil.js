// js/editar-perfil.js
document.addEventListener('DOMContentLoaded', () => {
    let sessao = JSON.parse(localStorage.getItem('table_sessao_ativa'));

    if (!sessao) {
        window.location.href = 'login.html';
        return;
    }

    // 1. Função para manter a tela sempre atualizada
    const atualizarTextosTela = () => {
        document.getElementById('exibicao-nome').textContent = sessao.nome;
        document.getElementById('exibicao-email').textContent = sessao.email || "sem_email_cadastrado@table.com";
        
        document.getElementById('input-nome').value = sessao.nome;
        document.getElementById('input-email').value = sessao.email || "";
    };
    
    atualizarTextosTela();

    // 2. Lógica do Cargo (Ser Mestre)
    const btnCargo = document.getElementById('btn-mudar-cargo');
    const textoBotaoCargo = document.getElementById('texto-botao-cargo');
    const iconeBotaoCargo = btnCargo.querySelector('i');

    const atualizarInterfaceCargo = () => {
        if (sessao.cargo === 'mestre') {
            textoBotaoCargo.textContent = 'Deixar de ser Mestre';
            iconeBotaoCargo.className = 'fas fa-user';
            btnCargo.style.backgroundColor = '#6c757d'; // Fica cinza
        } else {
            textoBotaoCargo.textContent = 'Seja mestre';
            iconeBotaoCargo.className = 'fas fa-book';
            btnCargo.style.backgroundColor = '#4a2a85'; // Fica roxo
        }
    };

    atualizarInterfaceCargo();

    btnCargo.addEventListener('click', () => {
        sessao.cargo = sessao.cargo === 'mestre' ? 'jogador' : 'mestre';
        localStorage.setItem('table_sessao_ativa', JSON.stringify(sessao));

        let db = JSON.parse(localStorage.getItem('table_usuario_db'));
        if (db) { 
            db.cargo = sessao.cargo;
            localStorage.setItem('table_usuario_db', JSON.stringify(db));
        }
        atualizarInterfaceCargo();
        alert(`Status atualizado! Agora você é um ${sessao.cargo.toUpperCase()}.`);
    });

    // 3. Salvar Dados do Perfil
    document.getElementById('btn-salvar-dados').addEventListener('click', () => {
        const novoNome = document.getElementById('input-nome').value;
        const novoEmail = document.getElementById('input-email').value;

        if(novoNome.trim() === '' || novoEmail.trim() === '') {
            alert('Preencha todos os campos!');
            return;
        }

        // Atualiza a sessão
        sessao.nome = novoNome;
        sessao.email = novoEmail;
        localStorage.setItem('table_sessao_ativa', JSON.stringify(sessao));

        // Atualiza o Banco de Dados simulado
        let db = JSON.parse(localStorage.getItem('table_usuario_db'));
        if (db) {
            db.nome = novoNome;
            db.email = novoEmail;
            localStorage.setItem('table_usuario_db', JSON.stringify(db));
        }

        atualizarTextosTela();
        
        // Atualiza também o nome no Header para não precisar recarregar a página
        const nomeHeader = document.getElementById('nav-nome-usuario-global');
        if(nomeHeader) nomeHeader.textContent = novoNome;

        alert('Dados atualizados com sucesso!');
    });

    // 4. Botão Sair da Conta
    document.getElementById('btn-sair-conta').addEventListener('click', () => {
        if (confirm("Tem certeza que deseja sair?")) {
            localStorage.removeItem('table_sessao_ativa');
            window.location.href = 'login.html';
        }
    });
    
    // 5. Botão Excluir Conta (Simulação)
    document.getElementById('btn-excluir-conta').addEventListener('click', () => {
        if (confirm("ATENÇÃO: Deseja realmente excluir sua conta? Esta ação não pode ser desfeita.")) {
            localStorage.removeItem('table_sessao_ativa');
            localStorage.removeItem('table_usuario_db');
            alert('Conta excluída com sucesso.');
            window.location.href = 'index.html';
        }
    });
});