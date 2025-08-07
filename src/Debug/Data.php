<?php

namespace Imhotep\Debug;

class Data implements \ArrayAccess
{
    protected string $type;

    protected mixed $value;

    protected array $attrs = [];

    public function __construct(
        protected array $data
    )
    {
        $this->type = $data['type'];
        $this->value = $data['value'];

        $this->parse();
    }

    protected function parse(): void
    {
        if ($this->type === 'string') {
            $this->attrs['length'] = mb_strlen($this->value, 'UTF-8');
        }

        if ($this->type === 'array') {
            $this->attrs['count'] = count($this->value);
        }

        if ($this->type === 'object') {
            $this->attrs['class_name'] = $this->data['class_name'];
            $this->attrs['object_id'] = $this->data['object_id'];
        }
    }

    public function dump(AbstractDumper $dumper): string
    {
        if ($this->type === 'string') {
            return $dumper->dumpString($this->value, $this->attrs);
        }

        if (in_array($this->type, ['integer', 'double', 'boolean', 'NULL', 'empty'])) {
            return $dumper->dumpScalar($this->type, $this->value, $this->attrs);
        }

        if ($this->type === 'array') {
            return $dumper->dumpArray($this->value, $this->attrs);
        }

        if ($this->type === 'object') {
            return $dumper->dumpObject($this->value, $this->attrs);
        }

        if ($this->type === 'property') {
            return $dumper->dumpProperty($this->data['name'], $this->value, $this->data['visibility']);
        }

        return '';
    }

    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset)
    {
        unset($this->data[$offset]);
    }
}