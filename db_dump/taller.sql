SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS db_taller;
USE db_taller;

CREATE TABLE ordenes_trabajo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placa_vehiculo VARCHAR(10) NOT NULL,
    propietario VARCHAR(100) NOT NULL,
    descripcion_falla TEXT NOT NULL,
    costo_estimado DECIMAL(10,2) NOT NULL,
    estado ENUM('Pendiente', 'En Proceso', 'Finalizado', 'Entregado') DEFAULT 'Pendiente',
    fecha_ingreso TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO ordenes_trabajo (placa_vehiculo, propietario, descripcion_falla, costo_estimado, estado) VALUES
 ('BCD123', 'Carlos Mora Rojas', 'Ruido en los frenos delanteros al detenerse', 85000.00, 'Pendiente'),
 ('659874', 'Ana Solano Vargas', 'El motor se recalienta despues de 20 minutos', 150000.00, 'En Proceso'),
 ('CL-2045', 'Luis Jiménez Castro', 'Cambio de aceite y revision general', 45000.00, 'Finalizado');
