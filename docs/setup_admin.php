<?php
/**
 * Admin Setup Script for Fix Smart
 * 
 * This script sets up the admin user with proper password hashing
 * and ensures all database tables are created correctly.
 */

require_once 'config/database.php';

echo "<h1>Fix Smart - Admin Setup</h1>";

try {
    $database = new Database();
    
    // First, initialize the database tables
    echo "<p>Setting up database...</p>";
    $result = $database->initDatabase();
    
    if (!$result) {
        throw new Exception('Failed to initialize database');
    }
    
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception('Failed to connect to database');
    }
    
    // Create/update admin user with proper password hash
    $adminUsername = 'admin';
    $adminPassword = 'admin123';
    $adminEmail = 'admin@fixsmart.com';
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    // Check if admin user exists
    $stmt = $db->prepare("SELECT id FROM admin_users WHERE username = ?");
    $stmt->execute([$adminUsername]);
    $existingAdmin = $stmt->fetch();
    
    if ($existingAdmin) {
        // Update existing admin user
        $stmt = $db->prepare("UPDATE admin_users SET password = ?, email = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $adminEmail, $adminUsername]);
        echo "<p>✅ Admin user updated successfully.</p>";
    } else {
        // Create new admin user
        $stmt = $db->prepare("INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute([$adminUsername, $hashedPassword, $adminEmail]);
        echo "<p>✅ Admin user created successfully.</p>";
    }
    
    // Update settings to use Fix Smart branding
    $settings = [
        'admin_email' => 'admin@fixsmart.com',
        'site_name' => 'Fix Smart',
        'whatsapp_number' => '+15551234567'
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    }
    
    // Update default guarantees content
    $guaranteesContent = "
        <h2>Our Service Guarantees</h2>
        <div class='guarantee-item'>
            <h3>100% Success Rate</h3>
            <p>We guarantee successful iCloud removal for all supported devices. If we can't unlock your device, you get a full refund.</p>
        </div>
        <div class='guarantee-item'>
            <h3>24-48 Hour Delivery</h3>
            <p>Most devices are unlocked within 24-48 hours. Complex cases may take up to 72 hours.</p>
        </div>
        <div class='guarantee-item'>
            <h3>Permanent Solution</h3>
            <p>Our unlock is permanent and will not be reverted by iOS updates or factory resets.</p>
        </div>
        <div class='guarantee-item'>
            <h3>Secure Process</h3>
            <p>Your device information is handled with the highest security standards and deleted after service completion.</p>
        </div>
        <div class='guarantee-item'>
            <h3>24/7 Support</h3>
            <p>Our support team is available 24/7 via WhatsApp and email to assist you throughout the process.</p>
        </div>
    ";
    
    $stmt = $db->prepare("INSERT INTO guarantees_content (id, content) VALUES (1, ?) ON DUPLICATE KEY UPDATE content = ?");
    $stmt->execute([$guaranteesContent, $guaranteesContent]);
    
    echo "<div style='color: green; padding: 20px; border: 1px solid green; background: #f0fff0; margin: 20px 0;'>";
    echo "<h2>✅ Setup Successful!</h2>";
    echo "<p>Admin dashboard is now ready to use.</p>";
    echo "<p><strong>Admin Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li>Username: <code>admin</code></li>";
    echo "<li>Password: <code>admin123</code></li>";
    echo "</ul>";
    echo "<p><strong>⚠️ Important:</strong> Please change the default password immediately after first login!</p>";
    echo "</div>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Access the admin panel at <a href='admin/' target='_blank'>admin/</a></li>";
    echo "<li>Log in with the credentials above</li>";
    echo "<li>Change the default password immediately</li>";
    echo "<li>Configure payment gateway settings</li>";
    echo "<li>Update USDT wallet addresses</li>";
    echo "<li>Customize website content and guarantees</li>";
    echo "<li>Delete this setup_admin.php file for security</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; border: 1px solid red; background: #ffe0e0; margin: 20px 0;'>";
    echo "<h2>❌ Setup Failed</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration in config/database.php and try again.</p>";
    echo "</div>";
}

echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
ul, ol { margin: 10px 0; }
h1, h2, h3 { color: #333; }
a { color: #007aff; }
</style>";
?>