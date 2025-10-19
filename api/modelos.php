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
                listarModelos();
            } else {
                listarModelos();
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

function listarModelos() {
    global $pdo;
    
    $where = ['mo.activo = 1'];
    $params = [];
    
    if (!empty($_GET['marca_id'])) {
        $where[] = 'mo.marca_id = ?';
        $params[] = $_GET['marca_id'];
    }
    
    $whereClause = implode(' AND ', $where);
    $limit = (int)($_GET['limit'] ?? 1000);
    
    $sql = "SELECT mo.id, mo.nombre, mo.marca_id, m.nombre as marca_nombre 
            FROM modelos mo
            LEFT JOIN marcas m ON mo.marca_id = m.id
            WHERE $whereClause 
            ORDER BY mo.nombre 
            LIMIT $limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $modelos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $modelos]);
}
?>