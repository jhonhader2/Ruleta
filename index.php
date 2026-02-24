<?php
/**
 * Punto de entrada principal.
 * Colonias desde config/colonias.php (fuente única).
 */
$coloniasConfig = require __DIR__ . '/config/colonias.php';
$coloniasNombres = array_column($coloniasConfig, 'nombre');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ruleta de Colonias Colombianas</title>
  <link rel="icon" href="favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
  <main class="container">
    <header class="header">
      <h1>Ruleta de Colonias Colombianas</h1>
      <p class="subtitle">Ingresa tu documento y descubre tu colonia asignada</p>
    </header>

    <form id="formAsignacion" class="form-asignacion" novalidate>
      <div class="form-group">
        <label for="documento">Documento de identidad</label>
        <input 
          type="text" 
          inputmode="numeric"
          pattern="[0-9]*"
          id="documento" 
          name="documento" 
          placeholder="Ej: 1234567890" 
          required 
          autocomplete="off"
          maxlength="15"
          aria-describedby="documento-hint"
        >
        <span id="documento-hint" class="input-hint">Solo números (cédula de ciudadanía)</span>
      </div>
      <button type="submit" id="btnAsignar" class="btn-asignar">
        <span class="btn-text">Asignar colonia</span>
        <span class="btn-loading" aria-hidden="true">Asignando…</span>
      </button>
    </form>

    <div id="resultado" class="resultado" role="alert" aria-live="polite">
      <div id="ruletaContainer" class="ruleta-container" aria-hidden="true">
        <div class="ruleta">
          <div id="ruletaWheel" class="ruleta-wheel" role="img" aria-label="Ruleta de colonias">
            <?php foreach ($coloniasConfig as $col): ?>
            <div class="segmento" data-colonia="<?= htmlspecialchars($col['nombre']) ?>"><?= htmlspecialchars($col['nombre']) ?></div>
            <?php endforeach; ?>
          </div>
          <div class="ruleta-marcador" aria-hidden="true"></div>
        </div>
        <div class="ruleta-leyenda" id="ruletaLeyenda">
            <?php foreach ($coloniasConfig as $col): ?>
          <span class="ruleta-leyenda-item <?= htmlspecialchars($col['slug']) ?>" data-colonia="<?= htmlspecialchars($col['nombre']) ?>"><?= htmlspecialchars($col['nombre']) ?></span>
            <?php endforeach; ?>
        </div>
      </div>
    </div>

    <footer class="footer">
      <p>Las colonias se asignan de forma aleatoria manteniendo equilibrio entre grupos.</p>
      <button type="button" id="btnReporte" class="btn-reporte">Descargar reporte</button>
    </footer>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    window.COLONIAS = <?= json_encode($coloniasNombres) ?>;
  </script>
  <script src="app.js"></script>
</body>
</html>
