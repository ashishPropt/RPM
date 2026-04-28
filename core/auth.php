<?php
/**
 * AppAuth — session-based auth using bcrypt against user_profiles table.
 */
class AppAuth
{
    /** Attempt login. Returns ['ok'=>true,'role'=>'...'] or ['ok'=>false,'error'=>'...']. */
    public static function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        if ($email === '' || $password === '') {
            return ['ok' => false, 'error' => 'Email and password are required.'];
        }

        $user = db()->row(
            'SELECT id, role, full_name, password_hash, is_active
               FROM user_profiles WHERE email = ? LIMIT 1',
            [$email]
        );

        if (!$user) {
            return ['ok' => false, 'error' => 'No account found with that email address.'];
        }
        if (!(bool)$user['is_active']) {
            return ['ok' => false, 'error' => 'This account is disabled. Contact your administrator.'];
        }
        if (!password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'Incorrect password.'];
        }

        session_regenerate_id(true);
        $_SESSION['uid']   = $user['id'];
        $_SESSION['role']  = $user['role'];
        $_SESSION['name']  = $user['full_name'];
        $_SESSION['auth']  = true;

        return ['ok' => true, 'role' => $user['role']];
    }

    /** Destroy session. */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool   { return !empty($_SESSION['auth']); }
    public static function role(): string  { return $_SESSION['role'] ?? ''; }
    public static function uid(): string   { return $_SESSION['uid']  ?? ''; }
    public static function name(): string  { return $_SESSION['name'] ?? ''; }

    /** Redirect to login if not authenticated, or wrong role. */
    public static function require(string $role = ''): void
    {
        if (!self::check()) {
            flash('error', 'Please sign in to continue.');
            redirect('login');
        }
        if ($role && self::role() !== $role) {
            redirect(self::role() === 'admin' ? 'admin' : 'tenant');
        }
    }

    public static function hash(string $pw): string
    {
        return password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
