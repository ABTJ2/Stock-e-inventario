<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/productos_logic.php';

require_module_access('productos');

$modulo_activo = 'stock';
$submodulo_activo = 'productos';
$breadcrumb = 'Productos';
$breadcrumb_link = app_url('dashboard.php');

$db = db();
$feedback = producto_procesar_post($db);
$filters = producto_filtros($_GET);
$productos = [];
$stocksPorProducto = [];
$errorCarga = '';

try {
    $catalogos = producto_catalogos($db);
    $productos = productos_listar($db, $filters);
    $stocksPorProducto = productos_stock_por_almacen($db, $productos);
} catch (Throwable $exception) {
    $catalogos = [
        'categorias' => [],
        'marcas' => [],
        'unidades' => [],
        'estados' => [],
        'almacenes' => [],
    ];
    $errorCarga = 'No se pudo cargar el modulo Productos. Verifique que schema.sql y seeds.sql esten aplicados.';
}

$categorias = $catalogos['categorias'];
$marcas = $catalogos['marcas'];
$unidades = $catalogos['unidades'];
$estados = $catalogos['estados'];
$almacenes = $catalogos['almacenes'];
$estadoActivoId = '';
$postAction = request_method_is('POST') ? (string) ($_POST['action'] ?? '') : '';
$reabrirNuevoProductoModal = $postAction === 'crear' && $feedback['type'] === 'danger';

