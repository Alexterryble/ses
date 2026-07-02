<?php
session_start();

// Configuración de errores
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
error_log("confirmar_cita_rapida.php: Script iniciado.");

// Respuesta por defecto
$response = ['success' => false, 'message' => ''];

try {
    require_once __DIR__ . '/../db/conexion.php';

    if (!isset($conn) || !$conn instanceof PDO) {
        throw new Exception("La conexión a la base de datos no fue establecida correctamente en conexion.php.");
    }

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $solicitud_id = $_POST['solicitud_id'] ?? null;
        $fecha_cita = $_POST['fecha_cita'] ?? null;
        $hora_cita = $_POST['hora_cita'] ?? null;

        $asesor_id = $_SESSION['asesor_id'] ?? null;
        $asesor_nombre = $_SESSION['asesor_nombre'] ?? 'Desconocido';

        error_log("confirmar_cita_rapida.php: Datos POST recibidos: " . print_r($_POST, true));
        error_log("confirmar_cita_rapida.php: Asesor ID de sesión: " . ($asesor_id ?? 'NULO'));
        error_log("confirmar_cita_rapida.php: Asesor Nombre de sesión: " . ($asesor_nombre ?? 'NULO'));

        // Validación
        if (empty($solicitud_id) || empty($asesor_id) || empty($asesor_nombre) || empty($fecha_cita) || empty($hora_cita)) {
            http_response_code(400);
            $response['message'] = 'Faltan campos requeridos para programar la cita.';
            error_log("confirmar_cita_rapida.php: Validación fallida - campos requeridos vacíos.");
        } else {
            $solicitud_id = intval($solicitud_id);
            $asesor_id = intval($asesor_id);
            $fecha_hora_cita_db = $fecha_cita . ' ' . $hora_cita . ':00';
            $estado_cita = 'Pendiente';

            // Insertar la cita sin tipo_cita ni notas
            $stmt = $conn->prepare("INSERT INTO citas (solicitud_id, asesor_id, asesor_nombre, fecha_hora_cita, estado_cita) 
                                     VALUES (:solicitud_id, :asesor_id, :asesor_nombre, :fecha_hora_cita, :estado_cita)");

            $stmt->bindParam(':solicitud_id', $solicitud_id, PDO::PARAM_INT);
            $stmt->bindParam(':asesor_id', $asesor_id, PDO::PARAM_INT);
            $stmt->bindParam(':asesor_nombre', $asesor_nombre, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_hora_cita', $fecha_hora_cita_db, PDO::PARAM_STR);
            $stmt->bindParam(':estado_cita', $estado_cita, PDO::PARAM_STR);

            error_log("confirmar_cita_rapida.php: Ejecutando INSERT con solicitud_id=$solicitud_id, asesor_id=$asesor_id, fecha=$fecha_hora_cita_db");

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Cita registrada correctamente.';
                error_log("confirmar_cita_rapida.php: Cita registrada exitosamente para solicitud_id: $solicitud_id");
            } else {
                http_response_code(500);
                $errorInfo = $stmt->errorInfo();
                $response['message'] = 'Error al registrar cita: ' . ($errorInfo[2] ?? 'Unknown error');
                error_log("confirmar_cita_rapida.php: Fallo al ejecutar consulta. PDO error: " . print_r($errorInfo, true));
            }

            $stmt->closeCursor();
        }

    } else {
        http_response_code(405);
        $response['message'] = 'Método no permitido.';
        error_log("confirmar_cita_rapida.php: Método no permitido.");
    }

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log("confirmar_cita_rapida.php: Error PDO: " . $e->getMessage());
} catch (Throwable $e) {
    http_response_code(500);
    $response['message'] = 'General server error: ' . $e->getMessage();
    error_log("confirmar_cita_rapida.php: Error general: " . $e->getMessage());
} finally {
    if (isset($conn) && $conn instanceof PDO) {
        $conn = null;
    }
}

echo json_encode($response);
exit;
