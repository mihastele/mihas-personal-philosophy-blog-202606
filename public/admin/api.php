<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'];

$authHeader = $_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (str_starts_with($authHeader, 'Bearer ')) {
    $authHeader = substr($authHeader, 7);
}

if (!validateApiKey($authHeader)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Provide a valid API key via X-Api-Key header.']);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON body.']);
        exit;
    }

    $title = trim($input['title'] ?? '');
    $content = $input['content'] ?? '';
    $excerpt = trim($input['excerpt'] ?? '');
    $coverImage = trim($input['cover_image'] ?? '') ?: null;
    $status = $input['status'] ?? 'draft';
    $customSlug = trim($input['slug'] ?? '') ?: null;

    if (!in_array($status, ['draft', 'published'], true)) {
        $status = 'draft';
    }

    if (empty($title)) {
        http_response_code(400);
        echo json_encode(['error' => 'Title is required.']);
        exit;
    }

    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Content is required.']);
        exit;
    }

    $id = createPost($title, $content, $excerpt, $coverImage, $status, $customSlug);
    $post = getPostById($id);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'post' => [
            'id' => $post['id'],
            'title' => $post['title'],
            'slug' => $post['slug'],
            'status' => $post['status'],
            'url' => SITE_URL . '/post.php?slug=' . $post['slug'],
        ],
    ]);
    exit;
}

if ($method === 'GET') {
    $posts = getAllPosts(50, 0);
    echo json_encode([
        'posts' => array_map(function ($p) {
            return [
                'id' => $p['id'],
                'title' => $p['title'],
                'slug' => $p['slug'],
                'status' => $p['status'],
                'published_at' => $p['published_at'],
                'created_at' => $p['created_at'],
            ];
        }, $posts),
    ]);
    exit;
}

if ($method === 'PUT' || $method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Post ID is required.']);
        exit;
    }

    $existing = getPostById($id);
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found.']);
        exit;
    }

    $title = trim($input['title'] ?? $existing['title']);
    $content = $input['content'] ?? $existing['content'];
    $excerpt = trim($input['excerpt'] ?? $existing['excerpt']);
    $coverImage = isset($input['cover_image']) ? ($input['cover_image'] ?: null) : $existing['cover_image'];
    $status = $input['status'] ?? $existing['status'];
    $customSlug = isset($input['slug']) ? ($input['slug'] ?: null) : null;

    if (!in_array($status, ['draft', 'published'], true)) {
        $status = $existing['status'];
    }

    updatePost($id, $title, $content, $excerpt, $coverImage, $status, $customSlug);
    $post = getPostById($id);

    echo json_encode([
        'success' => true,
        'post' => [
            'id' => $post['id'],
            'title' => $post['title'],
            'slug' => $post['slug'],
            'status' => $post['status'],
            'url' => SITE_URL . '/post.php?slug=' . $post['slug'],
        ],
    ]);
    exit;
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    $input = json_decode(file_get_contents('php://input'), true);
    if ($id <= 0 && $input) {
        $id = (int)($input['id'] ?? 0);
    }

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Post ID is required.']);
        exit;
    }

    if (deletePost($id)) {
        echo json_encode(['success' => true, 'message' => 'Post deleted.']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);
