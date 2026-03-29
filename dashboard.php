<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
verificarSesion();
require_once 'includes/functions.php';
require_once 'includes/callcenter_functions.php';
require_once 'includes/comunicado.php';
require_once 'includes/tenant.php';

$tenant = cargarTenant($pdo);
$basePath = getBasePath();

// comunicado.php ya maneja la lógica de sesión internamente
$mostrarComunicado = $comunicado_activo;


// Parámetros de búsqueda y filtro
$busqueda     = isset($_GET['buscar'])       ? sanitizar($_GET['buscar'])       : '';
$tipoBusqueda = isset($_GET['tipo_busqueda'])? sanitizar($_GET['tipo_busqueda']): '';
$verCitas      = isset($_GET['ver']) && $_GET['ver'] === 'citas';
$verArchivados = isset($_GET['ver']) && $_GET['ver'] === 'archivados';
$estadoActual  = $verArchivados ? 0 : 1;

// Paginación
define('REGISTROS_POR_PAGINA', 50);
$paginaActual  = max(1, (int) ($_GET['pagina'] ?? 1));
$offset        = ($paginaActual - 1) * REGISTROS_POR_PAGINA;
$totalRegistros = contarPacientes($pdo, $busqueda, $tipoBusqueda, $estadoActual, $verCitas);
$totalPaginas   = max(1, (int) ceil($totalRegistros / REGISTROS_POR_PAGINA));
if ($paginaActual > $totalPaginas) $paginaActual = $totalPaginas;

// Una sola consulta: trae pacientes + tratamientos agrupados
$pacientes = obtenerPacientes($pdo, $busqueda, $tipoBusqueda, $estadoActual, $verCitas, REGISTROS_POR_PAGINA, $offset);

// Totales para tarjetas de estadísticas (una sola consulta)
$statsRow = $pdo->query(
    "SELECT
        SUM(estado = 1)                                                       AS activos,
        SUM(estado = 0)                                                       AS archivados,
        SUM(estado = 1 AND fecha_ultima_cita >= CURRENT_DATE
            AND fecha_ultima_cita IS NOT NULL)                                AS citas
    FROM pacientes"
)->fetch();
$totalPacientesActivos = (int) $statsRow['activos'];
$totalArchivados       = (int) $statsRow['archivados'];
$totalCitasProx        = (int) $statsRow['citas'];

// Call center
$totalCallCenter = obtenerContadorCallCenter($pdo);

// Tratamientos para el selector de búsqueda
$todosTratamientos = obtenerTratamientos($pdo);

// Helper: armar URL manteniendo parámetros actuales, cambiando solo la página
function urlPagina($pagina, $busqueda, $tipoBusqueda, $verCitas, $verArchivados) {
    $params = ['pagina' => $pagina];
    if ($busqueda)     $params['buscar']       = $busqueda;
    if ($tipoBusqueda) $params['tipo_busqueda']= $tipoBusqueda;
    if ($verCitas)     $params['ver']          = 'citas';
    if ($verArchivados)$params['ver']          = 'archivados';
    return 'dashboard.php?' . http_build_query($params);
}

$currentPage = $verCitas ? 'citas' : ($verArchivados ? 'dashboard' : 'dashboard');
$pageTitle = 'Historias Clínicas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($tenant['clinic_name']) ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/fontawesome/css/all.min.css">
    <?php renderTenantCssVars($tenant); ?>
