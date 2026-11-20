<?php
// participante/mi_certificado.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';

// Requiere estar autenticado (participante o admin u organizador)
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$certId      = isset($_GET['cert_id']) && is_numeric($_GET['cert_id']) ? (int)$_GET['cert_id'] : 0;
$codigo      = trim($_GET['codigo'] ?? '');
$orientacion = strtolower(trim($_GET['orientacion'] ?? 'horizontal'));
if (!in_array($orientacion, ['horizontal', 'vertical'], true)) {
    $orientacion = 'horizontal';
}

$db = getDB();

if ($certId > 0) {
    $stmt = $db->prepare("
        SELECT c.*,
               u.nombre AS part_nombre, u.apellido AS part_apellido, u.email AS part_email, u.institucion AS part_institucion,
               e.nombre AS evento_nombre, e.descripcion AS evento_desc, e.fecha_inicio, e.fecha_fin, e.lugar, e.modalidad,
               cat.nombre AS categoria_nombre,
               CONCAT(org.nombre, ' ', org.apellido) AS organizador_nombre
        FROM certificados c
        JOIN usuarios u ON u.id = c.usuario_id
        JOIN eventos e ON e.id = c.evento_id
        LEFT JOIN categorias_eventos cat ON cat.id = e.categoria_id
        LEFT JOIN usuarios org ON org.id = e.organizador_id
        WHERE c.id = ?
    ");
    $stmt->execute([$certId]);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($codigo !== '') {
    $stmt = $db->prepare("
        SELECT c.*,
               u.nombre AS part_nombre, u.apellido AS part_apellido, u.email AS part_email, u.institucion AS part_institucion,
               e.nombre AS evento_nombre, e.descripcion AS evento_desc, e.fecha_inicio, e.fecha_fin, e.lugar, e.modalidad,
               cat.nombre AS categoria_nombre,
               CONCAT(org.nombre, ' ', org.apellido) AS organizador_nombre
        FROM certificados c
        JOIN usuarios u ON u.id = c.usuario_id
        JOIN eventos e ON e.id = c.evento_id
        LEFT JOIN categorias_eventos cat ON cat.id = e.categoria_id
        LEFT JOIN usuarios org ON org.id = e.organizador_id
        WHERE c.codigo_certificado = ?
    ");
    $stmt->execute([$codigo]);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $cert = null;
}

// Control de acceso: Si es participante (rol 3), solo puede ver sus propios certificados
if ($cert && (int)$_SESSION['rol_id'] === ROL_PARTICIPANTE && (int)$cert['usuario_id'] !== (int)$_SESSION['user_id']) {
    die('Acceso denegado: este certificado pertenece a otro participante.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado Oficial &mdash; <?= $cert ? e($cert['codigo_certificado']) : 'No encontrado' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style id="page-style">
        @page {
            size: letter <?= $orientacion === 'vertical' ? 'portrait' : 'landscape' ?>;
            margin: 6mm;
        }
    </style>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #1e293b;
            font-family: 'Montserrat', sans-serif;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 15px;
            min-height: 100vh;
        }

        .actions-bar {
            width: 100%;
            max-width: 1020px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn-group {
            display: inline-flex;
            background: #334155;
            padding: 4px;
            border-radius: 10px;
            gap: 4px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #475569; color: #fff; }
        .btn-secondary:hover { background: #334155; }
        .btn-toggle {
            background: transparent;
            color: #94a3b8;
            border-radius: 6px;
            font-size: 0.82rem;
            padding: 7px 14px;
        }
        .btn-toggle.active {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.25);
        }

        /* ─── Contenedor Base del Certificado (Tamaño Carta) ─── */
        .cert-wrapper {
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .cert-container {
            background: #ffffff;
            box-shadow: 0 15px 40px rgba(0,0,0,0.45);
            position: relative;
            box-sizing: border-box;
            background-color: #fff;
            transition: all 0.3s ease;
        }

        /* Formato Carta Horizontal (11in x 8.5in / Aspect Ratio 1.294) */
        .orientacion-horizontal .cert-container {
            width: 1020px;
            max-width: 100%;
            min-height: 700px;
            padding: 24px;
            border-radius: 10px;
        }

        /* Formato Carta Vertical (8.5in x 11in / Aspect Ratio 0.772) */
        .orientacion-vertical .cert-container {
            width: 760px;
            max-width: 100%;
            min-height: 960px;
            padding: 26px;
            border-radius: 10px;
        }

        .cert-border {
            border: 5px double #1a365d;
            height: 100%;
            text-align: center;
            position: relative;
            background: radial-gradient(circle, #ffffff 65%, #fcfbf8 100%);
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .orientacion-horizontal .cert-border {
            padding: 22px 30px;
        }

        .orientacion-vertical .cert-border {
            padding: 30px 28px;
        }

        /* Encabezado */
        .cert-header {
            margin-bottom: 8px;
        }
        .cert-logo {
            color: #1a365d;
            margin-bottom: 4px;
        }
        .orientacion-horizontal .cert-logo { font-size: 2.1rem; }
        .orientacion-vertical   .cert-logo { font-size: 2.5rem; margin-bottom: 8px; }

        .cert-institution {
            font-family: 'Cinzel', serif;
            font-weight: 800;
            letter-spacing: 2px;
            color: #1a365d;
            text-transform: uppercase;
            line-height: 1.25;
        }
        .orientacion-horizontal .cert-institution { font-size: 1.28rem; }
        .orientacion-vertical   .cert-institution { font-size: 1.45rem; }

        .cert-subtitle {
            letter-spacing: 1.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-top: 3px;
        }
        .orientacion-horizontal .cert-subtitle { font-size: 0.72rem; }
        .orientacion-vertical   .cert-subtitle { font-size: 0.78rem; margin-top: 5px; }

        /* Título del Certificado */
        .cert-title {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            color: #b45309;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .orientacion-horizontal .cert-title { font-size: 1.75rem; margin: 10px 0 6px; }
        .orientacion-vertical   .cert-title { font-size: 2.1rem; margin: 18px 0 10px; }

        .cert-intro {
            color: #475569;
            font-style: italic;
        }
        .orientacion-horizontal .cert-intro { font-size: 0.88rem; margin-bottom: 8px; }
        .orientacion-vertical   .cert-intro { font-size: 0.95rem; margin-bottom: 14px; }

        /* Participante */
        .cert-recipient {
            font-family: 'Cinzel', serif;
            font-weight: 800;
            color: #0f172a;
            border-bottom: 2px solid #b45309;
            display: inline-block;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .orientacion-horizontal .cert-recipient {
            font-size: 1.65rem;
            padding: 0 25px 4px;
            margin-bottom: 10px;
        }
        .orientacion-vertical .cert-recipient {
            font-size: 1.95rem;
            padding: 0 30px 6px;
            margin-bottom: 18px;
        }

        /* Cuerpo del Certificado */
        .cert-body {
            line-height: 1.48;
            color: #334155;
            margin: 0 auto;
        }
        .orientacion-horizontal .cert-body {
            font-size: 0.91rem;
            max-width: 860px;
            margin-bottom: 12px;
        }
        .orientacion-vertical .cert-body {
            font-size: 0.98rem;
            max-width: 650px;
            margin-bottom: 20px;
        }
        .cert-event-name {
            font-weight: 700;
            color: #1e3a8a;
        }

        /* Detalles Académicos */
        .cert-details-grid {
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .orientacion-horizontal .cert-details-grid {
            display: flex;
            justify-content: space-around;
            padding: 8px 12px;
            margin: 8px 0 14px;
            font-size: 0.78rem;
        }
        .orientacion-vertical .cert-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 12px 16px;
            margin: 14px 0 22px;
            font-size: 0.82rem;
            text-align: left;
        }
        .cert-details-grid div span {
            display: block;
            color: #64748b;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .cert-details-grid div strong {
            display: block;
            color: #0f172a;
            font-size: 0.88rem;
            font-weight: 700;
            margin-top: 2px;
        }

        /* Firmas y Sello */
        .cert-signatures {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 20px;
        }
        .orientacion-horizontal .cert-signatures {
            margin-top: 10px;
        }
        .orientacion-vertical .cert-signatures {
            margin-top: 25px;
            padding: 0 10px;
        }

        .sign-box {
            text-align: center;
        }
        .orientacion-horizontal .sign-box { width: 230px; }
        .orientacion-vertical   .sign-box { width: 200px; }

        .sign-name {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 4px;
        }
        .orientacion-horizontal .sign-name { font-size: 0.95rem; }
        .orientacion-vertical   .sign-name { font-size: 0.92rem; }

        .sign-line {
            border-top: 1.5px solid #64748b;
            padding-top: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #1e293b;
        }
        .sign-role {
            font-size: 0.72rem;
            color: #64748b;
            text-transform: uppercase;
        }

        /* Sello Oficial */
        .cert-seal {
            border-radius: 50%;
            border: 3px double #b45309;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #b45309;
            font-family: 'Cinzel', serif;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-align: center;
            background: #fffdf5;
            box-shadow: 0 0 0 3px rgba(180,83,9,0.12);
        }
        .orientacion-horizontal .cert-seal {
            width: 72px;
            height: 72px;
            font-size: 0.58rem;
        }
        .orientacion-vertical .cert-seal {
            width: 82px;
            height: 82px;
            font-size: 0.62rem;
        }
        .cert-seal i {
            font-size: 1.25rem;
            margin-bottom: 2px;
        }

        /* Pie de página */
        .cert-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #94a3b8;
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
            font-size: 0.7rem;
            letter-spacing: 0.3px;
        }
        .orientacion-horizontal .cert-footer { margin-top: 10px; }
        .orientacion-vertical   .cert-footer { margin-top: 18px; }

        /* ─── ESTILOS ESTRICTOS DE IMPRESIÓN (UNA SOLA PÁGINA) ─── */
        @media print {
            html, body {
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                overflow: hidden !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .actions-bar {
                display: none !important;
            }

            .cert-wrapper {
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            .cert-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                height: 100% !important;
                max-height: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                display: flex !important;
                flex-direction: column !important;
            }

            .cert-border {
                height: 100% !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .orientacion-horizontal .cert-border {
                padding: 18px 24px !important;
            }

            .orientacion-vertical .cert-border {
                padding: 24px 22px !important;
            }
        }
    </style>
</head>
<body class="orientacion-<?= $orientacion ?>" id="cert-body">

<div class="actions-bar">
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <a href="javascript:history.back()" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>

        <!-- Selector de Orientación Carta -->
        <div class="btn-group">
            <button type="button" class="btn btn-toggle <?= $orientacion === 'horizontal' ? 'active' : '' ?>" id="btn-horizontal" onclick="cambiarOrientacion('horizontal')">
                <i class="fas fa-arrows-left-right"></i> Carta Horizontal
            </button>
            <button type="button" class="btn btn-toggle <?= $orientacion === 'vertical' ? 'active' : '' ?>" id="btn-vertical" onclick="cambiarOrientacion('vertical')">
                <i class="fas fa-arrows-up-down"></i> Carta Vertical
            </button>
        </div>
    </div>

    <div>
        <button onclick="window.print()" class="btn btn-primary" style="box-shadow: 0 4px 14px rgba(37,99,235,0.4);">
            <i class="fas fa-print"></i> Imprimir / Guardar como PDF
        </button>
    </div>
</div>

<?php if (!$cert): ?>
    <div class="cert-container" style="text-align:center; padding: 60px; border-radius:10px; background:#fff; max-width:600px;">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e74c3c; margin-bottom: 15px;"></i>
        <h2>Certificado no encontrado</h2>
        <p style="color: #64748b; margin-top: 10px;">El certificado solicitado no existe o no tiene autorización para consultarlo.</p>
    </div>
<?php elseif ($cert['estado'] === 'revocado'): ?>
    <div class="cert-container" style="text-align:center; padding: 60px; border-radius:10px; background:#fff; max-width:600px;">
        <i class="fas fa-ban" style="font-size: 3rem; color: #dc2626; margin-bottom: 15px;"></i>
        <h2>Certificado Revocado</h2>
        <p style="color: #64748b; margin-top: 10px;">Este certificado fue emitido con código <code><?= e($cert['codigo_certificado']) ?></code> pero se encuentra revocado por la administración.</p>
    </div>
<?php else: ?>
    <div class="cert-wrapper">
        <div class="cert-container">
            <div class="cert-border">
                <!-- Encabezado Institucional -->
                <div class="cert-header">
                    <div class="cert-logo"><i class="fas fa-university"></i></div>
                    <div class="cert-institution">UNIVERSIDAD CRISTIANA EVANGÉLICA NUEVO MILENIO</div>
                    <div class="cert-subtitle">Dirección de Extensión Universitaria y Formación Continua</div>
                </div>

                <!-- Título del Certificado -->
                <div>
                    <div class="cert-title">CERTIFICADO DE PARTICIPACIÓN</div>
                    <div class="cert-intro">Se otorga el presente reconocimiento académico a:</div>
                </div>

                <!-- Nombre del Participante -->
                <div>
                    <div class="cert-recipient">
                        <?= e($cert['part_nombre'] . ' ' . $cert['part_apellido']) ?>
                    </div>
                </div>

                <!-- Texto Conmemorativo -->
                <div class="cert-body">
                    Por haber participado y cumplido satisfactoriamente con los requisitos académicos y de asistencia en el evento
                    <span class="cert-event-name">«<?= e($cert['evento_nombre']) ?>»</span>,
                    desarrollado en modalidad <strong><?= ucfirst($cert['modalidad']) ?></strong> el día
                    <strong><?= date('d \d\e F \d\e Y', strtotime($cert['fecha_inicio'])) ?></strong>
                    en <strong><?= e($cert['lugar'] ?: 'Campus Universitario') ?></strong>.
                </div>

                <!-- Cuadrícula de Detalles Académicos -->
                <div class="cert-details-grid">
                    <div>
                        <span>Categoría del Evento</span>
                        <strong><?= e($cert['categoria_nombre'] ?: 'Evento Académico') ?></strong>
                    </div>
                    <div>
                        <span>Asistencia Acreditada</span>
                        <strong><?= number_format($cert['porcentaje_asistencia'], 0) ?>% Asistencia</strong>
                    </div>
                    <div>
                        <span>Fecha de Expedición</span>
                        <strong><?= date('d/m/Y', strtotime($cert['fecha_emision'])) ?></strong>
                    </div>
                    <div>
                        <span>Código de Validación</span>
                        <strong style="color:#b45309;font-family:monospace;"><?= e($cert['codigo_certificado']) ?></strong>
                    </div>
                </div>

                <!-- Firmas y Sello Oficial -->
                <div class="cert-signatures">
                    <div class="sign-box">
                        <div class="sign-name">ING. THAMY PACHECO</div>
                        <div class="sign-line">Rector Institucional</div>
                        <div class="sign-role">Universidad UCENM</div>
                    </div>

                    <div class="cert-seal">
                        <i class="fas fa-stamp"></i>
                        <span>OFICIAL</span>
                        <span>VERIFICADO</span>
                    </div>

                    <div class="sign-box">
                        <div class="sign-name">
                            <?= e($cert['organizador_nombre'] ?: 'Comité Académico') ?>
                        </div>
                        <div class="sign-line">Organizador Responsable</div>
                        <div class="sign-role"><?= e($cert['evento_nombre']) ?></div>
                    </div>
                </div>

                <!-- Pie de página Oficial -->
                <div class="cert-footer">
                    <span>Documento oficial expedido electrónicamente por el Sistema de Gestión de Eventos Universitarios</span>
                    <span>Registro Oficial: <strong><?= e($cert['codigo_certificado']) ?></strong></span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function cambiarOrientacion(tipo) {
    var body = document.getElementById('cert-body');
    var btnH = document.getElementById('btn-horizontal');
    var btnV = document.getElementById('btn-vertical');
    var pageStyle = document.getElementById('page-style');

    if (tipo === 'vertical') {
        body.className = 'orientacion-vertical';
        btnV.classList.add('active');
        btnH.classList.remove('active');
        pageStyle.innerHTML = '@page { size: letter portrait; margin: 6mm; }';
    } else {
        body.className = 'orientacion-horizontal';
        btnH.classList.add('active');
        btnV.classList.remove('active');
        pageStyle.innerHTML = '@page { size: letter landscape; margin: 6mm; }';
    }

    // Actualizar URL sin recargar para guardar preferencia
    if (window.history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.set('orientacion', tipo);
        window.history.replaceState(null, '', url.toString());
    }
}
</script>

</body>
</html>
