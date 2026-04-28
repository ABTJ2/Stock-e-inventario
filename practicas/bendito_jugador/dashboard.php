<?php
require_once __DIR__ . '/includes/bootstrap.php';

require_auth();

$nombreCompleto = current_user()['full_name'] ?? 'Usuario';
$idRol = current_user()['role_id'] ?? 1;
$nombreRol = current_user()['role_name'] ?? 'Usuario';

$modulo_activo = 'stock';
$breadcrumb = 'Dashboard';
$breadcrumb_link = app_url('dashboard.php');

$totalProductos = 0;
$stockBajo = 0;
$totalProveedores = 0;
$ingresosRecientes = 0;

try {
    $conn = db();
    $stmt = $conn->query("SELECT COUNT(*) as total FROM productos WHERE estado = 'activo'");
    $totalProductos = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM productos WHERE stock_actual <= stock_minimo AND estado = 'activo'");
    $stockBajo = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM proveedores WHERE estado = 'activo'");
    $totalProveedores = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM ingresos_mercaderia WHERE DATE(fecha) = CURDATE()");
    $ingresosRecientes = $stmt->fetch()['total'] ?? 0;
} catch (Exception $e) {
    error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bendito Jugador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content-area">
                <h2 class="page-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard - Panel Principal
                </h2>
                
                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">Total Productos</span>
                            <div class="card-icon blue">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                        <div class="card-value"><?= $totalProductos; ?></div>
                        <div class="card-subtitle">Productos activos en el sistema</div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">Stock Bajo</span>
                            <div class="card-icon <?= $stockBajo > 0 ? 'red' : 'green'; ?>">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                        <div class="card-value"><?= $stockBajo; ?></div>
                        <div class="card-subtitle">Productos con stock mínimo</div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">Proveedores</span>
                            <div class="card-icon green">
                                <i class="fas fa-truck"></i>
                            </div>
                        </div>
                        <div class="card-value"><?= $totalProveedores; ?></div>
                        <div class="card-subtitle">Proveedores activos</div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">Ingresos Hoy</span>
                            <div class="card-icon orange">
                                <i class="fas fa-truck-loading"></i>
                            </div>
                        </div>
                        <div class="card-value"><?= $ingresosRecientes; ?></div>
                        <div class="card-subtitle">Ingresos de mercadería hoy</div>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <h3><i class="fas fa-bolt me-2"></i>Accesos Rápidos - Módulo de Stock</h3>
                    <div class="action-buttons">
                        <a href="<?= app_url('modulos/stock/productos/index.php'); ?>" class="action-btn">
                            <i class="fas fa-boxes"></i> Productos
                        </a>
                        <a href="<?= app_url('modulos/stock/proveedores/index.php'); ?>" class="action-btn">
                            <i class="fas fa-truck"></i> Proveedores
                        </a>
                        <a href="<?= app_url('modulos/stock/usuarios/index.php'); ?>" class="action-btn">
                            <i class="fas fa-users"></i> Usuarios
                        </a>
                        <a href="<?= app_url('modulos/stock/ingreso_mercaderia/index.php'); ?>" class="action-btn">
                            <i class="fas fa-truck-loading"></i> Ingreso de Mercadería
                        </a>
                        <a href="<?= app_url('modulos/stock/movimientos/index.php'); ?>" class="action-btn">
                            <i class="fas fa-exchange-alt"></i> Movimientos
                        </a>
                        <a href="<?= app_url('modulos/stock/consulta_stock/index.php'); ?>" class="action-btn">
                            <i class="fas fa-search"></i> Consulta de Stock
                        </a>
                        <a href="<?= app_url('modulos/stock/reportes/index.php'); ?>" class="action-btn">
                            <i class="fas fa-clipboard-list"></i> Reportes
                        </a>
                        <a href="<?= app_url('modulos/stock/traspasos/index.php'); ?>" class="action-btn">
                            <i class="fas fa-exchange-alt"></i> Traspasos
                        </a>
                        <a href="<?= app_url('modulos/stock/ajustes/index.php'); ?>" class="action-btn">
                            <i class="fas fa-sliders-h"></i> Ajustes
                        </a>
                    </div>
                </div>
                
                <?php if ($stockBajo > 0): ?>
                <div class="card mt-4" style="border-left: 4px solid var(--danger-color);">
                    <div class="card-header">
                        <span class="card-title" style="color: var(--danger);">
                            <i class="fas fa-exclamation-triangle me-2"></i>Alerta de Stock
                        </span>
                    </div>
                    <p style="color: var(--text-secondary); margin: 0;">
                        Existen <strong><?= $stockBajo; ?></strong> producto(s) con stock bajo o mínimo. 
                        <a href="<?= app_url('modulos/stock/consulta_stock/index.php'); ?>" style="color: var(--primary);">Ver detalles</a>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
