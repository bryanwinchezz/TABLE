<?php
/**
 * ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
 * CONFIGURAÇÃO E CONEXÃO COM O BANCO DE DADOS (PDO)
 * ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
 * Classe de Conexão com Padrão Singleton - TABLE RPG
 * Desenvolvida de forma limpa, protegida e autossuficiente (Portável)
 * ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['usuario']['foto'])) {
    $_SESSION['usuario']['foto'] = str_replace('avatar1.png', 'avatar.png', $_SESSION['usuario']['foto']);
}

class Database {
    private static ?PDO $instancia = null;
    
    private function __construct() {}
    
    /**
     * Retorna uma única instância da conexão PDO.
     * Implementa autodetecção inteligente para XAMPP local, Docker e Produção.
     */
    public static function getConexao(): PDO {
        if (self::$instancia === null) {
            $host = 'localhost';
            $dbname = 'db_table';
            $usuario = 'root';
            $charset = 'utf8mb4';
            
            $ports = [3306, 3307, 3308];
            $senhas = ['root', ''];
            
            $conexaoSucesso = false;
            $ultimoErro = null;
            
            foreach ($ports as $port) {
                foreach ($senhas as $senha) {
                    try {
                        $dsn = "mysql:host={$host};dbname={$dbname};port={$port};charset={$charset}";
                        self::$instancia = new PDO($dsn, $usuario, $senha, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                            PDO::ATTR_TIMEOUT => 2
                        ]);
                        $conexaoSucesso = true;
                        
                        // Executa Migration Automática Silenciosa de Tabelas e Colunas de Planos
                        try {
                            // 1. Criar tabela tb_chave_ativacao se não existir
                            self::$instancia->exec("
                                CREATE TABLE IF NOT EXISTS tb_chave_ativacao (
                                    id_chave INT AUTO_INCREMENT PRIMARY KEY,
                                    ds_codigo VARCHAR(50) UNIQUE NOT NULL,
                                    tp_plano VARCHAR(50) NOT NULL,
                                    fl_usada TINYINT(1) DEFAULT 0 NOT NULL,
                                    id_usuario_comprador INT NOT NULL,
                                    id_usuario_ativador INT NULL,
                                    mp_assinatura_id VARCHAR(100) NULL,
                                    dt_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    dt_ativacao DATETIME NULL
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                            ");

                            $chkAssin = self::$instancia->query("SHOW COLUMNS FROM tb_chave_ativacao LIKE 'mp_assinatura_id'");
                            if ($chkAssin->rowCount() === 0) {
                                self::$instancia->exec("ALTER TABLE tb_chave_ativacao ADD COLUMN mp_assinatura_id VARCHAR(100) NULL");
                            }

                            // 2. Adicionar flags de planos na tabela tb_usuario se não existirem
                            $chkMapas = self::$instancia->query("SHOW COLUMNS FROM tb_usuario LIKE 'fl_plano_mapas'");
                            if ($chkMapas->rowCount() === 0) {
                                self::$instancia->exec("ALTER TABLE tb_usuario ADD COLUMN fl_plano_mapas TINYINT(1) DEFAULT 0 NOT NULL");
                            }
                            
                            $chkSist = self::$instancia->query("SHOW COLUMNS FROM tb_usuario LIKE 'fl_plano_sistemas'");
                            if ($chkSist->rowCount() === 0) {
                                self::$instancia->exec("ALTER TABLE tb_usuario ADD COLUMN fl_plano_sistemas TINYINT(1) DEFAULT 0 NOT NULL");
                            }
                            
                            $chkComp = self::$instancia->query("SHOW COLUMNS FROM tb_usuario LIKE 'fl_plano_completo'");
                            if ($chkComp->rowCount() === 0) {
                                self::$instancia->exec("ALTER TABLE tb_usuario ADD COLUMN fl_plano_completo TINYINT(1) DEFAULT 0 NOT NULL");
                            }
                        } catch (Exception $migError) {
                            // Silencioso
                        }

                        break 2;
                    } catch (PDOException $e) {
                        $ultimoErro = $e;
                    }
                }
            }
            
            if (!$conexaoSucesso) {
                self::exibirErroConexao($ultimoErro);
            }
        }
        
        return self::$instancia;
    }

    /**
     * Exibe um erro amigável e seguro de conexão, evitando expor credenciais sensíveis.
     */
    private static function exibirErroConexao(PDOException $e): void {
        header("HTTP/1.1 500 Internal Server Error");
        echo "<!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <title>Erro de Banco de Dados | TABLE</title>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Montserrat:wght@400;600;800&display=swap');
                body { background: #050202; color: #fff; font-family: 'Montserrat', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                .card { background: rgba(15, 5, 5, 0.95); border: 2px solid rgba(255, 50, 50, 0.2); border-radius: 20px; padding: 40px; max-width: 550px; box-shadow: 0 0 40px rgba(255, 50, 50, 0.15); text-align: center; }
                h1 { font-family: 'Cinzel', serif; color: #ff3232; font-size: 2rem; margin-top: 0; text-shadow: 0 0 10px rgba(255, 50, 50, 0.4); letter-spacing: 2px; }
                p { color: #ccc; line-height: 1.6; font-size: 0.95rem; }
                .details { background: rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; font-family: monospace; font-size: 0.8rem; color: #ff4d4d; margin-top: 25px; text-align: left; overflow-x: auto; max-height: 120px; }
            </style>
        </head>
        <body>
            <div class='card'>
                <h1>Erro de Conexão Paranormal</h1>
                <p>Não foi possível estabelecer contato com a base de dados do sistema TABLE. Por favor, certifique-se de que o seu servidor MySQL está ativo no XAMPP e que o banco de dados <strong>db_table</strong> foi importado corretamente.</p>
                <div class='details'>Código do Erro: " . $e->getCode() . "<br>Mensagem: " . htmlspecialchars($e->getMessage()) . "</div>
            </div>
        </body>
        </html>";
        exit;
    }
}
?>
