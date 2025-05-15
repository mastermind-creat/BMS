-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'staff') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Applicants table
CREATE TABLE IF NOT EXISTS applicants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    institution VARCHAR(100) NOT NULL,
    course VARCHAR(100) NOT NULL,
    year_of_study INT NOT NULL,
    application_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bursary distributions table
CREATE TABLE IF NOT EXISTS distributions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    applicant_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    distribution_date DATE NOT NULL,
    status ENUM('pending', 'disbursed', 'cancelled') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Reports table
CREATE TABLE IF NOT EXISTS reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_type ENUM('distribution', 'applications', 'financial') NOT NULL,
    report_date DATE NOT NULL,
    report_data JSON NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
); 