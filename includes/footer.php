<footer style="background:#0F1523;color:#94A3B8;padding:48px 0 0;margin-top:64px">
  <div class="container">
    <div class="row g-4 mb-5">
      <!-- Brand col -->
      <div class="col-lg-4 col-md-6">
        <a href="/index.php" class="brand-logo mb-3 d-inline-flex">
          <div class="brand-mark"><i class="bi bi-bag-heart-fill"></i></div>
          <span class="brand-name" style="color:white">cinitymarket<span style="color:var(--brand)">.id</span></span>
        </a>
        <p style="font-size:13.5px;line-height:1.7;max-width:280px">Marketplace lokal terpercaya. Belanja mudah, diantar kurir toko, pembayaran aman via Tripay.</p>
        <div class="d-flex gap-2 mt-3">
          <a href="#" style="width:34px;height:34px;border-radius:8px;border:1px solid #2A3349;display:flex;align-items:center;justify-content:center;color:#94A3B8;font-size:16px;text-decoration:none"><i class="bi bi-instagram"></i></a>
          <a href="#" style="width:34px;height:34px;border-radius:8px;border:1px solid #2A3349;display:flex;align-items:center;justify-content:center;color:#94A3B8;font-size:16px;text-decoration:none"><i class="bi bi-facebook"></i></a>
          <a href="#" style="width:34px;height:34px;border-radius:8px;border:1px solid #2A3349;display:flex;align-items:center;justify-content:center;color:#94A3B8;font-size:16px;text-decoration:none"><i class="bi bi-twitter-x"></i></a>
        </div>
      </div>

      <!-- Pembeli -->
      <div class="col-6 col-lg-2">
        <div style="color:white;font-weight:700;font-size:13.5px;margin-bottom:14px">Pembeli</div>
        <?php foreach([
          ['/register.php','Daftar Akun'],
          ['/buyer/orders.php','Lacak Pesanan'],
          ['/buyer/cart.php','Keranjang'],
          ['/buyer/wishlist.php','Wishlist'],
        ] as [$href,$label]): ?>
        <a href="<?= $href ?>" style="display:block;color:#94A3B8;text-decoration:none;font-size:13px;margin-bottom:8px;transition:.15s" onmouseover="this.style.color='white'" onmouseout="this.style.color='#94A3B8'"><?= $label ?></a>
        <?php endforeach; ?>
      </div>

      <!-- Penjual -->
      <div class="col-6 col-lg-2">
        <div style="color:white;font-weight:700;font-size:13.5px;margin-bottom:14px">Penjual</div>
        <?php foreach([
          ['/register.php?role=seller','Buka Toko'],
          ['/seller/dashboard.php','Dashboard'],
          ['/seller/products.php','Kelola Produk'],
          ['/seller/orders.php','Pesanan Masuk'],
        ] as [$href,$label]): ?>
        <a href="<?= $href ?>" style="display:block;color:#94A3B8;text-decoration:none;font-size:13px;margin-bottom:8px;transition:.15s" onmouseover="this.style.color='white'" onmouseout="this.style.color='#94A3B8'"><?= $label ?></a>
        <?php endforeach; ?>
      </div>

      <!-- Pembayaran -->
      <div class="col-lg-4 col-md-6">
        <div style="color:white;font-weight:700;font-size:13.5px;margin-bottom:14px">Pembayaran</div>
        <div class="d-flex flex-wrap gap-2 mb-3">
          <?php foreach(['QRIS','Transfer Bank','GoPay','OVO','Dana','ShopeePay','Indomaret','Alfamart'] as $m): ?>
          <span style="background:#1E293B;border:1px solid #2A3349;border-radius:6px;padding:4px 10px;font-size:11.5px;color:#94A3B8"><?= $m ?></span>
          <?php endforeach; ?>
        </div>
        <div style="font-size:12.5px">Diproses aman via <a href="https://tripay.co.id" target="_blank" style="color:var(--brand);text-decoration:none;font-weight:600">Tripay Payment Gateway</a></div>
        <div style="margin-top:12px;font-size:12.5px">
          <span style="background:#1E293B;border:1px solid #2A3349;border-radius:6px;padding:5px 12px;display:inline-flex;align-items:center;gap:6px">
            <i class="bi bi-truck-front-fill" style="color:var(--brand)"></i>
            Kurir Toko Rp 2.000 + Admin Rp 2.000
          </span>
        </div>
      </div>
    </div>

    <div style="border-top:1px solid #1E293B;padding:20px 0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
      <span style="font-size:12.5px">© <?= date('Y') ?> cinitymarket.id — All rights reserved</span>
      <div class="d-flex gap-3" style="font-size:12.5px">
        <a href="/privacy.php" style="color:#94A3B8;text-decoration:none">Privasi</a>
        <a href="/terms.php" style="color:#94A3B8;text-decoration:none">Syarat</a>
        <a href="/help.php" style="color:#94A3B8;text-decoration:none">Bantuan</a>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Navbar scroll effect
const nav = document.getElementById('mainNav');
if (nav) {
  window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 10), { passive: true });
}
// Auto-dismiss toasts
const tw = document.getElementById('toastWrap');
if (tw) setTimeout(() => tw.style.opacity = '0', 3800);

// Confirm delete
document.querySelectorAll('[data-confirm]').forEach(el =>
  el.addEventListener('click', e => { if (!confirm(el.dataset.confirm)) e.preventDefault(); })
);
// Image preview
document.querySelectorAll('input[type="file"][accept*="image"]').forEach(inp => {
  inp.addEventListener('change', () => {
    const pid = inp.dataset.preview;
    if (pid && inp.files[0]) {
      const r = new FileReader();
      r.onload = e => { const img = document.getElementById(pid); if (img) img.src = e.target.result; };
      r.readAsDataURL(inp.files[0]);
    }
  });
});
</script>
</body>
</html>
