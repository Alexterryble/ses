<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once __DIR__ . '/../../db/conexion.php';

$solicitud_id = $_GET['solicitud_id'] ?? null;

if (!$solicitud_id) {
    echo json_encode(['success' => false, 'message' => 'No se proporcionó solicitud_id']);
    exit;
}

try {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $response = [];

    // Utilidad para consultas
    function obtenerDatos($conn, $tabla, $campo, $id) {
        $stmt = $conn->prepare("SELECT * FROM $tabla WHERE $campo = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
// solicitudes (encabezado del formato)
$response['solicitudes'] = obtenerDatos($conn, 'solicitudes', 'id', $solicitud_id)[0] ?? null;

    // datos_personales
    $response['datos_personales'] = obtenerDatos($conn, 'datos_personales', 'solicitud_id', $solicitud_id)[0] ?? null;

    // info_laboral
    $response['info_laboral'] = obtenerDatos($conn, 'info_laboral', 'solicitud_id', $solicitud_id)[0] ?? null;

    // info_adicional
    $response['info_adicional'] = obtenerDatos($conn, 'info_adicional', 'solicitud_id', $solicitud_id)[0] ?? null;

    // referencias_solicitante
    $response['referencias'] = obtenerDatos($conn, 'referencias_solicitante', 'solicitud_id', $solicitud_id);

    // codeudores
    $response['codeudores'] = obtenerDatos($conn, 'codeudores', 'solicitud_id', $solicitud_id);

    // funcionarios_firma (convertir BLOBs a base64)
    $funcionariosFirma = obtenerDatos($conn, 'funcionarios_firma', 'solicitud_id', $solicitud_id);
    if (!empty($funcionariosFirma)) {
        $ff = $funcionariosFirma[0];
        $response['funcionarios_firma'] = [
            'desempenia_funcion_publica' => $ff['desempenia_funcion_publica'] ?? null,
            'relacion_funcion_publica'   => $ff['relacion_funcion_publica'] ?? null,
            'lugar'                      => $ff['lugar'] ?? null,                 // 👈 agrega esto
            'fecha_firma'                => $ff['fecha_firma'] ?? ($ff['fecha'] ?? null), // 👈 y esto
            'firma'                      => !empty($ff['firma']) ? 'data:image/png;base64,' . base64_encode($ff['firma']) : null,
            'firma_formulario'           => !empty($ff['firma_formulario']) ? 'data:image/png;base64,' . base64_encode($ff['firma_formulario']) : null
        ];
    } else {
        $response['funcionarios_firma'] = null;
    }

// firma_declaracion
$firmaRaw = obtenerDatos($conn, 'firma_declaracion', 'solicitud_id', $solicitud_id);
if ($firmaRaw) {
    $firma = $firmaRaw[0];

    $firma_base64_1 = $firma['firma_base64']   ?? null;
    $firma_base64_2 = $firma['firma_base64_2'] ?? null;

    $response['firma_declaracion'] = [
        'funcion_publica'          => $firma['funcion_publica'] ?? null,
        'relacion_funcion_publica' => $firma['relacion_funcion_publica'] ?? null,
        // 👇 ESTO FALTABA
        'lugar'                    => $firma['lugar'] ?? null,
        'fecha'                    => $firma['fecha'] ?? null,
        // firmas
        'firma_formulario'   => ($firma_base64_1 && str_starts_with($firma_base64_1, 'data:'))
                                  ? $firma_base64_1
                                  : ($firma_base64_1 ? 'data:image/png;base64,' . base64_encode($firma_base64_1) : null),
        'firma_autorizacion' => ($firma_base64_2 && str_starts_with($firma_base64_2, 'data:'))
                                  ? $firma_base64_2
                                  : ($firma_base64_2 ? 'data:image/png;base64,' . base64_encode($firma_base64_2) : null),
    ];
} else {
    $response['firma_declaracion'] = null;
}


    // citas
    $response['citas'] = obtenerDatos($conn, 'citas', 'solicitud_id', $solicitud_id);

    // Respuesta final
    echo json_encode([
        'success' => true,
        'solicitud_id' => $solicitud_id,
        'datos' => $response
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener datos: ' . $e->getMessage()
    ]);
}
