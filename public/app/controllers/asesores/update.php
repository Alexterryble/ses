<?php
declare(strict_types=1);

ini_set('display_errors','0'); ini_set('log_errors','1'); error_reporting(E_ALL);
set_error_handler(function($s,$m,$f,$l){ throw new ErrorException($m,0,$s,$f,$l); });
header('Content-Type: application/json; charset=utf-8');
ob_start();

try {
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Sólo POST']); ob_end_flush(); exit;
  }

  // Sesión (prod / y local /public)
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
  if (!$db) throw new Exception('Sin conexión PDO');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Input
  $raw = file_get_contents('php://input');
  if ($raw === '' || $raw === false) throw new Exception('Sin payload');
  $in  = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

  // Acepta id_asesor o id
  $id = (int)($in['id_asesor'] ?? $in['id'] ?? 0);
  if ($id <= 0) throw new Exception('ID inválido');

  // ¿Es admin quien edita?
  $qAdm = $db->prepare("SELECT is_admin FROM asesores WHERE id_asesor=:id LIMIT 1");
  $qAdm->execute([':id'=>$meId]);
  $isAdmin = (int)$qAdm->fetchColumn();

  // Permisos: un asesor puede editarse a sí mismo; solo admin puede cambiar a otros
  if ($meId !== $id && $isAdmin !== 1) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'No autorizado']); ob_end_flush(); exit;
  }

  // Campos reales que se pueden actualizar (los que manda tu dashboard)
  $map = [
    'nombre'    => 'nombre',
    'rfc'       => 'rfc',
    'direccion' => 'direccion',
    'telefono'  => 'telefono',
    'clabe'     => 'clabe',
    'cuenta'    => 'cuenta',
    'rol'       => 'rol',
    'usuario'   => 'usuario', // si en algún momento quieres editarlo
    'email'     => 'email',   // en tu tabla la columna es 'email'
  ];

  $sets = [];
  $params = [':id' => $id];

  foreach ($map as $inKey => $col) {
    if (array_key_exists($inKey, $in)) {
      $val = trim((string)$in[$inKey]);
      if ($inKey === 'email' && $val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
      }
      $sets[] = "$col = :$inKey";
      $params[":$inKey"] = $val;
    }
  }

  // Solo admin puede tocar activo/is_admin si se incluyen
  if ($isAdmin === 1) {
    if (array_key_exists('activo', $in))   { $sets[]='activo = :activo';     $params[':activo']   = (int)$in['activo']; }
    if (array_key_exists('is_admin',$in))  { $sets[]='is_admin = :is_admin'; $params[':is_admin'] = (int)$in['is_admin']; }
  }

  if (!$sets) throw new Exception('Nada que actualizar');

  // Unicidad si vienen esos campos
  if (array_key_exists('usuario', $in)) {
    $chk = $db->prepare("SELECT COUNT(*) FROM asesores WHERE usuario=:u AND id_asesor<>:id");
    $chk->execute([':u'=>$in['usuario'], ':id'=>$id]);
    if ((int)$chk->fetchColumn() > 0) throw new Exception('El usuario ya existe');
  }
  if (array_key_exists('email', $in)) {
    $chk = $db->prepare("SELECT COUNT(*) FROM asesores WHERE email=:e AND id_asesor<>:id");
    $chk->execute([':e'=>$in['email'], ':id'=>$id]);
    if ((int)$chk->fetchColumn() > 0) throw new Exception('El email ya existe');
  }

  // Update
  $sql = "UPDATE asesores SET ".implode(', ', $sets)." WHERE id_asesor=:id";
  $st  = $db->prepare($sql);
  $st->execute($params);

  echo json_encode(['ok'=>true, 'msg'=>'Actualizado']);
  ob_end_flush(); exit;

} catch (Throwable $e) {
  if (ob_get_length()) ob_clean();
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
  exit;
}
