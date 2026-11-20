<?php
// admin/reportes.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(1);

$db = getDB();
$pageTitle = 'Estadísticas y Reportes';

// 1. Métricas generales
$totalEventos       = (int)$db->query("SELECT COUNT(*) FROM eventos")->fetchColumn();
$totalParticipantes = (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE rol_id = 3")->fetchColumn();
$totalInscripciones = (int)$db->query("SELECT COUNT(*) FROM inscripciones WHERE estado = 'activa'")->fetchColumn();
$totalCertificados  = (int)$db->query("SELECT COUNT(*) FROM certificados WHERE estado = 'emitido'")->fetchColumn();
$totalAsistentes    = (int)$db->query("SELECT COUNT(*) FROM asistencias WHERE presente = 1")->fetchColumn();
$totalAusentes      = (int)$db->query("SELECT COUNT(*) FROM asistencias WHERE presente = 0")->fetchColumn();
$totalAsistenciasRegistradas = $totalAsistentes + $totalAusentes;
$tasaAsistenciaGlobal = $totalAsistenciasRegistradas > 0 ? round(($totalAsistentes / $totalAsistenciasRegistradas) * 100, 1) : 0;

// 2. Reporte por evento (tabla detallada)
$stmtEventosDetalle = $db->query("
    SELECT e.id, e.nombre, e.fecha_inicio, e.cupo_maximo, e.cupo_disponible, e.estado, e.modalidad,
           c.nombre AS categoria,
           CONCAT(u.nombre, ' ', u.apellido) AS organizador,
           (SELECT COUNT(*) FROM inscripciones i WHERE i.evento_id = e.id AND i.estado = 'activa') AS inscritos,
           (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id AND a.presente = 1) AS presentes,
           (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id AND a.presente = 0) AS ausentes,
           (SELECT COUNT(*) FROM certificados cert WHERE cert.evento_id = e.id AND cert.estado = 'emitido') AS certs_emitidos
    FROM eventos e
    LEFT JOIN categorias_eventos c ON c.id = e.categoria_id
    LEFT JOIN usuarios u ON u.id = e.organizador_id
    ORDER BY e.fecha_inicio DESC
");
$reporteEventos = $stmtEventosDetalle->fetchAll(PDO::FETCH_ASSOC);

// Datos para gráficos Chart.js
$graficoNombres = [];
$graficoInscritos = [];
$graficoPresentes = [];

foreach (array_slice($reporteEventos, 0, 8) as $ev) {
    $nombreCorto = strlen($ev['nombre']) > 22 ? substr($ev['nombre'], 0, 20) . '...' : $ev['nombre'];
    $graficoNombres[] = $nombreCorto;
    $graficoInscritos[] = (int)$ev['inscritos'];
    $graficoPresentes[] = (int)$ev['presentes'];
}

// Eventos por estado
$stmtEstados = $db->query("SELECT estado, COUNT(*) AS cantidad FROM eventos GROUP BY estado");
$estadosRaw = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);
$estadosLabels = [];
$estadosData = [];
foreach ($estadosRaw as $er) {
    $estadosLabels[] = ucfirst($er['estado']);
    $estadosData[] = (int)$er['cantidad'];
}

include __DIR__ . '/../includes/header_admin.php';
?>

<div class="page-header">
    <h1><i class="fas fa-chart-line"></i> Estadísticas y Reportes</h1>
    <div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <strong>Reportes</strong></div>
</div>

<!-- Tarjetas de Resumen General -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon" style="background:#2980b9;"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-info">
            <div class="value"><?= $totalEventos ?></div>
            <div class="label">Total Eventos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#27ae60;"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <div class="value"><?= $totalParticipantes ?></div>
            <div class="label">Participantes Registrados</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f39c12;"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-info">
            <div class="value"><?= $totalInscripciones ?></div>
            <div class="label">Inscripciones Activas</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#8e44ad;"><i class="fas fa-award"></i></div>
        <div class="stat-info">
            <div class="value"><?= $totalCertificados ?></div>
            <div class="label">Certificados Emitidos</div>
        </div>
    </div>
</div>

<!-- Segunda fila de estadísticas de asistencia -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon" style="background:#16a085;"><i class="fas fa-user-check"></i></div>
        <div class="stat-info">
            <div class="value"><?= $totalAsistentes ?></div>
            <div class="label">Asistentes Presentes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#c0392b;"><i class="fas fa-user-times"></i></div>
        <div class="stat-info">
            <div class="value"><?= $totalAusentes ?></div>
            <div class="label">Ausentes Registrados</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#d35400;"><i class="fas fa-percentage"></i></div>
        <div class="stat-info">
            <div class="value"><?= $tasaAsistenciaGlobal ?>%</div>
            <div class="label">Tasa Asistencia Global</div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div class="row-two-col mb-4">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-chart-bar"></i> Inscripciones vs Asistentes por Evento</span>
        </div>
        <div class="card-body">
            <canvas id="chartComparativa" height="220"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-chart-pie"></i> Distribución de Eventos por Estado</span>
        </div>
        <div class="card-body">
            <canvas id="chartEstados" height="220"></canvas>
        </div>
    </div>
</div>

<!-- Tabla Detallada por Evento -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <span class="card-title"><i class="fas fa-table"></i> Reporte Detallado de Rendimiento de Eventos</span>
        <button class="btn btn-secondary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Imprimir Reporte</button>
    </div>
    <div class="card-body">
        <?php if (empty($reporteEventos)): ?>
            <p class="text-center text-muted p-4">No hay eventos para generar reportes en este momento.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th>Categoría</th>
                            <th>Fecha</th>
                            <th>Organizador</th>
                            <th style="text-align:center;">Cupo</th>
                            <th style="text-align:center;">Inscritos</th>
                            <th style="text-align:center;">Presentes</th>
                            <th style="text-align:center;">Ausentes</th>
                            <th style="text-align:center;">% Asistencia</th>
                            <th style="text-align:center;">Certificados</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reporteEventos as $re): 
                            $totControl = (int)$re['presentes'] + (int)$re['ausentes'];
                            $pctAsist = $totControl > 0 ? round(($re['presentes'] / $totControl) * 100, 1) : 0;
                            $pctCupo = $re['cupo_maximo'] > 0 ? round(($re['inscritos'] / $re['cupo_maximo']) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td>
                                    <strong><?= e($re['nombre']) ?></strong><br>
                                    <span class="badge badge-<?= $re['estado'] ?>"><?= ucfirst($re['estado']) ?></span>
                                </td>
                                <td><?= e($re['categoria'] ?: 'Sin categoría') ?></td>
                                <td><?= date('d/m/Y', strtotime($re['fecha_inicio'])) ?></td>
                                <td><?= e($re['organizador'] ?: 'Sin asignar') ?></td>
                                <td style="text-align:center;"><?= $re['cupo_maximo'] ?></td>
                                <td style="text-align:center;">
                                    <strong><?= $re['inscritos'] ?></strong>
                                    <small class="text-muted">(<?= $pctCupo ?>%)</small>
                                </td>
                                <td style="text-align:center; color:#27ae60; font-weight:bold;"><?= $re['presentes'] ?></td>
                                <td style="text-align:center; color:#c0392b;"><?= $re['ausentes'] ?></td>
                                <td style="text-align:center;">
                                    <strong><?= $pctAsist ?>%</strong>
                                </td>
                                <td style="text-align:center;">
                                    <span class="badge badge-en_curso"><?= $re['certs_emitidos'] ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Gráfico de barras comparativo
    const ctxComp = document.getElementById('chartComparativa');
    if (ctxComp) {
        new Chart(ctxComp, {
            type: 'bar',
            data: {
                labels: <?= json_encode($graficoNombres) ?>,
                datasets: [
                    {
                        label: 'Inscritos',
                        data: <?= json_encode($graficoInscritos) ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.7)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Asistentes Presentes',
                        data: <?= json_encode($graficoPresentes) ?>,
                        backgroundColor: 'rgba(39, 174, 96, 0.7)',
                        borderColor: 'rgba(39, 174, 96, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // 2. Gráfico de estados
    const ctxEst = document.getElementById('chartEstados');
    if (ctxEst) {
        new Chart(ctxEst, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($estadosLabels) ?>,
                datasets: [{
                    data: <?= json_encode($estadosData) ?>,
                    backgroundColor: ['#3498db', '#2ecc71', '#95a5a6', '#e74c3c']
                }]
            },
            options: {
                responsive: true
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
