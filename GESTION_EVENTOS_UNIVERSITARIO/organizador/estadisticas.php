<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(2);

$db  = getDB();
$uid = $_SESSION['user_id'];
$page_title = 'Estadísticas';

// Todos mis eventos con stats
$stmtEv = $db->prepare("
    SELECT e.id, e.nombre, e.fecha_inicio, e.estado, e.cupo_maximo,
           COUNT(DISTINCT r.id) AS inscritos,
           SUM(CASE WHEN a.presente = 1 THEN 1 ELSE 0 END) AS presentes,
           SUM(CASE WHEN a.presente = 0 THEN 1 ELSE 0 END) AS ausentes
    FROM eventos e
    LEFT JOIN inscripciones r ON r.evento_id = e.id AND r.estado = 'activa'
    LEFT JOIN asistencias a ON a.evento_id = e.id
    WHERE e.organizador_id = ?
    GROUP BY e.id
    ORDER BY e.fecha_inicio DESC
");
$stmtEv->execute([$uid]);
$eventos = $stmtEv->fetchAll(PDO::FETCH_ASSOC);

// Filtro por evento específico si se pasa ?event_id
$eventFilter = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);

// Datos para Chart.js
$chartLabels  = [];
$chartInscritos = [];
$chartCupoa   = [];
$chartAsistencia = [];
foreach ($eventos as $ev) {
    $chartLabels[]     = $ev['nombre'];
    $chartInscritos[]  = (int)$ev['inscritos'];
    $chartCupoa[]      = (int)$ev['cupo_maximo'];
    $pctA = $ev['inscritos'] > 0 ? round($ev['presentes'] / $ev['inscritos'] * 100, 1) : 0;
    $chartAsistencia[] = $pctA;
}

$estadoColors = [
    'programado' => '#17a2b8',
    'en_curso'   => '#28a745',
    'finalizado' => '#6c757d',
    'cancelado'  => '#dc3545',
];

require_once __DIR__ . '/../includes/header_org.php';
?>
<h1 style="font-size:1.5rem;margin-bottom:6px;">Estadísticas de Mis Eventos</h1>
<p style="color:#666;margin-bottom:24px;">Análisis de inscripciones y asistencia de los eventos que organizas.</p>

<!-- Tabla de eventos -->
<div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);margin-bottom:28px;">
    <h2 style="font-size:1rem;margin-bottom:16px;color:#2c7a3e;"><i class="fas fa-table"></i> Inscritos vs Cupo por Evento</h2>
    <?php if (empty($eventos)): ?>
        <p style="text-align:center;color:#999;padding:30px;">Sin eventos para mostrar.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.87rem;">
            <thead>
                <tr style="background:#f8f9fa;">
                    <th style="padding:11px;text-align:left;border-bottom:2px solid #dee2e6;">Evento</th>
                    <th style="padding:11px;text-align:left;border-bottom:2px solid #dee2e6;">Fecha</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">Cupo</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">Inscritos</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">Ocupación</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">Presentes</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">% Asistencia</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">Estado</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eventos as $ev):
                    $pctOcup = $ev['cupo_maximo'] > 0 ? round($ev['inscritos'] / $ev['cupo_maximo'] * 100) : 0;
                    $pctAsist = $ev['inscritos'] > 0 ? round($ev['presentes'] / $ev['inscritos'] * 100, 1) : 0;
                    $barOcupColor = $pctOcup >= 90 ? '#dc3545' : ($pctOcup >= 60 ? '#ffc107' : '#28a745');
                    $barAsistColor = $pctAsist >= 70 ? '#28a745' : ($pctAsist >= 40 ? '#ffc107' : '#dc3545');
                ?>
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:10px;font-weight:600;max-width:180px;"><?php echo htmlspecialchars($ev['nombre']); ?></td>
                    <td style="padding:10px;white-space:nowrap;"><?php echo date('d/m/Y', strtotime($ev['fecha_inicio'])); ?></td>
                    <td style="padding:10px;text-align:center;"><?php echo $ev['cupo_maximo']; ?></td>
                    <td style="padding:10px;text-align:center;font-weight:600;"><?php echo $ev['inscritos']; ?></td>
                    <td style="padding:10px;min-width:100px;">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="flex:1;background:#e9ecef;border-radius:4px;height:8px;">
                                <div style="background:<?php echo $barOcupColor; ?>;width:<?php echo min($pctOcup,100); ?>%;height:8px;border-radius:4px;"></div>
                            </div>
                            <span style="font-size:0.78rem;white-space:nowrap;"><?php echo $pctOcup; ?>%</span>
                        </div>
                    </td>
                    <td style="padding:10px;text-align:center;"><?php echo (int)$ev['presentes']; ?></td>
                    <td style="padding:10px;min-width:100px;">
                        <?php if ($ev['estado'] === 'finalizado' || (int)$ev['presentes'] > 0): ?>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="flex:1;background:#e9ecef;border-radius:4px;height:8px;">
                                <div style="background:<?php echo $barAsistColor; ?>;width:<?php echo min($pctAsist,100); ?>%;height:8px;border-radius:4px;"></div>
                            </div>
                            <span style="font-size:0.78rem;white-space:nowrap;"><?php echo $pctAsist; ?>%</span>
                        </div>
                        <?php else: ?><span style="color:#ccc;font-size:0.78rem;">N/A</span><?php endif; ?>
                    </td>
                    <td style="padding:10px;text-align:center;">
                        <span style="background:<?php echo $estadoColors[$ev['estado']] ?? '#6c757d'; ?>;color:#fff;padding:3px 8px;border-radius:20px;font-size:0.76rem;">
                            <?php echo ucfirst($ev['estado']); ?>
                        </span>
                    </td>
                    <td style="padding:10px;text-align:center;">
                        <a href="inscritos.php?event_id=<?php echo $ev['id']; ?>"
                           style="color:#17a2b8;text-decoration:none;font-size:0.82rem;" title="Ver inscritos">
                            <i class="fas fa-users"></i>
                        </a>
                        &nbsp;
                        <a href="asistencia.php?event_id=<?php echo $ev['id']; ?>"
                           style="color:#28a745;text-decoration:none;font-size:0.82rem;" title="Asistencia">
                            <i class="fas fa-clipboard-check"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Gráficos Chart.js -->
