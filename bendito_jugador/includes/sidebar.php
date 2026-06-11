<?php
require_once __DIR__ . '/bootstrap.php';

$modulo_activo = $modulo_activo ?? 'stock';
$submodulo_activo = $submodulo_activo ?? '';
$user = current_user();

$stockItems = [];
$menuPermitido = [
    'productos' => 'Productos (ABM)',
    'proveedores' => 'Proveedores (ABM)',
    'usuarios' => 'Usuarios (ABM)',
    'ingreso_mercaderia' => 'Ingreso de mercadería',
    'movimientos' => 'Movimientos de stock',
    'consulta_stock' => 'Consulta de stock',
    'traspasos' => 'Traspaso de producto',
    'ajustes' => 'Ajuste de inventario',
    'reportes' => 'Reportes',
];
$genericModules = [
    ['key' => 'recursos_humanos', 'title' => 'Módulo de Recursos Humanos', 'icon' => 'fa-users', 'url' => app_url('modulos/recursos_humanos/index.php')],
    ['key' => 'financiero_contable', 'title' => 'Módulo Financiero-Contable', 'icon' => 'fa-calculator', 'url' => app_url('modulos/financiero_contable/index.php')],
    ['key' => 'compras_logistica', 'title' => 'Módulo de Compras y Logística', 'icon' => 'fa-truck', 'url' => app_url('modulos/compras_logistica/index.php')],
    ['key' => 'ventas_marketing', 'title' => 'Módulo de Ventas y Marketing', 'icon' => 'fa-shopping-cart', 'url' => app_url('modulos/ventas_marketing/index.php')],
    ['key' => 'produccion', 'title' => 'Módulo de Producción', 'icon' => 'fa-industry', 'url' => app_url('modulos/produccion/index.php')],
];

foreach (app_modulos_permitidos_para_rol(current_user_role()) as $item) {
    $key = (string) $item['key'];
    if (isset($menuPermitido[$key])) {
        $item['label'] = $menuPermitido[$key];
        $stockItems[] = $item;
    }
}

$dashboardActivo = ($breadcrumb ?? '') === 'Dashboard';
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button class="sidebar-toggle" type="button" aria-label="Contraer menú">
            <i class="fas fa-bars"></i>
        </button>
        <div class="sidebar-brand">
            <img class="sidebar-brand-icon" src="<?= app_url('assets/img/bendito_jugador_icono.png'); ?>" alt="Bendito Jugador">
            <div class="sidebar-brand-text">
                <h2>BENDITO JUGADOR</h2>
                <small>ERP empresarial</small>
            </div>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <?= e(strtoupper(substr((string) ($user['full_name'] ?? $_SESSION['nombre_completo'] ?? 'U'), 0, 1))); ?>
        </div>
        <div>
            <strong><?= e($user['full_name'] ?? $_SESSION['nombre_completo'] ?? 'Usuario'); ?></strong>
            <span><?= e($user['role_name'] ?? $_SESSION['nombre_rol'] ?? 'Usuario'); ?></span>
        </div>
    </div>

    <nav class="sidebar-menu">
        <div class="module-item <?= $dashboardActivo ? 'active' : ''; ?>">
            <a class="module-header" href="<?= app_url('dashboard.php'); ?>">
                <i class="fas fa-tachometer-alt module-icon"></i>
                <span class="module-title">Dashboard</span>
            </a>
        </div>

        <div class="module-item <?= $modulo_activo === 'stock' && !$dashboardActivo ? 'active' : ''; ?> <?= $submodulo_activo !== '' ? 'open' : ''; ?>">
            <a class="module-header" href="javascript:void(0);">
                <i class="fas fa-boxes module-icon"></i>
                <span class="module-title">Stock y Control de Inventario</span>
                <i class="fas fa-chevron-right module-arrow"></i>
            </a>
            <div class="submenu">
                <?php foreach ($stockItems as $item): ?>
                    <a
                        href="<?= e($item['url']); ?>"
                        class="submenu-item <?= $submodulo_activo === $item['key'] ? 'active' : ''; ?>"
                        data-submodule="<?= e($item['key']); ?>"
                    >
                        <i class="fas fa-circle"></i><span><?= e($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php foreach ($genericModules as $module): ?>
            <div class="module-item <?= $modulo_activo === $module['key'] ? 'active' : ''; ?>">
                <a class="module-header" href="<?= e($module['url']); ?>">
                    <i class="fas <?= e($module['icon']); ?> module-icon"></i>
                    <span class="module-title"><?= e($module['title']); ?></span>
                </a>
            </div>
        <?php endforeach; ?>

    </nav>

    <div class="sidebar-footer">
        <a href="<?= app_url('logout.php'); ?>" class="btn-logout-sidebar">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar sesión</span>
        </a>
    </div>
</aside>
