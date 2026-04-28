<div class="page-body">

<!-- HERO -->
<section class="hero">
    <div class="hero-eyebrow">&#10022; Now in Version 2.0</div>
    <h1 class="hero-title fade-up">
        Property operations,<br><span class="accent">finally simplified</span>
    </h1>
    <p class="hero-sub fade-up delay-1">
        PropTXChange brings your properties, tenants, rent, and repairs into one unified platform &mdash; no more spreadsheets, no more scattered chats.
    </p>
    <div class="hero-actions fade-up delay-2">
        <a href="index.php?page=login&role=admin" class="btn-lg gold">Admin Portal &rarr;</a>
        <a href="index.php?page=features" class="btn-lg outline">Explore Features</a>
    </div>
    <div class="hero-stats fade-up delay-3">
        <div class="stat-item">
            <span class="stat-num">2</span>
            <span class="stat-label">Dedicated Portals</span>
        </div>
        <div class="stat-item">
            <span class="stat-num">14</span>
            <span class="stat-label">Database Tables</span>
        </div>
        <div class="stat-item">
            <span class="stat-num">4</span>
            <span class="stat-label">Repair Stages</span>
        </div>
        <div class="stat-item">
            <span class="stat-num">&infin;</span>
            <span class="stat-label">Properties Supported</span>
        </div>
    </div>
</section>

<!-- TWO PORTALS -->
<section class="section">
    <div class="section-header">
        <span class="section-label">Dual Access</span>
        <h2 class="section-title">Two portals, one platform</h2>
        <p class="section-desc">Separate, secure access paths for admins and tenants. Login routes each user to the right experience automatically.</p>
    </div>

    <div class="portals-grid">
        <div class="portal-card">
            <div class="portal-icon">&#x1F3E2;</div>
            <span class="portal-tag">Admin Side</span>
            <h3>Property Manager</h3>
            <p>Full operational control over your properties, units, tenants, rent collection, maintenance workflows, and document storage.</p>
            <div class="portal-features">
                <div class="portal-feature">Create and manage properties &amp; units</div>
                <div class="portal-feature">Record rent payments manually</div>
                <div class="portal-feature">Review and update repair requests</div>
                <div class="portal-feature">Upload and manage documents</div>
                <div class="portal-feature">Message tenants directly</div>
                <div class="portal-feature">Mark residents as former safely</div>
            </div>
            <a href="index.php?page=admin" class="portal-cta">View admin dashboard &rarr;</a>
        </div>

        <div class="portal-card">
            <div class="portal-icon">&#x1F3E0;</div>
            <span class="portal-tag">Tenant Side</span>
            <h3>Resident Portal</h3>
            <p>A clean, focused view for residents to check rent status, submit repair requests, receive notifications, and access lease documents.</p>
            <div class="portal-features">
                <div class="portal-feature">Check current amount owed</div>
                <div class="portal-feature">View lease summary</div>
                <div class="portal-feature">Submit repair &amp; maintenance requests</div>
                <div class="portal-feature">Upload repair photos</div>
                <div class="portal-feature">Receive rent &amp; repair notifications</div>
                <div class="portal-feature">Access shared documents</div>
            </div>
            <a href="index.php?page=tenant" class="portal-cta">View tenant portal &rarr;</a>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="section" style="background: var(--surface); max-width: 100%; padding: 5rem 2rem; margin: 0;">
<div style="max-width: var(--max-w); margin: 0 auto;">
    <div class="section-header">
        <span class="section-label">Getting Started</span>
        <h2 class="section-title">From setup to running in minutes</h2>
    </div>
    <div class="steps-row">
        <?php
        $steps = [
            ['01', 'Add Property', 'Create properties and units directly. No pre-setup required.'],
            ['02', 'Add Tenant', 'Register tenants and link their auth accounts for portal access.'],
            ['03', 'Track Rent', 'Record payments manually and monitor balances in real time.'],
            ['04', 'Manage Repairs', 'Tenants submit requests; admins move them through stages to completion.'],
        ];
        foreach ($steps as $s): ?>
        <div class="step-item">
            <div class="step-num"><?= $s[0] ?></div>
            <h4><?= $s[1] ?></h4>
            <p><?= $s[2] ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</section>

<!-- FEATURE HIGHLIGHTS -->
<section class="section">
    <div class="section-header">
        <span class="section-label">Core Modules</span>
        <h2 class="section-title">Everything in one place</h2>
        <p class="section-desc">Built around the real daily needs of a small property management operation.</p>
    </div>
    <div class="feature-grid">
        <?php
        $features = [
            ['&#x1F3D8;', 'Properties &amp; Units', 'Create properties with flexible location inputs &mdash; state, borough, city &mdash; all directly from the app.'],
            ['&#x1F4B5;', 'Rent Tracking', 'Track monthly rent cycles. See upcoming, overdue, and paid statuses with full payment history.'],
            ['&#x1F527;', 'Maintenance', 'Tenants submit requests with photos. Track through Open &rarr; In Process &rarr; Materials &rarr; Completed.'],
            ['&#x1F514;', 'Notifications', 'In-app alerts for rent reminders, overdue notices, repair updates, and completion confirmations.'],
            ['&#x1F4C4;', 'Documents', 'Upload and store lease documents. Tenants can access shared files from their portal.'],
            ['&#x1F4AC;', 'Messaging', 'Direct messaging from admin to tenant. Keep all communication inside the platform.'],
            ['&#x1F517;', 'Auth Linking', 'Link tenant records to Supabase auth accounts. Controlled, secure portal access per tenant.'],
            ['&#x1F4CA;', 'Tenant Score', 'Visible scoring system with reasons &mdash; a quick gauge of tenant reliability and standing.'],
            ['&#x1F5C2;', 'Former Tenants', 'Safely offboard residents. Preserve full history while disabling access and vacating units.'],
        ];
        foreach ($features as $f): ?>
        <div class="feature-card">
            <span class="feature-icon"><?= $f[0] ?></span>
            <h4><?= $f[1] ?></h4>
            <p><?= $f[2] ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

</div>
