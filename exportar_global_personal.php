<?php
require_once 'conexion.php';

// Definir el ciclo actual (Sábado pasado)
$hoy = new DateTime();
$dia_semana = $hoy->format('w');
if ($dia_semana == 6) { $fecha_sabado = $hoy; }
elseif ($dia_semana == 0) { $fecha_sabado = (clone $hoy)->modify('-1 day'); }
elseif ($dia_semana == 1) { $fecha_sabado = (clone $hoy)->modify('-2 days'); }
else { $fecha_sabado = (clone $hoy)->modify('last saturday'); }

$fecha_sql = $fecha_sabado->format('Y-m-d');

// Nombre del archivo para descarga
$filename = "Reporte_Personal_Ciclo_" . $fecha_sabado->format('d-m-Y') . ".xls";

// Configurar cabeceras para forzar descarga en Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Consulta Maestra: Une personal con sus pagos de ESTA semana
$sql = "SELECT p.nombre, p.cargo, p.pago_dia, 
               pag.dias_trabajados, pag.monto_total, pag.descuentos, pag.observaciones
        FROM personal p
        LEFT JOIN pagos_personal pag 
        ON p.id = pag.personal_id AND pag.fecha_inicio_ciclo = '$fecha_sql'";

$resultados = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<meta charset="UTF-8">
<table border="1">
    <thead>
    <tr style="background-color: #0d6efd; color: white;">
        <th colspan="7" style="font-size: 16px; text-align: center; padding: 10px;">
            REPORTE DE PERSONAL - CICLO: <?php echo $fecha_sabado->format('d/m/Y'); ?> AL <?php echo (clone $fecha_sabado)->modify('+2 days')->format('d/m/Y'); ?>
        </th>
    </tr>
    <tr style="background-color: #f0f0f0; font-weight: bold;">
        <th>Empleado</th>
        <th>Cargo</th>
        <th>Tarifa Diaria</th>
        <th>Días Trabajados</th>
        <th>Descuentos</th>
        <th>A Pagar (Neto)</th>
        <th>Observaciones</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($resultados as $row):
        $estado_color = empty($row['monto_total']) ? '#ffcccc' : '#ccffcc';
        ?>
        <tr>
            <td><?php echo $row['nombre']; ?></td>
            <td><?php echo $row['cargo']; ?></td>
            <td>S/ <?php echo number_format($row['pago_dia'], 2); ?></td>

            <td style="text-align: center;">
                <?php echo $row['dias_trabajados'] ? $row['dias_trabajados'] : '<span style="color:red;">No registrado</span>'; ?>
            </td>

            <td style="color: red;"><?php echo $row['descuentos'] > 0 ? '- S/ '.number_format($row['descuentos'], 2) : '-'; ?></td>

            <td style="background-color: <?php echo $estado_color; ?>; font-weight: bold;">
                <?php echo $row['monto_total'] ? 'S/ '.number_format($row['monto_total'], 2) : 'Pendiente'; ?>
            </td>

            <td><?php echo $row['observaciones']; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>