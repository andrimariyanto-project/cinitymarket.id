<?php
require_once __DIR__ . '/includes/functions.php';
startSession(); $user=auth(); $db=Database::getInstance();
$slug = clean($_GET['slug']??'');
$product = $db->fetchOne("SELECT p.*,sp.store_name,sp.id as seller_id,sp.store_slug,sp.rating as store_rating,c.name as cat_name FROM products p JOIN seller_profiles sp ON sp.id=p.seller_id JOIN categories c ON c.id=p.category_id WHERE p.slug=? AND p.status='active'",[$slug]);
if(!$product){setFlash('error','Produk tidak ditemukan');redirect('/index.php');}
$images=$db->fetchAll("SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC,sort_order",[$product['id']]);
$reviews=$db->fetchAll("SELECT r.*,u.name as buyer_name FROM reviews r JOIN users u ON u.id=r.buyer_id WHERE r.product_id=? AND r.is_visible=1 ORDER BY r.created_at DESC LIMIT 10",[$product['id']]);
// Increment views
$db->query("UPDATE products SET views=views+1 WHERE id=?",[$product['id']]);
$inWishlist = $user ? !!$db->fetchOne("SELECT id FROM wishlists WHERE user_id=? AND product_id=?",[$user['id'],$product['id']]) : false;

$pageTitle = $product['name'];
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
<nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb small">
<li class="breadcrumb-item"><a href="/index.php" class="text-decoration-none">Beranda</a></li>
<li class="breadcrumb-item"><a href="/search.php?cat=<?=urlencode($product['cat_name'])?>" class="text-decoration-none"><?=clean($product['cat_name'])?></a></li>
<li class="breadcrumb-item active text-truncate" style="max-width:200px"><?=clean($product['name'])?></li>
</ol></nav>

<div class="row g-4">
<div class="col-md-5">
  <div class="card border-0 shadow-sm">
    <div id="mainImgWrap" style="background:#F1F5F9;border-radius:12px;overflow:hidden;aspect-ratio:1">
      <img id="mainImg" src="<?=$images?'/'.clean($images[0]['image_path']):'/assets/img/no-image.svg'?>" style="width:100%;height:100%;object-fit:cover" alt="">
    </div>
    <?php if(count($images)>1):?>
    <div class="d-flex gap-2 mt-2 flex-wrap">
    <?php foreach($images as $img):?>
    <img src="/<?=clean($img['image_path'])?>" style="width:60px;height:60px;object-fit:cover;border-radius:8px;border:2px solid #E2E8F0;cursor:pointer" onclick="document.getElementById('mainImg').src=this.src" alt="">
    <?php endforeach;?>
    </div>
    <?php endif;?>
  </div>
</div>

