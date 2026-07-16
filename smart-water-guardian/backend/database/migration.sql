-- Create Database
CREATE DATABASE IF NOT EXISTS smart_water_db;
USE smart_water_db;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE users (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    role ENUM('consumer', 'municipal', 'admin', 'technician') DEFAULT 'consumer',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- ============================================
-- PROPERTIES TABLE
-- ============================================
CREATE TABLE properties (
    id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50),
    address TEXT NOT NULL,
    property_type ENUM('residential', 'commercial', 'industrial') DEFAULT 'residential',
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id)
);

-- ============================================
-- METERS TABLE
-- ============================================
CREATE TABLE meters (
    id VARCHAR(50) PRIMARY KEY,
    meter_id VARCHAR(50) UNIQUE NOT NULL,
    property_id VARCHAR(50),
    model VARCHAR(50) DEFAULT 'YF-S201',
    firmware_version VARCHAR(20) DEFAULT '1.0.0',
    install_date DATE,
    last_reading DECIMAL(10, 2) DEFAULT 0,
    last_reading_time DATETIME,
    battery_level DECIMAL(5, 2) DEFAULT 100,
    signal_strength DECIMAL(5, 2) DEFAULT 0,
    status ENUM('online', 'offline', 'error', 'maintenance') DEFAULT 'offline',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
    INDEX idx_meter_id (meter_id),
    INDEX idx_status (status)
);

-- ============================================
-- READINGS TABLE
-- ============================================
CREATE TABLE readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meter_id VARCHAR(50),
    flow_rate DECIMAL(10, 2) DEFAULT 0,
    volume DECIMAL(10, 2) DEFAULT 0,
    battery_level DECIMAL(5, 2) DEFAULT 100,
    reading_time DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meter_id) REFERENCES meters(id) ON DELETE CASCADE,
    INDEX idx_meter_reading (meter_id, reading_time),
    INDEX idx_reading_time (reading_time)
);

-- ============================================
-- ALERTS TABLE
-- ============================================
CREATE TABLE alerts (
    id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50),
    meter_id VARCHAR(50),
    alert_type ENUM('leak', 'critical_leak', 'threshold', 'battery_low', 'offline', 'high_usage') NOT NULL,
    message TEXT NOT NULL,
    severity ENUM('info', 'warning', 'high', 'critical') DEFAULT 'info',
    status ENUM('active', 'acknowledged', 'resolved') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    acknowledged_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (meter_id) REFERENCES meters(id) ON DELETE CASCADE,
    INDEX idx_user_alerts (user_id, status),
    INDEX idx_created (created_at)
);

-- ============================================
-- TARIFFS TABLE
-- ============================================
CREATE TABLE tariffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    tier INT NOT NULL,
    min_usage DECIMAL(10, 2) DEFAULT 0,
    max_usage DECIMAL(10, 2),
    rate_per_kl DECIMAL(10, 2) NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_effective (effective_from, effective_to)
);

-- ============================================
-- WATER SUPPLY TABLE
-- ============================================
CREATE TABLE water_supply (
    id INT AUTO_INCREMENT PRIMARY KEY,
    suburb VARCHAR(100) NOT NULL,
    volume_supplied DECIMAL(10, 2) NOT NULL,
    supply_date DATE NOT NULL,
    source VARCHAR(100) DEFAULT 'Municipal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_suburb_date (suburb, supply_date)
);

-- ============================================
-- BILLING TABLE
-- ============================================
CREATE TABLE billing (
    id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50),
    property_id VARCHAR(50),
    billing_month DATE NOT NULL,
    total_usage DECIMAL(10, 2) DEFAULT 0,
    total_amount DECIMAL(10, 2) DEFAULT 0,
    tariff_applied JSON,
    status ENUM('draft', 'finalized', 'paid', 'overdue') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finalized_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    INDEX idx_user_billing (user_id, billing_month),
    INDEX idx_status (status)
);

-- ============================================
-- SYSTEM LOGS TABLE
-- ============================================
CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50),
    action VARCHAR(100) NOT NULL,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_action (user_id, action),
    INDEX idx_created (created_at)
);

