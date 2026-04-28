-- ============================================================
-- PropTXChange — Supabase Database Schema
-- Run this in: Supabase Dashboard > SQL Editor
-- ============================================================

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================================
-- 1. USER PROFILES
-- Extends Supabase auth.users with role and display info
-- ============================================================
CREATE TABLE IF NOT EXISTS public.user_profiles (
    id            UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    role          TEXT NOT NULL CHECK (role IN ('admin', 'tenant')),
    full_name     TEXT,
    phone         TEXT,
    avatar_url    TEXT,
    created_at    TIMESTAMPTZ DEFAULT NOW(),
    updated_at    TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- 2. NEIGHBORHOODS
-- Optional location grouping for properties
-- ============================================================
CREATE TABLE IF NOT EXISTS public.neighborhoods (
    id            UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name          TEXT NOT NULL,
    city          TEXT NOT NULL,
    state_code    TEXT NOT NULL,
    created_at    TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- 3. PROPERTIES
-- A property is a building or address managed by an admin
-- ============================================================
CREATE TABLE IF NOT EXISTS public.properties (
    id                UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    admin_id          UUID NOT NULL REFERENCES public.user_profiles(id) ON DELETE CASCADE,
    neighborhood_id   UUID REFERENCES public.neighborhoods(id) ON DELETE SET NULL,
    name              TEXT NOT NULL,
    address           TEXT NOT NULL,
    city              TEXT NOT NULL,
    state_code        TEXT NOT NULL,
    zip_code          TEXT,
    description       TEXT,
    is_active         BOOLEAN DEFAULT TRUE,
    created_at        TIMESTAMPTZ DEFAULT NOW(),
    updated_at        TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- 4. UNITS
-- Individual rentable units within a property
-- ============================================================
CREATE TABLE IF NOT EXISTS public.units (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    property_id     UUID NOT NULL REFERENCES public.properties(id) ON DELETE CASCADE,
    unit_number     TEXT NOT NULL,
    bedrooms        INTEGER DEFAULT 1,
    bathrooms       NUMERIC(3,1) DEFAULT 1,
    square_feet     INTEGER,
    monthly_rent    NUMERIC(10,2) NOT NULL DEFAULT 0,
    is_occupied     BOOLEAN DEFAULT FALSE,
    is_active       BOOLEAN DEFAULT TRUE,
    notes           TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE (property_id, unit_number)
);

-- ============================================================
-- 5. TENANTS
-- Tenant records. Linked optionally to a user_profile.
-- ============================================================
CREATE TABLE IF NOT EXISTS public.tenants (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id         UUID REFERENCES public.user_profiles(id) ON DELETE SET NULL,
    unit_id         UUID REFERENCES public.units(id) ON DELETE SET NULL,
    first_name      TEXT NOT NULL,
    last_name       TEXT NOT NULL,
    email           TEXT NOT NULL,
    phone           TEXT,
    status          TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'former', 'pending')),
    score           INTEGER DEFAULT 100 CHECK (score BETWEEN 0 AND 100),
    score_notes     TEXT,
    move_in_date    DATE,
    move_out_date   DATE,
    emergency_contact_name  TEXT,
    emergency_contact_phone TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- 6. LEASES
-- Lease agreements tied to a tenant and unit
-- ============================================================
CREATE TABLE IF NOT EXISTS public.leases (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id       UUID NOT NULL REFERENCES public.tenants(id) ON DELETE CASCADE,
    unit_id         UUID NOT NULL REFERENCES public.units(id) ON DELETE CASCADE,
    start_date      DATE NOT NULL,
    end_date        DATE NOT NULL,
    monthly_rent    NUMERIC(10,2) NOT NULL,
    security_deposit NUMERIC(10,2) DEFAULT 0,
    status          TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'expired', 'terminated')),
    notes           TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- 7. RENT CHARGES
-- Monthly charges generated per lease cycle
-- ============================================================
CREATE TABLE IF NOT EXISTS public.rent_charges (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id       UUID NOT NULL REFERENCES public.tenants(id) ON DELETE CASCADE,
    lease_id        UUID NOT NULL REFERENCES public.leases(id) ON DELETE CASCADE,
    amount          NUMERIC(10,2) NOT NULL,
    due_date        DATE NOT NULL,
    charge_month    TEXT NOT NULL,  -- e.g. '2025-05'
    status          TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'partial', 'overdue', 'waived')),
    notes           TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- 8. RENT PAYMENTS
