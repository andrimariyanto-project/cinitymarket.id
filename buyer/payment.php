<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/tripay.php';
requireLogin();
$user = auth();
$db   = Database::getInstance();

$orderNum = clean($_GET['order'] ?? '');
$order    = $db->fetchOne("
    SELECT o.*, sp.store_name
    FROM orders o
    JOIN seller_profiles sp ON sp.id = o.seller_id
    WHERE o.order_number = ? AND o.buyer_id = ?
", [$orderNum, $user['id']]);

if (!$order) { setFlash('error', 'Pesanan tidak ditemukan'); redirect('/buyer/orders.php'); }
if (!in_array($order['status'], ['pending', 'payment_pending'])) {
    redirect('/buyer/order-detail.php?order=' . $orderNum);
}

$existingPayment = $db->fetchOne("SELECT * FROM payments WHERE order_id=? AND status='UNPAID'", [$order['id']]);
$tripay   = new Tripay();
$channels = [];
$res      = $tripay->getChannels();
if (!empty($res['data'])) $channels = $res['data'];

// Group channels nicely
$grouped = [];
foreach ($channels as $ch) {
    $group = $ch['group'] ?? 'Lainnya';
    $grouped[$group][] = $ch;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $method = clean($_POST['payment_method'] ?? '');
    if (!$method) {
        $error = 'Pilih metode pembayaran terlebih dahulu';
    } else {
        $items   = $db->fetchAll("SELECT * FROM order_items WHERE order_id=?", [$order['id']]);
        $tripayItems = array_map(fn($i) => [
            'name'     => $i['product_name'],
            'price'    => (int)$i['price'],
            'quantity' => (int)$i['quantity'],
        ], $items);
        $tripayItems[] = ['name' => 'Kurir Toko',   'price' => (int)$order['shipping_fee'], 'quantity' => 1];
        $tripayItems[] = ['name' => 'Biaya Admin',  'price' => (int)$order['admin_fee'],    'quantity' => 1];

        $result = $tripay->createTransaction([
            'payment_method' => $method,
            'merchant_ref'   => $order['order_number'],
            'amount'         => (int)$order['total'],
            'customer_name'  => $user['name'],
            'customer_email' => $user['email'],
            'customer_phone' => $db->fetchOne("SELECT phone FROM users WHERE id=?", [$user['id']])['phone'] ?? '08000000000',
            'order_items'    => $tripayItems,
        ]);

        if (!empty($result['success']) && !empty($result['data'])) {
            $data = $result['data'];
            $payData = [
                'tripay_reference'   => $data['reference'],
                'tripay_merchant_ref'=> $order['order_number'],
                'payment_method'     => $method,
                'payment_name'       => $data['payment_name'] ?? $method,
                'pay_code'           => $data['pay_code'] ?? '',
                'pay_url'            => $data['pay_url'] ?? '',
                'checkout_url'       => $data['checkout_url'] ?? '',
                'expired_at'         => date('Y-m-d H:i:s', $data['expired_time'] ?? time() + 86400),
            ];
            if ($existingPayment) {
                $db->update('payments', $payData, 'id=?', [$existingPayment['id']]);
            } else {
                $db->insert('payments', array_merge($payData, [
                    'order_id'     => $order['id'],
                    'amount'       => $order['total'],
                    'total_amount' => $order['total'],
                ]));
            }
            $db->update('orders', ['status' => 'payment_pending'], 'id=?', [$order['id']]);
            redirect('/buyer/payment-instruction.php?order=' . $orderNum);
        } else {
            $error = 'Gagal memproses pembayaran: ' . ($result['message'] ?? 'Coba metode lain');
        }
    }
}

$pageTitle = 'Pilih Pembayaran';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width:800px">

  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="/buyer/checkout.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Pilih Pembayaran</h1>
  </div>

  <!-- Progress -->
  <div class="order-progress mb-4">
    <div class="progress-step done"><div class="step-circle"><i class="bi bi-check"></i></div><div class="step-label">Keranjang</div></div>
    <div class="progress-step done"><div class="step-circle"><i class="bi bi-check"></i></div><div class="step-label">Alamat</div></div>
    <div class="progress-step active"><div class="step-circle"><i class="bi bi-credit-card"></i></div><div class="step-label">Bayar</div></div>
    <div class="progress-step"><div class="step-circle"><i class="bi bi-bag-check"></i></div><div class="step-label">Selesai</div></div>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger d-flex gap-2 mb-4">
    <i class="bi bi-exclamation-circle-fill mt-1"></i><span><?= clean($error) ?></span>
  </div>
  <?php endif; ?>

  <!-- Order summary pill -->
  <div class="d-flex align-items-center justify-content-between p-3 mb-4 rounded-3" style="background:var(--brand-light);border:1.5px solid var(--brand)">
    <div>
      <div style="font-size:12.5px;color:var(--brand);font-weight:600">Pesanan #<?= clean($orderNum) ?> · <?= clean($order['store_name']) ?></div>
      <div style="font-size:12px;color:var(--text-2)">Kurir Toko · Biaya Admin · Produk</div>
    </div>
    <div style="font-size:20px;font-weight:800;color:var(--brand)"><?= formatRupiah($order['total']) ?></div>
  </div>

  <form method="POST" id="payForm">
  <?= csrfField() ?>
  <input type="hidden" name="payment_method" id="selectedMethod" required>

  <?php if (empty($channels)): ?>
  <div class="card p-4 text-center">
    <i class="bi bi-wifi-off" style="font-size:40px;color:var(--text-3)"></i>
    <h5 class="mt-3">Channel pembayaran tidak tersedia</h5>
    <p class="text-secondary small">Periksa konfigurasi Tripay di pengaturan admin, atau coba lagi nanti.</p>
  </div>
  <?php else: ?>

  <div class="card mb-4">
    <div class="card-header">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-credit-card-2-front-fill text-primary"></i>
        Metode Pembayaran <small class="text-secondary fw-normal ms-1">via Tripay</small>
      </div>
    </div>
    <div class="card-body" style="padding:12px">
      <?php foreach ($grouped as $group => $chs): ?>
      <div style="margin-bottom:16px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3);padding:0 8px;margin-bottom:8px"><?= clean($group) ?></div>
        <div class="row g-2">
        <?php foreach ($chs as $ch): ?>
        <div class="col-6 col-md-4">
          <div class="pay-option" data-code="<?= clean($ch['code']) ?>"
               onclick="selectPayment('<?= clean($ch['code']) ?>', this)"
               style="border:1.5px solid var(--border);border-radius:10px;padding:10px 12px;cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:10px;background:white">
            <?php if (!empty($ch['icon_url'])): ?>
              <img src="<?= clean($ch['icon_url']) ?>" height="22" style="object-fit:contain;flex-shrink:0" alt="">
            <?php else: ?>
              <i class="bi bi-wallet2" style="font-size:20px;color:var(--brand);flex-shrink:0"></i>
            <?php endif; ?>
            <span style="font-size:13px;font-weight:600;line-height:1.3"><?= clean($ch['name']) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Selected indicator -->
  <div id="selectedInfo" style="display:none;margin-bottom:16px" class="alert alert-info d-flex align-items-center gap-2 py-2">
    <i class="bi bi-check-circle-fill text-primary"></i>
    <span>Metode dipilih: <strong id="selectedName"></strong></span>
  </div>

  <?php endif; ?>

  <div class="d-flex gap-3">
    <a href="/buyer/checkout.php" class="btn btn-outline-secondary flex-shrink-0">Kembali</a>
    <button type="submit" class="btn btn-primary flex-grow-1 btn-lg" id="payBtn" disabled>
      <i class="bi bi-lock-fill me-2"></i>Bayar <?= formatRupiah($order['total']) ?>
    </button>
  </div>
  </form>
</div>

<style>
.pay-option.selected {
  border-color: var(--brand) !important;
  background: var(--brand-light) !important;
  box-shadow: 0 0 0 3px rgba(45,107,228,.1);
}
</style>
<script>
function selectPayment(code, el) {
  document.querySelectorAll('.pay-option').forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('selectedMethod').value = code;
  document.getElementById('selectedName').textContent = el.querySelector('span').textContent;
  document.getElementById('selectedInfo').style.display = 'flex';
  document.getElementById('payBtn').disabled = false;
}
document.getElementById('payForm').addEventListener('submit', function() {
  const btn = document.getElementById('payBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
