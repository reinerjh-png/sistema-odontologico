<?php
/**
 * API Endpoint: Guardar Odontograma
 * Clínica Dental Premium Uchuya
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
verificarSesion();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Recuperar datos del POST
$id_paciente = isset($_POST['id_paciente']) ? intval($_POST['id_paciente']) : 0;
$numero_diente = isset($_POST['numero_diente']) ? trim(strip_tags($_POST['numero_diente'])) : '';
$superficie = isset($_POST['superficie']) ? trim(strip_tags($_POST['superficie'])) : '';
$id_condicion = isset($_POST['id_condicion']) ? intval($_POST['id_condicion']) : 0;
$fecha_registro = isset($_POST['fecha_registro']) ? trim(strip_tags($_POST['fecha_registro'])) : '';
$observacion = isset($_POST['observacion']) ? trim(strip_tags($_POST['observacion'])) : '';

// Validaciones básicas
if ($id_paciente <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de paciente inválido']);
    exit;
}

if (empty($numero_diente)) {
    echo json_encode(['success' => false, 'error' => 'Número de diente no especificado']);
    exit;
}

$superficiesValidas = ['oclusal', 'vestibular', 'lingual', 'mesial', 'distal', 'completo'];
if (!in_array($superficie, $superficiesValidas, true)) {
    echo json_encode(['success' => false, 'error' => 'Superficie inválida']);
    exit;
}

if ($id_condicion <= 0) {
    echo json_encode(['success' => false, 'error' => 'Condición inválida']);
    exit;
}

if (empty($fecha_registro) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_registro)) {
    echo json_encode(['success' => false, 'error' => 'Fecha de registro inválida']);
    exit;
}

$id_usuario = $_SESSION['usuario_id'] ?? null;

try {
    // Obtener el ID de la condición "Sano" (código 'SA')
    $stmtSano = $pdo->prepare("SELECT id_condicion FROM odontograma_condiciones WHERE codigo = 'SA' LIMIT 1");
    $stmtSano->execute();
    $id_sano = intval($stmtSano->fetchColumn());

    if ($id_condicion === $id_sano) {
        // Si la condición es "Sano", eliminamos el registro para esta superficie/diente
        // ya que 'Sano' es el estado por defecto y no necesitamos persistirlo en BD
        $stmtDel = $pdo->prepare("
            DELETE FROM odontograma_detalle 
            WHERE id_paciente = ? AND numero_diente = ? AND superficie = ? AND fecha_registro = ?
        ");
        $stmtDel->execute([$id_paciente, $numero_diente, $superficie, $fecha_registro]);
        
        // Si se marcó el diente completo como sano, eliminamos todas las superficies de ese diente para esa fecha
        if ($superficie === 'completo') {
            $stmtDelAll = $pdo->prepare("
                DELETE FROM odontograma_detalle 
                WHERE id_paciente = ? AND numero_diente = ? AND fecha_registro = ?
            ");
            $stmtDelAll->execute([$id_paciente, $numero_diente, $fecha_registro]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Pieza dental actualizada a sano',
            'deleted' => true
        ]);
    } else {
        // Si es una condición patológica o tratamiento, hacemos UPSERT
        // Si la superficie es 'completo' (ej: Ausente, Corona), podemos opcionalmente limpiar
        // las superficies individuales para esa fecha para evitar conflictos, o dejarlas.
        // Limpiarlas es más ordenado:
        if ($superficie === 'completo') {
            $stmtDelSurfaces = $pdo->prepare("
                DELETE FROM odontograma_detalle 
                WHERE id_paciente = ? AND numero_diente = ? AND superficie != 'completo' AND fecha_registro = ?
            ");
            $stmtDelSurfaces->execute([$id_paciente, $numero_diente, $fecha_registro]);
        } else {
            // Si pintamos una superficie individual, y existía una condición de 'completo',
            // removemos la de 'completo' para evitar conflicto visual
            $stmtDelCompleto = $pdo->prepare("
                DELETE FROM odontograma_detalle 
                WHERE id_paciente = ? AND numero_diente = ? AND superficie = 'completo' AND fecha_registro = ?
            ");
            $stmtDelCompleto->execute([$id_paciente, $numero_diente, $fecha_registro]);
        }

        $sql = "
            INSERT INTO odontograma_detalle 
                (id_paciente, numero_diente, superficie, id_condicion, fecha_registro, id_usuario, observacion)
            VALUES 
                (:id_paciente, :numero_diente, :superficie, :id_condicion, :fecha_registro, :id_usuario, :observacion)
            ON DUPLICATE KEY UPDATE 
                id_condicion = VALUES(id_condicion), 
                id_usuario = VALUES(id_usuario), 
                observacion = VALUES(observacion)
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_paciente' => $id_paciente,
            ':numero_diente' => $numero_diente,
            ':superficie' => $superficie,
            ':id_condicion' => $id_condicion,
            ':fecha_registro' => $fecha_registro,
            ':id_usuario' => $id_usuario,
            ':observacion' => $observacion
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Guardado correctamente'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar el odontograma: ' . $e->getMessage()
    ]);
}
