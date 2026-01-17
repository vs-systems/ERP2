<?php
/**
 * VS System ERP - Basic SMTP Mailer
 * Lightweight implementation for Port 465 (SSL) without external dependencies.
 */

namespace Vsys\Lib;

class Mailer
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $from;

    public function __construct()
    {
        // Credentials provided by USER
        $this->host = 'ssl://mail.vecinoseguro.com.ar';
        $this->port = 465;
        $this->user = 'web@vecinoseguro.com.ar';
        $this->pass = 'Andrea1910';
        $this->from = 'web@vecinoseguro.com.ar';
    }

    public function send($to, $subject, $body, $isHtml = true)
    {
        $timeout = 30;
        $socket = fsockopen($this->host, $this->port, $errno, $errstr, $timeout);

        if (!$socket) {
            throw new \Exception("Could not connect to SMTP server: $errstr ($errno)");
        }

        $getResponse = function ($s) {
            $response = "";
            while ($line = fgets($s, 515)) {
                $response .= $line;
                if (substr($line, 3, 1) == " ")
                    break;
            }
            return $response;
        };

        $sendCommand = function ($s, $cmd) use ($getResponse) {
            fputs($s, $cmd . "\r\n");
            return $getResponse($s);
        };

        try {
            $getResponse($socket); // Catch greeting
            $sendCommand($socket, "EHLO " . $_SERVER['SERVER_NAME']);
            $sendCommand($socket, "AUTH LOGIN");
            $sendCommand($socket, base64_encode($this->user));
            $sendCommand($socket, base64_encode($this->pass));
            $sendCommand($socket, "MAIL FROM: <{$this->from}>");
            $sendCommand($socket, "RCPT TO: <{$to}>");
            $sendCommand($socket, "DATA");

            $headers = "To: {$to}\r\n";
            $headers .= "From: VS System <{$this->from}>\r\n";
            $headers .= "Subject: {$subject}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
            $headers .= "Date: " . date('r') . "\r\n";

            fputs($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
            $getResponse($socket);

            $sendCommand($socket, "QUIT");
            fclose($socket);
            return true;
        } catch (\Exception $e) {
            fclose($socket);
            throw $e;
        }
    }
}
