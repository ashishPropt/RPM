<?php
/**
 * PropTXChange — MySQL Database Client (PDO)
 *
 * Bootstrapped by index.php — do NOT call session_start() or
 * require env.php here; index.php has already done both.
 *
 * Usage anywhere in the app:
 *   db()->queryOne('SELECT ...', [...]);
 *   db()->execute('INSERT ...', [...]);
 */

if (!defined('DB_HOST')) {
    // Safety net if somehow called outside index.php bootstrap
    require_once __DIR__ . '/../config/env.php';
}

class MySQLDB
{
    private PDO $pdo;

    public function __construct()
    {
        $dsn = 'mysql:host=' . DB_HOST
             . ';port='     . DB_PORT
             . ';dbname='   . DB_NAME
             . ';charset=utf8mb4';

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Show a clear error in dev; hide details in production
            if (defined('APP_ENV') && APP_ENV === 'production') {
                die('<p style="font-family:sans-serif;padding:2rem;color:#e05c5c">'.
                    '&#9888; Database connection failed. Please contact the administrator.</p>');
            }
            die('<p style="font-family:sans-serif;padding:2rem;color:#e05c5c">'.
                '<strong>Database connection failed:</strong><br>'
                . htmlspecialchars($e->getMessage()) . '<br><br>'
                . 'Check your credentials in <code>config/env.php</code>.</p>');
        }
    }

    // ── Core helpers ──────────────────────────────────────────

    /** Run a SELECT and return all rows as an array. */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Run a SELECT and return a single row, or null if not found. */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Run an INSERT / UPDATE / DELETE and return affected row count. */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** Return a fresh UUID from MySQL. */
    public function uuid(): string
    {
        $row = $this->queryOne('SELECT UUID() AS id');
        return $row['id'];
    }

    /** Expose the PDO instance for transactions. */
    public function pdo(): PDO
    {
        return $this->pdo;
    }

    // ── Dashboard Stats ───────────────────────────────────────

    public function getDashboardStats(): array
    {
        $props   = $this->queryOne('SELECT COUNT(*) AS cnt FROM properties   WHERE is_active = 1');
        $tenants = $this->queryOne('SELECT COUNT(*) AS cnt FROM tenants       WHERE status = "active"');
        $overdue = $this->queryOne(
            'SELECT COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total
             FROM rent_charges WHERE status = "overdue"'
        );
        $repairs = $this->queryOne(
            'SELECT COUNT(*) AS cnt FROM maintenance_requests
             WHERE status NOT IN ("completed", "cancelled")'
        );
        return [
            'total_properties' => (int)   ($props['cnt']    ?? 0),
            'active_tenants'   => (int)   ($tenants['cnt']  ?? 0),
            'overdue_count'    => (int)   ($overdue['cnt']  ?? 0),
            'overdue_amount'   => (float) ($overdue['total'] ?? 0),
            'open_repairs'     => (int)   ($repairs['cnt']  ?? 0),
        ];
    }

    // ── Properties ────────────────────────────────────────────

    public function getProperties(string $adminId): array
    {
        return $this->query(
            'SELECT p.*, n.name AS neighborhood_name
             FROM   properties p
             LEFT JOIN neighborhoods n ON n.id = p.neighborhood_id
             WHERE  p.admin_id = ? AND p.is_active = 1
             ORDER BY p.created_at DESC',
            [$adminId]
        );
    }

    public function getUnitsByProperty(string $propertyId): array
    {
        return $this->query(
            'SELECT * FROM units
             WHERE  property_id = ? AND is_active = 1
             ORDER BY unit_number ASC',
            [$propertyId]
        );
    }

    // ── Tenants ───────────────────────────────────────────────

    public function getActiveTenants(): array
    {
        return $this->query(
            'SELECT
                t.id, t.first_name, t.last_name, t.email, t.phone,
                t.status, t.score, t.score_notes, t.move_in_date, t.user_id,
                u.id   AS unit_id,       u.unit_number,    u.monthly_rent,
                p.id   AS property_id,   p.name AS property_name,
                p.address AS property_address
             FROM   tenants t
             LEFT JOIN units      u ON u.id = t.unit_id
             LEFT JOIN properties p ON p.id = u.property_id
             WHERE  t.status = "active"
             ORDER BY t.last_name ASC, t.first_name ASC'
        );
    }

    public function getTenantByUserId(string $userId): ?array
    {
        return $this->queryOne(
            'SELECT
                t.id, t.first_name, t.last_name, t.email, t.phone,
                t.status, t.score, t.score_notes, t.move_in_date, t.unit_id,
                u.unit_number, u.monthly_rent, u.property_id,
                p.name AS property_name, p.address AS property_address,
                p.city, p.state_code
             FROM   tenants t
             LEFT JOIN units      u ON u.id = t.unit_id
             LEFT JOIN properties p ON p.id = u.property_id
             WHERE  t.user_id = ?
             LIMIT 1',
            [$userId]
        );
    }

    public function getTenantById(string $tenantId): ?array
    {
        return $this->queryOne(
            'SELECT t.*, u.unit_number, u.monthly_rent,
                    p.name AS property_name, p.address AS property_address
             FROM   tenants t
             LEFT JOIN units      u ON u.id = t.unit_id
             LEFT JOIN properties p ON p.id = u.property_id
             WHERE  t.id = ?',
            [$tenantId]
        );
    }

    // ── Leases ────────────────────────────────────────────────

    public function getActiveLease(string $tenantId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM leases
             WHERE  tenant_id = ? AND status = "active"
             ORDER BY created_at DESC
             LIMIT 1',
            [$tenantId]
        );
    }

    // ── Rent Charges ──────────────────────────────────────────

    public function getRentCharges(string $tenantId): array
    {
        return $this->query(
            'SELECT * FROM rent_charges
             WHERE  tenant_id = ?
             ORDER BY due_date DESC',
            [$tenantId]
        );
    }

    public function getAllChargesWithTenants(): array
    {
        return $this->query(
            'SELECT
                rc.id, rc.amount, rc.due_date, rc.charge_month, rc.status,
                rc.tenant_id,
                CONCAT(t.first_name, " ", t.last_name) AS tenant_name,
                u.unit_number,
                p.name AS property_name
             FROM   rent_charges rc
             JOIN   tenants      t  ON t.id = rc.tenant_id
             LEFT JOIN units     u  ON u.id = t.unit_id
             LEFT JOIN properties p ON p.id = u.property_id
             ORDER BY rc.due_date DESC'
        );
    }

    public function recordPayment(
        string $chargeId,
        string $tenantId,
        float  $amount,
        string $method,
        string $notes,
        string $recordedBy
    ): bool {
        try {
            $this->pdo->beginTransaction();
            $payId = $this->uuid();
            $this->execute(
                'INSERT INTO rent_payments
                    (id, charge_id, tenant_id, amount_paid, payment_date, payment_method, recorded_by, notes)
                 VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?)',
                [$payId, $chargeId, $tenantId, $amount, $method, $recordedBy, $notes]
            );
            $this->execute(
                'UPDATE rent_charges SET status = "paid", updated_at = NOW() WHERE id = ?',
                [$chargeId]
            );
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    // ── Maintenance ───────────────────────────────────────────

    public function getMaintenanceRequests(array $where = []): array
    {
        $sql = 'SELECT
                    mr.id, mr.title, mr.description, mr.priority, mr.status,
                    mr.submitted_at, mr.completed_at, mr.assigned_to,
                    CONCAT(t.first_name, " ", t.last_name) AS tenant_name,
                    u.unit_number
                FROM maintenance_requests mr
                JOIN tenants t ON t.id = mr.tenant_id
                JOIN units   u ON u.id = mr.unit_id';

        $params = [];
        if (!empty($where)) {
            $clauses = [];
            foreach ($where as $col => $val) {
                $clauses[] = "mr.`$col` = ?";
                $params[]  = $val;
            }
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }
        $sql .= ' ORDER BY mr.created_at DESC';
        return $this->query($sql, $params);
    }

    public function updateMaintenanceStatus(
        string $requestId,
        string $newStatus,
        string $updatedBy,
        string $note = ''
    ): bool {
        try {
            $this->pdo->beginTransaction();
            $req       = $this->queryOne('SELECT status FROM maintenance_requests WHERE id = ?', [$requestId]);
            $oldStatus = $req['status'] ?? '';

            // Use separate queries to avoid interpolation of NULL
            if ($newStatus === 'completed') {
                $this->execute(
                    'UPDATE maintenance_requests
                     SET status = ?, updated_at = NOW(), completed_at = NOW()
                     WHERE id = ?',
                    [$newStatus, $requestId]
                );
            } else {
                $this->execute(
                    'UPDATE maintenance_requests
                     SET status = ?, updated_at = NOW(), completed_at = NULL
                     WHERE id = ?',
                    [$newStatus, $requestId]
                );
            }

            $logId = $this->uuid();
            $this->execute(
                'INSERT INTO maintenance_updates (id, request_id, updated_by, old_status, new_status, note)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$logId, $requestId, $updatedBy, $oldStatus, $newStatus, $note]
            );
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function submitRepairRequest(
        string $tenantId,
        string $unitId,
        string $title,
        string $description,
        string $priority = 'normal'
    ): bool {
        $id       = $this->uuid();
        $affected = $this->execute(
            'INSERT INTO maintenance_requests
                (id, tenant_id, unit_id, title, description, priority, status)
             VALUES (?, ?, ?, ?, ?, ?, "open")',
            [$id, $tenantId, $unitId, $title, $description, $priority]
        );
        return $affected > 0;
    }

    // ── Notifications ─────────────────────────────────────────

    public function getNotifications(string $userId, bool $unreadOnly = false): array
    {
        $sql    = 'SELECT * FROM notifications WHERE user_id = ?';
        $params = [$userId];
        if ($unreadOnly) {
            $sql .= ' AND is_read = 0';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT 20';
        return $this->query($sql, $params);
    }

    public function markNotificationRead(string $notifId): void
    {
        $this->execute('UPDATE notifications SET is_read = 1 WHERE id = ?', [$notifId]);
    }

    // ── Documents ─────────────────────────────────────────────

    public function getDocuments(string $tenantId): array
    {
        return $this->query(
            'SELECT * FROM documents
             WHERE  tenant_id = ? AND is_visible_to_tenant = 1
             ORDER BY created_at DESC',
            [$tenantId]
        );
    }
}

/**
 * Global singleton — call db() anywhere to get the MySQLDB instance.
 * The connection is created lazily on first call.
 */
function db(): MySQLDB
{
    static $instance = null;
    if ($instance === null) {
        $instance = new MySQLDB();
    }
    return $instance;
}
