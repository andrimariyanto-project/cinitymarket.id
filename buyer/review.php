<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin(); $user = auth(); $db = Database::getInstance();

$orderId = (int)($_GET['order'] ?? 0);
$order   = $db->fetchOne("SELECT o.*, sp.store_name FROM orders o JOIN seller_profiles sp ON sp.id=o.seller_id WHERE o.id=? AND o.buyer_id=? AND o.status IN ('completed','delivered')", [$orderId, $user['id']]);
if (!$order) { setFlash('error', 'Pesanan tidak valid untuk diulas'); redirect('/buyer/orders.php'); }

$items   = $db->fetchAll("SELECT oi.*, p.id as product_id, p.slug FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?", [$orderId]);
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $db->beginTransaction();
    try {
        foreach ($items as $it) {
            $rating  = (int)($_POST['rating_' . $it['id']] ?? 0);
            $comment = trim($_POST['comment_' . $it['id']] ?? '');
            if ($rating < 1 || $rating > 5) continue;
            // Check if already reviewed
            $exists = $db->fetchOne("SELECT id FROM reviews WHERE order_id=? AND product_id=?", [$orderId, $it['product_id']]);
            if (!$exists) {
                $db->insert('reviews', [
                    'order_id'   => $orderId,
                    'product_id' => $it['product_id'],
                    'buyer_id'   => $user['id'],
                    'seller_id'  => $order['seller_id'],
                    'rating'     => $rating,
                    'comment'    => $comment
                ]);
                // Update product rating
                $avg = $db->fetchOne("SELECT AVG(rating) as avg FROM reviews WHERE product_id=?", [$it['product_id']]);
                $db->update('products', ['rating' => round($avg['avg'], 2)], 'id=?', [$it['product_id']]);
            }
        }
        $db->commit();
        setFlash('success', 'Ulasan berhasil dikirim. Terima kasih!');
        redirect('/buyer/orders.php');
    } catch (Exception $e) {
        $db->rollback();
        $errors[] = 'Gagal menyimpan ulasan.';
    }
}
$pageTitle = 'Beri Ulasan';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4" style="max-width:650px">
  <h5 class="fw-bold mb-4"><i class="bi bi-star me-2"></i>Beri Ulasan Pesanan #<?= clean($order['order_number']) ?></h5>
  <?php if ($errors): ?><div class="alert alert-danger small"><?= implode('<br>', array_map('clean', $errors)) ?></div><?php endif; ?>
  <form method="POST">
    <?= csrfField() ?>
    <?php foreach ($items as $it): ?>
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
          <img src="<?= $it['product_image'] ? '/' . clean($it['product_image']) : '/assets/img/no-image.svg' ?>" class="img-thumb" alt="">
          <div class="fw-medium"><?= clean($it['product_name']) ?></div>
        </div>
        <!-- Star Rating -->
        <div class="mb-2">
          <label class="form-label fw-medium small">Rating *</label>
          <div class="d-flex gap-2" id="stars-<?= $it['id'] ?>">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <label class="fs-4" style="cursor:pointer;color:#CBD5E1" id="star-<?= $it['id'] ?>-<?= $i ?>">
              <input type="radio" name="rating_<?= $it['id'] ?>" value="<?= $i ?>" class="d-none" onchange="setStars(<?= $it['id'] ?>, <?= $i ?>)">★
            </label>
            <?php endfor; ?>
          </div>
        </div>
        <div>
          <label class="form-label fw-medium small">Komentar</label>
          <textarea name="comment_<?= $it['id'] ?>" class="form-control" rows="3" placeholder="Bagaimana pengalaman belanja Anda?"></textarea>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <div class="d-flex gap-2">
      <a href="/buyer/orders.php" class="btn btn-outline-secondary">Nanti Saja</a>
      <button type="submit" class="btn btn-primary fw-semibold flex-grow-1"><i class="bi bi-send me-2"></i>Kirim Ulasan</button>
    </div>
  </form>
</div>
<script>
function setStars(itemId, rating) {
  for (let i = 1; i <= 5; i++) {
    const star = document.getElementById('star-' + itemId + '-' + i);
    star.style.color = i <= rating ? '#F59E0B' : '#CBD5E1';
  }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
