<?php

namespace Imhotep\Contracts\Encryption;

interface Encrypter
{
    public function encrypt(mixed $value, bool $serialize = true): string;

    public function decrypt(string $payload, bool $unserialize = true): mixed;

    public static function generateKey(): string;

    public function getKey(): string;
}