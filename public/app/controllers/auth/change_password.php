<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

try{
  if (empty($_SESSION['asesor']['id'])) throw new Exception('No autenticado');

  require_once __DIR__ . '/../../db/conexion.php';

  $raw = file_get_contents('php://input');
  if (!$raw) throw new Exception('Sin payload');
  $in = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

  $new = (string)($in['new_password'] ?? '');
  if (strlen($new) < 8) throw new Exception('La contraseña debe tener mínimo 8 caracteres');

  $hash = password_hash($new, PASSWORD_BCRYPT);
  $st = $pdo->prepare("UPDATE financial.asesores SET password = :h WHERE id = :id");
  $st->execute([':h'=>$hash, ':id'=>$_SESSION['asesor']['id']]);

  echo json_encode(['ok'=>true]);
}catch(Throwable $e){
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
