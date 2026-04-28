<div class="page-body">
<div class="section">

    <div class="section-header">
        <span class="section-label">Platform Overview</span>
        <h2 class="section-title">Built for real property operations</h2>
        <p class="section-desc">Every feature in PropTXChange is designed around the actual daily needs of a small property management workflow.</p>
    </div>

    <?php
    $modules = [
        ['title' => 'Properties &amp; Units', 'tag' => 'Core',
         'desc' => 'Create and manage properties with flexible location inputs including state, neighborhood/borough, and city. Add units directly under each property. No pre-setup of neighborhoods required.',
         'items' => ['Multiple properties supported', 'Units organized under properties', 'State + neighborhood + city inputs', 'Admin-created directly from the app']],
        ['title' => 'Rent Tracking', 'tag' => 'Finance',
         'desc' => 'Monthly rent cycle management. Track upcoming rent before due dates, catch overdue payments, and record manual payments. Full payment history is preserved per tenant.',
         'items' => ['Manual payment recording by admin', 'Upcoming rent appears pre-due-date', 'Overdue flagging when unpaid', 'Paid records remain in history']],
        ['title' => 'Maintenance Workflow', 'tag' => 'Operations',
         'desc' => 'Tenants submit repair requests with optional photo uploads. Admins manage each request through a structured four-stage workflow. Only Completed is considered truly closed.',
         'items' => ['Open &rarr; In Process &rarr; Materials Needed &rarr; Completed', 'Photo upload by tenant', 'Admin status updates', 'Notifications on repair changes']],
        ['title' => 'Notifications', 'tag' => 'Communication',
         'desc' => 'In-app notification system covering rent reminders, overdue alerts, repair updates, and completion notices. Lighter reminders before due dates, stronger alerts once overdue.',
         'items' => ['Pre-due-date rent reminders', 'Overdue rent strong alerts', 'Repair status change notifications', 'Repair completion confirmation']],
        ['title' => 'Documents', 'tag' => 'Storage',
         'desc' => 'Admin-uploaded documents stored in Supabase Storage. Tenants can view documents that have been shared. Backed by two dedicated storage buckets.',
         'items' => ['Lease document storage', 'Maintenance image uploads', 'Tenant document access via portal', 'Supabase Storage backed']],
        ['title' => 'Auth Linking', 'tag' => 'Access Control',
         'desc' => 'Tenants receive portal access only after their record is explicitly linked to a Supabase auth account. Keeps database record and login access separate.',
         'items' => ['Create tenant record first', 'Create Supabase auth user separately', 'Link via admin panel', 'Tenant sees only their own data']],
    ];
    foreach ($modules as $m): ?>
    <div style="display:grid; grid-template-columns:280px 1fr; gap:2rem; padding:2.5rem 0; border-bottom:1px solid var(--border);">
        <div>
            <span class="badge badge-gold" style="margin-bottom:0.8rem; display:inline-block;"><?= $m['tag'] ?></span>
            <h3 style="font-family:var(--ff-display); font-size:1.4rem; font-weight:700; color:var(--text); line-height:1.2;"><?= $m['title'] ?></h3>
        </div>
        <div>
            <p style="color:var(--text-muted); font-size:0.95rem; line-height:1.7; margin-bottom:1.2rem;"><?= $m['desc'] ?></p>
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                <?php foreach ($m['items'] as $item): ?>
                <span style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:0.3rem 0.75rem; font-size:0.8rem; color:var(--text-soft);">&#10003; <?= $item ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div style="margin-top:4rem;">
        <div class="section-header" style="text-align:left; margin-bottom:2rem;">
            <span class="section-label">Backend</span>
            <h2 class="section-title" style="margin-bottom:0.5rem;">Powered by Supabase</h2>
            <p style="color:var(--text-muted); max-width:560px;">Authentication, database, and file storage &mdash; all handled by Supabase.</p>
        </div>
        <div class="three-col">
            <div class="db-card">
                <h3>Authentication</h3>
                <p style="font-size:0.875rem; color:var(--text-muted); line-height:1.6; margin-bottom:1.2rem;">Supabase Auth manages all sign-in accounts. Admin and tenant users are created separately and linked to their roles.</p>
                <div style="display:flex; flex-direction:column; gap:0.4rem; font-size:0.82rem; color:var(--text-muted);">
                    <span>&#10003; Create admin users</span>
                    <span>&#10003; Create &amp; invite tenant users</span>
                    <span>&#10003; Password reset flows</span>
                    <span>&#10003; Role-based routing after login</span>
                </div>
            </div>
            <div class="db-card">
                <h3>Database Tables</h3>
                <div class="db-table-list">
                    <?php
                    $tables = ['user_profiles','neighborhoods','properties','units','tenants','leases',
                               'rent_charges','rent_payments','maintenance_requests','maintenance_updates',
                               'maintenance_images','documents','contact_requests','notifications'];
                    foreach ($tables as $t): ?>
                    <span class="db-tag"><?= $t ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="db-card">
                <h3>Storage Buckets</h3>
                <p style="font-size:0.875rem; color:var(--text-muted); line-height:1.6; margin-bottom:1.2rem;">Two dedicated storage buckets handle file uploads linked to database records.</p>
                <div class="db-table-list">
                    <span class="db-tag">maintenance-images</span>
                    <span class="db-tag">lease-documents</span>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:2rem;">
        <div class="db-card">
            <h3>Safe Data Cleanup &mdash; Dependency Order</h3>
            <p style="font-size:0.875rem; color:var(--text-muted); line-height:1.6; margin-bottom:1.5rem;">When cleaning test data manually in Supabase, always delete child records before parent records:</p>
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
                <?php
                $order = ['notifications','contact_requests','maintenance_updates','maintenance_images',
                          'maintenance_requests','rent_payments','rent_charges','documents','leases',
                          'tenants','units','properties','neighborhoods'];
                foreach ($order as $i => $item): ?>
                <span class="db-tag"><?= $item ?></span>
                <?php if ($i < count($order)-1): ?><span style="color:var(--text-muted); font-size:0.7rem;">&rarr;</span><?php endif; endforeach; ?>
            </div>
        </div>
    </div>

</div>
</div>
