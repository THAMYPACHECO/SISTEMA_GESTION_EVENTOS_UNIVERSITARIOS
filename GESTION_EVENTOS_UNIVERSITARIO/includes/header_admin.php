<?php
// includes/header_admin.php
if (session_status() === PHP_SESSION_NONE) session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : ''; ?>Gestión de Eventos - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #16a085;
            --light: #ecf0f1;
            --dark: #1a252f;
            --sidebar-width: 260px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; color: #333; }
        .wrapper { display: flex; min-height: 100vh; }
        /* Topbar */
        .topbar {
            position: fixed; top: 0; left: var(--sidebar-width); right: 0; height: 60px;
            background: #fff; box-shadow: 0 2px 6px rgba(0,0,0,.1);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px; z-index: 100;
        }
        .topbar .toggle-btn { background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--primary); }
        .topbar .user-info { display: flex; align-items: center; gap: 10px; }
        .topbar .user-info img { width: 36px; height: 36px; border-radius: 50%; background: var(--secondary); display: flex; align-items: center; justify-content: center; }
        .topbar .user-name { font-weight: 600; font-size: .95rem; color: var(--primary); }
        .topbar .user-role { font-size: .78rem; color: #888; }
        .topbar .logout-btn {
            background: var(--danger); color: #fff; border: none; padding: 6px 14px;
            border-radius: 6px; cursor: pointer; font-size: .85rem; text-decoration: none;
            margin-left: 16px;
        }
        .topbar .logout-btn:hover { background: #c0392b; }
        /* Content */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: 60px;
            padding: 28px;
            flex: 1;
        }
        /* Page header */
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 1.6rem; color: var(--primary); margin-bottom: 4px; }
        .page-header .breadcrumb { font-size: .85rem; color: #888; }
        .page-header .breadcrumb a { color: var(--secondary); text-decoration: none; }
        /* Cards */
        .card {
            background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.07);
            padding: 24px; margin-bottom: 24px;
        }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .card-title { font-size: 1.1rem; font-weight: 600; color: var(--primary); }
        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: .9rem; text-decoration: none; transition: all .2s; }
        .btn-primary { background: var(--secondary); color: #fff; }
        .btn-primary:hover { background: #2980b9; }
        .btn-success { background: var(--success); color: #fff; }
        .btn-success:hover { background: #219a52; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: #c0392b; }
        .btn-warning { background: var(--warning); color: #fff; }
        .btn-warning:hover { background: #d68910; }
        .btn-info { background: var(--info); color: #fff; }
        .btn-info:hover { background: #148a72; }
        .btn-secondary { background: #95a5a6; color: #fff; }
        .btn-secondary:hover { background: #7f8c8d; }
        .btn-sm { padding: 4px 10px; font-size: .82rem; }
        /* Table */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; }
        table th { background: var(--primary); color: #fff; padding: 12px 14px; text-align: left; white-space: nowrap; }
        table td { padding: 10px 14px; border-bottom: 1px solid #eee; vertical-align: middle; }
        table tr:hover td { background: #f8f9fa; }
        /* Badges */
        .badge { padding: 3px 10px; border-radius: 20px; font-size: .78rem; font-weight: 600; }
        .badge-success { background: #d5f5e3; color: #1e8449; }
        .badge-danger { background: #fadbd8; color: #922b21; }
        .badge-warning { background: #fef9e7; color: #9a7d0a; }
        .badge-info { background: #d6eaf8; color: #1a5276; }
        .badge-secondary { background: #eaecee; color: #555; }
        /* Forms */
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: .9rem; color: #555; }
        .form-control {
            width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 6px;
            font-size: .95rem; transition: border .2s; outline: none;
        }
        .form-control:focus { border-color: var(--secondary); box-shadow: 0 0 0 3px rgba(52,152,219,.15); }
        select.form-control { background: #fff; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: .92rem; }
        .alert-success { background: #d5f5e3; color: #1e8449; border-left: 4px solid #27ae60; }
        .alert-danger { background: #fadbd8; color: #922b21; border-left: 4px solid #e74c3c; }
        .alert-warning { background: #fef9e7; color: #9a7d0a; border-left: 4px solid #f39c12; }
        /* Password wrapper */
        .password-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
        .password-wrapper input { width: 100% !important; padding-right: 44px !important; }
        .btn-toggle-password {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            background: none !important; border: none !important; cursor: pointer;
            color: #7f8c8d; font-size: 1.1rem; padding: 6px; display: inline-flex;
            align-items: center; justify-content: center; outline: none; z-index: 10;
        }
        .btn-toggle-password:hover { color: var(--primary); }
        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 999; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: #fff; border-radius: 12px; padding: 32px; max-width: 560px; width: 95%; max-height: 90vh; overflow-y: auto; position: relative; }
        .modal h2 { margin-bottom: 20px; color: var(--primary); }
        .modal .close-modal { position: absolute; top: 16px; right: 16px; background: none; border: none; font-size: 1.4rem; cursor: pointer; color: #888; }
        /* Stat cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 28px; }
        .stat-card { background: #fff; border-radius: 10px; padding: 22px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.07); }
        .stat-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #fff; flex-shrink: 0; }
        .stat-info .value { font-size: 1.8rem; font-weight: 700; color: var(--primary); line-height: 1; }
        .stat-info .label { font-size: .85rem; color: #888; margin-top: 4px; }
        /* Pagination */
        .pagination { display: flex; gap: 6px; justify-content: center; margin-top: 20px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 7px 13px; border-radius: 6px; text-decoration: none; font-size: .9rem; }
        .pagination a { background: #fff; border: 1px solid #ddd; color: var(--primary); }
        .pagination a:hover { background: var(--secondary); color: #fff; border-color: var(--secondary); }
        .pagination .current { background: var(--secondary); color: #fff; border: 1px solid var(--secondary); }
        /* Search bar */
        .search-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .search-bar .form-control { max-width: 300px; }
        /* Responsive */
        @media (max-width: 768px) {
            .topbar { left: 0; }
            .main-content { margin-left: 0; padding: 16px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
<?php include __DIR__ . '/sidebar_admin.php'; ?>
<div style="flex:1; display:flex; flex-direction:column;">
<div class="topbar">
    <button class="toggle-btn" onclick="document.querySelector('.sidebar').classList.toggle('collapsed')">
        <i class="fas fa-bars"></i>
    </button>
    <div class="user-info">
        <div>
            <div class="user-name"><?php echo htmlspecialchars(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '')); ?></div>
            <div class="user-role">Administrador</div>
        </div>
        <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</a>
    </div>
</div>
<div class="main-content">
