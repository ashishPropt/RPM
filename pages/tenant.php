<?php
$tenant = [
    'name'        => 'Diana Chen',
    'unit'        => 'Unit 3A',
    'property'    => '412 Nostrand Ave, Crown Heights',
    'initials'    => 'DC',
    'score'       => 68,
    'score_pct'   => 68,
    'lease_start' => 'Feb 1, 2024',
    'lease_end'   => 'Jan 31, 2025',
    'rent'        => '$2,100',
    'due_date'    => 'May 1, 2025',
    'status'      => 'Overdue',
];

$score_deg = round(($tenant['score_pct'] / 100) * 360);

$notifications = [
    ['type' => 'red',  'text' => 'Your April rent of $2,100 is overdue. Please contact your property manager.', 'time' => '2 days ago'],
    ['type' => 'gold', 'text' => 'Your repair request for the leaking sink has been picked up and is now In Process.', 'time' => '5 days ago'],
    ['type' => 'green','text' => 'Your hallway light fixture repair has been marked Completed.', 'time' => '2 weeks ago'],
    ['type' => 'gold', 'text' => 'Reminder: May rent of $2,100 is due in 3 days.', 'time' => '3 days ago'],
];

$repairs = [
    ['title' => 'Leaking sink faucet',   'status' => 'In Process', 'date' => 'Apr 22', 'dot' => 'dot-inprog'],
    ['title' => 'Hallway light fixture', 'status' => 'Completed',  'date' => 'Apr 14', 'dot' => 'dot-done'],
];
?>

<div class="page-body">
<div class="section">

    <div class="section-header" style="text-align:left; margin-bottom:1.5rem;">
        <span class="section-label">Tenant Portal</span>
        <h2 class="section-title" style="margin-bottom:0;">Resident Dashboard</h2>
    </div>

    <div class="tenant-hero">
        <div class="tenant-avatar"><?= $tenant['initials'] ?></div>
        <div class="tenant-info">
            <h2><?= $tenant['name'] ?></h2>
            <p><?= $tenant['unit'] ?> &middot; <?= $tenant['property'] ?></p>
            <p style="margin-top:0.4rem;"><span class="badge badge-red"><?= $tenant['status'] ?></span></p>
        </div>
        <div class="tenant-score">
            <div class="score-ring" style="background: conic-gradient(var(--gold) 0deg <?= $score_deg ?>deg, var(--border) <?= $score_deg ?>deg 360deg);">
                <span class="score-num"><?= $tenant['score'] ?></span>
            </div>
            <div class="score-label">Tenant Score</div>
        </div>
    </div>

    <div class="two-col" style="margin-bottom: 1.5rem;">
        <div class="card">
            <div class="card-head">
                <h3>Rent Status</h3>
                <span class="badge badge-red">Overdue</span>
            </div>
            <div class="card-body">
                <div class="rent-amount"><?= $tenant['rent'] ?></div>
                <div class="rent-due">Due <?= $tenant['due_date'] ?> &middot; April payment overdue</div>
                <div style="background:rgba(224,92,92,0.08); border:1px solid rgba(224,92,92,0.2); border-radius:var(--radius); padding:0.75rem 1rem; font-size:0.85rem; color:var(--red); line-height:1.5;">
                    Your April payment has not been received. Please contact your property manager to resolve this.
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3>Lease Summary</h3>
                <span class="badge badge-green">Active</span>
            </div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:1rem;">
                    <?php
                    $leaseDetails = [
                        ['Unit',         $tenant['unit']],
                        ['Property',     $tenant['property']],
                        ['Lease Start',  $tenant['lease_start']],
                        ['Lease End',    $tenant['lease_end']],
                        ['Monthly Rent', $tenant['rent']],
                    ];
                    foreach ($leaseDetails as $d): ?>
                    <div style="display:flex; justify-content:space-between; font-size:0.875rem; border-bottom:1px solid var(--border); padding-bottom:0.75rem;">
                        <span style="color:var(--text-muted);"><?= $d[0] ?></span>
                        <span style="color:var(--text); font-weight:500;"><?= $d[1] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="two-col">
        <div class="card">
            <div class="card-head">
                <h3>Maintenance Requests</h3>
                <a href="#" class="badge badge-gold" style="cursor:pointer;">+ Submit New</a>
            </div>
            <div class="card-body">
                <div style="display:flex; flex-direction:column; gap:1px; background:var(--border); border-radius:var(--radius); overflow:hidden; margin-bottom:1.5rem;">
                    <?php foreach ($repairs as $r): ?>
                    <div style="background:var(--ink-soft); padding:1rem 1.2rem; display:flex; gap:0.75rem; align-items:center;">
                        <div class="repair-dot <?= $r['dot'] ?>"></div>
                        <div style="flex:1;">
                            <div style="font-size:0.875rem; font-weight:500; color:var(--text);"><?= $r['title'] ?></div>
                            <div style="font-size:0.78rem; color:var(--text-muted);">Submitted <?= $r['date'] ?></div>
                        </div>
                        <span class="badge <?= $r['status'] === 'Completed' ? 'badge-green' : 'badge-gold' ?>"><?= $r['status'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p style="font-size:0.82rem; color:var(--text-muted); line-height:1.5;">To submit a new request, describe the issue and attach photos if available.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3>Notifications</h3>
                <span class="badge badge-red">3 unread</span>
            </div>
            <div class="card-body">
                <div class="notif-list">
                    <?php
                    $dotColors = ['red' => 'var(--red)', 'gold' => 'var(--gold)', 'green' => 'var(--green)'];
                    foreach ($notifications as $n): ?>
                    <div class="notif-item">
                        <div class="notif-dot" style="background:<?= $dotColors[$n['type']] ?>;"></div>
                        <div>
                            <div class="notif-text"><?= $n['text'] ?></div>
                            <span class="notif-time"><?= $n['time'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:2rem;">
        <div class="db-card">
            <h3>Your Tenant Score: <?= $tenant['score'] ?>/100</h3>
            <p style="font-size:0.875rem; color:var(--text-muted); margin-bottom:1.2rem; line-height:1.6;">Your score reflects your payment history and tenancy standing.</p>
            <div style="display:flex; flex-direction:column; gap:0.6rem;">
                <?php
                $factors = [
                    ['red',   '&minus;20', 'April rent payment is overdue'],
                    ['red',   '&minus;12', 'One previous late payment on record'],
                    ['green', '+45',       'On-time payments for 8 consecutive months'],
                    ['green', '+35',       'No outstanding repair disputes'],
                    ['gold',  '&minus;20', 'Ongoing overdue balance reduces base score'],
                ];
                foreach ($factors as $f): ?>
                <div style="display:flex; gap:0.75rem; align-items:center; font-size:0.85rem;">
                    <span class="badge badge-<?= $f[0] === 'red' ? 'red' : ($f[0] === 'green' ? 'green' : 'gold') ?>"><?= $f[1] ?></span>
                    <span style="color:var(--text-muted);"><?= $f[2] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>
</div>
