<?php
require_once 'conexion.php';

// --- LÓGICA DE AVANCE DE ESTADOS (Mantenemos la lógica interna por si quieres reactivarla luego) ---
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $accion = $_GET['accion'];

    // Obtener estado actual
    $stmt = $conn->prepare("SELECT estado FROM pedidos WHERE id = ?");
    $stmt->execute([$id]);
    $estado_actual = $stmt->fetchColumn();
    $nuevo_estado = $estado_actual;

    // Máquina de Estados
    if ($accion == 'avanzar') {
        $nuevo_estado = match($estado_actual) {
            'Pendiente' => 'En Corte',
            'En Corte' => 'En Costura',
            'En Costura' => 'En Acabado',
            'En Acabado' => 'Listo para Recoger',
            default => $estado_actual
        };
    } elseif ($accion == 'finalizar') {
        $nuevo_estado = 'Entregado';
        // Al finalizar, actualizamos "A Cuenta" para que sea igual al total (Pagado)
        $conn->prepare("UPDATE pedidos SET a_cuenta = costo_total WHERE id = ?")->execute([$id]);
    }

    $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?")->execute([$nuevo_estado, $id]);
    header("Location: historial.php"); exit;
}

// --- BUSCADOR ---
$where = ""; $params = [];
if (isset($_GET['q']) && $_GET['q'] != '') {
    $where = "WHERE cliente_nombre LIKE ? OR descripcion LIKE ? OR id = ?";
    $params = ["%".$_GET['q']."%", "%".$_GET['q']."%", $_GET['q']];
} elseif (isset($_GET['buscar'])) {
    $where = "WHERE proyecto = ?";
    $params = [$_GET['buscar']];
}

$pedidos = $conn->prepare("SELECT * FROM pedidos $where ORDER BY fecha_entrega ASC"); // Ordenamos por urgencia (fecha entrega)
$pedidos->execute($params);
$lista_pedidos = $pedidos->fetchAll(PDO::FETCH_ASSOC);

