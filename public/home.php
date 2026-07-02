<?php
// /sempiternal/public/home.php
declare(strict_types=1);

// 1) Verificar login
require_once __DIR__ . '/app/controllers/auth/require_login.php';

// 2) Datos del asesor en sesión
$asesor = $_SESSION['asesor'] ?? null;

$asesorId = (int)(
    $asesor['id_asesor']
    ?? $asesor['id']
    ?? $_SESSION['asesor_id']
    ?? $_SESSION['user_id']
    ?? 0
);

$asesorNombre = '';
$asesorRol    = '';

if ($asesor && is_array($asesor)) {
    $asesorNombre = trim(
        ($asesor['nombre'] ?? '') . ' ' .
        ($asesor['apellido_paterno'] ?? '') . ' ' .
        ($asesor['apellido_materno'] ?? '')
    );

    $asesorRol = $asesor['rol'] ?? '';
}

if ($asesorNombre === '') {
    $asesorNombre = 'Asesor';
}

if ($asesorRol === '') {
    $asesorRol = 'Asesor Financiero';
}

/* ==========================================================
   RESUMEN DE SOLICITUDES PARA EL DASHBOARD
   ========================================================== */

$resumenSolicitudes = [
    'total'      => 0,
    'activas'    => 0,
    'no_activas' => 0,
    'validadas'  => 0,
    'pendientes' => 0,
    'canceladas' => 0,
    'rechazadas' => 0,
];

/*
  Ruta correcta de tu conexión:
  http://localhost/sempiternal/public/app/db/conexion.php

  En PHP, como este archivo home.php está en:
  /sempiternal/public/home.php

  Entonces usamos:
  __DIR__ . '/app/db/conexion.php'
*/
$rutaConexion = __DIR__ . '/app/db/conexion.php';

if (file_exists($rutaConexion)) {
    require_once $rutaConexion;
}

try {
    $sqlResumen = "
        SELECT
            COUNT(*) AS total,

            SUM(CASE 
                WHEN TRIM(estado_validacion) IN ('Validado', 'Pendiente') 
                THEN 1 ELSE 0 
            END) AS activas,

            SUM(CASE 
                WHEN TRIM(estado_validacion) IN ('Cancelado', 'Rechazado') 
                THEN 1 ELSE 0 
            END) AS no_activas,

            SUM(CASE 
                WHEN TRIM(estado_validacion) = 'Validado' 
                THEN 1 ELSE 0 
            END) AS validadas,

            SUM(CASE 
                WHEN TRIM(estado_validacion) = 'Pendiente' 
                THEN 1 ELSE 0 
            END) AS pendientes,

            SUM(CASE 
                WHEN TRIM(estado_validacion) = 'Cancelado' 
                THEN 1 ELSE 0 
            END) AS canceladas,

            SUM(CASE 
                WHEN TRIM(estado_validacion) = 'Rechazado' 
                THEN 1 ELSE 0 
            END) AS rechazadas

        FROM solicitudes
        WHERE asesor_id = :asesor_id
    ";

    $rowResumen = null;

    if (isset($pdo) && $pdo instanceof PDO) {
        $stmtResumen = $pdo->prepare($sqlResumen);
        $stmtResumen->execute([
            ':asesor_id' => $asesorId
        ]);
        $rowResumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);

    } elseif (isset($conn) && $conn instanceof PDO) {
        $stmtResumen = $conn->prepare($sqlResumen);
        $stmtResumen->execute([
            ':asesor_id' => $asesorId
        ]);
        $rowResumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);
    }

    if ($rowResumen) {
        $resumenSolicitudes = [
            'total'      => (int)($rowResumen['total'] ?? 0),
            'activas'    => (int)($rowResumen['activas'] ?? 0),
            'no_activas' => (int)($rowResumen['no_activas'] ?? 0),
            'validadas'  => (int)($rowResumen['validadas'] ?? 0),
            'pendientes' => (int)($rowResumen['pendientes'] ?? 0),
            'canceladas' => (int)($rowResumen['canceladas'] ?? 0),
            'rechazadas' => (int)($rowResumen['rechazadas'] ?? 0),
        ];
    }

} catch (Throwable $e) {
    // No rompemos el dashboard si algo falla.
}

/* ==========================================================
   MÓDULOS DEL DASHBOARD
   ========================================================== */

$modulos = [
    [
        'titulo' => 'Préstamo para pensión',
        'descripcion' => 'Gestiona solicitudes de préstamos con garantía de pensión.',
        'url' => 'index.php',
        'icono' => '👤',
        'color' => 'teal',
    ],
    [
        'titulo' => 'Contratos de inversión',
        'descripcion' => 'Administra y consulta tus contratos de inversión vigentes.',
        'url' => 'contratos_inversion.php',
        'icono' => '📄',
        'color' => 'blue',
    ],
    [
        'titulo' => 'Inversión a 10 años',
        'descripcion' => 'Plan de inversión a largo plazo con rendimientos competitivos.',
        'url' => '/app/controllers/complemento/dashboard_cipcom.php',
        'icono' => '📈',
        'color' => 'green',
    ],
    [
        'titulo' => 'Ahorros',
        'descripcion' => 'Consulta y administra tus cuentas de ahorro de tus clientes.',
        'url' => 'dashboard_ahorro.php',
        'icono' => '🐷',
        'color' => 'teal',
    ],
];

