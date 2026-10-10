-- SpendWise Database Schema
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS spendwise;
USE spendwise;

-- 1. Users table for authentication and user preferences
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    currency VARCHAR(10) DEFAULT '₹',
    theme VARCHAR(10) DEFAULT 'light',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- In case users table already exists, ensure all columns exist:
ALTER TABLE users ADD COLUMN IF NOT EXISTS name VARCHAR(100);
ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(100);
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS currency VARCHAR(10) DEFAULT '₹';
ALTER TABLE users ADD COLUMN IF NOT EXISTS theme VARCHAR(10) DEFAULT 'light';
UPDATE users SET name = full_name WHERE (name IS NULL OR name = '') AND full_name IS NOT NULL;
UPDATE users SET full_name = name WHERE (full_name IS NULL OR full_name = '') AND name IS NOT NULL;

-- 2. Categories table (user_id is NULL for default system categories)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Payment Methods table (user_id is NULL for default system payment methods)
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    payment_method_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE RESTRICT
);

-- 5. Budgets table (category_id is NULL for overall monthly budget)
CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT DEFAULT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    alert_percent INT DEFAULT 80,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- In case budgets table already exists:
ALTER TABLE budgets ADD COLUMN IF NOT EXISTS month INT;
ALTER TABLE budgets ADD COLUMN IF NOT EXISTS year INT;

-- 6. Notifications table (for budget warning storage)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    budget_id INT DEFAULT NULL,
    message VARCHAR(255) NOT NULL,
    type VARCHAR(50) DEFAULT 'warning',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE
);

-- Insert Default System Categories (if not already present)
INSERT IGNORE INTO categories (id, user_id, name) VALUES
(1, NULL, 'Food & Dining'),
(2, NULL, 'Transportation'),
(3, NULL, 'Housing & Rent'),
(4, NULL, 'Utilities & Bills'),
(5, NULL, 'Entertainment'),
(6, NULL, 'Healthcare & Medical'),
(7, NULL, 'Shopping & Personal Care'),
(8, NULL, 'Education & Books');

-- Insert Default System Payment Methods (if not already present)
INSERT IGNORE INTO payment_methods (id, user_id, name) VALUES
(1, NULL, 'Cash'),
(2, NULL, 'Debit Card'),
(3, NULL, 'Credit Card'),
(4, NULL, 'UPI / Digital Wallet'),
(5, NULL, 'Net Banking');
