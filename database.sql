CREATE DATABASE IF NOT EXISTS ghana_school;
USE ghana_school;

CREATE TABLE IF NOT EXISTS donors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,
    tier_name VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    is_recurring BOOLEAN DEFAULT TRUE,
    payment_gateway ENUM('stripe', 'paypal') NOT NULL,
    gateway_transaction_id VARCHAR(255) NULL,
    status ENUM('pending', 'active', 'completed', 'cancelled', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE CASCADE
);
