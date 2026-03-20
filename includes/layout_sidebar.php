<?php
/**
 * Shared Sidebar Navigation
 * Requires: $tenant (from tenant.php), $basePath (from getBasePath())
 * Optional: $currentPage (string identifier for active state)
 */
$currentPage = $currentPage ?? '';
$isAdmin = isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="<?= $basePath ?>dashboard.php" class="sidebar-logo-link">
            <img src="<?= $basePath . htmlspecialchars($tenant['logo_url']) ?>" alt="Logo" class="sidebar-logo-img" onerror="this.style.display='none'">
            <span class="sidebar-clinic-name"><?= htmlspecialchars($tenant['clinic_name']) ?></span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= $basePath ?>dashboard.php" class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <i class="fas fa-th-large"></i><span>Dashboard</span>
        </a>
        <a href="<?= $basePath ?>crear.php" class="sidebar-link <?= $currentPage === 'crear' ? 'active' : '' ?>">
            <i class="fas fa-user-plus"></i><span>Nuevo Paciente</span>
        </a>
        <a href="<?= $basePath ?>dashboard.php?ver=citas" class="sidebar-link <?= $currentPage === 'citas' ? 'active' : '' ?>">
            <i class="fas fa-calendar-check"></i><span>Citas</span>
        </a>
        <a href="<?= $basePath ?>callcenter/index.php" class="sidebar-link <?= $currentPage === 'callcenter' ? 'active' : '' ?>">
            <i class="fas fa-phone-alt"></i><span>Call Center</span>
        </a>

        <?php if ($isAdmin): ?>
        <div class="sidebar-divider"></div>
        <div class="sidebar-section-label">Administración</div>
        <a href="<?= $basePath ?>admin/index.php" class="sidebar-link <?= $currentPage === 'admin' ? 'active' : '' ?>">
            <i class="fas fa-cogs"></i><span>Panel Admin</span>
        </a>
        <a href="<?= $basePath ?>admin/doctores.php" class="sidebar-link <?= $currentPage === 'doctores' ? 'active' : '' ?>">
            <i class="fas fa-user-md"></i><span>Doctores</span>
        </a>
        <a href="<?= $basePath ?>admin/usuarios.php" class="sidebar-link <?= $currentPage === 'usuarios' ? 'active' : '' ?>">
            <i class="fas fa-users-cog"></i><span>Usuarios</span>
        </a>
        <a href="<?= $basePath ?>admin/actividad.php" class="sidebar-link <?= $currentPage === 'actividad' ? 'active' : '' ?>">
            <i class="fas fa-history"></i><span>Actividad</span>
        </a>
        <a href="<?= $basePath ?>admin/branding.php" class="sidebar-link <?= $currentPage === 'branding' ? 'active' : '' ?>">
            <i class="fas fa-palette"></i><span>Marca / Branding</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= $basePath ?>logout.php" class="sidebar-link sidebar-logout">
            <i class="fas fa-sign-out-alt"></i><span>Cerrar Sesión</span>
        </a>
    </div>
</aside>
<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
