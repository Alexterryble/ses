<?php
// public/app/controllers/cp_buscar.php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/cp_buscar_error.log');

try {
  require_once __DIR__ . '/../db/conexion.php';
  if (!isset($conn) || !($conn instanceof PDO)) {
    throw new Exception('Conexión PDO $conn no disponible.');
  }
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // ——— Validar CP ———
  $cp = isset($_GET['cp']) ? trim($_GET['cp']) : '';
  if (!preg_match('/^\d{5}$/', $cp)) {
    echo json_encode(['success'=>false,'message'=>'CP inválido']);
    exit;
  }

  // ——— Consulta: deduplicada y ordenada ———
  // TRIM para quitar espacios, DISTINCT para evitar duplicados
  // COLLATE para ordenar “a la española” sin distinguir acentos/mayúsculas
  $sql = "
    SELECT DISTINCT
      TRIM(asentamiento)      AS colonia,
      TRIM(municipio_nombre)  AS municipio,
      TRIM(estado_nombre)     AS estado
    FROM codigos_postales
    WHERE cp = ?
    ORDER BY colonia COLLATE utf8mb4_spanish_ci
  ";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$cp]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (!$rows) {
    echo json_encode(['success'=>false,'message'=>'CP no encontrado']);
    exit;
  }

  // ——— En el (raro) caso de que el mismo CP traiga municipios/estados distintos,
  // tomamos el más frecuente para consistencia.
  $colonias  = [];
  $freqMun   = [];
  $freqEst   = [];

  foreach ($rows as $r) {
    $colonias[] = $r['colonia'];
    $m = $r['municipio'] ?? '';
    $e = $r['estado']    ?? '';
    $freqMun[$m] = ($freqMun[$m] ?? 0) + 1;
    $freqEst[$e] = ($freqEst[$e] ?? 0) + 1;
  }

  arsort($freqMun);
  arsort($freqEst);
  $municipio = key($freqMun) ?: ($rows[0]['municipio'] ?? '');
  $estado    = key($freqEst) ?: ($rows[0]['estado'] ?? '');

  // ——— Respuesta ———
  echo json_encode([
    'success'   => true,
    'cp'        => $cp,
    'municipio' => $municipio,
    'estado'    => $estado,
    'colonias'  => array_values($colonias)  // ya vienen únicas y ordenadas
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  error_log('cp_buscar.php ERROR: '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'Error interno',
    'debug'   => $e->getMessage() // quítalo en producción
  ], JSON_UNESCAPED_UNICODE);
}
