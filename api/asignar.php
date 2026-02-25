<?php
/**
 * API de asignación de colonias con ruleta balanceada.
 * Garantiza que ninguna colonia supere en más de 2 integrantes a las demás.
 */

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/rate-limit.php';

$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) {
    responderError(500, 'Configuración no encontrada. Copie config/database.example.php a config/database.php.');
}
require_once $configPath;
require_once __DIR__ . '/../config/validacion.php';

$coloniasConfig = require __DIR__ . '/../config/colonias.php';
$COLONIAS = array_column($coloniasConfig, 'nombre');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderError(405, 'Método no permitido');
}

verificarRateLimit();

/** Respuesta JSON de error (DRY). */
function responderError(int $codigo, string $mensaje): void {
    http_response_code($codigo);
    echo json_encode(['ok' => false, 'error' => $mensaje]);
    exit;
}

/** Respuesta cuando el documento ya tenía colonia asignada (DRY). */
function responderYaAsignado(array $existente): void {
    echo json_encode([
        'ok' => true,
        'colonia' => $existente['colonia'],
        'yaAsignado' => true,
        'mensaje' => 'Ya tenías colonia asignada: ' . $existente['colonia']
    ]);
    exit;
}

/** Obtiene conteos por colonia desde MySQL */
function obtenerConteosPorColonia(PDO $pdo, array $colonias): array {
    $stmt = $pdo->query('SELECT colonia, COUNT(*) AS total FROM asignaciones GROUP BY colonia');
    $raw = $stmt->fetchAll();
    $conteos = array_fill_keys($colonias, 0);
    foreach ($raw as $r) {
        if (isset($conteos[$r['colonia']])) {
            $conteos[$r['colonia']] = (int) $r['total'];
        }
    }
    return $conteos;
}

/** Verifica si un documento ya tiene colonia asignada */
function obtenerAsignacionExistente(PDO $pdo, string $documento): ?array {
    $stmt = $pdo->prepare('SELECT colonia FROM asignaciones WHERE documento = ?');
    $stmt->execute([$documento]);
    $r = $stmt->fetch();
    return $r ?: null;
}

/** Inserta asignación en MySQL */
function insertarAsignacion(PDO $pdo, string $documento, string $colonia): bool {
    $stmt = $pdo->prepare('INSERT INTO asignaciones (documento, colonia) VALUES (?, ?)');
    return $stmt->execute([$documento, $colonia]);
}

/** Ruleta balanceada: elegibles solo colonias con count < min + BALANCE_MAX_DIFERENCIA */
function seleccionarColoniaBalanceada(array $porColonia, array $colonias): string {
    $min = min($porColonia);
    $limite = $min + BALANCE_MAX_DIFERENCIA;
    $elegibles = array_filter($porColonia, fn($c) => $c < $limite);
    $coloniasElegibles = array_keys($elegibles);
    if (empty($coloniasElegibles)) {
        $coloniasElegibles = $colonias;
    }
    return $coloniasElegibles[array_rand($coloniasElegibles)];
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$documento = preg_replace('/[^0-9]/', '', trim($input['documento'] ?? $_POST['documento'] ?? ''));

if ($documento === '') {
    responderError(400, 'Documento de identidad requerido');
}

if (strlen($documento) < DOC_MIN_LENGTH || strlen($documento) > DOC_MAX_LENGTH) {
    responderError(400, 'Documento inválido (entre ' . DOC_MIN_LENGTH . ' y ' . DOC_MAX_LENGTH . ' dígitos)');
}

$archivoPersonas = __DIR__ . '/../data/personas.csv';
if (file_exists($archivoPersonas)) {
    $lineas = file($archivoPersonas, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $documentosPermitidos = array_map('trim', array_slice($lineas, 1)); // Saltar cabecera "Documento"
    if (!in_array($documento, $documentosPermitidos, true)) {
        responderError(404, 'El documento no se encuentra en el sistema');
    }
}

$existente = obtenerAsignacionExistente($pdo, $documento);
if ($existente) {
    responderYaAsignado($existente);
}

$conteos = obtenerConteosPorColonia($pdo, $COLONIAS);
$colonia = seleccionarColoniaBalanceada($conteos, $COLONIAS);

try {
    if (!insertarAsignacion($pdo, $documento, $colonia)) {
        throw new RuntimeException('Error al guardar');
    }
} catch (PDOException $e) {
    $existente = obtenerAsignacionExistente($pdo, $documento);
    if ($existente) {
        responderYaAsignado($existente);
    }
    responderError(500, 'Error al guardar la asignación');
}

echo json_encode([
    'ok' => true,
    'colonia' => $colonia,
    'yaAsignado' => false,
    'mensaje' => 'Te asignamos la colonia: ' . $colonia
]);
