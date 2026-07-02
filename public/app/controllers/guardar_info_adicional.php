<?php
require_once(__DIR__ . '/../db/conexion.php');
header('Content-Type: application/json');

// Función de limpieza
function limpiar($valor) {
  return htmlspecialchars(trim($valor));
}

// Validar solicitud_id
$solicitud_id = isset($_POST['solicitud_id']) ? intval($_POST['solicitud_id']) : 0;
if ($solicitud_id <= 0) {
  echo json_encode(["success" => false, "message" => "Falta el ID de solicitud."]);
  exit;
}

// Obtener y limpiar datos
$tipo_vivienda          = limpiar($_POST['tipo_vivienda'] ?? '');
$pago_casa              = floatval($_POST['pago_casa'] ?? 0);
$pago_servicios         = floatval($_POST['pago_servicios'] ?? 0);
$pago_otros             = floatval($_POST['pago_otros'] ?? 0);
$gasto_mensual          = floatval($_POST['gasto_mensual'] ?? 0);
$valor_casa             = floatval($_POST['valor_casa'] ?? 0);
$saldo_hipoteca         = floatval($_POST['saldo_hipoteca'] ?? 0);
$empresa_hipoteca       = limpiar($_POST['empresa_hipoteca'] ?? 'N/A');
$nombre_propietario     = limpiar($_POST['nombre_propietario'] ?? '');
$parentesco_propietario = limpiar($_POST['parentesco_propietario'] ?? '');
$telefono_propietario   = limpiar($_POST['telefono_propietario'] ?? '');
$posee_auto             = limpiar($_POST['posee_auto'] ?? 'No');
$marca_auto             = limpiar($_POST['marca_auto'] ?? 'N/A');
$valor_auto             = floatval($_POST['valor_auto'] ?? 0);
$empresa_auto           = limpiar($_POST['empresa_auto'] ?? 'N/A');
$mensualidad_auto       = floatval($_POST['mensualidad_auto'] ?? 0);

try {
  // Verificar si ya existe un registro para este folio
  $check = $conn->prepare("SELECT id FROM info_adicional WHERE solicitud_id = ?");
  $check->execute([$solicitud_id]);

  if ($check->rowCount() > 0) {
    // ✅ Actualizar si ya existe
    $stmt = $conn->prepare("
      UPDATE info_adicional SET
        tipo_vivienda = ?, pago_casa = ?, pago_servicios = ?, pago_otros = ?, gasto_mensual = ?,
        valor_casa = ?, saldo_hipoteca = ?, empresa_hipoteca = ?, nombre_propietario = ?,
        parentesco_propietario = ?, telefono_propietario = ?, posee_auto = ?, marca_auto = ?,
        valor_auto = ?, empresa_auto = ?, mensualidad_auto = ?
      WHERE solicitud_id = ?
    ");
    $stmt->execute([
      $tipo_vivienda, $pago_casa, $pago_servicios, $pago_otros, $gasto_mensual,
      $valor_casa, $saldo_hipoteca, $empresa_hipoteca, $nombre_propietario,
      $parentesco_propietario, $telefono_propietario, $posee_auto, $marca_auto,
      $valor_auto, $empresa_auto, $mensualidad_auto, $solicitud_id
    ]);
  } else {
    // 🆕 Insertar si no existe
    $stmt = $conn->prepare("
      INSERT INTO info_adicional (
        solicitud_id, tipo_vivienda, pago_casa, pago_servicios, pago_otros, gasto_mensual,
        valor_casa, saldo_hipoteca, empresa_hipoteca, nombre_propietario, parentesco_propietario,
        telefono_propietario, posee_auto, marca_auto, valor_auto, empresa_auto, mensualidad_auto
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
      $solicitud_id, $tipo_vivienda, $pago_casa, $pago_servicios, $pago_otros, $gasto_mensual,
      $valor_casa, $saldo_hipoteca, $empresa_hipoteca, $nombre_propietario, $parentesco_propietario,
      $telefono_propietario, $posee_auto, $marca_auto, $valor_auto, $empresa_auto, $mensualidad_auto
    ]);
  }

  echo json_encode(["success" => true, "message" => "✅ Información adicional guardada correctamente."]);
} catch (PDOException $e) {
  echo json_encode(["success" => false, "message" => "❌ Error al guardar: " . $e->getMessage()]);
}
?>
