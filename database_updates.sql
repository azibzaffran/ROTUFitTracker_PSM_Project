-- Database updates for ROTUFitTracker
-- Run this SQL in phpMyAdmin or MySQL command line

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS rotufit_tracker_db;
USE rotufit_tracker_db;

-- Create users table if not exists
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    height DECIMAL(5,2) DEFAULT NULL COMMENT 'Height in cm',
    weight DECIMAL(5,2) DEFAULT NULL COMMENT 'Weight in kg',
    profile_picture VARCHAR(255) DEFAULT NULL COMMENT 'Profile picture filename'
);

-- If users table already exists, add missing columns
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS height DECIMAL(5,2) DEFAULT NULL COMMENT 'Height in cm';

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS weight DECIMAL(5,2) DEFAULT NULL COMMENT 'Weight in kg';

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL COMMENT 'Profile picture filename';

-- Create training schedules table
CREATE TABLE IF NOT EXISTS training_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Create training types table (for junior, intermediate, senior levels)
CREATE TABLE IF NOT EXISTS training_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    level ENUM('junior', 'intermediate', 'senior') NOT NULL,
    instructor_id INT DEFAULT NULL,
    location VARCHAR(255) NOT NULL,
    FOREIGN KEY (schedule_id) REFERENCES training_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create password reset tokens table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
);