</head>
<body>
    <div class="app-wrapper">
        <?php include 'includes/layout_sidebar.php'; ?>

        <div class="app-content">
            <?php include 'includes/layout_header.php'; ?>

            <main class="main-content">
                <!-- Alertas -->
                <?php echo mostrarAlerta(); ?>

                <?php if ($mostrarComunicado): ?>
                <div id="alert-comunicado" class="gold-alert-container">
                    <div class="gold-alert-content">
                        <div class="gold-alert-header">
                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($comunicado_titulo); ?>
                        </div>
                        <div class="gold-alert-body">
                            <?php echo htmlspecialchars($comunicado_mensaje); ?>
                        </div>
                        <div class="gold-alert-footer">
                            <button onclick="cerrarAlertaGold()" class="btn-gold-alert">OK</button>
                        </div>
                    </div>
                </div>
                <script>
                    function cerrarAlertaGold() {
                        const alert = document.getElementById('alert-comunicado');
                        alert.classList.add('fade-out-alert');
                        setTimeout(() => { alert.remove(); }, 400);
                    }
                </script>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <a href="dashboard.php" class="stat-card <?php echo !$verCitas && !$verArchivados ? 'stat-card-active' : ''; ?>">
                        <div class="stat-icon stat-icon-blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?php echo $totalPacientesActivos; ?></div>
                        <div class="stat-label">PACIENTES</div>
                    </a>
                    <a href="dashboard.php?ver=citas" class="stat-card <?php echo $verCitas ? 'stat-card-active' : ''; ?>">
                        <div class="stat-icon stat-icon-green">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-number"><?php echo $totalCitasProx; ?></div>
                        <div class="stat-label">CITAS</div>
                    </a>
                    <a href="callcenter/index.php" class="stat-card">
                        <div class="stat-icon stat-icon-teal phone-wave-icon">
                            <i class="fas fa-phone"></i>
                            <?php if ($totalCallCenter > 0): ?>
                                <span class="wave-ring wave-ring-1"></span>
                                <span class="wave-ring wave-ring-2"></span>
                                <span class="wave-ring wave-ring-3"></span>
                            <?php endif; ?>
                        </div>
                        <div class="stat-number"><?php echo $totalCallCenter; ?></div>
                        <div class="stat-label">CALL CENTER</div>
                    </a>
                    <a href="dashboard.php?ver=archivados" class="stat-card <?php echo $verArchivados ? 'stat-card-active' : ''; ?>">
                        <div class="stat-icon stat-icon-red">
                            <i class="fas fa-archive"></i>
                        </div>
                        <div class="stat-number"><?php echo $totalArchivados; ?></div>
                        <div class="stat-label">ARCHIVADOS</div>
                    </a>
                </div>

                <!-- Barra de búsqueda -->
                <form action="" method="GET" class="search-container">
                    <div class="search-select-wrapper">
                        <select name="tipo_busqueda" class="search-select">
                            <option value="" <?php echo $tipoBusqueda === '' ? 'selected' : ''; ?>>Todos los campos</option>
                            <option value="numero_historia" <?php echo $tipoBusqueda === 'numero_historia' ? 'selected' : ''; ?>>N° Historia</option>
                            <option value="dni" <?php echo $tipoBusqueda === 'dni' ? 'selected' : ''; ?>>DNI</option>
                            <option value="nombre" <?php echo $tipoBusqueda === 'nombre' ? 'selected' : ''; ?>>Nombre</option>
                            <option value="tratamiento" <?php echo $tipoBusqueda === 'tratamiento' ? 'selected' : ''; ?>>Tratamiento</option>
                        </select>
                    </div>
                    <div class="search-input-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="buscar" id="search_input" class="search-input"
                               placeholder="Ingrese su búsqueda..."
                               autocomplete="off"
                               value="<?php echo $tipoBusqueda !== 'tratamiento' ? htmlspecialchars($busqueda) : ''; ?>"
                               <?php echo $tipoBusqueda === 'tratamiento' ? 'style="display:none;" disabled' : ''; ?>>
                        <select name="buscar" id="search_select_tratamiento" class="search-input"
                                autocomplete="off"
                                <?php echo $tipoBusqueda !== 'tratamiento' ? 'style="display:none;" disabled' : ''; ?>>
                            <option value="">Seleccione un tratamiento...</option>
                            <?php foreach ($todosTratamientos as $trat): ?>
                                <option value="<?php echo htmlspecialchars($trat['nombre']); ?>"
                                        <?php echo ($tipoBusqueda === 'tratamiento' && $busqueda === $trat['nombre']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($trat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-buscar">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <?php if ($busqueda): ?>
                        <a href="dashboard.php" class="btn-buscar" style="background: var(--color-text-secondary);">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </form>

                <script>
                    (function() {
                        const tipoBusquedaSelect = document.querySelector('select[name="tipo_busqueda"]');
                        const searchInput = document.getElementById('search_input');
                        const searchSelectTratamiento = document.getElementById('search_select_tratamiento');
                        function toggleSearchInputs() {
                            if (!tipoBusquedaSelect || !searchInput || !searchSelectTratamiento) return;
                            if (tipoBusquedaSelect.value === 'tratamiento') {
                                searchInput.style.display = 'none'; searchInput.disabled = true;
                                searchSelectTratamiento.style.display = 'block'; searchSelectTratamiento.disabled = false;
                            } else {
                                searchInput.style.display = 'block'; searchInput.disabled = false;
                                searchSelectTratamiento.style.display = 'none'; searchSelectTratamiento.disabled = true;
                            }
                        }
                        if (tipoBusquedaSelect) {
                            tipoBusquedaSelect.addEventListener('change', toggleSearchInputs);
                            toggleSearchInputs();
                        }
                    })();
                </script>

                <!-- Tabla de pacientes -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas <?php echo $verCitas ? 'fa-calendar-check' : ($verArchivados ? 'fa-archive' : 'fa-clipboard-list'); ?>"></i>
                            <?php
                                if ($verCitas) echo "Citas Próximas";
                                elseif ($verArchivados) echo "Historias Archivadas";
                                else echo "Listado de Historias Clínicas";
                            ?>
                        </h2>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span class="text-gray" style="font-size: 0.82rem;">
                                <?php echo number_format($totalRegistros); ?> registros &mdash;
                                página <?php echo $paginaActual; ?> de <?php echo $totalPaginas; ?>
                            </span>
                            <?php if ($verArchivados || $verCitas): ?>
                                <a href="dashboard.php" class="btn-nav btn-nav-secondary" style="padding: 6px 10px; font-size: 0.8rem;">
                                    <i class="fas fa-arrow-left"></i> Volver a Activos
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>N° Historia</th>
                                    <th>DNI</th>
                                    <th>Paciente</th>
                                    <th>Celular</th>
                                    <th>Doctor</th>
                                    <th>Fecha Cita</th>
                                    <?php if ($verCitas): ?><th>Hora Cita</th><?php endif; ?>
                                    <th>Tratamientos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pacientes)): ?>
                                    <tr>
                                        <td colspan="<?php echo $verCitas ? 10 : 9; ?>" class="table-empty">
                                            <i class="fas fa-folder-open" style="font-size: 1.8rem; margin-bottom: 8px; display: block; color: var(--color-text-light);"></i>
                                            No se encontraron historias clínicas
                                            <?php if ($busqueda): ?>
                                                para "<?php echo htmlspecialchars($busqueda); ?>"
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pacientes as $paciente): ?>
                                        <?php $tratamientosPaciente = $paciente['tratamientos']; ?>
                                        <tr>
                                            <td><strong class="text-accent"><?php echo htmlspecialchars($paciente['numero_historia']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($paciente['dni']); ?></td>
                                            <td><?php echo htmlspecialchars($paciente['nombres']); ?></td>
                                            <td><?php echo htmlspecialchars($paciente['celular']); ?></td>
                                            <td>
                                                <?php if ($paciente['doctor_nombre']): ?>
                                                    <span class="badge badge-doctor"><i class="fas fa-user-md"></i> <?php echo htmlspecialchars($paciente['doctor_nombre']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-gray">Sin asignar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($paciente['fecha_ultima_cita']): ?>
                                                    <?php echo date('d/m/Y', strtotime($paciente['fecha_ultima_cita'])); ?>
                                                <?php else: ?>
                                                    <span class="text-gray">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($verCitas): ?>
                                            <td>
                                                <?php if (!empty($paciente['hora_cita'])): ?>
                                                    <?php echo date('g:i A', strtotime($paciente['hora_cita'])); ?>
                                                <?php else: ?>
                                                    <span class="text-gray">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php endif; ?>
                                            <td>
                                                <?php if (!empty($tratamientosPaciente)): ?>
                                                    <?php foreach (array_slice($tratamientosPaciente, 0, 2) as $trat): ?>
                                                        <span class="badge badge-tratamiento"><?php echo htmlspecialchars($trat); ?></span>
                                                    <?php endforeach; ?>
                                                    <?php if (count($tratamientosPaciente) > 2): ?>
                                                        <span class="badge badge-tratamiento">+<?php echo count($tratamientosPaciente) - 2; ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-gray">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="acciones">
                                                    <a href="ver.php?id=<?php echo $paciente['id']; ?>" class="btn-accion btn-ver" title="Ver Detalles"><i class="fas fa-eye"></i></a>
                                                    <a href="editar.php?id=<?php echo $paciente['id']; ?>" class="btn-accion btn-editar" title="Editar"><i class="fas fa-edit"></i></a>
                                                    <?php if ($verArchivados): ?>
                                                        <button type="button" class="btn-accion" style="background: rgba(56,161,105,0.1); color: var(--color-success);" title="Restaurar"
                                                                onclick="confirmarAccion(<?php echo $paciente['id']; ?>, '<?php echo htmlspecialchars(addslashes($paciente['nombres'])); ?>', 'restaurar')">
                                                            <i class="fas fa-trash-restore"></i>
                                                        </button>
                                                        <?php if (esAdmin()): ?>
                                                            <button type="button" class="btn-accion btn-eliminar" title="Eliminar permanentemente"
                                                                    onclick="confirmarAccion(<?php echo $paciente['id']; ?>, '<?php echo htmlspecialchars(addslashes($paciente['nombres'])); ?>', 'eliminar')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <button type="button" class="btn-accion btn-eliminar" title="Archivar"
                                                                onclick="confirmarAccion(<?php echo $paciente['id']; ?>, '<?php echo htmlspecialchars(addslashes($paciente['nombres'])); ?>', 'archivar')">
                                                            <i class="fas fa-archive"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPaginas > 1): ?>
                    <div style="display:flex; justify-content:center; align-items:center; gap:6px; padding: 14px 0;">
                        <?php if ($paginaActual > 1): ?>
                            <a href="<?php echo htmlspecialchars(urlPagina(1, $busqueda, $tipoBusqueda, $verCitas, $verArchivados)); ?>" class="btn-nav btn-nav-secondary" style="padding:5px 10px;" title="Primera página"><i class="fas fa-angle-double-left"></i></a>
                            <a href="<?php echo htmlspecialchars(urlPagina($paginaActual - 1, $busqueda, $tipoBusqueda, $verCitas, $verArchivados)); ?>" class="btn-nav btn-nav-secondary" style="padding:5px 10px;"><i class="fas fa-angle-left"></i> Anterior</a>
                        <?php endif; ?>
                        <?php
                        $inicio = max(1, $paginaActual - 2);
                        $fin    = min($totalPaginas, $paginaActual + 2);
                        for ($p = $inicio; $p <= $fin; $p++):
                        ?>
                            <a href="<?php echo htmlspecialchars(urlPagina($p, $busqueda, $tipoBusqueda, $verCitas, $verArchivados)); ?>"
                               class="btn-nav <?php echo $p === $paginaActual ? 'btn-nav-primary' : 'btn-nav-secondary'; ?>"
                               style="padding:5px 10px; min-width:32px; text-align:center;">
                                <?php echo $p; ?>
                            </a>
                        <?php endfor; ?>
                        <?php if ($paginaActual < $totalPaginas): ?>
                            <a href="<?php echo htmlspecialchars(urlPagina($paginaActual + 1, $busqueda, $tipoBusqueda, $verCitas, $verArchivados)); ?>" class="btn-nav btn-nav-secondary" style="padding:5px 10px;">Siguiente <i class="fas fa-angle-right"></i></a>
                            <a href="<?php echo htmlspecialchars(urlPagina($totalPaginas, $busqueda, $tipoBusqueda, $verCitas, $verArchivados)); ?>" class="btn-nav btn-nav-secondary" style="padding:5px 10px;" title="Última página"><i class="fas fa-angle-double-right"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </main>

            <?php include 'includes/layout_footer.php'; ?>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div class="modal-overlay" id="modalAccion">
        <div class="modal">
            <div class="modal-icon" id="modalIcon"><i class="fas fa-archive"></i></div>
            <h3 class="modal-title" id="modalTitle">¿Archivar Historia Clínica?</h3>
            <p class="modal-text" id="modalTexto">Esta acción moverá la historia al archivo.</p>
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal()"><i class="fas fa-times"></i> Cancelar</button>
                <a href="#" id="btnConfirmarAccion" class="btn btn-danger"><i class="fas fa-check"></i> Confirmar</a>
            </div>
        </div>
    </div>

    <script>
        function confirmarAccion(id, nombre, tipo) {
            const modal = document.getElementById('modalAccion');
            const titulo = document.getElementById('modalTitle');
            const texto = document.getElementById('modalTexto');
            const btn = document.getElementById('btnConfirmarAccion');
            const icon = document.getElementById('modalIcon');

            if (tipo === 'archivar') {
                titulo.innerText = '¿Archivar Historia Clínica?';
                texto.innerHTML = '¿Está seguro de archivar la historia clínica de <strong>' + nombre + '</strong>?<br>Se podrá restaurar más tarde.';
                btn.href = 'archivar.php?id=' + id;
                btn.className = 'btn btn-danger';
                icon.innerHTML = '<i class="fas fa-archive"></i>';
                icon.style.color = 'var(--color-error)';
            } else if (tipo === 'restaurar') {
                titulo.innerText = '¿Restaurar Historia Clínica?';
                texto.innerHTML = '¿Desea restaurar la historia clínica de <strong>' + nombre + '</strong> al listado activo?';
                btn.href = 'restaurar.php?id=' + id;
                btn.className = 'btn btn-success';
                icon.innerHTML = '<i class="fas fa-trash-restore"></i>';
                icon.style.color = 'var(--color-success)';
            } else if (tipo === 'eliminar') {
                titulo.innerText = '¿ELIMINAR Historia Clínica?';
                texto.innerHTML = '<span style="color:var(--color-error);font-weight:bold;">¡ADVERTENCIA!</span> Esta acción borrará todas las imágenes, tratamientos y datos de <strong>' + nombre + '</strong> permanentemente.';
                btn.href = 'eliminar.php?id=' + id;
                btn.className = 'btn btn-danger';
                icon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                icon.style.color = 'var(--color-error)';
            }
            modal.classList.add('active');
        }

        function cerrarModal() { document.getElementById('modalAccion').classList.remove('active'); }
        document.getElementById('modalAccion').addEventListener('click', function(e) { if (e.target === this) cerrarModal(); });
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') cerrarModal(); });
    </script>
</body>
</html>
