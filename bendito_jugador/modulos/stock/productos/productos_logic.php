<?php
declare(strict_types=1);

function producto_estado_sql(array $estado): string
{
    return strcasecmp((string) ($estado['nombre_estado'] ?? ''), 'Activo') === 0 ? 'activo' : 'inactivo';
}

function producto_estado_por_id(PDO $db, int $idEstado): ?array
{
    $stmt = $db->prepare('SELECT * FROM estados_producto WHERE id_estado_producto = ?');
    $stmt->execute([$idEstado]);
    $estado = $stmt->fetch();

    return $estado ?: null;
}

function producto_catalogos(PDO $db): array
{
    return [
        'categorias' => $db->query('SELECT * FROM categorias_producto WHERE estado = 1 ORDER BY nombre')->fetchAll(),
        'marcas' => $db->query('SELECT * FROM marcas WHERE estado = 1 ORDER BY nombre')->fetchAll(),
        'unidades' => $db->query('SELECT * FROM unidades_medida WHERE estado = 1 ORDER BY nombre')->fetchAll(),
        'estados' => $db->query('SELECT * FROM estados_producto ORDER BY id_estado_producto')->fetchAll(),
        'almacenes' => $db->query('SELECT * FROM almacenes WHERE estado = 1 ORDER BY nombre')->fetchAll(),
    ];
}

function producto_siguiente_codigo(PDO $db): string
{
    $stmt = $db->query(
        "SELECT MAX(CAST(codigo AS UNSIGNED))
         FROM productos
         WHERE codigo REGEXP '^[0-9]+$'"
    );
    $nextNumber = ((int) ($stmt->fetchColumn() ?: 0)) + 1;

    do {
        $codigo = (string) $nextNumber;
        $stmt = $db->prepare('SELECT COUNT(*) FROM productos WHERE codigo = ?');
        $stmt->execute([$codigo]);
        $nextNumber++;
    } while ((int) $stmt->fetchColumn() > 0);

    return $codigo;
}

function producto_post_data(array $source): array
{
    return [
        'codigo' => strtoupper(trim((string) ($source['codigo'] ?? ''))),
        'nombre' => trim((string) ($source['nombre'] ?? '')),
        'descripcion' => trim((string) ($source['descripcion'] ?? '')),
        'precio_referencia' => trim((string) ($source['precio_referencia'] ?? '')),
        'stock_actual' => trim((string) ($source['stock_actual'] ?? '')),
        'stock_minimo' => trim((string) ($source['stock_minimo'] ?? '')),
        'id_categoria' => (int) ($source['id_categoria'] ?? 0),
        'id_marca' => (int) ($source['id_marca'] ?? 0),
        'id_unidad_medida' => (int) ($source['id_unidad_medida'] ?? 0),
        'id_estado_producto' => (int) ($source['id_estado_producto'] ?? 0),
        'id_almacen' => (int) ($source['id_almacen'] ?? 0),
    ];
}

function producto_opcion_existe(PDO $db, string $table, string $idColumn, int $id, bool $activeOnly = true): bool
{
    $allowed = [
        'categorias_producto' => 'id_categoria',
        'marcas' => 'id_marca',
        'unidades_medida' => 'id_unidad_medida',
        'estados_producto' => 'id_estado_producto',
        'almacenes' => 'id_almacen',
    ];

    if (($allowed[$table] ?? '') !== $idColumn) {
        return false;
    }

    $sql = "SELECT COUNT(*) FROM {$table} WHERE {$idColumn} = ?";
    if ($activeOnly && $table !== 'estados_producto') {
        $sql .= ' AND estado = 1';
    }

    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);

    return (int) $stmt->fetchColumn() > 0;
}

