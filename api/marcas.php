<?php
require_once '../includes/conexion.php';
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                listarMarcas();
            } else {
                listarMarcas();
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}

function listarMarcas() {
    global $pdo;
    
    $limit = (int)($_GET['limit'] ?? 1000);
    
    $sql = "SELECT id, nombre FROM marcas WHERE activo = 1 ORDER BY nombre LIMIT $limit";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $marcas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $marcas]);
}
?>