<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/conexion.php';

// Configurar cliente Gmail
$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/../credenciales.json');
$client->addScope(Google_Service_Gmail::GMAIL_MODIFY);
$client->setAccessType('offline');

// Cargar token de acceso
$tokenPath = __DIR__ . '/../token.json';
if (!file_exists($tokenPath)) {
    die("❌ Error: No existe el archivo token.json. Ejecuta auth_gmail.php primero.\n");
}

$client->setAccessToken(json_decode(file_get_contents($tokenPath), true));

// Refrescar token si está vencido
if ($client->isAccessTokenExpired()) {
    if ($client->getRefreshToken()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    } else {
        die("❌ Error: Token vencido y sin refresh_token. Ejecuta auth_gmail.php nuevamente.\n");
    }
}

$service = new Google_Service_Gmail($client);

// ========================================
// CONFIGURACIÓN: IDs basados en tu base de datos
// ========================================
$DEFAULTS = [
    'cliente_id' => 1,      // Hospital Santa Fe
    'proyecto_id' => 3,     // Gestión Interna - HOSPITAL SANTA FE - HSF
    'categoria_id' => 2,    // Consulta/Solicitudes (ideal para emails)
    'departamento_id' => 1, // SOPORTE IT
    'prioridad' => 'Media',
    'estado' => 'Nuevo'
];

echo "🔍 Buscando correos nuevos...\n";

// Leer últimos 10 correos no leídos
$optParams = ['maxResults' => 10, 'q' => 'is:unread'];

try {
    $messages = $service->users_messages->listUsersMessages('me', $optParams);
} catch (Exception $e) {
    die("❌ Error al conectar con Gmail: " . $e->getMessage() . "\n");
}

if (!$messages->getMessages()) {
    echo "📭 No hay correos nuevos.\n";
    exit(0);
}

$procesados = 0;
$errores = 0;

foreach ($messages->getMessages() as $message) {
    try {
        $msg = $service->users_messages->get('me', $message->getId());

        // Obtener headers del correo
        $headers = $msg->getPayload()->getHeaders();
        $from = $subject = $date = '';
        
        foreach ($headers as $header) {
            if ($header->name === 'From') $from = $header->value;
            if ($header->name === 'Subject') $subject = $header->value;
            if ($header->name === 'Date') $date = $header->value;
        }

        // Extraer solo el email del remitente
        $emailClean = $from;
        if (preg_match('/<(.+?)>/', $from, $matches)) {
            $emailClean = $matches[1];
        }

        // Validar que tenga asunto (campo obligatorio)
        if (empty($subject)) {
            echo "⚠️  Correo sin asunto ignorado de: $from\n";
            continue;
        }

        // Verificar duplicados
        $stmt = $pdo->prepare("SELECT id FROM correctivos WHERE contacto = ? AND titulo = ? LIMIT 1");
        $stmt->execute([$emailClean, $subject]);
        
        if ($stmt->rowCount() > 0) {
            echo "⏭️  Duplicado ignorado: \"$subject\"\n";
            // Marcar como leído aunque sea duplicado
            $service->users_messages->modify('me', $message->getId(), 
                new Google_Service_Gmail_ModifyMessageRequest([
                    'removeLabelIds' => ['UNREAD']
                ])
            );
            continue;
        }

        // Obtener cuerpo del mensaje
        $body = '';
        $payload = $msg->getPayload();
        
        // Intentar obtener texto plano
        if ($payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                if ($part->mimeType === 'text/plain') {
                    $data = $part->getBody()->getData();
                    if ($data) {
                        $body = base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
                    }
                    break;
                }
            }
        } elseif ($payload->getBody()->getData()) {
            $body = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload->getBody()->getData()));
        }

        // Si no hay texto plano, buscar HTML
        if (empty($body) && $payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                if ($part->mimeType === 'text/html') {
                    $data = $part->getBody()->getData();
                    if ($data) {
                        $bodyHtml = base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
                        $body = strip_tags($bodyHtml);
                    }
                    break;
                }
            }
        }

        // Validar que tenga contenido
        $body = trim($body);
        if (empty($body)) {
            $body = '[Correo recibido sin contenido de texto]';
        }

        // Limitar longitud del cuerpo si es muy largo
        if (strlen($body) > 5000) {
            $body = substr($body, 0, 5000) . "\n\n[Contenido truncado por longitud]";
        }

        // Insertar nuevo correctivo
        $stmt = $pdo->prepare("
            INSERT INTO correctivos (
                titulo, descripcion, solicitante, contacto, 
                estado, prioridad, 
                cliente_id, proyecto_id, categoria_id, departamento_id, 
                fecha_creacion, hora_creacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), TIME(NOW()))
        ");
        
        $stmt->execute([
            $subject,                       // título (asunto del correo)
            $body,                          // descripción (cuerpo del correo)
            $from,                          // solicitante (nombre + email)
            $emailClean,                    // contacto (solo email)
            $DEFAULTS['estado'],            // estado: Nuevo
            $DEFAULTS['prioridad'],         // prioridad: Media
            $DEFAULTS['cliente_id'],        // cliente_id: 1 (Hospital Santa Fe)
            $DEFAULTS['proyecto_id'],       // proyecto_id: 3 (Gestión Interna)
            $DEFAULTS['categoria_id'],      // categoria_id: 2 (Consulta/Solicitudes)
            $DEFAULTS['departamento_id']    // departamento_id: 1 (SOPORTE IT)
        ]);

        $nuevo_id = $pdo->lastInsertId();
        
        // Registrar en bitácora si existe la función
        if (function_exists('registrarBitacora')) {
            registrarBitacora('Sistema Gmail', 'Correctivos', "Correctivo #$nuevo_id creado desde email: $subject");
        }
        
        // Marcar correo como leído
        $service->users_messages->modify('me', $message->getId(), 
            new Google_Service_Gmail_ModifyMessageRequest([
                'removeLabelIds' => ['UNREAD']
            ])
        );

        echo "✅ Correctivo #$nuevo_id creado: \"$subject\"\n";
        echo "   De: $from\n";
        $procesados++;

    } catch (PDOException $e) {
        echo "❌ Error BD: " . $e->getMessage() . "\n";
        $errores++;
    } catch (Exception $e) {
        echo "❌ Error procesando correo: " . $e->getMessage() . "\n";
        $errores++;
    }
}

echo "\n📊 Resumen:\n";
echo "   ✅ Correctivos creados: $procesados\n";
if ($errores > 0) {
    echo "   ❌ Errores: $errores\n";
}
echo "   🕒 " . date('Y-m-d H:i:s') . "\n";