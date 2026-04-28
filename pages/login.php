<?php
/**
 * Login page.
 * POST → AppAuth::login() → session set → redirect to correct dashboard.
 */

// Already logged in
if (AppAuth::check()) {
    redirect(AppAuth::role() === 'admin' ? 'admin' : 'tenant');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    $result = AppAuth::login($email, $password);

    if ($result['ok']) {
        // Redirect to the correct dashboard based on role stored in DB
        redirect($result['role'] === 'admin' ? 'admin' : 'tenant');
    }

    $error = $result['error'];
}
?>

<div class="auth-wrap">
  <div class="auth-box">

    <div class="auth-header">
      <div class="auth-icon">🔑</div>
      <h2>Sign In</h2>
      <p>Access your PropTXChange account.</p>
    </div>

    <?= flash_html() ?>

    <?php if ($error): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=login" class="auth-form">

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($email) ?>"
               placeholder="you@example.com"
               autocomplete="email" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="Your password"
               autocomplete="current-password" required>
      </div>

      <button type="submit" class="btn btn-primary btn-full">Sign In →</button>

      <p class="auth-switch">
        Don&rsquo;t have an account? <a href="index.php?page=register">Register here</a>
      </p>
    </form>

  </div>
</div>
