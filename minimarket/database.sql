CREATE DATABASE minimarket_db;

USE minimarket_db;

-- Tabla de productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    cantidad INT NOT NULL DEFAULT 0
);

-- Tabla de ventas
CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    metodo_pago VARCHAR(20) NOT NULL,
    monto_pagado DECIMAL(10,2) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'PAGADA',
    cliente_tipo_documento VARCHAR(20) NULL,
    cliente_documento VARCHAR(30) NULL,
    cliente_nombre VARCHAR(100) NULL,
    cliente_telefono VARCHAR(30) NULL
);

-- Tabla con los productos incluidos en cada venta
CREATE TABLE detalle_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (venta_id) REFERENCES ventas(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- Las ventas pendientes no consumen el consecutivo de ventas ni el inventario.
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