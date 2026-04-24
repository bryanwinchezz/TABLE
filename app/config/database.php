<?php
// database.php

class Database {
    private static ?PDO $instancia = null;
    
    private function __construct() {}
    
    public static function getConexao(): PDO {
        if (self::$instancia === null) {
            $host = 'localhost';
            $dbname = 'db_table';
            $usuario = 'root';
            $senha = 'root';
            $port = 3306;
            $charset = 'utf8mb4';
            
            $dsn = "mysql:host={$host};dbname={$dbname};port={$port};charset={$charset}";
            
            try {
                self::$instancia = new PDO($dsn, $usuario, $senha, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                die("Erro ao conectar ao banco de dados: " . $e->getMessage());
            }
        }
        
        return self::$instancia;
    }
}
?>