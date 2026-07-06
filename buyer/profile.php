<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin(); $user = auth(); $db = Database::getInstance();
$profile = $db->fetchOne("SELECT * FROM users WHERE id=?", [$user['id']]);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? 'profile';
    if ($action === 'profile') {
        $name    = trim($_POST['name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city    = trim($_POST['city'] ?? '');
        $province= trim($_POST['province'] ?? '');
        if (!$name) $errors[] = 'Nama wajib diisi';
        if (empty($errors)) {
            // Handle avatar upload
            $avatar = $profile['avatar'];
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
                $newAvatar = uploadImage($_FILES['avatar'], 'avatars');
                if ($newAvatar) $avatar = $newAvatar;
            }
            $db->update('users', ['name'=>$name,'phone'=>$phone,'address'=>$address,'city'=>$city,'province'=>$province,'avatar'=>$avatar], 'id=?', [$user['id']]);
            $_SESSION['user']['name'] = $name;
            setFlash('success', 'Profil berhasil diperbarui');
            redirect('/buyer/profile.php');
        }
    } elseif ($action === 'password') {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($old, $profile['password'])) $errors[] = 'Password lama salah';
        if (strlen($new) < 8) $errors[] = 'Password baru minimal 8 karakter';
        if ($new !== $confirm) $errors[] = 'Konfirmasi password tidak cocok';
        if (!preg_match('/[A-Z]/', $new) || !preg_match('/[0-9]/', $new)) $errors[] = 'Password harus mengandung huruf kapital dan angka';
        if (empty($errors)) {
            $db->update('users', ['password' => password_hash($new, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST])], 'id=?', [$user['id']]);
            setFlash('success', 'Password berhasil diubah');
            redirect('/buyer/profile.php');
        }
    }
}
$pageTitle = 'Profil Saya';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4" style="max-width:700px">
  <h5 class="fw-bold mb-4"><i class="bi bi-person me-2"></i>Profil Saya</h5>
  <?php if ($errors): ?><div class="alert alert-danger small"><ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-4" id="profileTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-profile">Profil</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-password">Ubah Password</a></li>
  </ul>

  <div class="tab-content">
    <!-- Profile Tab -->
    <div class="tab-pane fade show active" id="tab-profile">
      <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="profile">
        <div class="text-center mb-4">
          <div class="position-relative d-inline-block">
            <?php if ($profile['avatar']): ?>
              <img src="/<?= clean($profile['avatar']) ?>" id="avatar-preview" class="rounded-circle" style="width:90px;height:90px;object-fit:cover;border:3px solid var(--border)">
            <?php else: ?>
              <div id="avatar-placeholder" class="rounded-circle d-flex align-items-center justify-content-center" style="width:90px;height:90px;background:var(--primary);color:white;font-size:36px;font-weight:700"><?= strtoupper(substr($profile['name'], 0, 1)) ?></div>
            <?php endif; ?>
          </div>
          <div class="mt-2"><input type="file" name="avatar" accept="image/*" class="form-control form-control-sm" style="max-width:250px;margin:0 auto" data-preview="avatar-preview"></div>
        </div>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label fw-medium">Nama Lengkap *</label><input type="text" name="name" class="form-control" value="<?= clean($profile['name']) ?>" required></div>
          <div class="col-md-6"><label class="form-label fw-medium">Email</label><input type="email" class="form-control" value="<?= clean($profile['email']) ?>" disabled></div>
          <div class="col-md-6"><label class="form-label fw-medium">No. HP</label><input type="text" name="phone" class="form-control" value="<?= clean($profile['phone'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label fw-medium">Kota</label><input type="text" name="city" class="form-control" value="<?= clean($profile['city'] ?? '') ?>"></div>
          <div class="col-12"><label class="form-label fw-medium">Alamat Lengkap</label><textarea name="address" class="form-control" rows="2"><?= clean($profile['address'] ?? '') ?></textarea></div>
          <div class="col-md-6"><label class="form-label fw-medium">Provinsi</label><input type="text" name="province" class="form-control" value="<?= clean($profile['province'] ?? '') ?>"></div>
        </div>
        <div class="mt-3"><button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Perubahan</button></div>
      </form>
    </div>

    <!-- Password Tab -->
    <div class="tab-pane fade" id="tab-password">
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="password">
        <div class="mb-3"><label class="form-label fw-medium">Password Lama</label><input type="password" name="old_password" class="form-control" required></div>
        <div class="mb-3"><label class="form-label fw-medium">Password Baru</label><input type="password" name="new_password" class="form-control" placeholder="Min 8 karakter, huruf kapital + angka" required></div>
        <div class="mb-4"><label class="form-label fw-medium">Konfirmasi Password Baru</label><input type="password" name="confirm_password" class="form-control" required></div>
        <button type="submit" class="btn btn-primary fw-semibold px-4">Ubah Password</button>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
