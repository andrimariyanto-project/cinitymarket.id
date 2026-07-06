<?php
require_once __DIR__ . '/includes/functions.php';
startSession();
if (isLoggedIn()) redirect('/index.php');
$message = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = sanitizeEmail($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } else {
        $db   = Database::getInstance();
        $user = $db->fetchOne("SELECT id FROM users WHERE email=? AND is_active=1", [$email]);
        $message = 'Jika email terdaftar, instruksi reset password telah dikirim ke inbox Anda.';
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $db->update('users', ['remember_token' => hash('sha256', $token)], 'id=?', [$user['id']]);
            // TODO: kirim email via PHPMailer/SMTP
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lupa Password — cinitymarket.id</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
<div class="container">
  <div class="d-flex justify-content-center">
    <div class="auth-card">
      <a href="/index.php" class="brand-logo mb-4 d-inline-flex">
        <div class="brand-mark"><i class="bi bi-bag-heart-fill"></i></div>
        <span class="brand-name">cinitymarket<span>.id</span></span>
      </a>
      <h2 style="font-size:22px;font-weight:800;margin-bottom:6px">Lupa Password?</h2>
      <p style="color:var(--text-2);font-size:14px;margin-bottom:24px">Masukkan email Anda, kami kirimkan link reset.</p>

      <?php if ($message): ?>
      <div class="alert alert-success d-flex gap-2 mb-4">
        <i class="bi bi-check-circle-fill mt-1"></i><span><?= clean($message) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-danger d-flex gap-2 mb-4">
        <i class="bi bi-exclamation-circle-fill mt-1"></i><span><?= clean($error) ?></span>
      </div>
      <?php endif; ?>

      <?php if (!$message): ?>
      <form method="POST">
        <?= csrfField() ?>
        <div class="mb-4">
          <label class="form-label">Email terdaftar</label>
          <input type="email" name="email" class="form-control" placeholder="nama@email.com"
                 value="<?= clean($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-lg">Kirim Link Reset</button>
      </form>
      <?php endif; ?>

      <div class="text-center mt-4" style="font-size:13.5px">
        <a href="/login.php" style="color:var(--brand);text-decoration:none;font-weight:600">← Kembali ke Login</a>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
