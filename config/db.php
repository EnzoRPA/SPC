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
            
            // Allow settings via individual Envs (default) or DATABASE_URL
            $original_host = getenv('DB_HOST') ?: 'db.ogiwoavudsjlwfkvndgc.supabase.co';
            $this->db_name = getenv('DB_NAME') ?: 'postgres';
            $this->username = getenv('DB_USER') ?: 'postgres';
            $this->password = getenv('DB_PASSWORD') ?: 'Gaither!202020202';
            $this->port = getenv('DB_PORT') ?: '5432';

            // Support DATABASE_URL from Vercel/Supabase Integ.
            // Format: postgresql://user:pass@host:port/dbname
            // Warning: parse_url fails if password has '#' (fragment). We parse manually if needed.
            $dbUrl = getenv('DATABASE_URL');
            if ($dbUrl) {
                // Try regex to handle special chars in password more gracefully
                // Regex pattern captures: scheme://user:pass@host:port/dbname
                if (preg_match('|postgres(?:ql)?://([^:]+):([^@]+)@([^:/]+)(?::(\d+))?/(\w+)|', $dbUrl, $matches)) {
                    $this->username = $matches[1];
                    $this->password = $matches[2]; // Captures # correctly
                    $original_host = $matches[3];
                    $this->port = !empty($matches[4]) ? $matches[4] : '5432';
                    $this->db_name = $matches[5];
                } else {
                    // Fallback to parse_url if regex fails (simple passwords)
                    $parsed = parse_url($dbUrl);
                    if ($parsed && isset($parsed['host'])) {
                        $original_host = $parsed['host'];
                        $this->db_name = ltrim($parsed['path'] ?? '/postgres', '/');
                        $this->username = $parsed['user'] ?? $this->username;
                        $this->password = $parsed['pass'] ?? $this->password;
                        $this->port = $parsed['port'] ?? '5432';
                    }
                }
            }

            // FORCE IPv4: Vercel defaults to IPv6 which fails. We must find an IPv4 address.
            $this->host = $original_host; // Default fallback

            // 1. Check for manual override in Environment
            if (getenv('DB_FORCE_IP')) {
                $this->host = getenv('DB_FORCE_IP');
            } else {
                // 2. Try to resolve IPv4 via gethostbynamel (returns array of IPs)
                $ips = @gethostbynamel($original_host);
                $found_ipv4 = false;
                
                if ($ips && is_array($ips)) {
                    foreach ($ips as $ip) {
                        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            $this->host = $ip;
                            $found_ipv4 = true;
                            // error_log("Resolved IPv4 via gethostbynamel: $ip");
                            break;
                        }
                    }
                }
                
                // 3. If still no IPv4, try gethostbyname as last resort
                if (!$found_ipv4) {
                    $ip = @gethostbyname($original_host);
                    if ($ip !== $original_host && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                         $this->host = $ip;
                    }
                }
            }
            
            // 4. FINAL FALLBACK: If we still don't have an IPv4 (still hostname or empty), 
            // hardcode the known SA-East-1 Pooler IP. This is a "Hail Mary" to fix the IPv6 error.
            if (!filter_var($this->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $this->host = '54.94.90.106'; // aws-0-sa-east-1.pooler.supabase.com
            }

            // 5. EXTRACT PROJECT REF (needed for routing via IP)
            // Hostname is usually: db.<project_ref>.supabase.co
            $projectRef = '';
            if (preg_match('/db\.([a-z0-9]+)\.supabase\.co/', $original_host, $matches)) {
                $projectRef = $matches[1];
            } else {
                // Try from username if formatted like postgres.ref
                $parts = explode('.', $this->username);
                if (count($parts) > 1) {
                    $projectRef = $parts[1];
                } else {
                    // Fallback hardcoded based on user logs: ogiwoavudsjlwfkvndgc
                    $projectRef = 'ogiwoavudsjlwfkvndgc';
                }
            }

            // 6. FORCE PORT 6543 REMOVED - User requested 5432
            // We rely on the port parsed from ENV or DATABASE_URL (default 5432)
            
            try {
                // Connect
                // Append endpoint to options so Supabase knows the tenant when we connect via IP
                $dsn = "{$this->driver}:host={$this->host};port={$this->port};dbname={$this->db_name};sslmode=require";
                if ($projectRef) {
                    $dsn .= ";options='endpoint={$projectRef}'";
                }
                
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
