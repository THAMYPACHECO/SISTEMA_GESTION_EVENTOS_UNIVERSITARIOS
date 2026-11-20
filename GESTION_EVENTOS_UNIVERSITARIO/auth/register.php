<?php
/**
 * Registro de nuevos participantes
 * Sistema de Gestión de Eventos Universitarios
 */

require_once __DIR__ . '/../config/config.php';

// Si ya está autenticado, redirigir
if (isLoggedIn()) {
    redirectByRole();
}

$errores  = [];
$success  = false;
$campos   = [
    'nombre'           => '',
    'apellido'         => '',
    'email'            => '',
    'telefono'         => '',
    'institucion'      => '',
];

// ── Proceso POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y sanear entradas
    foreach ($campos as $key => $_) {
        $campos[$key] = trim($_POST[$key] ?? '');
    }
    $password          = $_POST['password']          ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    // ── Validaciones ──────────────────────────────────────────────────────────
    if (empty($campos['nombre'])) {
        $errores['nombre'] = 'El nombre es obligatorio.';
    } elseif (mb_strlen($campos['nombre']) > 100) {
        $errores['nombre'] = 'El nombre no puede superar 100 caracteres.';
    }

    if (empty($campos['apellido'])) {
        $errores['apellido'] = 'El apellido es obligatorio.';
    } elseif (mb_strlen($campos['apellido']) > 100) {
        $errores['apellido'] = 'El apellido no puede superar 100 caracteres.';
    }

    if (empty($campos['email'])) {
        $errores['email'] = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($campos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'El correo electrónico no tiene un formato válido.';
    } elseif (mb_strlen($campos['email']) > 150) {
        $errores['email'] = 'El correo no puede superar 150 caracteres.';
    }

    if (empty($password)) {
        $errores['password'] = 'La contraseña es obligatoria.';
    } elseif (mb_strlen($password) < 8) {
        $errores['password'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errores['password'] = 'La contraseña debe contener al menos una mayúscula.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errores['password'] = 'La contraseña debe contener al menos un número.';
    }

    if ($password !== $confirmar_password) {
        $errores['confirmar_password'] = 'Las contraseñas no coinciden.';
    }

    if (!empty($campos['telefono']) && !preg_match('/^\+?[\d\s\-]{7,20}$/', $campos['telefono'])) {
        $errores['telefono'] = 'El teléfono no tiene un formato válido.';
    }

    if (empty($campos['institucion'])) {
        $errores['institucion'] = 'La institución es obligatoria.';
    } elseif (mb_strlen($campos['institucion']) > 200) {
        $errores['institucion'] = 'La institución no puede superar 200 caracteres.';
    }

    // ── Si no hay errores, insertar ───────────────────────────────────────────
    if (empty($errores)) {
        try {
            $pdo = getDB();

            // Verificar que el email no exista
            $stmtCheck = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
            $stmtCheck->execute([':email' => $campos['email']]);

            if ($stmtCheck->fetch()) {
                $errores['email'] = 'Ya existe una cuenta con ese correo electrónico.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                $stmtInsert = $pdo->prepare(
                    'INSERT INTO usuarios 
                        (nombre, apellido, email, password, telefono, institucion, rol_id, activo, fecha_registro, fecha_actualizacion)
                     VALUES
                        (:nombre, :apellido, :email, :password, :telefono, :institucion, :rol_id, 1, NOW(), NOW())'
                );
                $stmtInsert->execute([
                    ':nombre'      => $campos['nombre'],
                    ':apellido'    => $campos['apellido'],
                    ':email'       => $campos['email'],
                    ':password'    => $hash,
                    ':telefono'    => $campos['telefono'] ?: null,
                    ':institucion' => $campos['institucion'],
                    ':rol_id'      => ROL_PARTICIPANTE,  // 3
                ]);

                // Redirigir al login con mensaje de éxito
                header('Location: ' . BASE_URL . '/auth/login.php?registered=1');
                exit;
            }
        } catch (PDOException $e) {
            error_log('Register error: ' . $e->getMessage());
            $errores['general'] = 'Error interno. Intente de nuevo más tarde.';
        }
    }
}

/**
 * Devuelve la clase CSS del campo según si tiene error o no.
 */
