<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$user = auth();
$db   = Database::getInstance();

$cartItems = $db->fetchAll("
    SELECT c.*, p.name, p.price, p.stock, p.slug,
           (SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) as img,
           sp.id as seller_id, sp.store_name
    FROM cart c
    JOIN products p  ON p.id  = c.product_id
    JOIN seller_profiles sp ON sp.id = p.seller_id
    WHERE c.user_id = ?
", [$user['id']]);

if (empty($cartItems)) { setFlash('error', 'Keranjang kosong'); redirect('/buyer/cart.php'); }

// Group by seller
$bySeller = [];
foreach ($cartItems as $it) {
    $bySeller[$it['seller_id']]['name']    = $it['store_name'];
    $bySeller[$it['seller_id']]['items'][] = $it;
}
$sellerCount = count($bySeller);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $shipName  = trim($_POST['ship_name']  ?? '');
    $shipPhone = trim($_POST['ship_phone'] ?? '');
    $shipAddr  = trim($_POST['ship_address'] ?? '');
    $shipCity  = trim($_POST['ship_city']   ?? '');
    $note      = trim($_POST['note']        ?? '');

    if (!$shipName || !$shipPhone || !$shipAddr || !$shipCity) {
        setFlash('error', 'Lengkapi alamat pengiriman');
        redirect('/buyer/checkout.php');
    }

    // Validate stock
    foreach ($cartItems as $it) {
        $p = $db->fetchOne("SELECT stock FROM products WHERE id=?", [$it['product_id']]);
        if (!$p || $p['stock'] < $it['quantity']) {
            setFlash('error', 'Stok "' . $it['name'] . '" tidak mencukupi');
            redirect('/buyer/cart.php');
        }
    }

    $db->beginTransaction();
    try {
        $firstOrderNum = null;
        foreach ($bySeller as $sellerId => $sellerData) {
            $items    = $sellerData['items'];
            $subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
            $total    = $subtotal + ADMIN_FEE + COURIER_STORE_FEE;
            $orderNum = generateOrderNumber();

            $orderId = $db->insert('orders', [
                'order_number'     => $orderNum,
                'buyer_id'         => $user['id'],
                'seller_id'        => $sellerId,
                'status'           => 'pending',
                'subtotal'         => $subtotal,
                'admin_fee'        => ADMIN_FEE,
                'shipping_fee'     => COURIER_STORE_FEE,
                'total'            => $total,
                'shipping_method'  => 'courier_store',
                'shipping_name'    => $shipName,
                'shipping_phone'   => $shipPhone,
                'shipping_address' => $shipAddr,
                'shipping_city'    => $shipCity,
                'note'             => $note,
            ]);

            foreach ($items as $it) {
                $db->insert('order_items', [
                    'order_id'      => $orderId,
                    'product_id'    => $it['product_id'],
                    'product_name'  => $it['name'],
                    'product_image' => $it['img'] ?? '',
                    'price'         => $it['price'],
                    'quantity'      => $it['quantity'],
                    'subtotal'      => $it['price'] * $it['quantity'],
                ]);
                $db->query("UPDATE products SET stock = stock - ? WHERE id = ?",
                    [$it['quantity'], $it['product_id']]);
            }

            // Notify seller
            $sellerUser = $db->fetchOne("SELECT user_id FROM seller_profiles WHERE id=?", [$sellerId]);
            if ($sellerUser) {
                sendNotification($sellerUser['user_id'], 'Pesanan Baru! 🛍️',
                    'Pesanan #' . $orderNum . ' menunggu konfirmasi.',
                    'order', '/seller/order-detail.php?id=' . $orderId);
            }

            if (!$firstOrderNum) $firstOrderNum = $orderNum;
        }

        $db->query("DELETE FROM cart WHERE user_id = ?", [$user['id']]);
        $db->commit();

        redirect('/buyer/payment.php?order=' . $firstOrderNum);
    } catch (Exception $e) {
        $db->rollback();
        error_log($e->getMessage());
        setFlash('error', 'Gagal membuat pesanan. Coba lagi.');
        redirect('/buyer/checkout.php');
    }
}

