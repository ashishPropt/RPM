<?php
/**
 * Registration page — creates a user_profiles row + a tenants row (for tenant role).
 * Admin registration requires an invite code to prevent open sign-ups.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/flash.php';

// Already logged in — bounce to dashboard
if (AppAuth::isLoggedIn()) {
    header('Location: index.php?page=' . (AppAuth::getRole() === 'admin' ? 'admin' : 'tenant'));
    exit;
}

$role    = isset($_GET['role']) && $_GET['role'] === 'admin' ? 'admin' : 'tenant';
$isAdmin = ($role === 'admin');
$errors  = [];
$values  = [];  // repopulate form on error

// ── Admin invite code (set yours in config/env.php) ─────────
// define('ADMIN_INVITE_CODE', 'CHANGE_THIS_SECRET') in env.php
$adminInviteCode = defined('ADMIN_INVITE_CODE') ? ADMIN_INVITE_CODE : 'ADMIN2025';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitise inputs
    $values = [
        'full_name'   => trim($_POST['full_name']   ?? ''),
        'email'       => strtolower(trim($_POST['email']      ?? '')),
        'phone'       => trim($_POST['phone']       ?? ''),
        'password'    => $_POST['password']         ?? '',
        'password2'   => $_POST['password2']        ?? '',
        'invite_code' => trim($_POST['invite_code'] ?? ''),
    ];

    // ── Validation ──────────────────────────────────────────
    if (!$values['full_name'])
        $errors[] = 'Full name is required.';

    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = 'A valid email address is required.';

    if (strlen($values['password']) < 8)
        $errors[] = 'Password must be at least 8 characters.';

    if ($values['password'] !== $values['password2'])
        $errors[] = 'Passwords do not match.';

    if ($isAdmin && $values['invite_code'] !== $adminInviteCode)
        $errors[] = 'Invalid admin invite code.';

    // Check email not already taken
    if (empty($errors)) {
        $existing = db()->queryOne(
            'SELECT id FROM user_profiles WHERE email = ? LIMIT 1',
            [$values['email']]
        );
        if ($existing) {
            $errors[] = 'An account with that email address already exists.';
        }
    }

    // ── Create accounts ─────────────────────────────────────
    if (empty($errors)) {
        $userId = db()->queryOne('SELECT UUID() AS id')['id'];
        $hash   = AppAuth::hashPassword($values['password']);

        // Insert user_profiles row
        db()->execute(
            'INSERT INTO user_profiles (id, role, full_name, email, password_hash, phone)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $role, $values['full_name'], $values['email'], $hash, $values['phone']]
        );

        // If tenant — also create a tenants record
        if ($role === 'tenant') {
            $tenantId = db()->queryOne('SELECT UUID() AS id')['id'];
            $nameParts = explode(' ', $values['full_name'], 2);
            $firstName = $nameParts[0];
            $lastName  = $nameParts[1] ?? '';

            db()->execute(
                'INSERT INTO tenants (id, user_id, first_name, last_name, email, phone, status)
                 VALUES (?, ?, ?, ?, ?, ?, "pending")',
                [$tenantId, $userId, $firstName, $lastName, $values['email'], $values['phone']]
            );
        }

        // Flash and redirect to login
        flash_set('success',
            $role === 'admin'
            ? 'Admin account created. Please sign in.'
            : 'Your account has been created. You can now sign in. Note: your property manager may need to assign you to a unit before your full portal is active.'
        );
        header('Location: index.php?page=login&role=' . $role);
        exit;
    }
}
?>

<div class="page-body">
<div class="login-wrap" style="align-items:flex-start;padding-top:5rem;">
<div class="login-box" style="max-width:480px;">

    <div class="login-header fade-up">
        <div class="login-icon"><?= $isAdmin ? '&#x1F3E2;' : '&#x1F3E0;' ?></div>
        <h2>Create <?= $isAdmin ? 'Admin' : 'Tenant' ?> Account</h2>
        <p><?= $isAdmin
            ? 'Register as a property manager. An invite code is required.'
            : 'Create your resident account to access the tenant portal.' ?>
        </p>
    </div>

    <?php if (!empty($errors)): ?>
    <div style="background:rgba(224,92,92,0.1);border:1px solid rgba(224,92,92,0.3);border-radius:var(--radius);padding:0.9rem 1rem;margin-bottom:1.2rem;font-size:0.875rem;color:var(--red);">
        <strong>&#9888; Please fix the following:</strong>
        <ul style="margin:0.5rem 0 0 1.2rem;">
            <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form class="login-form fade-up delay-1" method="POST"
          action="index.php?page=register&role=<?= $role ?>">

        <!-- Role badge -->
        <div style="background:var(--gold-dim);border:1px solid rgba(201,168,76,0.2);border-radius:var(--radius);padding:0.75rem 1rem;font-size:0.82rem;color:var(--gold);line-height:1.5;">
            Registering as: <strong><?= $isAdmin ? 'Property Manager (Admin)' : 'Resident (Tenant)' ?></strong>
        </div>

        <!-- Full name -->
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name"
                   placeholder="Jane Smith"
                   value="<?= htmlspecialchars($values['full_name'] ?? '') ?>"
                   required>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   placeholder="you@example.com"
                   value="<?= htmlspecialchars($values['email'] ?? '') ?>"
                   autocomplete="email" required>
        </div>

        <!-- Phone -->
        <div class="form-group">
            <label for="phone">Phone <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
            <input type="tel" id="phone" name="phone"
                   placeholder="555-123-4567"
                   value="<?= htmlspecialchars($values['phone'] ?? '') ?>">
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="Min. 8 characters"
                   autocomplete="new-password" required>
        </div>

        <!-- Confirm password -->
        <div class="form-group">
            <label for="password2">Confirm Password</label>
            <input type="password" id="password2" name="password2"
                   placeholder="Repeat password"
                   autocomplete="new-password" required>
        </div>

        <!-- Admin invite code (only shown for admin role) -->
        <?php if ($isAdmin): ?>
        <div class="form-group">
            <label for="invite_code">Admin Invite Code</label>
            <input type="text" id="invite_code" name="invite_code"
                   placeholder="Enter the invite code provided to you"
                   autocomplete="off" required>
            <span style="font-size:0.75rem;color:var(--text-muted);margin-top:0.2rem;">Contact the system owner if you don&rsquo;t have a code.</span>
        </div>
        <?php endif; ?>

        <!-- Tenant notice -->
        <?php if (!$isAdmin): ?>
        <div style="background:rgba(92,138,224,0.08);border:1px solid rgba(92,138,224,0.2);border-radius:var(--radius);padding:0.75rem 1rem;font-size:0.82rem;color:var(--blue);line-height:1.5;">
            &#8505; After registering, your property manager will assign you to your unit. Until then, some portal features may be limited.
        </div>
        <?php endif; ?>

        <button type="submit" class="btn-full">Create Account &rarr;</button>

        <div class="login-switch">
            Already have an account?
            <a href="index.php?page=login&role=<?= $role ?>">Sign in here</a>
            <?php if (!$isAdmin): ?>
            &nbsp;&bull;&nbsp;
            Are you an admin? <a href="index.php?page=register&role=admin">Register as admin</a>
            <?php endif; ?>
        </div>
    </form>

</div>
</div>
</div>
