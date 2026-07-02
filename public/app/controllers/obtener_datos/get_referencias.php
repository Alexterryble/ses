<?php
require_once __DIR__ . '/../../db/conexion.php';
header('Content-Type: application/json');

$folio = $_GET['folio'] ?? null;

if (!$folio) {
  echo json_encode(['success' => false, 'message' => 'Folio no proporcionado']);
  exit;
}

try {
  $stmt = $conn->prepare("
    SELECT tipo, numero, nombre_completo, direccion, telefono, celular, email, parentesco 
    FROM referencias_solicitante 
    WHERE solicitud_id = ? 
    ORDER BY tipo, numero
  ");
  $stmt->execute([$folio]);
  $referencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if ($referencias) {
    echo json_encode(['success' => true, 'referencias' => $referencias]);
  } else {
    echo json_encode(['success' => false, 'message' => 'No se encontraron referencias para este folio.']);
  }

} catch (PDOException $e) {
  echo json_encode(['success' => false, 'message' => 'Error al consultar: ' . $e->getMessage()]);
}
