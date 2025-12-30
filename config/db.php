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

            // 5. EXTRACT PROJECT REF (needed for username formatting)
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

            // 6. ROUTING FIX: Append Project Ref to Username (CRITICAL for Direct Connection)
            // Supabase Direct (Port 5432) requires 'user.project_ref' to identify the tenant.
            if ($projectRef && strpos($this->username, $projectRef) === false) {
                $this->username .= ".{$projectRef}";
            }

            // REVERTED: No IPv4 forcing, No hardcoded IP, No endpoint option.
            // User confirmed Supabase requires Hostname (SNI) and correct User format.
            $this->host = $original_host;

            try {
                // Connect
                // Standard DSN for Supabase Direct
                $dsn = "{$this->driver}:host={$this->host};port={$this->port};dbname={$this->db_name};sslmode=require";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // IMPORTANT: Supabase Transaction Pooler might behave better with this off, 
                    // but for Direct Connection (5432) it's standard Postgres behavior.
                    // Leaving enabled (true) as it's generally safer for PHP PDO compatibility unless specific errors arise.
                    PDO::ATTR_EMULATE_PREPARES => true, 
                ];

                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                
                // Set charset just in case
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
