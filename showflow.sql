-- ShowFlow Database Schema


-- Users & Authentication
CREATE TABLE IF NOT EXISTS developer (
  d_id INT PRIMARY KEY AUTO_INCREMENT,
  password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS theatre (
  t_id INT PRIMARY KEY AUTO_INCREMENT,
  theatre_name VARCHAR(255) UNIQUE NOT NULL,
  location VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS manager (
  m_id VARCHAR(50) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  contact VARCHAR(20) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  t_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (t_id) REFERENCES theatre(t_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user (
  u_id VARCHAR(50) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  contact VARCHAR(20) UNIQUE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Content Management
CREATE TABLE IF NOT EXISTS movie (
  mov_id INT PRIMARY KEY AUTO_INCREMENT,
  mov_name VARCHAR(255) NOT NULL,
  mov_poster VARCHAR(500),
  mov_trailer VARCHAR(500),
  mov_synopsis TEXT,
  mov_genre VARCHAR(100),
  mov_duration INT,
  mov_release_date DATE,
  mov_description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS hall (
  h_id INT PRIMARY KEY AUTO_INCREMENT,
  t_id INT NOT NULL,
  hall_name VARCHAR(255) NOT NULL,
  total_rows INT NOT NULL,
  total_columns INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (t_id) REFERENCES theatre(t_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS show_schedule (
  s_id INT PRIMARY KEY AUTO_INCREMENT,
  mov_id INT NOT NULL,
  t_id INT NOT NULL,
  h_id INT NOT NULL,
  show_date DATE NOT NULL,
  show_time TIME NOT NULL,
  ticket_price DECIMAL(10, 2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mov_id) REFERENCES movie(mov_id) ON DELETE CASCADE,
  FOREIGN KEY (t_id) REFERENCES theatre(t_id) ON DELETE CASCADE,
  FOREIGN KEY (h_id) REFERENCES hall(h_id) ON DELETE CASCADE
);

-- Transactions & Booking
CREATE TABLE IF NOT EXISTS booking (
  book_id INT PRIMARY KEY AUTO_INCREMENT,
  u_id VARCHAR(50) NOT NULL,
  s_id INT NOT NULL,
  seat_numbers TEXT NOT NULL,
  total_amount DECIMAL(10, 2) NOT NULL,
  status VARCHAR(50) DEFAULT 'Confirmed',
  booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (u_id) REFERENCES user(u_id) ON DELETE CASCADE,
  FOREIGN KEY (s_id) REFERENCES show_schedule(s_id) ON DELETE CASCADE
);

-- Reviews & Feedback
CREATE TABLE IF NOT EXISTS review (
  rev_id INT PRIMARY KEY AUTO_INCREMENT,
  mov_id INT NOT NULL,
  u_id VARCHAR(50) NOT NULL,
  comment TEXT NOT NULL,
  rating INT CHECK (rating >= 1 AND rating <= 5),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mov_id) REFERENCES movie(mov_id) ON DELETE CASCADE,
  FOREIGN KEY (u_id) REFERENCES user(u_id) ON DELETE CASCADE
);

-- Concessions
CREATE TABLE IF NOT EXISTS food_item (
  food_id INT PRIMARY KEY AUTO_INCREMENT,
  t_id INT NOT NULL,
  food_name VARCHAR(255) NOT NULL,
  price DECIMAL(10, 2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (t_id) REFERENCES theatre(t_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS food_order (
  order_id INT PRIMARY KEY AUTO_INCREMENT,
  t_id INT NOT NULL,
  u_id VARCHAR(50) NOT NULL,
  food_id INT NOT NULL,
  quantity INT NOT NULL,
  total_price DECIMAL(10, 2),
  status VARCHAR(50) DEFAULT 'Pending',
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (t_id) REFERENCES theatre(t_id) ON DELETE CASCADE,
  FOREIGN KEY (u_id) REFERENCES user(u_id) ON DELETE CASCADE,
  FOREIGN KEY (food_id) REFERENCES food_item(food_id) ON DELETE CASCADE
);

-- Financial Management
CREATE TABLE IF NOT EXISTS contract (
  contract_id INT PRIMARY KEY AUTO_INCREMENT,
  t_id INT NOT NULL,
  mov_id INT NOT NULL,
  one_time_cost DECIMAL(10, 2) NOT NULL,
  percentage_per_ticket DECIMAL(5, 2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (t_id) REFERENCES theatre(t_id) ON DELETE CASCADE,
  FOREIGN KEY (mov_id) REFERENCES movie(mov_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS expense (
  ex_id INT PRIMARY KEY AUTO_INCREMENT,
  t_id INT NOT NULL,
  ex_date DATE NOT NULL,
  ex_reason VARCHAR(255) NOT NULL,
  cost DECIMAL(10, 2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (t_id) REFERENCES theatre(t_id) ON DELETE CASCADE
);

-- Support & Complaints
CREATE TABLE IF NOT EXISTS complaint (
  comp_id INT PRIMARY KEY AUTO_INCREMENT,
  t_id INT NOT NULL,
  u_id VARCHAR(50) NOT NULL,
  comp_date DATE NOT NULL,
  complaint_text TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (t_id) REFERENCES theatre(t_id) ON DELETE CASCADE,
  FOREIGN KEY (u_id) REFERENCES user(u_id) ON DELETE CASCADE
);

-- Notifications
CREATE TABLE IF NOT EXISTS user_notification (
  notif_id INT PRIMARY KEY AUTO_INCREMENT,
  u_id VARCHAR(50) NOT NULL,
  m_id VARCHAR(50),
  message TEXT NOT NULL,
  notif_type VARCHAR(50),
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (u_id) REFERENCES user(u_id) ON DELETE CASCADE,
  FOREIGN KEY (m_id) REFERENCES manager(m_id) ON DELETE SET NULL,
  INDEX idx_u_id (u_id),
  INDEX idx_created_at (created_at)
);


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


-- Insert default developer
INSERT IGNORE INTO developer (d_id, password) VALUES (1, '1234');
