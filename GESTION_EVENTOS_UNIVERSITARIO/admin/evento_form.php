<?php
// admin/evento_form.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(1);

$db = getDB();
$id     = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $id !== null;
$pageTitle = $isEdit ? 'Editar Evento' : 'Nuevo Evento';

$organizadores = $db->query("SELECT id, nombre, apellido FROM usuarios WHERE rol_id = 2 AND activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$categorias    = $db->query("SELECT id, nombre FROM categorias_eventos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

$evento = [
    'nombre'=>'','descripcion'=>'','categoria_id'=>'',
    'fecha_inicio'=>'','fecha_fin'=>'','hora_inicio'=>'','hora_fin'=>'',
    'lugar'=>'','modalidad'=>'presencial',
    'cupo_maximo'=>50,'organizador_id'=>'','estado'=>'programado','imagen'=>''
];

if ($isEdit) {
    $stmtE = $db->prepare("SELECT * FROM eventos WHERE id = ?");
    $stmtE->execute([$id]);
    $dbEvento = $stmtE->fetch(PDO::FETCH_ASSOC);
    if (!$dbEvento) { header('Location: eventos.php'); exit; }
    $evento = $dbEvento;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre        = trim($_POST['nombre'] ?? '');
    $descripcion   = trim($_POST['descripcion'] ?? '');
    $categoria_id  = (int)($_POST['categoria_id'] ?? 0);
    $fecha_inicio  = $_POST['fecha_inicio'] ?? '';
    $fecha_fin     = $_POST['fecha_fin'] ?? '';
    $hora_inicio   = $_POST['hora_inicio'] ?? '';
    $hora_fin      = $_POST['hora_fin'] ?? '';
    $lugar         = trim($_POST['lugar'] ?? '');
    $modalidad     = $_POST['modalidad'] ?? 'presencial';
    $cupo_maximo   = (int)($_POST['cupo_maximo'] ?? 0);
    $organizador_id= (int)($_POST['organizador_id'] ?? 0);
    $estado        = $_POST['estado'] ?? 'programado';
    $imagen        = trim($_POST['imagen'] ?? '');

    // Validaciones
    if (!$nombre)       $errors[] = 'El nombre del evento es obligatorio.';
    if (!$fecha_inicio) $errors[] = 'La fecha de inicio es obligatoria.';
    if (!$hora_inicio)  $errors[] = 'La hora de inicio es obligatoria.';
    if ($cupo_maximo < 1) $errors[] = 'El cupo máximo debe ser al menos 1.';
    if (!$organizador_id) $errors[] = 'Debe seleccionar un organizador.';
    if (!$categoria_id)   $errors[] = 'Debe seleccionar una categoría.';
    if (!in_array($modalidad, ['presencial','virtual','hibrido'])) $errors[] = 'Modalidad inválida.';
    if (!in_array($estado, ['programado','en_curso','finalizado','cancelado'])) $errors[] = 'Estado inválido.';
    if ($fecha_fin && $fecha_fin < $fecha_inicio) $errors[] = 'La fecha fin no puede ser anterior a la de inicio.';

    if (empty($errors)) {
        $cupo_disp = $isEdit ? (int)$evento['cupo_disponible'] : $cupo_maximo;
        // Recalcular cupo_disponible en edición si cupo cambió
        if ($isEdit) {
            $inscritos = $db->prepare("SELECT COUNT(*) FROM inscripciones WHERE evento_id = ? AND estado = 'activa'");
            $inscritos->execute([$id]);
            $numInscritos = (int)$inscritos->fetchColumn();
            $cupo_disp = max(0, $cupo_maximo - $numInscritos);
        }

        if ($isEdit) {
            $stmt = $db->prepare("
                UPDATE eventos SET nombre=?,descripcion=?,categoria_id=?,fecha_inicio=?,fecha_fin=?,
                hora_inicio=?,hora_fin=?,lugar=?,modalidad=?,cupo_maximo=?,cupo_disponible=?,
                organizador_id=?,estado=?,imagen=?,fecha_actualizacion=NOW()
                WHERE id=?
            ");
            $stmt->execute([$nombre,$descripcion,$categoria_id,$fecha_inicio,$fecha_fin ?: null,
                $hora_inicio,$hora_fin ?: null,$lugar,$modalidad,$cupo_maximo,$cupo_disp,
                $organizador_id,$estado,$imagen,$id]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO eventos (nombre,descripcion,categoria_id,fecha_inicio,fecha_fin,
                hora_inicio,hora_fin,lugar,modalidad,cupo_maximo,cupo_disponible,
                organizador_id,estado,imagen,fecha_creacion,fecha_actualizacion)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())
            ");
            $stmt->execute([$nombre,$descripcion,$categoria_id,$fecha_inicio,$fecha_fin ?: null,
                $hora_inicio,$hora_fin ?: null,$lugar,$modalidad,$cupo_maximo,$cupo_maximo,
                $organizador_id,$estado,$imagen]);
        }
        header('Location: eventos.php?msg=' . ($isEdit ? 'updated' : 'created'));
        exit;
    }
    $evento = compact('nombre','descripcion','categoria_id','fecha_inicio','fecha_fin',
        'hora_inicio','hora_fin','lugar','modalidad','cupo_maximo','organizador_id','estado','imagen');
}

include __DIR__ . '/../includes/header_admin.php';
?>
<div class="page-header">
    <h1><i class="fas fa-<?php echo $isEdit ? 'edit' : 'plus-circle'; ?>"></i> <?php echo $pageTitle; ?></h1>
    <div class="breadcrumb">
        <a href="dashboard.php">Dashboard</a> /
        <a href="eventos.php">Eventos</a> /
        <strong><?php echo $pageTitle; ?></strong>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle"></i>
    <ul style="margin:8px 0 0 16px;">
        <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card" style="max-width:860px;">
    <div class="card-header">
        <span class="card-title"><?php echo $pageTitle; ?></span>
    </div>
    <form method="POST">
        <div class="form-group">
            <label>Nombre del Evento *</label>
            <input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($evento['nombre']); ?>">
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <textarea name="descripcion" class="form-control" rows="4"><?php echo htmlspecialchars($evento['descripcion'] ?? ''); ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Categoría *</label>
                <select name="categoria_id" class="form-control" required>
                    <option value="">Seleccionar categoría</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo (string)$evento['categoria_id'] === (string)$cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Organizador *</label>
                <select name="organizador_id" class="form-control" required>
                    <option value="">Seleccionar organizador</option>
                    <?php foreach ($organizadores as $org): ?>
                        <option value="<?php echo $org['id']; ?>" <?php echo (string)$evento['organizador_id'] === (string)$org['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($org['nombre'] . ' ' . $org['apellido']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Fecha Inicio *</label>
                <input type="date" name="fecha_inicio" class="form-control" required value="<?php echo htmlspecialchars($evento['fecha_inicio']); ?>">
            </div>
            <div class="form-group">
                <label>Fecha Fin</label>
                <input type="date" name="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($evento['fecha_fin'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Hora Inicio *</label>
                <input type="time" name="hora_inicio" class="form-control" required value="<?php echo htmlspecialchars($evento['hora_inicio'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Hora Fin</label>
                <input type="time" name="hora_fin" class="form-control" value="<?php echo htmlspecialchars($evento['hora_fin'] ?? ''); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Lugar / URL</label>
                <input type="text" name="lugar" class="form-control" value="<?php echo htmlspecialchars($evento['lugar'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Modalidad *</label>
                <select name="modalidad" class="form-control" required>
                    <?php foreach (['presencial'=>'Presencial','virtual'=>'Virtual','hibrido'=>'Híbrido'] as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo $evento['modalidad'] === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Cupo Máximo *</label>
                <input type="number" name="cupo_maximo" class="form-control" min="1" required value="<?php echo htmlspecialchars($evento['cupo_maximo']); ?>">
            </div>
            <div class="form-group">
                <label>Estado *</label>
                <select name="estado" class="form-control" required>
                    <?php foreach (['programado','en_curso','finalizado','cancelado'] as $est): ?>
                        <option value="<?php echo $est; ?>" <?php echo $evento['estado'] === $est ? 'selected' : ''; ?>><?php echo ucfirst($est); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>URL Imagen (opcional)</label>
            <input type="text" name="imagen" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($evento['imagen'] ?? ''); ?>">
        </div>
        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Evento</button>
            <a href="eventos.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancelar</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
