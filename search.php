<?php
require_once __DIR__ . '/includes/functions.php';
startSession(); $user=auth(); $db=Database::getInstance();
$q     = clean(trim($_GET['q']??''));
$cat   = clean($_GET['cat']??'');
$sort  = in_array($_GET['sort']??'',['newest','price_asc','price_desc','popular'])?$_GET['sort']:'newest';
$page  = max(1,(int)($_GET['page']??1));
$where = "p.status='active'"; $params=[];
if($q){ $where.=" AND p.name LIKE ?"; $params[]="%$q%"; }
if($cat){ $where.=" AND c.slug=?"; $params[]=$cat; }
$orderBy = match($sort){ 'price_asc'=>'p.price ASC','price_desc'=>'p.price DESC','popular'=>'p.total_sold DESC',default=>'p.created_at DESC' };
$total=$db->fetchOne("SELECT COUNT(*) c FROM products p JOIN categories c ON c.id=p.category_id WHERE $where",$params)['c'];
$pg=paginate($total,$page);
$products=$db->fetchAll("SELECT p.*,sp.store_name,c.name as cat_name,(SELECT image_path FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) as img FROM products p JOIN seller_profiles sp ON sp.id=p.seller_id JOIN categories c ON c.id=p.category_id WHERE $where ORDER BY $orderBy LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",$params);
$categories=$db->fetchAll("SELECT * FROM categories WHERE is_active=1 ORDER BY name");
$pageTitle=$q?'Hasil: '.htmlspecialchars($q):'Semua Produk';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
<div class="row g-4">
<div class="col-lg-3">
<div class="sidebar card p-3">
<h6 class="fw-bold mb-3">Filter</h6>
<div class="mb-3"><label class="fw-medium small d-block mb-2">Kategori</label>
<a href="<?=$q?'/search.php?q='.urlencode($q):'/search.php'?>" class="d-block small py-1 <?=!$cat?'fw-bold text-primary':'text-secondary'?> text-decoration-none">Semua Kategori</a>
<?php foreach($categories as $c):?>
<a href="/search.php?<?=http_build_query(array_filter(['q'=>$q,'cat'=>$c['slug']]))?>" class="d-block small py-1 <?=$cat===$c['slug']?'fw-bold text-primary':'text-secondary'?> text-decoration-none"><?=clean($c['name'])?></a>
<?php endforeach;?>
</div>
</div>
</div>
<div class="col-lg-9">
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
<div class="text-secondary small"><?=$total?> produk ditemukan <?=$q?'untuk "<strong>'.clean($q).'</strong>"':''?></div>
<select class="form-select form-select-sm" style="width:180px" onchange="location.href=this.value">
  <?php $baseQ=array_filter(['q'=>$q,'cat'=>$cat]); foreach(['newest'=>'Terbaru','popular'=>'Terlaris','price_asc'=>'Harga: Murah','price_desc'=>'Harga: Mahal'] as $k=>$v):?>
  <option value="/search.php?<?=http_build_query(array_merge($baseQ,['sort'=>$k,'page'=>1]))?>" <?=$sort===$k?'selected':''?>><?=$v?></option>
  <?php endforeach;?>
</select>
</div>
<div class="row g-3">
<?php foreach($products as $p):?>
<div class="col-6 col-md-4">
<a href="/product.php?slug=<?=urlencode($p['slug'])?>" class="text-decoration-none">
<div class="product-card">
<div class="img-wrap"><img src="<?=$p['img']?'/'.clean($p['img']):'/assets/img/no-image.svg'?>" alt="<?=clean($p['name'])?>" loading="lazy"></div>
<div class="card-body">
<div class="fw-medium text-truncate text-dark mb-1"><?=clean($p['name'])?></div>
<div class="price mb-1"><?=formatRupiah($p['price'])?></div>
<div class="d-flex justify-content-between"><div class="rating"><i class="bi bi-star-fill"></i> <?=number_format($p['rating'],1)?></div><div class="store"><?=clean($p['store_name'])?></div></div>
</div>
</div>
</a>
</div>
<?php endforeach;?>
<?php if(empty($products)):?>
<div class="col-12 text-center py-5"><i class="bi bi-search fs-1 text-secondary"></i><p class="text-secondary mt-2">Produk tidak ditemukan</p></div>
<?php endif;?>
</div>
<!-- Pagination -->
<?php if($pg['total_pages']>1):?>
<nav class="mt-4"><ul class="pagination justify-content-center">
<?php if($pg['has_prev']):?><li class="page-item"><a class="page-link" href="?<?=http_build_query(array_merge($_GET,['page'=>$pg['current']-1]))?>">‹</a></li><?php endif;?>
<?php for($i=max(1,$pg['current']-2);$i<=min($pg['total_pages'],$pg['current']+2);$i++):?>
<li class="page-item <?=$i===$pg['current']?'active':''?>"><a class="page-link" href="?<?=http_build_query(array_merge($_GET,['page'=>$i]))?>"><?=$i?></a></li>
<?php endfor;?>
<?php if($pg['has_next']):?><li class="page-item"><a class="page-link" href="?<?=http_build_query(array_merge($_GET,['page'=>$pg['current']+1]))?>">›</a></li><?php endif;?>
</ul></nav>
<?php endif;?>
</div>
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php';?>
