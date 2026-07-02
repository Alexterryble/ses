<?php
declare(strict_types=1);

// ===============================
// CONEXIÓN LOCAL
// ===============================
require_once __DIR__ . '/../../db/conexion.php';

header('Content-Type: application/json; charset=utf-8');

// ===============================
// PARÁMETRO
// ===============================
$solicitudId = (int)($_GET['solicitud_id'] ?? $_GET['id'] ?? 0);

if ($solicitudId <= 0) {
  echo json_encode([
    'ok' => false,
    'error' => 'solicitud_id inválido'
  ]);
  exit;
}

// ===============================
// CONSULTA SQL LOCAL CORRECTA
// ===============================
$sql = "
  SELECT monto_total_pagar
  FROM railway.caratula_meta
  WHERE solicitud_id = :solicitud_id
  LIMIT 1
";


$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':solicitud_id' => $solicitudId
]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

// ===============================
// RESPUESTA
// ===============================
$monto = (float)($row['monto_total_pagar'] ?? 0);

echo json_encode([
  'ok' => true,
  'solicitud_id' => $solicitudId,
  'monto_total_pagar' => $monto
]);
