/**
 * Ruleta de Colonias Colombianas
 * Formulario de asignación con algoritmo de balance interno (+1 máx. diferencia).
 * COLONIAS: inyectado por index.php o fallback estático.
 */

const COLONIAS = window.COLONIAS || ['Andina', 'Amazónica', 'Caribe', 'Pacífico', 'Orinoquía'];
const DOC_MIN = 6;
const DOC_MAX = 15;
const ANGULO_POR_SEGMENTO = 360 / COLONIAS.length;

const form = document.getElementById('formAsignacion');
const inputDocumento = document.getElementById('documento');
const btnAsignar = document.getElementById('btnAsignar');
const resultado = document.getElementById('resultado');
const ruletaContainer = document.getElementById('ruletaContainer');
const ruletaWheel = document.getElementById('ruletaWheel');
const btnReporte = document.getElementById('btnReporte');

form.addEventListener('submit', manejarEnvio);
btnReporte?.addEventListener('click', descargarReporte);

cargarDashboard();

// Solo números en documento de identidad
inputDocumento.addEventListener('input', (e) => {
  e.target.value = e.target.value.replace(/\D/g, '');
});

async function manejarEnvio(e) {
  e.preventDefault();
  const documento = inputDocumento.value.trim();
  if (!documento) {
    mostrarError('Ingresa tu documento de identidad.');
    inputDocumento.focus();
    return;
  }
  if (!/^\d+$/.test(documento)) {
    mostrarError('El documento solo puede contener números.');
    inputDocumento.focus();
    return;
  }
  if (documento.length < DOC_MIN || documento.length > DOC_MAX) {
    mostrarError(`El documento debe tener entre ${DOC_MIN} y ${DOC_MAX} dígitos.`);
    inputDocumento.focus();
    return;
  }

  setLoading(true);

  try {
    const res = await fetch('api/asignar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ documento })
    });
    const data = await res.json();

    if (!data.ok) {
      throw new Error(data.error || 'Error al asignar colonia');
    }

    procesarAsignacion(data.colonia, data.yaAsignado, data.mensaje);
  } catch (err) {
    if (err.message.includes('fetch') || err.message.includes('Failed')) {
      const { colonia, yaAsignado } = asignarLocal(documento);
      const mensaje = yaAsignado
        ? `Ya tenías colonia asignada: ${colonia} (modo local)`
        : `Te asignamos la colonia: ${colonia} (modo local)`;
      procesarAsignacion(colonia, yaAsignado, mensaje);
    } else {
      mostrarError(err.message);
    }
  } finally {
    setLoading(false);
  }
}

function setLoading(loading) {
  btnAsignar.disabled = loading;
  btnAsignar.classList.toggle('loading', loading);
}

function mostrarResultado(mensaje) {
  Swal.fire({
    icon: 'success',
    title: '¡Colonia asignada!',
    text: mensaje,
    confirmButtonColor: '#f59e0b'
  });
}

/** Procesa asignación: mensaje + ruleta o leyenda (DRY). */
function procesarAsignacion(colonia, yaAsignado, mensaje) {
  if (yaAsignado) {
    mostrarResultado(mensaje);
    mostrarColoniaAsignada(colonia);
    cargarDashboard();
  } else {
    animarRuleta(colonia, mensaje);
  }
}

function mostrarError(mensaje) {
  Swal.fire({
    icon: 'error',
    title: 'Error',
    text: mensaje,
    confirmButtonColor: '#f59e0b'
  });
}

/** Resalta el ganador en la leyenda (DRY: usado en asignada y tras girar). */
function resaltarGanadorEnLeyenda(colonia) {
  document.querySelectorAll('.ruleta-leyenda-item').forEach(el => {
    el.classList.toggle('ganador', el.dataset.colonia === colonia);
  });
}

/** Muestra la colonia ya asignada sin girar la ruleta. */
function mostrarColoniaAsignada(colonia) {
  ruletaContainer.classList.add('visible', 'solo-leyenda');
  ruletaContainer.setAttribute('aria-hidden', 'false');
  resaltarGanadorEnLeyenda(colonia);
}

/**
 * Animación de la ruleta: rota hasta el segmento asignado.
 * Mensaje y resaltado se muestran al terminar de girar.
 */
