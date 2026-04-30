<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_auth();

$modulo_activo = 'stock';
$submodulo_activo = 'traspasos';
$breadcrumb = 'Traspasos entre Almacenes';
$breadcrumb_link = app_url('dashboard.php');

$almacenes = [];
try { $almacenes = db()->query("SELECT * FROM almacenes WHERE estado = 'activo'")->fetchAll(); } catch (Exception $e) {}

$productos = [];
try { $productos = db()->query("SELECT * FROM productos WHERE estado = 'activo' ORDER BY nombre")->fetchAll(); } catch (Exception $e) {}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
    $id_producto = intval($_POST['id_producto'] ?? 0);
    $id_almacen_origen = intval($_POST['id_almacen_origen'] ?? 0);
    $id_almacen_destino = intval($_POST['id_almacen_destino'] ?? 0);
    $cantidad = intval($_POST['cantidad'] ?? 0);
    
    if ($id_producto > 0 && $id_almacen_origen > 0 && $id_almacen_destino > 0 && $cantidad > 0) {
        if ($id_almacen_origen === $id_almacen_destino) {
            $error = 'El almacén de origen y destino deben ser diferentes.';
        } else {
            try {
                db()->beginTransaction();

                $stmt = db()->prepare("INSERT INTO traspasos (id_producto, id_almacen_origen, id_almacen_destino, cantidad, id_usuario, estado) VALUES (?, ?, ?, ?, ?, 'confirmado')");
                $stmt->execute([$id_producto, $id_almacen_origen, $id_almacen_destino, $cantidad, current_user()['id']]);
                $id_traspaso = db()->lastInsertId();

                // Origen
                $stmt = db()->prepare("SELECT stock_actual FROM stock_por_almacen WHERE id_producto = ? AND id_almacen = ?");
                $stmt->execute([$id_producto, $id_almacen_origen]);
                $origen = $stmt->fetch();
                $stock_origen_ant = intval($origen['stock_actual'] ?? 0);

                if ($stock_origen_ant < $cantidad) {
                    throw new Exception('Stock insuficiente en almacén de origen.');
                }

                $stock_origen_nuevo = $stock_origen_ant - $cantidad;
                $stmt = db()->prepare("UPDATE stock_por_almacen SET stock_actual = ? WHERE id_producto = ? AND id_almacen = ?");
                $stmt->execute([$stock_origen_nuevo, $id_producto, $id_almacen_origen]);

                $stmt = db()->prepare("INSERT INTO movimientos_stock (id_producto, id_usuario, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, referencia) VALUES (?, ?, 'traspaso', ?, ?, ?, ?, ?)");
                $motivo_or = 'Traspaso - salida';
                $ref = 'traspaso:' . $id_traspaso;
                $stmt->execute([$id_producto, current_user()['id'], $cantidad, $stock_origen_ant, $stock_origen_nuevo, $motivo_or, $ref]);

                // Destino
                $stmt = db()->prepare("SELECT stock_actual FROM stock_por_almacen WHERE id_producto = ? AND id_almacen = ?");
                $stmt->execute([$id_producto, $id_almacen_destino]);
                $dest = $stmt->fetch();
                if ($dest) {
                    $stock_dest_ant = intval($dest['stock_actual']);
                    $stock_dest_nuevo = $stock_dest_ant + $cantidad;
                    $stmt = db()->prepare("UPDATE stock_por_almacen SET stock_actual = ? WHERE id_producto = ? AND id_almacen = ?");
                    $stmt->execute([$stock_dest_nuevo, $id_producto, $id_almacen_destino]);
                } else {
                    $stock_dest_ant = 0;
                    $stock_dest_nuevo = $cantidad;
                    $stmt = db()->prepare("INSERT INTO stock_por_almacen (id_producto, id_almacen, stock_actual) VALUES (?, ?, ?)");
                    $stmt->execute([$id_producto, $id_almacen_destino, $stock_dest_nuevo]);
                }

                $stmt = db()->prepare("INSERT INTO movimientos_stock (id_producto, id_usuario, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, referencia) VALUES (?, ?, 'traspaso', ?, ?, ?, ?, ?)");
                $motivo_de = 'Traspaso - ingreso';
                $stmt->execute([$id_producto, current_user()['id'], $cantidad, $stock_dest_ant, $stock_dest_nuevo, $motivo_de, $ref]);

                // Actualizar stock global del producto sumando almacenes
                $stmt = db()->prepare("UPDATE productos SET stock_actual = (SELECT COALESCE(SUM(stock_actual),0) FROM stock_por_almacen WHERE id_producto = ?) WHERE id_producto = ?");
                $stmt->execute([$id_producto, $id_producto]);

                db()->commit();
                $success = 'Traspaso realizado correctamente.';
            } catch (Exception $e) {
                db()->rollBack();
                $error = 'Error al realizar traspaso: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Todos los campos son obligatorios.';
    }
}

$traspasos = [];
try { 
    $traspasos = db()->query("SELECT t.*, p.nombre as producto, ao.nombre as origen, ad.nombre as destino, u.nombre_completo FROM traspasos t JOIN productos p ON t.id_producto = p.id_producto JOIN almacenes ao ON t.id_almacen_origen = ao.id_almacen JOIN almacenes ad ON t.id_almacen_destino = ad.id_almacen JOIN usuarios u ON t.id_usuario = u.id_usuario ORDER BY t.created_at DESC LIMIT 20")->fetchAll(); 
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Traspasos entre Almacenes - Bendito Jugador</title>
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
                    <h2 class="page-title"><i class="fas fa-exchange-alt"></i>Traspasos entre Almacenes</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoTraspasoModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Traspaso
                    </button>
                </div>
                
                <?php if (!empty($error)): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?= e($error); ?></div><?php endif; ?>
                <?php if (!empty($success)): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i><?= e($success); ?></div><?php endif; ?>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr><th>ID</th><th>Fecha</th><th>Producto</th><th>Origen</th><th>Destino</th><th>Cantidad</th><th>Usuario</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($traspasos as $t): ?>
                            <tr>
                                <td><?= $t['id_traspaso']; ?></td>
                                <td><?= date('d/m/Y', strtotime($t['created_at'])); ?></td>
                                <td><?= e($t['producto']); ?></td>
                                <td><?= e($t['origen']); ?></td>
                                <td><?= e($t['destino']); ?></td>
                                <td><?= $t['cantidad']; ?></td>
                                <td><?= e($t['nombre_completo']); ?></td>
                                <td><span class="badge bg-success"><?= $t['estado']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($traspasos)): ?>
                            <tr><td colspan="8" class="text-center text-muted">No hay traspasos registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="nuevoTraspasoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Traspaso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">
                        
                        <div class="form-group">
                            <label class="form-label">Producto *</label>
                            <select name="id_producto" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($productos as $p): ?>
                                <option value="<?= $p['id_producto']; ?>"><?= e($p['codigo'] . ' - ' . $p['nombre']); ?> (Stock: <?= $p['stock_actual']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Almacén de Origen *</label>
                            <select name="id_almacen_origen" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($almacenes as $a): ?>
                                <option value="<?= $a['id_almacen']; ?>"><?= e($a['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Almacén de Destino *</label>
                            <select name="id_almacen_destino" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($almacenes as $a): ?>
                                <option value="<?= $a['id_almacen']; ?>"><?= e($a['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Cantidad *</label>
                            <input type="number" name="cantidad" class="form-control" required min="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Registrar Traspaso</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
