-- ============================================================================
-- CSE 391: Programming for the Internet
-- Assignment 3: PHP and MySQL Project - Car Workshop Appointment System
-- Database Schema & Initial Seed Data
-- ============================================================================

CREATE DATABASE IF NOT EXISTS cse391_workshop
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cse391_workshop;

-- ----------------------------------------------------------------------------
-- 1. Table: mechanics (5 Senior Mechanics, max 4 active cars per day each)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS mechanics;

CREATE TABLE mechanics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialty VARCHAR(120) NOT NULL,
    experience_years INT NOT NULL DEFAULT 10,
    phone VARCHAR(20) NOT NULL,
    max_daily_slots INT NOT NULL DEFAULT 4,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert the 5 Senior Mechanics
INSERT INTO mechanics (id, name, specialty, experience_years, phone, max_daily_slots) VALUES
(1, 'Tariqul Islam', 'Engine Diagnostics & Turbo Systems', 14, '01711223344', 4),
(2, 'Rafiqul Hasan', 'Transmission & Drivetrain Specialist', 12, '01711223355', 4),
(3, 'Kamrul Ahsan', 'Hybrid/EV & Electrical Architecture', 11, '01711223366', 4),
(4, 'Shafiqur Rahman', 'Suspension, Steering & Brake Systems', 15, '01711223377', 4),
(5, 'Mahbub Alam', 'ECU Tuning, Fuel Injection & Cooling', 10, '01711223388', 4);

-- ----------------------------------------------------------------------------
-- 2. Table: appointments
-- Stores client details, car registration (license) number, car engine number,
-- appointment date, and assigned mechanic.
-- ----------------------------------------------------------------------------
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(120) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    car_license_no VARCHAR(50) NOT NULL,
    car_engine_no VARCHAR(50) NOT NULL,
    appointment_date DATE NOT NULL,
    mechanic_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointment_mechanic
        FOREIGN KEY (mechanic_id) REFERENCES mechanics(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_date_mechanic (appointment_date, mechanic_id),
    INDEX idx_client_date (appointment_date, phone, car_license_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample initial appointments for demonstration
INSERT INTO appointments (client_name, address, phone, car_license_no, car_engine_no, appointment_date, mechanic_id) VALUES
('Tanvir Ahmed', 'House 14, Road 7, Dhanmondi, Dhaka', '01712345678', 'DHAKA-METRO-GA-32-4411', '884920194', CURDATE(), 1),
('Nusrat Jahan', 'Block C, Road 11, Banani, Dhaka', '01819876543', 'DHAKA-METRO-GA-41-9022', '553019284', CURDATE(), 1),
('Farhan Kabir', 'Sector 4, Road 9, Uttara, Dhaka', '01911223399', 'DHAKA-METRO-CHA-15-7788', '991028374', CURDATE(), 2),
('Sadia Afrin', 'Bashundhara R/A, Block D, Dhaka', '01615556677', 'DHAKA-METRO-GA-29-1105', '334819201', CURDATE(), 3),
('Mehedi Hasan', 'Mirpur DOHS, Avenue 3, Dhaka', '01518889900', 'DHAKA-METRO-GA-38-6612', '774102938', CURDATE(), 1);
