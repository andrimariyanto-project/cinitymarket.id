<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('seller');
$user   = auth();
$db     = Database::getInstance();
$seller = $db->fetchOne("SELECT * FROM seller_profiles WHERE user_id=?", [$user['id']]);
$id     = (int)($_GET['id'] ?? 0);
$order  = $db->fetchOne("
    SELECT o.*, u.name as buyer_name, u.email as buyer_email, u.phone as buyer_phone
    FROM orders o
    JOIN users u ON u.id = o.buyer_id
    WHERE o.id = ? AND o.seller_id = ?
", [$id, $seller['id']]);
if (!$order) { setFlash('error', 'Pesanan tidak ditemukan'); redirect('/seller/orders.php'); }
$items   = $db->fetchAll("SELECT * FROM order_items WHERE order_id=?", [$id]);
$payment = $db->fetchOne("SELECT * FROM payments WHERE order_id=?", [$id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $now    = date('Y-m-d H:i:s');
    $msgs   = [
        'confirm' => ['status'=>'confirmed', 'col'=>'confirmed_at', 'notif'=>'Pesanan dikonfirmasi penjual. Sedang disiapkan.'],
        'process' => ['status'=>'processing','col'=>'','notif'=>'Pesanan sedang diproses & disiapkan oleh toko.'],
        'ship'    => ['status'=>'shipped',   'col'=>'shipped_at',   'notif'=>'Pesanan sudah dikirim oleh kurir toko.'],
    ];
    if (isset($msgs[$action])) {
        $m = $msgs[$action];
        $data = ['status' => $m['status']];
        if ($m['col']) $data[$m['col']] = $now;
        if ($action === 'ship') {
            $tracking = clean($_POST['tracking'] ?? '');
            if ($tracking) $data['tracking_number'] = $tracking;
        }
        $db->update('orders', $data, 'id=?', [$id]);
        sendNotification($order['buyer_id'],
            match($action){ 'confirm'=>'Pesanan Dikonfirmasi ✅','process'=>'Pesanan Diproses 🔧','ship'=>'Pesanan Dikirim 🚚', default=>'' },
            $m['notif'] . ' (#' . $order['order_number'] . ')',
            'order', '/buyer/order-detail.php?order=' . $order['order_number']
        );
        setFlash('success', 'Status pesanan diperbarui');
        redirect('/seller/order-detail.php?id=' . $id);
    }
}

// Refresh order
$order = $db->fetchOne("SELECT o.*, u.name as buyer_name, u.email as buyer_email, u.phone as buyer_phone FROM orders o JOIN users u ON u.id=o.buyer_id WHERE o.id=? AND o.seller_id=?", [$id, $seller['id']]);

$statusMap = ['pending'=>0,'payment_pending'=>1,'paid'=>2,'confirmed'=>3,'processing'=>4,'shipped'=>5,'delivered'=>6,'completed'=>7];
$curIdx    = $statusMap[$order['status']] ?? 0;

$pageTitle = 'Pesanan #' . $order['order_number'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width:960px">
  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="/seller/orders.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
      <h1 class="page-title mb-0">Pesanan #<?= clean($order['order_number']) ?></h1>
      <div style="font-size:12.5px;color:var(--text-2)"><?= formatDate($order['created_at'], 'd M Y · H:i') ?></div>
    </div>
    <span class="status-chip status-<?= $order['status'] ?> ms-auto"><?= ucfirst(str_replace('_',' ',$order['status'])) ?></span>
  </div>

  <!-- Progress Track -->
  <?php if (!in_array($order['status'], ['cancelled','refund'])): ?>
  <div class="order-progress mb-4">
    <?php
    $steps = [
      2 => ['Bayar Lunas',  'bi-credit-card'],
      3 => ['Dikonfirmasi', 'bi-check-circle'],
      4 => ['Diproses',     'bi-gear'],
      5 => ['Dikirim',      'bi-truck-front'],
      7 => ['Selesai',      'bi-bag-check'],
    ];
    foreach ($steps as $idx => [$label, $icon]):
      $cls = $curIdx > $idx ? 'done' : ($curIdx === $idx ? 'active' : '');
    ?>
    <div class="progress-step <?= $cls ?>">
      <div class="step-circle"><?= $cls === 'done' ? '<i class="bi bi-check"></i>' : "<i class=\"bi $icon\"></i>" ?></div>
      <div class="step-label"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Action Panel -->
  <?php if ($order['status'] === 'paid'): ?>
  <div class="card mb-4 p-4" style="border-color:var(--brand);background:var(--brand-light)">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
      <div>
        <div style="font-weight:700;font-size:15px;color:var(--brand)"><i class="bi bi-exclamation-circle-fill me-2"></i>Pesanan Baru — Perlu Konfirmasi</div>
        <div style="font-size:13px;color:var(--text-2);margin-top:4px">Pembayaran sudah lunas. Konfirmasi kesanggupan Anda untuk memproses pesanan ini.</div>
      </div>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="confirm">
        <button class="btn btn-primary" onclick="return confirm('Konfirmasi pesanan ini?')">
          <i class="bi bi-check-circle me-2"></i>Konfirmasi Pesanan
        </button>
      </form>
    </div>
  </div>
  <?php elseif ($order['status'] === 'confirmed'): ?>
  <div class="card mb-4 p-4">
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
      <div>
        <div style="font-weight:700;font-size:15px"><i class="bi bi-gear me-2 text-secondary"></i>Siap Diproses</div>
        <div style="font-size:13px;color:var(--text-2);margin-top:4px">Siapkan produk untuk dikirim via kurir toko.</div>
      </div>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="process">
        <button class="btn btn-outline-primary"><i class="bi bi-gear me-2"></i>Tandai Sedang Diproses</button>
      </form>
    </div>
  </div>
  <?php elseif ($order['status'] === 'processing'): ?>
  <div class="card mb-4 p-4">
    <div style="font-weight:700;font-size:15px;margin-bottom:12px"><i class="bi bi-truck-front me-2 text-success"></i>Kirim Pesanan</div>
    <form method="POST" class="d-flex gap-3 flex-wrap align-items-end">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="ship">
      <div style="flex:1;min-width:200px">
        <label class="form-label">No. Resi / Kode Pengiriman (opsional)</label>
        <input type="text" name="tracking" class="form-control" placeholder="Masukkan no. resi jika ada">
      </div>
      <button class="btn btn-success"><i class="bi bi-truck-front me-2"></i>Tandai Sudah Dikirim</button>
    </form>
  </div>
  <?php elseif ($order['status'] === 'shipped'): ?>
  <div class="card mb-4 p-4 text-center" style="border-color:var(--success)">
    <i class="bi bi-truck-front-fill text-success" style="font-size:32px"></i>
    <div style="font-weight:700;margin-top:8px">Pesanan Sedang Dikirim</div>
    <div style="font-size:13px;color:var(--text-2)">Menunggu konfirmasi terima dari pembeli</div>
    <?php if ($order['tracking_number']): ?>
    <div class="mt-2 d-inline-block px-3 py-1 rounded-pill" style="background:var(--success-bg);color:var(--success);font-weight:700;font-size:14px">
      No. Resi: <?= clean($order['tracking_number']) ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-md-7">
      <!-- Items -->
      <div class="card mb-3">
        <div class="card-header">Produk Dipesan</div>
        <div class="card-body" style="padding:0">
          <?php foreach ($items as $it): ?>
          <div class="d-flex align-items-center gap-3 p-4" style="border-bottom:1px solid var(--border)">
            <img src="<?= $it['product_image'] ? '/'.clean($it['product_image']) : '/assets/img/no-image.svg' ?>" class="img-thumb" alt="">
            <div style="flex:1">
              <div style="font-weight:600;font-size:14px"><?= clean($it['product_name']) ?></div>
              <div style="font-size:13px;color:var(--text-2)"><?= formatRupiah($it['price']) ?> × <?= $it['quantity'] ?></div>
            </div>
            <div style="font-weight:800;font-size:14px"><?= formatRupiah($it['subtotal']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Pembeli -->
      <div class="card">
        <div class="card-header">Info Pembeli & Pengiriman</div>
        <div class="card-body" style="font-size:13.5px">
          <div class="row g-2">
            <div class="col-6">
              <div style="color:var(--text-3);font-size:12px;font-weight:600;text-transform:uppercase">Nama</div>
              <div style="font-weight:600"><?= clean($order['buyer_name']) ?></div>
            </div>
            <div class="col-6">
              <div style="color:var(--text-3);font-size:12px;font-weight:600;text-transform:uppercase">No. HP</div>
              <div style="font-weight:600"><?= clean($order['buyer_phone'] ?? '-') ?></div>
            </div>
            <div class="col-12 mt-2">
              <div style="color:var(--text-3);font-size:12px;font-weight:600;text-transform:uppercase">Alamat Pengiriman</div>
              <div style="font-weight:600;margin-top:4px"><?= clean($order['shipping_name']) ?></div>
              <div style="color:var(--text-2)"><?= clean($order['shipping_address']) ?>, <?= clean($order['shipping_city']) ?></div>
            </div>
            <?php if ($order['note']): ?>
            <div class="col-12 mt-2">
              <div style="color:var(--text-3);font-size:12px;font-weight:600;text-transform:uppercase">Catatan</div>
              <div style="color:var(--text-2);font-style:italic">"<?= clean($order['note']) ?>"</div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-5">
      <div class="card">
        <div class="card-header">Ringkasan Pembayaran</div>
        <div class="card-body" style="font-size:13.5px">
          <div class="d-flex justify-content-between mb-2 text-secondary"><span>Subtotal Produk</span><span><?= formatRupiah($order['subtotal']) ?></span></div>
          <div class="d-flex justify-content-between mb-2 text-secondary"><span>Kurir Toko</span><span><?= formatRupiah($order['shipping_fee']) ?></span></div>
          <div class="d-flex justify-content-between mb-2 text-secondary"><span>Biaya Admin</span><span><?= formatRupiah($order['admin_fee']) ?></span></div>
          <div class="d-flex justify-content-between pt-3 mt-1" style="border-top:1px solid var(--border);font-size:16px;font-weight:800">
            <span>Total</span><span style="color:var(--brand)"><?= formatRupiah($order['total']) ?></span>
          </div>
          <?php if ($payment): ?>
          <div style="border-top:1px solid var(--border);margin-top:16px;padding-top:12px">
            <div class="d-flex justify-content-between mb-1 text-secondary"><span>Metode Bayar</span><span><?= clean($payment['payment_name'] ?? '-') ?></span></div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-secondary">Status</span>
              <span style="font-weight:700;color:<?= $payment['status']==='PAID'?'var(--success)':'var(--warning)' ?>"><?= $payment['status'] ?></span>
            </div>
            <?php if ($payment['paid_at']): ?>
            <div class="d-flex justify-content-between text-secondary"><span>Dibayar</span><span><?= formatDate($payment['paid_at'], 'd M Y H:i') ?></span></div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
