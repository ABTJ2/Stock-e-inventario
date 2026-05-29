<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_auth();

$modulo_activo = 'stock';
$submodulo_activo = 'movimientos';
$breadcrumb = 'Movimientos de Stock';
$breadcrumb_link = app_url('dashboard.php');

$db = db();
$error = '';
$movimientos = [];
$productos = [];
$usuarios = [];
$almacenes = [];

$tipos = [
    'ingreso' => 'ENTRADA',
    'egreso' => 'EGRESO',
    'ajuste_positivo' => 'AJUSTE +',
    'ajuste_negativo' => 'AJUSTE -',
    'traspaso' => 'TRASPASO',
];

$filters = [
    'producto' => (int) ($_GET['producto'] ?? 0),
    'tipo' => trim((string) ($_GET['tipo'] ?? '')),
    'usuario' => (int) ($_GET['usuario'] ?? 0),
    'almacen' => (int) ($_GET['almacen'] ?? 0),
    'desde' => trim((string) ($_GET['desde'] ?? '')),
    'hasta' => trim((string) ($_GET['hasta'] ?? '')),
];

try {
    $productos = $db->query("SELECT id_producto, codigo, nombre FROM productos WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
    $usuarios = $db->query("SELECT id_usuario, nombre_completo FROM usuarios WHERE estado = 'activo' ORDER BY nombre_completo")->fetchAll();
    $almacenes = $db->query('SELECT id_almacen, nombre FROM almacenes WHERE estado = 1 ORDER BY nombre')->fetchAll();

    $where = [];
    $params = [];

    if ($filters['producto'] > 0) {
        $where[] = 'm.id_producto = ?';
        $params[] = $filters['producto'];
    }

    if (array_key_exists($filters['tipo'], $tipos)) {
        $where[] = 'm.tipo_movimiento = ?';
        $params[] = $filters['tipo'];
    }

    if ($filters['usuario'] > 0) {
        $where[] = 'm.id_usuario = ?';
        $params[] = $filters['usuario'];
    }

    if ($filters['almacen'] > 0) {
        $where[] = 'm.id_almacen = ?';
        $params[] = $filters['almacen'];
    }

    if ($filters['desde'] !== '') {
        $where[] = 'm.created_at >= ?';
        $params[] = $filters['desde'] . ' 00:00:00';
    }

    if ($filters['hasta'] !== '') {
        $where[] = 'm.created_at <= ?';
        $params[] = $filters['hasta'] . ' 23:59:59';
    }

    $sql = "SELECT m.id_movimiento,
                   m.created_at,
                   m.tipo_movimiento,
                   m.cantidad,
                   m.stock_anterior,
                   m.stock_nuevo,
                   m.motivo,
                   m.referencia,
                   m.entidad_origen,
                   m.id_entidad_origen,
                   p.codigo,
                   p.nombre AS producto,
                   u.nombre_completo,
                   a.nombre AS almacen_nombre
            FROM movimientos_stock m
            INNER JOIN productos p ON p.id_producto = m.id_producto
            INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
            LEFT JOIN almacenes a ON a.id_almacen = m.id_almacen";

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY m.created_at DESC, m.id_movimiento DESC LIMIT 200';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $movimientos = $stmt->fetchAll();
} catch (Throwable $exception) {
    $error = 'No se pudieron cargar los movimientos de stock.';
}

function movimiento_badge(string $tipo): string
{
    return match ($tipo) {
        'ingreso' => 'success',
        'egreso' => 'danger',
        'ajuste_positivo' => 'primary',
        'ajuste_negativo' => 'warning',
        'traspaso' => 'info',
        default => 'secondary',
    };
}

function movimiento_referencia(array $movimiento): string
{
    $origen = (string) ($movimiento['entidad_origen'] ?? '');
    $idOrigen = (int) ($movimiento['id_entidad_origen'] ?? 0);

    if ($origen !== '' && $idOrigen > 0) {
        return match ($origen) {
            'ingreso' => 'Ingreso #' . $idOrigen,
            'ajuste' => 'Ajuste #' . $idOrigen,
            'traspaso' => 'Traspaso #' . $idOrigen,
            'producto' => 'Producto #' . $idOrigen,
            default => ucfirst($origen) . ' #' . $idOrigen,
        };
    }

    $referencia = (string) ($movimiento['referencia'] ?? '');
    if ($referencia === '') {
        return '-';
    }

    [$tipo, $id] = array_pad(explode(':', $referencia, 2), 2, '');
    return match ($tipo) {
        'ingreso' => 'Ingreso #' . $id,
        'ajuste' => 'Ajuste #' . $id,
        'traspaso' => 'Traspaso #' . $id,
        default => $referencia,
    };
}

$hasFilters = $filters['producto'] > 0 || $filters['tipo'] !== '' || $filters['usuario'] > 0 || $filters['almacen'] > 0 || $filters['desde'] !== '' || $filters['hasta'] !== '';
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

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error); ?></div>
                <?php endif; ?>

                <form method="GET" class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Producto</label>
                                <select name="producto" class="form-control">
                                    <option value="0">Todos</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option value="<?= (int) $producto['id_producto']; ?>" <?= $filters['producto'] === (int) $producto['id_producto'] ? 'selected' : ''; ?>>
                                            <?= e($producto['codigo'] . ' - ' . $producto['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tipo</label>
                                <select name="tipo" class="form-control">
                                    <option value="">Todos</option>
                                    <?php foreach ($tipos as $value => $label): ?>
                                        <option value="<?= e($value); ?>" <?= $filters['tipo'] === $value ? 'selected' : ''; ?>><?= e($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Usuario</label>
                                <select name="usuario" class="form-control">
                                    <option value="0">Todos</option>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <option value="<?= (int) $usuario['id_usuario']; ?>" <?= $filters['usuario'] === (int) $usuario['id_usuario'] ? 'selected' : ''; ?>><?= e($usuario['nombre_completo']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Almacén</label>
                                <select name="almacen" class="form-control">
                                    <option value="0">Todos</option>
                                    <?php foreach ($almacenes as $almacen): ?>
                                        <option value="<?= (int) $almacen['id_almacen']; ?>" <?= $filters['almacen'] === (int) $almacen['id_almacen'] ? 'selected' : ''; ?>><?= e($almacen['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Desde</label>
                                <input type="date" name="desde" class="form-control" value="<?= e($filters['desde']); ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Hasta</label>
                                <input type="date" name="hasta" class="form-control" value="<?= e($filters['hasta']); ?>">
                            </div>
                            <div class="col-md-1 d-grid">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i></button>
                            </div>
                            <?php if ($hasFilters): ?>
                                <div class="col-12">
                                    <a href="<?= app_url('modulos/stock/movimientos/index.php'); ?>" class="btn btn-secondary">Limpiar filtros</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <div class="table-container">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th class="text-end">Cantidad</th>
                                <th>Almacén</th>
                                <th>Usuario</th>
                                <th>Observación</th>
                                <th>Vínculo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $movimiento): ?>
                                <tr>
                                    <td><?= e(date('d/m/Y H:i', strtotime((string) $movimiento['created_at']))); ?></td>
                                    <td><?= e($movimiento['codigo'] . ' - ' . $movimiento['producto']); ?></td>
                                    <td><span class="badge bg-<?= movimiento_badge((string) $movimiento['tipo_movimiento']); ?>"><?= e($tipos[$movimiento['tipo_movimiento']] ?? $movimiento['tipo_movimiento']); ?></span></td>
                                    <td class="text-end"><?= (int) $movimiento['cantidad']; ?></td>
                                    <td><?= e($movimiento['almacen_nombre'] ?? '-'); ?></td>
                                    <td><?= e($movimiento['nombre_completo']); ?></td>
                                    <td><?= e($movimiento['motivo'] ?: '-'); ?></td>
                                    <td><?= e(movimiento_referencia($movimiento)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$movimientos): ?>
                                <tr><td colspan="8" class="text-center text-muted">No hay movimientos registrados para los filtros seleccionados.</td></tr>
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
