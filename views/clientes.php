<?php
require_once '../includes/conexion.php';
verificarSesion();

$page_title = 'Clientes';
$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones (crear, editar, eliminar)
if ($_POST) {
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'crear':
            $nombre = trim($_POST['nombre'] ?? '');
            $estado = $_POST['estado'] ?? 'Activo';
            
            if (empty($nombre)) {
                $mensaje = 'El nombre del cliente es requerido';
                $tipo_mensaje = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO clientes (nombre, estado) VALUES (?, ?)");
                    $stmt->execute([$nombre, $estado]);
                    
                    registrarBitacora($_SESSION['usuario_nombre'], 'Clientes', "Cliente creado: $nombre");
                    $mensaje = 'Cliente creado correctamente';
                    $tipo_mensaje = 'success';
                } catch (PDOException $e) {
                    $mensaje = 'Error al crear el cliente';
                    $tipo_mensaje = 'error';
                }
            }
            break;
            
        case 'editar':
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $estado = $_POST['estado'] ?? 'Activo';
            
            if (!$id || empty($nombre)) {
                $mensaje = 'Datos incompletos para editar';
                $tipo_mensaje = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE clientes SET nombre = ?, estado = ? WHERE id = ?");
                    $stmt->execute([$nombre, $estado, $id]);
                    
                    registrarBitacora($_SESSION['usuario_nombre'], 'Clientes', "Cliente editado ID: $id - $nombre");
                    $mensaje = 'Cliente actualizado correctamente';
                    $tipo_mensaje = 'success';
                } catch (PDOException $e) {
                    $mensaje = 'Error al actualizar el cliente';
                    $tipo_mensaje = 'error';
                }
            }
            break;
            
        case 'eliminar':
            $id = (int)($_POST['id'] ?? 0);
            
            if (!$id) {
                $mensaje = 'ID de cliente no válido';
                $tipo_mensaje = 'error';
            } else {
                try {
                    // Verificar si tiene proyectos asociados
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM proyectos WHERE cliente_id = ?");
                    $stmt->execute([$id]);
                    $proyectos_count = $stmt->fetchColumn();
                    
                    if ($proyectos_count > 0) {
                        $mensaje = 'No se puede eliminar el cliente porque tiene proyectos asociados';
                        $tipo_mensaje = 'error';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
                        $stmt->execute([$id]);
                        
                        registrarBitacora($_SESSION['usuario_nombre'], 'Clientes', "Cliente eliminado ID: $id");
                        $mensaje = 'Cliente eliminado correctamente';
                        $tipo_mensaje = 'success';
                    }
                } catch (PDOException $e) {
                    $mensaje = 'Error al eliminar el cliente';
                    $tipo_mensaje = 'error';
                }
            }
            break;
    }
}

