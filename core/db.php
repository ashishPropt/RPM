<?php
/**
 * MySQLDB — thin PDO wrapper.
 * Call db() anywhere to get the singleton instance.
 */
class MySQLDB
{
    private PDO $pdo;

    public function __construct()
    {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT
             . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Show the full error so we can diagnose — we will lock this down once connected
            $host  = defined('DB_HOST') ? DB_HOST : '(DB_HOST not defined)';
            $name  = defined('DB_NAME') ? DB_NAME : '(DB_NAME not defined)';
            $user  = defined('DB_USER') ? DB_USER : '(DB_USER not defined)';
            $error = $e->getMessage();

            echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
                  <title>Database Error</title>
                  <style>body{font-family:sans-serif;padding:2rem;background:#111;color:#eee}
                  pre{background:#222;padding:1rem;border-radius:6px;color:#f88;overflow-x:auto}
                  table{border-collapse:collapse;margin:1rem 0}
                  td{padding:.4rem 1rem;border:1px solid #333}
                  .label{color:#aaa}</style></head><body>
                  <h2>&#9888; Database Connection Failed</h2>
                  <p>The application could not connect to MySQL. Check the details below:</p>
                  <table>
                    <tr><td class="label">DB_HOST</td><td>' . htmlspecialchars($host) . '</td></tr>
                    <tr><td class="label">DB_NAME</td><td>' . htmlspecialchars($name) . '</td></tr>
                    <tr><td class="label">DB_USER</td><td>' . htmlspecialchars($user) . '</td></tr>
                    <tr><td class="label">DB_PASS</td><td>(hidden)</td></tr>
                  </table>
                  <p><strong>MySQL error:</strong></p>
                  <pre>' . htmlspecialchars($error) . '</pre>
                  <p>Edit <code>config/config.php</code> on the server with your correct cPanel MySQL credentials.</p>
                  </body></html>';
            exit;
        }
    }

    /** Run SELECT, return all rows. */
    public function rows(string $sql, array $p = []): array
    {
        $s = $this->pdo->prepare($sql);
        $s->execute($p);
        return $s->fetchAll();
    }

    /** Run SELECT, return one row or null. */
    public function row(string $sql, array $p = []): ?array
    {
        $s = $this->pdo->prepare($sql);
        $s->execute($p);
        $r = $s->fetch();
        return $r ?: null;
    }

    /** Run INSERT/UPDATE/DELETE, return affected row count. */
    public function run(string $sql, array $p = []): int
    {
        $s = $this->pdo->prepare($sql);
        $s->execute($p);
        return $s->rowCount();
    }

    /** Begin a transaction, run a callable, commit or rollback. */
    public function transaction(callable $fn): bool
    {
        try {
            $this->pdo->beginTransaction();
            $fn($this);
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /** Get a fresh UUID from MySQL. */
    public function uuid(): string
    {
        return $this->row('SELECT UUID() AS id')['id'];
    }
}

function db(): MySQLDB
{
    static $i;
    return $i ??= new MySQLDB();
}
