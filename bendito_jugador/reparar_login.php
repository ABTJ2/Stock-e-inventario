<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

$messages = [];
$hasError = false;
$pdo = null;

function repair_login_message(array &$messages, string $text, bool $ok = true): void
{
    $messages[] = [
        'ok' => $ok,
        'text' => $text,
    ];
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    repair_login_message($messages, 'Conexion OK: base ' . DB_NAME . ', usuario ' . DB_USER . '.');

    $pdo->beginTransaction();

    $roleStatement = $pdo->prepare(
        "INSERT INTO roles (nombre_rol, descripcion, estado)
         VALUES (:nombre_rol, :descripcion, 'activo')
         ON DUPLICATE KEY UPDATE
            descripcion = VALUES(descripcion),
            estado = 'activo'"
    );
    $roleStatement->execute([
        'nombre_rol' => 'Administrador',
        'descripcion' => 'Usuario con acceso total al sistema',
    ]);

    $roleSelect = $pdo->prepare(
        "SELECT id_rol
         FROM roles
         WHERE nombre_rol = :nombre_rol
         LIMIT 1"
    );
    $roleSelect->execute(['nombre_rol' => 'Administrador']);
    $roleId = (int) $roleSelect->fetchColumn();

    if ($roleId <= 0) {
        throw new RuntimeException('No se pudo obtener el rol Administrador.');
    }

    repair_login_message($messages, 'Rol OK: Administrador (id_rol ' . $roleId . ').');

    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
    $userStatement = $pdo->prepare(
        "INSERT INTO usuarios (usuario, clave, nombre_completo, id_rol, estado, primer_ingreso)
         VALUES (:usuario, :clave, :nombre_completo, :id_rol, 'activo', 0)
         ON DUPLICATE KEY UPDATE
            clave = VALUES(clave),
            nombre_completo = VALUES(nombre_completo),
            id_rol = VALUES(id_rol),
            estado = 'activo',
            primer_ingreso = 0"
    );
    $userStatement->execute([
        'usuario' => 'admin2',
        'clave' => $passwordHash,
        'nombre_completo' => 'Administrador de Prueba',
        'id_rol' => $roleId,
    ]);

    $userSelect = $pdo->prepare(
        "SELECT id_usuario, usuario, clave, id_rol, estado, primer_ingreso
         FROM usuarios
         WHERE usuario = :usuario
         LIMIT 1"
    );
    $userSelect->execute(['usuario' => 'admin2']);
    $adminUser = $userSelect->fetch();

    if (!$adminUser) {
        throw new RuntimeException('No se pudo crear o actualizar el usuario admin2.');
    }

    repair_login_message(
        $messages,
        'Usuario OK: admin2 (id_usuario ' . (int) $adminUser['id_usuario'] . ', estado ' . $adminUser['estado'] . ', primer_ingreso ' . (int) $adminUser['primer_ingreso'] . ').'
    );

    if (!password_verify('admin123', (string) $adminUser['clave'])) {
        throw new RuntimeException('password_verify fallo para admin2/admin123.');
    }

    repair_login_message($messages, 'Prueba password_verify OK: admin123 coincide con la clave guardada.');

    $pdo->commit();
} catch (Throwable $exception) {
    $hasError = true;

    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    repair_login_message($messages, 'Error: ' . $exception->getMessage(), false);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reparar login | <?= e(APP_NAME); ?></title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #1f2933;
        }

        main {
            width: min(680px, calc(100% - 32px));
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
        }

        h1 {
            margin: 0 0 16px;
            font-size: 24px;
        }

        ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        li {
            margin: 10px 0;
            padding: 12px 14px;
            border-radius: 6px;
            background: #ecfdf3;
            border: 1px solid #bbf7d0;
        }

        li.error {
            background: #fef2f2;
            border-color: #fecaca;
        }

        .warning {
            margin-top: 18px;
            padding: 12px 14px;
            border-radius: 6px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            font-weight: 700;
        }

        .credentials {
            margin-top: 18px;
            padding: 12px 14px;
            border-radius: 6px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        code {
            font-weight: 700;
        }
    </style>
</head>
<body>
    <main>
        <h1><?= $hasError ? 'Reparacion con errores' : 'Reparacion del login completa'; ?></h1>

        <ul>
            <?php foreach ($messages as $message): ?>
                <li class="<?= $message['ok'] ? '' : 'error'; ?>"><?= e($message['text']); ?></li>
            <?php endforeach; ?>
        </ul>

        <?php if (!$hasError): ?>
            <div class="credentials">
                Ya podrias ingresar con usuario <code>admin2</code> y contrasena <code>admin123</code>.
            </div>
        <?php endif; ?>

        <div class="warning">
            Por seguridad, borrar este archivo <code>reparar_login.php</code> despues de usarlo.
        </div>
    </main>
</body>
</html>
