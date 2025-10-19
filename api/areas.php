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
                listarAreas();
            } else {
                listarAreas();
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

function listarAreas() {
    global $pdo;
    
    $where = ['a.activo = 1'];
    $params = [];
    
    if (!empty($_GET['ubicacion_id'])) {
        $where[] = 'a.ubicacion_id = ?';
        $params[] = $_GET['ubicacion_id'];
    }
    
    $whereClause = implode(' AND ', $where);
    
    $sql = "SELECT a.id, a.nombre, a.ubicacion_id, u.nombre as ubicacion_nombre 
            FROM areas a
            LEFT JOIN ubicaciones u ON a.ubicacion_id = u.id
            WHERE $whereClause 
            ORDER BY a.nombre";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $areas]);
}
?>