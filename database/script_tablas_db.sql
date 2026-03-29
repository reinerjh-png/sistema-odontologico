-- ========================================
-- Estructura de Base de Datos (sin datos)
-- Base de datos: `if0_41026781_clinica_uchuya`
-- Generado a partir del backup: 13/03/2026 09:15:06
-- Fecha de generación: 17/03/2026
-- Clínica Dental Premium Uchuya
-- ========================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Estructura de tabla `actividad_log`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `actividad_log`;
CREATE TABLE `actividad_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `detalle` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_actividad_usuario` (`usuario_id`),
  KEY `idx_actividad_fecha` (`created_at`),
  KEY `idx_log_fecha` (`created_at`),
  KEY `idx_log_usuario` (`usuario_id`),
  CONSTRAINT `fk_actividad_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- --------------------------------------------------------
-- Estructura de tabla `call_center_historial`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `call_center_historial`;
CREATE TABLE `call_center_historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `accion` enum('completada','pospuesta','rechazada') NOT NULL,
  `agendar_cita` tinyint(1) DEFAULT 0,
  `fecha_cita` date DEFAULT NULL,
  `fecha_accion` datetime NOT NULL,
  `ciclo_actual` tinyint(1) DEFAULT 1 COMMENT '1 = ciclo actual, 0 = ciclos anteriores',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_paciente` (`paciente_id`),
  KEY `idx_fecha_accion` (`fecha_accion`),
  KEY `idx_ciclo` (`ciclo_actual`),
  KEY `idx_accion` (`accion`),
  CONSTRAINT `call_center_historial_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- --------------------------------------------------------
-- Estructura de tabla `call_center_llamadas`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `call_center_llamadas`;
CREATE TABLE `call_center_llamadas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `estado` enum('pendiente','completada','pospuesta','rechazada') DEFAULT 'pendiente',
  `fecha_procesado` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fecha_asignacion` (`fecha_asignacion`),
  KEY `idx_estado` (`estado`),
  KEY `idx_paciente_fecha` (`paciente_id`,`fecha_asignacion`),
  CONSTRAINT `call_center_llamadas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- --------------------------------------------------------
-- Estructura de tabla `doctores`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `doctores`;
CREATE TABLE `doctores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `especialidad` varchar(100) DEFAULT 'Odontología General',
  `estado` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- --------------------------------------------------------
-- Estructura de tabla `paciente_imagenes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `paciente_imagenes`;
CREATE TABLE `paciente_imagenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_paciente_imagenes_paciente` (`paciente_id`),
  CONSTRAINT `paciente_imagenes_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- --------------------------------------------------------
-- Estructura de tabla `paciente_tratamientos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `paciente_tratamientos`;
CREATE TABLE `paciente_tratamientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `tratamiento_id` int(11) NOT NULL,
  `fecha_asignacion` date DEFAULT curdate(),
  `notas` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_paciente_tratamiento` (`paciente_id`,`tratamiento_id`),
  KEY `tratamiento_id` (`tratamiento_id`),
  KEY `idx_pt_paciente` (`paciente_id`),
  KEY `idx_pt_tratamiento` (`tratamiento_id`),
  CONSTRAINT `paciente_tratamientos_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `paciente_tratamientos_ibfk_2` FOREIGN KEY (`tratamiento_id`) REFERENCES `tratamientos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- --------------------------------------------------------
-- Estructura de tabla `pacientes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pacientes`;
CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_historia` int(10) NOT NULL,
  `dni` int(8) DEFAULT NULL,
  `nombres` varchar(200) NOT NULL,
  `genero` varchar(20) DEFAULT NULL,
  `celular` int(9) DEFAULT NULL,
  `edad` int(11) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `fecha_registro` date DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `fecha_ultima_cita` date DEFAULT NULL,
  `hora_cita` time DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_historia` (`numero_historia`),
  KEY `doctor_id` (`doctor_id`),
  KEY `idx_pacientes_dni` (`dni`),
  KEY `idx_pacientes_nombres` (`nombres`),
  KEY `idx_pacientes_numero_historia` (`numero_historia`),
  KEY `idx_pacientes_estado` (`estado`),
  KEY `idx_pacientes_fecha_cita` (`fecha_ultima_cita`),
  CONSTRAINT `pacientes_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- --------------------------------------------------------
-- Estructura de tabla `tratamientos`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `tratamientos`;
CREATE TABLE `tratamientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- --------------------------------------------------------
-- Estructura de tabla `usuarios`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `password_hash` varchar(64) NOT NULL COMMENT 'SHA-256 hash',
  `nombre_completo` varchar(150) NOT NULL,
  `rol` enum('admin','recepcionista') NOT NULL DEFAULT 'recepcionista',
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario administrador por defecto
INSERT INTO `usuarios` (`usuario`, `password_hash`, `nombre_completo`, `rol`, `estado`) VALUES
('admin', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'Administrador', 'admin', 1);

-- --------------------------------------------------------
-- Estructura de tabla `tenant_config`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `tenant_config`;
CREATE TABLE IF NOT EXISTS `tenant_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clinic_name` varchar(200) NOT NULL DEFAULT 'Clínica Dental',
  `logo_url` varchar(500) DEFAULT 'assets/logo.png',
  `color_primary` varchar(20) NOT NULL DEFAULT '#1B2B4B',
  `color_accent` varchar(20) NOT NULL DEFAULT '#4A90D9',
  `color_sidebar` varchar(20) NOT NULL DEFAULT '#1B2B4B',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración por defecto (solo si no existe)
INSERT INTO `tenant_config` (`clinic_name`, `logo_url`, `color_primary`, `color_accent`, `color_sidebar`)
SELECT 'Clínica Dental Premium Uchuya', 'assets/logo.png', '#1B2B4B', '#4A90D9', '#1B2B4B'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tenant_config` LIMIT 1);

-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
