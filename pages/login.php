<?php
require_once __DIR__ . '/../includes/auth.php';

$role  = isset($_GET['role']) ? $_GET['role'] : 'admin';
$isAdmin = $role === 'admin';
$error = '';
$success = '';

// Handle login form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $result = auth()->signIn($email, $password);
        if ($result['ok']) {
            $dest = ($result['role'] === 'admin') ? 'admin' : 'tenant';
            header('Location: index.php?page=' . $dest);
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    auth()->signOut();
    header('Location: index.php?page=login');
    exit;
}
?>

<div class="page-body">
<div class="login-wrap">
    <div class="login-box">

        <div class="login-header fade-up">
            <div class="login-icon"><?= $isAdmin ? '&#x1F3E2;' : '&#x1F3E0;' ?></div>
            <h2><?= $isAdmin ? 'Admin Sign In' : 'Tenant Sign In' ?></h2>
            <p><?= $isAdmin ? 'Access your property management dashboard.' : 'Access your resident portal and account.' ?></p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error fade-up" style="background:rgba(224,92,92,0.1); border:1px solid rgba(224,92,92,0.3); border-radius:var(--radius); padding:0.85rem 1rem; margin-bottom:1rem; font-size:0.875rem; color:var(--red);">
            &#9888; <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form class="login-form fade-up delay-1" method="POST" action="index.php?page=login&role=<?= $role ?>">

            <div style="background:var(--gold-dim); border:1px solid rgba(201,168,76,0.2); border-radius:var(--radius); padding:0.75rem 1rem; font-size:0.82rem; color:var(--gold); line-height:1.5;">
                <strong><?= $isAdmin ? 'Admin access' : 'Tenant access' ?>:</strong>
                <?= $isAdmin
                    ? ' Signing in as a property manager gives access to all admin tools and tenant data.'
                    : ' Signing in as a resident shows only your own rental information.' ?>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="you@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                       autocomplete="current-password" required>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <a href="#" style="font-size:0.8rem; color:var(--text-muted);">Forgot password?</a>
            </div>

            <button type="submit" class="btn-full">
                Sign In <?= $isAdmin ? 'as Admin' : 'as Tenant' ?> &rarr;
            </button>

            <div class="login-switch">
                <?php if ($isAdmin): ?>
                    Tenant? <a href="index.php?page=login&role=tenant">Sign in here</a>
                <?php else: ?>
                    Property manager? <a href="index.php?page=login&role=admin">Admin sign in</a>
                <?php endif; ?>
            </div>
        </form>

        <div style="margin-top:1.5rem; text-align:center;">
            <p style="font-size:0.75rem; color:var(--text-muted); line-height:1.6;">
                Authentication is handled by Supabase.<br>
                If you cannot sign in, contact your property manager or check your invite email.
            </p>
        </div>
    </div>
</div>
</div>
