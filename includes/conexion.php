<?php
session_start();

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'ticket_system');
define('DB_USER', 'root');
define('DB_PASS', 'Anyuri1527');

// Crear conexión
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para verificar sesión
function verificarSesion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ../index.php');
        exit();
    }
}

// Función para registrar en bitácora
function registrarBitacora($usuario, $modulo, $accion) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO bitacora (usuario, modulo, accion) VALUES (?, ?, ?)");
        $stmt->execute([$usuario, $modulo, $accion]);
    } catch(PDOException $e) {
        error_log("Error en bitácora: " . $e->getMessage());
    }
}

// Función para obtener estadísticas del dashboard
function obtenerEstadisticasDashboard() {
    global $pdo;
    
    $stats = [];
    
    // Contar correctivos por estado
    $stmt = $pdo->query("SELECT estado, COUNT(*) as total FROM correctivos GROUP BY estado");
    $correctivos_estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats['resueltos'] = 0;
    $stats['asignados'] = 0;
    $stats['en_espera'] = 0;
    $stats['pendientes'] = 0;
    
    foreach ($correctivos_estados as $estado) {
        switch ($estado['estado']) {
            case 'Resuelto':
                $stats['resueltos'] = $estado['total'];
                break;
            case 'Asignado':
                $stats['asignados'] = $estado['total'];
                break;
            case 'En espera del cliente':
                $stats['en_espera'] = $estado['total'];
                break;
            case 'En Proceso':
                $stats['pendientes'] += $estado['total'];
                break;
        }
    }
    
    // Datos para gráfico mensual
    $stmt = $pdo->query("
        SELECT 
            MONTH(fecha_creacion) as mes,
            YEAR(fecha_creacion) as año,
            COUNT(*) as total 
        FROM correctivos 
        WHERE YEAR(fecha_creacion) = YEAR(CURDATE()) 
        GROUP BY MONTH(fecha_creacion), YEAR(fecha_creacion)
        ORDER BY mes
    ");
    $stats['datos_mensuales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $stats;
}

// Función para formatear fecha en español
function formatearFecha($fecha) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $timestamp = strtotime($fecha);
    $dia = date('d', $timestamp);
    $mes = $meses[(int)date('m', $timestamp)];
    $año = date('Y', $timestamp);
    
    return "$dia de $mes de $año";
}

// Configurar zona horaria
date_default_timezone_set('America/Panama');
?>