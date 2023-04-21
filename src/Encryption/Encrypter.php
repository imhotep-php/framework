<?php

declare(strict_types=1);

namespace Imhotep\Encryption;

use Imhotep\Contracts\Encryption\DecryptException;
use Imhotep\Contracts\Encryption\Encrypter as EncrypterContract;
use Imhotep\Contracts\Encryption\EncryptException;
use Imhotep\Contracts\Encryption\EncryptionException;
use Throwable;

class Encrypter implements EncrypterContract
{
    /**
     * @throws EncryptionException
     */
    public function __construct(
        protected string $key,
        protected string $cipher = 'aes-128-gcm'
    )
    {
        if (str_starts_with($this->key, 'base64:')) {
            $this->key = base64_decode(substr($this->key, 7));
        }
        elseif (str_starts_with($this->key, 'hex:')) {
            $this->key = hex2bin(substr($this->key, 4));
        }

        $keySize = mb_strlen($this->key, '8bit');
        if (empty($this->key) || ! in_array($keySize, [8,16,32])) {
            throw new EncryptionException("Incorrect key length. Available length: 8, 16, 32 bits.");
        }

        $this->cipher = strtolower($this->cipher);

        if (! in_array($this->cipher, openssl_get_cipher_methods())) {
            throw new EncryptionException("Cipher [".$this->cipher."] not supported. Use for example: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm or any cipher from the method openssl_get_cipher_methods().");
        }
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
            throw new DecryptException("Decrypt: the payload is invalid.");
        }

        $iv = base64_decode($payload['iv']);

        $tag = empty($payload['tag']) ? '' : base64_decode($payload['tag']);

        $value = openssl_decrypt($payload['value'], $this->cipher, $this->key, 0, $iv, $tag);

        if ($value === false) {
            throw new DecryptException('Decrypt: could not decrypt the data.');
        }

        return $unserialize ? unserialize($value) : $value;
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
        if (! (is_array($payload) && isset($payload['iv'], $payload['value'], $payload['tag'], $payload['mac'])) ) {
            return false;
        }

        if ($payload['tag'] === '') {
            if (! hash_equals($this->hash($payload['iv'], $payload['value']), $payload['mac'])) {
                return false;
            }
        }

        return true;
    }

    protected function hash(string $iv, string $value): string
    {
        return hash_hmac('sha256', $iv.$value, $this->key);
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

    public function getKey(): string
    {
        return $this->key;
    }
}