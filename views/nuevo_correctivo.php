<?php
require_once '../includes/conexion.php';
verificarSesion();

$page_title = 'Nuevo Correctivo';
$mensaje = '';
$tipo_mensaje = '';

// Verificar si es edición
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id > 0;

if ($is_edit) {
    $page_title = 'Editar Correctivo';
    
    // Obtener datos del correctivo
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   cl.nombre as cliente_nombre,
                   p.nombre as proyecto_nombre,
                   cat.nombre as categoria_nombre,
                   sub.nombre as subcategoria_nombre,
                   u.nombre as ubicacion_nombre,
                   a.nombre as area_nombre,
                   d.nombre as departamento_nombre,
                   pr.nombre as proveedor_nombre,
                   t.nombre as tipo_nombre,
                   st.nombre as subtipo_nombre,
                   m.nombre as marca_nombre,
                   mo.nombre as modelo_nombre,
                   ua.nombre as asignado_nombre,
                   ur.nombre as responsable_nombre
            FROM correctivos c
            LEFT JOIN clientes cl ON c.cliente_id = cl.id
            LEFT JOIN proyectos p ON c.proyecto_id = p.id
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
            LEFT JOIN subcategorias sub ON c.subcategoria_id = sub.id
            LEFT JOIN ubicaciones u ON c.ubicacion_id = u.id
            LEFT JOIN areas a ON c.area_id = a.id
            LEFT JOIN departamentos d ON c.departamento_id = d.id
            LEFT JOIN proveedores pr ON c.proveedor_id = pr.id
            LEFT JOIN tipos t ON c.tipo_id = t.id
            LEFT JOIN subtipos st ON c.subtipo_id = st.id
            LEFT JOIN marcas m ON c.marca_id = m.id
            LEFT JOIN modelos mo ON c.modelo_id = mo.id
            LEFT JOIN usuarios ua ON c.asignado_a = ua.id
            LEFT JOIN usuarios ur ON c.responsable = ur.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $correctivo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$correctivo) {
            header('Location: correctivos.php');
            exit();
        }
    } catch (PDOException $e) {
        die("Error al obtener correctivo: " . $e->getMessage());
    }
}

// Procesar formulario
if ($_POST) {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $solicitante = trim($_POST['solicitante'] ?? '');
    $cc = trim($_POST['cc'] ?? '');
    $cliente_id = (int)($_POST['cliente_id'] ?? 0) ?: NULL;
    $proyecto_id = (int)($_POST['proyecto_id'] ?? 0) ?: NULL;
    $categoria_id = (int)($_POST['categoria_id'] ?? 0) ?: NULL;
    $subcategoria_id = (int)($_POST['subcategoria_id'] ?? 0) ?: NULL;
    $ubicacion_id = (int)($_POST['ubicacion_id'] ?? 0) ?: NULL;
    $area_id = (int)($_POST['area_id'] ?? 0) ?: NULL;
    $departamento_id = (int)($_POST['departamento_id'] ?? 0) ?: NULL;
    $proveedor_id = (int)($_POST['proveedor_id'] ?? 0) ?: NULL;
    $tipo_id = (int)($_POST['tipo_id'] ?? 0) ?: NULL;
    $subtipo_id = (int)($_POST['subtipo_id'] ?? 0) ?: NULL;
    $marca_id = (int)($_POST['marca_id'] ?? 0) ?: NULL;
    $modelo_id = (int)($_POST['modelo_id'] ?? 0) ?: NULL;
    $activo_id = (int)($_POST['activo_id'] ?? 0) ?: NULL;
    $prioridad = $_POST['prioridad'] ?? 'Media';
    $estado = $_POST['estado'] ?? 'Nuevo';
    $asignado_a = (int)($_POST['asignado_a'] ?? 0) ?: NULL;
    $responsable = (int)($_POST['responsable'] ?? 0) ?: NULL;
    $contacto = trim($_POST['contacto'] ?? '');
    $fecha_resolucion = !empty($_POST['fecha_resolucion']) ? $_POST['fecha_resolucion'] : null;
    $atencion = $_POST['atencion'] ?? 'En Sitio';
    $resolucion = trim($_POST['resolucion'] ?? '');
    $horas_trabajadas = (float)($_POST['horas_trabajadas'] ?? 0);
    $fuera_servicio = isset($_POST['fuera_servicio']) ? 1 : 0;
    $reporte_servicio = trim($_POST['reporte_servicio'] ?? '');
    $componente = trim($_POST['componente'] ?? '');

    // Validaciones
    $errores = [];
    
    if (empty($titulo)) {
        $errores[] = 'El título es requerido';
    }
    
    if (empty($descripcion)) {
        $errores[] = 'La descripción es requerida';
    }
    
    if (empty($solicitante)) {
        $errores[] = 'El solicitante es requerido';
    }
    
    if (!$cliente_id) {
        $errores[] = 'Debe seleccionar un cliente';
    }
    
    if (!$proyecto_id) {
        $errores[] = 'Debe seleccionar un proyecto';
    }
    
    if (!$categoria_id) {
        $errores[] = 'Debe seleccionar una categoría';
    }
    
    if (!$departamento_id) {
        $errores[] = 'Debe seleccionar un departamento';
    }

    if (!empty($errores)) {
        $mensaje = implode('<br>', $errores);
        $tipo_mensaje = 'error';
    } else {
        try {
            // Iniciar transacción
            $pdo->beginTransaction();
            
            if ($is_edit) {
                // Actualizar correctivo existente
                $stmt = $pdo->prepare("
                    UPDATE correctivos SET 
                        titulo = ?, descripcion = ?, solicitante = ?, cc = ?, cliente_id = ?, 
                        proyecto_id = ?, categoria_id = ?, subcategoria_id = ?, ubicacion_id = ?, 
                        area_id = ?, departamento_id = ?, proveedor_id = ?, tipo_id = ?, subtipo_id = ?,
                        marca_id = ?, modelo_id = ?, activo_id = ?, prioridad = ?, estado = ?, 
                        asignado_a = ?, responsable = ?, contacto = ?, fecha_resolucion = ?, 
                        atencion = ?, resolucion = ?, horas_trabajadas = ?, fuera_servicio = ?, 
                        reporte_servicio = ?, componente = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $titulo, $descripcion, $solicitante, $cc, $cliente_id, $proyecto_id, 
                    $categoria_id, $subcategoria_id, $ubicacion_id, $area_id, $departamento_id, 
                    $proveedor_id, $tipo_id, $subtipo_id, $marca_id, $modelo_id, $activo_id, 
                    $prioridad, $estado, $asignado_a, $responsable, $contacto, $fecha_resolucion, 
                    $atencion, $resolucion, $horas_trabajadas, $fuera_servicio, 
                    $reporte_servicio, $componente, $id
                ]);
                
                registrarBitacora($_SESSION['usuario_nombre'], 'Correctivos', "Correctivo editado ID: $id - $titulo");
                $mensaje = 'Correctivo actualizado correctamente';
                
            } else {
                // Crear nuevo correctivo
                $stmt = $pdo->prepare("
                    INSERT INTO correctivos (
                        titulo, descripcion, solicitante, cc, cliente_id, proyecto_id, 
                        categoria_id, subcategoria_id, ubicacion_id, area_id, departamento_id, 
                        proveedor_id, tipo_id, subtipo_id, marca_id, modelo_id, activo_id,
                        prioridad, estado, asignado_a, responsable, contacto, fecha_resolucion, 
                        atencion, resolucion, horas_trabajadas, fuera_servicio, 
                        reporte_servicio, componente, fecha_creacion, hora_creacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), TIME(NOW()))
                ");
                $stmt->execute([
                    $titulo, $descripcion, $solicitante, $cc, $cliente_id, $proyecto_id, 
                    $categoria_id, $subcategoria_id, $ubicacion_id, $area_id, $departamento_id, 
                    $proveedor_id, $tipo_id, $subtipo_id, $marca_id, $modelo_id, $activo_id,
                    $prioridad, $estado, $asignado_a, $responsable, $contacto, $fecha_resolucion, 
                    $atencion, $resolucion, $horas_trabajadas, $fuera_servicio, 
                    $reporte_servicio, $componente
                ]);
                
                $nuevo_id = $pdo->lastInsertId();
                registrarBitacora($_SESSION['usuario_nombre'], 'Correctivos', "Correctivo creado ID: $nuevo_id - $titulo");
                $mensaje = 'Correctivo creado correctamente con ID: ' . $nuevo_id;
            }
            
            // Confirmar transacción
            $pdo->commit();
            $tipo_mensaje = 'success';
            
            // Redirigir después de 2 segundos
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'correctivos.php';
                }, 2000);
            </script>";
            
        } catch (PDOException $e) {
            // Revertir transacción
            $pdo->rollBack();
            $mensaje = 'Error al guardar el correctivo: ' . $e->getMessage();
            $tipo_mensaje = 'error';
            error_log("Error guardando correctivo: " . $e->getMessage());
        }
    }
}

