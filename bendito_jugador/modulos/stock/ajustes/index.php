<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_auth();

$modulo_activo = 'stock';
$submodulo_activo = 'ajustes';
$breadcrumb = 'Ajuste de Inventario';
$breadcrumb_link = app_url('dashboard.php');

$error = '';
$success = '';

$productos = [];
try {
    $stmt = db()->query("SELECT * FROM productos WHERE estado = 'activo' ORDER BY nombre");
    $productos = $stmt->fetchAll();
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
    $id_producto = intval($_POST['id_producto'] ?? 0);
    $stock_nuevo = intval($_POST['stock_nuevo'] ?? 0);
    $motivo = $_POST['motivo'] ?? '';
    
    if ($id_producto > 0 && !empty($motivo)) {
        try {
            db()->beginTransaction();
            
            $stmt = db()->prepare("SELECT stock_actual FROM productos WHERE id_producto = ?");
            $stmt->execute([$id_producto]);
            $producto = $stmt->fetch();
            $stock_anterior = $producto['stock_actual'];
            
            $stmt = db()->prepare("INSERT INTO ajustes_inventario (id_producto, id_usuario, stock_anterior, stock_nuevo, motivo, estado) VALUES (?, ?, ?, ?, ?, 'aprobado')");
            $stmt->execute([$id_producto, current_user()['id'], $stock_anterior, $stock_nuevo, $motivo]);
            
            $stmt = db()->prepare("UPDATE productos SET stock_actual = ? WHERE id_producto = ?");
            $stmt->execute([$stock_nuevo, $id_producto]);
            
            // Actualizar stock por almacén (asume almacén principal = 1)
            $stmt = db()->prepare("SELECT stock_actual FROM stock_por_almacen WHERE id_producto = ? AND id_almacen = 1");
            $stmt->execute([$id_producto]);
            $sa = $stmt->fetch();
            if ($sa) {
                $stock_alm_anterior = intval($sa['stock_actual']);
                $stock_alm_nuevo = $stock_nuevo; // reflejar nuevo stock en almacén principal
                $stmt = db()->prepare("UPDATE stock_por_almacen SET stock_actual = ? WHERE id_producto = ? AND id_almacen = 1");
                $stmt->execute([$stock_alm_nuevo, $id_producto]);
            } else {
                $stock_alm_anterior = 0;
                $stock_alm_nuevo = $stock_nuevo;
                $stmt = db()->prepare("INSERT INTO stock_por_almacen (id_producto, id_almacen, stock_actual) VALUES (?, 1, ?)");
                $stmt->execute([$id_producto, $stock_alm_nuevo]);
            }

            // Registrar movimiento de ajuste
            $tipo = $stock_nuevo >= $stock_anterior ? 'ajuste_positivo' : 'ajuste_negativo';
            $stmt = db()->prepare("INSERT INTO movimientos_stock (id_producto, id_usuario, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, referencia) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $cantidad_mov = abs($stock_nuevo - $stock_anterior);
            $referencia = 'ajuste:' . db()->lastInsertId();
            $stmt->execute([$id_producto, current_user()['id'], $tipo, $cantidad_mov, $stock_anterior, $stock_nuevo, $motivo, $referencia]);
            
            db()->commit();
            $success = 'Ajuste de inventario realizado correctamente.';
        } catch (Exception $e) {
            db()->rollBack();
            $error = 'Error al realizar ajuste: ' . $e->getMessage();
        }
    } else {
        $error = 'Todos los campos son obligatorios.';
    }
}

$ajustes = [];
try { 
    $ajustes = db()->query("SELECT a.*, p.nombre as producto, u.nombre_completo FROM ajustes_inventario a JOIN productos p ON a.id_producto = p.id_producto JOIN usuarios u ON a.id_usuario = u.id_usuario ORDER BY a.created_at DESC LIMIT 20")->fetchAll(); 
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ajuste de Inventario - Bendito Jugador</title>
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
                    <h2 class="page-title"><i class="fas fa-sliders-h"></i>Ajuste de Inventario</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoAjusteModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Ajuste
                    </button>
                </div>
                
                <?php if (!empty($error)): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?= e($error); ?></div><?php endif; ?>
                <?php if (!empty($success)): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><?= e($success); ?></div><?php endif; ?>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr><th>ID</th><th>Fecha</th><th>Producto</th><th>Stock Anterior</th><th>Stock Nuevo</th><th>Diferencia</th><th>Motivo</th><th>Usuario</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ajustes as $a): ?>
                            <tr>
                                <td><?= $a['id_ajuste']; ?></td>
                                <td><?= date('d/m/Y', strtotime($a['created_at'])); ?></td>
                                <td><?= e($a['producto']); ?></td>
                                <td><?= $a['stock_anterior']; ?></td>
                                <td><strong><?= $a['stock_nuevo']; ?></strong></td>
                                <td>
                                    <?php $diff = $a['stock_nuevo'] - $a['stock_anterior']; ?>
                                    <span class="badge bg-<?= $diff >= 0 ? 'success' : 'danger'; ?>"><?= ($diff >= 0 ? '+' : '') . $diff; ?></span>
                                </td>
                                <td><?= e($a['motivo']); ?></td>
                                <td><?= e($a['nombre_completo']); ?></td>
                                <td><span class="badge bg-success"><?= $a['estado']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($ajustes)): ?>
                            <tr><td colspan="9" class="text-center text-muted">No hay ajustes registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="nuevoAjusteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Ajuste de Inventario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">
                        
                        <div class="form-group">
                            <label class="form-label">Producto *</label>
                            <select name="id_producto" class="form-control" id="productoSelect" required onchange="mostrarStockActual()">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($productos as $p): ?>
                                <option value="<?= $p['id_producto']; ?>" data-stock="<?= $p['stock_actual']; ?>"><?= e($p['codigo'] . ' - ' . $p['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Stock Actual</label>
                            <input type="text" id="stockActual" class="form-control" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nuevo Stock *</label>
                            <input type="number" name="stock_nuevo" class="form-control" required min="0">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Motivo del Ajuste *</label>
                            <textarea name="motivo" class="form-control" rows="3" placeholder="Ej: Conteo físico, corrección de error..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Registrar Ajuste</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    function mostrarStockActual() {
        const select = document.getElementById('productoSelect');
        const option = select.options[select.selectedIndex];
        const stock = option.getAttribute('data-stock') || '';
        document.getElementById('stockActual').value = stock;
    }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
