<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/translations.php';

requireAdmin();

$error = '';
$success = '';
$post = null;
$isEditing = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'save';

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                deletePost($id);
            }
            header('Location: /admin/');
            exit;
        }

        if ($action === 'upload_image') {
            header('Content-Type: application/json');
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $result = uploadImage($_FILES['image']);
                if ($result) {
                    echo json_encode(['success' => true, 'url' => $result['url'], 'filename' => $result['filename']]);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Invalid image. Allowed: JPG, PNG, GIF, WebP. Max 5MB.']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'No file uploaded.']);
            }
            exit;
        }

        if ($action === 'upload_custom_files') {
            header('Content-Type: application/json');
            if (isset($_FILES['custom_files']) && $_FILES['custom_files']['error'][0] === UPLOAD_ERR_OK) {
                $result = uploadCustomPostFiles($_FILES);
                if ($result) {
                    echo json_encode(['success' => true, 'dir' => $result['dir'], 'files' => $result['files']]);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Upload failed.']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'No files uploaded.']);
            }
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $excerpt = trim($_POST['excerpt'] ?? '');
        $coverImage = trim($_POST['cover_image'] ?? '') ?: null;
        $status = $_POST['status'] ?? 'draft';
        $customSlug = trim($_POST['custom_slug'] ?? '') ?: null;
        $language = $_POST['language'] ?? 'en';
        $postType = $_POST['post_type'] ?? 'markdown';
        $customDir = trim($_POST['custom_dir'] ?? '') ?: null;
        $editId = (int)($_POST['edit_id'] ?? 0);

        if (empty($title)) {
            $error = 'Title is required.';
        } elseif ($postType === 'markdown' && empty($content)) {
            $error = 'Content is required.';
        } elseif ($postType === 'html' && empty($customDir)) {
            $error = 'Custom files are required for HTML posts.';
        } else {
            if ($editId > 0) {
                updatePost($editId, $title, $content, $excerpt, $coverImage, $status, $language, $postType, $customSlug, $customDir);
                $success = 'Essay updated successfully.';
                $post = getPostById($editId);
                $isEditing = true;
            } else {
                $newId = createPost($title, $content, $excerpt, $coverImage, $status, $language, $postType, $customSlug, $customDir);
                header('Location: /admin/editor.php?id=' . $newId . '&saved=1');
                exit;
            }
        }
    }
}

if (isset($_GET['id'])) {
    $post = getPostById((int)$_GET['id']);
    if ($post) {
        $isEditing = true;
    }
}

if (isset($_GET['saved'])) {
    $success = 'Essay created successfully.';
}

