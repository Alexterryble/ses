<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

date_default_timezone_set('America/Mexico_City');

try {
    require_once __DIR__ . '/../../db/conexion.php'; // -> $conn (PDO)

    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new RuntimeException('Conexión PDO no disponible');
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $sidRaw = $_GET['solicitud_id'] ?? $_POST['solicitud_id'] ?? null;
    if ($sidRaw === null || !ctype_digit((string)$sidRaw)) {
      http_response_code(400);
      echo json_encode(['ok'=>false,'message'=>'solicitud_id requerido']);
      exit;
    }
    $sid = (int)$sidRaw;

    if ($method === 'GET') {
        $stmt = $conn->prepare("SELECT fecha_firma FROM pagares WHERE solicitud_id = :sid LIMIT 1");
        $stmt->execute([':sid'=>$sid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['ok'=>true,'fecha'=>$row['fecha_firma'] ?? null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        // Si mandas una fecha específica úsala; si no, hoy (en MX).
        $fecha = $_POST['fecha'] ?? null;
        if ($fecha) {
            // Sanitiza a YYYY-MM-DD
            $fecha = substr((string)$fecha, 0, 10);
        } else {
            $hoy = new DateTime('now', new DateTimeZone('America/Mexico_City'));
            $fecha = $hoy->format('Y-m-d');
        }

        // Inserta sólo si NO existe (respeta la UNIQUE).
        $conn->beginTransaction();

        // ¿Ya existe?
        $q = $conn->prepare("SELECT fecha_firma FROM pagares WHERE solicitud_id = :sid LIMIT 1 FOR UPDATE");
        $q->execute([':sid'=>$sid]);
        $row = $q->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $conn->commit();
            echo json_encode(['ok'=>true,'fecha'=>$row['fecha_firma'], 'existed'=>true], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ins = $conn->prepare("INSERT INTO pagares (solicitud_id, fecha_firma) VALUES (:sid, :fecha)");
        $ins->execute([':sid'=>$sid, ':fecha'=>$fecha]);
        $conn->commit();

        echo json_encode(['ok'=>true,'fecha'=>$fecha, 'existed'=>false], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['ok'=>false,'message'=>'Método no permitido']);
} catch (Throwable $e) {
    if ($conn && $conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
}
