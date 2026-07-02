<?php
require_once __DIR__ . '/../../db/conexion.php';

header('Content-Type: application/json');

// Aceptar 'solicitud_id' o 'folio' para mayor compatibilidad con tu JS
$solicitud_id = $_GET['solicitud_id'] ?? $_GET['folio'] ?? null;

if (!$solicitud_id) {
echo json_encode(['success' => false, 'message' => 'ID de solicitud o Folio no proporcionado']);
exit;
}

try {
$stmt = $conn->prepare("SELECT * FROM codeudores WHERE solicitud_id = ?");
$stmt->execute([$solicitud_id]); // Usa la variable unificada
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data) {
 echo json_encode(['success' => true, 'datos' => $data]);
 } else {
 echo json_encode(['success' => false, 'message' => 'No se encontró codeudor para esta solicitud.']);
 }
} catch (PDOException $e) {
 echo json_encode(['success' => false, 'message' => 'Error al consultar: ' . $e->getMessage()]);
}
// Tu JavaScript ya debería estar funcionando con los IDs que te pasé en la respuesta anterior.