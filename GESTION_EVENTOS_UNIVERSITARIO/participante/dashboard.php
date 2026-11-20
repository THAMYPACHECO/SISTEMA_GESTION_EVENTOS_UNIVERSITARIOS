<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(3);

$user_id = $_SESSION['user_id'];
$db = getDB();

// Stats
$stats = [];

// Eventos inscritos (activos)
$stmt = $db->prepare("SELECT COUNT(*) FROM inscripciones WHERE usuario_id = ? AND estado = 'activa'");
$stmt->execute([$user_id]);
$stats['inscritos'] = $stmt->fetchColumn();

// Asistencias registradas (presente)
$stmt = $db->prepare("SELECT COUNT(*) FROM asistencias WHERE usuario_id = ? AND presente = 1");
$stmt->execute([$user_id]);
$stats['asistencias'] = $stmt->fetchColumn();

// Certificados disponibles
$stmt = $db->prepare("SELECT COUNT(*) FROM certificados WHERE usuario_id = ? AND estado = 'emitido'");
$stmt->execute([$user_id]);
$stats['certificados'] = $stmt->fetchColumn();

// Próximos eventos inscritos
$stmt = $db->prepare("
    SELECT e.id, e.nombre, e.fecha_inicio, e.hora_inicio, e.lugar, e.modalidad, e.estado,
           r.id AS registration_id, r.fecha_inscripcion
    FROM inscripciones r
    JOIN eventos e ON e.id = r.evento_id
    WHERE r.usuario_id = ? AND r.estado = 'activa' AND e.estado IN ('programado','en_curso')
    ORDER BY e.fecha_inicio ASC
    LIMIT 5
");
$stmt->execute([$user_id]);
$proximosEventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header_part.php';
include __DIR__ . '/../includes/sidebar_part.php';
?>

<main class="main-content">
    <div style="margin-bottom:1.5rem;">
        <h1 style="font-size:1.6rem;color:#1e293b;font-weight:700;">
            <i class="fas fa-tachometer-alt" style="color:#2563eb;"></i> Mi Panel
        </h1>
        <p style="color:#64748b;margin-top:.3rem;">Bienvenido, <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong>. Aquí está tu resumen.</p>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div style="background:<?= $_SESSION['msg_type'] === 'success' ? '#dcfce7' : '#fee2e2' ?>;border:1px solid <?= $_SESSION['msg_type'] === 'success' ? '#86efac' : '#fca5a5' ?>;color:<?= $_SESSION['msg_type'] === 'success' ? '#166534' : '#991b1b' ?>;padding:.85rem 1.2rem;border-radius:10px;margin-bottom:1.2rem;display:flex;align-items:center;gap:.7rem;">
            <i class="fas fa-<?= $_SESSION['msg_type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($_SESSION['mensaje']) ?>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['msg_type']); ?>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.2rem;margin-bottom:2rem;">
        <div style="background:white;border-radius:14px;padding:1.5rem;box-shadow:0 1px 6px rgba(0,0,0,0.07);border-left:4px solid #2563eb;display:flex;align-items:center;gap:1.2rem;">
            <div style="background:#eff6ff;width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-calendar-check" style="color:#2563eb;font-size:1.4rem;"></i>
            </div>
            <div>
                <div style="font-size:2rem;font-weight:800;color:#1e293b;"><?= $stats['inscritos'] ?></div>
                <div style="color:#64748b;font-size:0.85rem;">Eventos Inscritos</div>
            </div>
        </div>
        <div style="background:white;border-radius:14px;padding:1.5rem;box-shadow:0 1px 6px rgba(0,0,0,0.07);border-left:4px solid #22c55e;display:flex;align-items:center;gap:1.2rem;">
            <div style="background:#f0fdf4;width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-user-check" style="color:#22c55e;font-size:1.4rem;"></i>
            </div>
            <div>
                <div style="font-size:2rem;font-weight:800;color:#1e293b;"><?= $stats['asistencias'] ?></div>
                <div style="color:#64748b;font-size:0.85rem;">Asistencias Registradas</div>
            </div>
        </div>
        <div style="background:white;border-radius:14px;padding:1.5rem;box-shadow:0 1px 6px rgba(0,0,0,0.07);border-left:4px solid #f59e0b;display:flex;align-items:center;gap:1.2rem;">
            <div style="background:#fffbeb;width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-certificate" style="color:#f59e0b;font-size:1.4rem;"></i>
            </div>
            <div>
                <div style="font-size:2rem;font-weight:800;color:#1e293b;"><?= $stats['certificados'] ?></div>
                <div style="color:#64748b;font-size:0.85rem;">Certificados</div>
            </div>
        </div>
    </div>

    <!-- Próximos Eventos -->
    <div style="background:white;border-radius:14px;padding:1.5rem;box-shadow:0 1px 6px rgba(0,0,0,0.07);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
            <h2 style="font-size:1.1rem;font-weight:700;color:#1e293b;"><i class="fas fa-clock" style="color:#2563eb;"></i> Próximos Eventos</h2>
            <a href="<?= BASE_URL ?>/participante/mis_inscripciones.php" style="font-size:0.85rem;color:#2563eb;text-decoration:none;">Ver todos <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php if (empty($proximosEventos)): ?>
            <div style="text-align:center;padding:2.5rem;color:#94a3b8;">
                <i class="fas fa-calendar-xmark" style="font-size:2.5rem;margin-bottom:.8rem;display:block;"></i>
                <p>No tienes eventos próximos inscritos.</p>
                <a href="<?= BASE_URL ?>/participante/eventos_disponibles.php" style="display:inline-block;margin-top:.8rem;background:#2563eb;color:white;padding:.5rem 1.2rem;border-radius:8px;text-decoration:none;font-size:.88rem;">Explorar Eventos</a>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.9rem;">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th style="text-align:left;padding:.7rem 1rem;color:#93c5fd;font-weight:600;border-bottom:2px solid #e2e8f0;">Evento</th>
                            <th style="text-align:left;padding:.7rem 1rem;color:#93c5fd;font-weight:600;border-bottom:2px solid #e2e8f0;">Fecha</th>
                            <th style="text-align:left;padding:.7rem 1rem;color:#93c5fd;font-weight:600;border-bottom:2px solid #e2e8f0;">Lugar</th>
                            <th style="text-align:left;padding:.7rem 1rem;color:#93c5fd;font-weight:600;border-bottom:2px solid #e2e8f0;">Modalidad</th>
                            <th style="text-align:left;padding:.7rem 1rem;color:#93c5fd;font-weight:600;border-bottom:2px solid #e2e8f0;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proximosEventos as $ev): ?>
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:.7rem 1rem;font-weight:600;color:#1e293b;"><?= htmlspecialchars($ev['nombre']) ?></td>
                            <td style="padding:.7rem 1rem;color:#475569;">
                                <?= date('d/m/Y', strtotime($ev['fecha_inicio'])) ?>
                                <?= $ev['hora_inicio'] ? ' ' . substr($ev['hora_inicio'], 0, 5) : '' ?>
                            </td>
                            <td style="padding:.7rem 1rem;color:#475569;"><?= htmlspecialchars($ev['lugar'] ?? '—') ?></td>
                            <td style="padding:.7rem 1rem;">
                                <?php
                                $mod = $ev['modalidad'];
                                $modColor = ['presencial' => '#16a34a', 'virtual' => '#2563eb', 'hibrido' => '#d97706'][$mod] ?? '#64748b';
                                $modBg   = ['presencial' => '#dcfce7', 'virtual' => '#dbeafe', 'hibrido' => '#fef3c7'][$mod] ?? '#f1f5f9';
                                ?>
                                <span style="background:<?= $modBg ?>;color:<?= $modColor ?>;padding:.25rem .7rem;border-radius:20px;font-size:.78rem;font-weight:600;text-transform:capitalize;"><?= htmlspecialchars($mod) ?></span>
                            </td>
                            <td style="padding:.7rem 1rem;">
                                <?php
                                $estColor = ['programado'=>'#1d4ed8','en_curso'=>'#15803d'][$ev['estado']] ?? '#64748b';
                                $estBg    = ['programado'=>'#dbeafe','en_curso'=>'#dcfce7'][$ev['estado']] ?? '#f1f5f9';
                                $estLabel = ['programado'=>'Programado','en_curso'=>'En Curso'][$ev['estado']] ?? $ev['estado'];
                                ?>
                                <span style="background:<?= $estBg ?>;color:<?= $estColor ?>;padding:.25rem .7rem;border-radius:20px;font-size:.78rem;font-weight:600;"><?= $estLabel ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Accesos Rápidos -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-top:1.5rem;">
        <a href="<?= BASE_URL ?>/participante/eventos_disponibles.php" style="background:white;border:2px solid #dbeafe;border-radius:12px;padding:1.2rem;text-align:center;text-decoration:none;color:#1e293b;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(37,99,235,.12)'" onmouseout="this.style.boxShadow='none'">
            <i class="fas fa-search" style="font-size:1.6rem;color:#2563eb;margin-bottom:.5rem;display:block;"></i>
            <span style="font-weight:600;font-size:.9rem;">Buscar Eventos</span>
        </a>
        <a href="<?= BASE_URL ?>/participante/mis_inscripciones.php" style="background:white;border:2px solid #dcfce7;border-radius:12px;padding:1.2rem;text-align:center;text-decoration:none;color:#1e293b;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(34,197,94,.12)'" onmouseout="this.style.boxShadow='none'">
            <i class="fas fa-list-check" style="font-size:1.6rem;color:#22c55e;margin-bottom:.5rem;display:block;"></i>
            <span style="font-weight:600;font-size:.9rem;">Mis Inscripciones</span>
        </a>
        <a href="<?= BASE_URL ?>/participante/mi_perfil.php" style="background:white;border:2px solid #fef3c7;border-radius:12px;padding:1.2rem;text-align:center;text-decoration:none;color:#1e293b;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(245,158,11,.12)'" onmouseout="this.style.boxShadow='none'">
            <i class="fas fa-user-edit" style="font-size:1.6rem;color:#f59e0b;margin-bottom:.5rem;display:block;"></i>
            <span style="font-weight:600;font-size:.9rem;">Mi Perfil</span>
        </a>
    </div>
</main>

</body>
</html>
