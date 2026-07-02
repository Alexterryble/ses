<?php
declare(strict_types=1);

require_once __DIR__ . '/../../db/conexion.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
  if (!isset($conn) || !($conn instanceof PDO)) {
    throw new RuntimeException('Conexión PDO no disponible.');
  }

  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Puede venir como folio, id o solicitud_id
  $valor = $_GET['folio'] ?? $_GET['id'] ?? $_GET['solicitud_id'] ?? null;

  if (!$valor) {
    echo json_encode([
      'success' => false,
      'message' => 'Folio o ID no proporcionado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $valor = trim((string)$valor);

  /*
    Si viene numérico, busca primero por id.
    Si viene como CIP-2026-00001, busca por folio.
  */
  if (ctype_digit($valor)) {
    $stmt = $conn->prepare("
      SELECT *
      FROM solicitudes
      WHERE id = :valor
      LIMIT 1
    ");
    $stmt->execute([
      ':valor' => (int)$valor
    ]);
  } else {
    $stmt = $conn->prepare("
      SELECT *
      FROM solicitudes
      WHERE folio = :valor
      LIMIT 1
    ");
    $stmt->execute([
      ':valor' => $valor
    ]);
  }

  $data = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$data) {
    echo json_encode([
      'success' => false,
      'message' => 'No se encontró la solicitud'
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // Fallback para registros viejos o nulos
  if (!isset($data['tasa_mensual']) || $data['tasa_mensual'] === null || $data['tasa_mensual'] === '') {
    $data['tasa_mensual'] = '10.50';
  }

  echo json_encode([
    'success' => true,
    'datos'   => $data
  ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Throwable $e) {
  http_response_code(500);

  echo json_encode([
    'success' => false,
    'message' => 'Error al obtener la solicitud',
    'error'   => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}