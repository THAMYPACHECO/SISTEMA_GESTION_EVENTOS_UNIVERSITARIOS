<?php
/**
 * Configuración de conexión a la base de datos
 * Sistema de Gestión de Eventos Universitarios
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_eventos_universitarios');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna una instancia PDO de conexión a la base de datos.
 * Usa patrón singleton para reutilizar la misma conexión.
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Sincronizar zona horaria de MySQL con PHP (ej: -06:00)
            $tzOffset = date('P');
            $pdo->exec("SET time_zone = '{$tzOffset}'");

            // Actualizar estados automáticos de eventos
            if (function_exists('actualizarEstadosEventos')) {
                actualizarEstadosEventos($pdo);
            }
        } catch (PDOException $e) {
            // No exponer detalles de conexión al usuario final
            error_log('Error de conexión BD: ' . $e->getMessage());
            die('Error interno del servidor. Intente más tarde.');
        }
    }

    return $pdo;
}
