<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

require_module_access('roles');

$modulo_activo = 'stock';
$submodulo_activo = 'roles';
$breadcrumb = 'Gestión de Roles';
$breadcrumb_link = app_url('dashboard.php');

$db = db();
$rolesDb = [];
$usuariosPorRol = [];
$error = '';
$rolesPermisos = app_roles_permisos();
$modulosStock = app_modulos_stock();

try {
    $stmt = $db->prepare(
        'SELECT r.id_rol, r.nombre_rol, r.descripcion, r.estado, COUNT(u.id_usuario) AS usuarios_asignados
         FROM roles r
         LEFT JOIN usuarios u ON u.id_rol = r.id_rol
         GROUP BY r.id_rol, r.nombre_rol, r.descripcion, r.estado
         ORDER BY r.nombre_rol'
    );
    $stmt->execute();
    $rolesDb = $stmt->fetchAll();

    foreach ($rolesDb as $rol) {
        $usuariosPorRol[app_normalizar_rol((string) $rol['nombre_rol'])] = (int) $rol['usuarios_asignados'];
    }
} catch (Throwable $exception) {
    $error = 'No se pudieron cargar los roles.';
}

$rolesListado = [];
foreach ($rolesPermisos as $nombreRol => $infoRol) {
    $rolesListado[] = [
        'nombre' => $nombreRol,
        'descripcion' => $infoRol['descripcion'],
        'usuarios_asignados' => $usuariosPorRol[$nombreRol] ?? 0,
        'modulos' => $infoRol['modulos'],
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Roles - Bendito Jugador</title>
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
                <h2 class="page-title"><i class="fas fa-user-shield"></i>Gestión de Roles y Permisos</h2>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><?= e($error); ?></div>
                <?php endif; ?>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Los permisos se controlan con un arreglo multidimensional en <strong>includes/permissions.php</strong>. El menú y el acceso a páginas se filtran según el rol del usuario.
                </div>

                <div class="table-container mb-4">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Rol</th>
                                <th>Descripción</th>
                                <th>Usuarios</th>
                                <th>Módulos habilitados</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rolesListado as $rol): ?>
                                <tr>
                                    <td><strong><?= e($rol['nombre']); ?></strong></td>
                                    <td><?= e($rol['descripcion']); ?></td>
                                    <td><span class="badge bg-primary"><?= (int) $rol['usuarios_asignados']; ?></span></td>
                                    <td>
                                        <?php foreach ($rol['modulos'] as $modulo): ?>
                                            <span class="badge bg-light text-dark border me-1 mb-1"><?= e($modulo); ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Módulo</th>
                                    <?php foreach ($rolesListado as $rol): ?>
                                        <th class="text-center"><?= e($rol['nombre']); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modulosStock as $modulo): ?>
                                    <tr>
                                        <td><i class="fas <?= e($modulo['icon']); ?> me-2 text-primary"></i><?= e($modulo['label']); ?></td>
                                        <?php foreach ($rolesListado as $rol): ?>
                                            <?php $permitido = app_rol_tiene_permiso($rol['nombre'], (string) $modulo['key']); ?>
                                            <td class="text-center">
                                                <span class="badge bg-<?= $permitido ? 'success' : 'secondary'; ?>">
                                                    <?= $permitido ? 'Sí' : 'No'; ?>
                                                </span>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php include __DIR__ . '/../../../includes/footer.php'; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
