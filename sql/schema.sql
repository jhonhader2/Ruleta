-- Esquema MySQL para Ruleta de Colonias Colombianas
-- Ejecutar en phpMyAdmin o: mysql -u root < sql/schema.sql

CREATE DATABASE IF NOT EXISTS ruleta
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE ruleta;

CREATE TABLE IF NOT EXISTS asignaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  documento VARCHAR(20) NOT NULL,
  colonia VARCHAR(30) NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_documento (documento),
  KEY idx_colonia (colonia)
) ENGINE=InnoDB;
