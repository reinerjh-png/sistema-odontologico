<?php
/**
 * Módulo Odontograma Interactivo
 * Clínica Dental Premium Uchuya
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
verificarSesion();
require_once '../includes/functions.php';
require_once '../includes/tenant.php';

$tenant = cargarTenant($pdo);
$basePath = getBasePath();

$id_paciente = isset($_GET['id_paciente']) ? intval($_GET['id_paciente']) : 0;
$paciente = null;
$error = '';

if ($id_paciente > 0) {
    $paciente = obtenerPacientePorId($pdo, $id_paciente);
    if (!$paciente) {
        $error = 'Paciente no encontrado.';
        $id_paciente = 0;
    }
}

// Cargar catálogo de condiciones activas
$condiciones = [];
try {
    $stmtCond = $pdo->query("SELECT * FROM odontograma_condiciones WHERE activo = 1 ORDER BY orden");
    $condiciones = $stmtCond->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Si la tabla no existe o falla, se manejará en el frontend o se usará fallback
}

$currentPage = 'odontograma';
$pageTitle = $paciente ? 'Odontograma de ' . htmlspecialchars($paciente['nombres']) : 'Odontograma · Selección de Paciente';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($tenant['clinic_name']) ?></title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/fontawesome/css/all.min.css">
    <?php renderTenantCssVars($tenant); ?>
    <style>
        /* Estilos del Módulo de Odontograma */
        .odontograma-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .odontograma-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 1200px) {
            .odontograma-grid {
                grid-template-columns: 1fr;
            }
        }
        .paciente-resumen-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border-light);
            border-radius: var(--radius);
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
        }
        .paciente-info-primary h2 {
            margin: 0;
            font-size: 1.3rem;
            color: var(--color-text);
            font-weight: 700;
        }
        .paciente-info-meta {
            font-size: 0.85rem;
            color: var(--color-text-secondary);
            margin-top: 4px;
            display: flex;
            gap: 16px;
        }
        .paciente-info-meta span strong {
            color: var(--color-text);
        }
        .odontograma-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border-light);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow-md);
        }
        .odontograma-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--color-border-light);
        }
        .toggle-btn-group {
            display: flex;
            background: var(--color-bg);
            padding: 4px;
            border-radius: 8px;
            border: 1px solid var(--color-border-light);
        }
        .toggle-btn {
            background: transparent;
            border: none;
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--color-text-secondary);
            cursor: pointer;
            border-radius: 6px;
            transition: var(--transition);
        }
        .toggle-btn.active {
            background: var(--color-surface);
            color: var(--color-accent);
            box-shadow: var(--shadow-sm);
        }
        .date-control-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .input-date {
            padding: 8px 12px;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            color: var(--color-text);
            font-size: 0.88rem;
            font-family: var(--font-main);
            outline: none;
        }
        .input-date:focus {
            border-color: var(--color-accent);
        }
        .select-historial {
            padding: 8px 12px;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            color: var(--color-text);
            font-size: 0.88rem;
            font-family: var(--font-main);
            cursor: pointer;
        }
        
        /* Odontograma Dentaduras */
        .odontograma-dentadura {
            display: flex;
            flex-direction: column;
            gap: 30px;
            overflow-x: auto;
            padding: 10px 0;
        }
        .arcada-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            min-width: 780px;
            padding: 10px 0;
            position: relative;
        }
        .arcada-row.upper {
            border-bottom: 1px dashed var(--color-border);
            padding-bottom: 20px;
        }
        /* Midline divider */
        .midline-indicator {
            width: 2px;
            height: 100%;
            background: var(--color-border);
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1;
            pointer-events: none;
            opacity: 0.5;
        }
        
        /* Diente individual */
        .diente-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 48px;
            position: relative;
            user-select: none;
        }
        .diente-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--color-text-secondary);
            margin: 4px 0;
            font-family: monospace;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 4px;
            transition: var(--transition);
        }
        .diente-label:hover {
            background: rgba(74, 144, 217, 0.1);
            color: var(--color-accent);
        }
        .diente-label.selected-tooth {
            background: var(--color-accent);
            color: #fff;
        }
        .diente-svg {
            width: 48px;
            height: 48px;
            overflow: visible;
        }
        .diente-surface {
            fill: var(--color-surface);
            stroke: var(--color-border);
            stroke-width: 1.5;
            cursor: pointer;
            transition: fill 0.2s ease, stroke 0.2s ease;
        }
        .diente-surface:hover {
            fill: rgba(74, 144, 217, 0.15) !important;
            stroke: var(--color-accent);
        }
        .diente-surface.selected-surface {
            stroke: var(--color-accent);
            stroke-width: 2.5;
        }
        
        /* Overlays especiales */
        .diente-overlay {
            pointer-events: none;
        }
        .diente-overlay.X-line {
            stroke-width: 6;
            stroke-linecap: round;
            opacity: 0.85;
        }
        .diente-overlay.Circle-line {
            fill: none;
            stroke-width: 4;
            opacity: 0.85;
        }
        .diente-overlay.Screw-line {
            fill: none;
            stroke-width: 3.5;
            stroke-linecap: round;
            opacity: 0.9;
        }
        
        /* Animaciones */
        @keyframes saveSuccess {
            0% { filter: drop-shadow(0 0 0px var(--color-success)); stroke-width: 2.5; }
            50% { filter: drop-shadow(0 0 6px var(--color-success)); stroke: var(--color-success); stroke-width: 4; }
            100% { filter: drop-shadow(0 0 0px var(--color-success)); stroke-width: 1.5; }
        }
        .save-flash {
            animation: saveSuccess 1.2s ease-out;
        }

        /* Panel lateral derecho */
        .panel-lateral {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .panel-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border-light);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-md);
        }
        .panel-titulo {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--color-text);
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid var(--color-border-light);
            padding-bottom: 8px;
        }
        .panel-titulo i {
            color: var(--color-accent);
        }
        
        /* Leyenda de Condiciones */
        .condiciones-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            max-height: 320px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .condicion-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border: 1px solid var(--color-border-light);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.85rem;
            font-weight: 500;
        }
        .condicion-item:hover {
            background: var(--color-bg-hover);
            border-color: var(--color-border);
        }
        .condicion-item.active {
            background: rgba(74, 144, 217, 0.08);
            border-color: var(--color-accent);
            color: var(--color-accent);
        }
        .condicion-color-dot {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            border: 1px solid rgba(0,0,0,0.1);
            flex-shrink: 0;
        }
        .condicion-codigo {
            font-family: monospace;
            font-weight: 700;
            background: var(--color-bg);
            padding: 1px 4px;
            border-radius: 4px;
            font-size: 0.75rem;
            width: 24px;
            text-align: center;
            color: var(--color-text-secondary);
        }
        
        /* Detalle del Diente / Superficie Activo */
        .detalle-pieza-box {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .info-diente-seleccionado {
            background: var(--color-bg);
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            border-left: 4px solid var(--color-accent);
            font-size: 0.88rem;
        }
        .info-diente-nombre {
            font-weight: 700;
            color: var(--color-text);
        }
        .info-diente-sub {
            font-size: 0.78rem;
            color: var(--color-text-secondary);
            margin-top: 2px;
        }
        .textarea-observacion {
            width: 100%;
            min-height: 80px;
            padding: 10px;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            color: var(--color-text);
            font-size: 0.88rem;
            resize: vertical;
            outline: none;
            font-family: var(--font-main);
        }
        .textarea-observacion:focus {
            border-color: var(--color-accent);
        }

        /* Toasts */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1500;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .toast-notification {
            background: var(--color-surface);
            border-left: 4px solid var(--color-success);
            color: var(--color-text);
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: var(--shadow-lg);
            font-size: 0.88rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.3s ease forwards;
        }
        .toast-notification.error {
            border-left-color: var(--color-error);
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast-fade-out {
            animation: slideOutRight 0.3s ease forwards;
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        /* Vista de Selección de Paciente */
        .paciente-seleccion-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .pacientes-search-card {
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <?php include '../includes/layout_sidebar.php'; ?>

        <div class="app-content">
            <?php include '../includes/layout_header.php'; ?>

            <main class="main-content">
                <?php echo mostrarAlerta(); ?>

                <?php if ($id_paciente > 0 && $paciente): ?>
                    <!-- VISTA DETALLADA DEL ODONTOGRAMA DEL PACIENTE -->
                    <div class="odontograma-container">
                        <!-- Ficha de resumen del paciente -->
                        <div class="paciente-resumen-card">
                            <div class="paciente-info-primary">
                                <h2><?= htmlspecialchars($paciente['nombres']) ?></h2>
                                <div class="paciente-info-meta">
                                    <span>N° Historia Clínica: <strong><?= htmlspecialchars($paciente['numero_historia']) ?></strong></span>
                                    <?php if ($paciente['dni']): ?>
                                        <span>DNI: <strong><?= htmlspecialchars($paciente['dni']) ?></strong></span>
                                    <?php endif; ?>
                                    <?php if ($paciente['edad']): ?>
                                        <span>Edad: <strong><?= htmlspecialchars($paciente['edad']) ?> años</strong></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <a href="../ver.php?id=<?= $paciente['id'] ?>" class="btn btn-secondary">
                                    <i class="fas fa-file-medical"></i> Ver Ficha Clínica
                                </a>
                            </div>
                        </div>

                        <!-- Contenedor Odontograma Grid -->
                        <div class="odontograma-grid">
                            <!-- Odontograma SVG principal -->
                            <div class="odontograma-card">
                                <div class="odontograma-controls">
                                    <!-- Selector Adulto / Niño -->
                                    <div class="toggle-btn-group">
                                        <button class="toggle-btn active" id="btn-adulto" onclick="setDentadura('adulto')">Adulto</button>
                                        <button class="toggle-btn" id="btn-nino" onclick="setDentadura('nino')">Niño (Deciduo)</button>
                                    </div>

                                    <!-- Historial y Fecha -->
                                    <div class="date-control-group">
                                        <label for="fecha_odontograma" class="form-label" style="margin: 0; font-size: 0.8rem;">Fecha:</label>
                                        <input type="date" id="fecha_odontograma" class="input-date" value="<?= date('Y-m-d') ?>" onchange="cargarOdontogramaDelServidor()">
                                        
                                        <!-- Selector de Historial de Fechas -->
                                        <select id="select_historial" class="select-historial" style="display: none;" onchange="cargarFechaHistorial(this.value)">
                                            <option value="">-- Historial --</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Renderizado de Dentaduras -->
                                <div class="odontograma-dentadura" id="contenedor-dentadura">
                                    <!-- Render Adulto Superior -->
                                    <div class="arcada-row upper" id="arcada-superior">
                                        <div class="midline-indicator"></div>
                                        <?php
                                        $adulto_sup = ['18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28'];
                                        foreach ($adulto_sup as $num): ?>
                                            <div class="diente-box" data-diente="<?= $num ?>">
                                                <span class="diente-label" onclick="seleccionarDienteCompleto('<?= $num ?>')"><?= $num ?></span>
                                                <svg viewBox="0 0 100 100" class="diente-svg">
                                                    <!-- Vestibular (Top) -->
                                                    <polygon points="0,0 100,0 70,30 30,30" class="diente-surface" data-superficie="vestibular" title="Vestibular"></polygon>
                                                    <!-- Mesial / Distal dependiente del lado -->
                                                    <polygon points="0,0 30,30 30,70 0,100" class="diente-surface" data-superficie="mesial" title="Mesial"></polygon>
                                                    <polygon points="100,0 70,30 70,70 100,100" class="diente-surface" data-superficie="distal" title="Distal"></polygon>
                                                    <!-- Lingual / Palatino (Bottom) -->
                                                    <polygon points="0,100 30,70 70,70 100,100" class="diente-surface" data-superficie="lingual" title="Lingual/Palatino"></polygon>
                                                    <!-- Oclusal / Incisal (Center) -->
                                                    <polygon points="30,30 70,30 70,70 30,70" class="diente-surface" data-superficie="oclusal" title="Oclusal/Incisal"></polygon>
                                                    
                                                    <!-- Overlays -->
                                                    <line x1="5" y1="5" x2="95" y2="95" class="diente-overlay X-line" style="display:none;"></line>
                                                    <line x1="95" y1="5" x2="5" y2="95" class="diente-overlay X-line" style="display:none;"></line>
                                                    <circle cx="50" cy="50" r="46" class="diente-overlay Circle-line" style="display:none;"></circle>
                                                    <path d="M 45 20 L 55 20 L 55 80 L 45 80 Z M 40 30 L 60 30 M 40 45 L 60 45 M 40 60 L 60 60" class="diente-overlay Screw-line" style="display:none;"></path>
                                                </svg>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Render Adulto Inferior -->
                                    <div class="arcada-row lower" id="arcada-inferior">
                                        <div class="midline-indicator"></div>
                                        <?php
                                        $adulto_inf = ['48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38'];
                                        foreach ($adulto_inf as $num): ?>
                                            <div class="diente-box" data-diente="<?= $num ?>">
                                                <svg viewBox="0 0 100 100" class="diente-svg">
                                                    <!-- Vestibular (Top) -->
                                                    <polygon points="0,0 100,0 70,30 30,30" class="diente-surface" data-superficie="vestibular" title="Vestibular"></polygon>
                                                    <!-- Mesial / Distal -->
                                                    <polygon points="0,0 30,30 30,70 0,100" class="diente-surface" data-superficie="mesial" title="Mesial"></polygon>
                                                    <polygon points="100,0 70,30 70,70 100,100" class="diente-surface" data-superficie="distal" title="Distal"></polygon>
                                                    <!-- Lingual (Bottom) -->
                                                    <polygon points="0,100 30,70 70,70 100,100" class="diente-surface" data-superficie="lingual" title="Lingual"></polygon>
                                                    <!-- Oclusal / Incisal (Center) -->
                                                    <polygon points="30,30 70,30 70,70 30,70" class="diente-surface" data-superficie="oclusal" title="Oclusal/Incisal"></polygon>
                                                    
                                                    <!-- Overlays -->
                                                    <line x1="5" y1="5" x2="95" y2="95" class="diente-overlay X-line" style="display:none;"></line>
                                                    <line x1="95" y1="5" x2="5" y2="95" class="diente-overlay X-line" style="display:none;"></line>
                                                    <circle cx="50" cy="50" r="46" class="diente-overlay Circle-line" style="display:none;"></circle>
                                                    <path d="M 45 20 L 55 20 L 55 80 L 45 80 Z M 40 30 L 60 30 M 40 45 L 60 45 M 40 60 L 60 60" class="diente-overlay Screw-line" style="display:none;"></path>
                                                </svg>
                                                <span class="diente-label" onclick="seleccionarDienteCompleto('<?= $num ?>')"><?= $num ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel Lateral (Catálogo de condiciones & Ficha de edición rápida) -->
                            <div class="panel-lateral">
                                <!-- Leyenda de condiciones -->
                                <div class="panel-card">
                                    <div class="panel-titulo">
                                        <i class="fas fa-palette"></i> Condiciones Dentales
                                    </div>
                                    <div class="condiciones-list">
                                        <?php foreach ($condiciones as $cond): ?>
                                            <div class="condicion-item" 
                                                 data-id="<?= $cond['id_condicion'] ?>" 
                                                 data-codigo="<?= htmlspecialchars($cond['codigo']) ?>" 
                                                 data-color="<?= htmlspecialchars($cond['color']) ?>"
                                                 data-nombre="<?= htmlspecialchars($cond['nombre']) ?>"
                                                 onclick="seleccionarCondicionActiva(this)">
                                                <div class="condicion-color-dot" style="background-color: <?= htmlspecialchars($cond['color']) ?>;"></div>
                                                <div class="condicion-codigo"><?= htmlspecialchars($cond['codigo']) ?></div>
                                                <span><?= htmlspecialchars($cond['nombre']) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button class="btn btn-secondary" style="width: 100%; margin-top: 12px; font-size: 0.8rem; padding: 8px 12px;" onclick="deseleccionarCondicionActiva()">
                                        <i class="fas fa-mouse-pointer"></i> Modo Inspección / Notas
                                    </button>
                                </div>

                                <!-- Detalle / Edición de la pieza seleccionada -->
                                <div class="panel-card" id="panel-diente-detalle">
                                    <div class="panel-titulo">
                                        <i class="fas fa-info-circle"></i> Detalle de Pieza
                                    </div>
                                    <div class="detalle-pieza-box">
                                        <div class="info-diente-seleccionado">
                                            <div class="info-diente-nombre" id="diente-seleccionado-nombre">Ninguna pieza seleccionada</div>
                                            <div class="info-diente-sub" id="diente-seleccionado-sub">Seleccione un diente o superficie para registrar observaciones.</div>
                                        </div>

                                        <div class="form-group" style="margin-bottom: 10px;">
                                            <label class="form-label" style="font-size: 0.75rem;">Superficie a Editar:</label>
                                            <select class="form-control" id="form-superficie" onchange="actualizarEdicionSuperficie()">
                                                <option value="">Seleccionar...</option>
                                                <option value="completo">Pieza Completa</option>
                                                <option value="vestibular">Vestibular</option>
                                                <option value="oclusal">Oclusal / Incisal</option>
                                                <option value="lingual">Lingual / Palatino</option>
                                                <option value="mesial">Mesial</option>
                                                <option value="distal">Distal</option>
                                            </select>
                                        </div>

                                        <div class="form-group" style="margin-bottom: 10px;">
                                            <label class="form-label" style="font-size: 0.75rem;">Condición Asociada:</label>
                                            <select class="form-control" id="form-condicion" onchange="aplicarCondicionDesdeForm()">
                                                <option value="">Sin condición (Sano)</option>
                                                <?php foreach ($condiciones as $cond): ?>
                                                    <option value="<?= $cond['id_condicion'] ?>" data-color="<?= htmlspecialchars($cond['color']) ?>" data-codigo="<?= htmlspecialchars($cond['codigo']) ?>"><?= htmlspecialchars($cond['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group" style="margin-bottom: 10px;">
                                            <label class="form-label" style="font-size: 0.75rem;">Observaciones / Notas:</label>
                                            <textarea class="textarea-observacion" id="form-observacion" placeholder="Escriba notas sobre el estado o tratamiento de esta pieza..."></textarea>
                                        </div>

                                        <button class="btn btn-primary" style="width: 100%;" onclick="guardarObservacionManual()">
                                            <i class="fas fa-save"></i> Guardar Pieza
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenedor de Toasts -->
                    <div class="toast-container" id="toast-container"></div>

                <?php else: ?>
                    <!-- BÚSQUEDA Y SELECCIÓN DE PACIENTE -->
                    <div class="paciente-seleccion-container">
                        <div class="page-title">
                            <i class="fas fa-tooth"></i> Módulo de Odontograma
                        </div>
                        
                        <div class="card pacientes-search-card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-search"></i> Buscar Paciente</h3>
                            </div>
                            <form method="POST" id="searchForm" class="search-container" onsubmit="event.preventDefault(); buscarPacientesAjax();">
                                <div class="search-select-wrapper">
                                    <select name="tipo_busqueda" id="tipo_busqueda" class="search-select">
                                        <option value="nombre">Nombres / Apellidos</option>
                                        <option value="numero_historia">N° Historia Clínica</option>
                                        <option value="dni">DNI</option>
                                    </select>
                                </div>
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" name="buscar" id="buscar_input" class="search-input" placeholder="Escriba para buscar..." oninput="buscarPacientesAjax();">
                                </div>
                                <button type="submit" class="btn-buscar">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </form>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-users"></i> Listado de Pacientes</h3>
                            </div>
                            <div class="table-container">
                                <table class="table" id="tabla-pacientes">
                                    <thead>
                                        <tr>
                                            <th>N° Historia</th>
                                            <th>Paciente</th>
                                            <th>DNI</th>
                                            <th>Celular</th>
                                            <th style="width: 150px; text-align: center;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-pacientes">
                                        <tr>
                                            <td colspan="5" class="table-empty">Escriba en el buscador para filtrar pacientes.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>

            <?php include '../includes/layout_footer.php'; ?>
        </div>
    </div>

    <!-- SCRIPTING FRONTEND -->
    <?php if ($id_paciente > 0 && $paciente): ?>
    <script>
        // Mapeo anatómico de dientes estándar FDI
        const nombresDientes = {
            // Adulto Superior Derecho
            '18': 'Tercer Molar Superior Derecho',
            '17': 'Segundo Molar Superior Derecho',
            '16': 'Primer Molar Superior Derecho',
            '15': 'Segundo Premolar Superior Derecho',
            '14': 'Primer Premolar Superior Derecho',
            '13': 'Canino Superior Derecho',
            '12': 'Incisivo Lateral Superior Derecho',
            '11': 'Incisivo Central Superior Derecho',
            // Adulto Superior Izquierdo
            '21': 'Incisivo Central Superior Izquierdo',
            '22': 'Incisivo Lateral Superior Izquierdo',
            '23': 'Canino Superior Izquierdo',
            '24': 'Primer Premolar Superior Izquierdo',
            '25': 'Segundo Premolar Superior Izquierdo',
            '26': 'Primer Molar Superior Izquierdo',
            '27': 'Segundo Molar Superior Izquierdo',
            '28': 'Tercer Molar Superior Izquierdo',
            // Adulto Inferior Izquierdo
            '31': 'Incisivo Central Inferior Izquierdo',
            '32': 'Incisivo Lateral Inferior Izquierdo',
            '33': 'Canino Inferior Izquierdo',
            '34': 'Primer Premolar Inferior Izquierdo',
            '35': 'Segundo Premolar Inferior Izquierdo',
            '36': 'Primer Molar Inferior Izquierdo',
            '37': 'Segundo Molar Inferior Izquierdo',
            '38': 'Tercer Molar Inferior Izquierdo',
            // Adulto Inferior Derecho
            '41': 'Incisivo Central Inferior Derecho',
            '42': 'Incisivo Lateral Inferior Derecho',
            '43': 'Canino Inferior Derecho',
            '44': 'Primer Premolar Inferior Derecho',
            '45': 'Segundo Premolar Inferior Derecho',
            '46': 'Primer Molar Inferior Derecho',
            '47': 'Segundo Molar Inferior Derecho',
            '48': 'Tercer Molar Inferior Derecho',
            // Niño Superior Derecho
            '55': 'Segundo Molar Superior Derecho Deciduo',
            '54': 'Primer Molar Superior Derecho Deciduo',
            '53': 'Canino Superior Derecho Deciduo',
            '52': 'Incisivo Lateral Superior Derecho Deciduo',
            '51': 'Incisivo Central Superior Derecho Deciduo',
            // Niño Superior Izquierdo
            '61': 'Incisivo Central Superior Izquierdo Deciduo',
            '62': 'Incisivo Lateral Superior Izquierdo Deciduo',
            '63': 'Canino Superior Izquierdo Deciduo',
            '64': 'Primer Molar Superior Izquierdo Deciduo',
            '65': 'Segundo Molar Superior Izquierdo Deciduo',
            // Niño Inferior Izquierdo
            '71': 'Incisivo Central Inferior Izquierdo Deciduo',
            '72': 'Incisivo Lateral Inferior Izquierdo Deciduo',
            '73': 'Canino Inferior Izquierdo Deciduo',
            '74': 'Primer Molar Inferior Izquierdo Deciduo',
            '75': 'Segundo Molar Inferior Izquierdo Deciduo',
            // Niño Inferior Derecho
            '81': 'Incisivo Central Inferior Derecho Deciduo',
            '82': 'Incisivo Lateral Inferior Derecho Deciduo',
            '83': 'Canino Inferior Derecho Deciduo',
            '84': 'Primer Molar Inferior Derecho Deciduo',
            '85': 'Segundo Molar Inferior Derecho Deciduo'
        };

        // Estado global del odontograma en memoria
        const estadoOdontograma = {}; // llave: "diente_superficie" -> {id_condicion, codigo, color, observacion}
        let condicionActiva = null; // {id_condicion, codigo, color, nombre}
        
        let dienteSeleccionado = null; // string (ej: "11")
        let superficieSeleccionada = null; // string (ej: "oclusal", o "completo")
        
        const idPaciente = <?= $id_paciente ?>;
        
        // Inicialización
        document.addEventListener('DOMContentLoaded', () => {
            setDentadura('adulto');
            cargarHistorialFechas();
            cargarOdontogramaDelServidor();
            
            // Asignar eventos de clic a todas las superficies SVG
            configurarEventosSuperficies();
        });

        // Configuración de eventos en elementos poligonales de dientes
        function configurarEventosSuperficies() {
            document.querySelectorAll('.diente-surface').forEach(el => {
                el.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const box = el.closest('.diente-box');
                    const diente = box.dataset.diente;
                    const superficie = el.dataset.superficie;
                    
                    if (condicionActiva) {
                        // Flujo rápido: aplicar condición seleccionada inmediatamente
                        aplicarCondicionADienteSuperficie(diente, superficie, condicionActiva);
                    } else {
                        // Flujo detallado: seleccionar para inspeccionar/editar notas
                        seleccionarSuperficieInspeccion(diente, superficie);
                    }
                });
            });
        }

        // Selección de condición activa desde el panel lateral
        function seleccionarCondicionActiva(element) {
            document.querySelectorAll('.condicion-item').forEach(item => item.classList.remove('active'));
            element.classList.add('active');
            
            condicionActiva = {
                id_condicion: parseInt(element.dataset.id),
                codigo: element.dataset.codigo,
                color: element.dataset.color,
                nombre: element.dataset.nombre
            };
        }

        // Deseleccionar condición activa (cambiar a modo inspección)
        function deseleccionarCondicionActiva() {
            document.querySelectorAll('.condicion-item').forEach(item => item.classList.remove('active'));
            condicionActiva = null;
            showToast('Modo de inspección y notas activado');
        }

        // Seleccionar diente completo al pulsar la etiqueta/número
        function seleccionarDienteCompleto(diente) {
            if (condicionActiva) {
                // Flujo rápido: aplicar condición al diente completo
                aplicarCondicionADienteSuperficie(diente, 'completo', condicionActiva);
            } else {
                // Flujo de inspección detallado
                seleccionarSuperficieInspeccion(diente, 'completo');
            }
        }

        // Selecciona una pieza/superficie en el panel lateral para inspeccionar
        function seleccionarSuperficieInspeccion(diente, superficie) {
            dienteSeleccionado = diente;
            superficieSeleccionada = superficie;
            
            // Highlight visual del diente seleccionado
            document.querySelectorAll('.diente-label').forEach(el => el.classList.remove('selected-tooth'));
            document.querySelectorAll('.diente-surface').forEach(el => el.classList.remove('selected-surface'));
            
            const box = document.querySelector(`.diente-box[data-diente="${diente}"]`);
            if (box) {
                box.querySelector('.diente-label').classList.add('selected-tooth');
                if (superficie !== 'completo') {
                    const poly = box.querySelector(`.diente-surface[data-superficie="${superficie}"]`);
                    if (poly) poly.classList.add('selected-surface');
                }
            }
            
            // Actualizar información en el panel derecho
            const nombre = nombresDientes[diente] || `Pieza ${diente}`;
            document.getElementById('diente-seleccionado-nombre').innerText = `Diente ${diente}`;
            document.getElementById('diente-seleccionado-sub').innerText = nombre;
            
            // Rellenar selectores del formulario
            document.getElementById('form-superficie').value = superficie;
            
            // Buscar datos del estado actual en memoria
            const key = `${diente}_${superficie}`;
            const estado = estadoOdontograma[key] || estadoOdontograma[`${diente}_completo`] || null;
            
            if (estado) {
                document.getElementById('form-condicion').value = estado.id_condicion;
                document.getElementById('form-observacion').value = estado.observacion || '';
            } else {
                document.getElementById('form-condicion').value = '';
                document.getElementById('form-observacion').value = '';
            }
        }

        // Actualiza el formulario cuando el usuario cambia la superficie manualmente en el select
        function actualizarEdicionSuperficie() {
            if (!dienteSeleccionado) return;
            const sup = document.getElementById('form-superficie').value;
            if (sup) {
                seleccionarSuperficieInspeccion(dienteSeleccionado, sup);
            }
        }

        // Aplica una condición desde el selector manual
        function aplicarCondicionDesdeForm() {
            if (!dienteSeleccionado || !superficieSeleccionada) {
                showToast('Por favor, seleccione una pieza y superficie primero', 'error');
                return;
            }
            const selectCond = document.getElementById('form-condicion');
            const idCond = parseInt(selectCond.value) || 0;
            
            let cond = null;
            if (idCond > 0) {
                const opt = selectCond.options[selectCond.selectedIndex];
                cond = {
                    id_condicion: idCond,
                    codigo: opt.dataset.codigo,
                    color: opt.dataset.color,
                    nombre: opt.text
                };
            } else {
                // "Sano" / Sin condición
                cond = {
                    id_condicion: obtenerIdSano(),
                    codigo: 'SA',
                    color: '#94a3b8',
                    nombre: 'Sano'
                };
            }
            
            aplicarCondicionADienteSuperficie(dienteSeleccionado, superficieSeleccionada, cond, false);
        }

        // Obtiene el ID de la condición "Sano" del catálogo
        function obtenerIdSano() {
            // Buscamos en el DOM de la leyenda el ID de la condición con código 'SA'
            const itemSano = document.querySelector('.condicion-item[data-codigo="SA"]');
            return itemSano ? parseInt(itemSano.dataset.id) : 1;
        }

        // Guarda manualmente la observación ingresada en el textarea
        function guardarObservacionManual() {
            if (!dienteSeleccionado || !superficieSeleccionada) {
                showToast('Seleccione una pieza dental primero', 'error');
                return;
            }
            
            const selectCond = document.getElementById('form-condicion');
            const idCond = parseInt(selectCond.value) || obtenerIdSano();
            const observacion = document.getElementById('form-observacion').value.trim();
            
            // Buscar detalles de la condición
            let cond = null;
            const itemCond = document.querySelector(`.condicion-item[data-id="${idCond}"]`);
            if (itemCond) {
                cond = {
                    id_condicion: idCond,
                    codigo: itemCond.dataset.codigo,
                    color: itemCond.dataset.color,
                    nombre: itemCond.dataset.nombre
                };
            } else {
                cond = {
                    id_condicion: idCond,
                    codigo: 'SA',
                    color: '#94a3b8',
                    nombre: 'Sano'
                };
            }
            
            // Guardar estado con la observación
            guardarEstadoEnServidor(dienteSeleccionado, superficieSeleccionada, cond, observacion);
        }

        // Aplica lógicamente y visualmente una condición a una superficie/diente
        function aplicarCondicionADienteSuperficie(diente, superficie, cond, autoguardar = true) {
            const key = `${diente}_${superficie}`;
            
            // 1. Limpiezas y consistencia lógica
            if (superficie === 'completo') {
                // Si aplicamos al diente completo, eliminamos estados de superficies individuales
                for (let k in estadoOdontograma) {
                    if (k.startsWith(`${diente}_`) && k !== key) {
                        delete estadoOdontograma[k];
                    }
                }
            } else {
                // Si aplicamos a superficie individual, eliminamos estado de diente completo
                delete estadoOdontograma[`${diente}_completo`];
            }
            
            // 2. Guardar en memoria
            estadoOdontograma[key] = {
                id_condicion: cond.id_condicion,
                codigo: cond.codigo,
                color: cond.color,
                observacion: (estadoOdontograma[key] ? estadoOdontograma[key].observacion : '')
            };
            
            // 3. Renderizar cambio en la UI
            dibujarDienteUI(diente);
            
            // 4. Enviar al servidor si está en autoguardado
            if (autoguardar) {
                guardarEstadoEnServidor(diente, superficie, cond, estadoOdontograma[key].observacion);
            }
            
            // Actualizar el formulario si este diente es el actualmente inspeccionado
            if (dienteSeleccionado === diente && superficieSeleccionada === superficie) {
                document.getElementById('form-condicion').value = cond.id_condicion;
            }
        }

        // Envía el estado de un diente al servidor vía AJAX (POST)
        function guardarEstadoEnServidor(diente, superficie, cond, observacion = '') {
            const fecha = document.getElementById('fecha_odontograma').value;
            
            const formData = new URLSearchParams();
            formData.append('id_paciente', idPaciente);
            formData.append('numero_diente', diente);
            formData.append('superficie', superficie);
            formData.append('id_condicion', cond.id_condicion);
            formData.append('fecha_registro', fecha);
            formData.append('observacion', observacion);
            
            fetch('guardar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Flash de éxito visual
                    const box = document.querySelector(`.diente-box[data-diente="${diente}"]`);
                    if (box) {
                        const svg = box.querySelector('.diente-svg');
                        svg.classList.add('save-flash');
                        setTimeout(() => svg.classList.remove('save-flash'), 1200);
                    }
                    
                    // Actualizar memoria local con notas por si se guardaron observaciones
                    const key = `${diente}_${superficie}`;
                    if (estadoOdontograma[key]) {
                        estadoOdontograma[key].observacion = observacion;
                    }
                    
                    showToast(`Pieza ${diente} guardada correctamente`);
                    cargarHistorialFechas(); // Recargar historial de fechas para incluir la fecha actual
                } else {
                    showToast(data.error || 'Error al guardar el estado', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error de conexión con el servidor', 'error');
            });
        }

        // Dibuja visualmente un diente en base al estado local en memoria
        function dibujarDienteUI(diente) {
            const box = document.querySelector(`.diente-box[data-diente="${diente}"]`);
            if (!box) return;
            
            const svg = box.querySelector('.diente-svg');
            const poligonos = svg.querySelectorAll('.diente-surface');
            
            // Restablecer valores iniciales
            poligonos.forEach(p => {
                p.style.fill = ''; // Heredar de variables CSS
                p.style.stroke = '';
            });
            
            // Ocultar overlays
            svg.querySelectorAll('.diente-overlay').forEach(ov => ov.style.display = 'none');
            
            // Verificar si hay condición para diente 'completo'
            const completoKey = `${diente}_completo`;
            const completoEstado = estadoOdontograma[completoKey];
            
            if (completoEstado) {
                const color = completoEstado.color;
                const codigo = completoEstado.codigo;
                
                // Si tiene condición completa
                poligonos.forEach(p => {
                    p.style.fill = color;
                });
                
                // Mostrar overlays gráficos según la patología
                if (codigo === 'AU' || codigo === 'EX') {
                    // Ausente o extracción (Cruz)
                    svg.querySelectorAll('.X-line').forEach(line => {
                        line.style.display = 'block';
                        line.style.stroke = color;
                    });
                } else if (codigo === 'CO' || codigo === 'PR') {
                    // Corona o prótesis (Círculo)
                    const circ = svg.querySelector('.Circle-line');
                    if (circ) {
                        circ.style.display = 'block';
                        circ.style.stroke = color;
                    }
                } else if (codigo === 'IM') {
                    // Implante (Perno/Tornillo)
                    const screw = svg.querySelector('.Screw-line');
                    if (screw) {
                        screw.style.display = 'block';
                        screw.style.stroke = color;
                    }
                }
            } else {
                // Pintar superficies individuales
                const superficies = ['vestibular', 'oclusal', 'lingual', 'mesial', 'distal'];
                superficies.forEach(sup => {
                    const key = `${diente}_${sup}`;
                    const estado = estadoOdontograma[key];
                    if (estado) {
                        const poly = svg.querySelector(`.diente-surface[data-superficie="${sup}"]`);
                        if (poly) {
                            poly.style.fill = estado.color;
                        }
                    }
                });
            }
        }

        // Carga los datos del odontograma desde el servidor
        function cargarOdontogramaDelServidor() {
            const fecha = document.getElementById('fecha_odontograma').value;
            if (!fecha) return;
            
            // Limpiar memoria
            for (let k in estadoOdontograma) delete estadoOdontograma[k];
            
            fetch(`cargar.php?id_paciente=${idPaciente}&fecha=${fecha}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Registrar en memoria
                    data.data.forEach(item => {
                        const key = `${item.numero_diente}_${item.superficie}`;
                        estadoOdontograma[key] = {
                            id_condicion: parseInt(item.id_condicion),
                            codigo: item.codigo,
                            color: item.color,
                            observacion: item.observacion || ''
                        };
                    });
                    
                    // Re-renderizar todos los dientes visibles
                    document.querySelectorAll('.diente-box').forEach(box => {
                        dibujarDienteUI(box.dataset.diente);
                    });
                    
                    // Limpiar inspección
                    dienteSeleccionado = null;
                    superficieSeleccionada = null;
                    document.getElementById('diente-seleccionado-nombre').innerText = 'Ninguna pieza seleccionada';
                    document.getElementById('diente-seleccionado-sub').innerText = 'Seleccione un diente o superficie para registrar observaciones.';
                    document.getElementById('form-superficie').value = '';
                    document.getElementById('form-condicion').value = '';
                    document.getElementById('form-observacion').value = '';
                    
                    document.querySelectorAll('.diente-label').forEach(el => el.classList.remove('selected-tooth'));
                    document.querySelectorAll('.diente-surface').forEach(el => el.classList.remove('selected-surface'));
                } else {
                    showToast(data.error || 'Error al cargar odontograma', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error al conectar con el servidor', 'error');
            });
        }

        // Carga el selector histórico de fechas
        function cargarHistorialFechas() {
            fetch(`historial.php?id_paciente=${idPaciente}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('select_historial');
                    select.innerHTML = '<option value="">-- Historial --</option>';
                    
                    if (data.data.length > 0) {
                        data.data.forEach(fecha => {
                            // Formatear fecha para mostrar d/m/Y
                            const partes = fecha.split('-');
                            const fechaFormato = `${partes[2]}/${partes[1]}/${partes[0]}`;
                            select.innerHTML += `<option value="${fecha}">${fechaFormato}</option>`;
                        });
                        select.style.display = 'inline-block';
                    } else {
                        select.style.display = 'none';
                    }
                }
            })
            .catch(err => console.error('Error al cargar historial', err));
        }

        // Cambia la fecha actual al seleccionar una fecha del historial
        function cargarFechaHistorial(fecha) {
            if (!fecha) return;
            document.getElementById('fecha_odontograma').value = fecha;
            cargarOdontogramaDelServidor();
        }

        // Alterna la visualización de la dentadura entre Adulto y Niño
        function setDentadura(tipo) {
            document.getElementById('btn-adulto').classList.remove('active');
            document.getElementById('btn-nino').classList.remove('active');
            
            const arcadaSuperior = document.getElementById('arcada-superior');
            const arcadaInferior = document.getElementById('arcada-inferior');
            
            if (tipo === 'adulto') {
                document.getElementById('btn-adulto').classList.add('active');
                
                // Renderizar dientes de adulto superior
                arcadaSuperior.innerHTML = '<div class="midline-indicator"></div>';
                const adSup = ['18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28'];
                adSup.forEach(num => {
                    arcadaSuperior.innerHTML += generarHtmlDiente(num, 'upper');
                });
                
                // Renderizar dientes de adulto inferior
                arcadaInferior.innerHTML = '<div class="midline-indicator"></div>';
                const adInf = ['48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38'];
                adInf.forEach(num => {
                    arcadaInferior.innerHTML += generarHtmlDiente(num, 'lower');
                });
            } else {
                document.getElementById('btn-nino').classList.add('active');
                
                // Renderizar dientes de niño superior
                arcadaSuperior.innerHTML = '<div class="midline-indicator"></div>';
                const childSup = ['55','54','53','52','51','61','62','63','64','65'];
                childSup.forEach(num => {
                    arcadaSuperior.innerHTML += generarHtmlDiente(num, 'upper');
                });
                
                // Renderizar dientes de niño inferior
                arcadaInferior.innerHTML = '<div class="midline-indicator"></div>';
                const childInf = ['85','84','83','82','81','71','72','73','74','75'];
                childInf.forEach(num => {
                    arcadaInferior.innerHTML += generarHtmlDiente(num, 'lower');
                });
            }
            
            // Volver a enlazar eventos a los nuevos elementos del DOM
            configurarEventosSuperficies();
            
            // Re-pintar los dientes que tienen estados cargados en memoria
            document.querySelectorAll('.diente-box').forEach(box => {
                dibujarDienteUI(box.dataset.diente);
            });
        }

        // Generador de estructura HTML/SVG para un diente
        function generarHtmlDiente(num, pos) {
            const labelHtml = `<span class="diente-label" onclick="seleccionarDienteCompleto('${num}')">${num}</span>`;
            const svgHtml = `
                <svg viewBox="0 0 100 100" class="diente-svg">
                    <polygon points="0,0 100,0 70,30 30,30" class="diente-surface" data-superficie="vestibular" title="Vestibular"></polygon>
                    <polygon points="0,0 30,30 30,70 0,100" class="diente-surface" data-superficie="mesial" title="Mesial"></polygon>
                    <polygon points="100,0 70,30 70,70 100,100" class="diente-surface" data-superficie="distal" title="Distal"></polygon>
                    <polygon points="0,100 30,70 70,70 100,100" class="diente-surface" data-superficie="lingual" title="Lingual"></polygon>
                    <polygon points="30,30 70,30 70,70 30,70" class="diente-surface" data-superficie="oclusal" title="Oclusal/Incisal"></polygon>
                    
                    <line x1="5" y1="5" x2="95" y2="95" class="diente-overlay X-line" style="display:none;"></line>
                    <line x1="95" y1="5" x2="5" y2="95" class="diente-overlay X-line" style="display:none;"></line>
                    <circle cx="50" cy="50" r="46" class="diente-overlay Circle-line" style="display:none;"></circle>
                    <path d="M 45 20 L 55 20 L 55 80 L 45 80 Z M 40 30 L 60 30 M 40 45 L 60 45 M 40 60 L 60 60" class="diente-overlay Screw-line" style="display:none;"></path>
                </svg>
            `;
            
            return `
                <div class="diente-box" data-diente="${num}">
                    ${pos === 'upper' ? labelHtml + svgHtml : svgHtml + labelHtml}
                </div>
            `;
        }

        // Muestra un Toast de notificación elegante
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type === 'error' ? 'error' : ''}`;
            
            const icon = type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle';
            toast.innerHTML = `<i class="fas ${icon}"></i> <span>${message}</span>`;
            
            container.appendChild(toast);
            
            // Auto remover
            setTimeout(() => {
                toast.classList.add('toast-fade-out');
                setTimeout(() => toast.remove(), 300);
            }, 2500);
        }
    </script>
    <?php else: ?>
    <!-- JS PARA VISTA DE SELECCIÓN DE PACIENTE -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Cargar listado inicial de pacientes al cargar la pantalla
            buscarPacientesAjax();
        });

        function buscarPacientesAjax() {
            const query = document.getElementById('buscar_input').value.trim();
            const type = document.getElementById('tipo_busqueda').value;
            const tbody = document.getElementById('tbody-pacientes');
            
            const formData = new URLSearchParams();
            formData.append('buscar', query);
            formData.append('tipo_busqueda', type);
            formData.append('ver', 'odontograma_select');
            
            // Haremos la petición directamente al dashboard.php del proyecto
            // pero le indicamos una bandera para que nos devuelva el listado en JSON 
            // o usaremos un endpoint ligero. Para respetar las convenciones y no alterar 
            // archivos core, haremos un fetch al mismo index.php pero en modo API:
            fetch(`../dashboard.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            // dashboard.php responde en HTML. Extraeremos las filas de pacientes 
            // parseando el HTML, lo cual es extremadamente seguro, compatible y no altera dashboard.php
            .then(r => r.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const filas = doc.querySelectorAll('.table tbody tr');
                
                tbody.innerHTML = '';
                
                if (filas.length === 0 || (filas.length === 1 && filas[0].classList.contains('table-empty'))) {
                    tbody.innerHTML = '<tr><td colspan="5" class="table-empty">No se encontraron pacientes que coincidan.</td></tr>';
                    return;
                }
                
                let count = 0;
                filas.forEach(fila => {
                    // Extraer los datos de la fila original del dashboard
                    // Formato original de filas en dashboard.php:
                    // td 1: numero_historia
                    // td 2: nombres (e.g. enlace a ver.php)
                    // td 3: DNI
                    // td 4: Celular
                    // td 5: Doctor
                    // td 6: Cita
                    // td 7: Acciones (enlaces ver/editar/archivar)
                    const celdas = fila.querySelectorAll('td');
                    if (celdas.length >= 4) {
                        const numHistoria = celdas[0].innerText.trim();
                        
                        // Obtener el nombre del paciente y su ID del enlace
                        const linkNombre = celdas[1].querySelector('a');
                        const nombre = linkNombre ? linkNombre.innerText.trim() : celdas[1].innerText.trim();
                        
                        // Extraer ID del paciente del href de ver.php?id=X
                        let id = 0;
                        if (linkNombre) {
                            const match = linkNombre.href.match(/[?&]id=(\d+)/);
                            if (match) id = parseInt(match[1]);
                        }
                        
                        // Si no hay enlace de nombre, buscar en las acciones (primer botón de ver suele ser ver.php?id=X)
                        if (id === 0) {
                            const actionLink = celdas[celdas.length - 1].querySelector('a');
                            if (actionLink) {
                                const match = actionLink.href.match(/[?&]id=(\d+)/);
                                if (match) id = parseInt(match[1]);
                            }
                        }
                        
                        const dni = celdas[2].innerText.trim() || '-';
                        const celular = celdas[3].innerText.trim() || '-';
                        
                        if (id > 0) {
                            tbody.innerHTML += `
                                <tr>
                                    <td><strong>${numHistoria}</strong></td>
                                    <td><strong>${nombre}</strong></td>
                                    <td>${dni}</td>
                                    <td>${celular}</td>
                                    <td style="text-align: center;">
                                        <a href="index.php?id_paciente=${id}" class="btn btn-success" style="padding: 6px 12px; font-size: 0.8rem;">
                                            <i class="fas fa-tooth"></i> Odontograma
                                        </a>
                                    </td>
                                </tr>
                            `;
                            count++;
                        }
                    }
                });
                
                if (count === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="table-empty">No se encontraron pacientes activos.</td></tr>';
                }
            })
            .catch(err => {
                console.error(err);
                tbody.innerHTML = '<tr><td colspan="5" class="table-empty" style="color: var(--color-error);"><i class="fas fa-exclamation-triangle"></i> Error al conectar con el listado de pacientes.</td></tr>';
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
