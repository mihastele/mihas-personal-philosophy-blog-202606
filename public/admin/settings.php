<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['settings_action'] ?? '';

        if ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (strlen($newPassword) < 8) {
                $error = 'New password must be at least 8 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match.';
            } elseif (changePassword($currentPassword, $newPassword)) {
                $success = 'Password changed successfully.';
            } else {
                $error = 'Current password is incorrect.';
            }
        } elseif ($action === 'update_settings') {
            $blogTitle = trim($_POST['blog_title'] ?? '');
            $blogTagline = trim($_POST['blog_tagline'] ?? '');

            if ($blogTitle !== '') {
                setSetting('blog_title', $blogTitle);
            }
            if ($blogTagline !== '') {
                setSetting('blog_tagline', $blogTagline);
            }
            $success = 'Settings updated successfully.';
        }
    }
}

$blogTitle = getSetting('blog_title', "Miha's Blog of Philosophy");
$blogTagline = getSetting('blog_tagline', 'Contemplations on existence, reason, and the human condition');
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-brand">
                <h2>Admin Panel</h2>
                <p>Philosophy Blog</p>
            </div>
            <ul class="admin-nav">
                <li><a href="/admin/">Dashboard</a></li>
                <li><a href="/admin/editor.php">New Essay</a></li>
                <li><a href="/admin/settings.php" class="active">Settings</a></li>
                <li><a href="/" target="_blank">View Blog</a></li>
                <li><a href="/admin/?action=logout">Logout</a></li>
            </ul>
        </aside>
        <div class="admin-content">
            <div class="admin-header">
                <h1>Settings</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; max-width: 900px;">
                <div style="background: var(--color-surface); padding: 28px; border-radius: var(--radius-lg); border: 1px solid var(--color-border-light);">
                    <h2 style="font-family: var(--font-serif); font-size: 1.3rem; margin-bottom: 20px;">Blog Settings</h2>
                    <form method="post" action="/admin/settings.php">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="settings_action" value="update_settings">
                        <div class="form-group">
                            <label for="blog_title">Blog Title</label>
                            <input type="text" id="blog_title" name="blog_title" value="<?= e($blogTitle) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="blog_tagline">Tagline</label>
                            <input type="text" id="blog_tagline" name="blog_tagline" value="<?= e($blogTagline) ?>">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>

                <div style="background: var(--color-surface); padding: 28px; border-radius: var(--radius-lg); border: 1px solid var(--color-border-light);">
                    <h2 style="font-family: var(--font-serif); font-size: 1.3rem; margin-bottom: 20px;">Change Password</h2>
                    <form method="post" action="/admin/settings.php">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="settings_action" value="change_password">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
