<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

try {
  require_once __DIR__ . '/../app/db/conexion.php';

  $db = (isset($pdo) && $pdo instanceof PDO) ? $pdo
      : ((isset($conn) && $conn instanceof PDO) ? $conn : null);

  if (!$db) throw new Exception('Sin conexión PDO ($pdo/$conn no encontrado)');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Año solicitado (sin modificarlo)
  $requestedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
  if ($requestedYear <= 0) $requestedYear = (int)date('Y');

  // Rango real disponible en la tabla
  $r = $db->query("SELECT MIN(year) AS min_year, MAX(year) AS max_year FROM uma_topado")->fetch(PDO::FETCH_ASSOC);
  $minYear = isset($r['min_year']) ? (int)$r['min_year'] : 0;
  $maxYear = isset($r['max_year']) ? (int)$r['max_year'] : 0;

  if ($minYear <= 0 || $maxYear <= 0) {
    throw new Exception('La tabla uma_topado no tiene registros.');
  }

  // 1) Buscar el año exacto
  $stmt = $db->prepare("
    SELECT year, uma, topado, pension_garantizada
    FROM uma_topado
    WHERE year = :y
    LIMIT 1
  ");
  $stmt->execute([':y' => $requestedYear]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  // 2) Si no existe, traer el más cercano hacia atrás (<= solicitado)
  if (!$row) {
    $stmt2 = $db->prepare("
      SELECT year, uma, topado, pension_garantizada
      FROM uma_topado
      WHERE year <= :y
      ORDER BY year DESC
      LIMIT 1
    ");
    $stmt2->execute([':y' => $requestedYear]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);
  }

  // 3) Si aún no hay (ej. pidieron un año menor que el mínimo), traer el mínimo disponible
  if (!$row) {
    $stmt3 = $db->prepare("
      SELECT year, uma, topado, pension_garantizada
      FROM uma_topado
      ORDER BY year ASC
      LIMIT 1
    ");
    $stmt3->execute();
    $row = $stmt3->fetch(PDO::FETCH_ASSOC);
  }

  if (!$row) throw new Exception("No hay datos en uma_topado.");

  $resolvedYear = (int)$row['year'];

  echo json_encode([
    'success' => true,
    'data' => [
      'year'   => $resolvedYear,
      'uma'    => (float)$row['uma'],
      'topado' => (float)$row['topado'],
      'pension_garantizada' => isset($row['pension_garantizada']) ? (float)$row['pension_garantizada'] : 0.0,
    ],
    'meta' => [
      'min_year' => $minYear,
      'max_year' => $maxYear,
      'requested_year' => $requestedYear,   // ✅ lo que pidió el usuario (ej. 2026)
      'resolved_year'  => $resolvedYear     // ✅ lo que realmente se entregó (2026 si existe, si no 2025)
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
