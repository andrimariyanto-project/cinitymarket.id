<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin'); $db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $keys = ['site_name','admin_fee','courier_store_fee','tripay_merchant_code','tripay_api_key','tripay_private_key','tripay_is_production','maintenance_mode'];
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $val = trim($_POST[$key]);
            $existing = $db->fetchOne("SELECT id FROM settings WHERE key_name=?", [$key]);
            if ($existing) $db->update('settings', ['value' => $val], 'key_name=?', [$key]);
            else $db->insert('settings', ['key_name' => $key, 'value' => $val]);
        }
    }
    setFlash('success', 'Pengaturan berhasil disimpan');
    redirect('/admin/settings.php');
}

$settings = [];
foreach ($db->fetchAll("SELECT key_name, value FROM settings") as $s) { $settings[$s['key_name']] = $s['value']; }
$get = fn($k, $default='') => $settings[$k] ?? $default;
$pageTitle = 'Pengaturan - Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid py-4"><div class="row">
<div class="col-lg-2"><div class="sidebar card p-3"><div class="fw-bold mb-3 text-primary"><i class="bi bi-shield-check me-2"></i>Admin</div>
<nav class="nav flex-column gap-1">
<a href="/admin/dashboard.php" class="nav-link text-secondary"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
<a href="/admin/orders.php" class="nav-link text-secondary"><i class="bi bi-bag me-2"></i>Pesanan</a>
<a href="/admin/sellers.php" class="nav-link text-secondary"><i class="bi bi-shop me-2"></i>Penjual</a>
<a href="/admin/users.php" class="nav-link text-secondary"><i class="bi bi-people me-2"></i>Pengguna</a>
<a href="/admin/settings.php" class="nav-link text-dark fw-medium"><i class="bi bi-gear me-2"></i>Pengaturan</a>
</nav></div></div>
<div class="col-lg-8">
<h5 class="fw-bold mb-4">Pengaturan Sistem</h5>
<form method="POST">
<?= csrfField() ?>
<div class="card mb-3"><div class="card-header bg-white fw-bold">Umum</div><div class="card-body row g-3">
  <div class="col-md-6"><label class="form-label fw-medium">Nama Website</label><input type="text" name="site_name" class="form-control" value="<?= clean($get('site_name','cinitymarket.id')) ?>"></div>
  <div class="col-md-3"><label class="form-label fw-medium">Biaya Admin (Rp)</label><input type="number" name="admin_fee" class="form-control" value="<?= (int)$get('admin_fee', 2000) ?>"></div>
  <div class="col-md-3"><label class="form-label fw-medium">Biaya Kurir Toko (Rp)</label><input type="number" name="courier_store_fee" class="form-control" value="<?= (int)$get('courier_store_fee', 2000) ?>"></div>
  <div class="col-md-4"><label class="form-label fw-medium">Mode Maintenance</label>
  <select name="maintenance_mode" class="form-select"><option value="0" <?= $get('maintenance_mode')==='0'?'selected':'' ?>>Nonaktif</option><option value="1" <?= $get('maintenance_mode')==='1'?'selected':'' ?>>Aktif</option></select></div>
</div></div>

<div class="card mb-3"><div class="card-header bg-white fw-bold">Konfigurasi Tripay</div><div class="card-body row g-3">
  <div class="col-md-4"><label class="form-label fw-medium">Merchant Code</label><input type="text" name="tripay_merchant_code" class="form-control" value="<?= clean($get('tripay_merchant_code')) ?>"></div>
  <div class="col-md-4"><label class="form-label fw-medium">API Key</label><input type="text" name="tripay_api_key" class="form-control" value="<?= clean($get('tripay_api_key')) ?>"></div>
  <div class="col-md-4"><label class="form-label fw-medium">Private Key</label><input type="text" name="tripay_private_key" class="form-control" value="<?= clean($get('tripay_private_key')) ?>"></div>
  <div class="col-md-4"><label class="form-label fw-medium">Mode Produksi</label>
  <select name="tripay_is_production" class="form-select"><option value="0" <?= $get('tripay_is_production')==='0'?'selected':'' ?>>Sandbox (Testing)</option><option value="1" <?= $get('tripay_is_production')==='1'?'selected':'' ?>>Production (Live)</option></select></div>
  <div class="col-12"><div class="alert alert-info small py-2 mb-0">Dapatkan API Key di <a href="https://tripay.co.id/merchant" target="_blank">tripay.co.id/merchant</a>. Callback URL: <code><?= APP_URL ?>/api/payment-callback.php</code></div></div>
</div></div>

<button type="submit" class="btn btn-primary fw-semibold px-5"><i class="bi bi-save me-2"></i>Simpan Pengaturan</button>
</form>
</div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
