<?php
// public/app/controllers/inversion/guardar_inversion.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../../db/conexion.php';

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('PDO no disponible');
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ================= ENTRADA JSON O POST =================
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    $isJSON = stripos($ct, 'application/json') !== false;

    $in = $isJSON
        ? (json_decode(file_get_contents('php://input'), true) ?? [])
        : $_POST;

    $asesorId = (int)($_SESSION['asesor_id'] ?? $_SESSION['user_id'] ?? 0);
    if ($asesorId <= 0) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Si viene id/inversion_id -> UPDATE, si no -> INSERT
    $idInversion = (int)($in['id'] ?? $in['inversion_id'] ?? 0);

    // ================= CAMPOS TITULAR =================
    $nombre      = trim((string)($in['nombre']      ?? ''));
    $ap_paterno  = trim((string)($in['ap_paterno']  ?? ''));
    $ap_materno  = trim((string)($in['ap_materno']  ?? ''));
    $rfc         = trim((string)($in['rfc']         ?? ''));
    $cp          = trim((string)($in['cp']          ?? ''));
    $direccion   = trim((string)($in['direccion']   ?? ''));
    $telefono    = trim((string)($in['telefono']    ?? ''));
    $correo      = trim((string)($in['correo']      ?? ''));
    $monto       = (float)($in['monto']             ?? 0);
    $plazo = (float)($in['plazo'] ?? 0);  // ✅ 0.5, 1, 2, 5

    // ✅ forma de pago (spei | cheque | efectivo)
    $formaPago   = strtolower(trim((string)($in['forma_pago'] ?? '')));

    // ================= BENEFICIARIO =================
    $benefNombre     = trim((string)($in['beneficiario_nombre'] ?? ''));
    $benefCurp       = strtoupper(trim((string)($in['beneficiario_curp'] ?? '')));
    $benefTelefono   = trim((string)($in['beneficiario_telefono'] ?? ''));
    $benefParentesco = trim((string)($in['beneficiario_parentesco'] ?? ''));

    // ================= VALIDACIONES =================
    if ($nombre === '' || $ap_paterno === '' || $monto <= 0 || $plazo <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Faltan datos obligatorios'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ✅ Validación plazo (1,2,5)
// ✅ Validación plazo (0.5, 1, 2, 5)
if (!in_array($plazo, [0.5, 1.0, 2.0, 5.0], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Plazo inválido. Solo 6 meses, 1, 2 o 5 años.'], JSON_UNESCAPED_UNICODE);
    exit;
}

    // ✅ Validación forma de pago (si viene)
    if ($formaPago !== '' && !in_array($formaPago, ['spei','cheque','efectivo'], true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Forma de pago inválida'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ✅ Validación básica CURP beneficiario (si viene)


    // ================= FECHAS BASE =================
    $tz   = new DateTimeZone('America/Mexico_City');
    $hoy  = new DateTime('now', $tz);

$fechaSolicitudNueva = $hoy->format('Y-m-d H:i:s');

if ($plazo == 0.5) {
    $fechaDev = (clone $hoy)->modify('+6 months');
} else {
    $fechaDev = (clone $hoy)->modify('+' . (int)$plazo . ' year');
}

$fechaDevolucionNueva = $fechaDev->format('Y-m-d');

    // =========================================================
    //  SI NO HAY ID -> INSERT (nuevo registro)
    // =========================================================
    if ($idInversion <= 0) {

        $sql = "INSERT INTO inversion (
                    asesor_id,
                    nombre, ap_paterno, ap_materno,
                    rfc, codigo_postal, direccion, telefono, correo,
                    monto, plazo_anios, forma_pago,
                    beneficiario_nombre, beneficiario_curp, beneficiario_telefono, beneficiario_parentesco,
                    fecha_solicitud, fecha_devolucion
                ) VALUES (
                    :asesor_id,
                    :nombre, :ap_paterno, :ap_materno,
                    :rfc, :cp, :direccion, :telefono, :correo,
                    :monto, :plazo, :forma_pago,
                    :benef_nombre, :benef_curp, :benef_telefono, :benef_parentesco,
                    :fecha_solicitud, :fecha_devolucion
                )";

        $st = $pdo->prepare($sql);
        $st->execute([
            ':asesor_id'        => $asesorId,
            ':nombre'           => $nombre,
            ':ap_paterno'       => $ap_paterno,
            ':ap_materno'       => $ap_materno,
            ':rfc'              => $rfc,
            ':cp'               => $cp,
            ':direccion'        => $direccion,
            ':telefono'         => $telefono,
            ':correo'           => $correo,
            ':monto'            => $monto,
            ':plazo'            => $plazo,
            ':forma_pago'       => ($formaPago === '' ? null : $formaPago),

            ':benef_nombre'     => ($benefNombre === '' ? null : $benefNombre),
            ':benef_curp'       => ($benefCurp === '' ? null : $benefCurp),
            ':benef_telefono'   => ($benefTelefono === '' ? null : $benefTelefono),
            ':benef_parentesco' => ($benefParentesco === '' ? null : $benefParentesco),

            ':fecha_solicitud'  => $fechaSolicitudNueva,
            ':fecha_devolucion' => $fechaDevolucionNueva,
        ]);

        // ID recién insertado
        $idInversion = (int)$pdo->lastInsertId();

        // ====== Generar y guardar FOLIO POR AÑO ======
        $anio = (int)date('Y');

        $stCount = $pdo->prepare("
          SELECT COUNT(*)
          FROM inversion
          WHERE YEAR(fecha_solicitud) = :anio
        ");
        $stCount->execute([':anio' => $anio]);
        $consecutivo = (int)$stCount->fetchColumn();

        $numero = $consecutivo + 1;

        // CIP-INV2026-0007
        $folio = sprintf('CIP-INV%d-%04d', $anio, $numero);

        $stFolio = $pdo->prepare("
          UPDATE inversion
          SET folio = :folio
          WHERE id = :id
        ");
        $stFolio->execute([
          ':folio' => $folio,
          ':id'    => $idInversion,
        ]);

        echo json_encode([
            'ok'            => true,
            'accion'        => 'insert',
            'inversion_id'  => $idInversion,
            'folio'         => $folio,
            'forma_pago'    => ($formaPago === '' ? null : $formaPago),
            'beneficiario'  => [
                'nombre'     => ($benefNombre === '' ? null : $benefNombre),
                'curp'       => ($benefCurp === '' ? null : $benefCurp),
                'telefono'   => ($benefTelefono === '' ? null : $benefTelefono),
                'parentesco' => ($benefParentesco === '' ? null : $benefParentesco),
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // =========================================================
    //  SI HAY ID -> UPDATE (fechas solo si están NULL, folio intocable)
    // =========================================================

    $sqlSel = "SELECT fecha_solicitud, fecha_devolucion, folio
               FROM inversion
               WHERE id = :id AND asesor_id = :asesor_id
               LIMIT 1";
    $stSel = $pdo->prepare($sqlSel);
    $stSel->execute([
        ':id'        => $idInversion,
        ':asesor_id' => $asesorId,
    ]);
    $row = $stSel->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Inversión no encontrada'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Fechas: solo se llenan si estaban vacías
    $fechaSolicitudFinal = $row['fecha_solicitud'] ?? null;
    if ($fechaSolicitudFinal === null || $fechaSolicitudFinal === '0000-00-00 00:00:00' || $fechaSolicitudFinal === '') {
        $fechaSolicitudFinal = $fechaSolicitudNueva;
    }

    $fechaDevolucionFinal = $row['fecha_devolucion'] ?? null;
    if ($fechaDevolucionFinal === null || $fechaDevolucionFinal === '0000-00-00' || $fechaDevolucionFinal === '') {
        $fechaDevolucionFinal = $fechaDevolucionNueva;
    }

    // FOLIO: jamás se modifica en UPDATE
    $folio = $row['folio'] ?? null;
    if ($folio === null || $folio === '') {
        $folio = sprintf('CIP-INV-%04d', $idInversion); // solo respuesta
    }

    // UPDATE (incluye beneficiario)
    $sqlUpd = "UPDATE inversion
               SET
                 nombre                  = :nombre,
                 ap_paterno              = :ap_paterno,
                 ap_materno              = :ap_materno,
                 rfc                     = :rfc,
                 codigo_postal           = :cp,
                 direccion               = :direccion,
                 telefono                = :telefono,
                 correo                  = :correo,
                 monto                   = :monto,
                 plazo_anios             = :plazo,
                 forma_pago              = :forma_pago,
                 beneficiario_nombre     = :benef_nombre,
                 beneficiario_curp       = :benef_curp,
                 beneficiario_telefono   = :benef_telefono,
                 beneficiario_parentesco = :benef_parentesco,
                 fecha_solicitud         = :fecha_solicitud,
                 fecha_devolucion        = :fecha_devolucion
               WHERE id = :id
                 AND asesor_id = :asesor_id
               LIMIT 1";

    $stUpd = $pdo->prepare($sqlUpd);
    $stUpd->execute([
        ':nombre'           => $nombre,
        ':ap_paterno'       => $ap_paterno,
        ':ap_materno'       => $ap_materno,
        ':rfc'              => $rfc,
        ':cp'               => $cp,
        ':direccion'        => $direccion,
        ':telefono'         => $telefono,
        ':correo'           => $correo,
        ':monto'            => $monto,
        ':plazo'            => $plazo,
        ':forma_pago'       => ($formaPago === '' ? null : $formaPago),

        ':benef_nombre'     => ($benefNombre === '' ? null : $benefNombre),
        ':benef_curp'       => ($benefCurp === '' ? null : $benefCurp),
        ':benef_telefono'   => ($benefTelefono === '' ? null : $benefTelefono),
        ':benef_parentesco' => ($benefParentesco === '' ? null : $benefParentesco),

        ':fecha_solicitud'  => $fechaSolicitudFinal,
        ':fecha_devolucion' => $fechaDevolucionFinal,
        ':id'               => $idInversion,
        ':asesor_id'        => $asesorId,
    ]);

    echo json_encode([
        'ok'           => true,
        'accion'       => 'update',
        'inversion_id' => $idInversion,
        'folio'        => $folio,
        'forma_pago'   => ($formaPago === '' ? null : $formaPago),
        'beneficiario' => [
            'nombre'     => ($benefNombre === '' ? null : $benefNombre),
            'curp'       => ($benefCurp === '' ? null : $benefCurp),
            'telefono'   => ($benefTelefono === '' ? null : $benefTelefono),
            'parentesco' => ($benefParentesco === '' ? null : $benefParentesco),
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('Error guardar_inversion: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor'], JSON_UNESCAPED_UNICODE);
    exit;
}
