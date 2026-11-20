<?php
// participante/mi_perfil.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(3);

$user_id = $_SESSION['user_id'];
$db = getDB();

$error = '';
$success = '';

// Cargar datos actuales del usuario
$stmt = $db->prepare("SELECT id, nombre, apellido, email, telefono, institucion, fecha_registro FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die('Usuario no encontrado.');
}

// 1. Procesar actualización de datos personales
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $nombre      = trim($_POST['nombre'] ?? '');
    $apellido    = trim($_POST['apellido'] ?? '');
    $telefono    = trim($_POST['telefono'] ?? '');
    $institucion = trim($_POST['institucion'] ?? '');

    if (!$nombre || !$apellido) {
        $error = 'El nombre y el apellido son obligatorios.';
    } else {
        try {
            $stmtUpd = $db->prepare("
                UPDATE usuarios 
                SET nombre = ?, apellido = ?, telefono = ?, institucion = ?, fecha_actualizacion = NOW()
                WHERE id = ?
            ");
            $stmtUpd->execute([$nombre, $apellido, $telefono, $institucion, $user_id]);

            // Actualizar nombre en la sesión
            $_SESSION['nombre'] = $nombre;
            $_SESSION['apellido'] = $apellido;

            $success = 'Tus datos de perfil han sido actualizados correctamente.';
            $usuario['nombre'] = $nombre;
            $usuario['apellido'] = $apellido;
            $usuario['telefono'] = $telefono;
            $usuario['institucion'] = $institucion;
        } catch (Exception $e) {
            $error = 'Error al actualizar el perfil: ' . $e->getMessage();
        }
    }
}

// 2. Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    $passActual = $_POST['password_actual'] ?? '';
    $passNuevo  = $_POST['password_nuevo'] ?? '';
    $passConf   = $_POST['password_confirm'] ?? '';

    if (!$passActual || !$passNuevo || !$passConf) {
        $error = 'Todos los campos de contraseña son requeridos.';
    } elseif (strlen($passNuevo) < 6) {
        $error = 'La nueva contraseña debe tener como mínimo 6 caracteres.';
    } elseif ($passNuevo !== $passConf) {
        $error = 'La nueva contraseña y la confirmación no coinciden.';
    } else {
        // Verificar contraseña actual
        $stmtPass = $db->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmtPass->execute([$user_id]);
        $hashActual = $stmtPass->fetchColumn();

        if (!password_verify($passActual, $hashActual)) {
            $error = 'La contraseña actual ingresada es incorrecta.';
        } else {
            $nuevoHash = password_hash($passNuevo, PASSWORD_BCRYPT);
            $stmtUpdPass = $db->prepare("UPDATE usuarios SET password = ?, fecha_actualizacion = NOW() WHERE id = ?");
            $stmtUpdPass->execute([$nuevoHash, $user_id]);
            $success = 'Tu contraseña ha sido cambiada exitosamente.';
        }
    }
}

$pageTitle = 'Mi Perfil';
include __DIR__ . '/../includes/header_part.php';
include __DIR__ . '/../includes/sidebar_part.php';
?>

