<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
verificarSesion();
require_once '../includes/functions.php';
require_once '../includes/callcenter_functions.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Método no permitido']));
}

// Obtener acción y datos
$accion = isset($_POST['accion']) ? sanitizar($_POST['accion']) : '';
$llamada_id = isset($_POST['llamada_id']) ? intval($_POST['llamada_id']) : 0;

if (empty($accion) || $llamada_id <= 0) {
    die(json_encode(['success' => false, 'message' => 'Datos inválidos']));
}

$response = ['success' => false, 'message' => ''];

try {
    switch ($accion) {
        case 'completada':
            $agendar_cita = isset($_POST['agendar_cita']) ? intval($_POST['agendar_cita']) : 0;
            $fecha_cita = isset($_POST['fecha_cita']) && !empty($_POST['fecha_cita']) 
                ? sanitizar($_POST['fecha_cita']) 
                : null;
            
            $resultado = marcarLlamadaCompletada($pdo, $llamada_id, $agendar_cita, $fecha_cita);
            
            if ($resultado) {
                $response['success'] = true;
                $response['message'] = 'Llamada marcada como completada';
                
                // Establecer mensaje de sesión
                setMensaje('<i class="fas fa-check-circle"></i> Llamada completada exitosamente', 'success');
            } else {
                $response['message'] = 'Error al marcar llamada como completada';
            }
            break;
            
        case 'posponer':
            $resultado = marcarLlamadaPospuesta($pdo, $llamada_id);
            
            if ($resultado) {
                $response['success'] = true;
                $response['message'] = 'Llamada pospuesta';
                
                setMensaje('<i class="fas fa-clock"></i> Llamada movida a "Llamar Después"', 'success');
            } else {
                $response['message'] = 'Error al posponer llamada';
            }
            break;
            
        case 'rechazar':
            $resultado = marcarLlamadaRechazada($pdo, $llamada_id);
            
            if ($resultado) {
                $response['success'] = true;
                $response['message'] = 'Llamada rechazada';
                
                setMensaje('<i class="fas fa-times-circle"></i> Llamada marcada como rechazada', 'warning');
            } else {
                $response['message'] = 'Error al rechazar llamada';
            }
            break;
            
        default:
            $response['message'] = 'Acción no válida';
            break;
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
}

// Devolver respuesta JSON
header('Content-Type: application/json');
echo json_encode($response);
