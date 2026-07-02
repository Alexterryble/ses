<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    require_once __DIR__ . '/../../db/conexion.php'; // Debe crear $conn (PDO)

    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new RuntimeException('Conexión PDO no disponible');
    }

    $debug = isset($_GET['debug']) && $_GET['debug'] == '1';

    // --- Entrada flexible ---
    $sidRaw   = $_GET['solicitud_id'] ?? ($_GET['id'] ?? null);
    $folioRaw = $_GET['folio'] ?? null;

    $sid   = null;
    $folio = null;

    if ($sidRaw !== null && $sidRaw !== '') {
        if (is_numeric($sidRaw)) {
            $sid = (int)$sidRaw;
        } else {
            echo json_encode([
                'ok' => false,
                'message' => 'solicitud_id/id inválido'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if ($folioRaw !== null && trim($folioRaw) !== '') {
        $folio = trim((string)$folioRaw);
    }

    if ($sid === null && ($folio === null || $folio === '')) {
        echo json_encode([
            'ok' => false,
            'message' => 'Falta solicitud_id/id o folio'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --- Búsqueda de la solicitud ---
    $solicitud = null;

    // ✅ Se agregó tasa_mensual
    $cols = '
        id,
        folio,
        monto,
        plazo,
        tasa_mensual,
        frecuencia_pago,
        contrato_modalidad,
        atendido_por,
        fecha_registro
    ';

    // 1) Prioriza por ID si viene explícito
    if ($sid !== null) {
        $stmt = $conn->prepare("SELECT $cols FROM solicitudes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $sid]);
        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // 2) Si no hubo ID o no encontró, intenta por folio exacto
    if ($solicitud === null && $folio !== null && $folio !== '') {
        $stmt = $conn->prepare("SELECT $cols FROM solicitudes WHERE folio = :folio LIMIT 1");
        $stmt->execute([':folio' => $folio]);
        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // 2.b) Si folio es numérico y no encontró, inténtalo como id
        if ($solicitud === null && ctype_digit($folio)) {
            $stmt = $conn->prepare("SELECT $cols FROM solicitudes WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => (int)$folio]);
            $solicitud = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }

    if (!$solicitud) {
        echo json_encode([
            'ok'      => false,
            'message' => 'Solicitud/Folio no encontrado',
            'hint'    => 'Prueba con ?solicitud_id=ID o ?folio=FOLIO exacto',
            'debug'   => $debug ? [
                'sidRaw' => $sidRaw,
                'folioRaw' => $folioRaw
            ] : null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sid = (int)$solicitud['id'];

    // ✅ Fallback por si algún registro viejo viene NULL
    if (!isset($solicitud['tasa_mensual']) || $solicitud['tasa_mensual'] === null || $solicitud['tasa_mensual'] === '') {
        $solicitud['tasa_mensual'] = '10.50';
    }

    // --- Cliente (datos_personales) ---
    $stmt = $conn->prepare("SELECT * FROM datos_personales WHERE solicitud_id = :sid LIMIT 1");
    $stmt->execute([':sid' => $sid]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // --- Asesor ---
    $asesor = null;

    if (!empty($solicitud['atendido_por'])) {
        $asesor = [
            'id'    => null,
            'texto' => $solicitud['atendido_por']
        ];

        $stmtA = $conn->prepare("
            SELECT id_asesor, nombre, rol, email, telefono, rfc, direccion, clabe, cuenta
              FROM asesores
             WHERE TRIM(LOWER(nombre)) = TRIM(LOWER(:nombre))
             LIMIT 1
        ");

        $stmtA->execute([
            ':nombre' => $solicitud['atendido_por']
        ]);

        $fila = $stmtA->fetch(PDO::FETCH_ASSOC);

        if ($fila) {
            $asesor = array_merge($asesor, [
                'id'        => (int)$fila['id_asesor'],
                'nombre'    => $fila['nombre'],
                'rol'       => $fila['rol'],
                'email'     => $fila['email'],
                'telefono'  => $fila['telefono'],
                'rfc'       => $fila['rfc'],
                'direccion' => $fila['direccion'],
                'clabe'     => $fila['clabe'],
                'cuenta'    => $fila['cuenta'],
            ]);
        }
    }

    echo json_encode([
        'ok'        => true,
        'success'   => true,
        'input'     => $debug ? [
            'sidRaw'   => $sidRaw,
            'folioRaw' => $folioRaw
        ] : null,
        'solicitud' => $solicitud,
        'cliente'   => $cliente,
        'asesor'    => $asesor,
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'ok'      => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}