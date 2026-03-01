<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'shopeasy_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Affiliate Settings
define('AFFILIATE_COMMISSION', 0.20); // 20%
define('COOKIE_DURATION', 86400); // 24 hours in seconds

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Set affiliate cookie
function setAffiliateCookie($affiliate_id) {
    setcookie('affiliate_ref', $affiliate_id, time() + COOKIE_DURATION, '/');
}

// Get affiliate ID from cookie
function getAffiliateId() {
    return isset($_COOKIE['affiliate_ref']) ? $_COOKIE['affiliate_ref'] : null;
}

// Track affiliate click
function trackClick($product_id, $source) {
    global $pdo;
    
    $affiliate_id = getAffiliateId();
    
    if ($affiliate_id) {
        $stmt = $pdo->prepare("
            INSERT INTO affiliate_clicks (affiliate_id, product_id, source, click_time) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$affiliate_id, $product_id, $source]);
    }
}

// Record sale and calculate commission
function recordSale($product_id, $amount) {
    global $pdo;
    
    $affiliate_id = getAffiliateId();
    $commission = $amount * AFFILIATE_COMMISSION;
    
    if ($affiliate_id) {
        // Record sale
        $stmt = $pdo->prepare("
            INSERT INTO affiliate_sales (affiliate_id, product_id, sale_amount, commission, sale_time) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$affiliate_id, $product_id, $amount, $commission]);
        
        // Update affiliate earnings
        $update = $pdo->prepare("
            UPDATE affiliates 
            SET total_earnings = total_earnings + ?,
                pending_earnings = pending_earnings + ?
            WHERE id = ?
        ");
        $update->execute([$commission, $commission, $affiliate_id]);
    }
}
?>
