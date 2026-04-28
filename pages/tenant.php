<?php
/**
 * Tenant Portal.
 * All POST actions use PRG (Post/Redirect/Get).
 */
AppAuth::requireRole('tenant');
$uid = AppAuth::uid();

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'submit_repair') {
        $t = db()->row(
            'SELECT id, unit_id FROM tenants WHERE user_id=? LIMIT 1',
            [$uid]
        );
        if ($t && !empty($t['unit_id'])) {
            $title = trim($_POST['title']       ?? '');
            $desc  = trim($_POST['description'] ?? '');
            $prio  = $_POST['priority']         ?? 'normal';
            if ($title !== '') {
                db()->run(
                    'INSERT INTO maintenance_requests
                        (id, tenant_id, unit_id, title, description, priority, status)
                     VALUES (?, ?, ?, ?, ?, ?, "open")',
                    [db()->uuid(), $t['id'], $t['unit_id'], $title, $desc, $prio]
                );
                flash('success', 'Repair request submitted successfully.');
            } else {
                flash('error', 'Please enter a title for your request.');
            }
        } else {
            flash('error', 'You are not assigned to a unit yet. Contact your property manager.');
        }
        redirect('tenant');
    }
}

// ── Mark notification read ────────────────────────────────────
if (isset($_GET['mark_read'])) {
    db()->run(
        'UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?',
        [$_GET['mark_read'], $uid]
    );
    redirect('tenant');
}

// ── Load data ─────────────────────────────────────────────────
$tenant = db()->row(
    'SELECT t.id, t.first_name, t.last_name, t.email, t.phone,
            t.status, t.score, t.score_notes, t.unit_id,
            u.unit_number, u.monthly_rent,
            p.name AS property_name, p.address AS property_address
     FROM   tenants t
     LEFT JOIN units      u ON u.id = t.unit_id
     LEFT JOIN properties p ON p.id = u.property_id
     WHERE  t.user_id = ? LIMIT 1',
    [$uid]
);

$lease = $tenant ? db()->row(
    'SELECT * FROM leases WHERE tenant_id=? AND status="active" ORDER BY created_at DESC LIMIT 1',
    [$tenant['id']]
) : null;

$charges = $tenant ? db()->rows(
    'SELECT * FROM rent_charges WHERE tenant_id=? ORDER BY due_date DESC',
    [$tenant['id']]
) : [];

$repairs = $tenant ? db()->rows(
    'SELECT * FROM maintenance_requests WHERE tenant_id=? ORDER BY created_at DESC',
    [$tenant['id']]
) : [];

$notifications = db()->rows(
    'SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20',
    [$uid]
);

$documents = $tenant ? db()->rows(
    'SELECT * FROM documents WHERE tenant_id=? AND is_visible_to_tenant=1 ORDER BY created_at DESC',
    [$tenant['id']]
) : [];

// Current unpaid charge
$current = null;
foreach ($charges as $ch) {
    if (in_array($ch['status'], ['pending','overdue','partial'], true)) {
        $current = $ch;
        break;
    }
}

$score  = (int)($tenant['score'] ?? 100);
$sdeg   = (int)round($score / 100 * 360);
$scolor = $score >= 90 ? 'var(--green)' : ($score >= 70 ? 'var(--gold)' : 'var(--red)');
$initials = strtoupper(
    substr($tenant['first_name'] ?? 'T', 0, 1) .
    substr($tenant['last_name']  ?? 'T', 0, 1)
);

$rbadge = ['open'=>'badge-red','in_process'=>'badge-gold','materials_needed'=>'badge-blue','completed'=>'badge-green'];
$rdot   = ['open'=>'dot-red','in_process'=>'dot-gold','materials_needed'=>'dot-blue','completed'=>'dot-green'];
$rlabel = ['open'=>'Open','in_process'=>'In Process','materials_needed'=>'Materials Needed','completed'=>'Completed'];
$ncolor = [
    'rent_reminder'   => 'var(--gold)',
    'rent_overdue'    => 'var(--red)',
    'repair_update'   => 'var(--blue)',
    'repair_complete' => 'var(--green)',
    'general'         => 'var(--muted)',
];
?>

<div class="container">

<?= flash_html() ?>

<?php if (!$tenant): ?>
<div class="empty-state">
  <div class="empty-icon">🏠</div>
  <h2>No tenant record found</h2>
  <p>Your account exists but hasn&rsquo;t been linked to a unit yet.<br>Please contact your property manager.</p>
</div>
<?php else: ?>

