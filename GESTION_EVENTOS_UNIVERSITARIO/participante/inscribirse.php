<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(3);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/participante/eventos_disponibles.php');
    exit;
}

$user_id  = $_SESSION['user_id'];
$event_id = (int)($_POST['event_id'] ?? 0);

if ($event_id <= 0) {
    $_SESSION['mensaje']  = 'ID de evento inválido.';
    $_SESSION['msg_type'] = 'error';
    header('Location: ' . BASE_URL . '/participante/eventos_disponibles.php');
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    // 1. Verificar que el evento exista, esté activo, tenga cupos y no haya pasado su fecha
    $stmt = $db->prepare("SELECT id, nombre, cupo_disponible, estado, fecha_inicio, fecha_fin, hora_inicio, hora_fin FROM eventos WHERE id = ? FOR UPDATE");
    $stmt->execute([$event_id]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$evento) {
        throw new Exception('El evento no existe.');
    }

    $ahora = date('Y-m-d H:i:s');
    $eventoFin = $evento['fecha_fin'] . ' ' . $evento['hora_fin'];
    $eventoInicio = $evento['fecha_inicio'] . ' ' . $evento['hora_inicio'];

    if ($evento['estado'] === 'finalizado' || $evento['estado'] === 'cancelado' || $eventoFin < $ahora) {
        throw new Exception('No es posible inscribirse: este evento ya ha finalizado.');
    }
    if ($eventoInicio <= $ahora) {
        throw new Exception('No es posible inscribirse: la fecha y hora de inicio de este evento ya ha pasado.');
    }
    if (!in_array($evento['estado'], ['programado', 'en_curso'])) {
        throw new Exception('El evento no está disponible para inscripciones.');
    }
    if ((int)$evento['cupo_disponible'] <= 0) {
        throw new Exception('No hay cupos disponibles en este evento.');
    }

    // 2. Verificar que no esté ya inscrito
    $stmt = $db->prepare("SELECT id FROM inscripciones WHERE usuario_id = ? AND evento_id = ? AND estado = 'activa'");
    $stmt->execute([$user_id, $event_id]);
    if ($stmt->fetchColumn()) {
        throw new Exception('Ya estás inscrito en este evento.');
    }

    // 3. Insertar inscripción
    $stmt = $db->prepare("
        INSERT INTO inscripciones (usuario_id, evento_id, fecha_inscripcion, estado)
        VALUES (?, ?, NOW(), 'activa')
    ");
    $stmt->execute([$user_id, $event_id]);

    // 4. Decrementar cupo_disponible
    $stmt = $db->prepare("UPDATE eventos SET cupo_disponible = cupo_disponible - 1 WHERE id = ? AND cupo_disponible > 0");
    $stmt->execute([$event_id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('No hay cupos disponibles (conflicto de concurrencia).');
    }

    $db->commit();

    $_SESSION['mensaje']  = '¡Te has inscrito exitosamente en "' . htmlspecialchars($evento['nombre']) . '"!';
    $_SESSION['msg_type'] = 'success';
    header('Location: ' . BASE_URL . '/participante/mis_inscripciones.php');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['mensaje']  = $e->getMessage();
    $_SESSION['msg_type'] = 'error';
    header('Location: ' . BASE_URL . '/participante/eventos_disponibles.php');
}
exit;
