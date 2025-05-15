# GLA Bursary Management System

A comprehensive bursary management system for God's Last Appeal Foundation to manage student bursaries, track fund distributions, and generate reports.

## Features

1. User Management
   - User registration and login
   - Role-based access control (Admin and Staff)
   - Secure password handling

2. Applicant Management
   - Add new applicants
   - Track application status
   - View applicant details
   - Update application status

3. Fund Distribution
   - Record fund distributions
   - Track distribution status
   - Manage disbursements
   - View distribution history

4. Reporting
   - Generate application reports
   - Generate distribution reports
   - Financial summaries
   - Export and print reports

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. Clone or download the repository to your web server directory
2. Create a MySQL database named `gla_bursary`
3. Import the database schema from `config/schema.sql`
4. Configure the database connection in `config/database.php`
5. Access the system through your web browser

## Default Login

After installation, you can create a new admin account through the registration page. The first registered user will automatically be assigned admin privileges.

## Directory Structure

```
├── auth/               # Authentication handlers
├── config/            # Configuration files
├── index.php          # Main entry point
├── dashboard.php      # Dashboard
├── applicants.php     # Applicant management
├── distributions.php  # Fund distribution
├── reports.php        # Report generation
└── README.md          # This file
```

## Security Features

- Password hashing using PHP's password_hash()
- Prepared statements to prevent SQL injection
- Input validation and sanitization
- Session-based authentication
- Role-based access control

## Usage

1. Register a new account or login with existing credentials
2. Navigate through the system using the sidebar menu
3. Add applicants and manage their applications
4. Record fund distributions and track their status
5. Generate reports as needed

## Support

For support or questions, please contact the system administrator.

## License

This project is proprietary and confidential. Unauthorized copying, distribution, or use is strictly prohibited. 