<?php
// /app/controllers/pagare/get_pagares.php
require_once __DIR__ . '/../../db/conexion.php';

$sid  = isset($_GET['solicitud_id']) ? (int)$_GET['solicitud_id'] : 0;
$lado = $_GET['lado'] ?? '';

if (!$sid || !in_array($lado, ['front','back'], true)) {
  http_response_code(400);
  exit('Bad request');
}

$cols = $lado === 'front'
  ? "ine_front_mime AS mime, ine_front AS bin"
  : "ine_back_mime  AS mime, ine_back  AS bin";

$st = $conn->prepare("SELECT $cols FROM railway.pagares WHERE solicitud_id = ? LIMIT 1");
$st->execute([$sid]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['bin'])) {
  http_response_code(404);
  exit('Not found');
}

$mime = $row['mime'] ?: 'image/jpeg';
header('Content-Type: ' . $mime);
header('Cache-Control: private, max-age=3600');
echo $row['bin'];
