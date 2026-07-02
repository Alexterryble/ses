<?php
require_once(__DIR__ . '/../db/conexion.php');
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Validar ID de solicitud
$solicitud_id = $_POST['solicitud_id'] ?? null;
if (!$solicitud_id || !is_numeric($solicitud_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Falta o es inválido el ID de solicitud']);
    exit;
}

// Recolectar datos
$funcion_publica          = trim($_POST['funcion_publica'] ?? '');
$relacion_funcion_publica = trim($_POST['relacion_funcion_publica'] ?? '');
$folio_consulta           = trim($_POST['folio_consulta'] ?? '');
$firma_base64_1           = $_POST['firma_base64_1'] ?? '';
$firma_base64_2           = $_POST['firma_base64_2'] ?? '';
$lugar                    = trim($_POST['lugar'] ?? '');
$fecha                    = trim($_POST['fecha'] ?? '');

try {
    // Verificar existencia de la solicitud
    $check = $conn->prepare("SELECT id FROM solicitudes WHERE id = ?");
    $check->execute([$solicitud_id]);

    if ($check->rowCount() === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'El ID de solicitud no existe en la base de datos'
        ]);
        exit;
    }

    // Revisar si ya existe registro
    $checkFirma = $conn->prepare("SELECT id FROM firma_declaracion WHERE solicitud_id = ?");
    $checkFirma->execute([$solicitud_id]);

    if ($checkFirma->rowCount() > 0) {
        // ✅ Actualizar
        $stmt = $conn->prepare("UPDATE firma_declaracion SET 
            funcion_publica = ?, 
            relacion_funcion_publica = ?, 
            folio_consulta = ?, 
            firma_base64 = ?, 
            firma_base64_2 = ?, 
            lugar = ?, 
            fecha = ?
            WHERE solicitud_id = ?");
        $stmt->execute([
            $funcion_publica,
            $relacion_funcion_publica,
            $folio_consulta,
            $firma_base64_1,
            $firma_base64_2,
            $lugar,
            $fecha,
            $solicitud_id
        ]);

        echo json_encode(['status' => 'ok', 'message' => 'Firma actualizada correctamente.']);
    } else {
        // ✅ Insertar
        $stmt = $conn->prepare("INSERT INTO firma_declaracion 
            (solicitud_id, funcion_publica, relacion_funcion_publica, folio_consulta, firma_base64, firma_base64_2, lugar, fecha)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $solicitud_id,
            $funcion_publica,
            $relacion_funcion_publica,
            $folio_consulta,
            $firma_base64_1,
            $firma_base64_2,
            $lugar,
            $fecha
        ]);
        echo json_encode(['status' => 'ok', 'message' => 'Firma guardada correctamente.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>
