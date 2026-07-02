<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$solicitudId = isset($_GET['solicitud_id']) ? (int)$_GET['solicitud_id'] : 0;
if ($solicitudId <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'solicitud_id inválido'], JSON_UNESCAPED_UNICODE);
  exit;
}

// opcional: filtrar por tipo (Familiar / Personal)
$tipo = isset($_GET['tipo']) ? trim((string)$_GET['tipo']) : '';
$tipo = $tipo !== '' ? $tipo : null;

require_once __DIR__ . '/../../db/conexion.php'; // -> $conn (PDO)

try {
  if (!isset($conn) || !($conn instanceof PDO)) {
    throw new RuntimeException('Conexión PDO no disponible en $conn. Revisa conexion.php');
  }
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // =========================
  // 1) Datos solicitud + nombres
  // =========================
  $sql = "
    SELECT
      s.folio,

      dp.nombres AS sol_nombres,
      dp.apellido_paterno AS sol_apellido_paterno,
      dp.apellido_materno AS sol_apellido_materno,

      c.nombre AS co_nombre,
      c.apellido_paterno AS co_apellido_paterno,
      c.apellido_materno AS co_apellido_materno,
      c.telefono AS co_telefono,
      c.celular  AS co_celular,
      c.telefono AS co_telefono

    FROM solicitudes s
    LEFT JOIN datos_personales dp ON dp.solicitud_id = s.id
    LEFT JOIN codeudores c        ON c.solicitud_id = s.id
    WHERE s.id = :id
    ORDER BY dp.id DESC, c.id DESC
    LIMIT 1
  ";

  $stmt = $conn->prepare($sql);
  $stmt->bindValue(':id', $solicitudId, PDO::PARAM_INT);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Solicitud no encontrada'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $solNombre = trim(
    ($row['sol_nombres'] ?? '') . ' ' .
    ($row['sol_apellido_paterno'] ?? '') . ' ' .
    ($row['sol_apellido_materno'] ?? '')
  );

  $coNombre = trim(
    ($row['co_nombre'] ?? '') . ' ' .
    ($row['co_apellido_paterno'] ?? '') . ' ' .
    ($row['co_apellido_materno'] ?? '')
  );

  // ✅ Tel del codeudor (prioriza telefono, luego celular, luego telefono_celular)
  $coTel = (string)($row['co_telefono'] ?? '');
  if ($coTel === '') $coTel = (string)($row['co_celular'] ?? '');
  if ($coTel === '') $coTel = (string)($row['co_telefono_celular'] ?? '');

  // =========================
  // 2) Referencias #1 y #2
  // =========================
  $sqlRef = "
    SELECT
      id, solicitud_id, tipo, numero,
      nombre_completo, direccion, telefono, celular, email, parentesco
    FROM referencias_solicitante
    WHERE solicitud_id = :id
      AND numero IN (1,2)
  ";

  if ($tipo !== null) {
    $sqlRef .= " AND tipo = :tipo ";
  }

  $sqlRef .= "
    ORDER BY
      CASE tipo
        WHEN 'Familiar' THEN 1
        WHEN 'Personal' THEN 2
        ELSE 9
      END,
      numero ASC
  ";

  $st2 = $conn->prepare($sqlRef);
  $st2->bindValue(':id', $solicitudId, PDO::PARAM_INT);
  if ($tipo !== null) $st2->bindValue(':tipo', $tipo, PDO::PARAM_STR);
  $st2->execute();
  $refs = $st2->fetchAll(PDO::FETCH_ASSOC);

  $refOut = [
    'Familiar' => [1 => null, 2 => null],
    'Personal' => [1 => null, 2 => null],
    'Otras'    => []
  ];

  foreach ($refs as $r) {
    $t = (string)($r['tipo'] ?? 'Otras');
    $n = (int)($r['numero'] ?? 0);
    if (($t === 'Familiar' || $t === 'Personal') && ($n === 1 || $n === 2)) {
      $refOut[$t][$n] = $r;
    } else {
      $refOut['Otras'][] = $r;
    }
  }

  echo json_encode([
    'ok' => true,
    'solicitud_id' => $solicitudId,
    'folio' => $row['folio'] ?? '',
    'solicitante' => [
      'nombres' => $row['sol_nombres'] ?? '',
      'apellido_paterno' => $row['sol_apellido_paterno'] ?? '',
      'apellido_materno' => $row['sol_apellido_materno'] ?? '',
      'nombre_completo' => $solNombre
    ],
    'codeudor' => [
      'nombre' => $row['co_nombre'] ?? '',
      'apellido_paterno' => $row['co_apellido_paterno'] ?? '',
      'apellido_materno' => $row['co_apellido_materno'] ?? '',
      'nombre_completo' => $coNombre,
      'telefono' => $coTel
    ],
    'referencias' => $refOut
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => 'Error al obtener datos',
    'detail' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}

