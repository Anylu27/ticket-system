<?php
require_once '../includes/conexion.php';
verificarSesion();

$page_title = 'Proyectos';
$mensaje = '';
$tipo_mensaje = '';

// Cliente preseleccionado (viene desde clientes.php)
$cliente_preseleccionado = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;

// Procesar acciones
if ($_POST) {
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'crear':
            $nombre = trim($_POST['nombre'] ?? '');
            $cliente_id = (int)($_POST['cliente_id'] ?? 0);
            $descripcion = trim($_POST['descripcion'] ?? '');
            $estado = $_POST['estado'] ?? 'Activo';
            
            if (empty($nombre) || !$cliente_id) {
                $mensaje = 'El nombre del proyecto y cliente son requeridos';
                $tipo_mensaje = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO proyectos (nombre, cliente_id, descripcion, estado) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nombre, $cliente_id, $descripcion, $estado]);
                    
                    registrarBitacora($_SESSION['usuario_nombre'], 'Proyectos', "Proyecto creado: $nombre");
                    $mensaje = 'Proyecto creado correctamente';
                    $tipo_mensaje = 'success';
                } catch (PDOException $e) {
                    $mensaje = 'Error al crear el proyecto';
                    $tipo_mensaje = 'error';
                }
            }
            break;
            
        case 'editar':
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $cliente_id = (int)($_POST['cliente_id'] ?? 0);
            $descripcion = trim($_POST['descripcion'] ?? '');
            $estado = $_POST['estado'] ?? 'Activo';
            
            if (!$id || empty($nombre) || !$cliente_id) {
                $mensaje = 'Datos incompletos para editar';
                $tipo_mensaje = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE proyectos SET nombre = ?, cliente_id = ?, descripcion = ?, estado = ? WHERE id = ?");
                    $stmt->execute([$nombre, $cliente_id, $descripcion, $estado, $id]);
                    
                    registrarBitacora($_SESSION['usuario_nombre'], 'Proyectos', "Proyecto editado ID: $id - $nombre");
                    $mensaje = 'Proyecto actualizado correctamente';
                    $tipo_mensaje = 'success';
                } catch (PDOException $e) {
                    $mensaje = 'Error al actualizar el proyecto';
                    $tipo_mensaje = 'error';
                }
            }
            break;
            
        case 'eliminar':
            $id = (int)($_POST['id'] ?? 0);
            
            if (!$id) {
                $mensaje = 'ID de proyecto no válido';
                $tipo_mensaje = 'error';
            } else {
                try {
                    // Verificar si tiene tickets asociados
                    $stmt = $pdo->prepare("
                        SELECT 
                            (SELECT COUNT(*) FROM correctivos WHERE proyecto_id = ?) +
                            (SELECT COUNT(*) FROM preventivos WHERE proyecto_id = ?) as total_tickets
                    ");
                    $stmt->execute([$id, $id]);
                    $total_tickets = $stmt->fetchColumn();
                    
                    if ($total_tickets > 0) {
                        $mensaje = 'No se puede eliminar el proyecto porque tiene tickets asociados';
                        $tipo_mensaje = 'error';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM proyectos WHERE id = ?");
                        $stmt->execute([$id]);
                        
                        registrarBitacora($_SESSION['usuario_nombre'], 'Proyectos', "Proyecto eliminado ID: $id");
                        $mensaje = 'Proyecto eliminado correctamente';
                        $tipo_mensaje = 'success';
                    }
                } catch (PDOException $e) {
                    $mensaje = 'Error al eliminar el proyecto';
                    $tipo_mensaje = 'error';
                }
            }
            break;
    }
}

