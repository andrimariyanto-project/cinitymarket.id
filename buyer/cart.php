<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$user = auth(); $db = Database::getInstance();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf(); $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $pid = (int)($_POST['product_id']??0); $qty = max(1,(int)($_POST['qty']??1));
        $p = $db->fetchOne("SELECT id,stock FROM products WHERE id=? AND status='active'",[$pid]);
        if ($p && $p['stock']>=$qty) {
            $ex = $db->fetchOne("SELECT id,quantity FROM cart WHERE user_id=? AND product_id=?",[$user['id'],$pid]);
            if ($ex) $db->update('cart',['quantity'=>min($ex['quantity']+$qty,$p['stock'])],'id=?',[$ex['id']]);
            else $db->insert('cart',['user_id'=>$user['id'],'product_id'=>$pid,'quantity'=>$qty]);
            setFlash('success','Produk ditambahkan ke keranjang!');
        } else setFlash('error','Stok tidak mencukupi');
        redirect($_SERVER['HTTP_REFERER']??'/buyer/cart.php');
    }
    if ($action === 'update') {
        $cid=(int)($_POST['cart_id']??0); $qty=(int)($_POST['qty']??1);
        if ($qty>0) $db->update('cart',['quantity'=>$qty],'id=? AND user_id=?',[$cid,$user['id']]);
        else $db->query("DELETE FROM cart WHERE id=? AND user_id=?",[$cid,$user['id']]);
        redirect('/buyer/cart.php');
    }
    if ($action === 'remove') {
        $db->query("DELETE FROM cart WHERE id=? AND user_id=?",[(int)($_POST['cart_id']??0),$user['id']]);
        redirect('/buyer/cart.php');
    }
}
$cartItems = $db->fetchAll("SELECT c.id as cart_id,c.quantity,c.product_id,p.name,p.price,p.stock,p.slug,pi.image_path as img,sp.id as seller_id,sp.store_name FROM cart c JOIN products p ON p.id=c.product_id JOIN seller_profiles sp ON sp.id=p.seller_id LEFT JOIN product_images pi ON pi.product_id=p.id AND pi.is_primary=1 WHERE c.user_id=? ORDER BY sp.id,c.created_at",[$user['id']]);
$sellers=[]; foreach($cartItems as $it){$sellers[$it['seller_id']]['info']=['store_name'=>$it['store_name']];$sellers[$it['seller_id']]['items'][]=$it;}
$subtotal=array_sum(array_map(fn($i)=>$i['price']*$i['quantity'],$cartItems));
$pageTitle='Keranjang';
require_once __DIR__.'/../includes/header.php';
?>
<div class="container py-4">
<h5 class="fw-bold mb-4"><i class="bi bi-cart3 me-2"></i>Keranjang Belanja (<?=count($cartItems)?> item)</h5>
<?php if(empty($cartItems)):?>
<div class="text-center py-5"><i class="bi bi-cart-x fs-1 text-secondary"></i><p class="text-secondary mt-2">Keranjang kosong</p><a href="/index.php" class="btn btn-primary">Mulai Belanja</a></div>
<?php else:?>
<div class="row g-4">
<div class="col-lg-8">
<?php foreach($sellers as $sid=>$seller):?>
<div class="card mb-3">
<div class="card-header bg-white fw-medium"><i class="bi bi-shop me-2 text-primary"></i><?=clean($seller['info']['store_name'])?></div>
<div class="card-body p-0">
<?php foreach($seller['items'] as $it):?>
<div class="d-flex align-items-center gap-3 p-3 border-bottom flex-wrap">
<img src="<?=$it['img']?'/'.clean($it['img']):'/assets/img/no-image.svg'?>" class="img-thumb" alt="">
<div class="flex-grow-1"><a href="/product.php?slug=<?=urlencode($it['slug'])?>" class="text-dark text-decoration-none fw-medium"><?=clean($it['name'])?></a><div class="text-price mt-1"><?=formatRupiah($it['price'])?></div></div>
<form method="POST" class="d-inline-flex align-items-center gap-1"><?=csrfField()?><input type="hidden" name="action" value="update"><input type="hidden" name="cart_id" value="<?=$it['cart_id']?>">
<button type="submit" name="qty" value="<?=$it['quantity']-1?>" class="btn btn-sm btn-outline-secondary px-2">-</button>
<span class="px-2 fw-medium"><?=$it['quantity']?></span>
<button type="submit" name="qty" value="<?=min($it['quantity']+1,$it['stock'])?>" class="btn btn-sm btn-outline-secondary px-2" <?=$it['quantity']>=$it['stock']?'disabled':''?>>+</button>
</form>
<div class="fw-bold"><?=formatRupiah($it['price']*$it['quantity'])?></div>
<form method="POST" class="d-inline"><?=csrfField()?><input type="hidden" name="action" value="remove"><input type="hidden" name="cart_id" value="<?=$it['cart_id']?>"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></button></form>
</div>
<?php endforeach;?>
</div>
</div>
<?php endforeach;?>
</div>
<div class="col-lg-4">
<div class="card" style="position:sticky;top:80px"><div class="card-body">
<h6 class="fw-bold mb-3">Ringkasan Pesanan</h6>
<div class="d-flex justify-content-between mb-2 small text-secondary"><span>Subtotal</span><span><?=formatRupiah($subtotal)?></span></div>
<div class="d-flex justify-content-between mb-2 small text-secondary"><span>Biaya Admin</span><span><?=formatRupiah(ADMIN_FEE)?></span></div>
<div class="d-flex justify-content-between mb-2 small text-secondary"><span>Ongkir</span><span>Dihitung di checkout</span></div>
<hr><div class="d-flex justify-content-between fw-bold"><span>Estimasi</span><span class="text-price"><?=formatRupiah($subtotal+ADMIN_FEE)?>+</span></div>
<a href="/buyer/checkout.php" class="btn btn-primary w-100 mt-3 fw-semibold">Checkout <i class="bi bi-arrow-right ms-1"></i></a>
</div></div>
</div>
</div>
<?php endif;?>
</div>
<?php require_once __DIR__.'/../includes/footer.php';?>
