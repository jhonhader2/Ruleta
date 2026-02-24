<?php
/**
 * Rate limiting simple por IP (archivo).
 * Límite: 10 solicitudes por minuto por IP.
 */

const RATE_LIMIT_REQUESTS = 10;
const RATE_LIMIT_WINDOW = 60;
const RATE_LIMIT_FILE = __DIR__ . '/../data/rate_limit.json';

function verificarRateLimit(): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $now = time();
    $dir = dirname(RATE_LIMIT_FILE);

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $data = [];
    if (file_exists(RATE_LIMIT_FILE)) {
        $raw = file_get_contents(RATE_LIMIT_FILE);
        if ($raw) {
            $data = json_decode($raw, true) ?: [];
        }
    }

    $data[$ip] = $data[$ip] ?? [];
    $data[$ip] = array_filter($data[$ip], fn($t) => ($now - $t) < RATE_LIMIT_WINDOW);

    if (count($data[$ip]) >= RATE_LIMIT_REQUESTS) {
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        header('Retry-After: ' . RATE_LIMIT_WINDOW);
        echo json_encode(['ok' => false, 'error' => 'Demasiadas solicitudes. Intenta más tarde.']);
        exit;
    }

    $data[$ip][] = $now;
    file_put_contents(RATE_LIMIT_FILE, json_encode($data));
}
