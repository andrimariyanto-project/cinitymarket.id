<?php
$pageTitle = 'Belanja Online Terpercaya';
require_once __DIR__ . '/includes/header.php';
$db = Database::getInstance();

$featured = $db->fetchAll("
    SELECT p.*, sp.store_name, sp.store_slug,
           (SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) as img
    FROM products p
    JOIN seller_profiles sp ON sp.id = p.seller_id
    WHERE p.status = 'active' AND p.stock > 0
    ORDER BY p.is_featured DESC, p.total_sold DESC, p.created_at DESC
    LIMIT 16
");
$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order LIMIT 8");
$totalProducts = $db->fetchOne("SELECT COUNT(*) c FROM products WHERE status='active'")['c'];
$totalSellers  = $db->fetchOne("SELECT COUNT(*) c FROM seller_profiles")['c'];
$totalOrders   = $db->fetchOne("SELECT COUNT(*) c FROM orders WHERE status='completed'")['c'];
?>

<!-- ===== HERO ===== -->
<section class="hero">
  <div class="container hero-content">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <div class="hero-badge">
          <i class="bi bi-patch-check-fill"></i> Platform Jual Beli Terpercaya
        </div>
        <h1>Belanja Mudah,<br>Diantar Langsung ke Pintu</h1>
        <p class="hero-sub">Ribuan produk dari penjual lokal terpercaya. Pembayaran aman via Tripay, diantar kurir toko dengan biaya terjangkau.</p>
        <div class="d-flex gap-3 mt-5">
          <a href="/search.php" class="btn btn-lg" style="background:white;color:var(--brand);font-weight:700;border:none">
            <i class="bi bi-search"></i> Mulai Belanja
          </a>
          <a href="/register.php?role=seller" class="btn btn-lg" style="background:rgba(255,255,255,.12);color:white;border:1.5px solid rgba(255,255,255,.25)">
            <i class="bi bi-shop"></i> Buka Toko
          </a>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="row g-3">
          <div class="col-4">
            <div class="hero-stat">
              <div class="hero-stat-num"><?= $totalProducts ?>+</div>
              <div class="hero-stat-label">Produk</div>
            </div>
          </div>
          <div class="col-4">
            <div class="hero-stat">
              <div class="hero-stat-num"><?= $totalSellers ?>+</div>
              <div class="hero-stat-label">Penjual</div>
            </div>
          </div>
          <div class="col-4">
            <div class="hero-stat">
              <div class="hero-stat-num"><?= $totalOrders ?>+</div>
              <div class="hero-stat-label">Transaksi</div>
            </div>
          </div>
          <div class="col-12">
            <div class="hero-stat" style="text-align:left">
              <div style="display:flex;align-items:center;gap:10px">
                <i class="bi bi-truck-front-fill" style="font-size:24px;color:var(--brand-mid)"></i>
                <div>
                  <div style="font-size:13.5px;font-weight:700;color:white">Kurir Toko — Rp 2.000</div>
                  <div style="font-size:12px;color:rgba(255,255,255,.5)">+ Biaya admin Rp 2.000/order</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== TRUST BAR ===== -->
<div class="trust-bar">
  <div class="container">
    <div class="row g-3 justify-content-center">
      <?php foreach ([
        ['bi-shield-fill-check','var(--success)','Transaksi 100% Aman'],
        ['bi-truck-front-fill','var(--brand)','Kurir Toko Rp 2.000'],
        ['bi-credit-card-2-front','var(--warning)','Multi Metode Bayar'],
        ['bi-arrow-repeat','var(--danger)','Jaminan Kepuasan'],
      ] as [$icon,$color,$label]): ?>
      <div class="col-6 col-md-3">
        <div class="trust-item justify-content-center">
          <i class="bi <?= $icon ?>" style="color:<?= $color ?>"></i>
          <span><?= $label ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="container section">

  <!-- Categories -->
  <div class="section-head">
    <span class="section-title">Kategori</span>
    <a href="/search.php" class="section-link">Semua <i class="bi bi-arrow-right"></i></a>
  </div>
  <div class="cat-grid mb-5">
    <?php foreach ($categories as $cat): ?>
    <a href="/search.php?cat=<?= urlencode($cat['slug']) ?>" class="cat-chip">
      <i class="bi <?= clean($cat['icon']) ?>"></i>
      <span><?= clean($cat['name']) ?></span>
    </a>
    <?php endforeach; ?>
    <a href="/search.php" class="cat-chip">
      <i class="bi bi-grid-3x3-gap"></i>
      <span>Semua</span>
    </a>
  </div>

  <!-- Featured Products -->
  <div class="section-head">
    <span class="section-title">✨ Produk Unggulan</span>
    <a href="/search.php" class="section-link">Lihat Semua <i class="bi bi-arrow-right"></i></a>
  </div>
  <div class="row g-3">
    <?php foreach ($featured as $p): ?>
    <div class="col-6 col-md-4 col-xl-3">
      <a href="/product.php?slug=<?= urlencode($p['slug']) ?>" style="text-decoration:none;display:block;height:100%">
        <div class="product-card h-100">
          <div class="img-wrap">
            <img src="<?= $p['img'] ? '/' . clean($p['img']) : '/assets/img/no-image.svg' ?>"
                 alt="<?= clean($p['name']) ?>" loading="lazy">
          </div>
          <div class="card-body">
            <div class="prod-name"><?= clean($p['name']) ?></div>
            <div class="prod-price"><?= formatRupiah($p['price']) ?></div>
            <div class="prod-meta">
              <span class="prod-rating"><i class="bi bi-star-fill"></i><?= number_format($p['rating'],1) ?> · <?= $p['total_sold'] ?> terjual</span>
              <span class="prod-store"><?= clean($p['store_name']) ?></span>
            </div>
          </div>
        </div>
      </a>
    </div>
    <?php endforeach; ?>
    <?php if (empty($featured)): ?>
    <div class="col-12 empty-state">
      <i class="bi bi-box-seam empty-state-icon"></i>
      <h5>Belum ada produk</h5>
      <p>Jadilah penjual pertama di cinitymarket.id</p>
      <a href="/register.php" class="btn btn-primary">Buka Toko Sekarang</a>
    </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
