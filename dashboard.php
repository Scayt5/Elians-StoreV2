<?php
// require_once 'seguridad.php'; // ACTIVAR SOLO CUANDO TENGAS LOGIN
require_once 'config.php';
require_once 'encabezado.php';

// --- CONFIGURACIÓN INICIAL ---
$calculo_realizado = false;
// Por defecto: del primer al último día del mes actual
$fecha_inicio = date('Y-m-01');
$fecha_fin = date('Y-m-t');

// VARIABLES DE DINERO (Inicializadas en 0)
$total_ingresos = 0;
$total_mano_obra = 0; // Automático

// Costos Variables (Materia Prima y Producción)
$gasto_tela = 0;
$gasto_insumos = 0; // Hilos, botones, cierres
$gasto_servicios_externos = 0; // Estampado fuera, bordado fuera

// Gastos Fijos (Operativos)
$gasto_alquiler = 0;
$gasto_servicios_basicos = 0; // Luz, agua, internet
$gasto_transporte = 0; // Pasajes, gasolina
$gasto_otros = 0;

// Resultados
$total_costos_variables = 0;
$total_gastos_fijos = 0;
$total_egresos = 0;
$utilidad_neta = 0;
$margen_ganancia = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. RECUPERAR FECHAS
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];

    // 2. RECUPERAR DATOS MANUALES (floatval convierte vacío a 0 automáticamente)
    $gasto_tela = floatval($_POST['gasto_tela']);
    $gasto_insumos = floatval($_POST['gasto_insumos']);
    $gasto_servicios_externos = floatval($_POST['gasto_servicios_externos']);

    $gasto_alquiler = floatval($_POST['gasto_alquiler']);
    $gasto_servicios_basicos = floatval($_POST['gasto_servicios_basicos']);
    $gasto_transporte = floatval($_POST['gasto_transporte']);
    $gasto_otros = floatval($_POST['gasto_otros']);

    // 3. CONSULTA AUTOMÁTICA: INGRESOS POR VENTAS
    // Suma todos los pedidos recibidos en ese rango
    $sql_ventas = "SELECT COALESCE(SUM(costo_total), 0) FROM pedidos 
                   WHERE DATE(fecha_recepcion) BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_ventas);
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $total_ingresos = $stmt->fetchColumn();

    // 4. CONSULTA AUTOMÁTICA: MANO DE OBRA
    // Suma (Horas * Tarifa) de la tabla asistencia
    $sql_mo = "SELECT COALESCE(SUM(a.horas * e.pago_hora), 0) 
               FROM asistencia a 
               JOIN empleados e ON a.empleado_id = e.id 
               WHERE a.fecha BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_mo);
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $total_mano_obra = $stmt->fetchColumn();

    // 5. CÁLCULOS FINANCIEROS
    $total_costos_variables = $total_mano_obra + $gasto_tela + $gasto_insumos + $gasto_servicios_externos;
    $total_gastos_fijos = $gasto_alquiler + $gasto_servicios_basicos + $gasto_transporte + $gasto_otros;

    $total_egresos = $total_costos_variables + $total_gastos_fijos;
    $utilidad_neta = $total_ingresos - $total_egresos;

    // Calcular margen % (Evitar división por cero)
    if ($total_ingresos > 0) {
        $margen_ganancia = ($utilidad_neta / $total_ingresos) * 100;
    }

    $calculo_realizado = true;
}

// --- DATOS PARA GRÁFICO (IGUAL QUE ANTES) ---
$sql_chart = "SELECT DATE(fecha_recepcion) as fecha, SUM(costo_total) as total 
              FROM pedidos WHERE fecha_recepcion >= DATE(NOW()) - INTERVAL 7 DAY 
              GROUP BY DATE(fecha_recepcion) ORDER BY fecha ASC";
