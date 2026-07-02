<?php
// /public/get_solicitudes.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    // Sesión
    require_once __DIR__ . '/app/controllers/auth/session_boot.php';

    if (empty($_SESSION['asesor']['id'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    // DB
    require_once __DIR__ . '/app/db/conexion.php';

    /** @var PDO|null $pdo */
    $db = (isset($pdo) && $pdo instanceof PDO)
        ? $pdo
        : ((isset($conn) && $conn instanceof PDO) ? $conn : null);

    if (!$db) {
        throw new RuntimeException('Conexión PDO no disponible');
    }
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Datos de sesión
    $uid       = (int)($_SESSION['asesor']['id'] ?? 0);
    $isAdmin   = !empty($_SESSION['asesor']['is_admin']);
    $asesorNom = (string)($_SESSION['asesor']['nombre'] ?? $_SESSION['user_name'] ?? '');

    /**
     * Helper: comprobar si existe una columna en solicitudes
     */
    $hasColumn = function (PDO $db, string $column): bool {
        $sql = "SELECT 1 
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'solicitudes'
                  AND COLUMN_NAME  = :col
                LIMIT 1";
        $st = $db->prepare($sql);
        $st->execute([':col' => $column]);
        return (bool)$st->fetchColumn();
    };

    // ¿Existe columna s.asesor_id?
    $hasAsesorId = $hasColumn($db, 'asesor_id');

    // Armar dinámicamente las columnas del SELECT
    $selectCols = [
        's.id AS folio',
        'dp.nombres',
        'dp.apellido_paterno',
        'dp.apellido_materno',
    ];

        // Estas columnas son opcionales; se van usando en el dashboard
        if ($hasColumn($db, 'estado')) {
            $selectCols[] = 's.estado';
        }
        if ($hasColumn($db, 'estado_validacion')) {
            $selectCols[] = 's.estado_validacion';
        }
        if ($hasColumn($db, 'fecha_registro')) {
            $selectCols[] = 's.fecha_registro';
        }
        if ($hasColumn($db, 'fecha_solicitud')) {
            $selectCols[] = 's.fecha_solicitud';
        }
        if ($hasColumn($db, 'created_at')) {
            $selectCols[] = 's.created_at';
        }
        if ($hasColumn($db, 'fecha_creacion')) {
            $selectCols[] = 's.fecha_creacion';
        }

    $cols = implode(",\n       ", $selectCols);

    // Base del FROM + JOIN
    $fromJoin = "
      FROM solicitudes s
      LEFT JOIN datos_personales dp ON dp.solicitud_id = s.id
    ";

    // Filtro para nombres válidos
    $whereBase = "dp.nombres IS NOT NULL AND dp.nombres <> ''";

    // Construir SQL según admin / asesor
    if ($isAdmin) {
        $sql = "
          SELECT
            $cols
          $fromJoin
          WHERE $whereBase
          ORDER BY s.id DESC
          LIMIT 500
        ";
        $st = $db->query($sql);
    } else {
        if ($hasAsesorId) {
            $sql = "
              SELECT
                $cols
              $fromJoin
              WHERE $whereBase
                AND s.asesor_id = :uid
              ORDER BY s.id DESC
              LIMIT 500
            ";
            $st = $db->prepare($sql);
            $st->execute([':uid' => $uid]);
        } else {
            // Fallback si aún no existe s.asesor_id en producción
            $sql = "
              SELECT
                $cols
              $fromJoin
              WHERE $whereBase
                AND s.atendido_por = :nom
              ORDER BY s.id DESC
              LIMIT 500
            ";
            $st = $db->prepare($sql);
            $st->execute([':nom' => $asesorNom]);
        }
    }

    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
