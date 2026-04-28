<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_auth();

$modulo_activo = 'stock';
$submodulo_activo = 'ingreso_mercaderia';
$breadcrumb = 'Ingreso de Mercadería';
$breadcrumb_link = app_url('dashboard.php');

$error = '';
$success = '';

$proveedores = [];
try {
    $stmt = db()->query("SELECT * FROM proveedores WHERE estado = 'activo' ORDER BY razon_social");
    $proveedores = $stmt->fetchAll();
} catch (Exception $e) {}

$productos = [];
try {
    $stmt = db()->query("SELECT * FROM productos WHERE estado = 'activo' ORDER BY nombre");
    $productos = $stmt->fetchAll();
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
    $id_proveedor = intval($_POST['id_proveedor'] ?? 0);
    $numero_factura = $_POST['numero_factura'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    $detalle = $_POST['detalle'] ?? [];
    
    if ($id_proveedor > 0 && count($detalle) > 0) {
        try {
            db()->beginTransaction();
            $stmt = db()->prepare("INSERT INTO ingresos_mercaderia (id_proveedor, id_usuario, numero_factura, fecha, observaciones, estado) VALUES (?, ?, ?, CURDATE(), ?, 'confirmado')");
            $stmt->execute([$id_proveedor, current_user()['id'], $numero_factura, $observaciones]);
            $id_ingreso = db()->lastInsertId();
            
            foreach ($detalle as $item) {
                $id_producto = intval($item['id_producto']);
                $cantidad = intval($item['cantidad']);
                $precio = floatval($item['precio']);
                
                if ($id_producto > 0 && $cantidad > 0) {
                    $stmt = db()->prepare("SELECT stock_actual FROM productos WHERE id_producto = ?");
                    $stmt->execute([$id_producto]);
                    $prod = $stmt->fetch();
                    $stock_anterior = intval($prod['stock_actual'] ?? 0);
                    $stock_nuevo = $stock_anterior + $cantidad;

                    $stmt = db()->prepare("INSERT INTO detalle_ingreso (id_ingreso, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id_ingreso, $id_producto, $cantidad, $precio]);

                    $stmt = db()->prepare("UPDATE productos SET stock_actual = stock_actual + ? WHERE id_producto = ?");
                    $stmt->execute([$cantidad, $id_producto]);

                    // Actualizar stock por almacén (asume almacén principal = 1)
                    $stmt = db()->prepare("SELECT stock_actual FROM stock_por_almacen WHERE id_producto = ? AND id_almacen = 1");
                    $stmt->execute([$id_producto]);
                    $sa = $stmt->fetch();
                    if ($sa) {
                        $stock_alm_anterior = intval($sa['stock_actual']);
                        $stock_alm_nuevo = $stock_alm_anterior + $cantidad;
                        $stmt = db()->prepare("UPDATE stock_por_almacen SET stock_actual = ? WHERE id_producto = ? AND id_almacen = 1");
                        $stmt->execute([$stock_alm_nuevo, $id_producto]);
                    } else {
                        $stock_alm_anterior = 0;
                        $stock_alm_nuevo = $cantidad;
                        $stmt = db()->prepare("INSERT INTO stock_por_almacen (id_producto, id_almacen, stock_actual) VALUES (?, 1, ?)");
                        $stmt->execute([$id_producto, $stock_alm_nuevo]);
                    }

                    // Registrar movimiento de stock
                    $stmt = db()->prepare("INSERT INTO movimientos_stock (id_producto, id_usuario, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, referencia) VALUES (?, ?, 'ingreso', ?, ?, ?, ?, ?)");
                    $motivo = 'Ingreso de mercadería';
                    $referencia = 'ingreso:' . $id_ingreso;
                    $stmt->execute([$id_producto, current_user()['id'], $cantidad, $stock_anterior, $stock_nuevo, $motivo, $referencia]);
                }
            }
            db()->commit();
            $success = 'Ingreso de mercadería registrado correctamente.';
        } catch (Exception $e) {
            db()->rollBack();
            $error = 'Error al registrar ingreso: ' . $e->getMessage();
        }
    } else {
        $error = 'Debe agregar al menos un producto al detalle.';
    }
}

$ingresos = [];
try {
    $stmt = db()->query("SELECT i.*, p.razon_social, u.nombre_completo FROM ingresos_mercaderia i LEFT JOIN proveedores p ON i.id_proveedor = p.id_proveedor JOIN usuarios u ON i.id_usuario = u.id_usuario ORDER BY i.fecha DESC LIMIT 20");
    $ingresos = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Error al cargar datos iniciales: ' . $e->getMessage();
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
                    <h2 class="page-title">
                        <i class="fas fa-truck-loading"></i>
                        Ingreso de Mercadería
                    </h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoIngresoModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Ingreso
                    </button>
                </div>
                
                <?php if (!empty($error)): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?= e($error); ?></div><?php endif; ?>
                <?php if (!empty($success)): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><?= e($success); ?></div><?php endif; ?>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr><th>ID</th><th>Fecha</th><th>Proveedor</th><th>Factura</th><th>Usuario</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ingresos as $i): ?>
                            <tr>
                                <td><?= $i['id_ingreso']; ?></td>
                                <td><?= date('d/m/Y', strtotime($i['fecha'])); ?></td>
                                <td><?= e($i['razon_social'] ?? '-'); ?></td>
                                <td><?= e($i['numero_factura'] ?? '-'); ?></td>
                                <td><?= e($i['nombre_completo']); ?></td>
                                <td><span class="badge bg-success"><?= $i['estado']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($ingresos)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No hay ingresos registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="nuevoIngresoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Ingreso de Mercadería</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="ingresoForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Proveedor *</label>
                                    <select name="id_proveedor" class="form-control" required>
                                        <option value="">Seleccionar...</option>
                                            <?php foreach ($proveedores as $p): ?>
                                            <option value="<?= $p['id_proveedor']; ?>"><?= e($p['razon_social']); ?></option>
                                            <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Número de Factura</label>
                                    <input type="text" name="numero_factura" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <hr>
                        <h6>Detalle de Productos</h6>
                        
                        <div class="mb-3">
                            <select id="productoSelect" class="form-control">
                                <option value="">Seleccionar producto...</option>
                                <?php foreach ($productos as $p): ?>
                                <option value="<?= $p['id_producto']; ?>" data-precio="<?= $p['precio']; ?>">
                                    <?= e($p['codigo'] . ' - ' . $p['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <input type="number" id="cantidadInput" class="form-control" placeholder="Cantidad" min="1">
                            </div>
                            <div class="col-md-4">
                                <input type="number" id="precioInput" class="form-control" placeholder="Precio unitario" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-success" onclick="agregarProducto()">
                                    <i class="fas fa-plus me-1"></i>Agregar
                                </button>
                            </div>
                        </div>
                        
                        <table class="table table-sm" id="detalleTable">
                            <thead>
                                <tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th><th></th></tr>
                            </thead>
                            <tbody id="detalleBody"></tbody>
                            <tfoot>
                                <tr><th colspan="3" class="text-end">Total:</th><th id="totalGeneral">$0.00</th><th></th></tr>
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
        const cantidad = parseInt(document.getElementById('cantidadInput').value);
        const precio = parseFloat(document.getElementById('precioInput').value);
        
        if (!select.value || !cantidad || !precio) {
            alert('Complete todos los campos del producto');
            return;
        }
        
        detalle.push({ id_producto: select.value, cantidad: cantidad, precio: precio });
        renderDetalle();
        
        select.value = '';
        document.getElementById('cantidadInput').value = '';
        document.getElementById('precioInput').value = '';
    }
    
    function eliminarProducto(index) {
        detalle.splice(index, 1);
        renderDetalle();
    }
    
    function renderDetalle() {
        const tbody = document.getElementById('detalleBody');
        tbody.innerHTML = '';
        let total = 0;
        
        detalle.forEach((item, index) => {
            const subtotal = item.cantidad * item.precio;
            total += subtotal;
            
            const select = document.getElementById('productoSelect');
            let nombre = 'Producto ID: ' + item.id_producto;
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value == item.id_producto) {
                    nombre = select.options[i].text;
                    break;
                }
            }
            
            tbody.innerHTML += `
                <tr>
                    <td>${nombre}</td>
                    <td>${item.cantidad}</td>
                    <td>$${item.precio.toFixed(2)}</td>
                    <td>$${subtotal.toFixed(2)}</td>
                    <td><button type="button" class="btn btn-sm btn-danger" onclick="eliminarProducto(${index})"><i class="fas fa-times"></i></button></td>
                </tr>
                <input type="hidden" name="detalle[${index}][id_producto]" value="${item.id_producto}">
                <input type="hidden" name="detalle[${index}][cantidad]" value="${item.cantidad}">
                <input type="hidden" name="detalle[${index}][precio]" value="${item.precio}">
            `;
        });
        
        document.getElementById('totalGeneral').textContent = '$' + total.toFixed(2);
    }
    
    function validarDetalle() {
        if (detalle.length === 0) {
            alert('Debe agregar al menos un producto al detalle');
            return false;
        }
        return true;
    }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
