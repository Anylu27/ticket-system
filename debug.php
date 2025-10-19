<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Verificando archivos...</h2>";

// Verificar que existe index.php
if (file_exists('index.php')) {
    echo "✓ index.php existe<br>";
    echo "Tamaño: " . filesize('index.php') . " bytes<br>";
} else {
    echo "✗ index.php NO existe<br>";
}

// Verificar que existe conexion.php
if (file_exists('includes/conexion.php')) {
    echo "✓ includes/conexion.php existe<br>";
} else {
    echo "✗ includes/conexion.php NO existe<br>";
}

echo "<h3>Intentando incluir index.php:</h3>";

// Capturar cualquier error al cargar index.php
ob_start();
try {
    include 'index.php';
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
$output = ob_get_clean();

if (empty($output)) {
    echo "No hay salida de index.php";
} else {
    echo "Salida capturada:<br>" . htmlspecialchars($output);
}
?>