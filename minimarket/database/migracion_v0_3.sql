-- Versión 0.3: permite marcar productos como descontinuados.
-- Ejecutar una sola vez si la base minimarket_db ya existía.
-- En instalaciones nuevas, database.sql ya crea esta columna.
USE minimarket_db;

ALTER TABLE productos
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;
