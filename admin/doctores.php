<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
verificarSesion();
requiereAdmin();
require_once '../includes/functions.php';
require_once '../includes/tenant.php';

$tenant = cargarTenant($pdo);
$basePath = getBasePath();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'agregar') {
        $nombre = sanitizar($_POST['nombre'] ?? '');
        $especialidad = sanitizar($_POST['especialidad'] ?? 'Odontología General');
        
        if (!empty($nombre)) {
            $stmt = $pdo->prepare("INSERT INTO doctores (nombre, especialidad, estado) VALUES (?, ?, 1)");
            $stmt->execute([$nombre, $especialidad]);
            registrarActividad($pdo, 'Agregar Doctor', 'Agregó al doctor: ' . $nombre);
            setMensaje('Doctor agregado exitosamente', 'success');
        } else {
            setMensaje('El nombre del doctor es obligatorio', 'error');
        }
    }
    
    if ($accion === 'activar') {
        $id = intval($_POST['doctor_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE doctores SET estado = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $pdo->prepare("SELECT nombre FROM doctores WHERE id = ?");
        $doc->execute([$id]);
        $docData = $doc->fetch();
        registrarActividad($pdo, 'Activar Doctor', 'Activó al doctor: ' . ($docData['nombre'] ?? 'ID ' . $id));
        setMensaje('Doctor activado exitosamente', 'success');
    }
    
    if ($accion === 'desactivar') {
        $id = intval($_POST['doctor_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE doctores SET estado = 0 WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $pdo->prepare("SELECT nombre FROM doctores WHERE id = ?");
        $doc->execute([$id]);
        $docData = $doc->fetch();
        registrarActividad($pdo, 'Desactivar Doctor', 'Desactivó al doctor: ' . ($docData['nombre'] ?? 'ID ' . $id));
        setMensaje('Doctor desactivado exitosamente', 'success');
    }
    
    if ($accion === 'eliminar') {
        $id = intval($_POST['doctor_id'] ?? 0);
        $check = $pdo->prepare("SELECT COUNT(*) FROM pacientes WHERE doctor_id = ?");
        $check->execute([$id]);
        $pacientesAsignados = $check->fetchColumn();
        
        if ($pacientesAsignados > 0) {
            setMensaje('No se puede eliminar: el doctor tiene ' . $pacientesAsignados . ' paciente(s) asignado(s). Desasigne los pacientes primero o desactive al doctor.', 'error');
        } else {
            $doc = $pdo->prepare("SELECT nombre FROM doctores WHERE id = ?");
            $doc->execute([$id]);
            $docData = $doc->fetch();
            $stmt = $pdo->prepare("DELETE FROM doctores WHERE id = ?");
            $stmt->execute([$id]);
            registrarActividad($pdo, 'Eliminar Doctor', 'Eliminó al doctor: ' . ($docData['nombre'] ?? 'ID ' . $id));
            setMensaje('Doctor eliminado exitosamente', 'success');
        }
    }
    
    header('Location: doctores.php');
    exit;
}

$doctores = $pdo->query("SELECT d.*, 
    (SELECT COUNT(*) FROM pacientes WHERE doctor_id = d.id AND estado = 1) as total_pacientes
    FROM doctores d ORDER BY d.estado DESC, d.nombre ASC")->fetchAll();

$currentPage = 'admin';
$pageTitle = 'Gestión de Doctores';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Doctores - <?= htmlspecialchars($tenant['clinic_name']) ?></title>
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
                <?php echo mostrarAlerta(); ?>

                <div class="card form-container">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-plus-circle"></i> Agregar Nuevo Doctor</h2>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="accion" value="agregar">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Nombre completo <span class="required">*</span></label>
                                <input type="text" name="nombre" class="form-control" placeholder="Ej: Fernando Uchuya" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Especialidad</label>
                                <input type="text" name="especialidad" class="form-control" placeholder="Odontología General" value="Odontología General">
                            </div>
                            <div class="form-group" style="display: flex; align-items: flex-end;">
                                <button type="submit" class="btn btn-primary" style="height: 48px; width: auto;"><i class="fas fa-plus"></i> Agregar Doctor</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <h2 class="card-title"><i class="fas fa-user-md"></i> Listado de Doctores</h2>
                            <span class="text-gray" style="font-size: 0.85rem;"><?php echo count($doctores); ?> doctor(es)</span>
                        </div>
                        <a href="index.php" class="btn-nav btn-nav-secondary" style="padding: 6px 12px; font-size: 0.85rem;"><i class="fas fa-arrow-left"></i> Volver a Admin</a>
                    </div>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr><th>ID</th><th>Nombre</th><th>Especialidad</th><th>Pacientes</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($doctores)): ?>
                                    <tr><td colspan="6" class="table-empty">No hay doctores registrados</td></tr>
                                <?php else: ?>
                                    <?php foreach ($doctores as $doc): ?>
                                        <tr style="<?php echo $doc['estado'] == 0 ? 'opacity: 0.6;' : ''; ?>">
                                            <td><strong class="text-gray"><?php echo $doc['id']; ?></strong></td>
                                            <td><strong><?php echo htmlspecialchars($doc['nombre']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($doc['especialidad']); ?></td>
                                            <td><span class="badge badge-tratamiento"><?php echo $doc['total_pacientes']; ?></span></td>
                                            <td>
                                                <?php if ($doc['estado'] == 1): ?>
                                                    <span class="text-success" style="font-weight: 600;"><i class="fas fa-check-circle"></i> Activo</span>
                                                <?php else: ?>
                                                    <span class="text-error" style="font-weight: 600;"><i class="fas fa-times-circle"></i> Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="acciones">
                                                    <?php if ($doc['estado'] == 1): ?>
                                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Desactivar a Dr. <?php echo htmlspecialchars($doc['nombre']); ?>?');">
                                                            <input type="hidden" name="accion" value="desactivar">
                                                            <input type="hidden" name="doctor_id" value="<?php echo $doc['id']; ?>">
                                                            <button type="submit" class="btn-accion" style="background: rgba(221,107,32,0.1); color: #DD6B20;" title="Desactivar"><i class="fas fa-ban"></i></button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="accion" value="activar">
                                                            <input type="hidden" name="doctor_id" value="<?php echo $doc['id']; ?>">
                                                            <button type="submit" class="btn-accion" style="background: rgba(56,161,105,0.1); color: var(--color-success);" title="Activar"><i class="fas fa-check"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿ELIMINAR permanentemente a Dr. <?php echo htmlspecialchars($doc['nombre']); ?>? Esta acción no se puede deshacer.');">
                                                        <input type="hidden" name="accion" value="eliminar">
                                                        <input type="hidden" name="doctor_id" value="<?php echo $doc['id']; ?>">
                                                        <button type="submit" class="btn-accion btn-eliminar" title="Eliminar"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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
