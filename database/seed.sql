-- ============================================================
-- PropTXChange — Sample Seed Data
-- Run AFTER schema.sql
-- NOTE: Replace the UUIDs below with real auth user UUIDs
--       from Supabase Auth after creating test accounts.
-- ============================================================

-- Sample neighborhood
INSERT INTO public.neighborhoods (id, name, city, state_code) VALUES
    ('11111111-0000-0000-0000-000000000001', 'Crown Heights', 'Brooklyn', 'NY'),
    ('11111111-0000-0000-0000-000000000002', 'Fulton Area',   'Brooklyn', 'NY'),
    ('11111111-0000-0000-0000-000000000003', 'Kingston Area', 'Brooklyn', 'NY')
ON CONFLICT DO NOTHING;

-- Sample properties (admin_id must match a real user_profile with role='admin')
-- Replace 'aaaaaaaa-...' with your actual admin user UUID
INSERT INTO public.properties (id, admin_id, neighborhood_id, name, address, city, state_code, zip_code) VALUES
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
ON CONFLICT DO NOTHING;

-- Sample units
INSERT INTO public.units (id, property_id, unit_number, bedrooms, bathrooms, monthly_rent, is_occupied) VALUES
    ('33333333-0000-0000-0000-000000000001', '22222222-0000-0000-0000-000000000001', '2B', 2, 1.0, 1850.00, TRUE),
    ('33333333-0000-0000-0000-000000000002', '22222222-0000-0000-0000-000000000001', '3A', 2, 1.0, 2100.00, TRUE),
    ('33333333-0000-0000-0000-000000000003', '22222222-0000-0000-0000-000000000002', '1C', 1, 1.0, 1650.00, TRUE),
    ('33333333-0000-0000-0000-000000000004', '22222222-0000-0000-0000-000000000002', '4D', 2, 2.0, 1975.00, TRUE),
    ('33333333-0000-0000-0000-000000000005', '22222222-0000-0000-0000-000000000003', '1A', 3, 2.0, 2250.00, TRUE)
ON CONFLICT DO NOTHING;
