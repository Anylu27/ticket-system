<?php
require_once '../includes/conexion.php';
verificarSesion();

$page_title = 'Gestión de Activos';
include '../includes/header.php';
?>

<div class="page-content">
    <div class="activos-container">
        <div class="activos-header">
            <h2>Gestión de Activos</h2>
            <div class="activos-actions">
                <button class="btn btn-success" onclick="nuevoActivo()">
                    <i class="fas fa-plus"></i> Nuevo Activo
                </button>
                <button class="btn btn-primary" onclick="importarActivos()">
                    <i class="fas fa-upload"></i> Importar
                </button>
                <button class="btn btn-secondary" onclick="exportarActivos()">
                    <i class="fas fa-download"></i> Exportar
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros-section">
            <div class="filtros-row">
                <div class="form-group">
                    <label>Buscar:</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Serial, nombre, marca...">
                </div>
                
                <div class="form-group">
                    <label>Tipo de Activo:</label>
                    <select id="filtroTipo" class="form-control">
                        <option value="">Todos</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Estado:</label>
                    <select id="filtroEstado" class="form-control">
                        <option value="">Todos</option>
                        <option value="ACTIVO">Activo</option>
                        <option value="EN_MANTENIMIENTO">En Mantenimiento</option>
                        <option value="INACTIVO">Inactivo</option>
                        <option value="DADO DE BAJA">Dado de Baja</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Ubicación:</label>
                    <select id="filtroUbicacion" class="form-control">
                        <option value="">Todas</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button class="btn btn-primary" onclick="aplicarFiltros()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button class="btn btn-secondary" onclick="limpiarFiltros()">
                        <i class="fas fa-eraser"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>

        <!-- Lista de activos -->
        <div class="table-container">
            <div id="loadingIndicator" class="loading-indicator" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i> Cargando activos...
            </div>
            
            <table class="activos-table" id="activosTable">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" onchange="seleccionarTodos(this)">
                        </th>
                        <th>Serial</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Marca/Modelo</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th>Fecha Instalación</th>
                        <th>Valor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="activosTableBody">
                    <!-- Los datos se cargarán dinámicamente -->
                </tbody>
            </table>
            
            <!-- Paginación -->
            <div class="pagination-container">
                <div class="pagination-info" id="paginationInfo">
                    <!-- Se actualizará dinámicamente -->
                </div>
                <div class="pagination" id="paginationControls">
                    <!-- Se actualizará dinámicamente -->
                </div>
            </div>
        </div>

        <!-- Resumen de estadísticas -->
        <div class="estadisticas-section">
            <div class="stats-grid" id="statsGrid">
                <!-- Se cargarán dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Modal para nuevo/editar activo -->
<div id="modalActivo" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3 id="modalActivoTitulo">Nuevo Activo</h3>
            <span class="close" onclick="ModalManager.close('modalActivo')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formActivo">
                <input type="hidden" id="activo_id" name="activo_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="serial">Serial <span style="color: red;">*</span></label>
                        <input type="text" id="serial" name="serial" required>
                    </div>
                    <div class="form-group">
                        <label for="serial_2">Serial 2</label>
                        <input type="text" id="serial_2" name="serial_2">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre <span style="color: red;">*</span></label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="tipo_activo">Tipo <span style="color: red;">*</span></label>
                        <select id="tipo_activo" name="tipo_activo" required>
                            <option value="">Seleccionar tipo...</option>
                            <option value="TECNOLOGÍA">Tecnología</option>
                            <option value="EQUIPO BIOMEDICO">Equipo Biomédico</option>
                            <option value="ADMINISTRATIVO">Administrativo</option>
                            <option value="MOBILIARIO">Mobiliario</option>
                            <option value="INFRAESTRUCTURA">Infraestructura</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="marca_id">Marca</label>
                        <select id="marca_id" name="marca_id">
                            <option value="">Seleccionar marca...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="modelo_id">Modelo</label>
                        <select id="modelo_id" name="modelo_id">
                            <option value="">Seleccionar modelo...</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="ubicacion_id">Ubicación</label>
                        <select id="ubicacion_id" name="ubicacion_id">
                            <option value="">Seleccionar ubicación...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="area_id">Área</label>
                        <select id="area_id" name="area_id">
                            <option value="">Seleccionar área...</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="estado_activo">Estado</label>
                        <select id="estado_activo" name="estado_activo">
                            <option value="ACTIVO">Activo</option>
                            <option value="EN_MANTENIMIENTO">En Mantenimiento</option>
                            <option value="INACTIVO">Inactivo</option>
                            <option value="DADO DE BAJA">Dado de Baja</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fecha_instalacion">Fecha de Instalación</label>
                        <input type="date" id="fecha_instalacion" name="fecha_instalacion">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="valor_adquisicion">Valor de Adquisición</label>
                        <input type="number" id="valor_adquisicion" name="valor_adquisicion" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="cliente_id">Cliente</label>
                        <select id="cliente_id" name="cliente_id">
                            <option value="">Seleccionar cliente...</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="2"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="ModalManager.close('modalActivo')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Variables globales
