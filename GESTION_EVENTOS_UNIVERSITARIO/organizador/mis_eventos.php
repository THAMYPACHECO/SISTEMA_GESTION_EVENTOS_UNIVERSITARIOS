<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(2);

$db  = getDB();
$uid = $_SESSION['user_id'];
$page_title = 'Mis Eventos';

$stmtEv = $db->prepare("
    SELECT e.id, e.nombre, e.fecha_inicio, e.fecha_fin, e.lugar, e.modalidad,
           e.cupo_maximo, e.cupo_disponible, e.estado,
           COUNT(r.id) AS inscritos_actuales
    FROM eventos e
    LEFT JOIN inscripciones r ON r.evento_id = e.id AND r.estado = 'activa'
    WHERE e.organizador_id = ?
    GROUP BY e.id
    ORDER BY e.fecha_inicio DESC
");
$stmtEv->execute([$uid]);
$eventos = $stmtEv->fetchAll(PDO::FETCH_ASSOC);

$estadoColors = [
    'programado' => '#17a2b8',
    'en_curso'   => '#28a745',
    'finalizado' => '#6c757d',
    'cancelado'  => '#dc3545',
];
$modalidadLabels = [
    'presencial' => '<i class="fas fa-map-marker-alt"></i> Presencial',
    'virtual'    => '<i class="fas fa-video"></i> Virtual',
    'hibrido'    => '<i class="fas fa-network-wired"></i> Híbrido',
];

require_once __DIR__ . '/../includes/header_org.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div>
        <h1 style="font-size:1.6rem;margin-bottom:4px;">Mis Eventos</h1>
        <p style="color:#666;font-size:0.9rem;">Gestiona los eventos que organizas</p>
    </div>
</div>

<div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);">
    <?php if (empty($eventos)): ?>
        <p style="text-align:center;color:#999;padding:40px;">No tienes eventos registrados. Contacta al administrador para que te asigne eventos.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.88rem;">
            <thead>
                <tr style="background:#f8f9fa;">
                    <th style="padding:11px 10px;text-align:left;border-bottom:2px solid #dee2e6;">Evento</th>
                    <th style="padding:11px 10px;text-align:left;border-bottom:2px solid #dee2e6;">Fecha</th>
                    <th style="padding:11px 10px;text-align:left;border-bottom:2px solid #dee2e6;">Lugar / Modalidad</th>
                    <th style="padding:11px 10px;text-align:center;border-bottom:2px solid #dee2e6;">Cupo</th>
                    <th style="padding:11px 10px;text-align:center;border-bottom:2px solid #dee2e6;">Inscritos</th>
                    <th style="padding:11px 10px;text-align:center;border-bottom:2px solid #dee2e6;">Estado</th>
                    <th style="padding:11px 10px;text-align:center;border-bottom:2px solid #dee2e6;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eventos as $ev):
                    $pct = $ev['cupo_maximo'] > 0 ? round($ev['inscritos_actuales'] / $ev['cupo_maximo'] * 100) : 0;
                    $barColor = $pct >= 90 ? '#dc3545' : ($pct >= 60 ? '#ffc107' : '#28a745');
                ?>
                <tr style="border-bottom:1px solid #f0f0f0;transition:background 0.15s;" onmouseover="this.style.background='#f9fffe'" onmouseout="this.style.background=''">
                    <td style="padding:10px;font-weight:600;max-width:200px;">
                        <?php echo htmlspecialchars($ev['nombre']); ?>
                    </td>
                    <td style="padding:10px;white-space:nowrap;">
                        <div><?php echo date('d/m/Y', strtotime($ev['fecha_inicio'])); ?></div>
                        <?php if ($ev['fecha_fin'] && $ev['fecha_fin'] !== $ev['fecha_inicio']): ?>
                            <div style="color:#999;font-size:0.8rem;">al <?php echo date('d/m/Y', strtotime($ev['fecha_fin'])); ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px;">
                        <div><?php echo htmlspecialchars($ev['lugar']); ?></div>
                        <div style="font-size:0.78rem;color:#888;margin-top:2px;"><?php echo $modalidadLabels[$ev['modalidad']] ?? $ev['modalidad']; ?></div>
                    </td>
                    <td style="padding:10px;text-align:center;"><?php echo $ev['cupo_maximo']; ?></td>
                    <td style="padding:10px;text-align:center;">
                        <div style="font-weight:600;"><?php echo $ev['inscritos_actuales']; ?> <span style="font-weight:400;color:#999;">/ <?php echo $ev['cupo_maximo']; ?></span></div>
                        <div style="background:#e9ecef;border-radius:4px;height:6px;margin-top:4px;">
                            <div style="background:<?php echo $barColor; ?>;width:<?php echo $pct; ?>%;height:6px;border-radius:4px;"></div>
                        </div>
                        <div style="font-size:0.75rem;color:#888;margin-top:2px;"><?php echo $pct; ?>%</div>
                    </td>
                    <td style="padding:10px;text-align:center;">
                        <span style="background:<?php echo $estadoColors[$ev['estado']] ?? '#6c757d'; ?>;color:#fff;padding:3px 10px;border-radius:20px;font-size:0.78rem;white-space:nowrap;">
                            <?php echo ucfirst($ev['estado']); ?>
                        </span>
                    </td>
                    <td style="padding:10px;text-align:center;">
                        <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                            <a href="inscritos.php?event_id=<?php echo $ev['id']; ?>"
                               style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;background:#17a2b8;color:#fff;border-radius:6px;text-decoration:none;font-size:0.82rem;font-weight:600;white-space:nowrap;"
                               title="Ver participantes inscritos">
                                <i class="fas fa-users"></i> Ver Inscritos
                            </a>
                            <a href="estadisticas.php?event_id=<?php echo $ev['id']; ?>"
                               style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;background:#fd7e14;color:#fff;border-radius:6px;text-decoration:none;font-size:0.82rem;font-weight:600;white-space:nowrap;"
                               title="Ver estadísticas">
                                <i class="fas fa-chart-bar"></i> Estadísticas
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top:12px;font-size:0.82rem;color:#999;">
        Total: <strong><?php echo count($eventos); ?></strong> evento(s).
    </div>
    <?php endif; ?>
</div>

</main></div>
<footer style="text-align:center;padding:16px;font-size:0.8rem;color:#999;background:#fff;border-top:1px solid #eee;margin-left:240px;">
    <?php echo APP_NAME; ?> &copy; <?php echo date('Y'); ?> — Módulo Organizador
</footer>
</body>
</html>
