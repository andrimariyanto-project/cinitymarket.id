# cinitymarket.id — Marketplace PHP

Marketplace berbasis PHP + MySQL dengan integrasi Tripay Payment Gateway.

## 📁 Struktur File
```
cinitymarket/
├── config/
│   ├── config.php          # Konfigurasi utama (DB, Tripay, fee)
│   └── database.php        # Class Database (PDO singleton)
├── includes/
│   ├── functions.php       # Auth, CSRF, helper functions
│   ├── tripay.php          # Integrasi Tripay API
│   ├── header.php          # Navbar + layout header
│   └── footer.php          # Footer
├── assets/
│   ├── css/style.css       # Stylesheet utama
│   ├── js/main.js          # JavaScript
│   └── uploads/            # Upload gambar (buat chmod 755)
├── buyer/
│   ├── cart.php            # Keranjang belanja
│   ├── checkout.php        # Checkout + pilih pengiriman
│   ├── payment.php         # Pilih metode bayar (Tripay)
│   ├── payment-instruction.php  # Instruksi setelah bayar
│   ├── orders.php          # Daftar pesanan
│   ├── order-detail.php    # Detail pesanan + konfirmasi terima
│   ├── order-action.php    # Handler aksi pesanan
│   ├── review.php          # Beri ulasan produk
│   ├── profile.php         # Edit profil + ganti password
│   ├── notifications.php   # Notifikasi
│   └── wishlist.php        # Produk favorit
├── seller/
│   ├── dashboard.php       # Dashboard statistik toko
│   ├── orders.php          # Pesanan masuk
│   ├── order-detail.php    # Detail + aksi pesanan (konfirmasi/proses/kirim)
│   ├── products.php        # Daftar produk
│   ├── product-add.php     # Tambah produk baru
│   ├── product-edit.php    # Edit produk
│   └── profile.php         # Profil & rekening toko
├── admin/
│   ├── dashboard.php       # Dashboard admin
│   ├── orders.php          # Semua pesanan
│   ├── sellers.php         # Kelola penjual + verifikasi
│   ├── users.php           # Kelola pengguna
│   └── settings.php        # Pengaturan sistem + Tripay
├── api/
│   └── payment-callback.php # Tripay webhook callback
├── index.php               # Halaman utama
├── login.php               # Login
├── register.php            # Daftar (pembeli/penjual)
├── logout.php              # Logout
├── product.php             # Detail produk
├── search.php              # Cari produk
├── store.php               # Halaman toko penjual
├── forgot-password.php     # Lupa password
├── database.sql            # Schema + seed data
└── .htaccess               # Security rules
```

## ⚙️ Instalasi

### 1. Import Database
```sql
mysql -u root -p < database.sql
```
Atau import via phpMyAdmin.

### 2. Konfigurasi `config/config.php`
```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'cinitymarket');
define('DB_USER', 'root');
define('DB_PASS', 'password_anda');

// URL website
define('APP_URL', 'https://cinitymarket.id');

// Tripay (ambil dari tripay.co.id/merchant)
define('TRIPAY_MERCHANT_CODE', 'T12345');
define('TRIPAY_API_KEY', 'xxx');
define('TRIPAY_PRIVATE_KEY', 'xxx');
define('TRIPAY_IS_PRODUCTION', false); // true untuk live

// Mode produksi
define('APP_ENV', 'production');
```

### 3. Permission Upload
```bash
chmod 755 assets/uploads/
# Buat subfolder jika belum ada
mkdir -p assets/uploads/products assets/uploads/avatars assets/uploads/stores
chmod 755 assets/uploads/products assets/uploads/avatars assets/uploads/stores
```

### 4. Web Server
- **Apache**: Aktifkan mod_rewrite, arahkan DocumentRoot ke folder project
- **Nginx**: Konfigurasikan root ke folder project
- **XAMPP/Laragon**: Taruh di htdocs/www

### 5. Callback Tripay
Di dashboard Tripay, set Callback URL:
```
https://cinitymarket.id/api/payment-callback.php
```

### 6. Login Admin Default
- Email: `admin@cinitymarket.id`
- Password: `Admin@123`
- **SEGERA ganti password setelah login pertama!**

## 💰 Struktur Biaya
- **Biaya Admin**: Rp 2.000 per order (per toko)
- **Kurir Toko**: Rp 2.000 per order
- **Ambil Sendiri**: Gratis

## 🔄 Alur Pesanan
```
Pembeli checkout
    → Pilih pengiriman (Kurir Toko Rp2.000 / Ambil Sendiri Gratis)
    → Pilih metode bayar (via Tripay)
    → [Tripay Callback] Status → PAID
    → Penjual: Konfirmasi → Proses → Kirim (input no. resi)
    → Pembeli: Konfirmasi Terima
    → Status: SELESAI
```

## 🔒 Keamanan
- CSRF Token pada semua form POST
- PDO Prepared Statements (anti SQL Injection)
- Password bcrypt cost-12
- Session httponly + samesite strict
- .htaccess: block akses ke config/, includes/
- Validasi HMAC signature pada callback Tripay
- Input sanitization di semua halaman

## 📊 Skalabilitas (~500 User)
Database MySQL dengan indexing pada:
- `orders(buyer_id, seller_id, status)`
- `products(seller_id, category_id, status)`
- `notifications(user_id, is_read)`

Rekomendasi server: VPS 2GB RAM + MySQL 8.0 + PHP 8.1+

## 🛠️ Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Extension: PDO, PDO_MySQL, cURL, GD/Imagick
- Apache (mod_rewrite) atau Nginx
