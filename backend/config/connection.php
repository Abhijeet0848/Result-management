<?php
// Automatic .env loader for local and serverless environments
(function() {
    $envFile = __DIR__ . '/../../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
})();

// High-Performance Persistent PostgreSQL Connection
$database_url = getenv('DATABASE_URL');

if (!empty($database_url)) {
    // Parse cloud DATABASE_URL (postgres://user:password@host:port/dbname)
    $db_parts = parse_url($database_url);
    $host = $db_parts['host'] ?? 'localhost';
    $port = $db_parts['port'] ?? 5432;
    $user = $db_parts['user'] ?? 'postgres';
    $password = $db_parts['pass'] ?? '';
    $dbname = ltrim($db_parts['path'] ?? 'result', '/');
    
    // Use persistent connection with TCP keepalive and connection timeout
    $connString = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require connect_timeout=5";
    $conn = @pg_pconnect($connString);
    if (!$conn) {
        $conn = @pg_connect($connString);
    }
} else {
    $host = getenv('DB_HOST') ?: "localhost";
    $dbname = getenv('DB_NAME') ?: "result";
    $user = getenv('DB_USER') ?: "postgres";
    $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
    $port = getenv('DB_PORT') ?: 5432;

    $connString = "host=$host port=$port dbname=$dbname user=$user password=$password connect_timeout=3";
    $conn = @pg_pconnect($connString);
    if (!$conn) {
        $conn = @pg_connect($connString);
    }
}

// Check connection status
if (!$conn) {
    error_log("PostgreSQL connection failed: " . ($conn ? pg_last_error($conn) : "Unable to reach PostgreSQL server"));
}
?>