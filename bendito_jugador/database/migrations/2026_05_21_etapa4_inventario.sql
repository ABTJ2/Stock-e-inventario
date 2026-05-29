USE bendito_jugador;

ALTER TABLE ingresos_mercaderia
    ADD COLUMN IF NOT EXISTS id_almacen INT NULL AFTER id_usuario;

ALTER TABLE detalle_ingreso
    ADD COLUMN IF NOT EXISTS observacion VARCHAR(255) NULL AFTER precio_unitario;

ALTER TABLE movimientos_stock
    ADD COLUMN IF NOT EXISTS id_almacen INT NULL AFTER id_usuario,
    ADD COLUMN IF NOT EXISTS entidad_origen ENUM('ingreso', 'ajuste', 'traspaso', 'producto') NULL AFTER referencia,
    ADD COLUMN IF NOT EXISTS id_entidad_origen INT NULL AFTER entidad_origen;

ALTER TABLE movimientos_stock
    ADD INDEX IF NOT EXISTS idx_movimientos_almacen (id_almacen),
    ADD INDEX IF NOT EXISTS idx_movimientos_origen (entidad_origen, id_entidad_origen);

ALTER TABLE ajustes_inventario
    ADD COLUMN IF NOT EXISTS id_almacen INT NULL AFTER id_usuario,
    ADD COLUMN IF NOT EXISTS diferencia INT NOT NULL DEFAULT 0 AFTER stock_nuevo;

ALTER TABLE auditoria_inventario
    ADD COLUMN IF NOT EXISTS id_almacen INT NULL AFTER id_producto;
