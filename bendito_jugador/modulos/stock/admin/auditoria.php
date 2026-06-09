<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_role('Administrador');

$modulo_activo = 'stock';
$submodulo_activo = 'auditoria_sistema';
$breadcrumb = 'Auditoría del Sistema';
$breadcrumb_link = app_url('dashboard.php');

$db = db();
$error = '';
$auditorias = [];
$usuarios = [];
$modulos = [];
$acciones = [];
$filters = [
    'usuario' => (int) ($_GET['usuario'] ?? 0),
    'modulo' => trim((string) ($_GET['modulo'] ?? '')),
    'accion' => trim((string) ($_GET['accion'] ?? '')),
    'fecha' => trim((string) ($_GET['fecha'] ?? '')),
];

try {
    $stmt = $db->prepare('SELECT id_usuario, nombre_completo FROM usuarios ORDER BY nombre_completo');
    $stmt->execute();
    $usuarios = $stmt->fetchAll();

    $stmt = $db->prepare('SELECT DISTINCT modulo FROM auditoria_sistema ORDER BY modulo');
    $stmt->execute();
    $modulos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $db->prepare('SELECT DISTINCT accion FROM auditoria_sistema ORDER BY accion');
    $stmt->execute();
    $acciones = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $where = [];
    $params = [];
    if ($filters['usuario'] > 0) {
        $where[] = 'a.id_usuario = ?';
        $params[] = $filters['usuario'];
    }
    if ($filters['modulo'] !== '') {
        $where[] = 'a.modulo = ?';
        $params[] = $filters['modulo'];
    }
    if ($filters['accion'] !== '') {
        $where[] = 'a.accion = ?';
        $params[] = $filters['accion'];
    }
    if ($filters['fecha'] !== '') {
        $where[] = 'DATE(a.created_at) = ?';
        $params[] = $filters['fecha'];
    }

    $sql = 'SELECT a.created_at, a.accion, a.modulo, a.entidad, a.id_entidad, a.detalle, a.ip, u.nombre_completo
            FROM auditoria_sistema a
            LEFT JOIN usuarios u ON u.id_usuario = a.id_usuario';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY a.created_at DESC, a.id_auditoria DESC LIMIT 300';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $auditorias = $stmt->fetchAll();
} catch (Throwable $exception) {
    $error = 'No se pudo cargar la auditoría del sistema.';
}

$hasFilters = $filters['usuario'] > 0 || $filters['modulo'] !== '' || $filters['accion'] !== '' || $filters['fecha'] !== '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría - Bendito Jugador</title>
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
                <h2 class="page-title"><i class="fas fa-clipboard-check"></i>Auditoría del Sistema</h2>
                <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error); ?></div><?php endif; ?>

                <form method="GET" class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3"><label class="form-label">Usuario</label><select name="usuario" class="form-control"><option value="0">Todos</option><?php foreach ($usuarios as $usuario): ?><option value="<?= (int) $usuario['id_usuario']; ?>" <?= $filters['usuario'] === (int) $usuario['id_usuario'] ? 'selected' : ''; ?>><?= e($usuario['nombre_completo']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-3"><label class="form-label">Módulo</label><select name="modulo" class="form-control"><option value="">Todos</option><?php foreach ($modulos as $modulo): ?><option value="<?= e($modulo); ?>" <?= $filters['modulo'] === $modulo ? 'selected' : ''; ?>><?= e($modulo); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-3"><label class="form-label">Acción</label><select name="accion" class="form-control"><option value="">Todas</option><?php foreach ($acciones as $accion): ?><option value="<?= e($accion); ?>" <?= $filters['accion'] === $accion ? 'selected' : ''; ?>><?= e($accion); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-2"><label class="form-label">Fecha</label><input type="date" name="fecha" class="form-control" value="<?= e($filters['fecha']); ?>"></div>
                            <div class="col-md-1 d-grid"><button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i></button></div>
                            <?php if ($hasFilters): ?><div class="col-12"><a class="btn btn-secondary" href="<?= app_url('modulos/stock/admin/auditoria.php'); ?>">Limpiar filtros</a></div><?php endif; ?>
                        </div>
                    </div>
                </form>

                <div class="table-container">
                    <table class="table align-middle">
                        <thead><tr><th>Fecha</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>Entidad</th><th>Detalle</th><th>IP</th></tr></thead>
                        <tbody>
                            <?php foreach ($auditorias as $auditoria): ?>
                                <tr>
                                    <td><?= e(date('d/m/Y H:i', strtotime((string) $auditoria['created_at']))); ?></td>
                                    <td><?= e($auditoria['nombre_completo'] ?? 'Sistema'); ?></td>
                                    <td><span class="badge bg-secondary"><?= e($auditoria['modulo']); ?></span></td>
                                    <td><?= e($auditoria['accion']); ?></td>
                                    <td><?= e(($auditoria['entidad'] ?: '-') . ((int) ($auditoria['id_entidad'] ?? 0) > 0 ? ' #' . (int) $auditoria['id_entidad'] : '')); ?></td>
                                    <td><?= e($auditoria['detalle'] ?: '-'); ?></td>
                                    <td><?= e($auditoria['ip'] ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$auditorias): ?><tr><td colspan="7" class="text-center text-muted">No hay eventos de auditoría para los filtros seleccionados.</td></tr><?php endif; ?>
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
