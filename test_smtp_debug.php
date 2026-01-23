<?php
/**
 * SMTP Debug Tool
 */
require_once __DIR__ . '/src/config/config.php';

$host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
$port = defined('SMTP_PORT') ? SMTP_PORT : 465;
$user = defined('SMTP_USER') ? SMTP_USER : '';
$pass = defined('SMTP_PASS') ? SMTP_PASS : '';
$to = 'vecinoseguro0@gmail.com'; // Test to self

echo "<h2>Probando Conexión SMTP</h2>";
echo "Host: $host<br>";
echo "Port: $port<br>";
echo "User: $user<br><br>";

$timeout = 10;
$errno = 0;
$errstr = '';

$socket = fsockopen(($port == 465 ? 'ssl://' : 'tls://') . $host, $port, $errno, $errstr, $timeout);

if (!$socket) {
    echo "<b style='color:red;'>Error al conectar: $errstr ($errno)</b>";
    exit;
}

echo "<b style='color:green;'>Conexión establecida con el socket.</b><br><br>";

function get_response($s)
{
    $res = "";
    while ($line = fgets($s, 515)) {
        $res .= $line;
        echo "<pre style='margin:0; padding:2px; background:#eee;'>S: " . htmlspecialchars($line) . "</pre>";
        if (substr($line, 3, 1) == " ")
            break;
    }
    return $res;
}

function send_cmd($s, $cmd)
{
    echo "<pre style='margin:0; padding:2px; background:#fff; border:1px solid #ccc;'>C: " . htmlspecialchars($cmd) . "</pre>";
    fputs($s, $cmd . "\r\n");
    return get_response($s);
}

get_response($socket);
send_cmd($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
send_cmd($socket, "AUTH LOGIN");
send_cmd($socket, base64_encode($user));
send_cmd($socket, base64_encode($pass));
send_cmd($socket, "MAIL FROM: <$user>");
send_cmd($socket, "RCPT TO: <$to>");
send_cmd($socket, "DATA");
fputs($socket, "Subject: SMTP Debug Test\r\n\r\nEste es un correo de prueba para verificar la configuracion SMTP.\r\n.\r\n");
get_response($socket);
send_cmd($socket, "QUIT");

fclose($socket);

echo "<br><br><b>Prueba finalizada. Si viste 'Authentication failed' o similar, el problema son las credenciales o la 'App Password'.</b>";
