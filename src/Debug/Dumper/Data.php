<?php

namespace Imhotep\Debug\Dumper;

class Data
{
    protected array $data;

    protected string $type;

    protected mixed $value;

    protected array $attrs = [];

    public function __construct($data)
    {
        $this->data = $data;
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
            return $dumper->dumpProperty($this->data['name'], $this->value, $this->data['is_public']);
        }

        return '';
    }
}