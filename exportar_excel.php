<?php
require_once 'conexion.php';
require_once 'config.php';

// Detectar si es un pedido individual o reporte general
$id_pedido = isset($_GET['id']) ? $_GET['id'] : null;

if ($id_pedido) {
    // CASO A: UN SOLO PEDIDO
    $filename = "Pedido_N" . str_pad($id_pedido, 6, "0", STR_PAD_LEFT) . ".xls";
    $titulo_reporte = "DETALLE DEL PEDIDO N° " . str_pad($id_pedido, 6, "0", STR_PAD_LEFT);

    // Consulta segura para un solo ID
    $stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->execute([$id_pedido]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // CASO B: REPORTE GENERAL (TODOS)
    $filename = "Reporte_General_Ventas_" . date('Ymd') . ".xls";
    $titulo_reporte = "REPORTE GENERAL DE VENTAS - " . strtoupper($config['empresa']['nombre']);

    // Consulta general
    $sql = "SELECT * FROM pedidos ORDER BY id DESC";
    $pedidos = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// Encabezados para obligar al navegador a descargar como Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// INICIO DEL EXCEL
echo "
<meta charset='UTF-8'>
<table border='1'>
    <tr style='background-color: #4CAF50; color: white;'>
        <th colspan='8' style='font-size: 16px; text-align: center; height: 40px; vertical-align: middle;'>
            $titulo_reporte
        </th>
    </tr>
    <tr style='background-color: #ddd; font-weight:bold;'>
        <th>N° Boleta</th>
        <th>Fecha Emisión</th>
        <th>Cliente</th>
        <th>Teléfono</th>
        <th>Descripción del Trabajo</th>
        <th>Estado</th>
        <th>Monto Total (S/.)</th>
        <th>Saldo Pendiente (S/.)</th>
    </tr>
";

$total_ingresos = 0;
$total_deuda = 0;

foreach ($pedidos as $p) {
    $estado = ucfirst($p['estado']);
    // Colores simples para Excel
    $color_estado = ($p['saldo'] > 0) ? '#ffcccc' : '#ccffcc';

    $total_ingresos += $p['costo_total'];
    $total_deuda += $p['saldo'];

    echo "<tr>
        <td style='text-align:center;'>B001-" . str_pad($p['id'], 6, "0", STR_PAD_LEFT) . "</td>
        <td style='text-align:center;'>" . date('d/m/Y', strtotime($p['fecha_recepcion'])) . "</td>
        <td>" . mb_strtoupper($p['cliente_nombre']) . "</td>
        <td style='text-align:center;'>" . $p['cliente_telefono'] . "</td>
        <td>" . $p['descripcion'] . "</td>
        <td style='background-color: $color_estado; text-align:center;'>$estado</td>
        <td style='text-align:right;'>" . number_format($p['costo_total'], 2) . "</td>
        <td style='text-align:right; color:red;'>" . number_format($p['saldo'], 2) . "</td>
    </tr>";
}

// Fila final de totales
echo "
    <tr style='font-weight: bold; background-color: #eee;'>
        <td colspan='6' style='text-align: right;'>TOTALES:</td>
        <td style='text-align:right;'>S/. " . number_format($total_ingresos, 2) . "</td>
        <td style='text-align:right; color: red;'>S/. " . number_format($total_deuda, 2) . "</td>
    </tr>
</table>";
?>