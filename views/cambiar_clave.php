<?php
require_once '../includes/conexion.php';
verificarSesion();

$page_title = 'Cambiar clave';
$mensaje = '';
$tipo_mensaje = '';

// Procesar cambio de contraseña
if ($_POST) {
    $nueva_clave = trim($_POST['nueva_clave'] ?? '');
    $confirmar_clave = trim($_POST['confirmar_clave'] ?? '');
    
    if (empty($nueva_clave) || empty($confirmar_clave)) {
        $mensaje = 'Por favor complete todos los campos';
        $tipo_mensaje = 'error';
    } elseif (strlen($nueva_clave) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres';
        $tipo_mensaje = 'error';
    } elseif ($nueva_clave !== $confirmar_clave) {
        $mensaje = 'Las contraseñas no coinciden';
        $tipo_mensaje = 'error';
    } else {
        try {
            $password_hash = password_hash($nueva_clave, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([$password_hash, $_SESSION['usuario_id']]);
            
            // Registrar en bitácora
            registrarBitacora($_SESSION['usuario_nombre'], 'Seguridad', 'Cambio de contraseña realizado');
            
            $mensaje = 'Contraseña actualizada correctamente';
            $tipo_mensaje = 'success';
            
        } catch (PDOException $e) {
            $mensaje = 'Error al actualizar la contraseña. Intente nuevamente.';
            $tipo_mensaje = 'error';
            error_log("Error cambio contraseña: " . $e->getMessage());
        }
    }
}

include '../includes/header.php';
?>

<div class="page-content">
    <div class="form-container" style="max-width: 500px; margin: 0 auto;">
        <div class="cambiar-clave-header" style="text-align: center; margin-bottom: 30px;">
            <div class="clave-icon" style="width: 80px; height: 80px; background-color: #5cb85c; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                <i class="fas fa-key" style="font-size: 36px; color: white;"></i>
            </div>
            <h2 style="margin: 0; color: #333; font-weight: 500;">Cambiar clave</h2>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="formCambiarClave">
            <div class="form-group">
                <label for="nueva_clave">Nueva clave <span style="color: red;">*</span></label>
                <div style="position: relative;">
                    <input type="password" id="nueva_clave" name="nueva_clave" required 
                           placeholder="Ingrese su nueva contraseña" autocomplete="new-password">
                    <button type="button" class="btn-show-password" onclick="togglePassword('nueva_clave')" 
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6c757d; cursor: pointer;">
                        <i class="fas fa-eye" id="icon-nueva_clave"></i>
                    </button>
                </div>
                <small style="color: #6c757d; font-size: 12px;">La contraseña debe tener al menos 6 caracteres</small>
            </div>
            
            <div class="form-group">
                <label for="confirmar_clave">Confirmar nueva clave <span style="color: red;">*</span></label>
                <div style="position: relative;">
                    <input type="password" id="confirmar_clave" name="confirmar_clave" required 
                           placeholder="Confirme su nueva contraseña" autocomplete="new-password">
                    <button type="button" class="btn-show-password" onclick="togglePassword('confirmar_clave')" 
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6c757d; cursor: pointer;">
                        <i class="fas fa-eye" id="icon-confirmar_clave"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-actions" style="margin-top: 30px; text-align: center;">
                <button type="submit" class="btn btn-success" style="padding: 12px 30px; font-size: 16px;">
                    <i class="fas fa-save"></i> Cambiar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const icon = document.getElementById('icon-' + fieldId);
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Validación en tiempo real
document.getElementById('formCambiarClave').addEventListener('submit', function(e) {
    const nuevaClave = document.getElementById('nueva_clave').value;
    const confirmarClave = document.getElementById('confirmar_clave').value;
    
    if (nuevaClave.length < 6) {
        e.preventDefault();
        alert('La contraseña debe tener al menos 6 caracteres');
        return false;
    }
    
    if (nuevaClave !== confirmarClave) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }
    
    return true;
});

// Validación visual en tiempo real
document.getElementById('confirmar_clave').addEventListener('input', function() {
    const nuevaClave = document.getElementById('nueva_clave').value;
    const confirmarClave = this.value;
    
    if (confirmarClave && nuevaClave !== confirmarClave) {
        this.style.borderColor = '#dc3545';
    } else {
        this.style.borderColor = '#5cb85c';
    }
});

document.getElementById('nueva_clave').addEventListener('input', function() {
    const clave = this.value;
    
    if (clave.length > 0 && clave.length < 6) {
        this.style.borderColor = '#dc3545';
    } else if (clave.length >= 6) {
        this.style.borderColor = '#5cb85c';
    } else {
        this.style.borderColor = '#ddd';
    }
});
</script>

<style>
.form-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    padding: 40px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.form-group input {
    width: 100%;
    padding: 12px 45px 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.form-group input:focus {
    outline: none;
    border-color: #5cb85c;
    box-shadow: 0 0 0 3px rgba(92, 184, 92, 0.1);
}

.btn-show-password:hover {
    color: #333 !important;
}

.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 25px;
    border: 1px solid;
    font-size: 14px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn-success {
    background-color: #5cb85c;
    color: white;
}

.btn-success:hover {
    background-color: #4cae4c;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.clave-icon {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(92, 184, 92, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(92, 184, 92, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(92, 184, 92, 0);
    }
}

@media (max-width: 768px) {
    .form-container {
        margin: 20px;
        padding: 30px 20px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>