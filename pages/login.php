<?php
/**
 * Login page — authenticates directly against the MySQL user_profiles table.
 *
 * Flow:
 *   GET  ?page=login           — show the form
 *   GET  ?page=login&logout=1  — destroy session and redirect
 *   POST ?page=login&role=...  — validate credentials and redirect on success
 *
 * All includes (session, env, db, auth, flash) are already loaded
 * by index.php before this page is included.
 */

// ── Logout ────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    auth()->signOut();
    flash_set('info', 'You have been signed out.');
    header('Location: index.php?page=login');
    exit;
}

// ── Already logged in ─────────────────────────────────────────
if (AppAuth::isLoggedIn()) {
    header('Location: index.php?page=' . (AppAuth::getRole() === 'admin' ? 'admin' : 'tenant'));
    exit;
}

// ── Setup ─────────────────────────────────────────────────────
$role    = ($_GET['role'] ?? 'tenant') === 'admin' ? 'admin' : 'tenant';
$isAdmin = $role === 'admin';
$error   = '';

// ── Handle POST (the actual login attempt) ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both your email address and password.';
    } else {
        // signIn() queries user_profiles, checks password_verify(), sets $_SESSION
        $result = auth()->signIn($email, $password);

        if ($result['ok']) {
            // Redirect to the correct portal based on the role stored in the DB
            header('Location: index.php?page=' . ($result['role'] === 'admin' ? 'admin' : 'tenant'));
            exit;
        }

        // signIn() returned an error string — show it to the user
        $error = $result['error'];
    }
}
?>

<div class="page-body">
<div class="login-wrap">
<div class="login-box">

    <div class="login-header fade-up">
        <div class="login-icon"><?= $isAdmin ? '&#x1F3E2;' : '&#x1F3E0;' ?></div>
        <h2><?= $isAdmin ? 'Admin Sign In' : 'Tenant Sign In' ?></h2>
        <p><?= $isAdmin
            ? 'Access your property management dashboard.'
            : 'Access your resident portal.' ?></p>
    </div>

    <?= flash_html() ?>

    <?php if ($error !== ''): ?>
    <div style="background:rgba(224,92,92,0.1);border:1px solid rgba(224,92,92,0.3);
                border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem;
                font-size:0.875rem;color:var(--red);">
        &#9888; <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form class="login-form fade-up delay-1"
          method="POST"
          action="index.php?page=login&role=<?= htmlspecialchars($role) ?>">

        <div style="background:var(--gold-dim);border:1px solid rgba(201,168,76,0.2);
                    border-radius:var(--radius);padding:0.75rem 1rem;
                    font-size:0.82rem;color:var(--gold);line-height:1.5;">
            <strong><?= $isAdmin ? '&#x1F511; Admin access' : '&#x1F3E0; Tenant access' ?>:</strong>
            <?= $isAdmin
                ? ' Sign in as a property manager to access all admin tools.'
                : ' Sign in as a resident to view your rent, repairs, and notifications.' ?>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email"
                   id="email"
                   name="email"
                   placeholder="you@example.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   autocomplete="email"
                   required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password"
                   id="password"
                   name="password"
                   placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                   autocomplete="current-password"
                   required>
        </div>

        <div style="display:flex;justify-content:flex-end;">
            <a href="#" style="font-size:0.8rem;color:var(--text-muted);">Forgot password?</a>
        </div>

        <button type="submit" class="btn-full">
            Sign In <?= $isAdmin ? 'as Admin' : 'as Tenant' ?> &rarr;
        </button>

        <div class="login-switch">
            <?php if ($isAdmin): ?>
                Tenant? <a href="index.php?page=login&role=tenant">Sign in here</a>
                &nbsp;&bull;&nbsp;
                New admin? <a href="index.php?page=register&role=admin">Register</a>
            <?php else: ?>
                Property manager? <a href="index.php?page=login&role=admin">Admin sign in</a>
                &nbsp;&bull;&nbsp;
                New tenant? <a href="index.php?page=register&role=tenant">Register</a>
            <?php endif; ?>
        </div>
    </form>

</div>
</div>
</div>
