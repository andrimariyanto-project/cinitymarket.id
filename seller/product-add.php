<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('seller'); $user=auth(); $db=Database::getInstance();
$seller=$db->fetchOne("SELECT * FROM seller_profiles WHERE user_id=?",[$user['id']]);
$categories=$db->fetchAll("SELECT * FROM categories WHERE is_active=1 ORDER BY name");
$errors=[];

if($_SERVER['REQUEST_METHOD']==='POST'){
    verifyCsrf();
    $name    = trim($_POST['name']??'');
    $desc    = trim($_POST['description']??'');
    $price   = (float)($_POST['price']??0);
    $stock   = (int)($_POST['stock']??0);
    $weight  = (float)($_POST['weight']??0);
    $catId   = (int)($_POST['category_id']??0);
    $status  = in_array($_POST['status']??'',['active','draft','inactive'])?$_POST['status']:'active';

    if(!$name) $errors[]='Nama produk wajib diisi';
    if($price<=0) $errors[]='Harga harus lebih dari 0';
    if($stock<0) $errors[]='Stok tidak boleh negatif';
    if(!$catId) $errors[]='Pilih kategori';

    if(empty($errors)){
        $db->beginTransaction();
        try {
            $slug = uniqueSlug('products',$name);
            $prodId = $db->insert('products',[
                'seller_id'=>$seller['id'],'category_id'=>$catId,'name'=>$name,'slug'=>$slug,
                'description'=>$desc,'price'=>$price,'stock'=>$stock,'weight'=>$weight,'status'=>$status
            ]);
            // Handle images
            $isPrimary=1;
            for($i=0;$i<5;$i++){
                if(isset($_FILES['images']['name'][$i]) && $_FILES['images']['error'][$i]===0){
                    $file=['name'=>$_FILES['images']['name'][$i],'type'=>$_FILES['images']['type'][$i],'tmp_name'=>$_FILES['images']['tmp_name'][$i],'error'=>$_FILES['images']['error'][$i],'size'=>$_FILES['images']['size'][$i]];
                    $path = uploadImage($file,'products');
                    if($path) { $db->insert('product_images',['product_id'=>$prodId,'image_path'=>$path,'is_primary'=>$isPrimary,'sort_order'=>$i]); $isPrimary=0; }
                }
            }
            $db->commit();
            setFlash('success','Produk berhasil ditambahkan!');
            redirect('/seller/products.php');
        } catch(Exception $e){ $db->rollback(); $errors[]='Gagal menyimpan produk'; }
    }
}
$pageTitle='Tambah Produk';
require_once __DIR__.'/../includes/header.php';
?>
<div class="container py-4" style="max-width:800px">
<h5 class="fw-bold mb-4"><i class="bi bi-plus-circle me-2"></i>Tambah Produk Baru</h5>
<?php if($errors):?><div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach($errors as $e):?><li><?=clean($e)?></li><?php endforeach;?></ul></div><?php endif;?>
<form method="POST" enctype="multipart/form-data">
<?=csrfField()?>
<div class="card mb-3"><div class="card-header bg-white fw-bold">Informasi Produk</div><div class="card-body">
<div class="mb-3"><label class="form-label fw-medium">Nama Produk *</label><input type="text" name="name" class="form-control" value="<?=clean($_POST['name']??'')?>" required></div>
<div class="mb-3"><label class="form-label fw-medium">Deskripsi</label><textarea name="description" class="form-control" rows="4"><?=clean($_POST['description']??'')?></textarea></div>
<div class="row g-3">
  <div class="col-md-6"><label class="form-label fw-medium">Kategori *</label>
  <select name="category_id" class="form-select" required><option value="">Pilih Kategori</option>
  <?php foreach($categories as $c):?><option value="<?=$c['id']?>" <?=(($_POST['category_id']??'')==$c['id'])?'selected':''?>><?=clean($c['name'])?></option><?php endforeach;?></select></div>
  <div class="col-md-6"><label class="form-label fw-medium">Status</label>
  <select name="status" class="form-select"><option value="active" <?=(($_POST['status']??'active')==='active')?'selected':''?>>Aktif - Tampil di toko</option><option value="draft" <?=(($_POST['status']??'')==='draft')?'selected':''?>>Draft - Tersimpan saja</option></select></div>
  <div class="col-md-4"><label class="form-label fw-medium">Harga (Rp) *</label><input type="number" name="price" class="form-control" value="<?=(int)($_POST['price']??0)?>" min="0" required></div>
  <div class="col-md-4"><label class="form-label fw-medium">Stok *</label><input type="number" name="stock" class="form-control" value="<?=(int)($_POST['stock']??0)?>" min="0" required></div>
  <div class="col-md-4"><label class="form-label fw-medium">Berat (gram)</label><input type="number" name="weight" class="form-control" value="<?=(int)($_POST['weight']??0)?>" min="0"></div>
</div>
</div></div>

<div class="card mb-3"><div class="card-header bg-white fw-bold">Foto Produk</div><div class="card-body">
<p class="text-secondary small mb-3">Upload hingga 5 foto. Foto pertama akan jadi foto utama. Format: JPG/PNG/WebP, maks 2MB/foto.</p>
<input type="file" name="images[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
</div></div>

<div class="d-flex gap-2">
<a href="/seller/products.php" class="btn btn-outline-secondary">Batal</a>
<button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-check2 me-2"></i>Simpan Produk</button>
</div>
</form>
</div>
<?php require_once __DIR__.'/../includes/footer.php';?>
