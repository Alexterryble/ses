<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

function toFloat($v): ?float {
  if ($v === null) return null;
  $s = trim((string)$v);
  if ($s === '') return null;

  $s = str_replace(['$', ' '], '', $s);
  $s = str_replace(',', '', $s);
  $s = preg_replace('/[^0-9.\-]/', '', $s);
  if ($s === '' || $s === '-' || $s === '.' || $s === '-.') return null;

  return (float)$s;
}

try {
  require_once __DIR__ . '/../../db/conexion.php';

  $db = (isset($pdo) && $pdo instanceof PDO) ? $pdo
      : ((isset($conn) && $conn instanceof PDO) ? $conn : null);

  if (!$db) throw new Exception('Sin conexión PDO');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Método no permitido']);
    exit;
  }

  $raw = file_get_contents('php://input') ?: '';
  $data = json_decode($raw, true);
  if (!is_array($data)) throw new Exception('JSON inválido');

  $nombre = trim((string)($data['nombre_completo'] ?? ''));
  $rfc    = strtoupper(trim((string)($data['rfc'] ?? '')));
  $benef  = trim((string)($data['beneficiario'] ?? ''));

  $firma  = trim((string)($data['firma_contrato'] ?? ''));
  $ingCap = toFloat($data['ingreso_capital'] ?? null);
  $aport  = toFloat($data['aportacion_mensual'] ?? null);
  $pens   = toFloat($data['pension_base'] ?? null);
  $rate   = toFloat($data['rendimiento_rate'] ?? null);

  $resR   = toFloat($data['resumen_rendimiento'] ?? null);
  $resC   = toFloat($data['resumen_capital'] ?? null);
  $resT   = toFloat($data['resumen_total'] ?? null);

  $tablaA = $data['tabla_aportaciones'] ?? null;
  $tablaI = $data['tabla_imss'] ?? null;

  // Validaciones mínimas
  if ($nombre === '' || mb_strlen($nombre) < 3) throw new Exception('Nombre inválido');
  if ($benef === ''  || mb_strlen($benef) < 3) throw new Exception('Beneficiario inválido');

  $rfc = preg_replace('/\s+/', '', $rfc);
  $moral  = '/^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/';
  $fisica = '/^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/';
  if ($rfc === '' || !(preg_match($moral, $rfc) || preg_match($fisica, $rfc))) {
    throw new Exception('RFC inválido');
  }

  // ✅ ahora es asesor_id (no user_id)
  $asesorId = $_SESSION['asesor']['id_asesor']
           ?? $_SESSION['asesor_id']
           ?? null;
  if ($asesorId !== null) $asesorId = (int)$asesorId;

  // Guardar tablas como JSON string (si vienen)
  $tablaAJson = is_array($tablaA) ? json_encode($tablaA, JSON_UNESCAPED_UNICODE) : (is_string($tablaA) ? $tablaA : null);
  $tablaIJson = is_array($tablaI) ? json_encode($tablaI, JSON_UNESCAPED_UNICODE) : (is_string($tablaI) ? $tablaI : null);

  $sql = "
    INSERT INTO cipcom
      (asesor_id, nombre_completo, rfc, beneficiario, firma_contrato,
       ingreso_capital, aportacion_mensual, pension_base, rendimiento_rate,
       resumen_rendimiento, resumen_capital, resumen_total,
       tabla_aportaciones, tabla_imss)
    VALUES
      (:asesor_id, :nombre, :rfc, :benef, :firma,
       :ingcap, :aport, :pens, :rate,
       :resr, :resc, :rest,
       :tablaA, :tablaI)
    ON DUPLICATE KEY UPDATE
      nombre_completo = VALUES(nombre_completo),
      beneficiario    = VALUES(beneficiario),
      firma_contrato  = VALUES(firma_contrato),
      ingreso_capital = VALUES(ingreso_capital),
      aportacion_mensual = VALUES(aportacion_mensual),
      pension_base    = VALUES(pension_base),
      rendimiento_rate = VALUES(rendimiento_rate),
      resumen_rendimiento = VALUES(resumen_rendimiento),
      resumen_capital = VALUES(resumen_capital),
      resumen_total   = VALUES(resumen_total),
      tabla_aportaciones = VALUES(tabla_aportaciones),
      tabla_imss      = VALUES(tabla_imss),
      updated_at      = CURRENT_TIMESTAMP
  ";

  $st = $db->prepare($sql);
  $st->execute([
    ':asesor_id' => $asesorId,
    ':nombre'  => $nombre,
    ':rfc'     => $rfc,
    ':benef'   => $benef,
    ':firma'   => ($firma === '' || $firma === '—') ? null : $firma,

    ':ingcap'  => $ingCap,
    ':aport'   => $aport,
    ':pens'    => $pens,
    ':rate'    => $rate,

    ':resr'    => $resR,
    ':resc'    => $resC,
    ':rest'    => $resT,

    ':tablaA'  => $tablaAJson,
    ':tablaI'  => $tablaIJson,
  ]);

  echo json_encode(['ok'=>true]);

} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
