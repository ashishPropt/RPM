<?php
/**
 * PropTXChange — Supabase Database Client
 *
 * Uses Supabase REST API (PostgREST) via PHP cURL.
 * Set credentials in config/env.php or environment variables.
 */

require_once __DIR__ . '/../config/env.php';

class SupabaseDB {
    private string $url;
    private string $key;
    private string $apiBase;

    public function __construct() {
        $this->url     = rtrim(SUPABASE_URL, '/');
        $this->key     = SUPABASE_ANON_KEY;
        $this->apiBase = $this->url . '/rest/v1';
    }

    // ── Core HTTP request ──────────────────────────────────────
    private function request(
        string $method,
        string $endpoint,
        array  $params  = [],
        array  $body    = [],
        array  $extra_headers = []
    ): array {
        $url = $this->apiBase . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $headers = array_merge([
            'apikey: '          . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ], $extra_headers);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);
        return [
            'status' => $httpCode,
            'data'   => $decoded ?? [],
            'ok'     => $httpCode >= 200 && $httpCode < 300,
        ];
    }

    // ── SELECT ────────────────────────────────────────────────
    public function select(string $table, array $params = []): array {
        return $this->request('GET', '/' . $table, $params);
    }

    // ── INSERT ────────────────────────────────────────────────
    public function insert(string $table, array $data): array {
        return $this->request('POST', '/' . $table, [], $data);
    }

    // ── UPDATE ────────────────────────────────────────────────
    public function update(string $table, array $filter, array $data): array {
        return $this->request('PATCH', '/' . $table, $filter, $data);
    }

    // ── DELETE ────────────────────────────────────────────────
    public function delete(string $table, array $filter): array {
        return $this->request('DELETE', '/' . $table, $filter);
    }

    // ── Convenience: single row by ID ─────────────────────────
    public function findById(string $table, string $id): ?array {
        $result = $this->select($table, ['id' => 'eq.' . $id, 'limit' => '1']);
        return ($result['ok'] && !empty($result['data'])) ? $result['data'][0] : null;
    }

    // ── Properties ────────────────────────────────────────────
    public function getProperties(string $adminId): array {
        $r = $this->select('properties', [
            'admin_id' => 'eq.' . $adminId,
            'is_active' => 'eq.true',
            'order' => 'created_at.desc',
            'select' => 'id,name,address,city,state_code,zip_code,is_active,neighborhoods(name)',
        ]);
        return $r['ok'] ? $r['data'] : [];
    }

    // ── Units ─────────────────────────────────────────────────
    public function getUnitsByProperty(string $propertyId): array {
        $r = $this->select('units', [
            'property_id' => 'eq.' . $propertyId,
            'is_active'   => 'eq.true',
            'order'       => 'unit_number.asc',
        ]);
        return $r['ok'] ? $r['data'] : [];
    }

    // ── Tenants ───────────────────────────────────────────────
    public function getActiveTenants(): array {
        $r = $this->select('tenants', [
            'status' => 'eq.active',
            'order'  => 'last_name.asc',
            'select' => 'id,first_name,last_name,email,phone,status,score,move_in_date,unit_id,units(unit_number,monthly_rent,properties(name,address))',
        ]);
        return $r['ok'] ? $r['data'] : [];
    }

    public function getTenantByUserId(string $userId): ?array {
        $r = $this->select('tenants', [
            'user_id' => 'eq.' . $userId,
            'limit'   => '1',
            'select'  => 'id,first_name,last_name,email,phone,status,score,score_notes,move_in_date,unit_id,units(unit_number,monthly_rent,property_id,properties(name,address,city,state_code))',
        ]);
        return ($r['ok'] && !empty($r['data'])) ? $r['data'][0] : null;
    }

    // ── Rent Charges ──────────────────────────────────────────
    public function getRentCharges(string $tenantId): array {
        $r = $this->select('rent_charges', [
            'tenant_id' => 'eq.' . $tenantId,
            'order'     => 'due_date.desc',
        ]);
        return $r['ok'] ? $r['data'] : [];
    }

    public function getAllChargesWithTenants(): array {
        $r = $this->select('rent_charges', [
            'order'  => 'due_date.desc',
            'select' => 'id,amount,due_date,charge_month,status,tenants(id,first_name,last_name,units(unit_number,properties(name)))',
        ]);
        return $r['ok'] ? $r['data'] : [];
    }

    public function recordPayment(string $chargeId, string $tenantId, float $amount, string $method, string $notes, string $recordedBy): array {
        // Insert payment
        $pay = $this->insert('rent_payments', [
            'charge_id'      => $chargeId,
            'tenant_id'      => $tenantId,
            'amount_paid'    => $amount,
            'payment_date'   => date('Y-m-d'),
            'payment_method' => $method,
            'notes'          => $notes,
            'recorded_by'    => $recordedBy,
        ]);
        // Update charge status
        if ($pay['ok']) {
            $this->update('rent_charges', ['id' => 'eq.' . $chargeId], ['status' => 'paid', 'updated_at' => date('c')]);
        }
        return $pay;
    }

    // ── Maintenance ───────────────────────────────────────────
    public function getMaintenanceRequests(array $filters = []): array {
        $params = array_merge([
            'order'  => 'created_at.desc',
            'select' => 'id,title,description,priority,status,submitted_at,completed_at,tenants(first_name,last_name),units(unit_number)',
        ], $filters);
        $r = $this->select('maintenance_requests', $params);
        return $r['ok'] ? $r['data'] : [];
    }

    public function updateMaintenanceStatus(string $requestId, string $newStatus, string $updatedBy, string $note = ''): array {
        // Get old status
        $req = $this->findById('maintenance_requests', $requestId);
        $oldStatus = $req['status'] ?? '';

        // Update request
        $this->update('maintenance_requests',
            ['id' => 'eq.' . $requestId],
            ['status' => $newStatus, 'updated_at' => date('c'),
             'completed_at' => ($newStatus === 'completed') ? date('c') : null]
        );

        // Log the update
        return $this->insert('maintenance_updates', [
            'request_id' => $requestId,
            'updated_by' => $updatedBy,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note'       => $note,
        ]);
    }

    // ── Notifications ─────────────────────────────────────────
    public function getNotifications(string $userId, bool $unreadOnly = false): array {
        $params = [
            'user_id' => 'eq.' . $userId,
            'order'   => 'created_at.desc',
            'limit'   => '20',
        ];
        if ($unreadOnly) {
            $params['is_read'] = 'eq.false';
        }
        $r = $this->select('notifications', $params);
        return $r['ok'] ? $r['data'] : [];
    }

    public function markNotificationRead(string $notifId): void {
        $this->update('notifications', ['id' => 'eq.' . $notifId], ['is_read' => true]);
    }

    // ── Documents ─────────────────────────────────────────────
    public function getDocuments(string $tenantId): array {
        $r = $this->select('documents', [
            'tenant_id'          => 'eq.' . $tenantId,
            'is_visible_to_tenant' => 'eq.true',
            'order'              => 'created_at.desc',
        ]);
        return $r['ok'] ? $r['data'] : [];
    }

    // ── Leases ────────────────────────────────────────────────
    public function getActiveLease(string $tenantId): ?array {
        $r = $this->select('leases', [
            'tenant_id' => 'eq.' . $tenantId,
            'status'    => 'eq.active',
            'limit'     => '1',
            'order'     => 'created_at.desc',
        ]);
        return ($r['ok'] && !empty($r['data'])) ? $r['data'][0] : null;
    }

    // ── Dashboard stats (admin) ───────────────────────────────
    public function getDashboardStats(): array {
        $properties   = $this->select('properties',           ['is_active' => 'eq.true',    'select' => 'id']);
        $activeTenants = $this->select('tenants',             ['status'    => 'eq.active',  'select' => 'id']);
        $overdueCharges = $this->select('rent_charges',       ['status'    => 'eq.overdue', 'select' => 'id,amount']);
        $openRepairs   = $this->select('maintenance_requests',['status'    => 'neq.completed', 'select' => 'id']);

        $overdueAmount = 0;
        if ($overdueCharges['ok']) {
            foreach ($overdueCharges['data'] as $c) {
                $overdueAmount += (float)$c['amount'];
            }
        }

        return [
            'total_properties'  => $properties['ok']    ? count($properties['data'])    : 0,
            'active_tenants'    => $activeTenants['ok'] ? count($activeTenants['data']) : 0,
            'overdue_count'     => $overdueCharges['ok'] ? count($overdueCharges['data']) : 0,
            'overdue_amount'    => $overdueAmount,
            'open_repairs'      => $openRepairs['ok']   ? count($openRepairs['data'])   : 0,
        ];
    }
}

// Global singleton
function db(): SupabaseDB {
    static $instance = null;
    if ($instance === null) {
        $instance = new SupabaseDB();
    }
    return $instance;
}
