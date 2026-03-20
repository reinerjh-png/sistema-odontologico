<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
verificarSesion();
requiereAdmin();
require_once '../includes/functions.php';
require_once '../includes/tenant.php';

$tenant = cargarTenant($pdo);
$basePath = getBasePath();

// Procesar acciones de usuarios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'agregar') {
        $usuario = sanitizar($_POST['usuario'] ?? '');
        $nombre = sanitizar($_POST['nombre'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol = $_POST['rol'] ?? 'recepcionista';
        
        $errores = [];
        if (empty($usuario)) $errores[] = 'El usuario es obligatorio';
        if (empty($nombre)) $errores[] = 'El nombre es obligatorio';
        if (empty($password) || strlen($password) < 4) $errores[] = 'La contraseña debe tener al menos 4 caracteres';
        if (!in_array($rol, ['admin', 'recepcionista'])) $errores[] = 'Rol inválido';
        
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $check->execute([$usuario]);
        if ($check->fetch()) $errores[] = 'El nombre de usuario ya existe';
        
        if (empty($errores)) {
            $hash = hash('sha256', $password);
            $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password_hash, nombre_completo, rol, estado) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$usuario, $hash, $nombre, $rol]);
            registrarActividad($pdo, 'Agregar Usuario', 'Agregó al usuario: ' . $usuario . ' (' . $nombre . ') con rol ' . $rol);
            setMensaje('Usuario creado exitosamente', 'success');
        } else {
            setMensaje(implode('. ', $errores), 'error');
        }
    }
    
    if ($accion === 'activar') {
        $id = intval($_POST['user_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE usuarios SET estado = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $u = $pdo->prepare("SELECT usuario FROM usuarios WHERE id = ?");
        $u->execute([$id]);
        $uData = $u->fetch();
        registrarActividad($pdo, 'Activar Usuario', 'Activó al usuario: ' . ($uData['usuario'] ?? 'ID ' . $id));
        setMensaje('Usuario activado exitosamente', 'success');
    }
    
    if ($accion === 'desactivar') {
        $id = intval($_POST['user_id'] ?? 0);
        if ($id == $_SESSION['usuario_id']) {
            setMensaje('No puede desactivar su propio usuario', 'error');
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET estado = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $u = $pdo->prepare("SELECT usuario FROM usuarios WHERE id = ?");
            $u->execute([$id]);
            $uData = $u->fetch();
            registrarActividad($pdo, 'Desactivar Usuario', 'Desactivó al usuario: ' . ($uData['usuario'] ?? 'ID ' . $id));
            setMensaje('Usuario desactivado exitosamente', 'success');
        }
    }
    
    if ($accion === 'eliminar') {
        $id = intval($_POST['user_id'] ?? 0);
        if ($id == $_SESSION['usuario_id']) {
            setMensaje('No puede eliminar su propio usuario', 'error');
        } else {
            $u = $pdo->prepare("SELECT usuario, nombre_completo FROM usuarios WHERE id = ?");
            $u->execute([$id]);
            $uData = $u->fetch();
            $pdo->prepare("DELETE FROM actividad_log WHERE usuario_id = ?")->execute([$id]);
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            registrarActividad($pdo, 'Eliminar Usuario', 'Eliminó al usuario: ' . ($uData['usuario'] ?? 'ID ' . $id) . ' (' . ($uData['nombre_completo'] ?? '') . ')');
            setMensaje('Usuario eliminado exitosamente', 'success');
        }
    }
    
    if ($accion === 'editar_usuario') {
        $id = intval($_POST['user_id'] ?? 0);
        $nombre = sanitizar($_POST['nombre'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        
        $errores = [];
        if (empty($nombre)) $errores[] = 'El nombre es obligatorio';
        
        if (empty($errores)) {
            $u = $pdo->prepare("SELECT usuario FROM usuarios WHERE id = ?");
            $u->execute([$id]);
            $uData = $u->fetch();
            $usuarioTexto = $uData['usuario'] ?? 'ID ' . $id;

            if (!empty($newPassword)) {
                if (strlen($newPassword) < 4) {
                    setMensaje('La contraseña debe tener al menos 4 caracteres', 'error');
                } else {
                    $hash = hash('sha256', $newPassword);
                    $stmt = $pdo->prepare("UPDATE usuarios SET nombre_completo = ?, password_hash = ? WHERE id = ?");
                    $stmt->execute([$nombre, $hash, $id]);
                    registrarActividad($pdo, 'Editar Usuario', 'Actualizó nombre y contraseña del usuario: ' . $usuarioTexto);
                    setMensaje('Usuario y contraseña actualizados exitosamente', 'success');
                }
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre_completo = ? WHERE id = ?");
                $stmt->execute([$nombre, $id]);
                registrarActividad($pdo, 'Editar Usuario', 'Actualizó nombre del usuario: ' . $usuarioTexto);
                setMensaje('Nombre de usuario actualizado exitosamente', 'success');
            }
        } else {
            setMensaje(implode('. ', $errores), 'error');
        }
    }
    
    header('Location: usuarios.php');
    exit;
}

$usuarios = $pdo->query("SELECT u.*, 
    (SELECT COUNT(*) FROM actividad_log WHERE usuario_id = u.id) as total_acciones,
    (SELECT MAX(created_at) FROM actividad_log WHERE usuario_id = u.id) as ultima_actividad
    FROM usuarios u ORDER BY u.estado DESC, u.rol ASC, u.nombre_completo ASC")->fetchAll();

$currentPage = 'admin';
$pageTitle = 'Gestión de Usuarios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?= htmlspecialchars($tenant['clinic_name']) ?></title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/fontawesome/css/all.min.css">
    <?php renderTenantCssVars($tenant); ?>
    <style>
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-container input {
            padding-right: 40px !important;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            color: var(--color-text-light);
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
        }
        .toggle-password:hover { color: var(--color-primary); }
    </style>
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
                        <h2 class="card-title"><i class="fas fa-user-plus"></i> Agregar Nuevo Usuario</h2>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="accion" value="agregar">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Usuario <span class="required">*</span></label>
                                <input type="text" name="usuario" class="form-control" placeholder="Ej: recepcion1" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nombre completo <span class="required">*</span></label>
                                <input type="text" name="nombre" class="form-control" placeholder="Ej: María López" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contraseña <span class="required">*</span></label>
                                <div class="password-container">
                                    <input type="password" name="password" id="inputPasswordNueva" class="form-control" placeholder="Mín. 4 caracteres" required minlength="4">
                                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('inputPasswordNueva', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rol</label>
                                <select name="rol" class="form-control">
                                    <option value="recepcionista">Recepcionista</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                            <div class="form-group full-width" style="text-align: right;">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Crear Usuario</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <h2 class="card-title"><i class="fas fa-users-cog"></i> Usuarios del Sistema</h2>
                            <span class="text-gray" style="font-size: 0.85rem;"><?php echo count($usuarios); ?> usuario(s)</span>
                        </div>
                        <a href="index.php" class="btn-nav btn-nav-secondary" style="padding: 6px 12px; font-size: 0.85rem;"><i class="fas fa-arrow-left"></i> Volver a Admin</a>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Estado</th><th>Acciones Log</th><th>Última Actividad</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usr): ?>
                                    <tr style="<?php echo $usr['estado'] == 0 ? 'opacity: 0.6;' : ''; ?>">
                                        <td><strong class="text-gray"><?php echo $usr['id']; ?></strong></td>
                                        <td><strong><?php echo htmlspecialchars($usr['usuario']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($usr['nombre_completo']); ?></td>
                                        <td>
                                            <?php if ($usr['rol'] === 'admin'): ?>
                                                <span class="badge" style="background: rgba(49,130,206,0.1); color: var(--color-primary);"><i class="fas fa-shield-alt"></i> Admin</span>
                                            <?php else: ?>
                                                <span class="badge" style="background: rgba(56,161,105,0.1); color: var(--color-success);"><i class="fas fa-user"></i> Recep</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($usr['estado'] == 1): ?>
                                                <span class="text-success" style="font-weight: 600;"><i class="fas fa-check-circle"></i> Activo</span>
                                            <?php else: ?>
                                                <span class="text-error" style="font-weight: 600;"><i class="fas fa-times-circle"></i> Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge badge-tratamiento"><?php echo $usr['total_acciones']; ?></span></td>
                                        <td style="font-size: 0.85rem; color: var(--color-text-secondary);">
                                            <?php echo $usr['ultima_actividad'] ? date('d/m/Y H:i', strtotime($usr['ultima_actividad'])) : 'Sin actividad'; ?>
                                        </td>
                                        <td>
                                            <div class="acciones">
                                                <button type="button" class="btn-accion" style="background: rgba(49,130,206,0.1); color: var(--color-primary);" title="Editar usuario" 
                                                        onclick="abrirModalEditar(<?php echo $usr['id']; ?>, '<?php echo htmlspecialchars($usr['usuario']); ?>', '<?php echo htmlspecialchars(addslashes($usr['nombre_completo'])); ?>')">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <?php if ($usr['id'] != $_SESSION['usuario_id']): ?>
                                                    <?php if ($usr['estado'] == 1): ?>
                                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Desactivar al usuario <?php echo htmlspecialchars($usr['usuario']); ?>?');">
                                                            <input type="hidden" name="accion" value="desactivar">
                                                            <input type="hidden" name="user_id" value="<?php echo $usr['id']; ?>">
                                                            <button type="submit" class="btn-accion" style="background: rgba(221,107,32,0.1); color: #DD6B20;" title="Desactivar"><i class="fas fa-ban"></i></button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="accion" value="activar">
                                                            <input type="hidden" name="user_id" value="<?php echo $usr['id']; ?>">
                                                            <button type="submit" class="btn-accion" style="background: rgba(56,161,105,0.1); color: var(--color-success);" title="Activar"><i class="fas fa-check"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿ELIMINAR permanentemente al usuario <?php echo htmlspecialchars($usr['usuario']); ?>?');">
                                                        <input type="hidden" name="accion" value="eliminar">
                                                        <input type="hidden" name="user_id" value="<?php echo $usr['id']; ?>">
                                                        <button type="submit" class="btn-accion btn-eliminar" title="Eliminar"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="color: var(--color-text-light); font-size: 0.75rem; font-style: italic;">(Tú)</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <?php include '../includes/layout_footer.php'; ?>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div class="modal-overlay" id="modalEditar">
        <div class="modal">
            <h3 class="modal-title"><i class="fas fa-user-edit"></i> Editar Usuario</h3>
            <p style="color: var(--color-text-secondary); margin-bottom: 20px; font-size: 0.95rem;">Usuario: <strong id="modalEditarUser" style="color: var(--color-text);"></strong></p>
            
            <form method="POST">
                <input type="hidden" name="accion" value="editar_usuario">
                <input type="hidden" name="user_id" id="modalEditarId">
                
                <div class="form-group">
                    <label class="form-label">Nombre completo <span class="required">*</span></label>
                    <input type="text" name="nombre" id="modalEditarNombre" class="form-control" required>
                </div>
                
                <div class="form-group" style="margin-top: 15px;">
                    <label class="form-label">Nueva Contraseña <span style="font-weight: normal; color: var(--color-text-light);">(Dejar en blanco para no cambiar)</span></label>
                    <div class="password-container">
                        <input type="password" name="new_password" id="inputPasswordEditar" class="form-control">
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('inputPasswordEditar', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="modal-buttons" style="margin-top: 25px;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalEditar()"><i class="fas fa-times"></i> Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function abrirModalEditar(id, usuario, nombre) {
            document.getElementById('modalEditarId').value = id;
            document.getElementById('modalEditarUser').textContent = usuario;
            document.getElementById('modalEditarNombre').value = nombre;
            
            let passwordInput = document.getElementById('inputPasswordEditar');
            let iconBtn = passwordInput.nextElementSibling;
            if (passwordInput.type === 'text') {
                passwordInput.type = 'password';
                iconBtn.innerHTML = '<i class="fas fa-eye"></i>';
            }
            passwordInput.value = '';
            
            document.getElementById('modalEditar').classList.add('active');
        }
        function cerrarModalEditar() {
            document.getElementById('modalEditar').classList.remove('active');
        }
        document.getElementById('modalEditar').addEventListener('click', function(e) {
            if (e.target === this) cerrarModalEditar();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') cerrarModalEditar();
        });
        
        function togglePasswordVisibility(inputId, btnElement) {
            const input = document.getElementById(inputId);
            const icon = btnElement.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
