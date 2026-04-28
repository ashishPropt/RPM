<?php
/**
 * Tenant Portal
 * Uses AppAuth (not SupabaseAuth) and MySQLDB flat row format.
 */
AppAuth::requireLogin('tenant');

$userId = AppAuth::getUserId();

// ── Handle POST actions BEFORE any output ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'submit_repair') {
        // getTenantByUserId gives us tenant id and unit_id as flat columns
        $t = db()->getTenantByUserId($userId);
        if ($t && !empty($t['unit_id'])) {
            db()->submitRepairRequest(
                $t['id'],
                $t['unit_id'],
                trim($_POST['title']       ?? ''),
                trim($_POST['description'] ?? ''),
                $_POST['priority']         ?? 'normal'
            );
        }
        header('Location: index.php?page=tenant&msg=repair_submitted');
        exit;
    }
}

// ── Mark notification read ────────────────────────────────────
if (isset($_GET['mark_read'])) {
    db()->markNotificationRead($_GET['mark_read']);
    header('Location: index.php?page=tenant');
    exit;
}

// ── Load data ─────────────────────────────────────────────────
// All MySQLDB methods return flat rows — no nested array keys
$tenant        = db()->getTenantByUserId($userId);
$lease         = $tenant ? db()->getActiveLease($tenant['id'])    : null;
$charges       = $tenant ? db()->getRentCharges($tenant['id'])    : [];
$repairs       = $tenant ? db()->getMaintenanceRequests(['tenant_id' => $tenant['id']]) : [];
$notifications = db()->getNotifications($userId);
$documents     = $tenant ? db()->getDocuments($tenant['id'])      : [];

// Current unpaid charge
$currentCharge = null;
foreach ($charges as $ch) {
    if (in_array($ch['status'], ['pending','overdue','partial'])) {
        $currentCharge = $ch;
        break;
    }
}

$score      = (int)($tenant['score'] ?? 100);
$scoreDeg   = (int)round(($score / 100) * 360);
$scoreColor = $score >= 90 ? 'var(--green)' : ($score >= 70 ? 'var(--gold)' : 'var(--red)');
$initials   = strtoupper(
    substr($tenant['first_name'] ?? 'T', 0, 1) .
    substr($tenant['last_name']  ?? 'T', 0, 1)
);

$repairBadge = [
    'open'             => 'badge-red',
    'in_process'       => 'badge-gold',
    'materials_needed' => 'badge-blue',
    'completed'        => 'badge-green',
];
$repairDot = [
    'open'             => 'dot-open',
    'in_process'       => 'dot-inprog',
    'materials_needed' => 'dot-materials',
    'completed'        => 'dot-done',
];
$repairLabel = [
    'open'             => 'Open',
    'in_process'       => 'In Process',
    'materials_needed' => 'Materials Needed',
    'completed'        => 'Completed',
];
$notifColor = [
    'rent_reminder'   => 'var(--gold)',
    'rent_overdue'    => 'var(--red)',
    'repair_update'   => 'var(--blue)',
    'repair_complete' => 'var(--green)',
    'general'         => 'var(--text-muted)',
];
?>

<div class="page-body">
<div class="section">

<?php if (isset($_GET['msg'])): ?>
<div style="background:rgba(76,175,124,0.1);border:1px solid rgba(76,175,124,0.3);border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1.5rem;font-size:0.875rem;color:var(--green);">
    &#10003; <?= $_GET['msg'] === 'repair_submitted' ? 'Your repair request has been submitted.' : 'Done.' ?>
</div>
<?php endif; ?>

<?php if (!$tenant): ?>
<div style="padding:3rem;text-align:center;color:var(--text-muted);">
    <p style="font-size:1.1rem;margin-bottom:0.5rem;">Tenant record not found.</p>
    <p style="font-size:0.875rem;">Your account exists but hasn&rsquo;t been linked to a unit yet. Please contact your property manager.</p>
</div>
<?php else: ?>

<!-- PAGE HEADER -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
    <div>
        <span class="section-label">Tenant Portal</span>
        <h2 class="section-title" style="margin-bottom:0;">Resident Dashboard</h2>
    </div>
</div>

<!-- TENANT HERO -->
<div class="tenant-hero">
    <div class="tenant-avatar"><?= $initials ?></div>
    <div class="tenant-info">
        <h2><?= htmlspecialchars($tenant['first_name'] . ' ' . $tenant['last_name']) ?></h2>
        <p>
            <?= htmlspecialchars($tenant['unit_number']        ?? 'No unit assigned') ?>
            <?php if (!empty($tenant['property_address'])): ?>
            &middot; <?= htmlspecialchars($tenant['property_address']) ?>
            <?php endif; ?>
        </p>
        <?php if ($currentCharge): ?>
        <p style="margin-top:0.4rem;">
            <span class="badge badge-<?= $currentCharge['status'] === 'overdue' ? 'red' : 'gold' ?>">
                <?= ucfirst($currentCharge['status']) ?>
            </span>
        </p>
        <?php endif; ?>
    </div>
    <div class="tenant-score">
        <div class="score-ring"
             style="background:conic-gradient(<?= $scoreColor ?> 0deg <?= $scoreDeg ?>deg, var(--border) <?= $scoreDeg ?>deg 360deg);">
            <span class="score-num"><?= $score ?></span>
        </div>
        <div class="score-label">Tenant Score</div>
    </div>
