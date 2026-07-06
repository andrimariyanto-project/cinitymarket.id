<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin(); $user = auth(); $db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $pid = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle' && $pid) {
        $exists = $db->fetchOne("SELECT id FROM wishlists WHERE user_id=? AND product_id=?", [$user['id'], $pid]);
        if ($exists) $db->query("DELETE FROM wishlists WHERE id=?", [$exists['id']]);
        else $db->insert('wishlists', ['user_id' => $user['id'], 'product_id' => $pid]);
        if (isset($_POST['ajax'])) { echo json_encode(['success'=>true,'in_wishlist'=>!$exists]); exit; }
    }
    redirect($_SERVER['HTTP_REFERER'] ?? '/buyer/wishlist.php');
}

$items = $db->fetchAll("SELECT p.*,sp.store_name,sp.store_slug,(SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) as img FROM wishlists w JOIN products p ON p.id=w.product_id JOIN seller_profiles sp ON sp.id=p.seller_id WHERE w.user_id=? ORDER BY w.created_at DESC", [$user['id']]);
$pageTitle = 'Wishlist';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h5 class="fw-bold mb-4"><i class="bi bi-heart me-2"></i>Wishlist (<?= count($items) ?>)</h5>
  <?php if (empty($items)): ?>
    <div class="text-center py-5"><i class="bi bi-heart fs-1 text-secondary"></i><p class="text-secondary mt-2">Wishlist kosong</p><a href="/index.php" class="btn btn-primary">Mulai Belanja</a></div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($items as $p): ?>
    <div class="col-6 col-md-4 col-lg-3">
      <div class="product-card position-relative">
        <form method="POST" class="position-absolute top-0 end-0 m-2" style="z-index:1">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
          <button class="btn btn-sm btn-light rounded-circle" style="width:32px;height:32px;padding:0;border:none;box-shadow:0 1px 3px rgba(0,0,0,.2)" title="Hapus dari wishlist">❤️</button>
        </form>
        <a href="/product.php?slug=<?= urlencode($p['slug']) ?>" class="text-decoration-none">
          <div class="img-wrap"><img src="<?= $p['img'] ? '/' . clean($p['img']) : '/assets/img/no-image.svg' ?>" alt="" loading="lazy"></div>
          <div class="card-body">
            <div class="fw-medium text-truncate text-dark mb-1"><?= clean($p['name']) ?></div>
            <div class="price mb-1"><?= formatRupiah($p['price']) ?></div>
            <div class="store"><?= clean($p['store_name']) ?></div>
          </div>
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
