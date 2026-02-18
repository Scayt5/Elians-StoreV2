<?php
require_once 'conexion.php';

// --- FUNCIÓN DE INTELIGENCIA BÁSICA ---
function analizarDescripcion($texto) {
    $texto = mb_strtoupper($texto, 'UTF-8'); // Convertir a mayúsculas para analizar mejor

    // 1. REGLAS DE DETECCIÓN (Tú defines qué palabras crean grupos)
    // Formato: 'PALABRA_CLAVE' => 'NOMBRE_DEL_GRUPO'

    $reglas = [
            'AGUSTIN'   => 'COLEGIO SAN AGUSTIN',
            'RAMOS'     => 'COLEGIO RAMOS',
            'INNOVA'    => 'INNOVA SCHOOLS',
            'PROMOCION' => 'PROMOCIONES 2026',
            'PROMO'     => 'PROMOCIONES 2026',
            'MUNI'      => 'MUNICIPALIDAD',
            'COMAS'     => 'MUNICIPALIDAD',
            'FUTBOL'    => 'DEPORTE / CAMISETAS',
            'EMPRESA'   => 'PEDIDOS CORPORATIVOS',
            'POLICIA'   => 'POLICIA NACIONAL',
            'ESTAMPA'   => 'SERVICIO DE ESTAMPADO'
    ];

    // 2. El sistema busca las palabras clave en la descripción
    foreach ($reglas as $clave => $grupo) {
        if (strpos($texto, $clave) !== false) {
            return $grupo; // ¡Encontró una coincidencia! Retorna el grupo.
        }
    }

    // 3. Lógica "Heurística" (Si dice "Colegio X", crea el grupo "Colegio X")
    // Esto sirve para colegios nuevos que no están en tu lista de arriba
    if (preg_match('/COLEGIO\s+([A-ZÁÉÍÓÚÑ]+)/', $texto, $coincidencias)) {
        return "COLEGIO " . $coincidencias[1];
    }

    return 'VARIOS'; // Si no entiende nada, lo pone en Varios
}

// --- PROCESO NORMAL DE GUARDADO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = $_POST['cliente_nombre'];
    $telefono = !empty($_POST['cliente_telefono']) ? $_POST['cliente_telefono'] : null;
    $email = !empty($_POST['cliente_email']) ? $_POST['cliente_email'] : null;
    $direccion = !empty($_POST['cliente_direccion']) ? $_POST['cliente_direccion'] : null;

    $descripcion = $_POST['descripcion']; // Lo que escribió el vendedor
    $total = $_POST['costo_total'];
    $acuenta = $_POST['a_cuenta'];
    $fecha_entrega = $_POST['fecha_entrega'];

    // AQUÍ OCURRE LA MAGIA AUTOMÁTICA
    $grupo_detectado = analizarDescripcion($descripcion);

    try {
        // Guardamos incluyendo el 'proyecto' (grupo detectado)
        $sql = "INSERT INTO pedidos (cliente_nombre, cliente_telefono, cliente_email, cliente_direccion, descripcion, costo_total, a_cuenta, fecha_entrega, proyecto) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$nombre, $telefono, $email, $direccion, $descripcion, $total, $acuenta, $fecha_entrega, $grupo_detectado]);

        $id_nuevo = $conn->lastInsertId();

        // Redirigir a la factura A4
        header("Location: generar_factura.php?id=" . $id_nuevo);
        exit;

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: index.php"); exit;
}
?>