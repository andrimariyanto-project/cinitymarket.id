<?php
// ============================================
// cinitymarket.id - Main Configuration
// ============================================

define('APP_NAME', 'cinitymarket.id');
define('APP_URL', 'https://cinitymarket.id');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development');

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'cinitymarket');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Tripay
define('TRIPAY_MERCHANT_CODE', 'YOUR_MERCHANT_CODE');
define('TRIPAY_API_KEY', 'YOUR_API_KEY');
define('TRIPAY_PRIVATE_KEY', 'YOUR_PRIVATE_KEY');
define('TRIPAY_IS_PRODUCTION', false);

// Fee — kurir toko saja, sudah termasuk admin fee
define('ADMIN_FEE', 2000);         // Rp 2.000 biaya layanan
define('COURIER_STORE_FEE', 2000); // Rp 2.000 ongkir kurir toko

// Upload
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', APP_URL . '/assets/uploads/');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Session & Security
define('SESSION_LIFETIME', 7200);
define('CSRF_TOKEN_NAME', 'csrf_token');
define('BCRYPT_COST', 12);

// Pagination
define('ITEMS_PER_PAGE', 24);

// Order
define('ORDER_PREFIX', 'CMK');
define('ORDER_EXPIRE_HOURS', 24);

error_reporting(APP_ENV === 'development' ? E_ALL : 0);
ini_set('display_errors', APP_ENV === 'development' ? 1 : 0);
