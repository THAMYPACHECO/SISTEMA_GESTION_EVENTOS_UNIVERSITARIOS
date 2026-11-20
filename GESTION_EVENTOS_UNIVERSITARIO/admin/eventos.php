<?php
// admin/eventos.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(1);

$db = getDB();
$pageTitle = 'Gestión de Eventos';

// Eliminar evento
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $eid = (int)$_GET['delete'];
    // Eliminar dependencias y luego el evento
    $db->prepare("DELETE FROM certificados WHERE evento_id = ?")->execute([$eid]);
    $db->prepare("DELETE FROM asistencias WHERE evento_id = ?")->execute([$eid]);
    $db->prepare("DELETE FROM inscripciones WHERE evento_id = ?")->execute([$eid]);
    $db->prepare("DELETE FROM eventos WHERE id = ?")->execute([$eid]);
    header('Location: eventos.php?msg=deleted');
    exit;
}

// Filtros y búsqueda
$filtroEstado    = trim($_GET['estado'] ?? '');
$filtroCategoria = trim($_GET['categoria'] ?? '');
$buscar          = trim($_GET['buscar'] ?? '');
$page            = max(1, (int)($_GET['page'] ?? 1));
$perPage         = 20;
$offset          = ($page - 1) * $perPage;

$where  = "WHERE 1=1";
$params = [];

if ($filtroEstado !== '' && in_array($filtroEstado, ['programado', 'en_curso', 'finalizado', 'cancelado'], true)) {
    $where .= " AND e.estado = ?";
    $params[] = $filtroEstado;
}

if ($filtroCategoria !== '' && is_numeric($filtroCategoria) && (int)$filtroCategoria > 0) {
    $where .= " AND e.categoria_id = ?";
    $params[] = (int)$filtroCategoria;
}

