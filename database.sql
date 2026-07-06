-- ============================================
-- cinitymarket.id - Database Schema
-- ============================================
CREATE DATABASE IF NOT EXISTS cinitymarket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cinitymarket;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('buyer','seller','admin') DEFAULT 'buyer',
    avatar VARCHAR(255) DEFAULT NULL,
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(10),
    is_active TINYINT(1) DEFAULT 1,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE seller_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    store_name VARCHAR(150) NOT NULL,
    store_slug VARCHAR(150) UNIQUE NOT NULL,
    store_description TEXT,
    store_logo VARCHAR(255),
    store_banner VARCHAR(255),
    store_address TEXT,
    store_city VARCHAR(100),
    store_province VARCHAR(100),
    is_verified TINYINT(1) DEFAULT 0,
    bank_name VARCHAR(50),
    bank_account VARCHAR(30),
    bank_holder VARCHAR(100),
    total_sales INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    icon VARCHAR(50),
    parent_id INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(15,2) NOT NULL,
    stock INT DEFAULT 0,
    weight DECIMAL(8,2) DEFAULT 0,
    images JSON,
    status ENUM('draft','active','inactive','deleted') DEFAULT 'active',
    is_featured TINYINT(1) DEFAULT 0,
    total_sold INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES seller_profiles(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(30) UNIQUE NOT NULL,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    status ENUM('pending','payment_pending','paid','confirmed','processing','shipped','delivered','completed','cancelled','refund') DEFAULT 'pending',
    subtotal DECIMAL(15,2) NOT NULL,
    admin_fee DECIMAL(10,2) DEFAULT 2000,
    shipping_fee DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(15,2) NOT NULL,
    shipping_method VARCHAR(20) DEFAULT 'courier_store',
    shipping_address TEXT,
    shipping_city VARCHAR(100),
    shipping_province VARCHAR(100),
    shipping_postal VARCHAR(10),
    shipping_name VARCHAR(100),
    shipping_phone VARCHAR(20),
    note TEXT,
    tracking_number VARCHAR(100),
    paid_at TIMESTAMP NULL,
    confirmed_at TIMESTAMP NULL,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancel_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id),
    FOREIGN KEY (seller_id) REFERENCES seller_profiles(id)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_image VARCHAR(255),
    price DECIMAL(15,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    tripay_reference VARCHAR(100) UNIQUE,
    tripay_merchant_ref VARCHAR(100),
    payment_method VARCHAR(50),
    payment_name VARCHAR(100),
    amount DECIMAL(15,2) NOT NULL,
    fee_merchant DECIMAL(10,2) DEFAULT 0,
    fee_customer DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    pay_code VARCHAR(100),
    pay_url TEXT,
    checkout_url TEXT,
    status ENUM('UNPAID','PAID','FAILED','REFUND','EXPIRED') DEFAULT 'UNPAID',
    paid_at TIMESTAMP NULL,
    expired_at TIMESTAMP NULL,
    callback_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    images JSON,
    seller_reply TEXT,
    replied_at TIMESTAMP NULL,
    is_visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (buyer_id) REFERENCES users(id),
    FOREIGN KEY (seller_id) REFERENCES seller_profiles(id)
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) UNIQUE NOT NULL,
    value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_products_seller ON products(seller_id);
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_orders_buyer ON orders(buyer_id);
CREATE INDEX idx_orders_seller ON orders(seller_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);

-- Seed: Admin user (password: Admin@123)
INSERT INTO users (name, email, password, role, is_active) VALUES
('Admin cinitymarket.id', 'admin@cinitymarket.id', '$2y$12$Dn9kX1YV.DvhHBMLXQfdpeWTAk5BMbzsyYtL4sLL35.q.1WFAx/Me', 'admin', 1);

INSERT INTO settings (key_name, value) VALUES
('site_name', 'cinitymarket.id'),
('admin_fee', '2000'),
('courier_store_fee', '2000'),
('tripay_merchant_code', 'YOUR_MERCHANT_CODE'),
('tripay_api_key', 'YOUR_API_KEY'),
('tripay_private_key', 'YOUR_PRIVATE_KEY'),
('tripay_is_production', '0'),
('maintenance_mode', '0');

INSERT INTO categories (name, slug, icon) VALUES
('Elektronik', 'elektronik', 'bi-cpu'),
('Fashion Pria', 'fashion-pria', 'bi-person'),
('Fashion Wanita', 'fashion-wanita', 'bi-person-dress'),
('Makanan & Minuman', 'makanan-minuman', 'bi-cup-hot'),
('Kesehatan', 'kesehatan', 'bi-heart-pulse'),
('Rumah & Dapur', 'rumah-dapur', 'bi-house'),
('Olahraga', 'olahraga', 'bi-trophy'),
('Hobi & Koleksi', 'hobi-koleksi', 'bi-star');
