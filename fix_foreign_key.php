<?php
require_once 'conexion.php';

try {
    echo "Starting foreign key fix...\n";

    // Check if the constraint exists
    $stmt = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'pagos_personal' AND CONSTRAINT_NAME = 'pagos_personal_ibfk_1' AND TABLE_SCHEMA = DATABASE()");
    if ($stmt->fetch()) {
        echo "Constraint 'pagos_personal_ibfk_1' found. Dropping it...\n";
        $conn->exec("ALTER TABLE pagos_personal DROP FOREIGN KEY pagos_personal_ibfk_1");
        echo "Constraint dropped.\n";
    } else {
        echo "Constraint 'pagos_personal_ibfk_1' not found. Skipping drop.\n";
    }

    // Add the correct constraint
    echo "Adding new foreign key constraint referencing 'empleados(id)'...\n";
    try {
        $conn->exec("ALTER TABLE pagos_personal ADD CONSTRAINT fk_pagos_personal_empleados FOREIGN KEY (personal_id) REFERENCES empleados(id) ON DELETE CASCADE");
        echo "New constraint added successfully.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate key name") !== false) {
            echo "Constraint 'fk_pagos_personal_empleados' already exists.\n";
        } else {
            throw $e;
        }
    }

    echo "Fix complete.\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>