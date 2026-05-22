<?php
/**
 * Sistema de Autenticación con Roles
 * Clínica Dental Premium Uchuya
 * 
 * Roles:
 *  - admin: Acceso completo (eliminar pacientes, gestión doctores/usuarios, ver actividad)
 *  - recepcionista: Acceso estándar (crear/editar/archivar pacientes, call center)
 */

// Configuración de intentos
define('MAX_INTENTOS_LOGIN', 5);
define('BLOQUEO_MINUTOS', 15);

/**
 * Calcula el prefijo de ruta relativo al directorio raíz del proyecto
 * para redirigir correctamente desde cualquier subdirectorio.
 */
function _getRedirectPrefix()
{
    $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
    $authDir    = str_replace('\\', '/', dirname(__DIR__));

    if (strpos($scriptPath, $authDir) === 0) {
        $relPath = substr(dirname($scriptPath), strlen($authDir) + 1);
        $depth   = $relPath ? substr_count($relPath, '/') + 1 : 0;
        return str_repeat('../', $depth);
    }
    return '';
}

/**
 * Verifica si el usuario tiene una sesión activa Y si su cuenta
 * sigue habilitada en la base de datos.
 *
 * Si la sesión no existe → redirige al login.
 * Si la cuenta fue desactivada o eliminada mientras tenía sesión
 * activa → destruye la sesión y redirige al login con aviso.
 *
 * SEGURIDAD: Esta función resuelve la vulnerabilidad que permitía
 * a un usuario con sesión activa seguir usando el sistema incluso
 * después de que el administrador desactivara su cuenta.
 */
function verificarSesion()
{
    // 1. Verificar que exista la sesión
    if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
        header('Location: ' . _getRedirectPrefix() . 'index.php');
        exit;
    }

    // 2. Verificar en la BD que la cuenta aún esté activa
    //    Solo si tenemos usuario_id en sesión y acceso a $pdo
    if (isset($_SESSION['usuario_id'])) {
        global $pdo;
        if ($pdo instanceof PDO) {
            try {
                $stmt = $pdo->prepare("SELECT estado FROM usuarios WHERE id = ? LIMIT 1");
                $stmt->execute([$_SESSION['usuario_id']]);
                $userDb = $stmt->fetch();

                // Cuenta desactivada o eliminada → cerrar sesión inmediatamente
                if (!$userDb || $userDb['estado'] == 0) {
                    // Destruir sesión completamente
                    $_SESSION = [];
                    if (ini_get('session.use_cookies')) {
                        $params = session_get_cookie_params();
                        setcookie(
                            session_name(), '',
                            time() - 42000,
                            $params['path'], $params['domain'],
                            $params['secure'], $params['httponly']
                        );
                    }
                    session_destroy();

                    // Reiniciar sesión solo para pasar el mensaje de aviso
                    session_start();
                    $_SESSION['mensaje']      = 'Su cuenta ha sido desactivada. Contacte al administrador.';
                    $_SESSION['tipo_mensaje'] = 'error';

                    header('Location: ' . _getRedirectPrefix() . 'index.php');
                    exit;
                }
            } catch (Exception $e) {
                // Si la BD no responde, no bloqueamos el acceso para no
                // tumbar el sistema. El check fallará silenciosamente.
            }
        }
    }
}

/**
 * Verifica si el usuario actual es administrador.
 */
