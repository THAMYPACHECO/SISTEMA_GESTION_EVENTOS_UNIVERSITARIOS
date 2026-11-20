<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav id="sidebar" style="
    width: var(--sidebar-width, 260px);
    background: #1e293b;
    color: #cbd5e1;
    position: fixed;
    top: 64px;
    left: 0;
    bottom: 0;
    overflow-y: auto;
    z-index: 900;
    transition: transform 0.3s ease;
    padding: 1.5rem 0;
">
    <style>
        #sidebar .nav-section { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; padding: 0.8rem 1.4rem 0.3rem; }
        #sidebar a { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 1.4rem; color: #94a3b8; text-decoration: none; font-size: 0.93rem; border-left: 3px solid transparent; transition: all 0.2s; }
        #sidebar a:hover { background: #263548; color: #e2e8f0; border-left-color: #3b82f6; }
        #sidebar a.active { background: #1e3a5f; color: #60a5fa; border-left-color: #3b82f6; font-weight: 600; }
        #sidebar a i { width: 20px; text-align: center; font-size: 0.95rem; }
        #sidebar .sidebar-user { padding: 1rem 1.4rem 1.5rem; border-bottom: 1px solid #334155; margin-bottom: 0.5rem; }
        #sidebar .sidebar-user .user-name { font-weight: 600; color: #e2e8f0; font-size: 0.95rem; }
        #sidebar .sidebar-user .user-role { font-size: 0.78rem; color: #64748b; }
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
        }
    </style>

    <div class="sidebar-user">
        <div style="display:flex;align-items:center;gap:.7rem;">
            <div style="width:42px;height:42px;background:#2563eb;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:white;font-size:1rem;">
                <?= strtoupper(substr($_SESSION['nombre'] ?? 'P', 0, 1)) ?>
            </div>
            <div>
                <div class="user-name"><?= htmlspecialchars(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '')) ?></div>
                <div class="user-role"><i class="fas fa-circle" style="color:#22c55e;font-size:0.6rem;"></i> Participante</div>
            </div>
        </div>
    </div>

    <div class="nav-section">Principal</div>
    <a href="<?= BASE_URL ?>/participante/dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>

    <div class="nav-section">Eventos</div>
    <a href="<?= BASE_URL ?>/participante/eventos_disponibles.php" class="<?= $currentPage === 'eventos_disponibles.php' ? 'active' : '' ?>">
        <i class="fas fa-calendar-alt"></i> Eventos Disponibles
    </a>
    <a href="<?= BASE_URL ?>/participante/mis_inscripciones.php" class="<?= $currentPage === 'mis_inscripciones.php' ? 'active' : '' ?>">
        <i class="fas fa-list-check"></i> Mis Inscripciones
    </a>

    <div class="nav-section">Personal</div>
    <a href="<?= BASE_URL ?>/participante/mi_perfil.php" class="<?= $currentPage === 'mi_perfil.php' ? 'active' : '' ?>">
        <i class="fas fa-user-circle"></i> Mi Perfil
    </a>
    <a href="<?= BASE_URL ?>/auth/logout.php">
        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
    </a>
</nav>
