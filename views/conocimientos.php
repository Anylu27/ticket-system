<?php
require_once '../includes/conexion.php';
verificarSesion();

$page_title = 'Activos';

// Por ahora mostraremos una interfaz básica ya que no tenemos datos específicos
// En un sistema real, aquí habría una tabla de activos

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
                    <label>Tipo de Activo:</label>
                    <select class="form-control">
                        <option value="">Todos</option>
                        <option value="hardware">Hardware</option>
                        <option value="software">Software</option>
                        <option value="mobiliario">Mobiliario</option>
                        <option value="equipos_medicos">Equipos Médicos</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Estado:</label>
                    <select class="form-control">
                        <option value="">Todos</option>
                        <option value="activo">Activo</option>
                        <option value="mantenimiento">En Mantenimiento</option>
                        <option value="baja">Dado de Baja</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Ubicación:</label>
                    <select class="form-control">
                        <option value="">Todas</option>
                        <option value="sala_1">Sala 1</option>
                        <option value="sala_2">Sala 2</option>
                        <option value="emergencia">Emergencia</option>
                        <option value="administracion">Administración</option>
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
            <table class="activos-table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" onchange="seleccionarTodos(this)">
                        </th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Marca/Modelo</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th>Fecha Adquisición</th>
                        <th>Valor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Datos de ejemplo -->
                    <tr>
                        <td><input type="checkbox" value="1"></td>
                        <td>HSF-PC-001</td>
                        <td>Computadora de Escritorio</td>
                        <td>Hardware</td>
                        <td>Dell OptiPlex 7090</td>
                        <td>Administración</td>
                        <td><span class="status-badge status-activo">Activo</span></td>
                        <td>2024-01-15</td>
                        <td>$1,200.00</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editarActivo(1)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-info" onclick="verHistorial(1)">
                                <i class="fas fa-history"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarActivo(1)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" value="2"></td>
                        <td>HSF-MED-002</td>
                        <td>Monitor de Signos Vitales</td>
                        <td>Equipos Médicos</td>
                        <td>Philips IntelliVue MX40</td>
                        <td>UCI - Sala 4</td>
                        <td><span class="status-badge status-activo">Activo</span></td>
                        <td>2023-11-20</td>
                        <td>$8,500.00</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editarActivo(2)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-info" onclick="verHistorial(2)">
                                <i class="fas fa-history"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarActivo(2)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" value="3"></td>
                        <td>HSF-SW-003</td>
                        <td>Licencia Windows 11 Pro</td>
                        <td>Software</td>
                        <td>Microsoft Windows 11 Pro</td>
                        <td>IT - Sistemas</td>
                        <td><span class="status-badge status-activo">Activo</span></td>
                        <td>2024-02-01</td>
                        <td>$199.00</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editarActivo(3)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-info" onclick="verHistorial(3)">
                                <i class="fas fa-history"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarActivo(3)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" value="4"></td>
                        <td>HSF-IMP-004</td>
                        <td>Impresora Láser</td>
                        <td>Hardware</td>
                        <td>HP LaserJet Pro M404n</td>
                        <td>Enfermería - Piso 2</td>
                        <td><span class="status-badge status-mantenimiento">En Mantenimiento</span></td>
                        <td>2023-08-10</td>
                        <td>$350.00</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editarActivo(4)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-info" onclick="verHistorial(4)">
                                <i class="fas fa-history"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarActivo(4)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" value="5"></td>
                        <td>HSF-MUE-005</td>
                        <td>Escritorio Ejecutivo</td>
                        <td>Mobiliario</td>
                        <td>Steelcase Series 1</td>
                        <td>Dirección General</td>
                        <td><span class="status-badge status-activo">Activo</span></td>
                        <td>2023-05-15</td>
                        <td>$450.00</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editarActivo(5)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-info" onclick="verHistorial(5)">
                                <i class="fas fa-history"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarActivo(5)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Paginación -->
            <div class="pagination-container">
                <div class="pagination-info">
                    Mostrando 1-5 de 156 activos
                </div>
                <div class="pagination">
                    <a href="#" class="current">1</a>
                    <a href="#">2</a>
                    <a href="#">3</a>
                    <span>...</span>
                    <a href="#">32</a>
                    <a href="#">Siguiente</a>
                </div>
            </div>
        </div>

        <!-- Resumen de estadísticas -->
        <div class="estadisticas-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon hardware">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <div class="stat-info">
                        <h3>87</h3>
                        <p>Hardware</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon software">
                        <i class="fas fa-code"></i>
                    </div>
                    <div class="stat-info">
                        <h3>43</h3>
                        <p>Software</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon medical">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <div class="stat-info">
                        <h3>26</h3>
                        <p>Equipo Médico</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon furniture">
                        <i class="fas fa-chair"></i>
                    </div>
                    <div class="stat-info">
                        <h3>156</h3>
                        <p>Mobiliario</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para nuevo/editar activo -->
