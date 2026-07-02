<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

/* Sesión compatible con / y /public */
$uri        = $_SERVER['REQUEST_URI'] ?? '';
$cookiePath = (strpos($uri, '/public/') !== false) ? '/public' : '/';
session_set_cookie_params([
  'path'     => $cookiePath,
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();

/* Normaliza el usuario para el frontend */
$user = $_SESSION['asesor'] ?? null;
if (is_array($user)) {
  // si en sesión vino id_asesor, expón también id
  $user['id']       = (int)($user['id'] ?? $user['id_asesor'] ?? 0);
  $user['is_admin'] = (int)($user['is_admin'] ?? 0);
}

echo json_encode([
  'auth' => (bool)$user,
  'user' => $user,
]);
