<?php
/**
 * PropTXChange — Site Header / Navigation
 *
 * Relies on AppAuth static methods which are available because
 * index.php has already included auth.php before this file.
 */

$current_page = $_GET['page'] ?? 'home';
$isLoggedIn   = AppAuth::isLoggedIn();
$userRole     = AppAuth::getRole();
$userName     = AppAuth::getUserName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PropTXChange &mdash; Property Operations Platform</title>
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
            <li><a href="index.php?page=home"     class="<?= $current_page === 'home'     ? 'active' : '' ?>">Overview</a></li>
            <li><a href="index.php?page=features" class="<?= $current_page === 'features' ? 'active' : '' ?>">Features</a></li>
            <?php if ($isLoggedIn && $userRole === 'admin'): ?>
                <li><a href="index.php?page=admin"  class="<?= $current_page === 'admin'  ? 'active' : '' ?>">Dashboard</a></li>
            <?php elseif ($isLoggedIn && $userRole === 'tenant'): ?>
                <li><a href="index.php?page=tenant" class="<?= $current_page === 'tenant' ? 'active' : '' ?>">My Portal</a></li>
            <?php endif; ?>
        </ul>

        <div class="nav-actions">
            <?php if ($isLoggedIn): ?>
                <span style="font-size:0.82rem;color:var(--text-muted);padding-right:0.5rem;">
                    <?= htmlspecialchars($userName) ?>
                    <span class="badge badge-<?= $userRole === 'admin' ? 'gold' : 'green' ?>" style="margin-left:0.3rem;">
                        <?= ucfirst($userRole) ?>
                    </span>
                </span>
                <a href="index.php?page=login&logout=1" class="btn-ghost">Sign Out</a>
            <?php else: ?>
                <a href="index.php?page=register&role=tenant" class="btn-ghost">Register</a>
                <a href="index.php?page=login&role=tenant"    class="btn-ghost">Tenant Login</a>
                <a href="index.php?page=login&role=admin"     class="btn-primary">Admin Login</a>
            <?php endif; ?>
        </div>

        <button class="hamburger" id="hamburger" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<div class="mobile-menu" id="mobileMenu">
    <a href="index.php?page=home">Overview</a>
    <a href="index.php?page=features">Features</a>
    <?php if ($isLoggedIn && $userRole === 'admin'): ?>
        <a href="index.php?page=admin">Dashboard</a>
    <?php elseif ($isLoggedIn && $userRole === 'tenant'): ?>
        <a href="index.php?page=tenant">My Portal</a>
    <?php endif; ?>
    <?php if ($isLoggedIn): ?>
        <a href="index.php?page=login&logout=1">Sign Out</a>
    <?php else: ?>
        <a href="index.php?page=register&role=tenant">Register</a>
        <a href="index.php?page=login&role=tenant">Tenant Login</a>
        <a href="index.php?page=login&role=admin">Admin Login</a>
    <?php endif; ?>
</div>
