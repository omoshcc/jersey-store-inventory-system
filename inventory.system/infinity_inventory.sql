-- ============================================================
--  INFINITY SPORTSWEAR HUB - INVENTORY MANAGEMENT SYSTEM
--  Database: inventory_system
--  Import this file via phpMyAdmin > Import
-- ============================================================

CREATE DATABASE IF NOT EXISTS inventory_system;
USE inventory_system;

-- ============================================================
-- TABLE 1: USERS (no pre-made accounts - set up yourself)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Manager', 'Storekeeper', 'Sales Assistant') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE 2: SUPPLIERS
-- ============================================================
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT
);

-- ============================================================
-- TABLE 3: INVENTORY (jerseys - team, size, colour, category)
-- ============================================================
CREATE TABLE IF NOT EXISTS inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(100) NOT NULL,
    size VARCHAR(10) NOT NULL,
    colour VARCHAR(30) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    reorder_point INT NOT NULL DEFAULT 5,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    category CHAR(1) NOT NULL DEFAULT 'B' COMMENT 'A=High Value, B=Medium, C=Low Value',
    supplier_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE 4: CUSTOMERS
-- ============================================================
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT
);

-- ============================================================
-- TABLE 5: TRANSACTIONS (sales)
-- ============================================================
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    customer_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    quantity_sold INT NOT NULL,
    sale_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory(inventory_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE 6: STOCK DELIVERIES (restocking)
-- ============================================================
CREATE TABLE IF NOT EXISTS stock_deliveries (
    delivery_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity_received INT NOT NULL,
    delivery_date DATE NOT NULL,
    received_by INT NOT NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES inventory(inventory_id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE 7: PURCHASE ORDERS
-- ============================================================
CREATE TABLE IF NOT EXISTS purchase_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    inventory_id INT NOT NULL,
    ordered_by INT NOT NULL,
    quantity_ordered INT NOT NULL,
    order_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'Pending' COMMENT 'Pending, Approved, Delivered, Cancelled',
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES inventory(inventory_id) ON DELETE CASCADE,
    FOREIGN KEY (ordered_by) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE 8: SUPPLIER PAYMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS supplier_payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    delivery_id INT NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'M-Pesa',
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_id) REFERENCES stock_deliveries(delivery_id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE 9: LOW STOCK ALERTS
-- ============================================================
CREATE TABLE IF NOT EXISTS low_stock_alerts (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    triggered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved BOOLEAN DEFAULT FALSE,
    resolved_at DATETIME DEFAULT NULL,
    FOREIGN KEY (inventory_id) REFERENCES inventory(inventory_id) ON DELETE CASCADE
);

-- ============================================================
-- DONE. No accounts created - set them up yourself via the
-- Sign Up page on login.php
-- ============================================================

-- ============================================================
-- TABLE 10: USER TESTING FEEDBACK
-- ============================================================
CREATE TABLE IF NOT EXISTS user_feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ease_of_use INT NOT NULL COMMENT '1-5 rating',
    usefulness INT NOT NULL COMMENT '1-5 rating',
    speed INT NOT NULL COMMENT '1-5 rating',
    accuracy INT NOT NULL COMMENT '1-5 rating',
    overall INT NOT NULL COMMENT '1-5 rating',
    comments TEXT,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================================
-- ALTER USERS TABLE: Add birth year for password reset
-- Run this if you already imported the database previously
-- ============================================================
ALTER TABLE users ADD COLUMN IF NOT EXISTS birth_year CHAR(4) DEFAULT NULL;
