<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

SupabaseAuth::requireLogin('admin');

$adminId = SupabaseAuth::getUserId();

// Load data from Supabase
$stats   = db()->getDashboardStats();
$tenants = db()->getActiveTenants();
$charges = db()->getAllChargesWithTenants();
$repairs = db()->getMaintenanceRequests();

// Handle payment recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    $result = db()->recordPayment(
        $_POST['charge_id'],
        $_POST['tenant_id'],
        (float)$_POST['amount'],
        $_POST['method'] ?? 'manual',
        $_POST['notes'] ?? '',
        $adminId
    );
    if ($result['ok']) {
        header('Location: index.php?page=admin&msg=payment_recorded');
        exit;
    }
}

// Handle repair status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_repair') {
    db()->updateMaintenanceStatus(
        $_POST['request_id'],
        $_POST['new_status'],
        $adminId,
        $_POST['note'] ?? ''
    );
    header('Location: index.php?page=admin&msg=repair_updated');
    exit;
}

$statusBadge = [
    'paid'     => 'badge-green',
    'pending'  => 'badge-gold',
    'overdue'  => 'badge-red',
    'partial'  => 'badge-blue',
    'waived'   => 'badge-gray',
];

$repairBadge = [
    'open'              => 'badge-red',
    'in_process'        => 'badge-gold',
    'materials_needed'  => 'badge-blue',
    'completed'         => 'badge-green',
    'cancelled'         => 'badge-gray',
];

$repairDot = [
    'open'             => 'dot-open',
    'in_process'       => 'dot-inprog',
    'materials_needed' => 'dot-materials',
    'completed'        => 'dot-done',
    'cancelled'        => 'dot-done',
];

$repairLabel = [
    'open'             => 'Open',
    'in_process'       => 'In Process',
    'materials_needed' => 'Materials Needed',
    'completed'        => 'Completed',
    'cancelled'        => 'Cancelled',
];
?>

