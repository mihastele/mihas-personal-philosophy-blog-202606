<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: text/html; charset=utf-8');

$db = getDB();

$stmt = $db->query('SELECT COUNT(*) FROM admin');
$count = (int) $stmt->fetchColumn();

$defaultPassword = 'admin123';
$hash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 12]);

if ($count === 0) {
    $stmt = $db->prepare('INSERT INTO admin (username, password_hash) VALUES (?, ?)');
    $stmt->execute(['admin', $hash]);
    $message = 'Admin user created. Username: <strong>admin</strong>, Password: <strong>admin123</strong>';
    $message .= '<br><strong style="color: #dc3545;">DELETE this file immediately and change the password in Settings!</strong>';
} else {
    $stmt = $db->prepare('UPDATE admin SET password_hash = ? WHERE username = ?');
    $stmt->execute([$hash, 'admin']);
    $message = 'Admin password reset to: <strong>admin123</strong>';
    $message .= '<br><strong style="color: #dc3545;">DELETE this file immediately and change the password in Settings!</strong>';
}

?>
<!DOCTYPE html>
<html>
<head><title>Setup Complete</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #faf9f6; }
    .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); max-width: 500px; text-align: center; }
    h1 { margin-bottom: 16px; }
    a { color: #8b6914; }
</style>
</head>
<body>
    <div class="card">
        <h1>Setup Complete</h1>
        <p><?= $message ?></p>
        <p style="margin-top: 20px;"><a href="/admin/">Go to Admin Panel &rarr;</a></p>
    </div>
</body>
</html>
