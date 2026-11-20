<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(2);

$db  = getDB();
$uid = $_SESSION['user_id'];
$page_title = 'Certificados';

// --- Generar certificados POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar'])) {
    $gen_event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    if (!$gen_event_id) {
        $_SESSION['mensaje'] = 'Evento inválido.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: certificados_org.php');
        exit;
    }

    // Verificar pertenencia y que sea finalizado
    $stmtChk = $db->prepare("SELECT id, nombre, estado FROM eventos WHERE id = ? AND organizador_id = ?");
    $stmtChk->execute([$gen_event_id, $uid]);
    $evChk = $stmtChk->fetch(PDO::FETCH_ASSOC);

    if (!$evChk) {
        $_SESSION['mensaje'] = 'Evento no encontrado o sin permiso.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: certificados_org.php');
        exit;
    }
    if ($evChk['estado'] !== 'finalizado') {
        $_SESSION['mensaje'] = 'Solo se pueden generar certificados para eventos con estado "finalizado".';
        $_SESSION['mensaje_tipo'] = 'warning';
        header('Location: certificados_org.php');
        exit;
    }

    // Obtener asistentes presentes que no tengan certificado
    $stmtAsist = $db->prepare("
        SELECT a.usuario_id,
               ROUND(SUM(a.presente) / COUNT(*) * 100, 2) AS pct_asistencia
        FROM asistencias a
        WHERE a.evento_id = ? AND a.presente = 1
          AND a.usuario_id NOT IN (
              SELECT usuario_id FROM certificados WHERE evento_id = ? AND estado = 'emitido'
          )
        GROUP BY a.usuario_id
    ");
    $stmtAsist->execute([$gen_event_id, $gen_event_id]);
    $asistentes = $stmtAsist->fetchAll(PDO::FETCH_ASSOC);

    if (empty($asistentes)) {
        $_SESSION['mensaje'] = 'No hay asistentes nuevos para certificar (ya están emitidos o no hay presentes).';
        $_SESSION['mensaje_tipo'] = 'info';
        header('Location: certificados_org.php');
        exit;
    }

    $stmtIns = $db->prepare("
        INSERT INTO certificados (usuario_id, evento_id, codigo_certificado, fecha_emision, porcentaje_asistencia, estado, fecha_creacion)
        VALUES (?, ?, ?, NOW(), ?, 'emitido', NOW())
    ");

    $generados = 0;
    $db->beginTransaction();
    try {
        foreach ($asistentes as $asist) {
            $codigo = strtoupper('CERT-' . $gen_event_id . '-' . $asist['usuario_id'] . '-' . substr(md5(uniqid()), 0, 6));
            $stmtIns->execute([$asist['usuario_id'], $gen_event_id, $codigo, $asist['pct_asistencia']]);
            $generados++;
        }
        $db->commit();
        $_SESSION['mensaje'] = "Se generaron $generados certificado(s) exitosamente para el evento «" . htmlspecialchars($evChk['nombre']) . "».";
        $_SESSION['mensaje_tipo'] = 'success';
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['mensaje'] = 'Error al generar certificados: ' . $e->getMessage();
        $_SESSION['mensaje_tipo'] = 'error';
    }
    header('Location: certificados_org.php');
    exit;
}

// --- Eventos finalizados del organizador ---
$stmtEvFin = $db->prepare("
    SELECT e.id, e.nombre, e.fecha_inicio,
           COUNT(DISTINCT a.usuario_id) AS asistentes_presentes,
           COUNT(DISTINCT c.id)         AS certificados_emitidos
    FROM eventos e
    LEFT JOIN asistencias a ON a.evento_id = e.id AND a.presente = 1
    LEFT JOIN certificados c ON c.evento_id = e.id AND c.estado = 'emitido'
    WHERE e.organizador_id = ? AND e.estado = 'finalizado'
    GROUP BY e.id
    ORDER BY e.fecha_inicio DESC
");
$stmtEvFin->execute([$uid]);
$eventosFin = $stmtEvFin->fetchAll(PDO::FETCH_ASSOC);

// --- Lista de certificados emitidos de mis eventos ---
$stmtCerts = $db->prepare("
    SELECT c.id, c.codigo_certificado, c.fecha_emision, c.porcentaje_asistencia, c.estado,
           u.nombre, u.apellido, u.email,
           e.nombre AS evento_nombre, e.id AS evento_id
    FROM certificados c
    JOIN usuarios u ON c.usuario_id = u.id
    JOIN eventos e ON c.evento_id = e.id
    WHERE e.organizador_id = ?
    ORDER BY c.fecha_emision DESC
");
$stmtCerts->execute([$uid]);
$certificados = $stmtCerts->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header_org.php';
?>
<h1 style="font-size:1.5rem;margin-bottom:6px;">Certificados de Mis Eventos</h1>
<p style="color:#666;margin-bottom:24px;">Genera y gestiona los certificados de asistencia para los eventos que organizas.</p>

<!-- Generar certificados -->
<div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);margin-bottom:24px;">
    <h2 style="font-size:1rem;margin-bottom:16px;color:#2c7a3e;"><i class="fas fa-cogs"></i> Generar Certificados</h2>
    <?php if (empty($eventosFin)): ?>
        <p style="color:#999;text-align:center;padding:20px;">No tienes eventos finalizados aún.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.88rem;">
            <thead>
                <tr style="background:#f8f9fa;">
                    <th style="padding:11px;text-align:left;border-bottom:2px solid #dee2e6;">Evento</th>
                    <th style="padding:11px;text-align:left;border-bottom:2px solid #dee2e6;">Fecha</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">Asistentes Presentes</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">Certificados Emitidos</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">Pendientes</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eventosFin as $evf):
                    $pendientes = max(0, $evf['asistentes_presentes'] - $evf['certificados_emitidos']);
                ?>
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:10px;font-weight:600;"><?php echo htmlspecialchars($evf['nombre']); ?></td>
                    <td style="padding:10px;"><?php echo date('d/m/Y', strtotime($evf['fecha_inicio'])); ?></td>
                    <td style="padding:10px;text-align:center;"><strong><?php echo $evf['asistentes_presentes']; ?></strong></td>
                    <td style="padding:10px;text-align:center;">
                        <span style="color:#28a745;font-weight:600;"><?php echo $evf['certificados_emitidos']; ?></span>
                    </td>
                    <td style="padding:10px;text-align:center;">
                        <?php if ($pendientes > 0): ?>
                            <span style="background:#fff3cd;color:#856404;padding:3px 10px;border-radius:20px;font-size:0.78rem;">
                                <?php echo $pendientes; ?> pendientes
                            </span>
                        <?php else: ?>
                            <span style="background:#d4edda;color:#155724;padding:3px 10px;border-radius:20px;font-size:0.78rem;">Completo</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px;text-align:center;">
                        <?php if ($pendientes > 0): ?>
                        <form method="POST" action="certificados_org.php" style="display:inline;">
                            <input type="hidden" name="event_id" value="<?php echo $evf['id']; ?>">
                            <button type="submit" name="generar" value="1"
                                    style="padding:6px 14px;background:#2c7a3e;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:0.82rem;"
                                    onclick="return confirm('¿Generar <?php echo $pendientes; ?> certificado(s) para este evento?')">
                                <i class="fas fa-certificate"></i> Generar (<?php echo $pendientes; ?>)
                            </button>
                        </form>
                        <?php else: ?>
                            <span style="color:#ccc;font-size:0.82rem;">Sin pendientes</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Lista de certificados emitidos -->
<div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h2 style="font-size:1rem;color:#2c7a3e;"><i class="fas fa-list"></i> Certificados Emitidos</h2>
        <span style="font-size:0.82rem;color:#666;">Total: <strong><?php echo count($certificados); ?></strong></span>
    </div>
    <?php if (empty($certificados)): ?>
        <p style="text-align:center;color:#999;padding:30px;">No hay certificados emitidos aún.</p>
    <?php else: ?>
    <div style="margin-bottom:12px;">
        <input type="text" id="buscarCert" placeholder="🔍 Buscar por nombre, evento o código..."
               oninput="filtrarCerts()"
               style="width:100%;max-width:400px;padding:8px 14px;border:1px solid #ddd;border-radius:8px;font-size:0.87rem;">
    </div>
    <div style="overflow-x:auto;">
        <table id="tablaCerts" style="width:100%;border-collapse:collapse;font-size:0.86rem;">
            <thead>
                <tr style="background:#f8f9fa;">
                    <th style="padding:11px;text-align:left;border-bottom:2px solid #dee2e6;">Código</th>
                    <th style="padding:11px;text-align:left;border-bottom:2px solid #dee2e6;">Participante</th>
                    <th style="padding:11px;text-align:left;border-bottom:2px solid #dee2e6;">Email</th>
                    <th style="padding:11px;text-align:left;border-bottom:2px solid #dee2e6;">Evento</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">Fecha Emisión</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">% Asistencia</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">Estado</th>
                    <th style="padding:11px;text-align:center;border-bottom:2px solid #dee2e6;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($certificados as $cert): ?>
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:10px;font-family:monospace;font-size:0.8rem;color:#555;"><?php echo htmlspecialchars($cert['codigo_certificado']); ?></td>
                    <td style="padding:10px;font-weight:600;"><?php echo htmlspecialchars($cert['apellido'] . ', ' . $cert['nombre']); ?></td>
                    <td style="padding:10px;font-size:0.82rem;"><?php echo htmlspecialchars($cert['email']); ?></td>
                    <td style="padding:10px;max-width:160px;"><?php echo htmlspecialchars($cert['evento_nombre']); ?></td>
                    <td style="padding:10px;text-align:center;white-space:nowrap;"><?php echo date('d/m/Y', strtotime($cert['fecha_emision'])); ?></td>
                    <td style="padding:10px;text-align:center;">
                        <span style="font-weight:600;color:<?php echo $cert['porcentaje_asistencia'] >= 70 ? '#28a745' : '#ffc107'; ?>;">
                            <?php echo number_format($cert['porcentaje_asistencia'], 1); ?>%
                        </span>
                    </td>
                    <td style="padding:10px;text-align:center;">
                        <span style="padding:3px 10px;border-radius:20px;font-size:0.78rem;
                            background:<?php echo $cert['estado'] === 'emitido' ? '#d4edda' : '#f8d7da'; ?>;
                            color:<?php echo $cert['estado'] === 'emitido' ? '#155724' : '#721c24'; ?>;">
                            <?php echo ucfirst($cert['estado']); ?>
                        </span>
                    </td>
                    <td style="padding:10px;text-align:center;white-space:nowrap;">
                        <a href="../participante/mi_certificado.php?cert_id=<?php echo $cert['id']; ?>" target="_blank"
                           style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none;font-size:0.8rem;font-weight:600;"
                           title="Visualizar e Imprimir Certificado">
                            <i class="fas fa-print"></i> Ver Certificado
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
function filtrarCerts() {
    var val = document.getElementById('buscarCert').value.toLowerCase();
    document.querySelectorAll('#tablaCerts tbody tr').forEach(function(row) {
        row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
}
</script>

</main></div>
<footer style="text-align:center;padding:16px;font-size:0.8rem;color:#999;background:#fff;border-top:1px solid #eee;margin-left:240px;">
    <?php echo APP_NAME; ?> &copy; <?php echo date('Y'); ?> — Módulo Organizador
</footer>
</body>
</html>
