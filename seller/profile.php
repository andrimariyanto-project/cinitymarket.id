<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('seller'); $user = auth(); $db = Database::getInstance();
$seller = $db->fetchOne("SELECT * FROM seller_profiles WHERE user_id=?", [$user['id']]);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $storeName  = trim($_POST['store_name'] ?? '');
    $storeDesc  = trim($_POST['store_description'] ?? '');
    $storeAddr  = trim($_POST['store_address'] ?? '');
    $storeCity  = trim($_POST['store_city'] ?? '');
    $storeProv  = trim($_POST['store_province'] ?? '');
    $bankName   = trim($_POST['bank_name'] ?? '');
    $bankAcc    = trim($_POST['bank_account'] ?? '');
    $bankHolder = trim($_POST['bank_holder'] ?? '');

    if (!$storeName) $errors[] = 'Nama toko wajib diisi';

    if (empty($errors)) {
        $data = ['store_name'=>$storeName,'store_description'=>$storeDesc,'store_address'=>$storeAddr,'store_city'=>$storeCity,'store_province'=>$storeProv,'bank_name'=>$bankName,'bank_account'=>$bankAcc,'bank_holder'=>$bankHolder];

        if (!$seller) {
            $data['user_id']    = $user['id'];
            $data['store_slug'] = uniqueSlug('seller_profiles', $storeName);
            $db->insert('seller_profiles', $data);
        } else {
            // Handle logo
            if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === 0) {
                $logo = uploadImage($_FILES['store_logo'], 'stores');
                if ($logo) $data['store_logo'] = $logo;
            }
            if (isset($_FILES['store_banner']) && $_FILES['store_banner']['error'] === 0) {
                $banner = uploadImage($_FILES['store_banner'], 'stores');
                if ($banner) $data['store_banner'] = $banner;
            }
            $db->update('seller_profiles', $data, 'id=?', [$seller['id']]);
        }
        setFlash('success', 'Profil toko berhasil disimpan');
        redirect('/seller/profile.php');
    }
    $seller = $db->fetchOne("SELECT * FROM seller_profiles WHERE user_id=?", [$user['id']]);
}
$pageTitle = 'Profil Toko';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4" style="max-width:800px">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="bi bi-shop me-2"></i>Profil Toko</h5>
    <a href="/seller/dashboard.php" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
  </div>
  <?php if ($errors): ?><div class="alert alert-danger small"><ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <!-- Toko Info -->
    <div class="card mb-3">
      <div class="card-header bg-white fw-bold">Informasi Toko</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label fw-medium">Nama Toko *</label><input type="text" name="store_name" class="form-control" value="<?= clean($seller['store_name'] ?? '') ?>" required></div>
          <div class="col-md-6"><label class="form-label fw-medium">Kota</label><input type="text" name="store_city" class="form-control" value="<?= clean($seller['store_city'] ?? '') ?>"></div>
          <div class="col-12"><label class="form-label fw-medium">Deskripsi Toko</label><textarea name="store_description" class="form-control" rows="3"><?= clean($seller['store_description'] ?? '') ?></textarea></div>
          <div class="col-12"><label class="form-label fw-medium">Alamat Toko</label><textarea name="store_address" class="form-control" rows="2"><?= clean($seller['store_address'] ?? '') ?></textarea></div>
          <div class="col-md-6"><label class="form-label fw-medium">Provinsi</label><input type="text" name="store_province" class="form-control" value="<?= clean($seller['store_province'] ?? '') ?>"></div>
          <div class="col-md-6">
            <label class="form-label fw-medium">Logo Toko</label>
            <?php if ($seller['store_logo'] ?? null): ?><img src="/<?= clean($seller['store_logo']) ?>" class="img-thumb d-block mb-1" alt=""><?php endif; ?>
            <input type="file" name="store_logo" class="form-control" accept="image/*">
          </div>
        </div>
      </div>
    </div>

    <!-- Rekening -->
    <div class="card mb-3">
      <div class="card-header bg-white fw-bold">Informasi Rekening</div>
      <div class="card-body">
        <div class="alert alert-info small py-2 mb-3"><i class="bi bi-info-circle me-2"></i>Rekening digunakan untuk pencairan dana hasil penjualan.</div>
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label fw-medium">Bank</label>
          <select name="bank_name" class="form-select">
            <option value="">Pilih Bank</option>
            <?php foreach (['BCA','BNI','BRI','Mandiri','BSI','CIMB','Danamon','BTN','Permata'] as $b): ?>
            <option value="<?= $b ?>" <?= ($seller['bank_name'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option>
            <?php endforeach; ?>
          </select></div>
          <div class="col-md-4"><label class="form-label fw-medium">No. Rekening</label><input type="text" name="bank_account" class="form-control" value="<?= clean($seller['bank_account'] ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label fw-medium">Atas Nama</label><input type="text" name="bank_holder" class="form-control" value="<?= clean($seller['bank_holder'] ?? '') ?>"></div>
        </div>
      </div>
    </div>
    <button type="submit" class="btn btn-primary fw-semibold px-5">Simpan Profil Toko</button>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
