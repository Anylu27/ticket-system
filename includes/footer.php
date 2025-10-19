</div> <!-- Fin content-area -->
        </div> <!-- Fin main-content -->
    </div> <!-- Fin main-wrapper -->

    <!-- Scripts globales -->
    <script>
        // Toggle sidebar en móvil
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // Cerrar sidebar al hacer clic fuera en móvil
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Configurar tooltips si están disponibles
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        });
    </script>
</body>
</html>