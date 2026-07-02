<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../../db/conexion.php';

header('Content-Type: application/json; charset=utf-8');

$solicitud_id = $_GET['solicitud_id'] ?? '';

if ($solicitud_id === '') {
  echo json_encode([
    'success' => false,
    'message' => 'Falta solicitud_id'
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

function tablaExiste(PDO $pdo, string $tabla): bool {
  try {
    $stmt = $pdo->prepare("
      SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?
    ");
    $stmt->execute([$tabla]);
    return (int)$stmt->fetchColumn() > 0;
  } catch (Throwable $e) {
    return false;
  }
}

function existeRegistro(PDO $pdo, string $tabla, string $columna, string $solicitud_id): bool {
  try {
    if (!tablaExiste($pdo, $tabla)) return false;

    $sql = "SELECT COUNT(*) FROM `$tabla` WHERE `$columna` = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$solicitud_id]);

    return (int)$stmt->fetchColumn() > 0;
  } catch (Throwable $e) {
    return false;
  }
}

function existeRegistroWhere(PDO $pdo, string $tabla, string $where, array $params): bool {
  try {
    if (!tablaExiste($pdo, $tabla)) return false;

    $sql = "SELECT COUNT(*) FROM `$tabla` WHERE $where";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int)$stmt->fetchColumn() > 0;
  } catch (Throwable $e) {
    return false;
  }
}

try {

  $completados = [
    // ✅ Tablas que sí vimos en tus capturas
    'pagare' => existeRegistro($pdo, 'pagares', 'solicitud_id', $solicitud_id),

    'contrato' => existeRegistroWhere(
      $pdo,
      'firmas_contrato',
      'solicitud_id = ? AND documento = ?',
      [$solicitud_id, 'contrato']
    ),

    'tabla_amortizacion' => existeRegistro(
      $pdo,
      'amortizacion',
      'solicitud_id',
      $solicitud_id
    ),

    'caratula' => existeRegistro(
      $pdo,
      'caratula_meta',
      'solicitud_id',
      $solicitud_id
    ),

    // Por ahora quedan en false hasta que sepamos sus tablas reales
    'aviso_privacidad' => false,
    'poliza_seguro' => false,
    'ine' => false,
    'verif_domicilio' => false,
    'verif_referencias' => false,
    'solicitud_manual' => false,
    'retroactivo_40' => false,
    'autorizacion_credito' => false,
  ];

  echo json_encode([
    'success' => true,
    'solicitud_id' => $solicitud_id,
    'completados' => $completados
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Error consultando documentos generados',
    'error' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}