foreach ($estados as $estado) {
    if (strcasecmp((string) $estado['nombre_estado'], 'Activo') === 0) {
        $estadoActivoId = (string) $estado['id_estado_producto'];
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Bendito Jugador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>

        <div class="main-content productos-main-content">
            <?php include __DIR__ . '/../../../includes/header.php'; ?>

            <div class="content-area productos-page">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 productos-page__header">
                    <h2 class="page-title mb-0">
                        <i class="fas fa-boxes"></i>
                        Gestión de Productos
                    </h2>
                    <button type="button" class="btn btn-primary productos-new-btn" data-bs-toggle="modal" data-bs-target="#nuevoProductoModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Producto
                    </button>
                </div>

                <?php if ($errorCarga !== ''): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?= e($errorCarga); ?>
                    </div>
                <?php endif; ?>

                <?php if ($feedback['message'] !== ''): ?>
                    <div class="alert alert-<?= e($feedback['type']); ?>">
                        <i class="fas fa-<?= $feedback['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                        <?= e($feedback['message']); ?>
                    </div>
                <?php endif; ?>

                <form method="GET" class="table-container productos-filter-card mb-4">
                    <div class="p-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-xl-3 col-lg-3 col-md-6 productos-filter-search">
                                <label class="form-label">Buscar por código o nombre</label>
                                <input type="text" name="q" class="form-control" placeholder="Ej: 25 o Camiseta" value="<?= e($filters['q']); ?>">
                            </div>
                            <div class="col-xl-2 col-lg-2 col-md-6">
                                <label class="form-label">Categoría</label>
                                <select name="categoria" class="form-control">
                                    <option value="">Todas</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= (int) $categoria['id_categoria']; ?>" <?= selected($filters['categoria'], $categoria['id_categoria']); ?>>
                                            <?= e($categoria['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-xl-2 col-lg-2 col-md-6">
                                <label class="form-label">Almacén</label>
                                <select name="almacen" class="form-control">
                                    <option value="">Todos</option>
                                    <?php foreach ($almacenes as $almacen): ?>
                                        <option value="<?= (int) $almacen['id_almacen']; ?>" <?= selected($filters['almacen'], $almacen['id_almacen']); ?>>
                                            <?= e($almacen['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-xl-2 col-lg-2 col-md-6">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="">Todos</option>
                                    <?php foreach ($estados as $estado): ?>
                                        <option value="<?= (int) $estado['id_estado_producto']; ?>" <?= selected($filters['estado'], $estado['id_estado_producto']); ?>>
                                            <?= e($estado['nombre_estado']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-12">
                                <div class="productos-filter-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>Filtrar
                                    </button>
                                    <a href="<?= app_url('modulos/stock/productos/index.php'); ?>" class="btn btn-secondary" title="Limpiar filtros" aria-label="Limpiar filtros">
                                        <i class="fas fa-rotate-left"></i><span>Limpiar</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="table-container productos-table-card">
                    <div class="table-responsive productos-table-responsive">
                        <table class="table align-middle mb-0 productos-table">
                            <thead>
                                <tr>
                                    <th class="productos-col-code">Código</th>
                                    <th class="productos-col-product">Producto</th>
                                    <th class="productos-col-category">Categoría / Marca</th>
                                    <th class="productos-col-warehouse">Almacén</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Stock min.</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $producto): ?>
                                    <?php
                                    $idProducto = (int) $producto['id_producto'];
                                    $stockTotal = (int) $producto['stock_total'];
                                    $stockMinimo = (int) $producto['stock_minimo'];
                                    $stockBajo = $stockTotal <= $stockMinimo;
                                    $estadoNombre = (string) ($producto['nombre_estado'] ?? $producto['estado'] ?? '-');
                                    $estadoBadge = strcasecmp($estadoNombre, 'Activo') === 0 ? 'success' : 'secondary';
                                    $stocks = $stocksPorProducto[$idProducto] ?? [];
                                    ?>
                                    <tr class="productos-row <?= $stockBajo ? 'is-low-stock' : ''; ?>">
                                        <td class="productos-code"><strong><?= e($producto['codigo']); ?></strong></td>
                                        <td class="productos-name-cell">
                                            <div class="productos-name fw-semibold"><?= e($producto['nombre']); ?></div>
                                            <?php if ($stockBajo): ?>
                                                <small class="productos-low-stock-note"><i class="fas fa-triangle-exclamation me-1"></i>Stock mínimo</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="productos-meta-cell">
                                            <div><?= e($producto['categoria_nombre'] ?? '-'); ?></div>
                                            <small>Marca: <?= e($producto['marca_nombre'] ?? '-'); ?></small>
                                        </td>
                                        <td><?= e($producto['almacen_nombre'] ?? '-'); ?></td>
                                        <td class="productos-price">$<?= number_format((float) $producto['precio_referencia'], 2, ',', '.'); ?></td>
                                        <td>
                                            <span class="productos-stock-badge <?= $stockBajo ? 'is-low' : 'is-ok'; ?>"><?= $stockTotal; ?></span>
                                        </td>
                                        <td><?= $stockMinimo; ?></td>
                                        <td><span class="badge bg-<?= $estadoBadge; ?> productos-state-badge"><?= e($estadoNombre); ?></span></td>
                                        <td class="text-end productos-actions-cell">
                                            <div class="productos-actions">
                                                <button type="button" class="btn btn-sm btn-outline-info productos-action-btn" data-bs-toggle="modal" data-bs-target="#detalleProducto<?= $idProducto; ?>" title="Ver detalle" aria-label="Ver detalle de <?= e($producto['nombre']); ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary productos-action-btn" data-bs-toggle="modal" data-bs-target="#editarProducto<?= $idProducto; ?>" title="Editar" aria-label="Editar <?= e($producto['nombre']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger productos-action-btn" data-bs-toggle="modal" data-bs-target="#eliminarProducto<?= $idProducto; ?>" title="Eliminar producto" aria-label="Eliminar producto">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="productos-stock-detail-row <?= $stockBajo ? 'is-low-stock-detail' : ''; ?>">
                                        <td colspan="9">
                                            <div class="productos-stock-detail">
                                                <span class="productos-stock-detail-title">
                                                    <i class="fas fa-warehouse"></i>Stock por almacén
                                                </span>
                                                <div class="productos-stock-chips">
                                                    <?php foreach ($stocks as $stock): ?>
                                                        <span class="productos-stock-chip">
                                                            <strong><?= e($stock['almacen_nombre']); ?></strong>
                                                            <span><?= (int) $stock['stock_actual']; ?> u.</span>
                                                            <?php if ((int) $stock['stock_reservado'] > 0): ?>
                                                                <small>Reservado <?= (int) $stock['stock_reservado']; ?></small>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                    <?php if (!$stocks): ?>
                                                        <span class="productos-stock-chip is-empty">Sin stock registrado</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (!$productos): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No hay productos para los filtros seleccionados.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php foreach ($productos as $producto): ?>
                    <?php
                    $idProducto = (int) $producto['id_producto'];
                    $stockTotal = (int) $producto['stock_total'];
                    $stockMinimo = (int) $producto['stock_minimo'];
                    $estadoNombre = (string) ($producto['nombre_estado'] ?? $producto['estado'] ?? '-');
                    $estadoBadge = strcasecmp($estadoNombre, 'Activo') === 0 ? 'success' : 'secondary';
                    $stocks = $stocksPorProducto[$idProducto] ?? [];
                    ?>
                    <div class="modal fade" id="detalleProducto<?= $idProducto; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Detalle de Producto</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <small class="text-muted">Código</small>
                                            <div class="fw-semibold"><?= e($producto['codigo']); ?></div>
                                        </div>
                                        <div class="col-md-8">
                                            <small class="text-muted">Nombre</small>
                                            <div class="fw-semibold"><?= e($producto['nombre']); ?></div>
                                        </div>
                                        <div class="col-12">
                                            <small class="text-muted">Descripción</small>
                                            <div><?= e($producto['descripcion'] ?: '-'); ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Categoría</small>
                                            <div><?= e($producto['categoria_nombre'] ?? '-'); ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Marca</small>
                                            <div><?= e($producto['marca_nombre'] ?? '-'); ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Unidad</small>
                                            <div><?= e($producto['unidad_nombre'] ?? '-'); ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Precio</small>
                                            <div>$<?= number_format((float) $producto['precio_referencia'], 2, ',', '.'); ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Stock total</small>
                                            <div><?= $stockTotal; ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Stock mínimo</small>
                                            <div><?= $stockMinimo; ?></div>
                                        </div>
                                        <div class="col-12">
                                            <h6 class="mt-2">Stock por almacén</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr><th>Almacén</th><th>Stock</th><th>Reservado</th></tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($stocks as $stock): ?>
                                                            <tr>
                                                                <td><?= e($stock['almacen_nombre']); ?></td>
                                                                <td><?= (int) $stock['stock_actual']; ?></td>
                                                                <td><?= (int) $stock['stock_reservado']; ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                        <?php if (!$stocks): ?>
                                                            <tr><td colspan="3" class="text-muted text-center">Sin stock registrado.</td></tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade productos-modal" id="editarProducto<?= $idProducto; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered productos-modal-dialog">
                            <div class="modal-content productos-modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar Producto</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                </div>
                                <form method="POST" action="<?= app_url('modulos/stock/productos/index.php'); ?>" class="productos-modal-form">
                                    <div class="modal-body productos-modal-body">
                                        <input type="hidden" name="action" value="editar">
                                        <input type="hidden" name="id_producto" value="<?= $idProducto; ?>">
                                        <?php
                                        $p = $producto;
                                        $p['stock_actual'] = (int) $producto['stock_almacen'];
                                        $stockLabel = 'Stock en almacén seleccionado';
                                        $codigoReadonly = true;
                                        include __DIR__ . '/producto_form.php';
                                        unset($stockLabel, $codigoReadonly);
                                        ?>
                                    </div>
                                    <div class="modal-footer productos-modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="eliminarProducto<?= $idProducto; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">¿Eliminar producto?</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                </div>
                                <form method="POST" action="<?= app_url('modulos/stock/productos/index.php'); ?>">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="eliminar">
                                        <input type="hidden" name="id_producto" value="<?= $idProducto; ?>">
                                        <p>Esta acción eliminará el producto seleccionado del sistema.</p>
                                        <small class="text-danger fw-semibold">Esta operación no se puede deshacer.</small>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php include __DIR__ . '/../../../includes/footer.php'; ?>
        </div>
    </div>

    <div class="modal fade productos-modal" id="nuevoProductoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered productos-modal-dialog">
            <div class="modal-content productos-modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST" action="<?= app_url('modulos/stock/productos/index.php'); ?>" class="productos-modal-form">
                    <div class="modal-body productos-modal-body">
                        <input type="hidden" name="action" value="crear">
                        <?php
                        $p = [
                            'codigo' => $errorCarga === '' ? producto_siguiente_codigo($db) : '',
                            'nombre' => '',
                            'descripcion' => '',
                            'precio_referencia' => 0,
                            'stock_actual' => 0,
                            'stock_minimo' => 0,
                            'id_categoria' => $categorias[0]['id_categoria'] ?? '',
                            'id_marca' => $marcas[0]['id_marca'] ?? '',
                            'id_unidad_medida' => $unidades[0]['id_unidad_medida'] ?? '',
                            'id_estado_producto' => $estadoActivoId,
                            'id_almacen' => $almacenes[0]['id_almacen'] ?? '',
                        ];

                        if ($reabrirNuevoProductoModal) {
                            $p = array_merge($p, producto_post_data($_POST));
                            $p['codigo'] = $errorCarga === '' ? producto_siguiente_codigo($db) : '';
                        }

                        $stockLabel = 'Stock inicial';
                        $codigoReadonly = true;
                        if ($reabrirNuevoProductoModal && $feedback['message'] !== ''): ?>
                            <div class="alert alert-danger productos-modal-alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?= e($feedback['message']); ?>
                            </div>
                        <?php endif;
                        include __DIR__ . '/producto_form.php';
                        unset($stockLabel, $codigoReadonly);
                        ?>
                    </div>
                    <div class="modal-footer productos-modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($reabrirNuevoProductoModal): ?>
        <script>
            const nuevoProductoModal = document.getElementById('nuevoProductoModal');
            if (nuevoProductoModal && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(nuevoProductoModal).show();
            }
        </script>
    <?php endif; ?>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
