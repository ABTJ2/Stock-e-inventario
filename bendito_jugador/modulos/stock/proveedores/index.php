<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

require_auth();

$modulo_activo = 'stock';
$submodulo_activo = 'proveedores';
$breadcrumb = 'Proveedores';
$breadcrumb_link = app_url('dashboard.php');

$proveedores = [];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear') {
        $cuit = $_POST['cuit'] ?? '';
        $razon_social = $_POST['razon_social'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $email = $_POST['email'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        $contacto = $_POST['contacto'] ?? '';
        
        if (empty($razon_social)) {
            $error = 'La razón social es obligatoria.';
        } else {
            try {
                $stmt = db()->prepare("INSERT INTO proveedores (cuit, razon_social, telefono, email, direccion, contacto, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')");
                $stmt->execute([$cuit, $razon_social, $telefono, $email, $direccion, $contacto]);
                $success = 'Proveedor creado correctamente.';
            } catch (Exception $e) {
                $error = 'Error al crear proveedor: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'editar') {
        $id = intval($_POST['id'] ?? 0);
        $cuit = $_POST['cuit'] ?? '';
        $razon_social = $_POST['razon_social'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $email = $_POST['email'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        $contacto = $_POST['contacto'] ?? '';
        
        if ($id > 0 && !empty($razon_social)) {
            $stmt = db()->prepare("UPDATE proveedores SET cuit=?, razon_social=?, telefono=?, email=?, direccion=?, contacto=? WHERE id_proveedor=?");
            $stmt->execute([$cuit, $razon_social, $telefono, $email, $direccion, $contacto, $id]);
            $success = 'Proveedor actualizado correctamente.';
        }
    }

    if ($action === 'eliminar') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = db()->prepare("UPDATE proveedores SET estado = 'inactivo' WHERE id_proveedor = ?");
            $stmt->execute([$id]);
            $success = 'Proveedor eliminado correctamente.';
        }
    }
}

try {
    $stmt = db()->query("SELECT * FROM proveedores WHERE estado = 'activo' ORDER BY razon_social");
    $proveedores = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Error al cargar proveedores: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proveedores - Bendito Jugador</title>
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
                        <i class="fas fa-truck"></i>
                        Gestión de Proveedores
                    </h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoProveedorModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Proveedor
                    </button>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= e($error); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= e($success); ?></div>
                <?php endif; ?>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>CUIT</th>
                                <th>Razón Social</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Contacto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proveedores as $p): ?>
                            <tr>
                                <td><?= e($p['cuit'] ?? '-'); ?></td>
                                <td><?= e($p['razon_social']); ?></td>
                                <td><?= e($p['telefono'] ?? '-'); ?></td>
                                <td><?= e($p['email'] ?? '-'); ?></td>
                                <td><?= e($p['contacto'] ?? '-'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editarModal<?= $p['id_proveedor']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="eliminar">
                                        <input type="hidden" name="id" value="<?= $p['id_proveedor']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar proveedor?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>

                            <div class="modal fade" id="editarModal<?= $p['id_proveedor']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar Proveedor</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="editar">
                                                <input type="hidden" name="id" value="<?= $p['id_proveedor']; ?>">
                                                <div class="form-group">
                                                    <label class="form-label">CUIT</label>
                                                    <input type="text" name="cuit" class="form-control" value="<?= e($p['cuit'] ?? ''); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Razón Social *</label>
                                                    <input type="text" name="razon_social" class="form-control" value="<?= e($p['razon_social']); ?>" required>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Teléfono</label>
                                                            <input type="text" name="telefono" class="form-control" value="<?= e($p['telefono'] ?? ''); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Email</label>
                                                            <input type="email" name="email" class="form-control" value="<?= e($p['email'] ?? ''); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Dirección</label>
                                                    <textarea name="direccion" class="form-control" rows="2"><?= e($p['direccion'] ?? ''); ?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Contacto</label>
                                                    <input type="text" name="contacto" class="form-control" value="<?= e($p['contacto'] ?? ''); ?>">
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
                            <?php endforeach; ?>
                            
                            <?php if (empty($proveedores)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No hay proveedores registrados.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="nuevoProveedorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">
                        <div class="form-group">
                            <label class="form-label">CUIT</label>
                            <input type="text" name="cuit" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Razón Social *</label>
                            <input type="text" name="razon_social" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" name="telefono" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Dirección</label>
                            <textarea name="direccion" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contacto</label>
                            <input type="text" name="contacto" class="form-control">
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
