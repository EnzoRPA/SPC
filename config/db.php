<?php

class Database {
    private $driver;
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    private function getEnvVar($key, $default = '') {
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?? $default;
    }

    public function getConnection() {
        $this->conn = null;
        
        // Environment variables or defaults
        $this->driver = $this->getEnvVar('DB_CONNECTION', 'mysql');
        $this->host = $this->getEnvVar('DB_HOST', 'localhost');
        $this->db_name = $this->getEnvVar('DB_NAME', 'spc_control');
        $this->username = $this->getEnvVar('DB_USER', 'root');
        $this->password = $this->getEnvVar('DB_PASSWORD', '');
        $this->port = $this->getEnvVar('DB_PORT', ($this->driver === 'pgsql' ? '5432' : '3306'));

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
