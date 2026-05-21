<?php
declare(strict_types=1);

if (!function_exists('navigation_modules')) {
    function navigation_modules(): array
    {
        return [
            [
                'key' => 'stock',
                'title' => 'Stock y Control de Inventario',
                'icon' => 'fa-boxes-stacked',
                'default_open' => true,
                'items' => [
                    ['key' => 'productos', 'label' => 'Productos (ABM)', 'url' => app_url('modulos/stock/productos/index.php')],
                    ['key' => 'proveedores', 'label' => 'Proveedores (ABM)', 'url' => app_url('modulos/stock/proveedores/index.php')],
                    ['key' => 'usuarios', 'label' => 'Usuarios (ABM)', 'url' => app_url('modulos/stock/usuarios/index.php')],
                    ['key' => 'roles', 'label' => 'Roles y permisos', 'url' => app_url('modulos/stock/admin/roles.php')],
                    ['key' => 'parametros', 'label' => 'Parámetros del sistema', 'url' => app_url('modulos/stock/admin/parametros.php')],
                    ['key' => 'auditoria_sistema', 'label' => 'Auditoría del sistema', 'url' => app_url('modulos/stock/admin/auditoria.php')],
                    ['key' => 'ingreso_mercaderia', 'label' => 'Ingreso de mercadería', 'url' => app_url('modulos/stock/ingreso_mercaderia/index.php')],
                    ['key' => 'movimientos', 'label' => 'Movimientos de stock', 'url' => app_url('modulos/stock/movimientos/index.php')],
                    ['key' => 'consulta_stock', 'label' => 'Consulta de stock', 'url' => app_url('modulos/stock/consulta_stock/index.php')],
                    ['key' => 'reportes', 'label' => 'Reportes', 'url' => app_url('modulos/stock/reportes/index.php')],
                    ['key' => 'reportes_gerenciales', 'label' => 'Reporte gerencial', 'url' => app_url('modulos/stock/reportes/index.php?reporte=gerencial')],
                    ['key' => 'respaldos', 'label' => 'Respaldos', 'url' => app_url('modulos/stock/admin/respaldos.php')],
                    ['key' => 'traspasos', 'label' => 'Traspaso entre almacenes', 'url' => app_url('modulos/stock/traspasos/index.php')],
                    ['key' => 'ajustes', 'label' => 'Ajuste de inventario', 'url' => app_url('modulos/stock/ajustes/index.php')],
                ],
            ],
            [
                'key' => 'rrhh',
                'title' => 'Módulo de Recursos Humanos',
                'icon' => 'fa-users',
                'items' => [
                    ['key' => 'rrhh_op_1', 'label' => 'Próximamente', 'url' => 'javascript:void(0);'],
                ],
            ],
            [
                'key' => 'financiero',
                'title' => 'Módulo Financiero-Contable',
                'icon' => 'fa-calculator',
                'items' => [
                    ['key' => 'finanzas_op_1', 'label' => 'Próximamente', 'url' => 'javascript:void(0);'],
                ],
            ],
            [
                'key' => 'compras',
                'title' => 'Módulo de Compras y Logística',
                'icon' => 'fa-truck-fast',
                'items' => [
                    ['key' => 'compras_op_1', 'label' => 'Próximamente', 'url' => 'javascript:void(0);'],
                ],
            ],
            [
                'key' => 'ventas',
                'title' => 'Módulo de Ventas y Marketing',
                'icon' => 'fa-chart-line',
                'items' => [
                    ['key' => 'ventas_op_1', 'label' => 'Próximamente', 'url' => 'javascript:void(0);'],
                ],
            ],
            [
                'key' => 'produccion',
                'title' => 'Módulo de Producción',
                'icon' => 'fa-industry',
                'items' => [
                    ['key' => 'produccion_op_1', 'label' => 'Próximamente', 'url' => 'javascript:void(0);'],
                ],
            ],
        ];
    }
}
