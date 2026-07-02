<?php
declare(strict_types=1);

/* =========================
   Helpers
========================= */
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money(float $n): string { return number_format($n, 2, '.', ','); }

// Extrae números de un string tipo: "564.24 + 2038.77" o "$ 1,234.56 + 200"
function salario_tokens(string $salDisp): array {
  $salDisp = trim($salDisp);
  if ($salDisp === '') return [];

  $parts = preg_split('/\s*\+\s*/', $salDisp);
  $vals = [];

  foreach ($parts as $p) {
    $p = trim((string)$p);
    if ($p === '') continue;

    $p = str_replace(['$', ' '], '', $p);

    // "1234,56" => "1234.56"
    if (substr_count($p, ',') === 1 && substr_count($p, '.') === 0) {
      $p = str_replace(',', '.', $p);
    } else {
      // "1,234.56" => "1234.56"
      $p = str_replace(',', '', $p);
    }

    if (is_numeric($p)) $vals[] = (float)$p;
  }

  // unique
  $uniq = [];
  foreach ($vals as $v) {
    $k = (string)$v;
    if (!isset($uniq[$k])) $uniq[$k] = $v;
  }
  return array_values($uniq);
}

function salario_sum(string $salDisp): float {
  $toks = salario_tokens($salDisp);
  return array_sum($toks);
}

function limpiar_nombre(string $nombre): string {
  $nombre = trim(preg_replace('/\s+/', ' ', $nombre));

  // DD MM YYYY / DD-MM-YYYY / DD/MM/YYYY
  $nombre = preg_replace('/\bDD\b\s*[-\/]?\s*\bMM\b\s*[-\/]?\s*\bYYYY\b/i', '', $nombre);

  // OCR raro: D D M M Y Y Y Y
  $nombre = preg_replace('/\bD\s*D\s*M\s*M\s*Y\s*Y\s*Y\s*Y\b/i', '', $nombre);

  // Si se pegó una fecha real al final
  $nombre = preg_replace('/\b\d{2}[\/-]\d{2}[\/-]\d{4}\b$/', '', $nombre);
  $nombre = preg_replace('/\b\d{2}\s+\d{2}\s+\d{4}\b$/', '', $nombre);

  return trim(preg_replace('/\s+/', ' ', $nombre));
}

/* =========================
   ✅ Parse de fecha robusto (para PDFs distintos)
========================= */
function parse_fecha_any(?string $s): ?DateTimeImmutable {
  $s = trim((string)$s);
  if ($s === '') return null;

  $s = preg_replace('/\s+/', '', $s);

  // DD/MM/YYYY o DD-MM-YYYY o DD.MM.YYYY
  if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $s, $m)) {
    $d = (int)$m[1]; $mo = (int)$m[2]; $y = (int)$m[3];
    if (!checkdate($mo, $d, $y)) return null;
    return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $y, $mo, $d));
  }

  // DD/MM/YY
  if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2})$/', $s, $m)) {
    $d = (int)$m[1]; $mo = (int)$m[2]; $yy = (int)$m[3];
    $y = ($yy < 70) ? (2000 + $yy) : (1900 + $yy);
    if (!checkdate($mo, $d, $y)) return null;
    return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $y, $mo, $d));
  }

  // YYYY-MM-DD o YYYY/MM/DD
  if (preg_match('/^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})$/', $s, $m)) {
    $y = (int)$m[1]; $mo = (int)$m[2]; $d = (int)$m[3];
    if (!checkdate($mo, $d, $y)) return null;
    return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $y, $mo, $d));
  }

  return null;
}

function fmt_dmy(?DateTimeImmutable $dt): string {
  return $dt ? $dt->format('d/m/Y') : '';
}

/* =========================
   Extraer CURP / NSS / Nombre / Semanas (fallback desde debug.preview)
========================= */
function extraer_persona_desde_texto(string $txt): array {
  $out = ['curp'=>'', 'nss'=>'', 'nombre'=>'', 'semanas'=>''];
  $t = strtoupper($txt);

  if (preg_match('/\b([A-Z]{4}\d{6}[HM][A-Z]{5}\d{2})\b/', $t, $m)) $out['curp'] = $m[1];

  if (preg_match('/\bNSS\b[^0-9]{0,20}([0-9][0-9\-\s]{9,20}[0-9])\b/', $t, $m)) {
    $digits = preg_replace('/\D+/', '', $m[1]);
    if (strlen($digits) >= 10) $out['nss'] = $digits;
  } elseif (preg_match('/\b(\d{11})\b/', $t, $m)) {
    $out['nss'] = $m[1];
  }

  $txtN = str_replace(["\r\n", "\r"], "\n", $txt);

  if (preg_match('/TOTAL\s+DE\s+SEMANAS\s+COTIZADAS\b.*?(?:\n|\r\n)\s*(\d{1,6})\s*(?:\n|$)/is', $txtN, $m)) {
    $w = (int)$m[1];
    if ($w >= 1 && $w <= 6000) $out['semanas'] = (string)$w;
  }

  if ($out['semanas'] === '') {
    $pos = stripos($txtN, 'TOTAL DE SEMANAS COTIZADAS');
    if ($pos !== false) {
      $chunk = substr($txtN, (int)$pos, 600);
      if (preg_match_all('/(?<![A-Z0-9])(\d{1,6})(?![A-Z0-9])/i', $chunk, $nums)) {
        foreach ($nums[1] as $cand) {
          $w = (int)$cand;
          if ($w >= 1900 && $w <= 2099) continue;
          if ($w >= 1 && $w <= 6000) { $out['semanas'] = (string)$w; break; }
        }
      }
    }
  }

  if (preg_match('/ESTIMAD[OA]\(A\),?\s*[\r\n]+\s*([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]{5,80})/u', $t, $m)) {
    $out['nombre'] = trim(preg_replace('/\s+/', ' ', $m[1]));
  } else {
    if (preg_match('/\n\s*([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]{5,80})\s*\n\s*NSS\b/u', $t, $m)) {
      $out['nombre'] = trim(preg_replace('/\s+/', ' ', $m[1]));
    }
  }

  return $out;
}

/* =========================
   Validación de request
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['pdf'])) {
  http_response_code(400);
  exit('Solicitud inválida.');
}
if (!isset($_FILES['pdf']['error']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
  exit('Error al subir el PDF (UPLOAD_ERR).');
}
$ext = strtolower(pathinfo($_FILES['pdf']['name'] ?? '', PATHINFO_EXTENSION));
if ($ext !== 'pdf') exit('Solo se permite subir archivos PDF.');

/* =========================
   Guardar archivo TEMPORAL
========================= */
$tmpBase = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cip_tmp';
if (!is_dir($tmpBase)) @mkdir($tmpBase, 0777, true);

$dest = $tmpBase . DIRECTORY_SEPARATOR . 'upl_' . bin2hex(random_bytes(10)) . '.pdf';

if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $dest)) exit('No se pudo mover el archivo subido al temporal (/tmp).');
if (!file_exists($dest) || filesize($dest) < 100) { @unlink($dest); exit('El PDF temporal quedó vacío o inválido.'); }

/* =========================
   Ejecutar Python
========================= */
$script = __DIR__ . DIRECTORY_SEPARATOR . 'parse_periodos.py';
if (!file_exists($script)) { @unlink($dest); exit('No existe parse_periodos.py en: ' . h($script)); }

$isWin  = (stripos(PHP_OS_FAMILY, 'Windows') !== false);
$python = $isWin ? 'py -3' : 'python3';

$cmd = $python . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($dest) . ' 2>&1';
$output = shell_exec($cmd);

// borra PDF temporal
@unlink($dest);

if ($output === null) exit("No se pudo ejecutar Python.\n\nComando:\n<pre>" . h($cmd) . "</pre>");
$outputTrim = trim((string)$output);
if ($outputTrim === '') exit("Python no devolvió salida.\n\nComando:\n<pre>" . h($cmd) . "</pre>");

$data = json_decode($outputTrim, true);
if (!is_array($data)) {
  $ini = strpos($outputTrim, '{');
  $fin = strrpos($outputTrim, '}');
  if ($ini !== false && $fin !== false && $fin > $ini) {
    $jsonOnly = substr($outputTrim, $ini, $fin - $ini + 1);
    $data = json_decode($jsonOnly, true);
  }
}
if (!is_array($data)) {
  exit(
    "La salida no fue JSON válido. Salida cruda:\n\n" .
    "<pre style='white-space:pre-wrap; font-family: ui-monospace,Consolas,monospace;'>" . h($output) . "</pre>" .
    "\nComando:\n<pre style='white-space:pre-wrap; font-family: ui-monospace,Consolas,monospace;'>" . h($cmd) . "</pre>"
  );
}

/* =========================
   Datos para UI
========================= */
$periodos = $data['periodos_sin_empalme'] ?? [];
$tramos   = $data['tramos_1750'] ?? [];
$totales  = $data['totales'] ?? ['dias_unicos'=>0,'dias_usados'=>0,'faltan_para_1750'=>1750];
$warn     = $data['warning'] ?? null;
$debug    = $data['debug'] ?? null;
$fechaReporte = '';

if (isset($data['fecha_emision_reporte']) && is_array($data['fecha_emision_reporte'])) {
  $fechaReporte = (string)($data['fecha_emision_reporte']['fecha_emision'] ?? '');
}

// Persona fallback
$persona = ['curp'=>'', 'nss'=>'', 'nombre'=>'', 'semanas'=>''];
$preview = (string)($debug['preview'] ?? '');
if ($preview !== '') $persona = extraer_persona_desde_texto($preview);

$persona_py = (isset($data['persona']) && is_array($data['persona'])) ? $data['persona'] : [];

$semRegistradas  = (string)($persona_py['semanas_imss'] ?? '');
$semDescontadas  = (string)($persona_py['semanas_descontadas'] ?? '');
$semReintegradas = (string)($persona_py['semanas_reintegradas'] ?? '');
$semTotales      = (string)($persona_py['semanas_totales'] ?? '');

$nombre_py = limpiar_nombre((string)($persona_py['nombre'] ?? ''));
$nombre_fb = limpiar_nombre((string)($persona['nombre'] ?? ''));
$nombre_final = $nombre_py !== '' ? $nombre_py : $nombre_fb;

$persona_py['nombre'] = $nombre_py;
$persona['nombre'] = $nombre_final;

if ($semTotales === '') $semTotales = (string)($persona['semanas'] ?? '');

// Helpers semanas
$onlyDigits = function($v) { $v = preg_replace('/\D+/', '', (string)$v); return $v === '' ? '' : $v; };
$inRange = function($v) { if ($v === '') return ''; $n = (int)$v; return ($n >= 0 && $n <= 6000) ? (string)$n : ''; };

$semRegistradas  = $inRange($onlyDigits($semRegistradas));
$semDescontadas  = $inRange($onlyDigits($semDescontadas));
$semReintegradas = $inRange($onlyDigits($semReintegradas));
$semTotales      = $inRange($onlyDigits($semTotales));

if ($semRegistradas === '' && $semReintegradas !== '' && ($semDescontadas === '' || $semDescontadas === '0')) {
  $semRegistradas  = $semReintegradas;
  $semReintegradas = '0';
}
if ($semTotales === '' && $semRegistradas !== '') {
  $calc = (int)$semRegistradas - (int)($semDescontadas !== '' ? $semDescontadas : 0) + (int)($semReintegradas !== '' ? $semReintegradas : 0);
  if ($calc < 0) $calc = 0;
  $semTotales = (string)$calc;
}

/* =========================
   Rows base (Tabla 3) desde $tramos (para JS)
========================= */
$rowsBase = [];
$totalGeneral = 0.0;


$rowsAll = [];

if (!empty($periodos)) {
  foreach ($periodos as $p) {
    $desde = (string)($p['desde'] ?? '');
    $hasta = (string)($p['hasta'] ?? '');
    $dias  = (int)($p['dias'] ?? 0);

    $salDisp = trim((string)($p['salario_display'] ?? ''));
    if ($salDisp === '') $salDisp = trim((string)($p['salario'] ?? ''));

    $tokens = salario_tokens($salDisp);
    $sumSal = isset($p['salario_suma']) ? (float)$p['salario_suma'] : array_sum($tokens);

    $base = (isset($p['salario']) && $p['salario'] !== null && $p['salario'] !== '')
      ? (float)$p['salario']
      : (!empty($tokens) ? max($tokens) : 0.0);

    $empalmes = max(0.0, $sumSal - $base);
    $subtotal = $dias * $sumSal;

    $rowsAll[] = [
      'desde'    => $desde,
      'hasta'    => $hasta,
      'periodo'  => $desde . ' - ' . $hasta,
      'salarios' => $salDisp,
      'base'     => $base,
      'empalmes' => $empalmes,
      'suma'     => $sumSal,
      'dias'     => $dias,
      'subtotal' => $subtotal,
    ];
  }
}



if (!empty($tramos)) {
  foreach ($tramos as $t) {
    $desde = (string)($t['desde'] ?? '');
    $hasta = (string)($t['hasta'] ?? '');
    $dias  = (int)($t['dias'] ?? 0);

    $salDisp = trim((string)($t['salario_display'] ?? ''));
    if ($salDisp === '') $salDisp = trim((string)($t['salario'] ?? ''));

    $tokens = salario_tokens($salDisp);
    $sumSal = isset($t['salario_suma']) ? (float)$t['salario_suma'] : array_sum($tokens);

    $base = (isset($t['salario']) && $t['salario'] !== null && $t['salario'] !== '')
      ? (float)$t['salario']
      : (!empty($tokens) ? max($tokens) : 0.0);

    $empalmes = max(0.0, $sumSal - $base);
    $subtotal = $dias * $sumSal;
    $totalGeneral += $subtotal;

    $rowsBase[] = [
      'desde'    => $desde,
      'hasta'    => $hasta,
      'periodo'  => $desde . ' - ' . $hasta,
      'salarios' => $salDisp,
      'base'     => $base,
      'empalmes' => $empalmes,
      'suma'     => $sumSal,
      'dias'     => $dias,
      'subtotal' => $subtotal,
    ];
  }
}

$anual   = $totalGeneral / 5;
$mensual = $totalGeneral / 60;
$diario  = $totalGeneral / 1750;

/* =========================
   ✅ Última fecha REAL (robusta) combinando periodos y tramos
========================= */
$maxDt = null;

// periodos_sin_empalme
foreach ($periodos as $p) {
  $dt = parse_fecha_any((string)($p['hasta'] ?? ''));
  if ($dt && (!$maxDt || $dt > $maxDt)) $maxDt = $dt;
}
// tramos_1750
foreach ($tramos as $t) {
  $dt = parse_fecha_any((string)($t['hasta'] ?? ''));
  if ($dt && (!$maxDt || $dt > $maxDt)) $maxDt = $dt;
}
// fallback: último rowBase
if (!$maxDt && !empty($rowsBase)) {
  $last = end($rowsBase);
  $dt = parse_fecha_any((string)($last['hasta'] ?? ''));
  if ($dt) $maxDt = $dt;
}

$ultima_fecha = fmt_dmy($maxDt);