function producto_validar(PDO $db, array $data, int $idProducto = 0, bool $validarCodigo = true): array
{
    $errors = [];

    if ($validarCodigo) {
        if ($data['codigo'] === '') {
            $errors[] = 'El codigo es obligatorio.';
        } elseif (!ctype_digit($data['codigo'])) {
            $errors[] = 'El codigo debe ser numerico.';
        } elseif (strlen($data['codigo']) > 50) {
            $errors[] = 'El codigo no puede superar 50 caracteres.';
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) FROM productos WHERE codigo = ? AND id_producto <> ?');
            $stmt->execute([$data['codigo'], $idProducto]);
            if ((int) $stmt->fetchColumn() > 0) {
                $errors[] = 'Ya existe un producto con ese codigo.';
            }
        }
    }

    if ($data['nombre'] === '') {
        $errors[] = 'El nombre es obligatorio.';
    }

    if ($data['precio_referencia'] === '') {
        $errors[] = 'El precio es obligatorio.';
    } elseif (!is_numeric($data['precio_referencia']) || (float) $data['precio_referencia'] < 0) {
        $errors[] = 'El precio debe ser numerico y no negativo.';
    }

    foreach (['stock_actual' => 'stock inicial/actual', 'stock_minimo' => 'stock minimo'] as $field => $label) {
        if ($data[$field] === '') {
            $errors[] = 'El ' . $label . ' es obligatorio.';
        } elseif (!ctype_digit((string) $data[$field])) {
            $errors[] = 'El ' . $label . ' debe ser un entero no negativo.';
        }
    }

    $checks = [
        ['id_categoria', 'categorias_producto', 'id_categoria', 'categoria'],
        ['id_marca', 'marcas', 'id_marca', 'marca'],
        ['id_unidad_medida', 'unidades_medida', 'id_unidad_medida', 'unidad de medida'],
        ['id_estado_producto', 'estados_producto', 'id_estado_producto', 'estado'],
        ['id_almacen', 'almacenes', 'id_almacen', 'almacen'],
    ];

    foreach ($checks as [$field, $table, $idColumn, $label]) {
        if ((int) $data[$field] <= 0 || !producto_opcion_existe($db, $table, $idColumn, (int) $data[$field])) {
            $errors[] = 'Debe seleccionar un valor valido para ' . $label . '.';
        }
    }

    return $errors;
}

function producto_sincronizar_compatibilidad(PDO $db, int $idProducto): void
{
    $stmt = $db->prepare(
        "UPDATE productos p
         LEFT JOIN categorias_producto c ON c.id_categoria = p.id_categoria
         LEFT JOIN unidades_medida u ON u.id_unidad_medida = p.id_unidad_medida
         LEFT JOIN estados_producto ep ON ep.id_estado_producto = p.id_estado_producto
         SET p.precio = p.precio_referencia,
             p.stock_actual = COALESCE((SELECT SUM(s.stock_actual) FROM stock_por_almacen s WHERE s.id_producto = p.id_producto), 0),
             p.categoria = c.nombre,
             p.unidad_medida = COALESCE(u.abreviatura, u.nombre),
             p.estado = CASE WHEN ep.nombre_estado = 'Activo' THEN 'activo' ELSE 'inactivo' END
         WHERE p.id_producto = ?"
    );
    $stmt->execute([$idProducto]);
}

function producto_crear(PDO $db, array $data): int
{
    $estado = producto_estado_por_id($db, (int) $data['id_estado_producto']);
    if ($estado === null) {
        throw new RuntimeException('Estado invalido.');
    }

    $stockInicial = (int) $data['stock_actual'];
    $idAlmacen = (int) $data['id_almacen'];
    $userId = (int) (current_user()['id'] ?? 0);

    $db->beginTransaction();

    try {
        $codigo = producto_siguiente_codigo($db);

        $stmt = $db->prepare(
            "INSERT INTO productos (
                codigo, nombre, descripcion, precio_referencia, precio, stock_actual, stock_minimo,
                estado, id_categoria, id_marca, id_unidad_medida, id_estado_producto
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $codigo,
            $data['nombre'],
            $data['descripcion'],
            (float) $data['precio_referencia'],
            (float) $data['precio_referencia'],
            $stockInicial,
            (int) $data['stock_minimo'],
            producto_estado_sql($estado),
            (int) $data['id_categoria'],
            (int) $data['id_marca'],
            (int) $data['id_unidad_medida'],
            (int) $data['id_estado_producto'],
        ]);

        $idProducto = (int) $db->lastInsertId();

        $stmt = $db->prepare(
            'INSERT INTO stock_por_almacen (id_producto, id_almacen, stock_actual, stock_reservado)
             VALUES (?, ?, ?, 0)'
        );
        $stmt->execute([$idProducto, $idAlmacen, $stockInicial]);

        if ($stockInicial > 0) {
            $stmt = $db->prepare(
                "INSERT INTO movimientos_stock
                    (id_producto, id_usuario, id_almacen, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, referencia, entidad_origen, id_entidad_origen)
                 VALUES (?, ?, ?, 'ingreso', ?, 0, ?, ?, ?, 'producto', ?)"
            );
            $stmt->execute([
                $idProducto,
                $userId,
                $idAlmacen,
                $stockInicial,
                $stockInicial,
                'Stock inicial al crear producto',
                'producto:' . $idProducto,
                $idProducto,
            ]);
        }

        producto_sincronizar_compatibilidad($db, $idProducto);
        audit_event('alta', 'productos', $userId, 'Alta de producto ' . $codigo . ' - ' . $data['nombre']);
        $db->commit();

        return $idProducto;
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        throw $exception;
    }
}

