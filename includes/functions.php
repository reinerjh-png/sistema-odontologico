<?php
/**
 * Funciones auxiliares
 * Clínica Dental Premium Uchuya
 */

/**
 * Obtener todos los doctores activos
 */
function obtenerDoctores($pdo) {
    $stmt = $pdo->query("SELECT * FROM doctores WHERE estado = 1 ORDER BY nombre");
    return $stmt->fetchAll();
}

/**
 * Obtener todos los tratamientos activos
 */
function obtenerTratamientos($pdo) {
    $stmt = $pdo->query("SELECT * FROM tratamientos WHERE estado = 1 ORDER BY nombre");
    return $stmt->fetchAll();
}

/**
 * Contar total de pacientes (para paginación)
 */
function contarPacientes($pdo, $busqueda = '', $tipoBusqueda = '', $estado = 1, $soloCitas = false) {
    $sql = "SELECT COUNT(*) FROM pacientes p WHERE p.estado = :estado";
    $params = [':estado' => $estado];

    if ($soloCitas) {
        $sql .= " AND p.fecha_ultima_cita >= CURRENT_DATE AND p.fecha_ultima_cita IS NOT NULL";
    }

    if (!empty($busqueda)) {
        $busquedaParam = "%{$busqueda}%";
        switch ($tipoBusqueda) {
            case 'numero_historia':
                $sql .= " AND LOWER(p.numero_historia) LIKE LOWER(:busqueda)";
                $params[':busqueda'] = $busquedaParam;
                break;
            case 'dni':
                $sql .= " AND LOWER(p.dni) LIKE LOWER(:busqueda)";
                $params[':busqueda'] = $busquedaParam;
                break;
            case 'nombre':
                $sql .= " AND LOWER(p.nombres) LIKE LOWER(:busqueda)";
                $params[':busqueda'] = $busquedaParam;
                break;
            case 'tratamiento':
                $sql .= " AND EXISTS (
                    SELECT 1 FROM paciente_tratamientos pt
                    JOIN tratamientos t ON pt.tratamiento_id = t.id
                    WHERE pt.paciente_id = p.id AND LOWER(t.nombre) LIKE LOWER(:busqueda)
                )";
                $params[':busqueda'] = $busquedaParam;
                break;
            default:
                $sql .= " AND (
                    LOWER(p.numero_historia) LIKE LOWER(:busqueda1)
                    OR LOWER(p.dni) LIKE LOWER(:busqueda2)
                    OR LOWER(p.nombres) LIKE LOWER(:busqueda3)
                    OR EXISTS (
                        SELECT 1 FROM paciente_tratamientos pt
                        JOIN tratamientos t ON pt.tratamiento_id = t.id
                        WHERE pt.paciente_id = p.id AND LOWER(t.nombre) LIKE LOWER(:busqueda4)
                    ))";
                $params[':busqueda1'] = $busquedaParam;
                $params[':busqueda2'] = $busquedaParam;
                $params[':busqueda3'] = $busquedaParam;
                $params[':busqueda4'] = $busquedaParam;
                break;
        }
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Obtener pacientes con información del doctor Y sus tratamientos en una sola consulta.
 * Usa GROUP_CONCAT para evitar el problema N+1 (una consulta extra por paciente).
 * Soporta paginación con $limite y $offset.
 */
