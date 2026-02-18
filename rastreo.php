<?php
require_once 'conexion.php';
require_once 'config.php';

// Validamos que venga un ID
if (!isset($_GET['id'])) { die("<h1>Error: Código de pedido no válido.</h1>"); }
$id = $_GET['id'];

// Buscamos el pedido
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) { die("<h1>Pedido no encontrado.</h1>"); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rastreo #<?php echo $pedido['id']; ?> - Elians Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .timeline { border-left: 3px solid #e9ecef; padding-left: 30px; list-style: none; margin-top: 20px; }
        .timeline-item { position: relative; margin-bottom: 30px; }
        .timeline-item::before {
            content: ''; position: absolute; left: -39px; top: 0;
            width: 20px; height: 20px; border-radius: 50%; background: #fff; border: 4px solid #dee2e6;
        }
        .timeline-item.active::before { background: #0d6efd; border-color: #0d6efd; box-shadow: 0 0 0 4px rgba(13,110,253,0.2); }
        .timeline-item.completed::before { background: #198754; border-color: #198754; }

        .card-status { border: none; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .header-logo { max-height: 60px; }
    </style>
</head>
<body>

<div class="container py-4" style="max-width: 600px;">

    <div class="text-center mb-4">
        <img src="logo.jpg" class="header-logo mb-2" onerror="this.style.display='none'">
        <h4 class="fw-bold text-dark">Seguimiento de Pedido</h4>
    </div>

    <div class="card card-status bg-white p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="text-muted small">NRO PEDIDO</span>
                <h2 class="fw-bold text-primary m-0">#<?php echo str_pad($pedido['id'], 5, '0', STR_PAD_LEFT); ?></h2>
            </div>
            <div class="text-end">
                <span class="text-muted small">FECHA ENTREGA</span>
                <h5 class="fw-bold text-dark m-0"><?php echo date('d/m', strtotime($pedido['fecha_entrega'])); ?></h5>
            </div>
        </div>

        <div class="alert alert-light border">
            <strong>Hola, <?php echo explode(' ', $pedido['cliente_nombre'])[0]; ?>.</strong><br>
            Tu pedido de <em>"<?php echo substr($pedido['descripcion'], 0, 30); ?>..."</em> está avanzando.
        </div>

        <h6 class="text-uppercase text-muted fw-bold small mt-4">Línea de Tiempo</h6>
        <ul class="timeline">
            <?php
            $estados = ['Pendiente', 'En Corte', 'En Costura', 'En Acabado', 'Listo para Recoger', 'Entregado'];
            $pasado = true;

            foreach($estados as $est) {
                $clase = '';
                $icono = '';

                if($pedido['estado'] == $est) {
                    $clase = 'active';
                    $icono = '<span class="badge bg-primary">EN PROCESO ACTUAL</span>';
                    $pasado = false;
                } elseif ($pasado) {
                    $clase = 'completed';
                    $icono = '<span class="text-success small fw-bold"><i class="fas fa-check"></i> COMPLETADO</span>';
                } else {
                    $clase = '';
                    $icono = '<span class="text-muted small">PENDIENTE</span>';
                }

                echo "<li class='timeline-item $clase'>";
                echo "<h6 class='fw-bold mb-1'>$est</h6>";
                echo "$icono";
                echo "</li>";
            }
            ?>
        </ul>

        <?php if($pedido['estado'] == 'Listo para Recoger'): ?>
            <div class="alert alert-success text-center mt-3 fw-bold animate-pulse">
                🎉 ¡TU PEDIDO ESTÁ LISTO! <br> PUEDES PASAR A RECOGERLO.
            </div>
        <?php endif; ?>

        <div class="text-center mt-4 text-muted small">
            Elians Store - Av. Sinchi Roca, Comas<br>
            <a href="https://wa.me/51993516112" class="btn btn-success btn-sm mt-2"><i class="fab fa-whatsapp"></i> Contactar</a>
        </div>
    </div>
</div>

</body>
</html>