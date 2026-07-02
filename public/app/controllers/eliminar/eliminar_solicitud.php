<?php
// public/app/controllers/eliminar/eliminar_solicitud.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    require_once __DIR__ . '/../../db/conexion.php';

    $pdo = $pdo ?? ($conn ?? null);
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('Sin conexión PDO');
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sid = (int)($_POST['solicitud_id'] ?? 0);
    if ($sid <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'No se proporcionó solicitud_id'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $estado = trim((string)($_POST['estado'] ?? 'Cancelado'));
    if ($estado === '') {
        $estado = 'Cancelado';
    }

    // Detectar columnas reales de la tabla en producción
    $colsRaw = $pdo->query("SHOW COLUMNS FROM `solicitudes`")->fetchAll(PDO::FETCH_ASSOC);
    $cols = array_map(static fn($c) => $c['Field'] ?? '', $colsRaw);

    if (in_array('estado_validacion', $cols, true)) {
        $sql = "UPDATE `solicitudes`
                SET `estado_validacion` = :estado
                WHERE `id` = :sid";
        $campoUsado = 'estado_validacion';
    } elseif (in_array('estado', $cols, true)) {
        $sql = "UPDATE `solicitudes`
                SET `estado` = :estado
                WHERE `id` = :sid";
        $campoUsado = 'estado';
    } else {
        throw new RuntimeException('La tabla solicitudes no tiene columna estado ni estado_validacion');
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':estado' => $estado,
        ':sid'    => $sid
    ]);

    if ($stmt->rowCount() <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró la solicitud o no hubo cambios',
            'campo_usado' => $campoUsado
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Solicitud marcada como cancelada correctamente',
        'solicitud_id' => $sid,
        'campo_usado' => $campoUsado,
        'estado' => $estado
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cancelar: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}