function producto_editar(PDO $db, int $idProducto, array $data): void
{
    $estado = producto_estado_por_id($db, (int) $data['id_estado_producto']);
    if ($estado === null) {
        throw new RuntimeException('Estado invalido.');
    }

    $stmt = $db->prepare('SELECT codigo FROM productos WHERE id_producto = ?');
    $stmt->execute([$idProducto]);
    $productoActual = $stmt->fetch();
    if (!$productoActual) {
        throw new RuntimeException('Producto inexistente.');
    }

    $codigoActual = (string) $productoActual['codigo'];

    $db->beginTransaction();

    try {
        $stmt = $db->prepare(
            "UPDATE productos
             SET nombre = ?, descripcion = ?, precio_referencia = ?, precio = ?,
                 stock_minimo = ?, estado = ?, id_categoria = ?, id_marca = ?, id_unidad_medida = ?,
                 id_estado_producto = ?
             WHERE id_producto = ?"
        );
        $stmt->execute([
            $data['nombre'],
            $data['descripcion'],
            (float) $data['precio_referencia'],
            (float) $data['precio_referencia'],
            (int) $data['stock_minimo'],
            producto_estado_sql($estado),
            (int) $data['id_categoria'],
            (int) $data['id_marca'],
            (int) $data['id_unidad_medida'],
            (int) $data['id_estado_producto'],
            $idProducto,
        ]);

        $idAlmacen = (int) $data['id_almacen'];
        $stockNuevo = (int) $data['stock_actual'];
        $userId = (int) (current_user()['id'] ?? 0);

        $stmt = $db->prepare(
            'SELECT stock_actual
             FROM stock_por_almacen
             WHERE id_producto = ? AND id_almacen = ?
             FOR UPDATE'
        );
        $stmt->execute([$idProducto, $idAlmacen]);
        $stockAnterior = $stmt->fetchColumn();
        $stockAnterior = $stockAnterior === false ? 0 : (int) $stockAnterior;

        $stmt = $db->prepare(
            'INSERT INTO stock_por_almacen (id_producto, id_almacen, stock_actual, stock_reservado)
             VALUES (?, ?, ?, 0)
             ON DUPLICATE KEY UPDATE stock_actual = VALUES(stock_actual)'
        );
        $stmt->execute([$idProducto, $idAlmacen, $stockNuevo]);

        if ($stockAnterior !== $stockNuevo) {
            $diferencia = $stockNuevo - $stockAnterior;
            $tipoMovimiento = $diferencia >= 0 ? 'ajuste_positivo' : 'ajuste_negativo';
            $stmt = $db->prepare(
                "INSERT INTO movimientos_stock
                    (id_producto, id_usuario, id_almacen, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, referencia, entidad_origen, id_entidad_origen)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'producto', ?)"
            );
            $stmt->execute([
                $idProducto,
                $userId,
                $idAlmacen,
                $tipoMovimiento,
                abs($diferencia),
                $stockAnterior,
                $stockNuevo,
                'Actualizacion de stock desde ABM productos',
                'producto:' . $idProducto,
                $idProducto,
            ]);
        }

        producto_sincronizar_compatibilidad($db, $idProducto);
        audit_event('edicion', 'productos', $userId, 'Edicion de producto ' . $codigoActual);
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        throw $exception;
    }
}