<div class="col-md-7">
  <h4 class="fw-bold mb-1"><?=clean($product['name'])?></h4>
  <div class="d-flex align-items-center gap-3 mb-3">
    <div class="text-warning small"><i class="bi bi-star-fill"></i> <?=number_format($product['rating'],1)?></div>
    <div class="text-secondary small"><?=$product['total_sold']?> terjual</div>
    <div class="text-secondary small"><?=$product['views']?> dilihat</div>
  </div>
  <div class="text-price" style="font-size:28px;font-weight:800"><?=formatRupiah($product['price'])?></div>
  
  <!-- Shipping Info -->
  <div class="mt-3 p-3 rounded-3" style="background:var(--surface-2);border:1px solid var(--border)">
    <div style="font-size:13px;font-weight:700;margin-bottom:8px;color:var(--text-1)"><i class="bi bi-truck-front-fill me-2" style="color:var(--brand)"></i>Pengiriman</div>
    <div class="d-flex align-items-center gap-3" style="font-size:13px;color:var(--text-2)">
      <div><i class="bi bi-truck-front me-1 text-primary"></i>Kurir Toko <strong><?=formatRupiah(COURIER_STORE_FEE)?></strong></div>
      <div class="text-secondary">+</div>
      <div><i class="bi bi-shield-check me-1 text-success"></i>Admin <strong><?=formatRupiah(ADMIN_FEE)?></strong></div>
    </div>
    <div style="font-size:12px;color:var(--text-3);margin-top:6px">Diantar kurir toko · Estimasi 1–2 hari</div>
  </div>

  <!-- Quantity & Add to Cart -->
  <?php if($user && $user['id'] !== $product['seller_id']):?>
  <div class="mt-4">
    <form method="POST" action="/buyer/cart.php">
      <?=csrfField()?>
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="product_id" value="<?=$product['id']?>">
      <div class="d-flex align-items-center gap-3 mb-3">
        <label class="fw-medium small">Jumlah:</label>
        <div class="d-flex align-items-center gap-2">
          <button type="button" onclick="adjustQty(-1)" class="btn btn-outline-secondary px-3">-</button>
          <input type="number" name="qty" id="qty" class="form-control text-center" value="1" min="1" max="<?=$product['stock']?>" style="width:70px">
          <button type="button" onclick="adjustQty(1)" class="btn btn-outline-secondary px-3">+</button>
        </div>
        <span class="text-secondary small">Stok: <?=$product['stock']?></span>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-outline-primary flex-fill fw-semibold" <?=$product['stock']<=0?'disabled':''?>><i class="bi bi-cart-plus me-2"></i>Keranjang</button>
        <button type="submit" formaction="/buyer/checkout-direct.php" class="btn btn-primary flex-fill fw-semibold" <?=$product['stock']<=0?'disabled':''?>><i class="bi bi-lightning me-2"></i>Beli Sekarang</button>
      </div>
    </form>
  </div>
  <?php elseif(!$user):?>
  <div class="mt-4"><a href="/login.php?redirect=<?=urlencode('/product.php?slug='.$slug)?>" class="btn btn-primary fw-semibold w-100">Login untuk Membeli</a></div>
  <?php endif;?>
  <?php if($product['stock']<=0):?><div class="alert alert-warning mt-2 small py-2">Stok habis</div><?php endif;?>

  <!-- Seller Info -->
  <div class="mt-4 p-3 border rounded-3 d-flex align-items-center gap-3">
    <div class="avatar-sm"><?=strtoupper(substr($product['store_name'],0,1))?></div>
    <div><div class="fw-medium"><?=clean($product['store_name'])?></div><div class="small text-secondary"><i class="bi bi-star-fill text-warning me-1"></i><?=number_format($product['store_rating'],1)?></div></div>
    <a href="/store.php?slug=<?=urlencode($product['store_slug'])?>" class="btn btn-sm btn-outline-primary ms-auto">Kunjungi Toko</a>
  </div>
</div>
</div>

<!-- Description -->
<div class="card mt-4"><div class="card-header bg-white fw-bold">Deskripsi Produk</div><div class="card-body" style="white-space:pre-wrap"><?=clean($product['description']??'Belum ada deskripsi')?></div></div>

<!-- Reviews -->
<?php if(!empty($reviews)):?>
<div class="card mt-4"><div class="card-header bg-white fw-bold">Ulasan (<?=count($reviews)?>)</div><div class="card-body">
<?php foreach($reviews as $r):?>
<div class="mb-3 pb-3 border-bottom">
  <div class="d-flex align-items-center gap-2 mb-1">
    <div class="avatar-sm" style="width:32px;height:32px;font-size:12px"><?=strtoupper(substr($r['buyer_name'],0,1))?></div>
    <div><div class="fw-medium small"><?=clean($r['buyer_name'])?></div>
    <div class="text-warning" style="font-size:13px"><?=str_repeat('★',$r['rating']).'☆'.(5-$r['rating'])?></div></div>
    <div class="ms-auto text-secondary small"><?=timeAgo($r['created_at'])?></div>
  </div>
  <p class="small mb-0 ms-5"><?=clean($r['comment']??'')?></p>
</div>
<?php endforeach;?>
</div></div>
<?php endif;?>
</div>

<script>
function adjustQty(d){const i=document.getElementById('qty');i.value=Math.max(1,Math.min(<?=$product['stock']?>,parseInt(i.value)+d));}
</script>
<?php require_once __DIR__ . '/includes/footer.php';?>
