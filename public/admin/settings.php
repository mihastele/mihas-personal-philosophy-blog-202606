<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/translations.php';

requireAdmin();

$error = '';
$success = '';
$adminLang = getCurrentLanguage();

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
            foreach (['en', 'sl'] as $lang) {
                $title = trim($_POST['blog_title_' . $lang] ?? '');
                $tagline = trim($_POST['blog_tagline_' . $lang] ?? '');
                if ($title !== '') setSetting('blog_title_' . $lang, $title);
                if ($tagline !== '') setSetting('blog_tagline_' . $lang, $tagline);
            }
            $defaultLang = $_POST['default_language'] ?? 'en';
            setSetting('default_language', $defaultLang);
            $success = 'Settings updated successfully.';
        }
    }
}

$blogTitleEn = getSetting('blog_title_en', "Miha's Blog of Philosophy");
$blogTaglineEn = getSetting('blog_tagline_en', 'Contemplations on existence, reason, and the human condition');
$blogTitleSl = getSetting('blog_title_sl', 'Mihov blog o filozofiji');
$blogTaglineSl = getSetting('blog_tagline_sl', 'Razmišljanja o obstoju, razumu in človeškem stanju');
$defaultLang = getSetting('default_language', 'en');
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="<?= e($adminLang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin_settings') ?> — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-brand">
                <h2><?= t('admin_panel') ?></h2>
                <p>Philosophy Blog</p>
            </div>
            <ul class="admin-nav">
                <li><a href="/admin/"><?= t('admin_dashboard') ?></a></li>
                <li><a href="/admin/editor.php"><?= t('admin_new_essay') ?></a></li>
                <li><a href="/admin/settings.php" class="active"><?= t('admin_settings') ?></a></li>
                <li><a href="/?lang=<?= e($adminLang) ?>" target="_blank"><?= t('admin_view_blog') ?></a></li>
                <li><a href="/admin/?action=logout"><?= t('admin_logout') ?></a></li>
            </ul>
        </aside>
        <div class="admin-content">
            <div class="admin-header">
                <h1><?= t('admin_settings') ?></h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; max-width: 1100px;">
                <div style="background: var(--color-surface); padding: 28px; border-radius: var(--radius-lg); border: 1px solid var(--color-border-light);">
                    <h2 style="font-family: var(--font-serif); font-size: 1.3rem; margin-bottom: 20px;"><?= t('admin_blog_settings') ?></h2>
                    <form method="post" action="/admin/settings.php">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="settings_action" value="update_settings">

                        <div style="margin-bottom: 20px; padding: 16px; background: var(--color-bg-alt); border-radius: var(--radius);">
                            <h3 style="font-size: 0.9rem; font-weight: 600; margin-bottom: 12px;">English</h3>
                            <div class="form-group">
                                <label><?= t('admin_blog_title') ?> (EN)</label>
                                <input type="text" name="blog_title_en" value="<?= e($blogTitleEn) ?>" required>
                            </div>
                            <div class="form-group">
                                <label><?= t('admin_blog_tagline') ?> (EN)</label>
                                <input type="text" name="blog_tagline_en" value="<?= e($blogTaglineEn) ?>">
                            </div>
                        </div>

                        <div style="margin-bottom: 20px; padding: 16px; background: var(--color-bg-alt); border-radius: var(--radius);">
                            <h3 style="font-size: 0.9rem; font-weight: 600; margin-bottom: 12px;">Slovenščina</h3>
                            <div class="form-group">
                                <label><?= t('admin_blog_title') ?> (SL)</label>
                                <input type="text" name="blog_title_sl" value="<?= e($blogTitleSl) ?>" required>
                            </div>
                            <div class="form-group">
                                <label><?= t('admin_blog_tagline') ?> (SL)</label>
                                <input type="text" name="blog_tagline_sl" value="<?= e($blogTaglineSl) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Default Language</label>
                            <select name="default_language">
                                <option value="en" <?= $defaultLang === 'en' ? 'selected' : '' ?>>English</option>
                                <option value="sl" <?= $defaultLang === 'sl' ? 'selected' : '' ?>>Slovenščina</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><?= t('admin_save_settings') ?></button>
                        </div>
                    </form>
                </div>

                <div style="background: var(--color-surface); padding: 28px; border-radius: var(--radius-lg); border: 1px solid var(--color-border-light);">
                    <h2 style="font-family: var(--font-serif); font-size: 1.3rem; margin-bottom: 20px;"><?= t('admin_change_password') ?></h2>
                    <form method="post" action="/admin/settings.php">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="settings_action" value="change_password">
                        <div class="form-group">
                            <label for="current_password"><?= t('admin_current_password') ?></label>
                            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                        </div>
                        <div class="form-group">
                            <label for="new_password"><?= t('admin_new_password') ?></label>
                            <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password"><?= t('admin_confirm_password') ?></label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><?= t('admin_change_password') ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
