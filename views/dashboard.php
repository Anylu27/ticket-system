<?php
require_once '../includes/conexion.php';
verificarSesion();

// Definir título de la página
$page_title = 'Dashboard';

// Obtener estadísticas
$stats = obtenerEstadisticasDashboard();

// Incluir header (que incluye sidebar)
include '../includes/header.php';
?>

<!-- Contenido del Dashboard -->

<!-- Grid de estadísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="color: #5cb85c;">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3>Resueltos</h3>
        <div class="stat-value" style="color: #5cb85c;">
            <?php echo $stats['resueltos']; ?>
        </div>
        <p style="font-size: 12px; color: #999;">Tickets completados</p>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="color: #5bc0de;">
            <i class="fas fa-user-check"></i>
        </div>
        <h3>Asignados</h3>
        <div class="stat-value" style="color: #5bc0de;">
            <?php echo $stats['asignados']; ?>
        </div>
        <p style="font-size: 12px; color: #999;">En trabajo activo</p>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="color: #f0ad4e;">
            <i class="fas fa-clock"></i>
        </div>
        <h3>En Espera</h3>
        <div class="stat-value" style="color: #f0ad4e;">
            <?php echo $stats['en_espera']; ?>
        </div>
        <p style="font-size: 12px; color: #999;">Esperando cliente</p>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="color: #d9534f;">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <h3>Pendientes</h3>
        <div class="stat-value" style="color: #d9534f;">
            <?php echo $stats['pendientes']; ?>
        </div>
        <p style="font-size: 12px; color: #999;">Sin asignar</p>
    </div>
</div>

<!-- Gráfico de tickets mensuales -->
<div class="card">
    <h3><i class="fas fa-chart-line"></i> Tickets por Mes</h3>
    <canvas id="chartMensual" height="80"></canvas>
</div>

<!-- Tabla de últimos tickets -->
<div class="card">
    <div class="clearfix">
        <h3 style="float: left;"><i class="fas fa-list"></i> Últimos Tickets</h3>
        <a href="correctivos.php" class="btn btn-primary" style="float: right;">
            <i class="fas fa-eye"></i> Ver Todos
        </a>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->query("SELECT c.*, cl.nombre as cliente_nombre 
                                 FROM correctivos c 
                                 LEFT JOIN clientes cl ON c.cliente_id = cl.id 
                                 ORDER BY c.fecha_creacion DESC 
                                 LIMIT 10");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                $badge_class = match($row['estado']) {
                    'Resuelto' => 'badge-success',
                    'Asignado' => 'badge-info',
                    'En espera del cliente' => 'badge-warning',
                    default => 'badge-secondary'
                };
            ?>
            <tr>
                <td>#<?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                <td><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                <td><span class="badge <?php echo $badge_class; ?>"><?php echo $row['estado']; ?></span></td>
                <td><?php echo date('d/m/Y', strtotime($row['fecha_creacion'])); ?></td>
                <td>
                    <button class="btn-icon" title="Ver detalles" onclick="verTicket(<?php echo $row['id']; ?>)">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
// Gráfico de tickets mensuales
const ctx = document.getElementById('chartMensual').getContext('2d');
const dataMensual = <?php echo json_encode($stats['datos_mensuales']); ?>;

const labels = dataMensual.map(d => {
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return meses[d.mes - 1];
});
const valores = dataMensual.map(d => d.total);

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Tickets',
            data: valores,
            borderColor: '#5cb85c',
            backgroundColor: 'rgba(92, 184, 92, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

function verTicket(id) {
    window.location.href = 'correctivos.php?id=' + id;
}
</script>

<?php
// Incluir footer
include '../includes/footer.php';
?>