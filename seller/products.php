<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('seller'); $user=auth(); $db=Database::getInstance();
$seller=$db->fetchOne("SELECT * FROM seller_profiles WHERE user_id=?",[$user['id']]);
$status=clean($_GET['status']??'active'); $page=max(1,(int)($_GET['page']??1));
$total=$db->fetchOne("SELECT COUNT(*) c FROM products WHERE seller_id=? AND status=?",[$seller['id'],$status])['c'];
$pg=paginate($total,$page,20);
$products=$db->fetchAll("SELECT p.*,c.name as cat_name,(SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) as img FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.seller_id=? AND p.status=? ORDER BY p.created_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",[$seller['id'],$status]);
$pageTitle='Produk Saya';
require_once __DIR__.'/../includes/header.php';
?>
<div class="container py-4">
<div class="d-flex justify-content-between align-items-center mb-4">
<h5 class="fw-bold mb-0">Produk Saya</h5>
<a href="/seller/product-add.php" class="btn btn-primary"><i class="bi bi-plus me-1"></i>Tambah Produk</a>
</div>
<div class="d-flex gap-2 mb-3">
<?php foreach(['active'=>'Aktif','inactive'=>'Nonaktif','draft'=>'Draft'] as $k=>$v):?>
<a href="?status=<?=$k?>" class="btn btn-sm <?=$status===$k?'btn-primary':'btn-outline-secondary'?>"><?=$v?></a>
<?php endforeach;?>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0 small">
<thead class="table-light"><tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Terjual</th><th></th></tr></thead>
<tbody>
<?php foreach($products as $p):?>
<tr>
<td><div class="d-flex align-items-center gap-2">
<img src="<?=$p['img']?'/'.clean($p['img']):'/assets/img/no-image.svg'?>" class="img-thumb" alt="">
<div class="fw-medium" style="max-width:200px"><?=clean($p['name'])?></div>
</div></td>
<td class="text-secondary"><?=clean($p['cat_name']??'-')?></td>
<td class="fw-bold text-price"><?=formatRupiah($p['price'])?></td>
<td><?php if($p['stock']<=5):?><span class="badge bg-danger"><?=$p['stock']?></span><?php else:?><?=$p['stock']?><?php endif;?></td>
<td><?=$p['total_sold']?></td>
<td><a href="/seller/product-edit.php?id=<?=$p['id']?>" class="btn btn-sm btn-outline-primary me-1">Edit</a>
<a href="/product.php?slug=<?=urlencode($p['slug'])?>" class="btn btn-sm btn-outline-secondary" target="_blank">Lihat</a></td>
</tr>
<?php endforeach;?>
<?php if(empty($products)):?><tr><td colspan="6" class="text-center py-4 text-secondary">Belum ada produk</td></tr><?php endif;?>
</tbody>
</table></div></div>
</div>
<?php require_once __DIR__.'/../includes/footer.php';?>