// Obtener lista de clientes
$stmt = $pdo->query("
    SELECT 
        c.*,
        COUNT(p.id) as total_proyectos
    FROM clientes c
    LEFT JOIN proyectos p ON c.id = p.cliente_id
    GROUP BY c.id
    ORDER BY c.nombre ASC
");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="page-content">
    <div class="clientes-container">
        <!-- Header -->
        <div class="section-header">
            <h2>Gestión de Clientes</h2>
            <button class="btn btn-success" onclick="ModalManager.open('modalCliente')">
                <i class="fas fa-plus"></i> Nuevo Cliente
            </button>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- Tabla de clientes -->
        <div class="table-container">
            <table class="clientes-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th>Proyectos</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                    <tr>
                        <td><?php echo $cliente['id']; ?></td>
                        <td><?php echo htmlspecialchars($cliente['nombre']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($cliente['estado']); ?>">
                                <?php echo $cliente['estado']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-count"><?php echo $cliente['total_proyectos']; ?></span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($cliente['fecha_creacion'] ?? 'now')); ?></td>
                        <td>
                            <div class="acciones-grupo">
                                <button class="btn btn-sm btn-primary" onclick="editarCliente(<?php echo $cliente['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-info" onclick="verProyectos(<?php echo $cliente['id']; ?>)">
                                    <i class="fas fa-folder"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="eliminarCliente(<?php echo $cliente['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para crear/editar cliente -->
<div id="modalCliente" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 id="modalClienteTitulo">Nuevo Cliente</h3>
            <span class="close" onclick="ModalManager.close('modalCliente')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formCliente" method="POST">
                <input type="hidden" id="clienteAccion" name="accion" value="crear">
                <input type="hidden" id="clienteId" name="id" value="">
                
                <div class="form-group">
                    <label for="clienteNombre">Nombre del Cliente <span style="color: red;">*</span></label>
                    <input type="text" id="clienteNombre" name="nombre" required 
                           placeholder="Ingrese el nombre del cliente">
                </div>
                
                <div class="form-group">
                    <label for="clienteEstado">Estado</label>
                    <select id="clienteEstado" name="estado">
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="ModalManager.close('modalCliente')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para ver proyectos -->
<div id="modalProyectos" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="modalProyectosTitulo">Proyectos del Cliente</h3>
            <span class="close" onclick="ModalManager.close('modalProyectos')">&times;</span>
        </div>
        <div class="modal-body" id="modalProyectosContenido">
            <!-- Contenido cargado dinámicamente -->
        </div>
    </div>
</div>

<script>
// Variable global para almacenar datos del cliente
let clienteActual = null;

function editarCliente(id) {
    // Buscar datos del cliente
    const clientes = <?php echo json_encode($clientes); ?>;
    clienteActual = clientes.find(c => c.id == id);
    
    if (!clienteActual) {
        Utils.showNotification('Cliente no encontrado', 'error');
        return;
    }
    
    // Configurar modal para edición
    document.getElementById('modalClienteTitulo').textContent = 'Editar Cliente';
    document.getElementById('clienteAccion').value = 'editar';
    document.getElementById('clienteId').value = clienteActual.id;
    document.getElementById('clienteNombre').value = clienteActual.nombre;
    document.getElementById('clienteEstado').value = clienteActual.estado;
    
    ModalManager.open('modalCliente');
}

function eliminarCliente(id) {
    const clientes = <?php echo json_encode($clientes); ?>;
    const cliente = clientes.find(c => c.id == id);
    
    if (!cliente) {
        Utils.showNotification('Cliente no encontrado', 'error');
        return;
    }
    
    if (cliente.total_proyectos > 0) {
        Utils.showNotification('No se puede eliminar el cliente porque tiene ' + cliente.total_proyectos + ' proyecto(s) asociado(s)', 'error');
        return;
    }
    
    if (confirm(`¿Está seguro que desea eliminar el cliente "${cliente.nombre}"?`)) {
        // Crear formulario para enviar eliminación
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

async function verProyectos(clienteId) {
    const clientes = <?php echo json_encode($clientes); ?>;
    const cliente = clientes.find(c => c.id == clienteId);
    
    if (!cliente) {
        Utils.showNotification('Cliente no encontrado', 'error');
        return;
    }
    
    document.getElementById('modalProyectosTitulo').textContent = `Proyectos de ${cliente.nombre}`;
    
    try {
        // Simular carga de proyectos (en un sistema real esto sería una llamada AJAX)
        const proyectosHtml = `
            <div class="proyectos-lista">
                <div class="proyectos-header">
                    <p>Total de proyectos: <strong>${cliente.total_proyectos}</strong></p>
                    <button class="btn btn-success btn-sm" onclick="nuevoProyecto(${clienteId})">
                        <i class="fas fa-plus"></i> Nuevo Proyecto
                    </button>
                </div>
                ${cliente.total_proyectos > 0 ? `
                    <table class="proyectos-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>CEMI-HOSPITAL SANTA FE</td>
                                <td><span class="status-badge status-activo">Activo</span></td>
                                <td>15/01/2024</td>
                                <td>
                                    <button class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                ` : '<p class="no-proyectos">No hay proyectos registrados para este cliente.</p>'}
            </div>
        `;
        
        document.getElementById('modalProyectosContenido').innerHTML = proyectosHtml;
        ModalManager.open('modalProyectos');
        
    } catch (error) {
        Utils.showNotification('Error al cargar proyectos', 'error');
    }
}

function nuevoProyecto(clienteId) {
    Utils.showNotification('Redirigiendo a gestión de proyectos...', 'info');
    // Redireccionar a página de proyectos con cliente preseleccionado
    window.location.href = `proyectos.php?cliente_id=${clienteId}`;
}

// Resetear formulario al abrir modal para nuevo cliente
document.getElementById('modalCliente').addEventListener('show', function() {
    if (document.getElementById('clienteAccion').value === 'crear') {
        document.getElementById('formCliente').reset();
        document.getElementById('modalClienteTitulo').textContent = 'Nuevo Cliente';
        document.getElementById('clienteAccion').value = 'crear';
        document.getElementById('clienteId').value = '';
    }
});

// Validación del formulario
document.getElementById('formCliente').addEventListener('submit', function(e) {
    const validation = FormManager.validate(this);
    if (!validation.isValid) {
        e.preventDefault();
        Utils.showNotification(validation.errors.join('<br>'), 'error');
        return false;
    }
});

// Configurar tabla para búsqueda y ordenamiento
document.addEventListener('DOMContentLoaded', function() {
    // Agregar funcionalidad de búsqueda
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Buscar clientes...';
    searchInput.style.cssText = 'margin-bottom: 20px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 300px;';
    
    const tableContainer = document.querySelector('.table-container');
    tableContainer.insertBefore(searchInput, tableContainer.firstChild);
    
    // Funcionalidad de búsqueda en tiempo real
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const rows = document.querySelectorAll('.clientes-table tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
});
</script>

<style>
.clientes-container {
    max-width: 1200px;
    margin: 0 auto;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}

.section-header h2 {
    margin: 0;
    color: #333;
    font-weight: 500;
}

.clientes-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.clientes-table th,
.clientes-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.clientes-table th {
    background: #f8f9fa;
    font-weight: 500;
    color: #333;
    font-size: 13px;
    text-transform: uppercase;
}

.clientes-table tbody tr:hover {
    background: #f8f9fa;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-activo {
    background: #e8f5e8;
    color: #2e7d32;
}

.status-inactivo {
    background: #ffebee;
    color: #c62828;
}

.badge-count {
    background: #007bff;
    color: white;
    padding: 4px 8px;
    border-radius: 50%;
    font-size: 12px;
    font-weight: 500;
    min-width: 20px;
    text-align: center;
    display: inline-block;
}

.acciones-grupo {
    display: flex;
    gap: 5px;
}

.proyectos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.proyectos-table {
    width: 100%;
    border-collapse: collapse;
}

.proyectos-table th,
.proyectos-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}

.proyectos-table th {
    background: #f8f9fa;
    font-weight: 500;
    font-size: 12px;
    text-transform: uppercase;
    color: #6c757d;
}

.no-proyectos {
    text-align: center;
    color: #6c757d;
    padding: 40px;
    font-style: italic;
}

.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    border: 1px solid;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .acciones-grupo {
        justify-content: center;
    }
    
    .clientes-table {
        font-size: 14px;
    }
    
    .clientes-table th,
    .clientes-table td {
        padding: 8px 10px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>