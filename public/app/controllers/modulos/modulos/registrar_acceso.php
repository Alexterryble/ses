<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../db/conexion.php';

$asesor = $_SESSION['asesor'] ?? null;

$asesorId = (int)(
    $asesor['id_asesor']
    ?? $asesor['id']
    ?? $_SESSION['asesor_id']
    ?? $_SESSION['user_id']
    ?? 0
);

$input = json_decode(file_get_contents('php://input'), true);

$moduloCodigo = trim((string)($input['modulo_codigo'] ?? ''));
$moduloNombre = trim((string)($input['modulo_nombre'] ?? ''));
$url          = trim((string)($input['url'] ?? ''));

if ($moduloCodigo === '' || $moduloNombre === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos'
    ]);
    exit;
}

try {
    $sql = "
        INSERT INTO modulo_accesos 
            (asesor_id, modulo_codigo, modulo_nombre, url, fecha_acceso)
        VALUES 
            (:asesor_id, :modulo_codigo, :modulo_nombre, :url, NOW())
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':asesor_id'     => $asesorId > 0 ? $asesorId : null,
        ':modulo_codigo' => $moduloCodigo,
        ':modulo_nombre' => $moduloNombre,
        ':url'           => $url,
    ]);

    echo json_encode([
        'success' => true
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}