<?php
require_once 'conexion.php';
require_once 'encabezado.php';

if(!isset($_GET['id'])) { header("Location: personal.php"); exit; }
$id = $_GET['id'];

// Obtener datos del empleado
$stmt = $conn->prepare("SELECT * FROM empleados WHERE id = ?");
$stmt->execute([$id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$emp) {
    header("Location: personal.php");
    exit;
}

// --- CÁLCULO DE FECHAS (CICLO SEMANAL: Sábado a Viernes) ---
$hoy = new DateTime();
$dia_semana = $hoy->format('w'); // 0=Dom, 6=Sab

// Encontrar el sábado de inicio de esta semana (o la anterior si es vie/sab)
if ($dia_semana == 6) { // Si es Sábado
    $fecha_base = $hoy;
} else {
    // Si es cualquier otro día, volvemos al sábado pasado
    $fecha_base = (clone $hoy)->modify('last saturday');
}

// Generar los 6 días de trabajo (Saltando Domingo)
$dias_laborables = [];
// Offsets desde el sábado: 0=Sab, 2=Lun, 3=Mar, 4=Mier, 5=Jue, 6=Vie
$offsets = [0, 2, 3, 4, 5, 6];

// CORRECCIÓN: Nombres de días según índice estándar de PHP (0=Domingo, 6=Sábado)
$nombres_dias = [
        0 => "Domingo",
        1 => "Lunes",
        2 => "Martes",
        3 => "Miércoles",
        4 => "Jueves",
        5 => "Viernes",
        6 => "Sábado"
];

foreach($offsets as $off) {
    $d = (clone $fecha_base)->modify("+$off days");
    $dias_laborables[] = [
            'fecha' => $d,
            'nombre' => $nombres_dias[$d->format('w')],
            'key' => $off // Usamos el offset como clave única para el formulario
    ];
}
?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card shadow-sm border-0 text-center p-3">
                    <div class="display-4 text-primary mb-2"><i class="fas fa-user-circle"></i></div>
                    <h4 class="fw-bold"><?php echo $emp['nombre']; ?></h4>
                    <p class="text-muted text-uppercase small fw-bold">Colaborador</p>
                    <div class="bg-light p-2 rounded">
                        <small class="text-uppercase text-muted fw-bold">Tarifa Diaria</small>
                        <h3 class="text-success fw-bold m-0">S/ <?php echo number_format($emp['pago_hora'] ?? 0, 2); ?></h3>
                        <small class="text-muted">(Referencia)</small>
                    </div>
                    <a href="personal.php" class="btn btn-outline-secondary w-100 mt-3"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="m-0"><i class="fas fa-calendar-check"></i> Registro de Asistencia</h5>
                        <span class="badge bg-white text-primary">
                        Del <?php echo $dias_laborables[0]['fecha']->format('d/m'); ?>
                        al <?php echo end($dias_laborables)['fecha']->format('d/m'); ?>
                    </span>
                    </div>

                    <div class="card-body p-0">
                        <form action="guardar_asistencia_ciclo.php" method="POST" id="formAsistencia">
                            <input type="hidden" name="personal_id" value="<?php echo $id; ?>">
                            <input type="hidden" name="fecha_inicio" value="<?php echo $fecha_base->format('Y-m-d'); ?>">

                            <!-- Enviamos el pago_hora del empleado -->
                            <input type="hidden" name="pago_dia" value="<?php echo $emp['pago_hora']; ?>">

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Día / Fecha</th>
                                        <th class="text-center" width="150">Asistencia</th>
                                        <th width="150">Horas</th>
                                        <th width="150">Descuento (S/.)</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($dias_laborables as $dia):
                                        $k = $dia['key'];
                                        $es_hoy = ($dia['fecha']->format('Y-m-d') == date('Y-m-d'));
                                        $clase_hoy = $es_hoy ? "table-active fw-bold" : "";
                                        ?>
                                        <tr class="<?php echo $clase_hoy; ?>">
                                            <td class="ps-4">
                                                <div class="fw-bold"><?php echo $dia['nombre']; ?></div>
                                                <small class="text-muted"><?php echo $dia['fecha']->format('d/m/Y'); ?></small>
                                                <?php if($es_hoy): ?><span class="badge bg-success ms-2">HOY</span><?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input fs-4" type="checkbox"
                                                           name="asistencia[<?php echo $k; ?>]"
                                                           value="1" checked
                                                           onchange="toggleRow(this, 'row_<?php echo $k; ?>')">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm" id="row_<?php echo $k; ?>_horas">
                                                    <input type="number" class="form-control text-center" name="horas[<?php echo $k; ?>]" value="8" min="0" max="24">
                                                    <span class="input-group-text">Hrs</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm" id="row_<?php echo $k; ?>_dcto">
                                                    <span class="input-group-text">S/</span>
                                                    <input type="number" class="form-control text-end text-danger fw-bold" name="descuento[<?php echo $k; ?>]" value="0.00" step="0.50" min="0">
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="4" class="p-3">
                                            <label class="form-label fw-bold text-muted">Observaciones Generales de la Semana:</label>
                                            <textarea name="obs_general" class="form-control" rows="2" placeholder="Ej: Bonificación por buen desempeño..."></textarea>
                                        </td>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="p-3 d-grid">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">
                                    <i class="fas fa-save"></i> GUARDAR Y CALCULAR PAGO
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="mt-4">
                    <h6 class="text-muted text-uppercase fw-bold ls-1"><i class="fas fa-history"></i> Historial de Pagos</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered bg-white align-middle">
                            <thead class="table-light"><tr><th>Fecha Inicio</th><th>Resumen</th><th class="text-end">Neto</th><th class="text-center">Acción</th></tr></thead>
                            <tbody>
                            <?php
                            $h = $conn->prepare("SELECT * FROM pagos_personal WHERE personal_id = ? ORDER BY id DESC LIMIT 5");
                            $h->execute([$id]);
                            while($row = $h->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo date('d/m', strtotime($row['fecha_inicio_ciclo'])); ?></td>
                                    <td class="small text-muted text-truncate" style="max-width: 150px;"><?php echo strip_tags($row['dias_trabajados']); ?></td>
                                    <td class="fw-bold text-success text-end">S/ <?php echo number_format($row['monto_total'], 2); ?></td>
                                    <td class="text-center">
                                        <a href="imprimir_boleta_pago.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary btn-sm py-0" title="Ver Boleta">
                                            <i class="fas fa-file-invoice"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function toggleRow(checkbox, rowIdPrefix) {
            // Busca los inputs de horas y descuentos asociados a esta fila
            let horasInput = checkbox.closest('tr').querySelector('input[name^="horas"]');
            let dctoInput = checkbox.closest('tr').querySelector('input[name^="descuento"]');

            if (checkbox.checked) {
                horasInput.disabled = false;
                dctoInput.disabled = false;
                checkbox.closest('tr').classList.remove('opacity-50');
            } else {
                horasInput.disabled = true;
                dctoInput.disabled = true;
                checkbox.closest('tr').classList.add('opacity-50');
            }
        }
    </script>

<?php require_once 'pie.php'; ?>