// ✅ Tu endpoint real UMA
$UMA_ENDPOINT = '/analisis/get_uma.php';
?>
<!doctype html>
<html lang="es">
<head>
  <!-- ✅ Opción recomendada: bundle (trae html2canvas + jsPDF) -->
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

  <meta charset="utf-8">
  <title>Resultado 1750 días</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<style>
  .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
  .soft-card{ background: #d9f4ff; border: 1px solid rgba(15,23,42,.12); box-shadow: 0 6px 18px rgba(2,6,23,.06); }
  .soft-label{ font-weight:800; color:#0b2f55; font-size:.95rem; }
  .hint{ font-size:.85rem; color:#0f172a; opacity:.75; }
  .btn-pill{ border-radius: 999px; padding-left: 18px; padding-right: 18px; }
  .input-pill{ border-radius: 14px; }
  .mini-card{ background: rgba(255,255,255,.55); border: 1px solid rgba(15,23,42,.12); border-radius: 18px; padding: 14px; height: 100%; }
  .mini-title{ font-weight: 900; color:#0b2f55; margin-bottom: 8px; }
  #tablaPrincipal thead th{ background:#0b2f55 !important; color:#fff !important; font-weight:800 !important; }
  .detalle-dias table { font-size: 0.85em; }
  @media print {
    body * { visibility: hidden; }
    .print-section, .print-section * { visibility: visible; }
    .print-section { position:absolute; left:0; top:0; width:100%; padding:0; margin:0; }
    .no-print, .no-print * { display:none !important; }
    .no-impresion, .no-impresion * { display:none !important; }
    details { display:block !important; }
    summary { display:none !important; }
    .table-responsive, .overflow-auto, .overflow-scroll{ overflow: visible !important; max-height: none !important; max-width: none !important; }
  }
  @media print{ .print-metric .fs-4{ font-size: 22px !important; font-weight: 800 !important; } }
</style>

<style>
  .periodos-toggle .caret{
    display:inline-block;
    transition: transform .2s ease;
  }
  .periodos-toggle[aria-expanded="true"] .caret{
    transform: rotate(180deg);
  }
  .tabla3-fab{
  position: fixed;
  right: 16px;
  bottom: 24px;
  z-index: 1050;

  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: flex-end;

  /* ✅ para que el contenedor no bloquee clicks */
  pointer-events: none;
}
.tabla3-fab .btn{
  pointer-events: auto;
  white-space: nowrap;
}

/* (opcional) ancho parejo */
.tabla3-fab .btn{
  width: 240px; /* ajusta a tu gusto */
}


.year-row td{
  background:#dbeafe !important;
  color:#0b2f55 !important;
  font-weight:800 !important;
  text-transform:uppercase;
  letter-spacing:.5px;
  padding:6px 8px !important;
}
</style>

</head>

<body class="bg-light">
<div class="container py-4 no-print">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="m-0">Resultado</h4>
  </div>

  <?php if (!empty($warn)): ?>
    <div class="alert alert-warning"><?= h($warn) ?></div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-semibold mb-2">Totales</div>
          <div>Días únicos (sin empalmes): <span class="fw-bold"><?= h($totales['dias_unicos'] ?? 0) ?></span></div>
          <div>Días usados (hasta 1750): <span class="fw-bold"><?= h($totales['dias_usados'] ?? 0) ?></span></div>
          <div>Restantes para 1750: <span class="fw-bold"><?= h($totales['faltan_para_1750'] ?? 0) ?></span></div>
          <hr>
          <div class="text-muted mono" style="font-size:.9rem">
            Archivo procesado: <?= h($_FILES['pdf']['name'] ?? 'PDF') ?>
          </div>
        </div>
      </div>
    </div>

<div class="col-12 col-lg-8">
  <div class="card shadow-sm">

    <!-- HEADER clickeable -->
    <div class="card-header p-0">
      <button
        class="btn w-100 text-start fw-semibold d-flex justify-content-between align-items-center periodos-toggle"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#collapsePeriodosFinales"
        aria-expanded="false"
        aria-controls="collapsePeriodosFinales"
      >
        <span>1) Periodos finales (segmentados)</span>
        <span class="caret">▾</span>
      </button>
    </div>

    <!-- ✅ INICIA CERRADO -->
    <div id="collapsePeriodosFinales" class="collapse">
      <div class="card-body">

        <?php if (!$periodos): ?>
          <div class="text-danger">No se pudieron construir periodos.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Desde</th>
                  <th>Hasta</th>
                  <th>Salario(s)</th>
                  <th class="text-end">Suma</th>
                  <th class="text-end">Días</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($periodos as $i => $p): ?>
                <?php
                  $disp = trim((string)($p['salario_display'] ?? ''));
                  $suma = isset($p['salario_suma']) ? (float)$p['salario_suma'] : salario_sum($disp);
                  $dias = (int)($p['dias'] ?? 0);
                ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= h((string)($p['desde'] ?? '')) ?></td>
                  <td><?= h((string)($p['hasta'] ?? '')) ?></td>
                  <td><?= h($disp) ?></td>
                  <td class="text-end"><?= h(money($suma)) ?></td>
                  <td class="text-end"><?= h((string)$dias) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      </div>
    </div>

  </div>
</div>


<div class="col-12">
  <div class="card shadow-sm">

    <!-- HEADER clickeable -->
    <div class="card-header p-0">
      <button
        class="btn w-100 text-start fw-semibold d-flex justify-content-between align-items-center periodos-toggle"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#collapseTramos1750"
        aria-expanded="false"
        aria-controls="collapseTramos1750"
      >
        <span>2) Tramos usados para llegar a 1750</span>
        <span class="caret">▾</span>
      </button>
    </div>

    <!-- ✅ INICIA CERRADO -->
    <div id="collapseTramos1750" class="collapse">
      <div class="card-body">

        <?php if (!$tramos): ?>
          <div class="text-danger">No hubo tramos para sumar 1750.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Desde</th>
                  <th>Hasta</th>
                  <th>Salarios</th>
                  <th class="text-end">Suma</th>
                  <th class="text-end">Días del tramo</th>
                  <th class="text-end">Acumulado</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($tramos as $i => $t): ?>
                  <?php
                    $disp = trim((string)($t['salario_display'] ?? ''));
                    $suma = isset($t['salario_suma']) ? (float)$t['salario_suma'] : salario_sum($disp);
                  ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= h((string)($t['desde'] ?? '')) ?></td>
                    <td><?= h((string)($t['hasta'] ?? '')) ?></td>
                    <td><?= h($disp) ?></td>
                    <td class="text-end"><?= h(money($suma)) ?></td>
                    <td class="text-end"><?= h((string)($t['dias'] ?? '')) ?></td>
                    <td class="text-end"><?= h((string)($t['acumulado'] ?? '')) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      </div>
    </div>

  </div>
</div>


<!-- BOTONES FLOTANTES -->
<div class="tabla3-fab no-print" role="group" aria-label="Acciones Tabla 3">
<a class="btn btn-outline-secondary btn-pill shadow" href="https://sempiternal-v1-production.up.railway.app/index.php">
  <i class="bi bi-arrow-left"></i> Volver
</a>

  <a class="btn btn-outline-secondary btn-pill shadow" href="/subir.php">
    <i class="bi bi-arrow-repeat"></i> Subir otro
  </a>
  
  <button type="button" class="btn btn-outline-danger btn-pill shadow" id="btnSavePdfTabla3">
    <i class="bi bi-filetype-pdf"></i> Guardar
  </button>

  <button type="button" onclick="imprimirTabla3()" class="btn btn-primary btn-pill shadow">
    <i class="bi bi-printer"></i> Imprimir
  </button>
<button type="button" class="btn btn-outline-dark btn-pill shadow" id="btnEnviarDatos">
  Enviar datos al otro HTML
</button>

<button type="button" class="btn btn-warning btn-pill shadow" id="btnResetTabla3">
  <i class="bi bi-arrow-counterclockwise"></i> Restablecer
</button>


</div>



    </div>

    <div class="col-12">
      <div class="card shadow-sm print-section" id="tabla3">
        <div class="card-body">
          <div class="fw-semibold mb-2">3) Resumen (salario base + empalmes + subtotal + total)</div>

          <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered align-middle" style="width:auto; min-width:360px;">
              <thead>
                <tr>
                  <th>CURP</th>
                  <th>NSS</th>
                  <th>Nombre</th>
                  <th>Fecha de emisión</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="mono"><?= h($persona['curp'] ?? '') ?></td>
                  <td class="mono"><?= h($persona['nss'] ?? '') ?></td>
                  <td><?= h($persona['nombre'] ?? '') ?></td>
                  <td class="mono"><?= h($fechaReporte) ?></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between align-items-end mb-3 print-metric">
            <div>
              <div class="fw-semibold">Salario promedio diario:</div>
              <div class="fs-4 fw-bold">$ <span id="metricDiario"><?= h(money((float)$diario)) ?></span></div>
            </div>

            <div class="text-end">
              <div class="fw-semibold">Total semanas cotizadas:</div>
              <div class="fs-4 fw-bold">
                <span id="metricSemanas"><?= h((string)($semTotales !== '' ? $semTotales : ($persona['semanas'] ?? ''))) ?></span>
              </div>
              <div class="small text-muted" id="metricSemanasHint"></div>
            </div>

          </div>



          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle" id="tablaPrincipal">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Periodo</th>
                  <th>Salario(s)</th>
                  <th class="text-end">Salario base</th>
                  <th class="text-end">Empalmes</th>
                  <th class="text-end">Suma total</th>
                  <th class="text-end">Días</th>
                  <th class="text-end">Subtotal (días × suma)</th>
                  <th class="no-impresion text-center">Acciones</th>
                  <th class="no-impresion">Días (detalle)</th>
                </tr>
              </thead>
              <tbody id="tbodyTabla3"></tbody>
            </table>

            <div class="row g-2" style="margin-top: 20px;">
              <div class="col-12 col-md-6" id="printSemanas">
                <table class="table table-sm table-bordered align-middle mb-0">
                  <tbody>
                    <tr><th class="text-start">Semanas registradas</th><td class="text-end"><?= h($semRegistradas) ?></td></tr>
                    <tr><th class="text-start">Semanas descontadas por desempleo</th><td class="text-end"><?= h($semDescontadas) ?></td></tr>
                    <tr><th class="text-start">Semanas reintegradas</th><td class="text-end"><input id="inpSemReintegradas" type="number" inputmode="numeric"min="0"max="6000"step="1"value="<?= h($semReintegradas) ?>"class="form-control form-control-sm text-end" style="display:inline-block; width:110px;" ></td></tr>

                    <tr><th class="text-start">Semanas Mod 40 (agregadas)</th><td class="text-end" id="cellSemMod40">0</td></tr>
                    <tr class="table-secondary">
  <th class="text-start fw-bold">Semanas totales</th>
  <td class="text-end fw-bold" id="cellSemTotales"><?= h($semTotales) ?></td>
</tr>

                  </tbody>
                </table>
              </div>

              <div class="col-12 col-md-6" id="tablaTotalesFinal">
                <table class="table table-sm table-bordered align-middle mb-0">
                  <tbody>
                    <tr class="table-secondary"><th class="text-end">TOTAL</th><td class="text-end fw-bold" id="totTotal"><?= h(money((float)$totalGeneral)) ?></td></tr>
                    <tr><th class="text-end">ANUAL (TOTAL / 5)</th><td class="text-end" id="totAnual"><?= h(money((float)$anual)) ?></td></tr>
                    <tr><th class="text-end">MENSUAL (TOTAL / 60)</th><td class="text-end" id="totMensual"><?= h(money((float)$mensual)) ?></td></tr>
                    <tr><th class="text-end">DIARIO (TOTAL / 1750)</th><td class="text-end" id="totDiario"><?= h(money((float)$diario)) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

          <div class="soft-card rounded-4 p-3 mb-3">
            <div class="row g-3">
              <div class="col-12 col-lg-4">
                <div class="mini-card">
                  <div class="mini-title">1) Inicio de días nuevos</div>

                  <div class="soft-label mb-1">Modo de inicio</div>
                  <select id="startMode" class="form-select input-pill">
                    <option value="last" selected>Desde última fecha de la tabla (<?= h($ultima_fecha ?: 'N/D') ?>) → día siguiente</option>
                    <option value="today">Desde hoy</option>
                    <option value="pick">Elegir año y mes (año siguiente)</option>
                    <option value="exact">Elegir fecha exacta</option>
                  </select>

                  <div id="pickWrap" class="mt-3 d-none">
                    <div class="row g-2">
                      <div class="col-6">
                        <div class="soft-label mb-1">Año</div>
                        <select id="pickYear" class="form-select input-pill"></select>
                      </div>
                      <div class="col-6">
                        <div class="soft-label mb-1">Mes</div>
                        <select id="pickMonth" class="form-select input-pill"></select>
                      </div>
                    </div>
                    <div class="hint mt-2" id="pickHint">—</div>
                  </div>

                  <div id="exactWrap" class="mt-3 d-none">
                    <div class="soft-label mb-1">Fecha exacta de inicio</div>
                    <input id="exactStartDate" type="date" class="form-control input-pill">
                    <div class="hint mt-2" id="exactHint">
                      En este modo puedes iniciar desde cualquier día específico.
                    </div>
                  </div>

                  <div class="hint mt-3" id="startPreview">—</div>
                </div>
              </div>

              <div class="col-12 col-lg-4">
                <div class="mini-card">
                  <div class="mini-title">2) Duración a agregar</div>

                  <div class="row g-2 align-items-end">
                    <div class="col-4">
                      <div class="soft-label mb-1">Años</div>
                      <input id="addYears" type="number" class="form-control input-pill" value="0" min="0" max="200">
                    </div>
                    <div class="col-4">
                      <div class="soft-label mb-1">Meses</div>
                      <input id="addMonths" type="number" class="form-control input-pill" value="0" min="0" max="2400">
                    </div>
                    <div class="col-4">
                      <div class="soft-label mb-1">Días</div>
                      <input id="addDays" type="number" class="form-control input-pill" value="0" min="0" max="50000">
                    </div>
                  </div>

                  <div class="hint mt-3">
                    Se agregarán días desde el <b>inicio calculado</b> y se recortarán días viejos para mantener 1750.
                    <br><b>✅ Ahora al eliminar días agregados, se reponen fechas viejas automáticamente.</b>
                  </div>

                  <div id="infoAjuste" class="mt-3 small fw-semibold text-primary"></div>
                </div>
              </div>

              <div class="col-12 col-lg-4">
                <div class="mini-card">
                  <div class="mini-title">3) Salario aplicado (automático)</div>

                  <div class="row g-2 align-items-start">
                    <div class="col-12">
                      <div class="d-flex gap-2 align-items-center">
                        <select id="salaryYear" class="form-select input-pill" style="max-width: 190px;"></select>
                        <div class="small">
                          <div><b>UMA:</b> <span id="umaVal">—</span></div>
                          <div><b>Tope:</b> <span id="topVal">—</span></div>
                          <div><b>P. garantizada:</b> <span id="pgVal">—</span></div>
                        </div>
                      </div>
                    </div>

                    <div class="col-12 mt-1">
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="soft-label mb-1">Salario del año</div>
                        <span class="hint">permite “+”</span>
                      </div>
                      <input id="addSalary" type="text" class="form-control input-pill"
                             placeholder="Ej: 646.69  ó  564.24 + 2038.77">
                      <div class="hint mt-2" id="salarySource">—</div>
                    </div>

                    <div class="col-12 d-grid mt-2">
                      <button id="btnApplyAdjust" class="btn btn-primary btn-pill">
                        Aplicar ajuste
                      </button>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>

  </div>
</div>

<script>
// ✅ fingerprint del PDF actual (depende del NSS/última fecha/rows)
const PDF_FINGERPRINT = <?= json_encode(
  (string)(
    ($persona['nss'] ?? 'SIN_NSS')
    . '|'
    . ($ultima_fecha ?? 'SIN_FECHA')
    . '|'
    . count($rowsBase)
  ),
  JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
) ?>;


/* =========================
   Datos base desde PHP
========================= */
const ROWS_BASE = <?= json_encode($rowsBase, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
const ROWS_ALL = <?= json_encode($rowsAll, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
const UMA_ENDPOINT = <?= json_encode($UMA_ENDPOINT, JSON_UNESCAPED_SLASHES) ?>;
const LAST_TABLE_DATE_STR = <?= json_encode((string)($ultima_fecha ?: '')) ?>; // DD/MM/YYYY

/* =========================
   Utilidades JS
========================= */
function fmtMoney(n){
  const x = Number(n||0);
  return x.toLocaleString('es-MX', { minimumFractionDigits:2, maximumFractionDigits:2 });
}
function escapeHtml(str){
  return String(str||'')
    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
    .replaceAll('"','&quot;').replaceAll("'","&#039;");
}
function parseDateAny(s){
  s = String(s||'').trim();
  if(!s) return null;
  s = s.replace(/\s+/g,'');

  const m1 = s.match(/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/);
  if (m1){
    const d = Number(m1[1]), mo = Number(m1[2]), y = Number(m1[3]);
    const dt = new Date(Date.UTC(y, mo-1, d));
    if (dt.getUTCFullYear()===y && (dt.getUTCMonth()+1)===mo && dt.getUTCDate()===d) return dt;
    return null;
  }
  const m2 = s.match(/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2})$/);
  if (m2){
    const d = Number(m2[1]), mo = Number(m2[2]), yy = Number(m2[3]);
    const y = (yy < 70) ? (2000+yy) : (1900+yy);
    const dt = new Date(Date.UTC(y, mo-1, d));
    if (dt.getUTCFullYear()===y && (dt.getUTCMonth()+1)===mo && dt.getUTCDate()===d) return dt;
    return null;
  }
  const m3 = s.match(/^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})$/);
  if (m3){
    const y = Number(m3[1]), mo = Number(m3[2]), d = Number(m3[3]);
    const dt = new Date(Date.UTC(y, mo-1, d));
    if (dt.getUTCFullYear()===y && (dt.getUTCMonth()+1)===mo && dt.getUTCDate()===d) return dt;
    return null;
  }
  return null;
}
function fmtDMY(dt){
  const d = String(dt.getUTCDate()).padStart(2,'0');
  const m = String(dt.getUTCMonth()+1).padStart(2,'0');
  const y = dt.getUTCFullYear();
  return `${d}/${m}/${y}`;
}
function addDays(dt, n){ return new Date(dt.getTime() + n*86400000); }
function diffDays(a,b){
  const A = Date.UTC(a.getUTCFullYear(),a.getUTCMonth(),a.getUTCDate());
  const B = Date.UTC(b.getUTCFullYear(),b.getUTCMonth(),b.getUTCDate());
  return Math.round((B-A)/86400000);
}
function parseSalaryTokens(s){
  const raw = String(s||'').trim();
  if(!raw) return [];
  const parts = raw.split('+').map(x=>x.trim()).filter(Boolean);
  const vals = [];
  for(const p0 of parts){
    let p = p0.replaceAll('$','').replaceAll(' ','');
    if ((p.match(/,/g)||[]).length===1 && (p.match(/\./g)||[]).length===0){
      p = p.replace(',','.');
    } else {
      p = p.replaceAll(',','');
    }
    const v = Number(p);
    if (!Number.isFinite(v)) continue;
    vals.push(v);
  }
  const set = new Set();
  const out = [];
  for(const v of vals){
    const k = String(v);
    if(!set.has(k)){ set.add(k); out.push(v); }
  }
  return out;
}
function toUTC00Local(dt){
  return new Date(Date.UTC(dt.getFullYear(), dt.getMonth(), dt.getDate()));
}
function addMonthsUTC(dateUTC, months){
  const y = dateUTC.getUTCFullYear();
  const m = dateUTC.getUTCMonth();
  const d = dateUTC.getUTCDate();
  const target = new Date(Date.UTC(y, m + months, 1));
  const lastDay = new Date(Date.UTC(target.getUTCFullYear(), target.getUTCMonth()+1, 0)).getUTCDate();
  target.setUTCDate(Math.min(d, lastDay));
  return target;
}
function addYearsUTC(dateUTC, years){
  return addMonthsUTC(dateUTC, years*12);
}