<!-- TENANT HERO -->
<div class="tenant-hero">
  <div class="t-avatar"><?= $initials ?></div>
  <div class="t-info">
    <h2><?= htmlspecialchars($tenant['first_name'] . ' ' . $tenant['last_name']) ?></h2>
    <p class="muted">
      <?= htmlspecialchars($tenant['unit_number'] ?? 'No unit assigned') ?>
      <?php if (!empty($tenant['property_name'])): ?>
        &bull; <?= htmlspecialchars($tenant['property_name']) ?>
      <?php endif; ?>
    </p>
    <?php if ($current): ?>
    <span class="badge badge-<?= $current['status']==='overdue' ? 'red' : 'gold' ?>" style="margin-top:.4rem;display:inline-block">
      <?= ucfirst($current['status']) ?>
    </span>
    <?php endif; ?>
  </div>
  <div class="t-score">
    <div class="score-ring" style="background:conic-gradient(<?= $scolor ?> 0deg <?= $sdeg ?>deg, var(--border) <?= $sdeg ?>deg 360deg)">
      <span><?= $score ?></span>
    </div>
    <div class="score-label">Tenant Score</div>
  </div>
</div>

<!-- RENT + LEASE -->
<div class="two-col mt">

  <!-- RENT STATUS -->
  <div class="card">
    <div class="card-head">
      <h3>Rent Status</h3>
      <?php if ($current): ?>
      <span class="badge badge-<?= $current['status']==='overdue' ? 'red' : 'gold' ?>"><?= ucfirst($current['status']) ?></span>
      <?php else: ?>
      <span class="badge badge-green">All Clear</span>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <?php if ($current): ?>
      <div class="rent-amount">$<?= number_format((float)$current['amount'], 0) ?></div>
      <div class="rent-due muted">Due <?= date('M j, Y', strtotime($current['due_date'])) ?></div>
      <?php if ($current['status'] === 'overdue'): ?>
      <div class="alert alert-error" style="margin-top:.75rem">Your payment is overdue. Please contact your property manager.</div>
      <?php endif; ?>
      <?php else: ?>
      <p style="color:var(--green);font-weight:500">✓ No outstanding charges.</p>
      <?php endif; ?>
      <?php if (!empty($charges)): ?>
      <div style="margin-top:1.25rem">
        <div class="label-sm muted" style="margin-bottom:.5rem">PAYMENT HISTORY</div>
        <?php foreach (array_slice($charges, 0, 4) as $ch): ?>
        <div style="display:flex;justify-content:space-between;font-size:.85rem;padding:.4rem 0;border-bottom:1px solid var(--border)">
          <span class="muted"><?= htmlspecialchars($ch['charge_month']) ?></span>
          <span class="mono">$<?= number_format((float)$ch['amount'], 0) ?></span>
          <span class="badge badge-<?= $ch['status']==='paid' ? 'green' : ($ch['status']==='overdue' ? 'red' : 'gold') ?>">
            <?= ucfirst($ch['status']) ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- LEASE SUMMARY -->
  <div class="card">
    <div class="card-head">
      <h3>Lease Summary</h3>
      <span class="badge badge-<?= $lease ? 'green' : 'gray' ?>"><?= $lease ? 'Active' : 'No Lease' ?></span>
    </div>
    <div class="card-body">
      <?php if ($lease): ?>
      <?php
      $rows = [
        ['Unit',         $tenant['unit_number']       ?? '—'],
        ['Property',     $tenant['property_address']  ?? '—'],
        ['Start',        date('M j, Y', strtotime($lease['start_date']))],
        ['End',          date('M j, Y', strtotime($lease['end_date']))],
        ['Monthly Rent', '$' . number_format((float)$lease['monthly_rent'], 0)],
        ['Deposit',      '$' . number_format((float)($lease['security_deposit'] ?? 0), 0)],
      ];
      foreach ($rows as [$label, $val]): ?>
      <div style="display:flex;justify-content:space-between;font-size:.875rem;padding:.6rem 0;border-bottom:1px solid var(--border)">
        <span class="muted"><?= $label ?></span>
        <span style="font-weight:500"><?= htmlspecialchars($val) ?></span>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <p class="muted">No active lease on file. Contact your property manager.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- REPAIRS + NOTIFICATIONS -->
