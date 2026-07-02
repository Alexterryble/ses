<?php
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../db/conexion.php');

// ---- helpers de saneamiento ----
function decimal_normalizado($raw) {
  if (!isset($raw) || $raw === '') return '';
  // Quita símbolos ($, espacios) y conserva último separador como decimal
  $s = preg_replace('/[^\d.,-]/', '', (string)$raw);
  $lastComma = strrpos($s, ','); $lastDot = strrpos($s, '.');
  $lastSep = max($lastComma === false ? -1 : $lastComma, $lastDot === false ? -1 : $lastDot);
  if ($lastSep >= 0) {
    $ent = preg_replace('/[.,]/', '', substr($s, 0, $lastSep));
    $dec = preg_replace('/[.,]/', '', substr($s, $lastSep + 1));
    $s = $ent . '.' . $dec;
  } else {
    $s = preg_replace('/[.,]/', '', $s);
  }
  return $s;
}
function es_decimal($s) {
  return preg_match('/^\d+(\.\d{1,2})?$/', (string)$s);
}

// --- Validar solicitud_id ---
$solicitud_id = $_POST['solicitud_id'] ?? null;
if (!$solicitud_id) {
  echo json_encode(['status'=>'error','user_msg'=>'Falta el ID de la solicitud.']);
  exit;
}

// --- Captura / normaliza campos ---
$telefono_trabajo = preg_replace('/\D+/', '', $_POST['telefono_trabajo'] ?? '');
$sueldo           = decimal_normalizado($_POST['sueldo'] ?? '');
$otros_ingresos   = decimal_normalizado($_POST['otros_ingresos'] ?? '');

// --- Validaciones de negocio (amigables) ---
$errores = [];
if ($sueldo === '' || !es_decimal($sueldo)) {
  echo json_encode([
    'status'=>'error',
    'user_msg'=>'El campo Sueldo debe ser un número válido (puede ser 0). Ej.: 0, 12500, 12500.50.'
  ]);
  exit;
}
if ($otros_ingresos !== '' && !es_decimal($otros_ingresos)) {
  $errores[] = 'Otros ingresos debe ser un número con hasta 2 decimales.';
}
if ($telefono_trabajo && (strlen($telefono_trabajo) < 7 || strlen($telefono_trabajo) > 15)) {
  $errores[] = 'Teléfono de trabajo debe contener de 7 a 15 dígitos.';
}
if ($errores) {
  echo json_encode(['status'=>'error','user_msg'=>implode(' ', $errores)]);
  exit;
}

// Si otros_ingresos viene vacío, guarda 0
if ($otros_ingresos === '') $otros_ingresos = '0';

$datos = [
  ':solicitud_id'       => $solicitud_id,
  ':puesto'             => $_POST['puesto'] ?? '',
  ':empresa'            => $_POST['empresa'] ?? '',
  ':giro_empresa'       => $_POST['giro_empresa'] ?? '',
  ':direccion_trabajo'  => $_POST['direccion_trabajo'] ?? '',
  ':calles_trabajo'     => $_POST['calles_trabajo'] ?? '',
  ':referencia_trabajo' => $_POST['ref_empresa_trabajo_input'] ?? '',
  ':colonia_trabajo'    => $_POST['colonia_trabajo'] ?? '',
  ':municipio_trabajo'  => $_POST['municipio_trabajo'] ?? '',
  ':estado_trabajo'     => $_POST['estado_trabajo'] ?? '',
  ':pais_trabajo'       => $_POST['pais_trabajo'] ?? '',
  ':tiempo_empleo'      => $_POST['tiempo_empleo'] ?? '',
  ':telefono_trabajo'   => $telefono_trabajo,
  ':horario_trabajo'    => $_POST['horario_trabajo'] ?? '',
  ':sueldo'             => $sueldo,          // ya normalizado "#####.##"
  ':forma_pago'         => $_POST['forma_pago'] ?? '',
  ':otros_ingresos'     => $otros_ingresos,  // "0" si vacío
  ':fuente_ingresos'    => $_POST['fuente_ingresos'] ?? '',
  ':ubicacion_negocio'  => $_POST['ubicacion_negocio'] ?? ''
];

try {
  // Verificar si existe
  $check = $conn->prepare("SELECT id FROM info_laboral WHERE solicitud_id = ?");
  $check->execute([$solicitud_id]);

  if ($check->rowCount() > 0) {
    $stmt = $conn->prepare("
      UPDATE info_laboral SET
        puesto=:puesto, empresa=:empresa, giro_empresa=:giro_empresa,
        direccion_trabajo=:direccion_trabajo, calles_trabajo=:calles_trabajo,
        referencia_trabajo=:referencia_trabajo, colonia_trabajo=:colonia_trabajo,
        municipio_trabajo=:municipio_trabajo, estado_trabajo=:estado_trabajo,
        pais_trabajo=:pais_trabajo, tiempo_empleo=:tiempo_empleo,
        telefono_trabajo=:telefono_trabajo, horario_trabajo=:horario_trabajo,
        sueldo=:sueldo, forma_pago=:forma_pago, otros_ingresos=:otros_ingresos,
        fuente_ingresos=:fuente_ingresos, ubicacion_negocio=:ubicacion_negocio
      WHERE solicitud_id=:solicitud_id
    ");
    $stmt->execute($datos);
    echo json_encode(['status'=>'ok','message'=>'Información laboral actualizada correctamente.']);
  } else {
    $stmt = $conn->prepare("
      INSERT INTO info_laboral (
        solicitud_id, puesto, empresa, giro_empresa, direccion_trabajo, calles_trabajo,
        referencia_trabajo, colonia_trabajo, municipio_trabajo, estado_trabajo, pais_trabajo,
        tiempo_empleo, telefono_trabajo, horario_trabajo, sueldo, forma_pago,
        otros_ingresos, fuente_ingresos, ubicacion_negocio
      ) VALUES (
        :solicitud_id, :puesto, :empresa, :giro_empresa, :direccion_trabajo, :calles_trabajo,
        :referencia_trabajo, :colonia_trabajo, :municipio_trabajo, :estado_trabajo, :pais_trabajo,
        :tiempo_empleo, :telefono_trabajo, :horario_trabajo, :sueldo, :forma_pago,
        :otros_ingresos, :fuente_ingresos, :ubicacion_negocio
      )
    ");
    $stmt->execute($datos);
    echo json_encode(['status'=>'ok','message'=>'Información laboral guardada correctamente.']);
  }
} catch (PDOException $e) {
  $driver = $e->errorInfo[1] ?? null; // 1366 = Incorrect decimal value
  if ($driver === 1366) {
    echo json_encode([
      'status'=>'error',
      'user_msg'=>'Hay valores numéricos inválidos. Usa solo números y hasta 2 decimales (ej. 12500.50).'
    ]);
  } else {
    echo json_encode(['status'=>'error','user_msg'=>'No pudimos guardar la información. Intenta de nuevo.']);
  }
}
