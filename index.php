<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/tenant.php';

$tenant = cargarTenant($pdo);

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Procesar formulario de login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $resultado = intentarLogin($pdo, $usuario, $password);
    
    if ($resultado['success']) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = $resultado['message'];
    }
}

// Verificar si está bloqueado (para deshabilitar el formulario)
$estaBloqueado = isset($_SESSION['login_bloqueado_hasta']) && ($_SESSION['login_bloqueado_hasta'] - time()) > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tenant['clinic_name']) ?> — Iniciar Sesión</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/fontawesome/css/all.min.css">
    <?php 
        // Forzar modo claro solo en el login para evitar problemas de contraste (ej. autocompletado)
        $tenant_login = $tenant;
        $tenant_login['theme_mode'] = 'light';
        renderTenantCssVars($tenant_login); 
    ?>
    <style>
        /* ── Full-page split login ── */
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 50%, var(--color-accent) 100%);
            font-family: var(--font-main);
            padding: 24px;
        }

        /* ── Contenedor split (tarjeta grande) ── */
        .login-container {
            display: flex;
            width: 100%;
            max-width: 960px;
            min-height: 560px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.35);
            animation: cardAppear 0.7s cubic-bezier(0.16,1,0.3,1) both;
        }

        @keyframes cardAppear {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ── PANEL IZQUIERDO: Bienvenida ── */
        .login-welcome {
            flex: 0 0 45%;
            background: linear-gradient(160deg, var(--color-secondary) 0%, var(--color-primary) 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px 40px;
            color: #fff;
            overflow: hidden;
        }

        /* Círculos decorativos */
        .circle-deco {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
        }

        .circle-deco-1 {
            width: 280px;
            height: 280px;
            top: -60px;
            right: -80px;
            background: rgba(255,255,255,0.08);
        }

        .circle-deco-2 {
            width: 200px;
            height: 200px;
            bottom: -40px;
            left: -50px;
            background: rgba(255,255,255,0.06);
        }

        .circle-deco-3 {
            width: 120px;
            height: 120px;
            top: 40%;
            right: 20px;
            background: rgba(255,255,255,0.05);
        }

        /* Logo en el panel */
        .welcome-logo {
            width: 110px;
            height: 110px;
            object-fit: contain;
            border-radius: 16px;
            background: rgba(255,255,255,0.12);
            padding: 10px;
            margin: 0 auto 28px;
            position: relative;
            z-index: 2;
            display: block;
        }

        .welcome-title {
            font-size: 2.4rem;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 8px;
            position: relative;
            z-index: 2;
            line-height: 1.1;
            font-style: italic;
        }

        .welcome-subtitle {
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.85);
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        .welcome-desc {
            font-size: 0.88rem;
            line-height: 1.7;
            color: rgba(255,255,255,0.7);
            position: relative;
            z-index: 2;
            max-width: 280px;
        }

        .welcome-sede {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            padding: 6px 16px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.9);
            position: relative;
            z-index: 2;
        }

        /* Estrella decorativa */
        .deco-star {
            position: absolute;
            bottom: 24px;
            right: 24px;
            font-size: 1.4rem;
            color: rgba(255,255,255,0.15);
            z-index: 2;
        }

        /* ── PANEL DERECHO: Formulario ── */
        .login-form-panel {
            flex: 1;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px 44px;
            position: relative;
        }

        .form-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--color-text);
            margin-bottom: 6px;
        }

        .form-subtitle {
            font-size: 0.88rem;
            color: var(--color-text-secondary);
            margin-bottom: 28px;
            line-height: 1.5;
        }

        /* ── Campos del formulario ── */
        .login-form {
            text-align: left;
        }

        .input-group {
            position: relative;
            margin-bottom: 18px;
        }

        .input-group .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-light);
            font-size: 0.95rem;
            z-index: 2;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-bg);
            border-radius: 50%;
            padding: 12px;
        }

        .login-input {
            width: 100%;
            padding: 14px 48px 14px 52px;
            background: var(--color-bg);
            border: 1.5px solid var(--color-border);
            border-radius: 10px;
            color: var(--color-text);
            font-size: 0.92rem;
            font-family: var(--font-main);
            outline: none;
            transition: all 0.25s ease;
            box-sizing: border-box;
        }

        .login-input::placeholder {
            color: var(--color-text-light);
        }

        .login-input:focus {
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3px rgba(74,144,217,0.12);
            background: #fff;
        }

        .login-input.input-error {
            border-color: var(--color-error) !important;
            box-shadow: 0 0 0 3px rgba(229,62,62,0.12) !important;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--color-text-light);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 4px;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: var(--color-accent);
        }

        /* ── Recordar / Olvidé ── */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            font-size: 0.84rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--color-text-secondary);
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--color-accent);
            cursor: pointer;
        }

        /* ── Botón Sign In ── */
        .btn-signin {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: var(--font-main);
            position: relative;
            overflow: hidden;
        }

        .btn-signin::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s ease;
        }

        .btn-signin:hover::before {
            left: 100%;
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(27, 43, 75, 0.35);
        }

        .btn-signin:active {
            transform: translateY(0);
        }

        .btn-signin:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-signin:disabled::before {
            display: none;
        }

        /* ── Separador ── */
        .form-divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 22px 0;
            color: var(--color-text-light);
            font-size: 0.82rem;
        }

        .form-divider::before,
        .form-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--color-border);
        }

        /* ── Footer del formulario ── */
        .form-footer {
            text-align: center;
            font-size: 0.85rem;
            color: var(--color-text-secondary);
            margin-top: 20px;
        }

        .form-footer-dev {
            text-align: center;
            font-size: 0.75rem;
            color: var(--color-text-light);
            margin-top: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .form-footer-dev span {
            color: var(--color-text-secondary);
            font-weight: 600;
        }

        /* ── Error toast ── */
        .login-error-msg {
            background: rgba(229, 62, 62, 0.08);
            border: 1.5px solid rgba(229, 62, 62, 0.3);
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 18px;
            color: var(--color-error);
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shakeError 0.5s ease;
        }

        .login-error-msg i {
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        @keyframes shakeError {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }

        /* ── Slogan ── */
        .login-slogan {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 16px;
            color: var(--color-text-light);
            font-size: 0.82rem;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .login-page { padding: 0; }

            .login-container {
                flex-direction: column;
                max-width: 100%;
                min-height: 100vh;
                border-radius: 0;
            }

            .login-welcome {
                flex: 0 0 auto;
                padding: 36px 28px 28px;
                min-height: auto;
            }

            .welcome-title { font-size: 1.8rem; }

            .circle-deco-1 { width: 180px; height: 180px; top: -40px; right: -50px; }
            .circle-deco-2 { width: 120px; height: 120px; bottom: -30px; left: -30px; }
            .circle-deco-3 { display: none; }

            .login-form-panel {
                padding: 32px 24px 40px;
            }

            .form-title { font-size: 1.4rem; }
        }

        @media (max-width: 480px) {
            .welcome-logo { width: 50px; height: 50px; margin-bottom: 16px; }
            .welcome-title { font-size: 1.5rem; letter-spacing: 2px; }
            .welcome-subtitle { font-size: 0.82rem; }
            .welcome-desc { display: none; }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-container">

            <!-- ═══ PANEL IZQUIERDO: Bienvenida ═══ -->
            <div class="login-welcome">
                <!-- Círculos decorativos -->
                <div class="circle-deco circle-deco-1"></div>
                <div class="circle-deco circle-deco-2"></div>
                <div class="circle-deco circle-deco-3"></div>

                <!-- Logo -->
                <img src="<?= htmlspecialchars($tenant['logo_url']) ?>" alt="<?= htmlspecialchars($tenant['clinic_name']) ?>" class="welcome-logo"
                     onerror="this.style.display='none'">

                <!-- Textos -->
                <div style="text-align: center;">
                    <h2 class="welcome-title">BIENVENIDO</h2>
                    <p class="welcome-subtitle"><?= htmlspecialchars($tenant['clinic_name']) ?></p>
                    <p class="welcome-desc" style="margin: 0 auto;">
                        Sistema de Gestión de Historias Clínicas.
                        Acceda al sistema para gestionar citas, pacientes y tratamientos.
                    </p>
                </div>

                <!-- Sede badge -->
                <div style="text-align: center;">
                    <div class="welcome-sede">
                        <i class="fas fa-map-marker-alt"></i>
                        Sede Central
                    </div>
                </div>

                <!-- Estrella decorativa -->
                <div class="deco-star">
                    <i class="fas fa-sparkles"></i>
                    ✦
                </div>
            </div>

            <!-- ═══ PANEL DERECHO: Formulario ═══ -->
            <div class="login-form-panel">
                <h1 class="form-title">Iniciar Sesión</h1>
                <p class="form-subtitle">Ingrese su usuario y contraseña para continuar.</p>

                <!-- Error -->
                <?php if (!empty($error)): ?>
                    <div class="login-error-msg">
                        <i class="fas <?php echo $estaBloqueado ? 'fa-lock' : 'fa-exclamation-triangle'; ?>"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Formulario -->
                <form method="POST" action="index.php" class="login-form" id="loginForm">
                    <!-- Usuario -->
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                        <input type="text"
                               name="usuario"
                               id="usuarioInput"
                               class="login-input <?php echo !empty($error) ? 'input-error' : ''; ?>"
                               placeholder="Usuario"
                               autocomplete="username"
                               value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>"
                               autofocus
                               <?php echo $estaBloqueado ? 'disabled' : ''; ?>
                               required>
                    </div>

                    <!-- Contraseña -->
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password"
                               name="password"
                               id="passwordInput"
                               class="login-input <?php echo !empty($error) ? 'input-error' : ''; ?>"
                               placeholder="Contraseña"
                               autocomplete="current-password"
                               <?php echo $estaBloqueado ? 'disabled' : ''; ?>
                               required>
                        <button type="button" class="toggle-password" id="togglePassword" title="Mostrar/ocultar contraseña">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>

                    <!-- Opciones -->
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" checked> Recordarme
                        </label>
                    </div>

                    <!-- Botón -->
                    <button type="submit" class="btn-signin" id="btn-entrar" <?php echo $estaBloqueado ? 'disabled' : ''; ?>>
                        <i class="fas fa-sign-in-alt"></i>
                        Ingresar al Sistema
                    </button>
                </form>

                <!-- Separador -->
                <div class="form-divider">o</div>

                <!-- Slogan -->
                <div class="login-slogan">
                    <i class="fas fa-tooth"></i>
                    Cuidamos tu sonrisa
                </div>

                <!-- Footer dev -->
                <div class="form-footer-dev">
                    &copy; 2026 &mdash; <span>Desarrollado por: Tec. Reiner Jimenez</span>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Toggle password visibility
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('passwordInput');
        const toggleIcon = document.getElementById('toggleIcon');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                toggleIcon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
            });
        }

        // Enter key to submit
        if (passwordInput) {
            passwordInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('loginForm').submit();
                }
            });
        }
    </script>
</body>
</html>
