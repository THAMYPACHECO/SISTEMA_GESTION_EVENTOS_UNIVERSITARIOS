<?php
// admin/inscripciones.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(1);

$db = getDB();
$pageTitle = 'Inscripciones';
$msg = $_GET['msg'] ?? '';
$error = '';

// 1. Procesar Registro Manual de Inscripción (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'inscribir') {
    $eventoId  = (int)($_POST['evento_id'] ?? 0);
    $usuarioId = (int)($_POST['usuario_id'] ?? 0);

    if ($eventoId <= 0 || $usuarioId <= 0) {
        $error = 'Debe seleccionar un evento y un participante válidos.';
    } else {
        try {
            $db->beginTransaction();

            // Verificar evento y cupos
            $stmtEv = $db->prepare("SELECT id, nombre, cupo_disponible, estado, fecha_fin, hora_fin FROM eventos WHERE id = ? FOR UPDATE");
            $stmtEv->execute([$eventoId]);
            $evData = $stmtEv->fetch(PDO::FETCH_ASSOC);

            if (!$evData) {
                throw new Exception('El evento seleccionado no existe.');
            }
            $ahora = date('Y-m-d H:i:s');
            $eventoFin = $evData['fecha_fin'] . ' ' . $evData['hora_fin'];
            if ($evData['estado'] === 'finalizado' || $evData['estado'] === 'cancelado' || $eventoFin < $ahora) {
                throw new Exception('No se puede inscribir: el evento ya ha finalizado o está inactivo.');
            }
            if ((int)$evData['cupo_disponible'] <= 0) {
                throw new Exception('El evento no cuenta con cupos disponibles.');
            }

            // Verificar si ya está inscrito
            $stmtChk = $db->prepare("SELECT id, estado FROM inscripciones WHERE usuario_id = ? AND evento_id = ?");
            $stmtChk->execute([$usuarioId, $eventoId]);
            $regExistente = $stmtChk->fetch(PDO::FETCH_ASSOC);

            if ($regExistente) {
                if ($regExistente['estado'] === 'activa') {
                    throw new Exception('El usuario ya se encuentra inscrito activamente en este evento.');
                } else {
                    // Reactivar inscripción previamente cancelada
                    $db->prepare("UPDATE inscripciones SET estado = 'activa', fecha_inscripcion = NOW() WHERE id = ?")
                       ->execute([$regExistente['id']]);
                    $db->prepare("UPDATE eventos SET cupo_disponible = cupo_disponible - 1 WHERE id = ?")
                       ->execute([$eventoId]);
                }
            } else {
                // Nueva inscripción
                $db->prepare("INSERT INTO inscripciones (usuario_id, evento_id, fecha_inscripcion, estado) VALUES (?, ?, NOW(), 'activa')")
                   ->execute([$usuarioId, $eventoId]);
                $db->prepare("UPDATE eventos SET cupo_disponible = cupo_disponible - 1 WHERE id = ?")
                   ->execute([$eventoId]);
            }

            $db->commit();
            header('Location: inscripciones.php?msg=inscribed&evento_id=' . $eventoId);
            exit;
        } catch (Exception $ex) {
            if ($db->inTransaction()) $db->rollBack();
            $error = $ex->getMessage();
        }
    }
}

// 2. Procesar Cancelación / Reactivación rápida (GET)
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $insId = (int)$_GET['toggle'];
    try {
        $db->beginTransaction();

        $stmtIns = $db->prepare("SELECT id, usuario_id, evento_id, estado FROM inscripciones WHERE id = ? FOR UPDATE");
        $stmtIns->execute([$insId]);
        $insData = $stmtIns->fetch(PDO::FETCH_ASSOC);

        if ($insData) {
            if ($insData['estado'] === 'activa') {
                // Cancelar y devolver cupo
                $db->prepare("UPDATE inscripciones SET estado = 'cancelada' WHERE id = ?")->execute([$insId]);
                $db->prepare("UPDATE eventos SET cupo_disponible = cupo_disponible + 1 WHERE id = ?")->execute([$insData['evento_id']]);
                $db->commit();
                header('Location: inscripciones.php?msg=cancelled');
                exit;
            } else {
                // Reactivar si hay cupo
                $stmtCupo = $db->prepare("SELECT cupo_disponible FROM eventos WHERE id = ?");
                $stmtCupo->execute([$insData['evento_id']]);
                $disp = (int)$stmtCupo->fetchColumn();

                if ($disp > 0) {
                    $db->prepare("UPDATE inscripciones SET estado = 'activa' WHERE id = ?")->execute([$insId]);
                    $db->prepare("UPDATE eventos SET cupo_disponible = cupo_disponible - 1 WHERE id = ?")->execute([$insData['evento_id']]);
                    $db->commit();
                    header('Location: inscripciones.php?msg=reactivated');
                    exit;
                } else {
                    throw new Exception('No hay cupos disponibles en el evento para reactivar la inscripción.');
                }
            }
        }
        $db->commit();
    } catch (Exception $ex) {
        if ($db->inTransaction()) $db->rollBack();
        $error = $ex->getMessage();
    }
}