/* =========================
   Expandir rows -> días
========================= */
function expandToDays(rows){
  const days = [];
  for (const r of rows){
    const d1 = parseDateAny(r.desde);
    const d2 = parseDateAny(r.hasta);
    if(!d1 || !d2) continue;

    const start = d1.getTime() <= d2.getTime() ? d1 : d2;
    const end   = d1.getTime() <= d2.getTime() ? d2 : d1;

    const base = Number(r.base||0);
    const emp  = Number(r.empalmes||0);
    const suma = Number(r.suma||0);
    const sal  = String(r.salarios||'');

    for (let dt = start; dt.getTime() <= end.getTime(); dt = addDays(dt, 1)){
days.push({
  ymd: dt.toISOString().slice(0,10),
  dt: new Date(dt.getTime()),
  base, emp, suma,
  sal,
  origin: 'base',
  hastaLabel: r.hasta_label || r.hasta || ''
});
    }
  }
  days.sort((a,b)=>a.dt.getTime()-b.dt.getTime());
  return days;
}

/* =========================
   Agrupar días consecutivos
========================= */
function groupDays(days){
  if(!days.length) return [];
  const out = [];
  let cur = { ...days[0], start: days[0].dt, end: days[0].dt, dias: 1 };

function same(a,b){
  return a.suma === b.suma &&
         a.base === b.base &&
         a.emp === b.emp &&
         a.sal === b.sal &&
         a.origin === b.origin &&
         (a.hastaLabel || '') === (b.hastaLabel || '');
}

  for (let i=1;i<days.length;i++){
    const d = days[i];
    const prev = days[i-1];
    const isNext = diffDays(prev.dt, d.dt) === 1;

    if (isNext && same(cur, d)){
      cur.end = d.dt;
      cur.dias += 1;
    } else {
      out.push(cur);
      cur = { ...d, start: d.dt, end: d.dt, dias: 1 };
    }
  }
  out.push(cur);

  return out.map(g=>{
    const desde = fmtDMY(g.start);
    const hasta = fmtDMY(g.end);
    const dias  = g.dias;
    const subtotal = dias * g.suma;
    return {
      desde, hasta,
      startYMD: g.start.toISOString().slice(0,10),
      endYMD: g.end.toISOString().slice(0,10),
      origin: g.origin,
      periodo: `${desde} - ${g.hastaLabel === 'Actual' ? 'Actual' : hasta}`,
      salarios: g.sal,
      base: g.base,
      empalmes: g.emp,
      suma: g.suma,
      dias,
      subtotal
    };
  });
}

