<?php
require_once __DIR__ . '/bootstrap.php';

$modulo_activo = $modulo_activo ?? 'stock';
$submodulo_activo = $submodulo_activo ?? '';
$user = current_user();

$genericModules = [
    ['key' => 'rrhh', 'title' => 'Módulo de Recursos Humanos', 'icon' => 'fa-users'],
    ['key' => 'financiero', 'title' => 'Módulo Financiero-Contable', 'icon' => 'fa-calculator'],
    ['key' => 'compras', 'title' => 'Módulo de Compras y Logística', 'icon' => 'fa-truck'],
    ['key' => 'ventas', 'title' => 'Módulo de Ventas y Marketing', 'icon' => 'fa-shopping-cart'],
    ['key' => 'produccion', 'title' => 'Módulo de Producción', 'icon' => 'fa-industry'],
];

$stockItems = [
    ['key' => 'productos', 'label' => 'Productos (ABM)', 'url' => app_url('modulos/stock/productos/index.php')],
    ['key' => 'proveedores', 'label' => 'Proveedores (ABM)', 'url' => app_url('modulos/stock/proveedores/index.php')],
    ['key' => 'usuarios', 'label' => 'Usuarios (ABM)', 'url' => app_url('modulos/stock/usuarios/index.php')],
    ['key' => 'roles', 'label' => 'Roles y permisos', 'url' => app_url('modulos/stock/admin/roles.php'), 'admin' => true],
    ['key' => 'parametros', 'label' => 'Parámetros del sistema', 'url' => app_url('modulos/stock/admin/parametros.php'), 'admin' => true],
    ['key' => 'auditoria_sistema', 'label' => 'Auditoría del sistema', 'url' => app_url('modulos/stock/admin/auditoria.php'), 'admin' => true],
    ['key' => 'ingreso_mercaderia', 'label' => 'Ingreso de mercadería', 'url' => app_url('modulos/stock/ingreso_mercaderia/index.php')],
    ['key' => 'movimientos', 'label' => 'Movimientos de stock', 'url' => app_url('modulos/stock/movimientos/index.php')],
    ['key' => 'consulta_stock', 'label' => 'Consulta de stock', 'url' => app_url('modulos/stock/consulta_stock/index.php')],
    ['key' => 'reportes', 'label' => 'Reportes', 'url' => app_url('modulos/stock/reportes/index.php')],
    ['key' => 'reportes_gerenciales', 'label' => 'Reporte gerencial', 'url' => app_url('modulos/stock/reportes/index.php?reporte=gerencial')],
    ['key' => 'respaldos', 'label' => 'Respaldos', 'url' => app_url('modulos/stock/admin/respaldos.php'), 'admin' => true],
    ['key' => 'traspasos', 'label' => 'Traspaso entre almacenes', 'url' => app_url('modulos/stock/traspasos/index.php')],
    ['key' => 'ajustes', 'label' => 'Ajuste de inventario', 'url' => app_url('modulos/stock/ajustes/index.php')],
];
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button class="sidebar-toggle" type="button" aria-label="Contraer menú">
            <i class="fas fa-bars"></i>
        </button>
        <div class="sidebar-brand">
            <img class="sidebar-brand-icon" src="<?= app_url('assets/img/bendito-jugador-icon.png'); ?>" alt="Bendito Jugador">
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
        <div class="module-item <?= $modulo_activo === 'stock' ? 'active' : ''; ?> <?= $submodulo_activo !== '' ? 'open' : ''; ?>">
            <a class="module-header" href="javascript:void(0);">
                <i class="fas fa-boxes module-icon"></i>
                <span class="module-title">Stock y Control de Inventario</span>
                <i class="fas fa-chevron-right module-arrow"></i>
            </a>
            <div class="submenu">
                <?php foreach ($stockItems as $item): ?>
                    <?php if (($item['admin'] ?? false) && !has_role('Administrador')) { continue; } ?>
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
            <div class="module-item <?= $modulo_activo === $module['key'] ? 'open active' : ''; ?>">
                <a class="module-header" href="javascript:void(0);">
                    <i class="fas <?= e($module['icon']); ?> module-icon"></i>
                    <span class="module-title"><?= e($module['title']); ?></span>
                    <i class="fas fa-chevron-right module-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="javascript:void(0);" class="submenu-item text-muted"><i class="fas fa-circle"></i><span>Próximamente</span></a>
                </div>
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
