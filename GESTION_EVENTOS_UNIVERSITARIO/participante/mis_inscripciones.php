<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(3);

$user_id = $_SESSION['user_id'];
$db = getDB();

// Obtener inscripciones con datos de asistencia y certificado
$stmt = $db->prepare("
    SELECT
        r.id AS registration_id,
        r.fecha_inscripcion,
        r.estado AS inscripcion_estado,
        e.id AS event_id,
        e.nombre AS event_nombre,
        e.fecha_inicio,
        e.fecha_fin,
        e.hora_inicio,
        e.lugar,
        e.modalidad,
        e.estado AS event_estado,
        a.presente,
        c.id AS cert_id,
        c.codigo_certificado,
        c.estado AS cert_estado
    FROM inscripciones r
    JOIN eventos e ON e.id = r.evento_id
    LEFT JOIN asistencias a ON a.usuario_id = r.usuario_id AND a.evento_id = r.evento_id
    LEFT JOIN certificados c ON c.usuario_id = r.usuario_id AND c.evento_id = r.evento_id AND c.estado = 'emitido'
    WHERE r.usuario_id = ?
    ORDER BY r.fecha_inscripcion DESC
");
$stmt->execute([$user_id]);
$inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Mis Inscripciones';
include __DIR__ . '/../includes/header_part.php';
include __DIR__ . '/../includes/sidebar_part.php';
?>
<main class="main-content">
    <div style="margin-bottom:1.5rem;">
        <h1 style="font-size:1.6rem;color:#1e293b;font-weight:700;"><i class="fas fa-list-check" style="color:#2563eb;"></i> Mis Inscripciones</h1>
        <p style="color:#64748b;margin-top:.3rem;">Historial completo de tus inscripciones a eventos.</p>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div style="background:<?= $_SESSION['msg_type']==='success'?'#dcfce7':'#fee2e2' ?>;border:1px solid <?= $_SESSION['msg_type']==='success'?'#86efac':'#fca5a5' ?>;color:<?= $_SESSION['msg_type']==='success'?'#166534':'#991b1b' ?>;padding:.85rem 1.2rem;border-radius:10px;margin-bottom:1.2rem;display:flex;align-items:center;gap:.7rem;">
            <i class="fas fa-<?= $_SESSION['msg_type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($_SESSION['mensaje']) ?>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['msg_type']); ?>
    <?php endif; ?>

    <?php if (empty($inscripciones)): ?>
        <div style="background:white;border-radius:14px;padding:3rem;text-align:center;color:#94a3b8;box-shadow:0 1px 6px rgba(0,0,0,0.07);">
            <i class="fas fa-inbox" style="font-size:3rem;margin-bottom:1rem;display:block;"></i>
            <p style="font-size:1rem;">Aún no te has inscrito en ningún evento.</p>
            <a href="<?= BASE_URL ?>/participante/eventos_disponibles.php" style="display:inline-block;margin-top:1rem;background:#2563eb;color:white;padding:.55rem 1.4rem;border-radius:8px;text-decoration:none;font-weight:600;font-size:.9rem;">Explorar Eventos</a>
        </div>
    <?php else: ?>
        <div style="background:white;border-radius:14px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,0.07);">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.88rem;">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th style="text-align:left;padding:.85rem 1rem;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0;">Evento</th>
                            <th style="text-align:left;padding:.85rem 1rem;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0;">Fecha Evento</th>
                            <th style="text-align:left;padding:.85rem 1rem;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0;">F. Inscripción</th>
                            <th style="text-align:center;padding:.85rem 1rem;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0;">Estado</th>
                            <th style="text-align:center;padding:.85rem 1rem;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0;">Asistencia</th>
                            <th style="text-align:center;padding:.85rem 1rem;color:#64748b;font-weight:600;border-bottom:2px solid #e2e8f0;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inscripciones as $ins):
                            // ¿Se puede cancelar? activa + evento no iniciado aún
                            $puedeCancel = $ins['inscripcion_estado'] === 'activa'
                                && in_array($ins['event_estado'], ['programado'])
                                && strtotime($ins['fecha_inicio']) > time();

                            // Asistencia
                            if ($ins['presente'] === null) {
                                $asistLabel = 'No registrada'; $asistColor = '#94a3b8'; $asistBg = '#f1f5f9'; $asistIcon = 'fa-clock';
                            } elseif ($ins['presente'] == 1) {
                                $asistLabel = 'Presente'; $asistColor = '#15803d'; $asistBg = '#dcfce7'; $asistIcon = 'fa-check';
                            } else {
                                $asistLabel = 'Ausente'; $asistColor = '#b91c1c'; $asistBg = '#fee2e2'; $asistIcon = 'fa-times';
                            }

                            // Estado inscripción
                            $estColors = ['activa' => ['#15803d','#dcfce7'], 'cancelada' => ['#b91c1c','#fee2e2']];
                            [$estC, $estBg] = $estColors[$ins['inscripcion_estado']] ?? ['#64748b','#f1f5f9'];
                        ?>
                        <tr style="border-bottom:1px solid #f1f5f9;<?= $ins['inscripcion_estado']==='cancelada'?'opacity:.65;':'' ?>">
                            <td style="padding:.85rem 1rem;">
                                <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($ins['event_nombre']) ?></div>
                                <?php if ($ins['lugar']): ?>
                                    <small style="color:#94a3b8;"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($ins['lugar']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="padding:.85rem 1rem;color:#475569;white-space:nowrap;">
                                <?= date('d/m/Y', strtotime($ins['fecha_inicio'])) ?>
                                <?= $ins['hora_inicio'] ? '<br><small style="color:#94a3b8;">' . substr($ins['hora_inicio'],0,5) . '</small>' : '' ?>
                            </td>
                            <td style="padding:.85rem 1rem;color:#475569;white-space:nowrap;">
                                <?= date('d/m/Y', strtotime($ins['fecha_inscripcion'])) ?>
                            </td>
                            <td style="padding:.85rem 1rem;text-align:center;">
                                <span style="background:<?= $estBg ?>;color:<?= $estC ?>;padding:.25rem .75rem;border-radius:20px;font-size:.78rem;font-weight:600;text-transform:capitalize;"><?= $ins['inscripcion_estado'] ?></span>
                            </td>
                            <td style="padding:.85rem 1rem;text-align:center;">
                                <span style="background:<?= $asistBg ?>;color:<?= $asistColor ?>;padding:.25rem .75rem;border-radius:20px;font-size:.78rem;font-weight:600;">
                                    <i class="fas <?= $asistIcon ?>"></i> <?= $asistLabel ?>
                                </span>
                            </td>
                            <td style="padding:.85rem 1rem;text-align:center;">
                                <div style="display:flex;gap:.4rem;justify-content:center;flex-wrap:wrap;">
                                    <?php if ($puedeCancel): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/participante/cancelar_inscripcion.php"
                                              onsubmit="return confirm('¿Cancelar inscripción en este evento?')">
                                            <input type="hidden" name="registration_id" value="<?= $ins['registration_id'] ?>">
                                            <button type="submit" style="background:#fee2e2;color:#991b1b;border:none;padding:.35rem .8rem;border-radius:7px;cursor:pointer;font-size:.8rem;font-weight:600;">
                                                <i class="fas fa-times-circle"></i> Cancelar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($ins['cert_id']): ?>
                                        <a href="<?= BASE_URL ?>/participante/mi_certificado.php?cert_id=<?= $ins['cert_id'] ?>"
                                           style="background:#fffbeb;color:#b45309;padding:.35rem .8rem;border-radius:7px;text-decoration:none;font-size:.8rem;font-weight:600;border:1px solid #fde68a;">
                                            <i class="fas fa-award"></i> Certificado
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($ins['inscripcion_estado']==='activa' && !$ins['cert_id'] && !$puedeCancel): ?>
                                        <span style="color:#94a3b8;font-size:.78rem;">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
