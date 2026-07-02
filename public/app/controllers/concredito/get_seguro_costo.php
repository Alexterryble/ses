<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');


  require_once __DIR__ . '/../../db/conexion.php';

function out(bool $ok, array $extra = [], int $code = 200): void {
  http_response_code($code);
  echo json_encode(array_merge(['ok' => $ok], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
  if (isset($conn) && $conn instanceof PDO) $pdo = $conn;
  else out(false, ['error' => 'Conexión PDO no disponible'], 500);
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/*
  Entrada:
  - GET monto=12345.67  (o POST)
  - opcional: fecha=YYYY-MM-DD para vigencia (default hoy)
*/
$monto = $_GET['monto'] ?? $_POST['monto'] ?? null;
$fecha = $_GET['fecha'] ?? $_POST['fecha'] ?? date('Y-m-d');

if ($monto === null || trim((string)$monto) === '') {
  out(false, ['error' => 'Falta parámetro monto'], 400);
}

// Normaliza monto: quita $, comas, espacios
$raw = trim((string)$monto);
$raw = str_replace(["\u{00A0}", ' ', '$', ','], '', $raw);
$raw = preg_replace('/[^\d.\-]/', '', $raw);
$nmonto = (float)$raw;

if (!is_finite($nmonto) || $nmonto <= 0) {
  out(true, [
    'monto' => $nmonto,
    'costo' => 0,
    'tarifa' => null,
    'message' => 'Monto no válido o cero'
  ]);
}

try {
  // 1) Primer tramo con suma >= monto y vigente_desde <= fecha (techo)
  $sql = "
    SELECT id, suma, costo, porcentaje, vigente_desde
    FROM seguro_tarifas
    WHERE suma >= :monto
      AND (vigente_desde IS NULL OR vigente_desde <= :fecha)
    ORDER BY suma ASC
    LIMIT 1
  ";
  $st = $pdo->prepare($sql);
  $st->execute([
    ':monto' => $nmonto,
    ':fecha' => $fecha,
  ]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    // 2) Si no hay (monto mayor a máximo), usa el último vigente
    $sql2 = "
      SELECT id, suma, costo, porcentaje, vigente_desde
      FROM seguro_tarifas
      WHERE (vigente_desde IS NULL OR vigente_desde <= :fecha)
      ORDER BY suma DESC
      LIMIT 1
    ";
    $st2 = $pdo->prepare($sql2);
    $st2->execute([':fecha' => $fecha]);
    $row = $st2->fetch(PDO::FETCH_ASSOC);
  }

  if (!$row) {
    out(false, ['error' => 'No hay tarifas disponibles en seguro_tarifas'], 404);
  }

  out(true, [
    'monto' => $nmonto,
    'costo' => (float)$row['costo'],
    'tarifa' => [
      'id' => (int)$row['id'],
      'suma' => (float)$row['suma'],
      'porcentaje' => isset($row['porcentaje']) ? (float)$row['porcentaje'] : null,
      'vigente_desde' => $row['vigente_desde'] ?? null,
    ]
  ]);

} catch (Throwable $e) {
  out(false, ['error' => 'Error al consultar seguro_tarifas', 'detail' => $e->getMessage()], 500);
}