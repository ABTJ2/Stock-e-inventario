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
                'items' => app_modulos_stock(),
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
