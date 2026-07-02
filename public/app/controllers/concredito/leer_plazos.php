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

try {
  $sid = isset($_GET['solicitud_id']) ? (int)$_GET['solicitud_id'] : 0;
  if ($sid <= 0) out(false, ['message' => 'Falta solicitud_id'], 400);

  $st = $pdo->prepare("SELECT mes, monto FROM caratula_plazos WHERE solicitud_id = ? ORDER BY mes ASC");
  $st->execute([$sid]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  out(true, ['items' => $rows]);

} catch (Throwable $e) {
  out(false, ['message' => 'Error al leer plazos', 'error' => $e->getMessage()], 500);
}