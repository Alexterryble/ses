<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

date_default_timezone_set('America/Mexico_City');

try {
    require_once __DIR__ . '/../../db/conexion.php'; // ajusta la ruta si tu conexion.php está en otro lado

    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new RuntimeException('Conexión PDO no disponible');
    }

    // ------------------ validar solicitud_id ------------------
    $sidRaw = $_GET['solicitud_id'] ?? $_POST['solicitud_id'] ?? null;
    if ($sidRaw === null || !ctype_digit((string)$sidRaw)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'solicitud_id requerido'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $sid = (int)$sidRaw;

    // firmante opcional (por defecto tomamos el prestatario si lo mandas)
    $firmante = $_GET['firmante'] ?? $_POST['firmante'] ?? null;
    $params   = [':sid' => $sid];

    if ($firmante !== null && $firmante !== '') {
        $sql = "SELECT signed_at, firmante
                  FROM firmas_contrato
                 WHERE solicitud_id = :sid
                   AND firmante = :firmante
              ORDER BY signed_at DESC
                 LIMIT 1";
        $params[':firmante'] = $firmante;
    } else {
        $sql = "SELECT signed_at, firmante
                  FROM firmas_contrato
                 WHERE solicitud_id = :sid
              ORDER BY signed_at DESC
                 LIMIT 1";
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row['signed_at'])) {
        echo json_encode([
            'ok'       => true,
            'fecha'    => null,
            'hora'     => null,
            'signed_at'=> null,
            'existed'  => false,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // signed_at viene tipo "2025-10-31 23:06:45"
    $dt    = new DateTime($row['signed_at'], new DateTimeZone('America/Mexico_City'));
    $fecha = $dt->format('Y-m-d');
    $hora  = $dt->format('H:i:s');

    echo json_encode([
        'ok'        => true,
        'fecha'     => $fecha,           // YYYY-MM-DD
        'hora'      => $hora,            // HH:MM:SS
        'signed_at' => $row['signed_at'],// original
        'firmante'  => $row['firmante'],
        'existed'   => true
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
