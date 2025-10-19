<?php
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$correo = "soporteit@hospitalsantafepanama.com";
$contrasena = "oypbszazarvhcmqo";
$imap_server = "{imap.gmail.com:993/imap/ssl}INBOX";

$db_host = "localhost";
$db_user = "root";
$db_pass = "Anyuri1527";
$db_name = "ticket_system";

$DEFAULT_CLIENTE_ID = 1;
$DEFAULT_PROYECTO_ID = 3;
$DEFAULT_CATEGORIA_ID = 8;
$DEFAULT_DEPARTAMENTO_ID = 1;

while (true) {
    $mailbox = @imap_open($imap_server, $correo, $contrasena);
    if (!$mailbox) {
        error_log("Error IMAP: " . imap_last_error());
        sleep(10);
        continue;
    }

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        error_log("Error MySQL: " . $conn->connect_error);
        imap_close($mailbox);
        sleep(10);
        continue;
    }

    echo "Conectado a IMAP y MySQL\n";

    while (imap_ping($mailbox)) {
        $emails = imap_search($mailbox, 'UNSEEN');
        
        if ($emails) {
            echo "Encontrados " . count($emails) . " correos nuevos\n";
            
            foreach ($emails as $num) {
                try {
                    echo "\n--- Procesando correo #$num ---\n";
                    
                    $overview = imap_fetch_overview($mailbox, $num, 0)[0];
                    $mensaje = imap_fetchbody($mailbox, $num, 1);

                    $remitente = $overview->from ?? 'Desconocido';
                    $asunto = iconv_mime_decode($overview->subject ?? '(Sin asunto)', 0, "UTF-8");
                    $mensaje_limpio = strip_tags($mensaje);

                    echo "Remitente: $remitente\n";
                    echo "Asunto: $asunto\n";

                    $titulo = $asunto;
                    $descripcion = $mensaje_limpio;
                    $solicitante = $remitente;
                    $cc = null;
                    
                    $cliente_id = $DEFAULT_CLIENTE_ID;
                    $proyecto_id = $DEFAULT_PROYECTO_ID;
                    $categoria_id = $DEFAULT_CATEGORIA_ID;
                    $departamento_id = $DEFAULT_DEPARTAMENTO_ID;
                    $subcategoria_id = null;
                    $ubicacion_id = null;
                    $area_id = null;
                    $proveedor_id = null;
                    $tipo_id = null;
                    $subtipo_id = null;
                    $marca_id = null;
                    $modelo_id = null;
                    $activo_id = null;
                    $prioridad = 'Media';
                    $estado = 'Nuevo';
                    $asignado_a = null;
                    $responsable = null;
                    $contacto = null;
                    $fecha_resolucion = null;
                    $atencion = 'En Sitio';
                    $resolucion = null;
                    $horas_trabajadas = 0;
                    $fuera_servicio = 0;
                    $reporte_servicio = null;
                    $componente = null;

                    $stmt = $conn->prepare("
                        INSERT INTO correctivos (
                            titulo, descripcion, solicitante, cc, cliente_id, proyecto_id, 
                            categoria_id, subcategoria_id, ubicacion_id, area_id, departamento_id, 
                            proveedor_id, tipo_id, subtipo_id, marca_id, modelo_id, activo_id,
                            prioridad, estado, asignado_a, responsable, contacto, fecha_resolucion, 
                            atencion, resolucion, horas_trabajadas, fuera_servicio, 
                            reporte_servicio, componente, fecha_creacion, hora_creacion
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), TIME(NOW()))
                    ");

                    if (!$stmt) {
                        echo "Error preparando: " . $conn->error . "\n";
                        continue;
                    }

                    // Generar cadena de tipos sin caracteres ocultos
                    // ssss (4 strings) + iiiiiiiiiiiiii (13 integers) + ss (2 strings) + ii (2 integers) + ssss (4 strings) + d (1 double) + i (1 integer) + ss (2 strings)
                    $tipos = 'ssss' . 'iiiiiiiiiiiii' . 'ss' . 'ii' . 'ssss' . 'd' . 'i' . 'ss';
                    
                    $stmt->bind_param(
                        $tipos,
                        $titulo,
                        $descripcion,
                        $solicitante,
                        $cc,
                        $cliente_id,
                        $proyecto_id,
                        $categoria_id,
                        $subcategoria_id,
                        $ubicacion_id,
                        $area_id,
                        $departamento_id,
                        $proveedor_id,
                        $tipo_id,
                        $subtipo_id,
                        $marca_id,
                        $modelo_id,
                        $activo_id,
                        $prioridad,
                        $estado,
                        $asignado_a,
                        $responsable,
                        $contacto,
                        $fecha_resolucion,
                        $atencion,
                        $resolucion,
                        $horas_trabajadas,
                        $fuera_servicio,
                        $reporte_servicio,
                        $componente
                    );

                    if ($stmt->execute()) {
                        $nuevo_id = $stmt->insert_id;
                        echo "✅ EXITO! Correctivo #$nuevo_id creado\n";
                        echo "   Titulo: $titulo\n";
                        echo "   Solicitante: $solicitante\n";
                        echo "   Cliente: Hospital Santa Fe\n";
                        echo "   Proyecto: Gestion Interna\n";
                        echo "   Categoria: Sistemas Informaticos\n";
                        echo "   Departamento: SOPORTE IT\n\n";
                    } else {
                        echo "❌ Error ejecutando: " . $stmt->error . "\n";
                    }

                    $stmt->close();
                    imap_setflag_full($mailbox, $num, "\\Seen");
                    echo "Correo marcado como leido\n";
                    
                } catch (Exception $e) {
                    echo "❌ Excepcion: " . $e->getMessage() . "\n";
                }
            }
        }

        sleep(2);
    }

    imap_close($mailbox);
    $conn->close();
    echo "🔄 Reconectando...\n";
    sleep(5);
}
?>