<?php
require_once 'conexion.php';
require_once 'config.php';
date_default_timezone_set('America/Lima');

if (!isset($_GET['id'])) header("Location: personal.php");
$id = $_GET['id'];

// 1. Obtener Empleado
$stmt = $conn->prepare("SELECT * FROM empleados WHERE id = ?");
$stmt->execute([$id]);
$empleado = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Consulta de Historial Agrupado por Semana
// Esta consulta mágica suma todo lo que pasó en cada semana del año
$sql = "
    SELECT 
        YEAR(fecha) as anio,
        WEEK(fecha, 1) as semana, 
        MIN(fecha) as inicio_semana,
        MAX(fecha) as fin_semana,
        SUM(horas) as total_horas
    FROM asistencia 
    WHERE empleado_id = ? 
    GROUP BY anio, semana 
    ORDER BY anio DESC, semana DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Historial - <?php echo $empleado['nombre']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="detalle_personal.php?id=<?php echo $id; ?>" class="btn btn-outline-dark">
            <i class="fas fa-arrow-left"></i> Volver al Editor
        </a>
        <h3 class="fw-bold text-primary">📂 Historial: <?php echo strtoupper($empleado['nombre']); ?></h3>
    </div>

    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5 class="m-0"><i class="fas fa-list"></i> Registro de Pagos Semanales</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead class="table-light">
                <tr>
                    <th>Periodo (Semana)</th>
                    <th class="text-center">Total Horas</th>
                    <th class="text-center">Tarifa Histórica</th>
                    <th class="text-end">Pago Calculado</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php if(count($historial) == 0): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No hay registros de asistencia aún.</td></tr>
                <?php endif; ?>

                <?php foreach($historial as $h):
                    // Calculamos el pago aproximado (Nota: para exactitud total deberíamos guardar el histórico de pagos en otra tabla,
                    // pero esto calcula en base a la tarifa actual y horas registradas).
                    $monto = $h['total_horas'] * $empleado['pago_hora'];

                    // Formato de fechas
                    $inicio = date('d/m/Y', strtotime($h['inicio_semana']));
                    $fin = date('d/m/Y', strtotime($h['fin_semana']));
                    ?>
                    <tr>
                        <td class="px-3">
                            <span class="fw-bold d-block">Semana <?php echo $h['semana']; ?> del <?php echo $h['anio']; ?></span>
                            <small class="text-muted">Del <?php echo $inicio; ?> al <?php echo $fin; ?></small>
                        </td>
                        <td class="text-center fs-5 text-primary fw-bold">
                            <?php echo $h['total_horas']; ?> hrs
                        </td>
                        <td class="text-center text-muted">
                            S/ <?php echo $empleado['pago_hora']; ?>
                        </td>
                        <td class="text-end fs-5 fw-bold text-success px-4">
                            S/ <?php echo number_format($monto, 2); ?>*
                        </td>
                        <td class="text-end px-3">
                            <a href="detalle_personal.php?id=<?php echo $id; ?>&inicio=<?php echo $h['inicio_semana']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted small">
            * El pago calculado en el historial se basa en las horas registradas y la tarifa actual.
        </div>
    </div>
</div>

</body>
</html>
