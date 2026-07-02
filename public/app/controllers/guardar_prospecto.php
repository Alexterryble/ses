<?php
// Asegúrate de que esta ruta sea correcta a tu archivo de conexión a la base de datos
require_once(__DIR__ . '/../db/conexion.php');
session_start(); // Iniciar la sesión para acceder a datos del usuario logueado
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Establecer la cabecera para indicar que la respuesta será JSON
header('Content-Type: application/json');

// Inicializar la respuesta con valores por defecto
$response = ['success' => false, 'message' => '', 'solicitud_id' => null];

// Verificar que la solicitud sea de tipo POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // --- INICIO DEPURACIÓN: Registrar todos los datos POST recibidos ---
    error_log("guardar_prospecto.php: Solicitud POST recibida.");
    error_log("Datos POST: " . print_r($_POST, true));
    error_log("Asesor logueado (SESSION): " . ($_SESSION['asesor_nombre'] ?? 'No disponible en sesión'));
    // --- FIN DEPURACIÓN ---

    // 1. Obtener el nombre del asesor logueado desde la sesión
    // Si la sesión 'asesor_nombre' no está definida, se usará 'Desconocido'
    $atendido_por = $_SESSION['asesor_nombre'] ?? 'Desconocido';

    // 2. Recopilar datos del formulario de prospecto enviados por POST
    // Usar el operador de fusión de null (??) para asignar null si la variable no existe
    $nombres = $_POST['nombres'] ?? null;
    $apellido_paterno = $_POST['apellido_paterno'] ?? null;
    $apellido_materno = $_POST['apellido_materno'] ?? null;
    $telefono = $_POST['telefono'] ?? null;
    $correo = $_POST['correo'] ?? null;

    // 3. Validación básica de los campos obligatorios
    if (empty($nombres) || empty($apellido_paterno) || empty($telefono) || empty($correo)) {
        $response['message'] = 'Los campos de nombre, apellido paterno, teléfono y correo son obligatorios.';
        error_log("guardar_prospecto.php: Validación fallida - campos obligatorios vacíos.");
        echo json_encode($response);
        exit; // Detener la ejecución si faltan campos obligatorios
    }

    try {
        // Obtener la conexión PDO a la base de datos
        // Usamos la variable $conn que se define en el archivo 'conexion.php' incluido.
        global $conn; // Declarar $conn como global para acceder a ella desde este ámbito

        // Iniciar una transacción para asegurar la atomicidad de las dos inserciones
        // Si una falla, ambas se revertirán
        $conn->beginTransaction();
        error_log("guardar_prospecto.php: Transacción iniciada.");

        // PASO A: Insertar un nuevo registro en la tabla 'solicitudes'
        // Solo se inserta el campo 'atendido_por' con el nombre del asesor.
        // Los campos 'medio', 'monto', 'plazo', 'frecuencia_pago' se dejan como NULL
        // o con sus valores por defecto definidos en la estructura de la tabla,
        // ya que se llenarán en el formulario de solicitud completo.
        $stmt_solicitud = $conn->prepare("INSERT INTO solicitudes (
            atendido_por, medio, monto, plazo, frecuencia_pago
        ) VALUES (
            :atendido_por, NULL, NULL, NULL, NULL
        )");
        $stmt_solicitud->bindParam(':atendido_por', $atendido_por);

        // --- DEPURACIÓN SQL Solicitud ---
        error_log("guardar_prospecto.php: Preparando SQL para 'solicitudes': " . $stmt_solicitud->queryString);
        // --- FIN DEPURACIÓN ---

        // Ejecutar la inserción en la tabla 'solicitudes'
        if (!$stmt_solicitud->execute()) {
            // --- DEPURACIÓN: Errores de ejecución de Solicitud ---
            error_log("guardar_prospecto.php: Falló la ejecución de la consulta de 'solicitudes'. Errores: " . print_r($stmt_solicitud->errorInfo(), true));
            // --- FIN DEPURACIÓN ---
            // Si la ejecución falla, lanzar una excepción
            throw new Exception('Error al iniciar la solicitud principal.');
        }

        // Obtener el ID del último registro insertado en la tabla 'solicitudes'
        $solicitud_id = $conn->lastInsertId();
        error_log("guardar_prospecto.php: Solicitud ID generada: " . $solicitud_id);

        // PASO B: Insertar los datos personales en la tabla 'datos_personales'
        // Se utiliza el 'solicitud_id' recién generado para vincular ambas tablas.
        $stmt_datos_personales = $conn->prepare("INSERT INTO datos_personales (
            solicitud_id, nombres, apellido_paterno, apellido_materno, telefono, correo
            -- Si recoges más campos en el formulario de prospecto, añádelos aquí
        ) VALUES (
            :solicitud_id, :nombres, :apellido_paterno, :apellido_materno, :telefono, :correo
        )");

        // Vincular los parámetros con los valores del formulario
        $stmt_datos_personales->bindParam(':solicitud_id', $solicitud_id);
        $stmt_datos_personales->bindParam(':nombres', $nombres);
        $stmt_datos_personales->bindParam(':apellido_paterno', $apellido_paterno);
        $stmt_datos_personales->bindParam(':apellido_materno', $apellido_materno);
        $stmt_datos_personales->bindParam(':telefono', $telefono);
        $stmt_datos_personales->bindParam(':correo', $correo);

        // --- DEPURACIÓN SQL Datos Personales ---
        error_log("guardar_prospecto.php: Preparando SQL para 'datos_personales': " . $stmt_datos_personales->queryString);
        // --- FIN DEPURACIÓN ---

        // Ejecutar la inserción en la tabla 'datos_personales'
        if (!$stmt_datos_personales->execute()) {
            // --- DEPURACIÓN: Errores de ejecución de Datos Personales ---
            error_log("guardar_prospecto.php: Falló la ejecución de la consulta de 'datos_personales'. Errores: " . print_r($stmt_datos_personales->errorInfo(), true));
            // --- FIN DEPURACIÓN ---
            // Si la ejecución falla, lanzar una excepción
            throw new Exception('Error al guardar los datos personales del prospecto.');
        }

        // Si ambas inserciones fueron exitosas, confirmar la transacción
        $conn->commit();
        error_log("guardar_prospecto.php: Transacción confirmada (commit).");

        // Preparar la respuesta de éxito
        $response['success'] = true;
        $response['message'] = 'Prospecto y Solicitud iniciados exitosamente.';
        $response['solicitud_id'] = $solicitud_id; // Devolver el ID de la solicitud principal al frontend

    } catch (PDOException $e) {
        // En caso de error de PDO (base de datos), revertir la transacción
        $conn->rollBack();
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
        // Registrar el error para depuración (no mostrar detalles sensibles al usuario)
        error_log("Error en guardar_prospecto.php (PDO): " . $e->getMessage());
    } catch (Exception $e) {
        // En caso de cualquier otra excepción, revertir la transacción
        $conn->rollBack();
        $response['message'] = 'Error: ' . $e->getMessage();
        // Registrar el error para depuración
        error_log("Error en guardar_prospecto.php (General): " . $e->getMessage());
    }
} else {
    // Si la solicitud no es POST, devolver un mensaje de error
    $response['message'] = 'Método de solicitud no permitido.';
    error_log("guardar_prospecto.php: Método de solicitud no permitido (no es POST).");
}

// Enviar la respuesta JSON al frontend
echo json_encode($response);
exit; // Asegurar que no se envíe ninguna otra salida