<?php if (!empty($eventos)): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px;margin-bottom:28px;">
    <!-- Gráfico: Inscritos vs Cupo -->
    <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);">
        <h3 style="font-size:0.95rem;margin-bottom:14px;color:#2c7a3e;"><i class="fas fa-users"></i> Inscritos vs Cupo</h3>
        <canvas id="chartInscritosCupo" height="260"></canvas>
    </div>
    <!-- Gráfico: % Asistencia -->
    <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);">
        <h3 style="font-size:0.95rem;margin-bottom:14px;color:#2c7a3e;"><i class="fas fa-chart-bar"></i> % Asistencia por Evento</h3>
        <canvas id="chartAsistencia" height="260"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
var labels = <?php echo json_encode($chartLabels); ?>;
var inscritos = <?php echo json_encode($chartInscritos); ?>;
var cupos = <?php echo json_encode($chartCupoa); ?>;
var asistencia = <?php echo json_encode($chartAsistencia); ?>;

// Acortar etiquetas largas
var shortLabels = labels.map(function(l) { return l.length > 20 ? l.substring(0,20)+'…' : l; });

new Chart(document.getElementById('chartInscritosCupo'), {
    type: 'bar',
    data: {
        labels: shortLabels,
        datasets: [
            {
                label: 'Inscritos',
                data: inscritos,
                backgroundColor: 'rgba(44,122,62,0.8)',
                borderRadius: 4,
            },
            {
                label: 'Cupo Máximo',
                data: cupos,
                backgroundColor: 'rgba(220,220,220,0.6)',
                borderRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

new Chart(document.getElementById('chartAsistencia'), {
    type: 'bar',
    data: {
        labels: shortLabels,
        datasets: [{
            label: '% Asistencia',
            data: asistencia,
            backgroundColor: asistencia.map(function(v) {
                return v >= 70 ? 'rgba(40,167,69,0.8)' : (v >= 40 ? 'rgba(255,193,7,0.8)' : 'rgba(220,53,69,0.8)');
            }),
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, max: 100,
                 ticks: { callback: function(v) { return v + '%'; } } }
        }
    }
});
</script>
<?php endif; ?>

</main></div>
<footer style="text-align:center;padding:16px;font-size:0.8rem;color:#999;background:#fff;border-top:1px solid #eee;margin-left:240px;">
    <?php echo APP_NAME; ?> &copy; <?php echo date('Y'); ?> — Módulo Organizador
</footer>
</body>
</html>
