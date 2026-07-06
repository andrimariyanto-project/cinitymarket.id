<?php
/**
 * cinitymarket.id — Setup & Reset Tool
 * HAPUS FILE INI setelah setup selesai!
 * DELETE THIS FILE after setup is complete!
 */

// Simple IP protection - only allow localhost unless overridden
$allowedIPs = ['127.0.0.1', '::1'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($clientIP, $allowedIPs) && !isset($_GET['token'])) {
    http_response_code(403);
    die('<h2>403 Forbidden</h2><p>Akses hanya dari localhost, atau tambahkan ?token=cinitysetup2024 ke URL</p>');
}
if (isset($_GET['token']) && $_GET['token'] !== 'cinitysetup2024') {
    http_response_code(403);
    die('Token salah');
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Reset / create admin
    if ($action === 'reset_admin') {
        $email    = trim($_POST['email']    ?? 'admin@cinitymarket.id');
        $name     = trim($_POST['name']     ?? 'Admin cinitymarket.id');
        $password = trim($_POST['password'] ?? '');

        if (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter';
        } else {
            try {
                $db   = Database::getInstance();
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                $existing = $db->fetchOne("SELECT id FROM users WHERE email=?", [$email]);
                if ($existing) {
                    $db->update('users', ['name'=>$name,'password'=>$hash,'role'=>'admin','is_active'=>1], 'email=?', [$email]);
                    $message = '✅ Password admin berhasil direset!';
                } else {
                    $db->insert('users', ['name'=>$name,'email'=>$email,'password'=>$hash,'role'=>'admin','is_active'=>1]);
                    $message = '✅ Admin baru berhasil dibuat!';
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }

    // Test DB connection
    if ($action === 'test_db') {
        try {
            $db  = Database::getInstance();
            $row = $db->fetchOne("SELECT COUNT(*) as c FROM users");
            $message = '✅ Koneksi database OK! Total user: ' . $row['c'];
        } catch (Exception $e) {
            $error = '❌ Koneksi gagal: ' . $e->getMessage();
        }
    }

    // Hash generator
    if ($action === 'gen_hash') {
        $pwd = $_POST['gen_password'] ?? '';
        if ($pwd) {
            $hash    = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);
            $message = 'Hash untuk "' . htmlspecialchars($pwd) . '":<br><code style="word-break:break-all;font-size:13px">' . $hash . '</code>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Setup — cinitymarket.id</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,sans-serif;background:#F1F5F9;min-height:100vh;padding:32px 16px;color:#0F172A}
.wrap{max-width:600px;margin:0 auto}
h1{font-size:22px;font-weight:800;margin-bottom:4px}
.sub{color:#64748B;font-size:13.5px;margin-bottom:32px}
.warning{background:#FEF3C7;border:1px solid #F59E0B;border-radius:10px;padding:14px 16px;font-size:13px;color:#92400E;margin-bottom:24px}
.card{background:white;border:1px solid #E2E8F0;border-radius:14px;padding:24px;margin-bottom:20px}
.card h2{font-size:15px;font-weight:700;margin-bottom:16px}
label{display:block;font-size:13px;font-weight:600;margin-bottom:5px;color:#374151}
input{width:100%;border:1.5px solid #E2E8F0;border-radius:8px;padding:9px 12px;font-size:14px;margin-bottom:12px;font-family:inherit}
input:focus{outline:none;border-color:#2D6BE4;box-shadow:0 0 0 3px rgba(45,107,228,.12)}
button{background:#2D6BE4;color:white;border:none;border-radius:8px;padding:10px 20px;font-size:14px;font-weight:600;cursor:pointer;width:100%}
button:hover{background:#1A56C4}
.msg{padding:12px 16px;border-radius:8px;font-size:13.5px;margin-bottom:16px}
.msg.ok{background:#DCFCE7;color:#14532D;border:1px solid #86EFAC}
.msg.err{background:#FEE2E2;color:#7F1D1D;border:1px solid #FCA5A5}
code{background:#F1F5F9;padding:2px 6px;border-radius:4px;font-size:12px}
.delete-warn{background:#FEE2E2;border:1px solid #FCA5A5;border-radius:10px;padding:14px 16px;font-size:13px;color:#7F1D1D;margin-top:24px;text-align:center}
</style>
</head>
<body>
<div class="wrap">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
    <div style="width:36px;height:36px;background:#2D6BE4;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px">🛍️</div>
    <h1>cinitymarket.id — Setup</h1>
  </div>
  <p class="sub">Tool untuk setup awal, reset password, dan cek koneksi database</p>

  <div class="warning">
    ⚠️ <strong>PENTING:</strong> Hapus file <code>setup.php</code> ini setelah selesai konfigurasi!
  </div>

  <?php if ($message): ?><div class="msg ok"><?= $message ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <!-- Test DB -->
  <div class="card">
    <h2>🔌 Test Koneksi Database</h2>
    <form method="POST">
      <input type="hidden" name="action" value="test_db">
      <button type="submit">Test Koneksi Sekarang</button>
    </form>
    <p style="font-size:12.5px;color:#64748B;margin-top:10px">
      Pastikan sudah mengisi DB_HOST, DB_NAME, DB_USER, DB_PASS di <code>config/config.php</code>
    </p>
  </div>

  <!-- Reset Admin -->
  <div class="card">
    <h2>🔑 Reset / Buat Akun Admin</h2>
    <form method="POST">
      <input type="hidden" name="action" value="reset_admin">
      <label>Email Admin</label>
      <input type="email" name="email" value="admin@cinitymarket.id" required>
      <label>Nama</label>
      <input type="text" name="name" value="Admin cinitymarket.id" required>
      <label>Password Baru *</label>
      <input type="text" name="password" placeholder="Minimal 6 karakter" required>
      <button type="submit">Reset / Buat Admin</button>
    </form>
  </div>

  <!-- Hash Generator -->
  <div class="card">
    <h2>🔐 Generate Password Hash</h2>
    <p style="font-size:13px;color:#64748B;margin-bottom:12px">Perlu insert user manual ke database? Generate hash bcrypt-nya di sini.</p>
    <form method="POST">
      <input type="hidden" name="action" value="gen_hash">
      <label>Password yang ingin di-hash</label>
      <input type="text" name="gen_password" placeholder="masukkan password..." required>
      <button type="submit">Generate Hash</button>
    </form>
  </div>

  <div class="delete-warn">
    🗑️ <strong>Setelah setup selesai, HAPUS file ini:</strong><br>
    <code>rm /path/to/cinitymarket/setup.php</code>
  </div>
</div>
</body>
</html>
