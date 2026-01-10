<?php declare(strict_types=1);

namespace Imhotep\Database\Model;

trait HasPrimaryKey
{
    protected string $keyColumn = 'id';

    protected string $keyType = 'int';

    public function getKeyColumn(): string
    {
        return $this->keyColumn;
    }

    public function getKeyType(): string
    {
        return $this->keyType;
    }

    public function getKey(): mixed
    {
        return $this->getAttribute($this->keyColumn);
    }

    public function keyName(): string
    {
        return $this->keyColumn;
    }

    public function key(): mixed
    {
        return $this->getKey();
    }
}