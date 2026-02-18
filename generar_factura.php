<?php
require_once 'conexion.php';
require_once 'config.php';
date_default_timezone_set('America/Lima');

if (!isset($_GET['id'])) { die("Error: Falta ID"); }
$id = $_GET['id'];

// Obtener datos
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) { die("Pedido no encontrado"); }

// --- CÁLCULOS EXACTOS ---
$total = $pedido['costo_total'];
$subtotal_base = $total / 1.18;
$igv = $total - $subtotal_base;
$saldo_pendiente = $total - $pedido['a_cuenta'];

// QR y Hash
$hash_seguridad = strtoupper(substr(md5($pedido['id'] . $total . $pedido['fecha_recepcion'] . "SEMILLA"), 0, 8));
$contenido_qr  = $config['empresa']['ruc'] . "|B001|" . str_pad($pedido['id'], 8, '0', STR_PAD_LEFT) . "|";
$contenido_qr .= number_format($igv, 2) . "|" . number_format($total, 2) . "|" . date('d/m/Y', strtotime($pedido['fecha_recepcion']));
$url_qr = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($contenido_qr);

// Letras
$total_letras = $total;
if (class_exists('NumberFormatter')) {
    $formatter = new NumberFormatter("es", NumberFormatter::SPELLOUT);
    $total_letras = strtoupper($formatter->format($total));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta B001-<?php echo str_pad($pedido['id'], 6, '0', STR_PAD_LEFT); ?></title>
    <style>
        /* CONFIGURACIÓN DE PÁGINA */
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; padding: 0;
            background: #505050;
            font-size: 11px;
            -webkit-print-color-adjust: exact;
        }

        .hoja-a4 {
            width: 210mm; height: 297mm;
            background: white;
            margin: 20px auto;
            /* MÁRGENES MÁS PEQUEÑOS PARA APROVECHAR ESPACIO */
            padding: 10mm 15mm;
            position: relative;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }

        @media print {
            body { background: white; }
            .hoja-a4 { margin: 0; box-shadow: none; width: 100%; height: 100%; page-break-after: always; }
            .no-print { display: none !important; }
        }

        /* --- CABECERA --- */
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; align-items: flex-start; }

        .empresa-info { width: 60%; color: #333; }

        /* LOGO GRANDE */
        .logo-img {
            max-height: 150px;
            max-width: 300px;
            margin-bottom: 10px;
            object-fit: contain;
        }

        .empresa-nombre { font-size: 18px; font-weight: 800; text-transform: uppercase; color: #000; letter-spacing: 0.5px; }
        .empresa-detalles { font-size: 11px; line-height: 1.4; color: #555; margin-top: 5px; }

        /* CUADRO RUC */
        .ruc-box {
            width: 35%;
            border: 2px solid #2c3e50;
            text-align: center;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }
        .ruc-top { font-size: 14px; font-weight: bold; padding: 10px 0 5px 0; color: #000; }
        .ruc-mid {
            background: #2c3e50; color: #fff;
            padding: 8px 0; margin: 5px 0;
            font-weight: bold; font-size: 15px; letter-spacing: 1px;
        }
        .ruc-bot { font-size: 18px; font-weight: bold; padding: 5px 0 10px 0; color: #000; }

        /* --- CLIENTE --- */
        .cliente-wrapper {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px 15px;
            margin-bottom: 15px;
            background-color: #fcfcfc;
        }
        .cliente-row { display: flex; margin-bottom: 3px; }
        .cliente-col { width: 50%; }
        .dato-label { font-weight: 700; color: #444; width: 90px; display: inline-block; }
        .dato-valor { color: #000; text-transform: uppercase; }

        /* --- TABLA --- */
        .tabla-productos { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .tabla-productos th {
            background-color: #eee; color: #000; font-weight: 700;
            padding: 6px 5px; border: 1px solid #000; text-transform: uppercase; font-size: 10px;
        }
        .tabla-productos td {
            border-left: 1px solid #000; border-right: 1px solid #000;
            padding: 8px 5px; vertical-align: top;
        }
        .col-cant { width: 8%; text-align: center; }
        .col-und  { width: 8%; text-align: center; }
        .col-desc { width: 59%; text-align: left; padding-left: 10px; }
        .col-punit { width: 12%; text-align: right; }
        .col-total { width: 13%; text-align: right; font-weight: bold; }

        /* AJUSTE CLAVE: Altura reducida para que quepa todo mejor */
        .fila-relleno td {
            border-bottom: 1px solid #000;
            height: 220px; /* REDUCIDO DE 380px A 220px */
        }

        /* --- TOTALES --- */
        .totales-wrapper { display: flex; justify-content: space-between; margin-top: 5px; }
        .letras-box { width: 60%; font-size: 10px; padding-top: 5px; }
        .son-texto { font-weight: bold; font-size: 11px; margin-bottom: 8px; background: #eee; padding: 5px; border-radius: 4px; }
        .obs-pago { border: 1px dashed #999; padding: 6px; border-radius: 4px; width: 95%; }

        .numeros-box { width: 35%; }
        .fila-num { display: flex; justify-content: space-between; padding: 2px 0; font-size: 11px; }
        .fila-num.total-final { border-top: 2px solid #000; margin-top: 5px; padding-top: 5px; font-size: 13px; font-weight: 800; color: #000; }

        /* --- FOOTER --- */
        .footer {
            margin-top: 15px; border-top: 1px solid #ccc; padding-top: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .qr-box { margin-right: 20px; border: 1px solid #ddd; padding: 2px; }
        .qr-img { width: 90px; height: 90px; display: block; }
        .legal-text { font-weight: bold; font-size: 13px; color: #000; text-align: center; flex-grow: 1; }

        /* --- BOTONES FLOTANTES --- */
        .btn-print {
            position: fixed; top: 20px; right: 20px;
            background: #d35400; color: white;
            padding: 12px 20px; border-radius: 50px;
            text-decoration: none; font-weight: bold;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3); transition: 0.3s; font-size: 12px;
        }
        .btn-print:hover { transform: scale(1.05); background: #1a252f; }

        .btn-back {
            position: fixed; top: 20px; left: 20px;
            background: #7f8c8d; color: white;
            padding: 12px 20px; border-radius: 50px;
            text-decoration: none; font-weight: bold;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3); transition: 0.3s; font-size: 12px;
        }
        .btn-back:hover { transform: scale(1.05); background: #95a5a6; }
    </style>
</head>
<body>

<a href="historial.php" class="btn-back no-print">⬅️ VOLVER AL HISTORIAL</a>
<a href="javascript:window.print()" class="btn-print no-print">🖨️ IMPRIMIR DOCUMENTO</a>

<div class="hoja-a4">

    <div class="header">
        <div class="empresa-info">
            <img src="<?php echo $config['rutas']['logo']; ?>" class="logo-img" onerror="this.style.display='none'">
            <div class="empresa-nombre"><?php echo $config['empresa']['nombre']; ?></div>
            <div class="empresa-detalles">
                <?php echo $config['empresa']['direccion']; ?><br>
                (Ref: <?php echo $config['empresa']['referencia']; ?>)<br>
                <strong>Telf:</strong> <?php echo $config['empresa']['telefono']; ?> &nbsp;|&nbsp;
                <strong>Email:</strong> ventas@eliansstore.com
            </div>
        </div>
        <div class="ruc-box">
            <div class="ruc-top">R.U.C. <?php echo $config['empresa']['ruc']; ?></div>
            <div class="ruc-mid">BOLETA DE VENTA ELECTRÓNICA</div>
            <div class="ruc-bot">B001-<?php echo str_pad($pedido['id'], 8, '0', STR_PAD_LEFT); ?></div>
        </div>
    </div>

    <div class="cliente-wrapper">
        <div class="cliente-row">
            <div class="cliente-col">
                <span class="dato-label">CLIENTE:</span>
                <span class="dato-valor"><?php echo $pedido['cliente_nombre']; ?></span>
            </div>
            <div class="cliente-col">
                <span class="dato-label">FECHA:</span>
                <span class="dato-valor"><?php echo date('d/m/Y', strtotime($pedido['fecha_recepcion'])); ?></span>
            </div>
        </div>
        <div class="cliente-row">
            <div class="cliente-col">
                <span class="dato-label">DIRECCIÓN:</span>
                <span class="dato-valor"><?php echo $pedido['cliente_direccion'] ?: '-'; ?></span>
            </div>
            <div class="cliente-col">
                <span class="dato-label">MONEDA:</span>
                <span class="dato-valor">SOLES (PEN)</span>
            </div>
        </div>
        <div class="cliente-row">
            <div class="cliente-col">
                <span class="dato-label">TELÉFONO:</span>
                <span class="dato-valor"><?php echo $pedido['cliente_telefono'] ?: '-'; ?></span>
            </div>
            <div class="cliente-col">
                <span class="dato-label">PAGO:</span>
                <span class="dato-valor">CONTADO</span>
            </div>
        </div>
    </div>

    <table class="tabla-productos">
        <thead>
        <tr>
            <th class="col-cant">CANT.</th>
            <th class="col-und">UND.</th>
            <th class="col-desc">DESCRIPCIÓN</th>
            <th class="col-punit">P. UNIT</th>
            <th class="col-total">IMPORTE</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="col-cant">1</td>
            <td class="col-und">NIU</td>
            <td class="col-desc" style="padding-top: 10px;">
                <?php echo nl2br(strtoupper($pedido['descripcion'])); ?>
            </td>
            <td class="col-punit" style="padding-top: 10px;">
                <?php echo number_format($total, 2); ?>
            </td>
            <td class="col-total" style="padding-top: 10px;">
                <?php echo number_format($total, 2); ?>
            </td>
        </tr>
        <tr class="fila-relleno">
            <td></td><td></td><td></td><td></td><td></td>
        </tr>
        </tbody>
    </table>

    <div class="totales-wrapper">
        <div class="letras-box">
            <div class="son-texto">SON: <?php echo $total_letras; ?> CON 00/100 SOLES</div>
            <div class="obs-pago">
                <strong>INFORMACIÓN DE PAGO:</strong><br>
                A CUENTA: S/ <?php echo number_format($pedido['a_cuenta'], 2); ?><br>
                <span style="color: #c0392b; font-weight: bold;">SALDO PENDIENTE: S/ <?php echo number_format($saldo_pendiente, 2); ?></span>
            </div>
        </div>

        <div class="numeros-box">
            <div class="fila-num">
                <span>OP. GRAVADA:</span>
                <span>S/ <?php echo number_format($subtotal_base, 2); ?></span>
            </div>
            <div class="fila-num">
                <span>I.G.V. (18%):</span>
                <span>S/ <?php echo number_format($igv, 2); ?></span>
            </div>
            <div class="fila-num total-final">
                <span>IMPORTE TOTAL:</span>
                <span>S/ <?php echo number_format($total, 2); ?></span>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="qr-box">
            <img src="<?php echo $url_qr; ?>" class="qr-img">
        </div>
        <div class="legal-text">
            *** GRACIAS POR SU PREFERENCIA ***
        </div>
    </div>
</div>

</body>
</html>