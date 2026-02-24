<?php
/**
 * API de reporte: asignaciones y conteos por colonia.
 * GET: JSON. GET?formato=csv: descarga CSV.
 */

error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/seguridad.php';

$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Configuración no encontrada']);
    exit;
}
require_once $configPath;

$coloniasConfig = require __DIR__ . '/../config/colonias.php';
$COLONIAS = array_column($coloniasConfig, 'nombre');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$stmt = $pdo->query('SELECT documento, colonia, creado_en FROM asignaciones ORDER BY colonia, documento');
$asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query('SELECT colonia, COUNT(*) AS total FROM asignaciones GROUP BY colonia');
$conteosRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$conteos = array_fill_keys($COLONIAS, 0);
foreach ($conteosRaw as $r) {
    if (isset($conteos[$r['colonia']])) {
        $conteos[$r['colonia']] = (int) $r['total'];
    }
}

$formato = $_GET['formato'] ?? 'json';

if ($formato === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte-colonias-' . date('Y-m-d-His') . '.csv"');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

    $csvOpts = ['separator' => ';', 'enclosure' => '"', 'escape' => '\\'];
    fputcsv($out, ['Documento', 'Colonia', 'Fecha asignación'], $csvOpts['separator'], $csvOpts['enclosure'], $csvOpts['escape']);
    foreach ($asignaciones as $a) {
        fputcsv($out, [$a['documento'], $a['colonia'], $a['creado_en']], $csvOpts['separator'], $csvOpts['enclosure'], $csvOpts['escape']);
    }
    fputcsv($out, [], $csvOpts['separator'], $csvOpts['enclosure'], $csvOpts['escape']);
    fputcsv($out, ['Resumen por colonia'], $csvOpts['separator'], $csvOpts['enclosure'], $csvOpts['escape']);
    foreach ($conteos as $colonia => $total) {
        fputcsv($out, [$colonia, (string) $total], $csvOpts['separator'], $csvOpts['enclosure'], $csvOpts['escape']);
    }
    fclose($out);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'asignaciones' => $asignaciones,
    'conteos' => $conteos,
    'total' => count($asignaciones)
]);