$csrfToken = generateCsrfToken();
$adminLang = getCurrentLanguage();
$pageTitle = $isEditing ? t('admin_edit_essay') : t('admin_new_essay');
?>
<!DOCTYPE html>
<html lang="<?= e($adminLang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Admin</title>
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
                <li><a href="/admin/editor.php" class="active"><?= t('admin_new_essay') ?></a></li>
                <li><a href="/admin/settings.php"><?= t('admin_settings') ?></a></li>
                <li><a href="/?lang=<?= e($adminLang) ?>" target="_blank"><?= t('admin_view_blog') ?></a></li>
                <li><a href="/admin/?action=logout"><?= t('admin_logout') ?></a></li>
            </ul>
        </aside>
        <div class="admin-content">
            <div class="admin-header">
                <h1><?= $isEditing ? t('admin_edit_essay') : t('admin_new_essay') ?></h1>
                <a href="/admin/" class="btn btn-secondary">&larr; <?= t('admin_dashboard') ?></a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <form method="post" action="/admin/editor.php" id="editorForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="custom_dir" id="customDirInput" value="<?= e($post['custom_dir'] ?? '') ?>">
                <?php if ($isEditing && $post): ?>
                    <input type="hidden" name="edit_id" value="<?= $post['id'] ?>">
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 300px; gap: 24px; align-items: start;">
                    <div>
                        <div class="form-group">
                            <label for="title"><?= t('admin_title') ?></label>
                            <input type="text" id="title" name="title" required value="<?= e($post['title'] ?? '') ?>" placeholder="<?= t('admin_title') ?>...">
                        </div>

                        <div class="form-group">
                            <label for="excerpt"><?= t('admin_excerpt') ?></label>
                            <textarea id="excerpt" name="excerpt" rows="2" placeholder="<?= t('admin_excerpt_placeholder') ?>"><?= e($post['excerpt'] ?? '') ?></textarea>
                        </div>

                        <div class="editor-container" id="markdownEditor">
                            <div class="editor-tabs">
                                <button type="button" class="editor-tab active" onclick="switchEditorTab('write', this)"><?= t('admin_write') ?></button>
                                <button type="button" class="editor-tab" onclick="switchEditorTab('preview', this)"><?= t('admin_preview') ?></button>
                            </div>

                            <div class="editor-toolbar" id="editorToolbar">
                                <button type="button" title="Bold" onclick="insertMarkdown('bold')"><strong>B</strong></button>
                                <button type="button" title="Italic" onclick="insertMarkdown('italic')"><em>I</em></button>
                                <button type="button" title="Strikethrough" onclick="insertMarkdown('strike')"><s>S</s></button>
                                <div class="separator"></div>
                                <button type="button" title="Heading 2" onclick="insertMarkdown('h2')">H2</button>
                                <button type="button" title="Heading 3" onclick="insertMarkdown('h3')">H3</button>
                                <div class="separator"></div>
                                <button type="button" title="Quote" onclick="insertMarkdown('quote')">&#10077;</button>
                                <button type="button" title="Link" onclick="insertMarkdown('link')">&#128279;</button>
                                <button type="button" title="Image" onclick="openImageModal()">&#128247;</button>
                                <div class="separator"></div>
                                <button type="button" title="Bullet List" onclick="insertMarkdown('ul')">&#8226; List</button>
                                <button type="button" title="Numbered List" onclick="insertMarkdown('ol')">1. List</button>
                                <div class="separator"></div>
                                <button type="button" title="Horizontal Rule" onclick="insertMarkdown('hr')">&mdash;</button>
                                <button type="button" title="Code" onclick="insertMarkdown('code')">&lt;/&gt;</button>
                            </div>

                            <textarea class="editor-textarea" id="content" name="content" placeholder="<?= t('admin_content_placeholder') ?>"><?= e($post['content'] ?? '') ?></textarea>
                            <div class="editor-preview" id="editorPreview"></div>
                        </div>

                        <div id="htmlUploadArea" style="display: none;">
                            <div style="background: var(--color-surface); padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--color-border-light);">
                                <h3 style="font-family: var(--font-serif); margin-bottom: 16px;"><?= t('admin_upload_files') ?></h3>
                                <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 16px;">
                                    <?= t('admin_html_files') ?>, <?= t('admin_css_files') ?>, <?= t('admin_js_files') ?>, <?= t('admin_zip_file') ?>
                                </p>
                                <div class="cover-upload" id="customFilesUpload">
                                    <p style="color: var(--color-text-muted); font-size: 0.85rem;">
                                        <?= t('admin_upload_files') ?> (HTML, CSS, JS, ZIP)
                                    </p>
                                    <input type="file" name="custom_files[]" id="customFilesInput" accept=".html,.htm,.css,.js,.zip" multiple>
                                </div>
                                <div id="customFilesStatus" style="margin-top: 12px;"></div>
                                <div class="form-group" style="margin-top: 16px;">
                                    <label for="mainFile"><?= t('admin_main_file') ?></label>
                                    <input type="text" id="mainFile" name="main_file" value="<?= e($post['content'] ?? 'index.html') ?>" placeholder="index.html">
                                    <small style="color: var(--color-text-muted); font-size: 0.8rem;"><?= t('admin_main_file_desc') ?></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="form-group">
                            <label for="language"><?= t('admin_language') ?></label>
                            <select id="language" name="language">
                                <option value="en" <?= ($post['language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                                <option value="sl" <?= ($post['language'] ?? '') === 'sl' ? 'selected' : '' ?>>Slovenščina</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="post_type"><?= t('admin_post_type') ?></label>
                            <select id="post_type" name="post_type" onchange="switchPostType(this.value)">
                                <option value="markdown" <?= ($post['post_type'] ?? 'markdown') === 'markdown' ? 'selected' : '' ?>><?= t('admin_type_markdown') ?></option>
                                <option value="html" <?= ($post['post_type'] ?? '') === 'html' ? 'selected' : '' ?>><?= t('admin_type_html') ?></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status"><?= t('admin_status_label') ?></label>
                            <select id="status" name="status">
                                <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>><?= t('admin_draft') ?></option>
                                <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>><?= t('admin_published') ?></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="custom_slug"><?= t('admin_custom_slug') ?></label>
                            <input type="text" id="custom_slug" name="custom_slug" value="<?= e($post['slug'] ?? '') ?>" placeholder="auto-generated-from-title">
                        </div>

                        <div class="form-group">
                            <label><?= t('admin_cover_image') ?></label>
                            <div id="coverUploadArea">
                                <?php if (!empty($post['cover_image'])): ?>
                                    <div class="cover-preview" id="coverPreview">
                                        <img src="<?= e(UPLOAD_URL . $post['cover_image']) ?>" alt="Cover">
                                        <button type="button" class="remove-cover" onclick="removeCover()">&times;</button>
                                    </div>
                                    <input type="hidden" name="cover_image" id="coverImageInput" value="<?= e($post['cover_image']) ?>">
                                <?php else: ?>
                                    <div class="cover-upload" id="coverUpload">
                                        <p style="color: var(--color-text-muted); font-size: 0.85rem;"><?= t('admin_upload_cover') ?></p>
                                        <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" onchange="uploadCover(this)">
                                    </div>
                                    <input type="hidden" name="cover_image" id="coverImageInput" value="">
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-actions" style="display: flex; gap: 8px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">
                                <?= $isEditing ? t('admin_update') : t('admin_create') ?>
                            </button>
                        </div>

                        <?php if ($isEditing && $post): ?>
                            <div style="margin-top: 12px;">
                                <form method="post" action="/admin/editor.php" onsubmit="return confirm('<?= t('admin_delete_confirm') ?>');">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" style="width: 100%; justify-content: center;"><?= t('admin_delete') ?></button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="image-modal" id="imageModal">
        <div class="image-modal-content">
            <h3><?= t('admin_insert_image') ?></h3>
            <div class="form-group">
                <label><?= t('admin_upload_image') ?></label>
                <input type="file" id="imageUploadInput" accept="image/jpeg,image/png,image/gif,image/webp">
            </div>
            <div class="form-group">
                <label for="imageAltText"><?= t('admin_alt_text') ?></label>
                <input type="text" id="imageAltText" placeholder="<?= t('admin_alt_placeholder') ?>">
            </div>
            <div id="imageUploadStatus" style="margin-bottom: 12px;"></div>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeImageModal()"><?= t('admin_cancel') ?></button>
                <button type="button" class="btn btn-primary" onclick="insertImage()"><?= t('admin_insert') ?></button>
            </div>
        </div>
    </div>

    <script>
        function switchPostType(type) {
            var mdEditor = document.getElementById('markdownEditor');
            var htmlUpload = document.getElementById('htmlUploadArea');
            if (type === 'html') {
                mdEditor.style.display = 'none';
                htmlUpload.style.display = 'block';
            } else {
                mdEditor.style.display = 'block';
                htmlUpload.style.display = 'none';
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            var postType = document.getElementById('post_type').value;
            switchPostType(postType);
        });
    </script>
    <script src="/js/app.js"></script>
</body>
</html>
