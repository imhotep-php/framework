<?php declare(strict_types=1);

namespace Imhotep\Hash\Drivers;

class BcryptDriver extends AbstractDriver
{
    protected int $cost;

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->cost = $options['cost'] ?? PASSWORD_BCRYPT_DEFAULT_COST;
    }

    public function name(): string
    {
        return 'bcrypt';
    }

    public function algo(): string
    {
        return PASSWORD_BCRYPT;
    }

    public function options(array $options): array
    {
        return [
            'cost' => $this->cost($options),
        ];
    }

    public function cost(array $options = []): int
    {
        return $options['cost'] ?? $this->cost;
    }

    public function setCost(int $cost): static
    {
        $this->cost = $cost;

        return $this;
    }
}