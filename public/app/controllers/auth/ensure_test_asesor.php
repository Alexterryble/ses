<?php
declare(strict_types=1);
ini_set('display_errors','0'); ini_set('log_errors','1'); error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
  require_once __DIR__ . '/../../db/conexion.php';
  $db = (isset($pdo) && $pdo instanceof PDO) ? $pdo : ((isset($conn) && $conn instanceof PDO) ? $conn : null);
  if (!$db) throw new Exception('Sin conexión PDO');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // 🔧 Ajusta estos valores de prueba
  $usuario   = 'test_asesor';
  $correo    = 'test@example.com';
  $nombre    = 'Asesor';
  $apPat     = 'DePrueba';
  $apMat     = 'Demo';
  $plainPass = '123456';                  // ← contraseña de prueba
  $hash      = password_hash($plainPass, PASSWORD_BCRYPT);

  // ¿Existe?
  $st = $db->prepare("SELECT id FROM asesores WHERE usuario = :u LIMIT 1");
  $st->execute([':u'=>$usuario]);
  $id = $st->fetchColumn();

  if ($id) {
    // Actualiza datos clave y password
    $up = $db->prepare("UPDATE asesores
                        SET correo=:c, nombre=:n, apellido_paterno=:ap, apellido_materno=:am,
                            password=:p, activo=1
                        WHERE id=:id");
    $up->execute([
      ':c'=>$correo, ':n'=>$nombre, ':ap'=>$apPat, ':am'=>$apMat, ':p'=>$hash, ':id'=>$id
    ]);
    echo json_encode(['ok'=>true,'msg'=>'Usuario actualizado','user'=>$usuario,'password'=>$plainPass]); exit;
  } else {
    // Crea el usuario
    $in = $db->prepare("INSERT INTO asesores
      (nombre, apellido_paterno, apellido_materno, usuario, correo, password, activo)
      VALUES (:n,:ap,:am,:u,:c,:p,1)");
    $in->execute([
      ':n'=>$nombre, ':ap'=>$apPat, ':am'=>$apMat, ':u'=>$usuario, ':c'=>$correo, ':p'=>$hash
    ]);
    echo json_encode(['ok'=>true,'msg'=>'Usuario creado','user'=>$usuario,'password'=>$plainPass]); exit;
  }

} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
