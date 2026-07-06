<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('seller'); $user=auth(); $db=Database::getInstance();
$seller = $db->fetchOne("SELECT * FROM seller_profiles WHERE user_id=?",[$user['id']]);
if(!$seller){setFlash('info','Lengkapi profil toko Anda');redirect('/seller/profile.php');}

$stats = [
    'orders_today'  => $db->fetchOne("SELECT COUNT(*) c FROM orders WHERE seller_id=? AND DATE(created_at)=CURDATE()",[$seller['id']])['c'],
    'orders_total'  => $db->fetchOne("SELECT COUNT(*) c FROM orders WHERE seller_id=? AND status NOT IN ('cancelled')",[$seller['id']])['c'],
    'revenue'       => $db->fetchOne("SELECT COALESCE(SUM(subtotal),0) c FROM orders WHERE seller_id=? AND status='completed'",[$seller['id']])['c'],
    'pending'       => $db->fetchOne("SELECT COUNT(*) c FROM orders WHERE seller_id=? AND status='paid'",[$seller['id']])['c'],
    'products'      => $db->fetchOne("SELECT COUNT(*) c FROM products WHERE seller_id=? AND status='active'",[$seller['id']])['c'],
];
$newOrders = $db->fetchAll("SELECT o.*,u.name as buyer_name FROM orders o JOIN users u ON u.id=o.buyer_id WHERE o.seller_id=? AND o.status='paid' ORDER BY o.created_at DESC LIMIT 5",[$seller['id']]);
$recentOrders = $db->fetchAll("SELECT o.*,u.name as buyer_name FROM orders o JOIN users u ON u.id=o.buyer_id WHERE o.seller_id=? ORDER BY o.created_at DESC LIMIT 10",[$seller['id']]);
$pageTitle='Dashboard Toko';
require_once __DIR__.'/../includes/header.php';
?>
<div class="container-fluid py-4">
<div class="row">
<!-- Sidebar -->
<div class="col-lg-2 col-md-3">
<div class="sidebar card p-3">
  <div class="text-center mb-3">
    <div class="avatar-sm mx-auto mb-2" style="width:48px;height:48px;font-size:20px"><?=strtoupper(substr($seller['store_name'],0,1))?></div>
    <div class="fw-bold small"><?=clean($seller['store_name'])?></div>
    <?php if($seller['is_verified']):?><span class="badge bg-success" style="font-size:10px">✓ Terverifikasi</span><?php endif;?>
  </div>
  <nav class="nav flex-column gap-1">
    <a href="/seller/dashboard.php" class="nav-link text-dark fw-medium active"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
    <a href="/seller/orders.php" class="nav-link text-secondary"><i class="bi bi-bag me-2"></i>Pesanan <?php if($stats['pending']>0):?><span class="badge bg-danger"><?=$stats['pending']?></span><?php endif;?></a>
    <a href="/seller/products.php" class="nav-link text-secondary"><i class="bi bi-box me-2"></i>Produk</a>
    <a href="/seller/product-add.php" class="nav-link text-secondary"><i class="bi bi-plus-circle me-2"></i>Tambah Produk</a>
    <a href="/seller/profile.php" class="nav-link text-secondary"><i class="bi bi-shop me-2"></i>Profil Toko</a>
    <hr>
    <a href="/index.php" class="nav-link text-secondary small"><i class="bi bi-house me-2"></i>Ke Beranda</a>
  </nav>
</div>
</div>

<!-- Main -->
<div class="col-lg-10 col-md-9">
<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0">Dashboard Toko</h5>
  <a href="/seller/product-add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus me-1"></i>Tambah Produk</a>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="d-flex justify-content-between align-items-start"><div><div class="text-secondary small">Pesanan Hari Ini</div><div class="stat-value mt-1"><?=$stats['orders_today']?></div></div><div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-bag"></i></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="d-flex justify-content-between align-items-start"><div><div class="text-secondary small">Perlu Dikonfirmasi</div><div class="stat-value mt-1 text-warning"><?=$stats['pending']?></div></div><div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-exclamation-circle"></i></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="d-flex justify-content-between align-items-start"><div><div class="text-secondary small">Total Pesanan</div><div class="stat-value mt-1"><?=$stats['orders_total']?></div></div><div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-graph-up"></i></div></div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><div class="d-flex justify-content-between align-items-start"><div><div class="text-secondary small">Total Pendapatan</div><div class="stat-value mt-1 text-success" style="font-size:16px"><?=formatRupiah($stats['revenue'])?></div></div><div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-cash-stack"></i></div></div></div></div>
</div>

<!-- New Orders Alert -->
<?php if(!empty($newOrders)):?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
  <i class="bi bi-exclamation-circle-fill fs-5"></i>
  <div><strong><?=count($newOrders)?> pesanan baru</strong> menunggu konfirmasi Anda. <a href="/seller/orders.php?status=paid" class="alert-link">Konfirmasi sekarang →</a></div>
</div>
<?php endif;?>

<!-- Recent Orders -->
<div class="card">
<div class="card-header bg-white d-flex justify-content-between align-items-center">
  <h6 class="fw-bold mb-0">Pesanan Terbaru</h6>
  <a href="/seller/orders.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
</div>
<div class="table-responsive">
<table class="table table-hover mb-0 small">
<thead class="table-light"><tr><th>No. Pesanan</th><th>Pembeli</th><th>Total</th><th>Pengiriman</th><th>Status</th><th>Waktu</th><th></th></tr></thead>
<tbody>
<?php foreach($recentOrders as $o):?>
<tr>
  <td class="fw-medium">#<?=clean($o['order_number'])?></td>
  <td><?=clean($o['buyer_name'])?></td>
  <td class="text-price fw-bold"><?=formatRupiah($o['total'])?></td>
  <td><?=$o['shipping_method']==='courier_store'?'<i class="bi bi-truck-front text-primary"></i> Kurir Toko'?></td>
  <td><span class="status-badge status-<?=$o['status']?>"><?=ucfirst(str_replace('_',' ',$o['status']))?></span></td>
  <td class="text-secondary"><?=timeAgo($o['created_at'])?></td>
  <td><a href="/seller/order-detail.php?id=<?=$o['id']?>" class="btn btn-sm btn-outline-secondary">Detail</a></td>
</tr>
<?php endforeach;?>
<?php if(empty($recentOrders)):?><tr><td colspan="7" class="text-center text-secondary py-4">Belum ada pesanan</td></tr><?php endif;?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
<?php require_once __DIR__.'/../includes/footer.php';?>
