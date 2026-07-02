<?php
/**
 * /app/controllers/auth/csrf.php
 * Devuelve un token CSRF para formularios/AJAX.
 * - Mismo origen (no CORS).
 * - Session cookie segura (Secure+HttpOnly+SameSite=Lax).
 * - No-cache para evitar tokens viejos.
 * 
 * Frontend: haz GET a este endpoint y usa el valor en header `X-CSRF-Token`
 * y/o en el body (campo `csrf`).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

/* ==== Sesión segura ==== */
$cookieParams = [
  'lifetime' => 0,
  'path'     => '/',
  'domain'   => '',        // si usas subdominios, ajusta aquí
  'secure'   => true,      // requiere HTTPS (en Railway sí hay)
  'httponly' => true,
  'samesite' => 'Lax',     // 'Strict' si no haces POST cross-site de ningún tipo
];
session_set_cookie_params($cookieParams);
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

session_start();

/* ==== Utilidad para crear token ==== */
function new_csrf_token(): string {
  // 32 bytes = 256 bits aleatorios; hex = 64 chars
  return bin2hex(random_bytes(32));
}

/* ==== Rotación opcional ====
   Si recibes POST con { rotate:1 } o query ?rotate=1,
   se fuerza la regeneración del token. */
$rotate = false;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $raw = file_get_contents('php://input');
  $in  = json_decode($raw, true);
  if (!is_array($in)) $in = $_POST ?: [];
  $rotate = isset($in['rotate']) && (string)$in['rotate'] === '1';
} else {
  $rotate = isset($_GET['rotate']) && $_GET['rotate'] === '1';
}

/* ==== Genera/recupera token ==== */
if ($rotate || empty($_SESSION['csrf'])) {
  $_SESSION['csrf']       = new_csrf_token();
  $_SESSION['csrf_issued']= time();
  // (Opcional) “atar” un poco el token al agente
  $_SESSION['csrf_fprint']= hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''));
}

/* ==== Respuesta ==== */
echo json_encode([
  'ok'        => true,
  'csrf'      => $_SESSION['csrf'],
  'issued_at' => $_SESSION['csrf_issued'] ?? time(),
  'header'    => 'X-CSRF-Token',
  'samesite'  => $cookieParams['samesite'],
], JSON_UNESCAPED_UNICODE);
