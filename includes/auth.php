<?php
/**
 * PropTXChange — Auth Helper (MySQL / PHP Sessions)
 *
 * Handles login, logout, and session-based role checking.
 * Passwords are stored as bcrypt hashes in the user_profiles table.
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/db.php';

class AppAuth {

    public function signIn(string $email, string $password): array {
        $user = db()->queryOne(
            'SELECT id, role, full_name, email, password_hash, is_active
             FROM user_profiles
             WHERE email = ?
             LIMIT 1',
            [strtolower(trim($email))]
        );

        if (!$user) {
            return ['ok' => false, 'error' => 'No account found with that email address.'];
        }

        if (!$user['is_active']) {
            return ['ok' => false, 'error' => 'This account has been deactivated.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'Incorrect password.'];
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_name']  = $user['full_name'];
        $_SESSION['logged_in']  = true;

        return ['ok' => true, 'role' => $user['role']];
    }

    public function signOut(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function isLoggedIn(): bool {
        return !empty($_SESSION['logged_in']);
    }

    public static function getRole(): string {
        return $_SESSION['user_role'] ?? '';
    }

    public static function getUserId(): string {
        return $_SESSION['user_id'] ?? '';
    }

    public static function getUserName(): string {
        return $_SESSION['user_name'] ?? '';
    }

    public static function requireLogin(string $requiredRole = ''): void {
        if (!self::isLoggedIn()) {
            header('Location: index.php?page=login');
            exit;
        }
        if ($requiredRole && self::getRole() !== $requiredRole) {
            header('Location: index.php?page=login&error=unauthorized');
            exit;
        }
    }

    // Utility: hash a new password (use when creating users)
    public static function hashPassword(string $plaintext): string {
        return password_hash($plaintext, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}

function auth(): AppAuth {
    static $instance = null;
    if ($instance === null) $instance = new AppAuth();
    return $instance;
}
