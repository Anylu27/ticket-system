<?php
require_once '../includes/conexion.php';
verificarSesion();

$page_title = 'Correctivos';

// Parámetros de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$offset = ($page - 1) * $records_per_page;

// Filtros
$filtro_estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$filtro_cliente = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
$filtro_proyecto = isset($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : 0;

// Construir consulta con filtros
$where_conditions = [];
$params = [];

if (!empty($filtro_estado)) {
    $where_conditions[] = "c.estado = ?";
    $params[] = $filtro_estado;
}

if ($filtro_cliente > 0) {
    $where_conditions[] = "c.cliente_id = ?";
    $params[] = $filtro_cliente;
}

if ($filtro_proyecto > 0) {
    $where_conditions[] = "c.proyecto_id = ?";
    $params[] = $filtro_proyecto;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Consulta principal
$sql = "
    SELECT 
        c.*,
        cl.nombre as cliente_nombre,
        p.nombre as proyecto_nombre,
        cat.nombre as categoria_nombre,
        u.nombre as asignado_nombre
    FROM correctivos c
    LEFT JOIN clientes cl ON c.cliente_id = cl.id
    LEFT JOIN proyectos p ON c.proyecto_id = p.id
    LEFT JOIN categorias cat ON c.categoria_id = cat.id
    LEFT JOIN usuarios u ON c.asignado_a = u.id
    $where_clause
    ORDER BY c.fecha_creacion DESC
    LIMIT $records_per_page OFFSET $offset
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $correctivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total de registros
    $count_sql = "
        SELECT COUNT(*) as total
        FROM correctivos c
        LEFT JOIN clientes cl ON c.cliente_id = cl.id
        LEFT JOIN proyectos p ON c.proyecto_id = p.id
        $where_clause
    ";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    
} catch (PDOException $e) {
    die("Error en consulta: " . $e->getMessage());
}

$total_pages = ceil($total_records / $records_per_page);

// Obtener listas para filtros
$clientes = $pdo->query("SELECT id, nombre FROM clientes WHERE estado = 'Activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$proyectos = $pdo->query("SELECT id, nombre FROM proyectos WHERE estado = 'Activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="page-content">
    <div class="correctivos-container">
        <!-- Header con filtros -->
        <div class="section-header">
            <div class="header-info">
                <h2>Gestión de Correctivos</h2>
                <p>Total de registros: <strong><?php echo $total_records; ?></strong></p>
            </div>
            <div class="header-actions">
                <button class="btn btn-success" onclick="nuevoCorrectivo()">
                    <i class="fas fa-plus"></i> Nuevo Correctivo
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <div class="filters-row">
                <div class="filter-group">
                    <label>Estado:</label>
                    <select id="filtroEstado" onchange="aplicarFiltros()">
                        <option value="">Todos</option>
                        <option value="Asignado" <?php echo $filtro_estado == 'Asignado' ? 'selected' : ''; ?>>Asignado</option>
                        <option value="En Proceso" <?php echo $filtro_estado == 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                        <option value="Resuelto" <?php echo $filtro_estado == 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                        <option value="Cerrado" <?php echo $filtro_estado == 'Cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                        <option value="En espera del cliente" <?php echo $filtro_estado == 'En espera del cliente' ? 'selected' : ''; ?>>En espera del cliente</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Cliente:</label>
                    <select id="filtroCliente" onchange="aplicarFiltros()">
                        <option value="">Todos los clientes</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente['id']; ?>" 
                                    <?php echo $filtro_cliente == $cliente['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cliente['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Proyecto:</label>
                    <select id="filtroProyecto" onchange="aplicarFiltros()">
                        <option value="">Todos los proyectos</option>
                        <?php foreach ($proyectos as $proyecto): ?>
                            <option value="<?php echo $proyecto['id']; ?>" 
                                    <?php echo $filtro_proyecto == $proyecto['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($proyecto['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Por página:</label>
                    <select id="recordsPorPagina" onchange="cambiarRecordsPorPagina()">
                        <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $records_per_page == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $records_per_page == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla de correctivos -->
        <div class="table-container">
            <table class="tabla-correctivos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Cliente</th>
                        <th>Proyecto</th>
                        <th>Solicitante</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>Asignado a</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($correctivos)): ?>
                    <tr>
                        <td colspan="10" class="text-center">No se encontraron correctivos</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($correctivos as $correctivo): ?>
                    <tr>
                        <td><strong>#<?php echo $correctivo['id']; ?></strong></td>
                        <td>
                            <div class="ticket-title">
                                <?php echo htmlspecialchars(substr($correctivo['titulo'], 0, 50)); ?>
                                <?php echo strlen($correctivo['titulo']) > 50 ? '...' : ''; ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($correctivo['cliente_nombre'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($correctivo['proyecto_nombre'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($correctivo['solicitante']); ?></td>
                        <td>
                            <?php 
                            $estado_class = strtolower(str_replace(' ', '-', $correctivo['estado']));
                            ?>
                            <span class="status-badge status-<?php echo $estado_class; ?>">
                                <?php echo $correctivo['estado']; ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $prioridad_class = strtolower($correctivo['prioridad']);
                            $prioridad_color = [
                                'baja' => '#28a745',
                                'media' => '#ffc107', 
                                'alta' => '#fd7e14',
                                'crítica' => '#dc3545'
                            ];
                            ?>
                            <span class="priority-badge" style="background-color: <?php echo $prioridad_color[$prioridad_class] ?? '#6c757d'; ?>;">
                                <?php echo $correctivo['prioridad']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($correctivo['asignado_nombre'] ?? 'Sin asignar'); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($correctivo['fecha_creacion'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="verDetalle(<?php echo $correctivo['id']; ?>)" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-info" onclick="editarCorrectivo(<?php echo $correctivo['id']; ?>)" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($correctivo['estado'] !== 'Resuelto' && $correctivo['estado'] !== 'Cerrado'): ?>
                                <button class="btn btn-sm btn-success" onclick="cambiarEstado(<?php echo $correctivo['id']; ?>, 'Resuelto')" title="Marcar como resuelto">
                                    <i class="fas fa-check"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Mostrando <?php echo $offset + 1; ?> a <?php echo min($offset + $records_per_page, $total_records); ?> de <?php echo $total_records; ?> registros
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $records_per_page; ?>&estado=<?php echo $filtro_estado; ?>&cliente_id=<?php echo $filtro_cliente; ?>&proyecto_id=<?php echo $filtro_proyecto; ?>" class="btn btn-sm">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="btn btn-sm btn-primary current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&per_page=<?php echo $records_per_page; ?>&estado=<?php echo $filtro_estado; ?>&cliente_id=<?php echo $filtro_cliente; ?>&proyecto_id=<?php echo $filtro_proyecto; ?>" class="btn btn-sm"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $records_per_page; ?>&estado=<?php echo $filtro_estado; ?>&cliente_id=<?php echo $filtro_cliente; ?>&proyecto_id=<?php echo $filtro_proyecto; ?>" class="btn btn-sm">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.correctivos-container {
    max-width: 1400px;
    margin: 0 auto;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.header-info h2 {
    margin: 0 0 5px 0;
    color: #333;
}

.header-info p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
}

.filters-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.filters-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    align-items: end;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
    font-size: 13px;
}

.filter-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.tabla-correctivos {
    width: 100%;
    font-size: 13px;
}

.tabla-correctivos th {
    background: #f8f9fa;
    font-weight: 500;
    text-transform: uppercase;
    font-size: 12px;
    padding: 12px 8px;
}

.tabla-correctivos td {
    padding: 12px 8px;
    vertical-align: middle;
}

.ticket-title {
    font-weight: 500;
    color: #333;
}

.priority-badge {
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.action-buttons {
    display: flex;
    gap: 5px;
    justify-content: center;
}

.action-buttons .btn {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.pagination {
    display: flex;
    gap: 5px;
    align-items: center;
}

.pagination .btn {
    padding: 6px 12px;
    border: 1px solid #dee2e6;
    text-decoration: none;
    color: #495057;
    border-radius: 4px;
}

.pagination .btn:hover {
    background: #e9ecef;
}

.pagination .btn.current {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .filters-row {
        grid-template-columns: 1fr;
    }
    
    .tabla-correctivos {
        font-size: 12px;
    }
    
    .pagination-container {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<script>
function aplicarFiltros() {
    const estado = document.getElementById('filtroEstado').value;
    const cliente = document.getElementById('filtroCliente').value;
    const proyecto = document.getElementById('filtroProyecto').value;
    const perPage = document.getElementById('recordsPorPagina').value;
    
    let url = '?page=1';
    url += '&per_page=' + perPage;
    if (estado) url += '&estado=' + encodeURIComponent(estado);
    if (cliente) url += '&cliente_id=' + cliente;
    if (proyecto) url += '&proyecto_id=' + proyecto;
    
    window.location.href = url;
}

function cambiarRecordsPorPagina() {
    aplicarFiltros();
}

function nuevoCorrectivo() {
    console.log('Intentando navegar a nuevo_correctivo.php');
    window.location.href = 'nuevo_correctivo.php';
}

function verDetalle(id) {
    console.log('Navegando a detalle del correctivo:', id);
    window.location.href = 'nuevo_correctivo.php?id=' + id;
}

function editarCorrectivo(id) {
    console.log('Navegando a editar correctivo:', id);
    window.location.href = 'nuevo_correctivo.php?id=' + id;
}

function cambiarEstado(id, estado) {
    if (confirm('¿Está seguro que desea cambiar el estado a "' + estado + '"?')) {
        alert('Funcionalidad en desarrollo: Cambiar estado del correctivo #' + id + ' a ' + estado);
    }
}

// Debug para verificar que el archivo nuevo_correctivo.php existe
document.addEventListener('DOMContentLoaded', function() {
    console.log('Página correctivos.php cargada correctamente');
});
</script>

<?php include '../includes/footer.php'; ?>