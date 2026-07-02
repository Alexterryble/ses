<?php
declare(strict_types=1);

ini_set('display_errors','0'); ini_set('log_errors','1'); error_reporting(E_ALL);
set_error_handler(function($s,$m,$f,$l){ throw new ErrorException($m,0,$s,$f,$l); });
header('Content-Type: application/json; charset=utf-8');
ob_start();

try {
  $uri = $_SERVER['REQUEST_URI'] ?? '';
  $cookiePath = (strpos($uri, '/public/') !== false) ? '/public' : '/';
  session_set_cookie_params(['path'=>$cookiePath,'httponly'=>true,'samesite'=>'Lax']);
  session_start();
  if (empty($_SESSION['asesor'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'No autenticado']); ob_end_flush(); exit; }

  require_once __DIR__ . '/../../db/conexion.php';
  $db = (isset($pdo) && $pdo instanceof PDO) ? $pdo : ((isset($conn) && $conn instanceof PDO) ? $conn : null);
  if (!$db) throw new Exception('Sin conexión PDO');

  $total    = (int)$db->query("SELECT COUNT(*) FROM asesores")->fetchColumn();
  $activos  = (int)$db->query("SELECT COUNT(*) FROM asesores WHERE activo = 1")->fetchColumn();
  $inactivos= (int)$db->query("SELECT COUNT(*) FROM asesores WHERE activo = 0")->fetchColumn();

  echo json_encode(['ok'=>true,'total'=>$total,'activos'=>$activos,'inactivos'=>$inactivos]);
  ob_end_flush(); exit;

} catch (Throwable $e) {
  if (ob_get_length()) ob_clean();
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
  exit;
}
