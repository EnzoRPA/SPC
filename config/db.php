<?php

class Database {
    private $driver;
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        // Environment variables or defaults
        $this->driver = getenv('DB_CONNECTION') ?: 'mysql';
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'spc_control';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
        $this->port = getenv('DB_PORT') ?: ($this->driver === 'pgsql' ? '5432' : '3306');

        try {
            $dsn = "{$this->driver}:host={$this->host};port={$this->port};dbname={$this->db_name}";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            if ($this->driver === 'mysql') {
                $this->conn->exec("set names utf8");
            }
            
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
    
    public function getDriver() {
        return $this->driver ?: (getenv('DB_CONNECTION') ?: 'mysql');
    }
}
