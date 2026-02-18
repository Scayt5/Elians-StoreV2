<?php
require_once 'conexion.php';

// 1. Obtener grupos IA
$sql_grupos = "SELECT proyecto, COUNT(*) as cantidad, SUM(costo_total) as total FROM pedidos GROUP BY proyecto ORDER BY total DESC";
$grupos = $conn->query($sql_grupos)->fetchAll(PDO::FETCH_ASSOC);

// 2. Obtener lista de pedidos
$pedidos = $conn->query("SELECT * FROM pedidos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// CARGAMOS EL DISEÑO NUEVO
require_once 'encabezado.php';
?>

<div class="row mb-4">
    <div class="col-12 mb-2">
        <h5 class="text-primary fw-bold"><i class="fas fa-chart-pie"></i> Distribución Detectada por IA</h5>
    </div>
    <?php foreach($grupos as $g): ?>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 h-100 border-start border-4 border-primary">
                <div class="card-body">
                    <h6 class="fw-bold text-dark text-uppercase small"><?php echo $g['proyecto']; ?></h6>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="badge bg-primary rounded-pill"><?php echo $g['cantidad']; ?> Pedidos</span>
                        <span class="fw-bold text-success small">S/ <?php echo number_format($g['total'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card shadow border-0">
    <div class="card-header bg-white py-3">
        <h6 class="m-0 fw-bold text-secondary"><i class="fas fa-list-ul"></i> Explorador de Pedidos</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Clasificación (IA)</th>
                <th>Cliente</th>
                <th>Descripción (Vista Rápida)</th>
                <th>Estado</th>
                <th class="text-end">Acción</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($pedidos as $p):
                $jsonPedido = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
                ?>
                <tr>
                    <td class="fw-bold">#<?php echo $p['id']; ?></td>
                    <td><span class="badge bg-light text-dark border"><?php echo $p['proyecto']; ?></span></td>
                    <td><?php echo $p['cliente_nombre']; ?></td>
                    <td><div class="text-truncate text-muted" style="max-width: 200px;"><?php echo $p['descripcion']; ?></div></td>
                    <td>
                        <?php
                        $bg = match($p['estado']) { 'Entregado' => 'dark', 'Listo para Recoger' => 'success', default => 'warning' };
                        ?>
                        <span class="badge bg-<?php echo $bg; ?>"><?php echo $p['estado']; ?></span>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-primary shadow-sm" onclick="verDetalle(<?php echo $jsonPedido; ?>)">
                            <i class="fas fa-eye"></i> Ver Todo
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-search-plus"></i> Detalle #<span id="mdlId"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-7 border-end">
                        <h4 id="mdlCliente" class="fw-bold text-dark mb-0"></h4>
                        <p class="mb-3 text-muted small">
                            <i class="fas fa-phone"></i> <span id="mdlTelefono"></span> |
                            <i class="fas fa-envelope"></i> <span id="mdlEmail"></span>
                        </p>

                        <div class="alert alert-light border">
                            <h6 class="fw-bold text-primary small">DESCRIPCIÓN COMPLETA:</h6>
                            <p id="mdlDescripcion" class="mb-0" style="white-space: pre-line;"></p>
                        </div>

                        <div class="d-flex justify-content-between bg-light p-3 rounded mt-3">
                            <div class="text-center"><small class="d-block text-muted">Total</small><span class="fw-bold" id="mdlTotal"></span></div>
                            <div class="text-center border-start border-end px-3"><small class="d-block text-muted">A Cuenta</small><span class="fw-bold text-success" id="mdlAcuenta"></span></div>
                            <div class="text-center"><small class="d-block text-muted">Saldo</small><span class="fw-bold text-danger" id="mdlSaldo"></span></div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <h6 class="text-muted fw-bold text-uppercase small mb-3">Línea de Tiempo</h6>
                        <ul class="list-unstyled" id="timelineContainer"></ul>
                        <div class="mt-4 text-center">
                            <small class="text-muted">Grupo IA:</small><br>
                            <span class="badge bg-warning text-dark mt-1" id="mdlProyecto"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" id="btnImprimir" target="_blank" class="btn btn-danger"><i class="fas fa-file-pdf"></i> PDF</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'pie.php'; ?>

<script>
    function verDetalle(pedido) {
        document.getElementById('mdlId').innerText = pedido.id;
        document.getElementById('mdlCliente').innerText = pedido.cliente_nombre;
        document.getElementById('mdlTelefono').innerText = pedido.cliente_telefono || '-';
        document.getElementById('mdlEmail').innerText = pedido.cliente_email || '-';
        document.getElementById('mdlDescripcion').innerText = pedido.descripcion;
        document.getElementById('mdlProyecto').innerText = pedido.proyecto;
        document.getElementById('mdlTotal').innerText = 'S/ ' + parseFloat(pedido.costo_total).toFixed(2);
        document.getElementById('mdlAcuenta').innerText = 'S/ ' + parseFloat(pedido.a_cuenta).toFixed(2);
        document.getElementById('mdlSaldo').innerText = 'S/ ' + (pedido.costo_total - pedido.a_cuenta).toFixed(2);
        document.getElementById('btnImprimir').href = 'generar_factura.php?id=' + pedido.id;

        const estados = ['Pendiente', 'En Corte', 'En Costura', 'En Acabado', 'Listo para Recoger', 'Entregado'];
        let htmlTL = '';
        let pasado = true;
        estados.forEach(est => {
            let color = 'text-muted';
            let icon = '<i class="far fa-circle"></i>';
            if (pedido.estado === est) { color = 'text-primary fw-bold'; icon = '<i class="fas fa-spinner fa-spin"></i>'; pasado = false; }
            else if (pasado) { color = 'text-success'; icon = '<i class="fas fa-check-circle"></i>'; }

            htmlTL += `<li class="mb-2 ${color}">${icon} ${est}</li>`;
        });
        document.getElementById('timelineContainer').innerHTML = htmlTL;
        new bootstrap.Modal(document.getElementById('modalDetalle')).show();
    }
</script>