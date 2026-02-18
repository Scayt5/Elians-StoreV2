<?php
require_once 'conexion.php';
require_once 'config.php';

// CONTRASEÑA DE SEGURIDAD (Para que nadie lo borre por error)
// Cambia esto si quieres, o úsalo así.
$password_seguridad = "elians2026";

$mensaje = "";

if (isset($_POST['confirmar']) && $_POST['password'] === $password_seguridad) {
    try {
        // 1. Desactivamos frenos de seguridad (Foreign Keys)
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0");

        // 2. VACIAR TABLAS (TRUNCATE borra datos y reinicia el contador ID a 1)

        // Borrar Ventas/Pedidos
        $conn->exec("TRUNCATE TABLE pedidos");

        // Borrar Dinero/Caja/Adelantos
        $conn->exec("TRUNCATE TABLE movimientos_personal");

        // Borrar Asistencias (Horas marcadas)
        $conn->exec("TRUNCATE TABLE asistencia");

        // Borrar Pagos/Boletas (Si existe)
        try { $conn->exec("TRUNCATE TABLE pagos_personal"); } catch(Exception $e) {}

        // 3. Reactivamos seguridad
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");

        $mensaje = "<div class='alert alert-success'>✅ ¡Sistema Limpio! Pedidos, Caja y Asistencia reiniciados a CERO.</div>";

    } catch (PDOException $e) {
        $mensaje = "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
    }
} elseif (isset($_POST['confirmar']) && $_POST['password'] !== $password_seguridad) {
    $mensaje = "<div class='alert alert-warning'>⛔ Contraseña incorrecta. No se borró nada.</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Limpiar Sistema - MODO DIOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-danger d-flex justify-content-center align-items-center vh-100">

<div class="card shadow-lg p-4 text-center" style="max-width: 500px;">
    <h1 class="display-1">🗑️</h1>
    <h2 class="text-danger fw-bold">LIMPIEZA TOTAL</h2>
    <p class="lead">Estás a punto de borrar <b>TODOS</b> los pedidos, asistencias y movimientos de caja.</p>
    <p class="text-muted small">Los empleados NO se borrarán.</p>

    <?php echo $mensaje; ?>

    <?php if(empty($mensaje) || strpos($mensaje, 'Error') !== false || strpos($mensaje, 'incorrecta') !== false): ?>
        <form method="POST" class="mt-3">
            <div class="mb-3">
                <label class="form-label fw-bold">Contraseña de Seguridad:</label>
                <input type="password" name="password" class="form-control text-center" placeholder="Ingrese clave..." required>
            </div>
            <button type="submit" name="confirmar" value="si" class="btn btn-dark btn-lg w-100" onclick="return confirm('¿ESTÁS 100% SEGURO? NO HAY VUELTA ATRÁS.')">
                💣 BORRAR TODO
            </button>
        </form>
    <?php else: ?>
        <a href="index.php" class="btn btn-success btn-lg w-100 mt-3">Ir al Inicio (Sistema Limpio)</a>
    <?php endif; ?>
</div>

</body>
</html>