<?php
/**
 * Admin Dashboard.
 * All POST actions use PRG (Post/Redirect/Get) so refresh never resubmits.
 */
AppAuth::requireRole('admin');
$aid = AppAuth::uid();

// ── Handle POST actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'record_payment') {
        $ok = db()->transaction(function ($db) use ($aid) {
            $db->run(
                'INSERT INTO rent_payments
                    (id, charge_id, tenant_id, amount_paid, payment_date, payment_method, recorded_by)
                 VALUES (?, ?, ?, ?, CURDATE(), ?, ?)',
                [
                    $db->uuid(),
                    $_POST['charge_id'],
                    $_POST['tenant_id'],
                    (float)($_POST['amount'] ?? 0),
                    $_POST['method'] ?? 'manual',
                    $aid,
                ]
            );
            $db->run(
                'UPDATE rent_charges SET status="paid", updated_at=NOW() WHERE id=?',
                [$_POST['charge_id']]
            );
        });
        flash($ok ? 'success' : 'error', $ok ? 'Payment recorded successfully.' : 'Payment could not be saved. Check logs.');
        redirect('admin');
    }

    if ($action === 'update_repair') {
        $rid    = $_POST['request_id'] ?? '';
        $status = $_POST['new_status']  ?? 'open';
        $old    = db()->row('SELECT status FROM maintenance_requests WHERE id=?', [$rid]);
        db()->transaction(function ($db) use ($rid, $status, $aid, $old) {
            if ($status === 'completed') {
                $db->run(
                    'UPDATE maintenance_requests SET status=?, updated_at=NOW(), completed_at=NOW() WHERE id=?',
                    [$status, $rid]
                );
            } else {
                $db->run(
                    'UPDATE maintenance_requests SET status=?, updated_at=NOW(), completed_at=NULL WHERE id=?',
                    [$status, $rid]
                );
            }
            $db->run(
                'INSERT INTO maintenance_updates (id, request_id, updated_by, old_status, new_status, note)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$db->uuid(), $rid, $aid, $old['status'] ?? '', $status, $_POST['note'] ?? '']
            );
        });
        flash('success', 'Repair status updated.');
        redirect('admin');
    }
}

// ── Load dashboard data ───────────────────────────────────────
$stats = [
    'properties' => (int)(db()->row('SELECT COUNT(*) c FROM properties WHERE is_active=1')['c'] ?? 0),
    'tenants'    => (int)(db()->row('SELECT COUNT(*) c FROM tenants    WHERE status="active"')['c'] ?? 0),
    'overdue'    => db()->row('SELECT COUNT(*) c, COALESCE(SUM(amount),0) t FROM rent_charges WHERE status="overdue"'),
    'repairs'    => (int)(db()->row('SELECT COUNT(*) c FROM maintenance_requests WHERE status NOT IN ("completed","cancelled")')['c'] ?? 0),
];

$tenants = db()->rows(
    'SELECT t.id, t.first_name, t.last_name, t.email, t.status, t.score,
            u.unit_number, u.monthly_rent,
            p.name AS property_name
     FROM   tenants t
     LEFT JOIN units      u ON u.id = t.unit_id
     LEFT JOIN properties p ON p.id = u.property_id
     WHERE  t.status = "active"
     ORDER BY t.last_name, t.first_name'
);

$charges = db()->rows(
    'SELECT rc.id, rc.amount, rc.due_date, rc.charge_month, rc.status,
            rc.tenant_id,
            CONCAT(t.first_name," ",t.last_name) AS tenant_name,
            u.unit_number
     FROM   rent_charges rc
     JOIN   tenants      t ON t.id = rc.tenant_id
     LEFT JOIN units     u ON u.id = t.unit_id
     ORDER BY rc.due_date DESC'
);

$repairs = db()->rows(
    'SELECT mr.id, mr.title, mr.status, mr.priority, mr.submitted_at,
            CONCAT(t.first_name," ",t.last_name) AS tenant_name,
            u.unit_number
     FROM   maintenance_requests mr
     JOIN   tenants t ON t.id = mr.tenant_id
     JOIN   units   u ON u.id = mr.unit_id
     ORDER BY mr.created_at DESC'
);

$chargeBadge = [
    'paid'=>'badge-green','pending'=>'badge-gold','overdue'=>'badge-red',
    'partial'=>'badge-blue','waived'=>'badge-gray',
];
$repairBadge = [
    'open'=>'badge-red','in_process'=>'badge-gold','materials_needed'=>'badge-blue',
    'completed'=>'badge-green','cancelled'=>'badge-gray',
];
$repairDot = [
    'open'=>'dot-red','in_process'=>'dot-gold','materials_needed'=>'dot-blue',
    'completed'=>'dot-green','cancelled'=>'dot-gray',
];
$repairLabel = [
    'open'=>'Open','in_process'=>'In Process','materials_needed'=>'Materials Needed',
    'completed'=>'Completed','cancelled'=>'Cancelled',
];
?>

<div class="container">

<?= flash_html() ?>

<div class="page-header">
  <div>
    <h1>Admin Dashboard</h1>
    <p class="muted">Welcome back, <?= htmlspecialchars(AppAuth::name()) ?></p>
  </div>
</div>

