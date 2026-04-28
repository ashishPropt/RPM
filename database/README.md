# PropTXChange — Database Setup Guide (MySQL)

## Requirements

- MySQL 8.0+ or MariaDB 10.4+
- PHP 8.0+ with `pdo_mysql` extension enabled

---

## Step 1 — Create the Database & Run the Schema

### Option A: Command Line
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS rpm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p rpm_db < database/schema.sql
```

### Option B: phpMyAdmin
1. Open phpMyAdmin
2. Create a new database named `rpm_db` (utf8mb4, utf8mb4_unicode_ci)
3. Select it, click the **SQL** tab
4. Paste the contents of `schema.sql` and click **Go**

---

## Step 2 — Configure Credentials

Open `config/env.php` and fill in your MySQL details:

```php
define('DB_HOST', 'localhost');   // usually localhost on shared hosting
define('DB_PORT', '3306');
define('DB_NAME', 'rpm_db');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

Or set real environment variables on your server (recommended for production).

---

## Step 3 — Create Your Admin Account

Run this in MySQL to create your first admin with a real bcrypt password:

```sql
INSERT INTO user_profiles (id, role, full_name, email, password_hash)
VALUES (
    UUID(),
    'admin',
    'Your Name',
    'admin@yourdomain.com',
    -- Generate hash in PHP: echo password_hash('YourPassword', PASSWORD_BCRYPT);
    '$2y$12$REPLACE_WITH_REAL_HASH'
);
```

Or generate a hash quickly in PHP CLI:
```bash
php -r "echo password_hash('YourPassword123', PASSWORD_BCRYPT);"
```

---

## Step 4 — (Optional) Load Sample Data

```bash
mysql -u root -p rpm_db < database/seed.sql
```

> **Note:** The seed file uses placeholder bcrypt hashes. Replace them with real ones before using for testing.

---

## Table Summary

```
user_profiles        — Login accounts for admins and tenants (bcrypt passwords)
neighborhoods        — Optional location groupings for properties
properties           — Buildings managed by an admin
units                — Rentable units within each property
tenants              — Tenant records, linked to a user_profile login
leases               — Lease agreements per tenant/unit
rent_charges         — Monthly charges generated per lease cycle
rent_payments        — Payments recorded against charges (with transaction)
maintenance_requests — Repair requests submitted by tenants
maintenance_updates  — Audit log of status changes for repairs
maintenance_images   — File paths for repair photos
documents            — Admin-uploaded files visible to tenants
contact_requests     — Tenant messages to admin
notifications        — In-app alerts for both roles
```

---

## Useful Views (included in schema.sql)

| View | Description |
|------|-------------|
| `v_active_tenants` | Tenants joined with unit + property |
| `v_open_repairs` | Non-completed repairs ordered by priority |
| `v_rent_charges` | Charges joined with tenant + unit + property names |

---

## Adding a Tenant Login

1. Create tenant record via Admin portal
2. Create a `user_profiles` row with `role = 'tenant'` and a bcrypt password
3. Set `tenants.user_id` to match the new `user_profiles.id`
4. Tenant can now sign in at the Tenant Login page

---

## Dependency Order for Manual Cleanup

When deleting test data, always delete child records first:

```
notifications → contact_requests → maintenance_updates
→ maintenance_images → maintenance_requests → rent_payments
→ rent_charges → documents → leases → tenants
→ units → properties → neighborhoods
```
