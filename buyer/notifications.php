<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin(); $user = auth(); $db = Database::getInstance();
// Mark all as read
$db->query("UPDATE notifications SET is_read=1 WHERE user_id=?", [$user['id']]);
$notifs = $db->fetchAll("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50", [$user['id']]);
$pageTitle = 'Notifikasi';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4" style="max-width:700px">
  <h5 class="fw-bold mb-4"><i class="bi bi-bell me-2"></i>Notifikasi</h5>
  <?php if (empty($notifs)): ?>
    <div class="text-center py-5"><i class="bi bi-bell-slash fs-1 text-secondary"></i><p class="text-secondary mt-2">Belum ada notifikasi</p></div>
  <?php else: ?>
    <?php foreach ($notifs as $n): ?>
    <a href="<?= clean($n['link'] ?? '#') ?>" class="text-decoration-none">
      <div class="card mb-2 card-hover <?= !$n['is_read'] ? 'border-primary' : '' ?>">
        <div class="card-body py-2 px-3 d-flex align-items-start gap-3">
          <div class="mt-1" style="font-size:20px">
            <?= match($n['type']) { 'order'=>'📦', 'payment'=>'💳', 'info'=>'ℹ️', default=>'🔔' } ?>
          </div>
          <div class="flex-grow-1">
            <div class="fw-medium small text-dark"><?= clean($n['title']) ?></div>
            <div class="text-secondary small"><?= clean($n['message']) ?></div>
          </div>
          <div class="text-secondary" style="font-size:11px;white-space:nowrap"><?= timeAgo($n['created_at']) ?></div>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
