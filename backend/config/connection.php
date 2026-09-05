<?php
// Database connection parameters (Environment Variables with Local Fallbacks)
$database_url = getenv('DATABASE_URL');

if (!empty($database_url)) {
    // Parse DATABASE_URL (postgres://user:password@host:port/dbname)
    $db_parts = parse_url($database_url);
    $host = $db_parts['host'] ?? 'localhost';
    $port = $db_parts['port'] ?? 5432;
    $user = $db_parts['user'] ?? 'postgres';
    $password = $db_parts['pass'] ?? '';
    $dbname = ltrim($db_parts['path'] ?? 'result', '/');
    
    $conn = @pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require");
} else {
    $host = getenv('DB_HOST') ?: "localhost";
    $dbname = getenv('DB_NAME') ?: "result";
    $user = getenv('DB_USER') ?: "postgres";
    $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "2003";
    $port = getenv('DB_PORT') ?: 5432;

    $conn = @pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
}

// Check if the connection was successful
if (!$conn) {
    error_log("PostgreSQL connection failed: " . ($conn ? pg_last_error($conn) : "Unable to reach PostgreSQL server"));
}
?>