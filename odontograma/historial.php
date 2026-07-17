<?php
/**
 * API Endpoint: Historial de Fechas del Odontograma
 * Clínica Dental Premium Uchuya
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
verificarSesion();

header('Content-Type: application/json; charset=utf-8');

$id_paciente = isset($_GET['id_paciente']) ? intval($_GET['id_paciente']) : 0;

if ($id_paciente <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de paciente inválido']);
    exit;
}

try {
    // Consultar fechas únicas registradas para este paciente, ordenadas de más reciente a más antigua
    $stmt = $pdo->prepare("
        SELECT DISTINCT fecha_registro 
        FROM odontograma_detalle 
        WHERE id_paciente = ? 
        ORDER BY fecha_registro DESC
    ");
    $stmt->execute([$id_paciente]);
    $fechas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'data' => $fechas
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener el historial: ' . $e->getMessage()
    ]);
}
