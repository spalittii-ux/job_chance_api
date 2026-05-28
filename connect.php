<?php
// Railway + local PostgreSQL connection

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $databaseUrl = getenv('DATABASE_URL');

    if ($databaseUrl) {
        // Railway DATABASE_URL
        $db = parse_url($databaseUrl);

        if ($db === false || !isset($db['host'], $db['user'], $db['pass'], $db['path'])) {
            throw new Exception('Invalid DATABASE_URL format');
        }

        $host = $db['host'];
        $port = $db['port'] ?? 5432;
        $dbname = ltrim($db['path'], '/');
        $user = urldecode($db['user']);
        $pass = urldecode($db['pass']);

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

        if (!empty($db['query'])) {
            parse_str($db['query'], $query);
            if (!empty($query['sslmode'])) {
                $dsn .= ";sslmode=" . $query['sslmode'];
            }
        }
    } else {
        // Local fallback
        $host = getenv('PGHOST') ?: 'localhost';
        $port = getenv('PGPORT') ?: 5432;
        $dbname = getenv('PGDATABASE') ?: 'jobchance';
        $user = getenv('PGUSER') ?: 'postgres';
        $pass = getenv('PGPASSWORD') ?: '12345678';

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
    }

    $con = new PDO(
        $dsn,
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    include_once __DIR__ . "/functions.php";

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "fail",
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}