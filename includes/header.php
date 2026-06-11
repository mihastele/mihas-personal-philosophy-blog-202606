<?php
require_once __DIR__ . '/functions.php';
$blogTitle = getSetting('blog_title', "Miha's Blog of Philosophy");
$blogTagline = getSetting('blog_tagline', 'Contemplations on existence, reason, and the human condition');
$currentPage = $currentPage ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($pageTitle ?? $blogTitle) ?></title>
    <meta name="description" content="<?= e($blogTagline) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="page-<?= e($currentPage) ?>">
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <a href="/" class="site-brand">
                    <h1 class="site-title"><?= e($blogTitle) ?></h1>
                    <p class="site-tagline"><?= e($blogTagline) ?></p>
                </a>
                <nav class="site-nav">
                    <a href="/" class="nav-link<?= $currentPage === 'home' ? ' active' : '' ?>">Essays</a>
                    <a href="/?search=" class="nav-link nav-search-toggle" aria-label="Search">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </a>
                </nav>
            </div>
            <div class="search-bar" id="searchBar" style="display:none;">
                <form action="/" method="get" class="search-form">
                    <input type="text" name="search" placeholder="Search essays..." value="<?= e($searchQuery ?? '') ?>" class="search-input" autocomplete="off">
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>
        </div>
    </header>
    <main class="site-main">
        <div class="container">