$datos_grafico = $conn->query($sql_chart)->fetchAll(PDO::FETCH_ASSOC);
$labels = []; $data = [];
foreach($datos_grafico as $d) { $labels[] = date('d/m', strtotime($d['fecha'])); $data[] = $d['total']; }
?>

    <div class="row mb-4 animate-fade-in">
        <div class="col-12">
            <h3 class="fw-bold text-uppercase text-secondary"><i class="fas fa-chart-pie me-2"></i> Análisis Financiero</h3>
            <p class="text-muted small">Calcula la rentabilidad real del negocio considerando costos variables y fijos.</p>
        </div>
    </div>

    <div class="card shadow border-0 mb-5">
        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold"><i class="fas fa-calculator text-warning"></i> Calculadora de Rentabilidad</h5>
            <span class="badge bg-warning text-dark">Modo Detallado</span>
        </div>
        <div class="card-body p-4">
            <form method="POST">

                <div class="row mb-4 p-3 bg-light rounded border mx-0">
                    <div class="col-md-6">
                        <label class="fw-bold text-dark small mb-1">📅 DESDE FECHA:</label>
                        <input type="date" name="fecha_inicio" class="form-control fw-bold" value="<?php echo $fecha_inicio; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold text-dark small mb-1">📅 HASTA FECHA:</label>
                        <input type="date" name="fecha_fin" class="form-control fw-bold" value="<?php echo $fecha_fin; ?>" required>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2"><i class="fas fa-robot"></i> Datos del Sistema</h6>

                        <div class="mb-3">
                            <label class="small text-muted fw-bold">MANO DE OBRA (CALCULADO)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-primary fw-bold">S/</span>
                                <input type="text" class="form-control bg-light text-primary fw-bold" value="<?php echo number_format($total_mano_obra, 2); ?>" readonly>
                            </div>
                            <div class="form-text x-small">Suma automática de horas trabajadas x tarifa.</div>
                        </div>
                    </div>

                    <div class="col-md-4 border-start border-end">
                        <h6 class="fw-bold text-danger border-bottom pb-2"><i class="fas fa-cut"></i> Costos de Producción (Opcional)</h6>

                        <div class="mb-2">
                            <label class="small text-muted">GASTO EN TELA</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" name="gasto_tela" class="form-control" placeholder="0.00" value="<?php echo ($gasto_tela > 0)?$gasto_tela:''; ?>">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="small text-muted">INSUMOS (Hilos, Botones...)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" name="gasto_insumos" class="form-control" placeholder="0.00" value="<?php echo ($gasto_insumos > 0)?$gasto_insumos:''; ?>">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="small text-muted">SERVICIOS EXTERNOS (Bordado, etc)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" name="gasto_servicios_externos" class="form-control" placeholder="0.00" value="<?php echo ($gasto_servicios_externos > 0)?$gasto_servicios_externos:''; ?>">
                            </div>
                        </div>
                        <div class="form-text x-small text-end fst-italic">Deja en blanco si no aplica.</div>
                    </div>

                    <div class="col-md-4">
                        <h6 class="fw-bold text-secondary border-bottom pb-2"><i class="fas fa-building"></i> Gastos Fijos / Taller (Opcional)</h6>

                        <div class="mb-2">
                            <label class="small text-muted">ALQUILER DEL LOCAL</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" name="gasto_alquiler" class="form-control" placeholder="0.00" value="<?php echo ($gasto_alquiler > 0)?$gasto_alquiler:''; ?>">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="small text-muted">SERVICIOS (Luz, Agua, Net)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" name="gasto_servicios_basicos" class="form-control" placeholder="0.00" value="<?php echo ($gasto_servicios_basicos > 0)?$gasto_servicios_basicos:''; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-2">
                                <label class="small text-muted">TRANSPORTE</label>
                                <input type="number" step="0.01" name="gasto_transporte" class="form-control form-control-sm" placeholder="0.00" value="<?php echo ($gasto_transporte > 0)?$gasto_transporte:''; ?>">
                            </div>
                            <div class="col-6 mb-2">
                                <label class="small text-muted">OTROS</label>
                                <input type="number" step="0.01" name="gasto_otros" class="form-control form-control-sm" placeholder="0.00" value="<?php echo ($gasto_otros > 0)?$gasto_otros:''; ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-success btn-lg px-5 fw-bold shadow-sm rounded-pill">
                        <i class="fas fa-sync-alt me-2 fa-spin-hover"></i> PROCESAR CÁLCULOS
                    </button>
                </div>
            </form>

            <?php if ($calculo_realizado): ?>
                <div class="mt-5 border-top pt-4 animate-fade-in">

                    <div class="row mb-4 text-center">
                        <div class="col-md-4 mb-3">
                            <div class="card bg-success text-white h-100 shadow-sm border-0">
                                <div class="card-body">
                                    <h6 class="text-uppercase opacity-75 fw-bold small">Ingresos Totales (Ventas)</h6>
                                    <h2 class="fw-bold mb-0">S/ <?php echo number_format($total_ingresos, 2); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-danger text-white h-100 shadow-sm border-0">
                                <div class="card-body">
                                    <h6 class="text-uppercase opacity-75 fw-bold small">Total Egresos (Gastos)</h6>
                                    <h2 class="fw-bold mb-0">S/ <?php echo number_format($total_egresos, 2); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <?php $color_final = ($utilidad_neta > 0) ? 'bg-primary' : 'bg-secondary'; ?>
                            <div class="card <?php echo $color_final; ?> text-white h-100 shadow border-0 position-relative overflow-hidden">
                                <div class="card-body">
                                    <h6 class="text-uppercase opacity-75 fw-bold small">UTILIDAD NETA (Bolsillo)</h6>
                                    <h2 class="fw-bold mb-0">S/ <?php echo number_format($utilidad_neta, 2); ?></h2>
                                    <span class="position-absolute top-0 end-0 m-2 badge bg-white text-dark small">
                                    <?php echo number_format($margen_ganancia, 1); ?>% Rentabilidad
                                </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="fw-bold text-muted mb-3"><i class="fas fa-list-ul"></i> Desglose de Gastos</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr class="border-bottom">
                                        <td class="text-muted"><i class="fas fa-users"></i> Mano de Obra (Planilla)</td>
                                        <td class="text-end fw-bold">S/ <?php echo number_format($total_mano_obra, 2); ?></td>
                                        <td class="text-muted ps-4"><i class="fas fa-home"></i> Alquiler Local</td>
                                        <td class="text-end fw-bold">S/ <?php echo number_format($gasto_alquiler, 2); ?></td>
                                    </tr>
                                    <tr class="border-bottom">
                                        <td class="text-muted"><i class="fas fa-layer-group"></i> Tela / Material</td>
                                        <td class="text-end fw-bold">S/ <?php echo number_format($gasto_tela, 2); ?></td>
                                        <td class="text-muted ps-4"><i class="fas fa-lightbulb"></i> Servicios Básicos</td>
                                        <td class="text-end fw-bold">S/ <?php echo number_format($gasto_servicios_basicos, 2); ?></td>
                                    </tr>
                                    <tr class="border-bottom">
                                        <td class="text-muted"><i class="fas fa-boxes"></i> Insumos Varios</td>
                                        <td class="text-end fw-bold">S/ <?php echo number_format($gasto_insumos, 2); ?></td>
                                        <td class="text-muted ps-4"><i class="fas fa-bus"></i> Transporte / Logística</td>
                                        <td class="text-end fw-bold">S/ <?php echo number_format($gasto_transporte, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted"><i class="fas fa-handshake"></i> Servicios Externos</td>
                                        <td class="text-end fw-bold">S/ <?php echo number_format($gasto_servicios_externos, 2); ?></td>
                                        <td class="text-muted ps-4"><i class="fas fa-ellipsis-h"></i> Otros Gastos</td>
                                        <td class="text-end fw-bold">S/ <?php echo number_format($gasto_otros, 2); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-body-tertiary fw-bold text-secondary">
            📈 Tendencia de Ventas (Últimos 7 días)
        </div>
        <div class="card-body">
            <canvas id="graficoVentas" style="max-height: 250px;"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('graficoVentas').getContext('2d');
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const textColor = isDark ? '#e0e0e0' : '#666';

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Ingresos Diarios (S/.)',
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderColor: '#0d6efd',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#0d6efd'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: textColor } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: isDark ? '#333' : '#eee' }, ticks: { color: textColor } },
                    x: { grid: { display: false }, ticks: { color: textColor } }
                }
            }
        });

        // Pequeña animación para el botón
        document.querySelector('.btn-success').addEventListener('mouseenter', function() {
            this.querySelector('.fa-sync-alt').classList.add('fa-spin');
        });
        document.querySelector('.btn-success').addEventListener('mouseleave', function() {
            this.querySelector('.fa-sync-alt').classList.remove('fa-spin');
        });
    </script>

<?php require_once 'pie.php'; ?>