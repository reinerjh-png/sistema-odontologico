<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
verificarSesion();
require_once 'includes/functions.php';
require_once 'includes/tenant.php';

$tenant = cargarTenant($pdo);
$basePath = getBasePath();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setMensaje('ID de paciente inválido', 'error');
    header('Location: dashboard.php');
    exit;
}

$id = intval($_GET['id']);
$paciente = obtenerPacientePorId($pdo, $id);

if (!$paciente) {
    setMensaje('Paciente no encontrado', 'error');
    header('Location: dashboard.php');
    exit;
}

$tratamientosPaciente = obtenerTratamientosPaciente($pdo, $id);
$imagenesPaciente = obtenerImagenesPaciente($pdo, $id);

$currentPage = 'dashboard';
$pageTitle = 'Detalle de Historia Clínica';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia N° <?= htmlspecialchars($paciente['numero_historia']) ?> - <?= htmlspecialchars($tenant['clinic_name']) ?></title>
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
                <div class="card" style="max-width: 900px; margin: 0 auto;">
                    <div class="detalle-header">
                        <div class="detalle-numero">
                            <span class="detalle-label">N° de Historia Clínica</span>
                            <span class="detalle-valor-grande"><?php echo htmlspecialchars($paciente['numero_historia']); ?></span>
                        </div>
                        <div style="text-align: right;">
                            <h2 style="margin: 0; color: var(--color-text); font-size: 1.5rem;font-weight:700;"><?php echo htmlspecialchars($paciente['nombres']); ?></h2>
                            <div style="margin-top: 8px;">
                                <?php if ($paciente['estado'] == 1): ?>
                                    <span class="badge badge-activo"><i class="fas fa-check-circle"></i> Paciente Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-activo" style="background: rgba(229,62,62,0.1); color: var(--color-error); border-color: rgba(229,62,62,0.2);"><i class="fas fa-archive"></i> Historia Archivada</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="detalle-grid">
                        <div class="detalle-seccion">
                            <h3 class="detalle-seccion-titulo"><i class="fas fa-user"></i> Datos Personales</h3>
                            <div class="detalle-contenido">
                                <div class="detalle-item">
                                    <span class="detalle-label">DNI</span>
                                    <span class="detalle-valor"><?php echo !empty($paciente['dni']) ? htmlspecialchars($paciente['dni']) : '<span class="text-gray">-</span>'; ?></span>
                                </div>
                                <div class="detalle-item">
                                    <span class="detalle-label">Edad</span>
                                    <span class="detalle-valor"><?php echo !empty($paciente['edad']) ? htmlspecialchars($paciente['edad']) . ' años' : '<span class="text-gray">-</span>'; ?></span>
                                </div>
                                <div class="detalle-item">
                                    <span class="detalle-label">Género</span>
                                    <span class="detalle-valor"><?php echo !empty($paciente['genero']) ? htmlspecialchars($paciente['genero']) : '<span class="text-gray">-</span>'; ?></span>
                                </div>
                                <div class="detalle-item">
                                    <span class="detalle-label">Celular</span>
                                    <span class="detalle-valor"><?php echo !empty($paciente['celular']) ? htmlspecialchars($paciente['celular']) : '<span class="text-gray">-</span>'; ?></span>
                                </div>
                                <div class="detalle-item full-width">
                                    <span class="detalle-label">Dirección</span>
                                    <span class="detalle-valor"><?php echo !empty($paciente['direccion']) ? htmlspecialchars($paciente['direccion']) : '<span class="text-gray">-</span>'; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="detalle-seccion">
                            <h3 class="detalle-seccion-titulo"><i class="fas fa-briefcase-medical"></i> Datos Clínicos</h3>
                            <div class="detalle-contenido">
                                <div class="detalle-item">
                                    <span class="detalle-label">Fecha de Registro</span>
                                    <span class="detalle-valor"><?php echo $paciente['fecha_registro'] ? date('d/m/Y', strtotime($paciente['fecha_registro'])) : '<span class="text-gray">-</span>'; ?></span>
                                </div>
                                <div class="detalle-item">
                                    <span class="detalle-label">Doctor Asignado</span>
                                    <span class="detalle-valor">
                                        <?php if (!empty($paciente['doctor_nombre'])): ?>
                                            <span class="badge badge-doctor"><i class="fas fa-user-md"></i> Dr. <?php echo htmlspecialchars($paciente['doctor_nombre']); ?></span>
                                        <?php else: ?>
                                            <span class="text-gray">Sin asignar</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="detalle-item full-width">
                                    <span class="detalle-label">Fecha de Próxima Cita</span>
                                    <span class="detalle-valor">
                                        <?php if ($paciente['fecha_ultima_cita']): ?>
                                            <strong class="text-accent"><?php echo date('d/m/Y', strtotime($paciente['fecha_ultima_cita'])); ?></strong>
                                            <?php if (!empty($paciente['hora_cita'])): ?>
                                                &nbsp;&mdash;&nbsp; <strong><?php echo date('g:i A', strtotime($paciente['hora_cita'])); ?></strong>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-gray">-</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="detalle-seccion-full">
                        <h3 class="detalle-seccion-titulo"><i class="fas fa-tooth"></i> Tratamientos a Realizar</h3>
                        <?php if (empty($tratamientosPaciente)): ?>
                            <p class="text-gray text-center" style="margin: 15px 0;">No se han asignado tratamientos a este paciente.</p>
                        <?php else: ?>
                            <div class="detalle-tratamientos">
                                <?php foreach ($tratamientosPaciente as $trat): ?>
                                    <span class="badge-tratamiento-grande">
                                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($trat['nombre']); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="detalle-seccion-full">
                        <h3 class="detalle-seccion-titulo"><i class="fas fa-notes-medical"></i> Observaciones Generales</h3>
                        <div class="detalle-observaciones">
                            <?php echo !empty($paciente['observaciones']) ? nl2br(htmlspecialchars($paciente['observaciones'])) : '<span class="text-gray" style="font-style: italic;">Sin observaciones registradas...</span>'; ?>
                        </div>
                    </div>

                    <?php if (!empty($imagenesPaciente)): ?>
                        <div class="imagenes-seccion" style="border-top: none; margin-top: 0; padding-top: 0;">
                            <h3 class="imagenes-titulo"><i class="fas fa-images"></i> Galería Fotográfica</h3>
                            <div class="galeria-grid">
                                <?php foreach ($imagenesPaciente as $index => $img): ?>
                                    <div class="galeria-item" onclick="abrirLightbox(<?php echo $index; ?>)">
                                        <img src="uploads/pacientes/<?php echo htmlspecialchars($paciente['numero_historia']); ?>/<?php echo htmlspecialchars($img['nombre_archivo']); ?>" 
                                             alt="<?php echo htmlspecialchars($img['nombre_original']); ?>"
                                             loading="lazy">
                                        <div class="galeria-item-overlay">
                                            <i class="fas fa-search-plus"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Lightbox Overlay -->
                        <div class="lightbox-overlay" id="lightboxOverlay">
                            <button class="lightbox-close" id="lightboxClose" title="Cerrar"><i class="fas fa-times"></i></button>
                            <button class="lightbox-nav lightbox-prev" id="lightboxPrev" title="Anterior"><i class="fas fa-chevron-left"></i></button>
                            <button class="lightbox-nav lightbox-next" id="lightboxNext" title="Siguiente"><i class="fas fa-chevron-right"></i></button>
                            
                            <div class="lightbox-content">
                                <img id="lightboxImg" src="" alt="">
                                <div class="lightbox-caption" id="lightboxCaption"></div>
                                <div class="lightbox-counter" id="lightboxCounter"></div>
                            </div>
                        </div>

                        <script>
                            const imagenes = [
                                <?php foreach ($imagenesPaciente as $img): ?>
                                {
                                    src: "uploads/pacientes/<?php echo htmlspecialchars($paciente['numero_historia']); ?>/<?php echo htmlspecialchars($img['nombre_archivo']); ?>",
                                    caption: "<?php echo htmlspecialchars(addslashes($img['nombre_original'])); ?>"
                                },
                                <?php endforeach; ?>
                            ];
                            
                            let indiceActual = 0;
                            const overlay = document.getElementById('lightboxOverlay');
                            const imgLightbox = document.getElementById('lightboxImg');
                            const caption = document.getElementById('lightboxCaption');
                            const counter = document.getElementById('lightboxCounter');
                            
                            function abrirLightbox(index) {
                                indiceActual = index;
                                actualizarLightbox();
                                overlay.classList.add('lightbox-active');
                                document.body.style.overflow = 'hidden';
                            }
                            
                            function cerrarLightbox() {
                                overlay.classList.remove('lightbox-active');
                                document.body.style.overflow = '';
                            }
                            
                            function actualizarLightbox() {
                                imgLightbox.style.animation = 'none';
                                imgLightbox.offsetHeight; 
                                imgLightbox.style.animation = 'lightboxZoomIn 0.3s ease';
                                
                                imgLightbox.src = imagenes[indiceActual].src;
                                caption.textContent = imagenes[indiceActual].caption;
                                counter.textContent = `${indiceActual + 1} / ${imagenes.length}`;
                            }
                            
                            function mostrarAnterior(e) { if(e) e.stopPropagation(); indiceActual = (indiceActual > 0) ? indiceActual - 1 : imagenes.length - 1; actualizarLightbox(); }
                            function mostrarSiguiente(e) { if(e) e.stopPropagation(); indiceActual = (indiceActual < imagenes.length - 1) ? indiceActual + 1 : 0; actualizarLightbox(); }
                            
                            document.getElementById('lightboxClose').addEventListener('click', cerrarLightbox);
                            document.getElementById('lightboxPrev').addEventListener('click', mostrarAnterior);
                            document.getElementById('lightboxNext').addEventListener('click', mostrarSiguiente);
                            
                            overlay.addEventListener('click', function(e) {
                                if (e.target === overlay || e.target.classList.contains('lightbox-content')) { cerrarLightbox(); }
                            });
                            
                            document.addEventListener('keydown', function(e) {
                                if (!overlay.classList.contains('lightbox-active')) return;
                                if (e.key === 'Escape') cerrarLightbox();
                                if (e.key === 'ArrowLeft') mostrarAnterior();
                                if (e.key === 'ArrowRight') mostrarSiguiente();
                            });
                        </script>
                    <?php endif; ?>

                    <div class="detalle-acciones">
                        <a href="dashboard.php<?php echo $paciente['estado'] == 0 ? '?ver=archivados' : ''; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver al Listado</a>
                        <a href="editar.php?id=<?php echo $paciente['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Editar Historia</a>
                    </div>
                </div>
            </main>

            <?php include 'includes/layout_footer.php'; ?>
        </div>
    </div>
</body>
</html>
