<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_auth();

$modulo_activo = 'stock';
$submodulo_activo = 'productos';
$breadcrumb = 'Productos';
$breadcrumb_link = app_url('dashboard.php');

$productos = [];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear') {
        $codigo = $_POST['codigo'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $precio = floatval($_POST['precio'] ?? 0);
        $stock_actual = intval($_POST['stock_actual'] ?? 0);
        $stock_minimo = intval($_POST['stock_minimo'] ?? 0);
        $categoria = $_POST['categoria'] ?? '';
        $unidad_medida = $_POST['unidad_medida'] ?? '';
        
        if (empty($codigo) || empty($nombre)) {
            $error = 'Código y nombre son obligatorios.';
        } else {
            try {
                $stmt = db()->prepare("INSERT INTO productos (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, categoria, unidad_medida, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'activo')");
                $stmt->execute([$codigo, $nombre, $descripcion, $precio, $stock_actual, $stock_minimo, $categoria, $unidad_medida]);
                $id_producto = db()->lastInsertId();

                // Si tiene stock inicial, registrar en stock_por_almacen (almacén principal = 1) y crear movimiento
                if ($stock_actual > 0) {
                    $stmt = db()->prepare("INSERT INTO stock_por_almacen (id_producto, id_almacen, stock_actual) VALUES (?, 1, ?)");
                    $stmt->execute([$id_producto, $stock_actual]);

                    $stmt = db()->prepare("INSERT INTO movimientos_stock (id_producto, id_usuario, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, referencia) VALUES (?, ?, 'ingreso', ?, ?, ?, ?, ?)");
                    $motivo = 'Stock inicial al crear producto';
                    $referencia = 'producto:' . $id_producto;
                    $stmt->execute([$id_producto, current_user()['id'], $stock_actual, 0, $stock_actual, $motivo, $referencia]);
                }

                $success = 'Producto creado correctamente.';
            } catch (Exception $e) {
                $error = 'Error al crear. ' . $e->getMessage();
            }
        }
    }

    if ($action === 'editar') {
        $id = intval($_POST['id'] ?? 0);
        $codigo = $_POST['codigo'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $precio = floatval($_POST['precio'] ?? 0);
        $stock_minimo = intval($_POST['stock_minimo'] ?? 0);
        $categoria = $_POST['categoria'] ?? '';
        $unidad_medida = $_POST['unidad_medida'] ?? '';
        
        if ($id > 0 && !empty($codigo) && !empty($nombre)) {
            $stmt = db()->prepare("UPDATE productos SET codigo=?, nombre=?, descripcion=?, precio=?, stock_minimo=?, categoria=?, unidad_medida=? WHERE id_producto=?");
            $stmt->execute([$codigo, $nombre, $descripcion, $precio, $stock_minimo, $categoria, $unidad_medida, $id]);
            $success = 'Producto actualizado correctamente.';
        }
    }

    if ($action === 'eliminar') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = db()->prepare("UPDATE productos SET estado = 'inactivo' WHERE id_producto = ?");
            $stmt->execute([$id]);
            $success = 'Producto eliminado correctamente.';
        }
    }

    if ($action === 'actualizar_stock') {
        $id = intval($_POST['id'] ?? 0);
        $nuevo_stock = intval($_POST['stock'] ?? 0);
        
        if ($id > 0) {
            // Obtener stock anterior
            $stmt = db()->prepare("SELECT stock_actual FROM productos WHERE id_producto = ?");
            $stmt->execute([$id]);
            $prod = $stmt->fetch();
            $stock_anterior = intval($prod['stock_actual'] ?? 0);

            // Actualizar producto
            $stmt = db()->prepare("UPDATE productos SET stock_actual = ? WHERE id_producto = ?");
            $stmt->execute([$nuevo_stock, $id]);

            // Actualizar stock por almacén (asume almacén principal = 1)
            $stmt = db()->prepare("SELECT stock_actual FROM stock_por_almacen WHERE id_producto = ? AND id_almacen = 1");
            $stmt->execute([$id]);
            $sa = $stmt->fetch();
            if ($sa) {
                $stock_alm_anterior = intval($sa['stock_actual']);
                $stock_alm_nuevo = $nuevo_stock;
                $stmt = db()->prepare("UPDATE stock_por_almacen SET stock_actual = ? WHERE id_producto = ? AND id_almacen = 1");
                $stmt->execute([$stock_alm_nuevo, $id]);
            } else {
                $stock_alm_anterior = 0;
                $stock_alm_nuevo = $nuevo_stock;
                $stmt = db()->prepare("INSERT INTO stock_por_almacen (id_producto, id_almacen, stock_actual) VALUES (?, 1, ?)");
                $stmt->execute([$id, $stock_alm_nuevo]);
            }

            // Registrar movimiento de ajuste manual
            $tipo = $nuevo_stock >= $stock_anterior ? 'ajuste_positivo' : 'ajuste_negativo';
            $cantidad_mov = abs($nuevo_stock - $stock_anterior);
            $stmt = db()->prepare("INSERT INTO movimientos_stock (id_producto, id_usuario, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, referencia) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $motivo = 'Actualización manual';
            $referencia = 'manual:' . $id;
            $stmt->execute([$id, current_user()['id'], $tipo, $cantidad_mov, $stock_anterior, $nuevo_stock, $motivo, $referencia]);

            $success = 'Stock actualizado.';
        }
    }
}

