<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<div class="sidebar">
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar icon"></i>
                Dashboard
            </a>
        </li>
        
        <li>
            <a href="calendario.php" class="<?php echo ($current_page == 'calendario') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt icon"></i>
                Calendario
            </a>
        </li>
        
        <li>
            <a href="correctivos.php" class="<?php echo ($current_page == 'correctivos') ? 'active' : ''; ?>">
                <i class="fas fa-tools icon"></i>
                Correctivos
            </a>
        </li>
        
        <li>
            <a href="preventivos.php" class="<?php echo ($current_page == 'preventivos') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check icon"></i>
                Preventivos
            </a>
        </li>
        
        <li>
            <a href="activos.php" class="<?php echo ($current_page == 'activos') ? 'active' : ''; ?>">
                <i class="fas fa-server icon"></i>
                Activos
            </a>
        </li>
        
        <li>
            <a href="conocimientos.php" class="<?php echo ($current_page == 'conocimientos') ? 'active' : ''; ?>">
                <i class="fas fa-book icon"></i>
                Base de Conocimientos
            </a>
        </li>
        
        <li>
            <a href="#" onclick="toggleSubmenu('maestros')" class="<?php echo (in_array($current_page, ['maestros', 'clientes', 'proyectos', 'categorias'])) ? 'active' : ''; ?>">
                <i class="fas fa-cog icon"></i>
                Maestros
                <i class="fas fa-chevron-right" style="margin-left: auto;" id="maestros-arrow"></i>
            </a>
            <ul class="submenu" id="maestros-submenu" style="display: none; margin-left: 40px;">
                <li><a href="clientes.php">Clientes</a></li>
                <li><a href="proyectos.php">Proyectos</a></li>
                <li><a href="categorias.php">Categorías</a></li>
                <li><a href="ubicaciones.php">Ubicaciones</a></li>
                <li><a href="areas.php">Áreas</a></li>
            </ul>
        </li>
        
        <li>
            <a href="#" onclick="toggleSubmenu('seguridad')" class="<?php echo (in_array($current_page, ['usuarios', 'bitacora'])) ? 'active' : ''; ?>">
                <i class="fas fa-shield-alt icon"></i>
                Seguridad
                <i class="fas fa-chevron-right" style="margin-left: auto;" id="seguridad-arrow"></i>
            </a>
            <ul class="submenu" id="seguridad-submenu" style="display: none; margin-left: 40px;">
                <li><a href="usuarios.php" class="<?php echo ($current_page == 'usuarios') ? 'active' : ''; ?>">Usuarios</a></li>
                <li><a href="bitacora.php" class="<?php echo ($current_page == 'bitacora') ? 'active' : ''; ?>">Bitácora</a></li>
            </ul>
        </li>
        
        <li>
            <a href="cambiar_clave.php" class="<?php echo ($current_page == 'cambiar_clave') ? 'active' : ''; ?>">
                <i class="fas fa-key icon"></i>
                Cambiar clave
            </a>
        </li>
        
        <li>
            <a href="../logout.php" onclick="return confirm('¿Está seguro que desea cerrar sesión?')">
                <i class="fas fa-sign-out-alt icon"></i>
                Cerrar sesión
            </a>
        </li>
    </ul>
</div>

<script>
function toggleSubmenu(menuId) {
    const submenu = document.getElementById(menuId + '-submenu');
    const arrow = document.getElementById(menuId + '-arrow');
    
    if (submenu.style.display === 'none' || submenu.style.display === '') {
        submenu.style.display = 'block';
        arrow.style.transform = 'rotate(90deg)';
    } else {
        submenu.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
    }
}

// Mostrar submenús activos al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = '<?php echo $current_page; ?>';
    const maestrosPages = ['maestros', 'clientes', 'proyectos', 'categorias', 'ubicaciones', 'areas'];
    const seguridadPages = ['usuarios', 'bitacora'];
    
    if (maestrosPages.includes(currentPage)) {
        document.getElementById('maestros-submenu').style.display = 'block';
        document.getElementById('maestros-arrow').style.transform = 'rotate(90deg)';
    }
    
    if (seguridadPages.includes(currentPage)) {
        document.getElementById('seguridad-submenu').style.display = 'block';
        document.getElementById('seguridad-arrow').style.transform = 'rotate(90deg)';
    }
});
</script>