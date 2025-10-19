<?php
echo "Probando conexión directa...<br>";

try {
    $conexion = new mysqli("localhost", "root", "", "ticket_system");
    
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }
    
    echo "Conexión exitosa!<br>";
    
    $resultado = $conexion->query("SHOW TABLES");
    echo "Tablas encontradas:<br>";
    while ($tabla = $resultado->fetch_row()) {
        echo "- " . $tabla[0] . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>