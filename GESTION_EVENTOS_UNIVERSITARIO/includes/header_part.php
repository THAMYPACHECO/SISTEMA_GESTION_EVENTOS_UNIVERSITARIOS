<?php
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(3);
$currentPage = basename($_SERVER['PHP_SELF']);
$userName = htmlspecialchars($_SESSION['nombre'] ?? 'Participante');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Portal Participante' ?> | Gestión de Eventos Universitarios</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --part-primary: #2563eb;
            --part-secondary: #1e40af;
            --part-accent: #3b82f6;
            --sidebar-width: 260px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; display: flex; flex-direction: column; min-height: 100vh; }
        .topbar {
            background: linear-gradient(135deg, var(--part-primary), var(--part-secondary));
            color: white;
            padding: 0 1.5rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .topbar-brand { display: flex; align-items: center; gap: 0.75rem; font-size: 1.1rem; font-weight: 700; }
        .topbar-brand i { font-size: 1.4rem; color: #93c5fd; }
        .topbar-toggle { background: none; border: none; color: white; font-size: 1.3rem; cursor: pointer; display: none; padding: 0.5rem; }
        .topbar-right { display: flex; align-items: center; gap: 1.2rem; }
        .topbar-user { display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem; }
        .topbar-user .avatar { width: 36px; height: 36px; background: #93c5fd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--part-secondary); font-size: 0.9rem; }
        .topbar-nav a { color: rgba(255,255,255,0.85); text-decoration: none; font-size: 0.9rem; padding: 0.3rem 0.6rem; border-radius: 6px; transition: background 0.2s; }
        .topbar-nav a:hover { background: rgba(255,255,255,0.15); color: white; }
        .layout { display: flex; padding-top: 64px; }
        .main-content { margin-left: var(--sidebar-width); padding: 2rem; flex: 1; }
        @media (max-width: 768px) {
            .topbar-toggle { display: block; }
            .main-content { margin-left: 0; padding: 1rem; }
            .topbar-nav { display: none; }
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="topbar-brand">
        <button class="topbar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <i class="fas fa-graduation-cap"></i>
        <span>Gestión Eventos — <small style="font-weight:400;opacity:.85">Participante</small></span>
    </div>
    <div class="topbar-right">
        <nav class="topbar-nav">
            <a href="<?= BASE_URL ?>/participante/dashboard.php"><i class="fas fa-home"></i> Inicio</a>
            <a href="<?= BASE_URL ?>/participante/eventos_disponibles.php"><i class="fas fa-calendar-alt"></i> Eventos</a>
            <a href="<?= BASE_URL ?>/participante/mis_inscripciones.php"><i class="fas fa-list-check"></i> Mis Inscripciones</a>
        </nav>
        <div class="topbar-user">
            <div class="avatar"><?= strtoupper(substr($_SESSION['nombre'] ?? 'P', 0, 1)) ?></div>
            <span><?= $userName ?></span>
        </div>
        <a href="<?= BASE_URL ?>/auth/logout.php" title="Cerrar sesión" style="color:rgba(255,255,255,0.8);"><i class="fas fa-sign-out-alt fa-lg"></i></a>
    </div>
</header>
<div class="layout">
<script>
function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    if (sb) sb.classList.toggle('open');
}
</script>
