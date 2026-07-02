<?php
// Conexión a la base de datos
require_once __DIR__ . '/../../db/conexion.php'; // Ajusta la ruta si tu archivo de conexión está en otro lugar

header('Content-Type: application/json');

try {
    // Consulta: solo solicitudes iniciadas (con nombre capturado)
    $sql = "SELECT solicitud_id, nombres, apellido_paterno, apellido_materno
            FROM datos_personales
            WHERE nombres IS NOT NULL AND nombres != ''
            ORDER BY solicitud_id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($resultados);
} catch (PDOException $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Error al obtener solicitudes: ' . $e->getMessage()
    ]);
}
?>
