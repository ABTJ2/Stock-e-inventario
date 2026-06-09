<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_role('Administrador');

$modulo_activo = 'stock';
$submodulo_activo = 'usuarios';
$breadcrumb = 'Gestión de Usuarios';
$breadcrumb_link = app_url('dashboard.php');

$db = db();
$error = '';
$success = '';
$adminId = (int) (current_user()['id'] ?? 0);
$claveTemporal = 'password123';

function usuario_existe(PDO $db, string $usuario, int $exceptoId = 0): bool
{
    $sql = 'SELECT COUNT(*) FROM usuarios WHERE usuario = ?';
    $params = [$usuario];

    if ($exceptoId > 0) {
        $sql .= ' AND id_usuario <> ?';
        $params[] = $exceptoId;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
}

if (request_method_is('POST')) {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'crear') {
            $usuario = strtolower(trim((string) ($_POST['usuario'] ?? '')));
            $nombreCompleto = trim((string) ($_POST['nombre_completo'] ?? ''));
            $idRol = (int) ($_POST['id_rol'] ?? 0);

            if ($usuario === '' || $nombreCompleto === '' || $idRol <= 0) {
                throw new RuntimeException('Todos los campos son obligatorios.');
            }

            if (!preg_match('/^[a-z0-9._-]{3,50}$/', $usuario)) {
                throw new RuntimeException('El usuario debe tener 3 a 50 caracteres y usar letras, números, punto, guion o guion bajo.');
            }

            if (usuario_existe($db, $usuario)) {
                throw new RuntimeException('El nombre de usuario ya está en uso.');
            }

            $stmt = $db->prepare("SELECT COUNT(*) FROM roles WHERE id_rol = ? AND estado = 'activo'");
            $stmt->execute([$idRol]);
            if ((int) $stmt->fetchColumn() === 0) {
                throw new RuntimeException('Rol inválido o inactivo.');
            }

            $stmt = $db->prepare(
                "INSERT INTO usuarios (usuario, clave, nombre_completo, id_rol, estado, primer_ingreso)
                 VALUES (?, ?, ?, ?, 'activo', 1)"
            );
            $stmt->execute([$usuario, password_hash($claveTemporal, PASSWORD_DEFAULT), $nombreCompleto, $idRol]);
            $nuevoId = (int) $db->lastInsertId();

            audit_event('crear', 'usuarios', $adminId, 'Usuario creado: ' . $usuario, 'usuarios', $nuevoId);
            $success = 'Usuario creado. Contraseña temporal: ' . $claveTemporal;
        }

        if ($action === 'editar') {
            $id = (int) ($_POST['id_usuario'] ?? 0);
            $nombreCompleto = trim((string) ($_POST['nombre_completo'] ?? ''));
            $idRol = (int) ($_POST['id_rol'] ?? 0);

            if ($id <= 0 || $nombreCompleto === '' || $idRol <= 0) {
                throw new RuntimeException('Datos inválidos para actualizar el usuario.');
            }

            $stmt = $db->prepare("SELECT COUNT(*) FROM roles WHERE id_rol = ? AND estado = 'activo'");
            $stmt->execute([$idRol]);
            if ((int) $stmt->fetchColumn() === 0) {
                throw new RuntimeException('Rol inválido o inactivo.');
            }

            $stmt = $db->prepare('UPDATE usuarios SET nombre_completo = ?, id_rol = ? WHERE id_usuario = ?');
            $stmt->execute([$nombreCompleto, $idRol, $id]);

            audit_event('editar', 'usuarios', $adminId, 'Usuario actualizado #' . $id, 'usuarios', $id);
            $success = 'Usuario actualizado correctamente.';
        }

        if ($action === 'reset') {
            $id = (int) ($_POST['id_usuario'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException('Usuario inválido.');
            }

            $stmt = $db->prepare('UPDATE usuarios SET clave = ?, primer_ingreso = 1 WHERE id_usuario = ?');
            $stmt->execute([password_hash($claveTemporal, PASSWORD_DEFAULT), $id]);

            audit_event('reset_password', 'usuarios', $adminId, 'Contraseña reseteada y primer_ingreso activado.', 'usuarios', $id);
            $success = 'Contraseña reseteada a "' . $claveTemporal . '". El usuario deberá cambiarla al ingresar.';
        }

        if ($action === 'estado') {
            $id = (int) ($_POST['id_usuario'] ?? 0);
            $estado = (string) ($_POST['estado'] ?? '');

            if ($id <= 0 || !in_array($estado, ['activo', 'inactivo'], true)) {
                throw new RuntimeException('Estado inválido.');
            }

            if ($id === $adminId && $estado === 'inactivo') {
                throw new RuntimeException('No podés desactivar tu propio usuario.');
            }

            $stmt = $db->prepare('UPDATE usuarios SET estado = ? WHERE id_usuario = ?');
            $stmt->execute([$estado, $id]);

            audit_event('cambiar_estado', 'usuarios', $adminId, 'Estado cambiado a ' . $estado, 'usuarios', $id);
            $success = $estado === 'activo' ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.';
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$usuarios = [];
$roles = [];
try {
    $usuarios = $db->query(
        'SELECT u.id_usuario, u.usuario, u.nombre_completo, u.id_rol, u.estado, u.primer_ingreso, u.fecha_ultimo_login, u.created_at, r.nombre_rol
         FROM usuarios u
         INNER JOIN roles r ON r.id_rol = u.id_rol
         ORDER BY u.estado ASC, u.nombre_completo ASC'
    )->fetchAll();

    $roles = $db->query("SELECT id_rol, nombre_rol FROM roles WHERE estado = 'activo' ORDER BY nombre_rol")->fetchAll();
} catch (Throwable $exception) {
    $error = $error !== '' ? $error : 'Error al cargar usuarios.';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios - Bendito Jugador</title>
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
                    <h2 class="page-title"><i class="fas fa-users"></i>Gestión de Usuarios</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoUsuarioModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Usuario
                    </button>
                </div>

                <?php if ($error !== ''): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error); ?></div><?php endif; ?>
                <?php if ($success !== ''): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= e($success); ?></div><?php endif; ?>

                <div class="table-container">
                    <table class="table align-middle">
                        <thead>
                            <tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Estado</th><th>Primer ingreso</th><th>Último login</th><th class="text-end">Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr class="<?= $usuario['estado'] === 'inactivo' ? 'table-light text-muted' : ''; ?>">
                                    <td><strong><?= e($usuario['usuario']); ?></strong></td>
                                    <td><?= e($usuario['nombre_completo']); ?></td>
                                    <td><span class="badge bg-info"><?= e($usuario['nombre_rol']); ?></span></td>
                                    <td><span class="badge bg-<?= $usuario['estado'] === 'activo' ? 'success' : 'secondary'; ?>"><?= e($usuario['estado']); ?></span></td>
                                    <td><?= (int) $usuario['primer_ingreso'] === 1 ? '<span class="badge bg-warning text-dark">Sí</span>' : '<span class="badge bg-success">No</span>'; ?></td>
                                    <td><?= $usuario['fecha_ultimo_login'] ? e(date('d/m/Y H:i', strtotime((string) $usuario['fecha_ultimo_login']))) : 'Nunca'; ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editarUsuario<?= (int) $usuario['id_usuario']; ?>"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#resetUsuario<?= (int) $usuario['id_usuario']; ?>"><i class="fas fa-key"></i></button>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="estado">
                                            <input type="hidden" name="id_usuario" value="<?= (int) $usuario['id_usuario']; ?>">
                                            <input type="hidden" name="estado" value="<?= $usuario['estado'] === 'activo' ? 'inactivo' : 'activo'; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-<?= $usuario['estado'] === 'activo' ? 'danger' : 'success'; ?>" onclick="return confirm('¿Confirmar cambio de estado?')" <?= (int) $usuario['id_usuario'] === $adminId && $usuario['estado'] === 'activo' ? 'disabled' : ''; ?>>
                                                <i class="fas fa-<?= $usuario['estado'] === 'activo' ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$usuarios): ?>
                                <tr><td colspan="7" class="text-center text-muted">No hay usuarios registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($usuarios as $usuario): ?>
        <div class="modal fade" id="editarUsuario<?= (int) $usuario['id_usuario']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Editar Usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="editar">
                            <input type="hidden" name="id_usuario" value="<?= (int) $usuario['id_usuario']; ?>">
                            <div class="form-group">
                                <label class="form-label">Usuario</label>
                                <input type="text" class="form-control" value="<?= e($usuario['usuario']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nombre completo *</label>
                                <input type="text" name="nombre_completo" class="form-control" value="<?= e($usuario['nombre_completo']); ?>" required maxlength="100">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rol *</label>
                                <select name="id_rol" class="form-control" required>
                                    <?php foreach ($roles as $rol): ?>
                                        <option value="<?= (int) $rol['id_rol']; ?>" <?= (int) $usuario['id_rol'] === (int) $rol['id_rol'] ? 'selected' : ''; ?>><?= e($rol['nombre_rol']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="resetUsuario<?= (int) $usuario['id_usuario']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Resetear Contraseña</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="reset">
                            <input type="hidden" name="id_usuario" value="<?= (int) $usuario['id_usuario']; ?>">
                            <p>Se reseteará la contraseña de <strong><?= e($usuario['nombre_completo']); ?></strong>.</p>
                            <p class="text-muted">Nueva contraseña temporal: <strong><?= e($claveTemporal); ?></strong>. Se marcará primer ingreso obligatorio.</p>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-warning">Resetear</button></div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="modal fade" id="nuevoUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Nuevo Usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">
                        <div class="form-group">
                            <label class="form-label">Usuario *</label>
                            <input type="text" name="usuario" class="form-control" required maxlength="50" pattern="[a-zA-Z0-9._-]{3,50}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nombre completo *</label>
                            <input type="text" name="nombre_completo" class="form-control" required maxlength="100">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Rol *</label>
                            <select name="id_rol" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?= (int) $rol['id_rol']; ?>"><?= e($rol['nombre_rol']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i>Contraseña temporal: <strong><?= e($claveTemporal); ?></strong>.</div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