-- Actual payments recorded against a rent charge
-- ============================================================
CREATE TABLE IF NOT EXISTS public.rent_payments (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    charge_id       UUID NOT NULL REFERENCES public.rent_charges(id) ON DELETE CASCADE,
    tenant_id       UUID NOT NULL REFERENCES public.tenants(id) ON DELETE CASCADE,
    amount_paid     NUMERIC(10,2) NOT NULL,
    payment_date    DATE NOT NULL DEFAULT CURRENT_DATE,
    payment_method  TEXT DEFAULT 'manual' CHECK (payment_method IN ('manual', 'check', 'cash', 'bank_transfer', 'online')),
    recorded_by     UUID REFERENCES public.user_profiles(id) ON DELETE SET NULL,
    notes           TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- 9. MAINTENANCE REQUESTS
-- Submitted by tenants, managed by admin
-- ============================================================
CREATE TABLE IF NOT EXISTS public.maintenance_requests (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id       UUID NOT NULL REFERENCES public.tenants(id) ON DELETE CASCADE,
    unit_id         UUID NOT NULL REFERENCES public.units(id) ON DELETE CASCADE,
    title           TEXT NOT NULL,
    description     TEXT,
    priority        TEXT NOT NULL DEFAULT 'normal' CHECK (priority IN ('low', 'normal', 'high', 'emergency')),
    status          TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'in_process', 'materials_needed', 'completed', 'cancelled')),
    submitted_at    TIMESTAMPTZ DEFAULT NOW(),
    completed_at    TIMESTAMPTZ,
    assigned_to     TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- 10. MAINTENANCE UPDATES
