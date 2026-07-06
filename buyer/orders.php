<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$user = auth();
$db   = Database::getInstance();

$status = clean($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$where  = "o.buyer_id = ?";
$params = [$user['id']];
if ($status) { $where .= " AND o.status = ?"; $params[] = $status; }

$total   = $db->fetchOne("SELECT COUNT(*) c FROM orders o WHERE $where", $params)['c'];
$pg      = paginate($total, $page, 10);
$orders  = $db->fetchAll("
    SELECT o.*, sp.store_name,
           (SELECT oi.product_image FROM order_items oi WHERE oi.order_id=o.id LIMIT 1) as thumb,
           (SELECT COUNT(*) FROM order_items WHERE order_id=o.id) as item_count
    FROM orders o
    JOIN seller_profiles sp ON sp.id = o.seller_id
    WHERE $where
    ORDER BY o.created_at DESC
    LIMIT {$pg['per_page']} OFFSET {$pg['offset']}
", $params);

$tabs = [
  ''             => 'Semua',
  'payment_pending' => 'Belum Bayar',
  'paid'         => 'Dibayar',
  'processing'   => 'Diproses',
  'shipped'      => 'Dikirim',
  'completed'    => 'Selesai',
  'cancelled'    => 'Dibatalkan',
];

$pageTitle = 'Pesanan Saya';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4" style="max-width:800px">
  <h1 class="page-title mb-4">Pesanan Saya</h1>

  <!-- Tab filter -->
  <div class="d-flex gap-2 flex-wrap mb-4" style="overflow-x:auto;padding-bottom:4px">
    <?php foreach ($tabs as $k => $v):
      $count = $k ? $db->fetchOne("SELECT COUNT(*) c FROM orders WHERE buyer_id=? AND status=?", [$user['id'], $k])['c'] : null;
    ?>
    <a href="/buyer/orders.php<?= $k ? '?status='.$k : '' ?>"
       class="btn btn-sm <?= $status === $k ? 'btn-primary' : 'btn-outline-secondary' ?>"
       style="white-space:nowrap">
      <?= $v ?>
      <?php if ($count): ?><span class="ms-1 badge <?= $status === $k ? 'bg-white text-primary' : 'bg-secondary' ?>"><?= $count ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($orders)): ?>
  <div class="empty-state">
    <i class="bi bi-bag empty-state-icon"></i>
    <h5>Belum ada pesanan</h5>
    <p>Pesanan Anda akan tampil di sini setelah checkout</p>
    <a href="/index.php" class="btn btn-primary">Mulai Belanja</a>
  </div>
  <?php else: ?>

  <div class="d-flex flex-column gap-3">
  <?php foreach ($orders as $o): ?>
  <div class="card" style="border-radius:var(--radius-lg);overflow:hidden">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom:1px solid var(--border);background:var(--surface-2)">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-shop" style="color:var(--brand)"></i>
        <span style="font-weight:600;font-size:13.5px"><?= clean($o['store_name']) ?></span>
        <span class="text-secondary" style="font-size:12px">· #<?= clean($o['order_number']) ?></span>
      </div>
      <span class="status-chip status-<?= $o['status'] ?>"><?= ucfirst(str_replace('_', ' ', $o['status'])) ?></span>
    </div>

    <!-- Body -->
    <div class="px-4 py-3 d-flex align-items-center gap-3">
      <?php if ($o['thumb']): ?>
      <img src="/<?= clean($o['thumb']) ?>" class="img-thumb" style="border-radius:10px" alt="">
      <?php endif; ?>
      <div style="flex:1;min-width:0">
        <div style="font-size:13px;color:var(--text-2)"><?= $o['item_count'] ?> produk · <?= formatDate($o['created_at'], 'd M Y') ?></div>
        <div style="font-size:15px;font-weight:800;color:var(--brand);margin-top:2px"><?= formatRupiah($o['total']) ?></div>
        <div style="font-size:12px;color:var(--text-3);margin-top:2px">
          <i class="bi bi-truck-front me-1"></i>Kurir Toko
          <?php if ($o['tracking_number']): ?> · Resi: <strong><?= clean($o['tracking_number']) ?></strong><?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="d-flex align-items-center justify-content-end gap-2 px-4 py-3" style="border-top:1px solid var(--border)">
      <?php if (in_array($o['status'], ['pending', 'payment_pending'])): ?>
        <a href="/buyer/payment.php?order=<?= clean($o['order_number']) ?>" class="btn btn-warning btn-sm fw-semibold">
          <i class="bi bi-credit-card me-1"></i>Bayar Sekarang
        </a>
        <form method="POST" action="/buyer/order-action.php" class="d-inline">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="cancel">
          <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
          <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Batalkan pesanan ini?')">Batalkan</button>
        </form>
      <?php endif; ?>
      <?php if ($o['status'] === 'shipped'): ?>
        <form method="POST" action="/buyer/order-action.php" class="d-inline">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="confirm_received">
          <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
          <button class="btn btn-success btn-sm fw-semibold" onclick="return confirm('Konfirmasi pesanan sudah diterima?')">
            <i class="bi bi-bag-check me-1"></i>Pesanan Diterima
          </button>
        </form>
      <?php endif; ?>
      <?php if (in_array($o['status'], ['completed', 'delivered'])): ?>
        <a href="/buyer/review.php?order=<?= $o['id'] ?>" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-star me-1"></i>Beri Ulasan
        </a>
      <?php endif; ?>
      <a href="/buyer/order-detail.php?order=<?= clean($o['order_number']) ?>"
         class="btn btn-outline-secondary btn-sm">Detail</a>
    </div>
  </div>
  <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($pg['total_pages'] > 1): ?>
  <nav class="mt-4"><ul class="pagination justify-content-center">
    <?php if ($pg['has_prev']): ?><li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$pg['current']-1])) ?>"><i class="bi bi-chevron-left"></i></a></li><?php endif; ?>
    <?php for ($i = max(1,$pg['current']-2); $i <= min($pg['total_pages'],$pg['current']+2); $i++): ?>
    <li class="page-item <?= $i===$pg['current']?'active':'' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
    <?php if ($pg['has_next']): ?><li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$pg['current']+1])) ?>"><i class="bi bi-chevron-right"></i></a></li><?php endif; ?>
  </ul></nav>
  <?php endif; ?>

  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
