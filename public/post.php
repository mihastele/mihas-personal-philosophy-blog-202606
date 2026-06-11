<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/translations.php';

$lang = getCurrentLanguage();
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    http_response_code(404);
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="empty-state"><h2>' . t('not_found') . '</h2><p><a href="/?lang=' . e($lang) . '">' . t('return_all') . '</a></p></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$post = getPostBySlug($slug, $lang);

if (!$post) {
    http_response_code(404);
    $currentPage = 'post';
    $pageTitle = t('not_found');
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="empty-state"><h2>' . t('not_found') . '</h2><p>' . t('not_found_desc') . ' <a href="/?lang=' . e($lang) . '">' . t('return_all') . '</a></p></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

if ($post['post_type'] === 'html' && !empty($post['custom_dir'])) {
    $mainFile = $post['content'] ?: 'index.html';
    $filePath = CUSTOM_POSTS_DIR . $post['custom_dir'] . '/' . $mainFile;

    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $content = str_replace('{{BASE_URL}}', CUSTOM_POSTS_URL . $post['custom_dir'] . '/', $content);
        echo $content;
        exit;
    } else {
        http_response_code(404);
        $currentPage = 'post';
        $pageTitle = t('not_found');
        require_once __DIR__ . '/../includes/header.php';
        echo '<div class="empty-state"><h2>' . t('not_found') . '</h2><p>' . t('not_found_desc') . ' <a href="/?lang=' . e($lang) . '">' . t('return_all') . '</a></p></div>';
        require_once __DIR__ . '/../includes/footer.php';
        exit;
    }
}

$currentPage = 'post';
$pageTitle = $post['title'] . ' — ' . getLocalizedSetting('blog_title');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="post-single">
    <a href="/?lang=<?= e($lang) ?>" class="post-back-link">&larr; <?= t('all_essays') ?></a>

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
