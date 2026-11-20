<?php
// includes/sidebar_admin.php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-university"></i>
        <span>Gestión Eventos</span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Panel</div>
        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php"
           class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        <div class="nav-section">Administración</div>
        <a href="<?php echo BASE_URL; ?>/admin/usuarios.php"
           class="nav-link <?php echo in_array($currentPage, ['usuarios.php','usuario_form.php']) ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Usuarios
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/eventos.php"
           class="nav-link <?php echo in_array($currentPage, ['eventos.php','evento_form.php']) ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i> Eventos
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/inscripciones.php"
           class="nav-link <?php echo $currentPage === 'inscripciones.php' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-list"></i> Inscripciones
        </a>

        <div class="nav-section">Control</div>
        <a href="<?php echo BASE_URL; ?>/admin/asistencia.php"
           class="nav-link <?php echo $currentPage === 'asistencia.php' ? 'active' : ''; ?>">
            <i class="fas fa-check-square"></i> Asistencia
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/certificados.php"
           class="nav-link <?php echo $currentPage === 'certificados.php' ? 'active' : ''; ?>">
            <i class="fas fa-certificate"></i> Certificados
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/reportes.php"
           class="nav-link <?php echo $currentPage === 'reportes.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i> Reportes
        </a>

        <div class="nav-section">Cuenta</div>
        <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </a>
    </nav>
</aside>
<style>
.sidebar {
    width: var(--sidebar-width);
    background: var(--primary);
    min-height: 100vh;
    position: fixed;
    top: 0; left: 0; bottom: 0;
    display: flex; flex-direction: column;
    z-index: 200;
    transition: transform .3s;
    overflow-y: auto;
}
.sidebar.collapsed { transform: translateX(-100%); }
.sidebar-header {
    padding: 22px 20px;
    background: var(--dark);
    color: #fff;
    font-size: 1.1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: .5px;
}
.sidebar-header i { font-size: 1.3rem; color: var(--secondary); }
.sidebar-nav { padding: 10px 0; }
.nav-section {
    padding: 10px 20px 4px;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #7f8c8d;
    font-weight: 700;
    margin-top: 6px;
}
.nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 20px;
    color: #bdc3c7;
    text-decoration: none;
    font-size: .93rem;
    transition: all .2s;
    border-left: 3px solid transparent;
}
.nav-link:hover, .nav-link.active {
    background: rgba(52,152,219,.15);
    color: #fff;
    border-left-color: var(--secondary);
}
.nav-link i { width: 20px; text-align: center; font-size: 1rem; }
</style>
