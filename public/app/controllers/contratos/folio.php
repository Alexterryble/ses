<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db/conexion.php';


function jexit($code, $payload){ http_response_code($code); echo json_encode($payload, JSON_UNESCAPED_UNICODE); exit; }

if (!isset($_GET['solicitud_id']) || !ctype_digit($_GET['solicitud_id'])) {
  jexit(422, ['ok'=>false,'error'=>'Falta o es inválido solicitud_id']);
}
$solicitud_id = (int)$_GET['solicitud_id'];

try{
  $stmt = $pdo->prepare("SELECT folio FROM solicitudes WHERE id = ?");
  $stmt->execute([$solicitud_id]);
  $folio = $stmt->fetchColumn();

  if ($folio === false) jexit(404, ['ok'=>false,'error'=>'Solicitud no encontrada']);

  jexit(200, ['ok'=>true, 'folio'=>$folio]); // puede venir NULL si aún no lo asignaste
}catch(Throwable $e){
  jexit(500, ['ok'=>false,'error'=>$e->getMessage()]);
}
