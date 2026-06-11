<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_module_access('ingreso_mercaderia');

$modulo_activo = 'stock';
$submodulo_activo = 'ingreso_mercaderia';
$breadcrumb = 'Ingreso de Mercadería';
$breadcrumb_link = app_url('dashboard.php');

$db = db();
$error = '';
$success = '';
$userId = (int) (current_user()['id'] ?? 0);

$proveedores = [];
$almacenes = [];
$productos = [];
$ingresos = [];

try {
    $proveedores = $db->query("SELECT id_proveedor, razon_social FROM proveedores WHERE estado = 'activo' ORDER BY razon_social")->fetchAll();
    $almacenes = $db->query('SELECT id_almacen, nombre FROM almacenes WHERE estado = 1 ORDER BY nombre')->fetchAll();
    $productos = $db->query("SELECT id_producto, codigo, nombre, precio FROM productos WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
} catch (Throwable $exception) {
    $error = 'No se pudieron cargar los datos iniciales.';
}

if (request_method_is('POST') && ($_POST['action'] ?? '') === 'crear') {
    $idProveedor = (int) ($_POST['id_proveedor'] ?? 0);
    $idAlmacen = (int) ($_POST['id_almacen'] ?? 0);
    $numeroFactura = substr(trim((string) ($_POST['numero_factura'] ?? '')), 0, 50);
    $observaciones = trim((string) ($_POST['observaciones'] ?? ''));
    $detalle = is_array($_POST['detalle'] ?? null) ? $_POST['detalle'] : [];

    try {
        if ($userId <= 0) {
            throw new RuntimeException('No se encontró el usuario logueado.');
        }

        if ($idProveedor <= 0 || $idAlmacen <= 0 || !$detalle) {
            throw new RuntimeException('Debe seleccionar proveedor, almacén y al menos un producto.');
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM proveedores WHERE id_proveedor = ? AND estado = 'activo'");
        $stmt->execute([$idProveedor]);
        if ((int) $stmt->fetchColumn() === 0) {
            throw new RuntimeException('Proveedor inválido o inactivo.');
        }

        $stmt = $db->prepare('SELECT COUNT(*) FROM almacenes WHERE id_almacen = ? AND estado = 1');
        $stmt->execute([$idAlmacen]);
        if ((int) $stmt->fetchColumn() === 0) {
            throw new RuntimeException('Almacén inválido o inactivo.');
        }

        $items = [];
        foreach ($detalle as $item) {
            if (!is_array($item)) {
                continue;
            }

            $idProducto = (int) ($item['id_producto'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? 0);
            $precio = (float) ($item['precio'] ?? -1);
            $observacion = substr(trim((string) ($item['observacion'] ?? '')), 0, 255);

            if ($idProducto <= 0 || $cantidad <= 0 || $precio < 0) {
                throw new RuntimeException('El detalle contiene productos, cantidades o precios inválidos.');
            }

            $items[] = [
                'id_producto' => $idProducto,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'observacion' => $observacion,
            ];
        }

        if (!$items) {
            throw new RuntimeException('Debe agregar al menos un producto válido al detalle.');
        }

        $db->beginTransaction();

        $stmt = $db->prepare(
            "INSERT INTO ingresos_mercaderia (id_proveedor, id_usuario, id_almacen, numero_factura, fecha, observaciones, estado)
             VALUES (?, ?, ?, ?, CURDATE(), ?, 'confirmado')"
        );
        $stmt->execute([$idProveedor, $userId, $idAlmacen, $numeroFactura, $observaciones]);
        $idIngreso = (int) $db->lastInsertId();

        foreach ($items as $item) {
            $stmt = $db->prepare("SELECT id_producto FROM productos WHERE id_producto = ? AND estado = 'activo' FOR UPDATE");
            $stmt->execute([$item['id_producto']]);
            if (!$stmt->fetch()) {
                throw new RuntimeException('Producto inválido o inactivo en el detalle.');
            }

            $stmt = $db->prepare(
                'SELECT stock_actual
                 FROM stock_por_almacen
                 WHERE id_producto = ? AND id_almacen = ?
                 FOR UPDATE'
            );
            $stmt->execute([$item['id_producto'], $idAlmacen]);
            $stockRow = $stmt->fetch();
            $stockAnterior = (int) ($stockRow['stock_actual'] ?? 0);
            $stockNuevo = $stockAnterior + $item['cantidad'];

            $stmt = $db->prepare('INSERT INTO detalle_ingreso (id_ingreso, id_producto, cantidad, precio_unitario, observacion) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$idIngreso, $item['id_producto'], $item['cantidad'], $item['precio'], $item['observacion']]);

            if ($stockRow) {
                $stmt = $db->prepare('UPDATE stock_por_almacen SET stock_actual = ? WHERE id_producto = ? AND id_almacen = ?');
                $stmt->execute([$stockNuevo, $item['id_producto'], $idAlmacen]);
            } else {
                $stmt = $db->prepare('INSERT INTO stock_por_almacen (id_producto, id_almacen, stock_actual, stock_reservado) VALUES (?, ?, ?, 0)');
                $stmt->execute([$item['id_producto'], $idAlmacen, $stockNuevo]);
            }

            $stmt = $db->prepare(
                'UPDATE productos
                 SET stock_actual = (SELECT COALESCE(SUM(s.stock_actual), 0) FROM stock_por_almacen s WHERE s.id_producto = ?)
                 WHERE id_producto = ?'
            );
            $stmt->execute([$item['id_producto'], $item['id_producto']]);

            $motivo = $item['observacion'] !== '' ? $item['observacion'] : 'Ingreso de mercadería';
            $stmt = $db->prepare(
                "INSERT INTO movimientos_stock
                    (id_producto, id_usuario, id_almacen, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, referencia, entidad_origen, id_entidad_origen)
                 VALUES (?, ?, ?, 'ingreso', ?, ?, ?, ?, ?, 'ingreso', ?)"
            );
            $stmt->execute([
                $item['id_producto'],
                $userId,
                $idAlmacen,
                $item['cantidad'],
                $stockAnterior,
                $stockNuevo,
                $motivo,
                'ingreso:' . $idIngreso,
                $idIngreso,
            ]);
        }

        audit_event('ingreso_mercaderia', 'stock', $userId, 'Ingreso #' . $idIngreso . ' confirmado.');
        $db->commit();
        $success = 'Ingreso de mercadería registrado correctamente.';
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = $exception->getMessage();
    }
}

try {
    $stmt = $db->query(
        "SELECT i.id_ingreso,
                i.fecha,
                i.numero_factura,
                i.estado,
                pr.razon_social,
                a.nombre AS almacen_nombre,
                u.nombre_completo,
                COUNT(d.id_detalle) AS items,
                COALESCE(SUM(d.cantidad * d.precio_unitario), 0) AS total
         FROM ingresos_mercaderia i
         LEFT JOIN proveedores pr ON pr.id_proveedor = i.id_proveedor
         LEFT JOIN almacenes a ON a.id_almacen = i.id_almacen
         INNER JOIN usuarios u ON u.id_usuario = i.id_usuario
         LEFT JOIN detalle_ingreso d ON d.id_ingreso = i.id_ingreso
         GROUP BY i.id_ingreso, i.fecha, i.numero_factura, i.estado, pr.razon_social, a.nombre, u.nombre_completo
         ORDER BY i.created_at DESC
         LIMIT 20"
    );
    $ingresos = $stmt->fetchAll();
} catch (Throwable $exception) {
    if ($error === '') {
        $error = 'No se pudo cargar el listado de ingresos.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingreso de Mercadería - Bendito Jugador</title>
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
                    <h2 class="page-title"><i class="fas fa-truck-loading"></i>Ingreso de Mercadería</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoIngresoModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Ingreso
                    </button>
                </div>

                <?php if ($error !== ''): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error); ?></div><?php endif; ?>
                <?php if ($success !== ''): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= e($success); ?></div><?php endif; ?>

                <div class="table-container">
                    <table class="table align-middle">
                        <thead>
                            <tr><th>ID</th><th>Fecha</th><th>Proveedor</th><th>Almacén</th><th>Factura</th><th>Items</th><th>Total</th><th>Usuario</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ingresos as $ingreso): ?>
                                <tr>
                                    <td><?= (int) $ingreso['id_ingreso']; ?></td>
                                    <td><?= e(date('d/m/Y', strtotime((string) $ingreso['fecha']))); ?></td>
                                    <td><?= e($ingreso['razon_social'] ?? '-'); ?></td>
                                    <td><?= e($ingreso['almacen_nombre'] ?? '-'); ?></td>
                                    <td><?= e($ingreso['numero_factura'] ?: '-'); ?></td>
                                    <td><?= (int) $ingreso['items']; ?></td>
                                    <td>$<?= number_format((float) $ingreso['total'], 2, ',', '.'); ?></td>
                                    <td><?= e($ingreso['nombre_completo']); ?></td>
                                    <td><span class="badge bg-success"><?= e($ingreso['estado']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$ingresos): ?>
                                <tr><td colspan="9" class="text-center text-muted">No hay ingresos registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="nuevoIngresoModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Ingreso de Mercadería</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="ingresoForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Proveedor *</label>
                                <select name="id_proveedor" class="form-control" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($proveedores as $proveedor): ?>
                                        <option value="<?= (int) $proveedor['id_proveedor']; ?>"><?= e($proveedor['razon_social']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Almacén destino *</label>
                                <select name="id_almacen" class="form-control" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($almacenes as $almacen): ?>
                                        <option value="<?= (int) $almacen['id_almacen']; ?>"><?= e($almacen['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Número de factura</label>
                                <input type="text" name="numero_factura" class="form-control" maxlength="50">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observación general</label>
                                <textarea name="observaciones" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <hr>
                        <h6>Detalle de productos</h6>

                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Producto</label>
                                <select id="productoSelect" class="form-control">
                                    <option value="">Seleccionar producto...</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option value="<?= (int) $producto['id_producto']; ?>" data-precio="<?= e((string) $producto['precio']); ?>">
                                            <?= e($producto['codigo'] . ' - ' . $producto['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Cantidad</label>
                                <input type="number" id="cantidadInput" class="form-control" min="1" step="1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Precio compra</label>
                                <input type="number" id="precioInput" class="form-control" min="0" step="0.01">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Observación</label>
                                <input type="text" id="observacionInput" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-1 d-grid">
                                <button type="button" class="btn btn-success" onclick="agregarProducto()"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>

                        <table class="table table-sm align-middle" id="detalleTable">
                            <thead>
                                <tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Observación</th><th>Subtotal</th><th></th></tr>
                            </thead>
                            <tbody id="detalleBody"></tbody>
                            <tfoot>
                                <tr><th colspan="4" class="text-end">Total:</th><th id="totalGeneral">$0,00</th><th></th></tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" onclick="return validarDetalle()">Registrar Ingreso</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    let detalle = [];

    function agregarProducto() {
        const select = document.getElementById('productoSelect');
        const option = select.options[select.selectedIndex];
        const cantidad = parseInt(document.getElementById('cantidadInput').value, 10);
        const precio = parseFloat(document.getElementById('precioInput').value);
        const observacion = document.getElementById('observacionInput').value.trim();

        if (!select.value || !Number.isInteger(cantidad) || cantidad <= 0 || Number.isNaN(precio) || precio < 0) {
            alert('Complete producto, cantidad positiva y precio válido.');
            return;
        }

        detalle.push({
            id_producto: select.value,
            nombre: option.text,
            cantidad: cantidad,
            precio: precio,
            observacion: observacion
        });

        renderDetalle();
        select.value = '';
        document.getElementById('cantidadInput').value = '';
        document.getElementById('precioInput').value = '';
        document.getElementById('observacionInput').value = '';
    }

    function eliminarProducto(index) {
        detalle.splice(index, 1);
        renderDetalle();
    }

    function crearHidden(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        return input;
    }

    function renderDetalle() {
        const tbody = document.getElementById('detalleBody');
        tbody.innerHTML = '';
        let total = 0;

        detalle.forEach((item, index) => {
            const subtotal = item.cantidad * item.precio;
            total += subtotal;

            const row = document.createElement('tr');
            const productCell = document.createElement('td');
            const quantityCell = document.createElement('td');
            const priceCell = document.createElement('td');
            const observationCell = document.createElement('td');
            const subtotalCell = document.createElement('td');
            const actionCell = document.createElement('td');

            productCell.textContent = item.nombre;
            quantityCell.textContent = item.cantidad;
            priceCell.textContent = '$' + item.precio.toFixed(2);
            observationCell.textContent = item.observacion || '-';
            subtotalCell.textContent = '$' + subtotal.toFixed(2);

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-danger';
            button.innerHTML = '<i class="fas fa-times"></i>';
            button.onclick = () => eliminarProducto(index);
            actionCell.appendChild(button);

            row.append(productCell, quantityCell, priceCell, observationCell, subtotalCell, actionCell);
            row.appendChild(crearHidden(`detalle[${index}][id_producto]`, item.id_producto));
            row.appendChild(crearHidden(`detalle[${index}][cantidad]`, item.cantidad));
            row.appendChild(crearHidden(`detalle[${index}][precio]`, item.precio));
            row.appendChild(crearHidden(`detalle[${index}][observacion]`, item.observacion));
            tbody.appendChild(row);
        });

        document.getElementById('totalGeneral').textContent = '$' + total.toFixed(2);
    }

    function validarDetalle() {
        if (detalle.length === 0) {
            alert('Debe agregar al menos un producto al detalle.');
            return false;
        }
        return true;
    }

    document.getElementById('productoSelect').addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        const precio = option ? option.getAttribute('data-precio') : '';
        if (precio !== null && precio !== '') {
            document.getElementById('precioInput').value = precio;
        }
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
