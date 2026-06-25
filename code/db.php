<?php
// NextHire - Database Connection Manager

require_once __DIR__ . '/config.php';

try {
    if (DB_TYPE === 'sqlite') {
        $db_file = SQLITE_DB_PATH;
        $db_exists = file_exists($db_file);
        
        $pdo = new PDO('sqlite:' . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Check if database needs initialization
        // We'll query sqlite_master to see if our tables exist
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='companies'");
        $has_tables = $stmt->fetch();
        
        if (!$has_tables) {
            require_once __DIR__ . '/db_init.php';
            initialize_database($pdo, 'sqlite');
        }
    } else {
        // MySQL connection
        $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Create database if it does not exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");
        
        // Check if tables exist
        $stmt = $pdo->query("SHOW TABLES LIKE 'companies'");
        if ($stmt->rowCount() === 0) {
            require_once __DIR__ . '/db_init.php';
            initialize_database($pdo, 'mysql');
        }
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Return the database connection instance
return $pdo;
?>
