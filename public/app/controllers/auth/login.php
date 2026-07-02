<?php
declare(strict_types=1);

/* ========= Barrera anti-HTML / errores visibles como JSON ========= */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

set_error_handler(function($sev, $msg, $file, $line) {
  throw new ErrorException($msg, 0, $sev, $file, $line);
});

ob_start();
header('Content-Type: application/json; charset=utf-8');

try {
  /* ===== Método permitido ===== */
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Sólo POST']); ob_end_flush(); exit;
  }

  /* ===== Sesión (Railway/producción sirve tu app en la RAÍZ) ===== */
  $isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ||
    ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443)
  );

  // Fijamos al root del dominio
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',      // raíz (sin /sempiternal ni /public)
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();

  /* ===== Conexión DB (debe exponer $pdo ó $conn como PDO) ===== */
  require_once __DIR__ . '/../../db/conexion.php';

  /** @var PDO|null $db */
  $db = (isset($pdo) && $pdo instanceof PDO)
      ? $pdo
      : ((isset($conn) && $conn instanceof PDO) ? $conn : null);

  if (!$db) throw new Exception('No se pudo inicializar la conexión PDO.');

  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

  /* ===== Input: JSON o FormData ===== */
  $in = [];
  $raw = file_get_contents('php://input');
  if (is_string($raw) && $raw !== '') {
    try {
      $in = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
      // si no era JSON, ignoramos; abajo tomamos $_POST
    }
  }
  if (!is_array($in) || !$in) $in = $_POST;

  $user     = trim((string)($in['user'] ?? $in['usuario'] ?? ''));
  $pass     = (string)($in['password'] ?? $in['pass'] ?? '');
  $remember = !empty($in['remember']);

  if ($user === '' || $pass === '') throw new Exception('Faltan credenciales');

  /* ===== Auth ===== */
  $sql = "SELECT
            id_asesor AS id,
            nombre,
            ''   AS apellido_paterno,
            ''   AS apellido_materno,
            usuario,
            email      AS correo,-- 👈 tomamos email como correo
             telefono,     
            rol,
            rfc,
            direccion,
            password,
            activo,
            is_admin
          FROM asesores
          WHERE (usuario = :u OR email = :e)
          LIMIT 1";
  $st = $db->prepare($sql);
  $st->execute([':u' => $user, ':e' => $user]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row)                      throw new Exception('Usuario no encontrado');
  if ((int)$row['activo'] !== 1) throw new Exception('Usuario inactivo');
  if (!password_verify($pass, $row['password'])) throw new Exception('Contraseña incorrecta');

  // Rehash si cambió el costo de bcrypt
  if (password_needs_rehash($row['password'], PASSWORD_BCRYPT)) {
    $new = password_hash($pass, PASSWORD_BCRYPT);
    $db->prepare("UPDATE asesores SET password = :p WHERE id_asesor = :id")
       ->execute([':p' => $new, ':id' => $row['id']]);
  }

  /* ===== Sesión OK ===== */
  session_regenerate_id(true);

  // Armamos nombre completo por si luego agregas apellidos a la tabla
  $nombreCompleto = trim(
    (string)$row['nombre'].' '.
    (string)($row['apellido_paterno'] ?? '').' '.
    (string)($row['apellido_materno'] ?? '')
  );

  $_SESSION['asesor'] = [
    'id'        => (int)$row['id'],
    'usuario'   => (string)$row['usuario'],
    'correo'    => (string)($row['correo'] ?? ''),  // 👈 email/correo
     'telefono'  => (string)($row['telefono'] ?? ''),       
    'nombre'    => $nombreCompleto,
    'rol'       => (string)($row['rol'] ?? ''),         // 👈 rol
    'rfc'       => (string)($row['rfc'] ?? ''),         // 👈 rfc
    'direccion' => (string)($row['direccion'] ?? ''),   // 👈 dirección
    'is_admin'  => (int)($row['is_admin'] ?? 0),
    'ts'        => time(),
  ];

  // Claves de compatibilidad que usa el resto de tu app
  $_SESSION['user_id']        = (int)$row['id'];
  $_SESSION['user_name']      = $_SESSION['asesor']['nombre'];
  $_SESSION['asesor_id']      = (int)$row['id'];
  $_SESSION['asesor_nombre']  = $_SESSION['asesor']['nombre'];
  $_SESSION['asesor_rol']     = $_SESSION['asesor']['rol'];        // 👈 extra
  $_SESSION['asesor_rfc']     = $_SESSION['asesor']['rfc'];        // 👈 extra
  $_SESSION['asesor_domicilio'] = $_SESSION['asesor']['direccion']; // 👈 extra
  $_SESSION['asesor_correo']  = $_SESSION['asesor']['correo'];     // 👈 extra

  // Recordarme 30 días (extiende la cookie de sesión)
  if ($remember) {
    $lifetime = 60 * 60 * 24 * 30;
    setcookie(session_name(), session_id(), [
      'expires'  => time() + $lifetime,
      'path'     => '/',        // raíz
      'secure'   => $isHttps,
      'httponly' => true,
      'samesite' => 'Lax',
    ]);
  }

  /* ===== Redirección por rol / id =====
     - id = 10        -> dashboard.html (forzado)
     - is_admin = 1   -> dashboard.html
     - resto          -> index.php
  */
  $forceDashboardIds = [10];
  $goDashboard = in_array((int)$row['id'], $forceDashboardIds, true)
              || (int)($row['is_admin'] ?? 0) === 1;

  // IMPORTANTE: sin /sempiternal ni /public en la URL, todo es relativo a la raíz
  $redirect = $goDashboard ? '/dashboard.html' : '/home.php';           

  echo json_encode([
    'ok'             => true,
    'usuario'        => $row['usuario'],
    'correo'         => (string)($row['correo'] ?? ''),     // 👈 lo devolvemos también
    'rol'            => (string)($row['rol'] ?? ''),
    'rfc'            => (string)($row['rfc'] ?? ''),
    'direccion'      => (string)($row['direccion'] ?? ''),
    'redirect'       => $redirect,
    'must_change_pw' => 0
  ]);
  ob_end_flush(); exit;

} catch (Throwable $e) {
  http_response_code(400);
  ob_end_clean();
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
  exit;
}
