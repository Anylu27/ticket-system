<?php
// Script para importar ACTIVOS.csv a la base de datos ticket_system
// Configuración de la base de datos
$config = [
    'host' => 'localhost',
    'dbname' => 'ticket_system',
    'username' => 'root',
    'password' => 'Anyuri1527',
    'charset' => 'utf8mb4'
];

// Configuración del archivo CSV
$archivo_csv = 'C:\xampp\mysql\data\Activos.csv';
$csv_config = [
    'delimiter' => ',',           // Separador del CSV (puede ser ; en algunos casos)
    'enclosure' => '"',           // Carácter de encierro
    'escape' => '\\',             // Carácter de escape
    'skip_first_row' => true,     // Si tiene encabezados
    'encoding' => 'UTF-8'         // Codificación del archivo
];

// Función para conectar a la base de datos
function conectarDB($config) {
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage() . "\n");
    }
}

// Función para detectar el delimitador del CSV
function detectarDelimitador($archivo) {
    $delimitadores = [',', ';', '\t', '|'];
    $primera_linea = '';
    
    if (($handle = fopen($archivo, "r")) !== FALSE) {
        $primera_linea = fgets($handle);
        fclose($handle);
    }
    
    $max_campos = 0;
    $mejor_delimitador = ',';
    
    foreach ($delimitadores as $delim) {
        $campos = explode($delim, $primera_linea);
        if (count($campos) > $max_campos) {
            $max_campos = count($campos);
            $mejor_delimitador = $delim;
        }
    }
    
    return $mejor_delimitador;
}

// Función para leer y procesar CSV
function procesarCSV($archivo, $csv_config) {
    if (!file_exists($archivo)) {
        throw new Exception("El archivo CSV no existe: $archivo");
    }

    // Detectar delimitador automáticamente
    $delimitador_detectado = detectarDelimitador($archivo);
    echo "Delimitador detectado: '$delimitador_detectado'\n";

    $datos = [];
    $encabezados = [];
    $fila_numero = 0;
    
    if (($handle = fopen($archivo, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, $delimitador_detectado, $csv_config['enclosure'], $csv_config['escape'])) !== FALSE) {
            $fila_numero++;
            
            // Guardar encabezados de la primera fila
            if ($fila_numero == 1) {
                $encabezados = array_map('trim', $data);
                echo "Encabezados encontrados: " . implode(', ', $encabezados) . "\n";
                
                if ($csv_config['skip_first_row']) {
                    continue;
                }
            }
            
            // Limpiar datos
            $data = array_map('trim', $data);
            
            // Crear array asociativo con los encabezados
            if (!empty($encabezados)) {
                $fila_asociativa = [];
                for ($i = 0; $i < count($encabezados) && $i < count($data); $i++) {
                    $fila_asociativa[$encabezados[$i]] = $data[$i];
                }
                $datos[] = $fila_asociativa;
            } else {
                $datos[] = $data;
            }
        }
        fclose($handle);
    }
    
    return [$datos, $encabezados];
}

// Función para buscar ID por nombre en tablas relacionadas
function buscarIdPorNombre($pdo, $tabla, $nombre, $campo_nombre = 'nombre') {
    if (empty($nombre)) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM $tabla WHERE $campo_nombre = ? LIMIT 1");
        $stmt->execute([$nombre]);
        $resultado = $stmt->fetch();
        return $resultado ? $resultado['id'] : null;
    } catch (PDOException $e) {
        echo "Error buscando en $tabla: " . $e->getMessage() . "\n";
        return null;
    }
}

