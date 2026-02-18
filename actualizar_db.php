<?php
require_once 'conexion.php';

echo "<h2>⏳ Verificando y Actualizando Base de Datos...</h2>";

try {
    // --- 1. VERIFICAR COLUMNA 'fecha_nacimiento' EN EMPLEADOS ---
    echo "<h4>1. Verificando tabla 'empleados'...</h4>";
    $stmt = $conn->query("SHOW COLUMNS FROM empleados LIKE 'fecha_nacimiento'");
    if ($stmt->fetch()) {
        echo "<p style='color:green'>✅ Columna 'fecha_nacimiento' ya existe.</p>";
    } else {
        $stmt_email = $conn->query("SHOW COLUMNS FROM empleados LIKE 'email'");
        if ($stmt_email->fetch()) {
            $conn->exec("ALTER TABLE empleados CHANGE email fecha_nacimiento DATE NULL DEFAULT NULL");
            echo "<p style='color:green'>✅ Columna 'email' renombrada a 'fecha_nacimiento'.</p>";
        } else {
            $conn->exec("ALTER TABLE empleados ADD COLUMN fecha_nacimiento DATE NULL DEFAULT NULL AFTER telefono");
            echo "<p style='color:green'>✅ Columna 'fecha_nacimiento' creada.</p>";
        }
    }

    // --- 2. VERIFICAR TABLA 'pagos_personal' ---
    echo "<h4>2. Verificando tabla 'pagos_personal'...</h4>";
    $stmt = $conn->query("SHOW TABLES LIKE 'pagos_personal'");
    if (!$stmt->fetch()) {
        // Crear tabla si no existe (Backup)
        $sql = "CREATE TABLE IF NOT EXISTS `pagos_personal` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `personal_id` int(11) NOT NULL,
          `fecha_inicio_ciclo` date NOT NULL,
          `dias_trabajados` text,
          `monto_total` decimal(10,2) DEFAULT '0.00',
          `descuentos` decimal(10,2) DEFAULT '0.00',
          `observaciones` text,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $conn->exec($sql);
        echo "<p style='color:green'>✅ Tabla 'pagos_personal' creada.</p>";
    } else {
        echo "<p style='color:green'>✅ Tabla 'pagos_personal' existe.</p>";
    }

    // --- 3. CORREGIR FOREIGN KEY INCORRECTA ---
    echo "<h4>3. Verificando integridad referencial (Foreign Keys)...</h4>";

    // Buscar la FK incorrecta
    $sql_check_fk = "SELECT CONSTRAINT_NAME 
                     FROM information_schema.KEY_COLUMN_USAGE 
                     WHERE TABLE_NAME = 'pagos_personal' 
                     AND CONSTRAINT_NAME = 'pagos_personal_ibfk_1' 
                     AND TABLE_SCHEMA = DATABASE()";
    $stmt_fk = $conn->query($sql_check_fk);

    if ($stmt_fk->fetch()) {
        echo "<p style='color:orange'>⚠️ Detectada restricción incorrecta 'pagos_personal_ibfk_1'. Eliminando...</p>";
        $conn->exec("ALTER TABLE pagos_personal DROP FOREIGN KEY pagos_personal_ibfk_1");
        echo "<p style='color:green'>✅ Restricción eliminada.</p>";
    }

    // Buscar si ya existe la FK correcta
    $sql_check_correct_fk = "SELECT CONSTRAINT_NAME 
                             FROM information_schema.KEY_COLUMN_USAGE 
                             WHERE TABLE_NAME = 'pagos_personal' 
                             AND COLUMN_NAME = 'personal_id'
                             AND REFERENCED_TABLE_NAME = 'empleados'
                             AND TABLE_SCHEMA = DATABASE()";
    $stmt_correct = $conn->query($sql_check_correct_fk);

    if (!$stmt_correct->fetch()) {
        echo "<p>⏳ Agregando nueva restricción hacia tabla 'empleados'...</p>";
        try {
            $conn->exec("ALTER TABLE pagos_personal ADD CONSTRAINT fk_pagos_personal_empleados FOREIGN KEY (personal_id) REFERENCES empleados(id) ON DELETE CASCADE");
            echo "<p style='color:green'>✅ Nueva restricción 'fk_pagos_personal_empleados' creada correctamente.</p>";
        } catch (PDOException $e) {
            // Si falla, puede ser porque hay datos huérfanos
            echo "<p style='color:red'>❌ Error al crear FK: " . $e->getMessage() . "</p>";
            echo "<p style='color:orange'>⚠️ Intentando limpiar datos huérfanos...</p>";
            // Borrar pagos que no tengan empleado válido
            $conn->exec("DELETE FROM pagos_personal WHERE personal_id NOT IN (SELECT id FROM empleados)");
            // Reintentar
            $conn->exec("ALTER TABLE pagos_personal ADD CONSTRAINT fk_pagos_personal_empleados FOREIGN KEY (personal_id) REFERENCES empleados(id) ON DELETE CASCADE");
            echo "<p style='color:green'>✅ Datos limpiados y restricción creada.</p>";
        }
    } else {
        echo "<p style='color:green'>✅ La restricción hacia 'empleados' ya está correcta.</p>";
    }

    echo "<hr>";
    echo "<h3>🎉 PROCESO TERMINADO CORRECTAMENTE</h3>";
    echo "<p><a href='personal.php' class='btn btn-primary'>Volver a Personal</a></p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>❌ Error Crítico: " . $e->getMessage() . "</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>