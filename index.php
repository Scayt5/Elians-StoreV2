<?php
require_once 'conexion.php';

// 1. Traemos los últimos 5 pedidos (Tu lógica original)
$sql_recientes = "SELECT * FROM pedidos ORDER BY id DESC LIMIT 5";
$pedidos_recientes = $conn->query($sql_recientes)->fetchAll(PDO::FETCH_ASSOC);

// 2. Traemos la lista de Colegios/Proyectos para el autocompletado (Nueva función)
$sql_proy = "SELECT DISTINCT proyecto FROM pedidos WHERE proyecto != 'Varios' AND proyecto IS NOT NULL ORDER BY proyecto ASC";
$lista_proyectos = $conn->query($sql_proy)->fetchAll(PDO::FETCH_COLUMN);

// 3. CARGAMOS EL DISEÑO MAESTRO (Aquí está la Barra Lateral y los Estilos)
require_once 'encabezado.php';
?>

    <div class="row">

        <div class="col-lg-5 mb-4">
            <div class="card shadow border-0 card-pedido">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold"><i class="fas fa-plus-circle"></i> Nuevo Pedido</h5>
                    <span class="badge bg-white text-primary">
                    <i class="fas fa-clock"></i> <?php echo date('d/m H:i'); ?>
                </span>
                </div>
                <div class="card-body p-4">
                    <form action="guardar_pedido.php" method="POST">

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">CLIENTE</label>
                            <input type="text" name="cliente_nombre" class="form-control form-control-lg bg-light" placeholder="Nombre completo" required autofocus>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small text-muted">TELÉFONO</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-phone"></i></span>
                                    <input type="text" name="cliente_telefono" class="form-control" placeholder="999...">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small text-muted">EMAIL (OPCIONAL)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="cliente_email" class="form-control" placeholder="@gmail.com">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">DIRECCIÓN (OPCIONAL)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-map-marker-alt"></i></span>
                                <input type="text" name="cliente_direccion" class="form-control" placeholder="Av. Principal 123...">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-primary">🏫 INSTITUCIÓN / GRUPO</label>
                            <input type="text" name="proyecto" class="form-control border-primary" list="lista-proyectos-dl" placeholder="Ej: Colegio San Agustín..." autocomplete="off">
                            <datalist id="lista-proyectos-dl">
                                <?php foreach($lista_proyectos as $proy): ?>
                                <option value="<?php echo $proy; ?>">
                                    <?php endforeach; ?>
                            </datalist>
                            <div class="form-text small">Si lo dejas vacío, la IA intentará detectarlo sola.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">DESCRIPCIÓN DEL TRABAJO</label>
                            <textarea name="descripcion" class="form-control bg-light" rows="3" placeholder="Ej: 6 Polos pique con logo..." required></textarea>
                        </div>

                        <div class="row mb-3 p-3 rounded mx-0 border bg-light">
                            <div class="col-12 mb-2">
                                <label class="form-label fw-bold text-success">💰 PRECIO TOTAL (S/.)</label>
                                <input type="number" step="0.5" id="txtTotal" name="costo_total" class="form-control fw-bold form-control-lg border-success" placeholder="0.00" required oninput="calcularSaldo()">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold text-warning">💵 A CUENTA</label>
                                <div class="input-group">
                                    <input type="number" step="0.5" id="txtAcuenta" name="a_cuenta" class="form-control fw-bold border-warning" placeholder="0.00" value="0.00" oninput="calcularSaldo()">
                                    <button type="button" class="btn btn-success" onclick="pagoCompleto()">
                                        <i class="fas fa-check-double"></i> Todo
                                    </button>
                                </div>
                                <small id="lblSaldo" class="text-danger fw-bold mt-1 d-block text-end">Saldo: S/. 0.00</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small text-muted">FECHA DE ENTREGA</label>
                            <input type="date" name="fecha_entrega" class="form-control" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold shadow-sm">
                                <i class="fas fa-save me-2"></i> GUARDAR E IMPRIMIR
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <h5 class="mb-3 text-secondary"><i class="fas fa-history"></i> Actividad Reciente</h5>
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($pedidos_recientes as $pedido):
                            $saldo = $pedido['costo_total'] - $pedido['a_cuenta'];
                            // Lógica de colores para estado
                            $bg_estado = match($pedido['estado']) {
                                'Entregado' => 'bg-dark',
                                'Listo para Recoger' => 'bg-success',
                                'Pendiente' => 'bg-warning text-dark',
                                default => 'bg-info text-dark'
                            };
                            ?>
                            <tr>
                                <td class="fw-bold opacity-75"><?php echo $pedido['id']; ?></td>
                                <td class="text-start">
                                    <div class="fw-bold"><?php echo $pedido['cliente_nombre']; ?></div>
                                    <div class="small opacity-75 text-truncate" style="max-width: 200px;">
                                        <?php echo $pedido['descripcion']; ?>
                                    </div>
                                </td>
                                <td class="fw-bold <?php echo ($saldo > 0) ? 'text-danger' : 'text-success'; ?>">
                                    S/. <?php echo number_format($saldo, 2); ?>
                                </td>
                                <td>
                                <span class="badge rounded-pill <?php echo $bg_estado; ?>">
                                    <?php echo strtoupper($pedido['estado']); ?>
                                </span>
                                </td>
                                <td>
                                    <a href="generar_factura.php?id=<?php echo $pedido['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Imprimir Ticket">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function pagoCompleto() {
            let total = document.getElementById('txtTotal').value;
            if(total) {
                document.getElementById('txtAcuenta').value = total;
                calcularSaldo();
            }
        }

        function calcularSaldo() {
            let total = parseFloat(document.getElementById('txtTotal').value) || 0;
            let acuenta = parseFloat(document.getElementById('txtAcuenta').value) || 0;
            let saldo = total - acuenta;
            let label = document.getElementById('lblSaldo');

            if (saldo <= 0) {
                label.className = "text-success fw-bold mt-1 d-block text-end";
                label.innerHTML = "✅ ¡Pagado Completo!";
            } else {
                label.className = "text-danger fw-bold mt-1 d-block text-end";
                label.innerHTML = "Saldo Pendiente: S/. " + saldo.toFixed(2);
            }
        }
    </script>

<?php
// 4. CARGAMOS EL CIERRE DEL DISEÑO
require_once 'pie.php';
?>