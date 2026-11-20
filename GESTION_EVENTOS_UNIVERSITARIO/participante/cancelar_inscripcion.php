<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/check_session.php';
requireLogin(3);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/participante/mis_inscripciones.php');
    exit;
}

$user_id         = $_SESSION['user_id'];
$registration_id = (int)($_POST['registration_id'] ?? 0);

if ($registration_id <= 0) {
    $_SESSION['mensaje']  = 'ID de inscripción inválido.';
    $_SESSION['msg_type'] = 'error';
    header('Location: ' . BASE_URL . '/participante/mis_inscripciones.php');
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    // Verificar que la inscripción exista, pertenezca al usuario y sea activa
    $stmt = $db->prepare("
        SELECT r.id, r.evento_id, r.estado, e.estado AS event_estado, e.fecha_inicio
        FROM inscripciones r
        JOIN eventos e ON e.id = r.evento_id
        WHERE r.id = ? AND r.usuario_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$registration_id, $user_id]);
    $reg = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reg) {
        throw new Exception('Inscripción no encontrada.');
    }
    if ($reg['estado'] !== 'activa') {
        throw new Exception('Esta inscripción ya no está activa.');
    }
    if ($reg['event_estado'] !== 'programado') {
        throw new Exception('Solo puedes cancelar inscripciones a eventos programados que no hayan iniciado.');
    }
    if (strtotime($reg['fecha_inicio']) <= time()) {
        throw new Exception('No puedes cancelar una inscripción a un evento que ya ha comenzado.');
    }

    // Cancelar inscripción
    $stmt = $db->prepare("UPDATE inscripciones SET estado='cancelada' WHERE id = ?");
    $stmt->execute([$registration_id]);

    // Restaurar cupo
    $stmt = $db->prepare("UPDATE eventos SET cupo_disponible = cupo_disponible + 1 WHERE id = ?");
    $stmt->execute([$reg['evento_id']]);

    $db->commit();

    $_SESSION['mensaje']  = 'Inscripción cancelada correctamente. El cupo ha sido liberado.';
    $_SESSION['msg_type'] = 'success';

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['mensaje']  = $e->getMessage();
    $_SESSION['msg_type'] = 'error';
}

header('Location: ' . BASE_URL . '/participante/mis_inscripciones.php');
exit;
