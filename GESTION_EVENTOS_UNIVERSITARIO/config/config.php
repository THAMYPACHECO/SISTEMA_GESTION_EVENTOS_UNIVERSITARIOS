<?php
/**
 * Configuración global del sistema
 * Sistema de Gestión de Eventos Universitarios
 */

// Iniciar sesión solo si no existe una activa y no se ejecuta por CLI
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // Cambiar a true en producción con HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ─── Rutas y URL ──────────────────────────────────────────────────────────────
define('BASE_URL', 'http://localhost/GESTION_EVENTOS_UNIVERSITARIO');
define('BASE_PATH', dirname(__DIR__));   // raíz del proyecto en disco

// ─── Información de la aplicación ────────────────────────────────────────────
define('APP_NAME',    'UCENM');
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR',  'Universidad');

// ─── Roles del sistema ────────────────────────────────────────────────────────
define('ROL_ADMIN',        1);
define('ROL_ORGANIZADOR',  2);
define('ROL_PARTICIPANTE', 3);

// ─── Rutas de redirección por rol ────────────────────────────────────────────
define('REDIRECT_ADMIN',        BASE_URL . '/admin/dashboard.php');
define('REDIRECT_ORGANIZADOR',  BASE_URL . '/organizador/dashboard.php');
define('REDIRECT_PARTICIPANTE', BASE_URL . '/participante/dashboard.php');

// ─── Configuración de zona horaria local ─────────────────────────────────────
// Central America Standard Time (UTC-06:00)
date_default_timezone_set('America/Managua');

// ─── Función para sincronizar y actualizar estados de eventos ─────────────────
/**
 * Actualiza automáticamente los estados de los eventos según la fecha y hora local:
 * - 'finalizado': si CONCAT(fecha_fin, ' ', hora_fin) < NOW() y no está cancelado.
 * - 'en_curso': si CONCAT(fecha_inicio, ' ', hora_inicio) <= NOW() y CONCAT(fecha_fin, ' ', hora_fin) >= NOW() y estaba programado.
 */
function actualizarEstadosEventos(?PDO $db = null): void {
    static $lastExecuted = 0;
    $now = time();
    // Ejecutar como máximo una vez cada 5 segundos para eficiencia
    if ($now - $lastExecuted < 5 && $db !== null) {
        return;
    }
    $lastExecuted = $now;

    try {
        if ($db === null) {
            $db = getDB();
        }
        // 1. Finalizar eventos cuya fecha y hora de fin ya pasaron
        $db->exec("
            UPDATE eventos
            SET estado = 'finalizado'
            WHERE estado NOT IN ('finalizado', 'cancelado')
              AND CONCAT(fecha_fin, ' ', hora_fin) < NOW()
        ");

        // 2. Colocar en curso eventos iniciados que aún no culminan
        $db->exec("
            UPDATE eventos
            SET estado = 'en_curso'
            WHERE estado = 'programado'
              AND CONCAT(fecha_inicio, ' ', hora_inicio) <= NOW()
              AND CONCAT(fecha_fin, ' ', hora_fin) >= NOW()
        ");
    } catch (Throwable $t) {
        error_log('Error en actualizarEstadosEventos: ' . $t->getMessage());
    }
}

// ─── Incluir dependencias base ───────────────────────────────────────────────
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/auth/check_session.php';

// ─── Helpers de escape ───────────────────────────────────────────────────────
/**
 * Escapa una cadena para salida HTML segura.
 */
function e(?string $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