/* =========================
   Tabla 3 + totales
========================= */
function buildDetailsHTML(r){
  const id = `det_${r.startYMD}_${r.endYMD}`.replaceAll('-','');
  const maxRows = Math.min(250, Number(r.dias||0));
  return `
    <details class="detalle-dias">
      <summary class="btn btn-sm btn-outline-primary">Ver ${r.dias} días</summary>
      <div class="mt-2" style="max-height:220px; overflow:auto;">
        <table class="table table-sm table-bordered align-middle mono mb-0">
          <thead>
            <tr>
              <th class="text-nowrap">Fecha</th>
              <th class="text-end text-nowrap">Salario base</th>
              <th class="text-end text-nowrap">Empalme</th>
              <th class="text-end text-nowrap">Suma total</th>
            </tr>
          </thead>
          <tbody id="${id}"></tbody>
        </table>
        ${(r.dias>maxRows) ? `<div class="text-muted small mt-1">Mostrando ${maxRows} de ${r.dias} días.</div>` : ``}
      </div>
    </details>
  `;
}
function fillDetailsRows(r){
  const id = `det_${r.startYMD}_${r.endYMD}`.replaceAll('-','');
  const tb = document.getElementById(id);
  if(!tb) return;

  tb.innerHTML = '';
  const dtStart = new Date(r.startYMD + "T00:00:00Z");
  const dtEnd   = new Date(r.endYMD   + "T00:00:00Z");

  const lo = dtStart.getTime();
  const hi = dtEnd.getTime();

  const maxRows = Math.min(250, Number(r.dias||0));
  let count = 0;

  for(let t = hi; t >= lo; t -= 86400000){
    const dt = new Date(t);
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="text-nowrap">${fmtDMY(dt)}</td>
      <td class="text-end text-nowrap">$ ${fmtMoney(r.base)}</td>
      <td class="text-end text-nowrap">$ ${fmtMoney(r.empalmes)}</td>
      <td class="text-end fw-semibold text-nowrap">$ ${fmtMoney(r.suma)}</td>
    `;
    tb.appendChild(tr);
    count++;
    if(count>=maxRows) break;
  }
}
function renderTabla3(rows){
  const tb = document.getElementById('tbodyTabla3');
  if(!tb) return;
  tb.innerHTML = '';

  rows.forEach((r, idx)=>{
    const tr = document.createElement('tr');

const acciones = `
  <div class="d-flex gap-2 justify-content-center">
    ${
      r.origin === 'added'
        ? `<button type="button" class="btn btn-sm btn-outline-primary btn-edit-row" data-start="${r.startYMD}" data-end="${r.endYMD}" title="Editar salario">
             <i class="bi bi-pencil-square"></i>
           </button>`
        : ``
    }

    <button type="button" class="btn btn-sm btn-outline-danger btn-del-row" data-start="${r.startYMD}" data-end="${r.endYMD}" title="Eliminar periodo">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>
`;

    tr.innerHTML = `
      <td class="text-nowrap">${idx+1}</td>
      <td class="text-nowrap">${r.periodo}</td>
      <td class="text-nowrap">${escapeHtml(r.salarios||'')}</td>
      <td class="text-end text-nowrap">${fmtMoney(r.base)}</td>
      <td class="text-end text-nowrap">${fmtMoney(r.empalmes)}</td>
      <td class="text-end text-nowrap">${fmtMoney(r.suma)}</td>
      <td class="text-end text-nowrap">${r.dias}</td>
      <td class="text-end fw-semibold text-nowrap">${fmtMoney(r.subtotal)}</td>
      <td class="no-impresion text-center">${acciones}</td>
      <td class="no-impresion text-center">${buildDetailsHTML(r)}</td>
    `;
    tb.appendChild(tr);
  });
}
function updateTotals(rows){
  let total = 0;
  for(const r of rows) total += Number(r.subtotal||0);

  document.getElementById('totTotal').textContent   = fmtMoney(total);
  document.getElementById('totAnual').textContent   = fmtMoney(total/5);
  document.getElementById('totMensual').textContent = fmtMoney(total/60);
  document.getElementById('totDiario').textContent  = fmtMoney(total/1750);
  document.getElementById('metricDiario').textContent = fmtMoney(total/1750);
}
function renderFromDays(days){
  const rows = groupDays(days);
  renderTabla3(rows);
  updateTotals(rows);
  updateLastOptionLabel();
  updateStartAndSalaryUI();

  updateWeeksUI();

  // ✅ SOLO si existe
  if (typeof guardarResumenParaOtroHTML === 'function') {
    guardarResumenParaOtroHTML();
  }
}


/* =========================
   ✅ Base: días ventana inicial 1750 (del PDF)
========================= */
/* =========================
   ✅ Todas las fechas extraídas del PDF
========================= */
const DAYS_ALL = (() => {
  const source = Array.isArray(ROWS_ALL) && ROWS_ALL.length ? ROWS_ALL : ROWS_BASE;
  const d = expandToDays(source);

  d.sort((a, b) => a.dt.getTime() - b.dt.getTime());

  return d.map(x => ({
    ...x,
    dt: new Date(x.dt.getTime()),
    origin: 'base'
  }));
})();
/* =========================
   ✅ Ventana inicial de 1750 días
========================= */
const DAYS_BASE = (() => {
  if (DAYS_ALL.length > 1750) return DAYS_ALL.slice(DAYS_ALL.length - 1750);
  return DAYS_ALL;
})();

/* ✅ Estado actual */
let daysCurrent = DAYS_BASE.map(x => ({
  ...x,
  dt: new Date(x.dt.getTime()),
  origin: 'base'
}));
// ✅ Total real de días agregados (no depende del recorte a 1750)
let ADDED_DAYS_TOTAL = 0;
let DELETED_DAYS_TOTAL = 0;
let DELETED_YMDS = new Set();



const KEY_SP_STATE = 'CIP_SP_STATE_V1';

function saveSPState(){
  try{
    const st = {
      startMode: document.getElementById('startMode')?.value || 'last',
      pickYear: document.getElementById('pickYear')?.value || '',
      pickMonth: document.getElementById('pickMonth')?.value || '',
      exactStartDate: document.getElementById('exactStartDate')?.value || '',
      salaryYear: document.getElementById('salaryYear')?.value || '',

      addYears: document.getElementById('addYears')?.value || '0',
      addMonths: document.getElementById('addMonths')?.value || '0',
      addDays: document.getElementById('addDays')?.value || '0',
      addSalary: document.getElementById('addSalary')?.value || '',

      inpSemReintegradas: document.getElementById('inpSemReintegradas')?.value || '0',

      ADDED_DAYS_TOTAL: Number(ADDED_DAYS_TOTAL || 0),
      DELETED_DAYS_TOTAL: Number(DELETED_DAYS_TOTAL || 0),

      semanasTotales: document.getElementById('cellSemTotales')?.textContent || '',
      semanasMod40: document.getElementById('cellSemMod40')?.textContent || '',
      diario: document.getElementById('metricDiario')?.textContent || '',

      ts: Date.now()
    };

    sessionStorage.setItem(KEY_SP_STATE, JSON.stringify(st));
  }catch(e){
    console.error('saveSPState error', e);
  }
}

function restoreSPState(){
  const raw = sessionStorage.getItem(KEY_SP_STATE); // ✅ aquí
  if(!raw) return false;

  try{
    const st = JSON.parse(raw);

    const setVal = (id, v) => {
      const el = document.getElementById(id);
      if (el && v !== undefined && v !== null) el.value = String(v);
    };

    setVal('startMode', st.startMode);
    setVal('pickYear', st.pickYear);
    setVal('pickMonth', st.pickMonth);
    setVal('exactStartDate', st.exactStartDate);
    setVal('salaryYear', st.salaryYear);

    setVal('addYears', st.addYears);
    setVal('addMonths', st.addMonths);
    setVal('addDays', st.addDays);
    setVal('addSalary', st.addSalary);

    setVal('inpSemReintegradas', st.inpSemReintegradas);

    if (typeof st.DELETED_DAYS_TOTAL === 'number') DELETED_DAYS_TOTAL = st.DELETED_DAYS_TOTAL;

    // UI
    showOrHidePick();
    updateStartAndSalaryUI();
    recalcSemanasTotalesFromUI();
    updateWeeksUI();

    if (st.diario) {
      const m = document.getElementById('metricDiario');
      if (m) m.textContent = st.diario;
    }

    return true;
  }catch(e){
    console.error('restoreSPState error', e);
    return false;
  }
}



const BASE_SEMANAS = Number(<?= json_encode((string)($semTotales !== '' ? $semTotales : ($persona['semanas'] ?? '0'))) ?>) || 0;

function getAddedDaysCount(){
  let c = 0;
  for (const d of daysCurrent) if (d.origin === 'added') c++;
  return c;
}

function updateWeeksUI(){
  const addedDays = Number(ADDED_DAYS_TOTAL || 0);
  const deletedDays = Number(DELETED_DAYS_TOTAL || 0);

  const addedWeeks = Math.floor(addedDays / 7);
  const addedRemDays = addedDays % 7;

  const deletedWeeks = Math.floor(deletedDays / 7);
  const deletedRemDays = deletedDays % 7;

  let totalWeeks = BASE_SEMANAS + addedWeeks - deletedWeeks;
  if (totalWeeks < 0) totalWeeks = 0;

  const a = document.getElementById('metricSemanas');
  const b = document.getElementById('cellSemTotales');
  const c = document.getElementById('cellSemMod40');
  const hint = document.getElementById('metricSemanasHint');

  if (a) a.textContent = String(totalWeeks);
  if (b) b.textContent = String(totalWeeks);

  if (c) c.textContent = String(addedWeeks);

  if (hint){
    const partes = [];

    if (addedDays > 0) {
      partes.push(
        addedRemDays > 0
          ? `+${addedWeeks} semana(s) y ${addedRemDays} día(s)`
          : `+${addedWeeks} semana(s)`
      );
    }

    if (deletedDays > 0) {
      partes.push(
        deletedRemDays > 0
          ? `-${deletedWeeks} semana(s) y ${deletedRemDays} día(s) eliminados`
          : `-${deletedWeeks} semana(s) eliminadas`
      );
    }

    hint.textContent = partes.length ? `(${partes.join(' | ')})` : '';
  }

  recalcSemanasTotalesFromUI();
}
function clampInt(v, min, max){
  v = String(v ?? '').replace(/\D+/g,'');
  if (v === '') return 0;
  let n = parseInt(v, 10);
  if (!Number.isFinite(n)) n = 0;
  if (n < min) n = min;
  if (n > max) n = max;
  return n;
}

function recalcSemanasTotalesFromUI(){
  // base fijas del PHP
  const semRegistradas  = clampInt(<?= json_encode($semRegistradas) ?>, 0, 6000);
  const semDescontadas  = clampInt(<?= json_encode($semDescontadas) ?>, 0, 6000);

  // reintegradas EDITABLE
  const inp = document.getElementById('inpSemReintegradas');
  const semReintegradasEdit = clampInt(inp?.value, 0, 6000);

  // Mod40
  const mod40 = clampInt(
    document.getElementById('cellSemMod40')?.textContent,
    0,
    6000
  );

  // total = registradas - descontadas + reintegradas + mod40
  let total = semRegistradas - semDescontadas + semReintegradasEdit + mod40;
  if (total < 0) total = 0;

  // pintar total en tabla
  const cellTotal = document.getElementById('cellSemTotales');
  if (cellTotal) cellTotal.textContent = String(total);

  // pintar total en métrica superior
  const metric = document.getElementById('metricSemanas');
  if (metric) metric.textContent = String(total);

  // salario actual visible en Tabla 3
  const diarioActual =
    (document.getElementById('metricDiario')?.textContent || '').trim() ||
    '0.00';

  try {
    const payloadRaw = sessionStorage.getItem('CIP_DATOS_TABLA3');
    const payload = payloadRaw ? JSON.parse(payloadRaw) : {};

    /*
      ✅ ORIGINAL REAL DESDE TABLA 3
      Estos son los que necesita SP.
    */
    payload.semanasOriginalesTabla3 = String(semRegistradas);
    payload.semanasDescontadasTabla3 = String(semDescontadas);
    payload.semanasTotalesTabla3 = String(total - mod40); 
    payload.diarioOriginalTabla3 = payload.diarioOriginalTabla3 || payload.salarioOriginal || diarioActual;

    /*
      ✅ Compatibilidad con lo que ya tenías
    */
    payload.semanas = String(total);
    payload.diario = diarioActual;

    payload.semanasOriginales = String(semRegistradas);
    payload.semanasDescontadas = String(semDescontadas);
    payload.semanasDescontadasDesempleo = String(semDescontadas);

    payload.salarioOriginal = payload.salarioOriginal || diarioActual;
    payload.diarioOriginal1750 = payload.diarioOriginal1750 || payload.diarioOriginalTabla3 || diarioActual;

    /*
      ✅ Finales / inversión
    */
    payload.semanasTotalesFinales = String(total);
    payload.salarioInversion = diarioActual;
    payload.diario1750 = diarioActual;

    /*
      ✅ Reintegradas / Mod40
    */
    payload.semanasReintegradas = String(semReintegradasEdit);
    payload.semanas_reintegradas = String(semReintegradasEdit);
    payload.incrementoSemanas = String(mod40);
    payload.semanasMod40 = String(mod40);

    payload.updatedAt = Date.now();

    sessionStorage.setItem('CIP_DATOS_TABLA3', JSON.stringify(payload));

    console.log('✅ Semanas y valores originales guardados desde Tabla 3:');
    console.table({
      semanasOriginalesTabla3: payload.semanasOriginalesTabla3,
      semanasDescontadasTabla3: payload.semanasDescontadasTabla3,
      semanasTotalesTabla3: payload.semanasTotalesTabla3,
      diarioOriginalTabla3: payload.diarioOriginalTabla3,

      semanasOriginales: payload.semanasOriginales,
      semanasDescontadas: payload.semanasDescontadas,
      salarioOriginal: payload.salarioOriginal,

      semanasTotalesFinales: payload.semanasTotalesFinales,
      salarioInversion: payload.salarioInversion,
      semanasMod40: payload.semanasMod40
    });

  } catch (e) {
    console.error('Error guardando semanas en CIP_DATOS_TABLA3:', e);
  }
}

// ✅ cuando el usuario edite reintegradas
document.getElementById('inpSemReintegradas')?.addEventListener('input', (e)=>{
  // normaliza el valor (0..6000)
  const n = clampInt(e.target.value, 0, 6000);
  e.target.value = String(n);
  recalcSemanasTotalesFromUI();
});





/* ✅ HISTORIAL: para regresar al estado previo al ajuste */
const HISTORY = []; // stack de snapshots

function deepCloneDays(arr){
  return arr.map(d => ({
    ...d,
    dt: new Date(d.dt.getTime())
  }));
}

/* =========================
   ✅ Última fecha robusta
========================= */
function getLastDateFromCurrent(){
  const last = daysCurrent[daysCurrent.length - 1]?.dt;
  return last ? new Date(last.getTime()) : null;
}
function getLastTableDateFallback(){
  const dt = parseDateAny(LAST_TABLE_DATE_STR);
  return dt ? dt : null;
}
function getLastDateRobust(){
  return getLastDateFromCurrent() || getLastTableDateFallback() || null;
}

/* =========================
   Inicio (modo) + Pick año/mes
========================= */
const MONTHS = [
  {v:1,  t:'Ene'},{v:2,  t:'Feb'},{v:3,  t:'Mar'},{v:4,  t:'Abr'},
  {v:5,  t:'May'},{v:6,  t:'Jun'},{v:7,  t:'Jul'},{v:8,  t:'Ago'},
  {v:9,  t:'Sep'},{v:10, t:'Oct'},{v:11, t:'Nov'},{v:12, t:'Dic'}
];

function getBaseYearPick(){
  const last = getLastDateRobust();
  const today = toUTC00Local(new Date());
  const base = last ? last : today;
  return base.getUTCFullYear() + 1;
}

function updateLastOptionLabel(){
  const sel = document.getElementById('startMode');
  if (!sel) return;
  const opt = Array.from(sel.options).find(o=>o.value==='last');
  if (!opt) return;
  const last = getLastDateRobust();
  opt.textContent = `Desde última fecha de la tabla (${last ? fmtDMY(last) : 'N/D'}) → día siguiente`;
}

let pickInitialized = false;

function fillPickYears(){
  const ySel = document.getElementById('pickYear');
  if(!ySel) return;

  const keep = ySel.value;
  const baseYear = getBaseYearPick();

  const YEARS_FORWARD = 50; // 👈 antes era 5 (por eso te limitaba)
  ySel.innerHTML = '';

  for(let y = baseYear; y <= baseYear + YEARS_FORWARD; y++){
    const opt = document.createElement('option');
    opt.value = String(y);
    opt.textContent = String(y);
    ySel.appendChild(opt);
  }

  // conservar selección si existe
  if (keep && Array.from(ySel.options).some(o=>o.value===keep)) ySel.value = keep;
  else ySel.value = String(baseYear);
}


function fillPickMonths(){
  const mSel = document.getElementById('pickMonth');
  if(!mSel) return;

  const keep = mSel.value;

  mSel.innerHTML = '';
  for(const mo of MONTHS){
    const opt = document.createElement('option');
    opt.value = String(mo.v);
    opt.textContent = mo.t;
    mSel.appendChild(opt);
  }

  if (keep && Array.from(mSel.options).some(o=>o.value===keep)) mSel.value = keep;
  else mSel.value = '1';
}

function ensurePickFilled(){
  if (pickInitialized) return;
  fillPickYears();
  fillPickMonths();
  pickInitialized = true;
}

function showOrHidePick(){
  const mode = String(document.getElementById('startMode')?.value || 'last');

  const pickWrap = document.getElementById('pickWrap');
  const exactWrap = document.getElementById('exactWrap');

  if (pickWrap) {
    pickWrap.classList.toggle('d-none', mode !== 'pick');
  }

  if (exactWrap) {
    exactWrap.classList.toggle('d-none', mode !== 'exact');
  }

  if (mode === 'pick'){
    fillPickYears();
    fillPickMonths();
    pickInitialized = true;
  }

  if (mode === 'exact'){
    setDefaultExactDateIfEmpty();
  }
}

function updateStartPreviewText(startNew){
  const el = document.getElementById('startPreview');
  if(!el) return;
  el.innerHTML = `Inicia el <b>${fmtDMY(startNew)}</b>.`;
}
function updatePickHint(){
  const hint = document.getElementById('pickHint');
  if(!hint) return;
  hint.textContent = `En este modo, inicia siempre el día 01 del mes seleccionado.`;
}


function setDefaultExactDateIfEmpty(){
  const inp = document.getElementById('exactStartDate');
  if (!inp) return;

  if (inp.value) return;

  const last = getLastDateRobust();
  const today = toUTC00Local(new Date());

  const base = last ? addDays(last, 1) : today;

  inp.value = base.toISOString().slice(0, 10);
}



function computeStartNewUTC(){
  const mode = String(document.getElementById('startMode')?.value || 'last');
  const last = getLastDateRobust();
  const today = toUTC00Local(new Date());

  if (mode === 'today') {
    return today;
  }

  if (mode === 'last') {
    const anchor = last ? new Date(last.getTime()) : today;
    return addDays(anchor, 1);
  }

  if (mode === 'exact') {
    setDefaultExactDateIfEmpty();

    const val = document.getElementById('exactStartDate')?.value || '';

    if (val) {
      const parts = val.split('-').map(Number);
      const y = parts[0];
      const m = parts[1];
      const d = parts[2];

      return new Date(Date.UTC(y, m - 1, d));
    }

    return today;
  }

  // pick = año y mes
  ensurePickFilled();

  const ySel = document.getElementById('pickYear');
  const mSel = document.getElementById('pickMonth');

  const y = Number(ySel?.value || getBaseYearPick());
  const m = Number(mSel?.value || 1);

  return new Date(Date.UTC(y, m - 1, 1));
}

/* =========================
   UMA + salario automático
========================= */
const UMA_CACHE = new Map();

async function fetchUMA(year){
  const y = Number(year);
  if (UMA_CACHE.has(y)) return UMA_CACHE.get(y);

  const url = `${UMA_ENDPOINT}?year=${encodeURIComponent(y)}`;
  const res = await fetch(url, { headers: { 'Accept':'application/json' } });
  const json = await res.json();

  if (!json || !json.success) throw new Error(json?.message || 'No se pudo obtener UMA');

  UMA_CACHE.set(y, json.data);
  return json.data;
}

function findAutoSalaryFromTable(year){
  let best = null;
  let bestT = -Infinity;

  for(const r of ROWS_BASE){
    const dt = parseDateAny(r.hasta);
    if(!dt) continue;
    if(dt.getUTCFullYear() !== Number(year)) continue;
    const t = dt.getTime();
    if(t > bestT){ bestT = t; best = r; }
  }
  if(!best) return null;
  const s = String(best.salarios || '').trim();
  if(!s) return null;
  return { salStr: s, source: 'tabla', suma: Number(best.suma||0) };
}

function buildSalaryYearSelect(centerYear){
  const sel = document.getElementById('salaryYear');
  if(!sel) return;

  const y = Number(centerYear);
  sel.innerHTML = '';

  const BACK = 10;
  const FORWARD = 50;

  const ogPast = document.createElement('optgroup');
  ogPast.label = `Anteriores ${BACK} (${y-BACK}–${y})`;
  for(let yy=y; yy>=y-BACK; yy--){
    const opt = document.createElement('option');
    opt.value = String(yy);
    opt.textContent = `Año ${yy}`;
    ogPast.appendChild(opt);
  }

  const ogNext = document.createElement('optgroup');
  ogNext.label = `Próximos ${FORWARD} (${y+1}–${y+FORWARD})`;
  for(let yy=y+1; yy<=y+FORWARD; yy++){
    const opt = document.createElement('option');
    opt.value = String(yy);
    opt.textContent = `Año ${yy}`;
    ogNext.appendChild(opt);
  }

  sel.appendChild(ogPast);
  sel.appendChild(ogNext);
  sel.value = String(y);
}


let salaryManual = false;
document.getElementById('addSalary')?.addEventListener('input', ()=>{ salaryManual = true; });

async function setSalaryAutoForYear(year, opts = {}) {
  const preferTope = !!opts.preferTope; // ✅ si true, ignora tabla y usa Tope UMA

  const addSalary = document.getElementById('addSalary');
  const umaVal = document.getElementById('umaVal');
  const topVal = document.getElementById('topVal');
  const pgVal  = document.getElementById('pgVal');
  const srcEl  = document.getElementById('salarySource');

  if (umaVal) umaVal.textContent = '…';
  if (topVal) topVal.textContent = '…';
  if (pgVal)  pgVal.textContent  = '…';

  // ✅ si preferTope, NO se toma salario de tabla
  const fromTbl = preferTope ? null : findAutoSalaryFromTable(year);

  try{
    const d = await fetchUMA(year);

    if (umaVal) umaVal.textContent = Number(d.uma||0).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
    if (topVal) topVal.textContent = Number(d.topado||0).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
    if (pgVal)  pgVal.textContent  = Number(d.pension_garantizada||0).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});

    if (fromTbl){
      if(addSalary && !salaryManual) addSalary.value = fromTbl.salStr;
      if(srcEl) srcEl.innerHTML = `Salario del año: <b>$ ${fmtMoney(fromTbl.suma)}</b> (Año ${year}). Fuente: tabla.`;
    } else {
      if(addSalary && !salaryManual) addSalary.value = String(d.topado ?? '').trim();
      if(srcEl) srcEl.innerHTML = `Salario sugerido (Tope UMA): <b>$ ${fmtMoney(Number(d.topado||0))}</b> (Año ${year}).`;
    }
  }catch(err){
    if (umaVal) umaVal.textContent = '—';
    if (topVal) topVal.textContent = '—';
    if (pgVal)  pgVal.textContent  = '—';
    if (srcEl) srcEl.textContent = 'No se pudo obtener UMA. Verifica el endpoint.';
    console.error(err);
  }
}


async function updateStartAndSalaryUI(){
  showOrHidePick();

  const mode = String(document.getElementById('startMode')?.value || 'last');

  const startNew = computeStartNewUTC();
  updateStartPreviewText(startNew);
  updatePickHint();

  let yearForUMA = startNew.getUTCFullYear();

  if (mode === 'pick') {
    ensurePickFilled();
    const py = Number(document.getElementById('pickYear')?.value || yearForUMA);
    if (py) yearForUMA = py;
  }

  buildSalaryYearSelect(yearForUMA);

  const sSel = document.getElementById('salaryYear');
  if (sSel) {
    sSel.value = String(yearForUMA);
    sSel.disabled = (mode === 'pick');
  }

 await setSalaryAutoForYear(yearForUMA, { preferTope: (mode === 'last') });
  salaryManual = false;

}

/* =========================
   ✅ Relleno seguro a 1750 (si faltan)
   - Si daysCurrent queda vacío, regresa al último snapshot (HISTORY)
========================= */
function refillTo1750Safe(){
  if (daysCurrent.length === 0 && HISTORY.length){
    const snap = HISTORY.pop();
    daysCurrent = deepCloneDays(snap.before);
    ADDED_DAYS_TOTAL = Number(snap.addedTotal || 0);
    DELETED_DAYS_TOTAL = Number(snap.deletedTotal || 0);
    return;
  }

  if (daysCurrent.length >= 1750) {
    daysCurrent.sort((a,b)=>a.dt.getTime()-b.dt.getTime());
    daysCurrent = daysCurrent.slice(daysCurrent.length - 1750);
    return;
  }

  const needed = 1750 - daysCurrent.length;
  const set = new Set(daysCurrent.map(d => d.ymd));

  const first = daysCurrent[0]?.dt || null;
  const last  = daysCurrent[daysCurrent.length - 1]?.dt || null;

  const toAdd = [];
  let left = needed;

  /*
    Primero intenta rellenar hacia atrás:
    si eliminaste 20/08/2021 - 31/08/2021,
    busca días anteriores a 20/08/2021.
  */
  if (first) {
    for (let i = DAYS_ALL.length - 1; i >= 0 && left > 0; i--) {
      const d = DAYS_ALL[i];

if (
  d.dt.getTime() < first.getTime() &&
  !set.has(d.ymd) &&
  !DELETED_YMDS.has(d.ymd)
) {
  toAdd.push({
    ...d,
    dt: new Date(d.dt.getTime()),
    origin: 'base'
  });

  set.add(d.ymd);
  left--;
}
    }
  }

  /*
    Si todavía faltan días, intenta rellenar con cualquier día del PDF
    que no esté usado. Esto evita que la tabla quede incompleta.
  */
  if (left > 0) {
    for (let i = DAYS_ALL.length - 1; i >= 0 && left > 0; i--) {
      const d = DAYS_ALL[i];

if (!set.has(d.ymd) && !DELETED_YMDS.has(d.ymd)) {
  toAdd.push({
    ...d,
    dt: new Date(d.dt.getTime()),
    origin: 'base'
  });

  set.add(d.ymd);
  left--;
}
    }
  }

  daysCurrent = toAdd.concat(daysCurrent);
  daysCurrent.sort((a,b)=>a.dt.getTime()-b.dt.getTime());

  if (daysCurrent.length > 1750) {
    daysCurrent = daysCurrent.slice(daysCurrent.length - 1750);
  }
}

/* =========================
   Editar / eliminar rangos
========================= */
function updateSalaryRange(startYMD, endYMD, newSalStr){
  const toks = parseSalaryTokens(newSalStr);
  if (!toks.length) return false;

  const suma = toks.reduce((a,b)=>a+b,0);
  const base = Math.max(...toks);
  const emp  = Math.max(0, suma - base);

  const a = new Date(startYMD + "T00:00:00Z").getTime();
  const b = new Date(endYMD   + "T00:00:00Z").getTime();
  const lo = Math.min(a,b), hi = Math.max(a,b);

  for (const d of daysCurrent){
    const t = d.dt.getTime();
    if (d.origin === 'added' && t >= lo && t <= hi){
      d.sal = newSalStr;
      d.base = base;
      d.emp = emp;
      d.suma = suma;
    }
  }

  // ✅ ESTO DEBE IR ANTES DEL RETURN
  guardarEstadoSemanas();
  saveSPState();

  return true;
}


function resetTabla3ToOriginal(){
  if (!confirm('¿Restablecer la tabla al estado original del PDF?')) return;

  // ✅ Regresa la ventana original de 1750 días
  daysCurrent = DAYS_BASE.map(x => ({
    ...x,
    dt: new Date(x.dt.getTime()),
    origin: 'base'
  }));

  // ✅ Reinicia contadores
  ADDED_DAYS_TOTAL = 0;
  DELETED_DAYS_TOTAL = 0;

  // ✅ Desbloquea fechas eliminadas
  DELETED_YMDS = new Set();

  // ✅ Limpia historial
  if (Array.isArray(HISTORY)) {
    HISTORY.length = 0;
  }

  // ✅ Limpia inputs de ajuste
  const addYears = document.getElementById('addYears');
  const addMonths = document.getElementById('addMonths');
  const addDays = document.getElementById('addDays');
  const addSalary = document.getElementById('addSalary');
  const info = document.getElementById('infoAjuste');

  if (addYears) addYears.value = '0';
  if (addMonths) addMonths.value = '0';
  if (addDays) addDays.value = '0';
  if (addSalary) addSalary.value = '';

  if (info) {
    info.textContent = '✅ Tabla restablecida al estado original del PDF.';
  }

  // ✅ Limpia memoria temporal de la tabla
  try {
    sessionStorage.removeItem(KEY_SP_STATE);
  } catch(e) {
    console.error('No se pudo limpiar KEY_SP_STATE', e);
  }

  // ✅ Vuelve a pintar todo
  renderFromDays(daysCurrent);
  updateWeeksUI();

  if (typeof guardarEstadoSemanas === 'function') {
    guardarEstadoSemanas();
  }

  if (typeof guardarResumenParaOtroHTML === 'function') {
    guardarResumenParaOtroHTML();
  }

  saveSPState();
}

function deleteRange(startYMD, endYMD){
  const a = new Date(startYMD + "T00:00:00Z").getTime();
  const b = new Date(endYMD   + "T00:00:00Z").getTime();
  const lo = Math.min(a,b);
  const hi = Math.max(a,b);

  let removed = 0;
  let removedAdded = 0;
  let removedBase = 0;

  HISTORY.push({
    before: deepCloneDays(daysCurrent),
    addedTotal: Number(ADDED_DAYS_TOTAL || 0),
    deletedTotal: Number(DELETED_DAYS_TOTAL || 0)
  });

  daysCurrent = daysCurrent.filter(d => {
    const t = d.dt.getTime();

if (t >= lo && t <= hi) {
  removed++;

  if (d.origin === 'added') {
    removedAdded++;
  } else {
    removedBase++;
    DELETED_YMDS.add(d.ymd); // ✅ evita que esa fecha vuelva a entrar
  }

  return false;
}

    return true;
  });

  if (removed === 0) {
    if (HISTORY.length) HISTORY.pop();
    return 0;
  }

  // Si borras días agregados, bajan semanas Mod 40
  ADDED_DAYS_TOTAL = Math.max(0, Number(ADDED_DAYS_TOTAL || 0) - removedAdded);

  // Si borras días originales/base, bajan semanas del total
  DELETED_DAYS_TOTAL = Number(DELETED_DAYS_TOTAL || 0) + removedBase;

  refillTo1750Safe();

  updateWeeksUI();
  guardarEstadoSemanas();
  saveSPState();

  return removed;
}


/* =========================
   Aplicar ajuste (GUARDA snapshot)
========================= */
function applyAdjustment(){
  const y = Number(document.getElementById('addYears').value || 0);
  const m = Number(document.getElementById('addMonths').value || 0);
  const d = Number(document.getElementById('addDays').value || 0);
  const salStr = String(document.getElementById('addSalary').value || '').trim();
  const info = document.getElementById('infoAjuste');

  const toks = parseSalaryTokens(salStr);
  if (!toks.length){
    info.textContent = '⚠️ Escribe un salario válido (permite +).';
    return;
  }

  // ✅ si no hay nada que agregar
  const monthsTotal = (y * 12) + m;
  if (monthsTotal <= 0 && d <= 0){
    info.textContent = '⚠️ Agrega al menos 1 mes/año o 1 día.';
    return;
  }

  // ✅ snapshot ANTES del ajuste (si ya lo estás usando con HISTORY)
  if (typeof HISTORY !== 'undefined' && Array.isArray(HISTORY)) {
    HISTORY.push({ before: deepCloneDays(daysCurrent), addedTotal: ADDED_DAYS_TOTAL });
  }

  const suma = toks.reduce((a,b)=>a+b,0);
  const base = Math.max(...toks);
  const emp  = Math.max(0, suma - base);

  const startNew = computeStartNewUTC();

  // =========================
  // ✅ NUEVA REGLA:
  // - Si hay meses/años: el fin SIEMPRE es el último día del mes final
  //   (mes final = mes de inicio + (monthsTotal - 1))
  // - Si solo hay días: fin = startNew + (d - 1)
  // - Si además hay días y meses/años: días extra DESPUÉS del fin de mes
  // =========================
  let newEnd;

  if (monthsTotal > 0){
    // mes base (1er día del mes de inicio)
    const month0 = new Date(Date.UTC(startNew.getUTCFullYear(), startNew.getUTCMonth(), 1));

    // mes final = month0 + (monthsTotal - 1)
    const endMonth = addMonthsUTC(month0, monthsTotal - 1);

    // último día del mes final
    newEnd = new Date(Date.UTC(endMonth.getUTCFullYear(), endMonth.getUTCMonth() + 1, 0));

    // días extra después del fin de mes
    if (d > 0) newEnd = addDays(newEnd, d);
  } else {
    // solo días: d=1 => 1 día (startNew)
    newEnd = addDays(startNew, d - 1);
  }

  const newDaysCount = diffDays(startNew, newEnd) + 1;
  if (newDaysCount <= 0){
    info.textContent = '⚠️ El ajuste no generó días nuevos.';
    // si usas HISTORY y no sirvió, puedes hacer HISTORY.pop()
    if (typeof HISTORY !== 'undefined' && Array.isArray(HISTORY) && HISTORY.length) HISTORY.pop();
    return;
  }

  const next = daysCurrent.slice();

  for(let dt = startNew; dt.getTime() <= newEnd.getTime(); dt = addDays(dt, 1)){
next.push({
  ymd: dt.toISOString().slice(0,10),
  dt: new Date(dt.getTime()),
  base, emp, suma,
  sal: salStr,
  origin: 'added',
  hastaLabel: ''
});
  }

  next.sort((a,b)=>a.dt.getTime()-b.dt.getTime());
  if (next.length > 1750) daysCurrent = next.slice(next.length - 1750);
  else {
    daysCurrent = next;
    if (typeof refillTo1750Safe === 'function') refillTo1750Safe();
    else if (typeof refillTo1750 === 'function') refillTo1750();
  }

  info.textContent = `✅ Ajuste aplicado: +${newDaysCount} día(s). Fin calculado: ${fmtDMY(newEnd)} (cierre de mes).`;
    ADDED_DAYS_TOTAL = Number(ADDED_DAYS_TOTAL || 0) + newDaysCount;
  renderFromDays(daysCurrent);
  saveSPState();
  guardarEstadoSemanas();
}


document.getElementById('btnApplyAdjust')?.addEventListener('click', applyAdjustment);

/* =========================
   Clicks en tabla (detalle / editar / eliminar)
========================= */
document.getElementById('tbodyTabla3')?.addEventListener('click', (ev)=>{
  const btnDel = ev.target.closest('.btn-del-row');
  const btnEdit = ev.target.closest('.btn-edit-row');
  const summary = ev.target.closest('summary');

  if (summary){
    const tr = summary.closest('tr');
    if(tr){
      const rows = groupDays(daysCurrent);
      const idx = Array.from(tr.parentElement.children).indexOf(tr);
      const row = rows[idx];
      if(row) fillDetailsRows(row);
    }
  }

if (btnDel){
  const s = btnDel.dataset.start;
  const e = btnDel.dataset.end;
  if (!s || !e) return;

  if (!confirm('¿Eliminar este periodo y reponer días anteriores del PDF para mantener 1750?')) return;

  const beforeLen = daysCurrent.length;
  const removed = deleteRange(s, e);

  renderFromDays(daysCurrent);

  const info = document.getElementById('infoAjuste');

  if (info) {
    if (removed > 0) {
      info.textContent =
        `🗑️ Se eliminaron ${removed} día(s). La tabla se rellenó hacia atrás con fechas disponibles del PDF. Total actual: ${daysCurrent.length} día(s).`;
    } else {
      info.textContent =
        '⚠️ No se eliminó ningún día en ese periodo.';
    }
  }
}

  if (btnEdit){
    const s = btnEdit.dataset.start;
    const e = btnEdit.dataset.end;
    if (!s || !e) return;

    const a = new Date(s + "T00:00:00Z").getTime();
    const b = new Date(e + "T00:00:00Z").getTime();
    const lo = Math.min(a,b), hi = Math.max(a,b);

    const first = daysCurrent.find(d => d.origin==='added' && d.dt.getTime()>=lo && d.dt.getTime()<=hi);
    const curSal = first ? first.sal : '';

    const nuevo = prompt('Nuevo salario para estos días (permite +):', curSal);
    if (nuevo == null) return;

    const ok = updateSalaryRange(s,e, String(nuevo).trim());
    if (!ok){ alert('Salario inválido.'); return; }

    renderFromDays(daysCurrent);
    document.getElementById('infoAjuste').textContent = '✏️ Se editó el salario de días agregados.';
  }
});



function bindAutoSaveSP(){
const ids = ['startMode','pickYear','pickMonth','exactStartDate','salaryYear','addYears','addMonths','addDays','addSalary','inpSemReintegradas'];

  ids.forEach(id=>{
    const el = document.getElementById(id);
    if(!el) return;
    el.addEventListener('input', saveSPState);
    el.addEventListener('change', saveSPState);
  });

  // Guarda también antes de salir/recargar
  window.addEventListener('beforeunload', saveSPState);
}



function resetStateForNewPDFIfNeeded(){
  try{
    const fp = (typeof PDF_FINGERPRINT !== 'undefined' && PDF_FINGERPRINT)
      ? String(PDF_FINGERPRINT)
      : '';

    if (!fp) return;

    const last = sessionStorage.getItem(KEY_LAST_PDF);

    if (last && last !== fp){
      IS_NEW_PDF = true;

sessionStorage.removeItem('CIP_TABLA3_STATE');
sessionStorage.removeItem('CIP_SP_STATE_V1');
sessionStorage.removeItem('CIP_DATOS_TABLA3');
sessionStorage.removeItem('CIP_DATOS_SP'); // ✅ importante
try{ localStorage.removeItem('cip_resumen_tabla3'); }catch(e){}

      ADDED_DAYS_TOTAL = 0;
      daysCurrent = DAYS_BASE.map(x => ({...x, dt: new Date(x.dt.getTime()), origin:'base'}));

      console.log('✅ Nuevo PDF detectado: estado reiniciado');
    }

    sessionStorage.setItem(KEY_LAST_PDF, fp);
  }catch(e){
    console.warn('resetStateForNewPDFIfNeeded error', e);
  }
}

const KEY_LAST_PDF = 'CIP_LAST_PDF_FP_V1';
let IS_NEW_PDF = false;


/* =========================
   INIT
========================= */
(function initUI(){
  resetStateForNewPDFIfNeeded();

document.getElementById('startMode')?.addEventListener('change', ()=>{
  showOrHidePick();
  updateStartAndSalaryUI();
});

document.getElementById('pickYear')?.addEventListener('change', updateStartAndSalaryUI);
document.getElementById('pickMonth')?.addEventListener('change', updateStartAndSalaryUI);
document.getElementById('exactStartDate')?.addEventListener('change', updateStartAndSalaryUI);
document.getElementById('exactStartDate')?.addEventListener('input', updateStartAndSalaryUI);

  document.getElementById('salaryYear')?.addEventListener('change', async (ev)=>{
    const y = Number(ev.target.value || 0);
    if (!y) return;
    salaryManual = false;
    await setSalaryAutoForYear(y);
  });

  // 1) Restaurar tabla (daysCurrent + ADDED_DAYS_TOTAL)
  if (typeof restoreTabla3State === 'function') {
    restoreTabla3State();
  }

  // 2) Restaurar inputs (UI)
  restoreSPState();

  // 3) Si es nuevo PDF, ya quedó reseteado en resetStateForNewPDFIfNeeded()
  //    (NO volver a poner ADDED_DAYS_TOTAL = 0 aquí)

  // 4) Render final único
  renderFromDays(daysCurrent);
  updateLastOptionLabel();
  showOrHidePick();
  updateStartAndSalaryUI();

  bindAutoSaveSP();
})();





function absUrl(path){
  try { return new URL(path, window.location.href).href; }
  catch(e){ return path; }
}

function removeColsByHeader(table, headersToRemove){
  if(!table) return;

  const norm = s => String(s || '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, ' ');

  const targets = new Set(headersToRemove.map(norm));

  const theadRow = table.querySelector('thead tr');
  if(!theadRow) return;

  const ths = Array.from(theadRow.querySelectorAll('th'));
  const idxs = [];

  ths.forEach((th, i)=>{
    const text = norm(th.textContent);
    if (targets.has(text)) idxs.push(i);
  });

  idxs.sort((a,b)=>b-a);

  for(const i of idxs){
    if (ths[i]) ths[i].remove();
  }

  table.querySelectorAll('tbody tr').forEach(tr=>{
    const cells = Array.from(tr.children);
    for(const i of idxs){
      if (cells[i]) cells[i].remove();
    }
  });
}

/* ✅ agrega clases al footer para poder darle diseño exacto */
function addFooterClasses(html, klass){
  if(!html) return '';
  // si ya trae class, lo deja y solo añade el tuyo
  if (/<table[^>]*class=/i.test(html)) {
    return html
      .replace(/<table([^>]*?)class="([^"]*)"/i, `<table$1class="$2 ${klass}"`);
  }
  return html.replace(/<table/i, `<table class="${klass}"`);
}

/* ✅ marca fila gris por texto (TOTAL / Semanas totales) sin tocar tu HTML original */
function markRowByContains(html, needle){
  if(!html) return '';
  const n = String(needle||'').toLowerCase();
  return html.replace(/<tr\b([^>]*)>([\s\S]*?)<\/tr>/gi, (m, attrs, inner)=>{
    const txt = inner.replace(/<[^>]+>/g,' ').replace(/\s+/g,' ').trim().toLowerCase();
    if (txt.includes(n)) {
      if (/class="/i.test(attrs)) {
        // añade table-secondary a la clase existente
        if (/table-secondary/i.test(attrs)) return m;
        return `<tr${attrs.replace(/class="([^"]*)"/i, (mm, c)=>`class="${c} table-secondary"`)}>${inner}</tr>`;
      }
      return `<tr class="table-secondary"${attrs ? ' '+attrs.trim() : ''}>${inner}</tr>`;
    }
    return m;
  });
}

