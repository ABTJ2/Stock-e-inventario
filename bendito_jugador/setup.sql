-- =====================================================
-- BENDITO JUGADOR - BASE DE DATOS
-- Sistema de Stock y Control de Inventario
-- =====================================================

CREATE DATABASE IF NOT EXISTS bendito_jugador
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT fk_usuarios_roles
        FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auditoria_sistema (
    id_auditoria BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    accion VARCHAR(80) NOT NULL,
    modulo VARCHAR(80) NOT NULL,
    entidad VARCHAR(80) NULL,
    id_entidad BIGINT NULL,
    detalle TEXT NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_auditoria_usuario (id_usuario),
    INDEX idx_auditoria_accion (accion),
    INDEX idx_auditoria_modulo (modulo),
    CONSTRAINT fk_auditoria_sistema_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parametros_sistema (
    id_parametro INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NULL,
    descripcion VARCHAR(255) NULL,
    tipo VARCHAR(30) NOT NULL DEFAULT 'texto',
    estado TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLAS SECUNDARIAS DE PRODUCTOS
-- =====================================================
CREATE TABLE IF NOT EXISTS categorias_producto (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    estado TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marcas (
    id_marca INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    estado TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS unidades_medida (
    id_unidad_medida INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    abreviatura VARCHAR(20),
    estado TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estados_producto (
    id_estado_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre_estado VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS almacenes (
    id_almacen INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    ubicacion VARCHAR(150),
    estado TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: productos
-- Incluye columnas de compatibilidad para modulos existentes:
-- precio, stock_actual, categoria, unidad_medida y estado.
-- =====================================================
CREATE TABLE IF NOT EXISTS productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio_referencia DECIMAL(10,2) DEFAULT 0,
    precio DECIMAL(10,2) DEFAULT 0,
    stock_actual INT DEFAULT 0,
    stock_minimo INT DEFAULT 0,
    categoria VARCHAR(100),
    unidad_medida VARCHAR(20),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    id_categoria INT NOT NULL,
    id_marca INT NOT NULL,
    id_unidad_medida INT NOT NULL,
    id_estado_producto INT NOT NULL,
    fecha_alta DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_productos_categoria
        FOREIGN KEY (id_categoria) REFERENCES categorias_producto(id_categoria),
    CONSTRAINT fk_productos_marca
        FOREIGN KEY (id_marca) REFERENCES marcas(id_marca),
    CONSTRAINT fk_productos_unidad
        FOREIGN KEY (id_unidad_medida) REFERENCES unidades_medida(id_unidad_medida),
    CONSTRAINT fk_productos_estado
        FOREIGN KEY (id_estado_producto) REFERENCES estados_producto(id_estado_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: proveedores
-- =====================================================
CREATE TABLE IF NOT EXISTS estados_proveedor (
    id_estado_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    nombre_estado VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rubros_proveedor (
    id_rubro_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    estado TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS condiciones_iva (
    id_condicion_iva INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    estado TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS paises (
    id_pais INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    estado TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provincias (
    id_provincia INT AUTO_INCREMENT PRIMARY KEY,
    id_pais INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    estado TINYINT DEFAULT 1,
    UNIQUE KEY uq_provincia_pais (id_pais, nombre),
    CONSTRAINT fk_provincias_pais
        FOREIGN KEY (id_pais) REFERENCES paises(id_pais)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS localidades (
    id_localidad INT AUTO_INCREMENT PRIMARY KEY,
    id_provincia INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    estado TINYINT DEFAULT 1,
    UNIQUE KEY uq_localidad_provincia (id_provincia, nombre),
    CONSTRAINT fk_localidades_provincia
        FOREIGN KEY (id_provincia) REFERENCES provincias(id_provincia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proveedores (
    id_proveedor INT PRIMARY KEY AUTO_INCREMENT,
    cuit VARCHAR(20) UNIQUE,
    razon_social VARCHAR(100) NOT NULL,
    nombre_fantasia VARCHAR(150),
    telefono VARCHAR(20),
    email VARCHAR(100),
    sitio_web VARCHAR(150),
    direccion TEXT,
    codigo_postal VARCHAR(20),
    contacto VARCHAR(100),
    plazo_pago VARCHAR(100),
    cbu VARCHAR(30),
    alias VARCHAR(80),
    datos_bancarios TEXT,
    observaciones TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    id_estado_proveedor INT NULL,
    id_rubro_proveedor INT NULL,
    id_condicion_iva INT NULL,
    id_pais INT NULL,
    id_provincia INT NULL,
    id_localidad INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_proveedores_estado
        FOREIGN KEY (id_estado_proveedor) REFERENCES estados_proveedor(id_estado_proveedor),
    CONSTRAINT fk_proveedores_rubro
        FOREIGN KEY (id_rubro_proveedor) REFERENCES rubros_proveedor(id_rubro_proveedor),
    CONSTRAINT fk_proveedores_condicion_iva
        FOREIGN KEY (id_condicion_iva) REFERENCES condiciones_iva(id_condicion_iva),
    CONSTRAINT fk_proveedores_pais
        FOREIGN KEY (id_pais) REFERENCES paises(id_pais),
    CONSTRAINT fk_proveedores_provincia
        FOREIGN KEY (id_provincia) REFERENCES provincias(id_provincia),
    CONSTRAINT fk_proveedores_localidad
        FOREIGN KEY (id_localidad) REFERENCES localidades(id_localidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: ingresos_mercaderia
-- =====================================================
CREATE TABLE IF NOT EXISTS ingresos_mercaderia (
    id_ingreso INT PRIMARY KEY AUTO_INCREMENT,
    id_proveedor INT,
    id_usuario INT NOT NULL,
    id_almacen INT NOT NULL,
    numero_factura VARCHAR(50),
    fecha DATE NOT NULL,
    observaciones TEXT,
    estado ENUM('pendiente', 'confirmado', 'cancelado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ingresos_proveedor
        FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor),
    CONSTRAINT fk_ingresos_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    CONSTRAINT fk_ingresos_almacen
        FOREIGN KEY (id_almacen) REFERENCES almacenes(id_almacen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: detalle_ingreso
-- =====================================================
CREATE TABLE IF NOT EXISTS detalle_ingreso (
    id_detalle INT PRIMARY KEY AUTO_INCREMENT,
    id_ingreso INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    observacion VARCHAR(255) NULL,
    CONSTRAINT fk_detalle_ingreso
        FOREIGN KEY (id_ingreso) REFERENCES ingresos_mercaderia(id_ingreso),
    CONSTRAINT fk_detalle_producto
        FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: movimientos_stock
-- =====================================================
CREATE TABLE IF NOT EXISTS movimientos_stock (
    id_movimiento INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_usuario INT NOT NULL,
    id_almacen INT NULL,
    tipo_movimiento ENUM('ingreso', 'egreso', 'ajuste_positivo', 'ajuste_negativo', 'traspaso') NOT NULL,
    cantidad INT NOT NULL,
    stock_anterior INT NOT NULL,
    stock_nuevo INT NOT NULL,
    motivo VARCHAR(255),
    referencia VARCHAR(100),
    entidad_origen ENUM('ingreso', 'ajuste', 'traspaso', 'producto') NULL,
    id_entidad_origen INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_movimientos_almacen (id_almacen),
    INDEX idx_movimientos_origen (entidad_origen, id_entidad_origen),
    CONSTRAINT fk_movimientos_producto
        FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    CONSTRAINT fk_movimientos_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    CONSTRAINT fk_movimientos_almacen
        FOREIGN KEY (id_almacen) REFERENCES almacenes(id_almacen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: auditoria_inventario
-- =====================================================
CREATE TABLE IF NOT EXISTS auditoria_inventario (
    id_auditoria INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_almacen INT NULL,
    id_usuario INT NOT NULL,
    stock_sistema INT NOT NULL,
    stock_real INT NOT NULL,
    diferencia INT NOT NULL,
    observaciones TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_producto
        FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    CONSTRAINT fk_auditoria_almacen
        FOREIGN KEY (id_almacen) REFERENCES almacenes(id_almacen),
    CONSTRAINT fk_auditoria_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: stock_por_almacen
-- =====================================================
CREATE TABLE IF NOT EXISTS stock_por_almacen (
    id_stock INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    id_almacen INT NOT NULL,
    stock_actual INT DEFAULT 0,
    stock_reservado INT DEFAULT 0,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_producto_almacen (id_producto, id_almacen),
    CONSTRAINT fk_stock_producto
        FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    CONSTRAINT fk_stock_almacen
        FOREIGN KEY (id_almacen) REFERENCES almacenes(id_almacen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT fk_traspasos_producto
        FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    CONSTRAINT fk_traspasos_origen
        FOREIGN KEY (id_almacen_origen) REFERENCES almacenes(id_almacen),
    CONSTRAINT fk_traspasos_destino
        FOREIGN KEY (id_almacen_destino) REFERENCES almacenes(id_almacen),
    CONSTRAINT fk_traspasos_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: ajustes_inventario
-- =====================================================
CREATE TABLE IF NOT EXISTS ajustes_inventario (
    id_ajuste INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_usuario INT NOT NULL,
    id_almacen INT NOT NULL,
    stock_anterior INT NOT NULL,
    stock_nuevo INT NOT NULL,
    diferencia INT NOT NULL,
    motivo TEXT NOT NULL,
    estado ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ajustes_producto
        FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    CONSTRAINT fk_ajustes_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    CONSTRAINT fk_ajustes_almacen
        FOREIGN KEY (id_almacen) REFERENCES almacenes(id_almacen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATOS INICIALES
-- =====================================================
INSERT IGNORE INTO roles (nombre_rol, descripcion) VALUES
('Administrador', 'Usuario con acceso total al sistema'),
('Empleado', 'Usuario con acceso limitado a operaciones basicas'),
('Supervisor Administrativo', 'Usuario con permisos de supervision y reportes'),
('Supervisor Auditor', 'Usuario con permisos de auditoria y control'),
('Gerente Zonal', 'Usuario con permisos de gestion por zona');

-- Credenciales iniciales de desarrollo:
-- admin / admin123
-- supervisor / password
-- operario / password
INSERT INTO usuarios (usuario, clave, nombre_completo, id_rol, primer_ingreso, estado) VALUES
('admin', '$2y$10$iYU2bFLHH5cnDbcJh//n5eFs8JBa4xNXevjxQQ0IC/5C545vJ77/q', 'Administrador Principal', (SELECT id_rol FROM roles WHERE nombre_rol = 'Administrador' LIMIT 1), 0, 'activo'),
('supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supervisor de Stock', (SELECT id_rol FROM roles WHERE nombre_rol = 'Supervisor Administrativo' LIMIT 1), 1, 'activo'),
('operario', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operario de Stock', (SELECT id_rol FROM roles WHERE nombre_rol = 'Empleado' LIMIT 1), 1, 'activo')
ON DUPLICATE KEY UPDATE
    clave = VALUES(clave),
    nombre_completo = VALUES(nombre_completo),
    id_rol = VALUES(id_rol),
    primer_ingreso = VALUES(primer_ingreso),
    estado = VALUES(estado);

INSERT IGNORE INTO categorias_producto (nombre, descripcion, estado) VALUES
('Indumentaria', 'Ropa deportiva', 1),
('Calzado', 'Botines y calzado deportivo', 1),
('Accesorios', 'Complementos deportivos', 1),
('Deportes', 'Articulos para practica deportiva', 1),
('Kits', 'Combos y conjuntos', 1);

INSERT IGNORE INTO marcas (nombre, estado) VALUES
('Bendito Jugador', 1),
('Adidas', 1),
('Nike', 1),
('Penalty', 1),
('Topper', 1);

INSERT IGNORE INTO unidades_medida (nombre, abreviatura, estado) VALUES
('Unidad', 'Unid.', 1),
('Par', 'Par', 1),
('Caja', 'Caja', 1),
('Pack', 'Pack', 1);

INSERT IGNORE INTO estados_producto (nombre_estado) VALUES
('Activo'),
('Inactivo'),
('Discontinuado');

INSERT IGNORE INTO almacenes (nombre, descripcion, ubicacion, estado) VALUES
('Almacen Central', 'Deposito principal', 'Deposito principal', 1),
('Deposito Norte', 'Sucursal norte', 'Sucursal norte', 1),
('Deposito Sur', 'Sucursal sur', 'Sucursal sur', 1);

INSERT IGNORE INTO estados_proveedor (nombre_estado) VALUES
('Activo'),
('Inactivo');

INSERT IGNORE INTO rubros_proveedor (nombre, estado) VALUES
('Indumentaria deportiva', 1),
('Calzado deportivo', 1),
('Accesorios deportivos', 1),
('Equipamiento deportivo', 1),
('Servicios generales', 1);

INSERT IGNORE INTO condiciones_iva (nombre, estado) VALUES
('Responsable Inscripto', 1),
('Monotributista', 1),
('Exento', 1),
('Consumidor Final', 1),
('No Responsable', 1);

INSERT IGNORE INTO paises (nombre, estado) VALUES
('Argentina', 1);

INSERT IGNORE INTO provincias (id_pais, nombre, estado) VALUES
((SELECT id_pais FROM paises WHERE nombre = 'Argentina' LIMIT 1), 'Buenos Aires', 1),
((SELECT id_pais FROM paises WHERE nombre = 'Argentina' LIMIT 1), 'Ciudad Autonoma de Buenos Aires', 1),
((SELECT id_pais FROM paises WHERE nombre = 'Argentina' LIMIT 1), 'Cordoba', 1),
((SELECT id_pais FROM paises WHERE nombre = 'Argentina' LIMIT 1), 'Santa Fe', 1);

INSERT IGNORE INTO localidades (id_provincia, nombre, estado) VALUES
((SELECT id_provincia FROM provincias WHERE nombre = 'Buenos Aires' LIMIT 1), 'La Plata', 1),
((SELECT id_provincia FROM provincias WHERE nombre = 'Buenos Aires' LIMIT 1), 'Mar del Plata', 1),
((SELECT id_provincia FROM provincias WHERE nombre = 'Ciudad Autonoma de Buenos Aires' LIMIT 1), 'CABA', 1),
((SELECT id_provincia FROM provincias WHERE nombre = 'Cordoba' LIMIT 1), 'Cordoba', 1),
((SELECT id_provincia FROM provincias WHERE nombre = 'Santa Fe' LIMIT 1), 'Rosario', 1);

INSERT IGNORE INTO proveedores (
    cuit, razon_social, nombre_fantasia, telefono, email, direccion, contacto, id_estado_proveedor,
    id_rubro_proveedor, id_condicion_iva, id_pais, id_provincia, id_localidad, estado
) VALUES
('20-12345678-5', 'Deportes Argentina S.A.', 'Deportes Argentina', '011-4567-8901', 'ventas@deportesarg.com', 'Av. Corrientes 1234, CABA', 'Juan Perez',
    (SELECT id_estado_proveedor FROM estados_proveedor WHERE nombre_estado = 'Activo'),
    (SELECT id_rubro_proveedor FROM rubros_proveedor WHERE nombre = 'Equipamiento deportivo'),
    (SELECT id_condicion_iva FROM condiciones_iva WHERE nombre = 'Responsable Inscripto'),
    (SELECT id_pais FROM paises WHERE nombre = 'Argentina'),
    (SELECT id_provincia FROM provincias WHERE nombre = 'Ciudad Autonoma de Buenos Aires'),
    (SELECT id_localidad FROM localidades WHERE nombre = 'CABA'),
    'activo'),
('27-87654321-0', 'Indumentaria Norte S.R.L.', 'Indumentaria Norte', '011-4789-0123', 'info@indnorte.com', 'Av. Rivadavia 5678, CABA', 'Maria Gonzalez',
    (SELECT id_estado_proveedor FROM estados_proveedor WHERE nombre_estado = 'Activo'),
    (SELECT id_rubro_proveedor FROM rubros_proveedor WHERE nombre = 'Indumentaria deportiva'),
    (SELECT id_condicion_iva FROM condiciones_iva WHERE nombre = 'Responsable Inscripto'),
    (SELECT id_pais FROM paises WHERE nombre = 'Argentina'),
    (SELECT id_provincia FROM provincias WHERE nombre = 'Ciudad Autonoma de Buenos Aires'),
    (SELECT id_localidad FROM localidades WHERE nombre = 'CABA'),
    'activo'),
('30-11223344-5', 'Sport World Import', 'Sport World', '011-3456-7890', 'contacto@sportworld.com', 'Av. Santa Fe 2345, CABA', 'Carlos Lopez',
    (SELECT id_estado_proveedor FROM estados_proveedor WHERE nombre_estado = 'Activo'),
    (SELECT id_rubro_proveedor FROM rubros_proveedor WHERE nombre = 'Accesorios deportivos'),
    (SELECT id_condicion_iva FROM condiciones_iva WHERE nombre = 'Responsable Inscripto'),
    (SELECT id_pais FROM paises WHERE nombre = 'Argentina'),
    (SELECT id_provincia FROM provincias WHERE nombre = 'Ciudad Autonoma de Buenos Aires'),
    (SELECT id_localidad FROM localidades WHERE nombre = 'CABA'),
    'activo');

INSERT IGNORE INTO productos (
    codigo, nombre, descripcion, precio_referencia, precio, stock_actual, stock_minimo,
    categoria, unidad_medida, estado, id_categoria, id_marca, id_unidad_medida, id_estado_producto
) VALUES
('PROD001', 'Camiseta Bendito Jugador', 'Camiseta oficial edicion limitada', 2500.00, 2500.00, 50, 10, 'Indumentaria', 'Unid.', 'activo',
    (SELECT id_categoria FROM categorias_producto WHERE nombre = 'Indumentaria'),
    (SELECT id_marca FROM marcas WHERE nombre = 'Bendito Jugador'),
    (SELECT id_unidad_medida FROM unidades_medida WHERE nombre = 'Unidad'),
    (SELECT id_estado_producto FROM estados_producto WHERE nombre_estado = 'Activo')),
('PROD002', 'Short Deportivo', 'Short deportivo profesional', 1800.00, 1800.00, 30, 5, 'Indumentaria', 'Unid.', 'activo',
    (SELECT id_categoria FROM categorias_producto WHERE nombre = 'Indumentaria'),
    (SELECT id_marca FROM marcas WHERE nombre = 'Bendito Jugador'),
    (SELECT id_unidad_medida FROM unidades_medida WHERE nombre = 'Unidad'),
    (SELECT id_estado_producto FROM estados_producto WHERE nombre_estado = 'Activo')),
('PROD003', 'Pelota de Futbol', 'Pelota profesional de match', 3200.00, 3200.00, 100, 20, 'Deportes', 'Unid.', 'activo',
    (SELECT id_categoria FROM categorias_producto WHERE nombre = 'Deportes'),
    (SELECT id_marca FROM marcas WHERE nombre = 'Penalty'),
    (SELECT id_unidad_medida FROM unidades_medida WHERE nombre = 'Unidad'),
    (SELECT id_estado_producto FROM estados_producto WHERE nombre_estado = 'Activo')),
('PROD004', 'Medias Profesionales', 'Medias de futbol con refuerzo', 850.00, 850.00, 80, 15, 'Accesorios', 'Par', 'activo',
    (SELECT id_categoria FROM categorias_producto WHERE nombre = 'Accesorios'),
    (SELECT id_marca FROM marcas WHERE nombre = 'Bendito Jugador'),
    (SELECT id_unidad_medida FROM unidades_medida WHERE nombre = 'Par'),
    (SELECT id_estado_producto FROM estados_producto WHERE nombre_estado = 'Activo')),
('PROD005', 'Canilleras', 'Canilleras protectoras', 1200.00, 1200.00, 40, 10, 'Accesorios', 'Unid.', 'activo',
    (SELECT id_categoria FROM categorias_producto WHERE nombre = 'Accesorios'),
    (SELECT id_marca FROM marcas WHERE nombre = 'Topper'),
    (SELECT id_unidad_medida FROM unidades_medida WHERE nombre = 'Unidad'),
    (SELECT id_estado_producto FROM estados_producto WHERE nombre_estado = 'Activo'));

INSERT IGNORE INTO stock_por_almacen (id_producto, id_almacen, stock_actual, stock_reservado)
SELECT p.id_producto, a.id_almacen, p.stock_actual, 0
FROM productos p
CROSS JOIN almacenes a
WHERE a.nombre = 'Almacen Central';

UPDATE productos p
SET p.stock_actual = (
    SELECT COALESCE(SUM(s.stock_actual), 0)
    FROM stock_por_almacen s
    WHERE s.id_producto = p.id_producto
);
