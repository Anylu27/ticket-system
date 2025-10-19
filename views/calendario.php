<?php
require_once '../includes/conexion.php';
verificarSesion();

$page_title = 'Calendario';

// Obtener mes y año actual o del parámetro
$mes_actual = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$año_actual = isset($_GET['año']) ? (int)$_GET['año'] : date('Y');

// Validar mes y año
if ($mes_actual < 1 || $mes_actual > 12) $mes_actual = date('n');
if ($año_actual < 2020 || $año_actual > 2030) $año_actual = date('Y');

// Nombres de meses en español
$nombres_meses = [
    1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
    5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
    9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
];

// Calcular primer día del mes y cantidad de días
$primer_dia = mktime(0, 0, 0, $mes_actual, 1, $año_actual);
$dias_en_mes = date('t', $primer_dia);
$dia_semana_inicio = date('w', $primer_dia); // 0 = domingo, 1 = lunes, etc.

// Ajustar para que lunes sea 0
$dia_semana_inicio = ($dia_semana_inicio == 0) ? 6 : $dia_semana_inicio - 1;

// Obtener eventos del mes (preventivos y correctivos)
$stmt = $pdo->prepare("
    (SELECT 
        id, 
        titulo, 
        fecha_programada as fecha, 
        hora_programada as hora,
        'preventivo' as tipo,
        estado
    FROM preventivos 
    WHERE YEAR(fecha_programada) = ? AND MONTH(fecha_programada) = ?)
    UNION ALL
    (SELECT 
        id, 
        titulo, 
        fecha_creacion as fecha, 
        hora_creacion as hora,
        'correctivo' as tipo,
        estado
    FROM correctivos 
    WHERE YEAR(fecha_creacion) = ? AND MONTH(fecha_creacion) = ?)
    ORDER BY fecha ASC, hora ASC
");
$stmt->execute([$año_actual, $mes_actual, $año_actual, $mes_actual]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizar eventos por día
$eventos_por_dia = [];
foreach ($eventos as $evento) {
    $dia = (int)date('j', strtotime($evento['fecha']));
    if (!isset($eventos_por_dia[$dia])) {
        $eventos_por_dia[$dia] = [];
    }
    $eventos_por_dia[$dia][] = $evento;
}

include '../includes/header.php';
?>

<div class="calendar-container">
    <!-- Header del calendario -->
    <div class="calendar-header">
        <div class="calendar-title">
            <?php echo $nombres_meses[$mes_actual] . ' DE ' . $año_actual; ?>
        </div>
        
        <div class="calendar-nav">
            <button onclick="navegarMes(-1)" title="Mes anterior">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="btn-today" onclick="irHoy()">Hoy</button>
            <button onclick="navegarMes(1)" title="Mes siguiente">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        
        <!-- Filtros de vista -->
        <div class="calendar-filters">
            <div class="filter-buttons">
                <button class="btn">Mes</button>
                <button class="btn">Semana</button>
                <button class="btn">Día</button>
                <button class="btn">Lista por día</button>
                <button class="btn active">Lista por semana</button>
            </div>
        </div>
    </div>
    
    <!-- Grid del calendario -->
    <div class="calendar-grid">
        <!-- Headers de días de la semana -->
        <div class="calendar-day-header">lun</div>
        <div class="calendar-day-header">mar</div>
        <div class="calendar-day-header">mié</div>
        <div class="calendar-day-header">jue</div>
        <div class="calendar-day-header">vie</div>
        <div class="calendar-day-header">sáb</div>
        <div class="calendar-day-header">dom</div>
        
        <!-- Días vacíos al inicio -->
        <?php for ($i = 0; $i < $dia_semana_inicio; $i++): ?>
            <div class="calendar-day calendar-day-empty"></div>
        <?php endfor; ?>
        
        <!-- Días del mes -->
        <?php for ($dia = 1; $dia <= $dias_en_mes; $dia++): ?>
            <div class="calendar-day" data-day="<?php echo $dia; ?>">
                <div class="calendar-day-number">
                    <?php echo $dia; ?>
                </div>
                
                <!-- Eventos del día -->
                <div class="calendar-events">
                    <?php if (isset($eventos_por_dia[$dia])): ?>
                        <?php foreach ($eventos_por_dia[$dia] as $evento): ?>
                            <div class="calendar-event <?php echo $evento['tipo']; ?>" 
                                 title="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                 onclick="verEvento(<?php echo $evento['id']; ?>, '<?php echo $evento['tipo']; ?>')">
                                <span class="event-time"><?php echo substr($evento['hora'], 0, 5); ?></span>
                                <span class="event-title"><?php echo htmlspecialchars(substr($evento['titulo'], 0, 20)); ?><?php echo strlen($evento['titulo']) > 20 ? '...' : ''; ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Mostrar "+X más" si hay muchos eventos -->
                        <?php if (count($eventos_por_dia[$dia]) > 3): ?>
                            <div class="calendar-event-more" onclick="verTodosEventos(<?php echo $dia; ?>)">
                                +<?php echo count($eventos_por_dia[$dia]) - 3; ?> más
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Modal para ver evento -->
<div id="modalEvento" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="eventoTitulo">Detalle del Evento</h3>
            <span class="close" onclick="cerrarModalEvento()">&times;</span>
        </div>
        <div class="modal-body" id="eventoContenido">
            <!-- Contenido cargado dinámicamente -->
        </div>
    </div>
</div>

<script>
const mesActual = <?php echo $mes_actual; ?>;
const añoActual = <?php echo $año_actual; ?>;

function navegarMes(direccion) {
    let nuevoMes = mesActual + direccion;
    let nuevoAño = añoActual;
    
    if (nuevoMes > 12) {
        nuevoMes = 1;
        nuevoAño++;
    } else if (nuevoMes < 1) {
        nuevoMes = 12;
        nuevoAño--;
    }
    
    window.location.href = `?mes=${nuevoMes}&año=${nuevoAño}`;
}

function irHoy() {
    const hoy = new Date();
    const mes = hoy.getMonth() + 1;
    const año = hoy.getFullYear();
    window.location.href = `?mes=${mes}&año=${año}`;
}

function verEvento(id, tipo) {
    // Cargar detalles del evento vía AJAX
    fetch(`../api/obtener_evento.php?id=${id}&tipo=${tipo}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('eventoTitulo').textContent = data.titulo;
            document.getElementById('eventoContenido').innerHTML = data.html;
            document.getElementById('modalEvento').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar el evento');
        });
}

function verTodosEventos(dia) {
    // Mostrar todos los eventos del día
    window.location.href = `eventos_dia.php?dia=${dia}&mes=${mesActual}&año=${añoActual}`;
}

function cerrarModalEvento() {
    document.getElementById('modalEvento').style.display = 'none';
}

// Resaltar día actual
document.addEventListener('DOMContentLoaded', function() {
    const hoy = new Date();
    if (hoy.getMonth() + 1 === mesActual && hoy.getFullYear() === añoActual) {
        const diaHoy = hoy.getDate();
        const elementoDia = document.querySelector(`[data-day="${diaHoy}"]`);
        if (elementoDia) {
            elementoDia.classList.add('today');
        }
    }
});

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const modal = document.getElementById('modalEvento');
    if (event.target == modal) {
        cerrarModalEvento();
    }
}
</script>

<style>
.calendar-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.calendar-header {
    padding: 20px 30px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.calendar-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

.calendar-nav {
    display: flex;
    gap: 10px;
    align-items: center;
}

.calendar-nav button {
    padding: 8px 12px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
}

.calendar-nav button:hover {
    background: #f8f9fa;
}

.btn-today {
    background: #5cb85c !important;
    color: white !important;
    border-color: #5cb85c !important;
}

.calendar-filters {
    display: flex;
    gap: 10px;
}

.calendar-filters .filter-buttons {
    display: flex;
    gap: 5px;
}

.calendar-filters .btn {
    padding: 6px 12px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.3s ease;
}

.calendar-filters .btn.active {
    background: #5cb85c;
    color: white;
    border-color: #5cb85c;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.calendar-day-header {
    padding: 15px 10px;
    background: #f8f9fa;
    text-align: center;
    font-weight: 500;
    font-size: 13px;
    color: #6c757d;
    text-transform: uppercase;
    border-bottom: 1px solid #e9ecef;
    border-right: 1px solid #f0f0f0;
}

.calendar-day-header:last-child {
    border-right: none;
}

.calendar-day {
    min-height: 120px;
    padding: 8px;
    border-right: 1px solid #f0f0f0;
    border-bottom: 1px solid #f0f0f0;
    position: relative;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.calendar-day:hover {
    background-color: #f8f9fa;
}

.calendar-day:last-child {
    border-right: none;
}

.calendar-day.today {
    background-color: #e8f5e8;
}

.calendar-day.today .calendar-day-number {
    background: #5cb85c;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.calendar-day-empty {
    background-color: #fafafa;
}

.calendar-day-number {
    font-weight: 500;
    margin-bottom: 5px;
    color: #333;
    font-size: 14px;
}

.calendar-events {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.calendar-event {
    background: #5cb85c;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    margin-bottom: 1px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    gap: 4px;
}

.calendar-event:hover {
    opacity: 0.8;
    transform: translateY(-1px);
}

.calendar-event.preventivo {
    background: #5cb85c;
}

.calendar-event.correctivo {
    background: #17a2b8;
}

.event-time {
    font-weight: 600;
    opacity: 0.9;
}

.event-title {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
}

.calendar-event-more {
    background: #6c757d;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
    text-align: center;
    cursor: pointer;
    margin-top: 2px;
}

.calendar-event-more:hover {
    background: #5a6268;
}

@media (max-width: 768px) {
    .calendar-header {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .calendar-day {
        min-height: 80px;
        padding: 5px;
    }
    
    .calendar-event {
        font-size: 10px;
        padding: 1px 4px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>