function getDiarioActual(){
  return (document.getElementById('metricDiario')?.textContent || '').trim() || '0.00';
}

function fixSemanasReintegradasForPrint(semanasClone){
  if (!semanasClone) return;

  // valor REAL escrito por el usuario (DOM real)
  const realVal = (document.getElementById('inpSemReintegradas')?.value || '0').trim();

  // en el CLONE: reemplaza el input por texto
  const inpClone = semanasClone.querySelector('#inpSemReintegradas');
  if (inpClone){
    const td = inpClone.closest('td');
    if (td){
      td.textContent = realVal === '' ? '0' : realVal;
      td.classList.add('text-end');
    } else {
      inpClone.outerHTML = `<span class="mono">${realVal === '' ? '0' : realVal}</span>`;
    }
  }
}


function getYearFromPeriodoText(periodo){
  const txt = String(periodo || '').trim();
  const matches = txt.match(/\d{2}\/\d{2}\/\d{4}/g);

  if (matches && matches.length) {
    const last = matches[matches.length - 1];
    const parts = last.split('/');
    return Number(parts[2]) || null;
  }

  return null;
}

function buildPrintRowsGroupedByYear(tabla){
  if (!tabla) return;

  const tbody = tabla.querySelector('tbody');
  if (!tbody) return;

  const rows = Array.from(tbody.querySelectorAll('tr'));
  if (!rows.length) return;

  let html = '';
  let currentYear = null;
  let rowNumber = 1;

  rows.forEach((tr) => {
    const cells = tr.querySelectorAll('td');
    if (!cells.length) return;

    const periodoText = cells[1] ? cells[1].textContent.trim() : '';
    const salarioText = cells[2] ? cells[2].textContent.trim() : '';
    const sumaText    = cells[3] ? cells[3].textContent.trim() : '';
    const accionesTxt = cells[6] ? cells[6].textContent.trim() : '—';

    const sumaNumerica = Number(
      sumaText
        .replace(/,/g, '')
        .replace(/\$/g, '')
        .trim()
    ) || 0;

    const partes = splitPeriodoByYear(periodoText, sumaNumerica);

    if (!partes.length) {
      html += tr.outerHTML;
      return;
    }

    partes.forEach((p) => {
      if (p.year !== currentYear) {
        currentYear = p.year;
        html += `
          <tr class="year-row">
            <td colspan="7" class="fw-bold text-start">AÑO ${p.year}</td>
          </tr>
        `;
      }

      html += `
        <tr>
          <td class="text-nowrap">${rowNumber++}</td>
          <td class="text-nowrap">${p.desde} - ${p.hasta}</td>
          <td class="text-nowrap">${salarioText}</td>
          <td class="text-end text-nowrap">${fmtMoney(sumaNumerica)}</td>
          <td class="text-end text-nowrap">${p.dias}</td>
          <td class="text-end fw-semibold text-nowrap">${fmtMoney(p.subtotal)}</td>
          <td class="text-center">${accionesTxt || '—'}</td>
        </tr>
      `;
    });
  });

  tbody.innerHTML = html;
}


