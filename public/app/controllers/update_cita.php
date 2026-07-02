<?php
session_start();
require_once __DIR__ . '/../db/conexion.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $cita_id = $data['id'] ?? null;
    $new_fecha_hora_cita_db = $data['fecha_hora'] ?? null;
    $new_estado_cita = $data['estado'] ?? null;

    if (empty($cita_id) || empty($new_fecha_hora_cita_db)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Faltan campos requeridos para actualizar la cita (ID y fecha/hora).']);
        exit;
    }

    try {
        global $conn;

        if (!isset($conn) || !$conn instanceof PDO) {
            throw new Exception("Conexión a base de datos no válida.");
        }

        $sql = "UPDATE citas SET fecha_hora_cita = :fecha_hora_cita, estado_cita = :estado_cita WHERE cita_id = :cita_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':fecha_hora_cita', $new_fecha_hora_cita_db);
        $stmt->bindParam(':estado_cita', $new_estado_cita);
        $stmt->bindParam(':cita_id', $cita_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Cita actualizada correctamente.';
            } else {
                $response['message'] = 'No se encontró la cita o no hubo cambios.';
            }
        } else {
            http_response_code(500);
            $response['message'] = 'Error al actualizar en la base de datos.';
        }

    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        http_response_code(500);
        $response['message'] = 'General error: ' . $e->getMessage();
    }
} else {
    http_response_code(405);
    $response['message'] = 'Método no permitido';
}

echo json_encode($response);
exit;
?>
