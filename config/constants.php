<?php
// config/constants.php
define('ROOT_PATH', dirname(__DIR__));
define('APP_URL', 'http://localhost:8079/pharmacy_management');

// Database Settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'pharmacy_managements');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Settings
define('SITE_NAME', 'Saudi Pharmacy');
define('DEFAULT_CURRENCY', 'SAR');
define('DEFAULT_LANGUAGE', 'ar'); // ar = Arabic, en = English

// Security
define('MAX_LOGIN_ATTEMPTS', 5);
define('SESSION_TIMEOUT', 3600); // 1 hour

// File Upload
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
