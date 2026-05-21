<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_guest();

$error = '';

if (request_method_is('POST')) {
    $username = post_string('usuario', 50);
    $password = (string) ($_POST['clave'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Ingresá usuario y contraseña para continuar.';
    } else {
        try {
            $user = find_user_for_login($username);

            if ($user === null || $user['estado'] !== 'activo' || !password_verify($password, $user['clave'])) {
                audit_event('login_failed', 'auth', $user !== null ? (int) $user['id_usuario'] : null, 'Intento fallido para usuario: ' . $username);
                $error = 'Las credenciales ingresadas no son válidas.';
            } else {
                login_user($user);

                if ((int) $user['primer_ingreso'] === 1) {
                    redirect(app_url('primer_ingreso.php'));
                }

                redirect(app_url('dashboard.php'));
            }
        } catch (Throwable $exception) {
            $error = 'No fue posible validar el acceso. Revisá la base de datos y la configuración del servidor.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME; ?> | Acceso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= app_url('css/style.css'); ?>">
</head>
<body class="auth-body">
    <main class="login-shell">
        <section class="login-showcase">
            <img class="showcase-logo" src="<?= app_url('assets/img/bendito-jugador-logo.png'); ?>" alt="Bendito Jugador">
            <p class="showcase-text">
                Gestión centralizada con enfoque ERP para operaciones, inventario, trazabilidad y decisiones estratégicas.
            </p>

            <div class="showcase-grid">
                <article class="showcase-card">
                    <span class="showcase-card__label">Módulo principal</span>
                    <strong>Stock y Control de Inventario</strong>
                    <p>Productos, proveedores, usuarios, ingresos, auditoría, ajustes y traspasos integrados.</p>
                </article>
                <article class="showcase-card">
                    <span class="showcase-card__label">Seguridad</span>
                    <strong>Acceso por roles y sesiones</strong>
                    <p>Control de permisos, primer ingreso obligatorio y trazabilidad del usuario responsable.</p>
                </article>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-card">
                <div class="login-brand">
                    <img class="login-brand__logo" src="<?= app_url('assets/img/bendito-jugador-logo.png'); ?>" alt="Bendito Jugador">
                    <div>
                        <span class="login-brand__eyebrow">Acceso al sistema</span>
                        <h2>Iniciar sesión</h2>
                        <p>Ingresá con tu usuario corporativo para acceder al panel principal.</p>
                    </div>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger app-alert" role="alert">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?= e($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" class="app-form" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="usuario">Usuario</label>
                        <div class="input-icon">
                            <i class="fa-regular fa-user"></i>
                            <input
                                type="text"
                                class="form-control"
                                id="usuario"
                                name="usuario"
                                placeholder="Ej.: admin"
                                value="<?= e($_POST['usuario'] ?? ''); ?>"
                                autocomplete="username"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="clave">Contraseña</label>
                        <div class="input-icon">
                            <i class="fa-solid fa-lock"></i>
                            <input
                                type="password"
                                class="form-control"
                                id="clave"
                                name="clave"
                                placeholder="Ingresá tu contraseña"
                                autocomplete="current-password"
                                required
                            >
                        </div>
                    </div>

                    <div class="login-actions">
                        <button type="submit" class="btn btn-primary btn-app w-100">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i>
                            Iniciar sesión
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-app w-100" id="exitAppButton">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            Salir
                        </button>
                    </div>
                </form>

                <div class="login-footer-note">
                    <span class="status-dot"></span>
                    Primer ingreso con cambio obligatorio de contraseña y control por roles.
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js'); ?>"></script>
</body>
</html>