try {
    $stmt = db()->query("SELECT * FROM productos WHERE estado = 'activo' ORDER BY nombre");
    $productos = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Error al cargar productos.';
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
                        Gestión de Productos
                    </h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoProductoModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Producto
                    </button>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= e($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= e($success); ?>
                    </div>
                <?php endif; ?>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Stock Mín.</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $p): ?>
                            <tr>
                                <td><?= e($p['codigo']); ?></td>
                                <td><?= e($p['nombre']); ?></td>
                                <td><?= e($p['categoria'] ?? '-'); ?></td>
                                <td>$<?= number_format($p['precio'], 2); ?></td>
                                <td>
                                    <?php if ($p['stock_actual'] <= $p['stock_minimo']): ?>
                                        <span class="badge" style="background: var(--danger); color: white;"><?= $p['stock_actual']; ?></span>
                                    <?php else: ?>
                                        <?= $p['stock_actual']; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= $p['stock_minimo']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editarModal<?= $p['id_producto']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#stockModal<?= $p['id_producto']; ?>">
                                        <i class="fas fa-plus-minus"></i>
                                    </button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="eliminar">
                                        <input type="hidden" name="id" value="<?= $p['id_producto']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar producto?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>

                            <div class="modal fade" id="editarModal<?= $p['id_producto']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar Producto</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="editar">
                                                <input type="hidden" name="id" value="<?= $p['id_producto']; ?>">
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Código</label>
                                                    <input type="text" name="codigo" class="form-control" value="<?= e($p['codigo']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Nombre</label>
                                                    <input type="text" name="nombre" class="form-control" value="<?= e($p['nombre']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Descripción</label>
                                                    <textarea name="descripcion" class="form-control" rows="2"><?= e($p['descripcion'] ?? ''); ?></textarea>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Precio</label>
                                                            <input type="number" name="precio" class="form-control" step="0.01" value="<?= $p['precio']; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Stock Mínimo</label>
                                                            <input type="number" name="stock_minimo" class="form-control" value="<?= $p['stock_minimo']; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Categoría</label>
                                                            <input type="text" name="categoria" class="form-control" value="<?= e($p['categoria'] ?? ''); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Unidad</label>
                                                            <select name="unidad_medida" class="form-control">
                                                                <option value="unidad" <?= ($p['unidad_medida'] ?? '') == 'unidad' ? 'selected' : ''; ?>>Unidad</option>
                                                                <option value="par" <?= ($p['unidad_medida'] ?? '') == 'par' ? 'selected' : ''; ?>>Par</option>
                                                                <option value="kit" <?= ($p['unidad_medida'] ?? '') == 'kit' ? 'selected' : ''; ?>>Kit</option>
                                                                <option value="kg" <?= ($p['unidad_medida'] ?? '') == 'kg' ? 'selected' : ''; ?>>Kilogramo</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="stockModal<?= $p['id_producto']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Actualizar Stock - <?= e($p['nombre']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="actualizar_stock">
                                                <input type="hidden" name="id" value="<?= $p['id_producto']; ?>">
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Stock Actual</label>
                                                    <input type="number" name="stock" class="form-control" value="<?= $p['stock_actual']; ?>" required>
                                                </div>
                                                <p class="text-muted">Stock mínimo: <?= $p['stock_minimo']; ?></p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-success">Actualizar Stock</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No hay productos registrados.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="nuevoProductoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">
                        
                        <div class="form-group">
                            <label class="form-label">Código *</label>
                            <input type="text" name="codigo" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Precio</label>
                                    <input type="number" name="precio" class="form-control" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Categoría</label>
                                    <input type="text" name="categoria" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Stock Inicial</label>
                                    <input type="number" name="stock_actual" class="form-control" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Stock Mínimo</label>
                                    <input type="number" name="stock_minimo" class="form-control" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Unidad de Medida</label>
                            <select name="unidad_medida" class="form-control">
                                <option value="unidad">Unidad</option>
                                <option value="par">Par</option>
                                <option value="kit">Kit</option>
                                <option value="kg">Kilogramo</option>
                            </select>
                        </div>
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
