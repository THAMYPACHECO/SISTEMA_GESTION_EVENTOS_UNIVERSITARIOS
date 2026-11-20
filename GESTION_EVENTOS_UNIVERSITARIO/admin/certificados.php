<?php
// admin/certificados.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(1);

$db = getDB();
$pageTitle = 'Gestión de Certificados';

$msg = $_GET['msg'] ?? '';
$error = '';
$filtroEvento = isset($_GET['evento_id']) && is_numeric($_GET['evento_id']) ? (int)$_GET['evento_id'] : 0;

// Acción: Revocar o reactivar certificado
if (isset($_GET['toggle_cert']) && is_numeric($_GET['toggle_cert'])) {
    $certId = (int)$_GET['toggle_cert'];
    $stmtC = $db->prepare("SELECT estado FROM certificados WHERE id = ?");
    $stmtC->execute([$certId]);
    $curr = $stmtC->fetchColumn();
    if ($curr) {
        $nuevoEstado = $curr === 'emitido' ? 'revocado' : 'emitido';
        $db->prepare("UPDATE certificados SET estado = ? WHERE id = ?")->execute([$nuevoEstado, $certId]);
        header('Location: certificados.php?msg=updated');
        exit;
    }
}

// Acción: Generar certificados para un evento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_certificados'])) {
    $evId = (int)($_POST['evento_id_generar'] ?? 0);
    if ($evId <= 0) {
        $error = 'Seleccione un evento válido para generar certificados.';
    } else {
        try {
            // Obtener participantes que asistieron (presente = 1) y que aún no tienen certificado
            $stmtAsistentes = $db->prepare("
                SELECT a.usuario_id, a.evento_id 
                FROM asistencias a
                WHERE a.evento_id = ? AND a.presente = 1
                  AND a.usuario_id NOT IN (
                      SELECT usuario_id FROM certificados WHERE evento_id = ?
                  )
            ");
            $stmtAsistentes->execute([$evId, $evId]);
            $candidatos = $stmtAsistentes->fetchAll(PDO::FETCH_ASSOC);

            if (empty($candidatos)) {
                $error = 'No hay asistentes con presencia confirmada pendientes de certificado en este evento (o ya se emitieron todos).';
            } else {
                $db->beginTransaction();
                $stmtInsertCert = $db->prepare("
                    INSERT INTO certificados (usuario_id, evento_id, codigo_certificado, fecha_emision, porcentaje_asistencia, estado)
                    VALUES (?, ?, ?, NOW(), 100.00, 'emitido')
                ");

                $emitidos = 0;
                foreach ($candidatos as $c) {
                    // Generar código único alfanumérico legible: CERT-{EV}-{AÑO}-{RAND}
                    $codigo = 'CERT-' . str_pad($evId, 3, '0', STR_PAD_LEFT) . '-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                    $stmtInsertCert->execute([
                        $c['usuario_id'],
                        $evId,
                        $codigo
                    ]);
                    $emitidos++;
                }

                $db->commit();
                header("Location: certificados.php?msg=generated&count=$emitidos&evento_id=$evId");
                exit;
            }
        } catch (Exception $ex) {
            if ($db->inTransaction()) $db->rollBack();
            $error = 'Error al generar certificados: ' . $ex->getMessage();
        }
    }
}

// Obtener lista de eventos para selectores
$eventos = $db->query("SELECT id, nombre, fecha_inicio, estado FROM eventos ORDER BY fecha_inicio DESC")->fetchAll(PDO::FETCH_ASSOC);

