<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('seller'); $user=auth(); $db=Database::getInstance();
$seller=$db->fetchOne("SELECT * FROM seller_profiles WHERE user_id=?",[$user['id']]);
$status=clean($_GET['status']??''); $page=max(1,(int)($_GET['page']??1));
$where="o.seller_id=?"; $params=[$seller['id']];
if($status){$where.=" AND o.status=?";$params[]=$status;}
$total=$db->fetchOne("SELECT COUNT(*) c FROM orders o WHERE $where",$params)['c'];
$pg=paginate($total,$page,20);
$orders=$db->fetchAll("SELECT o.*,u.name as buyer_name FROM orders o JOIN users u ON u.id=o.buyer_id WHERE $where ORDER BY o.created_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",$params);
$statuses=[
  ''=>'Semua','paid'=>'Perlu Konfirmasi','confirmed'=>'Dikonfirmasi',
  'processing'=>'Diproses','shipped'=>'Dikirim','completed'=>'Selesai','cancelled'=>'Dibatalkan'
];
$pageTitle='Pesanan Masuk';
require_once __DIR__.'/../includes/header.php';
?>
<div class="container py-4">
<h5 class="fw-bold mb-4"><i class="bi bi-bag me-2"></i>Pesanan Masuk</h5>
<div class="d-flex gap-2 flex-wrap mb-4">
<?php foreach($statuses as $k=>$v):?>
<a href="/seller/orders.php<?=$k?'?status='.$k:''?>" class="btn btn-sm <?=$status===$k?'btn-primary':'btn-outline-secondary'?>"><?=$v?></a>
<?php endforeach;?>
</div>
<?php if(empty($orders)):?>
<div class="text-center py-5"><i class="bi bi-inbox fs-1 text-secondary"></i><p class="text-secondary mt-2">Belum ada pesanan</p></div>
<?php else:?>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0 small">
<thead class="table-light"><tr><th>No. Pesanan</th><th>Pembeli</th><th>Total</th><th>Pengiriman</th><th>Status</th><th>Waktu</th><th></th></tr></thead>
<tbody>
<?php foreach($orders as $o):?>
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
</tbody>
</table></div></div>
<?php endif;?>
</div>
<?php require_once __DIR__.'/../includes/footer.php';?>