<main class="main-content">
    <div style="margin-bottom:1.5rem;">
        <h1 style="font-size:1.6rem;color:#1e293b;font-weight:700;">
            <i class="fas fa-user-circle" style="color:#2563eb;"></i> Mi Perfil
        </h1>
        <p style="color:#64748b;margin-top:.3rem;">Administra tus datos personales y credenciales de acceso.</p>
    </div>

    <?php if ($success): ?>
        <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:.85rem 1.2rem;border-radius:10px;margin-bottom:1.2rem;display:flex;align-items:center;gap:.7rem;">
            <i class="fas fa-check-circle"></i> <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:.85rem 1.2rem;border-radius:10px;margin-bottom:1.2rem;display:flex;align-items:center;gap:.7rem;">
            <i class="fas fa-exclamation-circle"></i> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;" class="profile-grid">
        <!-- Formulario Datos Personales -->
        <div style="background:#ffffff;border-radius:12px;padding:1.8rem;box-shadow:0 1px 3px rgba(0,0,0,0.08);border:1px solid #e2e8f0;">
            <h2 style="font-size:1.2rem;color:#1e293b;margin-bottom:1.2rem;font-weight:600;display:flex;align-items:center;gap:.5rem;">
                <i class="fas fa-id-card" style="color:#2563eb;"></i> Datos Personales
            </h2>

            <form method="POST" action="mi_perfil.php">
                <input type="hidden" name="actualizar_perfil" value="1">

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:.88rem;font-weight:600;color:#334155;margin-bottom:.3rem;">Correo Electrónico (Solo Lectura)</label>
                    <input type="email" value="<?= e($usuario['email']) ?>" readonly
                           style="width:100%;padding:.7rem;border-radius:8px;border:1px solid #cbd5e1;background:#f8fafc;color:#64748b;cursor:not-allowed;">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <label style="display:block;font-size:.88rem;font-weight:600;color:#334155;margin-bottom:.3rem;">Nombre *</label>
                        <input type="text" name="nombre" value="<?= e($usuario['nombre']) ?>" required
                               style="width:100%;padding:.7rem;border-radius:8px;border:1px solid #cbd5e1;color:#1e293b;">
                    </div>
                    <div>
                        <label style="display:block;font-size:.88rem;font-weight:600;color:#334155;margin-bottom:.3rem;">Apellido *</label>
                        <input type="text" name="apellido" value="<?= e($usuario['apellido']) ?>" required
                               style="width:100%;padding:.7rem;border-radius:8px;border:1px solid #cbd5e1;color:#1e293b;">
                    </div>
                </div>

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:.88rem;font-weight:600;color:#334155;margin-bottom:.3rem;">Teléfono de Contacto</label>
                    <input type="text" name="telefono" value="<?= e($usuario['telefono']) ?>" placeholder="Ej. +51 987654321"
                           style="width:100%;padding:.7rem;border-radius:8px;border:1px solid #cbd5e1;color:#1e293b;">
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block;font-size:.88rem;font-weight:600;color:#334155;margin-bottom:.3rem;">Institución o Carrera Universitaria</label>
                    <input type="text" name="institucion" value="<?= e($usuario['institucion']) ?>" placeholder="Ej. Facultad de Ingeniería"
                           style="width:100%;padding:.7rem;border-radius:8px;border:1px solid #cbd5e1;color:#1e293b;">
                </div>

                <div style="font-size:0.8rem;color:#64748b;margin-bottom:1.2rem;">
                    Registrado desde: <strong><?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></strong>
                </div>

                <button type="submit" style="background:#2563eb;color:#fff;border:none;padding:.75rem 1.5rem;border-radius:8px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </form>
        </div>

        <!-- Formulario Cambiar Contraseña -->
        <div style="background:#ffffff;border-radius:12px;padding:1.8rem;box-shadow:0 1px 3px rgba(0,0,0,0.08);border:1px solid #e2e8f0;">
            <h2 style="font-size:1.2rem;color:#1e293b;margin-bottom:1.2rem;font-weight:600;display:flex;align-items:center;gap:.5rem;">
                <i class="fas fa-lock" style="color:#d97706;"></i> Cambiar Contraseña
            </h2>

            <form method="POST" action="mi_perfil.php">
                <input type="hidden" name="cambiar_password" value="1">

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:.88rem;font-weight:600;color:#334155;margin-bottom:.3rem;">Contraseña Actual *</label>
                    <div class="password-wrapper">
                        <input type="password" id="password_actual" name="password_actual" required placeholder="Tu contraseña actual"
                               style="width:100%;padding:.7rem;border-radius:8px;border:1px solid #cbd5e1;color:#1e293b;">
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('password_actual', this)" title="Mostrar / Ocultar contraseña" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:.88rem;font-weight:600;color:#334155;margin-bottom:.3rem;">Nueva Contraseña * (mínimo 6 caracteres)</label>
                    <div class="password-wrapper">
                        <input type="password" id="password_nuevo" name="password_nuevo" minlength="6" required placeholder="Mínimo 6 caracteres"
                               style="width:100%;padding:.7rem;border-radius:8px;border:1px solid #cbd5e1;color:#1e293b;">
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('password_nuevo', this)" title="Mostrar / Ocultar contraseña" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block;font-size:.88rem;font-weight:600;color:#334155;margin-bottom:.3rem;">Confirmar Nueva Contraseña *</label>
                    <div class="password-wrapper">
                        <input type="password" id="password_confirm" name="password_confirm" minlength="6" required placeholder="Repite la nueva contraseña"
                               style="width:100%;padding:.7rem;border-radius:8px;border:1px solid #cbd5e1;color:#1e293b;">
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('password_confirm', this)" title="Mostrar / Ocultar contraseña" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" style="background:#d97706;color:#fff;border:none;padding:.75rem 1.5rem;border-radius:8px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;">
                    <i class="fas fa-key"></i> Actualizar Contraseña
                </button>
            </form>
        </div>
    </div>
</main>

<style>
@media (max-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
