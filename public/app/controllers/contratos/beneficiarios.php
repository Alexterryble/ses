<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db/conexion.php';

function jexit(int $c, array $p){ http_response_code($c); echo json_encode($p, JSON_UNESCAPED_UNICODE); exit; }
function norm(string $s): string { return preg_replace('~\s+~',' ', trim($s)); }

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jexit(405, ['ok'=>false,'error'=>'Método no permitido']);

$sid = isset($_GET['solicitud_id']) ? (int)$_GET['solicitud_id'] : 0;
if ($sid <= 0) jexit(422, ['ok'=>false,'error'=>'Falta solicitud_id']);

try {
  // 1) Hasta 3 referencias tipo 'Personal'
  $stP = $pdo->prepare("
    SELECT id,
           'REFERENCIA' AS fuente,
           TRIM(COALESCE(nombre_completo,'')) AS nombre,
           TRIM(parentesco) AS parentesco,
           numero
    FROM referencias_solicitante
    WHERE solicitud_id = :sid1 AND tipo = 'Personal'
    ORDER BY numero ASC, id ASC
    LIMIT 3
  ");
  $stP->execute([':sid1'=>$sid]);
  $refs = $stP->fetchAll(PDO::FETCH_ASSOC);

  // 2) Completar a 3 con NO personales (si faltan)
  if (count($refs) < 3) {
    $faltan = 3 - count($refs);
    $stX = $pdo->prepare("
      SELECT id,
             'REFERENCIA' AS fuente,
             TRIM(COALESCE(nombre_completo,'')) AS nombre,
             TRIM(parentesco) AS parentesco,
             numero
      FROM referencias_solicitante
      WHERE solicitud_id = :sid2
        AND (tipo <> 'Personal' OR tipo IS NULL)
      ORDER BY numero ASC, id ASC
      LIMIT {$faltan}
    ");
    $stX->execute([':sid2'=>$sid]);
    $extra = $stX->fetchAll(PDO::FETCH_ASSOC);
    $refs = array_merge($refs, $extra);
  }

  // 3) Codeudores (sin límite)
  $stC = $pdo->prepare("
    SELECT id,
           'CODEUDOR' AS fuente,
           TRIM(CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno)) AS nombre,
           TRIM(parentesco) AS parentesco,
           NULL AS numero
    FROM codeudores
    WHERE solicitud_id = :sid3
    ORDER BY nombre, apellido_paterno, apellido_materno
  ");
  $stC->execute([':sid3'=>$sid]);
  $cods = $stC->fetchAll(PDO::FETCH_ASSOC);

  // 4) Construcción final (SIN deduplicar por nombre)
  $out = [];

  // referencias: máximo 3, orden por numero
  usort($refs, function($a,$b){
    $na = $a['numero'] ?? 9999; $nb = $b['numero'] ?? 9999;
    return $na <=> $nb ?: ($a['id'] <=> $b['id']);
  });
  foreach ($refs as $r) {
    $out[] = [
      'id'         => (int)$r['id'],
      'fuente'     => 'REFERENCIA',
      'nombre'     => norm($r['nombre'] ?? ''),
      'parentesco' => $r['parentesco'] ?? null,
      'numero'     => isset($r['numero']) ? (int)$r['numero'] : null,
    ];
  }

  // codeudores: todos
  foreach ($cods as $c) {
    $out[] = [
      'id'         => (int)$c['id'],
      'fuente'     => 'CODEUDOR',
      'nombre'     => norm($c['nombre'] ?? ''),
      'parentesco' => $c['parentesco'] ?? null,
      'numero'     => null,
    ];
  }

  jexit(200, ['ok'=>true, 'beneficiarios'=>$out]);

} catch (Throwable $e) {
  jexit(500, ['ok'=>false,'error'=>$e->getMessage()]);
}
