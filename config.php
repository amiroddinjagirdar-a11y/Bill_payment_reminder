<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bill_reminder');

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'amiroddinjagirdar@gmail.com');
define('SMTP_PASS', 'arpt iflf izkj fcgw');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

// App Configuration
define('APP_NAME', 'Bill Payment Reminder');
define('APP_URL', 'http://localhost/bill-payment-reminder-email-sender/');

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

?>