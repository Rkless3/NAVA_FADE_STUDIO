-- =========================================================
-- NAVA FADE STUDIO
-- Complete Database Structure
-- =========================================================

CREATE DATABASE IF NOT EXISTS nava_fade_studio
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE nava_fade_studio;


-- =========================================================
-- DROP TABLES
-- Drop child tables first because of foreign keys
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;


-- =========================================================
-- USERS TABLE
-- Used for admin authentication
-- =========================================================

CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY unique_username (username)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- CUSTOMERS TABLE
-- Used for customer accounts
-- =========================================================

CREATE TABLE customers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    contact_number VARCHAR(11) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY unique_customer_email (email)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- SERVICES TABLE
-- =========================================================

CREATE TABLE services (
    id INT(11) NOT NULL AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration VARCHAR(50),
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- PRODUCTS TABLE
-- =========================================================

CREATE TABLE products (
    id INT(11) NOT NULL AUTO_INCREMENT,
    product_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT(11) NOT NULL DEFAULT 0,
    image VARCHAR(255),
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- APPOINTMENTS TABLE
-- =========================================================

CREATE TABLE appointments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    customer_id INT(11) NOT NULL,
    service VARCHAR(255) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    notes TEXT,
    status ENUM(
        'Pending',
        'Confirmed',
        'Completed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    CONSTRAINT fk_appointments_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- ORDERS TABLE
-- =========================================================

CREATE TABLE orders (
    id INT(11) NOT NULL AUTO_INCREMENT,
    customer_id INT(11) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM(
        'Pending',
        'Confirmed',
        'Processing',
        'Completed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- ORDER ITEMS TABLE
-- =========================================================

CREATE TABLE order_items (
    id INT(11) NOT NULL AUTO_INCREMENT,
    order_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,

    PRIMARY KEY (id),

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- PAYMENTS TABLE
-- IMPORTANT:
-- This table was missing from the old mysql.sql.
-- It is required by checkout.php and admin payment management.
-- =========================================================

CREATE TABLE payments (
    id INT(11) NOT NULL AUTO_INCREMENT,

    customer_id INT(11) NOT NULL,

    order_id INT(11) DEFAULT NULL,

    appointment_id INT(11) DEFAULT NULL,

    payment_method ENUM(
        'Cash',
        'GCash'
    ) NOT NULL,

    reference_number VARCHAR(100) DEFAULT NULL,

    receipt_image VARCHAR(255) DEFAULT NULL,

    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    status ENUM(
        'Pending',
        'Paid',
        'Failed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Pending',

    paid_at DATETIME DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX idx_payment_customer (customer_id),
    INDEX idx_payment_order (order_id),
    INDEX idx_payment_appointment (appointment_id),
    INDEX idx_payment_status (status),

    CONSTRAINT fk_payment_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_payment_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_payment_appointment
        FOREIGN KEY (appointment_id)
        REFERENCES appointments(id)
        ON DELETE CASCADE,

    CHECK (
        (order_id IS NOT NULL AND appointment_id IS NULL)
        OR
        (order_id IS NULL AND appointment_id IS NOT NULL)
    )
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- REVIEWS TABLE
-- =========================================================

CREATE TABLE reviews (
    id INT(11) NOT NULL AUTO_INCREMENT,
    customer_id INT(11) NOT NULL,
    rating TINYINT(1) NOT NULL,
    comment TEXT NOT NULL,

    status ENUM(
        'Pending',
        'Approved',
        'Hidden'
    ) NOT NULL DEFAULT 'Pending',

    is_featured TINYINT(1) NOT NULL DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    CONSTRAINT fk_reviews_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers(id)
        ON DELETE CASCADE,

    CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


-- =========================================================
-- SETTINGS TABLE
-- Used by admin settings page
-- =========================================================

CREATE TABLE settings (
    id INT(11) NOT NULL AUTO_INCREMENT,

    setting_key VARCHAR(100) NOT NULL,

    setting_value TEXT DEFAULT NULL,

    updated_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY unique_setting_key (setting_key)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- OPTIONAL DEFAULT SETTINGS
-- These are safe starting values for the settings page.
-- =========================================================

INSERT INTO settings
    (setting_key, setting_value)
VALUES
    ('site_name', 'NAVA Fade Studio'),
    ('site_email', ''),
    ('site_contact', ''),
    ('site_address', ''),
    ('facebook', ''),
    ('instagram', ''),
    ('business_hours', 'Monday - Sunday'),
    ('about_text', 'NAVA Fade Studio provides quality grooming services and products.'),
    ('maintenance_mode', '0');


-- =========================================================
-- END OF DATABASE
-- =========================================================