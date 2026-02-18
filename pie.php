</div> <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    // 1. Lógica del Modo Oscuro
    function toggleTheme() {
        const html = document.documentElement;
        const icon = document.querySelector('#btnTheme i');

        if (html.getAttribute('data-bs-theme') === 'dark') {
            html.setAttribute('data-bs-theme', 'light');
            localStorage.setItem('tema', 'light');
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        } else {
            html.setAttribute('data-bs-theme', 'dark');
            localStorage.setItem('tema', 'dark');
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }
    }

    // Poner el icono correcto al cargar
    if (localStorage.getItem('tema') === 'dark') {
        const icon = document.querySelector('#btnTheme i');
        if(icon) {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }
    }

    // 2. Activar DataTables en todas las tablas automáticamente
    $(document).ready(function() {
        $('table').DataTable({
            language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" },
            responsive: true,
            order: [[0, 'desc']] // Ordenar por ID descendente
        });
    });
</script>

</body>
</html>