<div class="two-col mt">

  <!-- MAINTENANCE -->
  <div class="card">
    <div class="card-head">
      <h3>Maintenance</h3>
      <button class="btn btn-sm btn-gold" onclick="toggleRepairForm()">+ New Request</button>
    </div>
    <div class="card-body">

      <!-- Submit form (hidden by default) -->
      <div id="repairForm" style="display:none;background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:1rem;margin-bottom:1rem">
        <form method="POST" action="index.php?page=tenant">
          <input type="hidden" name="action" value="submit_repair">
          <div class="form-group">
            <label>Issue Title</label>
            <input type="text" name="title" placeholder="e.g. Leaking kitchen faucet" required style="width:100%">
          </div>
          <div class="form-group" style="margin-top:.75rem">
            <label>Description</label>
            <textarea name="description" rows="3" placeholder="Describe the issue..." style="width:100%;resize:vertical"></textarea>
          </div>
          <div class="form-group" style="margin-top:.75rem">
            <label>Priority</label>
            <select name="priority" style="width:100%">
              <option value="low">Low</option>
              <option value="normal" selected>Normal</option>
              <option value="high">High</option>
              <option value="emergency">Emergency</option>
            </select>
          </div>
          <div style="display:flex;gap:.6rem;margin-top:1rem">
            <button type="submit" class="btn btn-primary">Submit Request</button>
            <button type="button" class="btn btn-ghost" onclick="toggleRepairForm()">Cancel</button>
          </div>
        </form>
      </div>

      <?php if (empty($repairs)): ?>
      <p class="muted">No maintenance requests yet.</p>
      <?php else: ?>
      <div class="repair-list">
        <?php foreach ($repairs as $r):
          $rs  = $r['status'] ?? 'open';
          $dot = $rdot[$rs]   ?? 'dot-gray';
          $bg  = $rbadge[$rs] ?? 'badge-gray';
          $lbl = $rlabel[$rs] ?? ucfirst($rs);
        ?>
        <div class="repair-row">
          <div class="repair-dot <?= $dot ?>"></div>
          <div class="repair-info">
            <div class="repair-title"><?= htmlspecialchars($r['title']) ?></div>
            <div class="repair-meta muted">Submitted <?= date('M j', strtotime($r['submitted_at'])) ?></div>
          </div>
          <span class="badge <?= $bg ?>"><?= $lbl ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- NOTIFICATIONS -->
  <div class="card">
    <div class="card-head">
      <h3>Notifications</h3>
      <?php $unread = count(array_filter($notifications, fn($n) => !(bool)$n['is_read'])); ?>
      <span class="badge badge-<?= $unread > 0 ? 'red' : 'green' ?>">
        <?= $unread > 0 ? $unread . ' unread' : 'All read' ?>
      </span>
    </div>
    <div class="card-body">
      <?php if (empty($notifications)): ?>
      <p class="muted">No notifications yet.</p>
      <?php else: ?>
      <?php foreach ($notifications as $n):
        $nc = $ncolor[$n['type']] ?? 'var(--muted)';
        $op = (bool)$n['is_read'] ? '.5' : '1';
      ?>
      <div style="display:flex;gap:.75rem;align-items:flex-start;padding:.6rem 0;border-bottom:1px solid var(--border);opacity:<?= $op ?>">
        <div style="width:8px;height:8px;border-radius:50%;background:<?= $nc ?>;margin-top:.4rem;flex-shrink:0"></div>
        <div style="flex:1">
          <div style="font-size:.875rem"><?= htmlspecialchars($n['body']) ?></div>
          <div class="muted" style="font-size:.75rem"><?= date('M j, g:i A', strtotime($n['created_at'])) ?></div>
        </div>
        <?php if (!(bool)$n['is_read']): ?>
        <a href="index.php?page=tenant&mark_read=<?= urlencode($n['id']) ?>" class="btn-link" style="font-size:.75rem">Mark read</a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- DOCUMENTS -->
<?php if (!empty($documents)): ?>
<div class="card mt">
  <div class="card-head">
    <h3>Documents</h3>
    <span class="badge badge-gold"><?= count($documents) ?></span>
  </div>
  <div class="card-body">
    <?php foreach ($documents as $d): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem 0;border-bottom:1px solid var(--border)">
      <div>
        <div style="font-size:.875rem;font-weight:500"><?= htmlspecialchars($d['title']) ?></div>
        <div class="muted" style="font-size:.75rem"><?= date('M j, Y', strtotime($d['created_at'])) ?></div>
      </div>
      <a href="<?= htmlspecialchars($d['storage_path']) ?>" target="_blank" class="btn btn-sm btn-ghost">View</a>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- SCORE BAR -->
<div class="card mt">
  <div class="card-head"><h3>Tenant Score</h3></div>
  <div class="card-body">
    <div style="display:flex;gap:1rem;align-items:center">
      <div style="flex:1;background:var(--border);border-radius:100px;height:8px">
        <div style="width:<?= $score ?>%;background:<?= $scolor ?>;height:100%;border-radius:100px"></div>
      </div>
      <span class="mono" style="color:<?= $scolor ?>;font-weight:600"><?= $score ?>/100</span>
    </div>
    <?php if (!empty($tenant['score_notes'])): ?>
    <p class="muted" style="margin-top:.75rem;font-size:.875rem"><?= htmlspecialchars($tenant['score_notes']) ?></p>
    <?php endif; ?>
  </div>
</div>

<?php endif; // end $tenant check ?>
</div>
