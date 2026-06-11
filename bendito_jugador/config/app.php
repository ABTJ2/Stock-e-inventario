<?php
declare(strict_types=1);

date_default_timezone_set('America/Argentina/Buenos_Aires');

define('APP_NAME', 'Bendito Jugador');
define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', 'local');
define('APP_DEBUG', true);

define('DB_HOST', 'localhost');
define('DB_NAME', 'stock_inventario');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

if (!function_exists('derive_app_url')) {
    function derive_app_url(): string
    {
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $appRoot = realpath(APP_ROOT) ?: APP_ROOT;

        $normalizedDocumentRoot = str_replace('\\', '/', (string) realpath($documentRoot));
        $normalizedAppRoot = str_replace('\\', '/', $appRoot);

        if ($normalizedDocumentRoot !== '' && str_starts_with($normalizedAppRoot, $normalizedDocumentRoot)) {
            $relative = trim(substr($normalizedAppRoot, strlen($normalizedDocumentRoot)), '/');
            if ($relative === '') {
                return '';
            }

            $segments = array_map('rawurlencode', explode('/', $relative));
            return '/' . implode('/', $segments);
        }

        return '/bendito_jugador';
    }
}

define('APP_URL', derive_app_url());

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('bendito_jugador_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