let currentPage = 1;
let currentFilters = {};
let marcasData = [];
let modelosData = [];

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    cargarOpcionesFiltros();
    cargarActivos();
    
    // Event listeners para filtros
    document.getElementById('searchInput').addEventListener('input', debounce(aplicarFiltros, 500));
    document.getElementById('filtroTipo').addEventListener('change', aplicarFiltros);
    document.getElementById('filtroEstado').addEventListener('change', aplicarFiltros);
    document.getElementById('filtroUbicacion').addEventListener('change', aplicarFiltros);
    
    // Event listener para cambio de marca
    document.getElementById('marca_id').addEventListener('change', function() {
        cargarModelos(this.value);
    });
    
    // Event listener para cambio de ubicación
    document.getElementById('ubicacion_id').addEventListener('change', function() {
        cargarAreas(this.value);
    });
});

// Función debounce para búsqueda
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Cargar opciones para filtros y formularios
async function cargarOpcionesFiltros() {
    try {
        // Cargar ubicaciones
        const ubicacionesResponse = await fetch('../api/ubicaciones.php?action=list');
        const ubicacionesData = await ubicacionesResponse.json();
        
        if (ubicacionesData.success) {
            const filtroUbicacion = document.getElementById('filtroUbicacion');
            const formUbicacion = document.getElementById('ubicacion_id');
            
            ubicacionesData.data.forEach(ubicacion => {
                const option1 = new Option(ubicacion.nombre, ubicacion.id);
                const option2 = new Option(ubicacion.nombre, ubicacion.id);
                filtroUbicacion.add(option1);
                formUbicacion.add(option2);
            });
        }
        
        // Cargar marcas
        const marcasResponse = await fetch('../api/marcas.php?action=list');
        const marcasDataResponse = await marcasResponse.json();
        
        if (marcasDataResponse.success) {
            marcasData = marcasDataResponse.data;
            const formMarca = document.getElementById('marca_id');
            
            marcasData.forEach(marca => {
                const option = new Option(marca.nombre, marca.id);
                formMarca.add(option);
            });
        }
        
        // Cargar clientes
        const clientesResponse = await fetch('../api/clientes.php?action=list');
        const clientesData = await clientesResponse.json();
        
        if (clientesData.success) {
            const formCliente = document.getElementById('cliente_id');
            
            clientesData.data.forEach(cliente => {
                const option = new Option(cliente.nombre, cliente.id);
                formCliente.add(option);
            });
        }
        
        // Cargar tipos únicos de activos
        const activosResponse = await fetch('../api/activos.php?action=list&limit=1000');
        const activosData = await activosResponse.json();
        
        if (activosData.success) {
            const tipos = [...new Set(activosData.data.map(activo => activo.tipo_activo).filter(tipo => tipo))];
            const filtroTipo = document.getElementById('filtroTipo');
            
            tipos.forEach(tipo => {
                const option = new Option(tipo, tipo);
                filtroTipo.add(option);
            });
        }
        
    } catch (error) {
        console.error('Error cargando opciones:', error);
        Utils.showNotification('Error cargando opciones de filtros', 'error');
    }
}

// Cargar modelos según marca seleccionada
async function cargarModelos(marcaId) {
    const modeloSelect = document.getElementById('modelo_id');
    modeloSelect.innerHTML = '<option value="">Seleccionar modelo...</option>';
    
    if (!marcaId) return;
    
    try {
        const response = await fetch(`../api/modelos.php?action=list&marca_id=${marcaId}`);
        const data = await response.json();
        
        if (data.success) {
            data.data.forEach(modelo => {
                const option = new Option(modelo.nombre, modelo.id);
                modeloSelect.add(option);
            });
        }
    } catch (error) {
        console.error('Error cargando modelos:', error);
    }
}

// Cargar áreas según ubicación seleccionada
async function cargarAreas(ubicacionId) {
    const areaSelect = document.getElementById('area_id');
    areaSelect.innerHTML = '<option value="">Seleccionar área...</option>';
    
    if (!ubicacionId) return;
    
    try {
        const response = await fetch(`../api/areas.php?action=list&ubicacion_id=${ubicacionId}`);
        const data = await response.json();
        
        if (data.success) {
            data.data.forEach(area => {
                const option = new Option(area.nombre, area.id);
                areaSelect.add(option);
            });
        }
    } catch (error) {
        console.error('Error cargando áreas:', error);
    }
}

