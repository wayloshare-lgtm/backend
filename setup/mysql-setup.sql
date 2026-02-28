-- WayloShare MySQL Database Setup
-- Run as: mysql -u root -p < mysql-setup.sql

-- Create database
CREATE DATABASE IF NOT EXISTS wayloshare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER IF NOT EXISTS 'wayloshare_user'@'localhost' IDENTIFIED BY 'CHANGE_THIS_PASSWORD';

-- Grant privileges
GRANT ALL PRIVILEGES ON wayloshare.* TO 'wayloshare_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Verify
SELECT User, Host FROM mysql.user WHERE User = 'wayloshare_user';
SHOW GRANTS FOR 'wayloshare_user'@'localhost';
