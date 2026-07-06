<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin'); $db=Database::getInstance();
$stats=[
  'users'    =>$db->fetchOne("SELECT COUNT(*) c FROM users WHERE role='buyer'")['c'],
  'sellers'  =>$db->fetchOne("SELECT COUNT(*) c FROM seller_profiles")['c'],
  'orders'   =>$db->fetchOne("SELECT COUNT(*) c FROM orders WHERE status NOT IN ('cancelled')")['c'],
  'revenue'  =>$db->fetchOne("SELECT COALESCE(SUM(admin_fee),0) c FROM orders WHERE status='completed'")['c'],
  'pending'  =>$db->fetchOne("SELECT COUNT(*) c FROM orders WHERE status='paid'")['c'],
];
$recentOrders=$db->fetchAll("SELECT o.*,u.name as buyer_name,sp.store_name FROM orders o JOIN users u ON u.id=o.buyer_id JOIN seller_profiles sp ON sp.id=o.seller_id ORDER BY o.created_at DESC LIMIT 15");
$pageTitle='Admin Panel';
require_once __DIR__.'/../includes/header.php';
?>
<div class="container-fluid py-4">
<div class="row">
<div class="col-lg-2">
<div class="sidebar card p-3">
<div class="fw-bold mb-3 text-primary"><i class="bi bi-shield-check me-2"></i>Admin Panel</div>
<nav class="nav flex-column gap-1">
<a href="/admin/dashboard.php" class="nav-link text-dark fw-medium"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
<a href="/admin/orders.php" class="nav-link text-secondary"><i class="bi bi-bag me-2"></i>Semua Pesanan</a>
<a href="/admin/sellers.php" class="nav-link text-secondary"><i class="bi bi-shop me-2"></i>Penjual</a>
<a href="/admin/users.php" class="nav-link text-secondary"><i class="bi bi-people me-2"></i>Pengguna</a>
<a href="/admin/products.php" class="nav-link text-secondary"><i class="bi bi-box me-2"></i>Produk</a>
<a href="/admin/settings.php" class="nav-link text-secondary"><i class="bi bi-gear me-2"></i>Pengaturan</a>
<hr>
<a href="/index.php" class="nav-link text-secondary small"><i class="bi bi-house me-2"></i>Ke Beranda</a>
</nav>
</div>
</div>
<div class="col-lg-10">
<h5 class="fw-bold mb-4">Dashboard Admin</h5>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-secondary small">Pembeli</div><div class="stat-value"><?=$stats['users']?></div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-secondary small">Penjual</div><div class="stat-value"><?=$stats['sellers']?></div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-secondary small">Total Pesanan</div><div class="stat-value"><?=$stats['orders']?></div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="text-secondary small">Pendapatan Admin</div><div class="stat-value text-success" style="font-size:16px"><?=formatRupiah($stats['revenue'])?></div></div></div>
</div>
<?php if($stats['pending']>0):?>
<div class="alert alert-warning small"><i class="bi bi-exclamation-circle-fill me-2"></i><?=$stats['pending']?> pesanan menunggu konfirmasi seller</div>
<?php endif;?>
<div class="card"><div class="card-header bg-white fw-bold d-flex justify-content-between"><span>Pesanan Terbaru</span><a href="/admin/orders.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a></div>
<div class="table-responsive"><table class="table table-hover small mb-0">
<thead class="table-light"><tr><th>No. Pesanan</th><th>Pembeli</th><th>Toko</th><th>Total</th><th>Status</th><th>Waktu</th></tr></thead>
<tbody>
<?php foreach($recentOrders as $o):?>
<tr><td class="fw-medium">#<?=clean($o['order_number'])?></td><td><?=clean($o['buyer_name'])?></td><td><?=clean($o['store_name'])?></td><td class="text-price fw-bold"><?=formatRupiah($o['total'])?></td><td><span class="status-badge status-<?=$o['status']?>"><?=ucfirst(str_replace('_',' ',$o['status']))?></span></td><td class="text-secondary"><?=timeAgo($o['created_at'])?></td></tr>
<?php endforeach;?>
</tbody>
</table></div>
</div>
</div>
</div>
</div>
<?php require_once __DIR__.'/../includes/footer.php';?>
