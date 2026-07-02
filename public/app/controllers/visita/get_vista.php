<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
  exit;
}

$id    = trim((string)($_GET['id'] ?? ''));
$folio = trim((string)($_GET['folio'] ?? ''));

if ($id === '' && $folio === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Falta parámetro: id o folio']);
  exit;
}

require_once __DIR__ . '/../../db/conexion.php'; // -> $conn (PDO)

try {

  if ($id !== '') {
    $sql = "
SELECT
  s.id,
  s.folio,

  -- DATOS PERSONALES
  dp.nombres,
  dp.apellido_paterno,
  dp.apellido_materno,
  dp.estado_civil,
  dp.dependientes,
  dp.telefono,
  dp.celular,
  dp.direccion,
  dp.colonia,
  dp.cp,
  dp.entre_calles,
  dp.municipio,
  dp.estado,
  dp.tiempo_domicilio,

  -- INFO ADICIONAL
  ia.tipo_vivienda,
  ia.saldo_hipoteca,
  ia.nombre_propietario,
  ia.parentesco_propietario

FROM solicitudes s
LEFT JOIN datos_personales dp
  ON dp.solicitud_id = s.id
LEFT JOIN info_adicional ia
  ON ia.solicitud_id = s.id
WHERE s.id = :id
LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);

  } else {
    $sql = "
      SELECT
        s.id,
        s.folio,
        dp.nombres,
        dp.apellido_paterno,
        dp.apellido_materno,
        dp.estado_civil,
        dp.dependientes,
        dp.telefono,
        dp.celular
      FROM solicitudes s
      LEFT JOIN datos_personales dp
        ON dp.solicitud_id = s.id
      WHERE s.folio = :folio
      LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':folio', $folio, PDO::PARAM_STR);
  }

  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Solicitud no encontrada']);
    exit;
  }

  // Construir nombre completo limpio
  $nombreCompleto = trim(
    ($row['nombres'] ?? '') . ' ' .
    ($row['apellido_paterno'] ?? '') . ' ' .
    ($row['apellido_materno'] ?? '')
  );

$direccion = trim((string)($row['direccion'] ?? ''));
$colonia   = trim((string)($row['colonia'] ?? ''));
$cp        = trim((string)($row['cp'] ?? ''));

// ✅ Orden: calle/no. + colonia + C.P.
$domicilioCompleto = trim($direccion);
if ($colonia !== '') $domicilioCompleto .= ($domicilioCompleto ? ', ' : '') . $colonia;
if ($cp !== '')      $domicilioCompleto .= ($domicilioCompleto ? ', ' : '') . 'C.P. ' . $cp;

$entreCalles = trim((string)($row['entre_calles'] ?? ''));



  echo json_encode([
    'ok' => true,
    'data' => [
      'id' => (int)$row['id'],
      'folio' => $row['folio'] ?? '',
      'nombre_solicitante' => $nombreCompleto,
      'estado_civil' => $row['estado_civil'] ?? '',
        'dependientes' => (int)($row['dependientes'] ?? 0),
        'telefono' => $row['telefono'] ?? '',
        'celular' => $row['celular'] ?? '',
        'domicilio_completo' => trim(
            ($row['direccion'] ?? '') . ' ' .
            ($row['colonia'] ?? '') . ' C.P. ' .
            ($row['cp'] ?? '')
        ),
        'entre_calles' => $row['entre_calles'] ?? '',
        'municipio' => $row['municipio'] ?? '',
        'estado' => $row['estado'] ?? '',
        'tiempo_domicilio' => $row['tiempo_domicilio'] ?? '',
        'tipo_vivienda' => $row['tipo_vivienda'] ?? '',
        'saldo_hipoteca' => $row['saldo_hipoteca'] ?? '',
        'nombre_propietario' => $row['nombre_propietario'] ?? '',
        'parentesco_propietario' => $row['parentesco_propietario'] ?? ''
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => 'Error interno',
    'detail' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
