<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

SupabaseAuth::requireLogin('tenant');

$userId = SupabaseAuth::getUserId();

// Load tenant data
$tenant        = db()->getTenantByUserId($userId);
$lease         = $tenant ? db()->getActiveLease($tenant['id']) : null;
$charges       = $tenant ? db()->getRentCharges($tenant['id']) : [];
$repairs       = $tenant ? db()->getMaintenanceRequests(['tenant_id' => 'eq.' . $tenant['id']]) : [];
$notifications = db()->getNotifications($userId);
$documents     = $tenant ? db()->getDocuments($tenant['id']) : [];

// Handle new repair request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_repair') {
    if ($tenant) {
        db()->insert('maintenance_requests', [
            'tenant_id'   => $tenant['id'],
            'unit_id'     => $tenant['unit_id'],
            'title'       => htmlspecialchars(trim($_POST['title'])),
            'description' => htmlspecialchars(trim($_POST['description'] ?? '')),
            'priority'    => $_POST['priority'] ?? 'normal',
            'status'      => 'open',
        ]);
    }
    header('Location: index.php?page=tenant&msg=repair_submitted');
    exit;
}

// Mark notification read
if (isset($_GET['mark_read'])) {
    db()->markNotificationRead($_GET['mark_read']);
    header('Location: index.php?page=tenant');
    exit;
}

// Current rent charge (most recent pending/overdue)
$currentCharge = null;
foreach ($charges as $ch) {
    if (in_array($ch['status'], ['pending', 'overdue', 'partial'])) {
        $currentCharge = $ch;
        break;
    }
}

$score      = $tenant['score'] ?? 100;
$scoreDeg   = round(($score / 100) * 360);
$scoreColor = $score >= 90 ? 'var(--green)' : ($score >= 70 ? 'var(--gold)' : 'var(--red)');

$initials   = strtoupper(substr($tenant['first_name'] ?? 'T', 0, 1) . substr($tenant['last_name'] ?? 'T', 0, 1));

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

$notifDotColor = [
    'rent_reminder'  => 'var(--gold)',
    'rent_overdue'   => 'var(--red)',
    'repair_update'  => 'var(--blue)',
    'repair_complete'=> 'var(--green)',
    'general'        => 'var(--text-muted)',
];
?>

