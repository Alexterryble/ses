<?php
// /sempiternal/public/app/controllers/util/seed_passwords_asesores.php

// Carga PDO ($pdo)
$path = __DIR__ . '/../../db/conexion.php';  // <-- ruta CORRECTA
if (!file_exists($path)) {
  // fallback absoluto por si cambia la ubicación
  $alt = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/sempiternal/public/app/db/conexion.php';
  if (file_exists($alt)) $path = $alt;
}
require_once $path;

if (!isset($pdo)) {
  die("No se pudo inicializar \$pdo. Revisa la ruta de conexion.php:\n$path");
}

function is_bcrypt($s){
  return is_string($s) && preg_match('/^\$2y\$\d{2}\$[A-Za-z0-9\.\/]{53}$/', $s);
}

header('Content-Type: text/plain; charset=utf-8');
echo "Asignando contraseñas temporales...\n\n";

$sql = "SELECT id, nombre, apellido_paterno, usuario, correo, password FROM asesores";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $u) {
  // si ya tiene hash bcrypt válido, lo dejamos
  if ($u['password'] && is_bcrypt($u['password'])) continue;

  // genera temporal: InicialNombre + ApellidoPaterno + 2025
  $ini  = mb_substr(trim((string)$u['nombre']), 0, 1, 'UTF-8');
  $ape  = preg_replace('/\s+/', '', (string)$u['apellido_paterno']);
  $temp = strtoupper($ini . $ape) . '2025';
  if ($temp === '2025') $temp = 'CIP2025'; // fallback

  $hash = password_hash($temp, PASSWORD_BCRYPT);
  $upd  = $pdo->prepare("UPDATE asesores SET password = :h WHERE id = :id");
  $upd->execute([':h'=>$hash, ':id'=>$u['id']]);

  $userShown = $u['usuario'] ?: $u['correo'] ?: ('id#'.$u['id']);
  echo "{$userShown} -> pass temporal: {$temp}\n";
}

echo "\nListo.\n";