// Filtros y paginación
$filtroEvento = isset($_GET['evento_id']) && is_numeric($_GET['evento_id']) ? (int)$_GET['evento_id'] : '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = "WHERE 1=1";
$params = [];
if ($filtroEvento) {
    $where .= " AND r.evento_id = ?";
    $params[] = $filtroEvento;
}

$total = $db->prepare("SELECT COUNT(*) FROM inscripciones r $where");
$total->execute($params);
$totalRows  = $total->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$stmt = $db->prepare("
    SELECT r.id, r.fecha_inscripcion, r.estado AS estado_inscripcion,
           u.id AS usuario_id, u.nombre, u.apellido, u.email, u.institucion,
           e.id AS evento_id, e.nombre AS evento, e.fecha_inicio, e.cupo_disponible
    FROM inscripciones r
    JOIN usuarios u ON u.id = r.usuario_id
    JOIN eventos e ON e.id = r.evento_id
    $where
    ORDER BY r.fecha_inscripcion DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar todos los eventos para selector y filtros
$eventos = $db->query("SELECT id, nombre, fecha_inicio, fecha_fin, hora_inicio, hora_fin, cupo_disponible, cupo_maximo, estado FROM eventos ORDER BY fecha_inicio DESC")->fetchAll(PDO::FETCH_ASSOC);

// Cargar participantes disponibles para inscribir (usuarios con rol participante o todos)
$participantes = $db->query("SELECT id, nombre, apellido, email, institucion, rol_id FROM usuarios WHERE activo = 1 ORDER BY apellido, nombre")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header_admin.php';
?>
<div class="page-header">
    <h1><i class="fas fa-clipboard-list"></i> Gestión de Inscripciones</h1>
    <div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <strong>Inscripciones</strong></div>
</div>

<?php if ($msg === 'inscribed'): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> Participante inscrito exitosamente en el evento.</div>
<?php elseif ($msg === 'cancelled'): ?>
    <div class="alert alert-warning"><i class="fas fa-info-circle"></i> Inscripción cancelada. Se ha liberado 1 cupo en el evento.</div>
<?php elseif ($msg === 'reactivated'): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> Inscripción reactivada correctamente.</div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="card-title">Listado de Inscripciones (<?= $totalRows ?>)</span>
        <button class="btn btn-primary" onclick="document.getElementById('modalInscribir').classList.add('active')">
            <i class="fas fa-user-plus"></i> + Inscribir Participante
        </button>
    </div>

    <!-- Filtro por evento -->
    <form method="GET" class="search-bar mt-2">
        <select name="evento_id" class="form-control" style="max-width:360px;" onchange="this.form.submit()">
            <option value="">Todos los eventos</option>
            <?php foreach ($eventos as $ev): ?>
                <option value="<?= $ev['id'] ?>" <?= (string)$filtroEvento === (string)$ev['id'] ? 'selected' : '' ?>>
                    <?= e($ev['nombre']) ?> (<?= date('d/m/Y', strtotime($ev['fecha_inicio'])) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
        <?php if ($filtroEvento): ?>
            <a href="inscripciones.php" class="btn btn-secondary"><i class="fas fa-times"></i> Ver todos</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Participante</th>
                    <th>Email</th>
                    <th>Institución</th>
                    <th>Evento</th>
                    <th>Fecha Inscripción</th>
                    <th>Estado</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($inscripciones as $i => $ins): ?>
                <tr>
                    <td><?= $offset + $i + 1 ?></td>
                    <td><strong><?= e($ins['apellido'] . ', ' . $ins['nombre']) ?></strong></td>
                    <td><?= e($ins['email']) ?></td>
                    <td><?= e($ins['institucion'] ?: '-') ?></td>
                    <td>
                        <?= e($ins['evento']) ?><br>
                        <small class="text-muted"><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($ins['fecha_inicio'])) ?></small>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($ins['fecha_inscripcion'])) ?></td>
                    <td>
                        <span class="badge badge-<?= $ins['estado_inscripcion'] === 'activa' ? 'success' : 'danger' ?>">
                            <?= ucfirst($ins['estado_inscripcion']) ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($ins['estado_inscripcion'] === 'activa'): ?>
                            <a href="inscripciones.php?toggle=<?= $ins['id'] ?><?= $filtroEvento ? '&evento_id='.$filtroEvento : '' ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('¿Seguro que desea cancelar esta inscripción? Se liberará 1 cupo en el evento.');"
                               title="Cancelar inscripción">
                                <i class="fas fa-user-times"></i> Cancelar
                            </a>
                        <?php else: ?>
                            <a href="inscripciones.php?toggle=<?= $ins['id'] ?><?= $filtroEvento ? '&evento_id='.$filtroEvento : '' ?>" 
                               class="btn btn-success btn-sm"
                               onclick="return confirm('¿Desea reactivar esta inscripción?');"
                               title="Reactivar inscripción">
                                <i class="fas fa-user-check"></i> Reactivar
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($inscripciones)): ?>
                <tr><td colspan="8" style="text-align:center;color:#888;padding:35px;">No se encontraron inscripciones registradas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?evento_id=<?= $filtroEvento ?>&page=<?= $page-1 ?>"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
            <?php if ($p === $page): ?>
                <span class="current"><?= $p ?></span>
            <?php else: ?>
                <a href="?evento_id=<?= $filtroEvento ?>&page=<?= $p ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?evento_id=<?= $filtroEvento ?>&page=<?= $page+1 ?>"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Inscribir Participante -->
