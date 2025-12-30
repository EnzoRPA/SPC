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

        // DETECT ENVIRONMENT to restore Local Access
        $isLocal = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1' || php_sapi_name() === 'cli');

        if ($isLocal) {
            // --- LOCAL ENVIRONMENT (MySQL/XAMPP) ---
            $this->driver = $this->getEnvVar('DB_CONNECTION', 'mysql');
            $this->host = $this->getEnvVar('DB_HOST', 'localhost');
            $this->db_name = $this->getEnvVar('DB_NAME', 'spc_control');
            $this->username = $this->getEnvVar('DB_USER', 'root');
            $this->password = $this->getEnvVar('DB_PASSWORD', '');
            $this->port = $this->getEnvVar('DB_PORT', '3306');
            
            try {
                $dsn = "{$this->driver}:host={$this->host};port={$this->port};dbname={$this->db_name}";
                $this->conn = new PDO($dsn, $this->username, $this->password);
                if ($this->driver === 'mysql') {
                    $this->conn->exec("set names utf8");
                }
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $exception) {
                echo "Local Connection error: " . $exception->getMessage();
            }

        } else {
            // --- VERCEL PRODUCTION (Supabase/PostgreSQL) ---
            $this->driver = 'pgsql';
            $original_host = 'db.ogiwoavudsjlwfkvndgc.supabase.co';
            $this->db_name = 'postgres';
            $this->username = 'postgres';
            $this->password = 'G4a1ther2020#';
            $this->port = '5432';

            // FORCE IPv4: Vercel defaults to IPv6 which fails. We must find an IPv4 address.
            $this->host = $original_host; // Default fallback
            $ips = gethostbynamel($original_host);
            if ($ips) {
                foreach ($ips as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $this->host = $ip;
                        break;
                    }
                }
            }

            try {
                // Extract Project Ref for Endpoint ID (SNI Equivalent)
                $ref = 'ogiwoavudsjlwfkvndgc';
                
                // Connect to IP, but send 'endpoint' option so Supabase knows who we are
                $dsn = "{$this->driver}:host={$this->host};port={$this->port};dbname={$this->db_name};sslmode=require;options='endpoint={$ref}'";
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->exec("SET NAMES 'UTF8'");
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $exception) {
                // Debug verbose for Production
                $debugParams = "Host: {$original_host} / {$this->host}";
                http_response_code(500);
                die("Production Connection error: " . $exception->getMessage() . "<br>Params: $debugParams");
            }
        }

        return $this->conn;
    }
    
    public function getDriver() {
        return $this->driver ?: (getenv('DB_CONNECTION') ?: 'mysql');
    }
}
