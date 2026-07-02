<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'ok' => false,
            'error' => 'Método no permitido'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once __DIR__ . '/../../db/conexion.php';

    $db = null;
    if (isset($pdo) && $pdo instanceof PDO) {
        $db = $pdo;
    } elseif (isset($conn) && $conn instanceof PDO) {
        $db = $conn;
    }

    if (!$db) {
        throw new Exception('No se encontró conexión PDO');
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        throw new Exception('JSON inválido');
    }

    $id = (int)($data['id'] ?? 0);
    $motivo = trim((string)($data['motivo_cancelacion'] ?? ''));

    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    $canceladoPor = null;

    if (isset($_SESSION['asesor']['id'])) {
        $canceladoPor = (int)$_SESSION['asesor']['id'];
    } elseif (isset($_SESSION['user_id'])) {
        $canceladoPor = (int)$_SESSION['user_id'];
    }

    $sqlCheck = "SELECT id, estatus FROM cipcom WHERE id = :id LIMIT 1";
    $st = $db->prepare($sqlCheck);
    $st->execute([':id' => $id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception('El registro no existe');
    }

    if (($row['estatus'] ?? 'activo') === 'cancelado') {
        throw new Exception('Este registro ya está cancelado');
    }

    $sql = "
        UPDATE cipcom
        SET
            estatus = 'cancelado',
            cancelado_at = NOW(),
            cancelado_por = :cancelado_por,
            motivo_cancelacion = :motivo_cancelacion,
            updated_at = NOW()
        WHERE id = :id
        LIMIT 1
    ";

    $upd = $db->prepare($sql);
    $upd->bindValue(':id', $id, PDO::PARAM_INT);

    if ($canceladoPor === null) {
        $upd->bindValue(':cancelado_por', null, PDO::PARAM_NULL);
    } else {
        $upd->bindValue(':cancelado_por', $canceladoPor, PDO::PARAM_INT);
    }

    if ($motivo === '') {
        $upd->bindValue(':motivo_cancelacion', null, PDO::PARAM_NULL);
    } else {
        $upd->bindValue(':motivo_cancelacion', $motivo, PDO::PARAM_STR);
    }

    $upd->execute();

    echo json_encode([
        'ok' => true,
        'message' => 'Registro cancelado correctamente'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}