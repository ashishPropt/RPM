-- ============================================================
-- PropTXChange — MySQL Database Schema
-- Compatible with: MySQL 8.0+ / MariaDB 10.4+
-- Run in: phpMyAdmin, MySQL Workbench, or CLI
--   mysql -u root -p rpm_db < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS rpm_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE rpm_db;

-- ============================================================
-- 1. USER PROFILES
-- Stores admin and tenant login accounts
-- ============================================================
CREATE TABLE IF NOT EXISTS user_profiles (
    id            CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    role          ENUM('admin','tenant') NOT NULL DEFAULT 'tenant',
    full_name     VARCHAR(150),
    email         VARCHAR(255)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    phone         VARCHAR(30),
    avatar_url    VARCHAR(500),
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_role  (role),
    INDEX idx_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. NEIGHBORHOODS
-- Optional location grouping for properties
-- ============================================================
CREATE TABLE IF NOT EXISTS neighborhoods (
    id            CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    name          VARCHAR(150)  NOT NULL,
    city          VARCHAR(100)  NOT NULL,
    state_code    CHAR(2)       NOT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. PROPERTIES
-- A building or address managed by an admin
-- ============================================================
CREATE TABLE IF NOT EXISTS properties (
    id                CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    admin_id          CHAR(36)      NOT NULL,
    neighborhood_id   CHAR(36)      DEFAULT NULL,
    name              VARCHAR(200)  NOT NULL,
    address           VARCHAR(300)  NOT NULL,
    city              VARCHAR(100)  NOT NULL,
    state_code        CHAR(2)       NOT NULL,
    zip_code          VARCHAR(10),
    description       TEXT,
    is_active         TINYINT(1)    NOT NULL DEFAULT 1,
    created_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_prop_admin   FOREIGN KEY (admin_id)        REFERENCES user_profiles(id) ON DELETE CASCADE,
    CONSTRAINT fk_prop_neigh   FOREIGN KEY (neighborhood_id) REFERENCES neighborhoods(id) ON DELETE SET NULL,
    INDEX idx_prop_admin  (admin_id),
    INDEX idx_prop_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. UNITS
-- Individual rentable units within a property
-- ============================================================
CREATE TABLE IF NOT EXISTS units (
    id              CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    property_id     CHAR(36)      NOT NULL,
    unit_number     VARCHAR(20)   NOT NULL,
    bedrooms        INT           NOT NULL DEFAULT 1,
    bathrooms       DECIMAL(3,1)  NOT NULL DEFAULT 1.0,
    square_feet     INT,
    monthly_rent    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_occupied     TINYINT(1)    NOT NULL DEFAULT 0,
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    notes           TEXT,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_unit_prop    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    UNIQUE KEY uq_unit_number (property_id, unit_number),
    INDEX idx_unit_property (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. TENANTS
-- Tenant records, optionally linked to a user_profile
-- ============================================================
CREATE TABLE IF NOT EXISTS tenants (
    id              CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    user_id         CHAR(36)      DEFAULT NULL,
    unit_id         CHAR(36)      DEFAULT NULL,
    first_name      VARCHAR(100)  NOT NULL,
    last_name       VARCHAR(100)  NOT NULL,
    email           VARCHAR(255)  NOT NULL,
    phone           VARCHAR(30),
    status          ENUM('active','former','pending') NOT NULL DEFAULT 'active',
    score           INT           NOT NULL DEFAULT 100 CHECK (score BETWEEN 0 AND 100),
    score_notes     TEXT,
    move_in_date    DATE,
    move_out_date   DATE,
    emergency_contact_name  VARCHAR(150),
    emergency_contact_phone VARCHAR(30),
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tenant_user FOREIGN KEY (user_id)  REFERENCES user_profiles(id) ON DELETE SET NULL,
    CONSTRAINT fk_tenant_unit FOREIGN KEY (unit_id)  REFERENCES units(id)         ON DELETE SET NULL,
    INDEX idx_tenant_user   (user_id),
    INDEX idx_tenant_unit   (unit_id),
    INDEX idx_tenant_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. LEASES
-- Lease agreements tied to a tenant and unit
-- ============================================================
CREATE TABLE IF NOT EXISTS leases (
    id               CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    tenant_id        CHAR(36)      NOT NULL,
    unit_id          CHAR(36)      NOT NULL,
    start_date       DATE          NOT NULL,
    end_date         DATE          NOT NULL,
    monthly_rent     DECIMAL(10,2) NOT NULL,
    security_deposit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status           ENUM('active','expired','terminated') NOT NULL DEFAULT 'active',
    notes            TEXT,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lease_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_lease_unit   FOREIGN KEY (unit_id)   REFERENCES units(id)   ON DELETE CASCADE,
    INDEX idx_lease_tenant (tenant_id),
    INDEX idx_lease_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. RENT CHARGES
-- Monthly charges generated per lease cycle
-- ============================================================
CREATE TABLE IF NOT EXISTS rent_charges (
    id              CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    tenant_id       CHAR(36)      NOT NULL,
    lease_id        CHAR(36)      NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    due_date        DATE          NOT NULL,
    charge_month    VARCHAR(7)    NOT NULL COMMENT 'Format: YYYY-MM e.g. 2025-05',
    status          ENUM('pending','paid','partial','overdue','waived') NOT NULL DEFAULT 'pending',
    notes           TEXT,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_charge_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_charge_lease  FOREIGN KEY (lease_id)  REFERENCES leases(id)  ON DELETE CASCADE,
    INDEX idx_charge_tenant (tenant_id),
    INDEX idx_charge_status (status),
    INDEX idx_charge_due    (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. RENT PAYMENTS
-- Actual payments recorded against a rent charge
-- ============================================================
CREATE TABLE IF NOT EXISTS rent_payments (
    id              CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    charge_id       CHAR(36)      NOT NULL,
    tenant_id       CHAR(36)      NOT NULL,
    amount_paid     DECIMAL(10,2) NOT NULL,
    payment_date    DATE          NOT NULL DEFAULT (CURRENT_DATE),
    payment_method  ENUM('manual','check','cash','bank_transfer','online') NOT NULL DEFAULT 'manual',
    recorded_by     CHAR(36)      DEFAULT NULL,
    notes           TEXT,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_charge     FOREIGN KEY (charge_id)   REFERENCES rent_charges(id)   ON DELETE CASCADE,
    CONSTRAINT fk_payment_tenant     FOREIGN KEY (tenant_id)   REFERENCES tenants(id)         ON DELETE CASCADE,
    CONSTRAINT fk_payment_recorded   FOREIGN KEY (recorded_by) REFERENCES user_profiles(id)   ON DELETE SET NULL,
    INDEX idx_payment_charge (charge_id),
    INDEX idx_payment_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. MAINTENANCE REQUESTS
-- Submitted by tenants, managed by admin
-- ============================================================
CREATE TABLE IF NOT EXISTS maintenance_requests (
    id              CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    tenant_id       CHAR(36)      NOT NULL,
    unit_id         CHAR(36)      NOT NULL,
    title           VARCHAR(255)  NOT NULL,
    description     TEXT,
    priority        ENUM('low','normal','high','emergency') NOT NULL DEFAULT 'normal',
    status          ENUM('open','in_process','materials_needed','completed','cancelled') NOT NULL DEFAULT 'open',
    assigned_to     VARCHAR(150),
    submitted_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at    DATETIME      DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_maint_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_maint_unit   FOREIGN KEY (unit_id)   REFERENCES units(id)   ON DELETE CASCADE,
    INDEX idx_maint_tenant (tenant_id),
    INDEX idx_maint_status (status),
    INDEX idx_maint_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. MAINTENANCE UPDATES
-- Status change log for maintenance requests
-- ============================================================
CREATE TABLE IF NOT EXISTS maintenance_updates (
    id              CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    request_id      CHAR(36)      NOT NULL,
    updated_by      CHAR(36)      DEFAULT NULL,
    old_status      VARCHAR(30),
    new_status      VARCHAR(30)   NOT NULL,
    note            TEXT,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mupdate_request FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_mupdate_user    FOREIGN KEY (updated_by)  REFERENCES user_profiles(id)        ON DELETE SET NULL,
    INDEX idx_mupdate_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. MAINTENANCE IMAGES
-- Photos attached to maintenance requests
-- ============================================================
CREATE TABLE IF NOT EXISTS maintenance_images (
    id              CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    request_id      CHAR(36)      NOT NULL,
    storage_path    VARCHAR(500)  NOT NULL,
    file_name       VARCHAR(255),
    uploaded_by     CHAR(36)      DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mimage_request FOREIGN KEY (request_id)  REFERENCES maintenance_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_mimage_user    FOREIGN KEY (uploaded_by)  REFERENCES user_profiles(id)        ON DELETE SET NULL,
    INDEX idx_mimage_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. DOCUMENTS
-- Admin-uploaded files (leases, notices, etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS documents (
    id                   CHAR(36)     NOT NULL PRIMARY KEY DEFAULT (UUID()),
    tenant_id            CHAR(36)     DEFAULT NULL,
    unit_id              CHAR(36)     DEFAULT NULL,
    property_id          CHAR(36)     DEFAULT NULL,
    uploaded_by          CHAR(36)     DEFAULT NULL,
    title                VARCHAR(255) NOT NULL,
    description          TEXT,
    storage_path         VARCHAR(500) NOT NULL,
    file_name            VARCHAR(255),
    file_type            VARCHAR(50),
    is_visible_to_tenant TINYINT(1)   NOT NULL DEFAULT 1,
    created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doc_tenant   FOREIGN KEY (tenant_id)   REFERENCES tenants(id)       ON DELETE CASCADE,
    CONSTRAINT fk_doc_unit     FOREIGN KEY (unit_id)     REFERENCES units(id)         ON DELETE SET NULL,
    CONSTRAINT fk_doc_property FOREIGN KEY (property_id) REFERENCES properties(id)    ON DELETE SET NULL,
    CONSTRAINT fk_doc_uploader FOREIGN KEY (uploaded_by) REFERENCES user_profiles(id) ON DELETE SET NULL,
    INDEX idx_doc_tenant  (tenant_id),
    INDEX idx_doc_visible (tenant_id, is_visible_to_tenant)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. CONTACT REQUESTS
-- Messages from tenants to admin
-- ============================================================
CREATE TABLE IF NOT EXISTS contact_requests (
    id          CHAR(36)     NOT NULL PRIMARY KEY DEFAULT (UUID()),
    tenant_id   CHAR(36)     DEFAULT NULL,
    subject     VARCHAR(255) NOT NULL,
    message     TEXT         NOT NULL,
    is_read     TINYINT(1)   NOT NULL DEFAULT 0,
    replied_at  DATETIME     DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contact_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
    INDEX idx_contact_tenant (tenant_id),
    INDEX idx_contact_read   (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. NOTIFICATIONS
-- In-app notifications for both admin and tenants
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id              CHAR(36)     NOT NULL PRIMARY KEY DEFAULT (UUID()),
    user_id         CHAR(36)     NOT NULL,
    type            ENUM('rent_reminder','rent_overdue','repair_update','repair_complete','general') NOT NULL,
    title           VARCHAR(255) NOT NULL,
    body            TEXT         NOT NULL,
    is_read         TINYINT(1)   NOT NULL DEFAULT 0,
    related_id      CHAR(36)     DEFAULT NULL COMMENT 'Optional FK to related record',
    related_type    VARCHAR(50)  DEFAULT NULL COMMENT 'e.g. rent_charge, maintenance_request',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES user_profiles(id) ON DELETE CASCADE,
    INDEX idx_notif_user     (user_id),
    INDEX idx_notif_unread   (user_id, is_read),
    INDEX idx_notif_created  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VIEWS
-- Useful pre-built joins for the PHP layer
-- ============================================================

-- Active tenants with their unit and property info
CREATE OR REPLACE VIEW v_active_tenants AS
    SELECT
        t.id            AS tenant_id,
        t.first_name,
        t.last_name,
        CONCAT(t.first_name, ' ', t.last_name) AS full_name,
        t.email,
        t.phone,
        t.status,
        t.score,
        t.score_notes,
        t.move_in_date,
        t.user_id,
        u.id            AS unit_id,
        u.unit_number,
        u.monthly_rent,
        p.id            AS property_id,
        p.name          AS property_name,
        p.address       AS property_address,
        p.city,
        p.state_code
    FROM tenants t
    LEFT JOIN units      u ON u.id = t.unit_id
    LEFT JOIN properties p ON p.id = u.property_id
    WHERE t.status = 'active';

-- Open + in-progress maintenance requests
CREATE OR REPLACE VIEW v_open_repairs AS
    SELECT
        mr.id,
        mr.title,
        mr.description,
        mr.priority,
        mr.status,
        mr.submitted_at,
        mr.assigned_to,
        CONCAT(t.first_name, ' ', t.last_name) AS tenant_name,
        u.unit_number
    FROM maintenance_requests mr
    JOIN tenants t ON t.id = mr.tenant_id
    JOIN units   u ON u.id = mr.unit_id
    WHERE mr.status != 'completed' AND mr.status != 'cancelled'
    ORDER BY
        FIELD(mr.priority, 'emergency','high','normal','low'),
        mr.submitted_at ASC;

-- Rent charges with tenant name
CREATE OR REPLACE VIEW v_rent_charges AS
    SELECT
        rc.id,
        rc.amount,
        rc.due_date,
        rc.charge_month,
        rc.status,
        rc.notes,
        rc.tenant_id,
        CONCAT(t.first_name, ' ', t.last_name) AS tenant_name,
        u.unit_number,
        p.name AS property_name
    FROM rent_charges rc
    JOIN tenants     t ON t.id = rc.tenant_id
    LEFT JOIN units  u ON u.id = t.unit_id
    LEFT JOIN properties p ON p.id = u.property_id
    ORDER BY rc.due_date DESC;
