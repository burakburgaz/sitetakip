<?php

class SMTP
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $security;
    private $socket;
    private $logs = [];

    public function __construct($host, $port, $user, $pass, $security = 'tls')
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->security = $security;
    }

    private function log($msg)
    {
        $this->logs[] = $msg;
    }

    public function getLogs()
    {
        return $this->logs;
    }

    public function send($to, $subject, $body, $fromEmail, $fromName)
    {
        $host = ($this->security == 'ssl' ? 'ssl://' : '') . $this->host;
        $port = $this->port;

        // Allow self-signed certs and disable verification for local dev
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $this->socket = stream_socket_client($host . ':' . $port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

        if (!$this->socket) {
            $this->log("Connection failed: $errno $errstr");
            return false;
        }

        $this->read(); // Greeting

        if (!$this->cmd("EHLO " . $_SERVER['SERVER_NAME']))
            return false;

        if ($this->security == 'tls') {
            if (!$this->cmd("STARTTLS"))
                return false;

            // Re-apply context for crypto
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            if (!$this->cmd("EHLO " . $_SERVER['SERVER_NAME']))
                return false;
        }

        if (!$this->cmd("AUTH LOGIN"))
            return false;
        if (!$this->cmd(base64_encode($this->user)))
            return false;
        if (!$this->cmd(base64_encode($this->pass)))
            return false;

        if (!$this->cmd("MAIL FROM: <{$this->user}>"))
            return false; // Usually must match auth user
        if (!$this->cmd("RCPT TO: <$to>"))
            return false;

        if (!$this->cmd("DATA"))
            return false;

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$this->user}>\r\n";
        $headers .= "Reply-To: <$fromEmail>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "Date: " . date("r") . "\r\n";

        $msg = $headers . "\r\n" . $body . "\r\n.\r\n";

        fputs($this->socket, $msg);
        $response = $this->read();

        if (substr($response, 0, 3) != '250') {
            $this->log("Error sending data: $response");
            return false;
        }

        $this->cmd("QUIT");
        fclose($this->socket);
        return true;
    }

    private function cmd($command)
    {
        fputs($this->socket, $command . "\r\n");
        $response = $this->read();
        $code = substr($response, 0, 3);
        // Accept 2xx and 3xx codes
        if (strlen($code) < 1 || ($code[0] != '2' && $code[0] != '3')) {
            $this->log("Command failed: $command -> $response");
            return false;
        }
        return true;
    }

    private function read()
    {
        $response = "";
        while ($str = fgets($this->socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ")
                break;
        }
        $this->log("Server: $response");
        return $response;
    }
}
