<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(3);

$user_id = $_SESSION['user_id'];
$db = getDB();

// Búsqueda/filtro
$search  = trim($_GET['buscar'] ?? '');
$modalidad = $_GET['modalidad'] ?? '';

$where = "e.estado IN ('programado','en_curso') AND CONCAT(e.fecha_fin, ' ', e.hora_fin) >= NOW() AND e.cupo_disponible >= 0";
$params = [];

if ($search !== '') {
    $where .= " AND (e.nombre LIKE ? OR e.lugar LIKE ? OR e.descripcion LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($modalidad !== '') {
    $where .= " AND e.modalidad = ?";
    $params[] = $modalidad;
}

// Obtener eventos con flag de inscripción del usuario
$stmt = $db->prepare("
    SELECT e.*,
           ec.nombre AS categoria_nombre,
           u.nombre AS org_nombre, u.apellido AS org_apellido,
           (SELECT COUNT(*) FROM inscripciones r2
            WHERE r2.evento_id = e.id AND r2.usuario_id = ? AND r2.estado = 'activa') AS ya_inscrito
    FROM eventos e
    LEFT JOIN categorias_eventos ec ON ec.id = e.categoria_id
    LEFT JOIN usuarios u ON u.id = e.organizador_id
    WHERE $where
    ORDER BY e.fecha_inicio ASC
");
$stmt->execute(array_merge([$user_id], $params));
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Eventos Disponibles';
include __DIR__ . '/../includes/header_part.php';
include __DIR__ . '/../includes/sidebar_part.php';
?>
<main class="main-content">
    <div style="margin-bottom:1.5rem;">
        <h1 style="font-size:1.6rem;color:#1e293b;font-weight:700;"><i class="fas fa-calendar-alt" style="color:#2563eb;"></i> Eventos Disponibles</h1>
        <p style="color:#64748b;margin-top:.3rem;">Explora y regístrate en los eventos universitarios disponibles.</p>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div style="background:<?= $_SESSION['msg_type']==='success'?'#dcfce7':'#fee2e2' ?>;border:1px solid <?= $_SESSION['msg_type']==='success'?'#86efac':'#fca5a5' ?>;color:<?= $_SESSION['msg_type']==='success'?'#166534':'#991b1b' ?>;padding:.85rem 1.2rem;border-radius:10px;margin-bottom:1.2rem;display:flex;align-items:center;gap:.7rem;">
            <i class="fas fa-<?= $_SESSION['msg_type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($_SESSION['mensaje']) ?>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['msg_type']); ?>
    <?php endif; ?>

    <!-- Filtros -->
    <form method="GET" style="background:white;border-radius:12px;padding:1.2rem;box-shadow:0 1px 6px rgba(0,0,0,0.07);margin-bottom:1.5rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:200px;">
            <label style="font-size:.82rem;color:#64748b;font-weight:600;display:block;margin-bottom:.3rem;">Buscar</label>
            <input type="text" name="buscar" value="<?= htmlspecialchars($search) ?>" placeholder="Nombre, lugar o descripción..."
                   style="width:100%;padding:.5rem .9rem;border:1px solid #e2e8f0;border-radius:8px;font-size:.9rem;outline:none;">
        </div>
        <div style="min-width:160px;">
            <label style="font-size:.82rem;color:#64748b;font-weight:600;display:block;margin-bottom:.3rem;">Modalidad</label>
            <select name="modalidad" style="width:100%;padding:.5rem .9rem;border:1px solid #e2e8f0;border-radius:8px;font-size:.9rem;background:white;outline:none;">
                <option value="">Todas</option>
                <option value="presencial" <?= $modalidad==='presencial'?'selected':'' ?>>Presencial</option>
                <option value="virtual"    <?= $modalidad==='virtual'?'selected':'' ?>>Virtual</option>
                <option value="hibrido"    <?= $modalidad==='hibrido'?'selected':'' ?>>Híbrido</option>
            </select>
        </div>
        <div style="display:flex;gap:.5rem;">
            <button type="submit" style="background:#2563eb;color:white;border:none;padding:.55rem 1.2rem;border-radius:8px;cursor:pointer;font-size:.9rem;font-weight:600;"><i class="fas fa-search"></i> Buscar</button>
            <?php if ($search || $modalidad): ?>
                <a href="<?= BASE_URL ?>/participante/eventos_disponibles.php" style="background:#f1f5f9;color:#64748b;padding:.55rem 1rem;border-radius:8px;text-decoration:none;font-size:.9rem;display:flex;align-items:center;gap:.4rem;"><i class="fas fa-times"></i> Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Resultados: conteo -->
    <p style="color:#64748b;font-size:.88rem;margin-bottom:1rem;"><?= count($eventos) ?> evento(s) encontrado(s)</p>

    <?php if (empty($eventos)): ?>
        <div style="background:white;border-radius:14px;padding:3rem;text-align:center;color:#94a3b8;box-shadow:0 1px 6px rgba(0,0,0,0.07);">
            <i class="fas fa-calendar-xmark" style="font-size:3rem;margin-bottom:1rem;display:block;"></i>
            <p style="font-size:1rem;">No hay eventos disponibles con los filtros seleccionados.</p>
        </div>
    <?php else: ?>
        <!-- Grid de tarjetas -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.5rem;">
            <?php foreach ($eventos as $ev):
                $cupo     = (int)$ev['cupo_disponible'];
                $inscrito = (bool)$ev['ya_inscrito'];
                $sinCupo  = $cupo <= 0;
                $yaInicio = ($ev['fecha_inicio'] . ' ' . $ev['hora_inicio']) <= date('Y-m-d H:i:s');
                $pct      = $ev['cupo_maximo'] > 0 ? round((($ev['cupo_maximo'] - $cupo) / $ev['cupo_maximo']) * 100) : 0;
                $modColor = ['presencial'=>'#16a34a','virtual'=>'#2563eb','hibrido'=>'#d97706'][$ev['modalidad']] ?? '#64748b';
                $modBg    = ['presencial'=>'#dcfce7','virtual'=>'#dbeafe','hibrido'=>'#fef3c7'][$ev['modalidad']] ?? '#f1f5f9';
                $estColor = ['programado'=>'#1d4ed8','en_curso'=>'#15803d'][$ev['estado']] ?? '#64748b';
                $estBg    = ['programado'=>'#dbeafe','en_curso'=>'#dcfce7'][$ev['estado']] ?? '#f1f5f9';
                $estLabel = ['programado'=>'Programado','en_curso'=>'En Curso'][$ev['estado']] ?? $ev['estado'];
            ?>
            <div style="background:white;border-radius:14px;overflow:hidden;box-shadow:0 1px 8px rgba(0,0,0,0.08);border:1px solid #e2e8f0;display:flex;flex-direction:column;">
                <!-- Header coloreado -->
                <div style="background:linear-gradient(135deg,#2563eb,#1e40af);padding:1.2rem;color:white;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem;">
                        <span style="background:<?= $modBg ?>;color:<?= $modColor ?>;padding:.2rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;"><?= $ev['modalidad'] ?></span>
                        <span style="background:<?= $estBg ?>;color:<?= $estColor ?>;padding:.2rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700;"><?= $estLabel ?></span>
                    </div>
                    <h3 style="font-size:1rem;font-weight:700;line-height:1.3;"><?= htmlspecialchars($ev['nombre']) ?></h3>
                    <?php if ($ev['categoria_nombre']): ?>
                        <small style="opacity:.8;"><?= htmlspecialchars($ev['categoria_nombre']) ?></small>
                    <?php endif; ?>
                </div>
                <!-- Body -->
                <div style="padding:1.2rem;flex:1;">
                    <?php if ($ev['descripcion']): ?>
                        <p style="color:#64748b;font-size:.85rem;line-height:1.5;margin-bottom:1rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                            <?= htmlspecialchars($ev['descripcion']) ?>
                        </p>
                    <?php endif; ?>
                    <div style="display:flex;flex-direction:column;gap:.5rem;">
                        <div style="display:flex;align-items:center;gap:.6rem;font-size:.85rem;color:#475569;">
                            <i class="fas fa-calendar" style="color:#2563eb;width:16px;"></i>
                            <?= date('d M Y', strtotime($ev['fecha_inicio'])) ?>
                            <?= $ev['hora_inicio'] ? ' — ' . substr($ev['hora_inicio'],0,5) : '' ?>
                        </div>
                        <?php if ($ev['lugar']): ?>
                        <div style="display:flex;align-items:center;gap:.6rem;font-size:.85rem;color:#475569;">
                            <i class="fas fa-map-marker-alt" style="color:#2563eb;width:16px;"></i>
                            <?= htmlspecialchars($ev['lugar']) ?>
                        </div>
                        <?php endif; ?>
                        <div style="display:flex;align-items:center;gap:.6rem;font-size:.85rem;color:#475569;">
                            <i class="fas fa-users" style="color:#2563eb;width:16px;"></i>
                            <?= $cupo ?> cupos disponibles de <?= $ev['cupo_maximo'] ?>
                        </div>
                    </div>
                    <!-- Barra de cupos -->
                    <div style="margin-top:.9rem;">
                        <div style="background:#e2e8f0;border-radius:10px;height:6px;overflow:hidden;">
                            <div style="background:<?= $pct >= 90 ? '#ef4444' : ($pct >= 60 ? '#f59e0b' : '#22c55e') ?>;height:100%;width:<?= $pct ?>%;transition:width .4s;"></div>
                        </div>
                        <small style="color:#94a3b8;font-size:.73rem;"><?= $pct ?>% de cupos ocupados</small>
                    </div>
                </div>
                <!-- Acción -->
                <div style="padding:1rem 1.2rem;border-top:1px solid #f1f5f9;">
                    <?php if ($inscrito): ?>
                        <div style="background:#dcfce7;color:#15803d;padding:.6rem 1rem;border-radius:8px;text-align:center;font-weight:600;font-size:.88rem;">
                            <i class="fas fa-check-circle"></i> Ya estás inscrito
                        </div>
                    <?php elseif ($yaInicio): ?>
                        <div style="background:#fef3c7;color:#92400e;padding:.6rem 1rem;border-radius:8px;text-align:center;font-weight:600;font-size:.88rem;">
                            <i class="fas fa-lock"></i> Inscripciones cerradas
                        </div>
                    <?php elseif ($sinCupo): ?>
                        <div style="background:#fee2e2;color:#991b1b;padding:.6rem 1rem;border-radius:8px;text-align:center;font-weight:600;font-size:.88rem;">
                            <i class="fas fa-ban"></i> Sin cupos disponibles
                        </div>
                    <?php else: ?>
                        <form method="POST" action="<?= BASE_URL ?>/participante/inscribirse.php">
                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                            <button type="submit" style="width:100%;background:#2563eb;color:white;border:none;padding:.65rem;border-radius:8px;cursor:pointer;font-weight:600;font-size:.9rem;transition:background .2s;" onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                                <i class="fas fa-plus-circle"></i> Inscribirme
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
