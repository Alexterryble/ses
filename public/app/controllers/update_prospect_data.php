<?php
require_once(__DIR__ . '/../db/conexion.php');
session_start(); // Iniciar la sesión para acceder a datos del usuario logueado

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Leer el cuerpo de la solicitud RAW (cruda)
    $json_data = file_get_contents('php://input');
    // Decodificar el JSON a un array asociativo de PHP
    $data = json_decode($json_data, true);

    // Verificar si la decodificación fue exitosa y si el ID está presente
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Error al decodificar los datos JSON.';
        echo json_encode($response);
        exit;
    }

    if (!isset($data['solicitud_id'])) {
        $response['message'] = 'Solicitud ID faltante.';
        echo json_encode($response);
        exit;
    }

    // Validar y sanear los datos (ejemplo básico, considera validaciones más robustas)
    $solicitud_id = $data['solicitud_id'];
    $nombres = $data['nombres'] ?? '';
    $apellido_paterno = $data['apellido_paterno'] ?? '';
    $apellido_materno = $data['apellido_materno'] ?? '';
    $telefono = $data['telefono'] ?? '';
    $correo = $data['correo'] ?? '';

    try {
        global $conn;
        $stmt = $conn->prepare("
            UPDATE datos_personales
            SET nombres = :nombres,
                apellido_paterno = :apellido_paterno,
                apellido_materno = :apellido_materno,
                telefono = :telefono,
                correo = :correo
            WHERE solicitud_id = :solicitud_id
        ");

        $stmt->execute([
            ':nombres' => $nombres,
            ':apellido_paterno' => $apellido_paterno,
            ':apellido_materno' => $apellido_materno,
            ':telefono' => $telefono,
            ':correo' => $correo,
            ':solicitud_id' => $solicitud_id,
        ]);

        $response['success'] = true;
        $response['message'] = 'Prospecto actualizado correctamente.';
    } catch (PDOException $e) {
        $response['message'] = 'Error en base de datos: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Método no permitido.';
}

echo json_encode($response);
exit;
?>
