<?php
// app/controllers/auth/require_login.php
// Pon esto como lo PRIMERO en cada página protegida.

session_set_cookie_params([
  'path'     => '/',     // <- raíz del sitio público (no uses /sempiternal/public)
  'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();

if (empty($_SESSION['asesor']['id'])) {
  // Descubre dónde está el login en tu webroot
  $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
  $loginPath = null;
  foreach (['/login.html','/login.php','/public/login.html','/public/login.php'] as $cand) {
    if (is_file($root . $cand)) { $loginPath = $cand; break; }
  }
  if ($loginPath === null) $loginPath = '/login.html';

  // URL a la que quería ir (solo path+query)
  $next = urlencode($_SERVER['REQUEST_URI'] ?? '/');

  header("Location: {$loginPath}?next={$next}", true, 302);
  exit;
}
