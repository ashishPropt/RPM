<?php
$current_page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PropTXChange — Property Operations Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;900&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">
            <span class="logo-icon">&#x2B21;</span>
            <span class="logo-text">PropTX<em>Change</em></span>
        </a>
        <ul class="nav-links">
            <li><a href="index.php?page=home" class="<?= $current_page === 'home' ? 'active' : '' ?>">Overview</a></li>
            <li><a href="index.php?page=features" class="<?= $current_page === 'features' ? 'active' : '' ?>">Features</a></li>
            <li><a href="index.php?page=admin" class="<?= $current_page === 'admin' ? 'active' : '' ?>">Admin</a></li>
            <li><a href="index.php?page=tenant" class="<?= $current_page === 'tenant' ? 'active' : '' ?>">Tenant</a></li>
        </ul>
        <div class="nav-actions">
            <a href="index.php?page=login&role=tenant" class="btn-ghost">Tenant Login</a>
            <a href="index.php?page=login&role=admin" class="btn-primary">Admin Login</a>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<div class="mobile-menu" id="mobileMenu">
    <a href="index.php?page=home">Overview</a>
    <a href="index.php?page=features">Features</a>
    <a href="index.php?page=admin">Admin Portal</a>
    <a href="index.php?page=tenant">Tenant Portal</a>
    <a href="index.php?page=login&role=tenant">Tenant Login</a>
    <a href="index.php?page=login&role=admin">Admin Login</a>
</div>
