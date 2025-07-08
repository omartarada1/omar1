<?php
/**
 * Database Setup Script for iCloud Unlock Pro
 * 
 * This script initializes the database and creates necessary tables.
 * Run this once before using the website.
 */

require_once 'config/database.php';

echo "<h1>iCloud Unlock Pro - Database Setup</h1>";

try {
    $database = new Database();
    
    echo "<p>Initializing database...</p>";
    
    $result = $database->initDatabase();
    
    if ($result) {
        echo "<div style='color: green; padding: 20px; border: 1px solid green; background: #f0fff0; margin: 20px 0;'>";
        echo "<h2>✅ Setup Successful!</h2>";
        echo "<p>Database and tables have been created successfully.</p>";
        echo "<p><strong>Default Admin Credentials:</strong></p>";
        echo "<ul>";
        echo "<li>Username: <code>admin</code></li>";
        echo "<li>Password: <code>admin123</code></li>";
        echo "</ul>";
        echo "<p><strong>⚠️ Important:</strong> Please change the default password immediately after first login!</p>";
        echo "</div>";
        
        // Create default admin user with hashed password
        $db = $database->getConnection();
        if ($db) {
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
            $stmt->execute([$hashedPassword]);
            
            echo "<p>✅ Default admin password has been properly hashed.</p>";
        }
        
        echo "<h3>Next Steps:</h3>";
        echo "<ol>";
        echo "<li>Delete this setup.php file for security</li>";
        echo "<li>Configure your payment gateway credentials in the database settings table</li>";
        echo "<li>Update email settings for SMTP configuration</li>";
        echo "<li>Test the website functionality</li>";
        echo "<li>Access the admin panel at <a href='admin/'>admin/</a></li>";
        echo "</ol>";
        
    } else {
        echo "<div style='color: red; padding: 20px; border: 1px solid red; background: #ffe0e0; margin: 20px 0;'>";
        echo "<h2>❌ Setup Failed</h2>";
        echo "<p>There was an error setting up the database. Please check your database configuration in config/database.php</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; border: 1px solid red; background: #ffe0e0; margin: 20px 0;'>";
    echo "<h2>❌ Error</h2>";
    echo "<p>Setup failed with error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
}

echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
ul, ol { margin: 10px 0; }
h1, h2, h3 { color: #333; }
</style>";
?>