function animarRuleta(coloniaGanadora, mensaje) {
  ruletaContainer.classList.add('visible');
  ruletaContainer.classList.remove('solo-leyenda');
  ruletaContainer.setAttribute('aria-hidden', 'false');

  document.querySelectorAll('.ruleta-leyenda-item').forEach(el => el.classList.remove('ganador'));

  const idx = COLONIAS.indexOf(coloniaGanadora);
  if (idx === -1) return;

  const anguloGanador = 360 - (idx * ANGULO_POR_SEGMENTO + ANGULO_POR_SEGMENTO / 2);
  const vueltas = 6 * 360;
  const rotacionFinal = vueltas + anguloGanador;

  ruletaWheel.classList.remove('spinning');
  ruletaWheel.style.transform = `rotate(0deg)`;

  const alTerminar = () => {
    resaltarGanadorEnLeyenda(coloniaGanadora);
    mostrarResultado(mensaje);
    cargarDashboard();
    ruletaWheel.removeEventListener('transitionend', alTerminar);
  };
  ruletaWheel.addEventListener('transitionend', alTerminar);

  requestAnimationFrame(() => {
    ruletaWheel.classList.add('spinning');
    ruletaWheel.style.transform = `rotate(${rotacionFinal}deg)`;
  });
}

/**
 * Fallback local con ruleta balanceada (localStorage).
 * Solo para uso sin backend; las asignaciones no se comparten entre dispositivos.
 */
function asignarLocal(documento) {
  const key = 'ruleta_colonias';
  let datos = { asignaciones: [], porColonia: Object.fromEntries(COLONIAS.map(c => [c, 0])) };

  try {
    const stored = localStorage.getItem(key);
    if (stored) {
      datos = JSON.parse(stored);
    }
  } catch (_) {}

  const existente = datos.asignaciones.find(a => 
    String(a.documento).toLowerCase() === String(documento).toLowerCase()
  );
  if (existente) return { colonia: existente.colonia, yaAsignado: true };

  const BALANCE_MAX = 1;
  const min = Math.min(...Object.values(datos.porColonia));
  const limite = min + BALANCE_MAX;
  const elegibles = COLONIAS.filter(c => datos.porColonia[c] < limite);
  const colonia = elegibles.length > 0 
    ? elegibles[Math.floor(Math.random() * elegibles.length)]
    : COLONIAS[Math.floor(Math.random() * COLONIAS.length)];

  datos.porColonia[colonia]++;
  datos.asignaciones.push({ documento, colonia, fecha: new Date().toISOString() });
  try {
    localStorage.setItem(key, JSON.stringify(datos));
  } catch (_) {}

  return { colonia, yaAsignado: false };
}

/** Carga y muestra el dashboard de totales. */
async function cargarDashboard() {
  const totalEl = document.getElementById('dashboardTotal');
  const gridEl = document.getElementById('dashboardGrid');
  if (!totalEl || !gridEl) return;

  try {
    const res = await fetch('api/reporte.php');
    const data = await res.json();
    if (!data.ok) throw new Error(data.error);

    const totalNumEl = document.getElementById('dashboardTotalNum');
    if (totalNumEl) totalNumEl.textContent = data.total;

    const maxTotal = Math.max(...Object.values(data.conteos), 1);
    const config = window.COLONIAS_CONFIG || COLONIAS.map(n => ({ nombre: n, slug: n.toLowerCase().replace(/[^a-z0-9]/g, '') }));
    gridEl.innerHTML = config.map(col => {
      const total = data.conteos[col.nombre] ?? 0;
      const slug = col.slug || col.nombre.toLowerCase().replace(/[^a-z0-9]/g, '');
      const pct = maxTotal > 0 ? (total / maxTotal) * 100 : 0;
      return `<div class="dashboard-card dashboard-card-${slug}">
        <div class="dashboard-card-header">
          <span class="dashboard-card-nombre">${escapeHtml(col.nombre)}</span>
          <span class="dashboard-card-total">${total}</span>
        </div>
        <div class="dashboard-card-bar"><span class="dashboard-card-bar-fill" style="width:${pct}%"></span></div>
      </div>`;
    }).join('');
  } catch {
    const totalNumEl = document.getElementById('dashboardTotalNum');
    if (totalNumEl) totalNumEl.textContent = '—';
    gridEl.innerHTML = '<p class="dashboard-empty">No se pudo cargar el resumen</p>';
  }
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

/** Descarga el reporte en CSV. */
async function descargarReporte() {
  if (!btnReporte) return;
  try {
    btnReporte.disabled = true;
    const res = await fetch('api/reporte.php?formato=csv');
    if (!res.ok) throw new Error('Error al generar el reporte');
    const blob = await res.blob();
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `reporte-colonias-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  } catch (err) {
    mostrarError(err.message || 'No se pudo descargar el reporte');
  } finally {
    btnReporte.disabled = false;
  }
}
