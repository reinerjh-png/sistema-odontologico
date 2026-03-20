<?php
/**
 * Funciones del Call Center
 * Clínica Dental Premium Uchuya
 */

/**
 * Verificar y resetear ciclo de llamadas si es necesario
 */
function verificarYResetearCiclo($pdo) {
    try {
        // Obtener total de pacientes elegibles (con celular)
        $stmtTotal = $pdo->query("
            SELECT COUNT(*) as total 
            FROM pacientes 
            WHERE estado = 1 
            AND celular IS NOT NULL 
            AND celular != ''
        ");
        $totalPacientes = $stmtTotal->fetch()['total'];
        
        // Obtener pacientes ya llamados en el ciclo actual
        $stmtLlamados = $pdo->query("
            SELECT COUNT(DISTINCT paciente_id) as llamados
            FROM call_center_historial
            WHERE ciclo_actual = 1
        ");
        $pacientesLlamados = $stmtLlamados->fetch()['llamados'];
        
        // Si todos los pacientes han sido llamados, resetear ciclo
        if ($pacientesLlamados >= $totalPacientes && $totalPacientes > 0) {
            $pdo->exec("UPDATE call_center_historial SET ciclo_actual = 0 WHERE ciclo_actual = 1");
            return true; // Ciclo reseteado
        }
        
        return false; // Ciclo continúa
    } catch (Exception $e) {
        error_log("Error al verificar ciclo: " . $e->getMessage());
        return false;
    }
}

/**
 * Asignar 12 pacientes aleatorios para el día actual
 * Solo se asignan si no hay asignaciones para hoy
 */
function asignarLlamadasDiarias($pdo) {
    try {
        // Verificar si ya hay asignación para hoy
        $stmt = $pdo->query("
            SELECT COUNT(*) as total 
            FROM call_center_llamadas 
            WHERE fecha_asignacion = CURRENT_DATE
        ");
        $yaAsignados = $stmt->fetch()['total'];
        
        if ($yaAsignados > 0) {
            return false; // Ya hay asignaciones para hoy
        }
        
        // Verificar y resetear ciclo si es necesario
        verificarYResetearCiclo($pdo);
        
        // Seleccionar 12 pacientes aleatorios no llamados en ciclo actual
        $stmt = $pdo->query("
            SELECT p.id
            FROM pacientes p
            WHERE p.estado = 1 
              AND p.celular IS NOT NULL 
              AND p.celular != ''
              AND p.id NOT IN (
                SELECT DISTINCT paciente_id 
                FROM call_center_historial 
                WHERE ciclo_actual = 1
              )
            ORDER BY RAND()
            LIMIT 12
        ");
        
        $pacientes = $stmt->fetchAll();
        
        // Si hay menos de 12 pacientes disponibles, tomar los que haya
        if (empty($pacientes)) {
            return false; // No hay pacientes disponibles
        }
        
        // Insertar asignaciones
        $stmtInsert = $pdo->prepare("
            INSERT INTO call_center_llamadas (paciente_id, fecha_asignacion)
            VALUES (?, CURRENT_DATE)
        ");
        
        foreach ($pacientes as $paciente) {
            $stmtInsert->execute([$paciente['id']]);
        }
        
        return count($pacientes);
        
    } catch (Exception $e) {
        error_log("Error al asignar llamadas: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener llamadas del día con información del paciente
 */
function obtenerLlamadasDelDia($pdo) {
    try {
        // Solo asignar llamadas una vez por sesión/día
        if (!isset($_SESSION['cc_asignado_hoy']) || $_SESSION['cc_asignado_hoy'] !== date('Y-m-d')) {
            asignarLlamadasDiarias($pdo);
            $_SESSION['cc_asignado_hoy'] = date('Y-m-d');
        }
        
        $stmt = $pdo->query("
            SELECT 
                cl.id as llamada_id,
                cl.estado as llamada_estado,
                p.*
            FROM call_center_llamadas cl
            INNER JOIN pacientes p ON cl.paciente_id = p.id
            WHERE cl.fecha_asignacion = CURRENT_DATE
            AND cl.estado IN ('pendiente', 'pospuesta')
            ORDER BY 
                CASE cl.estado 
                    WHEN 'pendiente' THEN 1 
                    WHEN 'pospuesta' THEN 2 
                END,
                cl.id
        ");
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error al obtener llamadas del día: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener contador de llamadas pendientes del día
 */
function obtenerContadorCallCenter($pdo) {
    try {
        // Solo asignar llamadas una vez por sesión/día
        if (!isset($_SESSION['cc_asignado_hoy']) || $_SESSION['cc_asignado_hoy'] !== date('Y-m-d')) {
            asignarLlamadasDiarias($pdo);
            $_SESSION['cc_asignado_hoy'] = date('Y-m-d');
        }
        
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM call_center_llamadas 
            WHERE fecha_asignacion = CURRENT_DATE
            AND estado IN ('pendiente', 'pospuesta')
        ");
        
        return $stmt->fetchColumn();
        
    } catch (Exception $e) {
        error_log("Error al obtener contador: " . $e->getMessage());
        return 0;
    }
}

/**
 * Marcar llamada como completada
 * $agendarCita: true/false
 * $fechaCita: fecha de la cita (YYYY-MM-DD) o null
 */
function marcarLlamadaCompletada($pdo, $llamada_id, $agendarCita = false, $fechaCita = null) {
    try {
        $pdo->beginTransaction();
        
        // Obtener paciente_id de la llamada
        $stmt = $pdo->prepare("SELECT paciente_id FROM call_center_llamadas WHERE id = ?");
        $stmt->execute([$llamada_id]);
        $paciente_id = $stmt->fetchColumn();
        
        if (!$paciente_id) {
            throw new Exception("Llamada no encontrada");
        }
        
        // Actualizar estado de la llamada
        $stmt = $pdo->prepare("
            UPDATE call_center_llamadas 
            SET estado = 'completada', fecha_procesado = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$llamada_id]);
        
        // Registrar en historial
        $stmt = $pdo->prepare("
            INSERT INTO call_center_historial 
            (paciente_id, accion, agendar_cita, fecha_cita, fecha_accion, ciclo_actual)
            VALUES (?, 'completada', ?, ?, NOW(), 1)
        ");
        $stmt->execute([$paciente_id, $agendarCita ? 1 : 0, $fechaCita]);
        
        // Si se agenda cita, actualizar fecha_ultima_cita del paciente
        if ($agendarCita && $fechaCita) {
            $stmt = $pdo->prepare("
                UPDATE pacientes 
                SET fecha_ultima_cita = ?
                WHERE id = ?
            ");
            $stmt->execute([$fechaCita, $paciente_id]);
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error al marcar llamada completada: " . $e->getMessage());
        return false;
    }
}

/**
 * Marcar llamada como pospuesta (llamar después)
 */
function marcarLlamadaPospuesta($pdo, $llamada_id) {
    try {
        $stmt = $pdo->prepare("
            UPDATE call_center_llamadas 
            SET estado = 'pospuesta', fecha_procesado = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([$llamada_id]);
        
    } catch (Exception $e) {
        error_log("Error al posponer llamada: " . $e->getMessage());
        return false;
    }
}

/**
 * Marcar llamada como rechazada
 */
function marcarLlamadaRechazada($pdo, $llamada_id) {
    try {
        $pdo->beginTransaction();
        
        // Obtener paciente_id
        $stmt = $pdo->prepare("SELECT paciente_id FROM call_center_llamadas WHERE id = ?");
        $stmt->execute([$llamada_id]);
        $paciente_id = $stmt->fetchColumn();
        
        if (!$paciente_id) {
            throw new Exception("Llamada no encontrada");
        }
        
        // Actualizar estado
        $stmt = $pdo->prepare("
            UPDATE call_center_llamadas 
            SET estado = 'rechazada', fecha_procesado = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$llamada_id]);
        
        // Registrar en historial
        $stmt = $pdo->prepare("
            INSERT INTO call_center_historial 
            (paciente_id, accion, fecha_accion, ciclo_actual)
            VALUES (?, 'rechazada', NOW(), 1)
        ");
        $stmt->execute([$paciente_id]);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error al rechazar llamada: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener estadísticas del Call Center para una fecha específica
 */
function obtenerEstadisticasCallCenter($pdo, $fecha = null) {
    try {
        if ($fecha === null) {
            $fecha = date('Y-m-d');
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN estado = 'pospuesta' THEN 1 ELSE 0 END) as pospuestas,
                SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas
            FROM call_center_llamadas
            WHERE fecha_asignacion = ?
        ");
        
        $stmt->execute([$fecha]);
        $stats = $stmt->fetch();
        
        // Calcular porcentaje de progreso
        $procesadas = ($stats['completadas'] + $stats['rechazadas']);
        $stats['progreso'] = $stats['total'] > 0 ? round(($procesadas / $stats['total']) * 100, 1) : 0;
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error al obtener estadísticas: " . $e->getMessage());
        return [
            'total' => 0,
            'pendientes' => 0,
            'completadas' => 0,
            'pospuestas' => 0,
            'rechazadas' => 0,
            'progreso' => 0
        ];
    }
}

/**
 * Obtener datos para el reporte histórico
 */
function obtenerReporteHistorico($pdo, $fechaInicio = null, $fechaFin = null) {
    try {
        if ($fechaInicio === null) {
            $fechaInicio = date('Y-m-d', strtotime('-30 days'));
        }
        if ($fechaFin === null) {
            $fechaFin = date('Y-m-d');
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                fecha_asignacion,
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN estado = 'pospuesta' THEN 1 ELSE 0 END) as pospuestas,
                SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes
            FROM call_center_llamadas
            WHERE fecha_asignacion BETWEEN ? AND ?
            GROUP BY fecha_asignacion
            ORDER BY fecha_asignacion DESC
        ");
        
        $stmt->execute([$fechaInicio, $fechaFin]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error al obtener reporte histórico: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener todos los pacientes asignados en una fecha con sus datos y estado
 */
function obtenerPacientesDelDiaReporte($pdo, $fecha = null) {
    try {
        if ($fecha === null) {
            $fecha = date('Y-m-d');
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                cl.id as llamada_id,
                cl.estado as llamada_estado,
                cl.fecha_procesado,
                p.id as paciente_id,
                p.numero_historia,
                p.nombres,
                p.celular,
                p.genero,
                p.edad,
                p.direccion,
                p.fecha_ultima_cita
            FROM call_center_llamadas cl
            JOIN pacientes p ON cl.paciente_id = p.id
            WHERE cl.fecha_asignacion = ?
            ORDER BY 
                FIELD(cl.estado, 'pendiente', 'pospuesta', 'completada', 'rechazada'),
                p.nombres ASC
        ");
        
        $stmt->execute([$fecha]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error al obtener pacientes del día para reporte: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener todos los pacientes asignados a una fecha con su estado y detalles
 */
function obtenerDetalleLlamadasPorFecha($pdo, $fecha) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                cl.id as llamada_id,
                cl.estado as llamada_estado,
                cl.fecha_procesado,
                p.nombres,
                p.numero_historia,
                p.celular,
                p.genero,
                p.edad,
                p.direccion
            FROM call_center_llamadas cl
            INNER JOIN pacientes p ON cl.paciente_id = p.id
            WHERE cl.fecha_asignacion = ?
            ORDER BY 
                CASE cl.estado 
                    WHEN 'completada' THEN 1 
                    WHEN 'pospuesta' THEN 2 
                    WHEN 'rechazada' THEN 3 
                    WHEN 'pendiente' THEN 4 
                END,
                cl.id
        ");
        
        $stmt->execute([$fecha]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error al obtener detalle de llamadas: " . $e->getMessage());
        return [];
    }
}
