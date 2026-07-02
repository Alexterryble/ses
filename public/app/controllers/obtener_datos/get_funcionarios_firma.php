<?php
require_once __DIR__ . '/../../db/conexion.php';
header('Content-Type: application/json');

$folio = $_GET['folio'] ?? null;

if (!$folio) {
  echo json_encode(['success' => false, 'message' => 'Folio no proporcionado']);
  exit;
}

try {
  $stmt = $conn->prepare("SELECT * FROM funcionarios_firma WHERE solicitud_id = ?");
  $stmt->execute([$folio]);
  $data = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($data) {
    // Convertir las firmas BLOB a base64
    $data['firma'] = $data['firma'] ? 'data:image/png;base64,' . base64_encode($data['firma']) : null;
    $data['firma_formulario'] = $data['firma_formulario'] ? 'data:image/png;base64,' . base64_encode($data['firma_formulario']) : null;

    echo json_encode(['success' => true, 'datos' => $data]);
  } else {
    echo json_encode(['success' => false, 'message' => 'No se encontraron firmas del funcionario.']);
  }
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
