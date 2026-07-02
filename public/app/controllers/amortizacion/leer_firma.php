<?php
// public/app/controllers/amortizacion/leer_firma.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    require_once __DIR__ . '/../../db/conexion.php';

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Conexión PDO no disponible');
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sid = (int)($_GET['solicitud_id'] ?? 0);
    if ($sid <= 0) {
        throw new InvalidArgumentException('solicitud_id requerido');
    }

    // ================== 1) Leer fila de amortizacion (firma) ==================
    $sqlAm = "
        SELECT solicitud_id, fecha_emision, firma_mime, firma_blob
        FROM amortizacion
        WHERE solicitud_id = :sid
        LIMIT 1
    ";
    $stAm = $pdo->prepare($sqlAm);
    $stAm->execute([':sid' => $sid]);
    $rowAm = $stAm->fetch(PDO::FETCH_ASSOC);

    if (!$rowAm) {
        // No hay registro de amortización; no devolvemos firma.
        echo json_encode(['ok' => true, 'amortizacion' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ================== 2) Leer fecha desde firmas_contrato.signed_at ==================
    // Tomamos la firma más reciente para esa solicitud_id.
    // Si quieres filtrar por documento/firmante, descomenta y ajusta:
    //   AND documento = 'contrato'
    //   AND firmante  = 'prestatario'
    $sqlFc = "
        SELECT signed_at
        FROM firmas_contrato
        WHERE solicitud_id = :sid
        ORDER BY signed_at DESC
        LIMIT 1
    ";
    $stFc = $pdo->prepare($sqlFc);
    $stFc->execute([':sid' => $sid]);
    $rowFc = $stFc->fetch(PDO::FETCH_ASSOC);

    $fechaEmision = (string)($rowAm['fecha_emision'] ?? '');

    if ($rowFc && !empty($rowFc['signed_at'])) {
        // signed_at viene tipo 'YYYY-MM-DD HH:MM:SS'
        $dt = new DateTime($rowFc['signed_at']);
        // Usamos solo la parte de fecha como 'YYYY-MM-DD'
        $fechaEmision = $dt->format('Y-m-d');
    }

    // ================== 3) Generar dataURL de la firma ==================
    $dataurl = null;
    if (!empty($rowAm['firma_blob']) && !empty($rowAm['firma_mime'])) {
        $dataurl = 'data:' . $rowAm['firma_mime'] . ';base64,' . base64_encode($rowAm['firma_blob']);
    }

    echo json_encode([
        'ok' => true,
        'amortizacion' => [
            'solicitud_id'  => (int)$rowAm['solicitud_id'],
            // 👇 ahora la fecha_emision sale de firmas_contrato.signed_at (si existe)
            'fecha_emision' => $fechaEmision,
            'tiene_firma'   => !empty($rowAm['firma_blob']),
            'firma_dataurl' => $dataurl,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
