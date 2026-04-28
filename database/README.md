# PropTXChange — Database Setup Guide

## Overview

This directory contains the full Supabase SQL schema and seed data for PropTXChange.

## Files

| File | Purpose |
|------|---------|
| `schema.sql` | Creates all 14 tables, indexes, RLS policies, and triggers |
| `seed.sql` | Sample data to get started (replace placeholder UUIDs) |

---

## Step 1 — Run the Schema

1. Go to your **Supabase Dashboard**
2. Click **SQL Editor** in the left sidebar
3. Paste the contents of `schema.sql` and click **Run**

This creates all tables, indexes, Row Level Security policies, and auto-triggers.

---

## Step 2 — Create Your Admin User

1. In Supabase Dashboard, go to **Authentication → Users → Add User**
2. Set email + password, then under **User Metadata** add:
```json
{ "role": "admin", "full_name": "Your Name" }
```
3. Copy the UUID from the Users table.

---

## Step 3 — (Optional) Run Seed Data

1. Open `seed.sql`
2. Replace `aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa` with your real admin UUID
3. Run in SQL Editor

---

## Step 4 — Configure the PHP App

Open `config/env.php` and set your credentials:

```php
// Option A: Environment variables (recommended)
export SUPABASE_URL=https://yourproject.supabase.co
export SUPABASE_ANON_KEY=your-anon-key

// Option B: Edit config/env.php directly (local dev only)
```

Find these values in:
**Supabase Dashboard → Project Settings → API**

---

## Table Summary

```
user_profiles        — Extends auth.users with role + display info
neighborhoods        — Optional location groupings
properties           — Buildings managed by an admin
units                — Rentable units within properties
tenants              — Tenant records (linked to auth optionally)
leases               — Lease agreements
rent_charges         — Monthly charges per lease cycle
rent_payments        — Payments recorded against charges
maintenance_requests — Repair requests from tenants
maintenance_updates  — Status change log for repairs
maintenance_images   — Photos attached to repair requests
documents            — Admin-uploaded files
contact_requests     — Tenant-to-admin messages
notifications        — In-app alerts for users
```

---

## Storage Buckets

Create these two buckets in **Supabase Storage**:

- `maintenance-images` — photos uploaded by tenants
- `lease-documents` — lease PDFs and admin documents

Set bucket policies to allow authenticated users to upload to their own paths.

---

## Adding a Tenant Login

1. Create the tenant record via the admin panel
2. Go to Supabase → **Authentication → Add User**
3. Add user metadata: `{ "role": "tenant" }`
4. In the admin panel, go to the tenant record → **Link existing auth user**

---

## Dependency Order for Manual Cleanup

When deleting test data, always go child → parent:

```
notifications → contact_requests → maintenance_updates
→ maintenance_images → maintenance_requests → rent_payments
→ rent_charges → documents → leases → tenants
→ units → properties → neighborhoods
```