<div class="page-body">
<div class="section">

    <?php if (isset($_GET['msg'])): ?>
    <div style="background:rgba(76,175,124,0.1); border:1px solid rgba(76,175,124,0.3); border-radius:var(--radius); padding:0.85rem 1rem; margin-bottom:1.5rem; font-size:0.875rem; color:var(--green);">
        &#10003; <?= $_GET['msg'] === 'repair_submitted' ? 'Your repair request has been submitted.' : 'Done.' ?>
    </div>
    <?php endif; ?>

    <?php if (!$tenant): ?>
    <div style="padding:3rem; text-align:center; color:var(--text-muted);">Tenant record not found. Please contact your property manager.</div>
    <?php else: ?>

    <div class="section-header" style="text-align:left; margin-bottom:1.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <span class="section-label">Tenant Portal</span>
                <h2 class="section-title" style="margin-bottom:0;">Resident Dashboard</h2>
            </div>
            <a href="index.php?page=login&logout=1" style="font-size:0.82rem; color:var(--text-muted); border:1px solid var(--border); padding:0.4rem 0.9rem; border-radius:var(--radius);">Sign Out</a>
        </div>
    </div>

    <!-- TENANT HERO CARD -->
    <div class="tenant-hero">
        <div class="tenant-avatar"><?= $initials ?></div>
        <div class="tenant-info">
            <h2><?= htmlspecialchars($tenant['first_name'] . ' ' . $tenant['last_name']) ?></h2>
            <p>
                <?= htmlspecialchars($tenant['units']['unit_number'] ?? '') ?>
                &middot;
                <?= htmlspecialchars($tenant['units']['properties']['address'] ?? '') ?>
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
            <div class="score-ring" style="background: conic-gradient(<?= $scoreColor ?> 0deg <?= $scoreDeg ?>deg, var(--border) <?= $scoreDeg ?>deg 360deg);">
                <span class="score-num"><?= $score ?></span>
            </div>
            <div class="score-label">Tenant Score</div>
        </div>
    </div>

    <!-- RENT + LEASE -->
    <div class="two-col" style="margin-bottom: 1.5rem;">

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
                <div class="rent-amount">$<?= number_format($currentCharge['amount'], 0) ?></div>
                <div class="rent-due">Due <?= date('M j, Y', strtotime($currentCharge['due_date'])) ?> &middot; <?= $currentCharge['charge_month'] ?></div>
                <?php if ($currentCharge['status'] === 'overdue'): ?>
                <div style="background:rgba(224,92,92,0.08); border:1px solid rgba(224,92,92,0.2); border-radius:var(--radius); padding:0.75rem 1rem; font-size:0.85rem; color:var(--red); line-height:1.5;">
                    Your payment is overdue. Please contact your property manager to resolve this.
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div style="color:var(--green); font-size:1rem; font-weight:500;">&#10003; No outstanding charges.</div>
                <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.5rem;">You&rsquo;re all caught up on rent payments.</p>
                <?php endif; ?>

                <?php if (!empty($charges)): ?>
                <div style="margin-top:1.5rem;">
                    <div style="font-size:0.72rem; font-weight:600; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.75rem;">Payment History</div>
                    <?php foreach (array_slice($charges, 0, 4) as $ch): ?>
                    <div style="display:flex; justify-content:space-between; font-size:0.82rem; padding:0.5rem 0; border-bottom:1px solid var(--border);">
                        <span style="color:var(--text-soft);"><?= $ch['charge_month'] ?></span>
                        <span style="font-family:var(--ff-mono);">$<?= number_format($ch['amount'], 0) ?></span>
                        <span class="badge badge-<?= $ch['status'] === 'paid' ? 'green' : ($ch['status'] === 'overdue' ? 'red' : 'gold') ?>"><?= ucfirst($ch['status']) ?></span>
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
                <div style="display:flex; flex-direction:column; gap:1rem;">
                    <?php
                    $leaseItems = [
                        ['Unit',         $tenant['units']['unit_number'] ?? '—'],
                        ['Property',     $tenant['units']['properties']['address'] ?? '—'],
                        ['Lease Start',  date('M j, Y', strtotime($lease['start_date']))],
                        ['Lease End',    date('M j, Y', strtotime($lease['end_date']))],
                        ['Monthly Rent', '$' . number_format($lease['monthly_rent'], 0)],
                        ['Deposit',      '$' . number_format($lease['security_deposit'] ?? 0, 0)],
                    ];
                    foreach ($leaseItems as $item): ?>
                    <div style="display:flex; justify-content:space-between; font-size:0.875rem; border-bottom:1px solid var(--border); padding-bottom:0.75rem;">
                        <span style="color:var(--text-muted);"><?= $item[0] ?></span>
                        <span style="color:var(--text); font-weight:500;"><?= htmlspecialchars($item[1]) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color:var(--text-muted); font-size:0.875rem;">No active lease found. Contact your property manager.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- REPAIRS & NOTIFICATIONS -->
    <div class="two-col">

        <!-- MAINTENANCE -->
        <div class="card">
            <div class="card-head">
                <h3>Maintenance Requests</h3>
                <button onclick="document.getElementById('repairForm').classList.toggle('hidden')" class="badge badge-gold" style="cursor:pointer; border:none; background:var(--gold-dim);">+ Submit New</button>
            </div>
            <div class="card-body">

                <!-- Submit repair form -->
                <div id="repairForm" class="hidden" style="background:var(--ink-soft); border:1px solid var(--border); border-radius:var(--radius); padding:1.25rem; margin-bottom:1.5rem;">
                    <form method="POST" action="index.php?page=tenant" style="display:flex; flex-direction:column; gap:0.9rem;">
                        <input type="hidden" name="action" value="submit_repair">
                        <div class="form-group">
                            <label>Issue Title</label>
                            <input type="text" name="title" placeholder="e.g. Leaking kitchen faucet" required style="background:var(--ink-mid); border:1px solid var(--border-light); border-radius:var(--radius); padding:0.6rem 0.8rem; font-size:0.875rem; color:var(--text); outline:none; width:100%;">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3" placeholder="Describe the issue in detail..." style="background:var(--ink-mid); border:1px solid var(--border-light); border-radius:var(--radius); padding:0.6rem 0.8rem; font-size:0.875rem; color:var(--text); outline:none; width:100%; resize:vertical;"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Priority</label>
                            <select name="priority" style="background:var(--ink-mid); border:1px solid var(--border-light); border-radius:var(--radius); padding:0.6rem 0.8rem; font-size:0.875rem; color:var(--text); outline:none; width:100%;">
                                <option value="low">Low</option>
                                <option value="normal" selected>Normal</option>
                                <option value="high">High</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>
                        <button type="submit" style="background:var(--gold); color:var(--ink); border:none; border-radius:var(--radius); padding:0.65rem 1rem; font-size:0.875rem; font-weight:600; cursor:pointer;">Submit Request</button>
                    </form>
                </div>

                <?php if (empty($repairs)): ?>
                <p style="font-size:0.875rem; color:var(--text-muted);">No maintenance requests yet.</p>
                <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:1px; background:var(--border); border-radius:var(--radius); overflow:hidden;">
                    <?php foreach ($repairs as $r):
                        $rs  = $r['status'] ?? 'open';
                        $dot = $repairDot[$rs]   ?? 'dot-open';
                        $lbl = $repairLabel[$rs]  ?? ucfirst($rs);
                        $bg  = $repairBadge[$rs]  ?? 'badge-gray';
                    ?>
                    <div style="background:var(--ink-soft); padding:1rem 1.2rem; display:flex; gap:0.75rem; align-items:center;">
                        <div class="repair-dot <?= $dot ?>"></div>
                        <div style="flex:1;">
                            <div style="font-size:0.875rem; font-weight:500; color:var(--text);"><?= htmlspecialchars($r['title']) ?></div>
                            <div style="font-size:0.78rem; color:var(--text-muted);">Submitted <?= date('M j', strtotime($r['submitted_at'])) ?></div>
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
                <?php $unread = count(array_filter($notifications, fn($n) => !$n['is_read'])); ?>
                <?php if ($unread > 0): ?>
                <span class="badge badge-red"><?= $unread ?> unread</span>
                <?php else: ?>
                <span class="badge badge-green">All read</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                <p style="font-size:0.875rem; color:var(--text-muted);">No notifications yet.</p>
                <?php else: ?>
                <div class="notif-list">
                    <?php foreach ($notifications as $n):
                        $dotColor = $notifDotColor[$n['type']] ?? 'var(--text-muted)';
                        $opacity  = $n['is_read'] ? '0.5' : '1';
                    ?>
                    <div class="notif-item" style="opacity:<?= $opacity ?>;">
                        <div class="notif-dot" style="background:<?= $dotColor ?>;"></div>
                        <div style="flex:1;">
                            <div class="notif-text" style="font-weight:<?= $n['is_read'] ? '400' : '500' ?>"><?= htmlspecialchars($n['body']) ?></div>
                            <span class="notif-time"><?= date('M j, g:i A', strtotime($n['created_at'])) ?></span>
                        </div>
                        <?php if (!$n['is_read']): ?>
                        <a href="index.php?page=tenant&mark_read=<?= $n['id'] ?>" style="font-size:0.72rem; color:var(--text-muted); flex-shrink:0;">Mark read</a>
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
            <div class="card-head"><h3>Documents</h3><span class="badge badge-gold"><?= count($documents) ?></span></div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <?php foreach ($documents as $d): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:0.75rem 0; border-bottom:1px solid var(--border);">
                        <div>
                            <div style="font-size:0.875rem; font-weight:500; color:var(--text);"><?= htmlspecialchars($d['title']) ?></div>
                            <div style="font-size:0.78rem; color:var(--text-muted);"><?= date('M j, Y', strtotime($d['created_at'])) ?></div>
                        </div>
                        <a href="<?= htmlspecialchars($d['storage_path']) ?>" target="_blank" style="font-size:0.78rem; color:var(--gold); font-weight:500;">View &rarr;</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- TENANT SCORE BREAKDOWN -->
    <div style="margin-top:2rem;">
        <div class="db-card">
            <h3>Your Tenant Score: <?= $score ?>/100</h3>
            <p style="font-size:0.875rem; color:var(--text-muted); margin-bottom:1rem; line-height:1.6;">
                <?= htmlspecialchars($tenant['score_notes'] ?? 'Your score reflects your payment history and tenancy standing.') ?>
            </p>
            <div style="display:flex; gap:1rem; align-items:center;">
                <div style="flex:1; background:var(--border); border-radius:100px; height:6px;">
                    <div style="width:<?= $score ?>%; background:<?= $scoreColor ?>; height:100%; border-radius:100px; transition:width 0.6s ease;"></div>
                </div>
                <span style="font-family:var(--ff-mono); font-size:0.9rem; color:<?= $scoreColor ?>; font-weight:500;"><?= $score ?>/100</span>
            </div>
        </div>
    </div>

    <?php endif; // end tenant check ?>

</div>
</div>

<style>
.hidden { display: none !important; }
</style>
