<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
verificarSesion();
requiereAdmin();
require_once '../includes/functions.php';
require_once '../includes/tenant.php';

$tenant = cargarTenant($pdo);
$basePath = getBasePath();

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar_branding') {
    $clinic_name = sanitizar($_POST['clinic_name'] ?? '');
    $color_primary = sanitizar($_POST['color_primary'] ?? '#1B2B4B');
    $color_accent = sanitizar($_POST['color_accent'] ?? '#4A90D9');
    $color_sidebar = sanitizar($_POST['color_sidebar'] ?? '#1B2B4B');
    
    // Validar colores hex
    function is_hex($str) {
        return preg_match('/^#[a-fA-F0-9]{6}$/', $str);
    }
    
    $errores = [];
    if (empty($clinic_name)) $errores[] = 'El nombre de la clínica es obligatorio';
    if (!is_hex($color_primary)) $errores[] = 'El color primario no es válido';
    if (!is_hex($color_accent)) $errores[] = 'El color de acento no es válido';
    if (!is_hex($color_sidebar)) $errores[] = 'El color del sidebar no es válido';
    
    // Procesar subida de logo si se proporciona
    $logo_url = $tenant['logo_url'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
        $fileName = $_FILES['logo']['name'];
        $fileSize = $_FILES['logo']['size'];
        $fileTmpName  = $_FILES['logo']['tmp_name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowed)) {
            $errores[] = "Formato de imagen no permitido. Usa JPG, PNG, SVG o WebP.";
        } elseif ($fileSize > 2000000) { // 2MB
            $errores[] = "La imagen no debe superar los 2MB.";
        } else {
            $uploadDirectory = "../assets/";
            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0755, true);
            }
            $newFileName = "logo_tenant_" . time() . "." . $fileExt;
            $uploadPath = $uploadDirectory . $newFileName;
            
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                $logo_url = "assets/" . $newFileName;
            } else {
                $errores[] = "Hubo un error al subir el logo.";
            }
        }
    }
    
    if (empty($errores)) {
        // Verificar si existe el registro, si no, crear
        $check = $pdo->query("SELECT id FROM tenant_config LIMIT 1")->fetch();
        if ($check) {
            $stmt = $pdo->prepare("UPDATE tenant_config SET clinic_name = ?, logo_url = ?, color_primary = ?, color_accent = ?, color_sidebar = ? WHERE id = ?");
            $stmt->execute([$clinic_name, $logo_url, $color_primary, $color_accent, $color_sidebar, $check['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO tenant_config (clinic_name, logo_url, color_primary, color_accent, color_sidebar) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$clinic_name, $logo_url, $color_primary, $color_accent, $color_sidebar]);
        }
        
        // Limpiar caché de sesión para recargar estilo en la próxima vista
        unset($_SESSION['tenant']);
        
        registrarActividad($pdo, 'Actualizar Configuración', 'Actualizó el branding de la clínica');
        setMensaje('Configuración de branding actualizada exitosamente', 'success');
        
        // Recargar tenant para reflejar en esta página
        $tenant = cargarTenant($pdo);
    } else {
        setMensaje(implode('. ', $errores), 'error');
    }
}

$currentPage = 'branding';
$pageTitle = 'Personalización de Branding';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branding - <?= htmlspecialchars($tenant['clinic_name']) ?></title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/fontawesome/css/all.min.css">
    <?php renderTenantCssVars($tenant); ?>
    <style>
        .branding-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 900px) {
            .branding-container {
                grid-template-columns: 1fr;
            }
        }
        
        .color-picker-group {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 5px;
        }
        
        .color-picker-input {
            width: 50px;
            height: 50px;
            padding: 0;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            overflow: hidden;
        }
        
        .color-picker-input::-webkit-color-swatch-wrapper {
            padding: 0;
        }
        
        .color-picker-input::-webkit-color-swatch {
            border: 2px solid var(--color-border);
            border-radius: var(--radius-md);
        }
        
        .hex-input {
            font-family: monospace;
            text-transform: uppercase;
        }
        
        /* Preview Styles */
        .preview-card {
            background: #F4F6F9;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 400px;
            position: relative;
        }
        
        .preview-header {
            background: var(--color-primary);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .preview-sidebar {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 200px;
            background: var(--color-sidebar);
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 10;
        }
        
        .preview-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        
        .preview-logo img {
            width: 30px;
            height: 30px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .preview-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 8px;
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
        }
        
        .preview-nav-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid var(--color-accent);
        }
        
        .preview-content {
            margin-left: 200px;
            padding: 20px;
            flex: 1;
        }
        
        .preview-btn {
            background: var(--color-primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .color-suggestions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .color-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
            transition: transform 0.2s;
        }
        
        .color-dot:hover {
            transform: scale(1.2);
            border-color: var(--color-border);
        }
        
        .current-logo {
            max-width: 150px;
            max-height: 80px;
            object-fit: contain;
            border: 1px dashed var(--color-border);
            padding: 10px;
            border-radius: var(--radius-md);
            background: var(--color-pure-white);
            margin-bottom: 10px;
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
                        <i class="fas fa-paint-roller" style="color: var(--color-primary); margin-right: 10px;"></i>
                        Personalización (White-Label)
                    </h1>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Admin
                    </a>
                </div>

                <?php echo mostrarAlerta(); ?>

                <div class="branding-container">
                    
                    <!-- Formulario de Configuración -->
                    <div class="card form-container">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-sliders-h"></i> Ajustes de Apariencia</h2>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" id="brandingForm">
                            <input type="hidden" name="accion" value="actualizar_branding">
                            
                            <div class="form-group" style="margin-bottom: 25px;">
                                <label class="form-label">Nombre de la Clínica / Empresa <span class="required">*</span></label>
                                <input type="text" name="clinic_name" id="clinic_name" class="form-control" value="<?= htmlspecialchars($tenant['clinic_name']) ?>" required>
                                <small class="text-gray mt-1" style="display:block;">Este nombre aparecerá en el sidebar y en el login.</small>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 25px;">
                                <label class="form-label">Logo Actual</label>
                                <div>
                                    <img src="../<?= htmlspecialchars($tenant['logo_url']) ?>" alt="Logo" class="current-logo">
                                </div>
                                <label class="form-label mt-2">Subir Nuevo Logo (Opcional)</label>
                                <input type="file" name="logo" class="form-control" accept="image/png, image/jpeg, image/jpg, image/svg+xml, image/webp">
                                <small class="text-gray mt-1" style="display:block;">Se recomienda una imagen en formato PNG o SVG con fondo transparente. Altura óptima: 40-60px.</small>
                            </div>
                            
                            <div style="border-top: 1px solid var(--color-border); margin: 25px 0;"></div>
                            <h3 style="font-size: 1.1rem; margin-bottom: 15px; color: var(--color-text);">Colores Corporativos</h3>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label class="form-label">Color Primario (Header, Botones principales)</label>
                                <div class="color-picker-group">
                                    <input type="color" name="color_primary" id="color_primary" class="color-picker-input" value="<?= htmlspecialchars($tenant['color_primary']) ?>">
                                    <input type="text" id="color_primary_hex" class="form-control hex-input" value="<?= htmlspecialchars($tenant['color_primary']) ?>" style="width: 120px;" maxlength="7">
                                </div>
                                <div class="color-suggestions">
                                    <div class="color-dot" style="background:#1B2B4B;" onclick="setColor('primary', '#1B2B4B')" title="Navy"></div>
                                    <div class="color-dot" style="background:#0F172A;" onclick="setColor('primary', '#0F172A')" title="Slate"></div>
                                    <div class="color-dot" style="background:#047857;" onclick="setColor('primary', '#047857')" title="Emerald"></div>
                                    <div class="color-dot" style="background:#4338CA;" onclick="setColor('primary', '#4338CA')" title="Indigo"></div>
                                    <div class="color-dot" style="background:#BE123C;" onclick="setColor('primary', '#BE123C')" title="Rose"></div>
                                    <div class="color-dot" style="background:#6D28D9;" onclick="setColor('primary', '#6D28D9')" title="Purple"></div>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label class="form-label">Color de Acento (Elementos activos, Detalles)</label>
                                <div class="color-picker-group">
                                    <input type="color" name="color_accent" id="color_accent" class="color-picker-input" value="<?= htmlspecialchars($tenant['color_accent']) ?>">
                                    <input type="text" id="color_accent_hex" class="form-control hex-input" value="<?= htmlspecialchars($tenant['color_accent']) ?>" style="width: 120px;" maxlength="7">
                                </div>
                                <div class="color-suggestions">
                                    <div class="color-dot" style="background:#4A90D9;" onclick="setColor('accent', '#4A90D9')" title="Blue"></div>
                                    <div class="color-dot" style="background:#38BDF8;" onclick="setColor('accent', '#38BDF8')" title="Sky"></div>
                                    <div class="color-dot" style="background:#10B981;" onclick="setColor('accent', '#10B981')" title="Green"></div>
                                    <div class="color-dot" style="background:#8B5CF6;" onclick="setColor('accent', '#8B5CF6')" title="Violet"></div>
                                    <div class="color-dot" style="background:#F43F5E;" onclick="setColor('accent', '#F43F5E')" title="Rose"></div>
                                    <div class="color-dot" style="background:#F59E0B;" onclick="setColor('accent', '#F59E0B')" title="Amber"></div>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 30px;">
                                <label class="form-label">Color del Sidebar (Fondo menú lateral)</label>
                                <div class="color-picker-group">
                                    <input type="color" name="color_sidebar" id="color_sidebar" class="color-picker-input" value="<?= htmlspecialchars($tenant['color_sidebar']) ?>">
                                    <input type="text" id="color_sidebar_hex" class="form-control hex-input" value="<?= htmlspecialchars($tenant['color_sidebar']) ?>" style="width: 120px;" maxlength="7">
                                </div>
                                <div class="color-suggestions">
                                    <div class="color-dot" style="background:#1B2B4B;" onclick="setColor('sidebar', '#1B2B4B')" title="Navy"></div>
                                    <div class="color-dot" style="background:#1E293B;" onclick="setColor('sidebar', '#1E293B')" title="Slate"></div>
                                    <div class="color-dot" style="background:#18181B;" onclick="setColor('sidebar', '#18181B')" title="Zinc"></div>
                                    <div class="color-dot" style="background:#172554;" onclick="setColor('sidebar', '#172554')" title="Blue"></div>
                                    <div class="color-dot" style="background:#4A2B2B;" onclick="setColor('sidebar', '#4A2B2B')" title="Dark Red"></div>
                                    <div class="color-dot" style="background:#064E3B;" onclick="setColor('sidebar', '#064E3B')" title="Dark Green"></div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                <button type="button" class="btn btn-secondary" onclick="resetForm()"><i class="fas fa-undo"></i> Restaurar Original</button>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Vista Previa en Tiempo Real -->
                    <div>
                        <h3 style="font-size: 1.1rem; margin-bottom: 15px; color: var(--color-text);">Vista Previa en Tiempo Real</h3>
                        <div class="preview-card" id="previewContainer">
                            <!-- Sidebar Preview -->
                            <div class="preview-sidebar" id="previewSidebar">
                                <div class="preview-logo">
                                    <img src="../<?= htmlspecialchars($tenant['logo_url']) ?>" alt="Logo">
                                    <span id="previewClinicName"><?= htmlspecialchars($tenant['clinic_name']) ?></span>
                                </div>
                                <div class="preview-nav-item">
                                    <i class="fas fa-home"></i> Inicio
                                </div>
                                <div class="preview-nav-item active" id="previewNavActive">
                                    <i class="fas fa-users"></i> Pacientes
                                </div>
                                <div class="preview-nav-item">
                                    <i class="fas fa-calendar"></i> Citas
                                </div>
                            </div>
                            
                            <!-- Content Preview -->
                            <div class="preview-content">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                                    <h4 style="margin:0; font-size:1.2rem; color: #1E293B;">Panel de Control</h4>
                                    <div style="background:white; padding:5px 15px; border-radius:30px; border:1px solid #E2E8F0; font-size:0.8rem; color:#64748B;">
                                        <i class="fas fa-search"></i> Buscar...
                                    </div>
                                </div>
                                
                                <div style="background:white; padding:20px; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #E2E8F0;">
                                    <h5 style="margin:0 0 15px 0; font-size:1rem; color:#334155;">Estadísticas</h5>
                                    <div style="display:flex; gap:15px; margin-bottom:20px;">
                                        <div style="flex:1; background:#F8FAFC; padding:15px; border-radius:8px; border:1px solid #E2E8F0;">
                                            <div style="font-size:0.75rem; color:#64748B; text-transform:uppercase;">Pacientes</div>
                                            <div style="font-size:1.5rem; font-weight:bold; color:#0F172A; margin-top:5px;">1,248</div>
                                        </div>
                                        <div style="flex:1; background:#F8FAFC; padding:15px; border-radius:8px; border:1px solid #E2E8F0;">
                                            <div style="font-size:0.75rem; color:#64748B; text-transform:uppercase;">Citas Hoy</div>
                                            <div style="font-size:1.5rem; font-weight:bold; color:#0F172A; margin-top:5px;">12</div>
                                        </div>
                                    </div>
                                    <button class="preview-btn" id="previewBtnPrimary">
                                        <i class="fas fa-plus"></i> Nuevo Registro
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card p-3 mt-4" style="background-color: var(--color-pure-white);">
                            <h4 style="font-size: 0.95rem; margin-bottom: 10px; color: var(--color-text);">Información sobre White-Label</h4>
                            <p style="font-size: 0.85rem; color: var(--color-text-secondary); margin: 0; line-height: 1.5;">
                                El sistema está diseñado para ser multi-tenant. Al modificar estos colores y el logo, todo el panel de administración, call center, y pantalla de inicio de sesión se adaptarán automáticamente a la identidad de marca configurada, sin tocar código fuente.
                            </p>
                        </div>
                    </div>
                </div>

            </main>

            <?php include '../includes/layout_footer.php'; ?>
        </div>
    </div>
    
    <script>
        // Variables iniciales
        const originalName = "<?= htmlspecialchars($tenant['clinic_name']) ?>";
        const originalColors = {
            primary: "<?= htmlspecialchars($tenant['color_primary']) ?>",
            accent: "<?= htmlspecialchars($tenant['color_accent']) ?>",
            sidebar: "<?= htmlspecialchars($tenant['color_sidebar']) ?>"
        };
        
        // Elementos del DOM
        const inputs = {
            name: document.getElementById('clinic_name'),
            primary: document.getElementById('color_primary'),
            primaryHex: document.getElementById('color_primary_hex'),
            accent: document.getElementById('color_accent'),
            accentHex: document.getElementById('color_accent_hex'),
            sidebar: document.getElementById('color_sidebar'),
            sidebarHex: document.getElementById('color_sidebar_hex')
        };
        
        // Elementos de la vista previa
        const previewEl = {
            name: document.getElementById('previewClinicName'),
            sidebar: document.getElementById('previewSidebar'),
            btn: document.getElementById('previewBtnPrimary'),
            activeNav: document.getElementById('previewNavActive')
        };
        
        // Función para actualizar la vista previa y sincronizar inputs
        function updateColors() {
            // Sincronizar inputs
            inputs.primaryHex.value = inputs.primary.value.toUpperCase();
            inputs.accentHex.value = inputs.accent.value.toUpperCase();
            inputs.sidebarHex.value = inputs.sidebar.value.toUpperCase();
            
            // Actualizar vista previa
            previewEl.name.textContent = inputs.name.value || 'Nombre Clínica';
            previewEl.sidebar.style.backgroundColor = inputs.sidebar.value;
            previewEl.btn.style.backgroundColor = inputs.primary.value;
            previewEl.activeNav.style.borderLeftColor = inputs.accent.value;
            
            // Opcional: Modificar también los estilos de la interfaz real para previsualización total
            document.documentElement.style.setProperty('--color-primary', inputs.primary.value);
            document.documentElement.style.setProperty('--color-accent', inputs.accent.value);
            document.documentElement.style.setProperty('--color-sidebar', inputs.sidebar.value);
        }
        
        // Función para validar y aplicar Hex escrito a mano
        function handleHexInput(type) {
            let hex = inputs[type + 'Hex'].value;
            if (!hex.startsWith('#')) hex = '#' + hex;
            
            // Validar formato hex básico de 3 o 6 letras
            if (/^#([0-9A-F]{3}){1,2}$/i.test(hex)) {
                inputs[type].value = hex;
                updateColors();
            }
        }
        
        // Función auxiliar para los botones de sugerencia
        function setColor(type, hex) {
            inputs[type].value = hex;
            inputs[type + 'Hex'].value = hex;
            updateColors();
        }
        
        // Función para restaurar
        function resetForm() {
            inputs.name.value = originalName;
            setColor('primary', originalColors.primary);
            setColor('accent', originalColors.accent);
            setColor('sidebar', originalColors.sidebar);
            document.getElementById('brandingForm').reset();
            updateColors();
        }
        
        // Listeners
        inputs.name.addEventListener('input', updateColors);
        
        inputs.primary.addEventListener('input', updateColors);
        inputs.accent.addEventListener('input', updateColors);
        inputs.sidebar.addEventListener('input', updateColors);
        
        inputs.primaryHex.addEventListener('input', () => handleHexInput('primary'));
        inputs.accentHex.addEventListener('input', () => handleHexInput('accent'));
        inputs.sidebarHex.addEventListener('input', () => handleHexInput('sidebar'));
        
        // Evitar submit si hex invalido
        document.getElementById('brandingForm').addEventListener('submit', function(e) {
            const hexRegex = /^#([A-Fa-f0-9]{6})$/;
            if (!hexRegex.test(inputs.primaryHex.value) || 
                !hexRegex.test(inputs.accentHex.value) || 
                !hexRegex.test(inputs.sidebarHex.value)) {
                e.preventDefault();
                alert('Por favor, asegúrese de que todos los colores tengan un formato hexadecimal válido (ej. #1B2B4B).');
            }
        });
        
        // Inicializar
        updateColors();
    </script>
</body>
</html>
