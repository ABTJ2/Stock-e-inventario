<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/proveedores_logic.php';

require_auth();

$modulo_activo = 'stock';
$submodulo_activo = 'proveedores';
$breadcrumb = 'Proveedores';
$breadcrumb_link = app_url('dashboard.php');

$db = db();
$feedback = proveedor_procesar_post($db);
$filters = proveedor_filtros($_GET);
$proveedores = [];
$errorCarga = '';

try {
    $catalogos = proveedor_catalogos($db);
    $proveedores = proveedores_listar($db, $filters);
} catch (Throwable $exception) {
    $catalogos = [
        'rubros' => [],
        'condiciones_iva' => [],
        'paises' => [],
        'provincias' => [],
        'localidades' => [],
        'estados' => [],
    ];
    $errorCarga = 'No se pudo cargar el modulo Proveedores. Verifique que schema.sql y seeds.sql esten aplicados.';
}

$rubros = $catalogos['rubros'];
$condicionesIva = $catalogos['condiciones_iva'];
$paises = $catalogos['paises'];
$provincias = $catalogos['provincias'];
$localidades = $catalogos['localidades'];
$estados = $catalogos['estados'];
$estadoActivoId = '';

foreach ($estados as $estado) {
    if (strcasecmp((string) $estado['nombre_estado'], 'Activo') === 0) {
        $estadoActivoId = (string) $estado['id_estado_proveedor'];
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proveedores - Bendito Jugador</title>
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
                        <i class="fas fa-truck"></i>
                        Gestion de Proveedores
                    </h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoProveedorModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Proveedor
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
                                <label class="form-label">Buscar</label>
                                <input type="text" name="q" class="form-control" placeholder="Razon social, CUIT, fantasia o email" value="<?= e($filters['q']); ?>">
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">Rubro</label>
                                <select name="rubro" class="form-control">
                                    <option value="">Todos</option>
                                    <?php foreach ($rubros as $rubro): ?>
                                        <option value="<?= (int) $rubro['id_rubro_proveedor']; ?>" <?= proveedor_selected($filters['rubro'], $rubro['id_rubro_proveedor']); ?>>
                                            <?= e($rubro['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">Condicion IVA</label>
                                <select name="condicion_iva" class="form-control">
                                    <option value="">Todas</option>
                                    <?php foreach ($condicionesIva as $condicion): ?>
                                        <option value="<?= (int) $condicion['id_condicion_iva']; ?>" <?= proveedor_selected($filters['condicion_iva'], $condicion['id_condicion_iva']); ?>>
                                            <?= e($condicion['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">Localidad</label>
                                <select name="localidad" class="form-control">
                                    <option value="">Todas</option>
                                    <?php foreach ($localidades as $localidad): ?>
                                        <option value="<?= (int) $localidad['id_localidad']; ?>" <?= proveedor_selected($filters['localidad'], $localidad['id_localidad']); ?>>
                                            <?= e($localidad['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="">Todos</option>
                                    <?php foreach ($estados as $estado): ?>
                                        <option value="<?= (int) $estado['id_estado_proveedor']; ?>" <?= proveedor_selected($filters['estado'], $estado['id_estado_proveedor']); ?>>
                                            <?= e($estado['nombre_estado']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i>Filtrar
                                </button>
                                <a href="<?= app_url('modulos/stock/proveedores/index.php'); ?>" class="btn btn-secondary" aria-label="Limpiar filtros">
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
                                    <th>CUIT</th>
                                    <th>Razon social</th>
                                    <th>Nombre fantasia</th>
                                    <th>Rubro</th>
                                    <th>Condicion IVA</th>
                                    <th>Localidad</th>
                                    <th>Email</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <?php
                                    $idProveedor = (int) $proveedor['id_proveedor'];
                                    $estadoNombre = (string) ($proveedor['nombre_estado'] ?? $proveedor['estado'] ?? '-');
                                    $estadoBadge = strcasecmp($estadoNombre, 'Activo') === 0 ? 'success' : 'secondary';
                                    ?>
                                    <tr>
                                        <td><strong><?= e($proveedor['cuit'] ?? '-'); ?></strong></td>
                                        <td><?= e($proveedor['razon_social']); ?></td>
                                        <td><?= e($proveedor['nombre_fantasia'] ?? '-'); ?></td>
                                        <td><?= e($proveedor['rubro_nombre'] ?? '-'); ?></td>
                                        <td><?= e($proveedor['condicion_iva_nombre'] ?? '-'); ?></td>
                                        <td><?= e($proveedor['localidad_nombre'] ?? '-'); ?></td>
                                        <td><?= e($proveedor['email'] ?? '-'); ?></td>
                                        <td><span class="badge bg-<?= $estadoBadge; ?>"><?= e($estadoNombre); ?></span></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#detalleProveedor<?= $idProveedor; ?>" title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editarProveedor<?= $idProveedor; ?>" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if (strcasecmp($estadoNombre, 'Inactivo') !== 0): ?>
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#desactivarProveedor<?= $idProveedor; ?>" title="Desactivar">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="detalleProveedor<?= $idProveedor; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detalle de Proveedor</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row g-3">
                                                        <div class="col-md-4"><small class="text-muted">CUIT</small><div class="fw-semibold"><?= e($proveedor['cuit'] ?? '-'); ?></div></div>
                                                        <div class="col-md-8"><small class="text-muted">Razon social</small><div class="fw-semibold"><?= e($proveedor['razon_social']); ?></div></div>
                                                        <div class="col-md-6"><small class="text-muted">Nombre fantasia</small><div><?= e($proveedor['nombre_fantasia'] ?? '-'); ?></div></div>
                                                        <div class="col-md-3"><small class="text-muted">Rubro</small><div><?= e($proveedor['rubro_nombre'] ?? '-'); ?></div></div>
                                                        <div class="col-md-3"><small class="text-muted">Condicion IVA</small><div><?= e($proveedor['condicion_iva_nombre'] ?? '-'); ?></div></div>
                                                        <div class="col-12"><small class="text-muted">Direccion</small><div><?= e($proveedor['direccion'] ?? '-'); ?></div></div>
                                                        <div class="col-md-3"><small class="text-muted">Pais</small><div><?= e($proveedor['pais_nombre'] ?? '-'); ?></div></div>
                                                        <div class="col-md-3"><small class="text-muted">Provincia</small><div><?= e($proveedor['provincia_nombre'] ?? '-'); ?></div></div>
                                                        <div class="col-md-3"><small class="text-muted">Localidad</small><div><?= e($proveedor['localidad_nombre'] ?? '-'); ?></div></div>
                                                        <div class="col-md-3"><small class="text-muted">Codigo postal</small><div><?= e($proveedor['codigo_postal'] ?? '-'); ?></div></div>
                                                        <div class="col-md-4"><small class="text-muted">Telefono</small><div><?= e($proveedor['telefono'] ?? '-'); ?></div></div>
                                                        <div class="col-md-4"><small class="text-muted">Email</small><div><?= e($proveedor['email'] ?? '-'); ?></div></div>
                                                        <div class="col-md-4"><small class="text-muted">Sitio web</small><div><?= e($proveedor['sitio_web'] ?? '-'); ?></div></div>
                                                        <div class="col-md-4"><small class="text-muted">Plazo de pago</small><div><?= e($proveedor['plazo_pago'] ?? '-'); ?></div></div>
                                                        <div class="col-md-4"><small class="text-muted">CBU</small><div><?= e($proveedor['cbu'] ?? '-'); ?></div></div>
                                                        <div class="col-md-4"><small class="text-muted">Alias</small><div><?= e($proveedor['alias'] ?? '-'); ?></div></div>
                                                        <div class="col-12"><small class="text-muted">Datos bancarios</small><div><?= e($proveedor['datos_bancarios'] ?? '-'); ?></div></div>
                                                        <div class="col-12"><small class="text-muted">Observaciones</small><div><?= e($proveedor['observaciones'] ?? '-'); ?></div></div>
                                                        <div class="col-md-4"><small class="text-muted">Estado</small><div><span class="badge bg-<?= $estadoBadge; ?>"><?= e($estadoNombre); ?></span></div></div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="editarProveedor<?= $idProveedor; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Editar Proveedor</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="editar">
                                                        <input type="hidden" name="id_proveedor" value="<?= $idProveedor; ?>">
                                                        <?php
                                                        $p = $proveedor;
                                                        include __DIR__ . '/proveedor_form.php';
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

                                    <div class="modal fade" id="desactivarProveedor<?= $idProveedor; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirmar desactivacion</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="desactivar">
                                                        <input type="hidden" name="id_proveedor" value="<?= $idProveedor; ?>">
                                                        <p class="mb-0">El proveedor <strong><?= e($proveedor['razon_social']); ?></strong> pasara a estado inactivo.</p>
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

                                <?php if (!$proveedores): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No hay proveedores para los filtros seleccionados.</td>
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

    <div class="modal fade" id="nuevoProveedorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">
                        <?php
                        $p = [
                            'cuit' => '',
                            'razon_social' => '',
                            'nombre_fantasia' => '',
                            'direccion' => '',
                            'id_pais' => $paises[0]['id_pais'] ?? '',
                            'id_provincia' => '',
                            'id_localidad' => '',
                            'codigo_postal' => '',
                            'telefono' => '',
                            'email' => '',
                            'sitio_web' => '',
                            'id_condicion_iva' => $condicionesIva[0]['id_condicion_iva'] ?? '',
                            'id_rubro_proveedor' => $rubros[0]['id_rubro_proveedor'] ?? '',
                            'plazo_pago' => '',
                            'cbu' => '',
                            'alias' => '',
                            'datos_bancarios' => '',
                            'id_estado_proveedor' => $estadoActivoId,
                            'observaciones' => '',
                        ];
                        include __DIR__ . '/proveedor_form.php';
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
