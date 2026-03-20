<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
verificarSesion();
requiereAdmin();
require_once '../includes/tenant.php';

$tenant = cargarTenant($pdo);
$basePath = getBasePath();

// Obtener estadísticas
$totalPacientes = $pdo->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();
$totalDoctores = $pdo->query("SELECT COUNT(*) FROM doctores")->fetchColumn();
$totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

// Actividad reciente
$actividadHoy = $pdo->query("SELECT COUNT(*) FROM actividad_log WHERE DATE(created_at) = CURRENT_DATE")->fetchColumn();

$currentPage = 'admin';
$pageTitle = 'Panel de Administración';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - <?= htmlspecialchars($tenant['clinic_name']) ?></title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/fontawesome/css/all.min.css">
    <?php renderTenantCssVars($tenant); ?>
</head>
<body>
    <div class="app-wrapper">
        <?php include '../includes/layout_sidebar.php'; ?>

        <div class="app-content">
            <?php include '../includes/layout_header.php'; ?>

            <main class="main-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-blue"><i class="fas fa-user-injured"></i></div>
                        <div class="stat-number"><?php echo number_format($totalPacientes); ?></div>
                        <div class="stat-label">Historias Clínicas</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-green"><i class="fas fa-user-md"></i></div>
                        <div class="stat-number"><?php echo number_format($totalDoctores); ?></div>
                        <div class="stat-label">Doctores</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-orange"><i class="fas fa-users-cog"></i></div>
                        <div class="stat-number"><?php echo number_format($totalUsuarios); ?></div>
                        <div class="stat-label">Usuarios Sistema</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-teal"><i class="fas fa-chart-line"></i></div>
                        <div class="stat-number"><?php echo number_format($actividadHoy); ?></div>
                        <div class="stat-label">Acciones Hoy</div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="card" style="margin-bottom: 0;">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-cogs"></i> Accesos Rápidos</h2>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <a href="doctores.php" class="btn btn-secondary" style="justify-content: flex-start;"><i class="fas fa-user-md"></i> Gestionar Doctores</a>
                            <a href="usuarios.php" class="btn btn-secondary" style="justify-content: flex-start;"><i class="fas fa-users-cog"></i> Gestionar Usuarios</a>
                            <a href="actividad.php" class="btn btn-secondary" style="justify-content: flex-start;"><i class="fas fa-history"></i> Ver Log de Actividad</a>
                            <a href="branding.php" class="btn btn-secondary" style="justify-content: flex-start;"><i class="fas fa-palette"></i> Panel de Marca (Branding)</a>
                        </div>
                    </div>

                    <div class="card" style="margin-bottom: 0;">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-info-circle"></i> Información del Sistema</h2>
                        </div>
                        <div style="color: var(--color-text-secondary); font-size: 0.9rem;">
                            <p style="margin-bottom: 8px;"><strong>Usuario Actual:</strong> <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></p>
                            <p style="margin-bottom: 8px;"><strong>Rol:</strong> Administrador</p>
                            <p style="margin-bottom: 8px;"><strong>Fecha y Hora:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                            <p style="margin-bottom: 8px;"><strong>Versión PHP:</strong> <?php echo phpversion(); ?></p>
                            <div style="margin-top: 24px; text-align: center;">
                                <a href="export_db.php" class="btn btn-primary"><i class="fas fa-download"></i> Respaldar Base de Datos</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <?php include '../includes/layout_footer.php'; ?>
        </div>
    </div>
</body>
</html>
