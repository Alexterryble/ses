<?php
// /public/app/controllers/resumen/get_solicitudes.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(bool $ok, array $extra = [], int $code = 200): void {
  http_response_code($code);
  echo json_encode(array_merge(['ok'=>$ok], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  // ✅ Sesión (este archivo está en /app/controllers/resumen)
  require_once __DIR__ . '/../auth/session_boot.php';

  // ✅ asesorId con fallbacks (igual que en tus módulos)
  $uid = (int)(
    $_SESSION['asesor']['id_asesor']
    ?? $_SESSION['asesor']['id']
    ?? $_SESSION['asesor_id']
    ?? $_SESSION['user_id']
    ?? 0
  );

  if ($uid <= 0) out(false, ['error'=>'No autenticado'], 401);

  // ✅ Nombre asesor (fallback por si hace falta)
  $asesorNom = trim((string)(
    ($_SESSION['asesor']['nombre'] ?? '') . ' ' .
    ($_SESSION['asesor']['apellido_paterno'] ?? '') . ' ' .
    ($_SESSION['asesor']['apellido_materno'] ?? '')
  ));
  if ($asesorNom === '') $asesorNom = (string)($_SESSION['user_name'] ?? '');

  $isAdmin = !empty($_SESSION['asesor']['is_admin']);

  // ✅ DB (este archivo está en /app/controllers/resumen => subir 2 niveles a /app/db)
  require_once __DIR__ . '/../../db/conexion.php';

  /** @var PDO|null $pdo */
  $db = (isset($pdo) && $pdo instanceof PDO)
    ? $pdo
    : ((isset($conn) && $conn instanceof PDO) ? $conn : null);

  if (!$db) throw new RuntimeException('Conexión PDO no disponible');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // ✅ helper: existe columna
  $hasColumn = function(PDO $db, string $table, string $col): bool {
    $sql = "SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = :t
              AND COLUMN_NAME  = :c
            LIMIT 1";
    $st = $db->prepare($sql);
    $st->execute([':t'=>$table, ':c'=>$col]);
    return (bool)$st->fetchColumn();
  };

  $hasAsesorId = $hasColumn($db, 'solicitudes', 'asesor_id');

  // ✅ SELECT base
  $selectCols = [
    's.id AS sid',
    's.folio AS folio',
    'dp.nombres',
    'dp.apellido_paterno',
    'dp.apellido_materno',
    // ✅ asesor REAL desde tabla "asesor" (id = solicitudes.asesor_id)
    "TRIM(CONCAT_WS(' ', a.nombre, a.apellido_paterno, a.apellido_materno)) AS asesor",
  ];

  // opcionales (solo si existen en solicitudes)
  if ($hasColumn($db, 'solicitudes', 'monto')) $selectCols[] = 's.monto';
  if ($hasColumn($db, 'solicitudes', 'plazo')) $selectCols[] = 's.plazo';

  // fecha (preferir fecha_registro si existe)
  if ($hasColumn($db, 'solicitudes', 'fecha_registro')) $selectCols[] = 's.fecha_registro';
  elseif ($hasColumn($db, 'solicitudes', 'fecha_solicitud')) $selectCols[] = 's.fecha_solicitud';
  elseif ($hasColumn($db, 'solicitudes', 'created_at')) $selectCols[] = 's.created_at';
  elseif ($hasColumn($db, 'solicitudes', 'fecha_creacion')) $selectCols[] = 's.fecha_creacion';

  // estado (preferir estado_validacion)
  if ($hasColumn($db, 'solicitudes', 'estado_validacion')) $selectCols[] = 's.estado_validacion';
  if ($hasColumn($db, 'solicitudes', 'estado')) $selectCols[] = 's.estado';

  $cols = implode(",\n       ", $selectCols);

  // ✅ FROM + JOINS
  // OJO: tu tabla se ve como "asesor" con columnas: id, nombre, apellido_paterno, apellido_materno
  $fromJoin = "
    FROM solicitudes s
    LEFT JOIN datos_personales dp ON dp.solicitud_id = s.id
    LEFT JOIN asesores a ON a.id = s.asesor_id

  ";

  // ✅ filtro nombres válidos
  $whereBase = "dp.nombres IS NOT NULL AND dp.nombres <> ''";

  // ✅ SQL por admin / asesor
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
      // fallback ultra (si no existiera asesor_id en alguna BD)
      $sql = "
        SELECT
          $cols
        $fromJoin
        WHERE $whereBase
        ORDER BY s.id DESC
        LIMIT 500
      ";
      $st = $db->query($sql);
    }
  }

  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // ✅ fallback: si por alguna razón el JOIN no trae asesor, usa atendido_por o el de sesión
  $hasAtendido = $hasColumn($db, 'solicitudes', 'atendido_por');
  foreach ($rows as &$r) {
    $r['asesor'] = trim((string)($r['asesor'] ?? ''));
    if ($r['asesor'] === '' && $hasAtendido) {
      // intentar extraer atendido_por si existe (sin reconsultar: solo si ya venía en SELECT no aplica)
      // si quieres traerlo siempre, agrega "s.atendido_por AS atendido_por" al SELECT.
      $r['asesor'] = $asesorNom !== '' ? $asesorNom : '—';
    }
    if ($r['asesor'] === '') $r['asesor'] = $asesorNom !== '' ? $asesorNom : '—';
  }
  unset($r);

  out(true, ['data'=>$rows]);

} catch (Throwable $e) {
  out(false, ['error'=>$e->getMessage()], 500);
}