if ((int)$asesorId === 7) {
    $modulos[] = [
        'titulo' => 'Seguimientos de producto',
        'descripcion' => 'Consulta el resumen general de productos colocados, métricas, avances y desempeño comercial.',
        'url' => 'https://hp-v1-production.up.railway.app/resumen.php',
        'icono' => '📈',
        'color' => 'green',
    ];
}





/* ==========================================================
   SOLICITUDES POR MES PARA GRÁFICA
   ========================================================== */

$solicitudesMesLabels = [
    'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
    'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'
];

$aniosSolicitudes = [];
$solicitudesPorAnio = [];

try {
    $sqlAnios = "
        SELECT DISTINCT YEAR(fecha_registro) AS anio
        FROM solicitudes
        WHERE fecha_registro IS NOT NULL
        ORDER BY anio DESC
    ";

    $rowsAnios = [];

    if (isset($pdo) && $pdo instanceof PDO) {
        $rowsAnios = $pdo->query($sqlAnios)->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($conn) && $conn instanceof PDO) {
        $rowsAnios = $conn->query($sqlAnios)->fetchAll(PDO::FETCH_ASSOC);
    }

    foreach ($rowsAnios as $rowAnio) {
        $anio = (int)($rowAnio['anio'] ?? 0);

        if ($anio > 0) {
            $aniosSolicitudes[] = $anio;
            $solicitudesPorAnio[$anio] = array_fill(0, 12, 0);
        }
    }

    if (empty($aniosSolicitudes)) {
        $anioActual = (int)date('Y');
        $aniosSolicitudes[] = $anioActual;
        $solicitudesPorAnio[$anioActual] = array_fill(0, 12, 0);
    }

    $sqlMesesPorAnio = "
        SELECT 
            YEAR(fecha_registro) AS anio,
            MONTH(fecha_registro) AS mes,
            COUNT(*) AS total
        FROM solicitudes
        WHERE fecha_registro IS NOT NULL
        GROUP BY YEAR(fecha_registro), MONTH(fecha_registro)
        ORDER BY anio DESC, mes ASC
    ";

    $rowsMesesPorAnio = [];

    if (isset($pdo) && $pdo instanceof PDO) {
        $rowsMesesPorAnio = $pdo->query($sqlMesesPorAnio)->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($conn) && $conn instanceof PDO) {
        $rowsMesesPorAnio = $conn->query($sqlMesesPorAnio)->fetchAll(PDO::FETCH_ASSOC);
    }

    foreach ($rowsMesesPorAnio as $rowMes) {
        $anio = (int)($rowMes['anio'] ?? 0);
        $mesIndex = (int)($rowMes['mes'] ?? 0) - 1;

        if ($anio > 0 && $mesIndex >= 0 && $mesIndex <= 11) {
            if (!isset($solicitudesPorAnio[$anio])) {
                $solicitudesPorAnio[$anio] = array_fill(0, 12, 0);
            }

            $solicitudesPorAnio[$anio][$mesIndex] = (int)($rowMes['total'] ?? 0);
        }
    }

} catch (Throwable $e) {
    // Temporal para pruebas:
    // echo '<pre style="color:white;background:#000;padding:10px;border-radius:10px;">' . $e->getMessage() . '</pre>';

    $anioActual = (int)date('Y');
    $aniosSolicitudes = [$anioActual];
    $solicitudesPorAnio = [
        $anioActual => array_fill(0, 12, 0)
    ];
}


/* ==========================================================
   MÓDULOS MÁS USADOS DESDE HISTORIAL
   ========================================================== */

$modulosUsadosLabels = [];
$modulosUsadosData   = [];

try {
    $sqlModulosUsados = "
        SELECT 
            modulo_nombre,
            COUNT(*) AS total
        FROM modulo_accesos
        GROUP BY modulo_codigo, modulo_nombre
        ORDER BY total DESC
        LIMIT 8
    ";

    $rowsModulosUsados = [];

    if (isset($pdo) && $pdo instanceof PDO) {
        $stmtModulosUsados = $pdo->query($sqlModulosUsados);
        $rowsModulosUsados = $stmtModulosUsados->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($conn) && $conn instanceof PDO) {
        $stmtModulosUsados = $conn->query($sqlModulosUsados);
        $rowsModulosUsados = $stmtModulosUsados->fetchAll(PDO::FETCH_ASSOC);
    }

    foreach ($rowsModulosUsados as $rowModulo) {
        $modulosUsadosLabels[] = (string)($rowModulo['modulo_nombre'] ?? 'Sin nombre');
        $modulosUsadosData[]   = (int)($rowModulo['total'] ?? 0);
    }

    /*
      Si todavía no hay historial, mostramos los módulos en 0
      para que la gráfica no salga vacía.
    */
    if (empty($modulosUsadosLabels)) {
        $modulosUsadosLabels = [
            'Préstamo para pensión',
            'Contratos de inversión',
            'Inversión a 10 años',
            'Ahorros'
        ];

        $modulosUsadosData = [0, 0, 0, 0];
    }

} catch (Throwable $e) {
    $modulosUsadosLabels = [
        'Préstamo para pensión',
        'Contratos de inversión',
        'Inversión a 10 años',
        'Ahorros'
    ];

    $modulosUsadosData = [0, 0, 0, 0];

    // Para pruebas:
    // echo '<pre style="color:white;background:#000;padding:10px;border-radius:10px;">' . $e->getMessage() . '</pre>';
}


/* ==========================================================
   DISTRIBUCIÓN POR TIPO / SECCIÓN
   Solicitudes, Inversiones, Complemento y Ahorro
   ========================================================== */

