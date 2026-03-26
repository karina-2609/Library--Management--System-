-- ============================================================
-- EduLib ADVANCED - Library Management System Schema
-- Includes Fines, Edition, Publisher, Students, and Librarians
-- ============================================================

CREATE DATABASE IF NOT EXISTS library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_db;

-- ----------------------------------------------------------------
-- 1. Table: books
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS books (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(255) NOT NULL,
    author        VARCHAR(255) NOT NULL,
    isbn          VARCHAR(50)  UNIQUE,
    edition       VARCHAR(50)  DEFAULT '1st Edition',   -- NEW
    publisher     VARCHAR(150) DEFAULT 'Unknown',       -- NEW
    category      VARCHAR(100) DEFAULT 'General',
    cover_url     VARCHAR(500) DEFAULT '',
    total_copies  INT          DEFAULT 1,
    available     INT          DEFAULT 1,
    added_on      DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- 2. Table: students
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  VARCHAR(50)  NOT NULL UNIQUE,
    name        VARCHAR(255) NOT NULL,
    email       VARCHAR(255),
    course      VARCHAR(150) DEFAULT '',
    phone       VARCHAR(20)  DEFAULT '',
    joined_on   DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- 3. Table: librarians (Admins)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS librarians (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    emp_id        VARCHAR(50)  NOT NULL UNIQUE,
    name          VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL,
    phone         VARCHAR(20)  DEFAULT '',
    qualification VARCHAR(150) DEFAULT 'MLIS',
    experience    INT          DEFAULT 0,     -- Years of experience
    joined_on     DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- 4. Table: issued_books
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS issued_books (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    book_id      INT NOT NULL,
    student_id   INT NOT NULL,
    issue_date   DATE NOT NULL,
    due_date     DATE NOT NULL,
    return_date  DATE DEFAULT NULL,
    status       ENUM('issued','returned','lost') DEFAULT 'issued',
    FOREIGN KEY (book_id)    REFERENCES books(id)    ON DELETE RESTRICT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- 5. Table: fines
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fines (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    issue_id       INT NOT NULL,
    student_id     INT NOT NULL,
    days_late      INT NOT NULL DEFAULT 0,
    fine_amount    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status         ENUM('unpaid','paid') DEFAULT 'unpaid',
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    paid_at        DATETIME DEFAULT NULL,
    FOREIGN KEY (issue_id)   REFERENCES issued_books(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- 6. Table: contacts
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contacts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    email      VARCHAR(255) NOT NULL,
    subject    VARCHAR(255) DEFAULT '',
    message    TEXT NOT NULL,
    sent_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------
-- 7. Table: announcements
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS announcements (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(255) NOT NULL,
    content    TEXT NOT NULL,
    active     BOOLEAN DEFAULT TRUE,
    posted_on  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ================================================================
-- SAMPLE DATA INSERTS
-- ================================================================

-- Clear existing data if re-importing
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE fines;
TRUNCATE TABLE issued_books;
TRUNCATE TABLE books;
TRUNCATE TABLE students;
TRUNCATE TABLE librarians;
TRUNCATE TABLE announcements;
SET FOREIGN_KEY_CHECKS = 1;

-- Books
INSERT INTO books (title, author, isbn, edition, publisher, category, total_copies, available) VALUES
('Introduction to Algorithms', 'Thomas H. Cormen', '978-0262033848', '3rd Edition', 'MIT Press', 'Computer Science', 5, 4),
('Clean Code', 'Robert C. Martin', '978-0132350884', '1st Edition', 'Prentice Hall', 'Computer Science', 3, 3),
('Database System Concepts', 'Silberschatz et al.', '978-0078022159', '7th Edition', 'McGraw-Hill', 'Computer Science', 4, 3),
('The Great Gatsby', 'F. Scott Fitzgerald', '978-0743273565', 'Classic Edition', 'Scribner', 'Fiction', 6, 6),
('Calculus: Early Transcendentals', 'James Stewart', '978-1285741550', '8th Edition', 'Cengage Learning', 'Mathematics', 4, 4),
('Physics for Scientists', 'Serway & Jewett', '978-1337553278', '10th Edition', 'Cengage Learning', 'Science', 2, 2);

-- Students
INSERT INTO students (student_id, name, email, course, phone) VALUES
('STU001', 'Alice Johnson', 'alice@global.edu', 'Computer Science', '555-0101'),
('STU002', 'Bob Martinez',  'bob@global.edu',   'Electrical Eng.',  '555-0102'),
('STU003', 'Carol Williams','carol@global.edu', 'Mathematics',      '555-0103');

-- Librarians
INSERT INTO librarians (emp_id, name, email, phone, qualification, experience) VALUES
('LIB001', 'Dr. Sarah Connor', 'sarah.lib@global.edu', '555-9001', 'Ph.D. in Library Science', 12),
('LIB002', 'James Miller',     'james.lib@global.edu', '555-9002', 'Master of Library Science', 5);

-- Announcements
INSERT INTO announcements (title, content, active) VALUES
('Holiday Closure', 'The library will be closed from December 24 to January 1 for the winter holidays.', 1),
('New IEEE Journals Available', 'We have just added a massive collection of 2024 IEEE engineering magazines. Access them at the reference desk!', 1);

-- Issued Books (Sample issue showing 1 late book generating a fine setup)
-- Book 1 to STU001 (Currently issued and overdue)
INSERT INTO issued_books (book_id, student_id, issue_date, due_date, status)
VALUES (1, 1, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'issued');

-- Book 3 to STU002 (Currently issued, not overdue)
INSERT INTO issued_books (book_id, student_id, issue_date, due_date, status)
VALUES (3, 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'issued');
