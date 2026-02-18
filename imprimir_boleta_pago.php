<?php
require_once 'conexion.php';
require_once 'config.php';

if (!isset($_GET['id'])) { die("Error: Falta ID"); }
$id = $_GET['id'];

// Obtener datos del pago
$sql = "SELECT p.*, e.nombre, e.pago_dia 
        FROM pagos_personal p 
        JOIN personal e ON p.personal_id = e.id 
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$boleta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$boleta) die("Boleta no encontrada");

// --- LÓGICA DE FECHAS ---
$fecha_inicio = new DateTime($boleta['fecha_inicio_ciclo']);
$fecha_fin = (clone $fecha_inicio)->modify('+6 days');

// --- RECONSTRUCCIÓN DE LA TABLA DIARIA ---
// Definimos el orden exacto de tu semana laboral: Sábado primero
$dias_semana = ['Sabado', 'Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];

// Mapa robusto para detectar nombres con o sin tildes
$mapa_dias = [
        'Sáb'=>'Sabado', 'Sab'=>'Sabado',
        'Dom'=>'Domingo',
        'Lun'=>'Lunes',
        'Mar'=>'Martes',
        'Mié'=>'Miercoles', 'Mie'=>'Miercoles',
        'Jue'=>'Jueves',
        'Vie'=>'Viernes'
];

$tabla_dias = [];
foreach($dias_semana as $d) {
    // Inicializar vacío
    $tabla_dias[$d] = ['horas'=>0, 'dcto'=>0, 'total'=>0, 'estado'=>'DESCANSÓ'];
}

// Procesar el string guardado "Sáb(8h), Lun(8h)..."
$partes = explode(',', $boleta['dias_trabajados']);
foreach($partes as $parte) {
    $parte = trim($parte);

    // Extraer nombre corto del día (ej: Sáb)
    preg_match('/^([A-ZÁÉÍÓÚÑa-z]+)\(/u', $parte, $matches_nombre);
    $nombre_corto = isset($matches_nombre[1]) ? $matches_nombre[1] : '';

    // Buscar coincidencia en el mapa
    $nombre_completo = isset($mapa_dias[$nombre_corto]) ? $mapa_dias[$nombre_corto] : '';

    if ($nombre_completo) {
        // Horas
        preg_match('/(\d+)h/', $parte, $matches_horas);
        $horas = isset($matches_horas[1]) ? (int)$matches_horas[1] : 8;

        // Descuento
        preg_match('/-S\/([\d\.]+)/', $parte, $matches_dcto);
        $dcto = isset($matches_dcto[1]) ? (float)$matches_dcto[1] : 0;

        // Cálculo del día
        $pago_dia = $boleta['pago_dia'] - $dcto;
        if($horas < 4) $pago_dia = $pago_dia / 2; // Ajuste si trabajó medio día

        $tabla_dias[$nombre_completo] = [
                'horas' => $horas,
                'dcto' => $dcto,
                'total' => $pago_dia,
                'estado' => 'ASISTIÓ'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta - <?php echo $boleta['nombre']; ?></title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #fff; color: #333; }

        /* Marca de Agua */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.15;
            z-index: -1;
            width: 60%;
            pointer-events: none;
        }

        .top-bar { height: 15px; background: #e67e22; width: 100%; }

        .container { padding: 40px; max-width: 1100px; margin: 0 auto; }

        /* Cabecera */
        .header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; border-bottom: 2px solid #e67e22; padding-bottom: 10px; }
        .header-logo-text { font-size: 32px; font-weight: 800; color: #e67e22; letter-spacing: -1px; text-transform: uppercase; }
        .header-info { text-align: right; font-size: 12px; color: #777; }
        .header-title { font-size: 24px; font-weight: bold; color: #333; text-transform: uppercase; }

        /* Grid de Información (Ahora 3 columnas, sin Cargo) */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-box { background: #fdf2e9; border-left: 4px solid #e67e22; padding: 15px; }
        .info-label { font-size: 10px; font-weight: bold; color: #e67e22; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .info-value { font-size: 16px; font-weight: bold; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* Tabla */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); }
        th { background: #e67e22; color: white; padding: 12px; text-align: center; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 12px; border-bottom: 1px solid #eee; text-align: center; font-size: 13px; color: #555; }
        tr:nth-child(even) { background: #fcfcfc; }

        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .status-ok { background: #d4edda; color: #155724; }
        .status-no { background: #f8f9fa; color: #6c757d; }

        .amount { font-weight: bold; color: #333; }
        .discount { color: #dc3545; font-weight: bold; font-size: 12px; }

        .total-row td { background: #333; color: white; font-weight: bold; font-size: 14px; border: none; padding: 15px; text-transform: uppercase; }
        .total-row .grand-total { background: #e67e22; font-size: 20px; }

        /* Pie */
        .footer { margin-top: 50px; display: flex; justify-content: space-around; text-align: center; }
        .firma { border-top: 1px dashed #bbb; width: 200px; padding-top: 10px; font-size: 11px; color: #777; text-transform: uppercase; }

        @media print {
            .no-print { display: none; }
            body { background: white; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .container { padding: 20px; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
    <button onclick="window.print()" style="background: #333; color: white; border: none; padding: 10px 20px; font-weight: bold; cursor: pointer; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        🖨️ IMPRIMIR
    </button>
    <a href="personal.php" style="background: #e67e22; color: white; text-decoration: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; margin-left: 10px;">
        VOLVER
    </a>
</div>

<div class="top-bar"></div>

<img src="<?php echo $config['rutas']['logo']; ?>" class="watermark" onerror="this.style.display='none'">

<div class="container">

    <div class="header">
        <div>
            <div class="header-logo-text"><?php echo strtoupper($config['empresa']['nombre']); ?></div>
            <div class="header-title" style="margin-top: 5px;">BOLETA DE PAGO</div>
        </div>
        <div class="header-info">
            <strong><?php echo $config['empresa']['direccion']; ?></strong><br>
            RUC: <?php echo $config['empresa']['ruc']; ?><br>
            Telf: <?php echo $config['empresa']['telefono']; ?>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">Trabajador</div>
            <div class="info-value"><?php echo strtoupper($boleta['nombre']); ?></div>
        </div>
        <div class="info-box">
            <div class="info-label">Periodo</div>
            <div class="info-value" style="font-size: 14px;">
                <?php echo $fecha_inicio->format('d/m'); ?> al <?php echo $fecha_fin->format('d/m/Y'); ?>
            </div>
        </div>
        <div class="info-box">
            <div class="info-label">Tarifa Diaria</div>
            <div class="info-value">S/ <?php echo number_format($boleta['pago_dia'], 2); ?></div>
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th width="15%">DÍA</th>
            <th width="15%">ESTADO</th>
            <th width="15%">HORAS</th>
            <th width="25%">DESCUENTOS / OBS</th>
            <th width="30%">TOTAL NETO</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($tabla_dias as $nombre_dia => $data): ?>
            <tr>
                <td style="font-weight: bold;"><?php echo $nombre_dia; ?></td>

                <td>
                    <?php if($data['estado'] == 'ASISTIÓ'): ?>
                        <span class="status-badge status-ok">ASISTIÓ</span>
                    <?php else: ?>
                        <span class="status-badge status-no">FALTA</span>
                    <?php endif; ?>
                </td>

                <td>
                    <?php echo ($data['horas'] > 0) ? $data['horas'] . ' hrs' : '-'; ?>
                </td>

                <td>
                    <?php if($data['dcto'] > 0): ?>
                        <span class="discount">- S/ <?php echo number_format($data['dcto'], 2); ?></span>
                    <?php else: ?>
                        <span style="color: #ccc;">-</span>
                    <?php endif; ?>
                </td>

                <td class="amount">
                    <?php echo ($data['total'] > 0) ? 'S/ ' . number_format($data['total'], 2) : '-'; ?>
                </td>
            </tr>
        <?php endforeach; ?>

        <tr class="total-row">
            <td colspan="3" style="text-align: right; padding-right: 20px;">TOTAL A PAGAR</td>
            <td></td>
            <td class="grand-total">S/ <?php echo number_format($boleta['monto_total'], 2); ?></td>
        </tr>
        </tbody>
    </table>

    <div style="margin-top: 10px; color: #777; font-size: 12px; font-style: italic;">
        * Observaciones: <?php echo $boleta['observaciones'] ? $boleta['observaciones'] : 'Ninguna'; ?>
    </div>

    <div class="footer">
        <div class="firma">
            <?php echo strtoupper($config['empresa']['nombre']); ?><br>
            EMPLEADOR
        </div>
        <div class="firma">
            <?php echo strtoupper($boleta['nombre']); ?><br>
            RECIBÍ CONFORME
        </div>
    </div>

</div>

</body>
</html>