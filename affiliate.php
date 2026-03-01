<?php
require_once 'config.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';

// Initialize database tables if not exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS affiliates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE
