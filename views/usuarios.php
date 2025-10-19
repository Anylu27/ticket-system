<?php
require_once '../includes/conexion.php';
verificarSesion();

$page_title = 'Usuarios';
$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones (crear, editar, eliminar)
if ($_POST) {
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'crear':
            $usuario = trim($_POST['usuario'] ?? '');
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $cargo = trim($_POST['cargo'] ?? '');
            $nivel = $_POST['nivel'] ?? 'Cliente SYM';
            $estado = $_POST['estado'] ?? 'Activo';
            $password = trim($_POST['password'] ?? '');
            
            if (empty($usuario) || empty($nombre) || empty($email) || empty($password)) {
                $mensaje = 'Usuario, nombre, email y contraseña son requeridos';
                $tipo_mensaje = 'error';
            } else {
                try {
                    // Verificar si el usuario o email ya existe
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = ? OR email = ?");
                    $stmt->execute([$usuario, $email]);
                    if ($stmt->fetchColumn() > 0) {
                        $mensaje = 'El usuario o email ya está registrado';
                        $tipo_mensaje = 'error';
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, nombre, email, telefono, cargo, nivel, estado, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$usuario, $nombre, $email, $telefono, $cargo, $nivel, $estado, $password_hash]);
                        
                        registrarBitacora($_SESSION['usuario_nombre'], 'Usuarios', "Usuario creado: $nombre");
                        $mensaje = 'Usuario creado correctamente';
                        $tipo_mensaje = 'success';
                    }
                } catch (PDOException $e) {
                    $mensaje = 'Error al crear el usuario: ' . $e->getMessage();
                    $tipo_mensaje = 'error';
                }
            }
            break;
            
        case 'editar':
            $id = (int)($_POST['id'] ?? 0);
            $usuario = trim($_POST['usuario'] ?? '');
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $cargo = trim($_POST['cargo'] ?? '');
            $nivel = $_POST['nivel'] ?? 'Cliente SYM';
            $estado = $_POST['estado'] ?? 'Activo';
            
            if (!$id || empty($usuario) || empty($nombre) || empty($email)) {
                $mensaje = 'Datos incompletos para editar';
                $tipo_mensaje = 'error';
            } else {
                try {
                    // Verificar si el usuario o email ya existe en otro registro
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE (usuario = ? OR email = ?) AND id != ?");
                    $stmt->execute([$usuario, $email, $id]);
                    if ($stmt->fetchColumn() > 0) {
                        $mensaje = 'El usuario o email ya está registrado en otro usuario';
                        $tipo_mensaje = 'error';
                    } else {
                        $stmt = $pdo->prepare("UPDATE usuarios SET usuario = ?, nombre = ?, email = ?, telefono = ?, cargo = ?, nivel = ?, estado = ? WHERE id = ?");
                        $stmt->execute([$usuario, $nombre, $email, $telefono, $cargo, $nivel, $estado, $id]);
                        
                        registrarBitacora($_SESSION['usuario_nombre'], 'Usuarios', "Usuario editado ID: $id - $nombre");
                        $mensaje = 'Usuario actualizado correctamente';
                        $tipo_mensaje = 'success';
                    }
                } catch (PDOException $e) {
                    $mensaje = 'Error al actualizar el usuario: ' . $e->getMessage();
                    $tipo_mensaje = 'error';
                }
            }
            break;
            
        case 'eliminar':
            $id = (int)($_POST['id'] ?? 0);
            
            if (!$id) {
                $mensaje = 'ID de usuario no válido';
                $tipo_mensaje = 'error';
            } elseif ($id == $_SESSION['usuario_id']) {
                $mensaje = 'No puedes eliminar tu propio usuario';
                $tipo_mensaje = 'error';
            } else {
                try {
                    // Verificar si tiene tickets asignados
                    $stmt = $pdo->prepare("
                        SELECT 
                            (SELECT COUNT(*) FROM correctivos WHERE asignado_a = ? OR responsable = ?) +
                            (SELECT COUNT(*) FROM preventivos WHERE asignado_a = ? OR responsable = ?) as total_tickets
                    ");
                    $stmt->execute([$id, $id, $id, $id]);
                    $total_tickets = $stmt->fetchColumn();
                    
                    if ($total_tickets > 0) {
                        $mensaje = 'No se puede eliminar el usuario porque tiene tickets asignados';
                        $tipo_mensaje = 'error';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                        $stmt->execute([$id]);
                        
                        registrarBitacora($_SESSION['usuario_nombre'], 'Usuarios', "Usuario eliminado ID: $id");
                        $mensaje = 'Usuario eliminado correctamente';
                        $tipo_mensaje = 'success';
                    }
                } catch (PDOException $e) {
                    $mensaje = 'Error al eliminar el usuario: ' . $e->getMessage();
                    $tipo_mensaje = 'error';
                }
            }
            break;
            
        case 'cambiar_password':
            $id = (int)($_POST['id'] ?? 0);
            $password = trim($_POST['password'] ?? '');
            
            if (!$id || empty($password)) {
                $mensaje = 'ID y contraseña son requeridos';
                $tipo_mensaje = 'error';
            } elseif (strlen($password) < 6) {
                $mensaje = 'La contraseña debe tener al menos 6 caracteres';
                $tipo_mensaje = 'error';
            } else {
                try {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                    $stmt->execute([$password_hash, $id]);
                    
                    registrarBitacora($_SESSION['usuario_nombre'], 'Usuarios', "Contraseña cambiada para usuario ID: $id");
                    $mensaje = 'Contraseña actualizada correctamente';
                    $tipo_mensaje = 'success';
                } catch (PDOException $e) {
                    $mensaje = 'Error al cambiar la contraseña: ' . $e->getMessage();
                    $tipo_mensaje = 'error';
                }
            }
            break;
    }
}