</div>

<!-- RENT + LEASE -->
<div class="two-col" style="margin-bottom:1.5rem;">

    <!-- RENT STATUS -->
    <div class="card">
        <div class="card-head">
            <h3>Rent Status</h3>
            <?php if ($currentCharge): ?>
            <span class="badge badge-<?= $currentCharge['status'] === 'overdue' ? 'red' : 'gold' ?>">
                <?= ucfirst($currentCharge['status']) ?>
            </span>
            <?php else: ?>
            <span class="badge badge-green">All Clear</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($currentCharge): ?>
            <div class="rent-amount">$<?= number_format((float)$currentCharge['amount'], 0) ?></div>
            <div class="rent-due">
                Due <?= date('M j, Y', strtotime($currentCharge['due_date'])) ?>
                &middot; <?= htmlspecialchars($currentCharge['charge_month']) ?>
            </div>
            <?php if ($currentCharge['status'] === 'overdue'): ?>
            <div style="background:rgba(224,92,92,0.08);border:1px solid rgba(224,92,92,0.2);border-radius:var(--radius);padding:0.75rem 1rem;font-size:0.85rem;color:var(--red);line-height:1.5;">
                Your payment is overdue. Please contact your property manager to resolve this.
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div style="color:var(--green);font-size:1rem;font-weight:500;">&#10003; No outstanding charges.</div>
            <p style="font-size:0.85rem;color:var(--text-muted);margin-top:0.5rem;">You&rsquo;re all caught up on rent payments.</p>
            <?php endif; ?>

            <?php if (!empty($charges)): ?>
            <div style="margin-top:1.5rem;">
                <div style="font-size:0.72rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-muted);margin-bottom:0.75rem;">Payment History</div>
                <?php foreach (array_slice($charges, 0, 4) as $ch): ?>
                <div style="display:flex;justify-content:space-between;font-size:0.82rem;padding:0.5rem 0;border-bottom:1px solid var(--border);">
                    <span style="color:var(--text-soft);"><?= htmlspecialchars($ch['charge_month']) ?></span>
                    <span style="font-family:var(--ff-mono);">$<?= number_format((float)$ch['amount'], 0) ?></span>
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
                ['Unit',         $tenant['unit_number']        ?? '—'],
                ['Property',     $tenant['property_address']   ?? '—'],
                ['Lease Start',  date('M j, Y', strtotime($lease['start_date']))],
                ['Lease End',    date('M j, Y', strtotime($lease['end_date']))],
                ['Monthly Rent', '$' . number_format((float)$lease['monthly_rent'], 0)],
                ['Deposit',      '$' . number_format((float)($lease['security_deposit'] ?? 0), 0)],
            ];
            foreach ($rows as $row): ?>
            <div style="display:flex;justify-content:space-between;font-size:0.875rem;border-bottom:1px solid var(--border);padding-bottom:0.75rem;margin-bottom:0.75rem;">
                <span style="color:var(--text-muted);"><?= $row[0] ?></span>
                <span style="color:var(--text);font-weight:500;"><?= htmlspecialchars($row[1]) ?></span>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p style="color:var(--text-muted);font-size:0.875rem;">No active lease on file. Contact your property manager.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- REPAIRS + NOTIFICATIONS -->
