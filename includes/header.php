<?php
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit();
}

// Obtener información del usuario logueado
$stmt = $pdo->prepare("SELECT nombre, usuario FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario_actual = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario_actual) {
    session_destroy();
    header('Location: ../index.php');
    exit();
}

// Obtener iniciales del usuario
$nombres = explode(' ', $usuario_actual['nombre']);
$iniciales = '';
foreach ($nombres as $nombre) {
    if (!empty($nombre)) {
        $iniciales .= strtoupper(substr($nombre, 0, 1));
    }
}
if (strlen($iniciales) > 2) {
    $iniciales = substr($iniciales, 0, 2);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Sistema de Tickets'; ?> - Hospital Santa Fe</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Botón menú móvil -->
    <button class="mobile-menu-btn" onclick="toggleMobileSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="main-wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-ticket-alt"></i> Tickets</h2>
                <p>Hospital Santa Fe</p>
            </div>
            <?php include 'sidebar.php'; ?>
        </div>
        
        <!-- Contenido principal -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1 class="page-title"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                <div class="user-info">
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                    <span><?php echo htmlspecialchars($usuario_actual['nombre']); ?></span>
                </div>
            </div>
            
            <!-- Área de contenido -->
            <div class="content-area">