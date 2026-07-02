<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../db/conexion.php');

function limpiar($valor) {
  return htmlspecialchars(trim($valor));
}

// Validación básica
$solicitud_id = $_POST['solicitud_id'] ?? null;
if (!$solicitud_id) {
  echo json_encode(['status' => 'error', 'message' => 'Falta el ID de solicitud']);
  exit;
}

// Preparar todos los datos
$datos = [
  'solicitud_id' => $solicitud_id,
  'nombres' => limpiar($_POST['nombres'] ?? ''),
  'apellido_paterno' => limpiar($_POST['apellido_paterno'] ?? ''),
  'apellido_materno' => limpiar($_POST['apellido_materno'] ?? ''),
  'correo' => limpiar($_POST['correo'] ?? ''),
  'genero' => limpiar($_POST['genero'] ?? ''),
  'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? '',
  'estado_nacimiento' => limpiar($_POST['estado_nacimiento'] ?? ''),
  'dependientes' => intval($_POST['dependientes'] ?? 0),
  'nacionalidad' => limpiar($_POST['nacionalidad'] ?? ''),
  'pais_nacimiento' => limpiar($_POST['pais_nacimiento'] ?? ''),
  'fiel' => limpiar($_POST['fiel'] ?? ''),
  'rfc' => limpiar($_POST['rfc'] ?? ''),
  'curp' => limpiar($_POST['curp'] ?? ''),
  'estado_civil' => limpiar($_POST['estado_civil'] ?? ''),
  'tiempo_estado_civil' => limpiar($_POST['tiempo_estado_civil'] ?? ''),
  'escolaridad' => limpiar($_POST['escolaridad'] ?? ''),
  'profesion' => limpiar($_POST['profesion'] ?? ''),
  'direccion' => limpiar($_POST['direccion'] ?? ''),
  'entre_calles' => limpiar($_POST['entre_calles'] ?? ''),
  'colonia' => limpiar($_POST['colonia'] ?? ''),
  'cp' => limpiar($_POST['cp'] ?? ''),
  'municipio' => limpiar($_POST['municipio'] ?? ''),
  'estado' => limpiar($_POST['estado'] ?? ''),
  'pais' => limpiar($_POST['pais'] ?? ''),
  'tiempo_domicilio' => limpiar($_POST['tiempo_domicilio'] ?? ''),
  'telefono' => limpiar($_POST['telefono'] ?? ''),
  'celular' => limpiar($_POST['celular'] ?? ''),
  'mejor_hora' => limpiar($_POST['mejor_hora'] ?? '')
];

try {
  // Verificar si ya existe
  $check = $conn->prepare("SELECT id FROM datos_personales WHERE solicitud_id = ?");
  $check->execute([$solicitud_id]);

  if ($check->rowCount() > 0) {
    // ✅ UPDATE si ya existe
    $stmt = $conn->prepare("
      UPDATE datos_personales SET
        nombres = :nombres,
        apellido_paterno = :apellido_paterno,
        apellido_materno = :apellido_materno,
        correo = :correo,
        genero = :genero,
        fecha_nacimiento = :fecha_nacimiento,
        estado_nacimiento = :estado_nacimiento,
        dependientes = :dependientes,
        nacionalidad = :nacionalidad,
        pais_nacimiento = :pais_nacimiento,
        fiel = :fiel,
        rfc = :rfc,
        curp = :curp,
        estado_civil = :estado_civil,
        tiempo_estado_civil = :tiempo_estado_civil,
        escolaridad = :escolaridad,
        profesion = :profesion,
        direccion = :direccion,
        entre_calles = :entre_calles,
        colonia = :colonia,
        cp = :cp,
        municipio = :municipio,
        estado = :estado,
        pais = :pais,
        tiempo_domicilio = :tiempo_domicilio,
        telefono = :telefono,
        celular = :celular,
        mejor_hora = :mejor_hora
      WHERE solicitud_id = :solicitud_id
    ");
    $stmt->execute($datos);

    echo json_encode(['status' => 'ok', 'message' => 'Datos personales actualizados correctamente.']);
  } else {
    // 🆕 INSERT si no existe
    $stmt = $conn->prepare("
      INSERT INTO datos_personales (
        solicitud_id, nombres, apellido_paterno, apellido_materno, correo, genero,
        fecha_nacimiento, estado_nacimiento, dependientes, nacionalidad, pais_nacimiento,
        fiel, rfc, curp, estado_civil, tiempo_estado_civil, escolaridad, profesion,
        direccion, entre_calles, colonia, cp, municipio, estado, pais,
        tiempo_domicilio, telefono, celular, mejor_hora
      ) VALUES (
        :solicitud_id, :nombres, :apellido_paterno, :apellido_materno, :correo, :genero,
        :fecha_nacimiento, :estado_nacimiento, :dependientes, :nacionalidad, :pais_nacimiento,
        :fiel, :rfc, :curp, :estado_civil, :tiempo_estado_civil, :escolaridad, :profesion,
        :direccion, :entre_calles, :colonia, :cp, :municipio, :estado, :pais,
        :tiempo_domicilio, :telefono, :celular, :mejor_hora
      )
    ");
    $stmt->execute($datos);

    echo json_encode(['status' => 'ok', 'message' => 'Datos personales guardados correctamente.']);
  }
} catch (PDOException $e) {
  echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
