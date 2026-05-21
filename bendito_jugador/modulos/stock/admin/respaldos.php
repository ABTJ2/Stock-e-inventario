<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_role('Administrador');

$modulo_activo = 'stock';
$submodulo_activo = 'respaldos';
$breadcrumb = 'Respaldos del Sistema';
$breadcrumb_link = app_url('dashboard.php');

$error = '';
$success = '';
$adminId = (int) (current_user()['id'] ?? 0);
$backupDir = realpath(APP_ROOT . '/../backups') ?: APP_ROOT . '/backups';

if (request_method_is('POST') && ($_POST['action'] ?? '') === 'generar') {
    try {
        if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true)) {
            throw new RuntimeException('No se pudo crear la carpeta de backups.');
        }

        $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        if (!is_file($mysqldump)) {
            throw new RuntimeException('No se encontró mysqldump en XAMPP.');
        }

        $fileName = 'backup_bendito_jugador_' . date('Ymd_His') . '.sql';
        $target = rtrim($backupDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
        $passwordPart = DB_PASS !== '' ? ' --password=' . escapeshellarg(DB_PASS) : '';
        $command = escapeshellarg($mysqldump)
            . ' --host=' . escapeshellarg(DB_HOST)
            . ' --user=' . escapeshellarg(DB_USER)
            . $passwordPart
            . ' --default-character-set=utf8mb4 '
            . escapeshellarg(DB_NAME)
            . ' > ' . escapeshellarg($target);

        $output = [];
        $code = 0;
        exec($command, $output, $code);
        if ($code !== 0 || !is_file($target) || filesize($target) === 0) {
            throw new RuntimeException('No se pudo generar el backup SQL.');
        }

        audit_event('generar_backup', 'respaldos', $adminId, 'Backup generado: ' . $fileName, 'backup', null);
        $success = 'Backup generado correctamente: ' . $fileName;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$backups = [];
if (is_dir($backupDir)) {
    foreach (glob(rtrim($backupDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.sql') ?: [] as $file) {
        $backups[] = [
            'nombre' => basename($file),
            'tamano' => filesize($file),
            'fecha' => filemtime($file),
        ];
    }
    usort($backups, static fn (array $a, array $b): int => $b['fecha'] <=> $a['fecha']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Respaldos - Bendito Jugador</title>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="page-title"><i class="fas fa-database"></i>Respaldos del Sistema</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="generar">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-download me-2"></i>Generar backup SQL</button>
                    </form>
                </div>

                <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error); ?></div><?php endif; ?>
                <?php if ($success !== ''): ?><div class="alert alert-success"><?= e($success); ?></div><?php endif; ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Carpeta</div><strong><?= e($backupDir); ?></strong></div></div></div>
                    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Archivos encontrados</div><h3 class="mb-0"><?= count($backups); ?></h3></div></div></div>
                    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Eliminación automática</div><span class="badge bg-success">Desactivada</span></div></div></div>
                </div>

                <div class="table-container">
                    <table class="table align-middle">
                        <thead><tr><th>Archivo</th><th>Tamaño</th><th>Fecha</th></tr></thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td><i class="fas fa-file-code me-2 text-primary"></i><?= e($backup['nombre']); ?></td>
                                    <td><?= number_format((float) $backup['tamano'] / 1024, 2, ',', '.'); ?> KB</td>
                                    <td><?= e(date('d/m/Y H:i', (int) $backup['fecha'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$backups): ?><tr><td colspan="3" class="text-center text-muted">No hay backups SQL en la carpeta configurada.</td></tr><?php endif; ?>
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
