<?php
$pagina_actual = basename($_SERVER['PHP_SELF']);
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['empresa']['nombre']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <style>
        /* --- ESTILOS MAESTROS (Modo Oscuro Automático) --- */
        body, .card, .sidebar, .main-content { transition: background-color 0.3s, color 0.3s; }

        /* Barra Lateral */
        .sidebar {
            width: 260px; height: 100vh; position: fixed; top: 0; left: 0;
            background: #212529; color: white; z-index: 1000; display: flex; flex-direction: column;
        }
        .main-content { margin-left: 260px; padding: 30px; }

        /* Links del menú */
        .menu-link {
            display: flex; align-items: center; padding: 12px 15px; color: #adb5bd;
            text-decoration: none; border-radius: 8px; margin-bottom: 5px; font-weight: 500;
        }
        .menu-link:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .menu-link.active { background: #0d6efd; color: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }

        /* Estilo del Logo */
        .logo-img { max-height: 80px; width: auto; border-radius: 8px; background: white; padding: 5px; }

        /* --- PARCHE MÁGICO PARA MODO OSCURO --- */
        [data-bs-theme="dark"] body { background-color: #121212 !important; }
        [data-bs-theme="dark"] .bg-white, [data-bs-theme="dark"] .bg-light {
            background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important;
        }
        [data-bs-theme="dark"] .form-control, [data-bs-theme="dark"] .form-select {
            background-color: #2b2b2b; border-color: #444; color: #fff;
        }
        [data-bs-theme="dark"] .table { --bs-table-color: #e0e0e0; --bs-table-bg: #1e1e1e; border-color: #333; }
        [data-bs-theme="dark"] .table-light { background-color: #2c2c2c !important; color: #fff !important; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: 0.3s; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>

    <script>
        if (localStorage.getItem('tema') === 'dark') {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        }
    </script>
</head>
<body>

<nav class="sidebar">
    <div class="p-4 text-center border-bottom border-secondary">
        <img src="<?php echo $config['rutas']['logo']; ?>" alt="Elians Store" class="logo-img mb-2 shadow">
        <h5 class="fw-bold m-0 text-white small text-uppercase spacing-1">SISTEMA DE GESTIÓN</h5>
    </div>

    <div class="p-3 flex-grow-1 overflow-auto">
        <small class="text-muted fw-bold text-uppercase ms-2" style="font-size: 0.75rem;">Principal</small>
        <a href="index.php" class="menu-link <?php echo $pagina_actual == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-cash-register me-3" style="width:20px"></i> Venta / Caja
        </a>
        <a href="personal.php" class="menu-link <?php echo $pagina_actual == 'personal.php' ? 'active' : ''; ?>">
            <i class="fas fa-users me-3" style="width:20px"></i> Personal
        </a>
        <a href="historial.php" class="menu-link <?php echo $pagina_actual == 'historial.php' ? 'active' : ''; ?>">
            <i class="fas fa-tshirt me-3" style="width:20px"></i> Producción
        </a>

        <hr class="border-secondary my-3 opacity-25">

        <small class="text-muted fw-bold text-uppercase ms-2" style="font-size: 0.75rem;">Gestión</small>
        <a href="proyectos.php" class="menu-link <?php echo $pagina_actual == 'proyectos.php' ? 'active' : ''; ?>">
            <i class="fas fa-school me-3" style="width:20px"></i> Colegios / Grupos
        </a>
        <a href="analisis_inteligente.php" class="menu-link <?php echo $pagina_actual == 'analisis_inteligente.php' ? 'active' : ''; ?>">
            <i class="fas fa-brain me-3" style="width:20px"></i> IA Análisis
        </a>
        <a href="dashboard.php" class="menu-link <?php echo $pagina_actual == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line me-3" style="width:20px"></i> Reportes
        </a>
    </div>

    <div class="p-3 border-top border-secondary">
        <a href="salir.php" class="btn btn-danger w-100 fw-bold shadow-sm">
            <i class="fas fa-power-off me-2"></i> CERRAR SISTEMA
        </a>
    </div>
</nav>

<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div class="d-flex align-items-center">
            <button class="btn btn-dark d-md-none me-3" onclick="document.querySelector('.sidebar').classList.toggle('active')">
                <i class="fas fa-bars"></i>
            </button>
            <h3 class="fw-bold m-0 text-body text-uppercase">
                <?php echo str_replace(['.php','_'], ['',' '], strtoupper($pagina_actual)); ?>
            </h3>
        </div>

        <div>
            <button class="btn btn-outline-secondary rounded-circle shadow-sm me-2" onclick="toggleTheme()" id="btnTheme">
                <i class="fas fa-moon"></i>
            </button>
            <button onclick="history.back()" class="btn btn-outline-dark fw-bold shadow-sm">
                <i class="fas fa-arrow-left"></i> VOLVER
            </button>
        </div>
    </div>