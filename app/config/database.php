<?php
/**
 * ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
 * CONFIGURAÇÃO E CONEXÃO COM O BANCO DE DADOS (PDO)
 * ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
 * Classe de Conexão com Padrão Singleton - TABLE RPG
 * Desenvolvida de forma limpa, protegida e autossuficiente (Portável)
 * ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
 */

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
            $port = 3306;
            $charset = 'utf8mb4';
            
            // Senha primária configurada (ex: MAMP/Docker/Ambiente específico)
            $senhaPrimaria = 'root';
            // Senha secundária (ex: XAMPP local padrão)
            $senhaSecundaria = '';
            
            $dsn = "mysql:host={$host};dbname={$dbname};port={$port};charset={$charset}";
            
            try {
                // Tenta conectar usando a senha primária ('root')
                self::$instancia = new PDO($dsn, $usuario, $senhaPrimaria, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // Se falhar devido a erro de credenciais (Código de erro MySQL 1045)
                if ($e->getCode() == 1045 || strpos($e->getMessage(), 'Access denied') !== false) {
                    try {
                        // Tenta com a senha secundária (vazia - padrão do XAMPP)
                        self::$instancia = new PDO($dsn, $usuario, $senhaSecundaria, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                        ]);
                    } catch (PDOException $ex) {
                        self::exibirErroConexao($ex);
                    }
                } else {
                    self::exibirErroConexao($e);
                }
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