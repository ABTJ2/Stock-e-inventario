<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_module_access('ajustes');

$modulo_activo = 'stock';
$submodulo_activo = 'ajustes';
$breadcrumb = 'Ajuste de Inventario';
$breadcrumb_link = app_url('dashboard.php');

$db = db();
$error = '';
$success = '';
$userId = (int) (current_user()['id'] ?? 0);

$productos = [];
$almacenes = [];
$stockPorAlmacen = [];
$ajustes = [];

try {
    $productos = $db->query("SELECT id_producto, codigo, nombre FROM productos WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
    $almacenes = $db->query('SELECT id_almacen, nombre FROM almacenes WHERE estado = 1 ORDER BY nombre')->fetchAll();

    $stmt = $db->query('SELECT id_almacen, id_producto, stock_actual FROM stock_por_almacen');
    foreach ($stmt->fetchAll() as $row) {
        $stockPorAlmacen[(int) $row['id_almacen']][(int) $row['id_producto']] = (int) $row['stock_actual'];
    }
} catch (Throwable $exception) {
    $error = 'No se pudieron cargar los datos iniciales.';
}

if (request_method_is('POST') && ($_POST['action'] ?? '') === 'crear') {
    $idAlmacen = (int) ($_POST['id_almacen'] ?? 0);
    $idProducto = (int) ($_POST['id_producto'] ?? 0);
    $stockFisico = (int) ($_POST['stock_fisico'] ?? -1);
    $motivo = trim((string) ($_POST['motivo'] ?? ''));

    try {
        if ($userId <= 0) {
            throw new RuntimeException('No se encontró el usuario logueado.');
        }

        if ($idAlmacen <= 0 || $idProducto <= 0 || $stockFisico < 0 || $motivo === '') {
            throw new RuntimeException('Seleccione almacén, producto, stock físico válido y motivo.');
        }

        $db->beginTransaction();

        $stmt = $db->prepare('SELECT id_almacen FROM almacenes WHERE id_almacen = ? AND estado = 1 FOR UPDATE');
        $stmt->execute([$idAlmacen]);
        if (!$stmt->fetch()) {
            throw new RuntimeException('Almacén inválido o inactivo.');
        }

        $stmt = $db->prepare("SELECT id_producto FROM productos WHERE id_producto = ? AND estado = 'activo' FOR UPDATE");
        $stmt->execute([$idProducto]);
        if (!$stmt->fetch()) {
            throw new RuntimeException('Producto inválido o inactivo.');
        }

        $stmt = $db->prepare(
            'SELECT stock_actual
             FROM stock_por_almacen
             WHERE id_producto = ? AND id_almacen = ?
             FOR UPDATE'
        );
        $stmt->execute([$idProducto, $idAlmacen]);
        $stockRow = $stmt->fetch();
        $stockSistema = (int) ($stockRow['stock_actual'] ?? 0);
        $diferencia = $stockFisico - $stockSistema;

        $stmt = $db->prepare(
            "INSERT INTO ajustes_inventario (id_producto, id_usuario, id_almacen, stock_anterior, stock_nuevo, diferencia, motivo, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'aprobado')"
        );
        $stmt->execute([$idProducto, $userId, $idAlmacen, $stockSistema, $stockFisico, $diferencia, $motivo]);
        $idAjuste = (int) $db->lastInsertId();

        if ($stockRow) {
            $stmt = $db->prepare('UPDATE stock_por_almacen SET stock_actual = ? WHERE id_producto = ? AND id_almacen = ?');
            $stmt->execute([$stockFisico, $idProducto, $idAlmacen]);
        } else {
            $stmt = $db->prepare('INSERT INTO stock_por_almacen (id_producto, id_almacen, stock_actual, stock_reservado) VALUES (?, ?, ?, 0)');
            $stmt->execute([$idProducto, $idAlmacen, $stockFisico]);
        }

        $stmt = $db->prepare(
            'UPDATE productos
             SET stock_actual = (SELECT COALESCE(SUM(s.stock_actual), 0) FROM stock_por_almacen s WHERE s.id_producto = ?)
             WHERE id_producto = ?'
        );
        $stmt->execute([$idProducto, $idProducto]);

        $tipoMovimiento = $diferencia < 0 ? 'ajuste_negativo' : 'ajuste_positivo';
        $stmt = $db->prepare(
            'INSERT INTO movimientos_stock
                (id_producto, id_usuario, id_almacen, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, referencia, entidad_origen, id_entidad_origen)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $idProducto,
            $userId,
            $idAlmacen,
            $tipoMovimiento,
            abs($diferencia),
            $stockSistema,
            $stockFisico,
            $motivo,
            'ajuste:' . $idAjuste,
            'ajuste',
            $idAjuste,
        ]);

        $stmt = $db->prepare(
            'INSERT INTO auditoria_inventario (id_producto, id_almacen, id_usuario, stock_sistema, stock_real, diferencia, observaciones)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$idProducto, $idAlmacen, $userId, $stockSistema, $stockFisico, $diferencia, $motivo]);

        audit_event('ajuste_inventario', 'stock', $userId, 'Ajuste #' . $idAjuste . ' registrado.');
        $db->commit();
        $success = 'Ajuste de inventario registrado correctamente.';
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = $exception->getMessage();
    }
}

try {
    $ajustes = $db->query(
        "SELECT aj.id_ajuste,
                aj.created_at,
                aj.stock_anterior,
                aj.stock_nuevo,
                aj.diferencia,
                aj.motivo,
                aj.estado,
                p.codigo,
                p.nombre AS producto,
                al.nombre AS almacen_nombre,
                u.nombre_completo
         FROM ajustes_inventario aj
         INNER JOIN productos p ON p.id_producto = aj.id_producto
         LEFT JOIN almacenes al ON al.id_almacen = aj.id_almacen
         INNER JOIN usuarios u ON u.id_usuario = aj.id_usuario
         ORDER BY aj.created_at DESC, aj.id_ajuste DESC
         LIMIT 20"
    )->fetchAll();
} catch (Throwable $exception) {
    if ($error === '') {
        $error = 'No se pudo cargar el listado de ajustes.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ajuste de Inventario - Bendito Jugador</title>
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
                    <h2 class="page-title"><i class="fas fa-sliders-h"></i>Ajuste de Inventario</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoAjusteModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Ajuste
                    </button>
                </div>

                <?php if ($error !== ''): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error); ?></div><?php endif; ?>
                <?php if ($success !== ''): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= e($success); ?></div><?php endif; ?>

                <div class="table-container">
                    <table class="table align-middle">
                        <thead>
                            <tr><th>ID</th><th>Fecha</th><th>Producto</th><th>Almacén</th><th>Sistema</th><th>Físico</th><th>Diferencia</th><th>Motivo</th><th>Usuario</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ajustes as $ajuste): ?>
                                <?php $diferencia = (int) $ajuste['diferencia']; ?>
                                <tr>
                                    <td><?= (int) $ajuste['id_ajuste']; ?></td>
                                    <td><?= e(date('d/m/Y H:i', strtotime((string) $ajuste['created_at']))); ?></td>
                                    <td><?= e($ajuste['codigo'] . ' - ' . $ajuste['producto']); ?></td>
                                    <td><?= e($ajuste['almacen_nombre'] ?? '-'); ?></td>
                                    <td><?= (int) $ajuste['stock_anterior']; ?></td>
                                    <td><strong><?= (int) $ajuste['stock_nuevo']; ?></strong></td>
                                    <td><span class="badge bg-<?= $diferencia < 0 ? 'danger' : 'success'; ?>"><?= ($diferencia > 0 ? '+' : '') . $diferencia; ?></span></td>
                                    <td><?= e($ajuste['motivo']); ?></td>
                                    <td><?= e($ajuste['nombre_completo']); ?></td>
                                    <td><span class="badge bg-success"><?= e($ajuste['estado']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$ajustes): ?>
                                <tr><td colspan="10" class="text-center text-muted">No hay ajustes registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="nuevoAjusteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Ajuste de Inventario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" onsubmit="return validarAjuste()">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">

                        <div class="form-group">
                            <label class="form-label">Almacén *</label>
                            <select name="id_almacen" class="form-control" id="almacenSelect" required onchange="actualizarStockActual()">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($almacenes as $almacen): ?>
                                    <option value="<?= (int) $almacen['id_almacen']; ?>"><?= e($almacen['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Producto *</label>
                            <select name="id_producto" class="form-control" id="productoSelect" required onchange="actualizarStockActual()">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($productos as $producto): ?>
                                    <option value="<?= (int) $producto['id_producto']; ?>"><?= e($producto['codigo'] . ' - ' . $producto['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Stock sistema</label>
                                <input type="text" id="stockActual" class="form-control" readonly value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Stock físico contado *</label>
                                <input type="number" name="stock_fisico" id="stockFisico" class="form-control" required min="0" step="1" oninput="calcularDiferencia()">
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <label class="form-label">Diferencia</label>
                            <input type="text" id="diferencia" class="form-control" readonly value="0">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Motivo del ajuste *</label>
                            <textarea name="motivo" class="form-control" rows="3" placeholder="Ej: Conteo físico, merma, corrección de carga..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Registrar Ajuste</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    const stockPorAlmacen = <?= json_encode($stockPorAlmacen, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

    function stockSistemaSeleccionado() {
        const almacenId = document.getElementById('almacenSelect').value;
        const productoId = document.getElementById('productoSelect').value;
        return parseInt((stockPorAlmacen[almacenId] || {})[productoId] || 0, 10);
    }

    function actualizarStockActual() {
        document.getElementById('stockActual').value = stockSistemaSeleccionado();
        calcularDiferencia();
    }

    function calcularDiferencia() {
        const stockSistema = stockSistemaSeleccionado();
        const stockFisicoInput = document.getElementById('stockFisico').value;
        const stockFisico = stockFisicoInput === '' ? stockSistema : parseInt(stockFisicoInput, 10);
        const diferencia = stockFisico - stockSistema;
        const diferenciaInput = document.getElementById('diferencia');
        diferenciaInput.value = (diferencia > 0 ? '+' : '') + diferencia;
        diferenciaInput.classList.toggle('is-invalid', stockFisico < 0 || Number.isNaN(stockFisico));
    }

    function validarAjuste() {
        const stockFisico = parseInt(document.getElementById('stockFisico').value, 10);
        if (!Number.isInteger(stockFisico) || stockFisico < 0) {
            alert('El stock físico no puede ser negativo.');
            return false;
        }
        return true;
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
