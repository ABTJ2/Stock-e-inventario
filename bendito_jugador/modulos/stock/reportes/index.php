<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_auth();

$modulo_activo = 'stock';
$submodulo_activo = 'reportes';
$breadcrumb = 'Reportes';
$breadcrumb_link = app_url('dashboard.php');

$db = db();
$reportesDisponibles = [
    'stock_actual' => 'Stock actual',
    'stock_bajo' => 'Stock bajo mínimo',
    'inventario_almacen' => 'Inventario por almacén',
    'inventario_categoria' => 'Inventario por categoría',
    'movimientos' => 'Movimientos de stock',
    'ingresos' => 'Ingresos de mercadería',
    'ajustes' => 'Ajustes de inventario',
    'traspasos' => 'Traspasos',
    'gerencial' => 'Reporte gerencial básico',
];

$reporte = (string) ($_GET['reporte'] ?? 'stock_actual');
if (!array_key_exists($reporte, $reportesDisponibles)) {
    $reporte = 'stock_actual';
}

$filters = [
    'producto' => (int) ($_GET['producto'] ?? 0),
    'almacen' => (int) ($_GET['almacen'] ?? 0),
    'categoria' => (int) ($_GET['categoria'] ?? 0),
    'tipo' => trim((string) ($_GET['tipo'] ?? '')),
    'desde' => trim((string) ($_GET['desde'] ?? '')),
    'hasta' => trim((string) ($_GET['hasta'] ?? '')),
];

function report_where_in(array &$where, array &$params, string $condition, mixed $value): void
{
    $where[] = $condition;
    $params[] = $value;
}

