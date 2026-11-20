<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(2);

$db  = getDB();
$uid = $_SESSION['user_id'];
$page_title = 'Inscritos del Evento';

// ─── Handler POST: cambiar estado de asistencia ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cambiar_asistencia') {
    $usuarioId     = (int)($_POST['usuario_id']     ?? 0);
    $eventoIdPost  = (int)($_POST['evento_id']      ?? 0);
    $inscripcionId = (int)($_POST['inscripcion_id'] ?? 0);
    $nuevoEstado   = $_POST['estado_asistencia']    ?? '';

    // Verificar que el evento pertenece a este organizador
    $stmtVerif = $db->prepare("SELECT id FROM eventos WHERE id = ? AND organizador_id = ?");
    $stmtVerif->execute([$eventoIdPost, $uid]);
    $ok = $stmtVerif->fetchColumn();

    if ($ok && $usuarioId > 0 && $eventoIdPost > 0) {
        if ($nuevoEstado === 'pendiente') {
            // Eliminar registro → queda "Pendiente"
            $stmtDel = $db->prepare("DELETE FROM asistencias WHERE usuario_id = ? AND evento_id = ?");
            $stmtDel->execute([$usuarioId, $eventoIdPost]);
        } elseif ($nuevoEstado === '1' || $nuevoEstado === '0') {
            $presente = (int)$nuevoEstado;
            // Upsert
            $stmtUp = $db->prepare("
                INSERT INTO asistencias (inscripcion_id, usuario_id, evento_id, presente, registrado_por)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE presente = VALUES(presente), registrado_por = VALUES(registrado_por)
            ");
            $stmtUp->execute([
                $inscripcionId ?: null,
                $usuarioId,
                $eventoIdPost,
                $presente,
                $uid
            ]);
        }
        header('Location: inscritos.php?event_id=' . $eventoIdPost . '&msg=actualizado');
        exit;
    }

    header('Location: inscritos.php?event_id=' . $eventoIdPost . '&msg=error');
    exit;
}
// ─────────────────────────────────────────────────────────────────────────────

// 1. Obtener todos los eventos asignados al organizador
$stmtMisEv = $db->prepare("SELECT id, nombre, fecha_inicio, lugar, estado FROM eventos WHERE organizador_id = ? ORDER BY fecha_inicio DESC");
$stmtMisEv->execute([$uid]);
$misEventos = $stmtMisEv->fetchAll(PDO::FETCH_ASSOC);

// 2. Determinar evento seleccionado (o el primero por defecto)
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$event_id && !empty($misEventos)) {
    $event_id = (int)$misEventos[0]['id'];
}

$evento = null;
if ($event_id) {
    foreach ($misEventos as $ev) {
        if ((int)$ev['id'] === $event_id) {
            $evento = $ev;
            break;
        }
    }
}

