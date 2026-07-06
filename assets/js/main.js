// CinityMarket.id - Main JS
document.addEventListener('DOMContentLoaded', () => {
  // Auto-dismiss alerts after 4s
  document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
    setTimeout(() => {
      const bs = bootstrap.Alert.getOrCreateInstance(alert);
      bs && bs.close();
    }, 4000);
  });

  // Confirm delete buttons
  document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', e => {
      if (!confirm(btn.dataset.confirm)) e.preventDefault();
    });
  });

  // Image preview on upload
  document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
    input.addEventListener('change', () => {
      const preview = document.getElementById(input.dataset.preview);
      if (preview && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => preview.src = e.target.result;
        reader.readAsDataURL(input.files[0]);
      }
    });
  });
});
