<?php
// public/app/controllers/caratula/guardar_meta.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new Exception('Método inválido');
  }

  require_once __DIR__ . '/../../db/conexion.php'; // expone $pdo (PDO)

  if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('Sin conexión PDO');
  }

  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // -------- Helpers --------
  $toNullIfEmpty = function ($v) {
    if (!isset($v)) return null;
    $v = is_string($v) ? trim($v) : $v;
    return ($v === '' ? null : $v);
  };

  $parseMoney = function ($s) {
    if (!isset($s)) return null;
    $s = trim((string)$s);
    if ($s === '') return null;

    $n = preg_replace('/[^\d.]/', '', $s);

    if ($n === '' || !is_numeric($n)) return null;

    return (float)$n;
  };

  // -------- Inputs ----------
  $sid = (int)($_POST['solicitud_id'] ?? 0);

  if ($sid <= 0) {
    throw new Exception('solicitud_id requerido');
  }

  // ✅ NUEVO: estados de checkboxes
  $aplicar_zona = isset($_POST['aplicar_zona']) ? (int)$_POST['aplicar_zona'] : 0;
  $incluir_seguro = isset($_POST['incluir_seguro']) ? (int)$_POST['incluir_seguro'] : 0;

  $aplicar_zona = $aplicar_zona === 1 ? 1 : 0;
  $incluir_seguro = $incluir_seguro === 1 ? 1 : 0;

  // zona
  $zona_id_raw = $_POST['zona_id'] ?? null;
  $zona_id = ($zona_id_raw === '' || $zona_id_raw === null) ? null : (int)$zona_id_raw;

  $zona_label = $toNullIfEmpty($_POST['zona_label'] ?? '');

  // Si no aplica zona, guardamos zona limpia
  if ($aplicar_zona === 0) {
    $zona_id = null;
    $zona_label = null;
  }

  // fecha_base
  $fecha_base = $toNullIfEmpty($_POST['fecha_base'] ?? '');

  if ($fecha_base !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_base)) {
    throw new Exception('fecha_base inválida (usa YYYY-MM-DD)');
  }

  // fecha_limite_pago
  $fecha_limite_pago = $toNullIfEmpty($_POST['fecha_limite_pago'] ?? '');

  if ($fecha_limite_pago !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_limite_pago)) {
    throw new Exception('fecha_limite_pago inválida (usa YYYY-MM-DD)');
  }

  // Firma opcional en DataURL
  $firma_blob = null;
  $firma_dataurl = $_POST['firma_png'] ?? '';

  if ($firma_dataurl && preg_match('#^data:image/(png|jpeg);base64,#', $firma_dataurl)) {
    $base64 = substr($firma_dataurl, strpos($firma_dataurl, ',') + 1);
    $decoded = base64_decode($base64, true);

    if ($decoded === false) {
      throw new Exception('Firma inválida');
    }

    if (strlen($decoded) > 20) {
      $firma_blob = $decoded;
    }
  }

  // Montos
  $monto_linea_credito = $parseMoney($_POST['monto_linea_credito'] ?? null);
  $monto_total_pagar = $parseMoney($_POST['monto_total_pagar'] ?? null);

  // -------- UPSERT ----------
  $sql = "
    INSERT INTO caratula_meta (
      solicitud_id,
      zona_id,
      zona_label,
      aplicar_zona,
      incluir_seguro,
      fecha_base,
      fecha_limite_pago,
      firma_blob,
      monto_linea_credito,
      monto_total_pagar,
      created_at,
      updated_at
    )
    VALUES (
      :sid,
      :zid,
      :zl,
      :aplicar_zona,
      :incluir_seguro,
      :fb,
      :flp,
      :fblob,
      :mlc,
      :mtp,
      NOW(),
      NOW()
    )
    ON DUPLICATE KEY UPDATE
      zona_id              = VALUES(zona_id),
      zona_label           = VALUES(zona_label),
      aplicar_zona         = VALUES(aplicar_zona),
      incluir_seguro       = VALUES(incluir_seguro),
      fecha_base           = COALESCE(caratula_meta.fecha_base, VALUES(fecha_base)),
      fecha_limite_pago    = COALESCE(VALUES(fecha_limite_pago), caratula_meta.fecha_limite_pago),
      firma_blob           = COALESCE(VALUES(firma_blob), caratula_meta.firma_blob),
      monto_linea_credito  = COALESCE(VALUES(monto_linea_credito), caratula_meta.monto_linea_credito),
      monto_total_pagar    = COALESCE(VALUES(monto_total_pagar), caratula_meta.monto_total_pagar),
      updated_at           = NOW()
  ";

  $st = $pdo->prepare($sql);

  $st->bindValue(':sid', $sid, PDO::PARAM_INT);

  if ($zona_id === null) {
    $st->bindValue(':zid', null, PDO::PARAM_NULL);
  } else {
    $st->bindValue(':zid', $zona_id, PDO::PARAM_INT);
  }

  if ($zona_label === null) {
    $st->bindValue(':zl', null, PDO::PARAM_NULL);
  } else {
    $st->bindValue(':zl', $zona_label, PDO::PARAM_STR);
  }

  $st->bindValue(':aplicar_zona', $aplicar_zona, PDO::PARAM_INT);
  $st->bindValue(':incluir_seguro', $incluir_seguro, PDO::PARAM_INT);

  if ($fecha_base === null) {
    $st->bindValue(':fb', null, PDO::PARAM_NULL);
  } else {
    $st->bindValue(':fb', $fecha_base, PDO::PARAM_STR);
  }

  if ($fecha_limite_pago === null) {
    $st->bindValue(':flp', null, PDO::PARAM_NULL);
  } else {
    $st->bindValue(':flp', $fecha_limite_pago, PDO::PARAM_STR);
  }

  if ($firma_blob !== null) {
    $st->bindValue(':fblob', $firma_blob, PDO::PARAM_LOB);
  } else {
    $st->bindValue(':fblob', null, PDO::PARAM_NULL);
  }

  if ($monto_linea_credito === null) {
    $st->bindValue(':mlc', null, PDO::PARAM_NULL);
  } else {
    $st->bindValue(':mlc', $monto_linea_credito);
  }

  if ($monto_total_pagar === null) {
    $st->bindValue(':mtp', null, PDO::PARAM_NULL);
  } else {
    $st->bindValue(':mtp', $monto_total_pagar);
  }

  $st->execute();

  echo json_encode([
    'ok' => true,
    'saved' => [
      'solicitud_id'        => $sid,
      'zona_id'             => $zona_id,
      'zona_label'          => $zona_label,
      'aplicar_zona'        => $aplicar_zona,
      'incluir_seguro'      => $incluir_seguro,
      'fecha_base'          => $fecha_base,
      'fecha_limite_pago'   => $fecha_limite_pago,
      'tiene_firma'         => $firma_blob !== null,
      'monto_linea_credito' => $monto_linea_credito,
      'monto_total_pagar'   => $monto_total_pagar,
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(400);

  echo json_encode([
    'ok' => false,
    'message' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}