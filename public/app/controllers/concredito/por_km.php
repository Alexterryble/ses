<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
  require_once __DIR__ . '/../../db/conexion.php';
  if (!isset($pdo)) throw new RuntimeException('Sin conexión');

  $km    = isset($_GET['km'])    ? (float)$_GET['km'] : 0.0;
  $monto = isset($_GET['monto']) ? (float)$_GET['monto'] : 0.0;
  $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

  $sql = "SELECT id, etiqueta, tipo, km_min, km_max, cuota_fija, pct_monto, minimo, maximo
          FROM investigacion_tarifas
          WHERE :km >= km_min
            AND :km <  km_max
            AND activo = 1
            AND vigente_desde <= :f
            AND (vigente_hasta IS NULL OR :f <= vigente_hasta)
          ORDER BY vigente_desde DESC
          LIMIT 1";

  $st = $pdo->prepare($sql);
  $st->execute([':km'=>$km, ':f'=>$fecha]);
  $t = $st->fetch(PDO::FETCH_ASSOC);
  if (!$t) throw new RuntimeException('No hay tarifa para ese km/vigencia');

  $cuota_fija = (float)$t['cuota_fija'];
  $pct        = (float)$t['pct_monto']; // % (ej. 1.5 = 1.5%)
  $extra      = $cuota_fija + ($monto * ($pct/100.0));

  if ($t['minimo'] !== null) $extra = max($extra, (float)$t['minimo']);
  if ($t['maximo'] !== null) $extra = min($extra, (float)$t['maximo']);

  $total = $monto + $extra;

  echo json_encode([
    'ok'   => true,
    'data' => [
      'tarifa'      => $t,
      'extra'       => round($extra, 2),
      'total'       => round($total, 2),
    ]
  ], JSON_UNESCAPED_UNICODE);
} catch(Throwable $e){
  http_response_code(400);
  echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
