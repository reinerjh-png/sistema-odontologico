<?php
/**
 * API Endpoint: Cargar Odontograma
 * Clínica Dental Premium Uchuya
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
verificarSesion();

header('Content-Type: application/json; charset=utf-8');

$id_paciente = isset($_GET['id_paciente']) ? intval($_GET['id_paciente']) : 0;
$fecha = isset($_GET['fecha']) ? trim(strip_tags($_GET['fecha'])) : '';

if ($id_paciente <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de paciente inválido']);
    exit;
}

if (empty($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode(['success' => false, 'error' => 'Fecha inválida o no proporcionada']);
    exit;
}

try {
    // Consultar detalles del odontograma para el paciente y la fecha especificada
    $stmt = $pdo->prepare("
        SELECT 
            d.numero_diente, 
            d.superficie, 
            d.id_condicion, 
            d.observacion,
            c.codigo, 
            c.nombre AS condicion_nombre, 
            c.color
        FROM odontograma_detalle d
        INNER JOIN odontograma_condiciones c ON d.id_condicion = c.id_condicion
        WHERE d.id_paciente = ? AND d.fecha_registro = ?
    ");
    $stmt->execute([$id_paciente, $fecha]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $resultados
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al cargar el odontograma: ' . $e->getMessage()
    ]);
}
