<?php
// Mostrar errores (desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once(__DIR__ . '/../db/conexion.php'); // $conn = PDO

// ---------- Entrada ----------
$input = file_get_contents("php://input");
$data  = json_decode($input, true);
$solicitud_id = $data['solicitud_id'] ?? $_POST['solicitud_id'] ?? null;

if (!$solicitud_id) {
  echo json_encode(['status'=>'error','message'=>'No se recibió el ID de la solicitud.']);
  exit;
}

// ---------- Helpers ----------
function verificarPaso($conn, $tabla, $campo, $id) {
  $stmt = $conn->prepare("SELECT COUNT(*) FROM $tabla WHERE $campo = ?");
  $stmt->execute([$id]);
  return $stmt->fetchColumn() > 0;
}

// ---------- Validaciones de pasos (igual que antes) ----------
$errores = [];
if (!verificarPaso($conn, 'datos_personales',       'solicitud_id', $solicitud_id)) $errores[] = 'Paso 1: Datos personales';
if (!verificarPaso($conn, 'info_laboral',           'solicitud_id', $solicitud_id)) $errores[] = 'Paso 2: Información laboral';
if (!verificarPaso($conn, 'info_adicional',         'solicitud_id', $solicitud_id)) $errores[] = 'Paso 3: Información adicional';
if (!verificarPaso($conn, 'firma_declaracion',      'solicitud_id', $solicitud_id)) $errores[] = 'Paso 4: Firma y declaración';
if (!verificarPaso($conn, 'referencias_solicitante','solicitud_id', $solicitud_id)) $errores[] = 'Paso 5: Referencias';
if (!verificarPaso($conn, 'codeudores',             'solicitud_id', $solicitud_id)) $errores[] = 'Paso 6: Codeudor';
if (!verificarPaso($conn, 'funcionarios_firma',     'solicitud_id', $solicitud_id)) $errores[] = 'Paso 7: Función pública y firmas';
if (!verificarPaso($conn, 'solicitudes',            'id',           $solicitud_id)) $errores[] = 'Paso 8: Encabezado (solicitudes)';

if (!empty($errores)) {
  echo json_encode(['status'=>'incompleto','faltan'=>$errores]);
  exit;
}

// ---------- Si ya tenía folio, lo devolvemos ----------
$stmt = $conn->prepare("SELECT folio FROM solicitudes WHERE id = ?");
$stmt->execute([$solicitud_id]);
$folioExistente = $stmt->fetchColumn();

if ($folioExistente) {
  echo json_encode(['status'=>'ya_generado','folio'=>$folioExistente]);
  exit;
}

// ---------- Generación segura y correlativa del folio ----------
try {
  $conn->beginTransaction();

  $anio = (int)date('Y');
  $prefijo = "CIP-$anio-";

  // Asegura que exista la fila de ese año en folio_seq (sin incrementar aún)
  $stmt = $conn->prepare("
    INSERT INTO folio_seq (anio, last_num) VALUES (?, 0)
    ON DUPLICATE KEY UPDATE last_num = last_num
  ");
  $stmt->execute([$anio]);

  // Bloquea la fila del año para tomar el siguiente número
  $stmt = $conn->prepare("SELECT last_num FROM folio_seq WHERE anio = ? FOR UPDATE");
  $stmt->execute([$anio]);
  $last = (int)$stmt->fetchColumn();
  $next = $last + 1;

  // Actualiza el consecutivo del año
  $stmt = $conn->prepare("UPDATE folio_seq SET last_num = ? WHERE anio = ?");
  $stmt->execute([$next, $anio]);

  // Arma el folio bonito: CIP-YYYY-00001
  $folio = $prefijo . str_pad($next, 5, '0', STR_PAD_LEFT);

  // Intenta guardar el folio en la solicitud (solo si sigue vacío)
  $stmt = $conn->prepare("
    UPDATE solicitudes
       SET folio = ?
     WHERE id = ?
       AND (folio IS NULL OR folio = '')
  ");
  $stmt->execute([$folio, $solicitud_id]);

  // Si no afectó filas, seguramente alguien ya le puso folio
  if ($stmt->rowCount() === 0) {
    // volvemos a leerlo y lo devolvemos
    $stmt = $conn->prepare("SELECT folio FROM solicitudes WHERE id = ?");
    $stmt->execute([$solicitud_id]);
    $ya = $stmt->fetchColumn();
    $conn->commit();
    echo json_encode(['status'=>'ya_generado','folio'=>$ya]);
    exit;
  }

  $conn->commit();
  echo json_encode(['status'=>'ok','folio'=>$folio]);

} catch (PDOException $e) {
  if ($conn->inTransaction()) $conn->rollBack();

  // Código 1062 = duplicado (por carrera). Podemos reintentar simple.
  if ((int)$e->errorInfo[1] === 1062) {
    // Lee el folio que quedó y devuélvelo
    $stmt = $conn->prepare("SELECT folio FROM solicitudes WHERE id = ?");
    $stmt->execute([$solicitud_id]);
    $ya = $stmt->fetchColumn();
    echo json_encode(['status'=>'ya_generado','folio'=>$ya]);
    exit;
  }

  echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
