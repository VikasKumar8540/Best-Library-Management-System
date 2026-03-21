  </div><!-- end page-content -->
</div><!-- end main-content -->
<script>
// Auto-hide alerts
document.querySelectorAll('.alert').forEach(function(el) {
  setTimeout(function() {
    el.style.transition = 'opacity 0.5s';
    el.style.opacity = '0';
    setTimeout(function(){ el.remove(); }, 500);
  }, 4000);
});

// Confirm delete
document.querySelectorAll('[data-confirm]').forEach(function(el) {
  el.addEventListener('click', function(e) {
    if (!confirm(this.dataset.confirm || 'Are you sure?')) {
      e.preventDefault();
    }
  });
});
</script>
</body>
</html>
