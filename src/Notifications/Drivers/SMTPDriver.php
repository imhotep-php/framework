<?php

namespace Imhotep\Notifications\Drivers;

use Imhotep\Contracts\Notifications\Notification;
use Imhotep\Contracts\Notifications\NotificationException;
use Imhotep\Notifications\Messages\MailMessage;

class SMTPDriver extends AbstractDriver
{
    protected mixed $socket = null;

    protected string $socketData = '';

    protected string $server;

    protected string $port;

    //protected string $domain;

    protected string $login;

    protected string $password;

    protected int $timeout = 5;

    protected object $from;

    protected ?string $error = null;

    protected Notification $notification;

    protected MailMessage $message;

    protected array|string $recipient;

    public function __construct(array $config)
    {
        if (empty($config['server'])) {
            throw new NotificationException("Property [server] is not configured for driver [smtp]");
        }

        if (empty($config['port'])) {
            throw new NotificationException("Property [port] is not configured for driver [smtp]");
        }

        if (empty($config['login'])) {
            throw new NotificationException("Property [login] is not configured for driver [smtp]");
        }

        if (empty($config['password'])) {
            throw new NotificationException("Property [password] is not configured for driver [smtp]");
        }

        if (isset($config['timeout'])) {
            $this->timeout = (int)$config['timeout'];
        }

        $this->server = $config['server'];
        //$this->domain = $config['domain'];
        $this->port = (int)$config['port'];
        $this->login = $config['login'];
        $this->password = $config['password'];

        if (isset($config['from'])) {
            $this->from = (object)$config['from'];
        }
    }

    public function send($recipient, Notification $notification): bool
    {
        if (! method_exists($notification, 'toMail')) {
            throw new NotificationException("Method [toMail] not exists");
        }

        $this->notification = $notification;
        $this->message = $notification->toMail();
        $this->recipient = $recipient;

        if (! $this->_connect()) {
            return $this->_error("Fail connect to socket");
        }

        if ($this->_code() != 220) {
            return $this->_error("CONNECT: ".$this->_data());
        }

        $data = "EHLO {$this->server}";
        if ($this->_send($data) != 250) {
            return $this->_error("EHLO: ".$this->_data());
        }

        if($this->login != '' && $this->password != ''){
            $data = "AUTH LOGIN";
            if ($this->_send($data) != 334) {
                return $this->_error("AUTH LOGIN: ".$this->_data());
            }

            $data = base64_encode($this->login);
            if ($this->_send($data) != 334) {
                return $this->_error("AUTH LOGIN: ".$this->_data());
            }

            $data = base64_encode($this->password);
            if ($this->_send($data) != 235) {
                return $this->_error("AUTH LOGIN: ".$this->_data());
            }
        }

        $data = "MAIL FROM:<".$this->from->mail.">";
        if ($this->_send($data) != 250) {
            return $this->_error("MAIL FROM: ".$this->_data());
        }

        $data = "RCPT TO:<".$recipient.">";
        if (! in_array($this->_send($data), [250, 251])) {
            return $this->_error("RCPT TO: ".$this->_data());
        }

        $data = "DATA";
        if ($this->_send($data) != 354) {
            return $this->_error("DATA: " . $this->_data());
        }

        $data = sprintf("%s\r\n%s\r\n.", $this->getHeaders(), $this->message->toHtml());
        if ($this->_send($data) != 250) {
            return $this->_error("DATA: ".$this->_data());
        }

        $this->_send("QUIT");

        $this->_close();

        return true;
    }

    protected function _connect(): bool
    {
        $this->socket = fsockopen($this->server, $this->port, $errno, $errstr, $this->timeout);

        stream_set_timeout($this->socket, $this->timeout);

        return (bool)$this->socket;
    }

    protected function _send(string $data, string $end = "\r\n"): int
    {
        $this->socketData = '';

        fputs($this->socket, $data.$end);

        return $this->_code();
    }

    protected function _close()
    {
        fclose($this->socket);
    }

    protected function _code(): int
    {
        return (int)substr($this->_data(),0,3);
    }

    protected function _data(): string
    {
        if (! empty($this->socketData)) {
            return $this->socketData;
        }

        while($str = fgets($this->socket,515)){
            $this->socketData.= $str;
            if(substr($str, 3, 1) == " ") break;
        }

        return $this->socketData;
    }

    protected function _error(string $message = null): bool
    {
        if (! is_null($message)) {
            $this->error = $message;
        }

        $this->_close();

        return false;
    }

    protected function getHeaders(): string
    {
        list($user, $domain) = explode('@', $this->from->mail);
        $messageId = $this->notification->id.'@'.$domain;

        $headers = [];
        $headers['From'] = $this->formatAddress($this->from->mail, $this->from->name);

        if (! empty($this->message->replyTo)) {
            $headers['Reply-To'] = $this->formatAddresses($this->message->replyTo);
        }

        $headers['To'] = $this->formatAddress($this->recipient);

        if (! empty($this->message->cc)) {
            $headers['Cc'] = $this->formatAddresses($this->message->cc);
        }

        if (! empty($this->message->bcc)) {
            $headers['Bcc'] = $this->formatAddresses($this->message->bcc);
        }

        $headers['Subject'] = $this->encodeHeader($this->cleanHeader($this->message->subject()));
        $headers['Date'] = date("D, j M Y G:i:s")." +0000";
        $headers['Message-ID'] = $this->formatAddress($messageId);

        $headers['X-Mailer'] = "Imhotep Mailer";
        if ( !is_null($this->message->priority) ) {
            $headers['X-Priority'] = $this->message->priority;
        }

        $headers['MIME-Version'] = "1.0";
        $headers['Content-Type'] = "text/html; charset=utf8";
        $headers['Content-Transfer-Encoding'] = "8bit";

        $result = '';
        foreach ($headers as $key => $val) {
            $result.= "{$key}: {$val}\r\n";
        }

        return $result;
    }

    protected function formatAddresses(array $addresses): string
    {
        $result = [];

        foreach ($addresses as $address) {
            $name = null;

            if (is_array($address)) {
                $name = $address[1];
                $address = $address[0];
            }

            $result[] = $this->formatAddress($address, $name);
        }

        return implode(",", $result);
    }

    protected function formatAddress(string $address, string $name = null): string
    {
        $result = "<{$address}>";

        if (! empty($name)) {
            $result = $this->encodeHeader($name, '') . ' ' . $result;
        }

        return $result;
    }

    protected function cleanHeader(string $string): string
    {
        return trim(str_replace(["\r","\n"], '', $string));
    }

    protected function encodeHeader(string $string): string
    {
        return mb_encode_mimeheader($string, 'utf-8');


        $encoding = 'utf-8';
        $result = '';

        while($length = mb_strlen($string, $encoding)) {
            $result .= "=?{$encoding}?B?"
                . base64_encode(mb_substr($string, 0, 24, $encoding))
                . "?=\r\n";

            $string = mb_substr($string,24, $length,  $encoding);
        }

        return $result;
    }
}