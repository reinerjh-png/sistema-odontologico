<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
verificarSesion();
require_once '../includes/functions.php';
require_once '../includes/callcenter_functions.php';
require_once '../includes/tenant.php';

$tenant = cargarTenant($pdo);
$basePath = getBasePath();

// Obtener fecha del reporte (hoy por defecto)
$fechaReporte = isset($_GET['fecha']) ? sanitizar($_GET['fecha']) : date('Y-m-d');

//Obtener estadísticas del día seleccionado
$statsHoy = obtenerEstadisticasCallCenter($pdo, $fechaReporte);

// Obtener pacientes individuales del día seleccionado
$pacientesDelDia = obtenerPacientesDelDiaReporte($pdo, $fechaReporte);

$currentPage = 'callcenter';
$pageTitle = 'Reportes Call Center';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Call Center - <?= htmlspecialchars($tenant['clinic_name']) ?></title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/fontawesome/css/all.min.css">
    <?php renderTenantCssVars($tenant); ?>
    <style>
        .report-selector {
            margin-bottom: 30px;
            padding: 20px;
            background: var(--color-pure-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: var(--shadow-sm);
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: var(--color-pure-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease;
        }
        
        .stat-box:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-box-icon {
            font-size: 2rem;
            margin-bottom: 12px;
        }

        .stat-box-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--color-text);
        }

        .stat-box-label {
            color: var(--color-text-secondary);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .report-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--color-text);
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .report-title i {
            color: var(--color-primary);
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <?php include '../includes/layout_sidebar.php'; ?>

        <div class="app-content">
            <?php include '../includes/layout_header.php'; ?>

            <main class="main-content">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h1 class="page-title" style="margin: 0;">
                        <i class="fas fa-chart-line" style="color: var(--color-primary); margin-right: 10px;"></i>
                        Reportes Diarios
                    </h1>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Llamadas
                    </a>
                </div>

                <!-- Selector de Fecha -->
                <form method="GET" class="report-selector">
                    <label for="fecha" class="form-label" style="margin: 0; font-weight: 600;">
                        <i class="fas fa-calendar-alt" style="color: var(--color-primary);"></i> Fecha a consultar:
                    </label>
                    <input type="date" 
                           id="fecha" 
                           name="fecha" 
                           class="form-control" 
                           value="<?php echo $fechaReporte; ?>"
                           max="<?php echo date('Y-m-d'); ?>"
                           style="max-width: 200px; margin: 0;">
                    <button type="submit" class="btn btn-primary" style="height: 48px;">
                        <i class="fas fa-search"></i> Extraer Datos
                    </button>
                </form>

                <h2 class="report-title">
                    <i class="fas fa-chart-pie"></i>
                    Estadísticas del <?php echo date('d/m/Y', strtotime($fechaReporte)); ?>
                </h2>

                <div class="stats-summary">
                    <div class="stat-box">
                        <div class="stat-box-icon" style="color: #6366F1;">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="stat-box-value">
                            <?php echo $statsHoy['total']; ?>
                        </div>
                        <div class="stat-box-label">Total Asignadas</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-box-icon" style="color: #10B981;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-box-value">
                            <?php echo $statsHoy['completadas']; ?>
                        </div>
                        <div class="stat-box-label">Completadas</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-box-icon" style="color: #EF4444;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-box-value">
                            <?php echo $statsHoy['rechazadas']; ?>
                        </div>
                        <div class="stat-box-label">Rechazadas</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-box-icon" style="color: #F59E0B;">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="stat-box-value">
                            <?php echo $statsHoy['pendientes'] + $statsHoy['pospuestas']; ?>
                        </div>
                        <div class="stat-box-label">Pendientes</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-box-icon" style="color: var(--color-primary);">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-box-value">
                            <?php echo $statsHoy['total'] > 0 ? round((($statsHoy['completadas'] + $statsHoy['rechazadas']) / $statsHoy['total']) * 100) : 0; ?>%
                        </div>
                        <div class="stat-box-label">Eficiencia</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-list-ul"></i> Detalle de Pacientes (<?php echo count($pacientesDelDia); ?>)</h2>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>N° Historia</th>
                                    <th>Nombre</th>
                                    <th>Celular</th>
                                    <th>Género</th>
                                    <th>Edad</th>
                                    <th>Dirección</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pacientesDelDia)): ?>
                                    <?php foreach ($pacientesDelDia as $paciente): ?>
                                        <?php
                                            $estado = $paciente['llamada_estado'];
                                            switch ($estado) {
                                                case 'completada':
                                                    $badgeClass = 'badge-entregado'; 
                                                    $estadoTexto = 'Completada';
                                                    $estadoIcon = 'fas fa-check-circle';
                                                    break;
                                                case 'pospuesta':
                                                    $badgeClass = 'badge-laboratorio'; 
                                                    $estadoTexto = 'Pospuesta';
                                                    $estadoIcon = 'fas fa-clock';
                                                    break;
                                                case 'rechazada':
                                                    $badgeClass = 'badge-pendiente'; 
                                                    $estadoTexto = 'Rechazada';
                                                    $estadoIcon = 'fas fa-times-circle';
                                                    break;
                                                default:
                                                    $badgeClass = 'badge-tratamiento'; 
                                                    $estadoTexto = 'Pendiente';
                                                    $estadoIcon = 'fas fa-hourglass-half';
                                                    break;
                                            }
                                        ?>
                                        <tr>
                                            <td><strong class="text-gray"><?php echo htmlspecialchars($paciente['numero_historia']); ?></strong></td>
                                            <td><strong><?php echo htmlspecialchars($paciente['nombres']); ?></strong></td>
                                            <td>
                                                <span style="font-weight: 600; color: var(--color-primary);">
                                                    <i class="fas fa-phone-alt" style="font-size: 0.8rem; margin-right: 4px;"></i>
                                                    <?php echo htmlspecialchars($paciente['celular']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($paciente['genero'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($paciente['edad'] ?? '-'); ?></td>
                                            <td><span class="text-gray" style="font-size: 0.9rem;"><?php echo htmlspecialchars($paciente['direccion'] ?? '-'); ?></span></td>
                                            <td>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <i class="<?php echo $estadoIcon; ?>"></i>
                                                    <?php echo $estadoTexto; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="table-empty">
                                            <i class="fas fa-inbox" style="font-size: 2.5rem; color: var(--color-text-light); margin-bottom: 10px; display: block;"></i>
                                            No hay pacientes asignados para esta fecha
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>

            <?php include '../includes/layout_footer.php'; ?>
        </div>
    </div>
</body>
</html>
