<?php
/**
 * Cerrar Sesión
 * Clínica Dental Premium Uchuya
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Registrar actividad antes de cerrar sesión
registrarActividad($pdo, 'Logout', 'Cierre de sesión');

cerrarSesion();
header('Location: index.php');
exit;
