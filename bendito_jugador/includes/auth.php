<?php
declare(strict_types=1);

if (!function_exists('db')) {
    function db(): PDO
    {
        static $connection = null;

        if ($connection instanceof PDO) {
            return $connection;
        }

        $database = new Database();
        $connection = $database->getConnection();
        return $connection;
    }
}

if (!function_exists('find_user_for_login')) {
    function find_user_for_login(string $username): ?array
    {
        $statement = db()->prepare(
            'SELECT u.id_usuario, u.usuario, u.clave, u.nombre_completo, u.id_rol, u.primer_ingreso, u.estado,
                    r.nombre_rol
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             WHERE u.usuario = :usuario
             LIMIT 1'
        );
        $statement->execute(['usuario' => $username]);
        $user = $statement->fetch();

        return $user ?: null;
    }
}

if (!function_exists('sync_legacy_session_user')) {
    function sync_legacy_session_user(array $user): void
    {
        $_SESSION['id_usuario'] = (int) $user['id_usuario'];
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['nombre_completo'] = $user['nombre_completo'];
        $_SESSION['id_rol'] = (int) $user['id_rol'];
        $_SESSION['nombre_rol'] = $user['nombre_rol'] ?? 'Sin rol';
        $_SESSION['primer_ingreso'] = (int) $user['primer_ingreso'];
    }
}

if (!function_exists('login_user')) {
    function login_user(array $user): void
    {
        session_regenerate_id(true);
        
        $_SESSION['user'] = [
            'id' => (int) $user['id_usuario'],
            'username' => $user['usuario'],
            'full_name' => $user['nombre_completo'],
            'role_id' => (int) $user['id_rol'],
            'role_name' => $user['nombre_rol'] ?? 'Sin rol',
            'first_login' => (int) $user['primer_ingreso'],
        ];

        sync_legacy_session_user($user);

        $statement = db()->prepare('UPDATE usuarios SET fecha_ultimo_login = NOW() WHERE id_usuario = :id_usuario');
        $statement->execute(['id_usuario' => (int) $user['id_usuario']]);

        audit_event('login', 'auth', (int) $user['id_usuario'], 'Inicio de sesión correcto.');
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return current_user() !== null;
    }
}

if (!function_exists('current_user_role')) {
    function current_user_role(): string
    {
        return (string) (current_user()['role_name'] ?? '');
    }
}

if (!function_exists('has_role')) {
    function has_role(string $roleName): bool
    {
        return strcasecmp(current_user_role(), $roleName) === 0;
    }
}

if (!function_exists('require_role')) {
    function require_role(string $roleName): void
    {
        require_auth();

        if (!has_role($roleName)) {
            http_response_code(403);
            exit('Acceso denegado.');
        }
    }
}

if (!function_exists('is_first_login_pending')) {
    function is_first_login_pending(): bool
    {
        return is_logged_in() && (int) (current_user()['first_login'] ?? 0) === 1;
    }
}

if (!function_exists('require_auth')) {
    function require_auth(bool $allowFirstLoginScreen = false): void
    {
        if (!is_logged_in()) {
            redirect(app_url('index.php'));
        }

        if (!$allowFirstLoginScreen && is_first_login_pending()) {
            redirect(app_url('primer_ingreso.php'));
        }
    }
}

if (!function_exists('require_guest')) {
    function require_guest(): void
    {
        if (!is_logged_in()) {
            return;
        }

        if (is_first_login_pending()) {
            redirect(app_url('primer_ingreso.php'));
        }

        redirect(app_url('dashboard.php'));
    }
}

if (!function_exists('logout_user')) {
    function logout_user(): void
    {
        $userId = (int) (current_user()['id'] ?? 0);
        if ($userId > 0) {
            audit_event('logout', 'auth', $userId, 'Cierre de sesión.');
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}

if (!function_exists('update_first_login_password')) {
    function update_first_login_password(int $userId, string $password): void
    {
        $statement = db()->prepare(
            'UPDATE usuarios
             SET clave = :clave, primer_ingreso = 0
             WHERE id_usuario = :id_usuario'
        );

        $statement->execute([
            'clave' => password_hash($password, PASSWORD_DEFAULT),
            'id_usuario' => $userId,
        ]);

        $_SESSION['user']['first_login'] = 0;
        $_SESSION['primer_ingreso'] = 0;

        audit_event('password_change', 'auth', $userId, 'Cambio obligatorio de contraseña por primer ingreso.');
    }
}

if (!function_exists('validate_password_policy')) {
    function validate_password_policy(string $password, string $confirmation): array
    {
        $errors = [];

        if ($password === '' || $confirmation === '') {
            $errors[] = 'Completá ambos campos de contraseña.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contraseña debe incluir al menos una letra mayúscula.';
        }

        if (!preg_match('/\d/', $password)) {
            $errors[] = 'La contraseña debe incluir al menos un número.';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'La contraseña debe incluir al menos un carácter especial.';
        }

        if ($password !== $confirmation) {
            $errors[] = 'Las contraseñas ingresadas no coinciden.';
        }

        return $errors;
    }
}