// Cargar certificados con filtros y paginación
$where = "WHERE 1=1";
$params = [];
if ($filtroEvento > 0) {
    $where .= " AND c.evento_id = ?";
    $params[] = $filtroEvento;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$stmtTotal = $db->prepare("SELECT COUNT(*) FROM certificados c $where");
$stmtTotal->execute($params);
$totalRows = (int)$stmtTotal->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$stmtCertificados = $db->prepare("
    SELECT c.id, c.codigo_certificado, c.fecha_emision, c.porcentaje_asistencia, c.estado,
           u.id AS usuario_id, u.nombre, u.apellido, u.email,
           e.id AS evento_id, e.nombre AS evento_nombre, e.fecha_inicio
    FROM certificados c
    JOIN usuarios u ON u.id = c.usuario_id
    JOIN eventos e ON e.id = c.evento_id
    $where
    ORDER BY c.fecha_emision DESC
    LIMIT $perPage OFFSET $offset
");
$stmtCertificados->execute($params);
$certificados = $stmtCertificados->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header_admin.php';
?>

<div class="page-header">
    <h1><i class="fas fa-award"></i> Gestión de Certificados</h1>
    <div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <strong>Certificados</strong></div>
</div>

<?php if ($msg === 'generated'): ?>
    <div class="alert alert-success">Se han generado exitosamente <?= (int)($_GET['count'] ?? 0) ?> certificados para los participantes asistentes.</div>
<?php elseif ($msg === 'updated'): ?>
    <div class="alert alert-info">Estado del certificado actualizado correctamente.</div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<!-- Generador Masivo de Certificados -->
<div class="card mb-3">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-magic"></i> Emisión Masiva de Certificados</span>
    </div>
    <div class="card-body">
        <p class="text-muted mb-2">Genera certificados con código único para todos los participantes cuya asistencia haya sido marcada como <strong>Presente</strong> y que aún no tengan su certificado.</p>
        <form method="POST" action="certificados.php" class="d-flex gap-2 align-items-center flex-wrap" onsubmit="return confirm('¿Desea generar los certificados para los asistentes del evento seleccionado?');">
            <input type="hidden" name="generar_certificados" value="1">
            <select name="evento_id_generar" class="form-control" style="max-width: 450px;" required>
                <option value="">-- Seleccionar Evento --</option>
                <?php foreach ($eventos as $ev): ?>
                    <option value="<?= $ev['id'] ?>"><?= e($ev['nombre']) ?> (<?= ucfirst($ev['estado']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-file-signature"></i> Generar Certificados</button>
        </form>
    </div>
</div>

<!-- Filtro y Listado de Certificados -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="card-title">Certificados Emitidos (<?= $totalRows ?>)</span>
        <form method="GET" action="certificados.php" class="d-flex gap-2">
            <select name="evento_id" class="form-control" onchange="this.form.submit()" style="max-width: 300px;">
                <option value="">Todos los eventos</option>
                <?php foreach ($eventos as $ev): ?>
                    <option value="<?= $ev['id'] ?>" <?= $filtroEvento === (int)$ev['id'] ? 'selected' : '' ?>>
                        <?= e($ev['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="card-body">
        <?php if (empty($certificados)): ?>
            <p class="text-center text-muted p-4">No se han emitido certificados aún<?= $filtroEvento ? ' para este evento' : '' ?>.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Participante</th>
                            <th>Evento</th>
                            <th>Fecha Emisión</th>
                            <th>Asistencia</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificados as $c): ?>
                            <tr>
                                <td><code><?= e($c['codigo_certificado']) ?></code></td>
                                <td><strong><?= e($c['apellido'] . ', ' . $c['nombre']) ?></strong><br><small class="text-muted"><?= e($c['email']) ?></small></td>
                                <td><?= e($c['evento_nombre']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($c['fecha_emision'])) ?></td>
                                <td><span class="badge badge-en_curso"><?= number_format($c['porcentaje_asistencia'], 0) ?>%</span></td>
                                <td>
                                    <?php if ($c['estado'] === 'emitido'): ?>
                                        <span class="badge badge-en_curso">Emitido</span>
                                    <?php else: ?>
                                        <span class="badge badge-cancelado">Revocado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="../participante/mi_certificado.php?cert_id=<?= $c['id'] ?>" target="_blank" class="btn btn-secondary btn-sm" title="Visualizar e Imprimir Certificado">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                        <a href="certificados.php?toggle_cert=<?= $c['id'] ?><?= $filtroEvento ? '&evento_id='.$filtroEvento : '' ?>" 
                                           class="btn btn-sm <?= $c['estado'] === 'emitido' ? 'btn-danger' : 'btn-success' ?>"
                                           onclick="return confirm('¿Seguro que desea cambiar el estado de este certificado?');"
                                           title="<?= $c['estado'] === 'emitido' ? 'Revocar' : 'Reactivar' ?>">
                                            <i class="fas fa-<?= $c['estado'] === 'emitido' ? 'ban' : 'check' ?>"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination mt-3">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="certificados.php?page=<?= $i ?><?= $filtroEvento ? '&evento_id='.$filtroEvento : '' ?>" 
                           class="page-link <?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
