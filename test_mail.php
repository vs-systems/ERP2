<?php
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Mailer.php';
use Vsys\Lib\Mailer;

echo "<h3>Email Connectivity Test</h3>";
try {
    $mailer = new Mailer();
    $to = 'vecinoseguro0@gmail.com';
    $subject = 'Test VS System - Gmail SMTP';
    $body = '<h1>System Check</h1><p>The VS System ERP is now sending emails via Gmail SMTP successfully from the NAS.</p>';

    if ($mailer->send($to, $subject, $body)) {
        echo "<b style='color:green'>SUCCESS:</b> Test email sent to $to. Please check your inbox (and Spam).";
    }
} catch (Exception $e) {
    echo "<b style='color:red'>FAILED:</b> " . $e->getMessage();
    echo "<br><p>Tip: If it says 'Connection timed out', the NAS might have Port 465 blocked by firewall. If it says 'Authentication failed', verify the Gmail password or use an 'App Password'.</p>";
}
