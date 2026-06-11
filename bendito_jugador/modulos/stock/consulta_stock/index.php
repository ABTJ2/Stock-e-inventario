<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_module_access('consulta_stock');

$modulo_activo = 'stock';
$submodulo_activo = 'consulta_stock';
$breadcrumb = 'Consulta de Stock';
$breadcrumb_link = app_url('dashboard.php');

$db = db();
$filters = [
    'buscar' => trim((string) ($_GET['buscar'] ?? '')),
    'almacen' => (int) ($_GET['almacen'] ?? 0),
    'categoria' => (int) ($_GET['categoria'] ?? 0),
    'estado' => trim((string) ($_GET['estado'] ?? 'activo')),
];

$almacenes = [];
$categorias = [];
$productos = [];
$error = '';

try {
    $almacenes = $db->query('SELECT id_almacen, nombre FROM almacenes WHERE estado = 1 ORDER BY nombre')->fetchAll();
    $categorias = $db->query('SELECT id_categoria, nombre FROM categorias_producto WHERE estado = 1 ORDER BY nombre')->fetchAll();

    $where = [];
    $params = [];

    if ($filters['buscar'] !== '') {
        $where[] = '(p.codigo LIKE ? OR p.nombre LIKE ?)';
        $params[] = '%' . $filters['buscar'] . '%';
        $params[] = '%' . $filters['buscar'] . '%';
    }

    if ($filters['almacen'] > 0) {
        $where[] = 's.id_almacen = ?';
        $params[] = $filters['almacen'];
    }

    if ($filters['categoria'] > 0) {
        $where[] = 'p.id_categoria = ?';
        $params[] = $filters['categoria'];
    }

    if (in_array($filters['estado'], ['activo', 'inactivo'], true)) {
        $where[] = 'p.estado = ?';
        $params[] = $filters['estado'];
    }

    $sql = "SELECT p.id_producto,
                   p.codigo,
                   p.nombre,
                   p.descripcion,
                   p.stock_minimo,
                   p.estado,
                   c.nombre AS categoria_nombre,
                   a.nombre AS almacen_nombre,
                   COALESCE(s.stock_actual, 0) AS stock_actual
            FROM productos p
            LEFT JOIN stock_por_almacen s ON s.id_producto = p.id_producto
            LEFT JOIN almacenes a ON a.id_almacen = s.id_almacen
            LEFT JOIN categorias_producto c ON c.id_categoria = p.id_categoria";

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY p.nombre, a.nombre';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll();
} catch (Throwable $exception) {
    $error = 'No se pudo cargar la consulta de stock.';
}

$hasFilters = $filters['buscar'] !== '' || $filters['almacen'] > 0 || $filters['categoria'] > 0 || $filters['estado'] !== 'activo';
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

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error); ?></div>
                <?php endif; ?>

                <form method="GET" class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Código o nombre</label>
                                <input type="text" name="buscar" class="form-control" placeholder="Buscar producto..." value="<?= e($filters['buscar']); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Almacén</label>
                                <select name="almacen" class="form-control">
                                    <option value="0">Todos</option>
                                    <?php foreach ($almacenes as $almacen): ?>
                                        <option value="<?= (int) $almacen['id_almacen']; ?>" <?= (int) $filters['almacen'] === (int) $almacen['id_almacen'] ? 'selected' : ''; ?>>
                                            <?= e($almacen['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Categoría</label>
                                <select name="categoria" class="form-control">
                                    <option value="0">Todas</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= (int) $categoria['id_categoria']; ?>" <?= (int) $filters['categoria'] === (int) $categoria['id_categoria'] ? 'selected' : ''; ?>>
                                            <?= e($categoria['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="" <?= $filters['estado'] === '' ? 'selected' : ''; ?>>Todos</option>
                                    <option value="activo" <?= $filters['estado'] === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactivo" <?= $filters['estado'] === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Buscar</button>
                                <?php if ($hasFilters): ?>
                                    <a href="<?= app_url('modulos/stock/consulta_stock/index.php'); ?>" class="btn btn-secondary">Limpiar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="table-container">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Descripción</th>
                                <th>Categoría</th>
                                <th>Almacén</th>
                                <th class="text-end">Cantidad</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                                <?php
                                $stockActual = (int) $producto['stock_actual'];
                                $stockMinimo = (int) $producto['stock_minimo'];
                                $stockBajo = $stockMinimo > 0 && $stockActual <= $stockMinimo;
                                ?>
                                <tr class="<?= $stockBajo ? 'table-warning' : ''; ?>">
                                    <td><?= e($producto['codigo']); ?></td>
                                    <td>
                                        <strong><?= e($producto['nombre']); ?></strong>
                                        <?php if ($stockBajo): ?>
                                            <span class="badge bg-danger ms-2">Stock bajo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($producto['descripcion'] ?: '-'); ?></td>
                                    <td><?= e($producto['categoria_nombre'] ?: '-'); ?></td>
                                    <td><?= e($producto['almacen_nombre'] ?: '-'); ?></td>
                                    <td class="text-end">
                                        <span class="badge bg-<?= $stockBajo ? 'danger' : 'success'; ?>"><?= $stockActual; ?></span>
                                    </td>
                                    <td><span class="badge bg-<?= $producto['estado'] === 'activo' ? 'success' : 'secondary'; ?>"><?= e($producto['estado']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$productos): ?>
                                <tr><td colspan="7" class="text-center text-muted">No se encontraron registros de stock.</td></tr>
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