window.guardarResumenParaOtroHTML = function guardarResumenParaOtroHTML(){
  const SESSION_KEY = 'CIP_DATOS_TABLA3';
  const SESSION_KEY_SP = 'CIP_DATOS_SP';

  const curp   = <?= json_encode((string)($persona['curp'] ?? '')) ?>;
  const nss    = <?= json_encode((string)($persona['nss'] ?? '')) ?>;
  const nombre = <?= json_encode((string)($persona['nombre'] ?? '')) ?>;

  const cleanNumberText = (v) => {
    return String(v ?? '')
      .replace(/\$/g, '')
      .replace(/,/g, '')
      .replace(/;/g, '')
      .trim();
  };

  const toNum = (v) => {
    const n = Number(cleanNumberText(v));
    return Number.isFinite(n) ? n : 0;
  };

  const isValid = (v) => {
    const txt = cleanNumberText(v);
    return txt !== '' && txt !== '0' && txt !== '0.00';
  };

  const firstValid = (...vals) => {
    for (const v of vals) {
      if (isValid(v)) return cleanNumberText(v);
    }
    return '';
  };

  const getText = (id) => {
    return (document.getElementById(id)?.textContent || '').trim();
  };

  const getValue = (id) => {
    return (document.getElementById(id)?.value || '').trim();
  };

  function getSemanasByLabel(label) {
    const rows = document.querySelectorAll('#printSemanas tr, table tr');

    const target = String(label || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim();

    for (const tr of rows) {
      const txt = String(tr.textContent || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();

      if (!txt.includes(target)) continue;

      const cells = Array.from(tr.children);
      if (cells.length >= 2) {
        return cleanNumberText(cells[cells.length - 1].textContent);
      }
    }

    return '';
  }

  /*
    ✅ Leer payload anterior.
    Primero intenta CIP_DATOS_SP porque debe tener el payload completo.
  */
  let payloadAnterior = {};
  try {
    payloadAnterior = JSON.parse(
      sessionStorage.getItem(SESSION_KEY_SP) ||
      sessionStorage.getItem(SESSION_KEY) ||
      '{}'
    );
  } catch (e) {
    payloadAnterior = {};
  }

  /*
    ✅ Semanas base desde PHP / DOM / payload anterior.
  */
  const semRegistradasPHP =
    <?= json_encode($semRegistradas !== '' ? $semRegistradas : ($semTotales !== '' ? $semTotales : '0')) ?>;

  const semDescontadasPHP =
    <?= json_encode($semDescontadas !== '' ? $semDescontadas : '0') ?>;

  const semReintegradasPHP =
    <?= json_encode($semReintegradas !== '' ? $semReintegradas : '0') ?>;

  const semRegistradas = firstValid(
    semRegistradasPHP,
    getSemanasByLabel('Semanas registradas'),
    payloadAnterior.semanasOriginalesTabla3,
    payloadAnterior.semanasRegistradasOriginales
  ) || '0';

  const semDescontadas = firstValid(
    semDescontadasPHP,
    getSemanasByLabel('Semanas descontadas'),
    payloadAnterior.semanasDescontadasTabla3,
    payloadAnterior.semanasDescontadasDesempleo,
    payloadAnterior.semanasDescontadas
  ) || '0';

  const inp = document.getElementById('inpSemReintegradas');

  const semReintegradasEdit = firstValid(
    inp?.value,
    getValue('inpSemReintegradas'),
    getSemanasByLabel('Semanas reintegradas'),
    semReintegradasPHP,
    payloadAnterior.semanasReintegradas,
    payloadAnterior.semanas_reintegradas
  ) || '0';

  /*
    ✅ Original real:
    registradas - descontadas + reintegradas.
  */
  const semanasTotalesTabla3Num =
    toNum(semRegistradas) -
    toNum(semDescontadas) +
    toNum(semReintegradasEdit);

  const semanasTotalesTabla3 = String(
    semanasTotalesTabla3Num < 0 ? 0 : semanasTotalesTabla3Num
  );

  /*
    ✅ Final visible actual.
    Este sí puede incluir Mod 40.
  */
  const semanasActuales = firstValid(
    getText('cellSemTotales'),
    getText('metricSemanas'),
    getSemanasByLabel('Semanas totales')
  ) || semanasTotalesTabla3;

  const incrementoSemanas = firstValid(
    getText('cellSemMod40'),
    getSemanasByLabel('Semanas Mod 40')
  ) || String(Math.max(0, toNum(semanasActuales) - toNum(semanasTotalesTabla3)));

  /*
    ✅ Salario final visible.
  */
  const diarioActual = firstValid(
    getText('metricDiario'),
    getText('totDiario')
  ) || firstValid(
    payloadAnterior.salarioInversion,
    payloadAnterior.diario1750,
    payloadAnterior.diario
  ) || '0.00';

  /*
    ✅ Salario original.
    Si ya existía uno válido, lo conserva.
    Si no, usa el actual.
  */
  const diarioOriginalTabla3 = firstValid(
    payloadAnterior.diarioOriginalTabla3,
    payloadAnterior.diarioOriginal1750,
    payloadAnterior.salarioOriginal,
    diarioActual
  ) || '0.00';

  const diasAgregadosMod40 =
    String(typeof ADDED_DAYS_TOTAL !== 'undefined' ? ADDED_DAYS_TOTAL : 0);

  const payload = {
    ...payloadAnterior,

    curp,
    nss,
    nombre,

    /*
      ✅ Compatibilidad visible.
      Estos son finales.
    */
    semanas: semanasActuales,
    diario: diarioActual,

    /*
      ✅ Originales Tabla 3.
    */
    semanasOriginalesTabla3: semRegistradas,
    semanasRegistradasOriginales: semRegistradas,

    semanasDescontadasTabla3: semDescontadas,
    semanasDescontadas: semDescontadas,
    semanasDescontadasDesempleo: semDescontadas,

    semanasReintegradas: semReintegradasEdit,
    semanas_reintegradas: semReintegradasEdit,

    semanasTotalesTabla3: semanasTotalesTabla3,

    diarioOriginalTabla3: diarioOriginalTabla3,
    diarioOriginal1750: diarioOriginalTabla3,
    salarioOriginal: diarioOriginalTabla3,

    /*
      ✅ Finales con Mod 40.
    */
    semanasTotalesFinales: semanasActuales,
    salarioInversion: diarioActual,
    diario1750: diarioActual,

    /*
      ✅ Mod 40.
    */
    incrementoSemanas,
    semanasMod40: incrementoSemanas,
    diasAgregadosMod40,

    updatedAt: Date.now()
  };

  sessionStorage.setItem(SESSION_KEY, JSON.stringify(payload));
  sessionStorage.setItem(SESSION_KEY_SP, JSON.stringify(payload));

  console.log('📌 Semanas procesar.php:');
  console.table({
    semanasOriginales: payload.semanasTotalesTabla3 || '0',
    semanasFinales: payload.semanasTotalesFinales || payload.semanas || '0'
  });

  return payload;
};

function imprimirTabla3() {
  const tabla3Original = document.getElementById('tabla3');
  if (!tabla3Original) { alert('No encontré la Tabla 3 (id="tabla3").'); return; }

  // Clonar sección 3 (para no tocar la pantalla)
  const tabla3Clone = tabla3Original.cloneNode(true);

  const tablaPrincipal = tabla3Clone.querySelector('#tablaPrincipal');
  if (!tablaPrincipal) { alert('No encontré #tablaPrincipal dentro de Tabla 3.'); return; }

  // Quitar columnas que no se imprimen
  removeColsByHeader(tablaPrincipal, ['días (detalle)', 'salario base', 'empalmes']);
  tablaPrincipal.id = 'tablaImpresion';
  buildPrintRowsGroupedByYear(tablaPrincipal);

  // === Tomar la TABLA de semanas (del DOM original) ===
  const semanasWrap = document.getElementById('printSemanas');
  const semanasTable = semanasWrap ? semanasWrap.querySelector('table') : null;
  let htmlSemanas = '';
if (semanasTable) {
  const clone = semanasTable.cloneNode(true);

  const realTot = (document.getElementById('cellSemTotales')?.textContent || '').trim();
  const cellTot = clone.querySelector('#cellSemTotales');
  if (cellTot && realTot) cellTot.textContent = realTot;

  const realMod40 = (document.getElementById('cellSemMod40')?.textContent || '').trim();
  const cellMod40 = clone.querySelector('#cellSemMod40');
  if (cellMod40 && realMod40) cellMod40.textContent = realMod40;

  // ✅ AQUÍ: convierte el input a texto con el valor real
  fixSemanasReintegradasForPrint(clone);

  htmlSemanas = clone.outerHTML;
}



  // === Tomar la TABLA de totales (del CLONE) ===
  const totWrap = tabla3Clone.querySelector('#tablaTotalesFinal');
  const totTable = totWrap ? totWrap.querySelector('table') : null;
  const htmlTot = totTable ? totTable.outerHTML : '';

  // Logos
const logoLeft = new URL('/img/logo.png', window.location.origin).href;



  const generado = new Date();
  const fechaStr = generado.toLocaleDateString('es-MX');
  const horaStr  = generado.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' });

  // Datos (PHP ya renderiza aquí)
  const curp    = <?= json_encode((string)($persona['curp'] ?? '')) ?>;
  const nss     = <?= json_encode((string)($persona['nss'] ?? '')) ?>;
  const nombre  = <?= json_encode((string)($persona['nombre'] ?? '')) ?>;
// ✅ Asegura que el UI esté actualizado antes de leer
if (typeof updateWeeksUI === 'function') updateWeeksUI();

// ✅ Tomar valores ACTUALES del DOM (no de PHP)
const semanas = (document.getElementById('metricSemanas')?.textContent || '').trim()
  || (document.getElementById('cellSemTotales')?.textContent || '').trim()
  || <?= json_encode((string)($semTotales ?? $persona['semanas'] ?? '')) ?>;

const diario = (document.getElementById('metricDiario')?.textContent || '').trim()
  || <?= json_encode(money((float)$diario)) ?>;









  // === Construir HTML final ===

const headerHTML = `
  <div class="rpt-wrap">
    <div class="rpt-head rpt-head-single">
      <div class="rpt-logo rpt-logo-single">
        <img src="${logoLeft}" alt="Logo" crossorigin="anonymous">
      </div>

      <div class="rpt-title rpt-title-single">
        <div class="rpt-h1">Resumen de Salarios - 1750 Días</div>
        <div class="rpt-h2">Generado: ${fechaStr} ${horaStr}</div>
      </div>
    </div>
      <div class="rpt-line"></div>

      <table class="table table-sm table-bordered" style="width:auto; min-width:520px; margin-bottom:10px;">
        <thead><tr><th>CURP</th><th>NSS</th><th>Nombre</th></tr></thead>
        <tbody><tr><td class="mono">${curp}</td><td class="mono">${nss}</td><td class="td-nombre">${nombre}</td></tr></tbody>
      </table>

<div class="rpt-meta rpt-metrics">
  <div class="metric">
    <span class="metric-label">Salario promedio diario:</span>
    <span class="metric-value"><strong>$ ${diario}</strong></span>
  </div>
  <div class="metric text-end">
    <span class="metric-label">Total semanas cotizadas:</span>
    <span class="metric-value"><strong>${semanas}</strong></span>
  </div>
</div>



      <div class="rpt-line" style="margin-top:10px;"></div>
    </div>
  `;

  const htmlTabla = tablaPrincipal.outerHTML;

  // ✅ IMPORTANTE: semanas y totales VAN DESPUÉS de la tabla principal
  // para que queden en la última página (no en la página 1)
  const footerHTML = `
    <div class="print-footer">
      <div class="print-footer-left">
        ${htmlSemanas || ''}
      </div>
      <div class="print-footer-right">
        ${htmlTot || ''}
      </div>
    </div>
  `;

const styles = `
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
@page { size: Letter portrait; margin: 6mm; }

*{ box-sizing:border-box; }

html, body{
  margin:0 !important;
  padding:0 !important;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
  font-family: Arial, sans-serif;
  color:#0f172a;
  background:#fff !important;
}

.rpt-wrap{
  width:100%;
  margin:0;
  padding:0;
}

.rpt-head-single{
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:flex-start;
  text-align:center;
  gap:0;
  padding:0;
  margin:0;
  line-height:1;
}

.rpt-logo-single{
  display:flex;
  justify-content:center;
  align-items:center;
  width:100%;
  height:58px;          /* controla cuánto espacio ocupa el bloque */
  overflow:hidden;      /* recorta el aire sobrante */
  margin:0;
  padding:0;
  line-height:1;
}

.rpt-logo-single img{
  width:250px;          
  max-width:none;
  height:auto;
  object-fit:contain;
  display:block;
  margin:0 auto;
  transform: translateY(10px); /* sube la imagen para quitar blanco */
}

.rpt-title-single{
  text-align:center;
  width:100%;
  margin:0;
  padding:0;
  line-height:1;
}

.rpt-h1{
  font-size:18px;
  font-weight:800;
  color:#0b2f55;
  margin:0;
  padding:0;
  line-height:1;
}

.rpt-h2{
  font-size:12px;
  color:#475569;
  margin:1px 0 0 0;
  padding:0;
  line-height:1;
}

.rpt-line{
  height:1px;
  background:#cbd5e1;
  margin:4px 0 8px 0;   /* antes dejaba más aire */
}

    .rpt-meta{
      display:flex;
      justify-content:space-between;
      gap:16px;
      font-size:14pt !important;
      align-items:flex-start;
    }

    .rpt-meta b,
    .rpt-meta strong{
      color:#0b2f55;
      font-size:14pt !important;
    }

    .rpt-meta .fs-5{
      font-size:18pt !important;
      font-weight:800 !important;
      line-height:1.1 !important;
    }

    table{
      width:100% !important;
      border-collapse:collapse !important;
    }

    .table{
      width:100% !important;
      font-size:9pt !important;
    }

    .table th,
    .table td{
      border:1px solid #cbd5e1 !important;
      padding:3px 5px !important;
      vertical-align:middle !important;
      white-space: nowrap !important;
    }

    thead{
      display: table-header-group !important;
    }

    .table thead th{
      background:#0b2f55 !important;
      color:#fff !important;
      font-weight:800 !important;
      font-size:9pt !important;
    }

    .table-striped tbody tr:nth-child(odd){
      background: rgba(2,6,23,0.04) !important;
    }

    .year-row td{
      background:#dbeafe !important;
      color:#0b2f55 !important;
      font-weight:800 !important;
      text-transform:uppercase;
      letter-spacing:.5px;
      padding:6px 8px !important;
    }

    #tablaImpresion thead th:nth-child(1){ width:8%; }
    #tablaImpresion thead th:nth-child(2){ width:46%; }
    #tablaImpresion thead th:nth-child(3){ width:46%; }

    .print-footer{
      display:flex;
      gap:12px;
      margin-top:14px;
      align-items:flex-start;
      justify-content:space-between;
      break-inside: avoid;
      page-break-inside: avoid;
    }

    .print-footer-left{
      flex: 1 1 60%;
      min-width: 0;
    }

    .print-footer-right{
      flex: 0 0 40%;
      min-width: 0;
    }

    @media print{
      .table-responsive,
      .overflow-auto,
      .overflow-scroll{
        overflow: visible !important;
        max-height:none !important;
        max-width:none !important;
      }
    }
  </style>
`;

  const w = window.open('', '_blank');
  if (!w) { alert('Tu navegador bloqueó la ventana. Permite pop-ups para localhost.'); return; }

  w.document.open();
  w.document.write(`
    <!doctype html>
    <html lang="es">
      <head>
        <meta charset="utf-8">
        <title>Imprimir Resumen</title>
        ${styles}
      </head>
      <body>
        ${headerHTML}
        ${htmlTabla}
        ${footerHTML}
        <script>
          window.onload = function(){
            setTimeout(function(){
              window.print();
              setTimeout(function(){ window.close(); }, 400);
            }, 250);
          }
        <\/script>
      </body>
    </html>
  `);
  w.document.close();
}

// Ctrl+P imprime con tu formato
document.addEventListener('keydown', function(e) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p') {
    e.preventDefault();
    imprimirTabla3();
  }
});




/* =========================
   Helpers para esperar assets
========================= */
function waitImagesInDoc(doc){
  const imgs = Array.from(doc.images || []);
  if (!imgs.length) return Promise.resolve();
  return Promise.all(imgs.map(img=>{
    if (img.complete) return Promise.resolve();
    return new Promise(res=>{ img.onload = img.onerror = res; });
  }));
}
function waitStylesInDoc(doc){
  const links = Array.from(doc.querySelectorAll('link[rel="stylesheet"]'));
  if (!links.length) return Promise.resolve();
  return Promise.all(links.map(l=>{
    return new Promise(res=>{
      l.onload = ()=>res();
      l.onerror = ()=>res();
    });
  }));
}
async function waitFontsInDoc(doc){
  try{ if (doc.fonts && doc.fonts.ready) await doc.fonts.ready; }catch(e){}
}
async function waitAllAssets(doc){
  await waitStylesInDoc(doc);
  await waitImagesInDoc(doc);
  await waitFontsInDoc(doc);
}

function createHiddenIFrame(){
  const iframe = document.createElement('iframe');
  iframe.style.position = 'fixed';
  iframe.style.left = '-9999px';
  iframe.style.top = '0';
  iframe.style.width = '1200px';
  iframe.style.height = '900px';
  iframe.style.background = '#fff';
  iframe.setAttribute('aria-hidden', 'true');
  document.body.appendChild(iframe);
  return iframe;
}

