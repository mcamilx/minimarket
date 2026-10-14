-- Ejecutar una sola vez si la base minimarket_db ya existía antes de v0.2.
-- En instalaciones nuevas, database.sql ya crea estas columnas y tablas.
USE minimarket_db;

ALTER TABLE ventas
    ADD COLUMN cliente_tipo_documento VARCHAR(20) NULL,
    ADD COLUMN cliente_documento VARCHAR(30) NULL,
    ADD COLUMN cliente_nombre VARCHAR(100) NULL,
    ADD COLUMN cliente_telefono VARCHAR(30) NULL;

CREATE TABLE ventas_pendientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL DEFAULT 0
);

CREATE TABLE detalle_venta_pendiente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_pendiente_id INT NOT NULL,
    producto_id INT NOT NULL,
    codigo VARCHAR(30) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    cantidad INT NOT NULL,
    UNIQUE KEY venta_pendiente_producto (venta_pendiente_id, producto_id),
    FOREIGN KEY (venta_pendiente_id) REFERENCES ventas_pendientes(id)
        ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);
