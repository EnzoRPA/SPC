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
        // Environment variables or defaults
        // Prioritize $_ENV for Vercel/Serverless
        $this->driver = $_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: 'mysql';
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'spc_control';
        $this->username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
        $this->port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: ($this->driver === 'pgsql' ? '5432' : '3306');

        try {
            $dsn = "{$this->driver}:host={$this->host};port={$this->port};dbname={$this->db_name}";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            if ($this->driver === 'mysql') {
                $this->conn->exec("set names utf8");
            }
            
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // DEBUG: Show actual connection params (hide password)
            $debugParams = "Driver: {$this->driver}, Host: {$this->host}, DB: {$this->db_name}, User: {$this->username}";
            $envVars = "ENV keys: " . implode(', ', array_keys($_ENV));
            
            // Allow this error to be seen
            die("Connection error: " . $exception->getMessage() . "<br>Params: $debugParams<br>Env Check: $envVars");
        }

        return $this->conn;
    }
    
    public function getDriver() {
        return $this->driver ?: (getenv('DB_CONNECTION') ?: 'mysql');
    }
}
