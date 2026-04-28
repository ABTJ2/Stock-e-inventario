<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_auth();

$modulo_activo = 'stock';
$submodulo_activo = 'usuarios';
$breadcrumb = 'Gestión de Usuarios';
$breadcrumb_link = app_url('dashboard.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear') {
        $usuario = $_POST['usuario'] ?? '';
        $nombre_completo = $_POST['nombre_completo'] ?? '';
        $id_rol = intval($_POST['id_rol'] ?? 0);
        
        if (empty($usuario) || empty($nombre_completo) || $id_rol === 0) {
            $error = 'Todos los campos son obligatorios.';
        } else {
            $claveTemporal = password_hash('password123', PASSWORD_DEFAULT);
            try {
                $stmt = db()->prepare("INSERT INTO usuarios (usuario, clave, nombre_completo, id_rol, estado, primer_ingreso) VALUES (?, ?, ?, ?, 'activo', 1)");
                $stmt->execute([$usuario, $claveTemporal, $nombre_completo, $id_rol]);
                $success = 'Usuario creado. Debe cambiar contraseña en primer ingreso.';
            } catch (Exception $e) {
                $error = 'Error al crear usuario. El nombre puede estar en uso.';
            }
        }
    }

    if ($action === 'editar') {
        $id = intval($_POST['id'] ?? 0);
        $nombre_completo = $_POST['nombre_completo'] ?? '';
        $id_rol = intval($_POST['id_rol'] ?? 0);
        
        if ($id > 0 && !empty($nombre_completo) && $id_rol > 0) {
            $stmt = db()->prepare("UPDATE usuarios SET nombre_completo=?, id_rol=? WHERE id_usuario=?");
            $stmt->execute([$nombre_completo, $id_rol, $id]);
            $success = 'Usuario actualizado correctamente.';
        }
    }

    if ($action === 'reset') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $nuevaClave = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = db()->prepare("UPDATE usuarios SET clave=?, primer_ingreso=1 WHERE id_usuario=?");
            $stmt->execute([$nuevaClave, $id]);
            $success = 'Contraseña reseteada a "password123".';
        }
    }

    if ($action === 'eliminar') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0 && $id != $_SESSION['id_usuario']) {
            $stmt = db()->prepare("UPDATE usuarios SET estado = 'inactivo' WHERE id_usuario = ?");
            $stmt->execute([$id]);
            $success = 'Usuario eliminado correctamente.';
        }
    }
}

$usuarios = [];
try {
    $stmt = db()->query("SELECT u.*, r.nombre_rol FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol WHERE u.estado = 'activo' ORDER BY u.nombre_completo");
    $usuarios = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Error al cargar usuarios.';
}

$roles = [];
try {
    $stmt = db()->query("SELECT * FROM roles WHERE estado = 'activo'");
    $roles = $stmt->fetchAll();
} catch (Exception $e) {
    $roles = [];
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
                    <h2 class="page-title">
                        <i class="fas fa-users"></i>
                        Gestión de Usuarios
                    </h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoUsuarioModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Usuario
                    </button>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?= e($error); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i><?= e($success); ?></div>
                <?php endif; ?>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre Completo</th>
                                <th>Rol</th>
                                <th>Último Login</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= e($u['usuario']); ?></td>
                                <td><?= e($u['nombre_completo']); ?></td>
                                <td><span class="badge bg-info"><?= e($u['nombre_rol']); ?></span></td>
                                <td><?= $u['fecha_ultimo_login'] ? date('d/m/Y H:i', strtotime($u['fecha_ultimo_login'])) : 'Nunca'; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editarModal<?= $u['id_usuario']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#resetModal<?= $u['id_usuario']; ?>">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <?php if ($u['id_usuario'] != current_user()['id']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="eliminar">
                                        <input type="hidden" name="id" value="<?= $u['id_usuario']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar usuario?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <div class="modal fade" id="editarModal<?= $u['id_usuario']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar Usuario</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="editar">
                                                <input type="hidden" name="id" value="<?= $u['id_usuario']; ?>">
                                                <div class="form-group">
                                                    <label class="form-label">Usuario</label>
                                                    <input type="text" class="form-control" value="<?= e($u['usuario']); ?>" disabled>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Nombre Completo</label>
                                                    <input type="text" name="nombre_completo" class="form-control" value="<?= e($u['nombre_completo']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Rol</label>
                                                    <select name="id_rol" class="form-control" required>
                                                        <?php foreach ($roles as $r): ?>
                                                        <option value="<?= $r['id_rol']; ?>" <?= $u['id_rol'] ? 'selected' : ''; ?>>
<?= e($r['nombre_rol']); ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="resetModal<?= $u['id_usuario']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Resetear Contraseña</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="reset">
                                                <input type="hidden" name="id" value="<?= $u['id_usuario']; ?>">
                                                <p>¿Resetear la contraseña de <strong><?= e($u['nombre_completo']); ?></strong>?</p>
                                                <p class="text-muted">La contraseña será: <strong>password123</strong></p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-warning">Resetear</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay usuarios registrados.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="nuevoUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">
                        <div class="form-group">
                            <label class="form-label">Nombre de Usuario *</label>
                            <input type="text" name="usuario" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nombre Completo *</label>
                            <input type="text" name="nombre_completo" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Rol *</label>
                            <select name="id_rol" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id_rol']; ?>"><?php echo htmlspecialchars($r['nombre_rol']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>La contraseña será "password123" y deberá cambiarla en su primer ingreso.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
