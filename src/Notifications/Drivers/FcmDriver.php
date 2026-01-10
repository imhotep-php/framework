<?php declare(strict_types=1);

namespace Imhotep\Notifications\Drivers;

use CurlHandle;
use Imhotep\Contracts\Config\IConfigRepository;
use Imhotep\Contracts\Notifications\INotificationDriver;
use Imhotep\Contracts\Notifications\INotification;
use Imhotep\Notifications\Messages\FcmMessage;
use InvalidArgumentException;
use RuntimeException;

class FcmDriver implements INotificationDriver
{
    protected string $projectId = '';

    protected string $credentialsPath = '';

    protected array $credentials = [];

    protected string $accessToken = '';

    protected int $tokenExpiresAt = 0;

    public function __construct(IConfigRepository $config)
    {
        $this->projectId = $config->stringOrFail('project_id');
        $this->credentialsPath = $config->stringOrFail('credentials_path');
    }

    public function send(mixed $recipient, INotification $notification): bool
    {
        if (!method_exists($notification, 'toFcm')) {
            throw new RuntimeException("Method [toFcm] not exists");
        }

        $tokens = $this->resolveTokens($recipient, $notification);

        /** @todo Else tokens is empty, logging? */
        if (is_null($tokens)) {
            return false;
        }

        $message = $notification->toFcm($recipient);

        if (! $message instanceof FcmMessage) {
            throw new RuntimeException('toFcm() must return an instance of FcmMessage');
        }

        if (is_array($tokens)) {
            return $this->sendMulticast($tokens, $message);
        }

        return $this->sendSingle($tokens, $message);
    }

    protected function resolveTokens(mixed $recipient, INotification $notification): array|string|null
    {
        if (is_string($recipient) && $recipient !== '') {
            return $recipient;
        }

        if (is_array($recipient)) {
            $recipient = array_values(array_filter($recipient, 'is_string'));

            return empty($recipient) ? null : $recipient;
        }

        if (is_object($recipient)) {
            $tokens = null;

            if (method_exists($recipient, 'routeNotificationFor')) {
                $tokens = $recipient->routeNotificationFor('fcm', $notification);
            }
            elseif (method_exists($recipient, 'routeNotificationForFcm')) {
                $tokens = $recipient->routeNotificationForFcm($notification);
            }

            if (is_string($tokens) || is_array($tokens)) {
                return $this->resolveTokens($tokens, $notification);
            }
        }

        return null;
    }

    protected function getAccessToken(): string
    {
        if (! empty($this->accessToken) && $this->tokenExpiresAt > time()) {
            return $this->accessToken;
        }

        $this->validateCredentials();

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->credentials['token_uri'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $this->generateJwt()
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (is_string($response)) {
            $result = json_decode($response, true);

            if (isset($result['access_token'])) {
                $this->tokenExpiresAt = time() + $result['expires_in'] - 10;

                return $this->accessToken = $result['access_token'];
            }
        }

        throw new RuntimeException('Failed to get FCM access token');
    }

    protected function validateCredentials(): void
    {
        if (! empty($this->accessToken)) {
            return;
        }

        if (! file_exists($this->credentialsPath)) {
            throw new InvalidArgumentException(
                sprintf('FCM credentials file not found at [%s]', $this->credentialsPath)
            );
        }

        $this->credentials = json_decode(file_get_contents($this->credentialsPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON in credentials file');
        }

        foreach (['client_email', 'private_key_id', 'private_key', 'token_uri'] as $key) {
            if (! isset($this->credentials[$key])) {
                throw new InvalidArgumentException(
                    sprintf('Missing required key in credentials: %s', $key)
                );
            }
        }
    }

    protected function generateJwt(): string
    {
        $header = base64_encode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => $this->credentials['private_key_id']
        ]));

        $now = time();

        $claims = base64_encode(json_encode([
            'iss' => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $this->credentials['token_uri'],
            'exp' => $now + 3600,
            'iat' => $now
        ]));

        $private_key = $this->credentials['private_key'];

        openssl_sign("$header.$claims", $signature, $private_key, 'SHA256');

        $signature = base64_encode($signature);

        return "$header.$claims.$signature";
    }

    protected function sendMulticast(array $tokens, FcmMessage $message): bool
    {
        $mh = curl_multi_init();
        $handles = [];

        foreach ($tokens as $i => $token) {
            $handles[$i] = $this->createCurlHandle($token, $message);
            curl_multi_add_handle($mh, $handles[$i]);
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        $results = [];
        foreach ($handles as $i => $handle) {
            $results[$i] = curl_multi_getcontent($handle);
            curl_multi_remove_handle($mh, $handle);
            curl_close($handle);
        }

        curl_multi_close($mh);

        $sended = 0;
        foreach ($results as $result) {
            if ($this->getMessageId($result)) {
                $sended++;
            }
        }

        return $sended > 0;
    }

    protected function sendSingle(string $token, FcmMessage $message): bool
    {
        $ch = $this->createCurlHandle($token, $message);

        $messageId = $this->getMessageId(curl_exec($ch));

        curl_close($ch);

        return ! is_null($messageId);
    }

    protected function createCurlHandle(string $token, FcmMessage $message): CurlHandle
    {
        $accessToken = $this->getAccessToken();

        $data = json_encode([
            'message' => [
                'token' => $token,
                ...$message->toArray()
            ]
        ]);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]
        ]);

        return $ch;
    }

    protected function getMessageId(mixed $response): ?string
    {
        if (is_string($response)) {
            $result = json_decode($response, true);

            if (isset($result['name'])) {
                return $result['name'];
            }
        }

        return null;
    }
}