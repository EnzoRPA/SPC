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
        // Check for VERCEL or specific Supabase Env to force Production
        $isVercel = getenv('VERCEL') || isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']);
        
        // If it is NOT Vercel, assume it is Local (this allows 192.168.x.x, localhost, etc.)
        $isLocal = !$isVercel;

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
                // Use error_log instead of echo to prevent "Headers already sent"
                error_log("Local Connection error: " . $exception->getMessage());
                // Do not die/echo here, let the caller handle null connection or throw
            }

        } else {
            // --- VERCEL PRODUCTION (Supabase/PostgreSQL) ---
            $this->driver = 'pgsql';
            $original_host = getenv('DB_HOST') ?: 'db.ogiwoavudsjlwfkvndgc.supabase.co';
            $this->db_name = getenv('DB_NAME') ?: 'postgres';
            $this->username = getenv('DB_USER') ?: 'postgres';
            // Use env var or fallback
            $this->password = getenv('DB_PASSWORD') ?: 'G4a1ther2020#';
            // Supabase Pooler Port is 6543 (preferred for Serverless)
            $this->port = getenv('DB_PORT') ?: '6543';

            // FORCE IPv4: Vercel defaults to IPv6 which fails. We must find an IPv4 address.
            $this->host = $original_host; // Default fallback
            
            // Try resolving A records (IPv4) explicitly
            try {
                $dns = dns_get_record($original_host, DNS_A);
                if ($dns && isset($dns[0]['ip'])) {
                    $this->host = $dns[0]['ip'];
                    // error_log("Resolved IPv4 for Supabase: " . $this->host);
                } else {
                    // Fallback to gethostbynamel
                    $ips = gethostbynamel($original_host);
                    if ($ips && isset($ips[0])) {
                         $this->host = $ips[0];
                    }
                }
            } catch (Exception $e) {
                error_log("DNS Resolution failed: " . $e->getMessage());
            }

            try {
                // Connect
                // NOTE: For Supabase Transaction Pool (Port 6543), we might need `pgbouncer=true` in options or similar, 
                // but usually just standard connection works if Prepared Statements are handled correctly.
                $dsn = "{$this->driver}:host={$this->host};port={$this->port};dbname={$this->db_name};sslmode=require";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // IMPORTANT for Supabase Transaction Pooler:
                    // Disable server-side prepared statements to avoid "ERROR: prepared statement ... does not exist"
                    PDO::ATTR_EMULATE_PREPARES => true, 
                ];

                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                
                // Set charset just in case, though pgsql usually handles it in DSN or default
                $this->conn->exec("SET NAMES 'UTF8'");
                
            } catch(PDOException $exception) {
                // Debug verbose for Production
                $debugParams = "Host: {$original_host} -> Resolved: {$this->host} | Port: {$this->port}";
                error_log("Production Connection error: " . $exception->getMessage() . " [Params: $debugParams]");
                
                // FORCE OUTPUT ERROR TO SCREEN FOR DEBUGGING
                http_response_code(500);
                echo "<h1>Erro de Conexão (Vercel)</h1>";
                echo "<p><strong>Erro:</strong> " . $exception->getMessage() . "</p>";
                echo "<p><strong>Detalhes:</strong> " . $debugParams . "</p>";
                die();
            }
        }

        return $this->conn;
    }
    
    public function getDriver() {
        return $this->driver ?: (getenv('DB_CONNECTION') ?: 'mysql');
    }
}