/* =========================
   ✅ Construye el HTML con TU diseño
========================= */
/* =========================
   ✅ FUNCIÓN UNIFICADA: Construir HTML para exportación
========================= */
function buildTabla3HTMLForExport() {
  const tabla3Original = document.getElementById('tabla3');
  if (!tabla3Original) throw new Error('No encontré Tabla 3 (id="tabla3").');

  const tabla3Clone = tabla3Original.cloneNode(true);
  const tablaPrincipal = tabla3Clone.querySelector('#tablaPrincipal');
  if (!tablaPrincipal) throw new Error('No encontré #tablaPrincipal dentro de Tabla 3.');

  // Quitar columnas no imprimibles
  removeColsByHeader(tablaPrincipal, ['días (detalle)', 'acciones', 'salario base', 'empalmes']);
  tablaPrincipal.id = 'tablaImpresion';

  // Aplicar clases a la tabla principal
  tablaPrincipal.className = 'table table-bordered table-striped';

  // Semanas y Totales (desde DOM real para que tome valores actuales)
  const semanasWrap = document.getElementById('printSemanas');
  const semanasTable = semanasWrap ? semanasWrap.querySelector('table') : null;
  let htmlSemanas = semanasTable ? semanasTable.outerHTML : '';
  
  const totWrapReal = document.getElementById('tablaTotalesFinal');
  const totTableReal = totWrapReal ? totWrapReal.querySelector('table') : null;
  let htmlTot = totTableReal ? totTableReal.outerHTML : '';

  // Datos para el encabezado
  const generado = new Date();
  const fechaStr = generado.toLocaleDateString('es-MX', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric' 
  });
  const horaStr = generado.toLocaleTimeString('es-MX', { 
    hour: '2-digit', 
    minute: '2-digit', 
    hour12: true 
  }).replace(' a. m.', ' a.m.').replace(' p. m.', ' p.m.');

  const curp = <?= json_encode((string)($persona['curp'] ?? '')) ?>;
  const nss = <?= json_encode((string)($persona['nss'] ?? '')) ?>;
  const nombre = <?= json_encode((string)($persona['nombre'] ?? '')) ?>;

  const semanas = document.getElementById('metricSemanas')?.textContent?.trim() ||
    <?= json_encode((string)($semTotales ?? $persona['semanas'] ?? '')) ?>;

  const diario = document.getElementById('metricDiario')?.textContent ||
    <?= json_encode(money((float)$diario)) ?>;

  // Rutas de logos
  const logoLeft = absUrl('img/imagen1.png');
  const logoRight = absUrl('img/Cip flecha.png');

  // Construir el HTML completo con el diseño de la imagen
  const fullHTML = `
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="utf-8">
      <title>Resumen de Salarios - 1750 Días</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>
        @page { 
          size: A4 landscape; 
          margin: 10mm; 
        }
        
        * { 
          box-sizing: border-box; 
          margin: 0;
          padding: 0;
        }
        
        body {
          font-family: Arial, sans-serif;
          font-size: 10pt;
          color: #333;
          background: #fff;
          margin: 0;
          padding: 15px;
          line-height: 1.4;
        }
        
        /* Encabezado principal */
        .header-container {
          text-align: center;
          margin-bottom: 20px;
          padding-bottom: 10px;
          border-bottom: 2px solid #0b2f55;
        }
        
        .main-title {
          font-size: 18px;
          font-weight: bold;
          color: #0b2f55;
          margin-bottom: 5px;
        }
        
        .generation-date {
          font-size: 12px;
          color: #666;
          margin-bottom: 15px;
        }
        
        /* Información personal - ESTILO SIMILAR A LA IMAGEN */
        .personal-info {
          margin-bottom: 15px;
        }
        
        .personal-info-grid {
          display: grid;
          grid-template-columns: auto 1fr;
          gap: 8px 15px;
          margin-bottom: 10px;
        }
        
        .info-label {
          font-weight: bold;
          color: #0b2f55;
          white-space: nowrap;
        }
        
        .info-value {
          font-family: 'Courier New', monospace;
          color: #333;
        }
        
        .nombre-completo {
          font-weight: bold;
          font-size: 11pt;
          margin-top: 5px;
        }
        
        /* Métricas - lado a lado */
        .metrics-container {
          display: flex;
          justify-content: space-between;
          margin: 15px 0;
          padding: 10px;
          background: #f8f9fa;
          border-radius: 4px;
          border: 1px solid #dee2e6;
        }
        
        .metric-item {
          font-size: 11pt;
        }
        
        .metric-label {
          color: #666;
        }
        
        .metric-value {
          font-weight: bold;
          color: #0b2f55;
          margin-left: 5px;
        }
        
        /* Tabla principal - ESTILO EXACTO COMO LA IMAGEN */
        .main-table-container {
          margin-top: 20px;
        }
        
        .table {
          width: 100%;
          border-collapse: collapse;
          font-size: 9pt;
          margin-bottom: 20px;
        }
        
        .table th {
          background: #0b2f55;
          color: white;
          font-weight: bold;
          text-align: center;
          padding: 8px 4px;
          border: 1px solid #0b2f55;
          white-space: nowrap;
        }
        
        .table td {
          padding: 6px 4px;
          border: 1px solid #ccc;
          text-align: center;
          white-space: nowrap;
        }
        
        .table tbody tr:nth-child(odd) {
          background-color: #f8f9fa;
        }
        
        .table tbody tr:nth-child(even) {
          background-color: white;
        }
        
        /* Columnas específicas como en la imagen */
        .table th:nth-child(1), .table td:nth-child(1) { width: 5%; }   /* # */
        .table th:nth-child(2), .table td:nth-child(2) { width: 20%; text-align: left; }  /* Periodo */
        .table th:nth-child(3), .table td:nth-child(3) { width: 15%; }  /* Salario(s) */
        .table th:nth-child(4), .table td:nth-child(4) { width: 12%; }  /* Suma total */
        .table th:nth-child(5), .table td:nth-child(5) { width: 8%; }   /* Días */
        .table th:nth-child(6), .table td:nth-child(6) { width: 18%; }  /* Subtotal */
        .table th:nth-child(7), .table td:nth-child(7) { width: 10%; }  /* Acciones */
        
        /* Alineación específica */
        .table td:nth-child(2) { text-align: left; }  /* Periodo alineado a la izquierda */
        .table td:nth-child(3),
        .table td:nth-child(4),
        .table td:nth-child(6) { text-align: right; }  /* Números alineados a la derecha */
        
        /* Formato de números */
        .number-cell {
          font-family: 'Courier New', monospace;
          text-align: right;
        }
        
        /* Footer con semanas */
        .footer-section {
          margin-top: 20px;
          padding-top: 10px;
          border-top: 1px solid #ccc;
        }
        
        .total-semanas {
          font-size: 11pt;
          font-weight: bold;
          color: #0b2f55;
          text-align: right;
        }
        
        /* Para impresión */
        @media print {
          body {
            padding: 0;
            margin: 0;
          }
          
          .no-print {
            display: none !important;
          }
          
          .table th {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
        }
      </style>
    </head>
    <body>
      <!-- Encabezado -->
      <div class="header-container">
        <h1 class="main-title">Resumen de Salarios - 1750 Días</h1>
        <div class="generation-date">Generado: ${fechaStr} ${horaStr}</div>
      </div>
      
      <!-- Información personal (ESTILO SIMILAR A LA IMAGEN) -->
      <div class="personal-info">
        <div class="personal-info-grid">
          <div class="info-label">CURP:</div>
          <div class="info-value">${curp}</div>
          
          <div class="info-label">NSS:</div>
          <div class="info-value">${nss}</div>
        </div>
        <div class="nombre-completo">${nombre}</div>
      </div>
      
      <!-- Métricas lado a lado -->
      <div class="metrics-container">
        <div class="metric-item">
          <span class="metric-label">Salario promedio diario:</span>
          <span class="metric-value">$ ${diario}</span>
        </div>
        <div class="metric-item">
          <span class="metric-label">Total semanas cotizadas:</span>
          <span class="metric-value">${semanas}</span>
        </div>
      </div>
      
      <!-- Tabla principal -->
      <div class="main-table-container">
        ${tablaPrincipal.outerHTML}
      </div>
      
      <!-- Footer con total de semanas -->
      <div class="footer-section">
        <div class="total-semanas">Total semanas cotizadas: ${semanas}</div>
      </div>
    </body>
    </html>
  `;

  return { fullHTML, nss, nombre };
}

/* =========================
   ✅ 1) IMPRIMIR (ventana nueva)
========================= */
async function printTabla3() {
  try {
    const { fullHTML } = buildTabla3HTMLForExport();
    
    const w = window.open('', '_blank');
    if (!w) {
      alert('Tu navegador bloqueó la ventana. Permite pop-ups para localhost.');
      return;
    }
    
    w.document.open();
    w.document.write(fullHTML);
    w.document.close();
    
    // Esperar a que se carguen los recursos
    w.onload = function() {
      setTimeout(function() {
        w.focus();
        w.print();
        setTimeout(function() {
          try { w.close(); } catch(e) {}
        }, 500);
      }, 300);
    };
    
  } catch (error) {
    console.error('Error al imprimir:', error);
    alert('Error al generar la impresión: ' + error.message);
  }
}

/* =========================
   ✅ 2) GUARDAR PDF (con html2pdf)
========================= */
async function saveTabla3PDF() {
  /* =========================
     0) asegurar html2pdf
  ========================= */
  async function ensureHtml2PdfLoaded() {
    if (typeof window.html2pdf === 'function') return;

    await new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
      s.onload = resolve;
      s.onerror = () => reject(new Error('No se pudo cargar html2pdf.bundle.min.js'));
      document.head.appendChild(s);
    });

    if (typeof window.html2pdf !== 'function') {
      throw new Error('html2pdf sigue sin estar disponible (CSP/bloqueo).');
    }
  }

  /* =========================
     helpers SOLO PDF
  ========================= */
  function removeColsByHeaderPDFOnly(table, headersToRemove = []) {
    if (!table) return;
    const thead = table.querySelector('thead');
    if (!thead) return;

    const wanted = headersToRemove.map(h => String(h).trim().toLowerCase());
    const ths = Array.from(thead.querySelectorAll('th'));

    const idxs = ths
      .map((th, i) => ({ i, t: th.textContent.trim().toLowerCase() }))
      .filter(o => wanted.some(w => o.t.includes(w)))
      .map(o => o.i)
      .sort((a, b) => b - a);

    idxs.forEach(colIndex => {
      table.querySelectorAll('tr').forEach(tr => {
        const cells = tr.querySelectorAll('th,td');
        if (cells[colIndex]) cells[colIndex].remove();
      });
    });

    // renumerar #
    const tbody = table.querySelector('tbody');
    if (tbody) {
      Array.from(tbody.querySelectorAll('tr')).forEach((tr, i) => {
        const td0 = tr.querySelector('td');
        if (td0) td0.textContent = String(i + 1);
      });
    }
  }

  function markRowByContainsEl(tableEl, needle) {
    if (!tableEl || !needle) return;
    const n = String(needle).toLowerCase();
    tableEl.querySelectorAll('tr').forEach(tr => {
      if (tr.textContent.toLowerCase().includes(n)) tr.classList.add('highlight-row');
    });
  }

  async function waitImages(root, timeoutMs = 7000) {
    const imgs = Array.from(root.querySelectorAll('img'));
    if (!imgs.length) return;

    await Promise.race([
      Promise.all(imgs.map(img => new Promise(res => {
        if (img.complete && img.naturalWidth > 0) return res();
        img.onload = () => res();
        img.onerror = () => res();
      }))),
      new Promise(res => setTimeout(res, timeoutMs))
    ]);

    await new Promise(r => setTimeout(r, 140));
  }

  /* =========================
     loader
  ========================= */
  const loadingEl = document.createElement('div');
  loadingEl.innerHTML = `
    <div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
      background:#0b2f55;color:#fff;padding:16px 18px;border-radius:10px;z-index:999999;
      font-family:Arial,sans-serif;font-size:14px;">
      Generando PDF...
    </div>`;
  document.body.appendChild(loadingEl);

  let tmpWrap = null;

  try {
    await ensureHtml2PdfLoaded();

    const tabla3Original = document.getElementById('tabla3');
    if (!tabla3Original) throw new Error('No encontré #tabla3');

    const tabla3Clone = tabla3Original.cloneNode(true);

    const tablaPrincipal = tabla3Clone.querySelector('#tablaPrincipal');
    if (!tablaPrincipal) throw new Error('No encontré #tablaPrincipal dentro de #tabla3');

    removeColsByHeaderPDFOnly(tablaPrincipal, ['días (detalle)', 'acciones', 'salario base', 'empalmes']);
    tablaPrincipal.id = 'tablaImpresion';
    tablaPrincipal.classList.add('table', 'table-sm', 'table-bordered', 'table-striped');

    const semanasWrap  = document.getElementById('printSemanas');
    const semanasTable = semanasWrap ? semanasWrap.querySelector('table') : null;
    const semanasClone = semanasTable ? semanasTable.cloneNode(true) : null;
    
    if (semanasClone) {
      const realMod40 = document.getElementById('cellSemMod40')?.textContent?.trim() || '0';
      const cloneMod40 = semanasClone.querySelector('#cellSemMod40');
      if (cloneMod40) cloneMod40.textContent = realMod40;

      // ✅ AQUÍ:
      fixSemanasReintegradasForPrint(semanasClone);

      markRowByContainsEl(semanasClone, 'Semanas Mod 40');
    }

    const totWrap  = tabla3Clone.querySelector('#tablaTotalesFinal');
    const totTable = totWrap ? totWrap.querySelector('table') : null;
    const totClone = totTable ? totTable.cloneNode(true) : null;
    if (totClone) {
      totClone.classList.add('table', 'table-sm', 'table-bordered');
      markRowByContainsEl(totClone, 'TOTAL');
    }

    const logoLeft  = new URL('imagenes/imagen1.png', document.baseURI).href;
    const logoRight = new URL('imagenes/Cip flecha.png', document.baseURI).href;

    const generado = new Date();
    const fechaStr = generado.toLocaleDateString('es-MX');
    const horaStr  = generado.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' });

    const curp   = <?= json_encode((string)($persona['curp'] ?? '')) ?>;
    const nss    = <?= json_encode((string)($persona['nss'] ?? '')) ?>;
    const nombre = <?= json_encode((string)($persona['nombre'] ?? '')) ?>;

    const semanas = document.getElementById('metricSemanas')?.textContent?.trim()
      || <?= json_encode((string)($semTotales ?? $persona['semanas'] ?? '')) ?>;

    const diario = document.getElementById('metricDiario')?.textContent?.trim()
      || <?= json_encode(money((float)$diario)) ?>;

    const headerHTML = `
      <div class="rpt-wrap">
        <div class="rpt-head">
          <div class="rpt-logo rpt-logo-left">
            <img src="${logoLeft}" alt="Logo izquierdo" crossorigin="anonymous">
          </div>
          <div class="rpt-title">
            <div class="rpt-h1">Resumen de Salarios - 1750 Días</div>
            <div class="rpt-h2">Generado: ${fechaStr} ${horaStr}</div>
          </div>
          <div class="rpt-logo rpt-logo-right">
            <img src="${logoRight}" alt="Logo derecho" crossorigin="anonymous">
          </div>
        </div>

        <div class="rpt-line"></div>

        <table class="table table-sm table-bordered rpt-mini" style="width:auto; min-width:520px; margin-bottom:10px;">
          <thead><tr><th>CURP</th><th>NSS</th><th>Nombre</th></tr></thead>
          <tbody><tr>
            <td class="mono">${curp}</td>
            <td class="mono">${nss}</td>
            <td class="td-nombre">${nombre}</td>
          </tr></tbody>
        </table>

        <div class="rpt-meta">
          <div class="metric">
            <span>Salario promedio diario:</span>
            <strong>$ ${diario}</strong>
          </div>
          <div class="metric metric-right">
            <span>Total semanas cotizadas:</span>
            <strong>${semanas}</strong>
          </div>
        </div>

        <div class="rpt-line" style="margin-top:10px;"></div>
      </div>
    `;

    const footerHTML = `
      <div class="print-footer">
        <div class="print-footer-left">${semanasClone ? semanasClone.outerHTML : ''}</div>
        <div class="print-footer-right">${totClone ? totClone.outerHTML : ''}</div>
      </div>
    `;

    /* =========================
       ✅ ancho seguro (Letter landscape)
       Letter landscape: 279.4mm de ancho.
       con márgenes 6mm izq/der -> área útil ~267.4mm
       (convertimos a px aprox con 96dpi y le restamos un “colchón”)
    ========================= */
    const mmToPx = (mm) => Math.floor(mm * 96 / 25.4);
    const MARGIN_MM = 6;                 // 👈 margen real
    const innerWmm = 279.4 - (MARGIN_MM * 2);
    const PAGE_W = mmToPx(innerWmm) - 20; // 👈 colchón anti-recorte

    const styles = `
      <style>
        *{ box-sizing:border-box; }
        html, body{
          margin:0 !important; padding:0 !important;
          font-family: Arial, sans-serif;
          color:#0f172a;
          background:#fff !important;
          -webkit-print-color-adjust: exact;
          print-color-adjust: exact;
        }

        /* ✅ contenedor “capturable” */
        .pdf-page{
          width:${PAGE_W}px;
          margin:0 auto;
          background:#fff;
          padding:10px 8px 16px 8px;
          overflow:visible !important;
        }

        .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
        .td-nombre{ white-space: normal !important; }

        .rpt-head{
          display:flex; align-items:center; justify-content:space-between; gap:12px;
          padding: 6px 0 10px 0;
        }
        .rpt-logo{ width:160px; display:flex; align-items:center; }
        .rpt-logo-left{ justify-content:flex-start; }
        .rpt-logo-right{ justify-content:flex-end; }
        .rpt-logo img{ max-height:46px; width:auto; object-fit:contain; }

        .rpt-title{ flex:1; text-align:center; line-height:1.1; }
        .rpt-h1{ font-size:18px; font-weight:800; color:#0b2f55; margin:0; }
        .rpt-h2{ font-size:12px; color:#475569; margin:6px 0 0; }
        .rpt-line{ height:1px; background:#cbd5e1; margin: 6px 0 12px 0; }

        .rpt-meta{
          display:flex; align-items:center; justify-content:space-between;
          gap:16px;
          font-size: 13pt;
        }
        .rpt-meta .metric{ white-space:nowrap; }
        .rpt-meta .metric-right{ margin-left:auto; text-align:right; }

        table{ width:100% !important; border-collapse:collapse !important; }
        thead{ display: table-header-group !important; }

        /* ✅ MUY importante para paginar tablas */
        #tablaImpresion { page-break-inside:auto; break-inside:auto; }
        #tablaImpresion tr { page-break-inside:avoid; break-inside:avoid; }
        #tablaImpresion thead { display: table-header-group !important; }

        /* ✅ Compacto */
        .table{
          width:100% !important;
          font-size:7.8pt !important;
          table-layout: fixed !important;
        }
        .table th, .table td{
          border:1px solid #cbd5e1 !important;
          padding:2px 3px !important;
          vertical-align:middle !important;
          white-space:nowrap !important;
          overflow:hidden !important;
          text-overflow:ellipsis !important;
        }
        .table thead th{
          background:#0b2f55 !important;
          color:#fff !important;
          font-weight:800 !important;
          font-size:7.8pt !important;
        }
        .table-striped tbody tr:nth-child(odd){
          background: rgba(2,6,23,0.04) !important;
        }

        /* ✅ Column widths (100% exacto) */
        #tablaImpresion thead th:nth-child(1){ width:4%; }   /* # */
        #tablaImpresion thead th:nth-child(2){ width:24%; }  /* Periodo */
        #tablaImpresion thead th:nth-child(3){ width:22%; }  /* Salario(s) */
        #tablaImpresion thead th:nth-child(4){ width:13%; }  /* Suma total */
        #tablaImpresion thead th:nth-child(5){ width:7%; }   /* Días */
        #tablaImpresion thead th:nth-child(6){ width:30%; }  /* Subtotal */

        .print-footer{
          display:flex; gap:12px; margin-top:14px;
          align-items:flex-start; justify-content:space-between;
          /* ✅ que el footer NO se parta; si no cabe, se va a la siguiente hoja */
          break-inside: avoid; page-break-inside: avoid;
        }
        .print-footer-left{ flex: 1 1 60%; min-width:0; }
        .print-footer-right{ flex: 0 0 40%; min-width:0; }

        .highlight-row td{
          background:#e3f2fd !important;
          font-weight:800 !important;
        }

        /* Opcional: fuerza salto manual si algún día lo necesitas */
        .page-break{ break-before: page; page-break-before: always; }
      </style>
    `;

    tmpWrap = document.createElement('div');
    tmpWrap.id = 'tmp_pdf_tabla3';
    tmpWrap.style.position = 'fixed';
    tmpWrap.style.left = '0';
    tmpWrap.style.top = '0';
    tmpWrap.style.opacity = '0.01';
    tmpWrap.style.pointerEvents = 'none';
    tmpWrap.style.background = '#fff';
    tmpWrap.style.zIndex = '0';

    tmpWrap.innerHTML = `${styles}<div class="pdf-page">${headerHTML}${tablaPrincipal.outerHTML}${footerHTML}</div>`;
    document.body.appendChild(tmpWrap);

    await waitImages(tmpWrap);

    const pageEl = tmpWrap.querySelector('.pdf-page');
    if (!pageEl) throw new Error('No se encontró .pdf-page');

    const filename = `Tabla3_${nss}_${new Date().toISOString().slice(0,10)}.pdf`;

    const opt = {
      margin: [MARGIN_MM, MARGIN_MM, MARGIN_MM, MARGIN_MM],
      filename,
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: {
        scale: 2,
        useCORS: true,
        backgroundColor: '#ffffff',
        scrollX: 0,
        scrollY: 0,
        x: 0,
        windowWidth: pageEl.scrollWidth,
        windowHeight: pageEl.scrollHeight
      },
      jsPDF: { unit: 'mm', format: 'letter', orientation: 'landscape' },

      /* ✅ CLAVE para que NO recorte y SÍ pagine:
         - css/legacy: permite que haga cortes por altura de hoja
         - avoid: evita cortar renglones y el footer
      */
      pagebreak: {
        mode: ['css', 'legacy'],
        avoid: ['tr', '.print-footer', 'img']
      }
    };

    await html2pdf().set(opt).from(pageEl).save();

  } catch (err) {
    console.error(err);
    alert('❌ ' + (err?.message || err));
  } finally {
    loadingEl.remove();
    if (tmpWrap) tmpWrap.remove();
  }
}


