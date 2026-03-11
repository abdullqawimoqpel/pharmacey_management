<?php
// includes/footer.php
?>
            </main>
        </div>
    </div>

    <footer class="footer">
        <div class="container-fluid">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> <?php echo __('site_name'); ?>. 
                <?php echo __('all_rights_reserved'); ?>.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebarMenu')?.classList.toggle('show');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebarMenu');
            const toggle = document.getElementById('sidebarToggle');
            if (sidebar && toggle && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert:not(.alert-permanent)').forEach(function(alert) {
                if (bootstrap.Alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            });
        }, 5000);

        // Confirm before delete actions
        document.querySelectorAll('.btn-delete').forEach(function(button) {
            button.addEventListener('click', function(e) {
                if (!confirm('<?php echo getCurrentLang() === "ar" ? "هل أنت متأكد من حذف هذا العنصر؟" : "Are you sure you want to delete this item?"; ?>')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>