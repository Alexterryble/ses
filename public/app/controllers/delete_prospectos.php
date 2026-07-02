<?php
session_start();
require_once(__DIR__ . '/../db/conexion.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Leer el cuerpo de la solicitud como JSON
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true); // Decodificar el JSON a un array asociativo

    $solicitud_id = $data['solicitud_id'] ?? null;

    if (empty($solicitud_id)) {
        $response['message'] = 'ID de solicitud de prospecto no proporcionado.';
        echo json_encode($response);
        exit;
    }

    try {
        global $conn; // Acceder a la conexión PDO

        // Iniciar una transacción para asegurar la integridad de los datos
        $conn->beginTransaction();

        // Eliminar de la tabla 'datos_personales'
        $stmt_dp = $conn->prepare("DELETE FROM datos_personales WHERE solicitud_id = :solicitud_id");
        $stmt_dp->bindParam(':solicitud_id', $solicitud_id, PDO::PARAM_INT);
        
        if ($stmt_dp->execute()) {
            // Verificar si se eliminó alguna fila
            if ($stmt_dp->rowCount() > 0) {
                $conn->commit(); // Confirmar la transacción
                $response['success'] = true;
                $response['message'] = 'Prospecto eliminado exitosamente.';
            } else {
                $conn->rollBack(); // Revertir si no se encontró el prospecto
                $response['message'] = 'No se encontró el prospecto con el ID proporcionado.';
            }
        } else {
            $conn->rollBack(); // Revertir la transacción en caso de error de ejecución
            $response['message'] = 'Error al eliminar el prospecto de la base de datos.';
            error_log("Error al ejecutar DELETE en datos_personales: " . print_r($stmt_dp->errorInfo(), true));
        }

    } catch (PDOException $e) {
        $conn->rollBack(); // Revertir la transacción en caso de error de BD
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
        error_log("Error en eliminar_prospecto.php (PDO): " . $e->getMessage());
    } catch (Exception $e) {
        $conn->rollBack(); // Revertir la transacción en caso de otros errores
        $response['message'] = 'Error: ' . $e->getMessage();
        error_log("Error en eliminar_prospecto.php (General): " . $e->getMessage());
    }
} else {
    $response['message'] = 'Método de solicitud no permitido.';
}

echo json_encode($response);
exit;
?>
