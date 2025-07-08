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

        CREATE TABLE IF NOT EXISTS `device_versions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `device_type` varchar(50) NOT NULL,
            `name` varchar(255) NOT NULL,
            `price` decimal(10,2) NOT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `sort_order` int(11) DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_device_type` (`device_type`),
            INDEX `idx_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `guarantees_content` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `content` longtext,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `website_texts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `text_key` varchar(100) NOT NULL UNIQUE,
            `text_value` text,
            `description` varchar(255),
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

        -- Insert default device versions
        INSERT IGNORE INTO `device_versions` (`device_type`, `name`, `price`, `sort_order`) VALUES 
        ('iphone', 'iPhone 15 Pro Max', 149.00, 1),
        ('iphone', 'iPhone 15 Pro', 139.00, 2),
        ('iphone', 'iPhone 15 Plus', 129.00, 3),
        ('iphone', 'iPhone 15', 119.00, 4),
        ('iphone', 'iPhone 14 Pro Max', 109.00, 5),
        ('iphone', 'iPhone 14 Pro', 99.00, 6),
        ('iphone', 'iPhone 14 Plus', 89.00, 7),
        ('iphone', 'iPhone 14', 89.00, 8),
        ('iphone', 'iPhone 13 Pro Max', 79.00, 9),
        ('iphone', 'iPhone 13 Pro', 79.00, 10),
        ('iphone', 'iPhone 13', 69.00, 11),
        ('iphone', 'iPhone 12 Pro Max', 69.00, 12),
        ('iphone', 'iPhone 12 Pro', 59.00, 13),
        ('iphone', 'iPhone 12', 59.00, 14),
        
        ('ipad', 'iPad Pro 12.9\" (6th gen)', 99.00, 1),
        ('ipad', 'iPad Pro 11\" (4th gen)', 89.00, 2),
        ('ipad', 'iPad Air (5th gen)', 79.00, 3),
        ('ipad', 'iPad (10th gen)', 69.00, 4),
        ('ipad', 'iPad (9th gen)', 59.00, 5),
        ('ipad', 'iPad mini (6th gen)', 69.00, 6),
        ('ipad', 'iPad Pro 12.9\" (5th gen)', 89.00, 7),
        ('ipad', 'iPad Pro 11\" (3rd gen)', 79.00, 8),
        
        ('mac', 'MacBook Pro 16\" (M3)', 199.00, 1),
        ('mac', 'MacBook Pro 14\" (M3)', 189.00, 2),
        ('mac', 'MacBook Air 15\" (M3)', 169.00, 3),
        ('mac', 'MacBook Air 13\" (M3)', 159.00, 4),
        ('mac', 'MacBook Pro 16\" (M2)', 179.00, 5),
        ('mac', 'MacBook Pro 14\" (M2)', 169.00, 6),
        ('mac', 'MacBook Air 13\" (M2)', 149.00, 7),
        ('mac', 'iMac 24\" (M3)', 189.00, 8),
        ('mac', 'Mac Studio (M2)', 219.00, 9),
        ('mac', 'Mac Pro (M2)', 299.00, 10);

        -- Insert default website texts
        INSERT IGNORE INTO `website_texts` (`text_key`, `text_value`, `description`) VALUES 
        ('site_title', 'Fix Smart', 'Main website title'),
        ('site_tagline', 'Professional Apple Device Unlocking Services', 'Website tagline'),
        ('hero_title', 'Professional iCloud Unlocking Service', 'Hero section main title'),
        ('hero_subtitle', 'Fast, secure, and reliable iCloud removal for iPhone, iPad, and Mac devices. Get your device unlocked by professionals with years of experience.', 'Hero section subtitle'),
        ('footer_text', 'Professional Apple device unlocking services', 'Footer description text'),
        ('whatsapp_number', '+15551234567', 'WhatsApp contact number');

        -- Insert default guarantees content (will be empty initially for admin to edit)
        INSERT IGNORE INTO `guarantees_content` (`id`, `content`) VALUES (1, '');

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