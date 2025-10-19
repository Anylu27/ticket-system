<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/conexion.php';
verificarSesion();

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit();
}

$ticket_id = isset($input['id']) ? (int)$input['id'] : 0;
$nuevo_estado = isset($input['estado']) ? trim($input['estado']) : '';
$tipo_ticket = isset($input['tipo']) ? trim($input['tipo']) : 'correctivo';

// Validar datos
if (!$ticket_id || !$nuevo_estado) {
    echo json_encode(['success' => false, 'message' => 'ID y estado son requeridos']);
    exit();
}

// Estados válidos
$estados_validos = [
    'correctivo' => ['Asignado', 'En Proceso', 'Resuelto', 'Cerrado', 'En espera del cliente'],
    'preventivo' => ['Asignado', 'En Proceso', 'Resuelto', 'Cerrado']
];

if (!in_array($nuevo_estado, $estados_validos[$tipo_ticket])) {
    echo json_encode(['success' => false, 'message' => 'Estado inválido']);
    exit();
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Obtener estado actual
    $tabla = $tipo_ticket === 'correctivo' ? 'correctivos' : 'preventivos';
    $stmt = $pdo->prepare("SELECT estado, titulo FROM $tabla WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        throw new Exception('Ticket no encontrado');
    }
    
    $estado_anterior = $ticket['estado'];
    
    // Actualizar estado
    $stmt = $pdo->prepare("UPDATE $tabla SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $ticket_id]);
    
    // Si se marca como resuelto y no tiene fecha de resolución, agregarla
    if ($nuevo_estado === 'Resuelto' && $tipo_ticket === 'correctivo') {
        $stmt = $pdo->prepare("UPDATE correctivos SET fecha_resolucion = NOW() WHERE id = ? AND fecha_resolucion IS NULL");
        $stmt->execute([$ticket_id]);
    }
    
    // Registrar en bitácora
    $accion = "Cambio de estado en $tipo_ticket ID: $ticket_id - '{$ticket['titulo']}' de '$estado_anterior' a '$nuevo_estado'";
    registrarBitacora($_SESSION['usuario_nombre'], ucfirst($tipo_ticket), $accion);
    
    // Registrar historial de cambios (si existe tabla de historial)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO historial_tickets (ticket_id, tipo_ticket, campo_modificado, valor_anterior, valor_nuevo, usuario_id, fecha_cambio) 
            VALUES (?, ?, 'estado', ?, ?, ?, NOW())
        ");
        $stmt->execute([$ticket_id, $tipo_ticket, $estado_anterior, $nuevo_estado, $_SESSION['usuario_id']]);
    } catch (Exception $e) {
        // La tabla de historial puede no existir, continuar sin error
    }
    
    // Confirmar transacción
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Estado actualizado correctamente',
        'nuevo_estado' => $nuevo_estado,
        'estado_anterior' => $estado_anterior
    ]);
    
} catch (Exception $e) {
    // Revertir transacción
    $pdo->rollBack();
    
    error_log("Error cambiando estado de ticket: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al actualizar el estado: ' . $e->getMessage()
    ]);
}
?>