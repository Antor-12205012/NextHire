<?php
// NextHire - Configuration Settings

// Error Reporting (Enable for development, disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start Session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
// Options: 'sqlite' or 'mysql'
define('DB_TYPE', 'sqlite'); 

// SQLite Specific Config
define('SQLITE_DB_PATH', __DIR__ . '/nexthire.sqlite');

// MySQL Specific Config (Used only if DB_TYPE is 'mysql')
define('DB_HOST', 'localhost');
define('DB_NAME', 'nexthire');
define('DB_USER', 'root');
define('DB_PASS', '');

// AI Service Configuration
// These can be updated via the Settings panel on the dashboard as well.
// If empty, the system automatically falls back to the high-performance local regex/pattern matching parser.
define('GEMINI_API_KEY', 'AIzaSyAxwyz8H2d-SCHI7UM-dPwKd_md9INzmzQ');
define('OPENAI_API_KEY', '');

// Application Constants
define('APP_NAME', 'NextHire');
define('APP_VERSION', '1.0.0');

// Create upload directory if it doesn't exist
$upload_dir = __DIR__ . '/uploads';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
define('UPLOAD_DIR', $upload_dir);
?>
