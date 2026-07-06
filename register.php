<?php
require_once __DIR__ . '/includes/functions.php';
startSession();
if (isLoggedIn()) redirect('/index.php');

$errors = [];
$role   = $_POST['role'] ?? $_GET['role'] ?? 'buyer';
if (!in_array($role, ['buyer','seller'])) $role = 'buyer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name    = trim($_POST['name'] ?? '');
    $email   = sanitizeEmail($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $password= $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$name || strlen($name) < 3) $errors[] = 'Nama minimal 3 karakter';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid';
    if (strlen($password) < 8) $errors[] = 'Password minimal 8 karakter';
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) $errors[] = 'Password harus ada huruf kapital & angka';
    if ($password !== $confirm) $errors[] = 'Konfirmasi password tidak cocok';

    if (empty($errors)) {
        $db = Database::getInstance();
        if ($db->fetchOne("SELECT id FROM users WHERE email=?", [$email])) {
            $errors[] = 'Email sudah terdaftar';
        } else {
            $db->beginTransaction();
            try {
                $uid = $db->insert('users', ['name'=>$name,'email'=>$email,'phone'=>$phone,'password'=>password_hash($password,PASSWORD_BCRYPT,['cost'=>BCRYPT_COST]),'role'=>$role]);
                if ($role === 'seller') {
                    $sname = $name . ' Shop';
                    $db->insert('seller_profiles', ['user_id'=>$uid,'store_name'=>$sname,'store_slug'=>uniqueSlug('seller_profiles',$sname)]);
                }
                $db->commit();
                setFlash('success', 'Akun berhasil dibuat! Silakan masuk.');
                redirect('/login.php');
            } catch (Exception $e) { $db->rollback(); $errors[] = 'Gagal mendaftar, coba lagi.'; }
        }
    }
}
?>
<!DOCTYPE html><html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Daftar — cinitymarket.id</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
<div class="container">
<div class="row justify-content-center" style="padding:40px 0">
<div class="col-md-6 col-lg-5">
<div class="auth-card">
  <div class="text-center mb-4">
    <a href="/index.php" class="brand-logo justify-content-center mb-3 d-inline-flex">
      <div class="brand-mark" style="width:40px;height:40px;font-size:20px"><i class="bi bi-bag-heart-fill"></i></div>
      <span class="brand-name" style="font-size:20px">cinitymarket<span>.id</span></span>
    </a>
    <h4 style="font-weight:800;margin-bottom:4px">Buat Akun Baru</h4>
  </div>

  <?php if ($errors): ?>
  <div class="alert alert-danger mb-4">
    <ul style="margin:0;padding-left:16px"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul>
  </div>
  <?php endif; ?>

  <!-- Role selector -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:24px">
    <?php foreach (['buyer'=>['bi-person-fill','Pembeli','Beli produk'],'seller'=>['bi-shop','Penjual','Jual produk']] as $r=>[$icon,$label,$sub]): ?>
    <label style="cursor:pointer">
      <input type="radio" name="role" value="<?=$r?>" form="regForm" class="d-none" <?=$role===$r?'checked':''?> onchange="document.getElementById('roleInput').value=this.value;updateRoleUI()">
      <div id="rolecard-<?=$r?>" style="border:2px solid <?=$role===$r?'var(--brand)':'var(--border)'?>;border-radius:var(--radius);padding:14px 12px;text-align:center;background:<?=$role===$r?'var(--brand-light)':'white'?>;transition:.15s" onclick="selectRole('<?=$r?>')">
        <i class="bi <?=$icon?>" style="font-size:24px;color:<?=$role===$r?'var(--brand)':'var(--text-3)?>"></i>
        <div style="font-weight:700;font-size:14px;margin-top:6px;color:<?=$role===$r?'var(--brand)':'var(--text-1)?>"><?=$label?></div>
        <div style="font-size:12px;color:var(--text-3)"><?=$sub?></div>
      </div>
    </label>
    <?php endforeach; ?>
  </div>

  <form id="regForm" method="POST">
    <?= csrfField() ?>
    <input type="hidden" name="role" id="roleInput" value="<?= $role ?>">
    <div class="mb-3">
      <label class="form-label">Nama Lengkap</label>
      <input type="text" name="name" class="form-control" value="<?= clean($_POST['name'] ?? '') ?>" placeholder="Masukkan nama lengkap" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?= clean($_POST['email'] ?? '') ?>" placeholder="nama@email.com" required>
    </div>
    <div class="mb-3">
      <label class="form-label">No. HP <span style="color:var(--text-3);font-weight:400">(opsional)</span></label>
      <input type="tel" name="phone" class="form-control" value="<?= clean($_POST['phone'] ?? '') ?>" placeholder="08xx-xxxx-xxxx">
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="Min 8 karakter, huruf kapital & angka" required>
    </div>
    <div class="mb-4">
      <label class="form-label">Konfirmasi Password</label>
      <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
    </div>
    <button type="submit" class="btn btn-primary w-100 btn-lg">Daftar Sekarang</button>
  </form>

  <div style="text-align:center;margin-top:20px;font-size:13.5px;color:var(--text-2)">
    Sudah punya akun? <a href="/login.php" style="color:var(--brand);font-weight:700;text-decoration:none">Masuk</a>
  </div>
</div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function selectRole(r){
  document.getElementById('roleInput').value=r;
  ['buyer','seller'].forEach(x=>{
    const active=x===r;
    const card=document.getElementById('rolecard-'+x);
    card.style.borderColor=active?'var(--brand)':'var(--border)';
    card.style.background=active?'var(--brand-light)':'white';
    card.querySelector('i').style.color=active?'var(--brand)':'var(--text-3)';
    card.querySelector('div').style.color=active?'var(--brand)':'var(--text-1)';
  });
}
</script>
</body></html>
