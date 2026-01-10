<?php declare(strict_types = 1);

namespace Imhotep\Notifications\Drivers;

use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Contracts\Notifications\INotificationDriver;
use Imhotep\Contracts\Notifications\INotification;
use Imhotep\Notifications\Messages\MailMessage;
use RuntimeException;

class MailDriver implements INotificationDriver
{
    protected mixed $socket = null;

    protected string $socketData = '';

    protected string $server;

    protected int $port;

    protected string $login;

    protected string $password;

    protected int $timeout;

    protected object $from;

    protected ?string $error = null;

    protected INotification $notification;

    protected MailMessage $message;

    protected mixed $recipient;

    public function __construct(IConfigRepository $config)
    {
        $this->server   = $config->stringOrFail('server');
        $this->port     = $config->intOrFail('port');
        $this->login    = $config->stringOrFail('login');
        $this->password = $config->stringOrFail('password');
        $this->timeout  = $config->int('timeout', 5);
        $this->from     = (object)$config->arrayOrFail('from');
    }

    public function send(mixed $recipient, INotification $notification): bool
    {
        if (! method_exists($notification, 'toMail')) {
            throw new RuntimeException("Method [toMail] not exists");
        }

        $recipientTo = null;

        if (is_string($recipient)) {
            $recipientTo = $recipient;
        }
        elseif (method_exists($recipient, 'routeNotificationFor')) {
            $recipientTo = $recipient->routeNotificationFor('mail', $notification);
        }

        if (is_null($recipientTo)) {
            return false;
        }

        $this->notification = $notification;
        $this->message = $notification->toMail($recipient);
        $this->recipient = $recipient;

        if (! $this->_connect()) {
            return $this->_error("Fail connect to socket");
        }

        if ($this->_code() !== 220) {
            return $this->_error("CONNECT: ".$this->_data());
        }

        $data = "EHLO {$this->server}";
        if ($this->_send($data) !== 250) {
            return $this->_error("EHLO: ".$this->_data());
        }

        if (str_contains($this->_data(), 'STARTTLS')) {
            if ($this->_send("STARTTLS") !== 220) {
                return $this->_error("STARTTLS: ".$this->_data());
            }

            if (! stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                return $this->_error("Failed to enable TLS encryption");
            }

            $data = "EHLO {$this->server}";
            if ($this->_send($data) !== 250) {
                return $this->_error("EHLO after TLS: ".$this->_data());
            }
        }

        if($this->login !== '' && $this->password !== ''){
            $data = "AUTH LOGIN";
            if ($this->_send($data) !== 334) {
                return $this->_error("AUTH LOGIN: " . $this->_data());
            }

            $data = base64_encode($this->login);
            if ($this->_send($data) !== 334) {
                return $this->_error("AUTH LOGIN: " . $this->_data());
            }

            $data = base64_encode($this->password);
            if ($this->_send($data) !== 235) {
                return $this->_error("AUTH LOGIN: " . $this->_data());
            }
        }

        $data = "MAIL FROM:<".$this->from->mail.">";
        if ($this->_send($data) !== 250) {
            return $this->_error("MAIL FROM: ".$this->_data());
        }

        $data = "RCPT TO:<".$recipientTo.">";
        if (! in_array($this->_send($data), [250, 251])) {
            return $this->_error("RCPT TO: ".$this->_data());
        }

        $data = "DATA";
        if ($this->_send($data) !== 354) {
            return $this->_error("DATA: " . $this->_data());
        }

        $data = sprintf("%s\r\n%s\r\n.", $this->getHeaders($recipientTo), $this->message->toHtml());
        if ($this->_send($data) !== 250) {
            return $this->_error("DATA: ".$this->_data());
        }

        $this->_send("QUIT");

        $this->_close();

        return true;
    }

    protected function _connect(): bool
    {
        $hostname = $this->server;

        if ($this->port === 465) {
            $hostname = 'ssl://'.$hostname;
        }

        $this->socket = fsockopen(
            $hostname, $this->port,
            $errno, $errstr,
            $this->timeout
        );

        if (!$this->socket) {
            throw new RuntimeException("Failed to connect to SMTP server: {$errstr} ({$errno})");
        }

        stream_set_timeout($this->socket, $this->timeout);

        return true;
    }

    protected function _send(string $data, string $end = "\r\n"): int
    {
        $this->socketData = '';

        fputs($this->socket, $data.$end);

        return $this->_code();
    }

    protected function _close(): void
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

    protected function getHeaders(string $recipientTo): string
    {
        $exploded = explode('@', $this->from->mail);

        $messageId = $this->notification->id.'@'.$exploded[1];

        $headers = [];
        $headers['From'] = $this->formatAddress($this->from->mail, $this->from->name);

        if (! empty($this->message->replyTo)) {
            $headers['Reply-To'] = $this->formatAddresses($this->message->replyTo);
        }

        $headers['To'] = $recipientTo;

        if (! empty($this->message->cc)) {
            $headers['Cc'] = $this->formatAddresses($this->message->cc);
        }

        if (! empty($this->message->bcc)) {
            $headers['Bcc'] = $this->formatAddresses($this->message->bcc);
        }

        $headers['Subject'] = $this->encodeHeader($this->cleanHeader($this->message->subject()));
        $headers['Date'] = date("D, j M Y G:i:s")." +0000";
        $headers['Message-ID'] = $this->formatAddress($messageId);

        $headers['X-Mailer'] = "Imhotep Notification";
        if ($this->message->priority >= 1 && $this->message->priority <= 5) {
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
            $result = $this->encodeHeader($name) . ' ' . $result;
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
    }
}