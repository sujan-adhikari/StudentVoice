-- ====================================================================
-- College Complaint Management System (TU BCA 4th Semester Project I)
-- Database DDL & Seed Data Script
-- Engine: MySQL / MariaDB (Compatible with XAMPP phpMyAdmin)
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `college_complaint_system` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `college_complaint_system`;

-- --------------------------------------------------------------------
-- 1. Table: users
-- Stores both students and administrators with role-based segregation.
-- --------------------------------------------------------------------
DROP TABLE IF EXISTS `complaint_logs`;
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    `phone` VARCHAR(20) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 2. Table: categories
-- Represents different administrative and physical departments of the college.
-- --------------------------------------------------------------------
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 3. Table: complaints
-- Stores all complaints lodged by students.
-- --------------------------------------------------------------------
CREATE TABLE `complaints` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `complaint_number` VARCHAR(30) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `image` VARCHAR(255) NULL,
    `status` ENUM('Pending', 'In Progress', 'Resolved', 'Rejected') NOT NULL DEFAULT 'Pending',
    `admin_remark` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_complaints_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_complaints_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 4. Table: complaint_logs
-- Simple timeline history tracking changes made to complaint statuses.
-- --------------------------------------------------------------------
CREATE TABLE `complaint_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `complaint_id` INT NOT NULL,
    `updated_by` INT NOT NULL,
    `previous_status` VARCHAR(50) NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `remark` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_logs_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_logs_user` FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SEED DATA
-- ====================================================================

-- Default Categories
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Infrastructure & Facilities', 'Problems related to benches, doors, fans, water dispensers, and washrooms.'),
(2, 'Computer Labs & Hardware', 'Issues with lab computers, mice, keyboards, projectors, and operating systems.'),
(3, 'Internet & Wi-Fi', 'Campus Wi-Fi connectivity, speed issues, or portal login access problems.'),
(4, 'Library Services', 'Issues related to book availability, library card processing, and quiet reading areas.'),
(5, 'Hostel & Mess', 'Problems regarding hostel rooms, electricity, hot water, and mess food hygiene.'),
(6, 'Academic & Faculty', 'Class scheduling conflicts, syllabus inquiries, and academic notice clarifications.'),
(7, 'Administration & Accounts', 'Fee receipts, ID card issuance, character certificates, and office delays.'),
(8, 'Other / Miscellaneous', 'General issues not covered in the above categories.');

-- Default Demo Accounts (Passwords: 'admin123' and 'student123')
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`) VALUES
(1, 'System Administrator', 'admin@college.edu', '$2y$10$jNNNmSKxj9CuSJum6vlPtO3pXVOCh6BvtkoRPQLymOVE/sUFNtzHu', 'admin', '9800000000'),
(2, 'Sujan Adhikari', 'student@college.edu', '$2y$10$reh7TRP4jwg21Vd/CQZRKO0rzIeKkahGuX9L74tFCXTPIa8fX8vR6', 'student', '9811111111');

-- Sample Complaints for Testing and Demonstrating Dashboards
INSERT INTO `complaints` (`id`, `complaint_number`, `user_id`, `category_id`, `title`, `description`, `image`, `status`, `admin_remark`, `created_at`) VALUES
(1, 'CMP-2026-0001', 2, 2, 'Lab 2 - PC 14 Screen Glitching and No Internet', 'The monitor on Computer 14 in BCA Lab 2 keeps flickering green and does not connect to the LAN cable. Unable to complete Data Structures lab tasks.', NULL, 'In Progress', 'Hardware technician assigned. Replacing VGA cable and NIC connector.', NOW() - INTERVAL 3 DAY),
(2, 'CMP-2026-0002', 2, 1, 'Ceiling Fan in Room 304 Making Loud Noise', 'The second ceiling fan on the left row of Room 304 makes a continuous squeaking noise during lectures, causing disturbance to students and lecturers.', NULL, 'Resolved', 'Maintenance team lubricated the bearings and tightened the mounting bracket on Tuesday.', NOW() - INTERVAL 7 DAY),
(3, 'CMP-2026-0003', 2, 3, 'Library 2nd Floor Wi-Fi Signal Dropping', 'Wi-Fi signal drops frequently in the reference section of the 2nd floor library. Students cannot download online journals.', NULL, 'Pending', NULL, NOW() - INTERVAL 1 DAY);

-- Sample Complaint Logs
INSERT INTO `complaint_logs` (`complaint_id`, `updated_by`, `previous_status`, `new_status`, `remark`, `created_at`) VALUES
(1, 1, 'Pending', 'In Progress', 'Hardware technician assigned. Replacing VGA cable and NIC connector.', NOW() - INTERVAL 2 DAY),
(2, 1, 'Pending', 'In Progress', 'Notified electrician and campus maintenance team.', NOW() - INTERVAL 6 DAY),
(2, 1, 'In Progress', 'Resolved', 'Maintenance team lubricated the bearings and tightened the mounting bracket on Tuesday.', NOW() - INTERVAL 5 DAY);