<div class="modal-overlay" id="modalInscribir">
    <div class="modal">
        <button class="close-modal" onclick="document.getElementById('modalInscribir').classList.remove('active')">&times;</button>
        <h2><i class="fas fa-user-plus"></i> Inscribir Participante a un Evento</h2>
        <form method="POST" action="inscripciones.php">
            <input type="hidden" name="action" value="inscribir">

            <div class="form-group">
                <label>Seleccionar Evento *</label>
                <select name="evento_id" class="form-control" required>
                    <option value="">-- Seleccione un evento disponible --</option>
                    <?php foreach ($eventos as $ev):
                        $finEv = ($ev['fecha_fin'] ?? '') . ' ' . ($ev['hora_fin'] ?? '');
                        $estaVencido = ($ev['estado'] === 'finalizado' || $ev['estado'] === 'cancelado' || (strtotime($finEv) && strtotime($finEv) < time()));
                        $noDisponible = $estaVencido || ((int)$ev['cupo_disponible'] <= 0);
                    ?>
                        <option value="<?= $ev['id'] ?>" <?= $noDisponible ? 'disabled' : '' ?>>
                            <?= e($ev['nombre']) ?> &mdash; Cupos: <?= $ev['cupo_disponible'] ?>/<?= $ev['cupo_maximo'] ?> (<?= $estaVencido ? 'Finalizado' : ucfirst($ev['estado']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Seleccionar Participante / Usuario *</label>
                <select name="usuario_id" class="form-control" required>
                    <option value="">-- Seleccione el participante --</option>
                    <?php foreach ($participantes as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= e($p['apellido'] . ', ' . $p['nombre']) ?> &mdash; <?= e($p['email']) ?> (<?= $p['rol_id'] == 3 ? 'Participante' : ($p['rol_id'] == 2 ? 'Organizador' : 'Admin') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalInscribir').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Inscribir Ahora</button>
            </div>
        </form>
    </div>
</div>

<?php if ($error): ?>
<script>document.getElementById('modalInscribir').classList.add('active');</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
