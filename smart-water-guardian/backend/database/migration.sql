-- Create Database
CREATE DATABASE IF NOT EXISTS smart_water_db;
USE smart_water_db;

-- Users Table
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Properties Table
CREATE TABLE properties (
    id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50),
    address TEXT NOT NULL,
    property_type ENUM('residential', 'commercial', 'industrial') DEFAULT 'residential',
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Meters Table
CREATE TABLE meters (
    id VARCHAR(50) PRIMARY KEY,
    meter_id VARCHAR(50) UNIQUE NOT NULL,
    property_id VARCHAR(50),
    model VARCHAR(50),
    firmware_version VARCHAR(20),
    install_date DATE,
    last_reading DECIMAL(10, 2) DEFAULT 0,
    last_reading_time DATETIME,
    battery_level DECIMAL(5, 2) DEFAULT 100,
    status ENUM('online', 'offline', 'error', 'maintenance') DEFAULT 'offline',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
);

-- Readings Table
CREATE TABLE readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meter_id VARCHAR(50),
    flow_rate DECIMAL(10, 2),
    volume DECIMAL(10, 2),
    battery_level DECIMAL(5, 2),
    reading_time DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meter_id) REFERENCES meters(id) ON DELETE CASCADE,
    INDEX idx_meter_reading (meter_id, reading_time)
);

-- Alerts Table
CREATE TABLE alerts (
    id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50),
    meter_id VARCHAR(50),
    alert_type ENUM('leak', 'critical_leak', 'threshold', 'battery_low', 'offline'),
    message TEXT NOT NULL,
    severity ENUM('info', 'warning', 'high', 'critical') DEFAULT 'info',
    status ENUM('active', 'acknowledged', 'resolved') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (meter_id) REFERENCES meters(id) ON DELETE CASCADE
);

-- Tariffs Table
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Water Supply Table (for municipalities)
CREATE TABLE water_supply (
    id INT AUTO_INCREMENT PRIMARY KEY,
    suburb VARCHAR(100),
    volume_supplied DECIMAL(10, 2),
    supply_date DATE NOT NULL,
    source VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- System Logs
CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50),
    action VARCHAR(100),
    details JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Admin
INSERT INTO users (id, name, surname, email, password, phone, address, role) 
VALUES ('ADMIN_001', 'System', 'Admin', 'admin@smartwater.co.za', 
        '$2y$12$3b5sMWRU/4I4zULaHHV.4u6P5VvRxzVEi1W8Yx2Z2IYTZt8RxqM6a', 
        '0123456789', 'Pretoria, South Africa', 'admin');

-- Insert Sample Tariffs
INSERT INTO tariffs (name, tier, min_usage, max_usage, rate_per_kl, effective_from) VALUES
('Residential Tariff', 1, 0, 6, 18.50, '2026-01-01'),
('Residential Tariff', 2, 6, 15, 22.00, '2026-01-01'),
('Residential Tariff', 3, 15, 30, 28.50, '2026-01-01'),
('Residential Tariff', 4, 30, 50, 35.00, '2026-01-01'),
('Residential Tariff', 5, 50, NULL, 45.00, '2026-01-01');