// Obtener proyectos con información del cliente y estadísticas
$stmt = $pdo->query("
    SELECT 
        p.*,
        c.nombre as cliente_nombre,
        (SELECT COUNT(*) FROM correctivos WHERE proyecto_id = p.id) as total_correctivos,
        (SELECT COUNT(*) FROM preventivos WHERE proyecto_id = p.id) as total_preventivos,
        (SELECT COUNT(*) FROM correctivos WHERE proyecto_id = p.id AND estado = 'Resuelto') as correctivos_resueltos,
        (SELECT COUNT(*) FROM preventivos WHERE proyecto_id = p.id AND estado = 'Resuelto') as preventivos_resueltos
    FROM proyectos p
    LEFT JOIN clientes c ON p.cliente_id = c.id
    ORDER BY p.nombre ASC
");
$proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de clientes para los selectores
$clientes = $pdo->query("SELECT id, nombre FROM clientes WHERE estado = 'Activo' ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="page-content">
    <div class="proyectos-container">
        <!-- Header -->
        <div class="section-header">
            <div class="header-info">
                <h2>Gestión de Proyectos</h2>
                <?php if ($cliente_preseleccionado): ?>
                    <?php 
                    $cliente_nombre = '';
                    foreach ($clientes as $cliente) {
                        if ($cliente['id'] == $cliente_preseleccionado) {
                            $cliente_nombre = $cliente['nombre'];
                            break;
                        }
                    }
                    ?>
                    <p class="cliente-seleccionado">
                        <i class="fas fa-filter"></i> 
                        Mostrando proyectos para: <strong><?php echo htmlspecialchars($cliente_nombre); ?></strong>
                        <a href="proyectos.php" class="btn-limpiar-filtro">
                            <i class="fas fa-times"></i> Quitar filtro
                        </a>
                    </p>
                <?php endif; ?>
            </div>
            <button class="btn btn-success" onclick="nuevoProyecto()">
                <i class="fas fa-plus"></i> Nuevo Proyecto
            </button>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas rápidas -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon proyectos">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count($proyectos); ?></h3>
                    <p>Total Proyectos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon activos">
                    <i class="fas fa-play"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count(array_filter($proyectos, fn($p) => $p['estado'] === 'Activo')); ?></h3>
                    <p>Proyectos Activos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon tickets">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo array_sum(array_column($proyectos, 'total_correctivos')) + array_sum(array_column($proyectos, 'total_preventivos')); ?></h3>
                    <p>Total Tickets</p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros-section">
            <div class="filtros-row">
                <div class="form-group">
                    <select id="filtroCliente" onchange="filtrarPorCliente()">
                        <option value="">Todos los clientes</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente['id']; ?>" 
                                    <?php echo $cliente['id'] == $cliente_preseleccionado ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cliente['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <select id="filtroEstado" onchange="filtrarPorEstado()">
                        <option value="">Todos los estados</option>
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                        <option value="Completado">Completado</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" id="busquedaProyecto" placeholder="Buscar proyectos..." 
                           onkeyup="buscarProyectos()">
                </div>
            </div>
        </div>

        <!-- Grid de proyectos -->
        <div class="proyectos-grid" id="proyectosGrid">
            <?php foreach ($proyectos as $proyecto): ?>
                <?php 
                // Filtrar por cliente preseleccionado
                if ($cliente_preseleccionado && $proyecto['cliente_id'] != $cliente_preseleccionado) continue;
                
                $total_tickets = $proyecto['total_correctivos'] + $proyecto['total_preventivos'];
                $tickets_resueltos = $proyecto['correctivos_resueltos'] + $proyecto['preventivos_resueltos'];
                $porcentaje_completado = $total_tickets > 0 ? round(($tickets_resueltos / $total_tickets) * 100) : 0;
                ?>
                <div class="proyecto-card" data-cliente="<?php echo $proyecto['cliente_id']; ?>" 
                     data-estado="<?php echo $proyecto['estado']; ?>"
                     data-nombre="<?php echo strtolower($proyecto['nombre']); ?>">
                    
                    <!-- Header del proyecto -->
                    <div class="proyecto-header">
                        <div class="proyecto-titulo">
                            <h3><?php echo htmlspecialchars($proyecto['nombre']); ?></h3>
                            <span class="proyecto-id">ID: <?php echo $proyecto['id']; ?></span>
                        </div>
                        <div class="proyecto-estado">
                            <span class="status-badge status-<?php echo strtolower($proyecto['estado']); ?>">
                                <?php echo $proyecto['estado']; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Información del cliente -->
                    <div class="proyecto-cliente">
                        <i class="fas fa-building"></i>
                        <span><?php echo htmlspecialchars($proyecto['cliente_nombre']); ?></span>
                    </div>

                    <!-- Descripción -->
                    <?php if ($proyecto['descripcion']): ?>
                    <div class="proyecto-descripcion">
                        <p><?php echo htmlspecialchars(substr($proyecto['descripcion'], 0, 100)); ?><?php echo strlen($proyecto['descripcion']) > 100 ? '...' : ''; ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Estadísticas de tickets -->
                    <div class="proyecto-stats">
                        <div class="stats-row">
                            <div class="stat-item">
                                <i class="fas fa-tools text-info"></i>
                                <span>Correctivos: <?php echo $proyecto['total_correctivos']; ?></span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-calendar-check text-success"></i>
                                <span>Preventivos: <?php echo $proyecto['total_preventivos']; ?></span>
                            </div>
                        </div>
                        
                        <?php if ($total_tickets > 0): ?>
                        <div class="progreso-container">
                            <div class="progreso-info">
                                <span>Progreso: <?php echo $tickets_resueltos; ?>/<?php echo $total_tickets; ?> tickets</span>
                                <span><?php echo $porcentaje_completado; ?>%</span>
                            </div>
                            <div class="progreso-bar">
                                <div class="progreso-fill" style="width: <?php echo $porcentaje_completado; ?>%"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Acciones -->
                    <div class="proyecto-acciones">
                        <button class="btn btn-sm btn-primary" onclick="editarProyecto(<?php echo $proyecto['id']; ?>)">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button class="btn btn-sm btn-info" onclick="verTickets(<?php echo $proyecto['id']; ?>)">
                            <i class="fas fa-ticket-alt"></i> Tickets
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarProyecto(<?php echo $proyecto['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Mensaje si no hay proyectos -->
        <?php if (empty($proyectos) || ($cliente_preseleccionado && !array_filter($proyectos, fn($p) => $p['cliente_id'] == $cliente_preseleccionado))): ?>
        <div class="no-proyectos">
            <i class="fas fa-folder-open"></i>
            <h3>No hay proyectos registrados</h3>
            <p>Crea tu primer proyecto para comenzar</p>
            <button class="btn btn-success" onclick="nuevoProyecto()">
                <i class="fas fa-plus"></i> Crear Proyecto
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para crear/editar proyecto -->
<div id="modalProyecto" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="modalProyectoTitulo">Nuevo Proyecto</h3>
            <span class="close" onclick="ModalManager.close('modalProyecto')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formProyecto" method="POST">
                <input type="hidden" id="proyectoAccion" name="accion" value="crear">
                <input type="hidden" id="proyectoId" name="id" value="">
                
                <div class="form-group">
                    <label for="proyectoNombre">Nombre del Proyecto <span style="color: red;">*</span></label>
                    <input type="text" id="proyectoNombre" name="nombre" required 
                           placeholder="Ingrese el nombre del proyecto">
                </div>
                
                <div class="form-group">
                    <label for="proyectoCliente">Cliente <span style="color: red;">*</span></label>
                    <select id="proyectoCliente" name="cliente_id" required>
                        <option value="">Seleccionar cliente...</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente['id']; ?>" 
                                    <?php echo $cliente['id'] == $cliente_preseleccionado ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cliente['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="proyectoDescripcion">Descripción</label>
                    <textarea id="proyectoDescripcion" name="descripcion" rows="3" 
                              placeholder="Descripción opcional del proyecto"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="proyectoEstado">Estado</label>
                    <select id="proyectoEstado" name="estado">
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                        <option value="Completado">Completado</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="ModalManager.close('modalProyecto')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Datos de proyectos para JavaScript
const proyectosData = <?php echo json_encode($proyectos); ?>;
const clientesData = <?php echo json_encode($clientes); ?>;

function nuevoProyecto() {
    document.getElementById('modalProyectoTitulo').textContent = 'Nuevo Proyecto';
    document.getElementById('formProyecto').reset();
    document.getElementById('proyectoAccion').value = 'crear';
    document.getElementById('proyectoId').value = '';
    
    // Preseleccionar cliente si viene de la página de clientes
    const clientePreseleccionado = <?php echo $cliente_preseleccionado; ?>;
    if (clientePreseleccionado) {
        document.getElementById('proyectoCliente').value = clientePreseleccionado;
    }
    
    ModalManager.open('modalProyecto');
}

function editarProyecto(id) {
    const proyecto = proyectosData.find(p => p.id == id);
    
    if (!proyecto) {
        Utils.showNotification('Proyecto no encontrado', 'error');
        return;
    }
    
    document.getElementById('modalProyectoTitulo').textContent = 'Editar Proyecto';
    document.getElementById('proyectoAccion').value = 'editar';
    document.getElementById('proyectoId').value = proyecto.id;
    document.getElementById('proyectoNombre').value = proyecto.nombre;
    document.getElementById('proyectoCliente').value = proyecto.cliente_id;
    document.getElementById('proyectoDescripcion').value = proyecto.descripcion || '';
    document.getElementById('proyectoEstado').value = proyecto.estado;
    
    ModalManager.open('modalProyecto');
}

function eliminarProyecto(id) {
    const proyecto = proyectosData.find(p => p.id == id);
    
    if (!proyecto) {
        Utils.showNotification('Proyecto no encontrado', 'error');
        return;
    }
    
    const totalTickets = parseInt(proyecto.total_correctivos) + parseInt(proyecto.total_preventivos);
    
    if (totalTickets > 0) {
        Utils.showNotification(`No se puede eliminar el proyecto porque tiene ${totalTickets} ticket(s) asociado(s)`, 'error');
        return;
    }
    
    if (confirm(`¿Está seguro que desea eliminar el proyecto "${proyecto.nombre}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function verTickets(proyectoId) {
    // Redireccionar a vista de tickets filtrada por proyecto
    Utils.showNotification('Redirigiendo a tickets del proyecto...', 'info');
    window.location.href = `correctivos.php?proyecto_id=${proyectoId}`;
}

function filtrarPorCliente() {
    const clienteId = document.getElementById('filtroCliente').value;
    if (clienteId) {
        window.location.href = `?cliente_id=${clienteId}`;
    } else {
        window.location.href = 'proyectos.php';
    }
}

function filtrarPorEstado() {
    const estado = document.getElementById('filtroEstado').value;
    const cards = document.querySelectorAll('.proyecto-card');
    
    cards.forEach(card => {
        if (!estado || card.dataset.estado === estado) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function buscarProyectos() {
    const query = document.getElementById('busquedaProyecto').value.toLowerCase();
    const cards = document.querySelectorAll('.proyecto-card');
    
    cards.forEach(card => {
        const nombre = card.dataset.nombre;
        if (nombre.includes(query)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Validación del formulario
document.getElementById('formProyecto').addEventListener('submit', function(e) {
    const validation = FormManager.validate(this);
    if (!validation.isValid) {
        e.preventDefault();
        Utils.showNotification(validation.errors.join('<br>'), 'error');
        return false;
    }
});
</script>

<style>
.proyectos-container {
    max-width: 1400px;
    margin: 0 auto;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}

.header-info h2 {
    margin: 0 0 5px 0;
    color: #333;
    font-weight: 500;
}

.cliente-seleccionado {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-limpiar-filtro {
    background: #dc3545;
    color: white;
    border: none;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-limpiar-filtro:hover {
    background: #c82333;
    color: white;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
}

.stat-icon.proyectos { background: #007bff; }
.stat-icon.activos { background: #28a745; }
.stat-icon.tickets { background: #6f42c1; }

.stat-info h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    color: #333;
}

.stat-info p {
    margin: 5px 0 0 0;
    color: #6c757d;
    font-size: 14px;
}

.filtros-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.filtros-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.proyectos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.proyecto-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px;
    transition: all 0.3s ease;
    border-left: 4px solid #5cb85c;
}

.proyecto-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.proyecto-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.proyecto-titulo h3 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 16px;
    font-weight: 500;
}

.proyecto-id {
    font-size: 12px;
    color: #6c757d;
}

.proyecto-cliente {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6c757d;
    font-size: 14px;
    margin-bottom: 15px;
}

.proyecto-descripcion {
    margin-bottom: 15px;
}

.proyecto-descripcion p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    line-height: 1.4;
}

.proyecto-stats {
    margin-bottom: 20px;
}

.stats-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    color: #6c757d;
}

.text-info { color: #17a2b8 !important; }
.text-success { color: #28a745 !important; }

.progreso-container {
    margin-top: 10px;
}

.progreso-info {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 5px;
}

.progreso-bar {
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
}

.progreso-fill {
    height: 100%;
    background: #5cb85c;
    transition: width 0.3s ease;
}

.proyecto-acciones {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.no-proyectos {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.no-proyectos i {
    font-size: 48px;
    margin-bottom: 20px;
    color: #dee2e6;
}

.no-proyectos h3 {
    margin: 0 0 10px 0;
    color: #495057;
}

.no-proyectos p {
    margin: 0 0 20px 0;
}

@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .stats-cards {
        grid-template-columns: 1fr;
    }
    
    .filtros-row {
        grid-template-columns: 1fr;
    }
    
    .proyectos-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-row {
        flex-direction: column;
        gap: 8px;
    }
    
    .proyecto-acciones {
        justify-content: center;
    }
}
</style>

<?php include '../includes/footer.php'; ?>