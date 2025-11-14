<?php
// Configuración SMTP Office 365
$smtpHost = 'smtp.office365.com';
$smtpPort = 587;
$username = 'infor@merakierp.com';
$password = 'INFOMERAKI2025';

// Destinatario
$to = 'dvarelae858@gmail.com';
$from = 'infor@merakierp.com';
$subject = 'Prueba SMTP desde PHP Vanilla';
$message = 'Este es un correo de prueba enviado mediante SMTP de Office 365 usando PHP puro.';

// Crear encabezados
$headers = [
    'From' => $from,
    'To' => $to,
    'Subject' => $subject,
    'Content-Type' => 'text/plain; charset=utf-8'
];

// Formatear encabezados
$headersStr = '';
foreach ($headers as $key => $value) {
    $headersStr .= "$key: $value\r\n";
}

// Configuración del socket
$socket = fsockopen($smtpHost, $smtpPort, $errno, $errstr, 30);

if (!$socket) {
    die("Error al conectar: $errno - $errstr");
}

// Leer respuesta inicial
$response = fgets($socket, 4096);
echo "<pre>Respuesta inicial: $response</pre>";

// Comandos SMTP
$commands = [
    "EHLO example.com\r\n",
    "STARTTLS\r\n", // Solo si el servidor lo soporta
    "AUTH LOGIN\r\n",
    base64_encode($username) . "\r\n",
    base64_encode($password) . "\r\n",
    "MAIL FROM: <$from>\r\n",
    "RCPT TO: <$to>\r\n",
    "DATA\r\n",
    "$headersStr\r\n$message\r\n.\r\n",
    "QUIT\r\n"
];

// Ejecutar comandos SMTP
foreach ($commands as $command) {
    fputs($socket, $command);
    $response = fgets($socket, 4096);
    echo "<pre>Comando: " . htmlspecialchars($command) . "Respuesta: $response</pre>";
    
    // Verificar errores en respuestas clave
    if (strpos($response, '535') !== false) {
        die("Error de autenticación. Verifica usuario y contraseña.");
    }
}

fclose($socket);
echo "<p>Proceso de envío completado. Revisa la bandeja de entrada.</p>";
?>