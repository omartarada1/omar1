<?php
// Database configuration
class Database {
    private $host = 'localhost';
    private $db_name = 'icloud_unlock_db';
    private $username = 'root';  // Change to your database username
    private $password = '';      // Change to your database password
    private $conn;

    // Get database connection
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            return null;
        }

        return $this->conn;
    }

    // Initialize database tables
    public function initDatabase() {
        $sql = "
        CREATE DATABASE IF NOT EXISTS `{$this->db_name}` 
        DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        
        USE `{$this->db_name}`;

        CREATE TABLE IF NOT EXISTS `unlock_requests` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `device_type` varchar(50) NOT NULL,
            `imei_serial` varchar(100) NOT NULL,
            `email` varchar(255) NOT NULL,
            `description` text,
            `payment_method` varchar(50) NOT NULL,
            `payment_status` enum('pending','paid','failed','completed') DEFAULT 'pending',
            `payment_data` text,
            `amount` decimal(10,2) NOT NULL,
            `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_email` (`email`),
            INDEX `idx_payment_status` (`payment_status`),
            INDEX `idx_status` (`status`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `admin_users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL UNIQUE,
            `password` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL,
            `last_login` timestamp NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(100) NOT NULL UNIQUE,
            `setting_value` text,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `pricing` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `device_type` varchar(50) NOT NULL UNIQUE,
            `price` decimal(10,2) NOT NULL,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        -- Insert default admin user (password: admin123)
        INSERT IGNORE INTO `admin_users` (`username`, `password`, `email`) VALUES 
        ('admin', '$2y$10\$YourHashedPasswordHere', 'admin@icloudunlockpro.com');

        -- Insert default pricing
        INSERT IGNORE INTO `pricing` (`device_type`, `price`) VALUES 
        ('iphone', 89.00),
        ('ipad', 79.00),
        ('mac', 149.00);

        -- Insert default settings
        INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES 
        ('admin_email', 'admin@icloudunlockpro.com'),
        ('site_name', 'iCloud Unlock Pro'),
        ('stripe_public_key', 'pk_test_YOUR_STRIPE_PUBLISHABLE_KEY'),
        ('stripe_secret_key', 'sk_test_YOUR_STRIPE_SECRET_KEY'),
        ('paypal_client_id', 'YOUR_PAYPAL_CLIENT_ID'),
        ('paypal_client_secret', 'YOUR_PAYPAL_CLIENT_SECRET'),
        ('usdt_trc20_address', 'TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE'),
        ('usdt_erc20_address', '0x1234567890123456789012345678901234567890'),
        ('whatsapp_number', '+15551234567'),
        ('smtp_host', 'smtp.gmail.com'),
        ('smtp_port', '587'),
        ('smtp_username', 'your@gmail.com'),
        ('smtp_password', 'your_app_password'),
        ('smtp_encryption', 'tls');
        ";

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host,
                $this->username,
                $this->password
            );
            
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec($sql);
            
            return true;
        } catch(PDOException $exception) {
            error_log("Database initialization error: " . $exception->getMessage());
            return false;
        }
    }
}
?>