<div class="two-col">

    <!-- MAINTENANCE -->
    <div class="card">
        <div class="card-head">
            <h3>Maintenance Requests</h3>
            <button onclick="document.getElementById('repairForm').classList.toggle('hidden')"
                    style="background:var(--gold-dim);border:1px solid rgba(201,168,76,0.3);color:var(--gold);font-size:0.75rem;font-weight:600;padding:0.25rem 0.7rem;border-radius:100px;cursor:pointer;letter-spacing:0.05em;">
                + Submit New
            </button>
        </div>
        <div class="card-body">

            <!-- Submit form -->
            <div id="repairForm" class="hidden"
                 style="background:var(--ink-soft);border:1px solid var(--border);border-radius:var(--radius);padding:1.25rem;margin-bottom:1.5rem;">
                <form method="POST" action="index.php?page=tenant"
                      style="display:flex;flex-direction:column;gap:0.9rem;">
                    <input type="hidden" name="action" value="submit_repair">

                    <div class="form-group">
                        <label>Issue Title</label>
                        <input type="text" name="title"
                               placeholder="e.g. Leaking kitchen faucet"
                               required
                               style="background:var(--ink-mid);border:1px solid var(--border-light);border-radius:var(--radius);padding:0.6rem 0.8rem;font-size:0.875rem;color:var(--text);outline:none;width:100%;font-family:var(--ff-body);">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3"
                                  placeholder="Describe the issue in detail..."
                                  style="background:var(--ink-mid);border:1px solid var(--border-light);border-radius:var(--radius);padding:0.6rem 0.8rem;font-size:0.875rem;color:var(--text);outline:none;width:100%;resize:vertical;font-family:var(--ff-body);"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority"
                                style="background:var(--ink-mid);border:1px solid var(--border-light);border-radius:var(--radius);padding:0.6rem 0.8rem;font-size:0.875rem;color:var(--text);outline:none;width:100%;font-family:var(--ff-body);">
                            <option value="low">Low</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">High</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>

                    <button type="submit"
                            style="background:var(--gold);color:var(--ink);border:none;border-radius:var(--radius);padding:0.65rem 1rem;font-size:0.875rem;font-weight:600;cursor:pointer;font-family:var(--ff-body);">
                        Submit Request
                    </button>
                </form>
            </div>

            <!-- Repair list -->
            <?php if (empty($repairs)): ?>
            <p style="font-size:0.875rem;color:var(--text-muted);">No maintenance requests yet.</p>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:1px;background:var(--border);border-radius:var(--radius);overflow:hidden;">
                <?php foreach ($repairs as $r):
                    $rs  = $r['status'] ?? 'open';
                    $dot = $repairDot[$rs]   ?? 'dot-open';
                    $lbl = $repairLabel[$rs] ?? ucfirst($rs);
                    $bg  = $repairBadge[$rs] ?? 'badge-gray';
                ?>
                <div style="background:var(--ink-soft);padding:1rem 1.2rem;display:flex;gap:0.75rem;align-items:center;">
                    <div class="repair-dot <?= $dot ?>"></div>
                    <div style="flex:1;">
                        <div style="font-size:0.875rem;font-weight:500;color:var(--text);"><?= htmlspecialchars($r['title']) ?></div>
                        <div style="font-size:0.78rem;color:var(--text-muted);">Submitted <?= date('M j', strtotime($r['submitted_at'])) ?></div>
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
            <p style="font-size:0.875rem;color:var(--text-muted);">No notifications yet.</p>
            <?php else: ?>
            <div class="notif-list">
                <?php foreach ($notifications as $n):
                    $color   = $notifColor[$n['type']] ?? 'var(--text-muted)';
                    $opacity = (bool)$n['is_read'] ? '0.5' : '1';
                ?>
                <div class="notif-item" style="opacity:<?= $opacity ?>;">
                    <div class="notif-dot" style="background:<?= $color ?>;"></div>
                    <div style="flex:1;">
                        <div class="notif-text"
                             style="font-weight:<?= (bool)$n['is_read'] ? '400' : '500' ?>">
                            <?= htmlspecialchars($n['body']) ?>
                        </div>
                        <span class="notif-time"><?= date('M j, g:i A', strtotime($n['created_at'])) ?></span>
                    </div>
                    <?php if (!(bool)$n['is_read']): ?>
                    <a href="index.php?page=tenant&mark_read=<?= urlencode($n['id']) ?>"
                       style="font-size:0.72rem;color:var(--text-muted);flex-shrink:0;">Mark read</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- DOCUMENTS -->
<?php if (!empty($documents)): ?>
<div style="margin-top:1.5rem;">
    <div class="card">
        <div class="card-head">
            <h3>Documents</h3>
            <span class="badge badge-gold"><?= count($documents) ?></span>
        </div>
        <div class="card-body">
            <?php foreach ($documents as $d): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem 0;border-bottom:1px solid var(--border);">
                <div>
                    <div style="font-size:0.875rem;font-weight:500;color:var(--text);"><?= htmlspecialchars($d['title']) ?></div>
                    <div style="font-size:0.78rem;color:var(--text-muted);"><?= date('M j, Y', strtotime($d['created_at'])) ?></div>
                </div>
                <a href="<?= htmlspecialchars($d['storage_path']) ?>"
                   target="_blank"
                   style="font-size:0.78rem;color:var(--gold);font-weight:500;">View &rarr;</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- SCORE BAR -->
<div style="margin-top:2rem;">
    <div class="db-card">
        <h3>Your Tenant Score: <?= $score ?>/100</h3>
        <p style="font-size:0.875rem;color:var(--text-muted);margin-bottom:1rem;line-height:1.6;">
            <?= htmlspecialchars($tenant['score_notes'] ?? 'Your score reflects your payment history and tenancy standing.') ?>
        </p>
        <div style="display:flex;gap:1rem;align-items:center;">
            <div style="flex:1;background:var(--border);border-radius:100px;height:6px;">
                <div style="width:<?= $score ?>%;background:<?= $scoreColor ?>;height:100%;border-radius:100px;"></div>
            </div>
            <span style="font-family:var(--ff-mono);font-size:0.9rem;color:<?= $scoreColor ?>;font-weight:500;"><?= $score ?>/100</span>
        </div>
    </div>
</div>

<?php endif; // end $tenant check ?>

</div>
</div>

<style>.hidden { display:none !important; }</style>
