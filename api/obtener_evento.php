<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/conexion.php';
verificarSesion();

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener parámetros
$evento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo_evento = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';

// Validar parámetros
if (!$evento_id || !$tipo_evento) {
    echo json_encode(['success' => false, 'message' => 'ID y tipo de evento son requeridos']);
    exit();
}

if (!in_array($tipo_evento, ['correctivo', 'preventivo'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo de evento inválido']);
    exit();
}

try {
    // Consultar según el tipo de evento
    if ($tipo_evento === 'correctivo') {
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                cl.nombre as cliente_nombre,
                p.nombre as proyecto_nombre,
                cat.nombre as categoria_nombre,
                u.nombre as asignado_nombre,
                resp.nombre as responsable_nombre
            FROM correctivos c
            LEFT JOIN clientes cl ON c.cliente_id = cl.id
            LEFT JOIN proyectos p ON c.proyecto_id = p.id
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
            LEFT JOIN usuarios u ON c.asignado_a = u.id
            LEFT JOIN usuarios resp ON c.responsable = resp.id
            WHERE c.id = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                cl.nombre as cliente_nombre,
                pr.nombre as proyecto_nombre,
                cat.nombre as categoria_nombre,
                u.nombre as asignado_nombre,
                resp.nombre as responsable_nombre
            FROM preventivos p
            LEFT JOIN clientes cl ON p.cliente_id = cl.id
            LEFT JOIN proyectos pr ON p.proyecto_id = pr.id
            LEFT JOIN categorias cat ON p.categoria_id = cat.id
            LEFT JOIN usuarios u ON p.asignado_a = u.id
            LEFT JOIN usuarios resp ON p.responsable = resp.id
            WHERE p.id = ?
        ");
    }
    
    $stmt->execute([$evento_id]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$evento) {
        echo json_encode(['success' => false, 'message' => 'Evento no encontrado']);
        exit();
    }
    
    // Generar HTML para mostrar los detalles
    $html = generarHtmlEvento($evento, $tipo_evento);
    
    echo json_encode([
        'success' => true,
        'titulo' => $evento['titulo'],
        'html' => $html,
        'evento' => $evento
    ]);
    
} catch (Exception $e) {
    error_log("Error obteniendo evento: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al obtener el evento'
    ]);
}

function generarHtmlEvento($evento, $tipo) {
    $estado_class = strtolower(str_replace(' ', '-', $evento['estado']));
    $tipo_icono = $tipo === 'correctivo' ? 'fas fa-tools' : 'fas fa-calendar-check';
    $tipo_color = $tipo === 'correctivo' ? '#17a2b8' : '#5cb85c';
    
    $html = '<div class="evento-detalle">';
    
    // Header del evento
    $html .= '<div class="evento-header" style="background-color: ' . $tipo_color . '; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">';
    $html .= '<div style="display: flex; align-items: center; gap: 10px;">';
    $html .= '<i class="' . $tipo_icono . '" style="font-size: 24px;"></i>';
    $html .= '<div>';
    $html .= '<h3 style="margin: 0; font-size: 18px;">' . htmlspecialchars($evento['titulo']) . '</h3>';
    $html .= '<p style="margin: 5px 0 0 0; opacity: 0.9;">ID: ' . $evento['id'] . ' • ' . ucfirst($tipo) . '</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Información básica
    $html .= '<div class="evento-info">';
    $html .= '<div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">';
    
    // Estado
    $html .= '<div class="info-item">';
    $html .= '<label style="font-weight: 500; color: #6c757d; font-size: 12px; text-transform: uppercase;">Estado</label>';
    $html .= '<div><span class="status-badge status-' . $estado_class . '">' . $evento['estado'] . '</span></div>';
    $html .= '</div>';
    
    // Solicitante
    $html .= '<div class="info-item">';
    $html .= '<label style="font-weight: 500; color: #6c757d; font-size: 12px; text-transform: uppercase;">Solicitante</label>';
    $html .= '<div>' . htmlspecialchars($evento['solicitante']) . '</div>';
    $html .= '</div>';
    
    // Cliente
    if ($evento['cliente_nombre']) {
        $html .= '<div class="info-item">';
        $html .= '<label style="font-weight: 500; color: #6c757d; font-size: 12px; text-transform: uppercase;">Cliente</label>';
        $html .= '<div>' . htmlspecialchars($evento['cliente_nombre']) . '</div>';
        $html .= '</div>';
    }
    
    // Proyecto
    if ($evento['proyecto_nombre']) {
        $html .= '<div class="info-item">';
        $html .= '<label style="font-weight: 500; color: #6c757d; font-size: 12px; text-transform: uppercase;">Proyecto</label>';
        $html .= '<div>' . htmlspecialchars($evento['proyecto_nombre']) . '</div>';
        $html .= '</div>';
    }
    
    // Asignado a
    if ($evento['asignado_nombre']) {
        $html .= '<div class="info-item">';
        $html .= '<label style="font-weight: 500; color: #6c757d; font-size: 12px; text-transform: uppercase;">Asignado a</label>';
        $html .= '<div>' . htmlspecialchars($evento['asignado_nombre']) . '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>'; // Cerrar info-grid
    
    // Fechas específicas según el tipo
    if ($tipo === 'correctivo') {
        $html .= '<div class="fechas-info" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">';
        $html .= '<h4 style="margin: 0 0 10px 0; color: #333;">Información de Fechas</h4>';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
        
        $html .= '<div>';
        $html .= '<label style="font-weight: 500; color: #6c757d; font-size: 12px;">Fecha de Creación</label>';
        $html .= '<div>' . date('d/m/Y H:i', strtotime($evento['fecha_creacion'] . ' ' . $evento['hora_creacion'])) . '</div>';
        $html .= '</div>';
        
        if ($evento['fecha_resolucion']) {
            $html .= '<div>';
            $html .= '<label style="font-weight: 500; color: #6c757d; font-size: 12px;">Fecha de Resolución</label>';
            $html .= '<div>' . date('d/m/Y H:i', strtotime($evento['fecha_resolucion'])) . '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
    } else {
        $html .= '<div class="fechas-info" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">';
        $html .= '<h4 style="margin: 0 0 10px 0; color: #333;">Programación</h4>';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
        
        if ($evento['fecha_programada']) {
            $html .= '<div>';
            $html .= '<label style="font-weight: 500; color: #6c757d; font-size: 12px;">Fecha Programada</label>';
            $html .= '<div>' . date('d/m/Y', strtotime($evento['fecha_programada']));
            if ($evento['hora_programada']) {
                $html .= ' ' . date('H:i', strtotime($evento['hora_programada']));
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        
        if ($evento['fecha_reprogramada']) {
            $html .= '<div>';
            $html .= '<label style="font-weight: 500; color: #6c757d; font-size: 12px;">Fecha Reprogramada</label>';
            $html .= '<div>' . date('d/m/Y', strtotime($evento['fecha_reprogramada']));
            if ($evento['hora_reprogramada']) {
                $html .= ' ' . date('H:i', strtotime($evento['hora_reprogramada']));
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
    }
    
    // Descripción
    if ($evento['descripcion']) {
        $html .= '<div class="descripcion-info" style="margin-bottom: 20px;">';
        $html .= '<h4 style="margin: 0 0 10px 0; color: #333;">Descripción</h4>';
        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; line-height: 1.6;">';
        $html .= nl2br(htmlspecialchars($evento['descripcion']));
        $html .= '</div>';
        $html .= '</div>';
    }
    
    // Resolución (solo para correctivos)
    if ($tipo === 'correctivo' && $evento['resolucion']) {
        $html .= '<div class="resolucion-info" style="margin-bottom: 20px;">';
        $html .= '<h4 style="margin: 0 0 10px 0; color: #333;">Resolución</h4>';
        $html .= '<div style="background: #e8f5e8; padding: 15px; border-radius: 8px; line-height: 1.6; border-left: 4px solid #5cb85c;">';
        $html .= nl2br(htmlspecialchars($evento['resolucion']));
        $html .= '</div>';
        $html .= '</div>';
    }
    
    // Acciones
    $html .= '<div class="evento-acciones" style="border-top: 1px solid #e9ecef; padding-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">';
    $html .= '<button class="btn btn-primary btn-sm" onclick="editarEvento(' . $evento['id'] . ', \'' . $tipo . '\')">';
    $html .= '<i class="fas fa-edit"></i> Editar';
    $html .= '</button>';
    
    if ($evento['estado'] !== 'Resuelto' && $evento['estado'] !== 'Cerrado') {
        $html .= '<button class="btn btn-success btn-sm" onclick="marcarResuelto(' . $evento['id'] . ', \'' . $tipo . '\')">';
        $html .= '<i class="fas fa-check"></i> Marcar Resuelto';
        $html .= '</button>';
    }
    
    $html .= '</div>';
    
    $html .= '</div>'; // Cerrar evento-info
    $html .= '</div>'; // Cerrar evento-detalle
    
    return $html;
}
?>