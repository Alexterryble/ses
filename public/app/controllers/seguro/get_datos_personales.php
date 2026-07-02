<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db/conexion.php';

try {
  $solicitudId = 0;
  if (isset($_GET['solicitud_id'])) $solicitudId = (int)$_GET['solicitud_id'];
  if (!$solicitudId && isset($_POST['solicitud_id'])) $solicitudId = (int)$_POST['solicitud_id'];

  if ($solicitudId <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Falta o es inválido solicitud_id'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $sql = "
    SELECT
      dp.nombres,
      dp.apellido_paterno,
      dp.apellido_materno,
      dp.fecha_nacimiento,
      dp.pais_nacimiento,
      dp.estado_nacimiento,
      dp.estado_civil,
      dp.nacionalidad,
      dp.profesion,
      dp.rfc,
      dp.curp,
      dp.celular,
      dp.telefono,
      dp.correo,

      dp.direccion,
      dp.entre_calles,
      dp.colonia,
      dp.cp,
      dp.municipio,
      dp.estado,
      dp.pais,

      il.puesto  AS puesto_laboral,
      il.empresa AS empresa_trabajo
    FROM datos_personales dp
    LEFT JOIN info_laboral il
      ON il.solicitud_id = dp.solicitud_id
    WHERE dp.solicitud_id = :sid
    LIMIT 1
  ";


  $st = $pdo->prepare($sql);
  $st->execute([':sid' => $solicitudId]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'No se encontraron datos_personales para este solicitud_id'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ✅ Helpers
  $trim = static fn($v): string => trim((string)($v ?? ''));

  $clean = static function($v) use ($trim): string {
    $v = $trim($v);
    if ($v === '' || strtoupper($v) === 'N/A') return '';
    return $v;
  };

  // ✅ Nombre completo
  $nombreCompleto = trim(
    $clean($row['apellido_paterno']) . ' ' .
    $clean($row['apellido_materno']) . ' ' .
    $clean($row['nombres'])
  );

  // ✅ Lugar nacimiento
  $lugarNacimiento = trim(
    $clean($row['estado_nacimiento']) .
    ($clean($row['pais_nacimiento']) !== '' ? ', ' . $clean($row['pais_nacimiento']) : '')
  );

  // ✅ Lugar residencia (completo)
  $partes = [];
  if ($clean($row['direccion']) !== '')     $partes[] = $clean($row['direccion']);
  if ($clean($row['entre_calles']) !== '') $partes[] = 'Entre calles: ' . $clean($row['entre_calles']);
  if ($clean($row['colonia']) !== '')      $partes[] = 'Col. ' . $clean($row['colonia']);
  if ($clean($row['cp']) !== '')           $partes[] = 'CP ' . $clean($row['cp']);
  if ($clean($row['municipio']) !== '')    $partes[] = $clean($row['municipio']);
  if ($clean($row['estado']) !== '')       $partes[] = $clean($row['estado']);
  if ($clean($row['pais']) !== '')         $partes[] = $clean($row['pais']);

  $lugarResidencia = implode(', ', $partes);

  // ✅ Ocupación desde info_laboral, si viene vacío usa dp.profesion
 $ocup = $clean($row['puesto_laboral']);


$calle     = $clean($row['direccion']);
$numero    = isset($row['numero']) ? $clean($row['numero']) : '';
$cp        = $clean($row['cp']);
$colonia   = $clean($row['colonia']);
$municipio = $clean($row['municipio']);

$ciudadEstado = trim(
  $municipio .
  ($clean($row['estado']) !== '' ? ', ' . $clean($row['estado']) : '')
);
$empresaTrabajo = $clean($row['empresa_trabajo']);


$data = [
  'nombre'           => $nombreCompleto,
  'fecha_nacimiento' => $clean($row['fecha_nacimiento']),
  'lugar_nacimiento' => $lugarNacimiento,
  'lugar_residencia' => $lugarResidencia,
  'estado_civil'     => $clean($row['estado_civil']),
  'nacionalidad'     => $clean($row['nacionalidad']),
  'ocupacion'        => $ocup,
  'rfc'              => $clean($row['rfc']),
  'curp'             => $clean($row['curp']),
  'telefono_celular' => $clean($row['celular']),
  'otro_telefono'    => $clean($row['telefono']),
  'correo'           => $clean($row['correo']),

  'calle'            => $calle,
  'numero'           => $numero,
  'cp'               => $cp,
  'colonia'          => $colonia,
  'municipio'        => $municipio,
  'ciudad_estado'    => $ciudadEstado,
  'empresa_trabajo' => $empresaTrabajo,

];


  echo json_encode(['ok'=>true,'solicitud_id'=>$solicitudId,'data'=>$data], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Error del servidor','details'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
