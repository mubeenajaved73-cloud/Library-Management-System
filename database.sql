-- ============================================================
-- Library Management System - Database Schema
-- Software Verification & Validation - Semester Project
-- Task 1 + Task 2
-- ============================================================

CREATE DATABASE IF NOT EXISTS library_system;
USE library_system;

-- ------------------------------------------------------------
-- Table: users
-- Stores both Admin (Librarian) and Member accounts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    role ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Table: books
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    author VARCHAR(100) NOT NULL,
    isbn VARCHAR(30) NOT NULL UNIQUE,
    category VARCHAR(60) DEFAULT 'General',
    publisher VARCHAR(100) DEFAULT NULL,
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    shelf_location VARCHAR(50) DEFAULT NULL,
    added_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Table: transactions
-- Records every issue / return operation (drives Task 2's
-- "library transaction" requirement and future Task 3-5 test
-- scenarios around issue/return/overdue logic)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    status ENUM('issued', 'returned', 'overdue') NOT NULL DEFAULT 'issued',
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Seed data: one admin account + a few sample books
-- Default admin password is "admin123" (hashed below with
-- PHP password_hash - bcrypt). Change after first login.
-- ------------------------------------------------------------
-- Password hash below corresponds to plaintext: admin123
-- (bcrypt hash - verified compatible with PHP's password_verify())
INSERT INTO users (full_name, email, password, role, status) VALUES
('System Administrator', 'admin@library.com', '$2b$12$rqpn4Mw1nt4KaIrDO3Vojur1QisqvGt8fyGjXTbcmoaJUIyY1w4fC', 'admin', 'active');

INSERT INTO books (title, author, isbn, category, publisher, total_copies, available_copies, shelf_location) VALUES
('Clean Code', 'Robert C. Martin', '9780132350884', 'Software Engineering', 'Prentice Hall', 3, 3, 'A1-01'),
('Introduction to Algorithms', 'Cormen, Leiserson, Rivest, Stein', '9780262033848', 'Computer Science', 'MIT Press', 2, 2, 'A1-02'),
('The Pragmatic Programmer', 'David Thomas, Andrew Hunt', '9780135957059', 'Software Engineering', 'Addison-Wesley', 2, 2, 'A1-03'),
('Database System Concepts', 'Silberschatz, Korth, Sudarshan', '9780078022159', 'Databases', 'McGraw-Hill', 4, 4, 'A2-01'),
('Software Testing: A Craftsman''s Approach', 'Paul C. Jorgensen', '9781466560680', 'Software Testing', 'CRC Press', 2, 2, 'A2-02');
