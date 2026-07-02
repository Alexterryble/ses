<?php
// /app/controllers/ine/upload_pagares.php
require_once __DIR__.'/../../db/conexion.php';
header('Content-Type: application/json');

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
  }

  $sid  = isset($_POST['solicitud_id']) ? (int)$_POST['solicitud_id'] : 0;
  $lado = $_POST['lado'] ?? ''; // 'front' | 'back'
  if (!$sid || !in_array($lado, ['front','back'], true)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'Parámetros inválidos']); exit;
  }

  if (empty($_FILES['img']) || $_FILES['img']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'Archivo faltante o con error']); exit;
  }

  $tmp  = $_FILES['img']['tmp_name'];
  $mime = mime_content_type($tmp) ?: '';
  if (!in_array($mime, ['image/png','image/jpeg','image/webp'], true)) {
    http_response_code(415);
    echo json_encode(['ok'=>false,'msg'=>'Formato no permitido']); exit;
  }

  // Límite: 2 MiB por seguridad
  if (filesize($tmp) > 2*1024*1024) {
    http_response_code(413);
    echo json_encode(['ok'=>false,'msg'=>'Archivo > 2MB']); exit;
  }

  $bin = file_get_contents($tmp);
  if ($bin === false) { throw new RuntimeException('No se pudo leer archivo'); }

  [$w,$h] = getimagesize($tmp);
  if (!$w || !$h) { throw new RuntimeException('No se pudo obtener tamaño'); }

  $sha = hash('sha256', $bin);

  // Asegurar que exista el registro en pagares
  $conn->prepare("INSERT IGNORE INTO railway.pagares (solicitud_id) VALUES (?)")->execute([$sid]);

  // Construir SET dinámico según lado
  $set = $lado === 'front'
    ? "ine_front = :bin, ine_front_mime = :mime, ine_front_w = :w, ine_front_h = :h, ine_front_sha = :sha"
    : "ine_back  = :bin, ine_back_mime  = :mime, ine_back_w  = :w, ine_back_h  = :h, ine_back_sha  = :sha";

  $sql = "UPDATE railway.pagares
          SET $set, ine_updated_at = NOW()
          WHERE solicitud_id = :sid
          LIMIT 1";

  $st = $conn->prepare($sql);
  $st->bindParam(':bin',  $bin, PDO::PARAM_LOB);
  $st->bindValue(':mime', $mime);
  $st->bindValue(':w',    $w, PDO::PARAM_INT);
  $st->bindValue(':h',    $h, PDO::PARAM_INT);
  $st->bindValue(':sha',  $sha);
  $st->bindValue(':sid',  $sid, PDO::PARAM_INT);
  $st->execute();

  echo json_encode(['ok'=>true, 'mime'=>$mime, 'width'=>$w, 'height'=>$h, 'sha'=>$sha]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
