<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/translations.php';

$action = $_GET['action'] ?? '';

if ($action === 'logout') {
    logout();
    header('Location: /admin/');
    exit;
}

$adminLang = getCurrentLanguage();

if (isAdminLoggedIn()) {
    $filterLang = $_GET['filter_lang'] ?? '';
    $posts = getAllPosts(50, 0, $filterLang);
    $adminPage = 'dashboard';
} else {
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($csrfToken)) {
            $error = 'Invalid request. Please try again.';
        } else {
            $loginType = $_POST['login_type'] ?? 'password';

            if ($loginType === 'secret') {
                $secretKey = $_POST['secret_key'] ?? '';
                if (loginWithSecretKey($secretKey)) {
                    header('Location: /admin/');
                    exit;
                }
                $error = 'Invalid secret key.';
            } else {
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                if (loginWithPassword($username, $password)) {
                    header('Location: /admin/');
                    exit;
                }
                $error = 'Invalid username or password.';
            }
        }
    }

    $adminPage = 'login';
}

if ($adminPage === 'login'):
?>
<!DOCTYPE html>
<html lang="<?= e($adminLang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin_login') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h1><?= t('admin_login') ?></h1>
        <p><?= t('site_title') ?></p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="login-tabs" style="display: flex; gap: 0; margin-bottom: 24px; border-bottom: 1px solid var(--color-border);">
            <button type="button" class="editor-tab active" onclick="switchLoginTab('password', this)"><?= t('admin_password_tab') ?></button>
            <button type="button" class="editor-tab" onclick="switchLoginTab('secret', this)"><?= t('admin_secret_tab') ?></button>
        </div>

        <form method="post" id="loginFormPassword" action="/admin/">
            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
            <input type="hidden" name="login_type" value="password">
            <div class="form-group">
                <label for="username"><?= t('admin_username') ?></label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password"><?= t('admin_password') ?></label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= t('admin_sign_in') ?></button>
            </div>
        </form>

        <form method="post" id="loginFormSecret" action="/admin/" style="display: none;">
            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
            <input type="hidden" name="login_type" value="secret">
            <div class="form-group">
                <label for="secret_key"><?= t('admin_secret_key') ?></label>
                <input type="password" id="secret_key" name="secret_key" required autocomplete="off">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= t('admin_sign_in_key') ?></button>
            </div>
        </form>
    </div>
    <script>
    function switchLoginTab(tab, btn) {
        document.querySelectorAll('.login-tabs .editor-tab').forEach(function(t) { t.classList.remove('active'); });
        btn.classList.add('active');
        document.getElementById('loginFormPassword').style.display = tab === 'password' ? 'block' : 'none';
        document.getElementById('loginFormSecret').style.display = tab === 'secret' ? 'block' : 'none';
    }
    </script>
</body>
</html>
<?php else: ?>
<!DOCTYPE html>
<html lang="<?= e($adminLang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin_dashboard') ?></title>
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
                <li><a href="/admin/" class="active"><?= t('admin_dashboard') ?></a></li>
                <li><a href="/admin/editor.php"><?= t('admin_new_essay') ?></a></li>
                <li><a href="/admin/settings.php"><?= t('admin_settings') ?></a></li>
                <li><a href="/?lang=<?= e($adminLang) ?>" target="_blank"><?= t('admin_view_blog') ?></a></li>
                <li><a href="/admin/?action=logout"><?= t('admin_logout') ?></a></li>
            </ul>
        </aside>
        <div class="admin-content">
            <div class="admin-header">
                <h1><?= t('admin_essays') ?></h1>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <a href="/admin/?filter_lang=" class="btn btn-sm <?= empty($filterLang) ? 'btn-primary' : 'btn-secondary' ?>">All</a>
                    <a href="/admin/?filter_lang=en" class="btn btn-sm <?= ($filterLang ?? '') === 'en' ? 'btn-primary' : 'btn-secondary' ?>">EN</a>
                    <a href="/admin/?filter_lang=sl" class="btn btn-sm <?= ($filterLang ?? '') === 'sl' ? 'btn-primary' : 'btn-secondary' ?>">SL</a>
                    <a href="/admin/editor.php" class="btn btn-primary">+ <?= t('admin_new_essay') ?></a>
                </div>
            </div>

            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <h2><?= t('no_posts') ?></h2>
                    <p><?= t('no_posts_desc') ?></p>
                </div>
            <?php else: ?>
                <div class="admin-table">
                    <table>
                        <thead>
                            <tr>
                                <th><?= t('admin_title') ?></th>
                                <th><?= t('admin_language') ?></th>
                                <th><?= t('admin_status') ?></th>
                                <th><?= t('admin_published') ?></th>
                                <th><?= t('admin_actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($p['title']) ?></strong>
                                        <span style="font-size: 0.75rem; color: var(--color-text-light); margin-left: 6px;"><?= e($p['post_type']) ?></span>
                                    </td>
                                    <td><span class="status-badge" style="background: #e3f2fd; color: #1565c0;"><?= e(strtoupper($p['language'])) ?></span></td>
                                    <td><span class="status-badge <?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                                    <td><?= $p['published_at'] ? formatDate($p['published_at']) : '—' ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="/admin/editor.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm"><?= t('admin_edit') ?></a>
                                            <form method="post" action="/admin/editor.php" style="display:inline;" onsubmit="return confirm('<?= t('admin_delete_confirm') ?>');">
                                                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><?= t('admin_delete') ?></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php endif; ?>
