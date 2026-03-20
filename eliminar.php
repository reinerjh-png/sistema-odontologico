<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Verificar sesión
verificarSesion();

// Verificar Rol de Administrador
// SOLO EL ADMIN PUEDE ELIMINAR PACIENTES
requiereAdmin();

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

// Proceder a eliminar el paciente
try {
    if (eliminarPaciente($pdo, $id)) {
        registrarActividad($pdo, 'Eliminar Paciente', 'Eliminó permanentemente al paciente: ' . $paciente['nombres'] . ' (HC: ' . $paciente['numero_historia'] . ')');
        setMensaje('Historia clínica y todos sus datos han sido eliminados permanentemente', 'success');
    } else {
        setMensaje('Ocurrió un error al intentar eliminar la historia clínica', 'error');
    }
} catch (Exception $e) {
    setMensaje('Error al eliminar: ' . $e->getMessage(), 'error');
}

// Redirigir de vuelta al dashboard (vista de archivados)
header('Location: dashboard.php?ver=archivados');
exit;
