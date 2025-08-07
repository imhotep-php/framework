<?php declare(strict_types=1);

namespace Imhotep\Debug\Dumper;

use Imhotep\Debug\Data;

class CliDumper extends AbstractDumper
{
    protected static array $styles = [
        'default' => '0',
        'meta' => '1;37',
        'type' => '90',
        'string' => '32',
        'number' => '36',
        'boolean' => '33',
        'null' => '33',
        'const' => '33',
        'property' => '94',
        'visibility' => '90',
        'recursion' => '1;37',
        'uninitialized' => '90',
    ];

    protected int $maxDepth;

    public function __construct($outputStream = null, int $maxDepth = 3)
    {
        parent::__construct($outputStream);
        $this->maxDepth = $maxDepth;
    }

    public function dump(Data $data)
    {
        $this->write(PHP_EOL.PHP_EOL);
        $this->write($this->dumpData($data));
        $this->write(PHP_EOL);
    }

    public function dumpData(Data $data, int $indent = 0)
    {
        $indentStr = str_repeat('  ', $indent);
        $result = '';
        $type = $data['type'];
        $value = $data['value'];

        if ($indent >= $this->maxDepth && in_array($type, ['array', 'object'])) {
            $result = $this->style('meta', '[свернуто, глубина > ' . $this->maxDepth . ']');
            return $result;
        }

        if ($type === 'string') {
            $result = $this->style('string', sprintf('"%s"', $value));
            $result .= ' ' . $this->style('type', 'string(' . $data['length'] . ')');
        }
        elseif ($type === 'integer' || $type === 'double') {
            $result = $this->style('number', (string)$value);
            $result .= ' ' . $this->style('type', $type);
        }
        elseif ($type === 'boolean') {
            $result = $this->style('boolean', $value ? 'true' : 'false');
        }
        elseif ($type === 'NULL') {
            $result = $this->style('null', 'null');
        }
        elseif ($type === 'array') {
            $result  = $this->style('meta', 'array:'.$data['count']);
            $result .= " (\n";
            foreach ($value as $key => $item) {
                $result .= sprintf('%s  [%s] => %s', $indentStr, $key, $this->dumpData($item, $indent+1))."\n";
            }
            $result .= $indentStr.')';
        }
        elseif ($type === 'object') {
            $result  = $this->style('meta', 'object:'.$data['class_name']).'#'.$data['object_id'];
            $result .= " (\n";
            foreach ($value as $item) {
                $result .= $indentStr.'  '.$this->dumpData($item, $indent+1)."\n";
            }
            $result .= $indentStr.')';
        }
        elseif ($type === 'property') {
            $visibility = $this->style('visibility', $data['visibility']);
            $name = $this->style('property', '$'.$data['name']);
            $value = $this->dumpData($data['value'], $indent);
            $result = sprintf('%s %s: %s', $visibility, $name, $value);
        }
        elseif ($type === 'recursion' || $type === 'uninitialized' || $type === 'meta') {
            $result = $this->style($type, (string)$value);
        }
        else {
            $result = (string)$value;
        }

        return $result;
    }

    protected function style(string $style, string $value, array $attrs = []): string
    {
        return sprintf("\x1b[%sm%s\x1b[0m", static::$styles[$style] ?? '0', $value);
    }
}