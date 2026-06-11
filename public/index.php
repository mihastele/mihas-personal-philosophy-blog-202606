<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/translations.php';

$lang = getCurrentLanguage();
$currentPage = 'home';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalPosts = countPublishedPosts($searchQuery, $lang);
$totalPages = max(1, (int) ceil($totalPosts / $perPage));
$posts = getPublishedPosts($perPage, $offset, $searchQuery, $lang);

$pageTitle = $searchQuery
    ? t('search_button') . ': ' . $searchQuery . ' — ' . getLocalizedSetting('blog_title')
    : getLocalizedSetting('blog_title');

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($searchQuery): ?>
    <div class="search-results-header" style="margin-bottom: 32px;">
        <p style="color: var(--color-text-muted); font-size: 0.9rem;">
            <?= $totalPosts ?> <?= t('results_for') ?> "<strong><?= e($searchQuery) ?></strong>"
            &mdash; <a href="/?lang=<?= e($lang) ?>"><?= t('view_all') ?></a>
        </p>
    </div>
<?php endif; ?>

<?php if (empty($posts)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">&#x1F4DC;</div>
        <h2><?= $searchQuery ? t('no_results') : t('no_posts') ?></h2>
        <p><?= $searchQuery ? t('no_results_desc') : t('no_posts_desc') ?></p>
    </div>
<?php else: ?>
    <div class="posts-grid">
        <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <div class="post-card-image">
                    <?php if ($post['cover_image']): ?>
                        <img src="<?= e(UPLOAD_URL . $post['cover_image']) ?>" alt="<?= e($post['title']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="post-card-image-placeholder">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="post-card-body">
                    <span class="post-card-date"><?= formatDate($post['published_at'] ?? $post['created_at']) ?></span>
                    <h2 class="post-card-title">
                        <a href="/post.php?slug=<?= e($post['slug']) ?>&lang=<?= e($lang) ?>"><?= e($post['title']) ?></a>
                    </h2>
                    <?php if ($post['excerpt']): ?>
                        <p class="post-card-excerpt"><?= e(truncate($post['excerpt'], 180)) ?></p>
                    <?php endif; ?>
                    <a href="/post.php?slug=<?= e($post['slug']) ?>&lang=<?= e($lang) ?>" class="post-card-link">
                        <?= t('read_essay') ?> <span>&rarr;</span>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?lang=<?= e($lang) ?>&page=<?= $page - 1 ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>">&larr; <?= t('previous') ?></a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?lang=<?= e($lang) ?>&page=<?= $i ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?lang=<?= e($lang) ?>&page=<?= $page + 1 ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>"><?= t('next') ?> &rarr;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
