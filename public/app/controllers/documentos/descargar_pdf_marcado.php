<?php
// public/app/controllers/documentos/descargar_pdf_marcado.php
declare(strict_types=1);

/* ===== Rutas base ===== */
$ROOT = dirname(__DIR__, 4); // .../Sempiternal_V1
$AUTO = $ROOT . '/vendor/autoload.php';
if (!is_file($AUTO)) {
  http_response_code(500);
  exit("No se encontró autoload de Composer: {$AUTO}");
}
require_once $AUTO;

use setasign\Fpdi\Tcpdf\Fpdi as BaseFPDI;

/** Algunas builds no exponen setXMPMetadata(); inyectamos XMP vía subclase */
#[\AllowDynamicProperties]
class PDF extends BaseFPDI {
  public function injectXmp(string $xml): void { $this->xmp = $xml; }
}

session_start();

/* ===== Resolver usuario desde sesión (acepta varias llaves) ===== */
$asesor = $_SESSION['asesor'] ?? [];

$userId = $_SESSION['user_id']
       ?? $_SESSION['asesor_id']
       ?? $_SESSION['usuario_id']
       ?? ($asesor['id'] ?? $asesor['asesor_id'] ?? $asesor['usuario_id'] ?? null);

$userName = $_SESSION['user_name']
         ?? $_SESSION['asesor_nombre']
         ?? $_SESSION['nombre']
         ?? ($asesor['nombre'] ?? null);

/* Exigir sesión */
if ($userId === null || $userName === null) {
  http_response_code(401);
  exit('Debes iniciar sesión para descargar el documento.');
}

$ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$token = bin2hex(random_bytes(8));
$ahora = (new DateTime('now', new DateTimeZone('America/Mexico_City')))->format('Y-m-d H:i:s');

/* ===== PDF base (debe ser compatible FPDI: PDF 1.4) ===== */
$tipo = $_GET['tipo'] ?? 'solicitud';
$tipo = preg_replace('/[^a-z0-9_]/i', '', (string)$tipo);

$map = [
  'solicitud'        => 'Solicitud_Credito_CIP.pdf',
  'visita_domicilio' => 'Formato de visita.pdf',
  'referencias'      => 'Referencia.pdf',
  'seguro'           => 'SEGURO.pdf',
  'lista'            => 'Lista de verificacion.pdf',
  'requisitos'       => 'Requisitos para Financimiento.pdf',
];

if (!isset($map[$tipo])) {
  http_response_code(400);
  exit('Tipo de plantilla no válido');
}

$src = $ROOT . '/public/formatos/' . $map[$tipo];
if (!is_file($src)) {
  http_response_code(404);
  exit("Plantilla no encontrada: {$src}");
}

/* ===== Títulos por tipo (para metadata) ===== */
$titles = [
  'solicitud'        => 'Solicitud de Crédito',
  'visita_domicilio' => 'Formato de Visita a Domicilio',
  'referencias'      => 'Confirmación Telefónica de Referencias',
  'seguro'           => 'Seguro',
  'lista'            => 'Lista de Verificación',
  'requisitos'       => 'Requisitos para Financimiento',
];
$docTitle = $titles[$tipo] ?? 'Documento';

/* ===== Crear PDF ===== */
$pdf = new PDF();

/* === Quitar header/footer por defecto y evitar páginas en blanco === */
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);

/* ===== Metadatos Info ===== */
$pdf->SetCreator('CIP Financial');
$pdf->SetAuthor('CIP Financial');
$pdf->SetTitle($docTitle);
$pdf->SetSubject("Copia personalizada — {$docTitle}");
$pdf->SetKeywords("user_id={$userId}; token={$token}; name={$userName}; ip={$ip}; tipo={$tipo};");

/* ===== Metadatos XMP (invisibles) ===== */
$template = <<<'XMP'
<x:xmpmeta xmlns:x="adobe:ns:meta/">
  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
           xmlns:pdfx="http://ns.adobe.com/pdfx/1.3/">
    <rdf:Description rdf:about="">
      <pdfx:DownloadUser>%s</pdfx:DownloadUser>
      <pdfx:DownloadName>%s</pdfx:DownloadName>
      <pdfx:DownloadToken>%s</pdfx:DownloadToken>
      <pdfx:DownloadedAt>%s</pdfx:DownloadedAt>
      <pdfx:DownloadIP>%s</pdfx:DownloadIP>
      <pdfx:DownloadType>%s</pdfx:DownloadType>
    </rdf:Description>
  </rdf:RDF>
</x:xmpmeta>
XMP;

$esc = fn($v) => htmlspecialchars((string)$v, ENT_XML1 | ENT_COMPAT, 'UTF-8');
$xmp = sprintf(
  $template,
  $esc($userId),
  $esc($userName),
  $esc($token),
  $esc($ahora),
  $esc($ip),
  $esc($tipo)
);

if (method_exists($pdf, 'setXMPMetadata')) {
  $pdf->setXMPMetadata($xmp);
} else {
  $pdf->injectXmp($xmp);
}

/* ===== Importar y estampar ===== */
try {
  $pageCount = $pdf->setSourceFile($src);
} catch (\Throwable $e) {
  http_response_code(415);
  exit('No se pudo abrir la plantilla PDF. Convierte a PDF 1.4. Detalle: ' . $e->getMessage());
}

/* Texto del pie */
$txt = "Descargado por {$userName} (ID:{$userId}) · {$ahora} · {$token}";

/* ===== Copiar páginas y estampar pie ===== */
for ($i = 1; $i <= $pageCount; $i++) {
  $tplId = $pdf->importPage($i);
  $size  = $pdf->getTemplateSize($tplId);

  $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
  $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height'], false);

  // ✅ Pie en TODAS las páginas
  $pdf->SetFont('helvetica', '', 8);
  $pdf->SetTextColor(120, 120, 120);
  $pdf->SetXY(10, $size['height'] - 9);
  $pdf->Cell($size['width'] - 20, 6, $txt, 0, 0, 'C', false, '', 0, false, 'T', 'M');
}

/* ===== Salida ===== */
$tmp = sys_get_temp_dir() . "/{$tipo}_{$userId}_{$token}.pdf";
$pdf->Output($tmp, 'F');

$downloadName = $map[$tipo] ?? ('documento_' . $tipo . '.pdf');

while (ob_get_level() > 0) { @ob_end_clean(); }
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($tmp));
readfile($tmp);
@unlink($tmp);
