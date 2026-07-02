<?php
require_once(__DIR__ . '/../db/conexion.php');
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
  exit;
}

/* === Normaliza fecha a YYYY-MM-DD (acepta DD/MM/YYYY, DD-MM-YYYY, etc.) === */
function normaliza_fecha($s) {
  $s = trim((string)$s);
  if ($s === '') return '';
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s; // ISO
  if (preg_match('/^(\d{2})[^\d]+(\d{2})[^\d]+(\d{4})$/', $s, $m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
  if (preg_match('/^(\d{2})(\d{2})(\d{4})$/', preg_replace('/\D/','',$s), $m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
  return '';
}

try {
  $solicitud_id      = $_POST["solicitud_id"] ?? null;
  $desempenia        = $_POST["desempenia_funcion_publica"] ?? '';
  $relacion          = $_POST["relacion_funcion_publica"] ?? '';
  $firmaAutorizacion = $_POST["firma_autorizacion"] ?? '';
  $firmaFormulario   = $_POST["firma_formulario"] ?? '';
  $lugar             = trim($_POST["lugar"] ?? '');
  $fecha             = normaliza_fecha($_POST["fecha"] ?? ''); // <-- usar normalización

  if (!$solicitud_id || !is_numeric($solicitud_id)) {
    throw new Exception("ID de solicitud inválido.");
  }

  // Verificar que exista el ID en solicitudes
  $check = $conn->prepare("SELECT COUNT(*) FROM solicitudes WHERE id = ?");
  $check->execute([$solicitud_id]);
  if ($check->fetchColumn() == 0) {
    throw new Exception("El ID $solicitud_id no existe en la tabla solicitudes.");
  }

  // Normalizar firmas (solo si vienen en base64 data URL)
  $firmaBinAutorizacion = null;
  if (strpos($firmaAutorizacion, 'data:image') === 0) {
    $firmaBinAutorizacion = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $firmaAutorizacion));
  }

  $firmaBinFormulario = null;
  if (strpos($firmaFormulario, 'data:image') === 0) {
    $firmaBinFormulario = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $firmaFormulario));
  }

  // ¿Ya existe registro?
  $checkFirma = $conn->prepare("SELECT id FROM funcionarios_firma WHERE solicitud_id = ?");
  $checkFirma->execute([$solicitud_id]);
  $existe = $checkFirma->fetchColumn();

  if ($existe) {
    // ---- UPDATE dinámico (no pisa firmas si no envías nuevas) ----
    $sets = [
      "desempenia_funcion_publica = :desempenia",
      "relacion_funcion_publica   = :relacion",
      "lugar                      = :lugar"
    ];

    if ($fecha !== '') {
      $sets[] = "fecha = :fecha";
    }
    if (!is_null($firmaBinAutorizacion)) {
      $sets[] = "firma = :firma";
    }
    if (!is_null($firmaBinFormulario)) {
      $sets[] = "firma_formulario = :firma_formulario";
    }

    $sql = "UPDATE funcionarios_firma SET " . implode(", ", $sets) . " WHERE solicitud_id = :solicitud_id";
    $stmt = $conn->prepare($sql);

    $stmt->bindValue(':desempenia', $desempenia);
    $stmt->bindValue(':relacion',   $relacion);
    $stmt->bindValue(':lugar',      $lugar);
    if ($fecha !== '') {
      $stmt->bindValue(':fecha', $fecha); // YYYY-MM-DD ya normalizado
    }
    if (!is_null($firmaBinAutorizacion)) {
      $stmt->bindValue(':firma', $firmaBinAutorizacion, PDO::PARAM_LOB);
    }
    if (!is_null($firmaBinFormulario)) {
      $stmt->bindValue(':firma_formulario', $firmaBinFormulario, PDO::PARAM_LOB);
    }
    $stmt->bindValue(':solicitud_id', $solicitud_id, PDO::PARAM_INT);

    $stmt->execute();
    echo json_encode(['status' => 'ok', 'message' => 'Registro actualizado correctamente.']);

  } else {
    // ---- INSERT (omitimos `fecha` si viene vacía para que se aplique el DEFAULT de MySQL) ----
    $cols = ["solicitud_id", "desempenia_funcion_publica", "relacion_funcion_publica", "lugar"];
    $vals = [":solicitud_id", ":desempenia", ":relacion", ":lugar"];

    if (!is_null($firmaBinAutorizacion)) {
      $cols[] = "firma";
      $vals[] = ":firma";
    }
    if (!is_null($firmaBinFormulario)) {
      $cols[] = "firma_formulario";
      $vals[] = ":firma_formulario";
    }
    if ($fecha !== '') {
      $cols[] = "fecha";
      $vals[] = ":fecha";
    }

    $sql = "INSERT INTO funcionarios_firma (" . implode(", ", $cols) . ") VALUES (" . implode(", ", $vals) . ")";
    $stmt = $conn->prepare($sql);

    $stmt->bindValue(':solicitud_id', $solicitud_id, PDO::PARAM_INT);
    $stmt->bindValue(':desempenia',   $desempenia);
    $stmt->bindValue(':relacion',     $relacion);
    $stmt->bindValue(':lugar',        $lugar);
    if (!is_null($firmaBinAutorizacion)) {
      $stmt->bindValue(':firma', $firmaBinAutorizacion, PDO::PARAM_LOB);
    }
    if (!is_null($firmaBinFormulario)) {
      $stmt->bindValue(':firma_formulario', $firmaBinFormulario, PDO::PARAM_LOB);
    }
    if ($fecha !== '') {
      $stmt->bindValue(':fecha', $fecha);
    }

    $stmt->execute();
    echo json_encode(['status' => 'ok', 'message' => 'Registro insertado correctamente.']);
  }

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'status'  => 'error',
    'message' => 'Excepción: ' . $e->getMessage(),
    'line'    => $e->getLine(),
    'file'    => $e->getFile()
  ]);
}
