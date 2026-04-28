<?php
/**
 * PropTXChange — Auth Helper
 *
 * Thin wrapper around Supabase Auth REST API.
 * Stores session token in PHP $_SESSION.
 */

require_once __DIR__ . '/../config/env.php';

class SupabaseAuth {
    private string $authBase;
    private string $key;

    public function __construct() {
        $this->authBase = rtrim(SUPABASE_URL, '/') . '/auth/v1';
        $this->key      = SUPABASE_ANON_KEY;
    }

    private function post(string $endpoint, array $body): array {
        $ch = curl_init($this->authBase . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: '       . $this->key,
            'Content-Type: application/json',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => $httpCode, 'data' => json_decode($response, true) ?? []];
    }

    public function signIn(string $email, string $password): array {
        $r = $this->post('/token?grant_type=password', [
            'email'    => $email,
            'password' => $password,
        ]);
        if ($r['status'] === 200 && isset($r['data']['access_token'])) {
            $_SESSION['access_token'] = $r['data']['access_token'];
            $_SESSION['user_id']      = $r['data']['user']['id'] ?? null;
            $_SESSION['user_email']   = $r['data']['user']['email'] ?? null;
            $_SESSION['user_role']    = $r['data']['user']['user_metadata']['role'] ?? 'tenant';
            return ['ok' => true, 'role' => $_SESSION['user_role']];
        }
        return ['ok' => false, 'error' => $r['data']['error_description'] ?? 'Login failed'];
    }

    public function signOut(): void {
        session_destroy();
    }

    public static function isLoggedIn(): bool {
        return !empty($_SESSION['access_token']);
    }

    public static function getRole(): string {
        return $_SESSION['user_role'] ?? '';
    }

    public static function getUserId(): string {
        return $_SESSION['user_id'] ?? '';
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
}

function auth(): SupabaseAuth {
    static $instance = null;
    if ($instance === null) $instance = new SupabaseAuth();
    return $instance;
}
