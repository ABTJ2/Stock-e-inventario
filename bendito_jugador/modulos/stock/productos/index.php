<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/productos_logic.php';

require_auth();

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

        <div class="main-content">
            <?php include __DIR__ . '/../../../includes/header.php'; ?>

            <div class="content-area">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <h2 class="page-title mb-0">
                        <i class="fas fa-boxes"></i>
                        Gestion de Productos
                    </h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoProductoModal">
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

                <form method="GET" class="table-container mb-4">
                    <div class="p-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Buscar por codigo o nombre</label>
                                <input type="text" name="q" class="form-control" placeholder="Ej: PROD001 o Camiseta" value="<?= e($filters['q']); ?>">
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">Categoria</label>
                                <select name="categoria" class="form-control">
                                    <option value="">Todas</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= (int) $categoria['id_categoria']; ?>" <?= selected($filters['categoria'], $categoria['id_categoria']); ?>>
                                            <?= e($categoria['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">Almacen</label>
                                <select name="almacen" class="form-control">
                                    <option value="">Todos</option>
                                    <?php foreach ($almacenes as $almacen): ?>
                                        <option value="<?= (int) $almacen['id_almacen']; ?>" <?= selected($filters['almacen'], $almacen['id_almacen']); ?>>
                                            <?= e($almacen['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
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
                            <div class="col-lg-2 col-md-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i>Filtrar
                                </button>
                                <a href="<?= app_url('modulos/stock/productos/index.php'); ?>" class="btn btn-secondary" aria-label="Limpiar filtros">
                                    <i class="fas fa-rotate-left"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Nombre</th>
                                    <th>Categoria</th>
                                    <th>Marca</th>
                                    <th>Almacen</th>
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
                                    <tr class="<?= $stockBajo ? 'table-warning' : ''; ?>">
                                        <td><strong><?= e($producto['codigo']); ?></strong></td>
                                        <td>
                                            <div class="fw-semibold"><?= e($producto['nombre']); ?></div>
                                            <?php if ($stockBajo): ?>
                                                <small class="text-danger"><i class="fas fa-triangle-exclamation me-1"></i>Stock minimo alcanzado</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($producto['categoria_nombre'] ?? '-'); ?></td>
                                        <td><?= e($producto['marca_nombre'] ?? '-'); ?></td>
                                        <td><?= e($producto['almacen_nombre'] ?? '-'); ?></td>
                                        <td>$<?= number_format((float) $producto['precio_referencia'], 2, ',', '.'); ?></td>
                                        <td>
                                            <span class="badge bg-<?= $stockBajo ? 'danger' : 'success'; ?>"><?= $stockTotal; ?></span>
                                        </td>
                                        <td><?= $stockMinimo; ?></td>
                                        <td><span class="badge bg-<?= $estadoBadge; ?>"><?= e($estadoNombre); ?></span></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#detalleProducto<?= $idProducto; ?>" title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editarProducto<?= $idProducto; ?>" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if (strcasecmp($estadoNombre, 'Inactivo') !== 0): ?>
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#desactivarProducto<?= $idProducto; ?>" title="Desactivar">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

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
                                                            <small class="text-muted">Codigo</small>
                                                            <div class="fw-semibold"><?= e($producto['codigo']); ?></div>
                                                        </div>
                                                        <div class="col-md-8">
                                                            <small class="text-muted">Nombre</small>
                                                            <div class="fw-semibold"><?= e($producto['nombre']); ?></div>
                                                        </div>
                                                        <div class="col-12">
                                                            <small class="text-muted">Descripcion</small>
                                                            <div><?= e($producto['descripcion'] ?: '-'); ?></div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <small class="text-muted">Categoria</small>
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
                                                            <small class="text-muted">Stock minimo</small>
                                                            <div><?= $stockMinimo; ?></div>
                                                        </div>
                                                        <div class="col-12">
                                                            <h6 class="mt-2">Stock por almacen</h6>
                                                            <div class="table-responsive">
                                                                <table class="table table-sm">
                                                                    <thead>
                                                                        <tr><th>Almacen</th><th>Stock</th><th>Reservado</th></tr>
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

                                    <div class="modal fade" id="editarProducto<?= $idProducto; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Editar Producto</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="editar">
                                                        <input type="hidden" name="id_producto" value="<?= $idProducto; ?>">
                                                        <?php
                                                        $p = $producto;
                                                        $p['stock_actual'] = (int) $producto['stock_almacen'];
                                                        $stockLabel = 'Stock en almacen seleccionado';
                                                        include __DIR__ . '/producto_form.php';
                                                        unset($stockLabel);
                                                        ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="desactivarProducto<?= $idProducto; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirmar desactivacion</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="desactivar">
                                                        <input type="hidden" name="id_producto" value="<?= $idProducto; ?>">
                                                        <p class="mb-0">El producto <strong><?= e($producto['codigo']); ?> - <?= e($producto['nombre']); ?></strong> pasara a estado inactivo.</p>
                                                        <small class="text-muted">No se eliminara fisicamente de la base de datos.</small>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-danger">Desactivar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <?php if (!$productos): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">No hay productos para los filtros seleccionados.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php include __DIR__ . '/../../../includes/footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="nuevoProductoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
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
                        $stockLabel = 'Stock inicial';
                        include __DIR__ . '/producto_form.php';
                        unset($stockLabel);
                        ?>
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
