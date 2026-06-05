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

// Configuración de seguridad de cookies de sesión
// Garantiza que las cookies de sesión solo se envíen por HTTPS
// y no sean accesibles desde JavaScript (mitigación XSS y phishing).
ini_set('session.cookie_secure',   1);   // Solo HTTPS
ini_set('session.cookie_httponly', 1);   // Inaccesible desde JS
ini_set('session.cookie_samesite', 'Lax'); // Protección CSRF básica
ini_set('session.use_strict_mode', 1);   // Rechaza IDs de sesión no generados por el servidor

// Iniciar sesión
session_start();
