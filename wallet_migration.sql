-- Wallet & MFS Recharge System Tables
-- Run this SQL file to create the necessary tables for the wallet system

-- Create balance table
CREATE TABLE IF NOT EXISTS balance (
    u_id VARCHAR(50) PRIMARY KEY,
    current_balance DECIMAL(10, 2) DEFAULT 0.00,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (u_id) REFERENCES user(u_id) ON DELETE CASCADE
);

-- Create recharge_history table
CREATE TABLE IF NOT EXISTS recharge_history (
    r_id INT AUTO_INCREMENT PRIMARY KEY,
    u_id VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_id VARCHAR(20) NOT NULL UNIQUE,
    method VARCHAR(50) NOT NULL COMMENT 'bKash, Nagad, etc.',
    status VARCHAR(20) DEFAULT 'Success' COMMENT 'Success, Pending, Failed',
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (u_id) REFERENCES user(u_id) ON DELETE CASCADE,
    INDEX idx_u_id (u_id),
    INDEX idx_date (date)
);

-- Create transaction log table for tracking all wallet operations
CREATE TABLE IF NOT EXISTS wallet_transaction_log (
    trans_id INT AUTO_INCREMENT PRIMARY KEY,
    u_id VARCHAR(50) NOT NULL,
    transaction_type VARCHAR(50) NOT NULL COMMENT 'recharge, booking, food_order, cancellation',
    reference_id VARCHAR(50),
    amount DECIMAL(10, 2) NOT NULL,
    operation VARCHAR(10) NOT NULL COMMENT 'credit, debit',
    balance_before DECIMAL(10, 2),
    balance_after DECIMAL(10, 2),
    status VARCHAR(20) DEFAULT 'Success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (u_id) REFERENCES user(u_id) ON DELETE CASCADE,
    INDEX idx_u_id (u_id),
    INDEX idx_created_at (created_at)
);

-- Add wallet column to existing tables if not present
ALTER TABLE booking ADD COLUMN IF NOT EXISTS paid_from_wallet TINYINT DEFAULT 0;
ALTER TABLE booking ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'Pending';

ALTER TABLE food_order ADD COLUMN IF NOT EXISTS paid_from_wallet TINYINT DEFAULT 0;
ALTER TABLE food_order ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'Pending';

-- Initialize balance for existing users
INSERT INTO balance (u_id, current_balance)
SELECT u_id, 0.00 FROM user 
WHERE u_id NOT IN (SELECT u_id FROM balance)
ON DUPLICATE KEY UPDATE current_balance = current_balance;