<div class="page-body">
<div class="section">

    <?php if (isset($_GET['msg'])): ?>
    <div style="background:rgba(76,175,124,0.1); border:1px solid rgba(76,175,124,0.3); border-radius:var(--radius); padding:0.85rem 1rem; margin-bottom:1.5rem; font-size:0.875rem; color:var(--green);">
        &#10003;
        <?= $_GET['msg'] === 'payment_recorded' ? 'Payment recorded successfully.' : 'Repair status updated.' ?>
    </div>
    <?php endif; ?>

    <div class="section-header" style="text-align:left; margin-bottom:2rem;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <span class="section-label">Admin Portal</span>
                <h2 class="section-title" style="margin-bottom:0.3rem;">Property Dashboard</h2>
                <p style="color:var(--text-muted); font-size:0.9rem;">Live overview of your portfolio &mdash; tenants, rent, and maintenance.</p>
            </div>
            <a href="index.php?page=login&logout=1" style="font-size:0.82rem; color:var(--text-muted); border:1px solid var(--border); padding:0.4rem 0.9rem; border-radius:var(--radius);">Sign Out</a>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="dash-grid">
        <div class="dash-card c-gold">
            <div class="dash-label">Total Properties</div>
            <div class="dash-value"><?= $stats['total_properties'] ?></div>
            <div class="dash-sub">Active buildings</div>
        </div>
        <div class="dash-card c-green">
            <div class="dash-label">Active Tenants</div>
            <div class="dash-value"><?= $stats['active_tenants'] ?></div>
            <div class="dash-sub">Currently housed</div>
        </div>
        <div class="dash-card c-red">
            <div class="dash-label">Overdue Charges</div>
            <div class="dash-value"><?= $stats['overdue_count'] ?></div>
            <div class="dash-sub">$<?= number_format($stats['overdue_amount'], 0) ?> outstanding</div>
        </div>
        <div class="dash-card c-blue">
            <div class="dash-label">Open Repairs</div>
            <div class="dash-value"><?= $stats['open_repairs'] ?></div>
            <div class="dash-sub">Awaiting action</div>
        </div>
    </div>

    <!-- TENANT TABLE -->
    <div class="data-table-wrap" style="margin-bottom: 2rem;">
        <div class="data-table-header">
            <h3>Tenants &amp; Rent Status</h3>
            <span class="badge badge-gold"><?= count($tenants) ?> tenants</span>
        </div>
        <?php if (empty($tenants)): ?>
        <div style="padding:2rem 1.5rem; color:var(--text-muted); font-size:0.9rem;">No active tenants found.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>Unit</th>
                    <th>Property</th>
                    <th>Monthly Rent</th>
                    <th>Score</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tenants as $t):
                    $unitNum  = $t['units']['unit_number']  ?? '—';
                    $propName = $t['units']['properties']['name'] ?? '—';
                    $rent     = $t['units']['monthly_rent']  ?? 0;
                    $score    = $t['score'] ?? 100;
                    $scoreColor = $score >= 90 ? 'var(--green)' : ($score >= 70 ? 'var(--gold)' : 'var(--red)');
                ?>
                <tr>
                    <td class="name"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
                    <td><?= htmlspecialchars($unitNum) ?></td>
                    <td style="color:var(--text-muted); font-size:0.82rem;"><?= htmlspecialchars($propName) ?></td>
                    <td class="amount">$<?= number_format($rent, 0) ?></td>
                    <td><span style="font-family:var(--ff-mono); font-size:0.82rem; color:<?= $scoreColor ?>"><?= $score ?></span></td>
                    <td>
                        <a href="index.php?page=tenant_detail&id=<?= $t['id'] ?>" style="font-size:0.78rem; color:var(--gold); font-weight:500;">View &rarr;</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- RENT CHARGES TABLE -->
    <div class="data-table-wrap" style="margin-bottom: 2rem;">
        <div class="data-table-header">
            <h3>Rent Charges</h3>
            <span class="badge badge-gold"><?= count($charges) ?> charges</span>
        </div>
        <?php if (empty($charges)): ?>
        <div style="padding:2rem 1.5rem; color:var(--text-muted); font-size:0.9rem;">No rent charges found.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr><th>Tenant</th><th>Unit</th><th>Month</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($charges as $c):
                    $tName   = isset($c['tenants']) ? $c['tenants']['first_name'] . ' ' . $c['tenants']['last_name'] : '—';
                    $unitNum = $c['tenants']['units']['unit_number'] ?? '—';
                    $badge   = $statusBadge[$c['status']] ?? 'badge-gray';
                ?>
                <tr>
                    <td class="name"><?= htmlspecialchars($tName) ?></td>
                    <td><?= htmlspecialchars($unitNum) ?></td>
                    <td style="font-family:var(--ff-mono); font-size:0.82rem;"><?= $c['charge_month'] ?></td>
                    <td class="amount">$<?= number_format($c['amount'], 0) ?></td>
                    <td style="font-size:0.82rem; color:var(--text-muted);"><?= date('M j, Y', strtotime($c['due_date'])) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= ucfirst($c['status']) ?></span></td>
                    <td>
                        <?php if ($c['status'] !== 'paid' && $c['status'] !== 'waived'): ?>
                        <form method="POST" action="index.php?page=admin" style="display:inline;">
                            <input type="hidden" name="action"    value="record_payment">
                            <input type="hidden" name="charge_id" value="<?= $c['id'] ?>">
                            <input type="hidden" name="tenant_id" value="<?= $c['tenants']['id'] ?? '' ?>">
                            <input type="hidden" name="amount"    value="<?= $c['amount'] ?>">
                            <input type="hidden" name="method"    value="manual">
                            <button type="submit" style="background:none; border:none; cursor:pointer; font-size:0.78rem; color:var(--gold); font-weight:500; padding:0;">Record Payment</button>
                        </form>
                        <?php else: ?>
                        <span style="font-size:0.78rem; color:var(--text-muted);">Settled</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- MAINTENANCE REQUESTS -->
    <div class="section-header" style="text-align:left; margin-bottom:1rem; margin-top:2rem;">
        <h3 style="font-family:var(--ff-display); font-size:1.3rem; font-weight:700; color:var(--text);">Maintenance Requests</h3>
    </div>
    <?php if (empty($repairs)): ?>
    <div style="padding:1.5rem; color:var(--text-muted); font-size:0.9rem;">No maintenance requests found.</div>
    <?php else: ?>
    <div class="repair-list">
        <?php foreach ($repairs as $r):
            $status    = $r['status'] ?? 'open';
            $tName     = isset($r['tenants']) ? $r['tenants']['first_name'] . ' ' . $r['tenants']['last_name'] : '—';
            $unitNum   = $r['units']['unit_number'] ?? '—';
            $dot       = $repairDot[$status]   ?? 'dot-open';
            $badge     = $repairBadge[$status] ?? 'badge-gray';
            $label     = $repairLabel[$status] ?? ucfirst($status);
            $submitted = date('M j', strtotime($r['submitted_at']));
        ?>
        <div class="repair-item">
            <div class="repair-dot <?= $dot ?>"></div>
            <div class="repair-info">
                <div class="repair-title"><?= htmlspecialchars($r['title']) ?></div>
                <div class="repair-meta"><?= htmlspecialchars($tName) ?> &middot; Unit <?= htmlspecialchars($unitNum) ?> &middot; Submitted <?= $submitted ?></div>
            </div>
            <span class="badge <?= $badge ?>"><?= $label ?></span>
            <?php if ($status !== 'completed' && $status !== 'cancelled'): ?>
            <form method="POST" action="index.php?page=admin" style="display:flex; gap:0.5rem; align-items:center;">
                <input type="hidden" name="action"     value="update_repair">
                <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                <select name="new_status" style="background:var(--ink-mid); border:1px solid var(--border-light); color:var(--text-soft); font-size:0.78rem; padding:0.3rem 0.5rem; border-radius:var(--radius); cursor:pointer;">
                    <option value="open"             <?= $status==='open'             ? 'selected' : '' ?>>Open</option>
                    <option value="in_process"       <?= $status==='in_process'       ? 'selected' : '' ?>>In Process</option>
                    <option value="materials_needed" <?= $status==='materials_needed' ? 'selected' : '' ?>>Materials Needed</option>
                    <option value="completed"        >Completed</option>
                </select>
                <button type="submit" style="background:none; border:none; cursor:pointer; font-size:0.78rem; color:var(--gold); font-weight:500; padding:0;">Update</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</div>
