<?php
require_once __DIR__ . '/../../db/conexion.php';

header('Content-Type: application/json');

$folio = $_GET['folio'] ?? null;

if (!$folio) {
  echo json_encode(['success' => false, 'message' => 'Folio no proporcionado']);
  exit;
}

try {
  $stmt = $conn->prepare("SELECT * FROM info_laboral WHERE solicitud_id = ?");
  $stmt->execute([$folio]);
  $data = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($data) {
    echo json_encode(['success' => true, 'datos' => $data]);
  } else {
    echo json_encode(['success' => false, 'message' => 'No se encontró información laboral para esta solicitud.']);
  }
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'message' => 'Error al consultar: ' . $e->getMessage()]);
}
