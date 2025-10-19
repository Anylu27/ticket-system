<?php
require_once '../includes/conexion.php';
verificarSesion();

$page_title = 'Bitácora';

// Parámetros de paginación
$registros_por_pagina = isset($_GET['registros']) ? (int)$_GET['registros'] : 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener total de registros
$stmt = $pdo->query("SELECT COUNT(*) FROM bitacora");
$total_registros = $stmt->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener registros de bitácora
$stmt = $pdo->prepare("
    SELECT 
        id,
        usuario,
        fecha,
        modulo,
        accion
    FROM bitacora 
    ORDER BY fecha DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$registros_por_pagina, $offset]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<!-- Contenido de bitácora -->
<div class="table-container">
    <div class="table-header">
        <div class="table-controls">
            <div class="records-select">
                <label>REGISTROS</label>
                <select onchange="cambiarRegistrosPorPagina(this.value)">
                    <option value="10" <?php echo $registros_por_pagina == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?php echo $registros_por_pagina == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $registros_por_pagina == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $registros_por_pagina == 100 ? 'selected' : ''; ?>>100</option>
                </select>
            </div>
        </div>
        
        <div class="table-search">
            <input type="text" placeholder="Buscar..." style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
            <button style="padding: 8px 12px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; margin-left: 5px;">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>
    
    <!-- Tabla de bitácora -->
    <table>
        <thead>
            <tr>
                <th>Acción</th>
                <th>Usuario</th>
                <th>Fecha</th>
                <th>Módulo</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $registro): ?>
            <tr>
                <td>
                    <span class="action-dots" onclick="verDetalle(<?php echo $registro['id']; ?>)">⋯</span>
                </td>
                <td><?php echo htmlspecialchars($registro['usuario']); ?></td>
                <td><?php echo date('Y-m-d H:i:s', strtotime($registro['fecha'])); ?></td>
                <td><?php echo htmlspecialchars($registro['modulo']); ?></td>
                <td>
                    <div class="accion-detalle">
                        <?php 
                        $accion = htmlspecialchars($registro['accion']);
                        if ($accion === '-') {
                            echo '<span class="accion-simple">-</span>';
                        } else if (strlen($accion) > 100) {
                            echo '<span class="accion-corta">' . substr($accion, 0, 100) . '...</span>';
                            echo '<span class="accion-completa" style="display: none;">' . $accion . '</span>';
                            echo '<button class="btn-ver-mas" onclick="toggleAccion(this)">Ver más</button>';
                        } else {
                            echo $accion;
                        }
                        ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Paginación -->
    <div class="pagination-container">
        <div class="pagination-info">
            Mostrando desde el <?php echo $offset + 1; ?> al <?php echo min($offset + $registros_por_pagina, $total_registros); ?> del total de <?php echo number_format($total_registros); ?> registros
        </div>
        
        <div class="pagination">
            <?php if ($pagina_actual > 1): ?>
                <a href="?pagina=<?php echo $pagina_actual - 1; ?>&registros=<?php echo $registros_por_pagina; ?>">Anterior</a>
            <?php endif; ?>
            
            <?php
            $inicio = max(1, $pagina_actual - 2);
            $fin = min($total_paginas, $pagina_actual + 2);
            
            for ($i = $inicio; $i <= $fin; $i++):
            ?>
                <?php if ($i == $pagina_actual): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?pagina=<?php echo $i; ?>&registros=<?php echo $registros_por_pagina; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($total_paginas > $fin): ?>
                <span>...</span>
                <a href="?pagina=<?php echo $total_paginas; ?>&registros=<?php echo $registros_por_pagina; ?>"><?php echo $total_paginas; ?></a>
            <?php endif; ?>
            
            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina_actual + 1; ?>&registros=<?php echo $registros_por_pagina; ?>">Siguiente</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para ver detalle -->
<div id="modalDetalle" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Detalle de Acción</h3>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body" id="detalleContenido">
            <!-- Contenido cargado dinámicamente -->
        </div>
    </div>
</div>

<script>
function cambiarRegistrosPorPagina(cantidad) {
    window.location.href = '?registros=' + cantidad + '&pagina=1';
}

function verDetalle(id) {
    // Cargar detalle vía AJAX o mostrar modal
    alert('Ver detalle del registro ID: ' + id);
}

function toggleAccion(button) {
    const row = button.closest('td');
    const accionCorta = row.querySelector('.accion-corta');
    const accionCompleta = row.querySelector('.accion-completa');
    
    if (accionCorta.style.display === 'none') {
        accionCorta.style.display = 'inline';
        accionCompleta.style.display = 'none';
        button.textContent = 'Ver más';
    } else {
        accionCorta.style.display = 'none';
        accionCompleta.style.display = 'inline';
        button.textContent = 'Ver menos';
    }
}

function cerrarModal() {
    document.getElementById('modalDetalle').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const modal = document.getElementById('modalDetalle');
    if (event.target == modal) {
        cerrarModal();
    }
}
</script>

<style>
.accion-detalle {
    max-width: 400px;
    line-height: 1.4;
}

.accion-simple {
    text-align: center;
    color: #6c757d;
    font-style: italic;
}

.btn-ver-mas {
    background: none;
    border: none;
    color: #007bff;
    cursor: pointer;
    font-size: 12px;
    margin-left: 5px;
    text-decoration: underline;
}

.btn-ver-mas:hover {
    color: #0056b3;
}

.accion-completa {
    word-wrap: break-word;
    white-space: pre-wrap;
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    padding: 15px 20px;
    background: #5cb85c;
    color: white;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 16px;
}

.modal-body {
    padding: 20px;
}

.close {
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
}

.close:hover {
    opacity: 0.7;
}
</style>

<?php include '../includes/footer.php'; ?>