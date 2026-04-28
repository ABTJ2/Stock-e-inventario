<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_auth();

$modulo_activo = 'stock';
$submodulo_activo = 'reportes';
$breadcrumb = 'Reportes de Auditoría';
$breadcrumb_link = app_url('dashboard.php');

$auditorias = [];
try { 
    $auditorias = db()->query("SELECT a.*, p.nombre as producto, u.nombre_completo FROM auditoria_inventario a JOIN productos p ON a.id_producto = p.id_producto JOIN usuarios u ON a.id_usuario = u.id_usuario ORDER BY a.fecha DESC LIMIT 20")->fetchAll(); 
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes de Auditoría - Bendito Jugador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome-6.5.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/../../../includes/header.php'; ?>
            <div class="content-area">
                <h2 class="page-title"><i class="fas fa-clipboard-list"></i>Reportes de Auditoría</h2>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr><th>Fecha</th><th>Producto</th><th>Stock Sistema</th><th>Stock Real</th><th>Diferencia</th><th>Usuario</th><th>Observaciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditorias as $a): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($a['fecha'])); ?></td>
                                <td><?= e($a['producto']); ?></td>
                                <td><?= $a['stock_sistema']; ?></td>
                                <td><?= $a['stock_real']; ?></td>
                                <td><span class="badge bg-<?= $a['diferencia'] >= 0 ? 'success' : 'danger'; ?>"><?= ($a['diferencia'] >= 0 ? '+' : '') . $a['diferencia']; ?></span></td>
                                <td><?= e($a['nombre_completo']); ?></td>
                                <td><?= e($a['observaciones'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($auditorias)): ?>
                            <tr><td colspan="7" class="text-center text-muted">No hay auditorías registradas.</td></tr>
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
