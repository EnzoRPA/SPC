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
            
            // 1. PRIMARY CONFIGURATION: Try DATABASE_URL first (Single Source of Truth)
            $dbUrl = getenv('DATABASE_URL');
            $config = [];

            if ($dbUrl) {
                // Parse DATABASE_URL (Handles special chars in password via regex)
                if (preg_match('|postgres(?:ql)?://([^:]+):([^@]+)@([^:/]+)(?::(\d+))?/(\w+)|', $dbUrl, $matches)) {
                    $config['username'] = $matches[1];
                    $config['password'] = $matches[2];
                    $config['host']     = $matches[3];
                    $config['port']     = !empty($matches[4]) ? $matches[4] : '5432';
                    $config['dbname']   = $matches[5];
                } else {
                    $parsed = parse_url($dbUrl);
                    if ($parsed && isset($parsed['host'])) {
                        $config['host']     = $parsed['host'];
                        $config['dbname']   = ltrim($parsed['path'] ?? '/postgres', '/');
                        $config['username'] = $parsed['user'] ?? 'postgres';
                        $config['password'] = $parsed['pass'] ?? '';
                        $config['port']     = $parsed['port'] ?? '5432';
                    }
                }
            }
            
            // 2. FALLBACK: Individual Env Vars (if DATABASE_URL is missing/failed)
            if (empty($config)) {
                $config['host']     = getenv('DB_HOST') ?: 'db.ogiwoavudsjlwfkvndgc.supabase.co';
                $config['dbname']   = getenv('DB_NAME') ?: 'postgres';
                $config['username'] = getenv('DB_USER') ?: 'postgres';
                $config['password'] = getenv('DB_PASSWORD') ?: 'Gaither!202020202';
                $config['port']     = getenv('DB_PORT') ?: '5432';
            }

            // Assign to class properties
            $this->host     = $config['host'];
            $this->db_name  = $config['dbname'];
            $this->username = $config['username'];
            $this->password = $config['password'];
            // Port will be overwritten below for Pooler
            
            // 3. EXTRACT PROJECT REF (Required for Supabase Pooler Endpoint)
            $projectRef = '';
            if (preg_match('/db\.([a-z0-9]+)\.supabase\.co/', $this->host, $matches)) {
                $projectRef = $matches[1];
            } else {
                $projectRef = 'ogiwoavudsjlwfkvndgc'; // Hardcoded Fallback
            }

            // 4. SUPABASE POOLER CONFIGURATION (Mandatory for Vercel PHP)
            // Force Port 6543, Clean Username, Add Endpoint Option via DSN.
            
            // A. Force Port 6543 (Pooler)
            $this->port = '6543';

            // B. Clean Username: Must be strictly 'postgres' (remove .suffix if present)
            if ($projectRef && strpos($this->username, $projectRef) !== false) {
                 $this->username = str_replace(".{$projectRef}", "", $this->username);
            }

             // C. Hostname Resolution Strategy (IPv6 bypass)
            // Vercel DNS resolves 'db.project.supabase.co' to IPv6 (AAAA), which fails.
            // We need an IPv4 address, BUT we must use a Hostname for SNI (not IP).
            // Solution: Resolve the CNAME (e.g. aws-0-sa-east-1.pooler.supabase.com).
            // This CNAME resolves to IPv4 and is a valid SSL host.
            $original_host_for_log = $this->host;
            
            $cname = dns_get_record($this->host, DNS_CNAME);
            if ($cname && isset($cname[0]['target'])) {
                $target = $cname[0]['target'];
                // Verify the target has an IPv4 (A) record
                $a_records = dns_get_record($target, DNS_A);
                if ($a_records) {
                    $this->host = $target; // Swap to CNAME target (e.g. aws-0-sa-east-1...)
                }
            }

            try {
                // D. Connect with Endpoint Option
                $dsn = "{$this->driver}:host={$this->host};port={$this->port};dbname={$this->db_name};sslmode=require";
                
                if ($projectRef) {
                    $dsn .= ";options='endpoint={$projectRef}'";
                }
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => true, 
                ];

                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                $this->conn->exec("SET NAMES 'UTF8'");
                
            } catch(PDOException $exception) {
                // Debug verbose for Production
                $debugParams = "Host: {$original_host_for_log} -> Used: {$this->host} | Port: {$this->port} | User: {$this->username}";
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