if ($buscar !== '') {
    $where .= " AND (e.nombre LIKE ? OR e.lugar LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

// Conteo total para paginación
$stmtTotal = $db->prepare("SELECT COUNT(*) FROM eventos e $where");
$stmtTotal->execute($params);
$totalRows  = (int)$stmtTotal->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// Consulta de eventos
$stmt = $db->prepare("
    SELECT e.id, e.nombre, e.fecha_inicio, e.fecha_fin, e.hora_inicio, e.hora_fin,
           e.lugar, e.cupo_maximo, e.cupo_disponible,
           e.estado, e.modalidad,
           c.nombre AS categoria,
           u.nombre AS org_nombre, u.apellido AS org_apellido
    FROM eventos e
    LEFT JOIN categorias_eventos c ON c.id = e.categoria_id
    LEFT JOIN usuarios u ON u.id = e.organizador_id
    $where
    ORDER BY e.fecha_inicio DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Conteos por estado para botones/pills de acceso directo
$counts = [
    'todos'       => (int)$db->query("SELECT COUNT(*) FROM eventos")->fetchColumn(),
    'programado'  => (int)$db->query("SELECT COUNT(*) FROM eventos WHERE estado = 'programado'")->fetchColumn(),
    'en_curso'    => (int)$db->query("SELECT COUNT(*) FROM eventos WHERE estado = 'en_curso'")->fetchColumn(),
    'finalizado'  => (int)$db->query("SELECT COUNT(*) FROM eventos WHERE estado = 'finalizado'")->fetchColumn(),
    'cancelado'   => (int)$db->query("SELECT COUNT(*) FROM eventos WHERE estado = 'cancelado'")->fetchColumn(),
];

// Categorías para el select
$categorias = $db->query("SELECT id, nombre FROM categorias_eventos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$msg = $_GET['msg'] ?? '';

include __DIR__ . '/../includes/header_admin.php';
?>
<div class="page-header">
    <h1><i class="fas fa-calendar-alt"></i> Gestión de Eventos</h1>
    <div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <strong>Eventos</strong></div>
</div>

<?php if ($msg === 'deleted'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Evento eliminado correctamente.</div><?php endif; ?>
<?php if ($msg === 'created'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Evento creado exitosamente.</div><?php endif; ?>
<?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Evento actualizado.</div><?php endif; ?>

<!-- Botones de filtro rápido por Estado -->
<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
    <span style="font-weight:600;font-size:0.88rem;color:#475569;margin-right:4px;"><i class="fas fa-filter"></i> Estado:</span>
    <a href="eventos.php<?= $filtroCategoria ? '?categoria=' . (int)$filtroCategoria : '' ?>" 
       style="padding:6px 14px;border-radius:20px;text-decoration:none;font-size:0.83rem;font-weight:600;border:1px solid #cbd5e1;background:<?= $filtroEstado === '' ? '#1e293b;color:#fff;' : '#fff;color:#475569;' ?>">
        Todos (<?= $counts['todos'] ?>)
    </a>
    <a href="eventos.php?estado=programado<?= $filtroCategoria ? '&categoria=' . (int)$filtroCategoria : '' ?>" 
       style="padding:6px 14px;border-radius:20px;text-decoration:none;font-size:0.83rem;font-weight:600;border:1px solid #93c5fd;background:<?= $filtroEstado === 'programado' ? '#2563eb;color:#fff;' : '#eff6ff;color:#1d4ed8;' ?>">
        <i class="fas fa-calendar-clock"></i> Programados (<?= $counts['programado'] ?>)
    </a>
    <a href="eventos.php?estado=en_curso<?= $filtroCategoria ? '&categoria=' . (int)$filtroCategoria : '' ?>" 
       style="padding:6px 14px;border-radius:20px;text-decoration:none;font-size:0.83rem;font-weight:600;border:1px solid #86efac;background:<?= $filtroEstado === 'en_curso' ? '#16a34a;color:#fff;' : '#f0fdf4;color:#15803d;' ?>">
        <i class="fas fa-play-circle"></i> En Curso (<?= $counts['en_curso'] ?>)
    </a>
    <a href="eventos.php?estado=finalizado<?= $filtroCategoria ? '&categoria=' . (int)$filtroCategoria : '' ?>" 
       style="padding:6px 14px;border-radius:20px;text-decoration:none;font-size:0.83rem;font-weight:600;border:1px solid #cbd5e1;background:<?= $filtroEstado === 'finalizado' ? '#475569;color:#fff;' : '#f1f5f9;color:#334155;' ?>">
        <i class="fas fa-check-circle"></i> Finalizados (<?= $counts['finalizado'] ?>)
    </a>
    <a href="eventos.php?estado=cancelado<?= $filtroCategoria ? '&categoria=' . (int)$filtroCategoria : '' ?>" 
       style="padding:6px 14px;border-radius:20px;text-decoration:none;font-size:0.83rem;font-weight:600;border:1px solid #fca5a5;background:<?= $filtroEstado === 'cancelado' ? '#dc2626;color:#fff;' : '#fef2f2;color:#b91c1c;' ?>">
        <i class="fas fa-ban"></i> Cancelados (<?= $counts['cancelado'] ?>)
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="card-title">Listado de Eventos (<?php echo $totalRows; ?>)</span>
        <a href="evento_form.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Nuevo Evento</a>
    </div>

    <!-- Barra de Filtros con Auto-Envío (onchange) -->
    <form method="GET" action="eventos.php" class="search-bar" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:12px;margin-bottom:16px;">
        <!-- Selector de Estado con auto-submit -->
        <select name="estado" class="form-control" style="max-width:180px;" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <option value="programado"  <?= $filtroEstado === 'programado' ? 'selected' : ''; ?>>Programado</option>
            <option value="en_curso"    <?= $filtroEstado === 'en_curso' ? 'selected' : ''; ?>>En Curso</option>
            <option value="finalizado"  <?= $filtroEstado === 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
            <option value="cancelado"   <?= $filtroEstado === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
        </select>

        <!-- Selector de Categoría con auto-submit -->
        <select name="categoria" class="form-control" style="max-width:220px;" onchange="this.form.submit()">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo (string)$filtroCategoria === (string)$cat['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['nombre']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
        <?php if ($filtroEstado !== '' || $filtroCategoria !== '' || $buscar !== ''): ?>
            <a href="eventos.php" class="btn btn-secondary" title="Quitar todos los filtros"><i class="fas fa-times"></i> Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Fecha Inicio</th>
                    <th>Lugar</th>
                    <th>Categoría</th>
                    <th>Cupo</th>
                    <th>Estado</th>
                    <th>Organizador</th>
                    <th style="text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($eventos as $i => $ev): ?>
                <tr>
                    <td><?php echo $offset + $i + 1; ?></td>
                    <td><strong><?php echo htmlspecialchars($ev['nombre']); ?></strong></td>
                    <td style="white-space:nowrap;">
                        <div><?php echo date('d/m/Y', strtotime($ev['fecha_inicio'])); ?></div>
                        <?php if (!empty($ev['hora_inicio'])): ?>
                            <small class="text-muted"><?php echo substr($ev['hora_inicio'], 0, 5); ?> hrs</small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($ev['lugar'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($ev['categoria'] ?? '-'); ?></td>
                    <td><?php echo ($ev['cupo_maximo'] - $ev['cupo_disponible']); ?>/<?php echo $ev['cupo_maximo']; ?></td>
                    <td>
                        <?php $badges=['programado'=>'info','en_curso'=>'success','finalizado'=>'secondary','cancelado'=>'danger']; ?>
                        <span class="badge badge-<?php echo $badges[$ev['estado']] ?? 'secondary'; ?>"><?php echo ucfirst(str_replace('_', ' ', $ev['estado'])); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($ev['org_nombre'] . ' ' . $ev['org_apellido']); ?></td>
                    <td style="white-space:nowrap;text-align:center;">
                        <a href="evento_form.php?id=<?php echo $ev['id']; ?>" class="btn btn-warning btn-sm" title="Editar evento"><i class="fas fa-edit"></i></a>
                        <a href="inscripciones.php?evento_id=<?php echo $ev['id']; ?>" class="btn btn-info btn-sm" title="Ver inscritos"><i class="fas fa-users"></i></a>
                        <a href="eventos.php?delete=<?php echo $ev['id']; ?>" class="btn btn-danger btn-sm confirm-delete" title="Eliminar"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($eventos)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;color:#888;padding:35px;">
                        <i class="fas fa-calendar-times" style="font-size:2.2rem;display:block;margin-bottom:8px;color:#94a3b8;"></i>
                        No se encontraron eventos con los filtros seleccionados.
                        <?php if ($filtroEstado !== '' || $filtroCategoria !== '' || $buscar !== ''): ?>
                            <div style="margin-top:10px;"><a href="eventos.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Restablecer filtros</a></div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php
        $pageParams = http_build_query([
            'estado'    => $filtroEstado,
            'categoria' => $filtroCategoria,
            'buscar'    => $buscar,
        ]);
        ?>
        <?php if ($page > 1): ?>
            <a href="?<?= $pageParams ?>&page=<?php echo $page-1; ?>"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
            <?php if ($p === $page): ?>
                <span class="current"><?php echo $p; ?></span>
            <?php else: ?>
                <a href="?<?= $pageParams ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?<?= $pageParams ?>&page=<?php echo $page+1; ?>"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
