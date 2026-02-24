<?php
/**
 * Medidas de seguridad para producción.
 * Incluir al inicio de endpoints públicos.
 */

// Cabeceras de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CORS: en producción solo mismo origen
$esProduccion = (getenv('APP_ENV') ?: '') === 'production';
$host = $_SERVER['HTTP_HOST'] ?? '';

if ($esProduccion && $host) {
    $origen = $_SERVER['HTTP_ORIGIN'] ?? '';
    $hostOrigen = $origen ? parse_url($origen, PHP_URL_HOST) : '';
    $h = strtolower($host);
    $ho = strtolower($hostOrigen ?? '');
    $mismoDominio = $ho === $h || $ho === 'www.' . $h || 'www.' . $ho === $h;
    if ($origen && $hostOrigen && $mismoDominio) {
        header('Access-Control-Allow-Origin: ' . $origen);
    }
} else {
    header('Access-Control-Allow-Origin: *');
}
