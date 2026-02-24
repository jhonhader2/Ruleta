<?php
/**
 * Punto de entrada principal.
 * Inyecta COLONIAS desde config para mantener DRY con el backend.
 */
$colonias = file_exists(__DIR__ . '/config/colonias.php')
  ? require __DIR__ . '/config/colonias.php'
  : ['Andina', 'Amazónica', 'Caribe', 'Pacífico', 'Orinoquía'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ruleta de Colonias Colombianas</title>
  <link rel="stylesheet" href="styles.css">
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
          <div id="ruletaWheel" class="ruleta-wheel" role="img" aria-label="Ruleta con las cinco colonias">
            <?php foreach ($colonias as $c): ?>
            <div class="segmento" data-colonia="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></div>
            <?php endforeach; ?>
          </div>
          <div class="ruleta-marcador" aria-hidden="true"></div>
        </div>
        <div class="ruleta-leyenda" id="ruletaLeyenda">
            <?php
            $clases = ['andina', 'amazonica', 'caribe', 'pacifico', 'orinoquia'];
            foreach ($colonias as $i => $c):
              $clase = $clases[$i] ?? 'colonia-' . $i;
            ?>
          <span class="ruleta-leyenda-item <?= htmlspecialchars($clase) ?>" data-colonia="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></span>
            <?php endforeach; ?>
        </div>
      </div>
      <div id="asignacionMensaje" class="asignacion-mensaje"></div>
    </div>

    <footer class="footer">
      <p>Las colonias se asignan de forma aleatoria manteniendo equilibrio entre grupos.</p>
    </footer>
  </main>

  <script>
    window.COLONIAS = <?= json_encode($colonias) ?>;
  </script>
  <script src="app.js"></script>
</body>
</html>
