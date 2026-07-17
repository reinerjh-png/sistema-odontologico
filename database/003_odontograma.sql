-- =====================================================
-- Migración: Módulo Odontograma Interactivo
-- Archivo: database/003_odontograma.sql
-- Clínica Dental Premium Uchuya
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- Catálogo de condiciones dentales (estándar FDI peruano)
CREATE TABLE IF NOT EXISTS `odontograma_condiciones` (
  `id_condicion` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo`       VARCHAR(10)  NOT NULL,  -- ej: 'CA', 'OB', 'EX'
  `nombre`       VARCHAR(80)  NOT NULL,  -- ej: 'Caries', 'Obturación'
  `color`        VARCHAR(7)   NOT NULL,  -- hex ej: '#ef4444'
  `descripcion`  TEXT,
  `activo`       TINYINT(1) DEFAULT 1,
  `orden`        INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estado de cada superficie por diente por paciente por fecha
CREATE TABLE IF NOT EXISTS `odontograma_detalle` (
  `id_detalle`      INT AUTO_INCREMENT PRIMARY KEY,
  `id_paciente`     INT NOT NULL,           -- FK a la tabla de pacientes
  `numero_diente`   VARCHAR(5) NOT NULL,    -- FDI: '11','12'... '48', niños: '51'..'85'
  `superficie`      ENUM('oclusal','vestibular','lingual','mesial','distal','completo') NOT NULL,
  `id_condicion`    INT NOT NULL,
  `fecha_registro`  DATE NOT NULL,
  `id_usuario`      INT,                    -- quien registró
  `observacion`     TEXT,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_condicion`) REFERENCES `odontograma_condiciones`(`id_condicion`),
  FOREIGN KEY (`id_paciente`) REFERENCES `pacientes`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uk_paciente_diente_superficie_fecha` (`id_paciente`, `numero_diente`, `superficie`, `fecha_registro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice para consultar rápido por paciente
CREATE INDEX `idx_odontograma_paciente` ON `odontograma_detalle`(`id_paciente`, `fecha_registro`);

-- Inserción de las condiciones base estándar FDI
INSERT IGNORE INTO `odontograma_condiciones` (`codigo`, `nombre`, `color`, `orden`) VALUES
('SA', 'Sano',                  '#94a3b8', 1),
('CA', 'Caries',                '#ef4444', 2),
('OB', 'Obturación',            '#3b82f6', 3),
('EX', 'Extracción indicada',   '#f97316', 4),
('AU', 'Ausente',               '#1e293b', 5),
('CO', 'Corona',                '#eab308', 6),
('PU', 'Pulpitis',              '#a855f7', 7),
('NE', 'Necrosis',              '#6b7280', 8),
('FR', 'Fractura',              '#ec4899', 9),
('IM', 'Implante',              '#14b8a6', 10),
('SE', 'Sellante',              '#84cc16', 11),
('PR', 'Prótesis Removible',    '#f59e0b', 12);

COMMIT;
