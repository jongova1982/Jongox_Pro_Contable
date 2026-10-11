-- JONGOX PRO - Esquema de base de datos MySQL
-- Cree/seleccione la base de datos jongox_contable y luego importe este archivo.

CREATE TABLE IF NOT EXISTS cliente (
  id_cedula VARCHAR(20) PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  telefono VARCHAR(25),
  correo VARCHAR(254),
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO cliente (id_cedula,nombre,telefono,correo)
VALUES ('CF','Consumidor final',NULL,NULL)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

CREATE TABLE IF NOT EXISTS proveedor (
  nit VARCHAR(20) PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  ciudad VARCHAR(80),
  telefono VARCHAR(25),
  direccion VARCHAR(150),
  correo_electronico VARCHAR(254)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS producto (
  codigo VARCHAR(30) PRIMARY KEY,
  descripcion VARCHAR(120) NOT NULL,
  unidad_medida ENUM('UND','PESO','MT') NOT NULL DEFAULT 'UND',
  nit_proveedor VARCHAR(20) NULL,
  costo DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0,
  precio_venta DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0,
  stock_minimo DECIMAL(14,3) UNSIGNED NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_producto_proveedor FOREIGN KEY (nit_proveedor) REFERENCES proveedor(nit) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS compra (
  id_compra BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo_producto VARCHAR(30) NOT NULL,
  cantidad DECIMAL(14,3) UNSIGNED NOT NULL,
  unidad_medida ENUM('UND','PESO','MT') NOT NULL,
  nit_proveedor VARCHAR(20) NOT NULL,
  costo_unitario DECIMAL(14,2) UNSIGNED NOT NULL,
  total DECIMAL(16,2) UNSIGNED NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_compra_producto FOREIGN KEY (codigo_producto) REFERENCES producto(codigo),
  CONSTRAINT fk_compra_proveedor FOREIGN KEY (nit_proveedor) REFERENCES proveedor(nit)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ventas (
  id_venta BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo_producto VARCHAR(30) NOT NULL,
  cantidad DECIMAL(14,3) UNSIGNED NOT NULL,
  unidad_medida ENUM('UND','PESO','MT') NOT NULL,
  id_cedula VARCHAR(20) NOT NULL,
  precio_unitario DECIMAL(14,2) UNSIGNED NOT NULL,
  total DECIMAL(16,2) UNSIGNED NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_venta_producto FOREIGN KEY (codigo_producto) REFERENCES producto(codigo),
  CONSTRAINT fk_venta_cliente FOREIGN KEY (id_cedula) REFERENCES cliente(id_cedula)
) ENGINE=InnoDB;

-- Migración segura para bases creadas con el diagrama original.
ALTER TABLE producto MODIFY descripcion VARCHAR(120) NOT NULL;
ALTER TABLE producto MODIFY nit_proveedor VARCHAR(20) NULL;
ALTER TABLE producto ADD COLUMN IF NOT EXISTS costo DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE producto ADD COLUMN IF NOT EXISTS precio_venta DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE producto ADD COLUMN IF NOT EXISTS stock_minimo DECIMAL(14,3) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE producto ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE compra MODIFY cantidad DECIMAL(14,3) UNSIGNED NOT NULL;
ALTER TABLE compra ADD COLUMN IF NOT EXISTS costo_unitario DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE compra ADD COLUMN IF NOT EXISTS total DECIMAL(16,2) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE compra ADD COLUMN IF NOT EXISTS fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS precio_unitario DECIMAL(14,2) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS total DECIMAL(16,2) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Compra relaciona producto y proveedor de forma independiente: un producto
-- puede adquirirse a cualquiera de los proveedores registrados.

CREATE TABLE IF NOT EXISTS movimientos_bodega (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo_producto VARCHAR(30) NOT NULL,
  tipo ENUM('ENTRADA','SALIDA','AJUSTE') NOT NULL,
  cantidad DECIMAL(14,3) NOT NULL,
  referencia VARCHAR(40) NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_movimiento_producto FOREIGN KEY (codigo_producto) REFERENCES producto(codigo)
) ENGINE=InnoDB;

-- La creación de vistas puede estar denegada en hosting compartido.
-- Si tu proveedor lo permite, puedes crear la vista inventario manualmente.
--
-- CREATE OR REPLACE VIEW inventario AS
-- SELECT p.codigo, p.descripcion, p.unidad_medida, p.costo, p.precio_venta, p.stock_minimo,
--        COALESCE(SUM(CASE WHEN m.tipo='ENTRADA' THEN m.cantidad WHEN m.tipo='SALIDA' THEN -m.cantidad ELSE m.cantidad END),0) AS existencia,
--        COALESCE(SUM(CASE WHEN m.tipo='ENTRADA' THEN m.cantidad WHEN m.tipo='SALIDA' THEN -m.cantidad ELSE m.cantidad END),0) * p.costo AS valor_inventario
-- FROM producto p LEFT JOIN movimientos_bodega m ON m.codigo_producto=p.codigo
-- GROUP BY p.codigo, p.descripcion, p.unidad_medida, p.costo, p.precio_venta, p.stock_minimo;
