<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(2);

$db  = getDB();
$uid = $_SESSION['user_id'];
$page_title = 'Registrar Asistencia';

// Validar event_id
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$event_id) {
    $_SESSION['mensaje'] = 'Debes seleccionar un evento válido.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: mis_eventos.php');
    exit;
}

// Verificar pertenencia
$stmtEv = $db->prepare("SELECT id, nombre, fecha_inicio, lugar, estado FROM eventos WHERE id = ? AND organizador_id = ?");
$stmtEv->execute([$event_id, $uid]);
$evento = $stmtEv->fetch(PDO::FETCH_ASSOC);
if (!$evento) {
    $_SESSION['mensaje'] = 'Evento no encontrado o sin permiso.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: mis_eventos.php');
    exit;
}

// --- Procesar POST: guardar asistencia ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_asistencia'])) {
    $presentes = $_POST['presentes'] ?? [];

    // Obtener todos los inscritos activos
    $stmtReg = $db->prepare("SELECT r.id, r.usuario_id FROM inscripciones r WHERE r.evento_id = ? AND r.estado = 'activa'");
    $stmtReg->execute([$event_id]);
    $registraciones = $stmtReg->fetchAll(PDO::FETCH_ASSOC);

    $stmtUpsert = $db->prepare("
        INSERT INTO asistencias (inscripcion_id, usuario_id, evento_id, presente, fecha_registro, registrado_por)
        VALUES (?, ?, ?, ?, NOW(), ?)
        ON DUPLICATE KEY UPDATE presente = VALUES(presente), fecha_registro = NOW(), registrado_por = VALUES(registrado_por)
    ");

    $db->beginTransaction();
    try {
        foreach ($registraciones as $reg) {
            $presente = in_array($reg['usuario_id'], $presentes) ? 1 : 0;
            $stmtUpsert->execute([$reg['id'], $reg['usuario_id'], $event_id, $presente, $uid]);
        }
        $db->commit();
        $_SESSION['mensaje'] = 'Asistencia registrada correctamente.';
        $_SESSION['mensaje_tipo'] = 'success';
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensaje'] = 'Error al guardar la asistencia: ' . $e->getMessage();
        $_SESSION['mensaje_tipo'] = 'error';
    }
    header('Location: asistencia.php?event_id=' . $event_id);
    exit;
}

// Obtener inscritos con estado de asistencia actual
$stmtIn = $db->prepare("
    SELECT u.id AS user_id, u.nombre, u.apellido, u.email, u.institucion,
           r.id AS reg_id,
           COALESCE(a.presente, -1) AS presente
    FROM inscripciones r
    JOIN usuarios u ON r.usuario_id = u.id
    LEFT JOIN asistencias a ON a.usuario_id = u.id AND a.evento_id = ?
    WHERE r.evento_id = ? AND r.estado = 'activa'
    ORDER BY u.apellido, u.nombre
");
$stmtIn->execute([$event_id, $event_id]);
$inscritos = $stmtIn->fetchAll(PDO::FETCH_ASSOC);

// Calcular porcentaje de asistencia actual
$totalInscritos = count($inscritos);
$totalPresentes = count(array_filter($inscritos, fn($i) => $i['presente'] == 1));
$porcentaje = $totalInscritos > 0 ? round($totalPresentes / $totalInscritos * 100, 1) : 0;

require_once __DIR__ . '/../includes/header_org.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <div>
        <h1 style="font-size:1.5rem;margin-bottom:4px;">Registrar Asistencia</h1>
        <p style="color:#666;font-size:0.88rem;">
            <strong><?php echo htmlspecialchars($evento['nombre']); ?></strong> —
            <i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($evento['fecha_inicio'])); ?> |
            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($evento['lugar']); ?>
        </p>
    </div>
    <a href="mis_eventos.php" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#6c757d;color:#fff;border-radius:8px;text-decoration:none;font-size:0.88rem;">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<!-- Barra de porcentaje -->
<div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);margin-bottom:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <span style="font-weight:600;font-size:1rem;"><i class="fas fa-chart-pie" style="color:#2c7a3e;"></i> Porcentaje de Asistencia Actual</span>
        <span style="font-size:1.5rem;font-weight:700;color:<?php echo $porcentaje >= 70 ? '#28a745' : ($porcentaje >= 40 ? '#ffc107' : '#dc3545'); ?>;">
            <?php echo $porcentaje; ?>%
        </span>
    </div>
    <div style="background:#e9ecef;border-radius:8px;height:14px;overflow:hidden;">
        <div style="background:linear-gradient(90deg,#2c7a3e,#5cb85c);width:<?php echo $porcentaje; ?>%;height:100%;border-radius:8px;transition:width 0.5s;"></div>
    </div>
    <div style="display:flex;gap:24px;margin-top:12px;font-size:0.85rem;color:#666;">
        <span><strong style="color:#28a745;"><?php echo $totalPresentes; ?></strong> presentes</span>
        <span><strong style="color:#dc3545;"><?php echo $totalInscritos - $totalPresentes; ?></strong> ausentes</span>
        <span><strong><?php echo $totalInscritos; ?></strong> inscritos totales</span>
    </div>
</div>

