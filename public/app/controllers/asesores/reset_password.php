<?php
declare(strict_types=1);

ini_set('display_errors','0'); ini_set('log_errors','1'); error_reporting(E_ALL);
set_error_handler(function($s,$m,$f,$l){ throw new ErrorException($m,0,$s,$f,$l); });
header('Content-Type: application/json; charset=utf-8');
ob_start();

try {
  // Solo POST
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Sólo POST']); ob_end_flush(); exit;
  }

  // Sesión (soporta / y /public)
  $uri = $_SERVER['REQUEST_URI'] ?? '';
  $cookiePath = (strpos($uri, '/public/') !== false) ? '/public' : '/';
  session_set_cookie_params(['path'=>$cookiePath,'httponly'=>true,'samesite'=>'Lax']);
  session_start();

  if (empty($_SESSION['asesor']['id'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'No autenticado']); ob_end_flush(); exit;
  }
  $meId = (int)$_SESSION['asesor']['id'];

  // DB
  require_once __DIR__ . '/../../db/conexion.php';
  $db = (isset($pdo) && $pdo instanceof PDO) ? $pdo : ((isset($conn) && $conn instanceof PDO) ? $conn : null);
  if (!$db) throw new Exception('Sin conexión DB');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Input
  $raw = file_get_contents('php://input');
  if ($raw === '' || $raw === false) throw new Exception('Sin payload');
  $in = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

  // Acepta id_asesor o id desde el front
  $id  = (int)($in['id_asesor'] ?? $in['id'] ?? 0);
  $pwd = (string)($in['password'] ?? '');

  if ($id <= 0)         throw new Exception('ID inválido');
  if (strlen($pwd) < 8) throw new Exception('Contraseña mínima de 8 caracteres');

  // ¿El que llama es admin?
  $stAdm = $db->prepare("SELECT is_admin FROM asesores WHERE id_asesor = :id LIMIT 1");
  $stAdm->execute([':id' => $meId]);
  $isAdmin = (int)$stAdm->fetchColumn();

  // Permisos: o soy yo mismo, o soy admin
  if ($meId !== $id && $isAdmin !== 1) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'No autorizado']); ob_end_flush(); exit;
  }

  // Actualiza password (usa id_asesor)
  $hash = password_hash($pwd, PASSWORD_BCRYPT);
  $st = $db->prepare("UPDATE asesores SET password = :p WHERE id_asesor = :id");
  $st->execute([':p'=>$hash, ':id'=>$id]);

  echo json_encode(['ok'=>true,'msg'=>'Contraseña actualizada ✅']);
  ob_end_flush(); exit;

} catch (Throwable $e) {
  if (ob_get_length()) ob_clean();
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
  exit;
}
