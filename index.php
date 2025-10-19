<?php
require_once 'includes/conexion.php';

$error = '';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: views/dashboard.php');
    exit();
}

// Procesar login
if ($_POST) {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, usuario, nombre, password FROM usuarios WHERE usuario = ? AND estado = 'Activo'");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nombre'] = $user['nombre'];
                $_SESSION['usuario_usuario'] = $user['usuario'];
                
                // Registrar en bitácora
                registrarBitacora($user['nombre'], 'LOGIN', 'Usuario inició sesión');
                
                header('Location: views/dashboard.php');
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch (PDOException $e) {
            $error = 'Error de conexión. Intente nuevamente.';
            error_log("Error de login: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Tickets</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #5cb85c 0%, #4cae4c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            margin: 20px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #5cb85c;
            font-size: 28px;
            margin: 0;
            font-weight: 600;
        }
        
        .logo p {
            color: #6c757d;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
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
        
        .form-group .input-icon {
            position: relative;
        }
        
        .form-group .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .form-group .input-icon input {
            padding-left: 45px;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #5cb85c;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-login:hover {
            background: #4cae4c;
        }
        
        .btn-login:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            font-size: 14px;
        }
        
        .hospital-info {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 12px;
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1><i class="fas fa-ticket-alt"></i> Tickets</h1>
            <p>Hospital Santa Fe</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="usuario" name="usuario" required autocomplete="username" 
                           value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
            </div>
            
            <button type="submit" class="btn-login" id="btnLogin">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>
        
        <div class="hospital-info">
            <p><strong>Sistema de Gestión de Tickets</strong></p>
            <p>Hospital Santa Fe - Panamá</p>
            <p>&copy; <?php echo date('Y'); ?> Todos los derechos reservados</p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('btnLogin');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';
        });
        
        // Focus en el primer campo
        document.getElementById('usuario').focus();
    </script>
</body>
</html>