<?php
require_once(__DIR__ . '/../db/conexion.php');
session_start(); // Iniciar la sesión para acceder a datos del usuario logueado

$folio = $_GET['folio'] ?? null;
$campo = $_GET['campo'] ?? 'firma_base64';

if (!$folio || !$campo) {
  http_response_code(400);
  exit('Folio o campo no proporcionado');
}

try {
  $stmt = $conn->prepare("SELECT `$campo` FROM firma_declaracion WHERE solicitud_id = ?");
  $stmt->execute([$folio]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($row && !empty($row[$campo])) {
    $base64 = $row[$campo];

    // Eliminar encabezado si lo tiene
    if (strpos($base64, 'base64,') !== false) {
      $base64 = explode('base64,', $base64)[1];
    }

    $imageData = base64_decode($base64);

    if ($imageData === false) {
      http_response_code(415);
      exit('Error al decodificar imagen');
    }

    header('Content-Type: image/png');
    echo $imageData;
  } else {
    http_response_code(404);
    exit('Firma no encontrada');
  }
} catch (Exception $e) {
  http_response_code(500);
  echo 'Error: ' . $e->getMessage();
}
