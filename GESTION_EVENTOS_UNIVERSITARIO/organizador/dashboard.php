<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(2);

$db = getDB();
$uid = $_SESSION['user_id'];
$page_title = 'Dashboard';

// --- Stats del organizador ---
// Mis eventos
$stmtEv = $db->prepare("SELECT COUNT(*) FROM eventos WHERE organizador_id = ?");
$stmtEv->execute([$uid]);
$totalEventos = $stmtEv->fetchColumn();

// Inscritos totales en mis eventos
$stmtIn = $db->prepare("
    SELECT COUNT(*) FROM inscripciones r
    JOIN eventos e ON r.evento_id = e.id
    WHERE e.organizador_id = ? AND r.estado = 'activa'
");
$stmtIn->execute([$uid]);
$totalInscritos = $stmtIn->fetchColumn();

// Asistentes confirmados
$stmtAs = $db->prepare("
    SELECT COUNT(*) FROM asistencias a
    JOIN eventos e ON a.evento_id = e.id
    WHERE e.organizador_id = ? AND a.presente = 1
");
$stmtAs->execute([$uid]);
$totalAsistentes = $stmtAs->fetchColumn();

// Certificados emitidos
$stmtCert = $db->prepare("
    SELECT COUNT(*) FROM certificados c
    JOIN eventos e ON c.evento_id = e.id
    WHERE e.organizador_id = ? AND c.estado = 'emitido'
");
$stmtCert->execute([$uid]);
$totalCertificados = $stmtCert->fetchColumn();

// Eventos recientes propios
$stmtRec = $db->prepare("
    SELECT e.id, e.nombre, e.fecha_inicio, e.fecha_fin, e.hora_inicio, e.hora_fin, e.lugar, e.estado, e.cupo_maximo,
           COUNT(r.id) AS inscritos
    FROM eventos e
    LEFT JOIN inscripciones r ON r.evento_id = e.id AND r.estado = 'activa'
    WHERE e.organizador_id = ?
    GROUP BY e.id
    ORDER BY e.fecha_creacion DESC
    LIMIT 5
");
$stmtRec->execute([$uid]);
$eventosRecientes = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

$estadoColors = [
    'programado'  => '#17a2b8',
    'en_curso'    => '#28a745',
    'finalizado'  => '#6c757d',
    'cancelado'   => '#dc3545',
];

require_once __DIR__ . '/../includes/header_org.php';
?>
<h1 style="font-size:1.6rem;margin-bottom:6px;">Dashboard del Organizador</h1>
<p style="color:#666;margin-bottom:24px;">Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong>. Aquí tienes el resumen de tu actividad.</p>

<!-- Tarjetas de stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px;">
    <?php
    $stats = [
        ['label' => 'Mis Eventos',         'value' => $totalEventos,     'icon' => 'calendar-alt',    'color' => '#2c7a3e'],
        ['label' => 'Inscritos Totales',   'value' => $totalInscritos,   'icon' => 'users',           'color' => '#17a2b8'],
        ['label' => 'Asistentes',          'value' => $totalAsistentes,  'icon' => 'clipboard-check', 'color' => '#ffc107'],
        ['label' => 'Certificados Emitidos','value' => $totalCertificados,'icon' => 'certificate',    'color' => '#6f42c1'],
    ];
    foreach ($stats as $s): ?>
    <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);display:flex;align-items:center;gap:16px;">
        <div style="background:<?php echo $s['color']; ?>;color:#fff;width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
            <i class="fas fa-<?php echo $s['icon']; ?>"></i>
        </div>
        <div>
            <div style="font-size:1.8rem;font-weight:700;line-height:1;"><?php echo number_format($s['value']); ?></div>
            <div style="font-size:0.82rem;color:#666;margin-top:2px;"><?php echo $s['label']; ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Accesos rápidos -->
<div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);margin-bottom:30px;">
    <h2 style="font-size:1.1rem;margin-bottom:16px;color:#2c7a3e;"><i class="fas fa-bolt"></i> Accesos Rápidos</h2>
    <div style="display:flex;flex-wrap:wrap;gap:12px;">
        <?php
        $accesos = [
            ['mis_eventos.php',     'calendar-alt',   'Mis Eventos',          '#2c7a3e'],
            ['inscritos.php',       'users',          'Ver Inscritos',         '#17a2b8'],
            ['estadisticas.php',    'chart-bar',      'Estadísticas',          '#fd7e14'],
            ['certificados_org.php','certificate',    'Certificados',          '#6f42c1'],
        ];
        foreach ($accesos as $a): ?>
        <a href="<?php echo BASE_URL; ?>/organizador/<?php echo $a[0]; ?>" style="
            display:flex;align-items:center;gap:8px;padding:10px 18px;
            background:<?php echo $a[3]; ?>;color:#fff;border-radius:8px;text-decoration:none;
            font-size:0.88rem;font-weight:500;transition:opacity 0.2s;"
            onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
            <i class="fas fa-<?php echo $a[1]; ?>"></i> <?php echo $a[2]; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Eventos recientes -->
<div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);">
    <h2 style="font-size:1.1rem;margin-bottom:16px;color:#2c7a3e;"><i class="fas fa-history"></i> Mis Eventos Recientes</h2>
    <?php if (empty($eventosRecientes)): ?>
        <p style="color:#999;text-align:center;padding:20px;">No tienes eventos registrados aún.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.88rem;">
            <thead>
                <tr style="background:#f8f9fa;">
                    <th style="padding:10px;text-align:left;border-bottom:2px solid #dee2e6;">Evento</th>
                    <th style="padding:10px;text-align:left;border-bottom:2px solid #dee2e6;">Fecha Inicio / Fin</th>
                    <th style="padding:10px;text-align:left;border-bottom:2px solid #dee2e6;">Lugar</th>
                    <th style="padding:10px;text-align:center;border-bottom:2px solid #dee2e6;">Inscritos</th>
                    <th style="padding:10px;text-align:center;border-bottom:2px solid #dee2e6;">Estado</th>
                    <th style="padding:10px;text-align:center;border-bottom:2px solid #dee2e6;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eventosRecientes as $ev): ?>
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:10px;font-weight:500;"><?php echo htmlspecialchars($ev['nombre']); ?></td>
                    <td style="padding:10px;white-space:nowrap;">
                        <div style="font-weight:600;color:#1e293b;">
                            <i class="far fa-calendar-alt" style="color:#2c7a3e;"></i>
                            <?php echo date('d/m/Y', strtotime($ev['fecha_inicio'])); ?>
                            <?php if (!empty($ev['hora_inicio'])): ?>
                                <span style="color:#64748b;font-weight:normal;font-size:0.8rem;"><?php echo substr($ev['hora_inicio'], 0, 5); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($ev['fecha_fin'])): ?>
                            <div style="font-size:0.78rem;color:#64748b;margin-top:2px;">
                                <i class="far fa-calendar-check" style="color:#6c757d;"></i> Fin: <?php echo date('d/m/Y', strtotime($ev['fecha_fin'])); ?>
                                <?php if (!empty($ev['hora_fin'])): ?>
                                    <span><?php echo substr($ev['hora_fin'], 0, 5); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px;"><?php echo htmlspecialchars($ev['lugar']); ?></td>
                    <td style="padding:10px;text-align:center;">
                        <span style="font-weight:600;"><?php echo $ev['inscritos']; ?></span>
                        <span style="color:#999;">/<?php echo $ev['cupo_maximo']; ?></span>
                    </td>
                    <td style="padding:10px;text-align:center;">
                        <span style="background:<?php echo $estadoColors[$ev['estado']] ?? '#6c757d'; ?>;color:#fff;padding:3px 10px;border-radius:20px;font-size:0.78rem;">
                            <?php echo ucfirst($ev['estado']); ?>
                        </span>
                    </td>
                    <td style="padding:10px;text-align:center;display:flex;gap:6px;justify-content:center;">
                        <a href="inscritos.php?event_id=<?php echo $ev['id']; ?>" style="color:#17a2b8;text-decoration:none;" title="Ver inscritos y registrar asistencia"><i class="fas fa-users"></i></a>
                        <a href="estadisticas.php?event_id=<?php echo $ev['id']; ?>" style="color:#fd7e14;text-decoration:none;" title="Estadísticas"><i class="fas fa-chart-bar"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="text-align:right;margin-top:12px;">
        <a href="mis_eventos.php" style="color:#2c7a3e;text-decoration:none;font-size:0.88rem;font-weight:500;">Ver todos mis eventos <i class="fas fa-arrow-right"></i></a>
    </div>
    <?php endif; ?>
</div>

</main></div>
<footer style="text-align:center;padding:16px;font-size:0.8rem;color:#999;background:#fff;border-top:1px solid #eee;margin-left:240px;">
    <?php echo APP_NAME; ?> &copy; <?php echo date('Y'); ?> — Módulo Organizador
</footer>
</body>
</html>
