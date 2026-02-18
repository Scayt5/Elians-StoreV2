<?php
// Iniciar sesión para poder destruirla
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema Cerrado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #e0e0e0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .icon-box { font-size: 80px; color: #dc3545; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="icon-box animate-pulse">
    <i class="fas fa-lock"></i>
</div>

<h2 class="fw-bold">SISTEMA CERRADO CORRECTAMENTE</h2>
<p class="text-muted">Es seguro cerrar esta ventana.</p>

<button onclick="window.close()" class="btn btn-outline-light mt-4">
    Cerrar Pestaña
</button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
<script>
    // Intento automático de cerrar la ventana (Navegadores modernos a veces bloquean esto)
    setTimeout(function() {
        window.close();
    }, 2000);
</script>
</body>
</html>