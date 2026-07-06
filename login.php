<?php
require_once __DIR__ . '/includes/functions.php';
startSession();
if (isLoggedIn()) redirect('/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email    = sanitizeEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        $db   = Database::getInstance();
        $user = $db->fetchOne("SELECT * FROM users WHERE email=? AND is_active=1", [$email]);
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'role'=>$user['role'],'avatar'=>$user['avatar']];
            $go = $_GET['redirect'] ?? ($user['role'] === 'admin' ? '/admin/dashboard.php' : ($user['role'] === 'seller' ? '/seller/dashboard.php' : '/index.php'));
            redirect($go);
        } else { $error = 'Email atau password salah.'; }
    } else { $error = 'Isi semua field.'; }
}
?>
<!DOCTYPE html><html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk — cinitymarket.id</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
<div class="container">
<div class="row justify-content-center align-items-center" style="min-height:100vh">
<div class="col-md-5 col-lg-4">
<div class="auth-card">
  <div class="text-center mb-5">
    <a href="/index.php" class="brand-logo justify-content-center mb-4 d-inline-flex">
      <div class="brand-mark" style="width:40px;height:40px;font-size:20px"><i class="bi bi-bag-heart-fill"></i></div>
      <span class="brand-name" style="font-size:20px">cinitymarket<span>.id</span></span>
    </a>
    <h4 style="font-weight:800;margin-bottom:4px">Selamat datang!</h4>
    <p style="color:var(--text-2);font-size:14px">Masuk ke akun Anda</p>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-exclamation-circle"></i><?= clean($error) ?>
  </div>
  <?php endif; ?>

  <form method="POST">
    <?= csrfField() ?>
    <div class="mb-4">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" placeholder="nama@email.com"
             value="<?= isset($_POST['email']) ? clean($_POST['email']) : '' ?>" required autofocus>
    </div>
    <div class="mb-5">
      <div class="d-flex justify-content-between mb-1">
        <label class="form-label mb-0">Password</label>
        <a href="/forgot-password.php" style="font-size:12.5px;color:var(--brand);text-decoration:none">Lupa password?</a>
      </div>
      <div style="position:relative">
        <input type="password" name="password" id="pw" class="form-control" placeholder="Password" required style="padding-right:44px">
        <button type="button" onclick="togglePw()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-3);cursor:pointer;font-size:16px;padding:0">
          <i class="bi bi-eye" id="pwIcon"></i>
        </button>
      </div>
    </div>
    <button type="submit" class="btn btn-primary w-100 btn-lg">Masuk</button>
  </form>

  <div style="text-align:center;margin-top:24px;font-size:13.5px;color:var(--text-2)">
    Belum punya akun? <a href="/register.php" style="color:var(--brand);font-weight:700;text-decoration:none">Daftar gratis</a>
  </div>
</div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePw(){
  const pw=document.getElementById('pw'),ic=document.getElementById('pwIcon');
  pw.type=pw.type==='password'?'text':'password';
  ic.className=pw.type==='password'?'bi bi-eye':'bi bi-eye-slash';
}
</script>
</body></html>
