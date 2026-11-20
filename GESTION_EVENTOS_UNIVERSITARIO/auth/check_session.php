<?php
/**
 * Verificación de sesión y control de acceso por rol
 * Sistema de Gestión de Eventos Universitarios
 */

// Asegurarse de que la sesión esté iniciada (evitar en CLI)
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica que el usuario esté autenticado y, opcionalmente,
 * que tenga el rol requerido. Si no cumple, redirige.
 *
 * @param int|int[]|null $rol  ID de rol (o array de IDs) permitido.
 *                             null = cualquier usuario autenticado.
 */
function requireLogin(mixed $rol = null): void {
    // ── 1. Comprobar autenticación ──────────────────────────────────────────
    if (empty($_SESSION['user_id'])) {
        $loginUrl = (defined('BASE_URL') ? BASE_URL : '') . '/auth/login.php';
        header('Location: ' . $loginUrl . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }

    // ── 2. Comprobar rol si se especificó ───────────────────────────────────
    if ($rol !== null) {
        $rolesPermitidos = is_array($rol) ? $rol : [$rol];
        $rolUsuario      = (int) ($_SESSION['rol_id'] ?? 0);

        if (!in_array($rolUsuario, $rolesPermitidos, true)) {
            // Redirigir al dashboard que corresponda al rol real del usuario
            redirectByRole($rolUsuario);
        }
    }
}

/**
 * Redirige al dashboard correspondiente según el rol del usuario.
 *
 * @param int|null $rolId  Si se omite, usa el rol de la sesión actual.
 */
function redirectByRole(?int $rolId = null): void {
    $rolId = $rolId ?? (int) ($_SESSION['rol_id'] ?? 0);
    $base  = defined('BASE_URL') ? BASE_URL : '';

    switch ($rolId) {
        case 1: // Administrador
            header('Location: ' . $base . '/admin/dashboard.php');
            break;
        case 2: // Organizador
            header('Location: ' . $base . '/organizador/dashboard.php');
            break;
        case 3: // Participante
            header('Location: ' . $base . '/participante/dashboard.php');
            break;
        default:
            header('Location: ' . $base . '/auth/login.php');
            break;
    }
    exit;
}

/**
 * Devuelve true si el usuario está autenticado.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Devuelve true si el usuario tiene uno de los roles indicados.
 *
 * @param int|int[] $rol
 */
function hasRole(mixed $rol): bool {
    if (!isLoggedIn()) {
        return false;
    }
    $roles = is_array($rol) ? $rol : [$rol];
    return in_array((int) ($_SESSION['rol_id'] ?? 0), $roles, true);
}
