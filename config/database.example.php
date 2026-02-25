<?php
/**
 * Plantilla de configuración MySQL.
 * Copiar a database.php (fuera de Git) y ajustar credenciales.
 * 
 *   cp config/database.example.php config/database.php
 * 
 * Las variables de entorno tienen prioridad sobre los valores por defecto:
 *   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
 */

// Cargar .env en local (Laragon, etc.) cuando no hay variables de entorno
if (!getenv('DB_HOST') && file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '' && strpos($line, '#') !== 0 && strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($val, " \t\n\r\0\x0B\"'"));
        }
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_NAME') ?: 'ruleta';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

$dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}
