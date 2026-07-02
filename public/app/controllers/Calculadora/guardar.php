<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
  // Ajusta esta ruta si tu conexion.php vive en otra carpeta.
  // Debe exponer $pdo (PDO) conectado a tu BD.
  require_once __DIR__ . '/../../db/conexion.php';

  if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('Conexión PDO no disponible');
  }

  // ===== Entrada (JSON o form-data) =====
  $raw  = file_get_contents('php://input');
  $data = json_decode($raw, true);
  if (!is_array($data)) { $data = $_POST; }

  // Campos esperados
  $solicitud_id     = isset($data['solicitud_id']) ? (int)$data['solicitud_id'] : 0;
  $cliente_id       = isset($data['cliente_id'])   ? (int)$data['cliente_id']   : 0;
  $nss              = trim((string)($data['nss'] ?? ''));
  $fecha_baja       = trim((string)($data['fecha_baja'] ?? ''));   // YYYY-MM-DD
  $umas_cotizar     = (float)($data['umas_cotizar'] ?? 1);
  $salario          = (float)($data['salario'] ?? 0);
  // permitir null cuando aparezca "—" o cadena vacía
  $periodos_cotizar = array_key_exists('periodos_cotizar', $data)
                      ? (($data['periodos_cotizar'] === '' || $data['periodos_cotizar'] === null) ? null : (int)$data['periodos_cotizar'])
                      : null;
  $fecha_alta       = trim((string)($data['fecha_alta'] ?? ''));

  // ===== Validaciones mínimas =====
  if ($solicitud_id <= 0) throw new InvalidArgumentException('solicitud_id requerido');

  // Resolver cliente_id automáticamente si no viene
  if ($cliente_id <= 0) {
    $q = $pdo->prepare("SELECT id FROM datos_personales WHERE solicitud_id = ? LIMIT 1");
    $q->execute([$solicitud_id]);
    $cliente_id = (int)($q->fetchColumn() ?: 0);
    if ($cliente_id <= 0) {
      throw new InvalidArgumentException('No se encontró cliente para esa solicitud');
    }
  }

  if ($nss === '')        throw new InvalidArgumentException('nss requerido');
  if ($fecha_baja === '') throw new InvalidArgumentException('fecha_baja requerida');
  if ($fecha_alta === '') throw new InvalidArgumentException('fecha_alta requerida');
  if ($salario <= 0)      throw new InvalidArgumentException('salario debe ser > 0');
  if ($umas_cotizar <= 0) $umas_cotizar = 1;

  // ===== UPSERT =====
  // Requiere UNIQUE (solicitud_id) en la tabla retroactivo
  $sql = "
    INSERT INTO retroactivo
      (solicitud_id, cliente_id, nss, fecha_baja, umas_cotizar, salario, periodos_cotizar, fecha_alta)
    VALUES
      (:solicitud_id, :cliente_id, :nss, :fecha_baja, :umas_cotizar, :salario, :periodos_cotizar, :fecha_alta)
    ON DUPLICATE KEY UPDATE
      cliente_id       = VALUES(cliente_id),
      nss              = VALUES(nss),
      fecha_baja       = VALUES(fecha_baja),
      umas_cotizar     = VALUES(umas_cotizar),
      salario          = VALUES(salario),
      periodos_cotizar = VALUES(periodos_cotizar),
      fecha_alta       = VALUES(fecha_alta),
      updated_at       = CURRENT_TIMESTAMP
  ";

  $st = $pdo->prepare($sql);
  $ok = $st->execute([
    ':solicitud_id'     => $solicitud_id,
    ':cliente_id'       => $cliente_id,
    ':nss'              => $nss,
    ':fecha_baja'       => $fecha_baja,
    ':umas_cotizar'     => $umas_cotizar,
    ':salario'          => $salario,
    ':periodos_cotizar' => $periodos_cotizar,
    ':fecha_alta'       => $fecha_alta,
  ]);

  if (!$ok) throw new RuntimeException('No se pudo guardar');

  // id del registro (insertado o existente)
  $id = (int)$pdo->lastInsertId();
  if ($id === 0) {
    $q = $pdo->prepare("SELECT id FROM retroactivo WHERE solicitud_id = ? LIMIT 1");
    $q->execute([$solicitud_id]);
    $id = (int)($q->fetchColumn() ?: 0);
  }

  echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
exit;