/* =========================
   ✅ Helper functions
========================= */
function removeColsByHeader(table, headersToRemove) {
  if (!table) return;
  
  const thead = table.querySelector('thead');
  const tbody = table.querySelector('tbody');
  if (!thead || !tbody) return;
  
  const headerCells = Array.from(thead.querySelectorAll('th'));
  const indicesToRemove = [];
  
  // Encontrar índices de columnas a eliminar
  headerCells.forEach((th, index) => {
    const text = th.textContent.toLowerCase().trim();
    if (headersToRemove.some(header => text.includes(header.toLowerCase()))) {
      indicesToRemove.push(index);
    }
  });
  
  // Eliminar columnas (en orden descendente para no afectar índices)
  indicesToRemove.sort((a, b) => b - a).forEach(index => {
    // Eliminar de thead
    const thRows = thead.querySelectorAll('tr');
    thRows.forEach(tr => {
      const cell = tr.querySelectorAll('th, td')[index];
      if (cell) cell.remove();
    });
    
    // Eliminar de tbody
    const trs = tbody.querySelectorAll('tr');
    trs.forEach(tr => {
      const cell = tr.querySelectorAll('td')[index];
      if (cell) cell.remove();
    });
  });
  
  // Renumerar las filas (#)
  const rows = tbody.querySelectorAll('tr');
  rows.forEach((row, index) => {
    const firstCell = row.querySelector('td:first-child');
    if (firstCell) {
      firstCell.textContent = index + 1;
    }
  });
}

function absUrl(path) {
  // Convertir ruta relativa a absoluta
  if (path.startsWith('http') || path.startsWith('//')) {
    return path;
  }
  
  // Si es ruta relativa, hacerla absoluta
  if (path.startsWith('/')) {
    return window.location.origin + path;
  } else {
    return window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/') + '/' + path;
  }
}





/* =========================
   ✅ Asignar eventos a botones
========================= */
document.addEventListener('DOMContentLoaded', function() {
  // Botón para imprimir
  document.getElementById('btnPrintTabla3')?.addEventListener('click', printTabla3);
  
  // Botón para guardar PDF
  document.getElementById('btnSavePdfTabla3')?.addEventListener('click', saveTabla3PDF);
  
  // También mantener el atajo Ctrl+P
  document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p') {
      e.preventDefault();
      printTabla3();
    }
  });
});

/* =========================
   ✅ Bind botones
========================= */
document.getElementById('btnPrintTabla3')?.addEventListener('click', printTabla3);
document.getElementById('btnSavePdfTabla3')?.addEventListener('click', saveTabla3PDF);


let fecha_actualxd = null;

document.querySelectorAll('#tablaPrincipal tbody tr').forEach(tr => {
  const texto = tr.children[1]?.textContent || '';
  // extrae la fecha FINAL del rango
  const match = texto.match(/-\s*(\d{2}\/\d{2}\/\d{4})/);
  if (!match) return;

  const [dd, mm, yyyy] = match[1].split('/');
  const fecha = new Date(Date.UTC(yyyy, mm - 1, dd));

  if (!fecha_actualxd || fecha > fecha_actualxd) {
    fecha_actualxd = fecha;
  }
});

// 👉 si solo quieres la fecha como texto DD/MM/YYYY
const fecha_final = fecha_actualxd
  ? fecha_actualxd.toLocaleDateString('es-MX')
  : '';

console.log('Fecha más reciente:', fecha_final);





document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('btnEnviarDatos');
  if (!btn) return;

  // ✅ Helper: obtiene la fecha más reciente (FIN del rango) desde la Tabla 3
function obtenerFechaActualXD() {
  let maxDt = null;

  document.querySelectorAll('#tablaPrincipal tbody tr').forEach(tr => {
    const periodoTxt = (tr.children[1]?.textContent || '').trim();
    if (!periodoTxt) return;

    // "01/09/2025 - 14/01/2026" -> toma "14/01/2026"
    const m = periodoTxt.match(/-\s*(\d{2}\/\d{2}\/\d{4})/);
    if (!m) return;

    const dt = parseDMYToUTC(m[1]);
    if (!dt) return;

    if (!maxDt || dt.getTime() > maxDt.getTime()) maxDt = dt;
  });

  // ✅ IMPORTANTE: regresar en DMY usando UTC (sin toLocaleDateString)
  return maxDt ? fmtDMY_UTC(maxDt) : '';
}


btn.addEventListener('click', () => {
  // === (1) Tomar CURP/NSS/NOMBRE desde la tabla correcta ===
  let curp = '';
  let nss = '';
  let nombre = '';

  const tablas = document.querySelectorAll('table');

  for (const table of tablas) {
    const ths = Array.from(table.querySelectorAll('thead th'))
      .map(th => th.textContent.trim().toUpperCase());

    if (ths.includes('CURP') && ths.includes('NSS') && ths.includes('NOMBRE')) {
      const fila = table.querySelector('tbody tr');

      if (fila) {
        const tds = fila.querySelectorAll('td');
        curp   = (tds[0]?.textContent || '').trim();
        nss    = (tds[1]?.textContent || '').trim();
        nombre = (tds[2]?.textContent || '').trim();
      }

      break;
    }
  }

  const fecha_actualxd = obtenerFechaActualXD();

  /*
    ✅ Primero guarda TODO el payload completo:
    semanas originales, finales, salario, Mod40, etc.
  */
const payloadBase = window.guardarResumenParaOtroHTML();

const payloadSP = {
  ...payloadBase,

  curp: curp || payloadBase.curp || '',
  nss: nss || payloadBase.nss || '',
  nombre: nombre || payloadBase.nombre || '',

  fecha_actualxd: fecha_actualxd || payloadBase.fecha_actualxd || '',

  semanasOriginalesTabla3: payloadBase.semanasOriginalesTabla3 || '0',
  semanasDescontadasTabla3: payloadBase.semanasDescontadasTabla3 || '0',
  semanasReintegradas: payloadBase.semanasReintegradas || '0',
  semanasTotalesTabla3: payloadBase.semanasTotalesTabla3 || '0',

  semanasTotalesFinales: payloadBase.semanasTotalesFinales || payloadBase.semanas || '0',
  salarioInversion: payloadBase.salarioInversion || payloadBase.diario1750 || payloadBase.diario || '0.00',
  diario1750: payloadBase.diario1750 || payloadBase.salarioInversion || payloadBase.diario || '0.00',

  semanas: payloadBase.semanasTotalesFinales || payloadBase.semanas || '0',
  diario: payloadBase.salarioInversion || payloadBase.diario1750 || payloadBase.diario || '0.00'
};

sessionStorage.setItem('CIP_DATOS_TABLA3', JSON.stringify(payloadSP));
sessionStorage.setItem('CIP_DATOS_SP', JSON.stringify(payloadSP));

console.log('🚀 Semanas enviadas al SP:');
console.table({
  semanasOriginales: payloadSP.semanasTotalesTabla3 || '0',
  semanasFinales: payloadSP.semanasTotalesFinales || payloadSP.semanas || '0'
});

window.location.href = '/sp.html';

  /*
    ✅ Guardar completo en ambas claves.
  */
  sessionStorage.setItem('CIP_DATOS_TABLA3', JSON.stringify(payloadSP));
  sessionStorage.setItem('CIP_DATOS_SP', JSON.stringify(payloadSP));

  console.log('🚀 Semanas enviadas al SP:');
  console.table({
    semanasOriginales: payloadSP.semanasTotalesTabla3 || '0',
    semanasFinales: payloadSP.semanasTotalesFinales || payloadSP.semanas || '0'
  });

  window.location.href = '/analisis/sp/sp.html';
});
});

function parseDMYToUTC(s){
  const m = String(s || '').trim().match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
  if (!m) return null;
  const dd = Number(m[1]);
  const mm = Number(m[2]);
  const yyyy = Number(m[3]);
  return new Date(Date.UTC(yyyy, mm - 1, dd));
}

function fmtDMY_UTC(dt){
  if (!dt) return '';
  const dd = String(dt.getUTCDate()).padStart(2, '0');
  const mm = String(dt.getUTCMonth() + 1).padStart(2, '0');
  const yyyy = dt.getUTCFullYear();
  return `${dd}/${mm}/${yyyy}`;
}

function addDaysUTC(dt, days){
  return new Date(dt.getTime() + (days * 86400000));
}

function diffDaysInclusiveUTC(start, end){
  const a = Date.UTC(start.getUTCFullYear(), start.getUTCMonth(), start.getUTCDate());
  const b = Date.UTC(end.getUTCFullYear(), end.getUTCMonth(), end.getUTCDate());
  return Math.floor((b - a) / 86400000) + 1;
}

function splitPeriodoByYear(periodoText, sumaTotal){
  const m = String(periodoText || '').trim().match(/^(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}\/\d{2}\/\d{4})$/);
  if (!m) return [];

  let start = parseDMYToUTC(m[1]);
  let end   = parseDMYToUTC(m[2]);
  if (!start || !end) return [];

  if (start > end) {
    const tmp = start;
    start = end;
    end = tmp;
  }

  const out = [];
  let current = new Date(start.getTime());

  while (current <= end) {
    const year = current.getUTCFullYear();
    const endOfYear = new Date(Date.UTC(year, 11, 31));
    const tramoEnd = end < endOfYear ? end : endOfYear;

    const dias = diffDaysInclusiveUTC(current, tramoEnd);
    const subtotal = dias * Number(sumaTotal || 0);

    out.push({
      year,
      subtotal
    });

    current = addDaysUTC(tramoEnd, 1);
  }

  return out;
}

function buildPrintRowsGroupedByYear(tabla){
  if (!tabla) return;

  const tbody = tabla.querySelector('tbody');
  if (!tbody) return;

  const rows = Array.from(tbody.querySelectorAll('tr'));
  if (!rows.length) return;

  const totalesPorAnio = {};

  rows.forEach((tr) => {
    const cells = tr.querySelectorAll('td');
    if (!cells.length) return;

    const periodoText = cells[1] ? cells[1].textContent.trim() : '';
    const sumaText    = cells[3] ? cells[3].textContent.trim() : '';

    const sumaNumerica = Number(
      sumaText
        .replace(/,/g, '')
        .replace(/\$/g, '')
        .trim()
    ) || 0;

    const partes = splitPeriodoByYear(periodoText, sumaNumerica);

    partes.forEach((p) => {
      if (!totalesPorAnio[p.year]) totalesPorAnio[p.year] = 0;
      totalesPorAnio[p.year] += p.subtotal;
    });
  });

  const anios = Object.keys(totalesPorAnio)
    .map(Number)
    .sort((a, b) => a - b);

  let html = '';
  let idx = 1;

  anios.forEach((anio) => {
    html += `
      <tr>
        <td class="text-nowrap">${idx++}</td>
        <td class="text-nowrap fw-bold">AÑO ${anio}</td>
        <td class="text-end fw-bold">${fmtMoney(totalesPorAnio[anio])}</td>
      </tr>
    `;
  });

  tbody.innerHTML = html;

  const theadRow = tabla.querySelector('thead tr');
  if (theadRow) {
    theadRow.innerHTML = `
      <th style="width:80px;">#</th>
      <th>Año</th>
      <th class="text-end">Total</th>
    `;
  }
}


const KEY_TABLA3_STATE = 'CIP_TABLA3_STATE';

function guardarEstadoSemanas(){
  try{
    const payload = {
      daysCurrent,
      ADDED_DAYS_TOTAL,
      timestamp: Date.now()
    };

    sessionStorage.setItem(KEY_TABLA3_STATE, JSON.stringify(payload));
  }catch(e){
    console.error('Error guardando estado', e);
  }
}
                


 
function restoreTabla3State(){
  if (typeof IS_NEW_PDF !== 'undefined' && IS_NEW_PDF) return;

  const raw = sessionStorage.getItem('CIP_TABLA3_STATE');
  if (!raw) return;

  try{
    const st = JSON.parse(raw);

    if (Array.isArray(st.daysCurrent)) {
      daysCurrent = st.daysCurrent.map(d => ({ ...d, dt: new Date(d.dt) }));
    }

    if (typeof st.ADDED_DAYS_TOTAL === 'number') {
      ADDED_DAYS_TOTAL = st.ADDED_DAYS_TOTAL;
    }

    // ✅ NO llames renderFromDays aquí
    // porque lo vamos a hacer al final del init
  }catch(e){
    console.error('No se pudo restaurar estado', e);
  }
}

document.getElementById('btnResetTabla3')?.addEventListener('click', resetTabla3ToOriginal);
</script>





</body>
</html>