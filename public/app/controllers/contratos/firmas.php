<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db/conexion.php';

function jexit(int $code, array $payload){
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

// ===== Helpers =====
function norm_label(?string $s): string {
  $s = trim((string)$s);
  $s = preg_replace('~\s+~',' ', $s);
  return mb_strtoupper($s, 'UTF-8');
}
// Reemplaza tu norm_bene_fuente por esta:
function norm_bene_fuente(?string $f): ?string {
  $f = strtoupper(trim((string)$f));
  return in_array($f, ['REFERENCIA','CODEUDOR'], true) ? $f : null;
}

function only_digits($s){ return preg_replace('/\D+/', '', (string)$s); }
function clabe_valida($clabe): bool {
  $s = only_digits($clabe);
  if (strlen($s) !== 18) return false;
  $w = [3,7,1]; $sum = 0;
  for($i=0;$i<17;$i++) $sum += ((int)$s[$i] * $w[$i%3]) % 10;
  $dv = (10 - ($sum % 10)) % 10;
  return $dv === (int)$s[17];
}

/* =========================
   GET  => leer firmas (+ entrega)
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $sid = isset($_GET['solicitud_id']) ? (int)$_GET['solicitud_id'] : 0;
  if ($sid <= 0) jexit(422, ['ok'=>false,'error'=>'Falta solicitud_id']);

  try {
    $q = $pdo->prepare("
      SELECT
        documento, firmante, page, mime,
        CASE WHEN firma_blob IS NOT NULL THEN TO_BASE64(firma_blob) ELSE NULL END AS b64,
        bytes, signed_at, user_agent,
        -- metas
        fecha_contrato, fecha_entrega, fecha_devolucion,
        beneficiario_nombre, beneficiario_fuente, beneficiario_numero,
        beneficiario_celular, beneficiario_email, beneficiario_parentesco,
        -- entrega
        entrega_tipo, entrega_banco, entrega_cuenta
      FROM firmas_contrato
      WHERE solicitud_id = ?
      ORDER BY documento, firmante, page
    ");
    $q->execute([$sid]);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
      $r['documento_norm'] = norm_label($r['documento'] ?? 'CONTRATO');
      $r['firmante_norm']  = norm_label($r['firmante']  ?? 'PRESTATARIO');
    }

    jexit(200, ['ok'=>true, 'firmas'=>$rows]);

  } catch (Throwable $e) {
    jexit(500, ['ok'=>false, 'error'=>$e->getMessage()]);
  }
}

/* =========================
   POST => guardar / upsert (+ entrega)
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $raw = file_get_contents('php://input');
  $in  = json_decode($raw, true);
  if (!is_array($in)) jexit(400, ['ok'=>false,'error'=>'JSON inválido']);

  $sid  = isset($in['solicitud_id']) ? (int)$in['solicitud_id'] : 0;
  if ($sid <= 0) jexit(422, ['ok'=>false,'error'=>'Falta solicitud_id']);

  $firmas = (isset($in['firmas']) && is_array($in['firmas'])) ? $in['firmas'] : [];
  $mode   = $in['mode'] ?? 'upsert'; // 'upsert' | 'replace_all'

  // ===== META =====
  $meta = is_array($in['meta'] ?? null) ? $in['meta'] : [];
  $fc = $meta['fecha_contrato']   ?? null;
  $fe = $meta['fecha_entrega']    ?? null;
  $fd = $meta['fecha_devolucion'] ?? null;

  $beneNombre = isset($meta['beneficiario']) ? trim((string)$meta['beneficiario']) : null;
  $beneFuente = norm_bene_fuente($meta['bene_fuente'] ?? null);
  $beneNumero = (isset($meta['bene_numero']) && $meta['bene_numero'] !== '') ? (int)$meta['bene_numero'] : null;

  $extra    = is_array($meta['beneficiario_extra'] ?? null) ? $meta['beneficiario_extra'] : [];
  $beneCel  = isset($extra['celular'])    ? trim((string)$extra['celular'])    : null;
  $beneMail = isset($extra['email'])      ? trim((string)$extra['email'])      : null;
  $benePar  = isset($extra['parentesco']) ? trim((string)$extra['parentesco']) : null;

  // ===== ENTREGA =====
  $ent = is_array($in['entrega'] ?? null) ? $in['entrega'] : [];
  $entTipo   = strtoupper(trim((string)($ent['tipo'] ?? '')));
  $entBanco  = trim((string)($ent['banco'] ?? ''));
  $entCuenta = only_digits($ent['cuenta'] ?? '');
  if ($entTipo === '')  $entTipo = null;
  if ($entBanco === '') $entBanco = null;
  if ($entCuenta === '') $entCuenta = null;

  // Validación ligera de cuenta/CLABE si se envía
  if ($entCuenta !== null) {
    $len = strlen($entCuenta);
    if ($len < 16 || $len > 19) jexit(422, ['ok'=>false, 'error'=>'La cuenta/CLABE debe tener entre 16 y 19 dígitos.']);
    if ($len === 18 && !clabe_valida($entCuenta)) jexit(422, ['ok'=>false, 'error'=>'CLABE no válida (dígito verificador).']);
  }

  try {
    $pdo->beginTransaction();

    if ($mode === 'replace_all') {
      $pdo->prepare("DELETE FROM firmas_contrato WHERE solicitud_id = ?")->execute([$sid]);
    }

    // --- Upsert de las imágenes/base64 ---
    if (!empty($firmas) || $mode === 'replace_all') {
      $sqlUp = "
        INSERT INTO firmas_contrato (
          solicitud_id, documento, firmante, page,
          storage, firma_blob, s3_key, mime, bytes,
          signed_at, signed_ip, user_agent, firma_sha256
        ) VALUES (
          :sid, :documento, :firmante, :page,
          :storage, :blob, :s3_key, :mime, :bytes,
          :signed_at, :signed_ip, :user_agent, :sha
        )
        ON DUPLICATE KEY UPDATE
          storage      = VALUES(storage),
          firma_blob   = VALUES(firma_blob),
          s3_key       = VALUES(s3_key),
          mime         = VALUES(mime),
          bytes        = VALUES(bytes),
          signed_ip    = VALUES(signed_ip),
          user_agent   = VALUES(user_agent),
          firma_sha256 = VALUES(firma_sha256),
          signed_at    = signed_at  -- conserva la primera fecha
      ";
      $ins = $pdo->prepare($sqlUp);

      foreach ($firmas as $f) {
        $documento = norm_label($f['documento'] ?? 'CONTRATO');
        $firmante  = norm_label($f['firmante']  ?? 'PRESTATARIO');
        $page      = isset($f['page']) ? (int)$f['page'] : 1;

        $storage   = ($f['storage'] ?? 'db') === 's3' ? 's3' : 'db';
        $mime      = $f['mime'] ?? 'image/png';
        $userAgent = $f['user_agent'] ?? null;
        $signedAt  = $f['signed_at']  ?? date('Y-m-d H:i:s');
        $signedIp  = null;

        $blob  = null; $bytes = null; $s3key = null; $sha = null;

        if ($storage === 'db') {
          $b64 = $f['data_base64'] ?? '';
          if (!$b64) throw new Exception('Falta data_base64 en firma');
          if (strpos($b64, 'base64,') !== false) $b64 = substr($b64, strpos($b64, 'base64,') + 7);
          $bin = base64_decode($b64, true);
          if ($bin === false) throw new Exception('Firma base64 inválida');
          $blob  = $bin;
          $bytes = strlen($bin);
          $sha   = hash('sha256', $bin);
        } else {
          $s3key = $f['s3_key'] ?? null;
          if (!$s3key) throw new Exception('Falta s3_key en firma (storage=s3)');
          $bytes = isset($f['bytes']) ? (int)$f['bytes'] : null;
          $sha   = $f['sha256'] ?? null;
        }

        $ins->bindValue(':sid',       $sid, PDO::PARAM_INT);
        $ins->bindValue(':documento', $documento);
        $ins->bindValue(':firmante',  $firmante);
        $ins->bindValue(':page',      $page, PDO::PARAM_INT);
        $ins->bindValue(':storage',   $storage);
        $ins->bindValue(':blob',      $blob,  $blob === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
        $ins->bindValue(':s3_key',    $s3key, $s3key === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $ins->bindValue(':mime',      $mime);
        $ins->bindValue(':bytes',     $bytes === null ? null : $bytes, $bytes === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $ins->bindValue(':signed_at', $signedAt);
        $ins->bindValue(':signed_ip', $signedIp, PDO::PARAM_NULL);
        $ins->bindValue(':user_agent',$userAgent);
        $ins->bindValue(':sha',       $sha);
        $ins->execute();
      }
    }

    // --- Meta (fechas/beneficiario) + Entrega (tipo/banco/cuenta) en la fila principal ---
    if ($fc || $fe || $fd || $beneNombre || $beneFuente || $beneNumero !== null || $beneCel || $beneMail || $benePar
        || $entTipo !== null || $entBanco !== null || $entCuenta !== null) {

      $upd = $pdo->prepare("
        UPDATE firmas_contrato
           SET fecha_contrato          = COALESCE(fecha_contrato, :fc),
               fecha_entrega           = :fe,
               fecha_devolucion        = :fd,
               beneficiario_nombre     = :bene_nombre,
               beneficiario_fuente     = :bene_fuente,
               beneficiario_numero     = :bene_numero,
               beneficiario_celular    = :bene_cel,
               beneficiario_email      = :bene_mail,
               beneficiario_parentesco = :bene_par,
               entrega_tipo            = :ent_tipo,
               entrega_banco           = :ent_banco,
               entrega_cuenta          = :ent_cuenta
         WHERE solicitud_id = :sid
           AND documento = 'CONTRATO'
           AND firmante  = 'PRESTATARIO'
           AND page = 1
      ");
      $upd->execute([
        ':sid'         => $sid,
        ':fc'          => $fc,
        ':fe'          => $fe,
        ':fd'          => $fd,
        ':bene_nombre' => $beneNombre,
        ':bene_fuente' => $beneFuente,
        ':bene_numero' => $beneNumero,
        ':bene_cel'    => $beneCel,
        ':bene_mail'   => $beneMail,
        ':bene_par'    => $benePar,
        ':ent_tipo'    => $entTipo,
        ':ent_banco'   => $entBanco,
        ':ent_cuenta'  => $entCuenta,
      ]);

      if ($upd->rowCount() === 0) {
        $insMeta = $pdo->prepare("
          INSERT INTO firmas_contrato (
            solicitud_id, documento, firmante, page,
            storage, firma_blob, s3_key, mime, bytes,
            signed_at, signed_ip, user_agent, firma_sha256,
            fecha_contrato, fecha_entrega, fecha_devolucion,
            beneficiario_nombre, beneficiario_fuente, beneficiario_numero,
            beneficiario_celular, beneficiario_email, beneficiario_parentesco,
            entrega_tipo, entrega_banco, entrega_cuenta
          ) VALUES (
            :sid, 'CONTRATO', 'PRESTATARIO', 1,
            'db', NULL, NULL, 'image/png', NULL,
            :signed_at, NULL, NULL, NULL,
            :fc, :fe, :fd,
            :bene_nombre, :bene_fuente, :bene_numero,
            :bene_cel, :bene_mail, :bene_par,
            :ent_tipo, :ent_banco, :ent_cuenta
          )
          ON DUPLICATE KEY UPDATE
            fecha_contrato          = COALESCE(firmas_contrato.fecha_contrato, VALUES(fecha_contrato)),
            fecha_entrega           = VALUES(fecha_entrega),
            fecha_devolucion        = VALUES(fecha_devolucion),
            beneficiario_nombre     = VALUES(beneficiario_nombre),
            beneficiario_fuente     = VALUES(beneficiario_fuente),
            beneficiario_numero     = VALUES(beneficiario_numero),
            beneficiario_celular    = VALUES(beneficiario_celular),
            beneficiario_email      = VALUES(beneficiario_email),
            beneficiario_parentesco = VALUES(beneficiario_parentesco),
            entrega_tipo            = VALUES(entrega_tipo),
            entrega_banco           = VALUES(entrega_banco),
            entrega_cuenta          = VALUES(entrega_cuenta)
        ");
        $insMeta->execute([
          ':sid'         => $sid,
          ':signed_at'   => date('Y-m-d H:i:s'),
          ':fc'          => $fc,
          ':fe'          => $fe,
          ':fd'          => $fd,
          ':bene_nombre' => $beneNombre,
          ':bene_fuente' => $beneFuente,
          ':bene_numero' => $beneNumero,
          ':bene_cel'    => $beneCel,
          ':bene_mail'   => $beneMail,
          ':bene_par'    => $benePar,
          ':ent_tipo'    => $entTipo,
          ':ent_banco'   => $entBanco,
          ':ent_cuenta'  => $entCuenta,
        ]);
      }
    }

    $pdo->commit();
    jexit(200, ['ok'=>true, 'solicitud_id'=>$sid, 'guardadas'=>count($firmas)]);

  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jexit(500, ['ok'=>false, 'error'=>$e->getMessage()]);
  }
}

/* Otro verbo no soportado */
jexit(405, ['ok'=>false,'error'=>'Método no permitido']);
