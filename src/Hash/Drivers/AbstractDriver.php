<?php declare(strict_types=1);

namespace Imhotep\Hash\Drivers;

use RuntimeException;

abstract class AbstractDriver
{
    protected bool $verifyAlgo = false;

    abstract public function name(): string;

    abstract public function algo(): string;

    abstract public function options(array $options): array;

    public function __construct(array $options)
    {
        $this->verifyAlgo = $options['verify'] ?? $this->verifyAlgo;
    }

    public function info(string $hash): array
    {
        return password_get_info($hash);
    }

    public function make(string $value, array $options = []): string
    {
        $hash = @password_hash($value, $this->algo(), $this->options($options));

        if (! is_string($hash)) {
            throw new RuntimeException('Hash driver '.$this->name().' not supported.');
        }

        return $hash;
    }

    public function check(string $value, string $hash, array $options = []): bool
    {
        if ($this->verifyAlgo && $this->info($hash)['algoName'] !== $this->algo()) {
            throw new RuntimeException('This password does not use the '.$this->name().' algorithm.');
        }

        return !empty($hash) && password_verify($value, $hash);
    }

    public function needsRehash(string $hash, array $options = []): bool
    {
        return password_needs_rehash($hash, $this->algo(), $this->options($options));
    }
}