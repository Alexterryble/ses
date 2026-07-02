<?php
// public/app/controllers/invercion/eliminar_inversion.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../../db/conexion.php';

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('PDO no disponible');
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $asesorId = (int)($_SESSION['asesor_id'] ?? $_SESSION['user_id'] ?? 0);
    if ($asesorId <= 0) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    // Leer ID (por GET o JSON/POST)
    $idInversion = 0;
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $idInversion = (int)($_GET['id'] ?? 0);
    } else {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        $isJSON = stripos($ct, 'application/json') !== false;
        $in = $isJSON
            ? (json_decode(file_get_contents('php://input'), true) ?? [])
            : $_POST;
        $idInversion = (int)($in['id'] ?? $in['inversion_id'] ?? 0);
    }

    if ($idInversion <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ID de inversión requerido']);
        exit;
    }

    // Solo marcamos como cancelado (eliminación lógica)
    $sql = "UPDATE inversion
            SET estado = 'cancelado'
            WHERE id = :id
              AND asesor_id = :asesor_id
              AND estado <> 'cancelado'
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':id'        => $idInversion,
        ':asesor_id' => $asesorId,
    ]);

    if ($st->rowCount() === 0) {
        echo json_encode([
            'ok'    => false,
            'error' => 'No se pudo cancelar (ya cancelado o no pertenece al asesor)'
        ]);
        exit;
    }

    echo json_encode([
        'ok'         => true,
        'inversion_id' => $idInversion
    ]);
    exit;

} catch (Throwable $e) {
    error_log('Error eliminar_inversion: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor']);
}
