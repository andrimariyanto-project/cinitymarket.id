<?php
require_once __DIR__ . '/../config/database.php';

// ===== SESSION =====
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', APP_ENV === 'production' ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        session_start();
    }
}

// ===== AUTH =====
function auth(): ?array {
    startSession();
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool { return auth() !== null; }
function isAdmin(): bool { return auth()['role'] === 'admin'; }
function isSeller(): bool { return in_array(auth()['role'], ['seller', 'admin']); }
function isBuyer(): bool { return isLoggedIn(); }

function requireLogin(string $redirect = '/login.php'): void {
    if (!isLoggedIn()) {
        setFlash('error', 'Silakan login terlebih dahulu');
        redirect($redirect);
    }
}

function requireRole(string $role): void {
    requireLogin();
    if (auth()['role'] !== $role && auth()['role'] !== 'admin') {
        setFlash('error', 'Akses ditolak');
        redirect('/index.php');
    }
}

// ===== CSRF =====
function csrfToken(): string {
    startSession();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrfToken() . '">';
}

function verifyCsrf(): void {
    startSession();
    $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token');
    }
}

// ===== FLASH MESSAGES =====
function setFlash(string $type, string $message): void {
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    startSession();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// ===== REDIRECT =====
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// ===== SANITIZE =====
function clean(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function sanitizeEmail(string $email): string {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

// ===== MONEY =====
function formatRupiah(float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// ===== DATE =====
function formatDate(string $date, string $format = 'd M Y'): string {
    $months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $d = new DateTime($date);
    $result = $d->format($format);
    if (str_contains($format, 'M')) {
        $result = str_replace(
            ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            $months, $result
        );
    }
    return $result;
}

function timeAgo(string $datetime): string {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit lalu';
    if ($time < 86400) return floor($time/3600) . ' jam lalu';
    if ($time < 604800) return floor($time/86400) . ' hari lalu';
    return formatDate($datetime);
}

// ===== SLUG =====
function makeSlug(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function uniqueSlug(string $table, string $text, int $excludeId = 0): string {
    $db = Database::getInstance();
    $slug = makeSlug($text);
    $original = $slug;
    $i = 1;
    while (true) {
        $row = $db->fetchOne("SELECT id FROM $table WHERE slug=? AND id!=?", [$slug, $excludeId]);
        if (!$row) break;
        $slug = $original . '-' . $i++;
    }
    return $slug;
}

// ===== ORDER NUMBER =====
function generateOrderNumber(): string {
    return ORDER_PREFIX . date('Ymd') . strtoupper(bin2hex(random_bytes(3)));
}

// ===== IMAGE UPLOAD =====
function uploadImage(array $file, string $folder = 'products'): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > MAX_UPLOAD_SIZE) return null;
    if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) return null;

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . strtolower($ext);
    $dir = UPLOAD_PATH . $folder . '/';

    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $destination = $dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'assets/uploads/' . $folder . '/' . $filename;
    }
    return null;
}

// ===== NOTIFICATIONS =====
function sendNotification(int $userId, string $title, string $message, string $type = 'info', string $link = ''): void {
    $db = Database::getInstance();
    $db->insert('notifications', [
        'user_id' => $userId,
        'title'   => $title,
        'message' => $message,
        'type'    => $type,
        'link'    => $link
    ]);
}

function getUnreadNotifCount(int $userId): int {
    $db = Database::getInstance();
    $row = $db->fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND is_read=0", [$userId]);
    return (int)($row['c'] ?? 0);
}

// ===== CART =====
function getCartCount(int $userId): int {
    $db = Database::getInstance();
    $row = $db->fetchOne("SELECT SUM(quantity) as c FROM cart WHERE user_id=?", [$userId]);
    return (int)($row['c'] ?? 0);
}

// ===== PAGINATION =====
function paginate(int $total, int $page, int $perPage = ITEMS_PER_PAGE): array {
    $totalPages = (int)ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $page,
        'total_pages' => $totalPages,
        'offset'      => $offset,
        'has_prev'    => $page > 1,
        'has_next'    => $page < $totalPages,
    ];
}
