<?php
/**
 * Endpoint AJAX para eliminar imágenes de pacientes
 * Clínica Dental Premium Uchuya
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';
verificarSesion();
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Validar imagen_id
$imagen_id = isset($_POST['imagen_id']) ? intval($_POST['imagen_id']) : 0;
if ($imagen_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de imagen inválido']);
    exit;
}

try {
    $resultado = eliminarImagenPaciente($pdo, $imagen_id);
    if ($resultado) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Imagen no encontrada']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al eliminar la imagen: ' . $e->getMessage()]);
}