require_once 'encabezado.php';
?>

    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <form class="d-flex flex-grow-1" method="GET">
                <input class="form-control me-2" type="search" name="q" placeholder="Buscar cliente, ticket..." value="<?php echo isset($_GET['q'])?$_GET['q']:''; ?>">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                <?php if(isset($_GET['q']) || isset($_GET['buscar'])): ?><a href="historial.php" class="btn btn-secondary ms-2">Limpiar</a><?php endif; ?>
            </form>
            <div class="btn-group">
                <a href="exportar_excel.php" class="btn btn-success fw-bold"><i class="fas fa-file-excel"></i> Excel</a>
                <button onclick="window.print()" class="btn btn-secondary fw-bold"><i class="fas fa-print"></i> Imprimir</button>
            </div>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Ticket</th>
                    <th>Entrega / Semáforo</th> <th>Cliente</th>
                    <th>Trabajo</th>
                    <th>Estado Actual</th>
                    <th>Saldo</th>
                    <th class="text-end no-print">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($lista_pedidos as $p):

                    // --- LÓGICA DEL SEMÁFORO DE TIEMPO ---
                    $fecha_entrega = new DateTime($p['fecha_entrega']);
                    $hoy = new DateTime();
                    $hoy->setTime(0,0,0);
                    $fecha_entrega->setTime(0,0,0);

                    $diferencia = $hoy->diff($fecha_entrega);
                    $dias_restantes = (int)$diferencia->format('%r%a'); // %r es el signo (+ o -)

                    // Configuramos colores e iconos según el tiempo
                    if ($p['estado'] == 'Entregado') {
                        // Si ya se entregó, azul relajado
                        $clase_semaforo = "bg-primary bg-opacity-10 text-primary border-primary";
                        $texto_semaforo = "ENTREGADO";
                        $icono_semaforo = "fa-check-circle";
                    } elseif ($dias_restantes < 0) {
                        // Pasó la fecha (Vencido)
                        $clase_semaforo = "bg-danger text-white animate-pulse"; // Rojo fuerte
                        $texto_semaforo = "VENCIDO (" . abs($dias_restantes) . "d)";
                        $icono_semaforo = "fa-skull";
                    } elseif ($dias_restantes == 0) {
                        // Es hoy
                        $clase_semaforo = "bg-danger text-white border-danger";
                        $texto_semaforo = "¡ENTREGA HOY!";
                        $icono_semaforo = "fa-exclamation-circle";
                    } elseif ($dias_restantes <= 2) {
                        // Faltan 1 o 2 días (Alerta)
                        $clase_semaforo = "bg-warning text-dark border-warning";
                        $texto_semaforo = "Pronto ($dias_restantes d)";
                        $icono_semaforo = "fa-clock";
                    } else {
                        // Faltan 3 o más días (Tranquilo)
                        $clase_semaforo = "bg-success bg-opacity-10 text-success border-success";
                        $texto_semaforo = "A tiempo ($dias_restantes d)";
                        $icono_semaforo = "fa-calendar-check";
                    }
                    ?>
                    <tr>
                        <td class="fw-bold text-muted">#<?php echo str_pad($p['id'], 4, "0", STR_PAD_LEFT); ?></td>

                        <td>
                            <div class="d-flex flex-column">
                                <span class="badge <?php echo $clase_semaforo; ?> p-2 border" style="font-size: 0.9rem;">
                                    <i class="fas <?php echo $icono_semaforo; ?> me-1"></i> <?php echo $texto_semaforo; ?>
                                </span>
                                <small class="text-muted mt-1 text-center">
                                    <?php echo date("d/m/Y", strtotime($p['fecha_entrega'])); ?>
                                </small>
                            </div>
                        </td>

                        <td>
                            <div class="fw-bold"><?php echo $p['cliente_nombre']; ?></div>
                            <small class="text-muted"><i class="fas fa-phone-alt"></i> <?php echo $p['cliente_telefono']; ?></small>
                        </td>

                        <td>
                            <div class="text-truncate" style="max-width: 200px;" title="<?php echo $p['descripcion']; ?>">
                                <?php echo $p['descripcion']; ?>
                            </div>
                            <small class="badge bg-light text-secondary border mt-1"><?php echo $p['proyecto']; ?></small>
                        </td>

                        <td class="text-center">
                            <span class="badge bg-secondary rounded-pill px-3 py-2">
                                <?php echo strtoupper($p['estado']); ?>
                            </span>

                            <?php if($p['estado'] != 'Entregado'): ?>
                                <div class="mt-2 no-print">
                                    <a href="historial.php?accion=avanzar&id=<?php echo $p['id']; ?>" class="btn btn-outline-dark btn-sm py-0" style="font-size: 0.7rem;" title="Avanzar etapa">
                                        Siguiente <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td class="fw-bold <?php echo ($p['costo_total'] - $p['a_cuenta']) > 0 ? 'text-danger' : 'text-success'; ?>">
                            S/. <?php echo number_format($p['costo_total'] - $p['a_cuenta'], 2); ?>
                        </td>

                        <td class="text-end no-print">
                            <div class="btn-group">
                                <a href="generar_ticket.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-secondary btn-sm" target="_blank" title="Ticket"><i class="fas fa-receipt"></i></a>

                                <?php if($p['estado'] == 'Listo para Recoger'): ?>
                                    <a href="historial.php?accion=finalizar&id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm" onclick="return confirm('¿Confirmar entrega y pago total?');" title="Entregar">
                                        <i class="fas fa-check"></i> Entregar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <style>
        /* Animación para el estado CRÍTICO (Rojo) */
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
        .animate-pulse {
            animation: pulse-red 2s infinite;
        }
    </style>

<?php require_once 'pie.php'; ?>