// Cargar activos
async function cargarActivos(page = 1) {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const tableBody = document.getElementById('activosTableBody');
    
    loadingIndicator.style.display = 'block';
    tableBody.innerHTML = '';
    
    try {
        const params = new URLSearchParams({
            page: page,
            limit: 10,
            ...currentFilters
        });
        
        const response = await fetch(`../api/activos.php?action=list&${params}`);
        const data = await response.json();
        
        if (data.success) {
            mostrarActivos(data.data);
            actualizarPaginacion(data.pagination);
            currentPage = page;
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error cargando activos:', error);
        Utils.showNotification('Error cargando activos: ' + error.message, 'error');
        tableBody.innerHTML = '<tr><td colspan="10" class="text-center">Error cargando datos</td></tr>';
    } finally {
        loadingIndicator.style.display = 'none';
    }
}

// Mostrar activos en la tabla
function mostrarActivos(activos) {
    const tableBody = document.getElementById('activosTableBody');
    
    if (activos.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="10" class="text-center">No se encontraron activos</td></tr>';
        return;
    }
    
    tableBody.innerHTML = activos.map(activo => `
        <tr>
            <td><input type="checkbox" value="${activo.id}"></td>
            <td>${activo.serial || ''}</td>
            <td>${activo.nombre || ''}</td>
            <td>${activo.tipo_activo || ''}</td>
            <td>${[activo.marca_nombre, activo.modelo_nombre].filter(Boolean).join(' ') || ''}</td>
            <td>${activo.ubicacion_nombre || ''}</td>
            <td><span class="status-badge status-${activo.estado_activo.toLowerCase().replace(/[^a-z]/g, '')}">${activo.estado_activo}</span></td>
            <td>${activo.fecha_instalacion ? new Date(activo.fecha_instalacion).toLocaleDateString() : ''}</td>
            <td>${activo.valor_adquisicion ? '$' + parseFloat(activo.valor_adquisicion).toLocaleString() : ''}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarActivo(${activo.id})" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-info" onclick="verHistorial(${activo.id})" title="Historial">
                    <i class="fas fa-history"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="eliminarActivo(${activo.id})" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Actualizar paginación
function actualizarPaginacion(pagination) {
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    const start = ((pagination.current_page - 1) * pagination.per_page) + 1;
    const end = Math.min(pagination.current_page * pagination.per_page, pagination.total);
    
    paginationInfo.textContent = `Mostrando ${start}-${end} de ${pagination.total} activos`;
    
    // Generar controles de paginación
    let controls = '';
    
    if (pagination.current_page > 1) {
        controls += `<a href="#" onclick="cargarActivos(${pagination.current_page - 1})">Anterior</a>`;
    }
    
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const className = i === pagination.current_page ? 'current' : '';
        controls += `<a href="#" class="${className}" onclick="cargarActivos(${i})">${i}</a>`;
    }
    
    if (pagination.current_page < pagination.total_pages) {
        controls += `<a href="#" onclick="cargarActivos(${pagination.current_page + 1})">Siguiente</a>`;
    }
    
    paginationControls.innerHTML = controls;
}

// Aplicar filtros
function aplicarFiltros() {
    currentFilters = {
        search: document.getElementById('searchInput').value,
        tipo_activo: document.getElementById('filtroTipo').value,
        estado_activo: document.getElementById('filtroEstado').value,
        ubicacion_id: document.getElementById('filtroUbicacion').value
    };
    
    // Remover filtros vacíos
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) {
            delete currentFilters[key];
        }
    });
    
    cargarActivos(1);
}

// Limpiar filtros
function limpiarFiltros() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filtroTipo').value = '';
    document.getElementById('filtroEstado').value = '';
    document.getElementById('filtroUbicacion').value = '';
    
    currentFilters = {};
    cargarActivos(1);
}

// Nuevo activo
function nuevoActivo() {
    document.getElementById('modalActivoTitulo').textContent = 'Nuevo Activo';
    document.getElementById('formActivo').reset();
    document.getElementById('activo_id').value = '';
    
    // Limpiar selects dependientes
    document.getElementById('modelo_id').innerHTML = '<option value="">Seleccionar modelo...</option>';
    document.getElementById('area_id').innerHTML = '<option value="">Seleccionar área...</option>';
    
    ModalManager.open('modalActivo');
}

// Editar activo
async function editarActivo(id) {
    try {
        const response = await fetch(`../api/activos.php?action=get&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const activo = data.data;
            
            document.getElementById('modalActivoTitulo').textContent = 'Editar Activo';
            document.getElementById('activo_id').value = activo.id;
            document.getElementById('serial').value = activo.serial || '';
            document.getElementById('serial_2').value = activo.serial_2 || '';
            document.getElementById('nombre').value = activo.nombre || '';
            document.getElementById('descripcion').value = activo.descripcion || '';
            document.getElementById('tipo_activo').value = activo.tipo_activo || '';
            document.getElementById('estado_activo').value = activo.estado_activo || '';
            document.getElementById('fecha_instalacion').value = activo.fecha_instalacion || '';
            document.getElementById('valor_adquisicion').value = activo.valor_adquisicion || '';
            document.getElementById('observaciones').value = activo.observaciones || '';
            document.getElementById('marca_id').value = activo.marca_id || '';
            document.getElementById('ubicacion_id').value = activo.ubicacion_id || '';
            document.getElementById('cliente_id').value = activo.cliente_id || '';
            
            // Cargar modelos y áreas dependientes
            if (activo.marca_id) {
                await cargarModelos(activo.marca_id);
                document.getElementById('modelo_id').value = activo.modelo_id || '';
            }
            
            if (activo.ubicacion_id) {
                await cargarAreas(activo.ubicacion_id);
                document.getElementById('area_id').value = activo.area_id || '';
            }
            
            ModalManager.open('modalActivo');
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error cargando activo:', error);
        Utils.showNotification('Error cargando activo: ' + error.message, 'error');
    }
}

// Eliminar activo
async function eliminarActivo(id) {
    if (!confirm('¿Está seguro que desea eliminar este activo?')) {
        return;
    }
    
    try {
        const response = await fetch(`../api/activos.php?action=delete&id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            Utils.showNotification('Activo eliminado correctamente', 'success');
            cargarActivos(currentPage);
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error eliminando activo:', error);
        Utils.showNotification('Error eliminando activo: ' + error.message, 'error');
    }
}

// Ver historial
function verHistorial(id) {
    Utils.showNotification('Funcionalidad de historial en desarrollo', 'info');
}

// Importar activos
function importarActivos() {
    Utils.showNotification('Funcionalidad de importación en desarrollo', 'info');
}

// Exportar activos
function exportarActivos() {
    Utils.showNotification('Exportando activos...', 'info');
    // Implementar exportación
}

// Seleccionar todos
function seleccionarTodos(checkbox) {
    const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

// Envío del formulario
document.getElementById('formActivo').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    // Convertir valores vacíos a null
    Object.keys(data).forEach(key => {
        if (data[key] === '') {
            data[key] = null;
        }
    });
    
    const isEdit = data.activo_id;
    const url = isEdit ? `../api/activos.php?action=update&id=${data.activo_id}` : '../api/activos.php?action=create';
    const method = isEdit ? 'PUT' : 'POST';
    
    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            Utils.showNotification(result.message || 'Activo guardado correctamente', 'success');
            ModalManager.close('modalActivo');
            cargarActivos(currentPage);
        } else {
            throw new Error(result.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error guardando activo:', error);
        Utils.showNotification('Error guardando activo: ' + error.message, 'error');
    }
});
</script>

<style>
.activos-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.activos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}

.activos-header h2 {
    margin: 0;
    color: #333;
    font-weight: 500;
}

.activos-actions {
    display: flex;
    gap: 10px;
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
    align-items: end;
}

.table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.activos-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.activos-table th,
.activos-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.activos-table th {
    background: #f8f9fa;
    font-weight: 500;
    color: #333;
    font-size: 13px;
    text-transform: uppercase;
    position: sticky;
    top: 0;
    z-index: 10;
}

.activos-table tbody tr:hover {
    background: #f8f9fa;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    white-space: nowrap;
}

.status-activo {
    background: #e8f5e8;
    color: #2e7d32;
}

.status-enmantenimiento {
    background: #fff3e0;
    color: #f57c00;
}

.status-inactivo {
    background: #ffebee;
    color: #c62828;
}

.status-dadodebaja {
    background: #fafafa;
    color: #616161;
}

.loading-indicator {
    text-align: center;
    padding: 40px;
    color: #666;
    font-size: 16px;
}

.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
}

.pagination-info {
    color: #666;
    font-size: 14px;
}

.pagination {
    display: flex;
    gap: 5px;
}

.pagination a {
    padding: 8px 12px;
    text-decoration: none;
    color: #007bff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    transition: all 0.2s;
}

.pagination a:hover {
    background: #e9ecef;
}

.pagination a.current {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.estadisticas-section {
    margin-top: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
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

.stat-icon.hardware {
    background: #007bff;
}

.stat-icon.software {
    background: #28a745;
}

.stat-icon.medical {
    background: #dc3545;
}

.stat-icon.furniture {
    background: #6f42c1;
}

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

.text-center {
    text-align: center;
}

@media (max-width: 768px) {
    .activos-container {
        padding: 10px;
    }
    
    .activos-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .activos-actions {
        justify-content: center;
    }
    
    .filtros-row {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    .activos-table {
        min-width: 800px;
    }
    
    .pagination-container {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>