<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
  // Conexión
  require_once __DIR__ . '/../../db/conexion.php'; // Debe definir $pdo (PDO)
  if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('Conexión PDO no disponible');
  }

  // Parámetro
  $sid = isset($_GET['solicitud_id']) ? (int)$_GET['solicitud_id'] : 0;
  if ($sid <= 0) {
    throw new InvalidArgumentException('solicitud_id requerido');
  }

  // === Retroactivo (si existe)
  $sqlRetro = "
    SELECT
      r.id,
      r.solicitud_id,
      r.cliente_id,
      r.nss,
      r.fecha_baja,
      r.umas_cotizar,
      r.salario,
      r.periodos_cotizar,
      r.fecha_alta,
      r.created_at,
      r.updated_at,
      DATE(r.created_at) AS fecha_calculo_date,   -- solo fecha (fija)
      r.created_at       AS fecha_calculo_ts      -- timestamp completo
    FROM retroactivo r
    WHERE r.solicitud_id = ?
    LIMIT 1
  ";
  $st = $pdo->prepare($sqlRetro);
  $st->execute([$sid]);
  $retro = $st->fetch(PDO::FETCH_ASSOC) ?: null;

  // === Cliente básico (para pintar el nombre / tener cliente_id)
  $sqlCliente = "
    SELECT
      id,
      solicitud_id,
      nombres,
      apellido_paterno,
      apellido_materno,
      correo
    FROM datos_personales
    WHERE solicitud_id = ?
    LIMIT 1
  ";
  $sc = $pdo->prepare($sqlCliente);
  $sc->execute([$sid]);
  $cliente = $sc->fetch(PDO::FETCH_ASSOC) ?: null;

  // Si el retro existe pero no trae cliente_id y tenemos cliente, sugiere al front el id
  if ($retro && empty($retro['cliente_id']) && $cliente && !empty($cliente['id'])) {
    $retro['cliente_id_sugerido'] = (int)$cliente['id'];
  }

  echo json_encode(
    ['ok' => true, 'retroactivo' => $retro, 'cliente' => $cliente],
    JSON_UNESCAPED_UNICODE
  );

} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
exit;
