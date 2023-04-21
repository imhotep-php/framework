<?php

namespace Imhotep\Debug\Dumper;

abstract class AbstractDumper
{
    protected string $output = 'php://output';

    protected $outputStream = null;

    public function __construct()
    {
        $this->setOutput();
    }

    protected function setOutput()
    {
        if (is_string($this->output)) {
            $this->outputStream = fopen($this->output, 'w');
        }
    }

    abstract public function dump(Data $data);

    public function dumpString(string $value, array $attrs = []): string
    {
        $value = $this->style('str', $value, $attrs);
        $type = $this->style('def', ': string ('.$attrs['length'].')');

        return sprintf("%s %s", $value, $type);
    }

    public function dumpScalar(string $type, mixed $value, array $attrs = []): string
    {
        $style = match($type) {
            'integer', 'double' => 'num',
            'boolean', 'NULL' => 'const',
        };

        if (in_array($type, ['integer', 'double'])) {
            $value = (string)$value;
        }
        elseif ($type === 'boolean') {
            $value = ($value) ? 'true' : 'false';
        }
        elseif ($type === 'NULL') {
            $value = 'null';
        }

        $value = $this->style($style, $value, $attrs);
        $type = $this->style('def', ": ".$type);

        return sprintf("%s %s", $value, $type);
    }

    public function dumpArray(array $values, array $attrs = []): string
    {
        return '';
    }


    public function write(string $data): void
    {
        fwrite($this->outputStream, $data);
    }


    public function getType(mixed $var): string
    {
        return gettype($var);
    }

    public function getValue(mixed $var): string
    {
        if (is_string($var)) {
            return $var;
        }

        if (is_int($var)) {
            return (string)$var;
        }

        if (is_bool($var)) {
            return ($var) ? 'true' : 'false';
        }

        if (is_double($var)) {
            return (string)$var;
        }

        return "";
    }

    public function isSimple(string $type): bool
    {
        if (in_array($type, ['string', 'integer', 'boolean', 'double'])) {
            return true;
        }

        return false;
    }

    public function dumpPrimitive(): void
    {

    }
}