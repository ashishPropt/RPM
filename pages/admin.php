<?php
$tenants = [
    ['name' => 'Marcus Webb',    'unit' => '2B', 'property' => '412 Nostrand Ave', 'rent' => '$1,850', 'status' => 'Paid',    'score' => 92],
    ['name' => 'Diana Chen',     'unit' => '3A', 'property' => '412 Nostrand Ave', 'rent' => '$2,100', 'status' => 'Overdue', 'score' => 68],
    ['name' => 'Tariq Osei',     'unit' => '1C', 'property' => '88 Fulton St',     'rent' => '$1,650', 'status' => 'Due Soon','score' => 85],
    ['name' => 'Sofia Marin',    'unit' => '4D', 'property' => '88 Fulton St',     'rent' => '$1,975', 'status' => 'Paid',    'score' => 97],
    ['name' => 'James Holloway', 'unit' => '1A', 'property' => '201 Kingston Ave', 'rent' => '$2,250', 'status' => 'Paid',    'score' => 88],
];

$repairs = [
    ['title' => 'Leaking sink faucet',    'tenant' => 'Diana Chen',     'unit' => '3A', 'status' => 'In Process',       'dot' => 'dot-inprog',   'date' => 'Apr 22'],
    ['title' => 'Broken window latch',    'tenant' => 'Tariq Osei',     'unit' => '1C', 'status' => 'Open',             'dot' => 'dot-open',     'date' => 'Apr 26'],
    ['title' => 'HVAC filter replacement','tenant' => 'Marcus Webb',    'unit' => '2B', 'status' => 'Materials Needed', 'dot' => 'dot-materials','date' => 'Apr 18'],
    ['title' => 'Hallway light fixture',  'tenant' => 'Sofia Marin',    'unit' => '4D', 'status' => 'Completed',        'dot' => 'dot-done',     'date' => 'Apr 14'],
    ['title' => 'Bathroom tile re-grout', 'tenant' => 'James Holloway', 'unit' => '1A', 'status' => 'In Process',       'dot' => 'dot-inprog',   'date' => 'Apr 20'],
];

$statusBadge = [
    'Paid'     => 'badge-green',
    'Overdue'  => 'badge-red',
    'Due Soon' => 'badge-gold',
];
?>

<div class="page-body">
<div class="section">

    <div class="section-header" style="text-align:left; margin-bottom:2rem;">
        <span class="section-label">Admin Portal</span>
        <h2 class="section-title" style="margin-bottom:0.3rem;">Property Dashboard</h2>
        <p style="color:var(--text-muted); font-size:0.9rem;">Overview of your portfolio &mdash; tenants, rent, and maintenance.</p>
    </div>

    <div class="dash-grid">
        <div class="dash-card c-gold">
            <div class="dash-label">Total Properties</div>
            <div class="dash-value">3</div>
            <div class="dash-sub">2 active buildings</div>
        </div>
        <div class="dash-card c-green">
            <div class="dash-label">Rent Collected</div>
            <div class="dash-value">$6,075</div>
            <div class="dash-sub">3 of 5 this month</div>
        </div>
        <div class="dash-card c-red">
            <div class="dash-label">Overdue</div>
            <div class="dash-value">1</div>
            <div class="dash-sub">Diana Chen &mdash; 3A</div>
        </div>
        <div class="dash-card c-blue">
            <div class="dash-label">Open Repairs</div>
            <div class="dash-value">4</div>
            <div class="dash-sub">1 needs materials</div>
        </div>
    </div>

    <div class="data-table-wrap" style="margin-bottom: 2rem;">
        <div class="data-table-header">
            <h3>Tenants &amp; Rent Status</h3>
            <span class="badge badge-gold">5 tenants</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tenant</th><th>Unit</th><th>Property</th>
                    <th>Monthly Rent</th><th>Status</th><th>Score</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tenants as $t): ?>
                <tr>
                    <td class="name"><?= htmlspecialchars($t['name']) ?></td>
                    <td><?= $t['unit'] ?></td>
                    <td style="color:var(--text-muted); font-size:0.82rem;"><?= $t['property'] ?></td>
                    <td class="amount"><?= $t['rent'] ?></td>
                    <td><span class="badge <?= $statusBadge[$t['status']] ?>"><?= $t['status'] ?></span></td>
                    <td>
                        <span style="font-family:var(--ff-mono); font-size:0.82rem; color:<?= $t['score'] >= 90 ? 'var(--green)' : ($t['score'] >= 75 ? 'var(--gold)' : 'var(--red)') ?>">
                            <?= $t['score'] ?>
                        </span>
                    </td>
                    <td><a href="#" style="font-size:0.78rem; color:var(--gold); font-weight:500;">Record Payment</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="section-header" style="text-align:left; margin-bottom:1rem; margin-top:2rem;">
        <h3 style="font-family:var(--ff-display); font-size:1.3rem; font-weight:700; color:var(--text);">Maintenance Requests</h3>
    </div>
    <div class="repair-list">
        <?php foreach ($repairs as $r): ?>
        <div class="repair-item">
            <div class="repair-dot <?= $r['dot'] ?>"></div>
            <div class="repair-info">
                <div class="repair-title"><?= htmlspecialchars($r['title']) ?></div>
                <div class="repair-meta"><?= $r['tenant'] ?> &middot; Unit <?= $r['unit'] ?> &middot; Submitted <?= $r['date'] ?></div>
            </div>
            <span class="badge <?= $r['status'] === 'Completed' ? 'badge-green' : ($r['status'] === 'Open' ? 'badge-red' : ($r['status'] === 'Materials Needed' ? 'badge-blue' : 'badge-gold')) ?>">
                <?= $r['status'] ?>
            </span>
            <?php if ($r['status'] !== 'Completed'): ?>
            <a href="#" style="font-size:0.78rem; color:var(--gold); font-weight:500; flex-shrink:0;">Update &rarr;</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-top: 3rem;">
        <div class="section-header" style="text-align:left; margin-bottom:1.5rem;">
            <span class="section-label">Quick Reference</span>
            <h3 style="font-family:var(--ff-display); font-size:1.5rem; font-weight:700; color:var(--text);">Admin workflow guide</h3>
        </div>
        <div class="three-col">
            <?php
            $guides = [
                ['Adding a Tenant', [
                    'Create the tenant record in the app',
                    'Go to Supabase &rarr; Authentication &rarr; Add User',
                    'Create or invite via email',
                    'Return to admin panel &rarr; Link existing auth user',
                ]],
                ['Offboarding a Resident', [
                    'Mark resident as Former in the app',
                    'Remove from unit when appropriate',
                    'Disable or delete auth user in Supabase',
                    'History, payments, and docs are preserved',
                ]],
                ['Troubleshooting Login', [
                    'Check Auth &rarr; Users in Supabase',
                    'Confirm the user account exists',
                    'Verify user_profiles table is linked',
                    'Send password reset from Supabase if needed',
                ]],
            ];
            foreach ($guides as $g): ?>
            <div class="db-card">
                <h3><?= $g[0] ?></h3>
                <div style="display:flex; flex-direction:column; gap:0.5rem;">
                    <?php foreach ($g[1] as $i => $step): ?>
                    <div style="display:flex; gap:0.75rem; align-items:flex-start;">
                        <span style="font-family:var(--ff-mono); font-size:0.7rem; color:var(--gold); margin-top:0.1rem; flex-shrink:0;"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></span>
                        <span style="font-size:0.85rem; color:var(--text-muted); line-height:1.5;"><?= $step ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
</div>
