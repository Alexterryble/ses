<?php
declare(strict_types=1);
require_once __DIR__ . '/../../db/conexion.php';
header('Content-Type: application/json');

$folio = $_GET['folio'] ?? null;

if (!$folio) {
  echo json_encode([
    'success' => false,
    'message' => 'Folio no proporcionado'
  ]);
  exit;
}

try {
  $stmt = $conn->prepare("SELECT * FROM firma_declaracion WHERE solicitud_id = ?");
  $stmt->execute([$folio]);
  $data = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($data) {
    // ✅ Convertir campos binarios existentes (BLOBs) en base64 solo si tienen contenido
    $data['firma_base64'] = !empty($data['firma_base64']) ? 'data:image/png;base64,' . base64_encode($data['firma_base64']) : null;
    $data['firma_base64_2'] = !empty($data['firma_base64_2']) ? 'data:image/png;base64,' . base64_encode($data['firma_base64_2']) : null;

    echo json_encode([
      'success' => true,
      'datos' => $data
    ]);
  } else {
    echo json_encode([
      'success' => false,
      'message' => 'No se encontró información de firma_declaracion para este folio.'
    ]);
  }
} catch (PDOException $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Error: ' . $e->getMessage()
  ]);
}
