-- schema.sql
-- Complete initial setup script for Drowning Fish Rescue house records database
-- Run this entire file as one script in phpMyAdmin, MySQL Workbench, or mysql client

-- 1. Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS house_info
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- 2. Switch to the database (prevents "No database selected" errors)
USE house_info;

-- =============================================================================
-- HOUSES (main entities)
-- =============================================================================
CREATE TABLE IF NOT EXISTS houses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT DEFAULT NULL,
    latitude DECIMAL(10,8) DEFAULT NULL,
    longitude DECIMAL(11,8) DEFAULT NULL,
    tax_number VARCHAR(50) DEFAULT NULL,
    map_zoom TINYINT UNSIGNED DEFAULT 20
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- PERMANENT ITEMS (furnace, AC, etc.)
-- =============================================================================
CREATE TABLE IF NOT EXISTS permanent_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    item_type ENUM('furnace','water_heater','dishwasher','washer','dryer','ac') NOT NULL,
    brand VARCHAR(100) DEFAULT NULL,
    model VARCHAR(100) DEFAULT NULL,
    sn VARCHAR(100) DEFAULT NULL,
    efficiency VARCHAR(50) DEFAULT NULL,
    kwh DECIMAL(6,2) DEFAULT 0.00,
    capacity INT DEFAULT 0,
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_item_per_house (house_id, item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- ELECTRIC METER
-- =============================================================================
CREATE TABLE IF NOT EXISTS electric_meters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    meter_number VARCHAR(50) DEFAULT NULL,
    company VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- GENERATOR
-- =============================================================================
CREATE TABLE IF NOT EXISTS generators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    brand VARCHAR(100) DEFAULT NULL,
    model VARCHAR(100) DEFAULT NULL,
    sn VARCHAR(100) DEFAULT NULL,
    efficiency VARCHAR(50) DEFAULT NULL,
    kwh DECIMAL(6,2) DEFAULT 0.00,
    fuel_type ENUM('LP','NG') DEFAULT 'LP',
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SOLAR INVERTER
-- =============================================================================
CREATE TABLE IF NOT EXISTS solar_inverters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    brand VARCHAR(100) DEFAULT NULL,
    model VARCHAR(100) DEFAULT NULL,
    sn VARCHAR(100) DEFAULT NULL,
    kwh DECIMAL(6,2) DEFAULT 0.00,
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SOLAR STRINGS & PANELS
-- =============================================================================
CREATE TABLE IF NOT EXISTS solar_strings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    connection_type ENUM('Series','Parallel') NOT NULL DEFAULT 'Series',
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS solar_panels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    string_id INT NOT NULL,
    brand VARCHAR(100) NOT NULL,
    watts INT NOT NULL,
    FOREIGN KEY (string_id) REFERENCES solar_strings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- BATTERY STRINGS & BATTERIES
-- =============================================================================
CREATE TABLE IF NOT EXISTS battery_strings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    connection_type ENUM('Series','Parallel') NOT NULL DEFAULT 'Parallel',
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS batteries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    string_id INT NOT NULL,
    brand VARCHAR(100) NOT NULL,
    watts INT NOT NULL,
    FOREIGN KEY (string_id) REFERENCES battery_strings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- ELECTRIC PANELS & BREAKERS
-- =============================================================================
CREATE TABLE IF NOT EXISTS electric_panels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    spaces TINYINT UNSIGNED NOT NULL DEFAULT 28,
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS breakers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    panel_id INT NOT NULL,
    column_num TINYINT NOT NULL,
    row_num TINYINT NOT NULL,
    room VARCHAR(100) DEFAULT NULL,
    amp INT DEFAULT 0,
    FOREIGN KEY (panel_id) REFERENCES electric_panels(id) ON DELETE CASCADE,
    UNIQUE KEY unique_breaker_position (panel_id, column_num, row_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- HOUSEHOLD ITEMS
-- =============================================================================
CREATE TABLE IF NOT EXISTS household_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    type ENUM('TV','Server','Other') NOT NULL DEFAULT 'TV',
    brand VARCHAR(100) DEFAULT NULL,
    model VARCHAR(100) DEFAULT NULL,
    sn VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- PHOTOS
-- =============================================================================
CREATE TABLE IF NOT EXISTS photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    section ENUM('Interior','Exterior') NOT NULL,
    filename VARCHAR(255) NOT NULL,
    is_ir TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = infrared scan',
    upload_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DESIGNS / PLANS / DRAWINGS
-- =============================================================================
CREATE TABLE IF NOT EXISTS designs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    upload_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- INITIAL DATA (safe to run multiple times)
-- =============================================================================

-- Insert the two houses if they don't exist
INSERT IGNORE INTO houses (id, name) VALUES 
(1, 'Main House'),
(2, 'Guest House');

-- Insert default rows for permanent items in both houses
INSERT IGNORE INTO permanent_items (house_id, item_type)
SELECT h.id, t.item_type
FROM houses h
CROSS JOIN (
    SELECT 'furnace' AS item_type UNION SELECT 'water_heater' UNION SELECT 'dishwasher'
    UNION SELECT 'washer' UNION SELECT 'dryer' UNION SELECT 'ac'
) t;

-- Optional: set default map zoom if needed
UPDATE houses SET map_zoom = 20 WHERE map_zoom IS NULL OR map_zoom < 20;
