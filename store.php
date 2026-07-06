<?php
require_once __DIR__ . '/includes/functions.php';
startSession(); $user = auth(); $db = Database::getInstance();
$slug = clean($_GET['slug'] ?? '');
$seller = $db->fetchOne("SELECT sp.*, u.name as owner_name FROM seller_profiles sp JOIN users u ON u.id=sp.user_id WHERE sp.store_slug=?", [$slug]);
if (!$seller) { setFlash('error', 'Toko tidak ditemukan'); redirect('/index.php'); }
$page = max(1, (int)($_GET['page'] ?? 1));
$total = $db->fetchOne("SELECT COUNT(*) c FROM products WHERE seller_id=? AND status='active'", [$seller['id']])['c'];
$pg = paginate($total, $page);
$products = $db->fetchAll("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) as img FROM products p WHERE p.seller_id=? AND p.status='active' ORDER BY p.created_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}", [$seller['id']]);
$pageTitle = clean($seller['store_name']);
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <!-- Store Header -->
  <div class="card mb-4 overflow-hidden">
    <?php if ($seller['store_banner']): ?><img src="/<?= clean($seller['store_banner']) ?>" style="width:100%;height:160px;object-fit:cover" alt=""><?php else: ?><div style="background:linear-gradient(135deg,#1E3A8A,#2563EB);height:100px"></div><?php endif; ?>
    <div class="card-body d-flex align-items-end gap-3" style="margin-top:<?= $seller['store_banner'] ? '-40px' : '-30px' ?>">
      <?php if ($seller['store_logo']): ?>
        <img src="/<?= clean($seller['store_logo']) ?>" class="rounded-circle" style="width:70px;height:70px;object-fit:cover;border:3px solid white">
      <?php else: ?>
        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:70px;height:70px;background:var(--primary);color:white;font-size:30px;border:3px solid white"><?= strtoupper(substr($seller['store_name'], 0, 1)) ?></div>
      <?php endif; ?>
      <div>
        <h4 class="fw-bold mb-0"><?= clean($seller['store_name']) ?></h4>
        <div class="small text-secondary d-flex gap-3">
          <?php if ($seller['store_city']): ?><span><i class="bi bi-geo-alt me-1"></i><?= clean($seller['store_city']) ?></span><?php endif; ?>
          <span><i class="bi bi-star-fill text-warning me-1"></i><?= number_format($seller['rating'], 1) ?></span>
          <span><i class="bi bi-bag me-1"></i><?= $seller['total_sales'] ?> penjualan</span>
        </div>
        <?php if ($seller['is_verified']): ?><span class="badge bg-success mt-1">✓ Penjual Terverifikasi</span><?php endif; ?>
      </div>
    </div>
    <?php if ($seller['store_description']): ?><div class="card-body pt-0 text-secondary small"><?= clean($seller['store_description']) ?></div><?php endif; ?>
  </div>

  <h6 class="fw-bold mb-3">Produk Toko (<?= $total ?>)</h6>
  <div class="row g-3">
    <?php foreach ($products as $p): ?>
    <div class="col-6 col-md-4 col-lg-3">
      <a href="/product.php?slug=<?= urlencode($p['slug']) ?>" class="text-decoration-none">
        <div class="product-card">
          <div class="img-wrap"><img src="<?= $p['img'] ? '/' . clean($p['img']) : '/assets/img/no-image.svg' ?>" alt="" loading="lazy"></div>
          <div class="card-body"><div class="fw-medium text-truncate text-dark mb-1"><?= clean($p['name']) ?></div><div class="price"><?= formatRupiah($p['price']) ?></div></div>
        </div>
      </a>
    </div>
    <?php endforeach; ?>
    <?php if (empty($products)): ?><div class="col-12 text-center py-5 text-secondary"><i class="bi bi-box-seam fs-1 d-block mb-2"></i>Toko belum memiliki produk</div><?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
