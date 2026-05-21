<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_auth(true);

if (!is_first_login_pending()) {
    redirect(app_url('dashboard.php'));
}

$errors = [];

if (request_method_is('POST')) {
    $newPassword = (string) ($_POST['nueva_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirmar_password'] ?? '');

    $errors = validate_password_policy($newPassword, $confirmPassword);

    if ($errors === []) {
        try {
            update_first_login_password((int) current_user()['id'], $newPassword);
            redirect(app_url('dashboard.php?welcome=1'));
        } catch (Throwable $exception) {
            $errors[] = 'No fue posible guardar la nueva contraseña. Intentá nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME; ?> | Primer ingreso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= app_url('css/style.css'); ?>">
</head>
<body class="auth-body auth-body--modal">
    <div class="forced-backdrop"></div>

    <div class="modal fade show forced-modal" id="firstLoginModal" tabindex="-1" aria-modal="true" role="dialog" style="display: block;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content app-modal">
                <div class="modal-header app-modal__header">
                    <div>
                        <span class="modal-kicker">Primer ingreso obligatorio</span>
                        <h1 class="modal-title">Definí tu nueva contraseña</h1>
                        <p class="modal-description">
                            Antes de operar en el sistema, necesitamos asegurar tu acceso con una contraseña nueva y segura.
                        </p>
                    </div>
                    <div class="security-lock">
                        <i class="fa-solid fa-shield-keyhole"></i>
                    </div>
                </div>

                <div class="modal-body app-modal__body">
                    <?php if ($errors !== []): ?>
                        <div class="alert alert-danger app-alert" role="alert">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <div>
                                <?php foreach ($errors as $message): ?>
                                    <div><?= e($message); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="password-layout">
                        <form method="post" class="app-form password-form">
                            <div class="form-group">
                                <label class="form-label" for="nueva_password">Nueva contraseña</label>
                                <div class="input-icon">
                                    <i class="fa-solid fa-lock"></i>
                                    <input type="password" class="form-control" id="nueva_password" name="nueva_password" minlength="8" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="confirmar_password">Repetir contraseña</label>
                                <div class="input-icon">
                                    <i class="fa-solid fa-lock"></i>
                                    <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" minlength="8" required>
                                </div>
                            </div>

                            <div class="password-security-message">
                                <i class="fa-solid fa-shield"></i>
                                Esta contraseña protegerá tu sesión y habilitará el acceso completo al dashboard.
                            </div>

                            <button type="submit" class="btn btn-primary btn-app w-100">
                                <i class="fa-solid fa-floppy-disk"></i>
                                Guardar contraseña
                            </button>
                        </form>

                        <aside class="password-rules">
                            <h2>Requisitos mínimos</h2>
                            <ul>
                                <li>Mínimo 8 caracteres.</li>
                                <li>Al menos una letra mayúscula.</li>
                                <li>Al menos un número.</li>
                                <li>Al menos un carácter especial.</li>
                                <li>Ambas contraseñas deben coincidir.</li>
                            </ul>

                            <div class="security-card">
                                <span class="security-card__eyebrow">Usuario activo</span>
                                <strong><?= e(current_user()['full_name']); ?></strong>
                                <p><?= e(current_user()['role_name']); ?></p>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