function fieldClass(array $errores, string $campo): string {
    return isset($errores[$campo]) ? ' input-error' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro &mdash; <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a237e 0%, #0d47a1 50%, #1565c0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 1.5rem;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.35);
            width: 100%;
            max-width: 540px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #1a237e, #1565c0);
            color: #fff;
            text-align: center;
            padding: 2rem;
        }
        .card-header .logo {
            width: 64px; height: 64px;
            background: rgba(255,255,255,.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto .8rem;
            font-size: 1.8rem;
        }
        .card-header h1 { font-size: 1.3rem; font-weight: 700; margin-bottom: .2rem; }
        .card-header p  { font-size: .82rem; opacity: .8; }

        .card-body { padding: 2rem; }

        .alert-danger {
            background: #fdecea;
            color: #c62828;
            border-left: 4px solid #c62828;
            border-radius: 8px;
            padding: .75rem 1rem;
            margin-bottom: 1.2rem;
            font-size: .875rem;
        }

        .row { display: flex; gap: 1rem; }
        .row .form-group { flex: 1; }

        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            color: #333;
            margin-bottom: .35rem;
        }
        .form-group input {
            width: 100%;
            padding: .65rem .9rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: .9rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-group input:focus {
            border-color: #1565c0;
            box-shadow: 0 0 0 3px rgba(21,101,192,.15);
        }
        .form-group input.input-error {
            border-color: #c62828;
        }
        .field-error {
            color: #c62828;
            font-size: .78rem;
            margin-top: .25rem;
        }

        .password-hint {
            font-size: .75rem;
            color: #777;
            margin-top: .25rem;
        }

        .btn-primary {
            display: block;
            width: 100%;
            padding: .85rem;
            background: linear-gradient(135deg, #1a237e, #1565c0);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s, transform .1s;
            margin-top: .5rem;
        }
        .btn-primary:hover  { opacity: .9; }
        .btn-primary:active { transform: scale(.98); }

        .card-footer {
            text-align: center;
            padding: 1rem 2rem 1.5rem;
            border-top: 1px solid #f0f0f0;
            font-size: .85rem;
            color: #555;
        }
        .card-footer a { color: #1565c0; font-weight: 600; text-decoration: none; }
        .card-footer a:hover { text-decoration: underline; }

        @media (max-width: 480px) {
            .row { flex-direction: column; gap: 0; }
        }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <div class="logo">🎓</div>
        <h1 style="color: #93c5fd;">CREAR CUENTA &nbsp; &nbsp;</h1> 
        <p><?= e(APP_NAME) ?> &nbsp; Registro de participante</p>
      
    </div>

    <div class="card-body">
        <?php if (!empty($errores['general'])): ?>
            <div class="alert-danger"><?= e($errores['general']) ?></div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>

            <!-- Nombre y Apellido -->
            <div class="row">
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input
                        type="text" id="nombre" name="nombre"
                        value="<?= e($campos['nombre']) ?>"
                        placeholder="Ej: María"
                        class="<?= isset($errores['nombre']) ? 'input-error' : '' ?>"
                        required
                    >
                    <?php if (isset($errores['nombre'])): ?>
                        <span class="field-error"><?= e($errores['nombre']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="apellido">Apellido *</label>
                    <input
                        type="text" id="apellido" name="apellido"
                        value="<?= e($campos['apellido']) ?>"
                        placeholder="Ej: García"
                        class="<?= isset($errores['apellido']) ? 'input-error' : '' ?>"
                        required
                    >
                    <?php if (isset($errores['apellido'])): ?>
                        <span class="field-error"><?= e($errores['apellido']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Correo electrónico *</label>
                <input
                    type="email" id="email" name="email"
                    value="<?= e($campos['email']) ?>"
                    placeholder="nombre@universidad.edu"
                    class="<?= isset($errores['email']) ? 'input-error' : '' ?>"
                    required autocomplete="username"
                >
                <?php if (isset($errores['email'])): ?>
                    <span class="field-error"><?= e($errores['email']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Contraseña y Confirmar -->
            <div class="row">
                <div class="form-group">
                    <label for="password">Contraseña *</label>
                    <div class="password-wrapper">
                        <input
                            type="password" id="password" name="password"
                            placeholder="••••••••"
                            class="<?= isset($errores['password']) ? 'input-error' : '' ?>"
                            required autocomplete="new-password"
                        >
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('password', this)" title="Mostrar / Ocultar contraseña" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errores['password'])): ?>
                        <span class="field-error"><?= e($errores['password']) ?></span>
                    <?php else: ?>
                        <span class="password-hint">Mín. 8 chars, 1 mayúscula y 1 número.</span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="confirmar_password">Confirmar *</label>
                    <div class="password-wrapper">
                        <input
                            type="password" id="confirmar_password" name="confirmar_password"
                            placeholder="••••••••"
                            class="<?= isset($errores['confirmar_password']) ? 'input-error' : '' ?>"
                            required autocomplete="new-password"
                        >
                        <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('confirmar_password', this)" title="Mostrar / Ocultar contraseña" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errores['confirmar_password'])): ?>
                        <span class="field-error"><?= e($errores['confirmar_password']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Teléfono e Institución -->
            <div class="row">
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input
                        type="tel" id="telefono" name="telefono"
                        value="<?= e($campos['telefono']) ?>"
                        placeholder="+51 999 000 111"
                        class="<?= isset($errores['telefono']) ? 'input-error' : '' ?>"
                    >
                    <?php if (isset($errores['telefono'])): ?>
                        <span class="field-error"><?= e($errores['telefono']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="institucion">Institución *</label>
                    <input
                        type="text" id="institucion" name="institucion"
                        value="<?= e($campos['institucion']) ?>"
                        placeholder="Universidad Nacional"
                        class="<?= isset($errores['institucion']) ? 'input-error' : '' ?>"
                        required
                    >
                    <?php if (isset($errores['institucion'])): ?>
                        <span class="field-error"><?= e($errores['institucion']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn-primary">Crear cuenta</button>
        </form>
    </div>

    <div class="card-footer">
        ¿Ya tienes cuenta?
        <a href="<?= BASE_URL ?>/auth/login.php">Inicia sesión aquí</a>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
