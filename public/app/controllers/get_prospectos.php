<?php
session_start();
// Asegúrate de que esta ruta sea correcta a tu archivo de conexión a la base de datos
require_once(__DIR__ . '/../db/conexion.php');
session_start(); // Iniciar la sesión para acceder a datos del usuario logueado

// Establecer la cabecera para indicar que la respuesta será JSON
header('Content-Type: application/json');

// Inicializar la respuesta con valores por defecto
$response = ['success' => false, 'message' => '', 'data' => []];

// Verificar que la solicitud sea de tipo GET (normalmente para obtener datos)
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    try {
        global $conn; // Acceder a la conexión PDO

        // Consulta SQL para obtener los datos de prospectos
        // Unimos 'solicitudes' con 'datos_personales' usando 'solicitud_id'
        // Seleccionamos los campos relevantes de ambas tablas
        $stmt = $conn->prepare("
            SELECT
                s.id AS solicitud_id,
                s.atendido_por,
                s.fecha_registro,
                dp.nombres,
                dp.apellido_paterno,
                dp.apellido_materno,
                dp.telefono,
                dp.correo
            FROM
                solicitudes s
            JOIN
                datos_personales dp ON s.id = dp.solicitud_id
            ORDER BY
                s.fecha_registro DESC
        ");

        // Ejecutar la consulta
        $stmt->execute();

        // Obtener todos los resultados como un array asociativo
        $prospectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si se encontraron prospectos, establecer la respuesta como exitosa
        if ($prospectos) {
            $response['success'] = true;
            $response['message'] = 'Prospectos obtenidos exitosamente.';
            $response['data'] = $prospectos;
        } else {
            $response['message'] = 'No se encontraron prospectos.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
        // Registrar el error para depuración (no mostrar detalles sensibles al usuario)
        error_log("Error en get_prospectos.php (PDO): " . $e->getMessage());
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
        error_log("Error en get_prospectos.php (General): " . $e->getMessage());
    }
} else {
    $response['message'] = 'Método de solicitud no permitido.';
    error_log("get_prospectos.php: Método de solicitud no permitido (no es GET).");
}

// Enviar la respuesta JSON al frontend
echo json_encode($response);
exit;
?>