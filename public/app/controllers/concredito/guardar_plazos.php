<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../db/conexion.php'; // ajusta si tu ruta cambia

function out(bool $ok, array $extra = [], int $code = 200): void {
  http_response_code($code);
  echo json_encode(array_merge(['ok' => $ok], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out(false, ['message' => 'Método no permitido'], 405);
  }

  $raw = file_get_contents('php://input') ?: '';
  $data = json_decode($raw, true);

  if (!is_array($data)) {
    out(false, ['message' => 'JSON inválido'], 400);
  }

  $solicitud_id = isset($data['solicitud_id']) ? (int)$data['solicitud_id'] : 0;
  $plazos = $data['plazos'] ?? null;

  if ($solicitud_id <= 0) out(false, ['message' => 'Falta solicitud_id'], 400);
  if (!is_array($plazos)) out(false, ['message' => 'Falta plazos[]'], 400);

  // Validación básica de items
  $items = [];
  foreach ($plazos as $it) {
    if (!is_array($it)) continue;
    $mes = isset($it['mes']) ? (int)$it['mes'] : 0;
    $monto = isset($it['monto']) ? (float)$it['monto'] : 0.0;

    if ($mes <= 0) continue;
    if ($monto < 0) $monto = 0.0;
    $monto = round($monto, 2);

    $items[] = ['mes' => $mes, 'monto' => $monto];
  }

  if (!$items) out(false, ['message' => 'No hay plazos válidos'], 400);

  // Transacción
  $pdo->beginTransaction();

  // 1) Borramos los meses que ya no existan (si cambió plazo)
  $meses = array_map(fn($x) => (int)$x['mes'], $items);
  $placeholders = implode(',', array_fill(0, count($meses), '?'));

  // Borra todo lo que NO esté en la lista enviada
  $sqlDel = "DELETE FROM caratula_plazos
             WHERE solicitud_id = ?
             AND mes NOT IN ($placeholders)";
  $stDel = $pdo->prepare($sqlDel);
  $stDel->execute(array_merge([$solicitud_id], $meses));

  // 2) Upsert de los meses enviados
  $sqlUp = "INSERT INTO caratula_plazos (solicitud_id, mes, monto)
            VALUES (:sid, :mes, :monto)
            ON DUPLICATE KEY UPDATE monto = VALUES(monto), updated_at = CURRENT_TIMESTAMP";
  $stUp = $pdo->prepare($sqlUp);

  foreach ($items as $it) {
    $stUp->execute([
      ':sid' => $solicitud_id,
      ':mes' => (int)$it['mes'],
      ':monto' => (string)number_format((float)$it['monto'], 2, '.', ''),
    ]);
  }

  $pdo->commit();
  out(true, ['message' => 'Plazos guardados', 'count' => count($items)]);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  out(false, ['message' => 'Error al guardar plazos', 'error' => $e->getMessage()], 500);
}