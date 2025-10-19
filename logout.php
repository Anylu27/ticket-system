<?php
require_once 'includes/conexion.php';

// Registrar en bitácora si hay sesión activa
if (isset($_SESSION['usuario_nombre'])) {
    registrarBitacora($_SESSION['usuario_nombre'], 'LOGOUT', 'Sesión cerrada');
}

// Destruir sesión
session_destroy();

// Redirigir al login
header('Location: index.php');
exit();
?>