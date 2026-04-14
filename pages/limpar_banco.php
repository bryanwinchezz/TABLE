<?php
require_once '../app/config/database.php';

try {
    $conn = Database::getConexao();
    
    echo "<h2>🧨 Limpando Banco de Dados...</h2>";

    // Desativa chaves estrangeiras para permitir TRUNCATE
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tabelas = [
        'tb_usuario', 'tb_personagem', 'tb_campanha', 'tb_campanha_usuario', 
        'tb_campanha_personagem', 'tb_combate', 'tb_monstro', 'tb_combate_monstro', 
        'tb_sistema', 'tb_pericia', 'tb_personagem_pericia', 'tb_atributo', 
        'tb_personagem_atributo', 'tb_monstro_pericia', 'tb_monstro_atributo', 
        'tb_classe', 'tb_personagem_classe', 'tb_habilidade', 
        'tb_habilidade_personagem', 'tb_rolagem_dado', 'tb_item', 
        'tb_personagem_item', 'tb_origem', 'tb_personagem_origem', 
        'tb_sessao', 'tb_convite_campanha'
    ];

    foreach ($tabelas as $tabela) {
        // Verifica se a tabela existe antes de tentar limpar
        $check = $conn->query("SHOW TABLES LIKE '$tabela'");
        if ($check->rowCount() > 0) {
            $conn->exec("TRUNCATE TABLE $tabela");
            echo "✅ Tabela '$tabela' limpa.<br>";
        } else {
            echo "ℹ️ Tabela '$tabela' não encontrada (pulando).<br>";
        }
    }

    // Reativa chaves estrangeiras
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<br><b>BANCO ZERADO COM SUCESSO!</b>";
    echo "<br><a href='cadastro.php'>Ir para novo Cadastro</a>";

} catch (PDOException $e) {
    die("❌ Erro ao limpar banco: " . $e->getMessage());
}
?>
