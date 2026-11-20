<?php
// admin/dashboard.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(1);

$db = getDB();
$pageTitle = 'Dashboard';

// --- Stats ---
$totalEventos       = $db->query("SELECT COUNT(*) FROM eventos")->fetchColumn();
$totalParticipantes = $db->query("SELECT COUNT(*) FROM usuarios WHERE rol_id = 3")->fetchColumn();
$totalInscripciones = $db->query("SELECT COUNT(*) FROM inscripciones WHERE estado = 'activa'")->fetchColumn();
$totalCertificados  = $db->query("SELECT COUNT(*) FROM certificados WHERE estado = 'emitido'")->fetchColumn();

// Eventos por estado para Chart.js
$stmtEstados = $db->query("SELECT estado, COUNT(*) as total FROM eventos GROUP BY estado");
$eventosPorEstado = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);
$estadosLabels = json_encode(array_column($eventosPorEstado, 'estado'));
$estadosData   = json_encode(array_column($eventosPorEstado, 'total'));

// Últimos 5 eventos
$stmtRecientes = $db->query("
    SELECT e.nombre, e.fecha_inicio, e.estado, e.cupo_maximo, e.cupo_disponible,
           u.nombre AS org_nombre, u.apellido AS org_apellido
    FROM eventos e
    LEFT JOIN usuarios u ON u.id = e.organizador_id
    ORDER BY e.fecha_creacion DESC LIMIT 5
");
$eventosRecientes = $stmtRecientes->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header_admin.php';
?>
<div class="page-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
    <div class="breadcrumb">Inicio / <strong>Panel Principal</strong></div>
</div>

<!-- Stats -->
<div class="stats-grid">

 <a href="../admin/eventos.php"> <!--redericcionamiento-->
    <div class="stat-card">
        <div class="stat-icon" style="background:#3498db;"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-info">
            <div class="value"><?php echo $totalEventos; ?></div>
            <div class="label">Total Eventos</div>
        </div>
    </div>
 </a>

    <div class="stat-card">
        <div class="stat-icon" style="background:#27ae60;"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <div class="value"><?php echo $totalParticipantes; ?></div>
            <div class="label">Participantes</div>
        </div>
    </div>

     <a href="../admin/inscripciones.php"> <!--redericcionamiento-->
    <div class="stat-card">
        <div class="stat-icon" style="background:#f39c12;"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-info">
            <div class="value"><?php echo $totalInscripciones; ?></div>
            <div class="label">Inscripciones Activas</div>
        </div>
    </div>
 </a>


     <a href="../admin/certificados.php"> <!--redericcionamiento-->
    <div class="stat-card">
        <div class="stat-icon" style="background:#9b59b6;"><i class="fas fa-certificate"></i></div>
        <div class="stat-info">
            <div class="value"><?php echo $totalCertificados; ?></div>
            <div class="label">Certificados Emitidos</div>
        </div>
    </div>
 </a>

</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px; flex-wrap:wrap;">
    <!-- Chart -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-chart-doughnut"></i> Eventos por Estado</span>
        </div>
        <canvas id="chartEstados" height="220"></canvas>
    </div>

    <!-- Accesos rápidos -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-bolt"></i> Accesos Rápidos</span>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <a href="<?php echo BASE_URL; ?>/admin/usuarios.php" class="btn btn-primary"><i class="fas fa-users"></i> Gestionar Usuarios</a>
            <a href="<?php echo BASE_URL; ?>/admin/eventos.php" class="btn btn-info"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a>
            <a href="<?php echo BASE_URL; ?>/admin/inscripciones.php" class="btn btn-warning"><i class="fas fa-clipboard-list"></i> Inscripciones</a>
            <a href="<?php echo BASE_URL; ?>/admin/asistencia.php" class="btn btn-success"><i class="fas fa-check-square"></i> Asistencia</a>
            <a href="<?php echo BASE_URL; ?>/admin/certificados.php" class="btn btn-secondary"><i class="fas fa-certificate"></i> Certificados</a>
            <a href="<?php echo BASE_URL; ?>/admin/reportes.php" class="btn btn-danger"><i class="fas fa-chart-bar"></i> Reportes</a>
        </div>
    </div>
</div>

<!-- Últimos eventos -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-history"></i> Últimos Eventos Registrados</span>
        <a href="<?php echo BASE_URL; ?>/admin/eventos.php" class="btn btn-primary btn-sm">Ver todos</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Evento</th><th>Fecha</th><th>Organizador</th><th>Cupo</th><th>Estado</th></tr>
            </thead>
            <tbody>
            <?php foreach ($eventosRecientes as $e): ?>
                <tr>
                    <td><?php echo htmlspecialchars($e['nombre']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($e['fecha_inicio'])); ?></td>
                    <td><?php echo htmlspecialchars($e['org_nombre'] . ' ' . $e['org_apellido']); ?></td>
                    <td><?php echo ($e['cupo_maximo'] - $e['cupo_disponible']); ?>/<?php echo $e['cupo_maximo']; ?></td>
                    <td>
                        <?php
                        $badges = ['programado'=>'info','en_curso'=>'success','finalizado'=>'secondary','cancelado'=>'danger'];
                        $b = $badges[$e['estado']] ?? 'secondary';
                        ?>
                        <span class="badge badge-<?php echo $b; ?>"><?php echo ucfirst($e['estado']); ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($eventosRecientes)): ?>
                <tr><td colspan="5" style="text-align:center;color:#888;">No hay eventos registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    var labels = <?php echo $estadosLabels; ?>;
    var data   = <?php echo $estadosData; ?>;
    var colors = ['#3498db','#27ae60','#95a5a6','#e74c3c'];
    new Chart(document.getElementById('chartEstados'), {
        type: 'doughnut',
        data: {
            labels: labels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
            datasets: [{ data: data, backgroundColor: colors.slice(0, labels.length), borderWidth: 2 }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
