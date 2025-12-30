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
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? false;
        if ($val === false) {
             $val = getenv($key);
        }
        return ($val !== false) ? $val : $default;
    }

    public function getConnection() {
        $this->conn = null;
        
        // Hardcoded for Vercel - Standard Supabase Config
        $this->driver = 'pgsql';
        $this->host = 'db.ogiwoavudsjlwfkvndgc.supabase.co';
        $this->db_name = 'postgres';
        $this->username = 'postgres';
        $this->password = 'G4a1ther2020#';
        $this->port = '5432';

        try {
            // Extract Project Ref for Endpoint ID
            $ref = explode('.', $this->host)[1] ?? 'ogiwoavudsjlwfkvndgc';
            
            // DSN with proper SSL and Endpoint options
            $dsn = "{$this->driver}:host={$this->host};port={$this->port};dbname={$this->db_name};sslmode=require;options='endpoint={$ref}'";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            if ($this->driver === 'mysql') {
                $this->conn->exec("set names utf8");
            }
            
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // DEBUG: Show actual connection params (hide password)
            $debugParams = "Driver: {$this->driver}, Host: {$this->host}, DB: {$this->db_name}, User: {$this->username}";
            $envKeys = implode(', ', array_keys($_ENV));
            $serverKeys = implode(', ', array_keys($_SERVER));
            $projName = $_ENV['VERCEL_PROJECT_NAME'] ?? $_SERVER['VERCEL_PROJECT_NAME'] ?? 'Unknown';
            
            // Allow this error to be seen
            die("Connection error: " . $exception->getMessage() . "<br>Project: $projName<br>Params: $debugParams<br>ENV keys: $envKeys<br>SERVER keys: $serverKeys");
        }

        return $this->conn;
    }
    
    public function getDriver() {
        return $this->driver ?: (getenv('DB_CONNECTION') ?: 'mysql');
    }
}