// Obtener listas para dropdowns
try {
    // Clientes activos
    $clientes = $pdo->query("SELECT id, nombre FROM clientes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Proyectos activos
    $proyectos = $pdo->query("SELECT id, nombre, cliente_id FROM proyectos WHERE estado = 'Activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Categorías de correctivo
    $categorias = $pdo->query("SELECT id, nombre FROM categorias WHERE tipo = 'Correctivo' AND activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Subcategorías
    $subcategorias = $pdo->query("SELECT id, nombre, categoria_id FROM subcategorias WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Ubicaciones
    $ubicaciones = $pdo->query("SELECT id, nombre FROM ubicaciones WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Áreas
    $areas = $pdo->query("SELECT id, nombre, ubicacion_id FROM areas WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Departamentos
    $departamentos = $pdo->query("SELECT id, nombre FROM departamentos WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Proveedores activos
    $proveedores = $pdo->query("SELECT id, nombre, encargado, email, telefono FROM proveedores WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Tipos
    $tipos = $pdo->query("SELECT id, nombre FROM tipos WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Subtipos
    $subtipos = $pdo->query("SELECT id, nombre, tipo_id FROM subtipos WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Marcas
    $marcas = $pdo->query("SELECT id, nombre FROM marcas WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Modelos
    $modelos = $pdo->query("SELECT id, nombre, marca_id FROM modelos WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Usuarios activos
    $usuarios = $pdo->query("SELECT id, usuario, nombre, email, cargo FROM usuarios WHERE estado = 'Activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    // Activos
    $activos = $pdo->query("
        SELECT a.id, a.serial, a.nombre,
               m.id as marca_id, m.nombre as marca_nombre, 
               mo.id as modelo_id, mo.nombre as modelo_nombre,
               u.nombre as ubicacion_nombre, ar.nombre as area_nombre
        FROM activos a
        LEFT JOIN marcas m ON a.marca_id = m.id
        LEFT JOIN modelos mo ON a.modelo_id = mo.id
        LEFT JOIN ubicaciones u ON a.ubicacion_id = u.id
        LEFT JOIN areas ar ON a.area_id = ar.id
        WHERE a.estado_activo = 'ACTIVO'
        ORDER BY a.serial, a.nombre
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error al cargar datos: " . $e->getMessage());
}

// Datos combinados de usuarios y proveedores para autocompletado
$personasData = [];

// Usuarios del sistema
foreach ($usuarios as $u) {
    $personasData[] = [
        'id' => $u['id'],
        'nombre' => $u['nombre'],
        'email' => $u['email'],
        'telefono' => '',
        'cargo' => $u['cargo'] ?? '',
        'usuario' => $u['usuario'],
        'tipo' => 'Usuario'
    ];
}

// Proveedores
foreach ($proveedores as $p) {
    $nombre = $p['nombre'];
    if (!empty($p['encargado'])) {
        $nombre .= ' - ' . $p['encargado'];
    }
    $personasData[] = [
        'id' => $p['id'],
        'nombre' => $nombre,
        'email' => $p['email'] ?? '',
        'telefono' => $p['telefono'] ?? '',
        'cargo' => 'Proveedor',
        'usuario' => '',
        'tipo' => 'Proveedor'
    ];
}
?>

<?php include '../includes/header.php'; ?>

<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.5;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }

        /* Header */
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        .form-header h1 {
            font-size: 24px;
            font-weight: 400;
            color: #333;
        }

        .user-info {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        /* Tabs */
        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 1px solid #e9ecef;
        }

        .tab {
            padding: 12px 24px;
            border: none;
            background: none;
            color: #666;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab.active {
            color: #4a90e2;
            border-bottom-color: #4a90e2;
            background: rgba(74, 144, 226, 0.05);
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .form-group label {
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #555;
        }

        .form-group.span-2 {
            grid-column: span 2;
        }

        .form-group.span-3 {
            grid-column: span 3;
        }

        .form-group.span-4 {
            grid-column: span 3;
        }

        /* Form Controls */
        .form-control {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background: white;
            transition: border-color 0.3s ease;
            width: 100%;
            min-height: 38px;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .form-control[readonly] {
            background: #f8f9fa;
            color: #6c757d;
        }

        /* Custom Select with Search */
        .select-container {
            position: relative;
            width: 100%;
        }

        .select-input {
            width: 100%;
            padding: 10px 32px 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 38px;
            box-sizing: border-box;
        }

        .select-input .select-text {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .select-input:focus,
        .select-input.active {
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
            outline: none;
        }

        .select-arrow {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            pointer-events: none;
            transition: transform 0.3s ease;
            font-size: 12px;
        }

        .select-input.active .select-arrow {
            transform: translateY(-50%) rotate(180deg);
        }

        .select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: none;
            margin-top: 2px;
        }

        .select-dropdown.show {
            display: block;
        }

        .search-input {
            width: 100%;
            padding: 10px 12px;
            border: none;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            outline: none;
            box-sizing: border-box;
        }

        .option-item {
            padding: 10px 12px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #f8f9fa;
            font-size: 14px;
        }

        .option-item:hover,
        .option-item.highlighted {
            background: #f8f9fa;
        }

        .option-item:last-child {
            border-bottom: none;
        }

        .option-item.selected {
            background: #e8f5e8;
            color: #4a90e2;
            font-weight: 500;
        }

        .no-options {
            padding: 15px;
            text-align: center;
            color: #666;
            font-style: italic;
        }

        /* Textarea */
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
        }

        /* Toolbar for description */
        .toolbar {
            display: flex;
            gap: 5px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            background: #f8f9fa;
        }

        .toolbar button {
            width: 28px;
            height: 28px;
            border: none;
            background: none;
            border-radius: 3px;
            color: #666;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease;
        }

        .toolbar button:hover {
            background: #e9ecef;
        }

        .toolbar + textarea {
            border-radius: 0 0 4px 4px;
        }

        /* Checkbox group */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            color: #856404;
            min-height: 38px;
        }

        .checkbox-group input[type="checkbox"] {
            margin: 0;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            font-weight: 500;
            cursor: pointer;
        }

        /* Required field indicator */
        .required {
            color: #dc3545;
            font-weight: bold;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #4a90e2;
            color: white;
        }

        .btn-primary:hover {
            background: #357abd;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
            text-decoration: none;
            color: white;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .main-container {
                max-width: 1000px;
            }
        }

        @media (max-width: 1024px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-group.span-3,
            .form-group.span-4 {
                grid-column: span 2;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .form-group.span-2,
            .form-group.span-3,
            .form-group.span-4 {
                grid-column: span 1;
            }
            
            .form-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
    </style>

    <div class="page-content">
        <div class="main-container">
        <!-- Header -->
        <div class="form-header">
            <h1><?php echo $is_edit ? 'Editar correctivo' : 'Nuevo correctivo'; ?></h1>
            <div class="user-info">AF <?php echo strtoupper($_SESSION['usuario_nombre']); ?></div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active">Correctivo</button>
            <button class="tab">Adjuntos</button>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" id="correctivoForm">
            <div class="form-grid">
                <!-- Primera fila -->
                <div class="form-group">
                    <label for="solicitante">Solicitante <span class="required">*</span></label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('solicitante')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit ? htmlspecialchars($correctivo['solicitante']) : 'Sin Asignar'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-solicitante">
                            <input type="text" class="search-input" placeholder="Buscar solicitante..." onkeyup="filterOptions('solicitante', this.value)">
                            <div class="options-container" id="options-solicitante">
                                <?php foreach ($personasData as $persona): ?>
                                    <div class="option-item" onclick="selectOption('solicitante', '<?php echo htmlspecialchars($persona['nombre']); ?>')">
                                        <?php echo htmlspecialchars($persona['nombre']); ?>
                                        <?php if ($persona['cargo']): ?>
                                            <small style="color: #666; display: block;"><?php echo htmlspecialchars($persona['cargo']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="solicitante" id="solicitante_hidden" value="<?php echo $is_edit ? htmlspecialchars($correctivo['solicitante']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="cc">C.C.</label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('cc')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit && $correctivo['cc'] ? htmlspecialchars($correctivo['cc']) : 'Seleccionar'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-cc">
                            <input type="text" class="search-input" placeholder="Buscar..." onkeyup="filterOptions('cc', this.value)">
                            <div class="options-container" id="options-cc">
                                <?php foreach ($personasData as $persona): ?>
                                    <div class="option-item" onclick="selectOption('cc', '<?php echo htmlspecialchars($persona['nombre']); ?>')">
                                        <?php echo htmlspecialchars($persona['nombre']); ?>
                                        <?php if ($persona['cargo']): ?>
                                            <small style="color: #666; display: block;"><?php echo htmlspecialchars($persona['cargo']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="cc" id="cc_hidden" value="<?php echo $is_edit ? htmlspecialchars($correctivo['cc']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="fecha_creacion">Fecha Creación</label>
                    <input type="date" class="form-control" id="fecha_creacion" readonly
                           value="<?php echo $is_edit ? date('Y-m-d', strtotime($correctivo['fecha_creacion'])) : date('Y-m-d'); ?>">
                </div>

                <!-- Segunda fila -->
                <div class="form-group">
                    <label for="hora_creacion">Hora Creación</label>
                    <input type="time" class="form-control" id="hora_creacion" readonly
                           value="<?php echo $is_edit ? ($correctivo['hora_creacion'] ? date('H:i', strtotime($correctivo['hora_creacion'])) : date('H:i')) : date('H:i'); ?>">
                </div>

                <!-- Título -->
                <div class="form-group span-2">
                    <label for="titulo">Título <span class="required">*</span></label>
                    <input type="text" class="form-control" id="titulo" name="titulo" required
                           value="<?php echo $is_edit ? htmlspecialchars($correctivo['titulo']) : ''; ?>">
                </div>

                <!-- Descripción -->
                <div class="form-group span-3">
                    <label for="descripcion">Descripción <span class="required">*</span></label>
                    <div class="toolbar">
                        <button type="button" title="Negrita"><i class="fas fa-bold"></i></button>
                        <button type="button" title="Cursiva"><i class="fas fa-italic"></i></button>
                        <button type="button" title="Subrayado"><i class="fas fa-underline"></i></button>
                        <button type="button" title="Lista"><i class="fas fa-list"></i></button>
                        <button type="button" title="Enlace"><i class="fas fa-link"></i></button>
                    </div>
                    <textarea class="form-control" id="descripcion" name="descripcion" required><?php echo $is_edit ? htmlspecialchars($correctivo['descripcion']) : ''; ?></textarea>
                </div>

                <!-- Tercera fila - Clientes, Proyectos, Categoría -->
                <div class="form-group">
                    <label for="cliente_id">Clientes <span class="required">*</span></label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('clientes')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit && $correctivo['cliente_nombre'] ? htmlspecialchars($correctivo['cliente_nombre']) : 'Hospital Santa Fe'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-clientes">
                            <input type="text" class="search-input" placeholder="Buscar cliente..." onkeyup="filterOptions('clientes', this.value)">
                            <div class="options-container" id="options-clientes">
                                <?php foreach ($clientes as $cliente): ?>
                                    <div class="option-item <?php echo ($is_edit && $correctivo['cliente_id'] == $cliente['id']) ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $cliente['id']; ?>" 
                                         onclick="selectOption('clientes', '<?php echo htmlspecialchars($cliente['nombre']); ?>', <?php echo $cliente['id']; ?>)">
                                        <?php echo htmlspecialchars($cliente['nombre']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="cliente_id" id="cliente_id_hidden" value="<?php echo $is_edit ? $correctivo['cliente_id'] : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="proyecto_id">Proyectos <span class="required">*</span></label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('proyectos')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit && $correctivo['proyecto_nombre'] ? htmlspecialchars($correctivo['proyecto_nombre']) : 'Sin Asignar'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-proyectos">
                            <input type="text" class="search-input" placeholder="Buscar proyecto..." onkeyup="filterOptions('proyectos', this.value)">
                            <div class="options-container" id="options-proyectos">
                                <?php foreach ($proyectos as $proyecto): ?>
                                    <div class="option-item <?php echo ($is_edit && $correctivo['proyecto_id'] == $proyecto['id']) ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $proyecto['id']; ?>" 
                                         data-cliente="<?php echo $proyecto['cliente_id']; ?>"
                                         onclick="selectOption('proyectos', '<?php echo htmlspecialchars($proyecto['nombre']); ?>', <?php echo $proyecto['id']; ?>)">
                                        <?php echo htmlspecialchars($proyecto['nombre']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="proyecto_id" id="proyecto_id_hidden" value="<?php echo $is_edit ? $correctivo['proyecto_id'] : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="categoria_id">Categoría <span class="required">*</span></label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('categoria')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit && $correctivo['categoria_nombre'] ? htmlspecialchars($correctivo['categoria_nombre']) : 'Seleccionar'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-categoria">
                            <input type="text" class="search-input" placeholder="Buscar categoría..." onkeyup="filterOptions('categoria', this.value)">
                            <div class="options-container" id="options-categoria">
                                <?php foreach ($categorias as $categoria): ?>
                                    <div class="option-item <?php echo ($is_edit && $correctivo['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $categoria['id']; ?>" 
                                         onclick="selectOption('categoria', '<?php echo htmlspecialchars($categoria['nombre']); ?>', <?php echo $categoria['id']; ?>)">
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="categoria_id" id="categoria_id_hidden" value="<?php echo $is_edit ? $correctivo['categoria_id'] : ''; ?>">
                </div>

                <!-- Cuarta fila - Subcategoría, Ubicación, Área -->
                <div class="form-group">
                    <label for="subcategoria_id">Subcategoría</label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('subcategoria')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit && $correctivo['subcategoria_nombre'] ? htmlspecialchars($correctivo['subcategoria_nombre']) : 'Seleccionar'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-subcategoria">
                            <input type="text" class="search-input" placeholder="Buscar subcategoría..." onkeyup="filterOptions('subcategoria', this.value)">
                            <div class="options-container" id="options-subcategoria">
                                <?php foreach ($subcategorias as $subcategoria): ?>
                                    <div class="option-item <?php echo ($is_edit && $correctivo['subcategoria_id'] == $subcategoria['id']) ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $subcategoria['id']; ?>" 
                                         data-categoria="<?php echo $subcategoria['categoria_id']; ?>"
                                         onclick="selectOption('subcategoria', '<?php echo htmlspecialchars($subcategoria['nombre']); ?>', <?php echo $subcategoria['id']; ?>)">
                                        <?php echo htmlspecialchars($subcategoria['nombre']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="subcategoria_id" id="subcategoria_id_hidden" value="<?php echo $is_edit ? ($correctivo['subcategoria_id'] ?: '') : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="ubicacion_id">Ubicación</label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('ubicacion')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit && $correctivo['ubicacion_nombre'] ? htmlspecialchars($correctivo['ubicacion_nombre']) : 'Hospital Santa Fe'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-ubicacion">
                            <input type="text" class="search-input" placeholder="Buscar ubicación..." onkeyup="filterOptions('ubicacion', this.value)">
                            <div class="options-container" id="options-ubicacion">
                                <?php foreach ($ubicaciones as $ubicacion): ?>
                                    <div class="option-item <?php echo ($is_edit && $correctivo['ubicacion_id'] == $ubicacion['id']) ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $ubicacion['id']; ?>" 
                                         onclick="selectOption('ubicacion', '<?php echo htmlspecialchars($ubicacion['nombre']); ?>', <?php echo $ubicacion['id']; ?>)">
                                        <?php echo htmlspecialchars($ubicacion['nombre']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="ubicacion_id" id="ubicacion_id_hidden" value="<?php echo $is_edit ? ($correctivo['ubicacion_id'] ?: '') : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="area_id">Área</label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('area')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit && $correctivo['area_nombre'] ? htmlspecialchars($correctivo['area_nombre']) : 'Sin Asignar'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-area">
                            <input type="text" class="search-input" placeholder="Buscar área..." onkeyup="filterOptions('area', this.value)">
                            <div class="options-container" id="options-area">
                                <?php foreach ($areas as $area): ?>
                                    <div class="option-item <?php echo ($is_edit && $correctivo['area_id'] == $area['id']) ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $area['id']; ?>" 
                                         data-ubicacion="<?php echo $area['ubicacion_id']; ?>"
                                         onclick="selectOption('area', '<?php echo htmlspecialchars($area['nombre']); ?>', <?php echo $area['id']; ?>)">
                                        <?php echo htmlspecialchars($area['nombre']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="area_id" id="area_id_hidden" value="<?php echo $is_edit ? ($correctivo['area_id'] ?: '') : ''; ?>">
                </div>

                <!-- Quinta fila - Activo, Marca, Modelo -->
                <div class="form-group">
                    <label for="activo_id">Activo</label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('activo')" tabindex="0">
                            <span class="select-text">
                                <?php 
                                if ($is_edit && $correctivo['activo_id']) {
                                    $activo_actual = null;
                                    foreach ($activos as $act) {
                                        if ($act['id'] == $correctivo['activo_id']) {
                                            $activo_actual = $act;
                                            break;
                                        }
                                    }
                                    if ($activo_actual) {
                                        echo htmlspecialchars($activo_actual['serial'] . ' - ' . $activo_actual['nombre']);
                                    } else {
                                        echo 'Sin Asignar';
                                    }
                                } else {
                                    echo 'Sin Asignar';
                                }
                                ?>
                            </span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-activo">
                            <input type="text" class="search-input" placeholder="Buscar activo..." onkeyup="filterOptions('activo', this.value)">
                            <div class="options-container" id="options-activo">
                                <?php foreach ($activos as $activo): ?>
                                    <div class="option-item <?php echo ($is_edit && $correctivo['activo_id'] == $activo['id']) ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $activo['id']; ?>" 
                                         data-marca-id="<?php echo $activo['marca_id'] ?: ''; ?>"
                                         data-marca-nombre="<?php echo htmlspecialchars($activo['marca_nombre'] ?: ''); ?>"
                                         data-modelo-id="<?php echo $activo['modelo_id'] ?: ''; ?>"
                                         data-modelo-nombre="<?php echo htmlspecialchars($activo['modelo_nombre'] ?: ''); ?>"
                                         onclick="selectAsset(<?php echo $activo['id']; ?>, '<?php echo htmlspecialchars(addslashes($activo['serial'] . ' - ' . $activo['nombre'])); ?>', <?php echo $activo['marca_id'] ?: 'null'; ?>, '<?php echo htmlspecialchars(addslashes($activo['marca_nombre'] ?: '')); ?>', <?php echo $activo['modelo_id'] ?: 'null'; ?>, '<?php echo htmlspecialchars(addslashes($activo['modelo_nombre'] ?: '')); ?>')">
                                        <strong><?php echo htmlspecialchars($activo['serial']); ?></strong> - <?php echo htmlspecialchars($activo['nombre']); ?>
                                        <?php if ($activo['marca_nombre'] || $activo['modelo_nombre']): ?>
                                            <small style="color: #666; display: block;">
                                                <?php echo htmlspecialchars(trim($activo['marca_nombre'] . ' ' . $activo['modelo_nombre'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="activo_id" id="activo_id_hidden" value="<?php echo $is_edit && $correctivo['activo_id'] ? $correctivo['activo_id'] : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="marca_id">Marca</label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('marca')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit && $correctivo['marca_nombre'] ? htmlspecialchars($correctivo['marca_nombre']) : 'Seleccionar'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-marca">
                            <input type="text" class="search-input" placeholder="Buscar marca..." onkeyup="filterOptions('marca', this.value)">
                            <div class="options-container" id="options-marca">
                                <?php foreach ($marcas as $marca): ?>
                                    <div class="option-item <?php echo ($is_edit && $correctivo['marca_id'] == $marca['id']) ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $marca['id']; ?>" 
                                         onclick="selectOption('marca', '<?php echo htmlspecialchars($marca['nombre']); ?>', <?php echo $marca['id']; ?>)">
                                        <?php echo htmlspecialchars($marca['nombre']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="marca_id" id="marca_id_hidden" value="<?php echo $is_edit ? ($correctivo['marca_id'] ?: '') : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="modelo_id">Modelo</label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('modelo')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit && $correctivo['modelo_nombre'] ? htmlspecialchars($correctivo['modelo_nombre']) : 'Seleccionar'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-modelo">
                            <input type="text" class="search-input" placeholder="Buscar modelo..." onkeyup="filterOptions('modelo', this.value)">
                            <div class="options-container" id="options-modelo">
                                <?php foreach ($modelos as $modelo): ?>
                                    <div class="option-item <?php echo ($is_edit && $correctivo['modelo_id'] == $modelo['id']) ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $modelo['id']; ?>" 
                                         data-marca="<?php echo $modelo['marca_id']; ?>"
                                         onclick="selectOption('modelo', '<?php echo htmlspecialchars($modelo['nombre']); ?>', <?php echo $modelo['id']; ?>)">
                                        <?php echo htmlspecialchars($modelo['nombre']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="modelo_id" id="modelo_id_hidden" value="<?php echo $is_edit ? ($correctivo['modelo_id'] ?: '') : ''; ?>">
                </div>

                <!-- Sexta fila - Prioridad, Estado, Departamentos -->
                <div class="form-group">
                    <label for="prioridad">Prioridad <span class="required">*</span></label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('prioridad')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit ? htmlspecialchars($correctivo['prioridad']) : 'Media'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-prioridad">
                            <div class="options-container" id="options-prioridad">
                                <div class="option-item <?php echo ($is_edit && $correctivo['prioridad'] == 'Baja') ? 'selected' : ''; ?>" onclick="selectOption('prioridad', 'Baja')">Baja</div>
                                <div class="option-item <?php echo ($is_edit && $correctivo['prioridad'] == 'Media') || !$is_edit ? 'selected' : ''; ?>" onclick="selectOption('prioridad', 'Media')">Media</div>
                                <div class="option-item <?php echo ($is_edit && $correctivo['prioridad'] == 'Alta') ? 'selected' : ''; ?>" onclick="selectOption('prioridad', 'Alta')">Alta</div>
                                <div class="option-item <?php echo ($is_edit && $correctivo['prioridad'] == 'Crítica') ? 'selected' : ''; ?>" onclick="selectOption('prioridad', 'Crítica')">Crítica</div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="prioridad" id="prioridad_hidden" value="<?php echo $is_edit ? htmlspecialchars($correctivo['prioridad']) : 'Media'; ?>">
                </div>

                <div class="form-group">
                    <label for="estado">Estado <span class="required">*</span></label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('estado')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit ? htmlspecialchars($correctivo['estado']) : 'Nuevo'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-estado">
                            <div class="options-container" id="options-estado">
                                <div class="option-item <?php echo ($is_edit && $correctivo['estado'] == 'Nuevo') || !$is_edit ? 'selected' : ''; ?>" onclick="selectOption('estado', 'Nuevo')">Nuevo</div>
                                <div class="option-item <?php echo ($is_edit && $correctivo['estado'] == 'En Progreso') ? 'selected' : ''; ?>" onclick="selectOption('estado', 'En Progreso')">En Progreso</div>
                                <div class="option-item <?php echo ($is_edit && $correctivo['estado'] == 'Esperando Respuesta') ? 'selected' : ''; ?>" onclick="selectOption('estado', 'Esperando Respuesta')">Esperando Respuesta</div>
                                <div class="option-item <?php echo ($is_edit && $correctivo['estado'] == 'Resuelto') ? 'selected' : ''; ?>" onclick="selectOption('estado', 'Resuelto')">Resuelto</div>
                                <div class="option-item <?php echo ($is_edit && $correctivo['estado'] == 'Cerrado') ? 'selected' : ''; ?>" onclick="selectOption('estado', 'Cerrado')">Cerrado</div>
                                <div class="option-item <?php echo ($is_edit && $correctivo['estado'] == 'Cancelado') ? 'selected' : ''; ?>" onclick="selectOption('estado', 'Cancelado')">Cancelado</div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="estado" id="estado_hidden" value="<?php echo $is_edit ? htmlspecialchars($correctivo['estado']) : 'Nuevo'; ?>">
                </div>

                <div class="form-group">
                    <label for="departamento_id">Departamentos / Grupos <span class="required">*</span></label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('departamento')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit && $correctivo['departamento_nombre'] ? htmlspecialchars($correctivo['departamento_nombre']) : 'Seleccionar'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-departamento">
                            <input type="text" class="search-input" placeholder="Buscar departamento..." onkeyup="filterOptions('departamento', this.value)">
                            <div class="options-container" id="options-departamento">
                                <?php foreach ($departamentos as $departamento): ?>
                                    <div class="option-item <?php echo ($is_edit && $correctivo['departamento_id'] == $departamento['id']) ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $departamento['id']; ?>" 
                                         onclick="selectOption('departamento', '<?php echo htmlspecialchars($departamento['nombre']); ?>', <?php echo $departamento['id']; ?>)">
                                        <?php echo htmlspecialchars($departamento['nombre']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="departamento_id" id="departamento_id_hidden" value="<?php echo $is_edit ? $correctivo['departamento_id'] : ''; ?>">
                </div>

                <!-- Séptima fila - Asignado a, Responsable, Contacto -->
                <div class="form-group">
                    <label for="asignado_a">Asignado a</label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('asignado')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit && $correctivo['asignado_nombre'] ? htmlspecialchars($correctivo['asignado_nombre']) : 'Seleccionar'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-asignado">
                            <input type="text" class="search-input" placeholder="Buscar usuario..." onkeyup="filterOptions('asignado', this.value)">
                            <div class="options-container" id="options-asignado">
                                <?php foreach ($usuarios as $usuario): ?>
                                    <div class="option-item <?php echo ($is_edit && $correctivo['asignado_a'] == $usuario['id']) ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $usuario['id']; ?>" 
                                         onclick="selectOption('asignado', '<?php echo htmlspecialchars($usuario['nombre']); ?>', <?php echo $usuario['id']; ?>)">
                                        <?php echo htmlspecialchars($usuario['nombre']); ?>
                                        <small style="color: #666; display: block;"><?php echo htmlspecialchars($usuario['cargo']); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="asignado_a" id="asignado_id_hidden" value="<?php echo $is_edit ? ($correctivo['asignado_a'] ?: '') : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="responsable">Responsable</label>
                    <div class="select-container">
                        <div class="select-input" onclick="toggleDropdown('responsable')" tabindex="0">
                            <span class="select-text"><?php echo $is_edit && $correctivo['responsable_nombre'] ? htmlspecialchars($correctivo['responsable_nombre']) : 'Seleccionar'; ?></span>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                        <div class="select-dropdown" id="dropdown-responsable">
                            <input type="text" class="search-input" placeholder="Buscar usuario..." onkeyup="filterOptions('responsable', this.value)">
                            <div class="options-container" id="options-responsable">
                                <?php foreach ($usuarios as $usuario): ?>
                                    <div class="option-item <?php echo ($is_edit && $correctivo['responsable'] == $usuario['id']) ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $usuario['id']; ?>" 
                                         onclick="selectOption('responsable', '<?php echo htmlspecialchars($usuario['nombre']); ?>', <?php echo $usuario['id']; ?>)">
                                        <?php echo htmlspecialchars($usuario['nombre']); ?>
                                        <small style="color: #666; display: block;"><?php echo htmlspecialchars($usuario['cargo']); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="responsable" id="responsable_id_hidden" value="<?php echo $is_edit ? ($correctivo['responsable'] ?: '') : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="contacto">Contacto</label>
                    <input type="text" class="form-control" id="contacto" name="contacto"
                           value="<?php echo $is_edit ? htmlspecialchars($correctivo['contacto']) : ''; ?>">
                </div>

                <!-- Octava fila - Componente de, Fuera de Servicio -->
                <div class="form-group span-2">
                    <label for="componente">Componente de</label>
                    <input type="text" class="form-control" id="componente" name="componente"
                           value="<?php echo $is_edit ? htmlspecialchars($correctivo['componente']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label style="opacity: 0; user-select: none; pointer-events: none;">Espacio</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="fuera_servicio" name="fuera_servicio" value="1"
                               <?php echo ($is_edit && $correctivo['fuera_servicio']) ? 'checked' : ''; ?>>
                        <label for="fuera_servicio">Fuera de Servicio</label>
                    </div>
                </div>

                <!-- Campos adicionales ocultos -->
                <div style="display: none;">
                    <input type="hidden" name="tipo_id" value="">
                    <input type="hidden" name="subtipo_id" value="">
                    <input type="hidden" name="proveedor_id" value="">
                    <input type="hidden" name="atencion" value="En Sitio">
                    <input type="hidden" name="fecha_resolucion" value="">
                    <input type="hidden" name="resolucion" value="">
                    <input type="hidden" name="horas_trabajadas" value="0">
                    <input type="hidden" name="reporte_servicio" value="">
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <?php echo $is_edit ? 'Actualizar Correctivo' : 'Guardar'; ?>
                </button>
                <a href="correctivos.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancelar
                </a>
            </div>
        </form>
    </div>
    </div>

    <script>
        // Variables globales
        let activeDropdown = null;

        // Datos del servidor para JavaScript
        const proyectos = <?php echo json_encode($proyectos); ?>;
        const subcategorias = <?php echo json_encode($subcategorias); ?>;
        const areas = <?php echo json_encode($areas); ?>;
        const modelos = <?php echo json_encode($modelos); ?>;
        const activos = <?php echo json_encode($activos); ?>;

        // Función para toggle dropdown
        function toggleDropdown(selectId) {
            const dropdown = document.getElementById(`dropdown-${selectId}`);
            const selectInput = dropdown.previousElementSibling;
            
            // Cerrar otros dropdowns
            if (activeDropdown && activeDropdown !== dropdown) {
                activeDropdown.classList.remove('show');
                activeDropdown.previousElementSibling.classList.remove('active');
            }
            
            // Toggle el dropdown actual
            const isOpen = dropdown.classList.contains('show');
            
            if (isOpen) {
                dropdown.classList.remove('show');
                selectInput.classList.remove('active');
                activeDropdown = null;
            } else {
                dropdown.classList.add('show');
                selectInput.classList.add('active');
                activeDropdown = dropdown;
                
                // Focus en el input de búsqueda si existe
                const searchInput = dropdown.querySelector('.search-input');
                if (searchInput) {
                    setTimeout(() => searchInput.focus(), 100);
                }
            }
        }

        // Función especial para seleccionar activo y rellenar marca/modelo automáticamente
        function selectAsset(activoId, activoText, marcaId, marcaNombre, modeloId, modeloNombre) {
            console.log('=== selectAsset llamada ===');
            console.log('Activo:', activoId, activoText);
            console.log('Marca:', marcaId, marcaNombre);
            console.log('Modelo:', modeloId, modeloNombre);
            
            // Seleccionar el activo
            const activoSelectInput = document.querySelector('#dropdown-activo').previousElementSibling;
            const activoSelectText = activoSelectInput.querySelector('.select-text');
            const activoHiddenInput = document.getElementById('activo_id_hidden');
            
            activoSelectText.textContent = activoText;
            activoHiddenInput.value = activoId;
            
            // Rellenar automáticamente la marca si existe
            if (marcaId !== null && marcaId !== 'null' && marcaId !== '' && marcaNombre) {
                const marcaSelectInput = document.querySelector('#dropdown-marca').previousElementSibling;
                const marcaSelectText = marcaSelectInput.querySelector('.select-text');
                const marcaHiddenInput = document.getElementById('marca_id_hidden');
                
                marcaSelectText.textContent = marcaNombre;
                marcaHiddenInput.value = marcaId;
                
                console.log('Marca asignada:', marcaId, marcaNombre);
                
                // Actualizar clase selected para marca
                const marcaOptions = document.querySelectorAll('#options-marca .option-item');
                marcaOptions.forEach(option => {
                    option.classList.remove('selected');
                    if (option.getAttribute('data-id') == marcaId) {
                        option.classList.add('selected');
                    }
                });
                
                // Filtrar modelos por marca PERO sin limpiar el modelo del activo
                const optionsContainer = document.getElementById('options-modelo');
                const options = optionsContainer.querySelectorAll('.option-item');
                
                options.forEach(option => {
                    const modeloMarcaId = option.getAttribute('data-marca');
                    if (!marcaId || modeloMarcaId == marcaId) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                });
                
            } else {
                // Limpiar marca si no hay
                const marcaHiddenInput = document.getElementById('marca_id_hidden');
                if (marcaHiddenInput) marcaHiddenInput.value = '';
                console.log('Marca limpiada');
            }
            
            // Rellenar automáticamente el modelo si existe - DESPUÉS de filtrar
            if (modeloId !== null && modeloId !== 'null' && modeloId !== '' && modeloNombre && modeloNombre !== '') {
                const modeloSelectInput = document.querySelector('#dropdown-modelo').previousElementSibling;
                const modeloSelectText = modeloSelectInput.querySelector('.select-text');
                const modeloHiddenInput = document.getElementById('modelo_id_hidden');
                
                modeloSelectText.textContent = modeloNombre;
                modeloHiddenInput.value = modeloId;
                
                console.log('Modelo asignado:', modeloId, modeloNombre);
                
                // Actualizar clase selected para modelo
                const modeloOptions = document.querySelectorAll('#options-modelo .option-item');
                modeloOptions.forEach(option => {
                    option.classList.remove('selected');
                    if (option.getAttribute('data-id') == modeloId) {
                        option.classList.add('selected');
                    }
                });
            } else {
                // Limpiar modelo si no hay
                const modeloSelectInput = document.querySelector('#dropdown-modelo').previousElementSibling;
                const modeloSelectText = modeloSelectInput.querySelector('.select-text');
                const modeloHiddenInput = document.getElementById('modelo_id_hidden');
                
                if (modeloSelectText) modeloSelectText.textContent = 'Seleccionar';
                if (modeloHiddenInput) modeloHiddenInput.value = '';
                
                console.log('Modelo limpiado');
            }
            
            // Actualizar clase selected para activo
            const activoOptions = document.querySelectorAll('#options-activo .option-item');
            activoOptions.forEach(option => {
                option.classList.remove('selected');
                if (option.getAttribute('data-id') == activoId) {
                    option.classList.add('selected');
                }
            });
            
            // Cerrar dropdown
            const dropdown = document.getElementById('dropdown-activo');
            dropdown.classList.remove('show');
            activoSelectInput.classList.remove('active');
            activeDropdown = null;
            
            // Limpiar búsqueda
            const searchInput = dropdown.querySelector('.search-input');
            if (searchInput) {
                searchInput.value = '';
                filterOptions('activo', '');
            }
        }

        // Función para seleccionar opción
        function selectOption(selectId, value, id = null) {
            const selectInput = document.querySelector(`#dropdown-${selectId}`).previousElementSibling;
            const selectText = selectInput.querySelector('.select-text');
            const hiddenInput = document.getElementById(`${selectId}_hidden`) || 
                               document.getElementById(`${selectId}_id_hidden`) ||
                               document.getElementById(`${selectId.replace('s', '')}_id_hidden`);
            
            selectText.textContent = value;
            
            // Actualizar campo hidden
            if (hiddenInput) {
                hiddenInput.value = id !== null && id !== undefined ? id : value;
            }
            
            // Actualizar clase selected
            const options = document.querySelectorAll(`#options-${selectId} .option-item`);
            options.forEach(option => {
                option.classList.remove('selected');
                if (option.textContent.trim().includes(value) || option.getAttribute('data-id') == id) {
                    option.classList.add('selected');
                }
            });
            
            // Cerrar dropdown
            const dropdown = document.getElementById(`dropdown-${selectId}`);
            dropdown.classList.remove('show');
            selectInput.classList.remove('active');
            activeDropdown = null;
            
            // Limpiar búsqueda
            const searchInput = dropdown.querySelector('.search-input');
            if (searchInput) {
                searchInput.value = '';
                filterOptions(selectId, '');
            }

            // Manejar dependencias
            handleDependencies(selectId, id);
        }

        // Función para manejar dependencias entre campos
        function handleDependencies(selectId, selectedId) {
            switch(selectId) {
                case 'clientes':
                    filterProyectosByCliente(selectedId);
                    break;
                case 'categoria':
                    filterSubcategoriasByCategoria(selectedId);
                    break;
                case 'ubicacion':
                    filterAreasByUbicacion(selectedId);
                    break;
                case 'marca':
                    filterModelosByMarca(selectedId);
                    break;
            }
        }

        // Filtrar proyectos por cliente
        function filterProyectosByCliente(clienteId) {
            const optionsContainer = document.getElementById('options-proyectos');
            const options = optionsContainer.querySelectorAll('.option-item');
            
            options.forEach(option => {
                const proyectoClienteId = option.getAttribute('data-cliente');
                if (!clienteId || proyectoClienteId == clienteId) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
        }

        // Filtrar subcategorías por categoría
        function filterSubcategoriasByCategoria(categoriaId) {
            const optionsContainer = document.getElementById('options-subcategoria');
            const options = optionsContainer.querySelectorAll('.option-item');
            
            options.forEach(option => {
                const subcategoriaCategoriaId = option.getAttribute('data-categoria');
                if (!categoriaId || subcategoriaCategoriaId == categoriaId) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });

            // Limpiar selección actual si no es válida
            const hiddenInput = document.getElementById('subcategoria_id_hidden');
            const selectText = document.querySelector('#dropdown-subcategoria').previousElementSibling.querySelector('.select-text');
            if (hiddenInput && hiddenInput.value) {
                const currentOption = optionsContainer.querySelector(`[data-id="${hiddenInput.value}"]`);
                if (currentOption && currentOption.style.display === 'none') {
                    hiddenInput.value = '';
                    selectText.textContent = 'Seleccionar';
                }
            }
        }

        // Filtrar áreas por ubicación
        function filterAreasByUbicacion(ubicacionId) {
            const optionsContainer = document.getElementById('options-area');
            const options = optionsContainer.querySelectorAll('.option-item');
            
            options.forEach(option => {
                const areaUbicacionId = option.getAttribute('data-ubicacion');
                if (!ubicacionId || areaUbicacionId == ubicacionId) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });

            // Limpiar selección actual si no es válida
            const hiddenInput = document.getElementById('area_id_hidden');
            const selectText = document.querySelector('#dropdown-area').previousElementSibling.querySelector('.select-text');
            if (hiddenInput && hiddenInput.value) {
                const currentOption = optionsContainer.querySelector(`[data-id="${hiddenInput.value}"]`);
                if (currentOption && currentOption.style.display === 'none') {
                    hiddenInput.value = '';
                    selectText.textContent = 'Sin Asignar';
                }
            }
        }

        // Filtrar modelos por marca
        function filterModelosByMarca(marcaId) {
            const optionsContainer = document.getElementById('options-modelo');
            const options = optionsContainer.querySelectorAll('.option-item');
            
            console.log('Filtrando modelos por marca:', marcaId);
            
            options.forEach(option => {
                const modeloMarcaId = option.getAttribute('data-marca');
                if (!marcaId || modeloMarcaId == marcaId) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });

            // NO limpiar la selección aquí - lo haremos solo cuando el usuario cambie manualmente la marca
        }

        // Función para filtrar opciones
        function filterOptions(selectId, searchTerm) {
            const optionsContainer = document.getElementById(`options-${selectId}`);
            const options = optionsContainer.querySelectorAll('.option-item');
            let visibleCount = 0;
            
            searchTerm = searchTerm.toLowerCase().trim();
            
            options.forEach(option => {
                const text = option.textContent.toLowerCase();
                let shouldShow = false;
                
                // Verificar si la opción está oculta por dependencias
                const isHiddenByDependency = option.style.display === 'none' && (
                    option.hasAttribute('data-categoria') || 
                    option.hasAttribute('data-ubicacion') || 
                    option.hasAttribute('data-marca') || 
                    option.hasAttribute('data-cliente')
                );
                
                // Si está oculta por dependencias, no mostrarla aunque coincida la búsqueda
                if (isHiddenByDependency) {
                    shouldShow = false;
                } else {
                    // Si no hay término de búsqueda o el texto coincide, mostrar
                    shouldShow = searchTerm === '' || text.includes(searchTerm);
                }
                
                if (shouldShow) {
                    option.style.display = 'block';
                    visibleCount++;
                } else if (!isHiddenByDependency) {
                    // Solo ocultar si no está ya oculta por dependencias
                    option.style.display = 'none';
                }
            });
            
            // Mostrar mensaje si no hay resultados
            let noResultsMsg = optionsContainer.querySelector('.no-options');
            if (visibleCount === 0 && searchTerm !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'no-options';
                    noResultsMsg.textContent = 'No se encontraron opciones';
                    optionsContainer.appendChild(noResultsMsg);
                }
                noResultsMsg.style.display = 'block';
            } else if (noResultsMsg) {
                noResultsMsg.style.display = 'none';
            }
        }

        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.select-container') && activeDropdown) {
                activeDropdown.classList.remove('show');
                activeDropdown.previousElementSibling.classList.remove('active');
                activeDropdown = null;
            }
        });

        // Prevenir que el click en el dropdown lo cierre
        document.addEventListener('click', function(event) {
            if (event.target.closest('.select-dropdown')) {
                event.stopPropagation();
            }
        });

        // Manejar Enter en búsqueda
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && event.target.classList.contains('search-input')) {
                event.preventDefault();
                const dropdown = event.target.closest('.select-dropdown');
                const firstVisibleOption = dropdown.querySelector('.option-item[style*="block"], .option-item:not([style*="none"])');
                if (firstVisibleOption) {
                    firstVisibleOption.click();
                }
            }
        });

        // Validación del formulario
        document.getElementById('correctivoForm').addEventListener('submit', function(event) {
            const titulo = document.getElementById('titulo').value.trim();
            const descripcion = document.getElementById('descripcion').value.trim();
            const solicitante = document.getElementById('solicitante_hidden').value.trim();
            const clienteId = document.getElementById('cliente_id_hidden').value;
            const proyectoId = document.getElementById('proyecto_id_hidden').value;
            const categoriaId = document.getElementById('categoria_id_hidden').value;
            const departamentoId = document.getElementById('departamento_id_hidden').value;
            
            console.log('=== VALORES AL ENVIAR ===');
            console.log('Activo ID:', document.getElementById('activo_id_hidden').value);
            console.log('Marca ID:', document.getElementById('marca_id_hidden').value);
            console.log('Modelo ID:', document.getElementById('modelo_id_hidden').value);
            
            const errores = [];
            
            if (!titulo) errores.push('El título es requerido');
            if (!descripcion) errores.push('La descripción es requerida');
            if (!solicitante) errores.push('Debe especificar un solicitante');
            if (!clienteId) errores.push('Debe seleccionar un cliente');
            if (!proyectoId) errores.push('Debe seleccionar un proyecto');
            if (!categoriaId) errores.push('Debe seleccionar una categoría');
            if (!departamentoId) errores.push('Debe seleccionar un departamento');
            
            if (errores.length > 0) {
                event.preventDefault();
                alert('Por favor corrija los siguientes errores:\n\n• ' + errores.join('\n• '));
                return false;
            }
        });

        // Inicializar dependencias al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Si estamos editando, aplicar filtros iniciales
            <?php if ($is_edit): ?>
                const clienteId = document.getElementById('cliente_id_hidden').value;
                const categoriaId = document.getElementById('categoria_id_hidden').value;
                const ubicacionId = document.getElementById('ubicacion_id_hidden').value;
                const marcaId = document.getElementById('marca_id_hidden').value;
                
                if (clienteId) filterProyectosByCliente(clienteId);
                if (categoriaId) filterSubcategoriasByCategoria(categoriaId);
                if (ubicacionId) filterAreasByUbicacion(ubicacionId);
                if (marcaId) filterModelosByMarca(marcaId);
            <?php endif; ?>
        });
    </script>
<?php include '../includes/footer.php'; ?>