// Obtener lista de usuarios con estadísticas
$stmt = $pdo->query("
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM correctivos WHERE asignado_a = u.id) as correctivos_asignados,
        (SELECT COUNT(*) FROM preventivos WHERE asignado_a = u.id) as preventivos_asignados,
        (SELECT COUNT(*) FROM correctivos WHERE responsable = u.id) as correctivos_responsable,
        (SELECT COUNT(*) FROM preventivos WHERE responsable = u.id) as preventivos_responsable
    FROM usuarios u 
    ORDER BY u.nivel DESC, u.nombre ASC
");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="page-content">
    <div class="usuarios-container">
        <!-- Header -->
        <div class="section-header">
            <div class="header-info">
                <h2>Gestión de Usuarios - Hospital Santa Fe</h2>
                <p>Total de usuarios registrados: <strong><?php echo count($usuarios); ?></strong></p>
            </div>
            <button class="btn btn-success" onclick="nuevoUsuario()">
                <i class="fas fa-plus"></i> Nuevo Usuario
            </button>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas rápidas -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon activos">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count(array_filter($usuarios, fn($u) => $u['estado'] === 'Activo')); ?></h3>
                    <p>Usuarios Activos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon directivos">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count(array_filter($usuarios, fn($u) => $u['nivel'] === 'Directores / Gerentes')); ?></h3>
                    <p>Directivos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon tecnicos">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count(array_filter($usuarios, fn($u) => $u['nivel'] === 'Ingenieros / Tecnicos')); ?></h3>
                    <p>Técnicos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon clientes">
                    <i class="fas fa-hospital"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count(array_filter($usuarios, fn($u) => in_array($u['nivel'], ['Cliente SYM', 'Clientes CEMI']))); ?></h3>
                    <p>Personal Médico</p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros-section">
            <div class="filtros-row">
                <div class="form-group">
                    <select id="filtroNivel" onchange="filtrarUsuarios()">
                        <option value="">Todos los niveles</option>
                        <option value="Directores / Gerentes">Directores / Gerentes</option>
                        <option value="Ingenieros / Tecnicos">Ingenieros / Técnicos</option>
                        <option value="Clientes CEMI">Clientes CEMI</option>
                        <option value="Cliente SYM">Cliente SYM</option>
                    </select>
                </div>
                <div class="form-group">
                    <select id="filtroEstado" onchange="filtrarUsuarios()">
                        <option value="">Todos los estados</option>
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" id="busquedaUsuario" placeholder="Buscar usuarios..." 
                           onkeyup="filtrarUsuarios()">
                </div>
                <div class="form-group">
                    <button class="btn btn-info" onclick="exportarUsuarios()">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla de usuarios -->
        <div class="table-container">
            <table class="usuarios-table" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Cargo</th>
                        <th>Nivel</th>
                        <th>Estado</th>
                        <th>Tickets</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                    <?php 
                    $total_tickets = $usuario['correctivos_asignados'] + $usuario['preventivos_asignados'] + 
                                    $usuario['correctivos_responsable'] + $usuario['preventivos_responsable']; 
                    ?>
                    <tr data-nivel="<?php echo $usuario['nivel']; ?>" data-estado="<?php echo $usuario['estado']; ?>">
                        <td><?php echo $usuario['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($usuario['usuario']); ?></strong></td>
                        <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                        <td>
                            <a href="mailto:<?php echo htmlspecialchars($usuario['email']); ?>">
                                <?php echo htmlspecialchars($usuario['email']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($usuario['telefono'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($usuario['cargo'] ?? '-'); ?></td>
                        <td>
                            <span class="nivel-badge nivel-<?php echo str_replace(['/', ' '], ['', '-'], strtolower($usuario['nivel'])); ?>">
                                <?php echo $usuario['nivel']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($usuario['estado']); ?>">
                                <?php echo $usuario['estado']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="tickets-count">
                                <span class="badge-count" title="Total de tickets asignados y como responsable">
                                    <?php echo $total_tickets; ?>
                                </span>
                                <?php if ($total_tickets > 0): ?>
                                <div class="tickets-detail">
                                    C: <?php echo $usuario['correctivos_asignados']; ?> | 
                                    P: <?php echo $usuario['preventivos_asignados']; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="acciones-grupo">
                                <button class="btn btn-sm btn-primary" onclick="editarUsuario(<?php echo $usuario['id']; ?>)" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="cambiarPassword(<?php echo $usuario['id']; ?>)" title="Cambiar contraseña">
                                    <i class="fas fa-key"></i>
                                </button>
                                <button class="btn btn-sm btn-info" onclick="verTickets(<?php echo $usuario['id']; ?>)" title="Ver tickets">
                                    <i class="fas fa-ticket-alt"></i>
                                </button>
                                <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                <button class="btn btn-sm btn-danger" onclick="eliminarUsuario(<?php echo $usuario['id']; ?>)" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para crear/editar usuario -->
<div id="modalUsuario" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3 id="modalUsuarioTitulo">Nuevo Usuario</h3>
            <span class="close" onclick="ModalManager.close('modalUsuario')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formUsuario" method="POST">
                <input type="hidden" id="usuarioAccion" name="accion" value="crear">
                <input type="hidden" id="usuarioId" name="id" value="">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="usuarioUsername">Usuario (Username) <span class="required">*</span></label>
                        <input type="text" id="usuarioUsername" name="usuario" required 
                               placeholder="username (sin espacios)" pattern="[a-zA-Z0-9_]+" 
                               title="Solo letras, números y guión bajo">
                    </div>
                    <div class="form-group">
                        <label for="usuarioNombre">Nombre Completo <span class="required">*</span></label>
                        <input type="text" id="usuarioNombre" name="nombre" required 
                               placeholder="Nombre completo del usuario">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="usuarioEmail">Email <span class="required">*</span></label>
                        <input type="email" id="usuarioEmail" name="email" required 
                               placeholder="email@hospitalsantafepanama.com">
                    </div>
                    <div class="form-group">
                        <label for="usuarioTelefono">Teléfono</label>
                        <input type="tel" id="usuarioTelefono" name="telefono" 
                               placeholder="6XXX-XXXX">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="usuarioCargo">Cargo</label>
                        <input type="text" id="usuarioCargo" name="cargo" 
                               placeholder="Cargo o posición del usuario">
                    </div>
                    <div class="form-group">
                        <label for="usuarioNivel">Nivel de Usuario <span class="required">*</span></label>
                        <select id="usuarioNivel" name="nivel" required>
                            <option value="Cliente SYM">Cliente SYM</option>
                            <option value="Clientes CEMI">Clientes CEMI</option>
                            <option value="Ingenieros / Tecnicos">Ingenieros / Técnicos</option>
                            <option value="Directores / Gerentes">Directores / Gerentes</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="usuarioEstado">Estado</label>
                        <select id="usuarioEstado" name="estado">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                    <div class="form-group" id="passwordGroup">
                        <label for="usuarioPassword">Contraseña <span class="required">*</span></label>
                        <input type="password" id="usuarioPassword" name="password" 
                               placeholder="Mínimo 6 caracteres" minlength="6">
                        <small class="form-text">Deja en blanco para mantener la contraseña actual (solo en edición)</small>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar Usuario
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="ModalManager.close('modalUsuario')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para cambiar contraseña -->
<div id="modalPassword" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3>Cambiar Contraseña</h3>
            <span class="close" onclick="ModalManager.close('modalPassword')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formPassword" method="POST">
                <input type="hidden" name="accion" value="cambiar_password">
                <input type="hidden" id="passwordUserId" name="id" value="">
                
                <div class="form-group">
                    <label>Usuario:</label>
                    <p id="passwordUserName" class="user-info"></p>
                </div>
                
                <div class="form-group">
                    <label for="nuevaPassword">Nueva Contraseña <span class="required">*</span></label>
                    <input type="password" id="nuevaPassword" name="password" required 
                           placeholder="Mínimo 6 caracteres" minlength="6">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-key"></i> Cambiar Contraseña
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="ModalManager.close('modalPassword')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Datos de usuarios para JavaScript
const usuariosData = <?php echo json_encode($usuarios); ?>;

function nuevoUsuario() {
    document.getElementById('modalUsuarioTitulo').textContent = 'Nuevo Usuario';
    document.getElementById('formUsuario').reset();
    document.getElementById('usuarioAccion').value = 'crear';
    document.getElementById('usuarioId').value = '';
    document.getElementById('usuarioPassword').required = true;
    document.querySelector('#passwordGroup small').style.display = 'none';
    
    ModalManager.open('modalUsuario');
}

function editarUsuario(id) {
    const usuario = usuariosData.find(u => u.id == id);
    
    if (!usuario) {
        Utils.showNotification('Usuario no encontrado', 'error');
        return;
    }
    
    document.getElementById('modalUsuarioTitulo').textContent = 'Editar Usuario';
    document.getElementById('usuarioAccion').value = 'editar';
    document.getElementById('usuarioId').value = usuario.id;
    document.getElementById('usuarioUsername').value = usuario.usuario;
    document.getElementById('usuarioNombre').value = usuario.nombre;
    document.getElementById('usuarioEmail').value = usuario.email;
    document.getElementById('usuarioTelefono').value = usuario.telefono || '';
    document.getElementById('usuarioCargo').value = usuario.cargo || '';
    document.getElementById('usuarioNivel').value = usuario.nivel;
    document.getElementById('usuarioEstado').value = usuario.estado;
    
    // En edición, la contraseña no es requerida
    document.getElementById('usuarioPassword').required = false;
    document.getElementById('usuarioPassword').value = '';
    document.querySelector('#passwordGroup small').style.display = 'block';
    
    ModalManager.open('modalUsuario');
}

function eliminarUsuario(id) {
    const usuario = usuariosData.find(u => u.id == id);
    
    if (!usuario) {
        Utils.showNotification('Usuario no encontrado', 'error');
        return;
    }
    
    const totalTickets = parseInt(usuario.correctivos_asignados) + parseInt(usuario.preventivos_asignados) + 
                        parseInt(usuario.correctivos_responsable) + parseInt(usuario.preventivos_responsable);
    
    if (totalTickets > 0) {
        Utils.showNotification(`No se puede eliminar el usuario porque tiene ${totalTickets} ticket(s) asignado(s)`, 'error');
        return;
    }
    
    if (confirm(`¿Está seguro que desea eliminar el usuario "${usuario.nombre}"?`)) {
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

function cambiarPassword(id) {
    const usuario = usuariosData.find(u => u.id == id);
    
    if (!usuario) {
        Utils.showNotification('Usuario no encontrado', 'error');
        return;
    }
    
    document.getElementById('passwordUserId').value = id;
    document.getElementById('passwordUserName').textContent = `${usuario.nombre} (${usuario.usuario})`;
    document.getElementById('nuevaPassword').value = '';
    
    ModalManager.open('modalPassword');
}

function verTickets(id) {
    const usuario = usuariosData.find(u => u.id == id);
    const totalTickets = parseInt(usuario.correctivos_asignados) + parseInt(usuario.preventivos_asignados) + 
                        parseInt(usuario.correctivos_responsable) + parseInt(usuario.preventivos_responsable);
    
    if (totalTickets === 0) {
        Utils.showNotification('Este usuario no tiene tickets asignados', 'info');
        return;
    }
    
    // Redireccionar a vista de tickets filtrada por usuario
    Utils.showNotification('Redirigiendo a tickets del usuario...', 'info');
    window.location.href = `correctivos.php?usuario_id=${id}`;
}

function filtrarUsuarios() {
    const nivel = document.getElementById('filtroNivel').value.toLowerCase();
    const estado = document.getElementById('filtroEstado').value.toLowerCase();
    const busqueda = document.getElementById('busquedaUsuario').value.toLowerCase();
    
    const filas = document.querySelectorAll('#tablaUsuarios tbody tr');
    
    filas.forEach(fila => {
        const dataNivel = fila.dataset.nivel.toLowerCase();
        const dataEstado = fila.dataset.estado.toLowerCase();
        const textoFila = fila.textContent.toLowerCase();
        
        const coincideNivel = !nivel || dataNivel === nivel;
        const coincideEstado = !estado || dataEstado === estado;
        const coincideBusqueda = !busqueda || textoFila.includes(busqueda);
        
        if (coincideNivel && coincideEstado && coincideBusqueda) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    });
}

function exportarUsuarios() {
    Utils.showNotification('Funcionalidad de exportación en desarrollo', 'info');
    // Aquí se implementaría la exportación a Excel/CSV
}

// Validaciones del formulario
document.getElementById('formUsuario').addEventListener('submit', function(e) {
    const usuario = document.getElementById('usuarioUsername').value.trim();
    const nombre = document.getElementById('usuarioNombre').value.trim();
    const email = document.getElementById('usuarioEmail').value.trim();
    const password = document.getElementById('usuarioPassword').value.trim();
    const isEdit = document.getElementById('usuarioAccion').value === 'editar';
    
    if (!usuario || !nombre || !email) {
        e.preventDefault();
        Utils.showNotification('Usuario, nombre y email son requeridos', 'error');
        return false;
    }
    
    // Validar formato del username
    if (!/^[a-zA-Z0-9_]+$/.test(usuario)) {
        e.preventDefault();
        Utils.showNotification('El usuario solo puede contener letras, números y guión bajo', 'error');
        return false;
    }
    
    if (!isEdit && (!password || password.length < 6)) {
        e.preventDefault();
        Utils.showNotification('La contraseña debe tener al menos 6 caracteres', 'error');
        return false;
    }
    
    if (isEdit && password && password.length < 6) {
        e.preventDefault();
        Utils.showNotification('Si especifica contraseña, debe tener al menos 6 caracteres', 'error');
        return false;
    }
});

document.getElementById('formPassword').addEventListener('submit', function(e) {
    const password = document.getElementById('nuevaPassword').value.trim();
    
    if (!password || password.length < 6) {
        e.preventDefault();
        Utils.showNotification('La contraseña debe tener al menos 6 caracteres', 'error');
        return false;
    }
});
</script>

<style>
.usuarios-container {
    max-width: 1500px;
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

.header-info p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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

.stat-icon.activos { background: #28a745; }
.stat-icon.directivos { background: #007bff; }
.stat-icon.tecnicos { background: #6f42c1; }
.stat-icon.clientes { background: #17a2b8; }

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
    align-items: end;
}

.usuarios-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    font-size: 13px;
}

.usuarios-table th,
.usuarios-table td {
    padding: 10px 8px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.usuarios-table th {
    background: #f8f9fa;
    font-weight: 500;
    color: #333;
    font-size: 12px;
    text-transform: uppercase;
}

.usuarios-table tbody tr:hover {
    background: #f8f9fa;
}

.nivel-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 500;
    text-transform: uppercase;
    white-space: nowrap;
}

.nivel-directores-gerentes {
    background: #fff3cd;
    color: #856404;
}

.nivel-ingenieros-tecnicos {
    background: #e1ecf4;
    color: #0c5460;
}

.nivel-clientes-cemi {
    background: #cce5ff;
    color: #0066cc;
}

.nivel-cliente-sym {
    background: #e8f5e8;
    color: #2e7d32;
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

.tickets-count {
    text-align: center;
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

.tickets-detail {
    font-size: 10px;
    color: #6c757d;
    margin-top: 2px;
}

.acciones-grupo {
    display: flex;
    gap: 3px;
    justify-content: center;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 4px;
}

.user-info {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    margin: 0;
    font-weight: 500;
}

.required {
    color: #dc3545;
}

.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border: 1px solid;
    display: flex;
    align-items: center;
    gap: 10px;
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
    
    .stats-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filtros-row {
        grid-template-columns: 1fr;
    }
    
    .usuarios-table {
        font-size: 12px;
    }
    
    .usuarios-table th,
    .usuarios-table td {
        padding: 6px 4px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .acciones-grupo {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<?php include '../includes/footer.php'; ?>