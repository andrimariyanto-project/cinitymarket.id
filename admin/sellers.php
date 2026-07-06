<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin'); $db = Database::getInstance();
$q = clean($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$where = '1=1'; $params = [];
if ($q) { $where .= " AND (sp.store_name LIKE ? OR u.email LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }

// Verify/unverify action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $sid = (int)($_POST['seller_id'] ?? 0);
    $ver = (int)($_POST['is_verified'] ?? 0);
    $db->update('seller_profiles', ['is_verified' => $ver ? 0 : 1], 'id=?', [$sid]);
    redirect('/admin/sellers.php?' . http_build_query($_GET));
}

$total = $db->fetchOne("SELECT COUNT(*) c FROM seller_profiles sp JOIN users u ON u.id=sp.user_id WHERE $where", $params)['c'];
$pg = paginate($total, $page, 30);
$sellers = $db->fetchAll("SELECT sp.*,u.name as owner_name,u.email,u.phone,u.is_active,(SELECT COUNT(*) FROM products WHERE seller_id=sp.id AND status='active') as prod_count,(SELECT COUNT(*) FROM orders WHERE seller_id=sp.id AND status='completed') as order_count FROM seller_profiles sp JOIN users u ON u.id=sp.user_id WHERE $where ORDER BY sp.created_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}", $params);
$pageTitle = 'Kelola Penjual - Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid py-4"><div class="row">
<div class="col-lg-2"><div class="sidebar card p-3"><div class="fw-bold mb-3 text-primary"><i class="bi bi-shield-check me-2"></i>Admin</div>
<nav class="nav flex-column gap-1">
<a href="/admin/dashboard.php" class="nav-link text-secondary"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
<a href="/admin/orders.php" class="nav-link text-secondary"><i class="bi bi-bag me-2"></i>Pesanan</a>
<a href="/admin/sellers.php" class="nav-link text-dark fw-medium"><i class="bi bi-shop me-2"></i>Penjual</a>
<a href="/admin/users.php" class="nav-link text-secondary"><i class="bi bi-people me-2"></i>Pengguna</a>
<a href="/admin/settings.php" class="nav-link text-secondary"><i class="bi bi-gear me-2"></i>Pengaturan</a>
</nav></div></div>
<div class="col-lg-10">
<h5 class="fw-bold mb-4">Kelola Penjual</h5>
<form method="GET" class="d-flex gap-2 mb-3"><input type="text" name="q" class="form-control form-control-sm" placeholder="Cari toko/email..." value="<?= clean($q) ?>" style="width:300px"><button class="btn btn-sm btn-outline-secondary">Cari</button></form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0 small">
<thead class="table-light"><tr><th>Toko</th><th>Pemilik</th><th>Email</th><th>Produk</th><th>Pesanan Selesai</th><th>Rating</th><th>Verifikasi</th><th></th></tr></thead>
<tbody>
<?php foreach ($sellers as $s): ?>
<tr>
<td><div class="fw-medium"><?= clean($s['store_name']) ?></div><div class="text-secondary" style="font-size:11px"><?= $s['store_city'] ? clean($s['store_city']) : '-' ?></div></td>
<td><?= clean($s['owner_name']) ?></td>
<td><?= clean($s['email']) ?></td>
<td><?= $s['prod_count'] ?></td>
<td><?= $s['order_count'] ?></td>
<td><i class="bi bi-star-fill text-warning"></i> <?= number_format($s['rating'], 1) ?></td>
<td><?= $s['is_verified'] ? '<span class="badge bg-success">✓ Terverifikasi</span>' : '<span class="badge bg-secondary">Belum</span>' ?></td>
<td><form method="POST" class="d-inline"><?= csrfField() ?><input type="hidden" name="seller_id" value="<?= $s['id'] ?>"><input type="hidden" name="is_verified" value="<?= $s['is_verified'] ?>"><button class="btn btn-sm <?= $s['is_verified'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>" onclick="return confirm('Ubah status verifikasi?')"><?= $s['is_verified'] ? 'Batalkan Verif' : 'Verifikasi' ?></button></form></td>
</tr>
<?php endforeach; ?>
<?php if (empty($sellers)): ?><tr><td colspan="8" class="text-center py-4 text-secondary">Belum ada penjual</td></tr><?php endif; ?>
</tbody>
</table></div></div>
</div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
