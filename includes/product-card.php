<?php
// includes/product-card.php
// Variable: $p (product row with image_path, store_name)
?>
<a href="/product.php?id=<?= $p['id'] ?>" class="cm-product-card">
  <?php if (!empty($p['image_path'])): ?>
    <img src="<?= UPLOAD_URL . e($p['image_path']) ?>"
         alt="<?= e($p['name']) ?>"
         class="cm-product-img"
         loading="lazy">
  <?php else: ?>
    <div class="cm-product-img-placeholder">
      <i data-feather="image" style="width:40px;height:40px;opacity:.3"></i>
    </div>
  <?php endif; ?>

  <div class="cm-product-info">
    <div class="cm-product-name"><?= e($p['name']) ?></div>
    <div class="cm-product-price"><?= rupiah((float)$p['price']) ?></div>
    <div class="cm-product-seller">
      <i data-feather="store" style="width:11px"></i>
      <?= e($p['store_name'] ?? 'Toko') ?>
    </div>
    <?php if ($p['sold_count'] > 0): ?>
      <div class="cm-product-meta mt-1"><?= number_format($p['sold_count']) ?> terjual</div>
    <?php endif; ?>
  </div>
</a>