function obtenerPacientes($pdo, $busqueda = '', $tipoBusqueda = '', $estado = 1, $soloCitas = false, $limite = 50, $offset = 0) {
    // Subconsulta de tratamientos agrupados por paciente
    $sql = "SELECT p.*, d.nombre as doctor_nombre,
                GROUP_CONCAT(t.nombre ORDER BY t.nombre SEPARATOR '||') as tratamientos_nombres
            FROM pacientes p
            LEFT JOIN doctores d ON p.doctor_id = d.id
            LEFT JOIN paciente_tratamientos pt ON pt.paciente_id = p.id
            LEFT JOIN tratamientos t ON t.id = pt.tratamiento_id
            WHERE p.estado = :estado";

    $params = [':estado' => $estado];

    if ($soloCitas) {
        $sql .= " AND p.fecha_ultima_cita >= CURRENT_DATE AND p.fecha_ultima_cita IS NOT NULL";
    }

    if (!empty($busqueda)) {
        $busquedaParam = "%{$busqueda}%";
        switch ($tipoBusqueda) {
            case 'numero_historia':
                $sql .= " AND LOWER(p.numero_historia) LIKE LOWER(:busqueda)";
                $params[':busqueda'] = $busquedaParam;
                break;
            case 'dni':
                $sql .= " AND LOWER(p.dni) LIKE LOWER(:busqueda)";
                $params[':busqueda'] = $busquedaParam;
                break;
            case 'nombre':
                $sql .= " AND LOWER(p.nombres) LIKE LOWER(:busqueda)";
                $params[':busqueda'] = $busquedaParam;
                break;
            case 'tratamiento':
                $sql .= " AND EXISTS (
                    SELECT 1 FROM paciente_tratamientos pt2
                    JOIN tratamientos t2 ON pt2.tratamiento_id = t2.id
                    WHERE pt2.paciente_id = p.id AND LOWER(t2.nombre) LIKE LOWER(:busqueda)
                )";
                $params[':busqueda'] = $busquedaParam;
                break;
            default:
                $sql .= " AND (
                    LOWER(p.numero_historia) LIKE LOWER(:busqueda1)
                    OR LOWER(p.dni) LIKE LOWER(:busqueda2)
                    OR LOWER(p.nombres) LIKE LOWER(:busqueda3)
                    OR EXISTS (
                        SELECT 1 FROM paciente_tratamientos pt2
                        JOIN tratamientos t2 ON pt2.tratamiento_id = t2.id
                        WHERE pt2.paciente_id = p.id AND LOWER(t2.nombre) LIKE LOWER(:busqueda4)
                    ))";
                $params[':busqueda1'] = $busquedaParam;
                $params[':busqueda2'] = $busquedaParam;
                $params[':busqueda3'] = $busquedaParam;
                $params[':busqueda4'] = $busquedaParam;
                break;
        }
    }

    $sql .= " GROUP BY p.id";
    if ($soloCitas) {
        $sql .= " ORDER BY p.fecha_ultima_cita ASC";
    } else {
        $sql .= " ORDER BY CAST(p.numero_historia AS UNSIGNED) DESC";
    }
    $sql .= " LIMIT :limite OFFSET :offset";
    $params[':limite']  = $limite;
    $params[':offset']  = $offset;

    $stmt = $pdo->prepare($sql);
    // LIMIT y OFFSET deben pasarse como enteros
    $stmt->bindValue(':limite',  $limite,  PDO::PARAM_INT);
    $stmt->bindValue(':offset',  $offset,  PDO::PARAM_INT);
    foreach ($params as $key => $val) {
        if ($key !== ':limite' && $key !== ':offset') {
            $stmt->bindValue($key, $val);
        }
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();

    // Convertir la cadena GROUP_CONCAT en array para uso en la vista
    foreach ($rows as &$row) {
        $row['tratamientos'] = $row['tratamientos_nombres']
            ? explode('||', $row['tratamientos_nombres'])
            : [];
    }
    unset($row);
    return $rows;
}

/**
 * Obtener total de citas próximas (desde hoy en adelante)
 */
function obtenerTotalCitasProx($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM pacientes WHERE estado = 1 AND fecha_ultima_cita >= CURRENT_DATE AND fecha_ultima_cita IS NOT NULL");
    return $stmt->fetchColumn();
}

/**
 * Archivar paciente (borrado lógico)
 */
