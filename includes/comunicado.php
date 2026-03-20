<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  COMUNICADOS AUTOMÁTICOS — Clínica Uchuya Premium
 * ══════════════════════════════════════════════════════════════
 *  Mensajes predeterminados por día y hora:
 *  1. Primera sesión del día  → Bienvenida + citas del día
 *  2. A partir de las 11:00 AM → Recordatorio call center
 *  3. A partir de las 8:00 PM  → Citas del día siguiente
 */

// ── Determinar mensaje automático según la hora ──────────────

$comunicado_activo  = false;
$comunicado_titulo  = "Aviso del Sistema";
$comunicado_mensaje = "Hola";
$comunicado_tipo    = "info";

$hoy       = date('Y-m-d');
$horaActual = (int) date('G'); // 0-23

// Clave de sesión para rastrear qué mensajes ya se mostraron hoy
if (!isset($_SESSION['comunicado_dia']) || $_SESSION['comunicado_dia'] !== $hoy) {
    // Nuevo día: resetear todos los flags
    $_SESSION['comunicado_dia'] = $hoy;
    $_SESSION['comunicado_bienvenida'] = false;
    $_SESSION['comunicado_callcenter'] = false;
    $_SESSION['comunicado_manana']     = false;
}

// Si todos los mensajes del día ya se mostraron, no hacer nada más
if ($_SESSION['comunicado_bienvenida'] && $_SESSION['comunicado_callcenter'] && $_SESSION['comunicado_manana']) {
    return;
}

// ── 1. Bienvenida y Citas de Hoy (a partir de las 8:00 AM) ───
if ($horaActual >= 8 && $horaActual < 11 && !$_SESSION['comunicado_bienvenida']) {
    // Contar citas agendadas para hoy
    $stmtHoy = $pdo->query(
        "SELECT COUNT(*) FROM pacientes 
         WHERE estado = 1 
         AND fecha_ultima_cita = CURRENT_DATE"
    );
    $citasHoy = (int) $stmtHoy->fetchColumn();

    $comunicado_activo  = true;
    $comunicado_titulo  = "¡Bienvenido al Sistema!";
    $comunicado_mensaje = "Bienvenido(a) al sistema de la Clínica Dental Uchuya Premium de Meilyng, hoy tienes {$citasHoy} citas agendadas, ¡Revisa el listado de Citas para más detalles!.";
    $comunicado_tipo    = "info";
    $_SESSION['comunicado_bienvenida'] = true;

// ── 2. Recordatorio Call Center (a partir de las 11:00 AM) ───
} elseif ($horaActual >= 11 && $horaActual <= 15 && !$_SESSION['comunicado_callcenter']) {
    $comunicado_activo  = true;
    $comunicado_titulo  = "Recordatorio — Call Center";
    $comunicado_mensaje = "Recuerda llamar a los pacientes registrados en el Call Center para confirmar o agendar sus citas.";
    $comunicado_tipo    = "warning";
    $_SESSION['comunicado_callcenter'] = true;

// ── 3. Citas de mañana (a partir de las 8:00 PM) ────────────
} elseif ($horaActual >= 20 && !$_SESSION['comunicado_manana']) {
    // Contar citas agendadas para mañana
    $stmtManana = $pdo->query(
        "SELECT COUNT(*) FROM pacientes 
         WHERE estado = 1 
         AND fecha_ultima_cita = CURRENT_DATE + INTERVAL 1 DAY"
    );
    $citasManana = (int) $stmtManana->fetchColumn();

    $comunicado_activo  = true;
    $comunicado_titulo  = "Citas para Mañana";
    $comunicado_mensaje = "Para mañana tienes {$citasManana} citas agendadas. ¡Revisa el listado de Citas Próximas para más detalles!";
    $comunicado_tipo    = "info";
    $_SESSION['comunicado_manana'] = true;
}
