-- PropTXChange MySQL Schema
-- Run: mysql -u root -p rpm_db < database/schema.sql

CREATE DATABASE IF NOT EXISTS rpm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rpm_db;

-- 1. User accounts (admins + tenants)
CREATE TABLE IF NOT EXISTS user_profiles (
    id            CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    role          ENUM('admin','tenant') NOT NULL DEFAULT 'tenant',
    full_name     VARCHAR(150)  NOT NULL,
    email         VARCHAR(255)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    phone         VARCHAR(30),
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role  (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Neighborhoods (optional grouping)
CREATE TABLE IF NOT EXISTS neighborhoods (
    id         CHAR(36)     NOT NULL PRIMARY KEY DEFAULT (UUID()),
    name       VARCHAR(150) NOT NULL,
    city       VARCHAR(100) NOT NULL,
    state_code CHAR(2)      NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Properties
CREATE TABLE IF NOT EXISTS properties (
    id               CHAR(36)     NOT NULL PRIMARY KEY DEFAULT (UUID()),
    admin_id         CHAR(36)     NOT NULL,
    neighborhood_id  CHAR(36)     DEFAULT NULL,
    name             VARCHAR(200) NOT NULL,
    address          VARCHAR(300) NOT NULL,
    city             VARCHAR(100) NOT NULL,
    state_code       CHAR(2)      NOT NULL,
    zip_code         VARCHAR(10),
    is_active        TINYINT(1)   NOT NULL DEFAULT 1,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_prop_admin FOREIGN KEY (admin_id)       REFERENCES user_profiles(id) ON DELETE CASCADE,
    CONSTRAINT fk_prop_neigh FOREIGN KEY (neighborhood_id) REFERENCES neighborhoods(id) ON DELETE SET NULL,
    INDEX idx_prop_admin  (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Units
CREATE TABLE IF NOT EXISTS units (
    id           CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    property_id  CHAR(36)      NOT NULL,
    unit_number  VARCHAR(20)   NOT NULL,
    bedrooms     INT           NOT NULL DEFAULT 1,
    bathrooms    DECIMAL(3,1)  NOT NULL DEFAULT 1.0,
    square_feet  INT,
    monthly_rent DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_occupied  TINYINT(1)    NOT NULL DEFAULT 0,
    is_active    TINYINT(1)    NOT NULL DEFAULT 1,
    notes        TEXT,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_unit_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    UNIQUE KEY uq_unit (property_id, unit_number),
    INDEX idx_unit_prop (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tenants
CREATE TABLE IF NOT EXISTS tenants (
    id                      CHAR(36)  NOT NULL PRIMARY KEY DEFAULT (UUID()),
    user_id                 CHAR(36)  DEFAULT NULL,
    unit_id                 CHAR(36)  DEFAULT NULL,
    first_name              VARCHAR(100) NOT NULL,
    last_name               VARCHAR(100) NOT NULL,
    email                   VARCHAR(255) NOT NULL,
    phone                   VARCHAR(30),
    status                  ENUM('active','former','pending') NOT NULL DEFAULT 'active',
    score                   INT NOT NULL DEFAULT 100,
    score_notes             TEXT,
    move_in_date            DATE,
    move_out_date           DATE,
    emergency_contact_name  VARCHAR(150),
    emergency_contact_phone VARCHAR(30),
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tenant_user FOREIGN KEY (user_id) REFERENCES user_profiles(id) ON DELETE SET NULL,
    CONSTRAINT fk_tenant_unit FOREIGN KEY (unit_id) REFERENCES units(id)         ON DELETE SET NULL,
    INDEX idx_tenant_user   (user_id),
    INDEX idx_tenant_unit   (unit_id),
    INDEX idx_tenant_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Leases
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
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lease_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_lease_unit   FOREIGN KEY (unit_id)   REFERENCES units(id)   ON DELETE CASCADE,
    INDEX idx_lease_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Rent Charges
CREATE TABLE IF NOT EXISTS rent_charges (
    id           CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    tenant_id    CHAR(36)      NOT NULL,
    lease_id     CHAR(36)      NOT NULL,
    amount       DECIMAL(10,2) NOT NULL,
    due_date     DATE          NOT NULL,
    charge_month VARCHAR(7)    NOT NULL COMMENT 'YYYY-MM',
    status       ENUM('pending','paid','partial','overdue','waived') NOT NULL DEFAULT 'pending',
    notes        TEXT,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_charge_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_charge_lease  FOREIGN KEY (lease_id)  REFERENCES leases(id)  ON DELETE CASCADE,
    INDEX idx_charge_tenant (tenant_id),
    INDEX idx_charge_status (status),
    INDEX idx_charge_due    (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Rent Payments
CREATE TABLE IF NOT EXISTS rent_payments (
    id             CHAR(36)      NOT NULL PRIMARY KEY DEFAULT (UUID()),
    charge_id      CHAR(36)      NOT NULL,
    tenant_id      CHAR(36)      NOT NULL,
    amount_paid    DECIMAL(10,2) NOT NULL,
    payment_date   DATE          NOT NULL DEFAULT (CURRENT_DATE),
    payment_method ENUM('manual','check','cash','bank_transfer','online') NOT NULL DEFAULT 'manual',
    recorded_by    CHAR(36)      DEFAULT NULL,
    notes          TEXT,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pay_charge FOREIGN KEY (charge_id)   REFERENCES rent_charges(id)  ON DELETE CASCADE,
    CONSTRAINT fk_pay_tenant FOREIGN KEY (tenant_id)   REFERENCES tenants(id)        ON DELETE CASCADE,
    CONSTRAINT fk_pay_by     FOREIGN KEY (recorded_by) REFERENCES user_profiles(id)  ON DELETE SET NULL,
    INDEX idx_pay_charge (charge_id),
    INDEX idx_pay_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Maintenance Requests
CREATE TABLE IF NOT EXISTS maintenance_requests (
    id           CHAR(36)  NOT NULL PRIMARY KEY DEFAULT (UUID()),
    tenant_id    CHAR(36)  NOT NULL,
    unit_id      CHAR(36)  NOT NULL,
    title        VARCHAR(255) NOT NULL,
    description  TEXT,
    priority     ENUM('low','normal','high','emergency') NOT NULL DEFAULT 'normal',
    status       ENUM('open','in_process','materials_needed','completed','cancelled') NOT NULL DEFAULT 'open',
    assigned_to  VARCHAR(150),
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mr_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_mr_unit   FOREIGN KEY (unit_id)   REFERENCES units(id)   ON DELETE CASCADE,
    INDEX idx_mr_tenant (tenant_id),
    INDEX idx_mr_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Maintenance Updates (audit log)
CREATE TABLE IF NOT EXISTS maintenance_updates (
    id         CHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
    request_id CHAR(36) NOT NULL,
    updated_by CHAR(36) DEFAULT NULL,
    old_status VARCHAR(30),
    new_status VARCHAR(30) NOT NULL,
    note       TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mu_req  FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_mu_user FOREIGN KEY (updated_by)  REFERENCES user_profiles(id)       ON DELETE SET NULL,
    INDEX idx_mu_req (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Documents
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
    CONSTRAINT fk_doc_prop     FOREIGN KEY (property_id) REFERENCES properties(id)    ON DELETE SET NULL,
    CONSTRAINT fk_doc_uploader FOREIGN KEY (uploaded_by) REFERENCES user_profiles(id) ON DELETE SET NULL,
    INDEX idx_doc_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id           CHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
    user_id      CHAR(36) NOT NULL,
    type         ENUM('rent_reminder','rent_overdue','repair_update','repair_complete','general') NOT NULL,
    title        VARCHAR(255) NOT NULL,
    body         TEXT         NOT NULL,
    is_read      TINYINT(1)   NOT NULL DEFAULT 0,
    related_id   CHAR(36)     DEFAULT NULL,
    related_type VARCHAR(50)  DEFAULT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES user_profiles(id) ON DELETE CASCADE,
    INDEX idx_notif_user   (user_id),
    INDEX idx_notif_unread (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
