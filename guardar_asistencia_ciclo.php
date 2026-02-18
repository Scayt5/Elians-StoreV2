<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $personal_id = $_POST['personal_id'];
    $fecha_inicio = $_POST['fecha_inicio'];
    // El formulario envía 'pago_dia' pero en realidad es la tarifa por hora (según la lógica corregida)
    $tarifa_hora = floatval($_POST['pago_dia']);
    $obs_general = $_POST['obs_general'];

    // Arrays recibidos del formulario
    $asistencia = isset($_POST['asistencia']) ? $_POST['asistencia'] : [];
    $horas = $_POST['horas'];
    $descuentos = $_POST['descuento'];

    $total_pagar = 0;
    $total_descuentos = 0;
    $detalles_texto = ""; // String para guardar en la BD (resumen legible)

    // Mapa de días para texto (0=Sábado porque así está definido en detalle_personal.php)
    // offsets: 0=Sab, 2=Lun, 3=Mar, 4=Mier, 5=Jue, 6=Vie
    $nombres_dias = [
        0 => "Sáb",
        1 => "Dom", // No debería venir, pero por si acaso
        2 => "Lun",
        3 => "Mar",
        4 => "Mié",
        5 => "Jue",
        6 => "Vie"
    ];

    // Primero borramos asistencias previas en ese rango de fechas para evitar duplicados si se edita?
    // Mejor no borrar a ciegas. Asumimos que se guarda una vez por semana.
    // Pero si dashboard usa 'asistencia', y guardamos de nuevo, se duplicarán horas?
    // Sería ideal borrar registros de asistencia para este empleado en las fechas que vamos a insertar.

    // Calculamos el rango de fechas de esta semana (Sab a Vie)
    $fecha_fin_semana = date('Y-m-d', strtotime($fecha_inicio . " + 6 days"));

    try {
        // Borrar asistencias previas de este empleado en esta semana para evitar duplicados
        $stmt_del = $conn->prepare("DELETE FROM asistencia WHERE empleado_id = ? AND fecha BETWEEN ? AND ?");
        $stmt_del->execute([$personal_id, $fecha_inicio, $fecha_fin_semana]);

        // Procesar cada día marcado
        foreach ($asistencia as $key => $valor) {
            if ($valor == '1') {
                $hrs = isset($horas[$key]) ? intval($horas[$key]) : 8;
                $dcto = isset($descuentos[$key]) ? floatval($descuentos[$key]) : 0;

                // Calcular fecha real
                $fecha_actual = date('Y-m-d', strtotime($fecha_inicio . " + $key days"));

                // Lógica de Pago: POR HORA
                $pago_bruto_dia = $tarifa_hora * $hrs;

                // Sumatorias
                $total_descuentos += $dcto;
                $total_pagar += ($pago_bruto_dia - $dcto);

                // Construir resumen para BD
                $nombre_dia = isset($nombres_dias[$key]) ? $nombres_dias[$key] : "Día $key";
                $info_dia = $nombre_dia . "(" . $hrs . "h";
                if ($dcto > 0) {
                    $info_dia .= " <span style='color:red'>-S/" . number_format($dcto, 2) . "</span>";
                }
                $info_dia .= ")";
                $detalles_texto .= $info_dia . ", ";

                // GUARDAR EN TABLA ASISTENCIA (Para el Dashboard Financiero)
                $stmt_asis = $conn->prepare("INSERT INTO asistencia (empleado_id, fecha, horas) VALUES (?, ?, ?)");
                $stmt_asis->execute([$personal_id, $fecha_actual, $hrs]);
            }
        }

        $detalles_texto = rtrim($detalles_texto, ", "); // Quitar última coma

        // GUARDAR EN PAGOS_PERSONAL (Resumen/Boleta)
        $sql = "INSERT INTO pagos_personal (personal_id, fecha_inicio_ciclo, dias_trabajados, monto_total, descuentos, observaciones) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$personal_id, $fecha_inicio, $detalles_texto, $total_pagar, $total_descuentos, $obs_general]);

        if (php_sapi_name() === 'cli') {
            echo "Guardado exitoso. Total: " . $total_pagar;
        } else {
            header("Location: personal.php?msg=guardado");
        }
    } catch (PDOException $e) {
        die("Error al guardar: " . $e->getMessage());
    }
}
?>