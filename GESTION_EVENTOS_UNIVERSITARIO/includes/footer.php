<?php // includes/footer.php ?>
</div><!-- /.main-content -->
</div><!-- /.flex wrapper inner -->
</div><!-- /.wrapper -->
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
<script>
// Toggle Password Visibility
function togglePasswordVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    var icon = btn ? btn.querySelector('i') : null;
    if (icon) {
        if (isPassword) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}
window.togglePasswordVisibility = togglePasswordVisibility;

// Generic confirm delete
document.querySelectorAll('.confirm-delete').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        if (!confirm('¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.')) {
            e.preventDefault();
        }
    });
});
// Auto-dismiss alerts after 4s
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(function(a) {
        a.style.transition = 'opacity .5s';
        a.style.opacity = '0';
        setTimeout(function() { a.remove(); }, 500);
    });
}, 4000);
</script>
</body>
</html>
