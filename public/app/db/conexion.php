<?php
// app/db/conexion.php
// Compatible con Railway, Render, Aiven y local.
// Crea $pdo y $conn como instancia PDO.

declare(strict_types=1);

/*
  Si necesitas levantar la app sin conectar BD:
  DB_SKIP_CONNECTION=true
*/
$skipConnection = strtolower((string) getenv('DB_SKIP_CONNECTION')) === 'true';

if ($skipConnection) {
  $pdo = null;
  $conn = null;
  return;
}

/*
  Prioridad:
  1. DB_*       -> Aiven / Render / Railway nuevo
  2. MYSQL*     -> Railway viejo
  3. Localhost  -> XAMPP/local
*/

$host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306';

$db = getenv('DB_DATABASE')
  ?: getenv('DB_NAME')
  ?: getenv('MYSQLDATABASE')
  ?: 'railway';

$user = getenv('DB_USERNAME')
  ?: getenv('DB_USER')
  ?: getenv('MYSQLUSER')
  ?: 'root';

$pass = getenv('DB_PASSWORD')
  ?: getenv('DB_PASS')
  ?: getenv('MYSQLPASSWORD')
  ?: '';

$charset = 'utf8mb4';

if (!$host || !$db || !$user) {
  throw new Exception('Faltan variables de entorno para conectar a MySQL.');
}

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

/*
  SSL para Aiven.
  Aiven normalmente requiere SSL.

  Opción recomendada:
  - Descarga el CA certificate desde Aiven.
  - Guárdalo como: app/db/ca.pem
  - Súbelo a GitHub junto con tu proyecto.

  El certificado CA no es contraseña, se puede incluir.
*/
$caPath = __DIR__ . '/ca.pem';

if (file_exists($caPath)) {
  $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
}

/*
  Algunos entornos necesitan esta opción.
  Si el servidor marca error de certificado, puedes desactivar verificación.
  No es lo ideal, pero ayuda en Railway si el CA da problemas.
*/
if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
  $sslVerify = strtolower((string) getenv('DB_SSL_VERIFY'));

  if ($sslVerify === 'false' || $sslVerify === '0') {
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
  }
}

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
  $conn = $pdo;
} catch (PDOException $e) {
  error_log('Error de conexión MySQL: ' . $e->getMessage());
  throw new Exception('Error de conexión a la base de datos.');
}