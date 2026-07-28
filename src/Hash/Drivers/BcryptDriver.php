<?php declare(strict_types=1);

namespace Imhotep\Hash\Drivers;

use InvalidArgumentException;

class BcryptDriver extends AbstractDriver
{
    protected int $cost;

    protected int $limit = 0;

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->cost = $options['cost'] ?? PASSWORD_BCRYPT_DEFAULT_COST;

        if (!empty($options['limit']) && is_numeric($options['limit']) && (int)$options['limit'] > 0) {
            $this->limit = (int)$options['limit'];
        }
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
        return $options['rounds'] ?? $options['cost'] ?? $this->cost;
    }

    public function setCost(int $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function limit(): ?int
    {
        return $this->limit > 0 ? $this->limit : null;
    }

    public function setLimit(?int $limit): static
    {
        $this->limit = ($limit && $limit > 0) ? $limit : 0;

        return $this;
    }

    public function make(string $value, array $options = []): string
    {
        if ($this->limit > 0 && strlen($value) > $this->limit) {
            throw new InvalidArgumentException('Value is too long to hash. Value must be less than '.$this->limit.' bytes.');
        }

        return parent::make($value, $options);
    }
}