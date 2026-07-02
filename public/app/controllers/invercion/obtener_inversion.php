<?php
// public/app/controllers/inversion/obtener_inversion.php
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

    // ✅ Asesor ID REAL (fallbacks)
    $asesorId = (int) (
        $_SESSION['asesor']['id_asesor']
        ?? $_SESSION['asesor']['id']
        ?? $_SESSION['asesor_id']
        ?? $_SESSION['user_id']
        ?? 0
    );

    if ($asesorId <= 0) {
        http_response_code(401);
        echo json_encode([
            'ok' => false,
            'error' => 'No autenticado (asesorId vacío en sesión)'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==== Obtener ID por GET o JSON ====
    $idInversion = 0;

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
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
        echo json_encode(['ok' => false, 'error' => 'ID de inversión requerido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ✅ Helper: comprobar si existe una columna
    $hasColumn = function (PDO $db, string $table, string $column): bool {
        $sql = "SELECT 1
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :t
                  AND COLUMN_NAME = :c
                LIMIT 1";
        $st = $db->prepare($sql);
        $st->execute([':t' => $table, ':c' => $column]);
        return (bool)$st->fetchColumn();
    };

    // ✅ Columnas base
    $cols = [
        "id",
        "asesor_id",
        "nombre",
        "ap_paterno",
        "ap_materno",
        "rfc",
        "codigo_postal",
        "direccion",
        "telefono",
        "correo",
        "monto",
        "plazo_anios",
        "fecha_solicitud",
        "fecha_devolucion",
        "folio",
        "estado"
    ];

    // ✅ Opcionales (si existen en BD)
    if ($hasColumn($pdo, 'inversion', 'forma_pago')) {
        $cols[] = "forma_pago";
    }

    if ($hasColumn($pdo, 'inversion', 'beneficiario_nombre')) {
        $cols[] = "beneficiario_nombre";
    }
    if ($hasColumn($pdo, 'inversion', 'beneficiario_curp')) {
        $cols[] = "beneficiario_curp";
    }
    if ($hasColumn($pdo, 'inversion', 'beneficiario_telefono')) {
        $cols[] = "beneficiario_telefono";
    }
    if ($hasColumn($pdo, 'inversion', 'beneficiario_parentesco')) {
        $cols[] = "beneficiario_parentesco";
    }

    /* =========================================================
       ✅ PERMISO:
       - Dueño (asesor_id = sesión) VE lo suyo
       - Asesor ID 7 VE TODO
       ========================================================= */

    $sql = "SELECT " . implode(",\n              ", $cols) . "
            FROM inversion
            WHERE id = :id";

    $params = [
        ':id' => $idInversion,
    ];

    // ✅ Si NO es el asesor 7, entonces sí filtramos por dueño
    if ($asesorId !== 7) {
        $sql .= " AND asesor_id = :asesor_id";
        $params[':asesor_id'] = $asesorId;
    }

    $sql .= " LIMIT 1";

    $st = $pdo->prepare($sql);
    $st->execute($params);

    $inv = $st->fetch(PDO::FETCH_ASSOC);

    if (!$inv) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'error' => 'Inversión no encontrada (no coincide id o asesor_id)'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'ok'        => true,
        'inversion' => $inv,
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('Error obtener_inversion: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error del servidor',
        // ✅ si no quieres mostrar detalle en producción, bórralo:
        'detail' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
