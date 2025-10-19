<?php
require_once __DIR__ . '/../vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/../credenciales.json'); // Sube un nivel
$client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
$client->addScope(Google_Service_Gmail::GMAIL_MODIFY);
$client->setAccessType('offline');
$client->setPrompt('consent');

$authUrl = $client->createAuthUrl();
echo "🔗 Abre este enlace en modo incógnito:\n$authUrl\n\n";

echo "📥 Pega el código de autorización aquí: ";
$authCode = trim(fgets(STDIN));

$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

// Verificar si el token tiene refresh_token
if (!isset($accessToken['refresh_token'])) {
    echo "❌ El token generado NO contiene refresh_token. No podrás renovar el acceso automáticamente.\n";
    echo "🔁 Intenta revocar permisos o usar otra cuenta.\n";
    exit;
}

file_put_contents(__DIR__ . '/../token.json', json_encode($accessToken)); // Guardar en raíz
echo "✅ Token guardado correctamente con refresh_token.\n";