<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: /admin/');
        exit;
    }
}

function loginWithSecretKey(string $key): bool
{
    if (empty(ADMIN_SECRET_KEY)) {
        return false;
    }

    if (!hash_equals(ADMIN_SECRET_KEY, $key)) {
        return false;
    }

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['login_time'] = time();
    session_regenerate_id(true);
    return true;
}

function loginWithPassword(string $username, string $password): bool
{
    $db = getDB();
    $stmt = $db->prepare('SELECT id, password_hash FROM admin WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin) {
        return false;
    }

    if (!password_verify($password, $admin['password_hash'])) {
        return false;
    }

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['login_time'] = time();
    session_regenerate_id(true);
    return true;
}

function logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function changePassword(string $currentPassword, string $newPassword): bool
{
    $db = getDB();
    $stmt = $db->prepare('SELECT password_hash FROM admin WHERE id = 1 LIMIT 1');
    $stmt->execute();
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
        return false;
    }

    $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare('UPDATE admin SET password_hash = ? WHERE id = 1');
    return $stmt->execute([$newHash]);
}

function validateApiKey(string $key): bool
{
    if (empty(API_KEY)) {
        return false;
    }

    return hash_equals(API_KEY, $key);
}

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool
{
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
