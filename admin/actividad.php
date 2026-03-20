<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
verificarSesion();
requiereAdmin();
require_once '../includes/functions.php';
require_once '../includes/tenant.php';

$tenant = cargarTenant($pdo);
$basePath = getBasePath();

$filtroUsuario = isset($_GET['usuario']) ? intval($_GET['usuario']) : '';
$filtroFechaDesde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$filtroFechaHasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

$where = [];
$params = [];
if ($filtroUsuario) { $where[] = "a.usuario_id = ?"; $params[] = $filtroUsuario; }
if ($filtroFechaDesde) { $where[] = "DATE(a.created_at) >= ?"; $params[] = $filtroFechaDesde; }
if ($filtroFechaHasta) { $where[] = "DATE(a.created_at) <= ?"; $params[] = $filtroFechaHasta; }
$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$porPagina = 50;
$paginaActual = max(1, intval($_GET['pagina'] ?? 1));
$offset = ($paginaActual - 1) * $porPagina;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM actividad_log a $whereSQL");
$countStmt->execute($params);
$totalRegistros = $countStmt->fetchColumn();
$totalPaginas = max(1, ceil($totalRegistros / $porPagina));

$stmt = $pdo->prepare("SELECT a.*, u.usuario, u.nombre_completo, u.rol FROM actividad_log a JOIN usuarios u ON u.id = a.usuario_id $whereSQL ORDER BY a.created_at DESC LIMIT $porPagina OFFSET $offset");
$stmt->execute($params);
$actividades = $stmt->fetchAll();

$todosUsuarios = $pdo->query("SELECT id, usuario, nombre_completo FROM usuarios ORDER BY nombre_completo")->fetchAll();

