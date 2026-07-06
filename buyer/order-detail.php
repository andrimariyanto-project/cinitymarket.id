<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin(); $user = auth(); $db = Database::getInstance();

$orderNum = clean($_GET['order'] ?? '');
$order    = $db->fetchOne("
    SELECT o.*, sp.store_name, sp.store_slug
    FROM orders o
    JOIN seller_profiles sp ON sp.id = o.seller_id
    WHERE o.order_number = ? AND o.buyer_id = ?
", [$orderNum, $user['id']]);

if (!$order) {
    setFlash('error', 'Pesanan tidak ditemukan');
    redirect('/buyer/orders.php');
}

$items   = $db->fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
$payment = $db->fetchOne("SELECT * FROM payments WHERE order_id = ?", [$order['id']]);

$pageTitle = 'Detail Pesanan #' . $orderNum;
require_once __DIR__ . '/../includes/header.php';

$statusOrder = ['pending','payment_pending','paid','confirmed','processing','shipped','delivered','completed'];
$currentIdx  = array_search($order['status'], $statusOrder);
$steps = [
    'payment_pending' => ['label'=>'Menunggu Bayar','icon'=>'bi-hourglass'],
    'paid'            => ['label'=>'Bayar Lunas','icon'=>'bi-credit-card'],
    'confirmed'       => ['label'=>'Dikonfirmasi','icon'=>'bi-shop'],
    'processing'      => ['label'=>'Diproses','icon'=>'bi-gear'],
    'shipped'         => ['label'=>'Dikirim','icon'=>'bi-truck'],
    'completed'       => ['label'=>'Selesai','icon'=>'bi-bag-check'],
];
?>
<div class="container py-4" style="max-width: 900px">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h5 class="fw-bold mb-0">Pesanan #<?= clean($orderNum) ?></h5>
      <div class="text-secondary small"><?= formatDate($order['created_at'], 'd M Y H:i') ?></div>
    </div>
    <span class="status-badge status-<?= $order['status'] ?> fs-6"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></span>
  </div>

  <!-- Progress Steps -->
  <?php if (!in_array($order['status'], ['cancelled', 'refund'])): ?>
  <div class="order-steps mb-4">
    <?php foreach ($steps as $st => $info):
      $stIdx = array_search($st, $statusOrder);
      $cls   = ($stIdx < $currentIdx) ? 'done' : ($stIdx === $currentIdx ? 'active' : '');
    ?>
    <div class="step <?= $cls ?>">
      <div class="step-dot"><?= $cls === 'done' ? '<i class="bi bi-check"></i>' : "<i class=\"{$info['icon']}\"></i>" ?></div>
      <div class="step-label"><?= $info['label'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($order['status'] === 'cancelled'): ?>
  <div class="alert alert-danger mb-4">
    <i class="bi bi-x-circle me-2"></i>
    <strong>Pesanan Dibatalkan</strong>
    <?php if ($order['cancel_reason']): ?>
      — <?= clean($order['cancel_reason']) ?>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Action Buttons -->
  <div class="d-flex gap-2 mb-4 flex-wrap">
    <?php if (in_array($order['status'], ['pending', 'payment_pending'])): ?>
      <a href="/buyer/payment.php?order=<?= clean($orderNum) ?>" class="btn btn-warning fw-semibold"><i class="bi bi-credit-card me-2"></i>Bayar Sekarang</a>
      <form method="POST" action="/buyer/order-action.php" class="d-inline">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="cancel">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <button class="btn btn-outline-danger" onclick="return confirm('Batalkan pesanan ini?')"><i class="bi bi-x me-1"></i>Batalkan</button>
      </form>
    <?php endif; ?>
    <?php if ($order['status'] === 'shipped'): ?>
      <form method="POST" action="/buyer/order-action.php">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="confirm_received">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <button class="btn btn-success fw-semibold" onclick="return confirm('Konfirmasi pesanan sudah diterima?')">
          <i class="bi bi-bag-check me-2"></i>Pesanan Diterima
        </button>
      </form>
    <?php endif; ?>
    <?php if (in_array($order['status'], ['completed', 'delivered'])): ?>
      <a href="/buyer/review.php?order=<?= $order['id'] ?>" class="btn btn-outline-primary"><i class="bi bi-star me-2"></i>Beri Ulasan</a>
    <?php endif; ?>
    <a href="/buyer/orders.php" class="btn btn-outline-secondary ms-auto"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
  </div>

  <div class="row g-4">
    <!-- Left: Items + Shipping -->
    <div class="col-md-7">
      <!-- Order Items -->
      <div class="card mb-3">
        <div class="card-header bg-white fw-bold d-flex align-items-center gap-2">
          <i class="bi bi-shop text-primary"></i> <?= clean($order['store_name']) ?>
        </div>
        <div class="card-body p-0">
          <?php foreach ($items as $it): ?>
          <div class="d-flex align-items-center gap-3 p-3 border-bottom">
            <img src="<?= $it['product_image'] ? '/' . clean($it['product_image']) : '/assets/img/no-image.svg' ?>" class="img-thumb" alt="">
            <div class="flex-grow-1">
              <div class="fw-medium small"><?= clean($it['product_name']) ?></div>
              <div class="text-secondary small"><?= formatRupiah($it['price']) ?> × <?= $it['quantity'] ?></div>
            </div>
            <div class="fw-bold small"><?= formatRupiah($it['subtotal']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Shipping Info -->
      <div class="card mb-3">
        <div class="card-header bg-white fw-bold">Info Pengiriman</div>
        <div class="card-body small">
          <?php if ($order['shipping_method'] === 'courier_store'): ?>
          <div class="mb-2 d-flex gap-2">
            <i class="bi bi-truck text-primary mt-1"></i>
            <div>
              <div class="fw-medium">Kurir Toko — <?= formatRupiah($order['shipping_fee']) ?></div>
              <?php if ($order['tracking_number']): ?>
                <div class="text-secondary">No. Resi: <strong class="text-primary"><?= clean($order['tracking_number']) ?></strong></div>
              <?php else: ?>
                <div class="text-secondary">No. Resi belum tersedia</div>
              <?php endif; ?>
            </div>
          </div>
          <hr class="my-2">
          <div class="text-secondary mb-1">Dikirim ke:</div>
          <div class="fw-medium"><?= clean($order['shipping_name']) ?> · <?= clean($order['shipping_phone'] ?? '') ?></div>
          <div><?= clean($order['shipping_address']) ?>, <?= clean($order['shipping_city']) ?> <?= clean($order['shipping_province'] ?? '') ?></div>
          <?php endif; ?>
          <?php if ($order['note']): ?>
          <div class="mt-2 text-secondary"><i class="bi bi-chat-text me-1"></i>Catatan: <em>"<?= clean($order['note']) ?>"</em></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Right: Payment Summary -->
    <div class="col-md-5">
      <div class="card">
        <div class="card-header bg-white fw-bold">Ringkasan Pembayaran</div>
        <div class="card-body small">
          <div class="d-flex justify-content-between mb-2 text-secondary"><span>Subtotal Produk</span><span><?= formatRupiah($order['subtotal']) ?></span></div>
          <div class="d-flex justify-content-between mb-2 text-secondary"><span>Biaya Admin</span><span><?= formatRupiah($order['admin_fee']) ?></span></div>
          <div class="d-flex justify-content-between mb-2 text-secondary">
            <span>Ongkir</span>
            <span><?= $order['shipping_fee'] > 0 ? formatRupiah($order['shipping_fee']) : 'GRATIS' ?></span>
          </div>
          <hr class="my-2">
          <div class="d-flex justify-content-between fw-bold fs-6"><span>Total</span><span class="text-price"><?= formatRupiah($order['total']) ?></span></div>

          <?php if ($payment): ?>
          <hr class="my-2">
          <div class="d-flex justify-content-between text-secondary"><span>Metode Bayar</span><span><?= clean($payment['payment_name'] ?? $payment['payment_method'] ?? '-') ?></span></div>
          <div class="d-flex justify-content-between text-secondary"><span>Status</span>
            <span class="<?= $payment['status'] === 'PAID' ? 'text-success fw-bold' : '' ?>"><?= $payment['status'] ?></span>
          </div>
          <?php if ($payment['pay_code'] && $payment['status'] === 'UNPAID'): ?>
          <div class="mt-2 p-2 bg-light rounded-2 text-center">
            <div class="text-secondary" style="font-size:11px">Kode Pembayaran</div>
            <div class="fw-bold text-primary fs-5"><?= clean($payment['pay_code']) ?></div>
            <button onclick="navigator.clipboard.writeText('<?= clean($payment['pay_code']) ?>').then(()=>this.textContent='✓ Disalin')" class="btn btn-sm btn-outline-primary mt-1" style="font-size:11px">Salin Kode</button>
          </div>
          <?php endif; ?>
          <?php if ($payment['expired_at'] && $payment['status'] === 'UNPAID'): ?>
          <div class="text-secondary text-center mt-1" style="font-size:11px">Bayar sebelum <?= formatDate($payment['expired_at'], 'd M Y H:i') ?></div>
          <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
