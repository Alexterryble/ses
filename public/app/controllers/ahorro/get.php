<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../../db/conexion.php';

try {
  if (session_status() === PHP_SESSION_NONE) session_start();

  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'ID inválido']);
    exit;
  }

  $asesor = $_SESSION['asesor'] ?? [];
  $asesor_id = (int)($asesor['id'] ?? $asesor['asesor_id'] ?? 0);
  $rol = strtolower((string)($asesor['rol'] ?? 'asesor'));

  $sql = "SELECT
    id, asesor_id, folio,
    nombre, ap_paterno, ap_materno, rfc,
    cp, direccion, correo, telefono,
    beneficiario_nombre, beneficiario_curp, beneficiario_telefono, beneficiario_parentesco,
    fecha_inicio_ahorro, fecha_devolucion,
    monto_semanal, porcentaje, estado,
    creado_en, actualizado_en
  FROM ahorro
  WHERE id = :id";

  $params = [':id' => $id];

  // ✅ PERMITIR SI:
  // - Es admin
  // - Es asesor dueño
  // - Es asesor ID 7 (aunque no sea admin)
  if ($rol !== 'admin' && $asesor_id !== 7) {
    $sql .= " AND asesor_id = :asesor_id";
    $params[':asesor_id'] = $asesor_id;
  }

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'No encontrado']);
    exit;
  }

  echo json_encode(['ok'=>true,'data'=>$row]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
