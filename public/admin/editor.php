<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

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

        $title = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $excerpt = trim($_POST['excerpt'] ?? '');
        $coverImage = trim($_POST['cover_image'] ?? '') ?: null;
        $status = $_POST['status'] ?? 'draft';
        $customSlug = trim($_POST['custom_slug'] ?? '') ?: null;
        $editId = (int)($_POST['edit_id'] ?? 0);

        if (empty($title)) {
            $error = 'Title is required.';
        } elseif (empty($content)) {
            $error = 'Content is required.';
        } else {
            if ($editId > 0) {
                updatePost($editId, $title, $content, $excerpt, $coverImage, $status, $customSlug);
                $success = 'Essay updated successfully.';
                $post = getPostById($editId);
                $isEditing = true;
            } else {
                $newId = createPost($title, $content, $excerpt, $coverImage, $status, $customSlug);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditing ? 'Edit' : 'New' ?> Essay — Admin</title>
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
                <li><a href="/admin/editor.php" class="active">New Essay</a></li>
                <li><a href="/admin/settings.php">Settings</a></li>
                <li><a href="/" target="_blank">View Blog</a></li>
                <li><a href="/admin/?action=logout">Logout</a></li>
            </ul>
        </aside>
        <div class="admin-content">
            <div class="admin-header">
                <h1><?= $isEditing ? 'Edit Essay' : 'New Essay' ?></h1>
                <a href="/admin/" class="btn btn-secondary">&larr; Back</a>
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
                <?php if ($isEditing && $post): ?>
                    <input type="hidden" name="edit_id" value="<?= $post['id'] ?>">
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 300px; gap: 24px; align-items: start;">
                    <div>
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required value="<?= e($post['title'] ?? '') ?>" placeholder="Enter essay title...">
                        </div>

                        <div class="form-group">
                            <label for="excerpt">Excerpt (short summary)</label>
                            <textarea id="excerpt" name="excerpt" rows="2" placeholder="A brief summary shown on the card..."><?= e($post['excerpt'] ?? '') ?></textarea>
                        </div>

                        <div class="editor-container">
                            <div class="editor-tabs">
                                <button type="button" class="editor-tab active" onclick="switchEditorTab('write', this)">Write</button>
                                <button type="button" class="editor-tab" onclick="switchEditorTab('preview', this)">Preview</button>
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

                            <textarea class="editor-textarea" id="content" name="content" placeholder="Write your essay in Markdown..."><?= e($post['content'] ?? '') ?></textarea>
                            <div class="editor-preview" id="editorPreview"></div>
                        </div>
                    </div>

                    <div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="custom_slug">Custom Slug (optional)</label>
                            <input type="text" id="custom_slug" name="custom_slug" value="<?= e($post['slug'] ?? '') ?>" placeholder="auto-generated-from-title">
                        </div>

                        <div class="form-group">
                            <label>Cover Image</label>
                            <div id="coverUploadArea">
                                <?php if (!empty($post['cover_image'])): ?>
                                    <div class="cover-preview" id="coverPreview">
                                        <img src="<?= e(UPLOAD_URL . $post['cover_image']) ?>" alt="Cover">
                                        <button type="button" class="remove-cover" onclick="removeCover()">&times;</button>
                                    </div>
                                    <input type="hidden" name="cover_image" id="coverImageInput" value="<?= e($post['cover_image']) ?>">
                                <?php else: ?>
                                    <div class="cover-upload" id="coverUpload">
                                        <p style="color: var(--color-text-muted); font-size: 0.85rem;">Click or drop to upload cover image</p>
                                        <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" onchange="uploadCover(this)">
                                    </div>
                                    <input type="hidden" name="cover_image" id="coverImageInput" value="">
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-actions" style="display: flex; gap: 8px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">
                                <?= $isEditing ? 'Update Essay' : 'Create Essay' ?>
                            </button>
                        </div>

                        <?php if ($isEditing && $post): ?>
                            <div style="margin-top: 12px;">
                                <form method="post" action="/admin/editor.php" onsubmit="return confirm('Delete this essay permanently?');">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" style="width: 100%; justify-content: center;">Delete Essay</button>
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
            <h3>Insert Image</h3>
            <div class="form-group">
                <label>Upload Image</label>
                <input type="file" id="imageUploadInput" accept="image/jpeg,image/png,image/gif,image/webp">
            </div>
            <div class="form-group">
                <label for="imageAltText">Alt Text</label>
                <input type="text" id="imageAltText" placeholder="Describe the image...">
            </div>
            <div id="imageUploadStatus" style="margin-bottom: 12px;"></div>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeImageModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="insertImage()">Insert</button>
            </div>
        </div>
    </div>

    <script src="/js/app.js"></script>
</body>
</html>