$currentPage = 'admin';
$pageTitle = 'Registro de Actividad';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Actividad - <?= htmlspecialchars($tenant['clinic_name']) ?></title>
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
                <div class="card form-container">
                    <div class="card-header">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <h2 class="card-title"><i class="fas fa-filter"></i> Filtros</h2>
                        </div>
                        <?php if ($filtroUsuario || $filtroFechaDesde || $filtroFechaHasta): ?>
                            <a href="actividad.php" class="btn-nav btn-nav-secondary" style="padding: 6px 12px; font-size: 0.85rem;"><i class="fas fa-times"></i> Limpiar filtros</a>
                        <?php endif; ?>
                    </div>
                    <form method="GET">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Usuario</label>
                                <select name="usuario" class="form-control">
                                    <option value="">Todos los usuarios</option>
                                    <?php foreach ($todosUsuarios as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" <?php echo $filtroUsuario == $u['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['nombre_completo']); ?> (<?php echo htmlspecialchars($u['usuario']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Desde</label>
                                <input type="date" name="fecha_desde" class="form-control" value="<?php echo htmlspecialchars($filtroFechaDesde); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Hasta</label>
                                <input type="date" name="fecha_hasta" class="form-control" value="<?php echo htmlspecialchars($filtroFechaHasta); ?>">
                            </div>
                            <div class="form-group full-width" style="text-align: right;">
                                <a href="index.php" class="btn btn-secondary" style="margin-right: 8px;"><i class="fas fa-arrow-left"></i> Volver</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar Actividad</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-history"></i> Historial de Acciones</h2>
                        <span class="text-gray" style="font-size: 0.85rem;"><?php echo number_format($totalRegistros); ?> registro(s) &mdash; página <?php echo $paginaActual; ?> de <?php echo $totalPaginas; ?></span>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr><th>Fecha y Hora</th><th>Usuario</th><th>Rol</th><th>Acción</th><th>Detalle</th><th>IP</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($actividades)): ?>
                                    <tr><td colspan="6" class="table-empty"><i class="fas fa-clipboard-list" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>No se encontraron registros de actividad</td></tr>
                                <?php else: ?>
                                    <?php foreach ($actividades as $act): ?>
                                        <tr>
                                            <td style="white-space: nowrap; font-size: 0.85rem;">
                                                <strong><?php echo date('d/m/Y', strtotime($act['created_at'])); ?></strong><br>
                                                <span class="text-gray"><?php echo date('H:i:s', strtotime($act['created_at'])); ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($act['nombre_completo']); ?></strong><br>
                                                <span class="text-gray" style="font-size: 0.8rem;"><?php echo htmlspecialchars($act['usuario']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($act['rol'] === 'admin'): ?>
                                                    <span class="badge" style="background: rgba(49,130,206,0.1); color: var(--color-primary);"><i class="fas fa-shield-alt"></i> Admin</span>
                                                <?php else: ?>
                                                    <span class="badge" style="background: rgba(56,161,105,0.1); color: var(--color-success);"><i class="fas fa-user"></i> Recep</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $iconMap = [
                                                    'Login' => 'fa-sign-in-alt', 'Logout' => 'fa-sign-out-alt',
                                                    'Crear Paciente' => 'fa-user-plus', 'Editar Paciente' => 'fa-user-edit',
                                                    'Archivar Paciente' => 'fa-archive', 'Restaurar Paciente' => 'fa-trash-restore',
                                                    'Agregar Doctor' => 'fa-user-md', 'Eliminar Doctor' => 'fa-user-times',
                                                    'Activar Doctor' => 'fa-check-circle', 'Desactivar Doctor' => 'fa-ban',
                                                    'Agregar Usuario' => 'fa-user-plus', 'Eliminar Usuario' => 'fa-user-minus',
                                                    'Activar Usuario' => 'fa-user-check', 'Desactivar Usuario' => 'fa-user-slash',
                                                    'Cambiar Contraseña' => 'fa-key', 'Actualizar Configuración' => 'fa-cogs',
                                                ];
                                                $icon = $iconMap[$act['accion']] ?? 'fa-circle';
                                                ?>
                                                <span style="color: var(--color-primary); margin-right: 4px;"><i class="fas <?php echo $icon; ?>"></i></span>
                                                <strong><?php echo htmlspecialchars($act['accion']); ?></strong>
                                            </td>
                                            <td style="max-width: 300px; font-size: 0.85rem; color: var(--color-text-secondary);"><?php echo htmlspecialchars($act['detalle']); ?></td>
                                            <td style="font-size: 0.8rem; color: var(--color-text-light);"><?php echo htmlspecialchars($act['ip_address']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPaginas > 1): ?>
                    <div style="display: flex; justify-content: center; align-items: center; gap: 8px; padding: 16px 0;">
                        <?php
                        $filterParams = [];
                        if ($filtroUsuario) $filterParams['usuario'] = $filtroUsuario;
                        if ($filtroFechaDesde) $filterParams['fecha_desde'] = $filtroFechaDesde;
                        if ($filtroFechaHasta) $filterParams['fecha_hasta'] = $filtroFechaHasta;
                        function urlPaginaActividad($pagina, $filterParams) {
                            $filterParams['pagina'] = $pagina;
                            return 'actividad.php?' . http_build_query($filterParams);
                        }
                        ?>
                        <?php if ($paginaActual > 1): ?>
                            <a href="<?php echo htmlspecialchars(urlPaginaActividad(1, $filterParams)); ?>" class="btn-nav btn-nav-secondary" style="padding:6px 12px;"><i class="fas fa-angle-double-left"></i></a>
                            <a href="<?php echo htmlspecialchars(urlPaginaActividad($paginaActual - 1, $filterParams)); ?>" class="btn-nav btn-nav-secondary" style="padding:6px 12px;"><i class="fas fa-angle-left"></i> Anterior</a>
                        <?php endif; ?>
                        <?php for ($p = max(1, $paginaActual - 2); $p <= min($totalPaginas, $paginaActual + 2); $p++): ?>
                            <a href="<?php echo htmlspecialchars(urlPaginaActividad($p, $filterParams)); ?>" class="btn-nav <?php echo $p === $paginaActual ? 'btn-nav-primary' : 'btn-nav-secondary'; ?>" style="padding:6px 12px; min-width:36px; text-align:center;"><?php echo $p; ?></a>
                        <?php endfor; ?>
                        <?php if ($paginaActual < $totalPaginas): ?>
                            <a href="<?php echo htmlspecialchars(urlPaginaActividad($paginaActual + 1, $filterParams)); ?>" class="btn-nav btn-nav-secondary" style="padding:6px 12px;">Siguiente <i class="fas fa-angle-right"></i></a>
                            <a href="<?php echo htmlspecialchars(urlPaginaActividad($totalPaginas, $filterParams)); ?>" class="btn-nav btn-nav-secondary" style="padding:6px 12px;"><i class="fas fa-angle-double-right"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </main>

            <?php include '../includes/layout_footer.php'; ?>
        </div>
    </div>
</body>
</html>
