<?php
declare(strict_types=1);

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
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('request_method_is')) {
    function request_method_is(string $method): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
    }
}

if (!function_exists('post_string')) {
    function post_string(string $key, int $maxLength = 255): string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return substr($value, 0, $maxLength);
    }
}

if (!function_exists('client_ip')) {
    function client_ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }
}

if (!function_exists('db_table_exists')) {
    function db_table_exists(PDO $db, string $table): bool
    {
        $statement = $db->prepare(
            'SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $statement->execute([$table]);

        return (int) $statement->fetchColumn() > 0;
    }
}