-- Status change log for maintenance requests
-- ============================================================
CREATE TABLE IF NOT EXISTS public.maintenance_updates (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    request_id      UUID NOT NULL REFERENCES public.maintenance_requests(id) ON DELETE CASCADE,
    updated_by      UUID REFERENCES public.user_profiles(id) ON DELETE SET NULL,
    old_status      TEXT,
    new_status      TEXT NOT NULL,
    note            TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- 11. MAINTENANCE IMAGES
-- Photos attached to maintenance requests
-- ============================================================
CREATE TABLE IF NOT EXISTS public.maintenance_images (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    request_id      UUID NOT NULL REFERENCES public.maintenance_requests(id) ON DELETE CASCADE,
    storage_path    TEXT NOT NULL,
    file_name       TEXT,
    uploaded_by     UUID REFERENCES public.user_profiles(id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- 12. DOCUMENTS
-- Admin-uploaded files (leases, notices, etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS public.documents (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id       UUID REFERENCES public.tenants(id) ON DELETE CASCADE,
    unit_id         UUID REFERENCES public.units(id) ON DELETE SET NULL,
    property_id     UUID REFERENCES public.properties(id) ON DELETE SET NULL,
    uploaded_by     UUID REFERENCES public.user_profiles(id) ON DELETE SET NULL,
    title           TEXT NOT NULL,
    description     TEXT,
    storage_path    TEXT NOT NULL,
    file_name       TEXT,
    file_type       TEXT,
    is_visible_to_tenant BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- 13. CONTACT REQUESTS
-- Messages from tenants to admin
-- ============================================================
CREATE TABLE IF NOT EXISTS public.contact_requests (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id       UUID REFERENCES public.tenants(id) ON DELETE SET NULL,
    subject         TEXT NOT NULL,
    message         TEXT NOT NULL,
    is_read         BOOLEAN DEFAULT FALSE,
    replied_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- 14. NOTIFICATIONS
-- In-app notifications for both admin and tenants
-- ============================================================
CREATE TABLE IF NOT EXISTS public.notifications (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id         UUID NOT NULL REFERENCES public.user_profiles(id) ON DELETE CASCADE,
    type            TEXT NOT NULL CHECK (type IN ('rent_reminder', 'rent_overdue', 'repair_update', 'repair_complete', 'general')),
    title           TEXT NOT NULL,
    body            TEXT NOT NULL,
    is_read         BOOLEAN DEFAULT FALSE,
    related_id      UUID,   -- optional FK to relevant record
    related_type    TEXT,   -- 'rent_charge', 'maintenance_request', etc.
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- INDEXES for common query patterns
-- ============================================================
CREATE INDEX IF NOT EXISTS idx_tenants_unit       ON public.tenants(unit_id);
CREATE INDEX IF NOT EXISTS idx_tenants_user       ON public.tenants(user_id);
CREATE INDEX IF NOT EXISTS idx_tenants_status     ON public.tenants(status);
CREATE INDEX IF NOT EXISTS idx_units_property     ON public.units(property_id);
CREATE INDEX IF NOT EXISTS idx_leases_tenant      ON public.leases(tenant_id);
CREATE INDEX IF NOT EXISTS idx_rent_charges_tenant ON public.rent_charges(tenant_id);
CREATE INDEX IF NOT EXISTS idx_rent_charges_status ON public.rent_charges(status);
CREATE INDEX IF NOT EXISTS idx_rent_payments_charge ON public.rent_payments(charge_id);
CREATE INDEX IF NOT EXISTS idx_maintenance_tenant  ON public.maintenance_requests(tenant_id);
CREATE INDEX IF NOT EXISTS idx_maintenance_status  ON public.maintenance_requests(status);
CREATE INDEX IF NOT EXISTS idx_notifications_user  ON public.notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_read  ON public.notifications(user_id, is_read);

-- ============================================================
-- ROW LEVEL SECURITY (RLS)
-- ============================================================
ALTER TABLE public.user_profiles          ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.properties             ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.units                  ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tenants                ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.leases                 ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rent_charges           ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rent_payments          ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.maintenance_requests   ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.maintenance_updates    ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.maintenance_images     ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.documents              ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.contact_requests       ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.notifications          ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.neighborhoods          ENABLE ROW LEVEL SECURITY;

-- user_profiles: users can read/update their own profile
CREATE POLICY "users_own_profile" ON public.user_profiles
    FOR ALL USING (auth.uid() = id);

-- admins can read all profiles
CREATE POLICY "admins_read_profiles" ON public.user_profiles
    FOR SELECT USING (
        EXISTS (SELECT 1 FROM public.user_profiles WHERE id = auth.uid() AND role = 'admin')
    );

-- properties: admins manage, tenants can read their own property
CREATE POLICY "admins_manage_properties" ON public.properties
    FOR ALL USING (
        EXISTS (SELECT 1 FROM public.user_profiles WHERE id = auth.uid() AND role = 'admin')
    );

CREATE POLICY "tenants_view_own_property" ON public.properties
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM public.tenants t
            JOIN public.units u ON u.id = t.unit_id
            WHERE t.user_id = auth.uid() AND u.property_id = properties.id
        )
    );

-- units: admins full access, tenants view own
CREATE POLICY "admins_manage_units" ON public.units
    FOR ALL USING (
        EXISTS (SELECT 1 FROM public.user_profiles WHERE id = auth.uid() AND role = 'admin')
    );

CREATE POLICY "tenants_view_own_unit" ON public.units
    FOR SELECT USING (
        EXISTS (SELECT 1 FROM public.tenants WHERE user_id = auth.uid() AND unit_id = units.id)
    );

-- tenants: admins full, tenants view own
CREATE POLICY "admins_manage_tenants" ON public.tenants
    FOR ALL USING (
        EXISTS (SELECT 1 FROM public.user_profiles WHERE id = auth.uid() AND role = 'admin')
    );

CREATE POLICY "tenants_view_self" ON public.tenants
    FOR SELECT USING (user_id = auth.uid());

-- leases, charges, payments: same pattern
CREATE POLICY "admins_manage_leases" ON public.leases
    FOR ALL USING (EXISTS (SELECT 1 FROM public.user_profiles WHERE id = auth.uid() AND role = 'admin'));
CREATE POLICY "tenants_view_own_lease" ON public.leases
    FOR SELECT USING (EXISTS (SELECT 1 FROM public.tenants WHERE user_id = auth.uid() AND id = leases.tenant_id));

CREATE POLICY "admins_manage_charges" ON public.rent_charges
    FOR ALL USING (EXISTS (SELECT 1 FROM public.user_profiles WHERE id = auth.uid() AND role = 'admin'));
CREATE POLICY "tenants_view_own_charges" ON public.rent_charges
    FOR SELECT USING (EXISTS (SELECT 1 FROM public.tenants WHERE user_id = auth.uid() AND id = rent_charges.tenant_id));

CREATE POLICY "admins_manage_payments" ON public.rent_payments
    FOR ALL USING (EXISTS (SELECT 1 FROM public.user_profiles WHERE id = auth.uid() AND role = 'admin'));
CREATE POLICY "tenants_view_own_payments" ON public.rent_payments
    FOR SELECT USING (EXISTS (SELECT 1 FROM public.tenants WHERE user_id = auth.uid() AND id = rent_payments.tenant_id));

-- maintenance: tenants can insert + view own; admins full
CREATE POLICY "admins_manage_maintenance" ON public.maintenance_requests
    FOR ALL USING (EXISTS (SELECT 1 FROM public.user_profiles WHERE id = auth.uid() AND role = 'admin'));
CREATE POLICY "tenants_manage_own_maintenance" ON public.maintenance_requests
    FOR ALL USING (EXISTS (SELECT 1 FROM public.tenants WHERE user_id = auth.uid() AND id = maintenance_requests.tenant_id));

CREATE POLICY "admins_manage_maint_updates" ON public.maintenance_updates
    FOR ALL USING (EXISTS (SELECT 1 FROM public.user_profiles WHERE id = auth.uid() AND role = 'admin'));
CREATE POLICY "tenants_view_maint_updates" ON public.maintenance_updates
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM public.maintenance_requests mr
            JOIN public.tenants t ON t.id = mr.tenant_id
            WHERE t.user_id = auth.uid() AND mr.id = maintenance_updates.request_id
        )
    );

CREATE POLICY "admins_manage_maint_images" ON public.maintenance_images
    FOR ALL USING (EXISTS (SELECT 1 FROM public.user_profiles WHERE id = auth.uid() AND role = 'admin'));
CREATE POLICY "tenants_manage_own_images" ON public.maintenance_images
    FOR ALL USING (
        EXISTS (
            SELECT 1 FROM public.maintenance_requests mr
            JOIN public.tenants t ON t.id = mr.tenant_id
            WHERE t.user_id = auth.uid() AND mr.id = maintenance_images.request_id
        )
    );

-- documents: admins full, tenants view visible ones
CREATE POLICY "admins_manage_documents" ON public.documents
    FOR ALL USING (EXISTS (SELECT 1 FROM public.user_profiles WHERE id = auth.uid() AND role = 'admin'));
CREATE POLICY "tenants_view_documents" ON public.documents
    FOR SELECT USING (
        is_visible_to_tenant = TRUE AND
        EXISTS (SELECT 1 FROM public.tenants WHERE user_id = auth.uid() AND id = documents.tenant_id)
    );

-- notifications: users see only their own
CREATE POLICY "own_notifications" ON public.notifications
    FOR ALL USING (user_id = auth.uid());

-- contact requests: tenants manage own, admins full
CREATE POLICY "admins_manage_contacts" ON public.contact_requests
    FOR ALL USING (EXISTS (SELECT 1 FROM public.user_profiles WHERE id = auth.uid() AND role = 'admin'));
CREATE POLICY "tenants_own_contacts" ON public.contact_requests
    FOR ALL USING (EXISTS (SELECT 1 FROM public.tenants WHERE user_id = auth.uid() AND id = contact_requests.tenant_id));

-- neighborhoods: admins manage, all authenticated can read
CREATE POLICY "admins_manage_neighborhoods" ON public.neighborhoods
    FOR ALL USING (EXISTS (SELECT 1 FROM public.user_profiles WHERE id = auth.uid() AND role = 'admin'));
CREATE POLICY "authenticated_view_neighborhoods" ON public.neighborhoods
    FOR SELECT USING (auth.role() = 'authenticated');

-- ============================================================
-- UPDATED_AT TRIGGER FUNCTION
-- ============================================================
CREATE OR REPLACE FUNCTION public.handle_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_user_profiles_updated_at
    BEFORE UPDATE ON public.user_profiles
    FOR EACH ROW EXECUTE FUNCTION public.handle_updated_at();

CREATE TRIGGER trg_properties_updated_at
    BEFORE UPDATE ON public.properties
    FOR EACH ROW EXECUTE FUNCTION public.handle_updated_at();

CREATE TRIGGER trg_units_updated_at
    BEFORE UPDATE ON public.units
    FOR EACH ROW EXECUTE FUNCTION public.handle_updated_at();

CREATE TRIGGER trg_tenants_updated_at
    BEFORE UPDATE ON public.tenants
    FOR EACH ROW EXECUTE FUNCTION public.handle_updated_at();

CREATE TRIGGER trg_leases_updated_at
    BEFORE UPDATE ON public.leases
    FOR EACH ROW EXECUTE FUNCTION public.handle_updated_at();

CREATE TRIGGER trg_rent_charges_updated_at
    BEFORE UPDATE ON public.rent_charges
    FOR EACH ROW EXECUTE FUNCTION public.handle_updated_at();

CREATE TRIGGER trg_maintenance_updated_at
    BEFORE UPDATE ON public.maintenance_requests
    FOR EACH ROW EXECUTE FUNCTION public.handle_updated_at();

-- ============================================================
-- AUTO-CREATE USER PROFILE ON SIGNUP
-- ============================================================
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO public.user_profiles (id, role, full_name)
    VALUES (
        NEW.id,
        COALESCE(NEW.raw_user_meta_data->>'role', 'tenant'),
        COALESCE(NEW.raw_user_meta_data->>'full_name', NEW.email)
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();
