<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_auth();

$modulo_activo = 'stock';
$submodulo_activo = 'consulta_stock';
$breadcrumb = 'Consulta de Stock';
$breadcrumb_link = app_url('dashboard.php');

$productos = [];
$busqueda = $_GET['buscar'] ?? '';
try {
    if ($busqueda) {
        $stmt = db()->prepare("SELECT p.*, (p.stock_actual - p.stock_minimo) as diferencia FROM productos p WHERE (p.nombre LIKE ? OR p.codigo LIKE ?) AND p.estado = 'activo' ORDER BY p.nombre");
        $stmt->execute(["%$busqueda%", "%$busqueda%"]);
    } else {
        $stmt = db()->query("SELECT p.*, (p.stock_actual - p.stock_minimo) as diferencia FROM productos p WHERE p.estado = 'activo' ORDER BY p.nombre");
    }
    $productos = $stmt->fetchAll();
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Stock - Bendito Jugador</title>
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
                <h2 class="page-title"><i class="fas fa-search"></i>Consulta de Stock</h2>
                
                <form method="GET" class="d-flex gap-2 mb-4">
                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por código o nombre..." value="<?= e($busqueda); ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
                    <?php if ($busqueda): ?><a href="index.php" class="btn btn-secondary">Limpiar</a><?php endif; ?>
                </form>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr><th>Código</th><th>Nombre</th><th>Categoría</th><th>Stock Actual</th><th>Stock Mín.</th><th>Diferencia</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $p): ?>
                            <tr>
                                <td><?= e($p['codigo']); ?></td>
                                <td><?= e($p['nombre']); ?></td>
                                <td><?= e($p['categoria'] ?? '-'); ?></td>
                                <td><strong><?= $p['stock_actual']; ?></strong></td>
                                <td><?= $p['stock_minimo']; ?></td>
                                <td>
                                    <?php if ($p['diferencia'] <= 0): ?>
                                        <span class="badge bg-danger"><?= $p['diferencia']; ?></span>
                                    <?php elseif ($p['diferencia'] <= 10): ?>
                                        <span class="badge bg-warning"><?= $p['diferencia']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?= $p['diferencia']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-<?= $p['stock_actual'] > 0 ? 'success' : 'danger'; ?>"><?= $p['stock_actual'] > 0 ? 'Disponible' : 'Sin Stock'; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($productos)): ?>
                            <tr><td colspan="7" class="text-center text-muted">No se encontraron productos.</td></tr>
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
