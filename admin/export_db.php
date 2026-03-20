<?php
/**
 * Exportar Base de Datos - Clínica Dental Premium Uchuya
 * Genera un archivo SQL con la estructura y datos de la BD
 * Compatible con InfinityFree (sin mysqldump, usa PDO puro)
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
verificarSesion();
requiereAdmin();

// Nombre del archivo
$fecha = date('Y-m-d_H-i-s');
$filename = DB_NAME . '_backup_' . $fecha . '.sql';

// Headers para descarga
header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Desactivar límite de tiempo para bases grandes
@set_time_limit(300);

// Iniciar output directo (no acumular en string para ahorrar memoria)
echo "-- ========================================\n";
echo "-- Backup de Base de Datos\n";
echo "-- Base de datos: `" . DB_NAME . "`\n";
echo "-- Fecha: " . date('d/m/Y H:i:s') . "\n";
echo "-- Clínica Dental Premium Uchuya\n";
echo "-- ========================================\n\n";
echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
echo "SET AUTOCOMMIT = 0;\n";
echo "START TRANSACTION;\n";
echo "SET time_zone = \"+00:00\";\n\n";
echo "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
echo "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
echo "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
echo "/*!40101 SET NAMES utf8mb4 */;\n\n";

// Desactivar chequeo de FK durante la restauración
echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";

// Obtener todas las tablas
$tables = [];
$result = $pdo->query("SHOW TABLES");
while ($row = $result->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    // Estructura de la tabla
    echo "-- --------------------------------------------------------\n";
    echo "-- Estructura de tabla `$table`\n";
    echo "-- --------------------------------------------------------\n\n";
    echo "DROP TABLE IF EXISTS `$table`;\n";
    
    $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
    echo $createTable[1] . ";\n\n";
    
    // Obtener nombres de columnas
    $colStmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = [];
    while ($col = $colStmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = '`' . $col['Field'] . '`';
    }
    $columnList = implode(', ', $columns);
    
    // Datos de la tabla
    $rows = $pdo->query("SELECT * FROM `$table`");
    $numFields = $rows->columnCount();
    
    $rowCount = 0;
    $insertValues = [];
    
    while ($row = $rows->fetch(PDO::FETCH_NUM)) {
        $rowCount++;
        $values = [];
        for ($j = 0; $j < $numFields; $j++) {
            if (!isset($row[$j]) || $row[$j] === null) {
                $values[] = 'NULL';
            } else {
                $values[] = $pdo->quote($row[$j]);
            }
        }
        $insertValues[] = '(' . implode(', ', $values) . ')';
        
        // Escribir en bloques de 100 registros
        if (count($insertValues) >= 100) {
            echo "INSERT INTO `$table` ($columnList) VALUES\n" . implode(",\n", $insertValues) . ";\n";
            $insertValues = [];
            flush();
        }
    }
    
    // Escribir registros restantes
    if (!empty($insertValues)) {
        echo "INSERT INTO `$table` ($columnList) VALUES\n" . implode(",\n", $insertValues) . ";\n";
        flush();
    }
    
    if ($rowCount > 0) {
        echo "\n";
    }

    echo "-- --------------------------------------------------------\n\n";
}

echo "SET FOREIGN_KEY_CHECKS = 1;\n\n";
echo "COMMIT;\n\n";
echo "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
echo "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
echo "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";

exit;
