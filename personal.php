<?php
require_once 'conexion.php';

// PROCESOS PHP (Guardar nuevo empleado)
if (isset($_POST['accion']) && $_POST['accion'] == 'nuevo_empleado') {
    // Intentamos guardar. Si la base de datos es vieja, guardamos null en los campos nuevos para que no falle.
    try {
        $fecha_nac = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;

        // Verificamos columnas disponibles (parche de seguridad)
        $columnas = $conn->query("SHOW COLUMNS FROM empleados LIKE 'fecha_nacimiento'")->fetchAll();

        if (count($columnas) > 0) {
            // SI existe la columna nueva
            $conn->prepare("INSERT INTO empleados (nombre, telefono, fecha_nacimiento, direccion, pago_hora) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$_POST['nombre'], $_POST['telefono'], $fecha_nac, $_POST['direccion'], $_POST['pago_hora']]);
        } else {
            // NO existe la columna (Modo compatibilidad): Guardamos la fecha en 'email' temporalmente
            $conn->prepare("INSERT INTO empleados (nombre, telefono, email, direccion, pago_hora) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$_POST['nombre'], $_POST['telefono'], $fecha_nac, $_POST['direccion'], $_POST['pago_hora']]);
        }
        header("Location: personal.php"); exit;
    } catch (PDOException $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}
$empleados = $conn->query("SELECT * FROM empleados")->fetchAll(PDO::FETCH_ASSOC);

// INCLUIR DISEÑO
require_once 'encabezado.php';
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark m-0">Gestión de Personal</h3>
        <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevo">
            <i class="fas fa-user-plus"></i> Nuevo Empleado
        </button>
    </div>

    <div class="row">
        <?php foreach($empleados as $e):
            // Lógica de Cumpleaños SEGURA (Sin errores)
            $es_cumple = false;
            // Usamos isset para evitar el "Warning: Undefined array key"
            $fecha_nac = isset($e['fecha_nacimiento']) ? $e['fecha_nacimiento'] : null;

            // Si no hay fecha_nacimiento, revisamos el campo 'email' por si acaso (compatibilidad)
            // Esto arregla el bug visual si la base de datos aun no se actualizó pero guardamos en email
            if (empty($fecha_nac) && isset($e['email']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $e['email'])) {
                $fecha_nac = $e['email'];
            }

            if ($fecha_nac) {
                $hoy = date('m-d');
                $cumple = date('m-d', strtotime($fecha_nac));
                if ($hoy == $cumple) $es_cumple = true;
            }
            ?>
            <div class="col-md-4 mb-4">
                <a href="detalle_personal.php?id=<?php echo $e['id']; ?>" class="text-decoration-none text-dark">
                    <div class="card border-0 shadow-sm h-100 hover-card <?php echo $es_cumple ? 'border border-warning border-3' : ''; ?>" style="transition:0.3s;">
                        <div class="card-body position-relative">
                            <?php if($es_cumple): ?>
                                <span class="position-absolute top-0 end-0 m-2 badge bg-warning text-dark animate-bounce shadow">
                                    🎂 ¡FELIZ CUMPLEAÑOS!
                                </span>
                            <?php endif; ?>

                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:50px; height:50px; font-size:20px;">
                                    <?php echo strtoupper(substr($e['nombre'], 0, 1)); ?>
                                </div>
                                <div class="ms-3">
                                    <h5 class="fw-bold m-0"><?php echo $e['nombre']; ?></h5>
                                    <small class="text-muted">S/ <?php echo number_format($e['pago_hora'], 2); ?> / hora</small>
                                </div>
                            </div>
                            <p class="mb-1 small">
                                <i class="fas fa-phone text-muted me-2"></i> <?php echo $e['telefono'] ?: '--'; ?>
                            </p>
                            <p class="mb-1 small">
                                <i class="fas fa-birthday-cake text-muted me-2"></i>
                                <strong>Nacimiento:</strong>
                                <?php echo $fecha_nac ? date('d/m/Y', strtotime($fecha_nac)) : '<span class="text-muted fst-italic">No registrado</span>'; ?>
                            </p>
                            <hr>
                            <div class="text-end text-primary fw-bold small">
                                VER PLANILLAS <i class="fas fa-arrow-right ms-1"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="modal fade" id="modalNuevo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Registrar Nuevo Personal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="accion" value="nuevo_empleado">
                        <div class="mb-3"><label>Nombre Completo</label><input type="text" name="nombre" class="form-control" required></div>
                        <div class="row mb-3">
                            <div class="col"><label>Teléfono</label><input type="text" name="telefono" class="form-control"></div>
                            <div class="col"><label>Pago x Hora (S/)</label><input type="number" name="pago_hora" class="form-control" value="5.00" step="0.5"></div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold text-primary">Fecha de Nacimiento (Cumpleaños)</label>
                            <input type="date" name="fecha_nacimiento" class="form-control">
                            <div class="form-text text-muted small">Si no aparece, deje en blanco.</div>
                        </div>

                        <div class="mb-3 text-end">
                            <a href="exportar_global_personal.php" class="btn btn-success fw-bold shadow-sm">
                                <i class="fas fa-file-excel"></i> Descargar Reporte Semanal Global
                            </a>
                        </div>

                        <div class="mb-3"><label>Dirección</label><input type="text" name="direccion" class="form-control"></div>
                        <button type="submit" class="btn btn-success w-100">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        .animate-bounce { animation: bounce 1s infinite; }
    </style>

<?php require_once 'pie.php'; ?>