// CinityMarket.id - App JavaScript

document.addEventListener('DOMContentLoaded', function () {

  // ---- PAYMENT METHOD SELECTOR ----
  document.querySelectorAll('.cm-pay-option').forEach(function (label) {
    label.parentElement.querySelector('input[type="radio"]')?.addEventListener('change', function () {
      document.querySelectorAll('.cm-pay-option').forEach(function (l) {
        l.style.borderColor = '';
        l.style.background  = '';
      });
      if (this.checked) {
        label.style.borderColor = 'var(--primary)';
        label.style.background  = 'var(--primary-light)';
      }
    });
  });

  // ---- AUTO-DISMISS ALERTS ----
  setTimeout(function () {
    document.querySelectorAll('.alert.alert-success').forEach(function (alert) {
      alert.style.transition = 'opacity .5s';
      alert.style.opacity    = '0';
      setTimeout(function () { alert.remove(); }, 500);
    });
  }, 4000);

  // ---- RIPPLE ON BUTTONS ----
  document.querySelectorAll('.btn-primary, .btn-success').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      const r   = btn.getBoundingClientRect();
      const rpl = document.createElement('span');
      rpl.style.cssText = [
        'position:absolute', 'width:10px', 'height:10px',
        'border-radius:50%', 'background:rgba(255,255,255,.4)',
        'pointer-events:none', 'transform:scale(0)',
        'animation:ripple .5s linear',
        'left:' + (e.clientX - r.left - 5) + 'px',
        'top:'  + (e.clientY - r.top  - 5) + 'px'
      ].join(';');
      btn.style.position = 'relative';
      btn.style.overflow = 'hidden';
      btn.appendChild(rpl);
      setTimeout(function () { rpl.remove(); }, 600);
    });
  });

  // ---- FORMAT PRICE INPUTS ----
  document.querySelectorAll('input[data-format="currency"]').forEach(function (inp) {
    inp.addEventListener('input', function () {
      const raw    = this.value.replace(/\D/g, '');
      this.value   = raw ? parseInt(raw, 10).toLocaleString('id-ID') : '';
    });
  });

  // ---- FEATHER ICONS (re-run after dynamic content) ----
  if (typeof feather !== 'undefined') feather.replace();
});

// Ripple animation CSS injection
const style = document.createElement('style');
style.textContent = '@keyframes ripple { to { transform:scale(20); opacity:0; } }';
document.head.appendChild(style);

// ---- ADD TO CART (fetch) ----
function addToCart(productId, qty) {
  const form = new FormData();
  form.append('action', 'add');
  form.append('product_id', productId);
  form.append('quantity', qty || 1);
  form.append('csrf_token', document.querySelector('meta[name="csrf"]')?.content || '');

  fetch('/pages/buyer/cart.php', { method: 'POST', body: form })
    .then(function (r) {
      if (r.ok) {
        showToast('Produk ditambahkan ke keranjang!', 'success');
        // Update cart badge
        const badge = document.querySelector('.cm-icon-btn .cm-badge');
        if (badge) badge.textContent = parseInt(badge.textContent || '0') + 1;
      }
    })
    .catch(function () { showToast('Gagal menambahkan ke keranjang.', 'error'); });
}

// ---- TOAST ----
function showToast(message, type) {
  const colors = { success: '#22C55E', error: '#EF4444', info: '#0EA5E9' };
  const toast  = document.createElement('div');
  toast.textContent = message;
  toast.style.cssText = [
    'position:fixed', 'bottom:24px', 'right:24px', 'z-index:9999',
    'background:' + (colors[type] || '#333'),
    'color:#fff', 'padding:12px 20px', 'border-radius:12px',
    'font-weight:600', 'font-size:.875rem', 'box-shadow:0 4px 20px rgba(0,0,0,.2)',
    'animation:slideUp .3s ease'
  ].join(';');
  document.body.appendChild(toast);
  setTimeout(function () {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity .3s';
    setTimeout(function () { toast.remove(); }, 300);
  }, 3000);
}

const toastStyle = document.createElement('style');
toastStyle.textContent = '@keyframes slideUp { from { transform:translateY(20px);opacity:0; } to { transform:translateY(0);opacity:1; } }';
document.head.appendChild(toastStyle);