function producto_identificador_sql(string $identifier): string
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
        throw new RuntimeException('Identificador SQL invalido.');
    }

    return '`' . $identifier . '`';
}

function producto_tablas_relacionadas(PDO $db): array
{
    $stmt = $db->query(
        "SELECT TABLE_NAME, COLUMN_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE()
           AND REFERENCED_TABLE_NAME = 'productos'
           AND REFERENCED_COLUMN_NAME = 'id_producto'"
    );

    $tablas = $stmt->fetchAll();
    $orden = [
        'detalle_ingreso',
        'movimientos_stock',
        'stock_por_almacen',
        'traspasos',
        'ajustes_inventario',
        'auditoria_inventario',
    ];

    usort($tablas, static function (array $a, array $b) use ($orden): int {
        $posA = array_search((string) $a['TABLE_NAME'], $orden, true);
        $posB = array_search((string) $b['TABLE_NAME'], $orden, true);

        $posA = $posA === false ? PHP_INT_MAX : $posA;
        $posB = $posB === false ? PHP_INT_MAX : $posB;

        return $posA <=> $posB;
    });

    return $tablas;
}

function producto_eliminar(PDO $db, int $idProducto): void
{
    $stmt = $db->prepare('SELECT codigo, nombre FROM productos WHERE id_producto = ?');
    $stmt->execute([$idProducto]);
    $producto = $stmt->fetch();
    if (!$producto) {
        throw new RuntimeException('Producto inexistente.');
    }

    $db->beginTransaction();

    try {
        foreach (producto_tablas_relacionadas($db) as $relacion) {
            $tabla = producto_identificador_sql((string) $relacion['TABLE_NAME']);
            $columna = producto_identificador_sql((string) $relacion['COLUMN_NAME']);
            $stmt = $db->prepare("DELETE FROM {$tabla} WHERE {$columna} = ?");
            $stmt->execute([$idProducto]);
        }

        $stmt = $db->prepare('DELETE FROM productos WHERE id_producto = ?');
        $stmt->execute([$idProducto]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Producto inexistente.');
        }

        audit_event(
            'eliminacion',
            'productos',
            (int) (current_user()['id'] ?? 0),
            'Eliminacion de producto ' . $producto['codigo'] . ' - ' . $producto['nombre'],
            'productos',
            $idProducto
        );

        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        throw $exception;
    }
}

function producto_procesar_post(PDO $db): array
{
    if (!request_method_is('POST')) {
        return ['type' => '', 'message' => ''];
    }

    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'crear' || $action === 'editar') {
            $idProducto = (int) ($_POST['id_producto'] ?? 0);
            $data = producto_post_data($_POST);
            if ($action === 'crear') {
                $data['codigo'] = producto_siguiente_codigo($db);
            }

            $errors = producto_validar($db, $data, $action === 'editar' ? $idProducto : 0, $action === 'crear');

            if ($action === 'editar' && $idProducto <= 0) {
                $errors[] = 'Producto invalido.';
            }

            if ($errors) {
                return ['type' => 'danger', 'message' => implode(' ', $errors)];
            }

            if ($action === 'crear') {
                producto_crear($db, $data);
                return ['type' => 'success', 'message' => 'Producto creado correctamente.'];
            }

            producto_editar($db, $idProducto, $data);
            return ['type' => 'success', 'message' => 'Producto actualizado correctamente.'];
        }

        if ($action === 'eliminar') {
            $idProducto = (int) ($_POST['id_producto'] ?? 0);
            if ($idProducto <= 0) {
                return ['type' => 'danger', 'message' => 'No se pudo eliminar el producto.'];
            }

            producto_eliminar($db, $idProducto);
            return ['type' => 'success', 'message' => 'Producto eliminado correctamente.'];
        }
    } catch (PDOException $exception) {
        if ($action === 'eliminar') {
            return ['type' => 'danger', 'message' => 'No se pudo eliminar el producto.'];
        }

        if ($exception->getCode() === '23000') {
            return ['type' => 'danger', 'message' => 'No se pudo guardar: codigo duplicado o relacion invalida.'];
        }

        return ['type' => 'danger', 'message' => 'No se pudo completar la operacion en la base de datos.'];
    } catch (Throwable $exception) {
        if ($action === 'eliminar') {
            return ['type' => 'danger', 'message' => 'No se pudo eliminar el producto.'];
        }

        return ['type' => 'danger', 'message' => $exception->getMessage() ?: 'No se pudo completar la operacion.'];
    }

    return ['type' => '', 'message' => ''];
}

