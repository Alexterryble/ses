<?php
require_once(__DIR__ . '/../db/conexion.php');
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['status' => 'error', 'error' => 'Método inválido']); exit;
}

$solicitud_id = $_POST['solicitud_id'] ?? null;
if (!$solicitud_id || !ctype_digit((string)$solicitud_id)) {
  echo json_encode(['status' => 'error', 'error' => 'Falta o inválido solicitud_id']); exit;
}

try {
  $conn->beginTransaction();

  // Reemplazo total: borra referencias previas de esta solicitud
  $stmtDel = $conn->prepare("DELETE FROM referencias_solicitante WHERE solicitud_id = ?");
  $stmtDel->execute([$solicitud_id]);

  // INSERT con columna email (cámbiala por 'correo' si así está en tu DB)
  $stmtIns = $conn->prepare("
    INSERT INTO referencias_solicitante
      (solicitud_id, tipo, numero, nombre_completo, direccion, telefono, celular, email, parentesco)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");

  $insertados = 0;

  // Helper lectura segura
  $val = function($k) {
    return isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
  };

  // --------- FAMILIARES: 1 y 2 (respetando tus IDs) ----------
  for ($i = 1; $i <= 2; $i++) {
    $suf = ($i === 1) ? '' : "_$i";

    $nombre     = $val("form_ref_fam_nombre$suf");
    $direccion  = $val("form_ref_fam_direccion$suf");
    $telefono   = $val("form_ref_fam_telefono$suf");
    $celular    = $val("form_ref_fam_celular$suf");
    $email      = $val("form_ref_fam_correo$suf"); // <-- NUEVO: correo/email
    $parentesco = $val("form_ref_fam_parentesco$suf");

    // Si todo viene vacío, no insertamos esta fila
    if ($nombre.$direccion.$telefono.$celular.$email.$parentesco === '') continue;

    $stmtIns->execute([
      (int)$solicitud_id,
      'Familiar',
      $i, // numero 1 o 2
      $nombre,
      $direccion,
      ($telefono !== '' ? $telefono : null),
      ($celular  !== '' ? $celular  : null),
      ($email    !== '' ? $email    : null),
      $parentesco
    ]);
    $insertados += $stmtIns->rowCount();
  }

  // --------- PERSONAL: SOLO 1 (sin *_2) ----------
  $nombreP     = $val('form_ref_per_nombre');
  $direccionP  = $val('form_ref_per_direccion');
  $telefonoP   = $val('form_ref_per_telefono');
  $celularP    = $val('form_ref_per_celular');
  $emailP      = $val('form_ref_per_correo'); // <-- NUEVO: correo/email
  $parentescoP = $val('form_ref_per_parentesco');

  if ($nombreP.$direccionP.$telefonoP.$celularP.$emailP.$parentescoP !== '') {
    $stmtIns->execute([
      (int)$solicitud_id,
      'Personal',
      1, // solo la primera personal
      $nombreP,
      $direccionP,
      ($telefonoP !== '' ? $telefonoP : null),
      ($celularP  !== '' ? $celularP  : null),
      ($emailP    !== '' ? $emailP    : null),
      $parentescoP
    ]);
    $insertados += $stmtIns->rowCount();
  }

  $conn->commit();
  echo json_encode(['status' => 'ok', 'insertados' => $insertados]);

} catch (PDOException $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}