<!-- Formulario de asistencia -->
<div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);">
    <?php if (empty($inscritos)): ?>
        <p style="text-align:center;color:#999;padding:40px;">No hay participantes inscritos en este evento.</p>
    <?php else: ?>
    <form method="POST" action="asistencia.php?event_id=<?php echo $event_id; ?>">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
            <div style="display:flex;gap:10px;align-items:center;">
                <button type="button" onclick="marcarTodos(true)"
                    style="padding:7px 14px;background:#28a745;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:0.82rem;">
                    <i class="fas fa-check-double"></i> Marcar todos presentes
                </button>
                <button type="button" onclick="marcarTodos(false)"
                    style="padding:7px 14px;background:#dc3545;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:0.82rem;">
                    <i class="fas fa-times"></i> Desmarcar todos
                </button>
            </div>
            <input type="text" id="buscar" placeholder="🔍 Buscar participante..." oninput="filtrarAsistencia()"
                   style="padding:7px 14px;border:1px solid #ddd;border-radius:6px;font-size:0.85rem;min-width:220px;">
        </div>

        <div style="overflow-x:auto;">
            <table id="tablaAsistencia" style="width:100%;border-collapse:collapse;font-size:0.87rem;">
                <thead>
                    <tr style="background:#f8f9fa;">
                        <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;width:60px;">
                            <input type="checkbox" id="checkAll" onchange="marcarTodos(this.checked)" title="Marcar todos">
                        </th>
                        <th style="padding:11px;text-align:left;border-bottom:2px solid #dee2e6;">Apellido</th>
                        <th style="padding:11px;text-align:left;border-bottom:2px solid #dee2e6;">Nombre</th>
                        <th style="padding:11px;text-align:left;border-bottom:2px solid #dee2e6;">Email</th>
                        <th style="padding:11px;text-align:left;border-bottom:2px solid #dee2e6;">Institución</th>
                        <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">Estado Anterior</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscritos as $ins):
                        $estaPresente = $ins['presente'] == 1;
                    ?>
                    <tr class="fila-asistencia" style="border-bottom:1px solid #f0f0f0;cursor:pointer;"
                        onclick="toggleCheck(<?php echo $ins['user_id']; ?>)"
                        id="fila-<?php echo $ins['user_id']; ?>">
                        <td style="padding:10px;text-align:center;" onclick="event.stopPropagation()">
                            <input type="checkbox" name="presentes[]" value="<?php echo $ins['user_id']; ?>"
                                   id="cb-<?php echo $ins['user_id']; ?>"
                                   <?php echo $estaPresente ? 'checked' : ''; ?>
                                   onchange="actualizarFila(<?php echo $ins['user_id']; ?>)"
                                   style="width:18px;height:18px;cursor:pointer;">
                        </td>
                        <td style="padding:10px;font-weight:600;"><?php echo htmlspecialchars($ins['apellido']); ?></td>
                        <td style="padding:10px;"><?php echo htmlspecialchars($ins['nombre']); ?></td>
                        <td style="padding:10px;font-size:0.82rem;"><?php echo htmlspecialchars($ins['email']); ?></td>
                        <td style="padding:10px;font-size:0.82rem;color:#666;"><?php echo htmlspecialchars($ins['institucion'] ?? '—'); ?></td>
                        <td style="padding:10px;text-align:center;">
                            <?php if ($ins['presente'] == 1): ?>
                                <span style="background:#d4edda;color:#155724;padding:3px 10px;border-radius:20px;font-size:0.78rem;">Presente</span>
                            <?php elseif ($ins['presente'] == 0): ?>
                                <span style="background:#f8d7da;color:#721c24;padding:3px 10px;border-radius:20px;font-size:0.78rem;">Ausente</span>
                            <?php else: ?>
                                <span style="background:#fff3cd;color:#856404;padding:3px 10px;border-radius:20px;font-size:0.78rem;">Sin registrar</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px;text-align:right;">
            <input type="hidden" name="guardar_asistencia" value="1">
            <button type="submit"
                    style="padding:11px 28px;background:#2c7a3e;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:0.95rem;font-weight:600;"
                    onclick="return confirm('¿Guardar la asistencia de todos los participantes?')">
                <i class="fas fa-save"></i> Guardar Asistencia
            </button>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
function marcarTodos(val) {
    document.querySelectorAll('input[name="presentes[]"]').forEach(function(cb) {
        cb.checked = val;
        actualizarFila(cb.value);
    });
    var checkAll = document.getElementById('checkAll');
    if (checkAll) checkAll.checked = val;
}
function toggleCheck(userId) {
    var cb = document.getElementById('cb-' + userId);
    if (cb) { cb.checked = !cb.checked; actualizarFila(userId); }
}
function actualizarFila(userId) {
    var cb = document.getElementById('cb-' + userId);
    var fila = document.getElementById('fila-' + userId);
    if (fila) {
        fila.style.background = cb && cb.checked ? '#f0fff4' : '';
    }
}
function filtrarAsistencia() {
    var val = document.getElementById('buscar').value.toLowerCase();
    document.querySelectorAll('.fila-asistencia').forEach(function(row) {
        row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
}
// Inicializar colores
document.querySelectorAll('input[name="presentes[]"]').forEach(function(cb) {
    if (cb.checked) actualizarFila(cb.value);
});
</script>

</main></div>
<footer style="text-align:center;padding:16px;font-size:0.8rem;color:#999;background:#fff;border-top:1px solid #eee;margin-left:240px;">
    <?php echo APP_NAME; ?> &copy; <?php echo date('Y'); ?> — Módulo Organizador
</footer>
</body>
</html>