function esAdmin()
{
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

/**
 * Requiere que el usuario sea admin. Si no lo es, redirige al dashboard.
 */
function requiereAdmin()
{
    if (!esAdmin()) {
        header('Location: ' . _getRedirectPrefix() . 'dashboard.php');
        exit;
    }
}

/**
 * Intenta autenticar al usuario con usuario y contraseña.
 * Valida contra la tabla `usuarios` en la base de datos.
 * 
 * @param PDO $pdo Conexión a la BD
 * @param string $usuario Nombre de usuario
 * @param string $password Contraseña en texto plano
 * @return array ['success' => bool, 'message' => string]
 */
function intentarLogin($pdo, $usuario, $password)
{
    // Verificar si está bloqueado por intentos fallidos
    if (isset($_SESSION['login_bloqueado_hasta'])) {
        $tiempoRestante = $_SESSION['login_bloqueado_hasta'] - time();
        if ($tiempoRestante > 0) {
            $minutosRestantes = ceil($tiempoRestante / 60);
            return [
                'success' => false,
                'message' => "Cuenta bloqueada por seguridad. Intente en {$minutosRestantes} minuto(s).",
                'bloqueado' => true
            ];
        } else {
            unset($_SESSION['login_bloqueado_hasta']);
            unset($_SESSION['login_intentos']);
        }
    }

    // Inicializar contador de intentos
    if (!isset($_SESSION['login_intentos'])) {
        $_SESSION['login_intentos'] = 0;
    }

    // Buscar usuario en la base de datos
    $stmt = $pdo->prepare("SELECT id, usuario, password_hash, nombre_completo, rol, estado FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    // Verificar credenciales
    if ($user && $user['password_hash'] === hash('sha256', $password)) {
        // Verificar si el usuario está activo
        if ($user['estado'] == 0) {
            return [
                'success' => false,
                'message' => 'Su cuenta ha sido desactivada. Contacte al administrador.'
            ];
        }

        // Login exitoso — guardar datos en sesión
        $_SESSION['autenticado'] = true;
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nombre'] = $user['nombre_completo'];
        $_SESSION['usuario_usuario'] = $user['usuario'];
        $_SESSION['usuario_rol'] = $user['rol'];
        $_SESSION['login_tiempo'] = time();
        unset($_SESSION['login_intentos']);
        unset($_SESSION['login_bloqueado_hasta']);

        // Registrar actividad de login
        registrarActividad($pdo, 'Login', 'Inicio de sesión exitoso');

        // -------------------------------------------------------------
        // Notificación por WhatsApp en segundo plano (vía Twilio)
        // Solo para usuarios que NO son administradores
        // -------------------------------------------------------------
        if ($user['rol'] !== 'admin') {
            try {
                // CREDENCIALES DE TWILIO (Variables de Entorno)
                $envFile = __DIR__ . '/../.env';
                $env = file_exists($envFile) ? parse_ini_file($envFile) : [];

                $twilio_account_sid = isset($env['TWILIO_ACCOUNT_SID']) ? $env['TWILIO_ACCOUNT_SID'] : getenv('TWILIO_ACCOUNT_SID');
                $twilio_auth_token = isset($env['TWILIO_AUTH_TOKEN']) ? $env['TWILIO_AUTH_TOKEN'] : getenv('TWILIO_AUTH_TOKEN');

                // Si no hay credenciales, saltar la notificación
                if (!$twilio_account_sid || !$twilio_auth_token) {
                    throw new Exception("Credenciales de Twilio no configuradas.");
                }

                $twilio_whatsapp_number = "whatsapp:+14155238886"; // Sandbox Twilio
                $telefono_destino = "whatsapp:+51977480721"; // El número del propietario

                $mensajeTexto = "🔔 *Inicio de sesión detectado*\nEl usuario *" . $user['nombre_completo'] . "* (" . $user['usuario'] . ") ha iniciado sesión en el sistema de la Clínica Dental Uchuya Sede *Tingo María*.";

                $urlTwilio = "https://api.twilio.com/2010-04-01/Accounts/" . $twilio_account_sid . "/Messages.json";

                $data = [
                    'From' => $twilio_whatsapp_number,
                    'To' => $telefono_destino,
                    'Body' => $mensajeTexto
                ];

                $ch = curl_init($urlTwilio);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_USERPWD, $twilio_account_sid . ":" . $twilio_auth_token);
                curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Timeout muy corto (2 seg) para que no bloquee el login si el API demora
                @curl_exec($ch);
                @curl_close($ch);

            } catch (Exception $e) {
                // Ignoramos el error para no colgar el sistema de inicio de sesión
            }
        }
        //----------------------------------------------------------------------

        return [
            'success' => true,
            'message' => 'Acceso concedido'
        ];
    } else {
        // Credenciales incorrectas — incrementar intentos
        $_SESSION['login_intentos']++;

        $intentosRestantes = MAX_INTENTOS_LOGIN - $_SESSION['login_intentos'];

        if ($_SESSION['login_intentos'] >= MAX_INTENTOS_LOGIN) {
            $_SESSION['login_bloqueado_hasta'] = time() + (BLOQUEO_MINUTOS * 60);
            $_SESSION['login_intentos'] = 0;

            return [
                'success' => false,
                'message' => "Demasiados intentos fallidos. Cuenta bloqueada por " . BLOQUEO_MINUTOS . " minutos.",
                'bloqueado' => true
            ];
        }

        return [
            'success' => false,
            'message' => "Usuario o contraseña incorrectos. Le quedan {$intentosRestantes} intento(s)."
        ];
    }
}

/**
 * Registra una actividad en el log.
 * 
 * @param PDO $pdo Conexión a la BD
 * @param string $accion Tipo de acción (Login, Crear Paciente, etc.)
 * @param string $detalle Detalle de la acción
 */
function registrarActividad($pdo, $accion, $detalle = '')
{
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
    if (!$usuario_id)
        return;

    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';

    try {
        $stmt = $pdo->prepare("INSERT INTO actividad_log (usuario_id, accion, detalle, ip_address, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $accion, $detalle, $ip, date('Y-m-d H:i:s')]);
    } catch (Exception $e) {
        // Silenciar errores del log para no afectar la operación principal
    }
}

/**
 * Cierra la sesión del usuario y redirige al login.
 */
function cerrarSesion()
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
