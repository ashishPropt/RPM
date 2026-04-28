<?php
/**
 * PropTXChange — Authentication (MySQL / PHP Sessions)
 *
 * Bootstrapped by index.php — session_start(), env.php, and db.php
 * are already loaded before this file is included.
 *
 * How login works:
 *   1. Look up the user row in user_profiles by email.
 *   2. Verify the submitted password against the stored bcrypt hash.
 *   3. On success, store user info in $_SESSION and redirect.
 */

if (!defined('APP_BOOTSTRAPPED')) {
    // Safety net — ensure config is loaded
    require_once __DIR__ . '/../config/env.php';
}

class AppAuth
{
    // ── Sign In ───────────────────────────────────────────────

    public function signIn(string $email, string $password): array
    {
        // Trim whitespace from submitted values
        $email    = strtolower(trim($email));
        $password = trim($password);

        if ($email === '' || $password === '') {
            return ['ok' => false, 'error' => 'Email and password are required.'];
        }

        // Look up account in user_profiles
        $user = db()->queryOne(
            'SELECT id, role, full_name, email, password_hash, is_active
             FROM   user_profiles
             WHERE  email = ?
             LIMIT  1',
            [$email]
        );

        if (!$user) {
            return ['ok' => false, 'error' => 'No account found with that email address.'];
        }

        if (!(bool)$user['is_active']) {
            return ['ok' => false, 'error' => 'This account has been deactivated. Contact your administrator.'];
        }

        // Verify bcrypt password
        if (!password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'Incorrect password. Please try again.'];
        }

        // Rotate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        // Store minimal user info in session
        $_SESSION['logged_in']  = true;
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_name']  = $user['full_name'] ?? $user['email'];

        return ['ok' => true, 'role' => $user['role']];
    }

    // ── Sign Out ──────────────────────────────────────────────

    public function signOut(): void
    {
        // Clear all session data
        $_SESSION = [];

        // Expire the session cookie
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $p['path'], $p['domain'],
                $p['secure'], $p['httponly']
            );
        }

        session_destroy();
    }

    // ── Session helpers (static, usable anywhere) ─────────────

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['logged_in']);
    }

    public static function getRole(): string
    {
        return $_SESSION['user_role'] ?? '';
    }

    public static function getUserId(): string
    {
        return $_SESSION['user_id'] ?? '';
    }

    public static function getUserName(): string
    {
        return $_SESSION['user_name'] ?? '';
    }

    public static function getUserEmail(): string
    {
        return $_SESSION['user_email'] ?? '';
    }

    /**
     * Require the user to be logged in, optionally as a specific role.
     * Redirects to the login page if the check fails.
     */
    public static function requireLogin(string $requiredRole = ''): void
    {
        if (!self::isLoggedIn()) {
            header('Location: index.php?page=login');
            exit;
        }
        if ($requiredRole !== '' && self::getRole() !== $requiredRole) {
            // Logged in but wrong role — send to their own portal
            $dest = self::getRole() === 'admin' ? 'admin' : 'tenant';
            header('Location: index.php?page=' . $dest);
            exit;
        }
    }

    // ── Utility ───────────────────────────────────────────────

    /** Hash a plaintext password for storage. */
    public static function hashPassword(string $plaintext): string
    {
        return password_hash($plaintext, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}

/** Global helper — call auth() anywhere to get the AppAuth instance. */
function auth(): AppAuth
{
    static $instance = null;
    if ($instance === null) {
        $instance = new AppAuth();
    }
    return $instance;
}
