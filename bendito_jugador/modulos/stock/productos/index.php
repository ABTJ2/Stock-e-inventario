<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_auth();

$modulo_activo = 'stock';
$submodulo_activo = 'productos';
$breadcrumb = 'Productos';
$breadcrumb_link = app_url('dashboard.php');

$productos = [];
$categorias = [];
$marcas = [];
$unidades = [];
$estados = [];
$almacenes = [];
$error = '';
$success = '';

function table_exists(PDO $db, string $table): bool
{
    $stmt = $db->prepare(
        "SELECT COUNT(*)
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function column_exists(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare(
        "SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function index_exists(PDO $db, string $table, string $index): bool
{
    $stmt = $db->prepare(
        "SELECT COUNT(*)
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?"
    );
    $stmt->execute([$table, $index]);
    return (int) $stmt->fetchColumn() > 0;
}

function add_column_if_missing(PDO $db, string $table, string $column, string $definition): void
{
    if (!column_exists($db, $table, $column)) {
        $db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}

function seed_if_missing(PDO $db, string $table, string $nameColumn, array $rows): void
{
    foreach ($rows as $row) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$nameColumn} = ?");
        $stmt->execute([$row[$nameColumn]]);

        if ((int) $stmt->fetchColumn() === 0) {
            $columns = array_keys($row);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $stmt = $db->prepare(
                "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})"
            );
            $stmt->execute(array_values($row));
        }
    }
}

function ensure_product_schema(): void
{
    $db = db();

    $db->exec(
        "CREATE TABLE IF NOT EXISTS categorias_producto (
            id_categoria INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion VARCHAR(255),
            estado TINYINT DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS marcas (
            id_marca INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            estado TINYINT DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS unidades_medida (
            id_unidad_medida INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            abreviatura VARCHAR(20),
            estado TINYINT DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS estados_producto (
            id_estado_producto INT AUTO_INCREMENT PRIMARY KEY,
            nombre_estado VARCHAR(50) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS almacenes (
            id_almacen INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT,
            ubicacion VARCHAR(150),
            estado TINYINT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    if (!table_exists($db, 'productos')) {
        $db->exec(
            "CREATE TABLE productos (
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
                id_categoria INT NULL,
                id_marca INT NULL,
                id_unidad_medida INT NULL,
                id_estado_producto INT NULL,
                fecha_alta DATETIME DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    add_column_if_missing($db, 'productos', 'precio_referencia', 'DECIMAL(10,2) DEFAULT 0');
    add_column_if_missing($db, 'productos', 'precio', 'DECIMAL(10,2) DEFAULT 0');
    add_column_if_missing($db, 'productos', 'stock_actual', 'INT DEFAULT 0');
    add_column_if_missing($db, 'productos', 'categoria', 'VARCHAR(100)');
    add_column_if_missing($db, 'productos', 'unidad_medida', 'VARCHAR(20)');
    add_column_if_missing($db, 'productos', 'estado', "ENUM('activo', 'inactivo') DEFAULT 'activo'");
    add_column_if_missing($db, 'productos', 'id_categoria', 'INT NULL');
    add_column_if_missing($db, 'productos', 'id_marca', 'INT NULL');
    add_column_if_missing($db, 'productos', 'id_unidad_medida', 'INT NULL');
    add_column_if_missing($db, 'productos', 'id_estado_producto', 'INT NULL');
    add_column_if_missing($db, 'productos', 'fecha_alta', 'DATETIME DEFAULT CURRENT_TIMESTAMP');
    add_column_if_missing(
        $db,
        'productos',
        'fecha_actualizacion',
        'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    );

    if (
        table_exists($db, 'stock_por_almacen')
        && (!column_exists($db, 'stock_por_almacen', 'id_almacen')
            || !column_exists($db, 'stock_por_almacen', 'stock_actual'))
    ) {
        $backupTable = 'stock_por_almacen_respaldo_' . date('YmdHis');
        $db->exec("RENAME TABLE stock_por_almacen TO {$backupTable}");
    }

    $db->exec(
        "CREATE TABLE IF NOT EXISTS stock_por_almacen (
            id_stock INT AUTO_INCREMENT PRIMARY KEY,
            id_producto INT NOT NULL,
            id_almacen INT NOT NULL,
            stock_actual INT DEFAULT 0,
            stock_reservado INT DEFAULT 0,
            fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_producto_almacen (id_producto, id_almacen)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    add_column_if_missing($db, 'stock_por_almacen', 'stock_reservado', 'INT DEFAULT 0');
    add_column_if_missing(
        $db,
        'stock_por_almacen',
        'fecha_actualizacion',
        'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    );

    if (!index_exists($db, 'stock_por_almacen', 'uq_producto_almacen')) {
        $db->exec(
            "DELETE s1
             FROM stock_por_almacen s1
             INNER JOIN stock_por_almacen s2
                ON s1.id_producto = s2.id_producto
               AND s1.id_almacen = s2.id_almacen
               AND s1.id_stock > s2.id_stock"
        );
        $db->exec(
            "ALTER TABLE stock_por_almacen
             ADD UNIQUE KEY uq_producto_almacen (id_producto, id_almacen)"
        );
    }

    seed_if_missing($db, 'categorias_producto', 'nombre', [
        ['nombre' => 'Indumentaria', 'descripcion' => 'Ropa deportiva', 'estado' => 1],
        ['nombre' => 'Calzado', 'descripcion' => 'Botines y calzado deportivo', 'estado' => 1],
        ['nombre' => 'Accesorios', 'descripcion' => 'Complementos deportivos', 'estado' => 1],
        ['nombre' => 'Deportes', 'descripcion' => 'Articulos para practica deportiva', 'estado' => 1],
        ['nombre' => 'Kits', 'descripcion' => 'Combos y conjuntos', 'estado' => 1],
    ]);

    seed_if_missing($db, 'marcas', 'nombre', [
        ['nombre' => 'Bendito Jugador', 'estado' => 1],
        ['nombre' => 'Adidas', 'estado' => 1],
        ['nombre' => 'Nike', 'estado' => 1],
        ['nombre' => 'Penalty', 'estado' => 1],
        ['nombre' => 'Topper', 'estado' => 1],
    ]);

    seed_if_missing($db, 'unidades_medida', 'nombre', [
        ['nombre' => 'Unidad', 'abreviatura' => 'Unid.', 'estado' => 1],
        ['nombre' => 'Par', 'abreviatura' => 'Par', 'estado' => 1],
        ['nombre' => 'Caja', 'abreviatura' => 'Caja', 'estado' => 1],
        ['nombre' => 'Pack', 'abreviatura' => 'Pack', 'estado' => 1],
    ]);

    seed_if_missing($db, 'estados_producto', 'nombre_estado', [
        ['nombre_estado' => 'Activo'],
        ['nombre_estado' => 'Inactivo'],
        ['nombre_estado' => 'Discontinuado'],
    ]);

    seed_if_missing($db, 'almacenes', 'nombre', [
        ['nombre' => 'Almacen Central', 'descripcion' => 'Deposito principal', 'ubicacion' => 'Deposito principal', 'estado' => 1],
        ['nombre' => 'Deposito Norte', 'descripcion' => 'Sucursal norte', 'ubicacion' => 'Sucursal norte', 'estado' => 1],
        ['nombre' => 'Deposito Sur', 'descripcion' => 'Sucursal sur', 'ubicacion' => 'Sucursal sur', 'estado' => 1],
    ]);

    $db->exec("UPDATE productos SET precio_referencia = precio WHERE precio_referencia = 0 AND precio > 0");
    $db->exec("UPDATE productos SET precio = precio_referencia WHERE precio = 0 AND precio_referencia > 0");
    $db->exec(
        "UPDATE productos p
         JOIN categorias_producto c ON c.nombre = p.categoria
         SET p.id_categoria = c.id_categoria
         WHERE p.id_categoria IS NULL"
    );
    $db->exec(
        "UPDATE productos p
         SET p.id_categoria = (SELECT id_categoria FROM categorias_producto ORDER BY id_categoria LIMIT 1)
         WHERE p.id_categoria IS NULL"
    );
    $db->exec(
        "UPDATE productos p
         SET p.id_marca = (SELECT id_marca FROM marcas ORDER BY id_marca LIMIT 1)
         WHERE p.id_marca IS NULL"
    );
    $db->exec(
        "UPDATE productos p
         JOIN unidades_medida u
              ON LOWER(u.nombre) = LOWER(p.unidad_medida)
              OR LOWER(u.abreviatura) = LOWER(p.unidad_medida)
         SET p.id_unidad_medida = u.id_unidad_medida
         WHERE p.id_unidad_medida IS NULL"
    );
    $db->exec(
        "UPDATE productos p
         SET p.id_unidad_medida = (SELECT id_unidad_medida FROM unidades_medida ORDER BY id_unidad_medida LIMIT 1)
         WHERE p.id_unidad_medida IS NULL"
    );
    $db->exec(
        "UPDATE productos p
         SET p.id_estado_producto = (
             SELECT id_estado_producto FROM estados_producto
             WHERE nombre_estado = IF(p.estado = 'inactivo', 'Inactivo', 'Activo')
             LIMIT 1
         )
         WHERE p.id_estado_producto IS NULL"
    );
    $db->exec(
        "INSERT IGNORE INTO stock_por_almacen (id_producto, id_almacen, stock_actual, stock_reservado)
         SELECT p.id_producto, (SELECT id_almacen FROM almacenes ORDER BY id_almacen LIMIT 1), p.stock_actual, 0
         FROM productos p
         WHERE NOT EXISTS (
             SELECT 1 FROM stock_por_almacen s WHERE s.id_producto = p.id_producto
         )"
    );
}

function fetch_options(string $table, string $order = 'nombre'): array
{
    return db()->query("SELECT * FROM {$table} ORDER BY {$order}")->fetchAll();
}

function selected($current, $expected): string
{
    return (string) $current === (string) $expected ? 'selected' : '';
}

function next_product_code(): string
{
    $stmt = db()->query(
        "SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) AS ultimo
         FROM productos
         WHERE codigo REGEXP '^PROD[0-9]+$'"
    );
    $nextNumber = ((int) ($stmt->fetchColumn() ?: 0)) + 1;

    do {
        $code = 'PROD' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
        $stmt = db()->prepare('SELECT COUNT(*) FROM productos WHERE codigo = ?');
        $stmt->execute([$code]);
        $exists = (int) $stmt->fetchColumn() > 0;
        $nextNumber++;
    } while ($exists);

    return $code;
}

function validate_product_input(array $data): array
{
    $errors = [];

    if (trim((string) ($data['codigo'] ?? '')) === '') {
        $errors[] = 'El codigo es obligatorio.';
    }

    if (trim((string) ($data['nombre'] ?? '')) === '') {
        $errors[] = 'El nombre es obligatorio.';
    }

    foreach (['precio_referencia' => 'precio', 'stock_minimo' => 'stock minimo', 'stock_actual' => 'stock actual'] as $field => $label) {
        if (!is_numeric($data[$field] ?? null) || (float) $data[$field] < 0) {
            $errors[] = 'El ' . $label . ' debe ser numerico y no negativo.';
        }
    }

    foreach (['id_categoria' => 'categoria', 'id_marca' => 'marca', 'id_unidad_medida' => 'unidad de medida', 'id_estado_producto' => 'estado', 'id_almacen' => 'almacen'] as $field => $label) {
        if ((int) ($data[$field] ?? 0) <= 0) {
            $errors[] = 'Debe seleccionar ' . $label . '.';
        }
    }

    return $errors;
}

function sync_product_compatibility(PDO $db, int $idProducto): void
{
    $stmt = $db->prepare(
        "UPDATE productos p
         JOIN categorias_producto c ON c.id_categoria = p.id_categoria
         JOIN unidades_medida u ON u.id_unidad_medida = p.id_unidad_medida
         JOIN estados_producto ep ON ep.id_estado_producto = p.id_estado_producto
         SET p.precio = p.precio_referencia,
             p.stock_actual = COALESCE((SELECT SUM(s.stock_actual) FROM stock_por_almacen s WHERE s.id_producto = p.id_producto), 0),
             p.categoria = c.nombre,
             p.unidad_medida = COALESCE(u.abreviatura, u.nombre),
             p.estado = CASE WHEN ep.nombre_estado = 'Activo' THEN 'activo' ELSE 'inactivo' END
         WHERE p.id_producto = ?"
    );
    $stmt->execute([$idProducto]);
}

function stock_for_product(array $product): int
{
    return (int) ($product['stock_actual'] ?? 0);
}

try {
    ensure_product_schema();
} catch (Throwable $exception) {
    $error = 'No se pudo preparar la base de datos del modulo Productos. Revise setup.sql o la conexion.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear' || $action === 'editar') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'codigo' => trim((string) ($_POST['codigo'] ?? '')),
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
            'precio_referencia' => $_POST['precio_referencia'] ?? ($_POST['precio'] ?? 0),
            'stock_actual' => $_POST['stock_actual'] ?? 0,
            'stock_minimo' => $_POST['stock_minimo'] ?? 0,
            'id_categoria' => (int) ($_POST['id_categoria'] ?? 0),
            'id_marca' => (int) ($_POST['id_marca'] ?? 0),
            'id_unidad_medida' => (int) ($_POST['id_unidad_medida'] ?? 0),
            'id_estado_producto' => (int) ($_POST['id_estado_producto'] ?? 0),
            'id_almacen' => (int) ($_POST['id_almacen'] ?? 0),
        ];

        if ($action === 'crear') {
            $data['codigo'] = next_product_code();
        }

        if ($action === 'editar' && $id > 0) {
            $stmt = db()->prepare('SELECT codigo FROM productos WHERE id_producto = ?');
            $stmt->execute([$id]);
            $data['codigo'] = (string) ($stmt->fetchColumn() ?: '');
        }

        $validationErrors = validate_product_input($data);

        if ($action === 'editar' && $id <= 0) {
            $validationErrors[] = 'Producto invalido.';
        }

        if ($validationErrors) {
            $error = implode(' ', $validationErrors);
        } else {
            $db = db();

            try {
                $db->beginTransaction();

                if ($action === 'crear') {
                    $stmt = $db->prepare(
                        "INSERT INTO productos (
                            codigo, nombre, descripcion, precio_referencia, precio, stock_actual,
                            stock_minimo, id_categoria, id_marca, id_unidad_medida, id_estado_producto
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([
                        $data['codigo'],
                        $data['nombre'],
                        $data['descripcion'],
                        (float) $data['precio_referencia'],
                        (float) $data['precio_referencia'],
                        (int) $data['stock_actual'],
                        (int) $data['stock_minimo'],
                        $data['id_categoria'],
                        $data['id_marca'],
                        $data['id_unidad_medida'],
                        $data['id_estado_producto'],
                    ]);
                    $id = (int) $db->lastInsertId();

                    $stmt = $db->prepare(
                        "INSERT INTO stock_por_almacen (id_producto, id_almacen, stock_actual, stock_reservado)
                         VALUES (?, ?, ?, 0)"
                    );
                    $stmt->execute([$id, $data['id_almacen'], (int) $data['stock_actual']]);

                    if ((int) $data['stock_actual'] > 0) {
                        $stmt = $db->prepare(
                            "INSERT INTO movimientos_stock
                                (id_producto, id_usuario, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, referencia)
                             VALUES (?, ?, 'ingreso', ?, 0, ?, ?, ?)"
                        );
                        $stmt->execute([
                            $id,
                            (int) (current_user()['id'] ?? 0),
                            (int) $data['stock_actual'],
                            (int) $data['stock_actual'],
                            'Stock inicial al crear producto',
                            'producto:' . $id,
                        ]);
                    }

                    $success = 'Producto creado correctamente.';
                } else {
                    $stmt = $db->prepare(
                        "UPDATE productos
                         SET codigo = ?, nombre = ?, descripcion = ?, precio_referencia = ?, precio = ?,
                             stock_minimo = ?, id_categoria = ?, id_marca = ?, id_unidad_medida = ?,
                             id_estado_producto = ?
                         WHERE id_producto = ?"
                    );
                    $stmt->execute([
                        $data['codigo'],
                        $data['nombre'],
                        $data['descripcion'],
                        (float) $data['precio_referencia'],
                        (float) $data['precio_referencia'],
                        (int) $data['stock_minimo'],
                        $data['id_categoria'],
                        $data['id_marca'],
                        $data['id_unidad_medida'],
                        $data['id_estado_producto'],
                        $id,
                    ]);

                    $stmt = $db->prepare(
                        "INSERT INTO stock_por_almacen (id_producto, id_almacen, stock_actual, stock_reservado)
                         VALUES (?, ?, ?, 0)
                         ON DUPLICATE KEY UPDATE
                             stock_actual = VALUES(stock_actual),
                             stock_reservado = stock_reservado"
                    );
                    $stmt->execute([$id, $data['id_almacen'], (int) $data['stock_actual']]);

                    $success = 'Producto actualizado correctamente.';
                }

                sync_product_compatibility($db, $id);
                $db->commit();
            } catch (Throwable $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                    $error = 'No se pudo guardar: ya existe un producto con ese codigo o hay una relacion invalida.';
                } else {
                    $error = 'No se pudo guardar el producto. Verifique los datos e intente nuevamente.';
                }
            }
        }
    }

    if ($action === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            try {
                $db = db();
                $db->beginTransaction();

                $stmt = $db->prepare(
                    "UPDATE productos
                     SET id_estado_producto = (SELECT id_estado_producto FROM estados_producto WHERE nombre_estado = 'Inactivo' LIMIT 1),
                         estado = 'inactivo'
                     WHERE id_producto = ?"
                );
                $stmt->execute([$id]);

                $db->commit();
                $success = 'Producto desactivado correctamente.';
            } catch (Throwable $exception) {
                if (isset($db) && $db->inTransaction()) {
                    $db->rollBack();
                }
                $error = 'No se pudo desactivar el producto.';
            }
        }
    }
}

try {
    $categorias = db()->query("SELECT * FROM categorias_producto WHERE estado = 1 ORDER BY nombre")->fetchAll();
    $marcas = db()->query("SELECT * FROM marcas WHERE estado = 1 ORDER BY nombre")->fetchAll();
    $unidades = db()->query("SELECT * FROM unidades_medida WHERE estado = 1 ORDER BY nombre")->fetchAll();
    $estados = fetch_options('estados_producto', 'id_estado_producto');
    $almacenes = db()->query("SELECT * FROM almacenes WHERE estado = 1 OR estado = 'activo' ORDER BY nombre")->fetchAll();

    $search = trim((string) ($_GET['q'] ?? ''));
    $filterCategoria = (int) ($_GET['categoria'] ?? 0);
    $filterEstado = (int) ($_GET['estado'] ?? 0);
    $filterAlmacen = (int) ($_GET['almacen'] ?? 0);

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(p.codigo LIKE ? OR p.nombre LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    if ($filterCategoria > 0) {
        $where[] = 'p.id_categoria = ?';
        $params[] = $filterCategoria;
    }

    if ($filterEstado > 0) {
        $where[] = 'p.id_estado_producto = ?';
        $params[] = $filterEstado;
    }

    if ($filterAlmacen > 0) {
        $where[] = 'EXISTS (SELECT 1 FROM stock_por_almacen sf WHERE sf.id_producto = p.id_producto AND sf.id_almacen = ?)';
        $params[] = $filterAlmacen;
    }

    $sql = "SELECT
                p.*,
                c.nombre AS categoria_nombre,
                m.nombre AS marca_nombre,
                u.nombre AS unidad_nombre,
                u.abreviatura AS unidad_abreviatura,
                ep.nombre_estado,
                sa.id_almacen,
                a.nombre AS almacen_nombre,
                COALESCE(st.stock_actual, 0) AS stock_actual
            FROM productos p
            LEFT JOIN categorias_producto c ON c.id_categoria = p.id_categoria
            LEFT JOIN marcas m ON m.id_marca = p.id_marca
            LEFT JOIN unidades_medida u ON u.id_unidad_medida = p.id_unidad_medida
            LEFT JOIN estados_producto ep ON ep.id_estado_producto = p.id_estado_producto
            LEFT JOIN (
                SELECT id_producto, SUM(stock_actual) AS stock_actual
                FROM stock_por_almacen
                GROUP BY id_producto
            ) st ON st.id_producto = p.id_producto
            LEFT JOIN (
                SELECT id_producto, MIN(id_almacen) AS id_almacen
                FROM stock_por_almacen
                GROUP BY id_producto
            ) sa ON sa.id_producto = p.id_producto
            LEFT JOIN almacenes a ON a.id_almacen = sa.id_almacen";

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY p.nombre';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll();
} catch (Throwable $exception) {
    $error = $error ?: 'Error al cargar productos.';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos - Bendito Jugador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../../../includes/header.php'; ?>

            <div class="content-area">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="page-title">
                        <i class="fas fa-boxes"></i>
                        Gestion de Productos
                    </h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoProductoModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Producto
                    </button>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= e($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= e($success); ?>
                    </div>
                <?php endif; ?>

                <form method="GET" class="table-container mb-4">
                    <div class="p-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Buscar por codigo o nombre</label>
                                <input type="text" name="q" class="form-control" value="<?= e($_GET['q'] ?? ''); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Categoria</label>
                                <select name="categoria" class="form-control">
                                    <option value="">Todas</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= (int) $categoria['id_categoria']; ?>" <?= selected($_GET['categoria'] ?? '', $categoria['id_categoria']); ?>>
                                            <?= e($categoria['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="">Todos</option>
                                    <?php foreach ($estados as $estado): ?>
                                        <option value="<?= (int) $estado['id_estado_producto']; ?>" <?= selected($_GET['estado'] ?? '', $estado['id_estado_producto']); ?>>
                                            <?= e($estado['nombre_estado']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Almacen</label>
                                <select name="almacen" class="form-control">
                                    <option value="">Todos</option>
                                    <?php foreach ($almacenes as $almacen): ?>
                                        <option value="<?= (int) $almacen['id_almacen']; ?>" <?= selected($_GET['almacen'] ?? '', $almacen['id_almacen']); ?>>
                                            <?= e($almacen['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i>Filtrar
                                </button>
                                <a href="<?= app_url('modulos/stock/productos/index.php'); ?>" class="btn btn-secondary">
                                    <i class="fas fa-rotate-left"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Nombre</th>
                                    <th>Categoria</th>
                                    <th>Marca</th>
                                    <th>Unidad</th>
                                    <th>Precio</th>
                                    <th>Stock actual</th>
                                    <th>Stock min.</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $p): ?>
                                    <?php
                                    $stockActual = stock_for_product($p);
                                    $stockMinimo = (int) $p['stock_minimo'];
                                    ?>
                                    <tr>
                                        <td><?= e($p['codigo']); ?></td>
                                        <td><?= e($p['nombre']); ?></td>
                                        <td><?= e($p['categoria_nombre'] ?? '-'); ?></td>
                                        <td><?= e($p['marca_nombre'] ?? '-'); ?></td>
                                        <td><?= e($p['unidad_abreviatura'] ?: ($p['unidad_nombre'] ?? '-')); ?></td>
                                        <td>$<?= number_format((float) $p['precio_referencia'], 2, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($stockActual <= $stockMinimo): ?>
                                                <span class="badge" style="background: var(--danger); color: white;"><?= $stockActual; ?></span>
                                            <?php else: ?>
                                                <?= $stockActual; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $stockMinimo; ?></td>
                                        <td><?= e($p['nombre_estado'] ?? '-'); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editarModal<?= (int) $p['id_producto']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="eliminar">
                                                <input type="hidden" name="id" value="<?= (int) $p['id_producto']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Desactivar producto?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editarModal<?= (int) $p['id_producto']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Editar Producto</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="editar">
                                                        <input type="hidden" name="id" value="<?= (int) $p['id_producto']; ?>">

                                                        <?php include __DIR__ . '/producto_form.php'; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <?php if (!$productos): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">No hay productos registrados.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="nuevoProductoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">
                        <?php
                        $p = [
                            'codigo' => $error === '' ? next_product_code() : '',
                            'nombre' => '',
                            'descripcion' => '',
                            'precio_referencia' => 0,
                            'stock_actual' => 0,
                            'stock_minimo' => 0,
                            'id_categoria' => '',
                            'id_marca' => '',
                            'id_unidad_medida' => '',
                            'id_estado_producto' => $estados[0]['id_estado_producto'] ?? '',
                            'id_almacen' => $almacenes[0]['id_almacen'] ?? '',
                        ];
                        include __DIR__ . '/producto_form.php';
                        ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
