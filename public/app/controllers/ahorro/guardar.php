<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Método no permitido']);
    exit;
  }

  session_start();
  $asesor = $_SESSION['asesor'] ?? null;
  $asesor_id = is_array($asesor) ? (int)($asesor['id'] ?? $asesor['asesor_id'] ?? 0) : 0;
  $rol = is_array($asesor) ? (string)($asesor['rol'] ?? 'asesor') : 'asesor';

  if ($asesor_id <= 0) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'No hay sesión de asesor.']);
    exit;
  }

  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'JSON inválido o vacío']);
    exit;
  }

  // ✅ si viene id, es EDITAR
  $id = (int)($data['id'] ?? 0);

  $nombre      = trim((string)($data['nombre'] ?? ''));
  $ap_paterno  = trim((string)($data['ap_paterno'] ?? ''));
  $ap_materno  = trim((string)($data['ap_materno'] ?? ''));
  $rfc         = strtoupper(trim((string)($data['rfc'] ?? '')));
  $cp          = trim((string)($data['cp'] ?? ''));
  $correo      = trim((string)($data['correo'] ?? ''));
  $telefono    = trim((string)($data['telefono'] ?? ''));
  $direccion   = trim((string)($data['direccion'] ?? ''));
  $fecha_ini   = trim((string)($data['fecha_inicio_ahorro'] ?? '')); // YYYY-MM-DD
  $fecha_dev   = trim((string)($data['fecha_devolucion'] ?? ''));    // YYYY-MM-DD
  $monto       = (float)($data['monto_semanal'] ?? 0);
  $porcentaje  = (float)($data['porcentaje'] ?? 0);
  $estado      = trim((string)($data['estado'] ?? '')); // opcional

      // ✅ BENEFICIARIO
  $beneficiario_nombre     = trim((string)($data['beneficiario_nombre'] ?? ''));
  $beneficiario_curp       = strtoupper(trim((string)($data['beneficiario_curp'] ?? '')));
  $beneficiario_telefono   = trim((string)($data['beneficiario_telefono'] ?? ''));
  $beneficiario_parentesco = trim((string)($data['beneficiario_parentesco'] ?? ''));


  if ($nombre === '' || $ap_paterno === '' || $fecha_ini === '' || $monto <= 0) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>'Faltan campos requeridos (nombre, apellido paterno, fecha inicio, monto).']);
    exit;
  }

  require_once __DIR__ . '/../../db/conexion.php';
  if (!isset($pdo) || !($pdo instanceof PDO)) throw new Exception('No existe $pdo en conexion.php');

  // ====== EDITAR ======
  if ($id > 0) {

    // 🔐 asegura que el registro exista y pertenezca al asesor (si no es admin)
    if (strtolower($rol) !== 'admin') {
      $chk = $pdo->prepare("SELECT id FROM ahorro WHERE id = :id AND asesor_id = :asesor_id LIMIT 1");
      $chk->execute([':id'=>$id, ':asesor_id'=>$asesor_id]);
      if (!$chk->fetch()) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'error'=>'No tienes permiso para editar este registro.']);
        exit;
      }
    }

    $sql = "UPDATE ahorro SET
      nombre = :nombre,
      ap_paterno = :ap_paterno,
      ap_materno = :ap_materno,
      rfc = :rfc,
      cp = :cp,
      correo = :correo,
      telefono = :telefono,
      direccion = :direccion,
      fecha_inicio_ahorro = :fecha_inicio_ahorro,
      fecha_devolucion = :fecha_devolucion,
      monto_semanal = :monto_semanal,
      porcentaje = :porcentaje,
      beneficiario_nombre = :beneficiario_nombre,
      beneficiario_curp = :beneficiario_curp,
      beneficiario_telefono = :beneficiario_telefono,
      beneficiario_parentesco = :beneficiario_parentesco,

      actualizado_en = NOW()"
      . ($estado !== '' ? ", estado = :estado" : "") .
      " WHERE id = :id" .
      (strtolower($rol) !== 'admin' ? " AND asesor_id = :asesor_id" : "");

    $params = [
      ':nombre'=>$nombre,
      ':ap_paterno'=>$ap_paterno,
      ':ap_materno'=>$ap_materno,
      ':rfc'=>$rfc,
      ':cp'=>$cp,
      ':correo'=>$correo,
      ':telefono'=>$telefono,
      ':direccion'=>$direccion,
      ':fecha_inicio_ahorro'=>$fecha_ini,
      ':fecha_devolucion'=>$fecha_dev,
      ':monto_semanal'=>$monto,
      ':porcentaje'=>$porcentaje,
      ':beneficiario_nombre' => $beneficiario_nombre,
      ':beneficiario_curp' => $beneficiario_curp,
      ':beneficiario_telefono' => $beneficiario_telefono,
      ':beneficiario_parentesco' => $beneficiario_parentesco,
      ':id'=>$id
    ];
    if ($estado !== '') $params[':estado'] = $estado;
    if (strtolower($rol) !== 'admin') $params[':asesor_id'] = $asesor_id;

    $st = $pdo->prepare($sql);
    $st->execute($params);

    echo json_encode(['ok'=>true,'mode'=>'update','id'=>$id]);
    exit;
  }

  // ====== CREAR (INSERT) ======
  $pdo->beginTransaction();

  $sql = "INSERT INTO ahorro
    (asesor_id, nombre, ap_paterno, ap_materno, rfc, cp, direccion, correo, telefono,
     beneficiario_nombre, beneficiario_curp, beneficiario_telefono, beneficiario_parentesco,
     fecha_inicio_ahorro, fecha_devolucion, monto_semanal, porcentaje, estado,
     creado_en, actualizado_en)
    VALUES
    (:asesor_id, :nombre, :ap_paterno, :ap_materno, :rfc, :cp, :direccion, :correo, :telefono,
     :beneficiario_nombre, :beneficiario_curp, :beneficiario_telefono, :beneficiario_parentesco,
     :fecha_inicio_ahorro, :fecha_devolucion, :monto_semanal, :porcentaje, 'activo',
     NOW(), NOW())";


  $st = $pdo->prepare($sql);
  $st->execute([
    ':asesor_id' => $asesor_id,
    ':nombre' => $nombre,
    ':ap_paterno' => $ap_paterno,
    ':ap_materno' => $ap_materno,
    ':rfc' => $rfc,
    ':correo' => $correo,
    ':cp' => $cp,
    ':direccion' => $direccion,
    ':telefono' => $telefono,
    ':fecha_inicio_ahorro' => $fecha_ini,
    ':fecha_devolucion' => $fecha_dev,
    ':monto_semanal' => $monto,
    ':porcentaje' => $porcentaje,
    ':beneficiario_nombre' => $beneficiario_nombre,
    ':beneficiario_curp' => $beneficiario_curp,
    ':beneficiario_telefono' => $beneficiario_telefono,
    ':beneficiario_parentesco' => $beneficiario_parentesco,

  ]);

  $newId = (int)$pdo->lastInsertId();
  $anio = (int)date('Y');
  $folio = sprintf('CIP-AHO%d-%04d', $anio, $newId);

  $up = $pdo->prepare("UPDATE ahorro SET folio = :folio, actualizado_en = NOW() WHERE id = :id");
  $up->execute([':folio'=>$folio, ':id'=>$newId]);

  $pdo->commit();

  echo json_encode(['ok'=>true,'mode'=>'insert','id'=>$newId,'folio'=>$folio,'asesor_id'=>$asesor_id]);
  exit;

} catch (Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
  exit;
}