function producto_filtros(array $source): array
{
    return [
        'q' => trim((string) ($source['q'] ?? '')),
        'categoria' => (int) ($source['categoria'] ?? 0),
        'almacen' => (int) ($source['almacen'] ?? 0),
        'estado' => (int) ($source['estado'] ?? 0),
    ];
}

function productos_listar(PDO $db, array $filters): array
{
    $where = [];
    $params = [];

    if ($filters['q'] !== '') {
        $where[] = '(p.codigo LIKE ? OR p.nombre LIKE ?)';
        $params[] = '%' . $filters['q'] . '%';
        $params[] = '%' . $filters['q'] . '%';
    }

    if ($filters['categoria'] > 0) {
        $where[] = 'p.id_categoria = ?';
        $params[] = $filters['categoria'];
    }

    if ($filters['estado'] > 0) {
        $where[] = 'p.id_estado_producto = ?';
        $params[] = $filters['estado'];
    }

    if ($filters['almacen'] > 0) {
        $where[] = 'EXISTS (SELECT 1 FROM stock_por_almacen sf WHERE sf.id_producto = p.id_producto AND sf.id_almacen = ?)';
        $params[] = $filters['almacen'];
    }

    $sql = "SELECT
                p.*,
                c.nombre AS categoria_nombre,
                m.nombre AS marca_nombre,
                u.nombre AS unidad_nombre,
                u.abreviatura AS unidad_abreviatura,
                ep.nombre_estado,
                COALESCE(st.stock_total, 0) AS stock_total,
                sa.id_almacen AS id_almacen,
                a.nombre AS almacen_nombre,
                COALESCE(sa.stock_actual, 0) AS stock_almacen
            FROM productos p
            LEFT JOIN categorias_producto c ON c.id_categoria = p.id_categoria
            LEFT JOIN marcas m ON m.id_marca = p.id_marca
            LEFT JOIN unidades_medida u ON u.id_unidad_medida = p.id_unidad_medida
            LEFT JOIN estados_producto ep ON ep.id_estado_producto = p.id_estado_producto
            LEFT JOIN (
                SELECT id_producto, SUM(stock_actual) AS stock_total
                FROM stock_por_almacen
                GROUP BY id_producto
            ) st ON st.id_producto = p.id_producto
            LEFT JOIN stock_por_almacen sa ON sa.id_stock = (
                SELECT s2.id_stock
                FROM stock_por_almacen s2
                WHERE s2.id_producto = p.id_producto
                ORDER BY CASE WHEN ? > 0 AND s2.id_almacen = ? THEN 0 ELSE 1 END, s2.id_almacen
                LIMIT 1
            )
            LEFT JOIN almacenes a ON a.id_almacen = sa.id_almacen";

    array_unshift($params, $filters['almacen'], $filters['almacen']);

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY p.nombre ASC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function productos_stock_por_almacen(PDO $db, array $productos): array
{
    $ids = array_map(static fn (array $producto): int => (int) $producto['id_producto'], $productos);
    $ids = array_values(array_filter($ids));

    if (!$ids) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare(
        "SELECT s.id_producto, s.id_almacen, a.nombre AS almacen_nombre, s.stock_actual, s.stock_reservado
         FROM stock_por_almacen s
         INNER JOIN almacenes a ON a.id_almacen = s.id_almacen
         WHERE s.id_producto IN ({$placeholders})
         ORDER BY a.nombre"
    );
    $stmt->execute($ids);

    $stocks = [];
    foreach ($stmt->fetchAll() as $row) {
        $stocks[(int) $row['id_producto']][] = $row;
    }

    return $stocks;
}

function selected($current, $expected): string
{
    return (string) $current === (string) $expected ? 'selected' : '';
}
