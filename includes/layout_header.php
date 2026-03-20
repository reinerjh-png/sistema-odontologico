<?php
/**
 * Shared Top Header Bar
 * Requires: $tenant, $basePath, $pageTitle (string)
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$userName = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario');
$userRole = $_SESSION['usuario_rol'] ?? 'recepcionista';
?>
<header class="top-header">
    <div class="top-header-left">
        <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()" title="Menú">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="top-header-title"><?= htmlspecialchars($pageTitle) ?></h1>
    </div>
    <div class="top-header-right">
        <div class="header-user-info">
            <div class="header-user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="header-user-details">
                <span class="header-user-name"><?= $userName ?></span>
                <span class="header-user-role badge-role badge-role-<?= $userRole === 'admin' ? 'admin' : 'recep' ?>"><?= $userRole === 'admin' ? 'Admin' : 'Recepción' ?></span>
            </div>
        </div>
    </div>
</header>