$distribucionTipoLabels = [
    'Solicitudes',
    'Inversiones',
    'Complemento',
    'Ahorro'
];

$distribucionTipoData = [0, 0, 0, 0];

try {
    $db = null;

    if (isset($pdo) && $pdo instanceof PDO) {
        $db = $pdo;
    } elseif (isset($conn) && $conn instanceof PDO) {
        $db = $conn;
    }

    if ($db instanceof PDO) {

        $stmtSolicitudes = $db->query("SELECT COUNT(*) AS total FROM solicitudes");
        $totalSolicitudes = (int)($stmtSolicitudes->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $stmtInversiones = $db->query("SELECT COUNT(*) AS total FROM inversion");
        $totalInversiones = (int)($stmtInversiones->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $stmtComplemento = $db->query("SELECT COUNT(*) AS total FROM cipcom");
        $totalComplemento = (int)($stmtComplemento->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $stmtAhorro = $db->query("SELECT COUNT(*) AS total FROM ahorro");
        $totalAhorro = (int)($stmtAhorro->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $distribucionTipoData = [
            $totalSolicitudes,
            $totalInversiones,
            $totalComplemento,
            $totalAhorro
        ];
    }

} catch (Throwable $e) {
    $distribucionTipoData = [0, 0, 0, 0];

    // Para pruebas:
    // echo '<pre style="color:white;background:#000;padding:10px;border-radius:10px;">' . $e->getMessage() . '</pre>';
}



/* ==========================================================
   ACTIVIDAD RECIENTE SOLO DEL USUARIO ACTUAL
   ========================================================== */

$actividadReciente = [];

try {
    $db = null;

    if (isset($pdo) && $pdo instanceof PDO) {
        $db = $pdo;
    } elseif (isset($conn) && $conn instanceof PDO) {
        $db = $conn;
    }

    if ($db instanceof PDO && $asesorId > 0) {
        $sqlActividad = "
            (
                SELECT
                    'acceso' AS tipo,
                    CONCAT('Acceso a módulo: ', modulo_nombre) AS titulo,
                    CONCAT('Módulo: ', modulo_nombre) AS detalle,
                    fecha_acceso AS fecha,
                    modulo_nombre AS modulo
                FROM modulo_accesos
                WHERE asesor_id = :asesor_id_acceso
            )

            UNION ALL

            (
                SELECT
                    'solicitud' AS tipo,
                    'Nueva solicitud registrada' AS titulo,
                    CONCAT(
                        'Folio: ', COALESCE(folio, 'Sin folio'),
                        ' / Cliente: ', COALESCE(atendido_por, 'Sin cliente')
                    ) AS detalle,
                    fecha_registro AS fecha,
                    'Solicitudes' AS modulo
                FROM solicitudes
                WHERE asesor_id = :asesor_id_solicitud
            )

            ORDER BY fecha DESC
            LIMIT 5
        ";

        $stmtActividad = $db->prepare($sqlActividad);
        $stmtActividad->execute([
            ':asesor_id_acceso'    => $asesorId,
            ':asesor_id_solicitud' => $asesorId,
        ]);

        $actividadReciente = $stmtActividad->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Throwable $e) {
    $actividadReciente = [];

    // Para pruebas:
    // echo '<pre style=\"color:white;background:#000;padding:10px;border-radius:10px;\">' . $e->getMessage() . '</pre>';
}


function tiempoRelativo(?string $fecha): string
{
    if (!$fecha) {
        return 'Sin fecha';
    }

    try {
        $dt = new DateTime($fecha);
        $now = new DateTime();

        $diff = $now->getTimestamp() - $dt->getTimestamp();

        if ($diff < 60) {
            return 'Hace unos segundos';
        }

        if ($diff < 3600) {
            $min = floor($diff / 60);
            return 'Hace ' . $min . ' min';
        }

        if ($diff < 86400) {
            $horas = floor($diff / 3600);
            return 'Hace ' . $horas . ' h';
        }

        if ($diff < 604800) {
            $dias = floor($diff / 86400);
            return 'Hace ' . $dias . ' d';
        }

        return $dt->format('d/m/Y');
    } catch (Throwable $e) {
        return 'Sin fecha';
    }
}


/* ==========================================================
   GRÁFICA DINÁMICA POR SECCIÓN Y AÑO
   Solicitudes, Inversiones, Complemento y Ahorro
   ========================================================== */

$graficaMesLabels = [
    'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
    'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'
];

$seccionesGrafica = [
    'solicitudes' => [
        'label' => 'Solicitudes',
        'tabla' => 'solicitudes',
        'fecha' => 'fecha_registro',
    ],
    'inversiones' => [
        'label' => 'Inversiones',
        'tabla' => 'inversion',
        'fecha' => 'fecha_solicitud',
    ],
    'complemento' => [
        'label' => 'Complemento',
        'tabla' => 'cipcom',
        'fecha' => 'created_at',
    ],
    'ahorro' => [
        'label' => 'Ahorro',
        'tabla' => 'ahorro',
        'fecha' => 'creado_en',
    ],
];

$graficaPorSeccionAnio = [];
$aniosGrafica = [];

try {
    $db = null;

    if (isset($pdo) && $pdo instanceof PDO) {
        $db = $pdo;
    } elseif (isset($conn) && $conn instanceof PDO) {
        $db = $conn;
    }

    if ($db instanceof PDO) {

        function columnaExiste(PDO $db, string $tabla, string $columna): bool
        {
            $sql = "
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = :tabla
                  AND column_name = :columna
            ";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':tabla' => $tabla,
                ':columna' => $columna,
            ]);

            return (int)$stmt->fetchColumn() > 0;
        }

        function detectarColumnaFecha(PDO $db, string $tabla, ?string $preferida = null): ?string
        {
            if ($preferida && columnaExiste($db, $tabla, $preferida)) {
                return $preferida;
            }

            $posibles = [
                'fecha_registro',
                'fecha_creacion',
                'fecha',
                'created_at',
                'fecha_alta',
                'fecha_captura',
                'fecha_contrato',
                'firma_contrato'
            ];

            foreach ($posibles as $columna) {
                if (columnaExiste($db, $tabla, $columna)) {
                    return $columna;
                }
            }

            return null;
        }

        foreach ($seccionesGrafica as $codigo => $info) {
            $tabla = $info['tabla'];
            $columnaFecha = detectarColumnaFecha($db, $tabla, $info['fecha']);

            $graficaPorSeccionAnio[$codigo] = [
                'label' => $info['label'],
                'fecha' => $columnaFecha,
                'data' => []
            ];

            if (!$columnaFecha) {
                continue;
            }

            $sql = "
                SELECT
                    YEAR(`$columnaFecha`) AS anio,
                    MONTH(`$columnaFecha`) AS mes,
                    COUNT(*) AS total
                FROM `$tabla`
                WHERE `$columnaFecha` IS NOT NULL
                GROUP BY YEAR(`$columnaFecha`), MONTH(`$columnaFecha`)
                ORDER BY anio DESC, mes ASC
            ";

            $stmt = $db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $anio = (int)($row['anio'] ?? 0);
                $mesIndex = (int)($row['mes'] ?? 0) - 1;
                $total = (int)($row['total'] ?? 0);

                if ($anio <= 0 || $mesIndex < 0 || $mesIndex > 11) {
                    continue;
                }

                if (!in_array($anio, $aniosGrafica, true)) {
                    $aniosGrafica[] = $anio;
                }

                if (!isset($graficaPorSeccionAnio[$codigo]['data'][$anio])) {
                    $graficaPorSeccionAnio[$codigo]['data'][$anio] = array_fill(0, 12, 0);
                }

                $graficaPorSeccionAnio[$codigo]['data'][$anio][$mesIndex] = $total;
            }
        }

        rsort($aniosGrafica);

        if (empty($aniosGrafica)) {
            $aniosGrafica[] = (int)date('Y');
        }

        foreach ($graficaPorSeccionAnio as $codigo => $info) {
            foreach ($aniosGrafica as $anio) {
                if (!isset($graficaPorSeccionAnio[$codigo]['data'][$anio])) {
                    $graficaPorSeccionAnio[$codigo]['data'][$anio] = array_fill(0, 12, 0);
                }
            }
        }
    }

} catch (Throwable $e) {
    $aniosGrafica = [(int)date('Y')];

    $graficaPorSeccionAnio = [
        'solicitudes' => [
            'label' => 'Solicitudes',
            'fecha' => 'fecha_registro',
            'data' => [
                (int)date('Y') => array_fill(0, 12, 0)
            ]
        ]
    ];

    // Para pruebas:
    // echo '<pre style="color:white;background:#000;padding:10px;border-radius:10px;">' . $e->getMessage() . '</pre>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel principal | CONSULTORÍA INTEGRAL DE PENSIONES</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Gráficas -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  :root {
    --bg-main: #061426;
    --bg-soft: #081b31;
    --bg-card: rgba(12, 34, 57, 0.92);
    --bg-card-2: rgba(15, 42, 70, 0.96);

    --border-soft: rgba(138, 184, 219, 0.18);
    --border-strong: rgba(78, 210, 224, 0.35);

    --text-main: #f5f8ff;
    --text-soft: #9fb2c8;
    --text-muted: #7589a3;

    --teal: #10c7bd;
    --blue: #1f87ff;
    --green: #70d36b;
    --orange: #ff9f3f;
    --purple: #8d59df;
    --gold: #ffc533;
    --danger: #ff5b6b;

    --shadow-card: 0 18px 45px rgba(0, 0, 0, 0.30);
    --radius-xl: 22px;
    --radius-lg: 18px;
    --radius-md: 14px;
  }

  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  body {
    min-height: 100vh;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    color: var(--text-main);
    background:
      radial-gradient(circle at 50% -10%, rgba(33, 100, 172, 0.35), transparent 42%),
      radial-gradient(circle at 100% 0%, rgba(16, 199, 189, 0.10), transparent 35%),
      linear-gradient(135deg, #04101f 0%, #07172a 48%, #04111f 100%);
    padding: 28px;
  }

  a {
    color: inherit;
    text-decoration: none;
  }

  .page {
    width: 100%;
    max-width: 1680px;
    margin: 0 auto;
  }

  /* ================= HEADER ================= */

  .topbar {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 24px;
    margin-bottom: 26px;
  }

  .brand {
    display: flex;
    align-items: center;
    gap: 16px;
    padding-right: 24px;
    border-right: 1px solid rgba(255,255,255,0.14);
  }

  .brand-mark {
    width: 58px;
    height: 58px;
    display: grid;
    place-items: end center;
    grid-template-columns: repeat(4, 1fr);
    gap: 4px;
  }

  .brand-mark span {
    display: block;
    width: 9px;
    border-radius: 8px 8px 2px 2px;
    background: linear-gradient(180deg, #18e4d3, #1d7dff);
    box-shadow: 0 0 18px rgba(16,199,189,0.35);
  }

  .brand-mark span:nth-child(1) { height: 26px; opacity: .75; }
  .brand-mark span:nth-child(2) { height: 38px; opacity: .9; }
  .brand-mark span:nth-child(3) { height: 48px; }
  .brand-mark span:nth-child(4) { height: 34px; opacity: .7; }

  .brand-text strong {
    display: block;
    font-size: 2.1rem;
    letter-spacing: .18em;
    line-height: 1;
    font-weight: 700;
  }

  .brand-text span {
    display: block;
    color: var(--text-soft);
    font-size: .85rem;
    margin-top: 6px;
  }

  .welcome h1 {
    font-size: clamp(1.7rem, 3vw, 2.4rem);
    line-height: 1.05;
    font-weight: 750;
    letter-spacing: .01em;
  }

  .welcome p {
    margin-top: 8px;
    color: var(--text-soft);
    font-size: .98rem;
  }

  .top-actions {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .user-chip {
    min-width: 220px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 16px;
    background: rgba(12,34,57,0.85);
    border: 1px solid var(--border-soft);
    box-shadow: 0 10px 24px rgba(0,0,0,.15);
  }

  .avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #10c7bd, #2c7df7);
    box-shadow: 0 0 0 5px rgba(16,199,189,0.10);
    font-size: 1.3rem;
  }

  .user-chip strong {
    display: block;
    font-size: .95rem;
  }

  .user-chip span {
    color: var(--text-soft);
    display: block;
    margin-top: 2px;
    font-size: .82rem;
  }

  .logout-btn {
    height: 58px;
    padding: 0 22px;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,.24);
    background: rgba(10, 24, 43, .65);
    color: var(--text-main);
    font-weight: 700;
    transition: .18s ease;
  }

  .logout-btn:hover {
    border-color: rgba(255,91,107,.7);
    background: rgba(255,91,107,.10);
    color: #ffd9dd;
    transform: translateY(-2px);
  }

  /* ================= KPI ================= */

  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 16px;
  }

  .kpi-card {
    position: relative;
    min-height: 120px;
    overflow: hidden;
    border-radius: var(--radius-lg);
    background:
      linear-gradient(135deg, rgba(255,255,255,.035), rgba(255,255,255,.01)),
      var(--bg-card);
    border: 1px solid var(--border-soft);
    box-shadow: var(--shadow-card);
    padding: 22px;
    display: flex;
    align-items: center;
    gap: 20px;
  }

  .kpi-card::after {
    content: "";
    position: absolute;
    inset: auto -30px -60px auto;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: rgba(16,199,189,0.10);
    filter: blur(5px);
  }

  .kpi-icon {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    font-size: 2rem;
    color: white;
    box-shadow: inset 0 -12px 24px rgba(0,0,0,.16);
  }

  .kpi-icon.teal { background: linear-gradient(135deg, #13cfc5, #0d817d); }
  .kpi-icon.blue { background: linear-gradient(135deg, #2697ff, #0956c7); }
  .kpi-icon.green { background: linear-gradient(135deg, #81d976, #2f8844); }
  .kpi-icon.orange { background: linear-gradient(135deg, #ffb456, #e0701f); }
  .kpi-icon.danger { background: linear-gradient(135deg, #ff6b7b, #b6283a); }

  .kpi-info p {
    color: var(--text-main);
    font-size: 1rem;
    margin-bottom: 6px;
  }

  .kpi-info h2 {
    font-size: 2rem;
    line-height: 1;
    letter-spacing: .03em;
  }

  .kpi-info small {
    display: block;
    margin-top: 8px;
    color: var(--text-soft);
  }

  .up {
    color: #78e483;
    font-weight: 800;
  }

  .down {
    color: #ffb05b;
    font-weight: 800;
  }

  /* ================= LAYOUT ================= */

  .charts-grid {
    display: grid;
    grid-template-columns: 1.1fr 1.1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
  }

  .content-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .panel {
    position: relative;
    border-radius: var(--radius-xl);
    background:
      linear-gradient(135deg, rgba(255,255,255,.03), rgba(255,255,255,.012)),
      var(--bg-card);
    border: 1px solid var(--border-soft);
    box-shadow: var(--shadow-card);
    overflow: hidden;
  }

  .panel-header {
    min-height: 54px;
    padding: 18px 22px 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
  }

  .panel-header h2 {
    font-size: 1.08rem;
    font-weight: 750;
    letter-spacing: .01em;
  }

  .select-mini {
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid var(--border-soft);
    background: rgba(14, 37, 62, .9);
    color: var(--text-main);
    font-size: .82rem;
    outline: none;
  }

  .chart-box {
    height: 220px;
    padding: 6px 18px 18px;
  }

  .chart-box canvas {
    width: 100% !important;
    height: 100% !important;
  }

  /* ================= MODULES ================= */

  .modules-panel {
    padding: 0 16px 16px;
  }

  .module-search {
    margin: 0 6px 16px;
    display: flex;
    gap: 12px;
  }

  .module-search input {
    width: 100%;
    height: 46px;
    padding: 0 16px;
    border-radius: 14px;
    border: 1px solid var(--border-soft);
    outline: none;
    background: rgba(6,20,38,.65);
    color: var(--text-main);
    font-size: .95rem;
  }

  .module-search input::placeholder {
    color: var(--text-muted);
  }

  .modules-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 18px;
  }

  .module-card {
    position: relative;
    min-height: 165px;
    padding: 18px;
    border-radius: 18px;
    background: rgba(14, 41, 68, .92);
    border: 1px solid rgba(255,255,255,.09);
    overflow: hidden;
    transition: .18s ease;
    display: flex;
    gap: 14px;
  }

  .module-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at top right, rgba(16,199,189,.14), transparent 55%);
    opacity: 0;
    transition: .18s ease;
    pointer-events: none;
  }

  .module-card:hover {
    transform: translateY(-4px);
    border-color: var(--border-strong);
    background: rgba(18, 52, 84, .98);
    box-shadow: 0 18px 32px rgba(0,0,0,.22);
  }

  .module-card:hover::before {
    opacity: 1;
  }

  .module-icon {
    position: relative;
    z-index: 1;
    width: 58px;
    height: 58px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    border-radius: 15px;
    font-size: 1.6rem;
  }

  .module-icon.teal { background: linear-gradient(135deg, #12cfc4, #0b817d); }
  .module-icon.blue { background: linear-gradient(135deg, #2494ff, #0a5dcf); }
  .module-icon.green { background: linear-gradient(135deg, #76d56d, #317d42); }
  .module-icon.purple { background: linear-gradient(135deg, #9c6dff, #6834b8); }
  .module-icon.orange { background: linear-gradient(135deg, #ffad4e, #dc6a1a); }
  .module-icon.gold { background: linear-gradient(135deg, #ffd84d, #d99b14); }

  .module-info {
    position: relative;
    z-index: 1;
    min-width: 0;
    padding-right: 32px;
  }

  .module-info h3 {
    font-size: .98rem;
    margin-bottom: 8px;
    font-weight: 750;
    line-height: 1.2;
  }

  .module-info p {
    color: var(--text-soft);
    font-size: .86rem;
    line-height: 1.45;
  }

  .arrow {
    position: absolute;
    right: 14px;
    bottom: 14px;
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
    color: white;
    z-index: 2;
    transition: .18s ease;
  }

  .module-card:hover .arrow {
    background: rgba(16,199,189,.25);
    transform: translateX(3px);
  }

  /* ================= RESPONSIVE ================= */

  @media (max-width: 1450px) {
    .modules-grid {
      grid-template-columns: repeat(4, minmax(0, 1fr));
    }
  }

  @media (max-width: 1280px) {
    .kpi-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .charts-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 1100px) {
    .modules-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 820px) {
    body {
      padding: 16px;
    }

    .topbar {
      grid-template-columns: 1fr;
      gap: 16px;
    }

    .brand {
      border-right: 0;
      padding-right: 0;
    }

    .top-actions {
      flex-direction: column;
      align-items: stretch;
    }

    .user-chip,
    .logout-btn {
      width: 100%;
    }

    .kpi-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 700px) {
    .modules-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
</head>
<body>

  <main class="page">

    <!-- HEADER -->
    <header class="topbar">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">
          <span></span>
          <span></span>
          <span></span>
          <span></span>
        </div>

        <div class="brand-text">
          <strong>CONSULTORÍA </strong>
          <span>INTEGRAL DE PENSIONES</span>
        </div>
      </div>

      <div class="welcome">
        <h1>Hola, <?= htmlspecialchars($asesorNombre, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p>Selecciona tu módulo y revisa el resumen general.</p>
      </div>

      <div class="top-actions">
        <div class="user-chip">
          <div class="avatar">👤</div>
          <div>
            <strong><?= htmlspecialchars($asesorNombre, ENT_QUOTES, 'UTF-8'); ?></strong>
            <span><?= htmlspecialchars($asesorRol, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        </div>

        <a href="/app/controllers/auth/logout.php" class="logout-btn">
          <span>↪</span>
          Cerrar sesión
        </a>
      </div>
    </header>

    <!-- KPI CARDS -->
<section class="kpi-grid">

  <article class="kpi-card">
    <div class="kpi-icon teal">📋</div>
    <div class="kpi-info">
      <p>Solicitudes totales</p>
      <h2 style="color: var(--teal);">
        <?= number_format($resumenSolicitudes['total']); ?>
      </h2>
      <small>Total registradas en el sistema</small>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-icon green">✅</div>
    <div class="kpi-info">
      <p>Solicitudes activas</p>
      <h2 style="color: var(--green);">
        <?= number_format($resumenSolicitudes['activas']); ?>
      </h2>
      <small>
        <?= number_format($resumenSolicitudes['validadas']); ?> validadas /
        <?= number_format($resumenSolicitudes['pendientes']); ?> pendientes
      </small>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-icon orange">⏳</div>
    <div class="kpi-info">
      <p>Pendientes</p>
      <h2 style="color: var(--orange);">
        <?= number_format($resumenSolicitudes['pendientes']); ?>
      </h2>
      <small>Solicitudes esperando seguimiento</small>
    </div>
  </article>

  <article class="kpi-card">
    <div class="kpi-icon blue">🚫</div>
    <div class="kpi-info">
      <p>No activas</p>
      <h2 style="color: var(--danger);">
        <?= number_format($resumenSolicitudes['no_activas']); ?>
      </h2>
      <small>
        <?= number_format($resumenSolicitudes['canceladas']); ?> canceladas /
        <?= number_format($resumenSolicitudes['rechazadas']); ?> rechazadas
      </small>
    </div>
  </article>

</section>

    <!-- CHARTS -->
    <section class="charts-grid">
<article class="panel">
  <div class="panel-header">
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
      <h2 id="tituloGraficaMes">Solicitudes por mes</h2>
    </div>

    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
      <select class="select-mini" id="selectSeccionGrafica">
        <?php foreach ($graficaPorSeccionAnio as $codigo => $info): ?>
          <option value="<?= htmlspecialchars((string)$codigo, ENT_QUOTES, 'UTF-8'); ?>">
            <?= htmlspecialchars((string)$info['label'], ENT_QUOTES, 'UTF-8'); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select class="select-mini" id="selectAnioGrafica">
        <?php foreach ($aniosGrafica as $anio): ?>
          <option value="<?= (int)$anio; ?>">
            <?= (int)$anio; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="chart-box">
    <canvas id="chartSolicitudes"></canvas>
  </div>
</article>

      <article class="panel">
        <div class="panel-header">
          <h2>Módulos más usados</h2>
          <select class="select-mini">
            <option>Este mes</option>
          </select>
        </div>
        <div class="chart-box">
          <canvas id="chartModulos"></canvas>
        </div>
      </article>

      <article class="panel">
        <div class="panel-header">
          <h2>Distribución por tipo</h2>
          <select class="select-mini">
            <option>Este año</option>
          </select>
        </div>
        <div class="chart-box">
          <canvas id="chartDistribucion"></canvas>
        </div>
      </article>
    </section>

    <!-- MODULES + ACTIVITY -->
    <section class="content-grid">

      <article class="panel modules-panel">
        <div class="panel-header">
          <h2>Acceso rápido a módulos</h2>
        </div>

        <div class="module-search">
          <input type="text" id="buscarModulo" placeholder="Buscar módulo, por ejemplo: ahorro, pagos, inversión...">
        </div>

        <div class="modules-grid" id="modulesGrid">
          <?php foreach ($modulos as $modulo): ?>
              <a
                href="<?= htmlspecialchars($modulo['url'], ENT_QUOTES, 'UTF-8'); ?>"
                class="module-card js-module-link"
                data-modulo-codigo="<?= htmlspecialchars(
                    strtolower(
                        preg_replace('/[^a-zA-Z0-9]+/', '_', $modulo['titulo'])
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                data-modulo-nombre="<?= htmlspecialchars($modulo['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                data-url="<?= htmlspecialchars($modulo['url'], ENT_QUOTES, 'UTF-8'); ?>"
                data-title="<?= htmlspecialchars(mb_strtolower($modulo['titulo'] . ' ' . $modulo['descripcion']), ENT_QUOTES, 'UTF-8'); ?>"
              >
              <div class="module-icon <?= htmlspecialchars($modulo['color'], ENT_QUOTES, 'UTF-8'); ?>">
                <?= htmlspecialchars($modulo['icono'], ENT_QUOTES, 'UTF-8'); ?>
              </div>

              <div class="module-info">
                <h3><?= htmlspecialchars($modulo['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?= htmlspecialchars($modulo['descripcion'], ENT_QUOTES, 'UTF-8'); ?></p>
              </div>

              <span class="arrow">→</span>
            </a>
          <?php endforeach; ?>
        </div>
      </article>



    </section>

  </main>

 <script>
  // ================= BUSCADOR DE MÓDULOS =================
  const buscarModulo = document.getElementById('buscarModulo');
  const moduleCards = document.querySelectorAll('.module-card');

  if (buscarModulo) {
    buscarModulo.addEventListener('input', function () {
      const texto = this.value.trim().toLowerCase();

      moduleCards.forEach(card => {
        const contenido = (card.dataset.title || '').toLowerCase();
        card.style.display = contenido.includes(texto) ? 'flex' : 'none';
      });
    });
  }

  // ================= CONFIGURACIÓN GENERAL DE GRÁFICAS =================
  const chartTextColor = '#9fb2c8';
  const gridColor = 'rgba(159, 178, 200, 0.12)';

  Chart.defaults.font.family = 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
  Chart.defaults.color = chartTextColor;

  // ==========================================================
  // GRÁFICA DINÁMICA POR SECCIÓN Y AÑO
  // Solicitudes / Inversiones / Complemento / Ahorro
  // ==========================================================

  const graficaMesLabels = <?= json_encode($graficaMesLabels, JSON_UNESCAPED_UNICODE); ?>;
  const graficaPorSeccionAnio = <?= json_encode($graficaPorSeccionAnio, JSON_UNESCAPED_UNICODE); ?>;
  const aniosGrafica = <?= json_encode($aniosGrafica, JSON_UNESCAPED_UNICODE); ?>;

  const selectSeccionGrafica = document.getElementById('selectSeccionGrafica');
  const selectAnioGrafica = document.getElementById('selectAnioGrafica');
  const tituloGraficaMes = document.getElementById('tituloGraficaMes');
  const canvasSolicitudes = document.getElementById('chartSolicitudes');

  const seccionInicial = selectSeccionGrafica ? selectSeccionGrafica.value : 'solicitudes';
  const anioInicial = selectAnioGrafica ? selectAnioGrafica.value : String(new Date().getFullYear());

  function obtenerDatosGraficaMes() {
    const seccion = selectSeccionGrafica ? selectSeccionGrafica.value : seccionInicial;
    const anio = selectAnioGrafica ? selectAnioGrafica.value : anioInicial;

    if (
      graficaPorSeccionAnio[seccion] &&
      graficaPorSeccionAnio[seccion].data &&
      graficaPorSeccionAnio[seccion].data[anio]
    ) {
      return graficaPorSeccionAnio[seccion].data[anio];
    }

    return Array(12).fill(0);
  }

  function obtenerLabelGraficaMes() {
    const seccion = selectSeccionGrafica ? selectSeccionGrafica.value : seccionInicial;
    return graficaPorSeccionAnio[seccion]?.label || 'Registros';
  }

  function actualizarTituloGraficaMes() {
    const label = obtenerLabelGraficaMes();

    if (tituloGraficaMes) {
      tituloGraficaMes.textContent = label + ' por mes';
    }
  }

  let chartSolicitudes = null;

  if (canvasSolicitudes) {
    chartSolicitudes = new Chart(canvasSolicitudes, {
      type: 'line',
      data: {
        labels: graficaMesLabels,
        datasets: [{
          label: obtenerLabelGraficaMes(),
          data: obtenerDatosGraficaMes(),
          borderColor: '#10c7bd',
          backgroundColor: 'rgba(16,199,189,0.12)',
          pointBackgroundColor: '#10c7bd',
          pointBorderColor: '#dffefa',
          pointRadius: 4,
          pointHoverRadius: 6,
          borderWidth: 3,
          tension: 0.38,
          fill: true
        }]
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#0d243c',
            borderColor: 'rgba(16,199,189,.4)',
            borderWidth: 1,
            titleColor: '#ffffff',
            bodyColor: '#d6f7f5',
            padding: 12,
            callbacks: {
              label: function(context) {
                return obtenerLabelGraficaMes() + ': ' + context.raw;
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: chartTextColor }
          },
          y: {
            beginAtZero: true,
            grid: { color: gridColor },
            ticks: {
              color: chartTextColor,
              precision: 0
            }
          }
        }
      }
    });
  }

  function refrescarGraficaMes() {
    if (!chartSolicitudes) return;

    chartSolicitudes.data.datasets[0].label = obtenerLabelGraficaMes();
    chartSolicitudes.data.datasets[0].data = obtenerDatosGraficaMes();
    chartSolicitudes.update();

    actualizarTituloGraficaMes();
  }

  if (selectSeccionGrafica) {
    selectSeccionGrafica.addEventListener('change', refrescarGraficaMes);
  }

  if (selectAnioGrafica) {
    selectAnioGrafica.addEventListener('change', refrescarGraficaMes);
  }

  actualizarTituloGraficaMes();

  // ==========================================================
  // MÓDULOS MÁS USADOS
  // ==========================================================

  const canvasModulos = document.getElementById('chartModulos');

  if (canvasModulos) {
    new Chart(canvasModulos, {
      type: 'bar',
      data: {
        labels: <?= json_encode($modulosUsadosLabels, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
          label: 'Accesos',
          data: <?= json_encode($modulosUsadosData, JSON_UNESCAPED_UNICODE); ?>,
          backgroundColor: '#1f87ff',
          borderColor: '#62b2ff',
          borderWidth: 1,
          borderRadius: 8,
          maxBarThickness: 34
        }]
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#0d243c',
            borderColor: 'rgba(31,135,255,.4)',
            borderWidth: 1,
            titleColor: '#ffffff',
            bodyColor: '#dbeeff',
            padding: 12
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              color: chartTextColor,
              autoSkip: false,
              maxRotation: 25,
              minRotation: 0
            }
          },
          y: {
            beginAtZero: true,
            grid: { color: gridColor },
            ticks: {
              color: chartTextColor,
              precision: 0
            }
          }
        }
      }
    });
  }

  // ==========================================================
  // DISTRIBUCIÓN POR TIPO
  // ==========================================================

  const canvasDistribucion = document.getElementById('chartDistribucion');

  if (canvasDistribucion) {
    new Chart(canvasDistribucion, {
      type: 'doughnut',
      data: {
        labels: <?= json_encode($distribucionTipoLabels, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
          data: <?= json_encode($distribucionTipoData, JSON_UNESCAPED_UNICODE); ?>,
          backgroundColor: [
            '#10c7bd',
            '#1f87ff',
            '#ffc533',
            '#70d36b'
          ],
          borderColor: '#081b31',
          borderWidth: 4,
          hoverOffset: 8
        }]
      },
      options: {
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: {
          legend: {
            position: 'right',
            labels: {
              color: chartTextColor,
              usePointStyle: true,
              pointStyle: 'circle',
              padding: 16,
              font: {
                size: 12
              }
            }
          },
          tooltip: {
            backgroundColor: '#0d243c',
            borderColor: 'rgba(255,255,255,.15)',
            borderWidth: 1,
            titleColor: '#ffffff',
            bodyColor: '#e8f2ff',
            padding: 12,
            callbacks: {
              label: function(context) {
                return context.label + ': ' + context.raw;
              }
            }
          }
        }
      }
    });
  }

  // ================= REGISTRAR ACCESO A MÓDULOS =================
  document.querySelectorAll('.js-module-link').forEach(link => {
    link.addEventListener('click', function (e) {
      e.preventDefault();

      const destino = this.getAttribute('href');

      const payload = {
        modulo_codigo: this.dataset.moduloCodigo || '',
        modulo_nombre: this.dataset.moduloNombre || '',
        url: this.dataset.url || destino
      };

      fetch('/app/controllers/modulos/modulos/registrar_acceso.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload),
        keepalive: true
      })
      .catch(() => {})
      .finally(() => {
        window.location.href = destino;
      });
    });
  });
</script>
</body>
</html>