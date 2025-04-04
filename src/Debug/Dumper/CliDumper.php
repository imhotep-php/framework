<?php declare(strict_types=1);

namespace Imhotep\Debug\Dumper;

class CliDumper extends AbstractDumper
{
    public function dump(Data $data)
    {
        $this->write(PHP_EOL.PHP_EOL);
        $this->write($data->dump($this));
        $this->write(PHP_EOL);
    }

    protected function style(string $style, string $value, array $attrs = []): string
    {
        return $value;
    }
}