// 3. Obtener lista de inscritos con id de inscripción
$inscritos = [];
if ($evento) {
    $stmtIn = $db->prepare("
        SELECT u.id AS usuario_id, u.nombre, u.apellido, u.email, u.telefono, u.institucion,
               r.id AS inscripcion_id, r.fecha_inscripcion, r.estado AS estado_reg,
               a.presente
        FROM inscripciones r
        JOIN usuarios u ON r.usuario_id = u.id
        LEFT JOIN asistencias a ON a.usuario_id = u.id AND a.evento_id = ?
        WHERE r.evento_id = ?
        ORDER BY u.apellido, u.nombre
    ");
    $stmtIn->execute([$event_id, $event_id]);
    $inscritos = $stmtIn->fetchAll(PDO::FETCH_ASSOC);
}

// 4. Modo Imprimir / Exportar
$imprimir = isset($_GET['imprimir']) && $_GET['imprimir'] == '1';
if ($imprimir && $evento): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Inscritos — <?= htmlspecialchars($evento['nombre']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; padding: 25px; color: #333; }
        h1 { font-size: 18px; margin-bottom: 6px; }
        .meta { margin: 10px 0 20px; color: #555; line-height: 1.5; font-size: 13px; border-bottom: 1px solid #ddd; padding-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px 10px; text-align: left; }
        th { background: #f2f2f2; font-weight: bold; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
<h1>Lista Oficial de Participantes Inscritos</h1>
<div class="meta">
    <strong>Evento:</strong> <?= htmlspecialchars($evento['nombre']); ?><br>
    <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($evento['fecha_inicio'])); ?> | 
    <strong>Lugar:</strong> <?= htmlspecialchars($evento['lugar'] ?: 'Campus Universitario'); ?> | 
    <strong>Total inscritos:</strong> <?= count($inscritos); ?>
</div>
<button onclick="window.print()" style="margin-bottom:15px;padding:8px 18px;background:#2c7a3e;color:#fff;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">🖨 Imprimir Listado</button>
<table>
    <thead>
        <tr>
            <th style="width:30px;">#</th>
            <th>Participante</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Institución</th>
            <th>Fecha Inscripción</th>
            <th>Asistencia</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($inscritos as $i => $ins):
            if ($ins['presente'] === null) $asistTxt = 'Pendiente';
            elseif ($ins['presente'] == 1) $asistTxt = 'Se presentó';
            else $asistTxt = 'No se presentó';
        ?>
        <tr>
            <td><?= $i + 1; ?></td>
            <td><strong><?= htmlspecialchars($ins['apellido'] . ', ' . $ins['nombre']); ?></strong></td>
            <td><?= htmlspecialchars($ins['email']); ?></td>
            <td><?= htmlspecialchars($ins['telefono'] ?: '—'); ?></td>
            <td><?= htmlspecialchars($ins['institucion'] ?: '—'); ?></td>
            <td><?= date('d/m/Y H:i', strtotime($ins['fecha_inscripcion'])); ?></td>
            <td><?= $asistTxt; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<p style="margin-top:20px;font-size:11px;color:#888;">Reporte generado el <?= date('d/m/Y H:i'); ?> por <?= htmlspecialchars($_SESSION['nombre'] ?? ''); ?> (Organizador)</p>
</body>
</html>
<?php
    exit;
endif;

require_once __DIR__ . '/../includes/header_org.php';
?>

<?php
// Mensaje de confirmación / error
$msg = $_GET['msg'] ?? '';
if ($msg === 'actualizado'): ?>
<div style="background:#dcfce7;color:#166534;border:1px solid #bbf7d0;border-radius:8px;padding:12px 18px;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-check-circle"></i> <strong>Asistencia actualizada correctamente.</strong>
</div>
<?php elseif ($msg === 'error'): ?>
<div style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;border-radius:8px;padding:12px 18px;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-exclamation-circle"></i> <strong>Error al actualizar la asistencia. Intenta de nuevo.</strong>
</div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-size:1.5rem;margin-bottom:4px;">
            <i class="fas fa-users" style="color:#2c7a3e;"></i> Participantes Inscritos
        </h1>
        <p style="color:#666;font-size:0.88rem;">Consulta y gestiona la asistencia de los participantes de tus eventos</p>
    </div>
    <?php if ($evento): ?>
    <div style="display:flex;gap:10px;">
        <a href="inscritos.php?event_id=<?= $event_id; ?>&imprimir=1" target="_blank"
           style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#6f42c1;color:#fff;border-radius:8px;text-decoration:none;font-size:0.88rem;font-weight:600;">
            <i class="fas fa-print"></i> Exportar / Imprimir
        </a>
        <a href="mis_eventos.php" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;font-size:0.88rem;">
            <i class="fas fa-arrow-left"></i> Mis Eventos
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Selector de Evento -->
<div style="background:#fff;border-radius:12px;padding:16px 20px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);">
    <?php if (empty($misEventos)): ?>
        <p style="color:#888;margin:0;"><i class="fas fa-info-circle"></i> No tienes eventos asignados todavía. Contacta al administrador para asignarte eventos.</p>
    <?php else: ?>
        <form method="GET" action="inscritos.php" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <label style="font-weight:600;font-size:0.92rem;color:#2c3e50;">
                <i class="fas fa-calendar-alt" style="color:#2c7a3e;"></i> Seleccionar Evento:
            </label>
            <select name="event_id" onchange="this.form.submit()" style="padding:9px 14px;border:1px solid #ced4da;border-radius:8px;font-size:0.92rem;min-width:320px;background:#fff;color:#333;">
                <?php foreach ($misEventos as $ev): ?>
                    <option value="<?= $ev['id']; ?>" <?= (int)$ev['id'] === (int)$event_id ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($ev['nombre']); ?> (<?= date('d/m/Y', strtotime($ev['fecha_inicio'])); ?> - <?= ucfirst($ev['estado']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" style="padding:8px 16px;background:#2c7a3e;color:#fff;border:none;border-radius:6px;cursor:pointer;">Cargar</button></noscript>
        </form>
    <?php endif; ?>
</div>

<?php if ($evento): ?>
<div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);">
    <div style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #eee;padding-bottom:12px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:1.15rem;color:#1e293b;margin-bottom:4px;"><?= htmlspecialchars($evento['nombre']); ?></h2>
            <small style="color:#64748b;">
                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($evento['lugar'] ?: 'Campus'); ?> &nbsp;|&nbsp; 
                <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($evento['fecha_inicio'])); ?>
            </small>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <?php
            $totalInscritos = count($inscritos);
            $presentados = 0; $noPresentados = 0; $pendientes = 0;
            foreach ($inscritos as $ins) {
                if ($ins['presente'] === null) $pendientes++;
                elseif ($ins['presente'] == 1) $presentados++;
                else $noPresentados++;
            }
            ?>
            <span style="background:#e0f2fe;color:#0369a1;padding:6px 14px;border-radius:20px;font-weight:600;font-size:0.82rem;">
                <i class="fas fa-users"></i> Total: <?= $totalInscritos; ?>
            </span>
            <span style="background:#dcfce7;color:#166534;padding:6px 14px;border-radius:20px;font-weight:600;font-size:0.82rem;">
                <i class="fas fa-check"></i> Se presentaron: <?= $presentados; ?>
            </span>
            <span style="background:#fee2e2;color:#991b1b;padding:6px 14px;border-radius:20px;font-weight:600;font-size:0.82rem;">
                <i class="fas fa-times"></i> No se presentaron: <?= $noPresentados; ?>
            </span>
            <span style="background:#fef9c3;color:#854d0e;padding:6px 14px;border-radius:20px;font-weight:600;font-size:0.82rem;">
                <i class="fas fa-clock"></i> Pendientes: <?= $pendientes; ?>
            </span>
        </div>
    </div>

    <?php if (empty($inscritos)): ?>
        <p style="text-align:center;color:#94a3b8;padding:40px 20px;">
            <i class="fas fa-user-slash" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:0.5;"></i>
            Aún no hay participantes inscritos en este evento.
        </p>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.88rem;">
            <thead>
                <tr style="background:#f8fafc;color:#475569;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:10px;text-align:left;width:40px;">#</th>
                    <th style="padding:10px;text-align:left;">Participante</th>
                    <th style="padding:10px;text-align:left;">Email</th>
                    <th style="padding:10px;text-align:left;">Teléfono</th>
                    <th style="padding:10px;text-align:left;">Institución</th>
                    <th style="padding:10px;text-align:center;">Fecha Registro</th>
                    <th style="padding:10px;text-align:center;">Estado Inscripción</th>
                    <th style="padding:10px;text-align:center;min-width:180px;">Asistencia</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inscritos as $i => $ins): 
                    // Determinar valor actual para el select
                    if ($ins['presente'] === null) $valorActual = 'pendiente';
                    else $valorActual = (string)(int)$ins['presente'];
                ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px;"><?= $i + 1; ?></td>
                    <td style="padding:10px;"><strong><?= htmlspecialchars($ins['apellido'] . ', ' . $ins['nombre']); ?></strong></td>
                    <td style="padding:10px;"><?= htmlspecialchars($ins['email']); ?></td>
                    <td style="padding:10px;"><?= htmlspecialchars($ins['telefono'] ?: '—'); ?></td>
                    <td style="padding:10px;"><?= htmlspecialchars($ins['institucion'] ?: '—'); ?></td>
                    <td style="padding:10px;text-align:center;font-size:0.8rem;color:#64748b;"><?= date('d/m/Y H:i', strtotime($ins['fecha_inscripcion'])); ?></td>
                    <td style="padding:10px;text-align:center;">
                        <span style="background:<?= $ins['estado_reg'] === 'activa' ? '#dcfce7' : '#fee2e2'; ?>;color:<?= $ins['estado_reg'] === 'activa' ? '#166534' : '#991b1b'; ?>;padding:3px 10px;border-radius:12px;font-size:0.75rem;font-weight:600;">
                            <?= ucfirst($ins['estado_reg']); ?>
                        </span>
                    </td>
                    <td style="padding:8px;text-align:center;">
                        <form method="POST" action="inscritos.php" style="display:inline-flex;align-items:center;gap:6px;">
                            <input type="hidden" name="action"         value="cambiar_asistencia">
                            <input type="hidden" name="usuario_id"     value="<?= (int)$ins['usuario_id']; ?>">
                            <input type="hidden" name="evento_id"      value="<?= (int)$event_id; ?>">
                            <input type="hidden" name="inscripcion_id" value="<?= (int)$ins['inscripcion_id']; ?>">
                            <select name="estado_asistencia" onchange="this.form.submit()"
                                style="padding:5px 8px;border-radius:6px;font-size:0.8rem;cursor:pointer;border:1.5px solid
                                <?php
                                    if ($valorActual === '1') echo '#16a34a';
                                    elseif ($valorActual === '0') echo '#dc2626';
                                    else echo '#ca8a04';
                                ?>;
                                background:
                                <?php
                                    if ($valorActual === '1') echo '#dcfce7';
                                    elseif ($valorActual === '0') echo '#fee2e2';
                                    else echo '#fef9c3';
                                ?>;
                                color:
                                <?php
                                    if ($valorActual === '1') echo '#166534';
                                    elseif ($valorActual === '0') echo '#991b1b';
                                    else echo '#854d0e';
                                ?>;
                                font-weight:600;">
                                <option value="1"        <?= $valorActual === '1'        ? 'selected' : ''; ?>>✔ Se presentó</option>
                                <option value="pendiente"<?= $valorActual === 'pendiente' ? 'selected' : ''; ?>>⏳ Pendiente</option>
                                <option value="0"        <?= $valorActual === '0'        ? 'selected' : ''; ?>>✘ No se presentó</option>
                            </select>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p style="margin-top:14px;font-size:0.78rem;color:#94a3b8;text-align:right;">
        <i class="fas fa-info-circle"></i> El cambio de asistencia se guarda automáticamente al seleccionar una opción.
    </p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
