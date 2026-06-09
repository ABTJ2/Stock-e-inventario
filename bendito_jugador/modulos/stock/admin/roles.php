<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_role('Administrador');

$modulo_activo = 'stock';
$submodulo_activo = 'roles';
$breadcrumb = 'Gestión de Roles';
$breadcrumb_link = app_url('dashboard.php');

$db = db();
$roles = [];
$permisosPorRol = [];
$tienePermisos = false;
$error = '';

try {
    $stmt = $db->prepare(
        'SELECT r.id_rol, r.nombre_rol, r.descripcion, r.estado, r.created_at, COUNT(u.id_usuario) AS usuarios_asignados
         FROM roles r
         LEFT JOIN usuarios u ON u.id_rol = r.id_rol
         GROUP BY r.id_rol, r.nombre_rol, r.descripcion, r.estado, r.created_at
         ORDER BY r.nombre_rol'
    );
    $stmt->execute();
    $roles = $stmt->fetchAll();

    $tienePermisos = db_table_exists($db, 'permisos') && db_table_exists($db, 'rol_permiso');
    if ($tienePermisos) {
        $stmt = $db->prepare(
            'SELECT rp.id_rol, p.nombre AS permiso
             FROM rol_permiso rp
             INNER JOIN permisos p ON p.id_permiso = rp.id_permiso
             ORDER BY p.nombre'
        );
        $stmt->execute();
        foreach ($stmt->fetchAll() as $row) {
            $permisosPorRol[(int) $row['id_rol']][] = $row['permiso'];
        }
    }
} catch (Throwable $exception) {
    $error = 'No se pudieron cargar los roles.';
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
                <h2 class="page-title"><i class="fas fa-user-shield"></i>Gestión de Roles</h2>
                <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error); ?></div><?php endif; ?>
                <?php if (!$tienePermisos): ?>
                    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No existen tablas de permisos configuradas. Se muestra el listado de roles y usuarios asignados.</div>
                <?php endif; ?>

                <div class="table-container">
                    <table class="table align-middle">
                        <thead><tr><th>Rol</th><th>Descripción</th><th>Usuarios</th><th>Permisos</th><th>Estado</th></tr></thead>
                        <tbody>
                            <?php foreach ($roles as $rol): ?>
                                <tr>
                                    <td><strong><?= e($rol['nombre_rol']); ?></strong></td>
                                    <td><?= e($rol['descripcion'] ?: '-'); ?></td>
                                    <td><span class="badge bg-primary"><?= (int) $rol['usuarios_asignados']; ?></span></td>
                                    <td>
                                        <?php $permisos = $permisosPorRol[(int) $rol['id_rol']] ?? []; ?>
                                        <?php if ($permisos): ?>
                                            <?php foreach ($permisos as $permiso): ?>
                                                <span class="badge bg-light text-dark border me-1"><?= e($permiso); ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin permisos detallados</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-<?= $rol['estado'] === 'activo' ? 'success' : 'secondary'; ?>"><?= e($rol['estado']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$roles): ?><tr><td colspan="5" class="text-center text-muted">No hay roles registrados.</td></tr><?php endif; ?>
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
