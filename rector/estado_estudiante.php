<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['estudiante_id'], $_POST['nuevo_estado'])) {
    $estudiante_id = intval($_POST['estudiante_id']);
    $nuevo_estado = trim($_POST['nuevo_estado']);

    // Validar ID
    if ($estudiante_id <= 0) {
        echo json_encode(['error' => 'ID de estudiante inválido.']);
        exit;
    }

    // Validar estado permitido
    $estados_permitidos = ['activo', 'inactivo', 'retirado', 'egresado'];
    if (!in_array($nuevo_estado, $estados_permitidos)) {
        echo json_encode(['error' => 'Estado no permitido.']);
        exit;
    }

    try {
        $db->beginTransaction();

        // Actualizar estado del estudiante
        $db->query("UPDATE estudiantes SET estado = :estado WHERE id = :id");
        $db->bind(':estado', $nuevo_estado);
        $db->bind(':id', $estudiante_id);
        $db->execute();

        $db->commit();

        echo json_encode([
            'success' => 'Estado del estudiante actualizado correctamente.',
            'nuevo_estado' => $nuevo_estado
        ]);
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['error' => 'Error al actualizar estado: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Solicitud inválida.']);
}
