<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_auth();

$modulo_activo = 'stock';
$submodulo_activo = 'reportes_gerenciales';
$breadcrumb = 'Reportes Gerenciales';
$breadcrumb_link = app_url('dashboard.php');

$resumen = ['total_productos' => 0, 'stock_bajo' => 0, 'total_proveedores' => 0, 'valor_stock' => 0];
$topProductos = [];
try {
    $resumen['total_productos'] = db()->query("SELECT COUNT(*) as total FROM productos WHERE estado = 'activo'")->fetch()['total'];
    $resumen['stock_bajo'] = db()->query("SELECT COUNT(*) as total FROM productos WHERE stock_actual <= stock_minimo AND estado = 'activo'")->fetch()['total'];
    $resumen['total_proveedores'] = db()->query("SELECT COUNT(*) as total FROM proveedores WHERE estado = 'activo'")->fetch()['total'];
    $resumen['valor_stock'] = db()->query("SELECT SUM(stock_actual * precio) as total FROM productos WHERE estado = 'activo'")->fetch()['total'] ?? 0;
    $topProductos = db()->query("SELECT nombre, stock_actual, precio, (stock_actual * precio) as valor_total FROM productos WHERE estado = 'activo' ORDER BY stock_actual DESC LIMIT 5")->fetchAll();
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes Gerenciales - Bendito Jugador</title>
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
                <h2 class="page-title"><i class="fas fa-chart-line"></i>Reportes Gerenciales</h2>
                
                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-header"><span class="card-title">Total Productos</span><div class="card-icon blue"><i class="fas fa-box"></i></div></div>
                        <div class="card-value"><?= $resumen['total_productos']; ?></div>
                        <div class="card-subtitle">Productos activos</div>
                    </div>
                    <div class="card">
                        <div class="card-header"><span class="card-title">Stock Bajo</span><div class="card-icon red"><i class="fas fa-exclamation-triangle"></i></div></div>
                        <div class="card-value"><?= $resumen['stock_bajo']; ?></div>
                        <div class="card-subtitle">Por debajo del mínimo</div>
                    </div>
                    <div class="card">
                        <div class="card-header"><span class="card-title">Valor Total Stock</span><div class="card-icon green"><i class="fas fa-dollar-sign"></i></div></div>
                        <div class="card-value">$<?= number_format($resumen['valor_stock'], 2); ?></div>
                        <div class="card-subtitle">En inventario</div>
                    </div>
                    <div class="card">
                        <div class="card-header"><span class="card-title">Proveedores</span><div class="card-icon orange"><i class="fas fa-truck"></i></div></div>
                        <div class="card-value"><?= $resumen['total_proveedores']; ?></div>
                        <div class="card-subtitle">Activos</div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <h5 class="mb-3"><i class="fas fa-trophy me-2"></i>Top 5 Productos con Más Stock</h5>
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Producto</th><th>Stock</th><th>Precio Unit.</th><th>Valor Total</th></tr></thead>
                            <tbody>
                                <?php foreach ($topProductos as $p): ?>
                                <tr>
                                    <td><?= e($p['nombre']); ?></td>
                                    <td><strong><?= $p['stock_actual']; ?></strong></td>
                                    <td>$<?= number_format($p['precio'], 2); ?></td>
                                    <td>$<?= number_format($p['valor_total'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($topProductos)): ?>
                                <tr><td colspan="4" class="text-center text-muted">Sin datos disponibles.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
