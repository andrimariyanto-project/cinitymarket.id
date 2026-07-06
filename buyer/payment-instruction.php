<?php
require_once __DIR__ . '/../includes/functions.php';
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
if (!$order) redirect('/buyer/orders.php');

$payment = $db->fetchOne("SELECT * FROM payments WHERE order_id=?", [$order['id']]);
$items   = $db->fetchAll("SELECT * FROM order_items WHERE order_id=?", [$order['id']]);

$pageTitle = 'Instruksi Pembayaran';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width:580px">

  <!-- Success header -->
  <div class="text-center mb-5">
    <div style="width:72px;height:72px;background:var(--success-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:36px">✅</div>
    <h2 style="font-weight:800;font-size:22px;letter-spacing:-.3px">Pesanan Berhasil Dibuat!</h2>
    <p style="color:var(--text-2);font-size:14px;margin-top:6px">Selesaikan pembayaran untuk memproses pesanan Anda</p>
  </div>

  <!-- Progress -->
  <div class="order-progress mb-4">
    <div class="progress-step done"><div class="step-circle"><i class="bi bi-check"></i></div><div class="step-label">Keranjang</div></div>
    <div class="progress-step done"><div class="step-circle"><i class="bi bi-check"></i></div><div class="step-label">Alamat</div></div>
    <div class="progress-step active"><div class="step-circle"><i class="bi bi-clock"></i></div><div class="step-label">Bayar</div></div>
    <div class="progress-step"><div class="step-circle"><i class="bi bi-bag-check"></i></div><div class="step-label">Selesai</div></div>
  </div>

  <!-- Order Info -->
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <div style="font-size:12px;color:var(--text-3);font-weight:600">NO. PESANAN</div>
          <div style="font-weight:800;font-size:16px;color:var(--brand)">#<?= clean($orderNum) ?></div>
        </div>
        <span class="status-chip status-payment_pending"><?= ucfirst(str_replace('_',' ',$order['status'])) ?></span>
      </div>
      <div style="font-size:13px;border-top:1px solid var(--border);padding-top:12px">
        <div class="d-flex justify-content-between mb-1 text-secondary"><span>Toko</span><span class="text-dark fw-semibold"><?= clean($order['store_name']) ?></span></div>
        <div class="d-flex justify-content-between mb-1 text-secondary"><span>Kurir Toko</span><span><?= formatRupiah($order['shipping_fee']) ?></span></div>
        <div class="d-flex justify-content-between mb-1 text-secondary"><span>Biaya Admin</span><span><?= formatRupiah($order['admin_fee']) ?></span></div>
        <div class="d-flex justify-content-between mt-2 pt-2" style="border-top:1px solid var(--border);font-size:16px;font-weight:800">
          <span>Total Bayar</span><span style="color:var(--brand)"><?= formatRupiah($order['total']) ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Payment Instruction -->
  <?php if ($payment && $payment['status'] === 'UNPAID'): ?>
  <div class="card mb-3">
    <div class="card-header d-flex align-items-center gap-2">
      <i class="bi bi-credit-card-2-front-fill text-primary"></i>
      Instruksi Pembayaran — <?= clean($payment['payment_name']) ?>
    </div>
    <div class="card-body">
      <?php if ($payment['pay_code']): ?>
      <div class="text-center mb-3 p-3 rounded-3" style="background:var(--surface-2)">
        <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-3);margin-bottom:6px">
          <?= stripos($payment['payment_method'], 'VA') !== false ? 'Nomor Virtual Account' : 'Kode Pembayaran' ?>
        </div>
        <div id="payCode" style="font-size:28px;font-weight:800;color:var(--brand);letter-spacing:3px;font-variant-numeric:tabular-nums">
          <?= clean($payment['pay_code']) ?>
        </div>
        <button onclick="copyPayCode()" class="btn btn-outline-primary btn-sm mt-2" id="copyBtn">
          <i class="bi bi-clipboard me-1"></i>Salin Kode
        </button>
      </div>
      <?php endif; ?>

      <?php if ($payment['pay_url']): ?>
      <a href="<?= clean($payment['pay_url']) ?>" target="_blank" class="btn btn-primary w-100 btn-lg mb-3">
        <i class="bi bi-box-arrow-up-right me-2"></i>Bayar via Aplikasi / Website
      </a>
      <?php endif; ?>

      <?php if ($payment['expired_at']): ?>
      <div class="text-center p-2 rounded-2" style="background:var(--warning-bg);font-size:13px;color:var(--warning)">
        <i class="bi bi-clock me-1"></i>Bayar sebelum
        <strong><?= formatDate($payment['expired_at'], 'd M Y H:i') ?></strong>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php elseif ($payment && $payment['status'] === 'PAID'): ?>
  <div class="card mb-3 p-4 text-center" style="border-color:var(--success)">
    <i class="bi bi-patch-check-fill text-success" style="font-size:40px"></i>
    <h5 class="mt-3 text-success">Pembayaran Dikonfirmasi!</h5>
    <p class="text-secondary small">Pesanan Anda sedang diproses oleh penjual</p>
  </div>
  <?php else: ?>
  <div class="card mb-3 p-4 text-center">
    <a href="/buyer/payment.php?order=<?= clean($orderNum) ?>" class="btn btn-warning btn-lg">
      <i class="bi bi-credit-card me-2"></i>Pilih Metode Pembayaran
    </a>
  </div>
  <?php endif; ?>

  <!-- Apa selanjutnya -->
  <div class="card mb-4" style="border:none;background:var(--surface-2)">
    <div class="card-body">
      <div style="font-size:13px;font-weight:700;margin-bottom:12px;color:var(--text-1)">Apa yang terjadi selanjutnya?</div>
      <?php foreach ([
        ['1','Selesaikan pembayaran di atas'],
        ['2','Penjual menerima notifikasi & mengkonfirmasi pesanan'],
        ['3','Pesanan diproses & disiapkan kurir toko'],
        ['4','Pesanan diantar ke alamat Anda'],
        ['5','Konfirmasi terima → Selesai!'],
      ] as [$n, $txt]): ?>
      <div class="d-flex gap-3 mb-2">
        <div style="width:22px;height:22px;border-radius:50%;background:var(--brand);color:white;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px"><?= $n ?></div>
        <span style="font-size:13px;color:var(--text-2)"><?= $txt ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="d-flex gap-3">
    <a href="/buyer/orders.php" class="btn btn-outline-secondary flex-fill">Lihat Pesanan</a>
    <a href="/index.php" class="btn btn-primary flex-fill">Lanjut Belanja</a>
  </div>
</div>

<script>
function copyPayCode() {
  const code = document.getElementById('payCode').textContent.trim();
  navigator.clipboard.writeText(code).then(() => {
    const btn = document.getElementById('copyBtn');
    btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Tersalin!';
    btn.classList.replace('btn-outline-primary', 'btn-success');
    setTimeout(() => {
      btn.innerHTML = '<i class="bi bi-clipboard me-1"></i>Salin Kode';
      btn.classList.replace('btn-success', 'btn-outline-primary');
    }, 2000);
  });
}
// Auto dismiss after 30s if paid
<?php if ($payment && $payment['status'] === 'PAID'): ?>
setTimeout(() => location.href = '/buyer/orders.php', 4000);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
