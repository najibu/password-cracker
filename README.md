# Password Cracker

This project is a password cracker built using PHP, HTML, CSS and Javascript. It aims to crack hashed passwords stored in a database to reveal the easy, medium and hard ones.

## Requirements

- PHP 8.1 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (for dependency management)

## Installation

1. Clone the repository:
    ```bash
    git clone https://github.com/najibu/password-cracker.git
    cd password-cracker
    ```

2. Import the database:
    ```bash
    mysql -u root -p < sql/not_so_smart_users.sql
    ```

3. Install composer dependencies
    ```bash
    composer install
    ```

4. Configure the database connection:
    Create a [.env](http://_vscodecontentref_/1) file in the root directory and add your database credentials:
    ```env
    DB_HOST=localhost
    DB_NAME=password_cracker
    DB_USER=your_username
    DB_PASS=your_password
    ```

5. Start the PHP development server:
    ```bash
    cd src
    php -S localhost:8000 
    ```

## Usage

The password cracker can find:
- 4 user IDs who used 5 numbers as passwords (e.g., 12345)
- 4 user IDs who used 3 uppercase characters and 1 number (e.g., ABC1)
- 12 user IDs who used lowercase dictionary words (max 6 chars) (e.g., london)
- 2 user IDs who used 6 character passwords with mixed case and numbers (e.g., AbC12z)

Visit `http://localhost:8000` in your browser to use the password cracker.

## Security Notice

This tool is for educational purposes only. Do not use it to crack passwords without authorization.
