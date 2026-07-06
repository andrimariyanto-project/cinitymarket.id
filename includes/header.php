<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
$user       = auth();
$cartCount  = $user ? getCartCount($user['id']) : 0;
$notifCount = $user ? getUnreadNotifCount($user['id']) : 0;
$flash      = getFlash();

// Detect active section for sidebar
$currentPath = $_SERVER['PHP_SELF'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? clean($pageTitle) . ' — ' : '' ?>cinitymarket.id</title>
<meta name="description" content="<?= isset($pageDesc) ? clean($pageDesc) : 'Belanja dan jual beli online mudah, aman, terpercaya.' ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar" id="mainNav">
  <div class="container d-flex align-items-center gap-3">

    <!-- Brand -->
    <a href="/index.php" class="brand-logo me-2">
      <div class="brand-mark"><i class="bi bi-bag-heart-fill"></i></div>
      <span class="brand-name">cinitymarket<span>.id</span></span>
    </a>

    <!-- Search (desktop) -->
    <form class="search-wrap d-none d-lg-flex flex-grow-1" action="/search.php" method="GET">
      <input type="text" name="q" placeholder="Cari produk, toko, atau kategori..."
             value="<?= isset($_GET['q']) ? clean($_GET['q']) : '' ?>">
      <button type="submit"><i class="bi bi-search"></i></button>
    </form>

    <!-- Right actions -->
    <div class="d-flex align-items-center gap-2 ms-auto">
      <?php if ($user): ?>
        <a href="/buyer/cart.php" class="nav-icon-btn" title="Keranjang">
          <i class="bi bi-bag"></i>
          <?php if ($cartCount > 0): ?><span class="nav-badge"><?= $cartCount ?></span><?php endif; ?>
        </a>
        <a href="/buyer/notifications.php" class="nav-icon-btn d-none d-md-flex" title="Notifikasi">
          <i class="bi bi-bell"></i>
          <?php if ($notifCount > 0): ?><span class="nav-badge"><?= $notifCount ?></span><?php endif; ?>
        </a>
        <!-- User dropdown -->
        <div class="dropdown">
          <button class="user-pill dropdown-toggle border-0" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <span class="d-none d-md-inline" style="font-size:13.5px;font-weight:600;color:var(--text-1)"><?= clean(explode(' ', $user['name'])[0]) ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow">
            <li><span class="dropdown-header"><?= clean($user['name']) ?><br><small class="fw-normal" style="color:var(--text-3)"><?= clean($user['email']) ?></small></span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/buyer/profile.php"><i class="bi bi-person me-2 text-muted"></i>Profil Saya</a></li>
            <li><a class="dropdown-item" href="/buyer/orders.php"><i class="bi bi-bag me-2 text-muted"></i>Pesanan Saya</a></li>
            <li><a class="dropdown-item" href="/buyer/wishlist.php"><i class="bi bi-heart me-2 text-muted"></i>Wishlist</a></li>
            <li><a class="dropdown-item" href="/buyer/notifications.php"><i class="bi bi-bell me-2 text-muted"></i>Notifikasi<?php if ($notifCount > 0): ?> <span class="badge badge-brand ms-1"><?= $notifCount ?></span><?php endif; ?></a></li>
            <?php if (in_array($user['role'], ['seller','admin'])): ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="/seller/dashboard.php" style="color:var(--brand)"><i class="bi bi-shop me-2"></i>Dashboard Toko</a></li>
            <?php endif; ?>
            <?php if ($user['role'] === 'admin'): ?>
              <li><a class="dropdown-item text-danger" href="/admin/dashboard.php"><i class="bi bi-shield-check me-2"></i>Admin Panel</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a href="/login.php" class="btn btn-outline-secondary btn-sm">Masuk</a>
        <a href="/register.php" class="btn btn-primary btn-sm">Daftar Gratis</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Mobile search bar -->
<div class="mobile-search d-lg-none">
  <form action="/search.php" method="GET">
    <div class="search-wrap w-100">
      <input type="text" name="q" placeholder="Cari produk..." value="<?= isset($_GET['q']) ? clean($_GET['q']) : '' ?>">
      <button type="submit"><i class="bi bi-search"></i></button>
    </div>
  </form>
</div>

<!-- Toast notifications -->
<?php if ($flash): ?>
<div class="toast-wrap" id="toastWrap">
  <div class="toast-item <?= $flash['type'] === 'error' ? 'error' : ($flash['type'] === 'success' ? 'success' : 'info') ?>">
    <span class="toast-icon">
      <?= $flash['type'] === 'success' ? '✅' : ($flash['type'] === 'error' ? '❌' : 'ℹ️') ?>
    </span>
    <span><?= clean($flash['message']) ?></span>
    <button class="toast-close" onclick="this.closest('.toast-item').remove()">×</button>
  </div>
</div>
<?php endif; ?>

<!-- Bootstrap JS (defer) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>

