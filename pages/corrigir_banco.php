<?php
require_once '../app/config/database.php';

try {
    $conn = Database::getConexao();
    
    echo "<h2>🛠️ Corrigindo Banco de Dados...</h2>";

    // 1. Adicionar nm_exibicao se não existir
    $check_exib = $conn->query("SHOW COLUMNS FROM tb_usuario LIKE 'nm_exibicao'");
    if ($check_exib->rowCount() == 0) {
        $conn->exec("ALTER TABLE tb_usuario ADD COLUMN nm_exibicao VARCHAR(70) DEFAULT NULL AFTER id_usuario");
        echo "✅ Coluna 'nm_exibicao' adicionada!<br>";
    } else {
        echo "ℹ️ Coluna 'nm_exibicao' já existe.<br>";
    }

    // 2. Adicionar fl_ativo se não existir
    $check_ativo = $conn->query("SHOW COLUMNS FROM tb_usuario LIKE 'fl_ativo'");
    if ($check_ativo->rowCount() == 0) {
        $conn->exec("ALTER TABLE tb_usuario ADD COLUMN fl_ativo TINYINT(1) NOT NULL DEFAULT 1");
        echo "✅ Coluna 'fl_ativo' adicionada!<br>";
    } else {
        echo "ℹ️ Coluna 'fl_ativo' já existe.<br>";
    }

    // 2. Adicionar dt_nascimento se não existir
    $check_nasc = $conn->query("SHOW COLUMNS FROM tb_usuario LIKE 'dt_nascimento'");
    if ($check_nasc->rowCount() == 0) {
        $conn->exec("ALTER TABLE tb_usuario ADD COLUMN dt_nascimento DATE DEFAULT NULL");
        echo "✅ Coluna 'dt_nascimento' adicionada!<br>";
    } else {
        echo "ℹ️ Coluna 'dt_nascimento' já existe.<br>";
    }

    // 3. Adicionar tp_cargo se não existir
    $check_cargo = $conn->query("SHOW COLUMNS FROM tb_usuario LIKE 'tp_cargo'");
    if ($check_cargo->rowCount() == 0) {
        $conn->exec("ALTER TABLE tb_usuario ADD COLUMN tp_cargo ENUM('jogador','mestre','admin') NOT NULL DEFAULT 'jogador'");
        echo "✅ Coluna 'tp_cargo' adicionada!<br>";
    } else {
        echo "ℹ️ Coluna 'tp_cargo' já existe.<br>";
    }

    echo "<br><b>Concluído! Agora o login deve funcionar perfeitamente.</b>";
    echo "<br><a href='login.php'>Voltar para o Login</a>";

} catch (PDOException $e) {
    die("❌ Erro ao corrigir banco: " . $e->getMessage());
}
?>