function archivarPaciente($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE pacientes SET estado = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Restaurar paciente
 */
function restaurarPaciente($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE pacientes SET estado = 1 WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Obtener tratamientos de un paciente
 */
function obtenerTratamientosPaciente($pdo, $paciente_id) {
    $stmt = $pdo->prepare("SELECT t.* FROM tratamientos t 
                           INNER JOIN paciente_tratamientos pt ON t.id = pt.tratamiento_id 
                           WHERE pt.paciente_id = ?");
    $stmt->execute([$paciente_id]);
    return $stmt->fetchAll();
}

/**
 * Crear nuevo paciente
 */
function crearPaciente($pdo, $datos, $tratamientos = []) {
    try {
        $pdo->beginTransaction();
        
        $sql = "INSERT INTO pacientes (numero_historia, dni, nombres, genero, celular, edad, direccion, fecha_registro, doctor_id, fecha_ultima_cita, hora_cita, observaciones) 
                VALUES (:numero_historia, :dni, :nombres, :genero, :celular, :edad, :direccion, :fecha_registro, :doctor_id, :fecha_ultima_cita, :hora_cita, :observaciones)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':numero_historia' => $datos['numero_historia'],
            ':dni' => $datos['dni'],
            ':nombres' => $datos['nombres'],
            ':genero' => $datos['genero'],
            ':celular' => $datos['celular'],
            ':edad' => $datos['edad'],
            ':direccion' => $datos['direccion'],
            ':fecha_registro' => $datos['fecha_registro'],
            ':doctor_id' => $datos['doctor_id'] ?: null,
            ':fecha_ultima_cita' => $datos['fecha_ultima_cita'] ?: null,
            ':hora_cita' => $datos['hora_cita'] ?: null,
            ':observaciones' => $datos['observaciones']
        ]);
        
        $paciente_id = $pdo->lastInsertId();
        
        // Insertar tratamientos
        if (!empty($tratamientos)) {
            $stmtTrat = $pdo->prepare("INSERT INTO paciente_tratamientos (paciente_id, tratamiento_id) VALUES (?, ?)");
            foreach ($tratamientos as $tratamiento_id) {
                $stmtTrat->execute([$paciente_id, $tratamiento_id]);
            }
        }
        
        $pdo->commit();
        return $paciente_id;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Actualizar paciente
 */
function actualizarPaciente($pdo, $id, $datos, $tratamientos = []) {
    try {
        $pdo->beginTransaction();
        
        $sql = "UPDATE pacientes SET 
                numero_historia = :numero_historia,
                dni = :dni,
                nombres = :nombres,
                genero = :genero,
                celular = :celular,
                edad = :edad,
                direccion = :direccion,
                fecha_registro = :fecha_registro,
                doctor_id = :doctor_id,
                fecha_ultima_cita = :fecha_ultima_cita,
                hora_cita = :hora_cita,
                observaciones = :observaciones
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':numero_historia' => $datos['numero_historia'],
            ':dni' => $datos['dni'],
            ':nombres' => $datos['nombres'],
            ':genero' => $datos['genero'],
            ':celular' => $datos['celular'],
            ':edad' => $datos['edad'],
            ':direccion' => $datos['direccion'],
            ':fecha_registro' => $datos['fecha_registro'],
            ':doctor_id' => $datos['doctor_id'] ?: null,
            ':fecha_ultima_cita' => $datos['fecha_ultima_cita'] ?: null,
            ':hora_cita' => $datos['hora_cita'] ?: null,
            ':observaciones' => $datos['observaciones'],
            ':id' => $id
        ]);
        
        // Eliminar tratamientos anteriores
        $pdo->prepare("DELETE FROM paciente_tratamientos WHERE paciente_id = ?")->execute([$id]);
        
        // Insertar nuevos tratamientos
        if (!empty($tratamientos)) {
            $stmtTrat = $pdo->prepare("INSERT INTO paciente_tratamientos (paciente_id, tratamiento_id) VALUES (?, ?)");
            foreach ($tratamientos as $tratamiento_id) {
                $stmtTrat->execute([$id, $tratamiento_id]);
            }
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Obtener un paciente por ID (independientemente del estado)
 */
function obtenerPacientePorId($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Sanitizar entrada
 */
function sanitizar($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Mostrar mensaje de alerta
 */
function mostrarAlerta() {
    if (isset($_SESSION['mensaje'])) {
        $tipo = $_SESSION['tipo_mensaje'] ?? 'success';
        $mensaje = $_SESSION['mensaje'];
        unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
        
        return "<div class='alerta alerta-{$tipo}'>{$mensaje}</div>";
    }
    return '';
}

/**
 * Establecer mensaje de alerta
 */
function setMensaje($mensaje, $tipo = 'success') {
    $_SESSION['mensaje'] = $mensaje;
    $_SESSION['tipo_mensaje'] = $tipo;
}

/**
 * Obtener imágenes de un paciente
 */
function obtenerImagenesPaciente($pdo, $paciente_id) {
    $stmt = $pdo->prepare("SELECT * FROM paciente_imagenes WHERE paciente_id = ? ORDER BY created_at DESC");
    $stmt->execute([$paciente_id]);
    return $stmt->fetchAll();
}

/**
 * Guardar registro de imagen de paciente
 */
function guardarImagenPaciente($pdo, $paciente_id, $nombre_archivo, $nombre_original) {
    $stmt = $pdo->prepare("INSERT INTO paciente_imagenes (paciente_id, nombre_archivo, nombre_original) VALUES (?, ?, ?)");
    $stmt->execute([$paciente_id, $nombre_archivo, $nombre_original]);
    return $pdo->lastInsertId();
}

/**
 * Eliminar imagen de paciente (archivo + registro BD)
 */
function eliminarImagenPaciente($pdo, $imagen_id) {
    // Obtener datos de la imagen
    $stmt = $pdo->prepare("SELECT * FROM paciente_imagenes WHERE id = ?");
    $stmt->execute([$imagen_id]);
    $imagen = $stmt->fetch();
    
    if (!$imagen) {
        return false;
    }
    
    // Obtener numero_historia del paciente para la ruta de la carpeta
    $stmtPac = $pdo->prepare("SELECT numero_historia FROM pacientes WHERE id = ?");
    $stmtPac->execute([$imagen['paciente_id']]);
    $pac = $stmtPac->fetch();
    $carpeta = $pac ? $pac['numero_historia'] : $imagen['paciente_id'];
    
    // Eliminar archivo físico
    $ruta = 'uploads/pacientes/' . $carpeta . '/' . $imagen['nombre_archivo'];
    if (file_exists($ruta)) {
        unlink($ruta);
    }
    
    // Eliminar registro de la BD
    $stmt = $pdo->prepare("DELETE FROM paciente_imagenes WHERE id = ?");
    $stmt->execute([$imagen_id]);
    
    return true;
}

/**
 * Eliminar paciente permanentemente (Solo Admin)
 * Borra paciente, tratamientos y todas sus imágenes físicas.
 */
function eliminarPaciente($pdo, $id) {
    try {
        $pdo->beginTransaction();
        
        // 1. Obtener numero_historia para la ruta de la carpeta
        $paciente = obtenerPacientePorId($pdo, $id);
        $carpeta = $paciente ? $paciente['numero_historia'] : $id;
        
        // Obtener imágenes del paciente para borrarlas del disco
        $imagenes = obtenerImagenesPaciente($pdo, $id);
        $directorio = 'uploads/pacientes/' . $carpeta;
        
        // Borrar archivos físicos
        foreach ($imagenes as $img) {
            $ruta = $directorio . '/' . $img['nombre_archivo'];
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }
        
        // Intentar borrar el directorio del paciente si existe
        if (is_dir($directorio)) {
            @rmdir($directorio); 
        }
        
        // 2. Borrar relaciones y datos de la BD (imágenes, tratamientos, paciente)
        // (Asumiendo que no hay ON DELETE CASCADE configurado, lo borramos manualmente)
        $pdo->prepare("DELETE FROM paciente_imagenes WHERE paciente_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM paciente_tratamientos WHERE paciente_id = ?")->execute([$id]);
        
        // 3. Borrar el paciente
        $stmt = $pdo->prepare("DELETE FROM pacientes WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
