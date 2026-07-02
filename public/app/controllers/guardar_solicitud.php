<?php
require_once(__DIR__ . '/../db/conexion.php');
session_start();

header('Content-Type: application/json; charset=utf-8');

function limpiar($v) {
  return htmlspecialchars(trim((string)$v), ENT_QUOTES, 'UTF-8');
}

try {
  // --- ID puede venir como 'id' o 'solicitud_id'
  $id = (int)($_POST['id'] ?? $_POST['solicitud_id'] ?? 0);

  // Nombre del asesor que atendió (acepta 'atendido_por' o 'atendio')
  $atendido_por = limpiar($_POST['atendido_por'] ?? $_POST['atendio'] ?? '');

  // Otros campos
  $medio      = limpiar($_POST['medio'] ?? '');
  $monto      = is_numeric($_POST['monto'] ?? null) ? (float)$_POST['monto'] : 0.0;
  $plazo      = is_numeric($_POST['plazo'] ?? null) ? (int)$_POST['plazo'] : 0;
  $frecuencia = limpiar($_POST['frecuencia'] ?? 'Mensual');

  // ✅ Nueva tasa mensual
  $tasa_mensual = is_numeric($_POST['tasa_mensual'] ?? null)
    ? (float)$_POST['tasa_mensual']
    : 10.50;

  // Protección básica
  if ($tasa_mensual < 0) {
    $tasa_mensual = 0.00;
  }

  // Modalidad del contrato: P10 / P10_ORD / P40 / P40_ORD / SEM_P10
  $contrato_modalidad = strtoupper(limpiar($_POST['contrato_modalidad'] ?? ''));

  $validas = ['P10', 'P10_ORD', 'P40', 'P40_ORD', 'SEM_P10'];

  if (!in_array($contrato_modalidad, $validas, true)) {
    echo json_encode([
      'status'  => 'error',
      'message' => 'Modalidad inválida. Selecciona Unipersonal 10, Sem Personal 10, Personal 10 Ordinario, Personal 40 o Personal 40 Ordinario.'
    ]);
    exit;
  }

  // Asesor logueado (si no existe, deja 0)
  $asesor_id = (int)($_SESSION['asesor']['id'] ?? $_SESSION['asesor_id'] ?? 0);

  // Validaciones básicas
  if ($atendido_por === '' || $medio === '' || $monto <= 0 || $plazo <= 0 || $frecuencia === '') {
    echo json_encode([
      'status'  => 'error',
      'message' => 'Todos los campos son obligatorios y deben tener valores válidos.'
    ]);
    exit;
  }

  // Usa $conn (PDO) de tu conexion.php
  if (!isset($conn) || !($conn instanceof PDO)) {
    echo json_encode([
      'status'  => 'error',
      'message' => 'Conexión a BD no disponible.'
    ]);
    exit;
  }

  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Si nos mandan id válido, intentamos actualizar
  if ($id > 0) {
    $checkSql = "SELECT id FROM solicitudes WHERE id = :id";
    $st = $conn->prepare($checkSql);
    $st->execute([':id' => $id]);

    if ($st->fetchColumn()) {
      $upd = $conn->prepare("
        UPDATE solicitudes
           SET atendido_por       = :atendido_por,
               medio              = :medio,
               monto              = :monto,
               plazo              = :plazo,
               tasa_mensual       = :tasa_mensual,
               frecuencia_pago    = :frecuencia,
               contrato_modalidad = :modalidad
         WHERE id = :id
      ");

      $upd->execute([
        ':atendido_por' => $atendido_por,
        ':medio'        => $medio,
        ':monto'        => $monto,
        ':plazo'        => $plazo,
        ':tasa_mensual' => $tasa_mensual,
        ':frecuencia'   => $frecuencia,
        ':modalidad'    => $contrato_modalidad,
        ':id'           => $id
      ]);

      echo json_encode([
        'status'       => 'ok',
        'accion'       => 'actualizado',
        'message'      => '✅ Solicitud actualizada correctamente.',
        'solicitud_id' => $id,
        'tasa_mensual' => $tasa_mensual
      ]);
      exit;
    }
  }

  // INSERT (nueva solicitud)
  $ins = $conn->prepare("
    INSERT INTO solicitudes (
      asesor_id,
      atendido_por,
      medio,
      monto,
      plazo,
      tasa_mensual,
      frecuencia_pago,
      contrato_modalidad,
      fecha_registro
    ) VALUES (
      :asesor_id,
      :atendido_por,
      :medio,
      :monto,
      :plazo,
      :tasa_mensual,
      :frecuencia,
      :modalidad,
      NOW()
    )
  ");

  $ins->execute([
    ':asesor_id'    => $asesor_id,
    ':atendido_por' => $atendido_por,
    ':medio'        => $medio,
    ':monto'        => $monto,
    ':plazo'        => $plazo,
    ':tasa_mensual' => $tasa_mensual,
    ':frecuencia'   => $frecuencia,
    ':modalidad'    => $contrato_modalidad
  ]);

  $nuevoId = (int)$conn->lastInsertId();

  echo json_encode([
    'status'       => 'ok',
    'accion'       => 'creado',
    'message'      => '✅ Solicitud creada correctamente.',
    'solicitud_id' => $nuevoId,
    'tasa_mensual' => $tasa_mensual
  ]);

} catch (Throwable $e) {
  echo json_encode([
    'status'  => 'error',
    'message' => '❌ Error al guardar en la base de datos.',
    'error'   => $e->getMessage()
  ]);
}