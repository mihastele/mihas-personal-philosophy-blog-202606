<?php

declare(strict_types=1);

function loadEnv(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }

    return $env;
}

$envPath = dirname(__DIR__) . '/.env';
$env = loadEnv($envPath);

define('DB_HOST', $env['DB_HOST'] ?? 'mysql');
define('DB_NAME', $env['DB_NAME'] ?? 'mihas_blog');
define('DB_USER', $env['DB_USER'] ?? 'mihas_blog');
define('DB_PASS', $env['DB_PASS'] ?? '');
define('ADMIN_SECRET_KEY', $env['ADMIN_SECRET_KEY'] ?? '');
define('API_KEY', $env['API_KEY'] ?? '');
define('SITE_URL', rtrim($env['SITE_URL'] ?? 'http://localhost:8080', '/'));
define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('UPLOAD_URL', '/uploads/');
define('CUSTOM_POSTS_DIR', __DIR__ . '/../public/custom_posts/');
define('CUSTOM_POSTS_URL', '/custom_posts/');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
