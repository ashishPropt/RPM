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
            $msg = APP_ENV === 'production'
                ? 'Database connection failed. Please contact the administrator.'
                : 'DB Error: ' . $e->getMessage()
                  . ' — Check credentials in config/config.php';
            die('<p style="font-family:sans-serif;color:#c00;padding:2rem">' . htmlspecialchars($msg) . '</p>');
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
