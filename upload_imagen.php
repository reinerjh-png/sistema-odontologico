<?php
/**
 * Endpoint AJAX para subir imágenes de pacientes
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

// Validar paciente_id
$paciente_id = isset($_POST['paciente_id']) ? intval($_POST['paciente_id']) : 0;
if ($paciente_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de paciente inválido']);
    exit;
}

// Verificar que el paciente existe
$paciente = obtenerPacientePorId($pdo, $paciente_id);
if (!$paciente) {
    echo json_encode(['success' => false, 'error' => 'Paciente no encontrado']);
    exit;
}

// Verificar que se recibió un archivo
if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    $errores_upload = [
        UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo del servidor',
        UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
        UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
        UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo',
    ];
    $codigo = $_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE;
    $mensaje = $errores_upload[$codigo] ?? 'Error desconocido al subir el archivo';
    echo json_encode(['success' => false, 'error' => $mensaje]);
    exit;
}

$archivo = $_FILES['imagen'];

// Validar tipo de archivo
$tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$tipo_real = finfo_file($finfo, $archivo['tmp_name']);
finfo_close($finfo);

if (!in_array($tipo_real, $tipos_permitidos)) {
    echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido. Solo se aceptan: JPG, PNG, GIF, WEBP']);
    exit;
}

// Validar tamaño (máximo 5MB)
$max_size = 5 * 1024 * 1024;
if ($archivo['size'] > $max_size) {
    echo json_encode(['success' => false, 'error' => 'El archivo excede el tamaño máximo de 5MB']);
    exit;
}

// Crear directorio usando numero_historia (no el ID interno)
$directorio = 'uploads/pacientes/' . $paciente['numero_historia'];
if (!is_dir($directorio)) {
    mkdir($directorio, 0755, true);
}

// Generar nombre único para el archivo
$extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
$nombre_archivo = uniqid('img_') . '_' . time() . '.' . strtolower($extension);
$ruta_destino = $directorio . '/' . $nombre_archivo;

// Mover archivo
if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
    echo json_encode(['success' => false, 'error' => 'Error al guardar el archivo']);
    exit;
}

// Registrar en la base de datos
try {
    $imagen_id = guardarImagenPaciente($pdo, $paciente_id, $nombre_archivo, $archivo['name']);
    echo json_encode([
        'success' => true,
        'imagen' => [
            'id' => $imagen_id,
            'nombre_archivo' => $nombre_archivo,
            'nombre_original' => $archivo['name'],
            'url' => $ruta_destino
        ]
    ]);
} catch (Exception $e) {
    // Si falla la BD, eliminar el archivo subido
    if (file_exists($ruta_destino)) {
        unlink($ruta_destino);
    }
    echo json_encode(['success' => false, 'error' => 'Error al registrar la imagen: ' . $e->getMessage()]);
}
