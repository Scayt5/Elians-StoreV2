<?php
require_once 'conexion.php';
date_default_timezone_set('America/Lima');

if (!isset($_GET['id']) || !isset($_GET['inicio'])) die("Error de datos");

$id = $_GET['id'];
$inicio = $_GET['inicio'];
$fin = date('Y-m-d', strtotime('+6 days', strtotime($inicio)));

$stmt = $conn->prepare("SELECT * FROM empleados WHERE id = ?");
$stmt->execute([$id]);
$empleado = $stmt->fetch(PDO::FETCH_ASSOC);

// Cabeceras para forzar descarga Excel
$filename = "Boleta_" . preg_replace('/[^A-Za-z0-9]/', '', $empleado['nombre']) . "_" . $inicio . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Generar tabla HTML (Excel la interpreta)
echo "<meta charset='UTF-8'>";
echo "<table border='1'>";
echo "<tr><th colspan='7' style='background:#f39c12; color:white; font-size:14pt;'>BOLETA DE PAGO - ELIANS STORE</th></tr>";
echo "<tr><td colspan='7'><strong>Empleado:</strong> " . strtoupper($empleado['nombre']) . "</td></tr>";
echo "<tr><td colspan='7'><strong>Periodo:</strong> $inicio al $fin</td></tr>";
echo "<tr><td colspan='7'><strong>Tarifa por Hora:</strong> S/ " . $empleado['pago_hora'] . "</td></tr>";
echo "<tr><td colspan='7'></td></tr>";

echo "<tr>
        <th style='background:#333; color:white;'>Fecha</th>
        <th style='background:#333; color:white;'>Día</th>
        <th style='background:#333; color:white;'>Entrada</th>
        <th style='background:#333; color:white;'>Salida</th>
        <th style='background:#333; color:white;'>Horas Trab.</th>
        <th style='background:#333; color:white;'>Dscto (S/.)</th>
        <th style='background:#333; color:white;'>Total (S/.)</th>
      </tr>";

$t_pago = 0;
$cursor = strtotime($inicio);
while ($cursor <= strtotime($fin)) {
    $fecha = date('Y-m-d', $cursor);
    $dia = date('D', $cursor);
    $dias_esp = ['Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mié','Thu'=>'Jue','Fri'=>'Vie','Sat'=>'Sáb','Sun'=>'Dom'];

    // Obtener asistencia
    $stmt = $conn->prepare("SELECT * FROM asistencia WHERE empleado_id = ? AND fecha = ?");
    $stmt->execute([$id, $fecha]);
    $asist = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener descuento
    $stmt2 = $conn->prepare("SELECT * FROM movimientos_personal WHERE nombre_personal = ? AND tipo='DESCUENTO' AND fecha LIKE ?");
    $stmt2->execute([$empleado['nombre'], "$fecha%"]);
    $desc = $stmt2->fetch(PDO::FETCH_ASSOC);

    $h = $asist ? $asist['horas'] : 0;
    $m_desc = $desc ? $desc['monto'] : 0;
    $neto = ($h * $empleado['pago_hora']) - $m_desc;
    $t_pago += $neto;

    echo "<tr>
            <td>$fecha</td>
            <td>" . $dias_esp[$dia] . "</td>
            <td>" . ($asist ? date('H:i', strtotime($asist['entrada'])) : '-') . "</td>
            <td>" . ($asist ? date('H:i', strtotime($asist['salida'])) : '-') . "</td>
            <td>" . str_replace('.', ',', $h) . "</td>
            <td>" . number_format($m_desc, 2) . "</td>
            <td>" . number_format($neto, 2) . "</td>
          </tr>";

    $cursor = strtotime('+1 day', $cursor);
}

echo "<tr><td colspan='6' align='right'><strong>TOTAL A PAGAR:</strong></td><td style='background:#f1c40f;'><strong>S/ " . number_format($t_pago, 2) . "</strong></td></tr>";
echo "</table>";
?>