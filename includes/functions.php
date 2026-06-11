<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function getSetting(string $key, string $default = ''): string
{
    $db = getDB();
    $stmt = $db->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

function setSetting(string $key, string $value): bool
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    return $stmt->execute([$key, $value]);
}

function getPublishedPosts(int $limit = 20, int $offset = 0, string $search = ''): array
{
    $db = getDB();

    $sql = 'SELECT id, title, slug, excerpt, cover_image, published_at, created_at
            FROM posts WHERE status = ?';
    $params = ['published'];

    if ($search !== '') {
        $sql .= ' AND (title LIKE ? OR excerpt LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= ' ORDER BY published_at DESC LIMIT ? OFFSET ?';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function countPublishedPosts(string $search = ''): int
{
    $db = getDB();
    $sql = 'SELECT COUNT(*) FROM posts WHERE status = ?';
    $params = ['published'];

    if ($search !== '') {
        $sql .= ' AND (title LIKE ? OR excerpt LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function getPostBySlug(string $slug): ?array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT * FROM posts WHERE slug = ? AND status = ? LIMIT 1'
    );
    $stmt->execute([$slug, 'published']);
    $post = $stmt->fetch();
    return $post ?: null;
}

function getPostById(int $id): ?array
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    return $post ?: null;
}

function getAllPosts(int $limit = 50, int $offset = 0): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT * FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?'
    );
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

function generateSlug(string $title): string
{
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'untitled';
}

function uniqueSlug(string $slug, ?int $excludeId = null): string
{
    $db = getDB();
    $original = $slug;
    $counter = 1;

    while (true) {
        $sql = 'SELECT id FROM posts WHERE slug = ?';
        $params = [$slug];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $sql .= ' LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if (!$stmt->fetch()) {
            return $slug;
        }

        $slug = $original . '-' . $counter;
        $counter++;
    }
}

function createPost(string $title, string $content, string $excerpt, ?string $coverImage, string $status, ?string $customSlug = null): int
{
    $db = getDB();

    $slug = $customSlug ? generateSlug($customSlug) : generateSlug($title);
    $slug = uniqueSlug($slug);

    $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;

    $stmt = $db->prepare(
        'INSERT INTO posts (title, slug, excerpt, content, cover_image, status, published_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$title, $slug, $excerpt, $content, $coverImage, $status, $publishedAt]);

    return (int) $db->lastInsertId();
}

function updatePost(int $id, string $title, string $content, string $excerpt, ?string $coverImage, string $status, ?string $customSlug = null): bool
{
    $db = getDB();
    $existing = getPostById($id);

    if (!$existing) {
        return false;
    }

    $slug = $customSlug ? generateSlug($customSlug) : generateSlug($title);
    $slug = uniqueSlug($slug, $id);

    $publishedAt = $existing['published_at'];
    if ($status === 'published' && empty($publishedAt)) {
        $publishedAt = date('Y-m-d H:i:s');
    }

    $stmt = $db->prepare(
        'UPDATE posts SET title = ?, slug = ?, excerpt = ?, content = ?, cover_image = ?, status = ?, published_at = ?
         WHERE id = ?'
    );

    return $stmt->execute([$title, $slug, $excerpt, $content, $coverImage, $status, $publishedAt, $id]);
}

function deletePost(int $id): bool
{
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM posts WHERE id = ?');
    return $stmt->execute([$id]);
}

function uploadImage(array $file): ?array
{
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024;

    if (!in_array($file['type'], $allowed, true)) {
        return null;
    }

    if ($file['size'] > $maxSize) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $ext = match ($file['type']) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        default => 'bin',
    };

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }

    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO images (filename, original_name, mime_type, file_size) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$filename, $file['name'], $file['type'], $file['size']]);

    return [
        'id' => (int) $db->lastInsertId(),
        'filename' => $filename,
        'url' => UPLOAD_URL . $filename,
    ];
}

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function formatDate(string $date): string
{
    return date('F j, Y', strtotime($date));
}

function truncate(string $text, int $length = 160): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . '...';
}

function markdownToHtml(string $md): string
{
    $html = $md;

    $html = preg_replace_callback('/```(.*?)```/s', function ($m) {
        return '<pre><code>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</code></pre>';
    }, $html);

    $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
    $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);

    $html = preg_replace('/^> (.+)$/m', '<blockquote><p>$1</p></blockquote>', $html);
    $html = preg_replace('/<\/blockquote>\n<blockquote>/', "\n", $html);

    $html = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $html);
    $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $html);

    $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
    $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
    $html = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $html);
    $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

    $html = preg_replace('/^---$/m', '<hr>', $html);

    $lines = explode("\n", $html);
    $result = [];
    $inList = false;
    $listType = '';

    foreach ($lines as $line) {
        if (preg_match('/^- (.+)$/', $line, $m)) {
            if (!$inList || $listType !== 'ul') {
                if ($inList) $result[] = '</' . $listType . '>';
                $result[] = '<ul>';
                $inList = true;
                $listType = 'ul';
            }
            $result[] = '<li>' . $m[1] . '</li>';
        } elseif (preg_match('/^\d+\. (.+)$/', $line, $m)) {
            if (!$inList || $listType !== 'ol') {
                if ($inList) $result[] = '</' . $listType . '>';
                $result[] = '<ol>';
                $inList = true;
                $listType = 'ol';
            }
            $result[] = '<li>' . $m[1] . '</li>';
        } else {
            if ($inList) {
                $result[] = '</' . $listType . '>';
                $inList = false;
                $listType = '';
            }
            $trimmed = trim($line);
            if ($trimmed === '') {
                $result[] = '';
            } elseif (
                !str_starts_with($trimmed, '<h') &&
                !str_starts_with($trimmed, '<blockquote') &&
                !str_starts_with($trimmed, '<hr') &&
                !str_starts_with($trimmed, '<pre') &&
                !str_starts_with($trimmed, '<img') &&
                !str_starts_with($trimmed, '<ul') &&
                !str_starts_with($trimmed, '<ol')
            ) {
                $result[] = '<p>' . $line . '</p>';
            } else {
                $result[] = $line;
            }
        }
    }
    if ($inList) $result[] = '</' . $listType . '>';

    return implode("\n", $result);
}
