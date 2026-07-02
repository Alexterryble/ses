<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db/conexion.php';

function out(bool $ok, array $data = [], string $msg = ''): void {
  echo json_encode([
    'success' => $ok,
    'message' => $msg,
    'data'    => $data
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

// ✅ Acepta ?id=169  o  ?folio=CIP-2026-00001
$id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$folio = isset($_GET['folio']) ? trim((string)$_GET['folio']) : '';

if ($id <= 0 && $folio === '') {
  out(false, [], 'Falta parámetro: id o folio');
}

try {
  // ✅ solicitudes + datos_personales
  $sql = "
    SELECT
      s.id,
      s.folio,
      s.atendido_por,
      COALESCE(dp.nombres,'')           AS nombres,
      COALESCE(dp.apellido_paterno,'')  AS apellido_paterno,
      COALESCE(dp.apellido_materno,'')  AS apellido_materno
    FROM solicitudes s
    LEFT JOIN datos_personales dp
      ON dp.solicitud_id = s.id
    WHERE " . ($id > 0 ? "s.id = :id" : "s.folio = :folio") . "
    LIMIT 1
  ";

  $stmt = $pdo->prepare($sql);
  if ($id > 0) $stmt->bindValue(':id', $id, PDO::PARAM_INT);
  else         $stmt->bindValue(':folio', $folio, PDO::PARAM_STR);

  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) out(false, [], 'No se encontró la solicitud');

  // ✅ nombre completo limpio
  $nombreCliente = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
    $row['nombres'] ?? '',
    $row['apellido_paterno'] ?? '',
    $row['apellido_materno'] ?? ''
  ]))));

  out(true, [
    'id'            => (int)$row['id'],
    'folio'         => (string)($row['folio'] ?? ''),
    'atendido_por'  => (string)($row['atendido_por'] ?? ''),
    'nombre_cliente'=> $nombreCliente,
  ]);

} catch (Throwable $e) {
  out(false, [], 'Error: ' . $e->getMessage());
}
