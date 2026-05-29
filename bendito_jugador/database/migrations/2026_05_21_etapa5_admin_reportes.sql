USE bendito_jugador;

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

ALTER TABLE auditoria_sistema
    ADD COLUMN IF NOT EXISTS entidad VARCHAR(80) NULL AFTER modulo,
    ADD COLUMN IF NOT EXISTS id_entidad BIGINT NULL AFTER entidad;

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

INSERT INTO parametros_sistema (clave, valor, descripcion, tipo, estado) VALUES
('empresa_nombre', 'Bendito Jugador', 'Nombre comercial mostrado en reportes y respaldos.', 'texto', 1),
('stock_alerta_visual', '1', 'Activa alertas visuales de stock bajo.', 'booleano', 1),
('backup_carpeta', 'backups', 'Carpeta local donde se guardan los respaldos SQL.', 'texto', 1),
('csv_separador', ';', 'Separador usado en exportaciones CSV.', 'texto', 1)
ON DUPLICATE KEY UPDATE
    descripcion = VALUES(descripcion),
    tipo = VALUES(tipo),
    estado = VALUES(estado);
