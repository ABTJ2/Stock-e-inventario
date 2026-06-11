<?php
declare(strict_types=1);

if (!function_exists('app_roles_permisos')) {
    function app_roles_permisos(): array
    {
        return [
            'Administrador' => [
                'descripcion' => 'Acceso total al sistema, usuarios, roles, configuracion y todos los modulos de stock.',
                'modulos' => [
                    'dashboard', 'productos', 'proveedores', 'usuarios', 'roles', 'parametros', 'auditoria_sistema',
                    'ingreso_mercaderia', 'movimientos', 'consulta_stock', 'reportes', 'reportes_gerenciales',
                    'respaldos', 'traspasos', 'ajustes',
                ],
            ],
            'Supervisor Administrativo' => [
                'descripcion' => 'Gestion operativa y administrativa de productos, proveedores, usuarios, traspasos, ajustes y reportes.',
                'modulos' => ['dashboard', 'productos', 'proveedores', 'usuarios', 'ingreso_mercaderia', 'movimientos', 'consulta_stock', 'reportes', 'traspasos', 'ajustes'],
            ],
            'Empleado / Operario' => [
                'descripcion' => 'Operacion diaria: consulta de productos, movimientos, traspasos, stock e informes basicos.',
                'modulos' => ['dashboard', 'productos', 'ingreso_mercaderia', 'movimientos', 'consulta_stock', 'reportes', 'traspasos'],
            ],
            'Supervisor Auditor' => [
                'descripcion' => 'Control de stock minimo, ajustes, auditoria e informes.',
                'modulos' => ['dashboard', 'consulta_stock', 'reportes', 'auditoria_sistema', 'ajustes'],
            ],
            'Gerente Zonal' => [
                'descripcion' => 'Consulta general, reportes y vista gerencial.',
                'modulos' => ['dashboard', 'consulta_stock', 'reportes', 'reportes_gerenciales'],
            ],
        ];
    }
}

if (!function_exists('app_normalizar_rol')) {
    function app_normalizar_rol(string $rol): string
    {
        $rol = trim($rol);
        $aliasRoles = [
            'Empleado' => 'Empleado / Operario',
            'Operario' => 'Empleado / Operario',
            'Empleado/Operario' => 'Empleado / Operario',
            'Empleado / Operario' => 'Empleado / Operario',
        ];

        return $aliasRoles[$rol] ?? $rol;
    }
}

if (!function_exists('app_modulos_stock')) {
    function app_modulos_stock(): array
    {
        return [
            ['key' => 'productos', 'label' => 'Productos (ABM)', 'url' => app_url('modulos/stock/productos/index.php'), 'icon' => 'fa-box'],
            ['key' => 'proveedores', 'label' => 'Proveedores (ABM)', 'url' => app_url('modulos/stock/proveedores/index.php'), 'icon' => 'fa-truck'],
            ['key' => 'usuarios', 'label' => 'Usuarios (ABM)', 'url' => app_url('modulos/stock/usuarios/index.php'), 'icon' => 'fa-users'],
            ['key' => 'roles', 'label' => 'Roles y permisos', 'url' => app_url('modulos/stock/admin/roles.php'), 'icon' => 'fa-user-shield'],
            ['key' => 'parametros', 'label' => 'Parámetros del sistema', 'url' => app_url('modulos/stock/admin/parametros.php'), 'icon' => 'fa-sliders'],
            ['key' => 'auditoria_sistema', 'label' => 'Auditoría del sistema', 'url' => app_url('modulos/stock/admin/auditoria.php'), 'icon' => 'fa-clock-rotate-left'],
            ['key' => 'ingreso_mercaderia', 'label' => 'Ingreso de mercadería', 'url' => app_url('modulos/stock/ingreso_mercaderia/index.php'), 'icon' => 'fa-dolly'],
            ['key' => 'movimientos', 'label' => 'Movimientos de stock', 'url' => app_url('modulos/stock/movimientos/index.php'), 'icon' => 'fa-right-left'],
            ['key' => 'consulta_stock', 'label' => 'Consulta de stock', 'url' => app_url('modulos/stock/consulta_stock/index.php'), 'icon' => 'fa-magnifying-glass-chart'],
            ['key' => 'reportes', 'label' => 'Reportes', 'url' => app_url('modulos/stock/reportes/index.php'), 'icon' => 'fa-chart-column'],
            ['key' => 'reportes_gerenciales', 'label' => 'Reporte gerencial', 'url' => app_url('modulos/stock/reportes/index.php?reporte=gerencial'), 'icon' => 'fa-chart-line'],
            ['key' => 'respaldos', 'label' => 'Respaldos', 'url' => app_url('modulos/stock/admin/respaldos.php'), 'icon' => 'fa-database'],
            ['key' => 'traspasos', 'label' => 'Traspaso entre almacenes', 'url' => app_url('modulos/stock/traspasos/index.php'), 'icon' => 'fa-truck-ramp-box'],
            ['key' => 'ajustes', 'label' => 'Ajuste de inventario', 'url' => app_url('modulos/stock/ajustes/index.php'), 'icon' => 'fa-clipboard-check'],
        ];
    }
}

if (!function_exists('app_rol_tiene_permiso')) {
    function app_rol_tiene_permiso(string $rol, string $modulo): bool
    {
        $roles = app_roles_permisos();
        $rolNormalizado = app_normalizar_rol($rol);

        if (!isset($roles[$rolNormalizado])) {
            return false;
        }

        foreach ($roles[$rolNormalizado]['modulos'] as $moduloPermitido) {
            if ($moduloPermitido === $modulo) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('app_modulos_permitidos_para_rol')) {
    function app_modulos_permitidos_para_rol(string $rol): array
    {
        $itemsPermitidos = [];

        foreach (app_modulos_stock() as $item) {
            if (app_rol_tiene_permiso($rol, (string) $item['key'])) {
                $itemsPermitidos[] = $item;
            }
        }

        return $itemsPermitidos;
    }
}

if (!function_exists('require_module_access')) {
    function require_module_access(string $modulo): void
    {
        require_auth();

        if (app_rol_tiene_permiso(current_user_role(), $modulo)) {
            return;
        }

        http_response_code(403);
        exit('Acceso denegado para este rol.');
    }
}
