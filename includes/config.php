<?php
/**
 * Configuración de la Base de Datos
 * Clínica Dental Premium Uchuya
 */

// Configuración de conexión MySQL
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_odontologico');

// Configuración del sitio
define('SITE_NAME', 'Clínica Dental Premium Uchuya');
define('SITE_SLOGAN', 'Excelencia en su sonrisa');

// Zona horaria
date_default_timezone_set('America/Lima');

// Conexión a la base de datos
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
        );
}
catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para iniciar sesión
session_start();
