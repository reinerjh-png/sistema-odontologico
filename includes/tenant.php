<?php
/**
 * Tenant Configuration Loader
 * Loads branding config from tenant_config table, caches in session.
 * Must be included AFTER config.php (needs $pdo) and AFTER session_start().
 */

function cargarTenant($pdo) {
    // Return cached tenant if available
    if (isset($_SESSION['tenant']) && !isset($_GET['refresh_tenant'])) {
        return $_SESSION['tenant'];
    }

    // Defaults
    $defaults = [
        'clinic_name'      => defined('SITE_NAME') ? SITE_NAME : 'Clínica Dental',
        'logo_url'         => 'assets/logo.png',
        'color_primary'    => '#1B2B4B',
        'color_secondary'  => '#2A4A7F',
        'color_accent'     => '#4A90D9',
        'color_sidebar'    => '#1B2B4B',
    ];

    try {
        $stmt = $pdo->query("SELECT * FROM tenant_config LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            $defaults['clinic_name']     = $row['clinic_name'] ?: $defaults['clinic_name'];
            $defaults['logo_url']        = $row['logo_url'] ?: $defaults['logo_url'];
            $defaults['color_primary']   = $row['color_primary'] ?: $defaults['color_primary'];
            $defaults['color_secondary'] = isset($row['color_secondary']) && $row['color_secondary'] ? $row['color_secondary'] : $defaults['color_secondary'];
            $defaults['color_accent']    = $row['color_accent'] ?: $defaults['color_accent'];
            $defaults['color_sidebar']   = $row['color_sidebar'] ?: $defaults['color_sidebar'];
        }
    } catch (Exception $e) {
        // Table might not exist yet; use defaults silently
    }

    $_SESSION['tenant'] = $defaults;
    return $defaults;
}

/**
 * Outputs CSS custom properties for tenant branding into a <style> tag.
 */
function renderTenantCssVars($tenant) {
    $p  = htmlspecialchars($tenant['color_primary']);
    $sc = htmlspecialchars($tenant['color_secondary']);
    $a  = htmlspecialchars($tenant['color_accent']);
    $s  = htmlspecialchars($tenant['color_sidebar']);
    echo "<style>:root{--color-primary:{$p};--color-secondary:{$sc};--color-accent:{$a};--color-sidebar:{$s};}</style>\n";
}

/**
 * Resolve the base path prefix for assets depending on script depth.
 * Root-level files return '', admin/ files return '../', etc.
 */
function getBasePath() {
    $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
    $rootDir = str_replace('\\', '/', dirname(__DIR__));
    if (strpos($scriptPath, $rootDir) === 0) {
        $relPath = substr(dirname($scriptPath), strlen($rootDir) + 1);
        $depth = $relPath ? substr_count($relPath, '/') + 1 : 0;
        return str_repeat('../', $depth);
    }
    return '';
}
