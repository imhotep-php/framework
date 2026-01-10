<?php declare(strict_types=1);

namespace Imhotep\Notifications\Drivers;

use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Contracts\Notifications\INotification;
use Imhotep\Contracts\Notifications\INotificationDriver;
use Imhotep\Notifications\Messages\SmsMessage;
use InvalidArgumentException;
use RuntimeException;

class SmsDriver implements INotificationDriver
{
    protected string $service = '';

    protected string $apiKey = '';

    protected ?string $from = null;

    protected bool $translit = false;

    protected bool $test = false;

    public function __construct(IConfigRepository $config)
    {
        $this->test = $config->bool('translit', false);
        $this->translit = $config->bool('translit', false);
        $this->from = $config->string('from');

        $this->service = $config->stringOrFail('service');

        if ($this->service === 'smsru') {
            $this->apiKey = $config->stringOrFail('api_id');
        }
        elseif ($this->service === 'smsaero') {
            $email = $config->stringOrFail('email');
            $apiKey = $config->stringOrFail('api_key');

            $this->apiKey = "$email:$apiKey";
        }
        else {
            throw new InvalidArgumentException('Invalid sms service, available: smsru');
        }
    }

    public function send(mixed $recipient, INotification $notification): mixed
    {
        if (! method_exists($notification, 'toSms')) {
            throw new RuntimeException("Method [toSms] not exists");
        }

        if (is_null($to = $this->resolveTo($recipient, $notification))) {
            return false;
        }

        $message = $notification->toSms($recipient);

        $from = $this->from ?? $message->from ?? '';

        if ($this->service === 'smsru') {
            return $this->sendToSmsru($to, $message, $from);
        }
        elseif ($this->service === 'smsaero') {
            return $this->sendToSmsaero($to, $message, $from);
        }

        return null;
    }

    public function resolveTo(mixed $recipient, INotification $notification): ?string
    {
        if (is_string($recipient)) {
            return $recipient;
        }
        elseif (method_exists($recipient, 'routeNotificationFor')) {
           return $recipient->routeNotificationFor('sms', $notification);
        }

        return null;
    }

    protected function sendToSmsru(string $to, SmsMessage $message, string $from): mixed
    {
        $data = [
            'from' => $from,
            'to' => $to,
            'text' => $message->text,
            'api_id' => $this->apiKey,
            'partner_id' => '96454',
            'translit' => $this->translit,
            'test' => (int)$this->test,
            'json' => 1,
        ];

        $ch = curl_init("https://sms.ru/sms/send");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        $response = curl_exec($ch);

        $errorMessage = curl_error($ch);
        $errorCode = curl_errno($ch);

        curl_close($ch);

        $json = json_decode($response, false);

        if (json_last_error() === JSON_ERROR_NONE) {
            if ($json->status === 'OK') {
                return $json;
            }

            throw new \Exception("SMSRU ".$json->status_code.": ".$json->status_text, $json->status_code);
        }

        throw new \Exception("CURL ".$errorCode.": ".$errorMessage, $errorCode);
    }

    protected function sendToSmsaero(string $to, SmsMessage $message, string $from): mixed
    {
        $data = [
            'sign' => $from,
            'number' => $to,
            'text' => $message->text,
        ];

        $url = "https://gate.smsaero.ru/v2/sms/send?".http_build_query($data);

        $auth = base64_encode($this->apiKey);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . $auth,
                'Accept: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 30,
        ]);

        $errorMessage = curl_error($ch);
        $errorCode = curl_errno($ch);
        $response = curl_exec($ch);

        $json = json_decode($response, false);

        if (json_last_error() === JSON_ERROR_NONE) {
            if ($json->success === true) {
                return $json;
            }

            throw new \Exception("SMSAERO ".$json->message.' '.json_encode($json->data));
        }

        throw new \Exception("CURL ".$errorCode.": ".$errorMessage, $errorCode);
    }
}