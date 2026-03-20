-- ========================================
-- Tabla tenant_config para white-label / multi-tenant
-- ========================================

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
