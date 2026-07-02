<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../db/conexion.php';

$solicitud_id   = $_POST['solicitud_id']   ?? null;
$tipo_documento = $_POST['tipo_documento'] ?? null;

if (!$solicitud_id || !$tipo_documento || !isset($_FILES['archivo'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$archivo = $_FILES['archivo'];

// Validamos tipo
if ($archivo['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error al subir archivo']);
    exit;
}
if (mime_content_type($archivo['tmp_name']) !== 'application/pdf') {
    echo json_encode(['success' => false, 'message' => 'Solo se permiten PDFs']);
    exit;
}

// --- MODO LOCAL (XAMPP) ---
// Guardar en carpeta local primero
$carpetaDestino = __DIR__ . '/../../uploads/';
if (!is_dir($carpetaDestino)) {
    mkdir($carpetaDestino, 0777, true);
}
$nombreUnico = uniqid() . "_" . basename($archivo['name']);
$rutaDestino = $carpetaDestino . $nombreUnico;

if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo mover el archivo']);
    exit;
}

// Guardar metadata en la BD
$stmt = $conn->prepare("INSERT INTO documentos_solicitud 
    (solicitud_id, tipo_documento, nombre_archivo, ruta_archivo, mime_type, tamano_bytes, subido_por, fecha_subida, version) 
    VALUES (?, ?, ?, ?, ?, ?, 'sistema', NOW(), 1)");

$stmt->execute([
    $solicitud_id,
    $tipo_documento,
    $archivo['name'],
    $nombreUnico,
    $archivo['type'],
    $archivo['size']
]);

echo json_encode([
    'success' => true,
    'message' => 'Archivo subido con éxito',
    'archivo' => $nombreUnico
]);
