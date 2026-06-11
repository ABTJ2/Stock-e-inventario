<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_module_access('parametros');

$modulo_activo = 'stock';
$submodulo_activo = 'parametros';
$breadcrumb = 'Parámetros del Sistema';
$breadcrumb_link = app_url('dashboard.php');

$db = db();
$error = '';
$success = '';
$adminId = (int) (current_user()['id'] ?? 0);

if (request_method_is('POST') && ($_POST['action'] ?? '') === 'guardar') {
    $valores = is_array($_POST['parametros'] ?? null) ? $_POST['parametros'] : [];

    try {
        $db->beginTransaction();
        $stmt = $db->prepare('UPDATE parametros_sistema SET valor = ? WHERE id_parametro = ? AND estado = 1');
        foreach ($valores as $id => $valor) {
            $stmt->execute([trim((string) $valor), (int) $id]);
        }
        audit_event('editar', 'parametros', $adminId, 'Parámetros del sistema actualizados.', 'parametros_sistema', null);
        $db->commit();
        $success = 'Parámetros actualizados correctamente.';
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = 'No se pudieron guardar los parámetros.';
    }
}

$parametros = [];
try {
    $stmt = $db->prepare('SELECT id_parametro, clave, valor, descripcion, tipo, updated_at FROM parametros_sistema WHERE estado = 1 ORDER BY clave');
    $stmt->execute();
    $parametros = $stmt->fetchAll();
} catch (Throwable $exception) {
    $error = $error !== '' ? $error : 'No se pudieron cargar los parámetros.';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Parámetros - Bendito Jugador</title>
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
                <h2 class="page-title"><i class="fas fa-sliders-h"></i>Parámetros del Sistema</h2>
                <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error); ?></div><?php endif; ?>
                <?php if ($success !== ''): ?><div class="alert alert-success"><?= e($success); ?></div><?php endif; ?>

                <form method="POST" class="card border-0 shadow-sm">
                    <div class="card-body">
                        <input type="hidden" name="action" value="guardar">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead><tr><th>Clave</th><th>Valor</th><th>Tipo</th><th>Descripción</th><th>Actualizado</th></tr></thead>
                                <tbody>
                                    <?php foreach ($parametros as $parametro): ?>
                                        <tr>
                                            <td><strong><?= e($parametro['clave']); ?></strong></td>
                                            <td><input type="text" name="parametros[<?= (int) $parametro['id_parametro']; ?>]" class="form-control" value="<?= e($parametro['valor']); ?>"></td>
                                            <td><span class="badge bg-secondary"><?= e($parametro['tipo']); ?></span></td>
                                            <td><?= e($parametro['descripcion'] ?: '-'); ?></td>
                                            <td><?= $parametro['updated_at'] ? e(date('d/m/Y H:i', strtotime((string) $parametro['updated_at']))) : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (!$parametros): ?><tr><td colspan="5" class="text-center text-muted">No hay parámetros configurados.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
