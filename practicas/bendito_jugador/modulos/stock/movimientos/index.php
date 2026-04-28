<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_auth();

$modulo_activo = 'stock';
$submodulo_activo = 'movimientos';
$breadcrumb = 'Movimientos de Stock';
$breadcrumb_link = app_url('dashboard.php');

$movimientos = [];
try { 
    $movimientos = db()->query("SELECT m.*, p.nombre as producto, u.nombre_completo FROM movimientos_stock m JOIN productos p ON m.id_producto = p.id_producto JOIN usuarios u ON m.id_usuario = u.id_usuario ORDER BY m.created_at DESC LIMIT 50")->fetchAll(); 
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimientos de Stock - Bendito Jugador</title>
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
                <h2 class="page-title"><i class="fas fa-exchange-alt"></i>Movimientos de Stock</h2>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Stock Anterior</th><th>Stock Nuevo</th><th>Usuario</th><th>Motivo</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $m): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($m['created_at'])); ?></td>
                                <td><?= e($m['producto']); ?></td>
                                <td><span class="badge bg-<?= $m['tipo_movimiento'] === 'ingreso' ? 'success' : ($m['tipo_movimiento'] === 'egreso' ? 'danger' : 'warning'); ?>"><?= $m['tipo_movimiento']; ?></span></td>
                                <td><?= $m['cantidad']; ?></td>
                                <td><?= $m['stock_anterior']; ?></td>
                                <td><?= $m['stock_nuevo']; ?></td>
                                <td><?= e($m['nombre_completo']); ?></td>
                                <td><?= e($m['motivo'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($movimientos)): ?>
                            <tr><td colspan="8" class="text-center text-muted">No hay movimientos registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
