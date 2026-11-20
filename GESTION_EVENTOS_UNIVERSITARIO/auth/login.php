<?php
/**
 * Inicio de sesión
 * Sistema de Gestión de Eventos Universitarios
 */

require_once __DIR__ . '/../config/config.php';

// Si ya está autenticado, redirigir a su dashboard
if (isLoggedIn()) {
    redirectByRole();
}

$error   = '';
$success = '';
$email   = '';

// Mensaje de logout exitoso
if (isset($_GET['logout'])) {
    $success = 'Has cerrado sesión correctamente.';
}
// Mensaje de registro exitoso
if (isset($_GET['registered'])) {
    $success = 'Registro exitoso. Ahora puedes iniciar sesión.';
}

// ── Proceso POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Por favor, completa todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no tiene un formato válido.';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare(
                'SELECT u.id, u.nombre, u.apellido, u.email, u.password, 
                        u.rol_id, u.activo, r.nombre AS rol_nombre
                 FROM   usuarios u
                 INNER JOIN roles r ON r.id = u.rol_id
                 WHERE  u.email = :email
                 LIMIT  1'
            );
            $stmt->execute([':email' => $email]);
            $usuario = $stmt->fetch();

            if (!$usuario || !password_verify($password, $usuario['password'])) {
                $error = 'Correo o contraseña incorrectos.';
            } elseif (!(bool) $usuario['activo']) {
                $error = 'Tu cuenta está desactivada. Contacta al administrador.';
            } else {
                // Regenerar ID de sesión para prevenir session fixation
                session_regenerate_id(true);

                $_SESSION['user_id']     = $usuario['id'];
                $_SESSION['nombre']      = $usuario['nombre'];
                $_SESSION['apellido']    = $usuario['apellido'];
                $_SESSION['email']       = $usuario['email'];
                $_SESSION['rol_id']      = $usuario['rol_id'];
                $_SESSION['rol_nombre']  = $usuario['rol_nombre'];

                // Redirigir según rol
                redirectByRole((int) $usuario['rol_id']);
            }
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'Error interno. Intente de nuevo más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión &mdash; <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ── Reset & base ───────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a237e 0%, #0d47a1 50%, #1565c0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 1rem;
        }

        /* ── Card ───────────────────────────────────────────────────────── */
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.35);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }

        /* ── Cabecera ────────────────────────────────────────────────────── */
        .card-header {
            background: linear-gradient(135deg, #1a237e, #1565c0);
            color: #fff;
            text-align: center;
            padding: 2.5rem 2rem 2rem;
        }
        .card-header .logo {
            width: 72px; height: 72px;
            background: rgba(255,255,255,.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }
        .card-header h1 { font-size: 1.4rem; font-weight: 700; margin-bottom: .3rem; }
        .card-header p  { font-size: .85rem; opacity: .8; }

        /* ── Cuerpo ──────────────────────────────────────────────────────── */
        .card-body { padding: 2rem; }

        .alert {
            border-radius: 8px;
            padding: .75rem 1rem;
            margin-bottom: 1.2rem;
            font-size: .875rem;
        }
        .alert-danger  { background: #fdecea; color: #c62828; border-left: 4px solid #c62828; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; }

        .form-group { margin-bottom: 1.2rem; }
        .form-group label {
            display: block;
            font-size: .85rem;
            font-weight: 600;
            color: #333;
            margin-bottom: .4rem;
        }
        .form-group input {
            width: 100%;
            padding: .7rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: .95rem;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        .form-group input:focus {
            border-color: #1565c0;
            box-shadow: 0 0 0 3px rgba(21,101,192,.15);
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
            padding: 1.2rem 2rem 1.5rem;
            border-top: 1px solid #f0f0f0;
            font-size: .85rem;
            color: #555;
        }
        .card-footer a { color: #1565c0; font-weight: 600; text-decoration: none; }
        .card-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="card">
    <!-- Cabecera -->
    <div class="card-header">
        <div class="logo">🎓</div>
        <h1 style="color: #93c5fd;"><?= e(APP_NAME) ?></h1>
        <p>Plataforma de Gestión de Eventos</p>
    </div>

    <!-- Cuerpo -->
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($email) ?>"
                    placeholder="usuario@universidad.edu"
                    required
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('password', this)" title="Mostrar / Ocultar contraseña" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary">Iniciar Sesión</button>
        </form>
    </div>

    <!-- Pie -->
    <div class="card-footer">
        ¿No tienes cuenta?
        <a href="<?= BASE_URL ?>/auth/register.php">Regístrate como participante</a>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
