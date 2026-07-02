<?php
// Asegúrate de que no haya NADA antes de <?php, ni espacios, ni líneas en blanco
session_start();
require_once __DIR__ . '/app/db/conexion.php'; // Asegúrate de que conexion.php no imprima nada

// Limpiar cualquier buffer de salida que pudiera existir
ob_clean(); // Agrega esta línea para limpiar el búfer de salida ANTES de enviar encabezados

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => []];

try {
    global $conn;

    if (!isset($conn) || !$conn instanceof PDO) {
        throw new Exception("La conexión a la base de datos no fue establecida correctamente en conexion.php.");
    }

    $stmt = $conn->prepare("
        SELECT 
            c.cita_id,
            c.solicitud_id,
            c.asesor_id,
            c.asesor_nombre,
            c.fecha_hora_cita,
            c.estado_cita,
            c.fecha_creacion,
            dp.nombres AS nombres_prospecto,
            dp.apellido_paterno AS apellido_paterno_prospecto,
            dp.apellido_materno AS apellido_materno_prospecto
        FROM 
            citas c
        JOIN 
            solicitudes s ON c.solicitud_id = s.id
        JOIN 
            datos_personales dp ON s.id = dp.solicitud_id
        ORDER BY 
            c.fecha_hora_cita ASC
    ");

    $stmt->execute();
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($citas as &$cita) {
        $cita['apellidos_prospecto'] = trim($cita['apellido_paterno_prospecto'] . ' ' . ($cita['apellido_materno_prospecto'] ?? ''));
        unset($cita['apellido_paterno_prospecto']);
        unset($cita['apellido_materno_prospecto']);
    }

    $response['success'] = true;
    $response['data'] = $citas;

} catch (PDOException $e) {
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    error_log("Error al obtener citas (PDOException): " . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Error general al obtener citas: ' . $e->getMessage();
    error_log("Error general al obtener citas: " . $e->getMessage());
} finally {
    if (isset($conn) && $conn instanceof PDO) {
        $conn = null;
    }
}

echo json_encode($response);
exit; // Asegúrate de que esta línea esté siempre al final y sin nada más después
?>