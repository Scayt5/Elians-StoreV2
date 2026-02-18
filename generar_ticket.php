<?php
// Cargar librerías locales (QR y otras)
require_once 'vendor/autoload.php';
require_once 'conexion.php';
require_once 'config.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

date_default_timezone_set('America/Lima');

if (!isset($_GET['id'])) { die("Error: Falta ID"); }
$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) { die("Pedido no encontrado"); }

// Cálculos
$total = $pedido['costo_total'];
$subtotal_base = $total / 1.18;
$igv = $total - $subtotal_base;
$saldo_pendiente = $total - $pedido['a_cuenta'];

// Conversión a Letras
$total_letras = $total;
if (class_exists('NumberFormatter')) {
    $formatter = new NumberFormatter("es", NumberFormatter::SPELLOUT);
    $total_letras = strtoupper($formatter->format($total));
}

// --- GENERACIÓN DE QR LOCAL (OFFLINE) ---
// Datos para el QR
$contenido_qr  = $config['empresa']['ruc'] . "|B001|" . str_pad($pedido['id'], 8, '0', STR_PAD_LEFT) . "|";
$contenido_qr .= number_format($total, 2) . "|" . date('d/m/Y', strtotime($pedido['fecha_recepcion']));

// Configuración del QR
$opciones_qr = new QROptions([
        'version'      => 5,       // Tamaño/Complejidad
        'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'     => QRCode::ECC_L, // Nivel de corrección de errores
        'scale'        => 5,       // Tamaño de píxeles
        'imageBase64'  => true,    // Para incrustar directamente en HTML
]);

// Generar la imagen Base64
$qrcode = new QRCode($opciones_qr);
$imagen_qr = $qrcode->render($contenido_qr);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket B001-<?php echo $pedido['id']; ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; margin: 0; padding: 0; background: #fff; color: #000; }
        .ticket { width: 78mm; margin: 0 auto; padding: 5px; background: white; }

        @media print {
            .ticket { width: 100%; margin: 0; padding: 0; }
            .no-print { display: none; }
            @page { margin: 0; size: auto; }
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }

        .logo-img { width: 50%; margin: 0 auto 5px auto; display: block; filter: grayscale(100%); }

        .header-text { margin-bottom: 10px; line-height: 1.2; }
        .doc-title { font-weight: bold; margin-top: 5px; font-size: 14px; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 5px 0; }

        .info-block { margin-bottom: 5px; line-height: 1.3; border-bottom: 1px dashed #000; padding-bottom: 5px; }

        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th { border-bottom: 1px dashed #000; padding: 3px 0; text-align: left; font-size: 11px; }
        td { padding: 4px 0; vertical-align: top; }

        .totals-block { margin-top: 5px; border-top: 1px dashed #000; padding-top: 5px; }
        .row-total { display: flex; justify-content: space-between; margin-bottom: 2px; }

        .son-letras { border-top: 1px dashed #000; padding: 5px 0; margin: 5px 0; font-size: 10px; font-style: italic; }

        .pagos-block { margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 5px; }

        .footer { text-align: center; font-size: 10px; margin-top: 10px; line-height: 1.3; }

        .btn-print { width: 100%; background: #333; color: white; padding: 10px; font-weight: bold; border: none; cursor: pointer; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="ticket">
    <button onclick="window.print()" class="btn-print no-print">IMPRIMIR TICKET</button>

    <div class="center header-text">
        <img src="<?php echo $config['rutas']['logo']; ?>" class="logo-img" onerror="this.style.display='none'">

        <div style="font-size: 16px; font-weight: bold;"><?php echo $config['empresa']['nombre']; ?></div>
        <div><?php echo $config['empresa']['direccion']; ?></div>
        <div>RUC: <?php echo $config['empresa']['ruc']; ?></div>
        <div>Telf: <?php echo $config['empresa']['telefono']; ?></div>

        <div class="doc-title">BOLETA DE VENTA<br>ELECTRÓNICA</div>
        <div class="bold" style="font-size: 14px; margin-top: 5px;">B001-<?php echo str_pad($pedido['id'], 8, '0', STR_PAD_LEFT); ?></div>
    </div>

    <div class="info-block">
        <b>FECHA:</b> <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_recepcion'])); ?><br>
        <b>CLIENTE:</b> <?php echo strtoupper($pedido['cliente_nombre']); ?><br>
        <b>CELULAR:</b> <?php echo $pedido['cliente_telefono']; ?>
    </div>

    <table>
        <thead>
        <tr>
            <th width="10%">CANT</th>
            <th width="65%">DESCRIPCIÓN</th>
            <th width="25%" class="right">TOTAL</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="center">1</td>
            <td><?php echo strtoupper($pedido['descripcion']); ?></td>
            <td class="right"><?php echo number_format($total, 2); ?></td>
        </tr>
        </tbody>
    </table>

    <div class="totals-block">
        <div class="row-total"><span>OP. GRAVADA:</span> <span><?php echo number_format($subtotal_base, 2); ?></span></div>
        <div class="row-total"><span>I.G.V. (18%):</span> <span><?php echo number_format($igv, 2); ?></span></div>
        <div class="row-total bold" style="font-size: 14px; margin-top: 5px;">
            <span>IMPORTE TOTAL:</span> <span>S/ <?php echo number_format($total, 2); ?></span>
        </div>
    </div>

    <div class="son-letras">
        SON: <?php echo $total_letras; ?> CON 00/100 SOLES
    </div>

    <div class="pagos-block">
        <div class="row-total"><span>A CUENTA:</span> <span>S/ <?php echo number_format($pedido['a_cuenta'], 2); ?></span></div>
        <div class="row-total bold"><span>SALDO PENDIENTE:</span> <span>S/ <?php echo number_format($saldo_pendiente, 2); ?></span></div>
    </div>

    <div class="footer">
        <img src="<?php echo $imagen_qr; ?>" style="width: 120px; height: 120px; margin: 10px 0;"><br>

        Representación Impresa de la<br>Boleta de Venta Electrónica<br>

        <br>
        <strong>*** GRACIAS POR SU PREFERENCIA ***</strong>
        <br><br>
        <small>Sistema desarrollado por Elians Store</small>
    </div>
</div>

</body>
</html>