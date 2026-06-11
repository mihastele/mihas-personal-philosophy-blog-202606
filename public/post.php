<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    http_response_code(404);
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="empty-state"><h2>Essay not found</h2><p><a href="/">Return to all essays</a></p></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$post = getPostBySlug($slug);

if (!$post) {
    http_response_code(404);
    $currentPage = 'post';
    $pageTitle = 'Not Found';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="empty-state"><h2>Essay not found</h2><p>The essay you seek does not exist. <a href="/">Return to all essays</a></p></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$currentPage = 'post';
$pageTitle = $post['title'] . ' — ' . getSetting('blog_title', "Miha's Blog of Philosophy");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="post-single">
    <a href="/" class="post-back-link">&larr; All essays</a>

    <header class="post-single-header">
        <span class="post-single-date"><?= formatDate($post['published_at'] ?? $post['created_at']) ?></span>
        <h1 class="post-single-title"><?= e($post['title']) ?></h1>
    </header>

    <?php if ($post['cover_image']): ?>
        <div class="post-single-cover">
            <img src="<?= e(UPLOAD_URL . $post['cover_image']) ?>" alt="<?= e($post['title']) ?>">
        </div>
    <?php endif; ?>

    <div class="post-single-content" id="postContent">
        <?= markdownToHtml($post['content']) ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
