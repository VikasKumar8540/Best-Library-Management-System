-- Library Management System Database
-- Import this file in phpMyAdmin

CREATE DATABASE IF NOT EXISTS library_db;
USE library_db;

-- Users table (admin, librarian, student)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','librarian','student') NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    student_id VARCHAR(50) NULL DEFAULT NULL,
    profile_pic VARCHAR(255) DEFAULT 'default.png',
    status ENUM('active','inactive') DEFAULT 'active',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books table
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(150) NOT NULL,
    isbn VARCHAR(20) UNIQUE,
    category VARCHAR(80),
    publisher VARCHAR(150),
    year INT,
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    description TEXT,
    cover_image VARCHAR(255) DEFAULT 'default_book.png',
    status ENUM('active','inactive') DEFAULT 'active',
    added_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Book issues table
CREATE TABLE book_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    issued_by INT,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    fine_amount DECIMAL(8,2) DEFAULT 0.00,
    fine_paid ENUM('no','yes') DEFAULT 'no',
    status ENUM('issued','returned','overdue') DEFAULT 'issued',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Book requests (student to librarian)
CREATE TABLE book_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    book_id INT,
    request_title VARCHAR(200),
    message TEXT NOT NULL,
    status ENUM('pending','approved','rejected','resolved') DEFAULT 'pending',
    librarian_reply TEXT,
    replied_by INT,
    replied_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE SET NULL,
    FOREIGN KEY (replied_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    sent_by INT NOT NULL,
    target_type ENUM('all','student') NOT NULL,
    target_student_id INT DEFAULT NULL,
    is_read ENUM('no','yes') DEFAULT 'no',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Fine settings
CREATE TABLE fine_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fine_per_day DECIMAL(6,2) DEFAULT 2.00,
    max_issue_days INT DEFAULT 14,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default fine setting
INSERT INTO fine_settings (fine_per_day, max_issue_days) VALUES (2.00, 14);

-- Insert default admin
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@library.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Default password: password

-- Insert default librarian
INSERT INTO users (name, email, password, role, phone) VALUES
('Ravi Kumar', 'librarian@library.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'librarian', '9876543210');
-- Default password: password

-- Insert sample students
INSERT INTO users (name, email, password, role, phone, student_id) VALUES
('Ananya Singh', 'ananya@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '9123456780', 'STU001'),
('Rahul Sharma', 'rahul@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '9123456781', 'STU002');
-- Default password: password

-- Insert sample books
INSERT INTO books (title, author, isbn, category, publisher, year, total_copies, available_copies, description) VALUES
('Introduction to Algorithms', 'Thomas H. Cormen', '978-0262033848', 'Computer Science', 'MIT Press', 2009, 3, 3, 'A comprehensive introduction to algorithms.'),
('Clean Code', 'Robert C. Martin', '978-0132350884', 'Programming', 'Prentice Hall', 2008, 2, 2, 'A handbook of agile software craftsmanship.'),
('The Great Gatsby', 'F. Scott Fitzgerald', '978-0743273565', 'Fiction', 'Scribner', 1925, 5, 5, 'Classic American novel.'),
('Physics for Scientists', 'Paul A. Tipler', '978-1429201247', 'Science', 'W. H. Freeman', 2007, 4, 4, 'Comprehensive physics textbook.'),
('Calculus Early Transcendentals', 'James Stewart', '978-1285741550', 'Mathematics', 'Cengage', 2015, 3, 3, 'Calculus textbook for engineers.'),
('Database System Concepts', 'Abraham Silberschatz', '978-0073523323', 'Computer Science', 'McGraw-Hill', 2010, 2, 2, 'Fundamental database concepts.');

CREATE TABLE password_resets (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(255) NOT NULL,
  otp        VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

  ALTER TABLE password_resets ADD COLUMN used TINYINT(1) NOT NULL DEFAULT 0;
);
