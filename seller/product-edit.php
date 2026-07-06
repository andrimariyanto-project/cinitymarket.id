<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('seller'); $user = auth(); $db = Database::getInstance();
$seller = $db->fetchOne("SELECT * FROM seller_profiles WHERE user_id=?", [$user['id']]);
$id = (int)($_GET['id'] ?? 0);
$product = $db->fetchOne("SELECT * FROM products WHERE id=? AND seller_id=?", [$id, $seller['id']]);
if (!$product) { setFlash('error', 'Produk tidak ditemukan'); redirect('/seller/products.php'); }
$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active=1 ORDER BY name");
$images = $db->fetchAll("SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order", [$id]);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete_img') {
        $imgId = (int)($_POST['img_id'] ?? 0);
        $img = $db->fetchOne("SELECT * FROM product_images WHERE id=? AND product_id=?", [$imgId, $id]);
        if ($img) {
            @unlink(UPLOAD_PATH . '/../' . $img['image_path']);
            $db->query("DELETE FROM product_images WHERE id=?", [$imgId]);
        }
        redirect('/seller/product-edit.php?id=' . $id);
    }

    $name   = trim($_POST['name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $price  = (float)($_POST['price'] ?? 0);
    $stock  = (int)($_POST['stock'] ?? 0);
    $weight = (float)($_POST['weight'] ?? 0);
    $catId  = (int)($_POST['category_id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['active','draft','inactive']) ? $_POST['status'] : 'active';

    if (!$name) $errors[] = 'Nama produk wajib diisi';
    if ($price <= 0) $errors[] = 'Harga harus lebih dari 0';
    if (empty($errors)) {
        $db->update('products', ['name'=>$name,'description'=>$desc,'price'=>$price,'stock'=>$stock,'weight'=>$weight,'category_id'=>$catId,'status'=>$status,'slug'=>uniqueSlug('products',$name,$id)], 'id=?', [$id]);
        // New images
        $existCount = $db->fetchOne("SELECT COUNT(*) c FROM product_images WHERE product_id=?", [$id])['c'];
        for ($i = 0; $i < 5; $i++) {
            if (isset($_FILES['images']['name'][$i]) && $_FILES['images']['error'][$i] === 0) {
                $file = ['name'=>$_FILES['images']['name'][$i],'type'=>$_FILES['images']['type'][$i],'tmp_name'=>$_FILES['images']['tmp_name'][$i],'error'=>$_FILES['images']['error'][$i],'size'=>$_FILES['images']['size'][$i]];
                $path = uploadImage($file, 'products');
                if ($path) { $db->insert('product_images', ['product_id'=>$id,'image_path'=>$path,'is_primary'=>($existCount === 0 ? 1 : 0),'sort_order'=>$existCount + $i]); $existCount++; }
            }
        }
        setFlash('success', 'Produk berhasil diperbarui');
        redirect('/seller/products.php');
    }
}
$pageTitle = 'Edit Produk';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4" style="max-width:800px">
  <h5 class="fw-bold mb-4"><i class="bi bi-pencil-square me-2"></i>Edit Produk</h5>
  <?php if ($errors): ?><div class="alert alert-danger small"><ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="card mb-3"><div class="card-header bg-white fw-bold">Informasi Produk</div><div class="card-body">
      <div class="mb-3"><label class="form-label fw-medium">Nama Produk *</label><input type="text" name="name" class="form-control" value="<?= clean($product['name']) ?>" required></div>
      <div class="mb-3"><label class="form-label fw-medium">Deskripsi</label><textarea name="description" class="form-control" rows="4"><?= clean($product['description'] ?? '') ?></textarea></div>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-medium">Kategori *</label>
          <select name="category_id" class="form-select" required><option value="">Pilih</option>
          <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= $product['category_id'] == $c['id'] ? 'selected' : '' ?>><?= clean($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label fw-medium">Status</label>
          <select name="status" class="form-select"><option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Aktif</option><option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Nonaktif</option><option value="draft" <?= $product['status'] === 'draft' ? 'selected' : '' ?>>Draft</option></select></div>
        <div class="col-md-4"><label class="form-label fw-medium">Harga (Rp) *</label><input type="number" name="price" class="form-control" value="<?= (int)$product['price'] ?>" min="0" required></div>
        <div class="col-md-4"><label class="form-label fw-medium">Stok *</label><input type="number" name="stock" class="form-control" value="<?= $product['stock'] ?>" min="0" required></div>
        <div class="col-md-4"><label class="form-label fw-medium">Berat (gram)</label><input type="number" name="weight" class="form-control" value="<?= (int)$product['weight'] ?>" min="0"></div>
      </div>
    </div></div>

    <div class="card mb-3"><div class="card-header bg-white fw-bold">Foto Produk</div><div class="card-body">
      <?php if (!empty($images)): ?>
      <div class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach ($images as $img): ?>
        <div class="position-relative">
          <img src="/<?= clean($img['image_path']) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid <?= $img['is_primary'] ? 'var(--primary)' : 'var(--border)' ?>">
          <?php if ($img['is_primary']): ?><span class="position-absolute top-0 start-0 badge bg-primary" style="font-size:9px">Utama</span><?php endif; ?>
          <form method="POST" class="d-inline">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete_img">
            <input type="hidden" name="img_id" value="<?= $img['id'] ?>">
            <button class="position-absolute top-0 end-0 btn btn-danger btn-sm" style="width:20px;height:20px;padding:0;font-size:11px;border-radius:50%" onclick="return confirm('Hapus foto?')">×</button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <label class="form-label fw-medium small">Tambah Foto Baru (maks 5 per upload)</label>
      <input type="file" name="images[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
    </div></div>

    <div class="d-flex gap-2">
      <a href="/seller/products.php" class="btn btn-outline-secondary">Batal</a>
      <button type="submit" class="btn btn-primary fw-semibold px-4"><i class="bi bi-check2 me-2"></i>Simpan Perubahan</button>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
