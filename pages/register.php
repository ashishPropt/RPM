<?php
/**
 * Registration page for both admins and tenants.
 * Admin registration requires ADMIN_CODE defined in config/config.php.
 */

if (AppAuth::check()) {
    redirect(AppAuth::role() === 'admin' ? 'admin' : 'tenant');
}

$role    = ($_GET['role'] ?? 'tenant') === 'admin' ? 'admin' : 'tenant';
$isAdmin = $role === 'admin';
$errors  = [];
$v       = [];   // form values to repopulate on error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role    = ($_POST['role'] ?? 'tenant') === 'admin' ? 'admin' : 'tenant';
    $isAdmin = $role === 'admin';

    $v = [
        'full_name'   => trim($_POST['full_name']   ?? ''),
        'email'       => strtolower(trim($_POST['email']      ?? '')),
        'phone'       => trim($_POST['phone']       ?? ''),
        'password'    =>      $_POST['password']    ?? '',
        'password2'   =>      $_POST['password2']   ?? '',
        'admin_code'  => trim($_POST['admin_code']  ?? ''),
    ];

    // --- Validate ---
    if ($v['full_name'] === '')                                $errors[] = 'Full name is required.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL))       $errors[] = 'Enter a valid email address.';
    if (strlen($v['password']) < 8)                            $errors[] = 'Password must be at least 8 characters.';
    if ($v['password'] !== $v['password2'])                    $errors[] = 'Passwords do not match.';
    if ($isAdmin && $v['admin_code'] !== ADMIN_CODE)           $errors[] = 'Invalid admin code.';

    if (empty($errors)) {
        // Check for duplicate email
        $exists = db()->row('SELECT id FROM user_profiles WHERE email = ? LIMIT 1', [$v['email']]);
        if ($exists) $errors[] = 'An account with that email already exists.';
    }

    if (empty($errors)) {
        $uid  = db()->uuid();
        $hash = AppAuth::hash($v['password']);

        // Create login account
        db()->run(
            'INSERT INTO user_profiles (id, role, full_name, email, password_hash, phone)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$uid, $role, $v['full_name'], $v['email'], $hash, $v['phone']]
        );

        // For tenants, also create the tenants record (status=pending until admin assigns a unit)
        if ($role === 'tenant') {
            $tid   = db()->uuid();
            $parts = explode(' ', $v['full_name'], 2);
            db()->run(
                'INSERT INTO tenants (id, user_id, first_name, last_name, email, phone, status)
                 VALUES (?, ?, ?, ?, ?, ?, "pending")',
                [$tid, $uid, $parts[0], $parts[1] ?? '', $v['email'], $v['phone']]
            );
        }

        flash('success', 'Account created! Please sign in' .
            ($role === 'tenant' ? '. Your property manager will assign your unit.' : '.'));
        redirect('login');
    }
}
?>

<div class="auth-wrap">
  <div class="auth-box" style="max-width:460px">

    <div class="auth-header">
      <div class="auth-icon"><?= $isAdmin ? '🏢' : '🏠' ?></div>
      <h2>Create <?= $isAdmin ? 'Admin' : 'Tenant' ?> Account</h2>
      <p><?= $isAdmin ? 'Manage your properties. An admin code is required.' : 'Access your resident portal.' ?></p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <strong>⚠ Please fix the following:</strong>
      <ul style="margin:.5rem 0 0 1.2rem">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=register" class="auth-form">
      <input type="hidden" name="role" value="<?= $role ?>">

      <div class="form-group">
        <label for="full_name">Full Name</label>
        <input type="text" id="full_name" name="full_name"
               value="<?= htmlspecialchars($v['full_name'] ?? '') ?>"
               placeholder="Jane Smith" required>
      </div>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($v['email'] ?? '') ?>"
               placeholder="jane@example.com" autocomplete="email" required>
      </div>

      <div class="form-group">
        <label for="phone">Phone <small style="font-weight:400;color:var(--muted)">(optional)</small></label>
        <input type="tel" id="phone" name="phone"
               value="<?= htmlspecialchars($v['phone'] ?? '') ?>"
               placeholder="555-123-4567">
      </div>

      <div class="form-group">
        <label for="password">Password <small style="font-weight:400;color:var(--muted)">(min 8 chars)</small></label>
        <input type="password" id="password" name="password"
               autocomplete="new-password" required>
      </div>

      <div class="form-group">
        <label for="password2">Confirm Password</label>
        <input type="password" id="password2" name="password2"
               autocomplete="new-password" required>
      </div>

      <?php if ($isAdmin): ?>
      <div class="form-group">
        <label for="admin_code">Admin Invite Code</label>
        <input type="text" id="admin_code" name="admin_code"
               placeholder="Provided by the system owner" autocomplete="off" required>
      </div>
      <?php else: ?>
      <div class="alert alert-info" style="margin-bottom:0">
        ℹ After registering, your property manager will assign you to your unit.
      </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-primary btn-full">Create Account →</button>

      <p class="auth-switch">
        Already registered? <a href="index.php?page=login">Sign in here</a>
        <?php if (!$isAdmin): ?>
          &bull; <a href="index.php?page=register&role=admin">Admin? Register here</a>
        <?php else: ?>
          &bull; <a href="index.php?page=register&role=tenant">Tenant? Register here</a>
        <?php endif; ?>
      </p>
    </form>

  </div>
</div>
