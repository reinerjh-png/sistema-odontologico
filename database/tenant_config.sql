-- Script para ejecutar en phpMyAdmin
-- Añade la columna 'theme_mode' a la tabla tenant_config

ALTER TABLE `tenant_config` 
ADD COLUMN `theme_mode` VARCHAR(20) NOT NULL DEFAULT 'light' 
AFTER `color_sidebar`;
