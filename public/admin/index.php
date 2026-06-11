<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$action = $_GET['action'] ?? '';

if ($action === 'logout') {
    logout();
    header('Location: /admin/');
    exit;
}

if (isAdminLoggedIn()) {
    $posts = getAllPosts();
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Miha's Blog of Philosophy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h1>Admin Access</h1>
        <p>Miha's Blog of Philosophy</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="login-tabs" style="display: flex; gap: 0; margin-bottom: 24px; border-bottom: 1px solid var(--color-border);">
            <button type="button" class="editor-tab active" onclick="switchLoginTab('password', this)">Password</button>
            <button type="button" class="editor-tab" onclick="switchLoginTab('secret', this)">Secret Key</button>
        </div>

        <form method="post" id="loginFormPassword" action="/admin/">
            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
            <input type="hidden" name="login_type" value="password">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Sign In</button>
            </div>
        </form>

        <form method="post" id="loginFormSecret" action="/admin/" style="display: none;">
            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
            <input type="hidden" name="login_type" value="secret">
            <div class="form-group">
                <label for="secret_key">Secret Key</label>
                <input type="password" id="secret_key" name="secret_key" required autocomplete="off">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Sign In with Key</button>
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Miha's Blog of Philosophy</title>
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
                <li><a href="/admin/" class="active">Dashboard</a></li>
                <li><a href="/admin/editor.php">New Essay</a></li>
                <li><a href="/admin/settings.php">Settings</a></li>
                <li><a href="/" target="_blank">View Blog</a></li>
                <li><a href="/admin/?action=logout">Logout</a></li>
            </ul>
        </aside>
        <div class="admin-content">
            <div class="admin-header">
                <h1>Essays</h1>
                <a href="/admin/editor.php" class="btn btn-primary">+ New Essay</a>
            </div>

            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <h2>No essays yet</h2>
                    <p>Begin your philosophical journey with the first essay.</p>
                </div>
            <?php else: ?>
                <div class="admin-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Published</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $p): ?>
                                <tr>
                                    <td><strong><?= e($p['title']) ?></strong></td>
                                    <td><span class="status-badge <?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                                    <td><?= $p['published_at'] ? formatDate($p['published_at']) : '—' ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="/admin/editor.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                            <form method="post" action="/admin/editor.php" style="display:inline;" onsubmit="return confirm('Delete this essay permanently?');">
                                                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
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
