-- SpendWise Database Schema
-- Compatible with both Local XAMPP and InfinityFree / cPanel phpMyAdmin

-- NOTE FOR LOCAL XAMPP:
-- If creating database on XAMPP for the first time, you can uncomment these two lines:
-- CREATE DATABASE IF NOT EXISTS spendwise;
-- USE spendwise;

-- NOTE FOR INFINITYFREE:
-- In InfinityFree Control Panel -> Create Database first (e.g. if0_xxxxxxx_spendwise),
-- then open phpMyAdmin, select that database on the left, and click 'Import' to run this file.

-- 1. Users table for authentication and user preferences
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `currency` VARCHAR(10) DEFAULT '₹',
    `theme` VARCHAR(10) DEFAULT 'light',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Categories table (user_id is NULL for default active categories)
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Payment Methods table (user_id is NULL for default active payment methods)
CREATE TABLE IF NOT EXISTS `payment_methods` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Expenses table
CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `payment_method_id` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `expense_date` DATE NOT NULL,
    `description` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Budgets table (category_id is NULL for overall monthly budget)
CREATE TABLE IF NOT EXISTS `budgets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT DEFAULT NULL,
    `month` INT NOT NULL,
    `year` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `alert_percent` INT DEFAULT 80,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Notifications table (for budget warning storage)
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `budget_id` INT DEFAULT NULL,
    `message` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) DEFAULT 'warning',
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`budget_id`) REFERENCES `budgets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Default Starter Categories
INSERT IGNORE INTO `categories` (`id`, `user_id`, `name`) VALUES
(1, NULL, 'Food & Dining'),
(2, NULL, 'Transportation'),
(3, NULL, 'Housing & Rent'),
(4, NULL, 'Utilities & Bills'),
(5, NULL, 'Entertainment'),
(6, NULL, 'Healthcare & Medical'),
(7, NULL, 'Shopping & Personal Care'),
(8, NULL, 'Education & Books');

-- Insert Default Starter Payment Methods
INSERT IGNORE INTO `payment_methods` (`id`, `user_id`, `name`) VALUES
(1, NULL, 'Cash'),
(2, NULL, 'Debit Card'),
(3, NULL, 'Credit Card'),
(4, NULL, 'UPI / Digital Wallet'),
(5, NULL, 'Net Banking');
