# Database Setup

## 1. Create database and run schema
```bash
mysql -u root -p -e "CREATE DATABASE rpm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p rpm_db < database/schema.sql
```

Or paste `schema.sql` into **phpMyAdmin > SQL**.

## 2. Set credentials
Edit `config/config.php`:
```php
define('DB_NAME', 'rpm_db');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
```

## 3. Create your admin account
Visit the site → click **Register** → choose Admin tab → enter the admin code from `config/config.php`.

Default code: `CHANGE_ME_2025` — **change this before going live.**

## 4. Assign tenants to units
After a tenant registers, go to your MySQL admin and run:
```sql
UPDATE tenants SET unit_id = 'THE_UNIT_UUID', status = 'active'
WHERE email = 'tenant@example.com';
```
Then optionally create a lease row for them.

## Table Overview
| Table | Purpose |
|---|---|
| user_profiles | Login accounts for admins and tenants |
| neighborhoods | Optional location groupings |
| properties | Buildings/addresses |
| units | Rentable units in each property |
| tenants | Tenant records (linked to user_profiles) |
| leases | Lease agreements |
| rent_charges | Monthly charges per lease cycle |
| rent_payments | Payments recorded against charges |
| maintenance_requests | Repair requests |
| maintenance_updates | Audit log of status changes |
| documents | Admin-uploaded files |
| notifications | In-app alerts |
