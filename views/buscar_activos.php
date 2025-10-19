<?php
/**
 * API para búsqueda de activos - Hospital Santa Fe
 * Versión sin problemas de headers/session
 */

// Limpiar cualquier output previo
if (ob_get_level()) {
    ob_clean();
}

// Configurar headers antes que cualquier output
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Conexión directa a la base de datos (sin includes que puedan causar problemas)
try {
    $host = 'localhost';
    $database = 'ticket_system';
    $username = 'root';
    $password = 'Anyuri1527';
    
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión',
        'message' => 'No se pudo conectar a la base de datos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Obtener parámetros sin usar sesiones
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = array_merge($_POST, $_GET);
    }
    
    $busqueda = trim($input['busqueda'] ?? '');
    $categoria_id = !empty($input['categoria_id']) ? (int)$input['categoria_id'] : null;
    $subcategoria_id = !empty($input['subcategoria_id']) ? (int)$input['subcategoria_id'] : null;
    $tipo_activo = trim($input['tipo_activo'] ?? '');
    $limite = !empty($input['limite']) ? (int)$input['limite'] : 20;
    
    // Validar entrada mínima
    if (strlen($busqueda) < 2 && !$categoria_id && !$subcategoria_id && !$tipo_activo) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'Ingrese al menos 2 caracteres para buscar',
            'total' => 0
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Verificar que la tabla activos existe
    try {
        $pdo->query("SELECT 1 FROM activos LIMIT 1");
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Tabla no encontrada',
            'message' => 'La tabla activos no existe. Ejecute el script SQL primero.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Construir consulta SQL
    $sql = "
        SELECT 
            a.id,
            a.serial,
            a.nombre,
            a.marca_id,
            a.modelo_id,
            a.tipo_activo,
            COALESCE(m.nombre, '') as marca_nombre,
            COALESCE(mo.nombre, '') as modelo_nombre,
            COALESCE(ar.nombre, '') as area_nombre,
            COALESCE(u.nombre, '') as ubicacion_nombre,
            a.categoria_id,
            a.subcategoria_id,
            CONCAT(COALESCE(a.serial, ''), ' - ', COALESCE(a.nombre, '')) as display_text
        FROM activos a
        LEFT JOIN marcas m ON a.marca_id = m.id
        LEFT JOIN modelos mo ON a.modelo_id = mo.id
        LEFT JOIN areas ar ON a.area_id = ar.id
        LEFT JOIN ubicaciones u ON a.ubicacion_id = u.id
        WHERE a.activo = 1 AND a.estado_activo = 'ACTIVO'
    ";
    
    $params = [];
    
    // Agregar filtros
    if ($categoria_id) {
        $sql .= " AND a.categoria_id = ?";
        $params[] = $categoria_id;
    }
    
    if ($subcategoria_id) {
        $sql .= " AND a.subcategoria_id = ?";
        $params[] = $subcategoria_id;
    }
    
    if ($tipo_activo) {
        $sql .= " AND a.tipo_activo LIKE ?";
        $params[] = '%' . $tipo_activo . '%';
    }
    
    if ($busqueda) {
        $sql .= " AND (
            a.serial LIKE ? OR 
            a.nombre LIKE ? OR 
            m.nombre LIKE ? OR 
            mo.nombre LIKE ? OR
            a.tipo_activo LIKE ?
        )";
        $searchParam = '%' . $busqueda . '%';
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    // Ordenamiento
    $sql .= " ORDER BY ";
    if ($busqueda) {
        $sql .= "
            CASE 
                WHEN a.serial LIKE ? THEN 1
                WHEN a.nombre LIKE ? THEN 2
                WHEN a.serial LIKE ? THEN 3
                WHEN a.nombre LIKE ? THEN 4
                ELSE 5
            END,
        ";
        $exactMatch = $busqueda . '%';
        $partialMatch = '%' . $busqueda . '%';
        $params = array_merge($params, [$exactMatch, $exactMatch, $partialMatch, $partialMatch]);
    }
    $sql .= " a.nombre, a.serial LIMIT ?";
    $params[] = $limite;
    
    // Ejecutar consulta
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $activos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear resultados
    $resultados = [];
    foreach ($activos as $activo) {
        $resultados[] = [
            'id' => (int)($activo['id'] ?? 0),
            'serial' => $activo['serial'] ?? '',
            'nombre' => $activo['nombre'] ?? '',
            'marca_id' => !empty($activo['marca_id']) ? (int)$activo['marca_id'] : null,
            'modelo_id' => !empty($activo['modelo_id']) ? (int)$activo['modelo_id'] : null,
            'marca_nombre' => $activo['marca_nombre'] ?? '',
            'modelo_nombre' => $activo['modelo_nombre'] ?? '',
            'tipo_activo' => $activo['tipo_activo'] ?? '',
            'area_nombre' => $activo['area_nombre'] ?? '',
            'ubicacion_nombre' => $activo['ubicacion_nombre'] ?? '',
            'categoria_id' => !empty($activo['categoria_id']) ? (int)$activo['categoria_id'] : null,
            'subcategoria_id' => !empty($activo['subcategoria_id']) ? (int)$activo['subcategoria_id'] : null,
            'display_text' => $activo['display_text'] ?? ''
        ];
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $resultados,
        'total' => count($resultados),
        'query_info' => [
            'busqueda' => $busqueda,
            'categoria_id' => $categoria_id,
            'subcategoria_id' => $subcategoria_id,
            'tipo_activo' => $tipo_activo,
            'limite' => $limite,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos',
        'message' => 'Error en la consulta: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor',
        'message' => 'Error interno: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>