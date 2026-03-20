<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
verificarSesion();
require_once 'includes/functions.php';
require_once 'includes/tenant.php';

$tenant = cargarTenant($pdo);
$basePath = getBasePath();

// Verificar si se recibió un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setMensaje('ID de paciente inválido', 'error');
    header('Location: dashboard.php');
    exit;
}

$id = intval($_GET['id']);

// Obtener paciente
$paciente = obtenerPacientePorId($pdo, $id);

if (!$paciente) {
    setMensaje('Paciente no encontrado', 'error');
    header('Location: dashboard.php');
    exit;
}

// Obtener tratamientos del paciente
$tratamientosPaciente = obtenerTratamientosPaciente($pdo, $id);
$tratamientosIds = array_column($tratamientosPaciente, 'id');

// Obtener imágenes del paciente
$imagenesPaciente = obtenerImagenesPaciente($pdo, $id);

// Obtener doctores y tratamientos para los selectores
$doctores = obtenerDoctores($pdo);
$tratamientos = obtenerTratamientos($pdo);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $datos = [
            'numero_historia' => sanitizar($_POST['numero_historia']),
            'dni' => sanitizar($_POST['dni']),
            'nombres' => sanitizar($_POST['nombres']),
            'genero' => sanitizar($_POST['genero']),
            'celular' => sanitizar($_POST['celular']),
            'edad' => intval($_POST['edad']),
            'direccion' => sanitizar($_POST['direccion']),
            'fecha_registro' => $_POST['fecha_registro'] ?: null,
            'doctor_id' => $_POST['doctor_id'] ?: null,
            'fecha_ultima_cita' => $_POST['fecha_ultima_cita'] ?: null,
            'hora_cita' => null,
            'observaciones' => sanitizar($_POST['observaciones'])
        ];
        
        // Convertir hora 12h a formato 24h TIME
        if (!empty($_POST['hora_cita_hora']) && !empty($_POST['hora_cita_ampm'])) {
            $h = intval($_POST['hora_cita_hora']);
            $m = isset($_POST['hora_cita_min']) ? str_pad(intval($_POST['hora_cita_min']), 2, '0', STR_PAD_LEFT) : '00';
            $ampm = $_POST['hora_cita_ampm'];
            if ($ampm === 'PM' && $h < 12) $h += 12;
            if ($ampm === 'AM' && $h == 12) $h = 0;
            $datos['hora_cita'] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . $m . ':00';
        }
        
        $tratamientosSeleccionados = $_POST['tratamientos'] ?? [];
        
        // Validaciones
        $errores = [];
        if (empty($datos['numero_historia'])) $errores[] = "El número de historia es obligatorio";
        if (empty($datos['nombres'])) $errores[] = "Los nombres son obligatorios";
        
        // Verificar si el número de historia ya existe (excluyendo el actual)
        $stmt = $pdo->prepare("SELECT id FROM pacientes WHERE numero_historia = ? AND id != ? AND estado = 1");
        $stmt->execute([$datos['numero_historia'], $id]);
        if ($stmt->fetch()) {
            $errores[] = "El número de historia ya existe en otro paciente";
        }
        
        if (empty($errores)) {
            actualizarPaciente($pdo, $id, $datos, $tratamientosSeleccionados);
            registrarActividad($pdo, 'Editar Paciente', 'Editó historia clínica: ' . $datos['numero_historia'] . ' - ' . $datos['nombres']);
            setMensaje('Historia clínica actualizada exitosamente', 'success');
            header('Location: dashboard.php');
            exit;
        } else {
            $mensajeError = implode('<br>', $errores);
        }
        
    } catch (Exception $e) {
        $mensajeError = 'Error al actualizar la historia clínica: ' . $e->getMessage();
    }
}

// Pre-compute hour values for time pickers
$horaCitaDB = $paciente['hora_cita'] ?? '';
$horaCitaHora = '';
$horaCitaMin = '00';
$horaCitaAmpm = '';
if (!empty($horaCitaDB)) {
    $ts = strtotime($horaCitaDB);
    $h24 = (int)date('G', $ts);
    $horaCitaAmpm = $h24 >= 12 ? 'PM' : 'AM';
    $h12 = $h24 % 12; if ($h12 === 0) $h12 = 12;
    $horaCitaHora = $h12;
    $horaCitaMin = date('i', $ts);
}