// Función para crear registros faltantes en tablas relacionadas
function crearRegistroSiNoExiste($pdo, $tabla, $nombre, $campo_nombre = 'nombre') {
    if (empty($nombre)) return null;
    
    // Primero buscar si existe
    $id = buscarIdPorNombre($pdo, $tabla, $nombre, $campo_nombre);
    if ($id) return $id;
    
    // Si no existe, crearlo
    try {
        $campos_adicionales = '';
        $valores_adicionales = '';
        
        // Agregar campos por defecto según la tabla
        switch ($tabla) {
            case 'marcas':
            case 'modelos':
            case 'ubicaciones':
            case 'areas':
                $campos_adicionales = ', activo';
                $valores_adicionales = ', 1';
                break;
        }
        
        $stmt = $pdo->prepare("INSERT INTO $tabla ($campo_nombre$campos_adicionales) VALUES (?$valores_adicionales)");
        $stmt->execute([$nombre]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        echo "Error creando registro en $tabla: " . $e->getMessage() . "\n";
        return null;
    }
}

// Función principal de importación
function importarActivos($archivo_csv, $config, $csv_config) {
    echo "=== IMPORTACIÓN DE ACTIVOS ===\n";
    echo "Archivo: $archivo_csv\n";
    echo "Base de datos: {$config['dbname']}\n";
    echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";
    
    try {
        // Conectar a la base de datos
        $pdo = conectarDB($config);
        echo "✓ Conexión a la base de datos establecida\n";
        
        // Leer CSV
        list($datos, $encabezados) = procesarCSV($archivo_csv, $csv_config);
        $total_filas = count($datos);
        echo "✓ CSV leído correctamente. Total de filas: $total_filas\n\n";
        
        if ($total_filas == 0) {
            echo "⚠️  No se encontraron datos para importar\n";
            return;
        }
        
        // Mostrar una muestra de los datos
        echo "=== MUESTRA DE DATOS ===\n";
        if (!empty($datos)) {
            $primera_fila = $datos[0];
            foreach ($primera_fila as $campo => $valor) {
                echo "$campo: $valor\n";
            }
        }
        echo "\n¿Desea continuar con la importación? (s/n): ";
        $respuesta = trim(fgets(STDIN));
        if (strtolower($respuesta) !== 's') {
            echo "Importación cancelada\n";
            return;
        }
        
        // Contadores
        $insertados = 0;
        $errores = [];
        
        // Iniciar transacción
        $pdo->beginTransaction();
        
        foreach ($datos as $indice => $fila) {
            $numero_fila = $indice + 1;
            
            try {
                // Mapear campos del CSV a campos de la tabla activos
                // Ajusta estos nombres según los encabezados de tu CSV
                $serial = $fila['serial'] ?? $fila['Serial'] ?? $fila['SERIAL'] ?? '';
                $nombre = $fila['nombre'] ?? $fila['Nombre'] ?? $fila['NOMBRE'] ?? '';
                $marca_nombre = $fila['marca'] ?? $fila['Marca'] ?? $fila['MARCA'] ?? '';
                $modelo_nombre = $fila['modelo'] ?? $fila['Modelo'] ?? $fila['MODELO'] ?? '';
                $ubicacion_nombre = $fila['ubicacion'] ?? $fila['Ubicacion'] ?? $fila['UBICACION'] ?? '';
                $area_nombre = $fila['area'] ?? $fila['Area'] ?? $fila['AREA'] ?? '';
                $estado = $fila['estado'] ?? $fila['Estado'] ?? $fila['ESTADO'] ?? 'ACTIVO';
                
                // Validaciones básicas
                if (empty($serial)) {
                    $errores[] = "Fila $numero_fila: Serial requerido";
                    continue;
                }
                
                if (empty($nombre)) {
                    $errores[] = "Fila $numero_fila: Nombre requerido";
                    continue;
                }
                
                // Buscar o crear IDs de tablas relacionadas
                $marca_id = null;
                if (!empty($marca_nombre)) {
                    $marca_id = crearRegistroSiNoExiste($pdo, 'marcas', $marca_nombre);
                }
                
                $modelo_id = null;
                if (!empty($modelo_nombre)) {
                    $modelo_id = crearRegistroSiNoExiste($pdo, 'modelos', $modelo_nombre);
                }
                
                $ubicacion_id = null;
                if (!empty($ubicacion_nombre)) {
                    $ubicacion_id = crearRegistroSiNoExiste($pdo, 'ubicaciones', $ubicacion_nombre);
                }
                
                $area_id = null;
                if (!empty($area_nombre)) {
                    $area_id = crearRegistroSiNoExiste($pdo, 'areas', $area_nombre);
                }
                
                // Verificar si el activo ya existe
                $stmt_check = $pdo->prepare("SELECT id FROM activos WHERE serial = ?");
                $stmt_check->execute([$serial]);
                $existe = $stmt_check->fetch();
                
                if ($existe) {
                    // Actualizar activo existente
                    $stmt_update = $pdo->prepare("
                        UPDATE activos 
                        SET nombre = ?, marca_id = ?, modelo_id = ?, ubicacion_id = ?, 
                            area_id = ?, estado_activo = ?, fecha_actualizacion = NOW()
                        WHERE serial = ?
                    ");
                    $stmt_update->execute([
                        $nombre, $marca_id, $modelo_id, $ubicacion_id, 
                        $area_id, $estado, $serial
                    ]);
                    echo "Actualizado: $serial - $nombre\n";
                } else {
                    // Insertar nuevo activo
                    $stmt_insert = $pdo->prepare("
                        INSERT INTO activos 
                        (serial, nombre, marca_id, modelo_id, ubicacion_id, area_id, 
                         estado_activo, fecha_creacion, fecha_actualizacion) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt_insert->execute([
                        $serial, $nombre, $marca_id, $modelo_id, $ubicacion_id, 
                        $area_id, $estado
                    ]);
                    echo "Insertado: $serial - $nombre\n";
                }
                
                $insertados++;
                
            } catch (PDOException $e) {
                $errores[] = "Fila $numero_fila: Error de base de datos - " . $e->getMessage();
                echo "Error en fila $numero_fila: " . $e->getMessage() . "\n";
            } catch (Exception $e) {
                $errores[] = "Fila $numero_fila: " . $e->getMessage();
                echo "Error en fila $numero_fila: " . $e->getMessage() . "\n";
            }
        }
        
        // Confirmar transacción
        $pdo->commit();
        
        // Resumen final
        echo "\n=== RESUMEN DE IMPORTACIÓN ===\n";
        echo "Total de filas procesadas: $total_filas\n";
        echo "Registros procesados exitosamente: $insertados\n";
        echo "Errores encontrados: " . count($errores) . "\n";
        
        if (!empty($errores)) {
            echo "\n=== ERRORES ENCONTRADOS ===\n";
            foreach ($errores as $error) {
                echo "• $error\n";
            }
        }
        
        echo "\n✓ Importación completada\n";
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        echo "Error fatal: " . $e->getMessage() . "\n";
    }
}

// Verificar que el archivo existe antes de ejecutar
if (!file_exists($archivo_csv)) {
    echo "Error: El archivo no existe en la ruta especificada: $archivo_csv\n";
    echo "Verifique que:\n";
    echo "1. El archivo existe\n";
    echo "2. La ruta es correcta\n";
    echo "3. Tiene permisos de lectura\n";
    exit(1);
}

// Ejecutar la importación
importarActivos($archivo_csv, $config, $csv_config);

?>

<!-- 
INSTRUCCIONES DE USO:

1. Guarda este código en un archivo PHP (ej: importar_activos.php)

2. Ejecuta desde línea de comandos:
   php importar_activos.php

3. El script:
   - Detecta automáticamente el delimitador del CSV
   - Muestra los encabezados encontrados
   - Te pide confirmación antes de importar
   - Crea automáticamente marcas, modelos, ubicaciones y áreas si no existen
   - Actualiza activos existentes o inserta nuevos según el serial

4. Campos esperados en el CSV (ajusta según tu archivo):
   - serial (obligatorio)
   - nombre (obligatorio) 
   - marca (opcional)
   - modelo (opcional)
   - ubicacion (opcional)
   - area (opcional)
   - estado (opcional, por defecto 'ACTIVO')

5. Si tu CSV tiene nombres de columnas diferentes, modifica las líneas:
   $serial = $fila['serial'] ?? $fila['Serial'] ?? $fila['SERIAL'] ?? '';
   
   Agrega las variaciones de nombres que uses.
-->