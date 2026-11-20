<?php
// admin/asistencia.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(1);

$db = getDB();
$pageTitle = 'Control de Asistencia';

$eventoId = isset($_GET['evento_id']) && is_numeric($_GET['evento_id']) ? (int)$_GET['evento_id'] : 0;
$msg = $_GET['msg'] ?? '';
$error = '';

// Obtener todos los eventos para el selector
$stmtEventos = $db->query("SELECT id, nombre, fecha_inicio, estado, cupo_maximo FROM eventos ORDER BY fecha_inicio DESC");
$eventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);

// Si se seleccionó un evento, cargar sus datos
$eventoSeleccionado = null;
$participantes = [];
$statsAsistencia = ['total_inscritos' => 0, 'presentes' => 0, 'ausentes' => 0, 'porcentaje' => 0];

if ($eventoId > 0) {
    $stmtE = $db->prepare("SELECT e.*, c.nombre AS categoria, CONCAT(u.nombre, ' ', u.apellido) AS organizador 
                           FROM eventos e 
                           LEFT JOIN categorias_eventos c ON c.id = e.categoria_id 
                           LEFT JOIN usuarios u ON u.id = e.organizador_id 
                           WHERE e.id = ?");
    $stmtE->execute([$eventoId]);
    $eventoSeleccionado = $stmtE->fetch(PDO::FETCH_ASSOC);

    if ($eventoSeleccionado) {
        // Procesar guardado de asistencia vía POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_asistencia'])) {
            $asistenciasPost = $_POST['asistencia'] ?? []; // array con usuario_id => presente (1)

            try {
                $db->beginTransaction();

                // Obtener todos los inscritos activos
                $stmtInscritos = $db->prepare("SELECT id, usuario_id FROM inscripciones WHERE evento_id = ? AND estado = 'activa'");
                $stmtInscritos->execute([$eventoId]);
                $inscritosActivos = $stmtInscritos->fetchAll(PDO::FETCH_ASSOC);

                $stmtUpsert = $db->prepare("
                    INSERT INTO asistencias (inscripcion_id, usuario_id, evento_id, presente, fecha_registro, registrado_por)
                    VALUES (?, ?, ?, ?, NOW(), ?)
                    ON DUPLICATE KEY UPDATE presente = VALUES(presente), fecha_registro = NOW(), registrado_por = VALUES(registrado_por)
                ");

                foreach ($inscritosActivos as $inscrito) {
                    $uId = $inscrito['usuario_id'];
                    $inscripId = $inscrito['id'];
                    $estaPresente = isset($asistenciasPost[$uId]) && $asistenciasPost[$uId] == 1 ? 1 : 0;

                    $stmtUpsert->execute([
                        $inscripId,
                        $uId,
                        $eventoId,
                        $estaPresente,
                        $_SESSION['user_id']
                    ]);
                }

                $db->commit();
                header("Location: asistencia.php?evento_id=$eventoId&msg=saved");
                exit;
            } catch (Exception $ex) {
                $db->rollBack();
                $error = 'Error al registrar la asistencia: ' . $ex->getMessage();
            }
        }

        // Obtener la lista de inscritos con su estado de asistencia actual
        $stmtP = $db->prepare("
            SELECT i.id AS inscripcion_id, i.fecha_inscripcion,
                   u.id AS usuario_id, u.nombre, u.apellido, u.email, u.institucion,
                   COALESCE(a.presente, 0) AS presente, a.fecha_registro
            FROM inscripciones i
            JOIN usuarios u ON u.id = i.usuario_id
            LEFT JOIN asistencias a ON a.usuario_id = u.id AND a.evento_id = ?
            WHERE i.evento_id = ? AND i.estado = 'activa'
            ORDER BY u.apellido ASC, u.nombre ASC
        ");
        $stmtP->execute([$eventoId, $eventoId]);
        $participantes = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        // Calcular estadísticas
        $totalInscritos = count($participantes);
        $presentes = 0;
        foreach ($participantes as $p) {
            if ($p['presente'] == 1) $presentes++;
        }
        $ausentes = $totalInscritos - $presentes;
        $porcentaje = $totalInscritos > 0 ? round(($presentes / $totalInscritos) * 100, 1) : 0;

        $statsAsistencia = [
            'total_inscritos' => $totalInscritos,
            'presentes' => $presentes,
            'ausentes' => $ausentes,
            'porcentaje' => $porcentaje
        ];
    }
}

include __DIR__ . '/../includes/header_admin.php';
?>

<div class="page-header">
    <h1><i class="fas fa-user-check"></i> Control de Asistencia</h1>
    <div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <strong>Asistencia</strong></div>
</div>

<?php if ($msg === 'saved'): ?>
    <div class="alert alert-success">Asistencia registrada y actualizada exitosamente.</div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<!-- Selector de Evento -->
<div class="card mb-3">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-filter"></i> Seleccionar Evento</span>
    </div>
    <div class="card-body">
        <form method="GET" action="asistencia.php" class="d-flex gap-2 align-items-center" style="max-width: 600px;">
            <select name="evento_id" class="form-control" onchange="this.form.submit()">
                <option value="">-- Seleccione un evento para gestionar la asistencia --</option>
                <?php foreach ($eventos as $ev): ?>
                    <option value="<?= $ev['id'] ?>" <?= $eventoId === (int)$ev['id'] ? 'selected' : '' ?>>
                        <?= e($ev['nombre']) ?> (<?= date('d/m/Y', strtotime($ev['fecha_inicio'])) ?> - <?= ucfirst($ev['estado']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Cargar</button>
        </form>
    </div>
</div>

<?php if ($eventoSeleccionado): ?>
    <!-- Tarjetas de Resumen del Evento -->
    <div class="stats-grid mb-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#2980b9;"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <div class="value"><?= $statsAsistencia['total_inscritos'] ?></div>
                <div class="label">Total Inscritos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#27ae60;"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <div class="value"><?= $statsAsistencia['presentes'] ?></div>
                <div class="label">Asistentes Presentes</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#e74c3c;"><i class="fas fa-times-circle"></i></div>
            <div class="stat-info">
                <div class="value"><?= $statsAsistencia['ausentes'] ?></div>
                <div class="label">Ausentes</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#f39c12;"><i class="fas fa-percentage"></i></div>
            <div class="stat-info">
                <div class="value"><?= $statsAsistencia['porcentaje'] ?>%</div>
                <div class="label">% Asistencia</div>
            </div>
        </div>
    </div>

    <!-- Lista de Participantes y Formulario de Asistencia -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="card-title">
                Participantes de: <strong><?= e($eventoSeleccionado['nombre']) ?></strong>
            </span>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-secondary btn-sm" onclick="marcarTodos(true)">Marcar Todos Presentes</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="marcarTodos(false)">Desmarcar Todos</button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($participantes)): ?>
                <p class="text-center text-muted p-3">No hay participantes inscritos activos en este evento.</p>
            <?php else: ?>
                <form method="POST" action="asistencia.php?evento_id=<?= $eventoId ?>">
                    <input type="hidden" name="guardar_asistencia" value="1">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Participante</th>
                                    <th>Correo</th>
                                    <th>Institución</th>
                                    <th style="width: 150px; text-align: center;">Asistencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $n = 1; foreach ($participantes as $p): ?>
                                    <tr>
                                        <td><?= $n++ ?></td>
                                        <td><strong><?= e($p['apellido'] . ', ' . $p['nombre']) ?></strong></td>
                                        <td><?= e($p['email']) ?></td>
                                        <td><?= e($p['institucion'] ?: 'No especificada') ?></td>
                                        <td style="text-align: center;">
                                            <label style="cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                                                <input type="checkbox" name="asistencia[<?= $p['usuario_id'] ?>]" value="1" 
                                                       class="chk-asistencia" <?= $p['presente'] == 1 ? 'checked' : '' ?>
                                                       style="width: 20px; height: 20px; cursor: pointer;">
                                                <span><?= $p['presente'] == 1 ? '<span class="badge badge-en_curso">Presente</span>' : '<span class="badge badge-cancelado">Ausente</span>' ?></span>
                                            </label>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3 gap-2">
                        <a href="eventos.php" class="btn btn-secondary">Volver a Eventos</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Asistencia</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card p-4 text-center text-muted">
        <i class="fas fa-calendar-check" style="font-size: 3rem; margin-bottom: 1rem; color: #95a5a6;"></i>
        <p>Por favor seleccione un evento en el selector superior para ver sus inscritos y registrar la asistencia.</p>
    </div>
<?php endif; ?>

<script>
function marcarTodos(estado) {
    const checkboxes = document.querySelectorAll('.chk-asistencia');
    checkboxes.forEach(cb => cb.checked = estado);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
