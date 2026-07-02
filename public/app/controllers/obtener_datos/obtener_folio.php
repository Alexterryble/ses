<?php
require_once __DIR__ . '/../../db/conexion.php';
header('Content-Type: application/json');

$solicitud_id = $_GET['solicitud_id'] ?? null;

if (!$solicitud_id) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$stmt = $conn->prepare("SELECT folio FROM solicitudes WHERE id = ?");
$stmt->execute([$solicitud_id]);
$folio = $stmt->fetchColumn();

if ($folio) {
    echo json_encode(['success' => true, 'folio' => $folio]);
} else {
    echo json_encode(['success' => false, 'message' => 'Folio no encontrado']);
}
