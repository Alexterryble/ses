<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db/conexion.php';

try {
  if (!isset($conn) || !($conn instanceof PDO)) { throw new RuntimeException('PDO no disponible'); }
  $id = (int)($_GET['solicitud_id'] ?? 0);
  if ($id <= 0) { http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Falta solicitud_id']); exit; }

  $st = $conn->prepare("SELECT contrato_modalidad FROM solicitudes WHERE id = :id LIMIT 1");
  $st->execute([':id'=>$id]);
  $mod = $st->fetchColumn();

  if ($mod === false) { http_response_code(404); echo json_encode(['ok'=>false,'message'=>'No encontrada']); exit; }
  echo json_encode(['ok'=>true,'modalidad'=>$mod ?? null]);
} catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
}