<!-- KPI CARDS -->
<div class="kpi-grid">
  <div class="kpi-card kpi-gold">
    <div class="kpi-label">Properties</div>
    <div class="kpi-value"><?= $stats['properties'] ?></div>
  </div>
  <div class="kpi-card kpi-green">
    <div class="kpi-label">Active Tenants</div>
    <div class="kpi-value"><?= $stats['tenants'] ?></div>
  </div>
  <div class="kpi-card kpi-red">
    <div class="kpi-label">Overdue</div>
    <div class="kpi-value"><?= (int)($stats['overdue']['c'] ?? 0) ?></div>
    <div class="kpi-sub">$<?= number_format((float)($stats['overdue']['t'] ?? 0), 0) ?> outstanding</div>
  </div>
  <div class="kpi-card kpi-blue">
    <div class="kpi-label">Open Repairs</div>
    <div class="kpi-value"><?= $stats['repairs'] ?></div>
  </div>
</div>

<!-- TENANTS TABLE -->
<div class="card mt">
  <div class="card-head">
    <h2>Tenants</h2>
    <span class="badge badge-gold"><?= count($tenants) ?></span>
  </div>
  <?php if (empty($tenants)): ?>
  <p class="card-empty">No active tenants yet. Once a tenant registers and is assigned a unit they will appear here.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Name</th><th>Unit</th><th>Property</th><th>Rent/mo</th><th>Score</th></tr>
      </thead>
      <tbody>
        <?php foreach ($tenants as $t):
          $sc     = (int)($t['score'] ?? 100);
          $sc_cls = $sc >= 90 ? 'good' : ($sc >= 70 ? 'ok' : 'bad');
        ?>
        <tr>
          <td><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
          <td><?= htmlspecialchars($t['unit_number']    ?? '—') ?></td>
          <td class="muted"><?= htmlspecialchars($t['property_name'] ?? '—') ?></td>
          <td class="mono">$<?= number_format((float)($t['monthly_rent'] ?? 0), 0) ?></td>
          <td><span class="score score-<?= $sc_cls ?>"><?= $sc ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- RENT CHARGES -->
<div class="card mt">
  <div class="card-head">
    <h2>Rent Charges</h2>
    <span class="badge badge-gold"><?= count($charges) ?></span>
  </div>
  <?php if (empty($charges)): ?>
  <p class="card-empty">No rent charges on record yet.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Tenant</th><th>Unit</th><th>Month</th><th>Amount</th><th>Due</th><th>Status</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($charges as $c):
          $b = $chargeBadge[$c['status']] ?? 'badge-gray';
        ?>
        <tr>
          <td><?= htmlspecialchars($c['tenant_name']) ?></td>
          <td><?= htmlspecialchars($c['unit_number'] ?? '—') ?></td>
          <td class="mono"><?= htmlspecialchars($c['charge_month']) ?></td>
          <td class="mono">$<?= number_format((float)$c['amount'], 0) ?></td>
          <td class="muted"><?= date('M j, Y', strtotime($c['due_date'])) ?></td>
          <td><span class="badge <?= $b ?>"><?= ucfirst($c['status']) ?></span></td>
          <td>
            <?php if (!in_array($c['status'], ['paid','waived'], true)): ?>
            <form method="POST" action="index.php?page=admin">
              <input type="hidden" name="action"    value="record_payment">
              <input type="hidden" name="charge_id" value="<?= htmlspecialchars($c['id']) ?>">
              <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($c['tenant_id']) ?>">
              <input type="hidden" name="amount"    value="<?= htmlspecialchars($c['amount']) ?>">
              <input type="hidden" name="method"    value="manual">
              <button type="submit" class="btn-link">Record Payment</button>
            </form>
            <?php else: ?>
            <span class="muted">Settled</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- MAINTENANCE REQUESTS -->
<div class="card mt">
  <div class="card-head">
    <h2>Maintenance Requests</h2>
    <span class="badge badge-gold"><?= count($repairs) ?></span>
  </div>
  <?php if (empty($repairs)): ?>
  <p class="card-empty">No maintenance requests on record yet.</p>
  <?php else: ?>
  <div class="repair-list">
    <?php foreach ($repairs as $r):
      $st  = $r['status'] ?? 'open';
      $dot = $repairDot[$st]   ?? 'dot-gray';
      $b   = $repairBadge[$st] ?? 'badge-gray';
      $lbl = $repairLabel[$st] ?? ucfirst($st);
    ?>
    <div class="repair-row">
      <div class="repair-dot <?= $dot ?>"></div>
      <div class="repair-info">
        <div class="repair-title"><?= htmlspecialchars($r['title']) ?></div>
        <div class="repair-meta muted">
          <?= htmlspecialchars($r['tenant_name']) ?>
          &bull; Unit <?= htmlspecialchars($r['unit_number'] ?? '—') ?>
          &bull; <?= date('M j', strtotime($r['submitted_at'])) ?>
        </div>
      </div>
      <span class="badge <?= $b ?>"><?= $lbl ?></span>
      <?php if (!in_array($st, ['completed','cancelled'], true)): ?>
      <form method="POST" action="index.php?page=admin" class="repair-form">
        <input type="hidden" name="action"     value="update_repair">
        <input type="hidden" name="request_id" value="<?= htmlspecialchars($r['id']) ?>">
        <select name="new_status" class="select-sm">
          <option value="open"             <?= $st==='open'             ? 'selected':'' ?>>Open</option>
          <option value="in_process"       <?= $st==='in_process'       ? 'selected':'' ?>>In Process</option>
          <option value="materials_needed" <?= $st==='materials_needed' ? 'selected':'' ?>>Materials Needed</option>
          <option value="completed">Completed</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Update</button>
      </form>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

</div>