$currentPage = 'dashboard';
$pageTitle = 'Editar Historia Clínica';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar - <?= htmlspecialchars($paciente['nombres']) ?> - <?= htmlspecialchars($tenant['clinic_name']) ?></title>
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
                <?php if (isset($mensajeError)): ?>
                    <div class="alerta alerta-error"><i class="fas fa-exclamation-circle"></i> <?php echo $mensajeError; ?></div>
                <?php endif; ?>

                <div class="card form-container">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-user-edit"></i> Editando: <?= htmlspecialchars($paciente['nombres']) ?></h2>
                        <span class="badge badge-activo">Historia N° <?= htmlspecialchars($paciente['numero_historia']) ?></span>
                    </div>

                    <form action="" method="POST" id="formHistoria">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Número de Historia <span class="required">*</span></label>
                                <input type="text" name="numero_historia" class="form-control" required
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                       value="<?= htmlspecialchars($paciente['numero_historia']) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">DNI</label>
                                <input type="text" name="dni" class="form-control" maxlength="8"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 8)"
                                       value="<?= htmlspecialchars($paciente['dni']) ?>">
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Nombres (Apellidos y Nombres) <span class="required">*</span></label>
                                <input type="text" name="nombres" class="form-control" required
                                       value="<?= htmlspecialchars($paciente['nombres']) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Género</label>
                                <select name="genero" class="form-control">
                                    <option value="">-- Seleccionar --</option>
                                    <option value="Masculino" <?= $paciente['genero'] === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                                    <option value="Femenino" <?= $paciente['genero'] === 'Femenino' ? 'selected' : '' ?>>Femenino</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Celular</label>
                                <input type="tel" name="celular" class="form-control" maxlength="9"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)"
                                       value="<?= htmlspecialchars($paciente['celular']) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Edad</label>
                                <input type="number" name="edad" class="form-control" min="0" max="150"
                                       value="<?= htmlspecialchars($paciente['edad']) ?>">
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="direccion" class="form-control"
                                       value="<?= htmlspecialchars($paciente['direccion']) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fecha de Registro</label>
                                <input type="date" name="fecha_registro" class="form-control"
                                       value="<?= htmlspecialchars($paciente['fecha_registro']) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Doctor</label>
                                <select name="doctor_id" class="form-control">
                                    <option value="">-- Seleccionar Doctor --</option>
                                    <?php foreach ($doctores as $doctor): ?>
                                        <option value="<?= $doctor['id'] ?>" <?= $paciente['doctor_id'] == $doctor['id'] ? 'selected' : '' ?>>
                                            Dr. <?= htmlspecialchars($doctor['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Agendar Cita</label>
                                <input type="date" name="fecha_ultima_cita" class="form-control"
                                       value="<?= htmlspecialchars($paciente['fecha_ultima_cita']) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Hora de Cita</label>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <select name="hora_cita_hora" class="form-control" style="flex: 1; min-width: 0;">
                                        <option value="">Hora</option>
                                        <?php for ($h = 1; $h <= 12; $h++): ?>
                                            <option value="<?= $h ?>" <?= $horaCitaHora == $h ? 'selected' : '' ?>><?= $h ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <span style="color: var(--color-text-light); font-weight: 700;">:</span>
                                    <select name="hora_cita_min" class="form-control" style="flex: 1; min-width: 0;">
                                        <?php foreach (['00','15','30','45'] as $min): ?>
                                            <option value="<?= $min ?>" <?= $horaCitaMin == $min ? 'selected' : '' ?>><?= $min ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="hora_cita_ampm" class="form-control" style="flex: 1; min-width: 0;">
                                        <option value="">--</option>
                                        <option value="AM" <?= $horaCitaAmpm === 'AM' ? 'selected' : '' ?>>AM</option>
                                        <option value="PM" <?= $horaCitaAmpm === 'PM' ? 'selected' : '' ?>>PM</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Tratamientos</label>
                                <div class="tratamientos-container">
                                    <?php foreach ($tratamientos as $tratamiento): ?>
                                        <div class="tratamiento-item">
                                            <input type="checkbox" name="tratamientos[]" value="<?= $tratamiento['id'] ?>"
                                                   id="trat_<?= $tratamiento['id'] ?>"
                                                   <?= in_array($tratamiento['id'], $tratamientosIds) ? 'checked' : '' ?>>
                                            <label for="trat_<?= $tratamiento['id'] ?>"><?= htmlspecialchars($tratamiento['nombre']) ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-control"><?= htmlspecialchars($paciente['observaciones']) ?></textarea>
                            </div>
                        </div>

                        <!-- Sección de imágenes -->
                        <div class="imagenes-seccion" style="margin-top: 30px; margin-bottom: 20px; border-top: 1px solid var(--color-border); padding-top: 20px;">
                            <h3 class="imagenes-titulo"><i class="fas fa-images"></i> Imágenes del Paciente</h3>
                            <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                                <input type="file" id="fileInput" style="display:none;" accept="image/*"
                                       onchange="subirImagen(this.files[0])" multiple>
                                <i class="fas fa-cloud-upload-alt upload-zone-icon"></i>
                                <p class="upload-zone-text">Haga clic, arrastre o pegue (Ctrl+V) imágenes aquí</p>
                                <small class="upload-zone-hint">Formatos: JPG, PNG, GIF — Máx. 5 MB</small>
                            </div>
                            <div class="imagenes-grid" id="imagenesGrid">
                                <?php foreach ($imagenesPaciente as $img): ?>
                                    <div class="imagen-item" id="img-<?= $img['id'] ?>">
                                        <img src="uploads/pacientes/<?= htmlspecialchars($paciente['numero_historia']) ?>/<?= htmlspecialchars($img['nombre_archivo']) ?>"
                                             alt="<?= htmlspecialchars($img['nombre_original']) ?>" loading="lazy">
                                        <div class="imagen-overlay">
                                            <span class="imagen-nombre"><?= htmlspecialchars($img['nombre_original']) ?></span>
                                            <button type="button" class="btn-eliminar-img" onclick="eliminarImagen(<?= $img['id'] ?>, this)"
                                                    title="Eliminar imagen">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-buttons">
                            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
                            <a href="ver.php?id=<?= $id ?>" class="btn btn-secondary"><i class="fas fa-eye"></i> Ver Detalle</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar Historia</button>
                        </div>
                    </form>
                </div>
            </main>

            <?php include 'includes/layout_footer.php'; ?>
        </div>
    </div>

    <script>
        // Drag & Drop
        const zone = document.getElementById('uploadZone');
        zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('upload-zone-active'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('upload-zone-active'));
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('upload-zone-active');
            if (e.dataTransfer.files.length > 0) {
                Array.from(e.dataTransfer.files).forEach(f => subirImagen(f));
            }
        });

        // Pegar desde portapapeles (Ctrl+V)
        document.addEventListener('paste', (e) => {
            // Ignorar si el usuario está escribiendo en un campo de texto
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            const items = (e.clipboardData || window.clipboardData).items;
            for (let i = 0; i < items.length; i++) {
                if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
                    e.preventDefault();
                    const file = items[i].getAsFile();
                    // Darle un nombre genérico al archivo pegado
                    const timestamp = new Date().getTime();
                    const renamedFile = new File([file], "clipboard_" + timestamp + ".png", { type: file.type });
                    subirImagen(renamedFile);
                }
            }
        });

        function subirImagen(file) {
            if (!file || !file.type.startsWith('image/')) return;
            if (file.size > 5 * 1024 * 1024) { alert('La imagen no debe superar 5 MB'); return; }
            const fd = new FormData();
            fd.append('imagen', file);
            fd.append('paciente_id', <?php echo $id; ?>);
            const tempId = 'temp-' + Date.now();
            const grid = document.getElementById('imagenesGrid');
            const tempDiv = document.createElement('div');
            tempDiv.className = 'imagen-item imagen-loading';
            tempDiv.id = tempId;
            tempDiv.innerHTML = '<i class="fas fa-spinner fa-spin spinner"></i>';
            grid.appendChild(tempDiv);
            fetch('upload_imagen.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        tempDiv.className = 'imagen-item';
                        tempDiv.id = 'img-' + data.imagen.id;
                        tempDiv.innerHTML = '<img src="' + data.imagen.url + '" alt="' + data.imagen.nombre_original + '" loading="lazy">' +
                            '<div class="imagen-overlay"><span class="imagen-nombre">' + data.imagen.nombre_original + '</span>' +
                            '<button type="button" class="btn-eliminar-img" onclick="eliminarImagen(' + data.imagen.id + ', this)" title="Eliminar"><i class="fas fa-trash"></i></button></div>';
                    } else {
                        tempDiv.remove();
                        alert(data.error || 'Error al subir la imagen');
                    }
                })
                .catch(() => { tempDiv.remove(); alert('Error de conexión'); });
        }

        function eliminarImagen(imgId, btn) {
            if (!confirm('¿Eliminar esta imagen?')) return;
            fetch('eliminar_imagen.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'imagen_id=' + imgId
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const item = document.getElementById('img-' + imgId);
                    if (item) { item.style.transition = 'all 0.3s ease'; item.style.opacity = '0'; item.style.transform = 'scale(0.8)'; setTimeout(() => item.remove(), 300); }
                } else {
                    alert(data.error || 'No se pudo eliminar');
                }
            })
            .catch(() => alert('Error de conexión'));
        }
    </script>
</body>
</html>
