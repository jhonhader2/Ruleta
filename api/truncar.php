<?php
/**
 * API para truncar la tabla asignaciones.
 * Requiere token de administración (ADMIN_TOKEN en .env).
 * POST con header: X-Admin-Token: <token>
 */

// Cargar .env en local (Laragon, etc.) cuando no hay variables de entorno
if (getenv('ADMIN_TOKEN') === false || getenv('ADMIN_TOKEN') === '') {
    $envPath = __DIR__ . '/../.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '' && strpos($line, '#') !== 0 && strpos($line, '=') !== false) {
                [$key, $val] = explode('=', $line, 2);
                putenv(trim($key) . '=' . trim($val, " \t\n\r\0\x0B\"'"));
            }
        }
    }
}

require_once __DIR__ . '/seguridad.php';

$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Configuración no encontrada']);
    exit;
}
require_once $configPath;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
$tokenEsperado = getenv('ADMIN_TOKEN') ?: '';

if ($tokenEsperado === '' || $token !== $tokenEsperado) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token inválido o no configurado']);
    exit;
}

try {
    $pdo->exec('TRUNCATE TABLE asignaciones');
    echo json_encode(['ok' => true, 'mensaje' => 'Tabla asignaciones truncada']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al truncar la tabla']);
}
