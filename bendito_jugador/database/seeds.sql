USE bendito_jugador;

INSERT INTO roles (nombre_rol, descripcion, estado) VALUES
('Administrador', 'Usuario con acceso total al sistema', 'activo'),
('Empleado', 'Usuario con acceso operativo basico', 'activo'),
('Supervisor Administrativo', 'Usuario con permisos de supervision y reportes', 'activo'),
('Supervisor Auditor', 'Usuario con permisos de auditoria y control', 'activo'),
('Gerente Zonal', 'Usuario con permisos de gestion por zona', 'activo')
ON DUPLICATE KEY UPDATE
    descripcion = VALUES(descripcion),
    estado = VALUES(estado);

-- Credenciales iniciales de desarrollo:
-- admin / admin123
-- supervisor / password
-- operario / password
INSERT INTO usuarios (usuario, clave, nombre_completo, id_rol, estado, primer_ingreso) VALUES
(
    'admin',
    '$2y$10$iYU2bFLHH5cnDbcJh//n5eFs8JBa4xNXevjxQQ0IC/5C545vJ77/q',
    'Administrador Principal',
    (SELECT id_rol FROM roles WHERE nombre_rol = 'Administrador' LIMIT 1),
    'activo',
    0
),
(
    'supervisor',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Supervisor de Stock',
    (SELECT id_rol FROM roles WHERE nombre_rol = 'Supervisor Administrativo' LIMIT 1),
    'activo',
    1
),
(
    'operario',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Operario de Stock',
    (SELECT id_rol FROM roles WHERE nombre_rol = 'Empleado' LIMIT 1),
    'activo',
    1
)
ON DUPLICATE KEY UPDATE
    clave = VALUES(clave),
    nombre_completo = VALUES(nombre_completo),
    id_rol = VALUES(id_rol),
    estado = VALUES(estado),
    primer_ingreso = VALUES(primer_ingreso);

INSERT INTO categorias_producto (nombre, descripcion, estado) VALUES
('Indumentaria', 'Ropa deportiva', 1),
('Calzado', 'Botines y calzado deportivo', 1),
('Accesorios', 'Complementos deportivos', 1),
('Deportes', 'Articulos para practica deportiva', 1),
('Kits', 'Combos y conjuntos', 1)
ON DUPLICATE KEY UPDATE
    descripcion = VALUES(descripcion),
    estado = VALUES(estado);

INSERT INTO marcas (nombre, estado) VALUES
('Bendito Jugador', 1),
('Adidas', 1),
('Nike', 1),
('Penalty', 1),
('Topper', 1)
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado);

INSERT INTO unidades_medida (nombre, abreviatura, estado) VALUES
('Unidad', 'Unid.', 1),
('Par', 'Par', 1),
('Caja', 'Caja', 1),
('Pack', 'Pack', 1)
ON DUPLICATE KEY UPDATE
    abreviatura = VALUES(abreviatura),
    estado = VALUES(estado);

INSERT INTO estados_producto (nombre_estado) VALUES
('Activo'),
('Inactivo'),
('Discontinuado')
ON DUPLICATE KEY UPDATE
    nombre_estado = VALUES(nombre_estado);

INSERT INTO almacenes (nombre, descripcion, ubicacion, estado) VALUES
('Almacen Central', 'Deposito principal', 'Deposito principal', 1),
('Deposito Norte', 'Sucursal norte', 'Sucursal norte', 1),
('Deposito Sur', 'Sucursal sur', 'Sucursal sur', 1)
ON DUPLICATE KEY UPDATE
    descripcion = VALUES(descripcion),
    ubicacion = VALUES(ubicacion),
    estado = VALUES(estado);

INSERT INTO estados_proveedor (nombre_estado) VALUES
('Activo'),
('Inactivo')
ON DUPLICATE KEY UPDATE
    nombre_estado = VALUES(nombre_estado);

INSERT INTO rubros_proveedor (nombre, estado) VALUES
('Indumentaria deportiva', 1),
('Calzado deportivo', 1),
('Accesorios deportivos', 1),
('Equipamiento deportivo', 1),
('Servicios generales', 1)
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado);

INSERT INTO condiciones_iva (nombre, estado) VALUES
('Responsable Inscripto', 1),
('Monotributista', 1),
('Exento', 1),
('Consumidor Final', 1),
('No Responsable', 1)
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado);

INSERT INTO paises (nombre, estado) VALUES
('Argentina', 1)
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado);

INSERT INTO provincias (id_pais, nombre, estado) VALUES
((SELECT id_pais FROM paises WHERE nombre = 'Argentina' LIMIT 1), 'Buenos Aires', 1),
((SELECT id_pais FROM paises WHERE nombre = 'Argentina' LIMIT 1), 'Ciudad Autonoma de Buenos Aires', 1),
((SELECT id_pais FROM paises WHERE nombre = 'Argentina' LIMIT 1), 'Cordoba', 1),
((SELECT id_pais FROM paises WHERE nombre = 'Argentina' LIMIT 1), 'Santa Fe', 1)
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado);

INSERT INTO localidades (id_provincia, nombre, estado) VALUES
((SELECT id_provincia FROM provincias WHERE nombre = 'Buenos Aires' LIMIT 1), 'La Plata', 1),
((SELECT id_provincia FROM provincias WHERE nombre = 'Buenos Aires' LIMIT 1), 'Mar del Plata', 1),
((SELECT id_provincia FROM provincias WHERE nombre = 'Ciudad Autonoma de Buenos Aires' LIMIT 1), 'CABA', 1),
((SELECT id_provincia FROM provincias WHERE nombre = 'Cordoba' LIMIT 1), 'Cordoba', 1),
((SELECT id_provincia FROM provincias WHERE nombre = 'Santa Fe' LIMIT 1), 'Rosario', 1)
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado);
