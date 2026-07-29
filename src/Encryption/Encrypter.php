<?php declare(strict_types=1);

namespace Imhotep\Encryption;

use Imhotep\Contracts\Encryption\DecryptException;
use Imhotep\Contracts\Encryption\Encrypter as EncrypterContract;
use Imhotep\Contracts\Encryption\EncryptException;
use Imhotep\Contracts\Encryption\EncryptionException;
use Imhotep\Support\Traits\DeprecatedGetters;
use Imhotep\Support\Traits\Macroable;
use Throwable;

class Encrypter implements EncrypterContract
{
    use DeprecatedGetters, Macroable {
        __call as macroCall;
    }

    protected string $key = '';

    protected array $previousKeys = [];

    protected string $cipher = '';

    /**
     * @throws EncryptionException
     */
    public function __construct(string $key, string $cipher = 'aes-128-gcm', string|array $previousKeys = '')
    {
        $this->key = $this->parseKey($key);

        if (! empty($previousKeys)) {
            if (is_string($previousKeys)) {
                $previousKeys = explode(',', $previousKeys);
            }

            foreach ($previousKeys as $previousKey) {
                $this->previousKeys[] = $this->parseKey($previousKey);
            }
        }

        $this->cipher = strtolower($cipher);

        if (! in_array($this->cipher, openssl_get_cipher_methods())) {
            throw new EncryptionException("Cipher [".$this->cipher."] not supported. Use for example: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm or any cipher from the method openssl_get_cipher_methods().");
        }
    }

    public function key(): string
    {
        return $this->key;
    }

    /**
     * @throws EncryptException
     */
    public function encrypt(mixed $value, bool $serialize = true): string
    {
        try {
            $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        } catch (Throwable $e) {
            throw new EncryptException($e->getMessage(), $e->getCode(), $e);
        }

        $value = openssl_encrypt(
            $serialize ? serialize($value) : (string)$value,
            $this->cipher, $this->key, 0, $iv, $tag
        );

        if ($value === false) {
            throw new EncryptException("Encrypt: could not encrypt the data");
        }

        $iv = base64_encode($iv);
        $tag = base64_encode($tag ?? '');
        $mac = ($tag == '') ? $this->hash($iv, $value) : '';

        $json = json_encode(compact('iv', 'value', 'tag', 'mac'), JSON_UNESCAPED_UNICODE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EncryptException("Encrypt: could not encrypt the data");
        }

        return base64_encode($json);
    }

    /**
     * @throws EncryptException
     */
    public function encryptString(string $value): string
    {
        return $this->encrypt($value, false);
    }

    /**
     * @throws DecryptException
     */
    public function decrypt(string $payload, bool $unserialize = true): mixed
    {
        $payload = json_decode(base64_decode($payload), true);

        if (! $this->isValidPayload($payload)) {
            throw new DecryptException("Decrypt: the payload is invalid");
        }

        $iv = base64_decode($payload['iv']);
        $tag = empty($payload['tag']) ? '' : base64_decode($payload['tag']);

        $keys = [$this->key, ...$this->previousKeys]; $value = false;

        $shouldValidateMac = $this->shouldValidateMac();
        $foundValidMac = false;

        foreach ($keys as $key) {
            if ($shouldValidateMac) {
                if (!$this->validateMac($payload, $key)) {
                    continue; // MAC не совпал - пропускаем этот ключ
                }
                $foundValidMac = true;
            }

            $value = openssl_decrypt($payload['value'], $this->cipher, $key, 0, $iv, $tag);

            if ($value !== false) {
                break;
            }
        }

        if ($value !== false) {
            return $unserialize ? unserialize($value) : $value;
        }

        if ($shouldValidateMac && ! $foundValidMac) {
            throw new DecryptException('Decrypt: The MAC is invalid');
        }

        throw new DecryptException('Decrypt: could not decrypt the data');
    }

    /**
     * @throws DecryptException
     */
    public function decryptString(string $payload): string
    {
        return $this->decrypt($payload, false);
    }

    protected function isValidPayload($payload): bool
    {
        if (!is_array($payload)) {
            return false;
        }

        if (!isset($payload['iv'], $payload['value'], $payload['mac'])) {
            return false;
        }

        if (!is_string($payload['iv']) || !is_string($payload['value']) || !is_string($payload['mac'])) {
            return false;
        }

        if (isset($payload['tag']) && !is_string($payload['tag'])) {
            return false;
        }

        return strlen(base64_decode($payload['iv'], true)) === openssl_cipher_iv_length(strtolower($this->cipher));
    }

    protected function shouldValidateMac(): bool
    {
        return in_array($this->cipher, ['aes-128-cbc','aes-256-cbc']);
    }

    protected function validateMac(array $payload, string $key): bool
    {
        return hash_equals($this->hash($payload['iv'], $payload['value'], $key), $payload['mac']);
    }


    protected function hash(string $iv, string $value, ?string $key = null): string
    {
        return hash_hmac('sha256', $iv.$value, $key ?? $this->key);
    }

    /**
     * @throws EncryptionException
     */
    public static function generateKey(): string
    {
        try {
            return random_bytes(32);
        } catch (Throwable $e) {
            throw new EncryptionException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws EncryptionException
     */
    public static function genKeyBase64(): string
    {
        return "base64:".base64_encode(static::generateKey());
    }

    /**
     * @throws EncryptionException
     */
    public static function genKeyHex(): string
    {
        return "hex:".bin2hex(static::generateKey());
    }

    protected function parseKey(string $key): string
    {
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }
        elseif (str_starts_with($key, 'hex:')) {
            $key = hex2bin(substr($key, 4));
        }

        $keySize = mb_strlen($key, '8bit');
        if (empty($key) || ! in_array($keySize, [8, 16, 24, 32], true)) {
            throw new EncryptionException("Incorrect key length. Available length: 8, 16, 24, 32 bytes.");
        }

        return $key;
    }

    public function __call(string $method, array $parameters): mixed
    {
        if ($result = $this->deprecatedGettersCall($method, $parameters)) {
            return $result;
        }

        return $this->macroCall($method, $parameters);
    }
}