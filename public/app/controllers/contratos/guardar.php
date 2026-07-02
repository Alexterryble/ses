<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../db/conexion.php';

    // Compatibilidad: a veces la conexión viene como $pdo y a veces como $conn
    if (isset($pdo) && $pdo instanceof PDO) {
        $db = $pdo;
    } elseif (isset($conn) && $conn instanceof PDO) {
        $db = $conn;
    } else {
        throw new RuntimeException('Conexión PDO no disponible');
    }

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $inRaw = file_get_contents('php://input');
    $in = json_decode($inRaw, true);

    if (!is_array($in)) {
        throw new InvalidArgumentException('JSON inválido');
    }

    $solicitudId = (int)($in['solicitud_id'] ?? 0);
    if ($solicitudId <= 0) {
        throw new InvalidArgumentException('solicitud_id requerido');
    }

    $firmas  = is_array($in['firmas']  ?? null) ? $in['firmas']  : [];
    $meta    = is_array($in['meta']    ?? null) ? $in['meta']    : [];
    $entrega = is_array($in['entrega'] ?? null) ? $in['entrega'] : [];

    // =========================
    // Helper: dataURL -> binario + mime
    // =========================
    $decodeDataUrl = function (?string $dataUrl): ?array {
        if (!$dataUrl) return null;

        if (!preg_match('#^data:image/(png|jpeg|jpg);base64,#i', $dataUrl, $m)) {
            return null;
        }

        $ext  = strtolower($m[1]);
        $mime = ($ext === 'jpeg' || $ext === 'jpg') ? 'image/jpeg' : 'image/png';

        $commaPos = strpos($dataUrl, ',');
        if ($commaPos === false) return null;

        $b64 = substr($dataUrl, $commaPos + 1);
        $bin = base64_decode($b64, true);

        if ($bin === false) return null;

        return [$bin, $mime];
    };

    // =========================
    // Helper: validar YYYY-MM-DD
    // =========================
    $ymd = function ($s): ?string {
        if (!is_string($s)) return null;
        $s = substr(trim($s), 0, 10);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : null;
    };

    // =========================
    // Normalizar entrega
    // =========================
    $tipo = strtoupper(trim((string)($entrega['tipo'] ?? '')));
    if ($tipo !== '' && !in_array($tipo, ['EFECTIVO', 'CHEQUE', 'TRANSFERENCIA'], true)) {
        $tipo = 'EFECTIVO';
    }

    $banco  = trim((string)($entrega['banco'] ?? ''));
    $cuenta = preg_replace('/\D+/', '', (string)($entrega['cuenta'] ?? ''));

    // =========================
    // Normalizar meta
    // =========================
    $fecha_contrato   = $ymd($meta['fecha_contrato']   ?? null);
    $fecha_entrega    = $ymd($meta['fecha_entrega']    ?? null);
    $fecha_devolucion = $ymd($meta['fecha_devolucion'] ?? null);

    $beneficiario_nombre     = trim((string)($meta['beneficiario'] ?? ''));
    $beneficiario_fuente     = strtoupper(trim((string)($meta['bene_fuente'] ?? '')));
    $beneficiario_numero     = isset($meta['bene_numero']) ? (int)$meta['bene_numero'] : null;
    $beneficiario_celular    = trim((string)($meta['beneficiario_extra']['celular'] ?? ''));
    $beneficiario_email      = trim((string)($meta['beneficiario_extra']['email'] ?? ''));
    $beneficiario_parentesco = trim((string)($meta['beneficiario_extra']['parentesco'] ?? ''));

    if (!in_array($beneficiario_fuente, ['REFERENCIA', 'CODEUDOR', 'OTRO'], true)) {
        $beneficiario_fuente = '';
    }

    $db->beginTransaction();

    // =========================================================
    // 1) ASEGURAR FILA BASE
    //    Esto evita que el UPDATE falle silenciosamente si aún
    //    no existe ningún registro para la solicitud.
    // =========================================================
    $sqlBase = "
        INSERT INTO firmas_contrato
            (solicitud_id, documento, firmante, page, storage, signed_at, signed_ip, user_agent)
        VALUES
            (:sid, 'CONTRATO', 'prestatario', 1, 'db', NOW(), :ip, :ua)
        ON DUPLICATE KEY UPDATE
            solicitud_id = VALUES(solicitud_id)
    ";
    $stmtBase = $db->prepare($sqlBase);
    $stmtBase->execute([
        ':sid' => $solicitudId,
        ':ip'  => $_SERVER['REMOTE_ADDR']     ?? null,
        ':ua'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);

    // =========================================================
    // 2) INSERT / UPDATE DE FIRMAS
    // =========================================================
    $firmasProcesadas = 0;

    if (!empty($firmas)) {
        $sqlFirma = "
            INSERT INTO firmas_contrato
                (solicitud_id, documento, firmante, page, storage, firma_blob, mime, bytes, signed_at, signed_ip, user_agent)
            VALUES
                (:sid, :documento, :firmante, :page, 'db', :blob, :mime, :bytes, NOW(), :ip, :ua)
            ON DUPLICATE KEY UPDATE
                firma_blob = VALUES(firma_blob),
                mime       = VALUES(mime),
                bytes      = VALUES(bytes),
                signed_at  = NOW(),
                signed_ip  = VALUES(signed_ip),
                user_agent = VALUES(user_agent)
        ";
        $stmtFirma = $db->prepare($sqlFirma);

        foreach ($firmas as $f) {
            $documento = strtoupper(trim((string)($f['documento'] ?? 'CONTRATO')));
            $firmante  = trim((string)($f['firmante'] ?? ''));
            $page      = (int)($f['page'] ?? 1);
            $dataBase64= (string)($f['data_base64'] ?? '');

            if ($firmante === '') {
                continue;
            }

            if ($page <= 0) {
                $page = 1;
            }

            $decoded = $decodeDataUrl($dataBase64);
            if (!$decoded) {
                continue;
            }

            [$bin, $mime] = $decoded;

            $stmtFirma->execute([
                ':sid'       => $solicitudId,
                ':documento' => $documento,
                ':firmante'  => $firmante,
                ':page'      => $page,
                ':blob'      => $bin,
                ':mime'      => $mime,
                ':bytes'     => strlen($bin),
                ':ip'        => $_SERVER['REMOTE_ADDR']     ?? null,
                ':ua'        => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);

            $firmasProcesadas++;
        }
    }

    // =========================================================
    // 3) UPDATE DE META Y ENTREGA
    //    Se actualizan TODAS las filas de la misma solicitud
    //    para mantener consistencia.
    // =========================================================
    $sets = [];
    $vals = [
        ':sid' => $solicitudId
    ];

    if ($fecha_contrato !== null) {
        $sets[] = "fecha_contrato = :fc";
        $vals[':fc'] = $fecha_contrato;
    }

    if ($fecha_entrega !== null) {
        $sets[] = "fecha_entrega = :fe";
        $vals[':fe'] = $fecha_entrega;
    }

    if ($fecha_devolucion !== null) {
        $sets[] = "fecha_devolucion = :fd";
        $vals[':fd'] = $fecha_devolucion;
    }

    if ($beneficiario_nombre !== '') {
        $sets[] = "beneficiario_nombre = :bn";
        $vals[':bn'] = $beneficiario_nombre;
    }

    if ($beneficiario_fuente !== '') {
        $sets[] = "beneficiario_fuente = :bf";
        $vals[':bf'] = $beneficiario_fuente;
    }

    if ($beneficiario_numero !== null) {
        $sets[] = "beneficiario_numero = :bnum";
        $vals[':bnum'] = $beneficiario_numero;
    }

    if ($beneficiario_celular !== '') {
        $sets[] = "beneficiario_celular = :bcel";
        $vals[':bcel'] = $beneficiario_celular;
    }

    if ($beneficiario_email !== '') {
        $sets[] = "beneficiario_email = :bmail";
        $vals[':bmail'] = $beneficiario_email;
    }

    if ($beneficiario_parentesco !== '') {
        $sets[] = "beneficiario_parentesco = :bpar";
        $vals[':bpar'] = $beneficiario_parentesco;
    }

    if ($tipo !== '') {
        $sets[] = "entrega_tipo = :et";
        $vals[':et'] = $tipo;

        if ($tipo === 'TRANSFERENCIA') {
            $sets[] = "entrega_banco = :eb";
            $sets[] = "entrega_cuenta = :ec";
            $vals[':eb'] = mb_substr($banco, 0, 80, 'UTF-8');
            $vals[':ec'] = $cuenta;
        } else {
            // Si cambió a efectivo/cheque, limpia datos bancarios
            $sets[] = "entrega_banco = NULL";
            $sets[] = "entrega_cuenta = NULL";
        }
    }

    $filasActualizadas = 0;

    if (!empty($sets)) {
        $sqlUpd = "UPDATE firmas_contrato SET " . implode(', ', $sets) . " WHERE solicitud_id = :sid";
        $stmtUpd = $db->prepare($sqlUpd);
        $stmtUpd->execute($vals);
        $filasActualizadas = $stmtUpd->rowCount();
    }

    $db->commit();

    echo json_encode([
        'ok' => true,
        'solicitud_id' => $solicitudId,
        'firmas_recibidas' => count($firmas),
        'firmas_procesadas' => $firmasProcesadas,
        'meta_actualizada' => !empty($sets),
        'filas_actualizadas' => $filasActualizadas
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}