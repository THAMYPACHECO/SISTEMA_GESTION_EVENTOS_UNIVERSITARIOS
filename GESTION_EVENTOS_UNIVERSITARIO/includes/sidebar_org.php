<?php
$current = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
function orgNavLink($href, $icon, $label, $current) {
    $file = basename($href);
    $active = ($current === $file) ? 'style="background:rgba(255,255,255,0.18);font-weight:600;"' : '';
    echo '<a href="' . BASE_URL . '/organizador/' . $href . '" ' . $active . '><i class="fas fa-' . $icon . '"></i> ' . $label . '</a>';
}
?>
<aside style="
    width: 240px;
    background: linear-gradient(180deg, #2c7a3e 0%, #1a5c2e 100%);
    color: #fff;
    position: fixed;
    top: 60px;
    left: 0;
    bottom: 0;
    overflow-y: auto;
    z-index: 900;
    display: flex;
    flex-direction: column;
">
    <div style="padding: 20px 16px 8px;">
        <p style="font-size:0.72rem;text-transform:uppercase;letter-spacing:1.5px;opacity:0.6;margin-bottom:8px;">Menú Organizador</p>
    </div>
    <nav style="display:flex;flex-direction:column;gap:2px;padding:0 10px;">
        <?php
        $links = [
            ['dashboard.php',       'tachometer-alt', 'Dashboard'],
            ['mis_eventos.php',     'calendar-alt',   'Mis Eventos'],
            ['inscritos.php',       'users',          'Ver Inscritos'],
            ['estadisticas.php',    'chart-bar',      'Estadísticas'],
            ['certificados_org.php','certificate',    'Certificados'],
        ];
        foreach ($links as $l) {
            $file = $l[0];
            $active = ($current === $file) ? 'background:rgba(255,255,255,0.18);font-weight:600;' : '';
            echo '<a href="' . BASE_URL . '/organizador/' . $file . '" style="
                display:flex;align-items:center;gap:10px;padding:10px 14px;
                border-radius:8px;color:#fff;text-decoration:none;font-size:0.9rem;
                transition:background 0.2s;' . $active . '"
                onmouseover="this.style.background=\'rgba(255,255,255,0.15)\'"
                onmouseout="this.style.background=\'' . ($active ? 'rgba(255,255,255,0.18)' : 'transparent') . '\'">
                <i class="fas fa-' . $l[1] . '" style="width:18px;text-align:center;"></i>
                ' . $l[2] . '
            </a>';
        }
        ?>
    </nav>
    <div style="margin-top:auto;padding:16px;border-top:1px solid rgba(255,255,255,0.15);font-size:0.78rem;opacity:0.6;text-align:center;">
        <?php echo APP_NAME; ?> v<?php echo APP_VERSION ?? '1.0'; ?>
    </div>
</aside>
