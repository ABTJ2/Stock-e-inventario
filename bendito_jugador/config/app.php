<?php
declare(strict_types=1);

date_default_timezone_set('America/Argentina/Buenos_Aires');

define('APP_NAME', 'Bendito Jugador');
define('APP_ROOT', dirname(__DIR__));

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
    session_name('bendito_jugador_session');
    session_start();
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        return APP_URL . ($path !== '' ? '/' . $path : '');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
