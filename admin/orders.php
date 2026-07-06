<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin'); $db = Database::getInstance();
$status = clean($_GET['status'] ?? '');
$q = clean($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$where = '1=1'; $params = [];
if ($status) { $where .= " AND o.status=?"; $params[] = $status; }
if ($q) { $where .= " AND (o.order_number LIKE ? OR u.name LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
$total = $db->fetchOne("SELECT COUNT(*) c FROM orders o JOIN users u ON u.id=o.buyer_id WHERE $where", $params)['c'];
$pg = paginate($total, $page, 30);
$orders = $db->fetchAll("SELECT o.*,u.name as buyer_name,sp.store_name FROM orders o JOIN users u ON u.id=o.buyer_id JOIN seller_profiles sp ON sp.id=o.seller_id WHERE $where ORDER BY o.created_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}", $params);
$statuses = [
  ''=>'Semua','pending'=>'Pending','payment_pending'=>'Menunggu Bayar','paid'=>'Dibayar',
  'confirmed'=>'Dikonfirmasi','processing'=>'Diproses','shipped'=>'Dikirim','completed'=>'Selesai','cancelled'=>'Dibatalkan'
];
$pageTitle = 'Semua Pesanan - Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid py-4">
<div class="row">
<div class="col-lg-2"><div class="sidebar card p-3">
<div class="fw-bold mb-3 text-primary"><i class="bi bi-shield-check me-2"></i>Admin</div>
<nav class="nav flex-column gap-1">
<a href="/admin/dashboard.php" class="nav-link text-secondary"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
<a href="/admin/orders.php" class="nav-link text-dark fw-medium"><i class="bi bi-bag me-2"></i>Pesanan</a>
<a href="/admin/sellers.php" class="nav-link text-secondary"><i class="bi bi-shop me-2"></i>Penjual</a>
<a href="/admin/users.php" class="nav-link text-secondary"><i class="bi bi-people me-2"></i>Pengguna</a>
<a href="/admin/settings.php" class="nav-link text-secondary"><i class="bi bi-gear me-2"></i>Pengaturan</a>
</nav>
</div></div>
<div class="col-lg-10">
<div class="d-flex justify-content-between align-items-center mb-4"><h5 class="fw-bold mb-0">Semua Pesanan</h5></div>
<div class="d-flex flex-wrap gap-2 mb-3">
<form action="" method="GET" class="d-flex gap-2"><input type="text" name="q" class="form-control form-control-sm" placeholder="Cari no. pesanan / pembeli..." value="<?= clean($q) ?>" style="width:250px"><button class="btn btn-sm btn-outline-secondary">Cari</button></form>
</div>
<div class="d-flex flex-wrap gap-2 mb-3">
<?php foreach ($statuses as $k => $v): ?><a href="?status=<?= $k ?><?= $q ? '&q=' . urlencode($q) : '' ?>" class="btn btn-sm <?= $status === $k ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $v ?></a><?php endforeach; ?>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0 small">
<thead class="table-light"><tr><th>No. Pesanan</th><th>Pembeli</th><th>Toko</th><th>Total</th><th>Ongkir</th><th>Admin Fee</th><th>Status</th><th>Waktu</th></tr></thead>
<tbody>
<?php foreach ($orders as $o): ?>
<tr>
<td class="fw-medium">#<?= clean($o['order_number']) ?></td>
<td><?= clean($o['buyer_name']) ?></td>
<td><?= clean($o['store_name']) ?></td>
<td class="fw-bold"><?= formatRupiah($o['total']) ?></td>
<td><?= $o['shipping_fee'] > 0 ? formatRupiah($o['shipping_fee']) : 'Gratis' ?></td>
<td class="text-success"><?= formatRupiah($o['admin_fee']) ?></td>
<td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst(str_replace('_', ' ', $o['status'])) ?></span></td>
<td class="text-secondary"><?= timeAgo($o['created_at']) ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($orders)): ?><tr><td colspan="8" class="text-center py-4 text-secondary">Belum ada pesanan</td></tr><?php endif; ?>
</tbody>
</table></div></div>
<?php if ($pg['total_pages'] > 1): ?>
<nav class="mt-3"><ul class="pagination justify-content-center pagination-sm">
<?php for ($i = 1; $i <= $pg['total_pages']; $i++): ?><li class="page-item <?= $i === $pg['current'] ? 'active' : '' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a></li><?php endfor; ?>
</ul></nav>
<?php endif; ?>
</div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