-- ============================================
-- NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE notifications (
    id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50),
    type ENUM('email', 'push', 'sms') DEFAULT 'push',
    title VARCHAR(255),
    message TEXT,
    data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id, is_read)
);

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- Insert Admin User
INSERT INTO users (id, name, surname, email, password, phone, address, role) 
VALUES (
    'ADMIN_001', 
    'System', 
    'Admin', 
    'admin@smartwater.co.za', 
    '$2y$12$3b5sMWRU/4I4zULaHHV.4u6P5VvRxzVEi1W8Yx2Z2IYTZt8RxqM6a', 
    '0123456789', 
    'Pretoria, South Africa', 
    'admin'
);
-- Password: Admin@2026

-- Insert Municipal User
INSERT INTO users (id, name, surname, email, password, phone, address, role) 
VALUES (
    'MUN_001', 
    'Municipal', 
    'Manager', 
    'municipal@smartwater.co.za', 
    '$2y$12$3b5sMWRU/4I4zULaHHV.4u6P5VvRxzVEi1W8Yx2Z2IYTZt8RxqM6a', 
    '0112345678', 
    'Johannesburg, South Africa', 
    'municipal'
);

-- Insert Sample Consumer
INSERT INTO users (id, name, surname, email, password, phone, address, role) 
VALUES (
    'USR_001', 
    'John', 
    'Doe', 
    'john@example.com', 
    '$2y$12$3b5sMWRU/4I4zULaHHV.4u6P5VvRxzVEi1W8Yx2Z2IYTZt8RxqM6a', 
    '0821234567', 
    '123 Main St, Sandton, Johannesburg', 
    'consumer'
);
-- Password: Admin@2026

-- Insert Sample Properties
INSERT INTO properties (id, user_id, address, property_type, latitude, longitude) VALUES
('PROP_001', 'USR_001', '123 Main St, Sandton, Johannesburg', 'residential', -26.1076, 28.0567),
('PROP_002', 'USR_001', '456 Oak Ave, Fourways, Johannesburg', 'residential', -26.0152, 28.0050);

-- Insert Sample Meters
INSERT INTO meters (id, meter_id, property_id, model, firmware_version, install_date, status, is_active) VALUES
('MTR_001', 'METER_001', 'PROP_001', 'YF-S201', '1.0.0', '2026-01-15', 'online', 1),
('MTR_002', 'METER_002', 'PROP_002', 'YF-S201', '1.0.0', '2026-02-01', 'online', 1);

-- Insert Sample Tariffs
INSERT INTO tariffs (name, tier, min_usage, max_usage, rate_per_kl, effective_from) VALUES
('Residential Tariff', 1, 0, 6, 18.50, '2026-01-01'),
('Residential Tariff', 2, 6, 15, 22.00, '2026-01-01'),
('Residential Tariff', 3, 15, 30, 28.50, '2026-01-01'),
('Residential Tariff', 4, 30, 50, 35.00, '2026-01-01'),
('Residential Tariff', 5, 50, NULL, 45.00, '2026-01-01');

-- Insert Sample Water Supply Data
INSERT INTO water_supply (suburb, volume_supplied, supply_date) VALUES
('Sandton', 125000, '2026-03-01'),
('Sandton', 130000, '2026-03-02'),
('Sandton', 128000, '2026-03-03'),
('Fourways', 95000, '2026-03-01'),
('Fourways', 98000, '2026-03-02'),
('Fourways', 92000, '2026-03-03'),
('Rosebank', 85000, '2026-03-01'),
('Rosebank', 87000, '2026-03-02'),
('Rosebank', 83000, '2026-03-03');

-- Insert Sample Readings
INSERT INTO readings (meter_id, flow_rate, volume, battery_level, reading_time) VALUES
('MTR_001', 12.5, 125.5, 85, '2026-03-12 10:00:00'),
('MTR_001', 10.2, 105.0, 84, '2026-03-12 10:01:00'),
('MTR_001', 8.5, 87.0, 84, '2026-03-12 10:02:00'),
('MTR_001', 15.0, 152.0, 83, '2026-03-12 10:03:00'),
('MTR_002', 5.5, 55.0, 92, '2026-03-12 10:00:00'),
('MTR_002', 4.8, 48.0, 92, '2026-03-12 10:01:00'),
('MTR_002', 6.2, 62.0, 91, '2026-03-12 10:02:00');