$profile    = $db->fetchOne("SELECT * FROM users WHERE id=?", [$user['id']]);
$subtotalAll = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
$totalAll    = $subtotalAll + (ADMIN_FEE * $sellerCount) + (COURIER_STORE_FEE * $sellerCount);

$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width:960px">

  <!-- Page header -->
  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="/buyer/cart.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Checkout</h1>
  </div>

  <!-- Progress -->
  <div class="order-progress mb-4">
    <div class="progress-step done">
      <div class="step-circle"><i class="bi bi-check"></i></div>
      <div class="step-label">Keranjang</div>
    </div>
    <div class="progress-step active">
      <div class="step-circle"><i class="bi bi-geo-alt"></i></div>
      <div class="step-label">Alamat</div>
    </div>
    <div class="progress-step">
      <div class="step-circle"><i class="bi bi-credit-card"></i></div>
      <div class="step-label">Bayar</div>
    </div>
    <div class="progress-step">
      <div class="step-circle"><i class="bi bi-bag-check"></i></div>
      <div class="step-label">Selesai</div>
    </div>
  </div>

  <form method="POST" id="checkoutForm">
  <?= csrfField() ?>
  <div class="row g-4">
    <div class="col-lg-7">

      <!-- Alamat Pengiriman -->
      <div class="checkout-card">
        <div class="section-eyebrow"><i class="bi bi-geo-alt-fill"></i>Alamat Pengiriman</div>
        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label">Nama Penerima *</label>
            <input type="text" name="ship_name" class="form-control"
                   value="<?= clean($profile['name'] ?? '') ?>" required placeholder="Nama lengkap penerima">
          </div>
          <div class="col-sm-6">
            <label class="form-label">No. HP *</label>
            <input type="tel" name="ship_phone" class="form-control"
                   value="<?= clean($profile['phone'] ?? '') ?>" required placeholder="08xxxxxxxxxx">
          </div>
          <div class="col-12">
            <label class="form-label">Alamat Lengkap *</label>
            <textarea name="ship_address" class="form-control" rows="2" required
                      placeholder="Nama jalan, nomor, RT/RW, kelurahan..."><?= clean($profile['address'] ?? '') ?></textarea>
          </div>
          <div class="col-sm-8">
            <label class="form-label">Kota / Kecamatan *</label>
            <input type="text" name="ship_city" class="form-control"
                   value="<?= clean($profile['city'] ?? '') ?>" required placeholder="Kota atau kecamatan">
          </div>
          <div class="col-sm-4">
            <label class="form-label">Kode Pos</label>
            <input type="text" name="ship_postal" class="form-control"
                   value="<?= clean($profile['postal_code'] ?? '') ?>" placeholder="12345">
          </div>
        </div>
      </div>

      <!-- Pengiriman — fixed kurir toko -->
      <div class="checkout-card">
        <div class="section-eyebrow"><i class="bi bi-truck-front-fill"></i>Pengiriman</div>
        <div class="d-flex align-items-center justify-content-between p-3 rounded-3"
             style="background:var(--brand-light);border:2px solid var(--brand)">
          <div class="d-flex align-items-center gap-3">
            <div style="width:40px;height:40px;background:var(--brand);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:18px">
              <i class="bi bi-truck-front-fill"></i>
            </div>
            <div>
              <div style="font-weight:700;color:var(--brand)">Kurir Toko</div>
              <div style="font-size:12.5px;color:var(--text-2)">Diantar langsung oleh kurir toko · Estimasi 1–2 hari</div>
            </div>
          </div>
          <div style="font-weight:800;color:var(--brand);font-size:15px"><?= formatRupiah(COURIER_STORE_FEE) ?></div>
        </div>
        <div class="mt-2 px-1" style="font-size:12.5px;color:var(--text-3)">
          <i class="bi bi-info-circle me-1"></i>Biaya per penjual · Pengiriman lokal area toko
        </div>
      </div>

      <!-- Catatan -->
      <div class="checkout-card">
        <div class="section-eyebrow"><i class="bi bi-chat-text-fill"></i>Catatan (Opsional)</div>
        <textarea name="note" class="form-control" rows="2"
                  placeholder="Catatan untuk penjual, misal: warna, ukuran, instruksi khusus..."></textarea>
      </div>

      <!-- Item list -->
      <div class="checkout-card">
        <div class="section-eyebrow"><i class="bi bi-box-seam-fill"></i>Rincian Produk</div>
        <?php foreach ($bySeller as $sid => $sellerData): ?>
        <div class="mb-3">
          <div style="font-size:12.5px;font-weight:700;color:var(--text-2);margin-bottom:8px">
            <i class="bi bi-shop me-1"></i><?= clean($sellerData['name']) ?>
          </div>
          <?php foreach ($sellerData['items'] as $it): ?>
          <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--border)">
            <img src="<?= $it['img'] ? '/' . clean($it['img']) : '/assets/img/no-image.svg' ?>"
                 class="img-thumb" alt="">
            <div style="flex:1;min-width:0">
              <div style="font-weight:600;font-size:13.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= clean($it['name']) ?></div>
              <div style="font-size:12.5px;color:var(--text-3)"><?= formatRupiah($it['price']) ?> × <?= $it['quantity'] ?></div>
            </div>
            <div style="font-weight:700;white-space:nowrap"><?= formatRupiah($it['price'] * $it['quantity']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>

    </div>

    <!-- Summary -->
    <div class="col-lg-5">
      <div class="summary-card">
        <div style="font-weight:700;font-size:15px;margin-bottom:16px">Ringkasan Pembayaran</div>

        <div class="summary-row">
          <span>Subtotal (<?= array_sum(array_column($cartItems, 'quantity')) ?> item)</span>
          <span><?= formatRupiah($subtotalAll) ?></span>
        </div>
        <?php if ($sellerCount > 1): ?>
        <div class="summary-row" style="font-size:12px;color:var(--text-3)">
          <span style="padding-left:12px">· <?= $sellerCount ?> penjual × Rp 2.000</span>
          <span></span>
        </div>
        <?php endif; ?>
        <div class="summary-row">
          <span>Kurir Toko</span>
          <span><?= formatRupiah(COURIER_STORE_FEE * $sellerCount) ?></span>
        </div>
        <div class="summary-row">
          <span>Biaya Admin</span>
          <span><?= formatRupiah(ADMIN_FEE * $sellerCount) ?></span>
        </div>

        <div class="summary-row total">
          <span>Total Pembayaran</span>
          <span class="amount"><?= formatRupiah($totalAll) ?></span>
        </div>

        <!-- Fee breakdown info -->
        <div class="mt-3 p-3 rounded-3" style="background:var(--surface-2);font-size:12px;color:var(--text-2)">
          <div class="d-flex gap-2 mb-1"><i class="bi bi-truck-front text-primary"></i><span>Kurir Toko Rp <?= number_format(COURIER_STORE_FEE, 0, ',', '.') ?>/toko</span></div>
          <div class="d-flex gap-2"><i class="bi bi-shield-check text-success"></i><span>Biaya Admin Rp <?= number_format(ADMIN_FEE, 0, ',', '.') ?>/toko (keamanan transaksi)</span></div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100 mt-4" id="checkoutBtn">
          <i class="bi bi-lock-fill me-2"></i>Lanjut ke Pembayaran
        </button>
        <a href="/buyer/cart.php" class="btn btn-outline-secondary w-100 mt-2">
          <i class="bi bi-arrow-left me-2"></i>Kembali ke Keranjang
        </a>

        <div class="text-center mt-3" style="font-size:11.5px;color:var(--text-3)">
          <i class="bi bi-shield-lock me-1"></i>Transaksi aman · Diproses via Tripay
        </div>
      </div>
    </div>
  </div>
  </form>
</div>

<script>
document.getElementById('checkoutForm').addEventListener('submit', function() {
  const btn = document.getElementById('checkoutBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
