<?php
$page     = $_GET['page'] ?? 'home';
$loggedIn = AppAuth::check();
$role     = AppAuth::role();
$name     = AppAuth::name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PropTXChange</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/app.css">
</head>
<body>
<nav class="nav">
  <div class="nav-inner">
    <a href="index.php" class="nav-brand">
      <span class="brand-hex">⬡</span> PropTX<strong>Change</strong>
    </a>
    <div class="nav-links">
      <?php if ($loggedIn && $role === 'admin'): ?>
        <a href="index.php?page=admin"  class="<?= $page==='admin'  ? 'active':'' ?>">Dashboard</a>
      <?php elseif ($loggedIn && $role === 'tenant'): ?>
        <a href="index.php?page=tenant" class="<?= $page==='tenant' ? 'active':'' ?>">My Portal</a>
      <?php else: ?>
        <a href="index.php?page=home"   class="<?= $page==='home'   ? 'active':'' ?>">Home</a>
      <?php endif; ?>
    </div>
    <div class="nav-actions">
      <?php if ($loggedIn): ?>
        <span class="nav-user"><?= htmlspecialchars($name) ?> <em class="role-tag <?= $role ?>"><?= $role ?></em></span>
        <a href="index.php?page=logout" class="btn btn-ghost">Sign Out</a>
      <?php else: ?>
        <a href="index.php?page=register" class="btn btn-ghost">Register</a>
        <a href="index.php?page=login"    class="btn btn-primary">Sign In</a>
      <?php endif; ?>
    </div>
    <button class="nav-toggle" id="navToggle" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>
  <div class="nav-mobile" id="navMobile">
    <?php if ($loggedIn && $role === 'admin'): ?>
      <a href="index.php?page=admin">Dashboard</a>
    <?php elseif ($loggedIn && $role === 'tenant'): ?>
      <a href="index.php?page=tenant">My Portal</a>
    <?php else: ?>
      <a href="index.php?page=home">Home</a>
    <?php endif; ?>
    <?php if ($loggedIn): ?>
      <a href="index.php?page=logout">Sign Out</a>
    <?php else: ?>
      <a href="index.php?page=register">Register</a>
      <a href="index.php?page=login">Sign In</a>
    <?php endif; ?>
  </div>
</nav>
<main class="main">
