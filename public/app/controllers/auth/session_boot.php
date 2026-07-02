<?php
// app/controllers/auth/session_boot.php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  // Opcional: endurecer cookies
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false,    // true si sirves por HTTPS
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}
