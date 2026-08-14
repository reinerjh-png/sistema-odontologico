<?php
/**
 * Shared Footer
 * Requires: $tenant
 */
?>
<footer class="content-footer">
    <p>&copy; <?= date('Y') ?> <strong><?= htmlspecialchars($tenant['clinic_name']) ?></strong> &mdash; Sistema de Gestión Dental</p>
</footer>

<script>
function toggleSidebar() {
    if (window.innerWidth <= 768) {
        document.getElementById('sidebar').classList.toggle('sidebar-open');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    } else {
        const collapsed = document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar-collapsed', collapsed);
    }
}
</script>
