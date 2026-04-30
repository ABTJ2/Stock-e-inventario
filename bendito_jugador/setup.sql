-- =====================================================
-- BENDITO JUGADOR - BASE DE DATOS
-- Sistema de Stock y Control de Inventario
-- =====================================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS bendito_jugador;
USE bendito_jugador;

-- =====================================================
-- TABLA: roles
-- =====================================================
CREATE TABLE IF NOT EXISTS roles (
    id_rol INT PRIMARY KEY AUTO_INCREMENT,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: usuarios
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    id_rol INT NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    primer_ingreso TINYINT(1) DEFAULT 1,
    fecha_ultimo_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

-- =====================================================
-- TABLA: productos
-- =====================================================
CREATE TABLE IF NOT EXISTS productos (
    id_producto INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) DEFAULT 0,
    stock_actual INT DEFAULT 0,
    stock_minimo INT DEFAULT 0,
    categoria VARCHAR(50),
    unidad_medida VARCHAR(20),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: proveedores
-- =====================================================
CREATE TABLE IF NOT EXISTS proveedores (
    id_proveedor INT PRIMARY KEY AUTO_INCREMENT,
    cuit VARCHAR(20) UNIQUE,
    razon_social VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion TEXT,
    contacto VARCHAR(100),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: almacenes
-- =====================================================
CREATE TABLE IF NOT EXISTS almacenes (
    id_almacen INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    ubicacion VARCHAR(100),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: ingresos_mercaderia
-- =====================================================
CREATE TABLE IF NOT EXISTS ingresos_mercaderia (
    id_ingreso INT PRIMARY KEY AUTO_INCREMENT,
    id_proveedor INT,
    id_usuario INT NOT NULL,
    numero_factura VARCHAR(50),
    fecha DATE NOT NULL,
    observaciones TEXT,
    estado ENUM('pendiente', 'confirmado', 'cancelado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- =====================================================
-- TABLA: detalle_ingreso
-- =====================================================
CREATE TABLE IF NOT EXISTS detalle_ingreso (
    id_detalle INT PRIMARY KEY AUTO_INCREMENT,
    id_ingreso INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_ingreso) REFERENCES ingresos_mercaderia(id_ingreso),
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
);

-- =====================================================
-- TABLA: movimientos_stock
-- =====================================================
CREATE TABLE IF NOT EXISTS movimientos_stock (
    id_movimiento INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_usuario INT NOT NULL,
    tipo_movimiento ENUM('ingreso', 'egreso', 'ajuste_positivo', 'ajuste_negativo', 'traspaso') NOT NULL,
    cantidad INT NOT NULL,
    stock_anterior INT NOT NULL,
    stock_nuevo INT NOT NULL,
    motivo VARCHAR(255),
    referencia VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- =====================================================
-- TABLA: auditoria_inventario
-- =====================================================
CREATE TABLE IF NOT EXISTS auditoria_inventario (
    id_auditoria INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_usuario INT NOT NULL,
    stock_sistema INT NOT NULL,
    stock_real INT NOT NULL,
    diferencia INT NOT NULL,
    observaciones TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- =====================================================
-- TABLA: traspasos
-- =====================================================
CREATE TABLE IF NOT EXISTS traspasos (
    id_traspaso INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_almacen_origen INT NOT NULL,
    id_almacen_destino INT NOT NULL,
    cantidad INT NOT NULL,
    id_usuario INT NOT NULL,
    estado ENUM('pendiente', 'confirmado', 'cancelado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    FOREIGN KEY (id_almacen_origen) REFERENCES almacenes(id_almacen),
    FOREIGN KEY (id_almacen_destino) REFERENCES almacenes(id_almacen),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- =====================================================
-- TABLA: ajustes_inventario
-- =====================================================
CREATE TABLE IF NOT EXISTS ajustes_inventario (
    id_ajuste INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_usuario INT NOT NULL,
    stock_anterior INT NOT NULL,
    stock_nuevo INT NOT NULL,
    motivo TEXT NOT NULL,
    estado ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- =====================================================
-- DATOS INICIALES: ROLES
-- =====================================================
INSERT INTO roles (nombre_rol, descripcion) VALUES
('Administrador', 'Usuario con acceso total al sistema'),
('Empleado', 'Usuario con acceso limitado a operaciones básicas'),
('Supervisor Administrativo', 'Usuario con permisos de supervisión y reportes'),
('Supervisor Auditor', 'Usuario con permisos de auditoría y control'),
('Gerente Zonal', 'Usuario con permisos de gestión por zona');

-- =====================================================
-- DATOS INICIALES: USUARIOS (contraseña: admin123)
-- =====================================================
INSERT INTO usuarios (usuario, clave, nombre_completo, id_rol, primer_ingreso, estado) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Principal', 1, 0, 'activo'),
('supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supervisor de Stock', 3, 1, 'activo'),
('operario', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operario de склад', 2, 1, 'activo');

-- =====================================================
-- DATOS INICIALES: ALMACENES
-- =====================================================
INSERT INTO almacenes (nombre, descripcion, ubicacion) VALUES
('Principal', 'Almacén principal de la empresa', 'Zona Centro'),
('Secundario', 'Almacén secundario', 'Zona Sur'),
('Exhibición', 'Salón de ventas y exhibición', 'Zona Norte');

-- =====================================================
-- DATOS INICIALES: PRODUCTOS
-- =====================================================
INSERT INTO productos (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, categoria, unidad_medida) VALUES
('PROD001', 'Camiseta Bendito Jugador', 'Camiseta oficial edición limitada', 2500.00, 50, 10, 'Indumentaria', 'unidad'),
('PROD002', 'Short Deportivo', 'Short deportivo profesional', 1800.00, 30, 5, 'Indumentaria', 'unidad'),
('PROD003', 'Pelota de Fútbol', 'Pelota profesional de match', 3200.00, 100, 20, 'Deportes', 'unidad'),
('PROD004', 'Medias Profesionales', 'Medias de fútbol con refuerzo', 850.00, 80, 15, 'Accesorios', 'par'),
('PROD005', 'Canilleras', 'Canilleras protectoras', 1200.00, 40, 10, 'Accesorios', 'unidad'),
('PROD006', 'Guantes de Arquero', 'Guantes profesionales', 4500.00, 15, 5, 'Deportes', 'unidad'),
('PROD007', 'Bolso Deportivo', 'Bolso grande con compartimentos', 5500.00, 25, 5, 'Accesorios', 'unidad'),
('PROD008', 'Botines Elite', 'Botines de alta gama', 8500.00, 20, 5, 'Calzado', 'unidad'),
('PROD009', 'Rompevientos', 'Rompevientos impermeable', 4200.00, 18, 5, 'Indumentaria', 'unidad'),
('PROD010', 'Kit Entrenamiento', 'Conjunto completo entrenamiento', 6800.00, 12, 3, 'Kits', 'unidad');

-- =====================================================
-- DATOS INICIALES: PROVEEDORES
-- =====================================================
INSERT INTO proveedores (cuit, razon_social, telefono, email, direccion, contacto) VALUES
('20-12345678-5', 'Deportes Argentina S.A.', '011-4567-8901', 'ventas@deportesarg.com', 'Av. Corrientes 1234, CABA', 'Juan Pérez'),
('27-87654321-0', 'Indumentaria Norte S.R.L.', '011-4789-0123', 'info@indnorte.com', 'Av. Rivadavia 5678, CABA', 'María González'),
('30-11223344-5', 'Sport World Import', '011-3456-7890', 'contacto@sportworld.com', 'Av. Santa Fe 2345, CABA', 'Carlos López');

-- =====================================================
-- TABLA: stock_por_almacen (por producto por almacén)
-- =====================================================
CREATE TABLE IF NOT EXISTS stock_por_almacen (
    id_stock INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_almacen INT NOT NULL,
    stock_actual INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    FOREIGN KEY (id_almacen) REFERENCES almacenes(id_almacen)
);

-- POBLAR STOCK POR ALMACÉN: asignar stock actual al almacén Principal (id_almacen = 1)
INSERT INTO stock_por_almacen (id_producto, id_almacen, stock_actual)
SELECT id_producto, 1, stock_actual FROM productos;

-- Mantener consistencia global en productos a partir del stock por almacén
UPDATE productos p
SET p.stock_actual = (
    SELECT COALESCE(SUM(s.stock_actual),0) FROM stock_por_almacen s WHERE s.id_producto = p.id_producto
);