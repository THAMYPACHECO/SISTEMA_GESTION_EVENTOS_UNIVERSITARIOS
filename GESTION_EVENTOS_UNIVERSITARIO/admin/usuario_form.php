<?php
// admin/usuario_form.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(1);

$db = getDB();
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $id !== null;
$pageTitle = $isEdit ? 'Editar Usuario' : 'Nuevo Usuario';

$roles = $db->query("SELECT id, nombre FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Cargar usuario existente
$usuario = ['nombre'=>'','apellido'=>'','email'=>'','telefono'=>'','institucion'=>'','rol_id'=>3,'activo'=>1];
if ($isEdit) {
    $stmtU = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmtU->execute([$id]);
    $dbUser = $stmtU->fetch(PDO::FETCH_ASSOC);
    if (!$dbUser) { header('Location: usuarios.php'); exit; }
    $usuario = $dbUser;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre     = trim($_POST['nombre'] ?? '');
    $apellido   = trim($_POST['apellido'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $telefono   = trim($_POST['telefono'] ?? '');
    $institucion= trim($_POST['institucion'] ?? '');
    $rol_id     = (int)($_POST['rol_id'] ?? 3);
    $activo     = isset($_POST['activo']) ? 1 : 0;

    // Validaciones
    if (!$nombre)   $errors[] = 'El nombre es obligatorio.';
    if (!$apellido) $errors[] = 'El apellido es obligatorio.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if (!$isEdit && strlen($password) < 6) $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
    if ($password && $password !== $confirm) $errors[] = 'Las contraseñas no coinciden.';

    if (empty($errors)) {
        // Check email duplicado
        $chk = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $chk->execute([$email, $id ?? 0]);
        if ($chk->fetchColumn()) {
            $errors[] = 'Ya existe un usuario con ese email.';
        }
    }

    if (empty($errors)) {
        if ($isEdit) {
            if ($password) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE usuarios SET nombre=?,apellido=?,email=?,password=?,telefono=?,institucion=?,rol_id=?,activo=?,fecha_actualizacion=NOW() WHERE id=?");
                $stmt->execute([$nombre,$apellido,$email,$hash,$telefono,$institucion,$rol_id,$activo,$id]);
            } else {
                $stmt = $db->prepare("UPDATE usuarios SET nombre=?,apellido=?,email=?,telefono=?,institucion=?,rol_id=?,activo=?,fecha_actualizacion=NOW() WHERE id=?");
                $stmt->execute([$nombre,$apellido,$email,$telefono,$institucion,$rol_id,$activo,$id]);
            }
            header('Location: usuarios.php?msg=updated');
            exit;
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO usuarios (nombre,apellido,email,password,telefono,institucion,rol_id,activo,fecha_registro,fecha_actualizacion)
                                   VALUES (?,?,?,?,?,?,?,1,NOW(),NOW())");
            $stmt->execute([$nombre,$apellido,$email,$hash,$telefono,$institucion,$rol_id]);
            header('Location: usuarios.php?msg=created');
            exit;
        }
    }
    // Keep posted values
    $usuario = array_merge($usuario, compact('nombre','apellido','email','telefono','institucion','rol_id','activo'));
}

include __DIR__ . '/../includes/header_admin.php';
?>
<div class="page-header">
    <h1><i class="fas fa-<?php echo $isEdit ? 'user-edit' : 'user-plus'; ?>"></i> <?php echo $pageTitle; ?></h1>
    <div class="breadcrumb">
        <a href="dashboard.php">Dashboard</a> /
        <a href="usuarios.php">Usuarios</a> /
        <strong><?php echo $pageTitle; ?></strong>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle"></i>
    <ul style="margin:8px 0 0 16px;">
        <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card" style="max-width:720px;">
    <div class="card-header">
        <span class="card-title"><?php echo $pageTitle; ?></span>
    </div>
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($usuario['nombre']); ?>">
            </div>
            <div class="form-group">
                <label>Apellido *</label>
                <input type="text" name="apellido" class="form-control" required value="<?php echo htmlspecialchars($usuario['apellido']); ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($usuario['email']); ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Contraseña <?php echo $isEdit ? '(dejar vacío para no cambiar)' : '*'; ?></label>
                <div class="password-wrapper">
                    <input type="password" id="admin_user_pass" name="password" class="form-control" <?php echo $isEdit ? '' : 'required'; ?> minlength="6" autocomplete="new-password">
                    <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('admin_user_pass', this)" title="Mostrar / Ocultar contraseña" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Confirmar Contraseña</label>
                <div class="password-wrapper">
                    <input type="password" id="admin_user_pass_confirm" name="confirm_password" class="form-control" autocomplete="new-password">
                    <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('admin_user_pass_confirm', this)" title="Mostrar / Ocultar contraseña" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Institución</label>
                <input type="text" name="institucion" class="form-control" value="<?php echo htmlspecialchars($usuario['institucion'] ?? ''); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Rol *</label>
                <select name="rol_id" class="form-control" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php echo (int)$usuario['rol_id'] === (int)$r['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($isEdit): ?>
            <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:4px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;">
                    <input type="checkbox" name="activo" value="1" <?php echo $usuario['activo'] ? 'checked' : ''; ?> style="width:18px;height:18px;">
                    Usuario Activo
                </label>
            </div>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
            <a href="usuarios.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancelar</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
