<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../../db/conexion.php';

try {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);

    $id = (int)($data['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ID inválido']);
        exit;
    }

    // Verificar que exista
    $chk = $pdo->prepare("SELECT id, estado FROM ahorro WHERE id = ? LIMIT 1");
    $chk->execute([$id]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Registro no encontrado']);
        exit;
    }

    // Si ya está cancelado, no volver a procesar
    if (strtolower((string)($row['estado'] ?? '')) === 'cancelado') {
        echo json_encode([
            'ok' => true,
            'message' => 'El registro ya estaba cancelado'
        ]);
        exit;
    }

    // Borrado lógico
    $sql = "
        UPDATE ahorro
        SET estado = 'cancelado',
            actualizado_en = NOW()
        WHERE id = ?
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    echo json_encode([
        'ok' => true,
        'message' => 'Registro cancelado correctamente'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}