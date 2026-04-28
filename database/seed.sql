-- ============================================================
-- PropTXChange — MySQL Seed Data
-- Run AFTER schema.sql
-- NOTE: Replace placeholder UUIDs/passwords with real values
-- ============================================================

USE rpm_db;

-- ============================================================
-- Sample Admin User
-- Password: Admin@123  (bcrypt hash — change this!)
-- Generate a real hash in PHP: password_hash('yourpassword', PASSWORD_BCRYPT)
-- ============================================================
INSERT INTO user_profiles (id, role, full_name, email, password_hash, phone) VALUES
    ('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'admin', 'Property Manager', 'admin@proptxchange.com',
     '$2y$12$examplehashchangethisbeforeuse.admin1234567890ABCDEF', '555-000-0001')
ON DUPLICATE KEY UPDATE email = email;

-- ============================================================
-- Sample Neighborhoods
-- ============================================================
INSERT INTO neighborhoods (id, name, city, state_code) VALUES
    ('11111111-0000-0000-0000-000000000001', 'Crown Heights', 'Brooklyn', 'NY'),
    ('11111111-0000-0000-0000-000000000002', 'Fulton Area',   'Brooklyn', 'NY'),
    ('11111111-0000-0000-0000-000000000003', 'Kingston Area', 'Brooklyn', 'NY')
ON DUPLICATE KEY UPDATE name = name;

-- ============================================================
-- Sample Properties
-- ============================================================
INSERT INTO properties (id, admin_id, neighborhood_id, name, address, city, state_code, zip_code) VALUES
    ('22222222-0000-0000-0000-000000000001',
     'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
     '11111111-0000-0000-0000-000000000001',
     '412 Nostrand', '412 Nostrand Ave', 'Brooklyn', 'NY', '11216'),
    ('22222222-0000-0000-0000-000000000002',
     'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
     '11111111-0000-0000-0000-000000000002',
     '88 Fulton St', '88 Fulton St', 'Brooklyn', 'NY', '11201'),
    ('22222222-0000-0000-0000-000000000003',
     'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
     '11111111-0000-0000-0000-000000000003',
     '201 Kingston', '201 Kingston Ave', 'Brooklyn', 'NY', '11213')
ON DUPLICATE KEY UPDATE name = name;

-- ============================================================
-- Sample Units
-- ============================================================
INSERT INTO units (id, property_id, unit_number, bedrooms, bathrooms, monthly_rent, is_occupied) VALUES
    ('33333333-0000-0000-0000-000000000001', '22222222-0000-0000-0000-000000000001', '2B', 2, 1.0, 1850.00, 1),
    ('33333333-0000-0000-0000-000000000002', '22222222-0000-0000-0000-000000000001', '3A', 2, 1.0, 2100.00, 1),
    ('33333333-0000-0000-0000-000000000003', '22222222-0000-0000-0000-000000000002', '1C', 1, 1.0, 1650.00, 1),
    ('33333333-0000-0000-0000-000000000004', '22222222-0000-0000-0000-000000000002', '4D', 2, 2.0, 1975.00, 1),
    ('33333333-0000-0000-0000-000000000005', '22222222-0000-0000-0000-000000000003', '1A', 3, 2.0, 2250.00, 1)
ON DUPLICATE KEY UPDATE unit_number = unit_number;

-- ============================================================
-- Sample Tenant Login Accounts
-- Password for all: Tenant@123  (change before use!)
-- ============================================================
INSERT INTO user_profiles (id, role, full_name, email, password_hash, phone) VALUES
    ('bbbbbbbb-0000-0000-0000-000000000001', 'tenant', 'Marcus Webb',    'marcus@example.com',  '$2y$12$examplehashchangethisbeforeuse.tenant0001', '555-100-0001'),
    ('bbbbbbbb-0000-0000-0000-000000000002', 'tenant', 'Diana Chen',     'diana@example.com',   '$2y$12$examplehashchangethisbeforeuse.tenant0002', '555-100-0002'),
    ('bbbbbbbb-0000-0000-0000-000000000003', 'tenant', 'Tariq Osei',     'tariq@example.com',   '$2y$12$examplehashchangethisbeforeuse.tenant0003', '555-100-0003'),
    ('bbbbbbbb-0000-0000-0000-000000000004', 'tenant', 'Sofia Marin',    'sofia@example.com',   '$2y$12$examplehashchangethisbeforeuse.tenant0004', '555-100-0004'),
    ('bbbbbbbb-0000-0000-0000-000000000005', 'tenant', 'James Holloway', 'james@example.com',   '$2y$12$examplehashchangethisbeforeuse.tenant0005', '555-100-0005')
ON DUPLICATE KEY UPDATE email = email;

-- ============================================================
-- Sample Tenants (linked to user accounts + units)
-- ============================================================
INSERT INTO tenants (id, user_id, unit_id, first_name, last_name, email, phone, status, score, move_in_date) VALUES
    ('44444444-0000-0000-0000-000000000001', 'bbbbbbbb-0000-0000-0000-000000000001', '33333333-0000-0000-0000-000000000001', 'Marcus',  'Webb',     'marcus@example.com', '555-100-0001', 'active', 92, '2024-01-01'),
    ('44444444-0000-0000-0000-000000000002', 'bbbbbbbb-0000-0000-0000-000000000002', '33333333-0000-0000-0000-000000000002', 'Diana',   'Chen',     'diana@example.com',  '555-100-0002', 'active', 68, '2024-02-01'),
    ('44444444-0000-0000-0000-000000000003', 'bbbbbbbb-0000-0000-0000-000000000003', '33333333-0000-0000-0000-000000000003', 'Tariq',   'Osei',     'tariq@example.com',  '555-100-0003', 'active', 85, '2023-11-01'),
    ('44444444-0000-0000-0000-000000000004', 'bbbbbbbb-0000-0000-0000-000000000004', '33333333-0000-0000-0000-000000000004', 'Sofia',   'Marin',    'sofia@example.com',  '555-100-0004', 'active', 97, '2023-09-01'),
    ('44444444-0000-0000-0000-000000000005', 'bbbbbbbb-0000-0000-0000-000000000005', '33333333-0000-0000-0000-000000000005', 'James',   'Holloway', 'james@example.com',  '555-100-0005', 'active', 88, '2024-03-01')
ON DUPLICATE KEY UPDATE email = email;
