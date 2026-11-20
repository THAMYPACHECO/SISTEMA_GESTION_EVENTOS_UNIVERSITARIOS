<?php
// admin/usuarios.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(1);

$db = getDB();
$pageTitle = 'Gestión de Usuarios';

// Toggle activo/inactivo
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $uid = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE usuarios SET activo = NOT activo WHERE id = ?");
    $stmt->execute([$uid]);
    $redirectParams = [];
    if (isset($_GET['rol_id']) && $_GET['rol_id'] !== '') $redirectParams['rol_id'] = $_GET['rol_id'];
    if (isset($_GET['q']) && $_GET['q'] !== '') $redirectParams['q'] = $_GET['q'];
    if (isset($_GET['page']) && $_GET['page'] !== '') $redirectParams['page'] = $_GET['page'];
    $redirectUrl = 'usuarios.php?msg=toggle' . (!empty($redirectParams) ? '&' . http_build_query($redirectParams) : '');
    header('Location: ' . $redirectUrl);
    exit;
}

// Filtros, búsqueda y paginación
$search    = trim($_GET['q'] ?? '');
$filtroRol = trim($_GET['rol_id'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 20;
$offset    = ($page - 1) * $perPage;

$where  = "WHERE 1=1";
$params = [];

if ($search !== '') {
    $where  .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ? OR u.institucion LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filtroRol !== '' && is_numeric($filtroRol)) {
    $where .= " AND u.rol_id = ?";
    $params[] = (int)$filtroRol;
}

// Conteo total para paginación
$total = $db->prepare("SELECT COUNT(*) FROM usuarios u $where");
$total->execute($params);
$totalRows  = (int)$total->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

// Consulta de usuarios
$stmt = $db->prepare("
    SELECT u.id, u.nombre, u.apellido, u.email, u.telefono, u.institucion, u.rol_id, u.activo, u.fecha_registro, u.fecha_actualizacion,
           r.nombre AS rol
    FROM usuarios u
    LEFT JOIN roles r ON r.id = u.rol_id
    $where
    ORDER BY u.id ASC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Conteos rápidos por rol para los botones/pills
$counts = [
    'todos'        => (int)$db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
    'admin'        => (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE rol_id = 1")->fetchColumn(),
    'organizador'  => (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE rol_id = 2")->fetchColumn(),
    'participante' => (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE rol_id = 3")->fetchColumn(),
];

// Roles para modal creación y selectores
$roles = $db->query("SELECT id, nombre FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Crear usuario rápido via POST
$msgError = $msgOk = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $apellido    = trim($_POST['apellido'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $telefono    = trim($_POST['telefono'] ?? '');
    $institucion = trim($_POST['institucion'] ?? '');
    $rol_id      = (int)($_POST['rol_id'] ?? 3);

    if (!$nombre || !$apellido || !$email || !$password) {
        $msgError = 'Todos los campos obligatorios deben completarse.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msgError = 'El email no es válido.';
    } elseif (strlen($password) < 6) {
        $msgError = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        // Check duplicado
        $chk = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetchColumn()) {
            $msgError = 'Ya existe un usuario con ese email.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $db->prepare("INSERT INTO usuarios (nombre,apellido,email,password,telefono,institucion,rol_id,activo,fecha_registro,fecha_actualizacion)
                                   VALUES (?,?,?,?,?,?,?,1,NOW(),NOW())");
            $ins->execute([$nombre, $apellido, $email, $hash, $telefono, $institucion, $rol_id]);
            header('Location: usuarios.php?msg=created');
            exit;
        }
    }
}

$msg = $_GET['msg'] ?? '';
include __DIR__ . '/../includes/header_admin.php';
?>
<div class="page-header">
    <h1><i class="fas fa-users"></i> Gestión de Usuarios</h1>
    <div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / <strong>Usuarios</strong></div>
</div>

<?php if ($msg === 'created'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Usuario creado exitosamente.</div><?php endif; ?>
<?php if ($msg === 'updated'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Usuario actualizado.</div><?php endif; ?>
<?php if ($msg === 'toggle'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Estado del usuario actualizado.</div><?php endif; ?>
<?php if ($msgError): ?><div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($msgError); ?></div><?php endif; ?>

<!-- Botones de Filtro Rápido por Rol (Pills) -->
<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
    <span style="font-weight:600;font-size:0.88rem;color:#475569;margin-right:4px;"><i class="fas fa-filter"></i> Filtrar por Rol:</span>
    <a href="usuarios.php<?= $search !== '' ? '?q=' . urlencode($search) : '' ?>" 
       style="padding:6px 14px;border-radius:20px;text-decoration:none;font-size:0.83rem;font-weight:600;border:1px solid #cbd5e1;background:<?= $filtroRol === '' ? '#1e293b;color:#fff;' : '#fff;color:#475569;' ?>">
        Todos (<?= $counts['todos'] ?>)
    </a>
    <a href="usuarios.php?rol_id=1<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" 
       style="padding:6px 14px;border-radius:20px;text-decoration:none;font-size:0.83rem;font-weight:600;border:1px solid #fca5a5;background:<?= $filtroRol === '1' ? '#dc2626;color:#fff;' : '#fef2f2;color:#b91c1c;' ?>">
        <i class="fas fa-user-shield"></i> Administradores (<?= $counts['admin'] ?>)
    </a>
    <a href="usuarios.php?rol_id=2<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" 
       style="padding:6px 14px;border-radius:20px;text-decoration:none;font-size:0.83rem;font-weight:600;border:1px solid #fed7aa;background:<?= $filtroRol === '2' ? '#ea580c;color:#fff;' : '#fff7ed;color:#c2410c;' ?>">
        <i class="fas fa-user-tie"></i> Organizadores (<?= $counts['organizador'] ?>)
    </a>
    <a href="usuarios.php?rol_id=3<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" 
       style="padding:6px 14px;border-radius:20px;text-decoration:none;font-size:0.83rem;font-weight:600;border:1px solid #93c5fd;background:<?= $filtroRol === '3' ? '#2563eb;color:#fff;' : '#eff6ff;color:#1d4ed8;' ?>">
        <i class="fas fa-user-graduate"></i> Participantes (<?= $counts['participante'] ?>)
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="card-title">Listado de Usuarios (<?php echo $totalRows; ?>)</span>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalCrear').classList.add('active')">
            <i class="fas fa-plus"></i> Nuevo Usuario
        </button>
    </div>

    <!-- Barra de Filtros y Búsqueda -->
    <form method="GET" action="usuarios.php" class="search-bar" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:12px;margin-bottom:16px;">
        <input type="text" name="q" class="form-control" placeholder="Buscar por nombre, email o institución..." value="<?php echo htmlspecialchars($search); ?>" style="max-width:280px;">
        
        

        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
        <?php if ($search !== '' || $filtroRol !== ''): ?>
            <a href="usuarios.php" class="btn btn-secondary" title="Quitar filtros"><i class="fas fa-times"></i> Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Institución</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Fecha Registro</th>
                    <th style="text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><strong>#<?php echo $u['id']; ?></strong></td>
                    <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($u['apellido']); ?></td>
                    <td><code><?php echo htmlspecialchars($u['email']); ?></code></td>
                    <td><?php echo htmlspecialchars($u['telefono'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($u['institucion'] ?: '-'); ?></td>
                    <td>
                        <?php
                        $rolBadge = 'badge-info';
                        if ((int)$u['rol_id'] === 1) $rolBadge = 'badge-danger';
                        elseif ((int)$u['rol_id'] === 2) $rolBadge = 'badge-warning';
                        ?>
                        <span class="badge <?php echo $rolBadge; ?>"><?php echo htmlspecialchars($u['rol'] ?? 'Rol ' . $u['rol_id']); ?></span>
                    </td>
                    <td>
                        <?php if ($u['activo']): ?>
                            <span class="badge badge-success"><i class="fas fa-check"></i> Activo</span>
                        <?php else: ?>
                            <span class="badge badge-danger"><i class="fas fa-times"></i> Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td><small><?php echo date('d/m/Y H:i', strtotime($u['fecha_registro'])); ?></small></td>
                    <td style="text-align:center;white-space:nowrap;">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="usuario_form.php?id=<?php echo $u['id']; ?>" class="btn btn-warning btn-sm" title="Editar Usuario"><i class="fas fa-edit"></i> Editar</a>
                            <?php
                            $toggleParams = http_build_query(array_filter([
                                'toggle' => $u['id'],
                                'rol_id' => $filtroRol,
                                'q'      => $search,
                                'page'   => $page > 1 ? $page : null
                            ]));
                            ?>
                            <a href="usuarios.php?<?php echo $toggleParams; ?>"
                               class="btn btn-sm <?php echo $u['activo'] ? 'btn-danger' : 'btn-success'; ?>"
                               onclick="return confirm('¿Cambiar estado del usuario?')">
                                <i class="fas fa-<?php echo $u['activo'] ? 'ban' : 'check'; ?>"></i>
                                <?php echo $u['activo'] ? 'Desactivar' : 'Activar'; ?>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="10" style="text-align:center;color:#888;padding:35px;">
                        <i class="fas fa-user-slash" style="font-size:2.2rem;display:block;margin-bottom:8px;color:#94a3b8;"></i>
                        No se encontraron usuarios con los filtros aplicados.
                        <?php if ($search !== '' || $filtroRol !== ''): ?>
                            <div style="margin-top:10px;"><a href="usuarios.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Restablecer filtros</a></div>
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
        $pageParams = http_build_query(array_filter([
            'q'      => $search !== '' ? $search : null,
            'rol_id' => $filtroRol !== '' ? $filtroRol : null,
        ]));
        $prefix = $pageParams ? $pageParams . '&' : '';
        ?>
        <?php if ($page > 1): ?>
            <a href="?<?php echo $prefix; ?>page=<?php echo $page-1; ?>"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
            <?php if ($p === $page): ?>
                <span class="current"><?php echo $p; ?></span>
            <?php else: ?>
                <a href="?<?php echo $prefix; ?>page=<?php echo $p; ?>"><?php echo $p; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?<?php echo $prefix; ?>page=<?php echo $page+1; ?>"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Crear Usuario -->
<div class="modal-overlay" id="modalCrear">
    <div class="modal">
        <button class="close-modal" onclick="document.getElementById('modalCrear').classList.remove('active')">&times;</button>
        <h2><i class="fas fa-user-plus"></i> Nuevo Usuario</h2>
        <form method="POST">
            <input type="hidden" name="action" value="crear">
            <div class="form-row">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Apellido *</label>
                    <input type="text" name="apellido" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Contraseña * (mín. 6 caracteres)</label>
                <div class="password-wrapper">
                    <input type="password" id="modal_user_pass" name="password" class="form-control" required minlength="6">
                    <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('modal_user_pass', this)" title="Mostrar / Ocultar contraseña" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control" placeholder="Ej. +504 98765432">
                </div>
                <div class="form-group">
                    <label>Institución</label>
                    <input type="text" name="institucion" class="form-control" placeholder="Ej. Universidad UCENM">
                </div>
            </div>
            <div class="form-group">
                <label>Rol *</label>
                <select name="rol_id" class="form-control" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php echo $r['id'] == 3 ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Crear Usuario</button>
        </form>
    </div>
</div>
<?php if ($msgError): ?>
<script>document.getElementById('modalCrear').classList.add('active');</script>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