function run_report(PDO $db, string $reporte, array $filters): array
{
    $where = [];
    $params = [];

    switch ($reporte) {
        case 'stock_bajo':
        case 'stock_actual':
            if ($filters['producto'] > 0) {
                report_where_in($where, $params, 'p.id_producto = ?', $filters['producto']);
            }
            if ($filters['categoria'] > 0) {
                report_where_in($where, $params, 'p.id_categoria = ?', $filters['categoria']);
            }
            if ($filters['almacen'] > 0) {
                report_where_in($where, $params, 's.id_almacen = ?', $filters['almacen']);
            }
            $where[] = "p.estado = 'activo'";
            $sql = "SELECT p.codigo AS Código,
                           p.nombre AS Producto,
                           COALESCE(c.nombre, p.categoria, '-') AS Categoría,
                           COALESCE(SUM(s.stock_actual), 0) AS Stock,
                           p.stock_minimo AS Mínimo,
                           CASE WHEN p.stock_minimo > 0 AND COALESCE(SUM(s.stock_actual), 0) <= p.stock_minimo THEN 'Bajo' ELSE 'Normal' END AS Estado
                    FROM productos p
                    LEFT JOIN stock_por_almacen s ON s.id_producto = p.id_producto
                    LEFT JOIN categorias_producto c ON c.id_categoria = p.id_categoria
                    WHERE " . implode(' AND ', $where) . "
                    GROUP BY p.id_producto, p.codigo, p.nombre, c.nombre, p.categoria, p.stock_minimo";
            if ($reporte === 'stock_bajo') {
                $sql .= ' HAVING p.stock_minimo > 0 AND COALESCE(SUM(s.stock_actual), 0) <= p.stock_minimo';
            }
            $sql .= ' ORDER BY p.nombre';
            break;

        case 'inventario_almacen':
            if ($filters['producto'] > 0) {
                report_where_in($where, $params, 'p.id_producto = ?', $filters['producto']);
            }
            if ($filters['categoria'] > 0) {
                report_where_in($where, $params, 'p.id_categoria = ?', $filters['categoria']);
            }
            if ($filters['almacen'] > 0) {
                report_where_in($where, $params, 'a.id_almacen = ?', $filters['almacen']);
            }
            $where[] = "p.estado = 'activo'";
            $sql = "SELECT a.nombre AS Almacén,
                           p.codigo AS Código,
                           p.nombre AS Producto,
                           COALESCE(c.nombre, p.categoria, '-') AS Categoría,
                           COALESCE(s.stock_actual, 0) AS Stock,
                           p.stock_minimo AS Mínimo
                    FROM stock_por_almacen s
                    INNER JOIN productos p ON p.id_producto = s.id_producto
                    INNER JOIN almacenes a ON a.id_almacen = s.id_almacen
                    LEFT JOIN categorias_producto c ON c.id_categoria = p.id_categoria
                    WHERE " . implode(' AND ', $where) . '
                    ORDER BY a.nombre, p.nombre';
            break;

        case 'inventario_categoria':
            if ($filters['categoria'] > 0) {
                report_where_in($where, $params, 'p.id_categoria = ?', $filters['categoria']);
            }
            if ($filters['almacen'] > 0) {
                report_where_in($where, $params, 's.id_almacen = ?', $filters['almacen']);
            }
            $where[] = "p.estado = 'activo'";
            $sql = "SELECT COALESCE(c.nombre, p.categoria, 'Sin categoría') AS Categoría,
                           COUNT(DISTINCT p.id_producto) AS Productos,
                           COALESCE(SUM(s.stock_actual), 0) AS Stock,
                           COALESCE(SUM(s.stock_actual * p.precio), 0) AS Valor
                    FROM productos p
                    LEFT JOIN stock_por_almacen s ON s.id_producto = p.id_producto
                    LEFT JOIN categorias_producto c ON c.id_categoria = p.id_categoria
                    WHERE " . implode(' AND ', $where) . '
                    GROUP BY COALESCE(c.nombre, p.categoria, \'Sin categoría\')
                    ORDER BY Categoría';
            break;

        case 'movimientos':
            if ($filters['producto'] > 0) {
                report_where_in($where, $params, 'm.id_producto = ?', $filters['producto']);
            }
            if ($filters['almacen'] > 0) {
                report_where_in($where, $params, 'm.id_almacen = ?', $filters['almacen']);
            }
            if ($filters['tipo'] !== '') {
                report_where_in($where, $params, 'm.tipo_movimiento = ?', $filters['tipo']);
            }
            if ($filters['desde'] !== '') {
                report_where_in($where, $params, 'm.created_at >= ?', $filters['desde'] . ' 00:00:00');
            }
            if ($filters['hasta'] !== '') {
                report_where_in($where, $params, 'm.created_at <= ?', $filters['hasta'] . ' 23:59:59');
            }
            $sql = "SELECT DATE_FORMAT(m.created_at, '%d/%m/%Y %H:%i') AS Fecha,
                           p.codigo AS Código,
                           p.nombre AS Producto,
                           m.tipo_movimiento AS Tipo,
                           m.cantidad AS Cantidad,
                           COALESCE(a.nombre, '-') AS Almacén,
                           u.nombre_completo AS Usuario,
                           COALESCE(m.motivo, '-') AS Observación,
                           COALESCE(m.referencia, '-') AS Referencia
                    FROM movimientos_stock m
                    INNER JOIN productos p ON p.id_producto = m.id_producto
                    INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
                    LEFT JOIN almacenes a ON a.id_almacen = m.id_almacen";
            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY m.created_at DESC, m.id_movimiento DESC LIMIT 1000';
            break;

        case 'ingresos':
            if ($filters['almacen'] > 0) {
                report_where_in($where, $params, 'i.id_almacen = ?', $filters['almacen']);
            }
            if ($filters['desde'] !== '') {
                report_where_in($where, $params, 'i.fecha >= ?', $filters['desde']);
            }
            if ($filters['hasta'] !== '') {
                report_where_in($where, $params, 'i.fecha <= ?', $filters['hasta']);
            }
            $sql = "SELECT i.id_ingreso AS Ingreso,
                           DATE_FORMAT(i.fecha, '%d/%m/%Y') AS Fecha,
                           COALESCE(pr.razon_social, '-') AS Proveedor,
                           COALESCE(a.nombre, '-') AS Almacén,
                           COALESCE(i.numero_factura, '-') AS Factura,
                           COUNT(d.id_detalle) AS Items,
                           COALESCE(SUM(d.cantidad), 0) AS Unidades,
                           COALESCE(SUM(d.cantidad * d.precio_unitario), 0) AS Total,
                           u.nombre_completo AS Usuario
                    FROM ingresos_mercaderia i
                    LEFT JOIN proveedores pr ON pr.id_proveedor = i.id_proveedor
                    LEFT JOIN almacenes a ON a.id_almacen = i.id_almacen
                    INNER JOIN usuarios u ON u.id_usuario = i.id_usuario
                    LEFT JOIN detalle_ingreso d ON d.id_ingreso = i.id_ingreso";
            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' GROUP BY i.id_ingreso, i.fecha, pr.razon_social, a.nombre, i.numero_factura, u.nombre_completo ORDER BY i.fecha DESC, i.id_ingreso DESC';
            break;

        case 'ajustes':
            if ($filters['producto'] > 0) {
                report_where_in($where, $params, 'aj.id_producto = ?', $filters['producto']);
            }
            if ($filters['almacen'] > 0) {
                report_where_in($where, $params, 'aj.id_almacen = ?', $filters['almacen']);
            }
            if ($filters['desde'] !== '') {
                report_where_in($where, $params, 'aj.created_at >= ?', $filters['desde'] . ' 00:00:00');
            }
            if ($filters['hasta'] !== '') {
                report_where_in($where, $params, 'aj.created_at <= ?', $filters['hasta'] . ' 23:59:59');
            }
            $sql = "SELECT aj.id_ajuste AS Ajuste,
                           DATE_FORMAT(aj.created_at, '%d/%m/%Y %H:%i') AS Fecha,
                           p.codigo AS Código,
                           p.nombre AS Producto,
                           COALESCE(a.nombre, '-') AS Almacén,
                           aj.stock_anterior AS Sistema,
                           aj.stock_nuevo AS Físico,
                           aj.diferencia AS Diferencia,
                           aj.motivo AS Motivo,
                           u.nombre_completo AS Usuario
                    FROM ajustes_inventario aj
                    INNER JOIN productos p ON p.id_producto = aj.id_producto
                    LEFT JOIN almacenes a ON a.id_almacen = aj.id_almacen
                    INNER JOIN usuarios u ON u.id_usuario = aj.id_usuario";
            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY aj.created_at DESC, aj.id_ajuste DESC';
            break;

        case 'traspasos':
            if ($filters['producto'] > 0) {
                report_where_in($where, $params, 't.id_producto = ?', $filters['producto']);
            }
            if ($filters['almacen'] > 0) {
                report_where_in($where, $params, '(t.id_almacen_origen = ? OR t.id_almacen_destino = ?)', $filters['almacen']);
                $params[] = $filters['almacen'];
            }
            if ($filters['desde'] !== '') {
                report_where_in($where, $params, 't.created_at >= ?', $filters['desde'] . ' 00:00:00');
            }
            if ($filters['hasta'] !== '') {
                report_where_in($where, $params, 't.created_at <= ?', $filters['hasta'] . ' 23:59:59');
            }
            $sql = "SELECT t.id_traspaso AS Traspaso,
                           DATE_FORMAT(t.created_at, '%d/%m/%Y %H:%i') AS Fecha,
                           p.codigo AS Código,
                           p.nombre AS Producto,
                           ao.nombre AS Origen,
                           ad.nombre AS Destino,
                           t.cantidad AS Cantidad,
                           u.nombre_completo AS Usuario,
                           t.estado AS Estado
                    FROM traspasos t
                    INNER JOIN productos p ON p.id_producto = t.id_producto
                    INNER JOIN almacenes ao ON ao.id_almacen = t.id_almacen_origen
                    INNER JOIN almacenes ad ON ad.id_almacen = t.id_almacen_destino
                    INNER JOIN usuarios u ON u.id_usuario = t.id_usuario";
            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY t.created_at DESC, t.id_traspaso DESC';
            break;

        case 'gerencial':
            $sql = "SELECT 'Productos activos' AS Indicador, COUNT(*) AS Valor FROM productos WHERE estado = 'activo'
                    UNION ALL SELECT 'Productos con stock bajo', COUNT(*) FROM productos WHERE estado = 'activo' AND stock_minimo > 0 AND stock_actual <= stock_minimo
                    UNION ALL SELECT 'Proveedores activos', COUNT(*) FROM proveedores WHERE estado = 'activo'
                    UNION ALL SELECT 'Valor total stock', COALESCE(SUM(stock_actual * precio), 0) FROM productos WHERE estado = 'activo'
                    UNION ALL SELECT 'Ingresos registrados', COUNT(*) FROM ingresos_mercaderia
                    UNION ALL SELECT 'Movimientos registrados', COUNT(*) FROM movimientos_stock
                    UNION ALL SELECT 'Ajustes registrados', COUNT(*) FROM ajustes_inventario
                    UNION ALL SELECT 'Traspasos registrados', COUNT(*) FROM traspasos";
            break;

        default:
            throw new RuntimeException('Reporte inválido.');
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function export_csv(string $filename, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    if ($rows) {
        fputcsv($out, array_keys($rows[0]), ';');
        foreach ($rows as $row) {
            fputcsv($out, array_values($row), ';');
        }
    } else {
        fputcsv($out, ['Sin datos'], ';');
    }
    fclose($out);
    exit;
}

$error = '';
$rows = [];
try {
    $rows = run_report($db, $reporte, $filters);
    if (($_GET['export'] ?? '') === 'csv') {
        export_csv($reporte . '_' . date('Ymd_His') . '.csv', $rows);
    }
} catch (Throwable $exception) {
    $error = 'No se pudo generar el reporte.';
}

$productos = [];
$almacenes = [];
$categorias = [];
try {
    $stmt = $db->prepare("SELECT id_producto, codigo, nombre FROM productos WHERE estado = 'activo' ORDER BY nombre");
    $stmt->execute();
    $productos = $stmt->fetchAll();

    $stmt = $db->prepare('SELECT id_almacen, nombre FROM almacenes WHERE estado = 1 ORDER BY nombre');
    $stmt->execute();
    $almacenes = $stmt->fetchAll();

    $stmt = $db->prepare('SELECT id_categoria, nombre FROM categorias_producto WHERE estado = 1 ORDER BY nombre');
    $stmt->execute();
    $categorias = $stmt->fetchAll();
} catch (Throwable $exception) {
    $error = $error !== '' ? $error : 'No se pudieron cargar filtros.';
}

$csvParams = $_GET;
$csvParams['reporte'] = $reporte;
$csvParams['export'] = 'csv';
$csvUrl = app_url('modulos/stock/reportes/index.php') . '?' . http_build_query($csvParams);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes - Bendito Jugador</title>
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
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                    <h2 class="page-title mb-0"><i class="fas fa-chart-bar"></i>Reportes</h2>
                    <div class="d-flex gap-2">
                        <a class="btn btn-success" href="<?= e($csvUrl); ?>"><i class="fas fa-file-csv me-2"></i>Exportar CSV</a>
                        <button class="btn btn-outline-secondary" type="button" disabled><i class="fas fa-file-pdf me-2"></i>PDF preparado</button>
                    </div>
                </div>

                <?php if ($error !== ''): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error); ?></div><?php endif; ?>

                <form method="GET" class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Reporte</label>
                                <select name="reporte" class="form-control">
                                    <?php foreach ($reportesDisponibles as $key => $label): ?>
                                        <option value="<?= e($key); ?>" <?= $reporte === $key ? 'selected' : ''; ?>><?= e($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Producto</label>
                                <select name="producto" class="form-control">
                                    <option value="0">Todos</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option value="<?= (int) $producto['id_producto']; ?>" <?= $filters['producto'] === (int) $producto['id_producto'] ? 'selected' : ''; ?>><?= e($producto['codigo'] . ' - ' . $producto['nombre']); ?></option>
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
                            <div class="col-md-2">
                                <label class="form-label">Categoría</label>
                                <select name="categoria" class="form-control">
                                    <option value="0">Todas</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= (int) $categoria['id_categoria']; ?>" <?= $filters['categoria'] === (int) $categoria['id_categoria'] ? 'selected' : ''; ?>><?= e($categoria['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tipo movimiento</label>
                                <select name="tipo" class="form-control">
                                    <option value="">Todos</option>
                                    <?php foreach (['ingreso', 'egreso', 'ajuste_positivo', 'ajuste_negativo', 'traspaso'] as $tipo): ?>
                                        <option value="<?= e($tipo); ?>" <?= $filters['tipo'] === $tipo ? 'selected' : ''; ?>><?= e($tipo); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2"><label class="form-label">Desde</label><input type="date" name="desde" class="form-control" value="<?= e($filters['desde']); ?>"></div>
                            <div class="col-md-2"><label class="form-label">Hasta</label><input type="date" name="hasta" class="form-control" value="<?= e($filters['hasta']); ?>"></div>
                            <div class="col-md-8 d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-2"></i>Aplicar filtros</button>
                                <a href="<?= app_url('modulos/stock/reportes/index.php'); ?>" class="btn btn-secondary">Limpiar</a>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div><span class="text-muted">Reporte actual</span><h5 class="mb-0"><?= e($reportesDisponibles[$reporte]); ?></h5></div>
                        <span class="badge bg-primary fs-6"><?= count($rows); ?> registros</span>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <?php foreach (($rows ? array_keys($rows[0]) : ['Resultado']) as $header): ?>
                                        <th><?= e($header); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?= e($value); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$rows): ?><tr><td class="text-center text-muted">Sin datos para los filtros seleccionados.</td></tr><?php endif; ?>
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
