<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
verificarSesion();
require_once '../includes/functions.php';
require_once '../includes/callcenter_functions.php';
require_once '../includes/tenant.php';

$tenant = cargarTenant($pdo);
$basePath = getBasePath();

// Obtener llamadas del día (se asignan automáticamente si no existen)
$llamadas = obtenerLlamadasDelDia($pdo);

// Separar pendientes y pospuestas
$pendientes = array_filter($llamadas, function($l) { return $l['llamada_estado'] === 'pendiente'; });
$pospuestas = array_filter($llamadas, function($l) { return $l['llamada_estado'] === 'pospuesta'; });

// Obtener estadísticas
$stats = obtenerEstadisticasCallCenter($pdo);

$currentPage = 'callcenter';
$pageTitle = 'Call Center';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call Center - <?= htmlspecialchars($tenant['clinic_name']) ?></title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/fontawesome/css/all.min.css">
    <?php renderTenantCssVars($tenant); ?>
    <style>
        .callcenter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 20px;
            background: var(--color-pure-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }

        .callcenter-title {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--color-text);
        }

        .callcenter-subtitle {
            color: var(--color-text-secondary);
            font-size: 0.95rem;
            margin-top: 5px;
        }

        .progress-container {
            margin-bottom: 30px;
            padding: 25px;
            background: var(--color-pure-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            color: var(--color-text);
            font-weight: 600;
        }

        .progress-bar-container {
            width: 100%;
            height: 24px;
            background: var(--color-background);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--color-border);
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #10B981 0%, #34D399 100%);
            border-radius: 12px;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .patient-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .patient-card {
            background: var(--color-pure-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 20px;
            transition: var(--transition-fast);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
        }

        .patient-card:hover {
            border-color: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .patient-card.pospuesta {
            border-color: rgba(245, 158, 11, 0.4);
        }

        .patient-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--color-border);
        }

        .patient-header i {
            color: var(--color-primary);
            font-size: 1.5rem;
        }
        
        .patient-card.pospuesta .patient-header i {
            color: #F59E0B;
        }

        .patient-name {
            font-size: 1.1rem;
            color: var(--color-text);
            font-weight: 600;
        }

        .patient-historia {
            color: var(--color-text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .patient-phone {
            background: rgba(49, 130, 206, 0.05);
            border: 1px dashed var(--color-primary);
            border-radius: var(--radius-md);
            padding: 12px;
            margin: 12px 0 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .phone-label {
            color: var(--color-primary);
            font-size: 1.2rem;
            margin-bottom: 0;
        }

        .phone-number {
            font-size: 1.4rem;
            color: var(--color-primary);
            font-weight: 700;
            letter-spacing: 1px;
        }

        .patient-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .info-item {
            color: var(--color-text);
        }

        .info-label {
            font-weight: 500;
            color: var(--color-text-secondary);
            font-size: 0.85rem;
            margin-bottom: 2px;
        }

        .patient-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
            padding-top: 15px;
        }

        .btn-action {
            flex: 1;
            padding: 10px;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-fast);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-completada {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
        }
        .btn-completada:hover {
            background: #10B981;
            color: white;
        }

        .btn-posponer {
            background: rgba(245, 158, 11, 0.1);
            color: #F59E0B;
        }
        .btn-posponer:hover {
            background: #F59E0B;
            color: white;
        }

        .btn-rechazar {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }
        .btn-rechazar:hover {
            background: #EF4444;
            color: white;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--color-text);
            margin: 30px 0 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--color-primary);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--color-text-light);
            background: var(--color-pure-white);
            border-radius: var(--radius-lg);
            border: 1px dashed var(--color-border);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #10B981;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <?php include '../includes/layout_sidebar.php'; ?>

        <div class="app-content">
            <?php include '../includes/layout_header.php'; ?>

            <main class="main-content">
                
                <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
                    <a href="reporte.php" class="btn btn-secondary">
                        <i class="fas fa-chart-bar"></i> Ver Reportes Call Center
                    </a>
                </div>

                <div class="callcenter-header">
                    <div>
                        <div class="callcenter-title">
                            <i class="fas fa-headset" style="color: var(--color-primary);"></i>
                            Gestión Diaria de Llamadas - <?php echo date('d/m/Y'); ?>
                        </div>
                        <?php $restantes = $stats['pendientes'] + $stats['pospuestas']; ?>
                        <div class="callcenter-subtitle">
                            Llamadas pendientes del día: <strong><?php echo $restantes; ?></strong> de <?php echo $stats['total']; ?>
                        </div>
                        <?php $procesadas = $stats['completadas'] + $stats['rechazadas']; ?>
                    </div>
                </div>

                <!-- Barra de Progreso -->
                <div class="progress-container">
                    <div class="progress-label">
                        <span><i class="fas fa-chart-line"></i> Progreso del Día</span>
                        <span><strong style="color: var(--color-primary);"><?php echo $procesadas; ?></strong> llamadas realizadas de <strong><?php echo $stats['total']; ?></strong></span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo $stats['progreso']; ?>%;">
                            <?php if ($stats['progreso'] > 10): ?>
                                <?php echo $procesadas; ?> / <?php echo $stats['total']; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php echo mostrarAlerta(); ?>

                <!-- Pacientes Pendientes -->
                <?php if (!empty($pendientes)): ?>
                    <h2 class="section-title">
                        <i class="fas fa-users"></i>
                        Pacientes por Llamar (<?php echo count($pendientes); ?>)
                    </h2>
                    <div class="patient-grid">
                        <?php foreach ($pendientes as $paciente): ?>
                            <div class="patient-card" id="patient-card-<?php echo $paciente['llamada_id']; ?>">
                                <div class="patient-header">
                                    <i class="fas fa-user-circle"></i>
                                    <div>
                                        <div class="patient-name"><?php echo htmlspecialchars($paciente['nombres']); ?></div>
                                        <div class="patient-historia">N° Historia: <?php echo htmlspecialchars($paciente['numero_historia']); ?></div>
                                    </div>
                                </div>

                                <div class="patient-phone">
                                    <div class="phone-label"><i class="fas fa-phone-alt"></i></div>
                                    <div class="phone-number"><?php echo htmlspecialchars($paciente['celular']); ?></div>
                                </div>

                                <div class="patient-info">
                                    <div>
                                        <div class="info-label">Género</div>
                                        <div class="info-item"><?php echo htmlspecialchars($paciente['genero'] ?? '-'); ?></div>
                                    </div>
                                    <div>
                                        <div class="info-label">Edad</div>
                                        <div class="info-item"><?php echo htmlspecialchars($paciente['edad'] ?? '-'); ?> años</div>
                                    </div>
                                </div>

                                <div style="margin-bottom: 15px;">
                                    <div class="info-label">Dirección</div>
                                    <div class="info-item"><?php echo htmlspecialchars($paciente['direccion'] ?? 'No especificada'); ?></div>
                                </div>

                                <div class="patient-actions" style="flex-direction: column;">
                                    <button class="btn-action btn-completada" style="width: 100%; margin-bottom: 8px;" onclick="marcarCompletada(<?php echo $paciente['llamada_id']; ?>, '<?php echo htmlspecialchars(addslashes($paciente['nombres'])); ?>')">
                                        <i class="fas fa-check"></i> Completada
                                    </button>
                                    <div style="display: flex; gap: 8px; width: 100%;">
                                        <button class="btn-action btn-posponer" onclick="marcarPospuesta(<?php echo $paciente['llamada_id']; ?>, '<?php echo htmlspecialchars(addslashes($paciente['nombres'])); ?>')">
                                            <i class="fas fa-clock"></i> Después
                                        </button>
                                        <button class="btn-action btn-rechazar" onclick="marcarRechazada(<?php echo $paciente['llamada_id']; ?>, '<?php echo htmlspecialchars(addslashes($paciente['nombres'])); ?>')">
                                            <i class="fas fa-times"></i> Rechazar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h3 style="color: var(--color-text); margin-bottom: 8px;">¡Excelente trabajo!</h3>
                        <p>No hay llamadas pendientes por el momento.</p>
                    </div>
                <?php endif; ?>

                <!-- Llamar Después -->
                <?php if (!empty($pospuestas)): ?>
                    <h2 class="section-title">
                        <i class="fas fa-history" style="color: #F59E0B;"></i>
                        Llamar Después (<?php echo count($pospuestas); ?>)
                    </h2>
                    <div class="patient-grid">
                        <?php foreach ($pospuestas as $paciente): ?>
                            <div class="patient-card pospuesta" id="patient-card-<?php echo $paciente['llamada_id']; ?>">
                                <div class="patient-header">
                                    <i class="fas fa-user-clock"></i>
                                    <div>
                                        <div class="patient-name"><?php echo htmlspecialchars($paciente['nombres']); ?></div>
                                        <div class="patient-historia">N° Historia: <?php echo htmlspecialchars($paciente['numero_historia']); ?></div>
                                    </div>
                                </div>

                                <div class="patient-phone" style="border-color: rgba(245, 158, 11, 0.4); background: rgba(245, 158, 11, 0.05);">
                                    <div class="phone-label" style="color: #F59E0B;"><i class="fas fa-phone-alt"></i></div>
                                    <div class="phone-number" style="color: #F59E0B;"><?php echo htmlspecialchars($paciente['celular']); ?></div>
                                </div>

                                <div class="patient-info">
                                    <div>
                                        <div class="info-label">Género</div>
                                        <div class="info-item"><?php echo htmlspecialchars($paciente['genero'] ?? '-'); ?></div>
                                    </div>
                                    <div>
                                        <div class="info-label">Edad</div>
                                        <div class="info-item"><?php echo htmlspecialchars($paciente['edad'] ?? '-'); ?> años</div>
                                    </div>
                                </div>

                                <div style="margin-bottom: 15px;">
                                    <div class="info-label">Dirección</div>
                                    <div class="info-item"><?php echo htmlspecialchars($paciente['direccion'] ?? 'No especificada'); ?></div>
                                </div>

                                <div class="patient-actions">
                                    <button class="btn-action btn-completada" onclick="marcarCompletada(<?php echo $paciente['llamada_id']; ?>, '<?php echo htmlspecialchars(addslashes($paciente['nombres'])); ?>')">
                                        <i class="fas fa-check"></i> Completada
                                    </button>
                                    <button class="btn-action btn-rechazar" onclick="marcarRechazada(<?php echo $paciente['llamada_id']; ?>, '<?php echo htmlspecialchars(addslashes($paciente['nombres'])); ?>')">
                                        <i class="fas fa-times"></i> Rechazar
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>

            <?php include '../includes/layout_footer.php'; ?>
        </div>
    </div>

    <!-- Modal Completada -->
    <div class="modal-overlay" id="modalCompletada">
        <div class="modal">
            <div class="modal-icon" style="color: #10B981;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="modal-title">¿Desea agendar una cita?</h3>
            <p class="modal-text" id="textoCompletada"></p>
            <div style="margin: 20px 0;">
                <label class="form-label" style="text-align: left; display: block; margin-bottom: 10px;">Fecha de la cita (opcional):</label>
                <input type="date" id="fechaCita" class="form-control" min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalCompletada()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="confirmarCompletada()">
                    <i class="fas fa-check"></i> Confirmar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Rechazada -->
    <div class="modal-overlay" id="modalRechazada">
        <div class="modal">
            <div class="modal-icon" style="color: #EF4444;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="modal-title">¿Rechazar esta llamada?</h3>
            <p class="modal-text" id="textoRechazada"></p>
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalRechazada()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn" style="background: #EF4444; color: white; border-color: #EF4444;" onclick="confirmarRechazada()">
                    <i class="fas fa-check"></i> Confirmar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Pospuesta -->
    <div class="modal-overlay" id="modalPospuesta">
        <div class="modal">
            <div class="modal-icon" style="color: #F59E0B;">
                <i class="fas fa-clock"></i>
            </div>
            <h3 class="modal-title">¿Llamar luego?</h3>
            <p class="modal-text" id="textoPospuesta"></p>
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalPospuesta()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn" style="background: #F59E0B; color: white; border-color: #F59E0B;" onclick="confirmarPospuesta()">
                    <i class="fas fa-check"></i> Aceptar
                </button>
            </div>
        </div>
    </div>

    <script>
        let llamadaActual = null;
        let pacienteActual = '';

        function marcarCompletada(llamadaId, nombrePaciente) {
            llamadaActual = llamadaId;
            pacienteActual = nombrePaciente;
            document.getElementById('textoCompletada').innerHTML = 
                'Se marcará como completada la llamada de <strong>' + nombrePaciente + '</strong>.<br>Si desea, puede agendar una cita.';
            document.getElementById('modalCompletada').classList.add('active');
        }

        function cerrarModalCompletada() {
            document.getElementById('modalCompletada').classList.remove('active');
            document.getElementById('fechaCita').value = '';
            llamadaActual = null;
        }

        function confirmarCompletada() {
            const fechaCita = document.getElementById('fechaCita').value;
            const agendarCita = fechaCita ? 1 : 0;

            fetch('procesar_llamada.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `accion=completada&llamada_id=${llamadaActual}&agendar_cita=${agendarCita}&fecha_cita=${fechaCita}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la llamada');
            });
        }

        function marcarPospuesta(llamadaId, nombrePaciente) {
            llamadaActual = llamadaId;
            pacienteActual = nombrePaciente;
            document.getElementById('textoPospuesta').innerHTML = 
                '¿Deseas llamar luego al paciente <strong>' + nombrePaciente + '</strong>?';
            document.getElementById('modalPospuesta').classList.add('active');
        }

        function cerrarModalPospuesta() {
            document.getElementById('modalPospuesta').classList.remove('active');
            llamadaActual = null;
        }

        function confirmarPospuesta() {
            fetch('procesar_llamada.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `accion=posponer&llamada_id=${llamadaActual}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la llamada');
            });
        }

        function marcarRechazada(llamadaId, nombrePaciente) {
            llamadaActual = llamadaId;
            pacienteActual = nombrePaciente;
            document.getElementById('textoRechazada').innerHTML = 
                '¿Está seguro que desea rechazar la llamada de <strong>' + nombrePaciente + '</strong>?<br>Esta acción marcará que el paciente no está interesado.';
            document.getElementById('modalRechazada').classList.add('active');
        }

        function cerrarModalRechazada() {
            document.getElementById('modalRechazada').classList.remove('active');
            llamadaActual = null;
        }

        function confirmarRechazada() {
            fetch('procesar_llamada.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `accion=rechazar&llamada_id=${llamadaActual}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la llamada');
            });
        }

        // Eventos para modales
        document.getElementById('modalCompletada').addEventListener('click', function(e) {
            if (e.target === this) cerrarModalCompletada();
        });
        document.getElementById('modalRechazada').addEventListener('click', function(e) {
            if (e.target === this) cerrarModalRechazada();
        });
        document.getElementById('modalPospuesta').addEventListener('click', function(e) {
            if (e.target === this) cerrarModalPospuesta();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalCompletada();
                cerrarModalRechazada();
                cerrarModalPospuesta();
            }
        });
    </script>
</body>
</html>
