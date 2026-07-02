<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Usa POST.');
    }

    if (!isset($_FILES['pdf'])) {
        throw new Exception('No se recibió ningún archivo.');
    }

    $file = $_FILES['pdf'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        throw new Exception('Solo se permiten archivos PDF.');
    }

    $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'afore_extract';
    if (!is_dir($tmpDir)) {
        mkdir($tmpDir, 0777, true);
    }

    $safeName = 'pdf_' . uniqid('', true) . '.pdf';
    $pdfPath = $tmpDir . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $pdfPath)) {
        throw new Exception('No se pudo guardar el PDF temporalmente.');
    }

    $pythonScript = __DIR__ . DIRECTORY_SEPARATOR . 'extractor_afore.py';

    if (!file_exists($pythonScript)) {
        @unlink($pdfPath);
        throw new Exception('No se encontró el script extractor_afore.py');
    }

    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $pythonCmd = $isWindows ? 'py -3' : 'python3';

    $cmd = $pythonCmd . ' '
        . escapeshellarg($pythonScript) . ' '
        . escapeshellarg($pdfPath) . ' 2>&1';

    $output = shell_exec($cmd);

    @unlink($pdfPath);

    if ($output === null || trim($output) === '') {
        throw new Exception('Python no devolvió respuesta.');
    }

    $json = json_decode($output, true);

    if (!is_array($json)) {
        throw new Exception("La respuesta de Python no es JSON válido. Salida: " . $output);
    }

    echo json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}