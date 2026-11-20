<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | ' : ''; ?>Organizador - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; color: #333; display: flex; flex-direction: column; min-height: 100vh; }
        .topbar { background: linear-gradient(135deg, #2c7a3e, #1a5c2e); color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; height: 60px; position: fixed; top: 0; left: 0; right: 0; z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        .topbar .brand { font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .topbar .brand i { font-size: 1.3rem; }
        .topbar .user-info { display: flex; align-items: center; gap: 16px; font-size: 0.9rem; }
        .topbar .user-info .role-badge { background: rgba(255,255,255,0.2); padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; }
        .topbar .user-info a { color: #fff; text-decoration: none; opacity: 0.85; transition: opacity 0.2s; }
        .topbar .user-info a:hover { opacity: 1; }
        .layout { display: flex; margin-top: 60px; min-height: calc(100vh - 60px); }
        .main-content { flex: 1; margin-left: 240px; padding: 24px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 16px; } }
    </style>
</head>
<body>
<nav class="topbar">
    <div class="brand">
        <i class="fas fa-calendar-check"></i>
        <span><?php echo APP_NAME; ?> <small style="font-weight:400;opacity:0.8;">— Organizador</small></span>
    </div>
    <div class="user-info">
        <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Organizador'); ?></span>
        <span class="role-badge"><i class="fas fa-chalkboard-teacher"></i> Organizador</span>
        <a href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
    </div>
</nav>
<div class="layout">
<?php require_once __DIR__ . '/sidebar_org.php'; ?>
<main class="main-content">
<?php
// Mostrar mensajes flash
if (!empty($_SESSION['mensaje'])) {
    $tipo = $_SESSION['mensaje_tipo'] ?? 'info';
    $colores = ['success' => '#d4edda', 'error' => '#f8d7da', 'info' => '#d1ecf1', 'warning' => '#fff3cd'];
    $bordes  = ['success' => '#28a745', 'error'   => '#dc3545', 'info' => '#17a2b8', 'warning' => '#ffc107'];
    $bg  = $colores[$tipo] ?? $colores['info'];
    $brd = $bordes[$tipo]  ?? $bordes['info'];
    echo '<div style="background:' . $bg . ';border-left:4px solid ' . $brd . ';padding:12px 16px;border-radius:6px;margin-bottom:20px;">';
    echo htmlspecialchars($_SESSION['mensaje']);
    echo '</div>';
    unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
}
?>
