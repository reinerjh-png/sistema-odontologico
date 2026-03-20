<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
verificarSesion();
require_once 'includes/functions.php';

// Verificar si se recibió un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setMensaje('ID de paciente inválido', 'error');
    header('Location: dashboard.php');
    exit;
}

$id = intval($_GET['id']);

// Verificar que el paciente existe
$paciente = obtenerPacientePorId($pdo, $id);

if (!$paciente) {
    setMensaje('Paciente no encontrado', 'error');
    header('Location: dashboard.php');
    exit;
}

// Archivar paciente
try {
    if (archivarPaciente($pdo, $id)) {
        registrarActividad($pdo, 'Archivar Paciente', 'Archivó al paciente: ' . $paciente['nombres'] . ' (HC: ' . $paciente['numero_historia'] . ')');
        setMensaje('Historia clínica archivada exitosamente', 'success');
    } else {
        setMensaje('Error al archivar la historia clínica', 'error');
    }
} catch (Exception $e) {
    setMensaje('Error al archivar: ' . $e->getMessage(), 'error');
}

header('Location: dashboard.php');
exit;
