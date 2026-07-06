<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin'); $db = Database::getInstance();
$q = clean($_GET['q'] ?? '');
$role = clean($_GET['role'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$where = '1=1'; $params = [];
if ($q) { $where .= " AND (name LIKE ? OR email LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($role) { $where .= " AND role=?"; $params[] = $role; }
$total = $db->fetchOne("SELECT COUNT(*) c FROM users WHERE $where", $params)['c'];
$pg = paginate($total, $page, 30);
$users = $db->fetchAll("SELECT * FROM users WHERE $where ORDER BY created_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}", $params);

// Toggle active
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $uid = (int)($_POST['user_id'] ?? 0);
    $act = (int)($_POST['is_active'] ?? 0);
    if ($uid && $uid !== auth()['id']) { $db->update('users', ['is_active' => $act ? 0 : 1], 'id=?', [$uid]); }
    redirect('/admin/users.php?' . http_build_query($_GET));
}
$pageTitle = 'Kelola Pengguna - Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid py-4"><div class="row">
<div class="col-lg-2"><div class="sidebar card p-3"><div class="fw-bold mb-3 text-primary"><i class="bi bi-shield-check me-2"></i>Admin</div>
<nav class="nav flex-column gap-1">
<a href="/admin/dashboard.php" class="nav-link text-secondary"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
<a href="/admin/orders.php" class="nav-link text-secondary"><i class="bi bi-bag me-2"></i>Pesanan</a>
<a href="/admin/sellers.php" class="nav-link text-secondary"><i class="bi bi-shop me-2"></i>Penjual</a>
<a href="/admin/users.php" class="nav-link text-dark fw-medium"><i class="bi bi-people me-2"></i>Pengguna</a>
<a href="/admin/settings.php" class="nav-link text-secondary"><i class="bi bi-gear me-2"></i>Pengaturan</a>
</nav></div></div>
<div class="col-lg-10">
<h5 class="fw-bold mb-4">Kelola Pengguna</h5>
<div class="d-flex flex-wrap gap-2 mb-3">
<form method="GET" class="d-flex gap-2"><input type="text" name="q" class="form-control form-control-sm" placeholder="Cari nama/email..." value="<?= clean($q) ?>" style="width:250px">
<select name="role" class="form-select form-select-sm" style="width:130px"><option value="">Semua Role</option><option value="buyer" <?= $role==='buyer'?'selected':'' ?>>Pembeli</option><option value="seller" <?= $role==='seller'?'selected':'' ?>>Penjual</option><option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option></select>
<button class="btn btn-sm btn-outline-secondary">Cari</button></form>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0 small">
<thead class="table-light"><tr><th>Nama</th><th>Email</th><th>HP</th><th>Role</th><th>Status</th><th>Bergabung</th><th></th></tr></thead>
<tbody>
<?php foreach ($users as $u): ?>
<tr>
<td class="fw-medium"><?= clean($u['name']) ?></td>
<td><?= clean($u['email']) ?></td>
<td><?= clean($u['phone'] ?? '-') ?></td>
<td><span class="badge <?= $u['role']==='admin'?'bg-danger':($u['role']==='seller'?'bg-success':'bg-secondary') ?>"><?= $u['role'] ?></span></td>
<td><?= $u['is_active'] ? '<span class="text-success">Aktif</span>' : '<span class="text-danger">Nonaktif</span>' ?></td>
<td class="text-secondary"><?= formatDate($u['created_at']) ?></td>
<td><?php if ($u['id'] !== auth()['id']): ?><form method="POST" class="d-inline"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><input type="hidden" name="is_active" value="<?= $u['is_active'] ?>"><button class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" onclick="return confirm('Ubah status?')"><?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table></div></div>
</div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
