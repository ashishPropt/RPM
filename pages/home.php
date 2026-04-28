<?php
// Redirect already-logged-in users straight to their dashboard
if (AppAuth::check()) {
    redirect(AppAuth::role() === 'admin' ? 'admin' : 'tenant');
}
?>
<section class="hero">
  <div class="hero-inner">
    <div class="hero-text">
      <h1>Property management,<br><span class="accent">finally simple.</span></h1>
      <p class="hero-sub">PropTXChange keeps your properties, tenants, rent, and repairs in one place — no spreadsheets, no scattered chats.</p>
      <div class="hero-btns">
        <a href="index.php?page=register&role=tenant" class="btn btn-primary btn-lg">Get Started</a>
        <a href="index.php?page=login"                class="btn btn-ghost  btn-lg">Sign In</a>
      </div>
    </div>
    <div class="hero-cards">
      <div class="feature-card">
        <div class="feature-icon">🏘️</div>
        <h3>Properties &amp; Units</h3>
        <p>Manage multiple buildings and units from one dashboard.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">💵</div>
        <h3>Rent Tracking</h3>
        <p>Record payments, flag overdue charges, view full history.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🔧</div>
        <h3>Maintenance</h3>
        <p>Tenants submit requests. Admins track them to completion.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🔔</div>
        <h3>Notifications</h3>
        <p>In-app alerts for rent reminders, repairs, and updates.</p>
      </div>
    </div>
  </div>
</section>

<section class="portals">
  <div class="container">
    <h2 class="section-title">Two portals. One platform.</h2>
    <div class="portals-grid">
      <div class="portal-card">
        <div class="portal-icon">🏢</div>
        <h3>Admin Portal</h3>
        <ul>
          <li>Add &amp; manage properties and units</li>
          <li>Record rent payments manually</li>
          <li>Review and update repair requests</li>
          <li>Upload documents for tenants</li>
        </ul>
        <a href="index.php?page=register&role=admin" class="btn btn-gold">Register as Admin</a>
      </div>
      <div class="portal-card">
        <div class="portal-icon">🏠</div>
        <h3>Tenant Portal</h3>
        <ul>
          <li>Check your current rent status</li>
          <li>View your lease details</li>
          <li>Submit maintenance requests</li>
          <li>Receive notifications</li>
        </ul>
        <a href="index.php?page=register&role=tenant" class="btn btn-gold">Register as Tenant</a>
      </div>
    </div>
  </div>
</section>
