<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
  require_once __DIR__ . '/../../db/conexion.php';
  if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('Sin conexión PDO');
  }
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

  $hoy = date('Y-m-d');

  // --- Consulta preferida (con placeholders distintos: :hoy1 y :hoy2) ---
  $sqlPreferida = "
    SELECT
      id,
      etiqueta,
      tipo,
      km_min,
      km_max,
      cuota_fija,
      pct_monto,
      minimo,
      maximo
    FROM investigacion_tarifas
    WHERE activo = 1
      AND vigente_desde <= :hoy1
      AND (vigente_hasta IS NULL OR :hoy2 <= vigente_hasta)
    ORDER BY km_min ASC, etiqueta ASC
  ";

  try {
    $st = $pdo->prepare($sqlPreferida);
    $st->execute([':hoy1' => $hoy, ':hoy2' => $hoy]);
    $rows = $st->fetchAll();
  } catch (PDOException $e) {
    // Fallback si la tabla en Railway no tiene esas columnas/condiciones
    $sqlFallback = "
      SELECT
        id,
        etiqueta,
        NULL AS tipo,
        NULL AS km_min,
        NULL AS km_max,
        COALESCE(cuota_fija, 0)  AS cuota_fija,
        COALESCE(pct_monto, 0)   AS pct_monto,
        COALESCE(minimo, 0)      AS minimo,
        COALESCE(maximo, 0)      AS maximo
      FROM investigacion_tarifas
      ORDER BY id ASC
    ";
    $rows = $pdo->query($sqlFallback)->fetchAll();
  }

  // Normaliza tipos numéricos para el front
  foreach ($rows as &$r) {
    foreach (['km_min','km_max','cuota_fija','pct_monto','minimo','maximo'] as $k) {
      if (array_key_exists($k, $r)) {
        // convierte a float o null
        $r[$k] = is_null($r[$k]) ? null : (float)$r[$k];
      }
    }
  }
  unset($r);

  echo json_encode(['ok' => true, 'items' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
