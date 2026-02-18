<?php
require_once 'conexion.php';
require_once 'config.php';

// Esta consulta agrupa tus pedidos por la clasificación que hizo la IA
$sql = "SELECT proyecto, 
               COUNT(*) as cantidad, 
               SUM(costo_total) as total_dinero, 
               SUM(costo_total - a_cuenta) as deuda_total 
        FROM pedidos 
        GROUP BY proyecto 
        ORDER BY proyecto ASC";

$grupos = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clasificación - <?php echo $config['empresa']['nombre']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="btn-group shadow-sm">
            <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-tshirt"></i> Venta</a>
            <a href="proyectos.php" class="btn btn-primary active"><i class="fas fa-layer-group"></i> Por Colegios/Grupos</a>
            <a href="historial.php" class="btn btn-outline-secondary"><i class="fas fa-folder-open"></i> Historial</a>
        </div>
    </div>

    <h3 class="mb-4 text-primary fw-bold">📂 Pedidos Organizados Automáticamente</h3>

    <?php if(empty($grupos)): ?>
        <div class="alert alert-info text-center">
            Aún no hay pedidos registrados o clasificados.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach($grupos as $g): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100 border-top border-4 border-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title fw-bold text-dark">
                                    <i class="fas fa-folder text-warning"></i> <?php echo $g['proyecto']; ?>
                                </h5>
                                <span class="badge bg-primary rounded-pill"><?php echo $g['cantidad']; ?> Pedidos</span>
                            </div>

                            <hr>

                            <ul class="list-group list-group-flush mb-3 small">
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span>Facturado Total:</span>
                                    <strong>S/ <?php echo number_format($g['total_dinero'], 2); ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0 text-danger">
                                    <span>Deuda Pendiente:</span>
                                    <strong>S/ <?php echo number_format($g['deuda_total'], 2); ?></strong>
                                </li>
                            </ul>

                            <div class="d-grid">
                                <a href="historial.php?buscar=<?php echo urlencode($g['proyecto']); ?>" class="btn btn-outline-primary btn-sm">
                                    Ver Pedidos de este Grupo <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>