<div id="modalActivo" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="modalActivoTitulo">Nuevo Activo</h3>
            <span class="close" onclick="ModalManager.close('modalActivo')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formActivo">
                <div class="form-row">
                    <div class="form-group">
                        <label for="codigo_activo">Código <span style="color: red;">*</span></label>
                        <input type="text" id="codigo_activo" name="codigo_activo" required>
                    </div>
                    <div class="form-group">
                        <label for="nombre_activo">Nombre <span style="color: red;">*</span></label>
                        <input type="text" id="nombre_activo" name="nombre_activo" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_activo">Tipo <span style="color: red;">*</span></label>
                        <select id="tipo_activo" name="tipo_activo" required>
                            <option value="">Seleccionar tipo...</option>
                            <option value="hardware">Hardware</option>
                            <option value="software">Software</option>
                            <option value="mobiliario">Mobiliario</option>
                            <option value="equipos_medicos">Equipos Médicos</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="marca_modelo">Marca/Modelo</label>
                        <input type="text" id="marca_modelo" name="marca_modelo">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="ubicacion_activo">Ubicación</label>
                        <input type="text" id="ubicacion_activo" name="ubicacion_activo">
                    </div>
                    <div class="form-group">
                        <label for="estado_activo">Estado</label>
                        <select id="estado_activo" name="estado_activo">
                            <option value="activo">Activo</option>
                            <option value="mantenimiento">En Mantenimiento</option>
                            <option value="baja">Dado de Baja</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_adquisicion">Fecha de Adquisición</label>
                        <input type="date" id="fecha_adquisicion" name="fecha_adquisicion">
                    </div>
                    <div class="form-group">
                        <label for="valor_activo">Valor</label>
                        <input type="number" id="valor_activo" name="valor_activo" step="0.01">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="observaciones_activo">Observaciones</label>
                    <textarea id="observaciones_activo" name="observaciones_activo" rows="3"></textarea>
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
function nuevoActivo() {
    document.getElementById('modalActivoTitulo').textContent = 'Nuevo Activo';
    document.getElementById('formActivo').reset();
    ModalManager.open('modalActivo');
}

function editarActivo(id) {
    document.getElementById('modalActivoTitulo').textContent = 'Editar Activo';
    // Aquí cargarías los datos del activo
    ModalManager.open('modalActivo');
}

function eliminarActivo(id) {
    if (confirm('¿Está seguro que desea eliminar este activo?')) {
        Utils.showNotification('Activo eliminado correctamente', 'success');
        // Aquí eliminarías el activo
    }
}

function verHistorial(id) {
    Utils.showNotification('Funcionalidad en desarrollo', 'info');
}

function importarActivos() {
    Utils.showNotification('Funcionalidad de importación en desarrollo', 'info');
}

function exportarActivos() {
    Utils.showNotification('Exportando activos...', 'info');
    // Implementar exportación
}

function aplicarFiltros() {
    Utils.showNotification('Aplicando filtros...', 'info');
}

function limpiarFiltros() {
    Utils.showNotification('Filtros limpiados', 'info');
}

function seleccionarTodos(checkbox) {
    const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

// Envío del formulario
document.getElementById('formActivo').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const validation = FormManager.validate(this);
    if (!validation.isValid) {
        Utils.showNotification(validation.errors.join('<br>'), 'error');
        return;
    }
    
    Utils.showNotification('Activo guardado correctamente', 'success');
    ModalManager.close('modalActivo');
});
</script>

<style>
.activos-container {
    max-width: 1200px;
    margin: 0 auto;
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

.activos-table {
    width: 100%;
    border-collapse: collapse;
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
}

.status-activo {
    background: #e8f5e8;
    color: #2e7d32;
}

.status-mantenimiento {
    background: #fff3e0;
    color: #f57c00;
}

.status-baja {
    background: #ffebee;
    color: #c62828;
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

@media (max-width: 768px) {
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
}
</style>

<?php include '../includes/footer.php'; ?>